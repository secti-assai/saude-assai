<?php

namespace App\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BethaStockService
{
    private string $baseUrl = 'https://saude.suite.betha.cloud';

    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . config('services.betha.bearer_token'),
            'User-Access' => config('services.betha.user_access'),
            'Accept' => 'application/json',
        ];
    }

    /**
     * Passo 1 — Descobrir o id da unidade "Farmácia Central" ou "FARMACIA MUNICIPAL DE ASSAI"
     */
    public function getFarmaciaCentralUnitId(): ?int
    {
        return Cache::remember('betha_farmacia_central_unit_id', 86400, function () {
            try {
                $response = Http::withHeaders($this->getHeaders())
                    ->get("{$this->baseUrl}/dados/v1/unidades", [
                        'filter' => "nome like 'FARMACIA%'",
                        'fields' => 'id,nome',
                        'limit' => 50,
                    ]);

                if ($response->successful()) {
                    $unidades = $response->json('content') ?? [];
                    if (!empty($unidades)) {
                        return (int) $unidades[0]['id'];
                    }
                }

                // Fallback: tentar listar todas as unidades
                $allResponse = Http::withHeaders($this->getHeaders())
                    ->get("{$this->baseUrl}/dados/v1/unidades", ['limit' => 50]);

                if ($allResponse->successful()) {
                    $unidades = $allResponse->json('content') ?? [];
                    foreach ($unidades as $u) {
                        $nome = strtoupper($u['nome'] ?? '');
                        if (str_contains($nome, 'FARMACIA') || str_contains($nome, 'FARMÁCIA')) {
                            return (int) ($u['id'] ?? $u['idUnidade']);
                        }
                    }
                    if (!empty($unidades)) {
                        return (int) ($unidades[0]['id'] ?? $unidades[0]['idUnidade']);
                    }
                }
            } catch (\Exception $e) {
                Log::error('BethaStockService: Erro ao buscar ID da unidade de farmácia: ' . $e->getMessage());
            }

            return 5797; // Fallback padrao para Farmacia Municipal de Assai
        });
    }

    /**
     * Passo 2 — Buscar o saldo/estoque da unidade
     */
    public function getUnitStock(int $unitId, int $limit = 1000): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->get("{$this->baseUrl}/dados/v1/produtoSaldo/listSaldo", [
                    'idsUnidade' => $unitId,
                    'isSaldoPositivo' => 'true',
                    'exibeVencidos' => 'false',
                    'limit' => $limit,
                ]);

            if ($response->successful()) {
                return $response->json('content') ?? [];
            }

            Log::error('BethaStockService: Erro ao buscar saldo da unidade.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('BethaStockService: Excecao ao buscar saldo: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Passo 3 — Buscar os nomes/detalhes dos produtos por lista de IDs
     */
    public function getProductsDetails(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $productIds = array_values(array_unique(array_filter($productIds)));
        $missingIds = [];
        $result = [];

        // Verifica o cache local para cada idProduto
        foreach ($productIds as $id) {
            $cached = Cache::get("betha_product_detail_{$id}");
            if ($cached) {
                $result[$id] = $cached;
            } else {
                $missingIds[] = $id;
            }
        }

        // Se houver IDs não cacheados, busca em lote (chunks de 50 para nao estourar URL)
        if (!empty($missingIds)) {
            foreach (array_chunk($missingIds, 50) as $chunk) {
                try {
                    $filter = implode(' or ', array_map(fn($id) => "id = {$id}", $chunk));
                    $response = Http::withHeaders($this->getHeaders())
                        ->get("{$this->baseUrl}/dados/v1/produto", [
                            'filter' => $filter,
                            'fields' => 'id,descricao,codigo,denominacaoComumBrasileira,apresentacao,medicamentoControlado',
                            'limit' => 100,
                        ]);

                    if ($response->successful()) {
                        $produtos = $response->json('content') ?? [];
                        foreach ($produtos as $prod) {
                            $prodId = (int) ($prod['id'] ?? $prod['idProduto']);
                            $result[$prodId] = $prod;
                            // Salva no cache por 12 horas
                            Cache::put("betha_product_detail_{$prodId}", $prod, 43200);
                        }
                    } else {
                        Log::error('BethaStockService: Erro ao buscar detalhes dos produtos.', [
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('BethaStockService: Excecao ao buscar detalhes dos produtos: ' . $e->getMessage());
                }
            }
        }

        return $result;
    }

    /**
     * Combina os 3 passos e retorna uma lista formatada/paginada de medicamentos em estoque
     */
    public function getAvailableStockForDoctor(?string $query = null, int $page = 1, int $perPage = 50, ?string $requestUrl = null): LengthAwarePaginator
    {
        $unitId = $this->getFarmaciaCentralUnitId();
        
        // Cacheia a lista bruta de saldo da farmacia por 5 minutos para performance
        $cacheKey = "betha_raw_stock_unit_{$unitId}";
        $saldos = Cache::remember($cacheKey, 300, function () use ($unitId) {
            return $this->getUnitStock($unitId);
        });

        if (empty($saldos)) {
            return new LengthAwarePaginator([], 0, $perPage, $page, [
                'path' => $requestUrl ?? url('/medico/estoque'),
                'query' => request()->query(),
            ]);
        }

        // Agrupa saldo por idProduto
        $aggregatedStock = [];
        foreach ($saldos as $s) {
            $idProd = (int) ($s['idProduto'] ?? 0);
            if ($idProd <= 0) continue;

            $qnt = (float) ($s['saldo'] ?? 0);
            if (!isset($aggregatedStock[$idProd])) {
                $aggregatedStock[$idProd] = [
                    'idProduto' => $idProd,
                    'stock_total' => $qnt,
                    'valorUnitario' => (float) ($s['valorUnitario'] ?? 0),
                    'dataValidade' => $s['dataValidade'] ?? null,
                ];
            } else {
                $aggregatedStock[$idProd]['stock_total'] += $qnt;
            }
        }

        // Busca detalhes de todos os produtos com saldo
        $productIds = array_keys($aggregatedStock);
        $productsDetails = $this->getProductsDetails($productIds);

        // Monta os objetos formatados
        $formatted = [];
        foreach ($aggregatedStock as $idProd => $stockData) {
            $prodInfo = $productsDetails[$idProd] ?? null;
            $name = $prodInfo['descricao'] ?? "Medicamento #{$idProd}";
            $apresentacao = $prodInfo['apresentacao'] ?? $prodInfo['denominacaoComumBrasileira'] ?? null;
            $isControlado = !empty($prodInfo['medicamentoControlado']);

            // Tenta extrair concentracao do nome se houver (ex: 500 MG)
            $concentration = null;
            if (preg_match('/(\d+(?:\,\d+|\.\d+)?\s*(?:MG|G|ML|MCG|UI|U|IU))/i', $name, $matches)) {
                $concentration = strtoupper($matches[1]);
            }

            $item = (object) [
                'id' => $idProd,
                'name' => $name,
                'presentation' => $apresentacao,
                'concentration' => $concentration,
                'is_remume' => true, // Produtos da Farmacia Municipal pertencem a REMUME
                'is_controlado' => $isControlado,
                'stock_total' => $stockData['stock_total'],
                'data_validade' => $stockData['dataValidade'],
            ];

            // Aplica filtro por termo de busca se informado
            if ($query && trim($query) !== '') {
                $q = mb_strtolower(trim($query));
                $nameMatch = str_contains(mb_strtolower($item->name), $q);
                $presMatch = $item->presentation ? str_contains(mb_strtolower($item->presentation), $q) : false;
                
                if (!$nameMatch && !$presMatch) {
                    continue;
                }
            }

            $formatted[] = $item;
        }

        // Ordena por nome do medicamento
        usort($formatted, fn($a, $b) => strnatcasecmp($a->name, $b->name));

        // Paginação manual em memória
        $total = count($formatted);
        $offset = ($page - 1) * $perPage;
        $itemsForCurrentPage = array_slice($formatted, $offset, $perPage);

        return new LengthAwarePaginator(
            $itemsForCurrentPage,
            $total,
            $perPage,
            $page,
            [
                'path' => $requestUrl ?? url('/medico/estoque'),
                'query' => request()->query(),
            ]
        );
    }
}
