<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Delivery;
use App\Models\HealthUnit;
use App\Models\PortalContent;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(): View
    {
        $allContents = PortalContent::where('published', true)->latest('published_at')->latest()->get();

        $featuredAlert = $allContents
            ->first(fn (PortalContent $content): bool => str_contains(strtolower($content->type), 'alerta'));

        $news = $allContents
            ->filter(fn (PortalContent $content): bool => str_contains(strtolower($content->type), 'noticia'))
            ->take(6)
            ->values();

        $notices = $allContents
            ->reject(fn (PortalContent $content): bool => str_contains(strtolower($content->type), 'noticia'))
            ->take(6)
            ->values();

        $healthUnits = HealthUnit::where('is_active', true)->orderBy('name')->take(9)->get();

        $publicMetrics = [
            'atendimentos_mes' => Attendance::whereMonth('created_at', now()->month)->count(),
            'entregas_mes' => Delivery::whereMonth('created_at', now()->month)->count(),
            'co2_nao_emitido_kg' => round(Delivery::whereMonth('created_at', now()->month)->count() * 0.12, 2),
        ];

        return view('portal.index', compact('publicMetrics', 'featuredAlert', 'news', 'notices', 'healthUnits'));
    }

    public function adminIndex(): View
    {
        $contents = PortalContent::latest()->get();
        return view('admin.portal', compact('contents'));
    }

    public function units(): View
    {
        $healthUnits = HealthUnit::where('is_active', true)->orderBy('name')->get();
        return view('portal.units', compact('healthUnits'));
    }

    public function showNews($id): View
    {
        $news = PortalContent::findOrFail($id);
        
        $otherNews = PortalContent::where('published', true)
            ->where('id', '!=', $id)
            ->where('type', 'like', '%not%')
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('portal.news.show', compact('news', 'otherNews'));
    }

    public function newsIndex(): View
    {
        $news = PortalContent::where('published', true)
            ->where(function($query) {
                $query->where('type', 'like', '%notícia%')
                      ->orWhere('type', 'like', '%not%')
                      ->orWhere('type', 'like', '%campanha%');
            })
            ->latest('published_at')
            ->paginate(12);

        return view('portal.news.index', compact('news'));
    }

    public function edit(PortalContent $content): View
    {
        return view('admin.portal-edit', compact('content'));
    }

    public function update(Request $request, PortalContent $content): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'published' => ['nullable', 'boolean'],
        ]);

        $content->update([
            ...$data,
            'published' => (bool) ($data['published'] ?? true),
        ]);

        $this->audit->log($request, 'M2', 'ATUALIZAR_CONTEUDO', PortalContent::class, (string) $content->id);

        return redirect()->route('admin.portal')->with('status', 'Conteúdo atualizado com sucesso.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'published' => ['nullable', 'boolean'],
        ]);

        $content = PortalContent::create([
            ...$data,
            'published' => (bool) ($data['published'] ?? true),
            'published_at' => now(),
        ]);

        $this->audit->log($request, 'M2', 'CRIAR_CONTEUDO', PortalContent::class, $content->id);

        return back()->with('status', 'Conteudo publicado com sucesso.');
    }

    public function destroy(Request $request, PortalContent $content): RedirectResponse
    {
        $this->audit->log($request, 'M2', 'DELETAR_CONTEUDO', PortalContent::class, $content->id);
        $content->delete();

        return back()->with('status', 'Conteudo removido com sucesso.');
    }
}
