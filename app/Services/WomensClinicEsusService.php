<?php

namespace App\Services;

use App\Models\WomensClinicAppointment;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Thrift\Transport\TMemoryBuffer;
use Thrift\Protocol\TBinaryProtocolAccelerated;

// Importação das Classes Thrift Geradas do PEC
use CidadaoTransportThrift;
use SexoThrift;
use FichaAtendimentoIndividualChildThrift;
use FichaAtendimentoIndividualMasterThrift;
use DadoTransporteThrift;
use VariasLotacoesHeaderThrift;
use LotacaoHeaderThrift;
use DadoInstalacaoThrift;

class WomensClinicEsusService
{
    /**
     * O Pulo do Gato: Transforma o agendamento salvo no DB para o Schema Thrift 
     * e o serializa para ser ingerido no banco do e-SUS PEC local.
     */
    public function syncAppointmentToEsus(WomensClinicAppointment $appointment, $cnsProfissional, $cbo, $cnes)
    {
        DB::beginTransaction();
        try {
            // 1. Mapeamento dos Dados (DB -> Thrift) Identificação do Cidadão
            $cidadaoThrift = new CidadaoTransportThrift([
                'cns' => $appointment->cns,
                'cpf' => $appointment->cpf,
                'dataNascimento' => Carbon::parse($appointment->data_nascimento)->getTimestampMs(),
                'sexo' => $appointment->sexo == 1 ? SexoThrift::FEMININO : SexoThrift::MASCULINO,
                'nomeCompleto' => $appointment->nome_completo,
                'naoPossuiCns' => empty($appointment->cns),
                'desconheceNomeMae' => true, // Hardcoded para simplificar, mas idealmente vem do forms
                'estrangeiro' => false,
                'racaCorId' => 6, // 6 = Sem Informação
                'municipioNascimentoCep' => '00000000',
                'municipioNascimentoDne' => '00000',
            ]);

            // 2. Montar a Ficha Atendimento Child (Motivo/Clínica da Mulher)
            $fichaChild = new FichaAtendimentoIndividualChildThrift([
                'dataHoraInicialAtendimento' => Carbon::now()->getTimestampMs(),
                'dataHoraFinalAtendimento' => Carbon::now()->addMinutes(15)->getTimestampMs(),
                'cpfCidadao' => $appointment->cpf,
                'cnsCidadao' => $appointment->cns,
                'turno' => $appointment->turno, // 1=Manhã, 2=Tarde, 3=Noite
                'tipoAtendimento' => $appointment->tipo_atendimento, 
                // Campos Específicos Mulher
                'dumDaGestante' => $appointment->dum ? Carbon::parse($appointment->dum)->getTimestampMs() : null,
                'idadeGestacional' => $appointment->idade_gestacional,
                'stGravidezPlanejada' => $appointment->st_gravidez_planejada,
                'nuGestasPrevias' => $appointment->nu_gestas_previas,
                'nuPartos' => $appointment->nu_partos,
                'condutas' => [1], // 1 = Alta do Episódio
            ]);

            // 3. Montar a Ficha Master e o Cabeçalho
            $lotacaoObj = new LotacaoHeaderThrift([
                'profissionalCNS' => $cnsProfissional,
                'cboCodigo_2002' => $cbo,
                'cnes' => $cnes,
                // 'ine' -> Opcional
            ]);

            $header = new VariasLotacoesHeaderThrift([
                'lotacaoFormPrincipal' => $lotacaoObj,
                'dataAtendimento' => Carbon::now()->getTimestampMs(),
                'codigoIbgeMunicipio' => '4101908', // Assaí - PR
            ]);

            $fichaMaster = new FichaAtendimentoIndividualMasterThrift([
                'uuidFicha' => $cnes . '-' . Str::uuid()->toString(),
                'tpCdsOrigem' => 5, // Origem Integrado/Confiança
                'headerTransport' => $header,
                'atendimentosIndividuais' => [$fichaChild]
            ]);

            // 4. Montar DadoTransporteThrift Master (O Wrapper)
            $dadoTransporte = new DadoTransporteThrift([
                'uuidDadoSerializado' => $fichaMaster->uuidFicha,
                'tipoDadoSerializado' => 4, // 4 = Ficha Atendimento Individual
                'cnesDadoSerializado' => $cnes,
                'codIbge' => '4101908', // Assaí
                'remetente' => new DadoInstalacaoThrift([
                    'contraChave' => 'ClinicaMulher Assaí - 1.0',
                    'uuidInstalacao' => 'SAUDE-ASSAI-'.Str::uuid()->toString(),
                    'cpfOuCnpj' => '00000000000000', // CNPJ Gov
                    'nomeOuRazaoSocial' => 'PREFEITURA DE ASSAI'
                ]),
                // Serializando a ficha master dentro do DadoTransporte
                'dadoSerializado' => $this->serializeBinary($fichaMaster), 
            ]);

            // 5. O Passo Final: Banco de Dados PostgreSQL do PEC (Thrift Pulo do Gato)
            $binaryPayload = $this->serializeBinary($dadoTransporte);
            
            DB::connection('pec')->table('tb_dado_transp')->insert([
                'co_seq_dado_transp' => Str::uuid()->toString(), // ou sequence/max se não for uuid no pec
                'co_dado_transp_recebido' => null,
                'st_dado_validado' => 0,
                'bl_dado_serializado' => DB::connection('pec')->raw("decode('".bin2hex($binaryPayload)."', 'hex')"),
            ]);

            $appointment->update(['uuid_transporte' => $fichaMaster->uuidFicha]);

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            // Logar falha de auditoria
            activity('esus_integration')
                ->causedBy(auth()->user())
                ->log('Falha na serialização Thrift na Clínica Mulher: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Serializador Apache Thrift (Serializa o Objeto PHP para TBinaryProtocol)
     */
    private function serializeBinary($thriftObject)
    {
        $buffer = new TMemoryBuffer();
        $protocol = new TBinaryProtocolAccelerated($buffer);
        $thriftObject->write($protocol);
        return $buffer->getBuffer();
    }
}