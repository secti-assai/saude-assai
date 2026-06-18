<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use App\Models\StockItem;
use App\Models\HealthUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockImportController extends Controller
{
    public function index()
    {
        return view('import.stock');
    }

    public function store(Request $request)
    {
        set_time_limit(0); // Prevent timeout for large files

        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $content = file_get_contents($file->getRealPath());

        // Handle BOM and encoding issues
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8, ISO-8859-1, Windows-1252');
        if (substr($content, 0, 3) === "\xef\xbb\xbf") {
            $content = substr($content, 3);
        }

        $lines = explode("\n", $content);
        $currentProduct = null;
        $importedCount = 0;
        
        $currentDate = null;
        $currentSupplier = null;

        $healthUnit = HealthUnit::first();
        $healthUnitId = $healthUnit ? $healthUnit->id : 1;

        DB::beginTransaction();
        try {
            // Clean up old imports to avoid duplicates
            StockItem::query()->delete();

            $currentDate = null;
            $currentSupplier = null;
            $expectingDateSupplier = false;

            for ($i = 0; $i < count($lines); $i++) {
                $line = trim($lines[$i]);
                if (empty($line)) continue;

                $parts = array_map('trim', explode(';', $line));
                $nonEmpty = array_values(array_filter($parts, fn($v) => $v !== ''));

                if (count($nonEmpty) === 0) continue;

                // 1. Product line
                if (str_starts_with($nonEmpty[0], 'Produto:')) {
                    $currentProduct = trim(str_replace('Produto:', '', $nonEmpty[0]));
                    continue;
                }

                // 2. Header Data / Fornecedor
                if (in_array('Data', $nonEmpty) && in_array('Fornecedor', $nonEmpty)) {
                    $expectingDateSupplier = true;
                    continue;
                }

                // 3. Date / Supplier Line
                if ($expectingDateSupplier && preg_match('/^(\d{2}\/\d{2}\/\d{4})/', $nonEmpty[0], $matches)) {
                    try {
                        $currentDate = Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');
                    } catch (\Exception $e) {}
                    
                    if (count($nonEmpty) > 1) {
                        $supplier = $nonEmpty[count($nonEmpty) - 1];
                        $currentSupplier = ($supplier === 'FARMACIA MUNICIPAL DE ASSAI' || $supplier === 'Centro de custo') ? null : $supplier;
                    }
                    $expectingDateSupplier = false;
                    continue;
                }

                // 4. Entrada line
                if ($nonEmpty[0] === 'Entrada') {
                    if (count($nonEmpty) >= 4) {
                        $lote = $nonEmpty[1];
                        $quantStr = $nonEmpty[2];
                        $valStr = $nonEmpty[3];
                    } elseif (count($nonEmpty) === 3) {
                        $lote = null;
                        $quantStr = $nonEmpty[1];
                        $valStr = $nonEmpty[2];
                    } else {
                        continue; // Malformed
                    }

                    $quantStr = str_replace(['.', ','], ['', '.'], $quantStr);
                    $valStr = str_replace(['.', ','], ['', '.'], $valStr);

                    $quant = (float) $quantStr;
                    $val = (float) $valStr;

                    if ($currentProduct && $quant > 0) {
                        $medication = Medication::firstOrCreate(
                            ['name' => $currentProduct],
                            ['code' => uniqid('MED_'), 'is_remume' => true]
                        );

                        $unitCost = $val / $quant;

                        StockItem::create([
                            'medication_id' => $medication->id,
                            'health_unit_id' => $healthUnitId,
                            'batch' => $lote,
                            'quantity' => $quant,
                            'total_cost' => $val,
                            'unit_cost' => $unitCost,
                            'entry_date' => $currentDate,
                            'supplier' => $currentSupplier,
                        ]);

                        $importedCount++;
                    }
                }
            }

            DB::commit();
            return back()->with('success', "Importação concluída com sucesso! {$importedCount} registros de entrada importados e antigos limpos.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Erro ao importar: ' . $e->getMessage() . ' na linha: ' . $i);
        }
    }
}
