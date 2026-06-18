<?php

namespace App\Http\Controllers;

use App\Models\StockItem;
use App\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAnalysisController extends Controller
{
    public function index(Request $request)
    {
        $query = StockItem::query()->with('medication');

        // Extract available filters
        $availableYears = StockItem::select(DB::raw('EXTRACT(YEAR FROM entry_date) as year'))
            ->whereNotNull('entry_date')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $availableSuppliers = StockItem::select('supplier')
            ->whereNotNull('supplier')
            ->distinct()
            ->orderBy('supplier')
            ->pluck('supplier');

        $availableMedications = Medication::whereHas('stockItems')
            ->orderBy('name')
            ->get();

        // Apply filters
        $selectedYear = $request->input('year');
        $selectedMonth = $request->input('month');
        $selectedSupplier = $request->input('supplier');
        $selectedMedication = $request->input('medication_id');
        $selectedResource = $request->input('resource_origin');
        $selectedCategory = $request->input('product_category');

        if ($selectedYear) {
            $query->whereYear('entry_date', $selectedYear);
        }
        if ($selectedMonth) {
            $query->whereMonth('entry_date', $selectedMonth);
        }
        if ($selectedSupplier) {
            $query->where('supplier', $selectedSupplier);
        }
        if ($selectedMedication) {
            $query->where('medication_id', $selectedMedication);
        }

        $applyGovFilter = function ($q, $isGov) {
            if ($isGov) {
                $q->where(function($sub) {
                    $sub->whereRaw('LOWER(supplier) LIKE ?', ['%parana%'])
                        ->orWhereRaw('LOWER(supplier) LIKE ?', ['%17º regional%'])
                        ->orWhereRaw('LOWER(supplier) LIKE ?', ['%17%'])
                        ->orWhereRaw('LOWER(supplier) LIKE ?', ['%consorcio%'])
                        ->orWhereRaw('LOWER(supplier) LIKE ?', ['%prefeitura%']);
                });
            } else {
                $q->where(function($sub) {
                    $sub->whereRaw('LOWER(supplier) NOT LIKE ?', ['%parana%'])
                        ->whereRaw('LOWER(supplier) NOT LIKE ?', ['%17º regional%'])
                        ->whereRaw('LOWER(supplier) NOT LIKE ?', ['%17%'])
                        ->whereRaw('LOWER(supplier) NOT LIKE ?', ['%consorcio%'])
                        ->whereRaw('LOWER(supplier) NOT LIKE ?', ['%prefeitura%'])
                        ->orWhereNull('supplier');
                });
            }
        };

        if ($selectedResource === 'gov') {
            $applyGovFilter($query, true);
        } elseif ($selectedResource === 'mun') {
            $applyGovFilter($query, false);
        }

        $applyCategoryFilter = function ($q, $category) {
            $q->whereHas('medication', function($sub) use ($category) {
                if ($category === 'fralda') {
                    $sub->whereRaw('LOWER(name) LIKE ?', ['%fralda%']);
                } elseif ($category === 'leite') {
                    $sub->where(function($s2) {
                        $s2->whereRaw('LOWER(name) LIKE ?', ['%aptamil%'])
                           ->orWhereRaw('LOWER(name) LIKE ?', ['%neocate%'])
                           ->orWhereRaw('LOWER(name) LIKE ?', ['%alfamino%'])
                           ->orWhereRaw('LOWER(name) LIKE ?', ['%alfare%'])
                           ->orWhereRaw('LOWER(name) LIKE ?', ['%pediasure%'])
                           ->orWhereRaw('LOWER(name) LIKE ?', ['%enteral%'])
                           ->orWhereRaw('LOWER(name) LIKE ?', ['%milk%'])
                           ->orWhereRaw('LOWER(name) LIKE ?', ['%leite%'])
                           ->orWhereRaw('LOWER(name) LIKE ?', ['%formula%'])
                           ->orWhereRaw('LOWER(name) LIKE ?', ['%fórmula%'])
                           ->orWhereRaw('LOWER(name) LIKE ?', ['%soja%'])
                           ->orWhereRaw('LOWER(name) LIKE ?', ['%nutren%']);
                    });
                } elseif ($category === 'medicacao') {
                    $sub->whereRaw('LOWER(name) NOT LIKE ?', ['%fralda%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%aptamil%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%neocate%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%alfamino%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%alfare%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%pediasure%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%enteral%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%milk%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%leite%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%formula%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%fórmula%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%soja%'])
                        ->whereRaw('LOWER(name) NOT LIKE ?', ['%nutren%']);
                }
            });
        };

        if ($selectedCategory) {
            $applyCategoryFilter($query, $selectedCategory);
        }

        $filteredItems = (clone $query)->get();

        // Calculate KPIs
        $totalCost = $filteredItems->sum('total_cost');
        $totalQuantity = $filteredItems->sum('quantity');

        $totalGovCost = 0;
        $totalMunCost = 0;

        foreach($filteredItems as $item) {
            $s = mb_strtolower($item->supplier ?? '');
            $isGov = str_contains($s, 'parana') || 
                     str_contains($s, '17º regional') || 
                     str_contains($s, '17') || 
                     str_contains($s, 'consorcio') || 
                     str_contains($s, 'prefeitura');
            
            if ($isGov) {
                $totalGovCost += $item->total_cost;
            } else {
                $totalMunCost += $item->total_cost;
            }
        }

        // Group by supplier
        $supplierCosts = $filteredItems->groupBy('supplier')->map(function ($items, $supplier) {
            return [
                'supplier' => $supplier ?: 'Não Identificado',
                'total_cost' => $items->sum('total_cost'),
                'total_quantity' => $items->sum('quantity')
            ];
        })->sortByDesc('total_cost')->values();

        // Group by medication
        $medicationCosts = $filteredItems->groupBy('medication_id')->map(function ($items) {
            $medication = $items->first()->medication;
            $totalQty = $items->sum('quantity');
            $totalCost = $items->sum('total_cost');
            return [
                'name' => $medication ? $medication->name : 'Desconhecido',
                'total_cost' => $totalCost,
                'total_quantity' => $totalQty,
                'avg_unit_cost' => $totalQty > 0 ? ($totalCost / $totalQty) : 0
            ];
        })->sortByDesc('total_cost')->take(50)->values();

        // Detailed pagination
        $detailedItems = (clone $query)->orderByDesc('entry_date')->paginate(50)->withQueryString();

        return view('admin.stock_analysis', compact(
            'availableYears',
            'availableSuppliers',
            'availableMedications',
            'selectedYear',
            'selectedMonth',
            'selectedSupplier',
            'selectedMedication',
            'selectedResource',
            'selectedCategory',
            'totalCost',
            'totalQuantity',
            'totalGovCost',
            'totalMunCost',
            'supplierCosts',
            'medicationCosts',
            'detailedItems'
        ));
    }
}
