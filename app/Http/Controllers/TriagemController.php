<?php

namespace App\Http\Controllers;

use App\Models\TriagemPaciente;
use Illuminate\Http\Request;

class TriagemController extends Controller
{
    /**
     * Exibe a tela de busca de cidadãos filtrando duplicidades históricos
     */
    public function cidadao(Request $request)
    {
        $busca = $request->input('busca');
        $dataNasc = $request->input('data_nascimento');
        $nomeMae = $request->input('nome_mae');
        $municipioNasc = $request->input('municipio_nascimento');

        $query = TriagemPaciente::query();

        // 1. Filtros de Busca
        if ($busca) {
            // Remove pontos e traços caso o usuário digite o CPF formatado
            $buscaLimpa = preg_replace('/\D/', '', $busca);

            $query->where(function ($q) use ($busca, $buscaLimpa) {
                $q->where('full_name', 'like', "%{$busca}%")
                    ->orWhere('cns', 'like', "%{$busca}%");

                if (!empty($buscaLimpa)) {
                    $q->orWhere('cpf', 'like', "%{$buscaLimpa}%");
                }
            });
        }

        if ($dataNasc) {
            $query->whereDate('birth_date', $dataNasc);
        }

        if ($nomeMae) {
            $query->where('nome_mae', 'like', "%{$nomeMae}%");
        }

        if ($municipioNasc) {
            $query->where('municipio_nascimento', 'like', "%{$municipioNasc}%");
        }

        // 2. SOLUÇÃO DA DUPLICIDADE (Agrupamento por Cidadão)
        // Seleciona apenas o ID mais recente (MAX) de cada CPF para não duplicar históricos na busca
        $query->whereIn('id', function ($subquery) {
            $subquery->selectRaw('MAX(id)')
                ->from('triagem_pacientes')
                ->whereNotNull('cpf')
                ->groupBy('cpf');
        });

        // 3. Ordenação por prioridade de atendimento técnico
        $resultados = $query->orderByRaw("FIELD(status, 'EM_TRIAGEM', 'AGUARDANDO', 'FINALIZADO') ASC")
            ->orderBy('created_at', 'desc')
            ->get();

        return view('triagem.cidadao', compact('resultados', 'busca', 'dataNasc', 'nomeMae', 'municipioNasc'));
    }

    /**
     * Altera o status de um paciente que já estava AGUARDANDO na fila
     */
    public function iniciarAtendimento(Request $request, TriagemPaciente $paciente)
    {
        $request->validate([
            'tipo_atendimento' => 'required|string',
        ]);

        $paciente->update([
            'status' => 'EM_TRIAGEM',
            'tipo_atendimento' => $request->tipo_atendimento,
            'arrived_at' => now(),
        ]);

        return redirect()->route('triagem.fila')->with('status', 'Triagem iniciada com sucesso!');
    }

    /**
     * Cria um NOVO registro de triagem para um paciente antigo/finalizado
     */
    public function novoAtendimento(Request $request, TriagemPaciente $paciente)
    {
        $request->validate([
            'tipo_atendimento' => 'required|string',
        ]);

        // Reclona os dados cadastrais do cidadão, gerando uma nova linha de atendimento
        $novaTriagem = $paciente->replicate([
            'status',
            'tipo_atendimento',
            'arrived_at',
            'created_at',
            'updated_at'
        ]);

        $novaTriagem->status = 'EM_TRIAGEM';
        $novaTriagem->tipo_atendimento = $request->tipo_atendimento;
        $novaTriagem->arrived_at = now();
        $novaTriagem->save();

        return redirect()->route('triagem.fila')->with('status', 'Novo atendimento aberto e enviado para a fila!');
    }

    public function filaArea()
    {
        $pacientes = TriagemPaciente::whereDate('created_at', today())
            ->get();

        $statusOptions = [
            'TODOS' => 'Todos',
            'AGUARDANDO' => 'Aguardando',
            'EM_ATENDIMENTO' => 'Em atendimento',
            'FINALIZADO' => 'Finalizado',
        ];

        $filters = [
            'status' => request('status', 'TODOS'),
            'date_start' => request('date_start'),
            'date_end' => request('date_end'),
        ];

        return view('triagem.fila', compact(
            'pacientes',
            'statusOptions',
            'filters'
        ));
    }
    public function cidadaoArea(Request $request)
    {
        $busca = $request->input('busca');

        $dataNasc = $request->input('data_nascimento');
        $nomeMae = $request->input('nome_mae');
        $municipioNasc = $request->input('municipio_nascimento');

        $resultados = collect();

        if ($busca) {

            $resultados = TriagemPaciente::query()
                ->when($busca, function ($query) use ($busca) {
                    $query->where('full_name', 'like', "%{$busca}%")
                        ->orWhere('cpf', 'like', "%{$busca}%")
                        ->orWhere('cns', 'like', "%{$busca}%");
                })
                ->get();
        }


        return view('triagem.cidadao', compact(
            'busca',
            'dataNasc',
            'nomeMae',
            'municipioNasc',
            'resultados'
        ));
    }

    public function cadastrarArea(Request $request)
    {
        return view('triagem.cidadao.cadastro', [
            'busca' => $request->busca ?? '',
            'dataNasc' => $request->data_nascimento ?? '',
            'nomeMae' => $request->nome_mae ?? '',
            'municipioNasc' => $request->municipio_nascimento ?? '',
        ]);
    }
}
