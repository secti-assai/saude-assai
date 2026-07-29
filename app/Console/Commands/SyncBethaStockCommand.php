<?php

namespace App\Console\Commands;

use App\Models\HealthUnit;
use App\Models\Medication;
use App\Models\StockItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncBethaStockCommand extends Command
{
    protected $signature = 'betha:sync-stock';
    protected $description = 'Sincroniza o estoque de medicamentos com a API da Betha em background para manter a base local atualizada';

    public function handle()
    {
        $this->info('Iniciando sincronização de estoque com a Betha...');
        
        $baseUrl = 'https://saude.suite.betha.cloud';
        $token = config('services.betha.bearer_token');
        $userAccess = config('services.betha.user_access');

        if (!$token || !$userAccess) {
            $this->error('Credenciais da Betha não configuradas.');
            return 1;
        }

        $headers = [
            'Authorization' => 'Bearer ' . $token,
            'User-Access' => $userAccess,
            'Accept' => 'application/json',
        ];

        try {
            // 1. Fetch products to map idProduto -> name
            // Note: Em produção real, pode ser necessário lidar com paginação
            $this->info('Buscando produtos...');
            $produtosResponse = Http::withHeaders($headers)
                ->get("{$baseUrl}/dados/v1/produto", [
                    'limit' => 2000,
                ]);

            if (!$produtosResponse->successful()) {
                $this->error('Falha ao buscar produtos: ' . $produtosResponse->body());
                return 1;
            }

            $produtosList = $produtosResponse->json('content') ?? [];
            $produtoMap = [];
            foreach ($produtosList as $prod) {
                // Mapeia o ID do produto para o seu nome
                $id = $prod['idProduto'] ?? $prod['id'] ?? null;
                $nome = $prod['nome'] ?? $prod['nomeProduto'] ?? $prod['descricao'] ?? 'Desconhecido';
                
                if ($id) {
                    $produtoMap[$id] = $nome;
                }
            }

            // 2. Test fetching unidades
            $this->info('Buscando unidades...');
            $unidadeResponse = Http::withHeaders($headers)
                ->get("{$baseUrl}/dados/v1/unidades", ['limit' => 50]);
            
            $this->info('Raw Unidade Response: ' . $unidadeResponse->body());
            
            $unidades = $unidadeResponse->json('content') ?? [];
            if (count($unidades) > 0) {
                $idUnidade = $unidades[0]['idUnidade'] ?? $unidades[0]['id'];
                $this->info("Usando idUnidade: {$idUnidade}");
                
                // 3. Fetch stock (saldo) with idsUnidade
                $this->info('Buscando saldo (estoque) com listSaldoDetalhe...');
                $saldoResponse = Http::withHeaders($headers)
                    ->get("{$baseUrl}/dados/v1/produtoSaldo/listSaldoDetalhe", [
                        'idsUnidade' => 5797, // Farmácia
                        'limit' => 200,
                    ]);

                if (!$saldoResponse->successful()) {
                    $this->error('Falha ao buscar saldo detalhe: ' . $saldoResponse->body());
                    return 1;
                }
                $saldos = $saldoResponse->json('content') ?? [];
            } else {
                $this->error('Nenhuma unidade encontrada.');
                return 1;
            }
            
            $healthUnit = HealthUnit::first();
            $healthUnitId = $healthUnit ? $healthUnit->id : 1;

            $this->info('Atualizando base local...');
            
            // Delete old stock to replace with new snapshot
            // We use transaction to avoid empty state during query
            \DB::transaction(function () use ($saldos, $produtoMap, $healthUnitId) {
                StockItem::query()->delete();
                
                $imported = 0;
                foreach ($saldos as $saldo) {
                    $idProduto = $saldo['idProduto'];
                    $quantidade = $saldo['saldo'] ?? 0;
                    
                    if ($quantidade <= 0) continue;

                    $nomeProduto = $produtoMap[$idProduto] ?? null;
                    if (!$nomeProduto) continue;

                    $medication = Medication::firstOrCreate(
                        ['name' => $nomeProduto],
                        ['code' => uniqid('MED_B_'), 'is_remume' => true]
                    );

                    StockItem::create([
                        'medication_id' => $medication->id,
                        'health_unit_id' => $healthUnitId,
                        'batch' => null,
                        'quantity' => $quantidade,
                        'total_cost' => $saldo['valor'] ?? 0,
                        'unit_cost' => $saldo['valorUnitario'] ?? 0,
                        'entry_date' => now()->format('Y-m-d'),
                        'supplier' => 'BETHA API SYNC',
                    ]);
                    $imported++;
                }
                
                $this->info("Importados {$imported} itens de estoque.");
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
