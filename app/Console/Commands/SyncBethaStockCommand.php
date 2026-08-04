<?php

namespace App\Console\Commands;

use App\Models\HealthUnit;
use App\Models\Medication;
use App\Models\StockItem;
use App\Services\BethaStockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncBethaStockCommand extends Command
{
    protected $signature = 'betha:sync-stock';
    protected $description = 'Sincroniza o estoque de medicamentos com a API da Betha em background para manter a base local atualizada';

    public function handle(BethaStockService $bethaStock)
    {
        $this->info('Iniciando sincronização de estoque com a Betha...');

        $token = config('services.betha.bearer_token');
        $userAccess = config('services.betha.user_access');

        if (!$token || !$userAccess) {
            $this->error('Credenciais da Betha não configuradas.');
            return 1;
        }

        try {
            // 1. Unidade
            $unitId = $bethaStock->getFarmaciaCentralUnitId() ?? 5797;
            $this->info("Usando ID da unidade de farmácia: {$unitId}");

            // 2. Saldo/Estoque
            $this->info('Buscando saldo da unidade na Betha...');
            $saldos = $bethaStock->getUnitStock($unitId, 1000);
            $this->info('Registros de saldo encontrados: ' . count($saldos));

            if (empty($saldos)) {
                $this->warn('Nenhum registro de saldo retornado pela Betha.');
                return 0;
            }

            // 3. Produtos
            $productIds = array_values(array_unique(array_filter(array_column($saldos, 'idProduto'))));
            $this->info('Buscando detalhes de ' . count($productIds) . ' produtos...');
            $produtoMap = $bethaStock->getProductsDetails($productIds);

            $healthUnit = HealthUnit::firstOrCreate(
                ['name' => 'Farmácia Municipal de Assaí'],
                ['code' => 'FM-01', 'kind' => 'FARMACIA', 'is_active' => true]
            );
            $healthUnitId = $healthUnit->id;

            $this->info('Atualizando base local...');

            DB::transaction(function () use ($saldos, $produtoMap, $healthUnitId) {
                StockItem::query()->delete();

                $imported = 0;
                foreach ($saldos as $saldo) {
                    $idProduto = (int) ($saldo['idProduto'] ?? 0);
                    $quantidade = (float) ($saldo['saldo'] ?? 0);

                    if ($quantidade <= 0 || $idProduto <= 0) {
                        continue;
                    }

                    $prodInfo = $produtoMap[$idProduto] ?? null;
                    $nomeProduto = $prodInfo['descricao'] ?? null;

                    if (!$nomeProduto) {
                        continue;
                    }

                    $apresentacao = $prodInfo['apresentacao'] ?? $prodInfo['denominacaoComumBrasileira'] ?? null;
                    $medication = Medication::firstOrCreate(
                        ['name' => $nomeProduto],
                        [
                            'code' => sprintf('BETHA-%d', $idProduto),
                            'presentation' => $apresentacao,
                            'is_remume' => true
                        ]
                    );

                    StockItem::create([
                        'medication_id' => $medication->id,
                        'health_unit_id' => $healthUnitId,
                        'batch' => $saldo['idProdutoLote'] ?? null,
                        'quantity' => (int) round($quantidade),
                        'total_cost' => $saldo['valor'] ?? 0,
                        'unit_cost' => $saldo['valorUnitario'] ?? 0,
                        'entry_date' => now()->format('Y-m-d'),
                        'supplier' => 'BETHA API SYNC',
                    ]);
                    $imported++;
                }

                $this->info("Importados {$imported} itens de estoque na base local.");
            });

            $this->info('Sincronização concluída com sucesso.');
            return 0;
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar estoque da Betha: ' . $e->getMessage());
            $this->error('Erro inesperado: ' . $e->getMessage());
            return 1;
        }
    }
}

