<?php

namespace App\Services;

use App\Models\Citizen;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BethaIntegrationService
{
    private string $baseUrl = 'https://saude.suite.betha.cloud';

    /**
     * Sincroniza o paciente para a Betha através da rota POST /dados/v1/clientes/integrar
     * @param Citizen|array $citizen (Pode ser o Model Citizen ou um array com os dados já convertidos se ele não existir localmente)
     * @param bool $inativo Define o status na criação
     */
    public function syncClient($citizen, bool $inativo = false): bool
    {
        $payload = $this->buildPayload($citizen);

        if (empty($payload['cpf']) && empty($payload['cns'])) {
            Log::warning('BethaIntegrationService: Cidadão sem CPF e CNS ignorado no sync.', ['citizen_id' => $citizen->id ?? null]);
            return false;
        }

        if (!empty($payload['cpf']) && !$inativo) {
            $cpf = $payload['cpf'];
            // Consulta o status atual usando a nova rota buscarPorCpf
            $checkResponse = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/dados/v1/clientes/buscarPorCpf/{$cpf}");

            if ($checkResponse->successful()) {
                $clienteBetha = $checkResponse->json();
                
                // Tenta identificar se o paciente está inativo na base deles
                $isInactive = false;
                if (isset($clienteBetha['inativo']) && $clienteBetha['inativo'] === true) {
                    $isInactive = true;
                } elseif (isset($clienteBetha['situacao']) && strtoupper(strval($clienteBetha['situacao'])) === 'INATIVO') {
                    $isInactive = true;
                } elseif (isset($clienteBetha['status']) && strtoupper(strval($clienteBetha['status'])) === 'INATIVO') {
                    $isInactive = true;
                }

                // Se identificamos que está inativo e a intenção (N2/ACS) é deixá-lo ativo, chamamos o ativar
                if ($isInactive) {
                    Http::withHeaders($this->getHeaders())
                        ->patch("{$this->baseUrl}/dados/v1/clientes/ativar/{$cpf}");
                }
            }
        }

        $response = Http::withHeaders($this->getHeaders())
            ->post("{$this->baseUrl}/dados/v1/clientes/integrar", $payload);

        if (!$response->successful()) {
            $responseData = $response->json();
            $isDuplicate = false;
            
            if ($response->status() === 422 && isset($responseData['detail'])) {
                foreach ($responseData['detail'] as $message) {
                    if (is_string($message) && str_contains($message, 'Já existe um(a) cliente')) {
                        $isDuplicate = true;
                        break;
                    }
                }
            }

            // Fallback: se deu erro de duplicidade e nós queremos que ele seja ativo, 
            // tentamos forçar o ativar caso a checagem acima não tenha pego
            if ($isDuplicate && !$inativo && !empty($payload['cpf'])) {
                $activateResponse = Http::withHeaders($this->getHeaders())
                    ->patch("{$this->baseUrl}/dados/v1/clientes/ativar/{$payload['cpf']}");
                
                if ($activateResponse->successful()) {
                    // Tenta integrar novamente após forçar a ativação
                    $response = Http::withHeaders($this->getHeaders())
                        ->post("{$this->baseUrl}/dados/v1/clientes/integrar", $payload);
                    
                    if ($response->successful()) {
                        $isDuplicate = false;
                    } else {
                        $responseData = $response->json(); // Atualiza com o novo erro
                    }
                }
            }

            if ($response->successful()) {
                // Sucesso no retry
            } elseif ($isDuplicate) {
                Log::info('BethaIntegrationService: Cidadão já existe na base da Betha, ignorando erro.', ['cpf' => $payload['cpf'] ?? null, 'cns' => $payload['cns'] ?? null]);
            } else {
                Log::error('BethaIntegrationService: Falha ao integrar cidadão.', [
                    'status' => $response->status(),
                    'response' => $responseData,
                    'payload' => $payload,
                ]);
                return false;
            }
        }

        if ($inativo) {
            return $this->inactivateClient($payload['cpf'], $payload['cns'], $payload['nomeCompleto']);
        }

        return true;
    }

    /**
     * Inativa o cliente na base da Betha.
     * Caso o cliente não possua CPF, ele enviará um POST atualizando o paciente (buscado pelo CNS)
     * com um CPF sintético válido e então chamará o PATCH para inativar.
     */
    public function inactivateClient(?string $cpf, ?string $cns, ?string $name = null, ?string $id = null): bool
    {
        $cpf = preg_replace('/\D/', '', (string) $cpf);
        $cns = preg_replace('/\D/', '', (string) $cns);

        if (empty($cpf)) {
            Log::warning('BethaIntegrationService: Impossível inativar cidadão sem CPF na Betha (novas rotas exigem CPF).', ['cns' => $cns]);
            return false;
        }

        // Executa o PATCH usando o CPF diretamente
        $response = Http::withHeaders($this->getHeaders())
            ->patch("{$this->baseUrl}/dados/v1/clientes/inativar/{$cpf}");

        if (!$response->successful()) {
            Log::error('BethaIntegrationService: Falha ao inativar cidadão via PATCH.', [
                'cpf' => $cpf,
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            return false;
        }

        return true;
    }

    private function buildPayload($citizen): array
    {
        if ($citizen instanceof Citizen) {
            $raca = $this->mapRaca($citizen->raca_cor);
            
            // Decodifica o endereço em JSON que agora contém metadados adicionais
            $address = is_string($citizen->address) ? json_decode($citizen->address, true) : $citizen->address;
            $nacionalidadeSigla = $address['nacionalidade_sigla'] ?? 'BR';
            
            $ibgeNaturalidade = $this->getIbgeCode($address['naturalidade'] ?? null, $address['naturalidade_uf'] ?? null);
            
            $cns = $citizen->cns ? preg_replace('/\D/', '', $citizen->cns) : null;
            if (!$this->isValidCns($cns)) $cns = null;
            
            return [
                'nomeCompleto' => $this->sanitizeNomeCompleto($citizen->full_name),
                'cpf' => $citizen->cpf ? preg_replace('/\D/', '', $citizen->cpf) : null,
                'cns' => $cns,
                'dataNascimento' => $citizen->birth_date ? $citizen->birth_date->format('Y-m-d') : null,
                'sexo' => $this->mapSexo($citizen->sexo),
                'raca' => $raca,
                'paisNacionalidade' => ['iso2' => $nacionalidadeSigla], // Pego do Gov.Assai, fallback BR
                'municipioNaturalidade' => ['codigoIBGE' => $ibgeNaturalidade],
                'endereco' => $this->buildEnderecoPayload($address),
            ];
        }

        // Assume that it's an array already mapped
        $raca = $this->mapRaca($citizen['raca_cor'] ?? null);
        $nacionalidadeSigla = $citizen['nacionalidade_sigla'] ?? 'BR';
        $ibgeNaturalidade = $this->getIbgeCode($citizen['naturalidade'] ?? null, $citizen['naturalidade_uf'] ?? null);
        
        $cns = isset($citizen['cns']) ? preg_replace('/\D/', '', $citizen['cns']) : null;
        if (!$this->isValidCns($cns)) $cns = null;
        
        return [
            'nomeCompleto' => $this->sanitizeNomeCompleto($citizen['name'] ?? $citizen['nomeCompleto'] ?? null),
            'cpf' => isset($citizen['cpf']) ? preg_replace('/\D/', '', $citizen['cpf']) : null,
            'cns' => $cns,
            'dataNascimento' => $citizen['birth_date'] ?? null,
            'sexo' => $this->mapSexo($citizen['sexo'] ?? null),
            'raca' => $raca,
            'paisNacionalidade' => ['iso2' => $nacionalidadeSigla], 
            'municipioNaturalidade' => ['codigoIBGE' => $ibgeNaturalidade], 
            'endereco' => $this->buildEnderecoPayload($citizen['address'] ?? null),
        ];
    }

    private function buildEnderecoPayload($addressJson): array
    {
        $address = is_string($addressJson) ? json_decode($addressJson, true) : $addressJson;
        $cep = isset($address['cep']) ? preg_replace('/\D/', '', $address['cep']) : '86220000';
        if ($cep) {
            $cep = str_pad($cep, 8, '0', STR_PAD_RIGHT);
        }
        
        $semNumero = empty($address['numero']) || $address['numero'] === 'S/N' || $address['numero'] === '000';
        $payload = [
            'cep' => $cep ?: '86220000',
            'municipio' => ['codigoIBGE' => 4101903], // Assaí
            'bairro' => [
                'municipio' => ['codigoIBGE' => 4101903],
                'nome' => mb_substr($address['bairro'] ?? 'Centro', 0, 50, 'UTF-8')
            ],
            'logradouro' => [
                'municipio' => ['codigoIBGE' => 4101903],
                'cep' => $cep ?: '86220000',
                'abreviaturaTipoLogradouro' => 'R',
                'nome' => mb_substr($address['logradouro'] ?? 'Sem Logradouro', 0, 50, 'UTF-8')
            ],
            'semNumero' => $semNumero
        ];
        
        if (!$semNumero) {
            $payload['numero'] = $address['numero'];
        }
        
        return $payload;
    }

    private function getIbgeCode(?string $cidade, ?string $uf): int
    {
        $cidade = trim((string) $cidade);
        $uf = trim(strtoupper((string) $uf));
        
        if (empty($cidade) || empty($uf)) {
            return 4101903; // Assaí Fallback
        }

        $cacheKey = "ibge_code_{$uf}_" . md5(strtolower($cidade));

        return \Illuminate\Support\Facades\Cache::rememberForever($cacheKey, function () use ($cidade, $uf) {
            try {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->timeout(5)
                    ->get("https://brasilapi.com.br/api/ibge/municipios/v1/{$uf}");

                if ($response->successful()) {
                    $municipios = $response->json();
                    $cidadeNormalizada = $this->removeAccents(strtolower($cidade));
                    
                    foreach ($municipios as $mun) {
                        if ($this->removeAccents(strtolower($mun['nome'])) === $cidadeNormalizada) {
                            return (int) $mun['codigo_ibge'];
                        }
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Falha ao buscar codigo IBGE na BrasilAPI para {$cidade} - {$uf}: " . $e->getMessage());
            }

            return 4101903; // Assaí Fallback
        });
    }

    private function sanitizeNomeCompleto(?string $nome): string
    {
        if (empty($nome)) {
            return 'Cidadao Sem Nome';
        }

        // Remova pontos e abreviações com ponto (ex: DE J. -> DE J)
        $nome = str_replace('.', '', $nome);

        // Remova caracteres especiais (mantendo apenas letras e espaços)
        $nome = preg_replace('/[^a-zA-ZÀ-ÖØ-öø-ÿ\s]/u', '', $nome);

        // Remova espaços múltiplos
        $nome = preg_replace('/\s+/', ' ', trim($nome));

        return mb_strtoupper($nome);
    }

    private function removeAccents(string $string): string
    {
        return preg_replace([
            '/(á|à|ã|â|ä)/', '/(Á|À|Ã|Â|Ä)/', '/(é|è|ê|ë)/', '/(É|È|Ê|Ë)/',
            '/(í|ì|î|ï)/', '/(Í|Ì|Î|Ï)/', '/(ó|ò|õ|ô|ö)/', '/(Ó|Ò|Õ|Ô|Ö)/',
            '/(ú|ù|û|ü)/', '/(Ú|Ù|Û|Ü)/', '/(ñ)/', '/(Ñ)/', '/(ç)/', '/(Ç)/'
        ], [
            'a', 'A', 'e', 'E', 'i', 'I', 'o', 'O', 'u', 'U', 'n', 'N', 'c', 'C'
        ], $string);
    }

    private function mapRaca($raca): string
    {
        if (is_numeric($raca)) {
            $mapInt = [
                1 => 'BRANCA',
                2 => 'PRETA',
                3 => 'PARDA',
                4 => 'AMARELA',
                5 => 'INDIGENA',
            ];
            return $mapInt[(int) $raca] ?? 'PARDA';
        }

        $raca = strtoupper(trim((string) $raca));
        $map = [
            'BRANCA' => 'BRANCA',
            'PRETA' => 'PRETA',
            'PARDA' => 'PARDA',
            'AMARELA' => 'AMARELA',
            'INDIGENA' => 'INDIGENA',
            'INDÍGENA' => 'INDIGENA',
        ];
        
        return $map[$raca] ?? 'PARDA';
    }

    private function mapSexo(?string $sexo): string
    {
        $sexo = strtoupper(substr(trim((string) $sexo), 0, 1));
        return $sexo === 'F' ? 'FEMININO' : 'MASCULINO';
    }

    private function generateValidSyntheticCpf(): string
    {
        $n = [];
        for ($i = 0; $i < 9; $i++) {
            $n[$i] = rand(0, 9);
        }
        $d1 = 0;
        for ($i = 0; $i < 9; $i++) {
            $d1 += $n[$i] * (10 - $i);
        }
        $d1 = 11 - ($d1 % 11);
        if ($d1 >= 10) $d1 = 0;
        $n[9] = $d1;

        $d2 = 0;
        for ($i = 0; $i < 10; $i++) {
            $d2 += $n[$i] * (11 - $i);
        }
        $d2 = 11 - ($d2 % 11);
        if ($d2 >= 10) $d2 = 0;
        $n[10] = $d2;
        
        return implode('', $n);
    }

    private function isValidCns(?string $cns): bool
    {
        $cns = preg_replace('/\D/', '', (string) $cns);
        if (strlen($cns) !== 15) return false;

        if (in_array($cns[0], ['1', '2'])) {
            $pis = substr($cns, 0, 11);
            $soma = 0;
            for ($i = 0; $i < 11; $i++) {
                $soma += ((int) $pis[$i]) * (15 - $i);
            }
            $resto = $soma % 11;
            $dv = 11 - $resto;
            if ($dv === 11) {
                $dv = 0;
            }
            
            if ($dv === 10) {
                $soma = 0;
                for ($i = 0; $i < 11; $i++) {
                    $soma += ((int) $pis[$i]) * (15 - $i);
                }
                $soma += 2;
                $resto = $soma % 11;
                $dv = 11 - $resto;
                $resultado = $pis . "001" . (string) $dv;
            } else {
                $resultado = $pis . "000" . (string) $dv;
            }
            return $cns === $resultado;
        } elseif (in_array($cns[0], ['7', '8', '9'])) {
            $soma = 0;
            for ($i = 0; $i < 15; $i++) {
                $soma += ((int) $cns[$i]) * (15 - $i);
            }
            return $soma % 11 === 0;
        }
        
        return false;
    }

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . config('services.betha.bearer_token'),
            'User-Access' => config('services.betha.user_access'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
