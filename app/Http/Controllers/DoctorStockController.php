<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use App\Models\StockItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DoctorStockController extends Controller
{
    /**
     * Display the stock consultation page for doctors.
     */
    public function index()
    {
        return Inertia::render('Doctor/StockConsultation');
    }

    /**
     * API endpoint to search medication stock.
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        $medicationsQuery = Medication::query()
            ->with(['stockItems' => function ($q) {
                $q->select('medication_id', DB::raw('SUM(quantity) as total_quantity'))
                  ->where('quantity', '>', 0)
                  ->groupBy('medication_id');
            }]);

        if (!empty($query)) {
            // Check if postgresql unaccent is available or just use ILIKE
            $medicationsQuery->where('name', 'ILIKE', '%' . $query . '%');
        }

        $medications = $medicationsQuery->orderBy('name', 'asc')->paginate(50);

        // Format response
        $medications->getCollection()->transform(function ($medication) {
            $stockItem = $medication->stockItems->first();
            $quantity = $stockItem ? $stockItem->total_quantity : 0;
            
            return [
                'id' => $medication->id,
                'name' => $medication->name,
                'presentation' => $medication->presentation,
                'concentration' => $medication->concentration,
                'is_remume' => $medication->is_remume,
                'stock_available' => (float) $quantity,
            ];
        });

        return response()->json($medications);
    }
}
