<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use App\Services\BethaStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorStockController extends Controller
{
    public function __construct(
        private readonly BethaStockService $bethaStock
    ) {}

    /**
     * Display the stock consultation page for doctors.
     *
     * Consumes the Betha Cloud API (3-step process: Unidades -> produtoSaldo -> produto)
     * via BethaStockService, falling back to local database if Betha API is unreachable.
     */
    public function index(Request $request)
    {
        $query = $request->input('q');
        $medications = null;

        if ($request->has('q')) {
            try {
                $page = (int) $request->input('page', 1);
                $medications = $this->bethaStock->getAvailableStockForDoctor(
                    query: $query,
                    page: $page,
                    perPage: 50,
                    requestUrl: $request->url()
                );
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('DoctorStockController: Falha ao consultar estoque na Betha: ' . $e->getMessage());
                $medications = null;
            }

            // Fallback para a base local se a Betha não retornar resultados ou falhar
            if ($medications === null || ($medications->total() === 0 && !empty($query))) {
                $localMedications = Medication::query()
                    ->addSelect([
                        'medications.*',
                        'stock_total' => DB::table('stock_items')
                            ->selectRaw('COALESCE(SUM(quantity), 0)')
                            ->whereColumn('stock_items.medication_id', 'medications.id')
                            ->where('quantity', '>', 0),
                    ])
                    ->when(!empty($query), fn($q) => $q->where('name', 'ILIKE', '%' . $query . '%'))
                    ->orderBy('name', 'asc')
                    ->paginate(50)
                    ->appends(['q' => $query]);

                if ($localMedications->total() > 0) {
                    $medications = $localMedications;
                }
            }
        }

        return view('doctor.stock', compact('medications', 'query'));
    }
}

