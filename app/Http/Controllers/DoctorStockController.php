<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorStockController extends Controller
{
    /**
     * Display the stock consultation page for doctors.
     *
     * The stock data comes from a CSV import performed by the admin
     * (Admin → Imp. Estoque). The admin uploads a stock movement CSV
     * exported from the Betha system; StockImportController parses it
     * and populates the medications / stock_items tables locally.
     * This page simply queries that local database.
     */
    public function index(Request $request)
    {
        $query = $request->input('q');
        $medications = null;

        if ($request->filled('q')) {
            $medications = Medication::query()
                ->addSelect([
                    'medications.*',
                    'stock_total' => DB::table('stock_items')
                        ->selectRaw('COALESCE(SUM(quantity), 0)')
                        ->whereColumn('stock_items.medication_id', 'medications.id')
                        ->where('quantity', '>', 0),
                ])
                ->where('name', 'ILIKE', '%' . $query . '%')
                ->orderBy('name', 'asc')
                ->paginate(50)
                ->appends(['q' => $query]);
        }

        return view('doctor.stock', compact('medications', 'query'));
    }
}
