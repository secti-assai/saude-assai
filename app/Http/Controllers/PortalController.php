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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PortalController extends Controller
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function index(): View
    {
        $allContents = PortalContent::where('published', true)->latest('published_at')->latest()->get();

        // 1. Pega TODOS os Alertas que estão publicados (ON)
        $featuredAlerts = $allContents
            ->filter(fn (PortalContent $content): bool => $content->type === 'Alerta')
            ->values();

        // Filtra as Notícias
        $news = $allContents
            ->filter(fn (PortalContent $content): bool => in_array($content->type, ['Notícia', 'Campanha']))
            ->take(6)
            ->values();

        // Filtra os Avisos
        $notices = $allContents
            ->filter(fn (PortalContent $content): bool => $content->type === 'Aviso')
            ->take(6)
            ->values();

        $healthUnits = HealthUnit::where('is_active', true)->orderBy('name')->take(9)->get();

        $publicMetrics = [
            'atendimentos_mes' => Attendance::whereMonth('created_at', now()->month)->count(),
            'entregas_mes' => Delivery::whereMonth('created_at', now()->month)->count(),
            'co2_nao_emitido_kg' => round(Delivery::whereMonth('created_at', now()->month)->count() * 0.12, 2),
        ];

        // 2. ATENÇÃO: Troque 'featuredAlert' por 'featuredAlerts' aqui no compact!
        return view('portal.index', compact('publicMetrics', 'featuredAlerts', 'news', 'notices', 'healthUnits'));
    }

    public function adminIndex(): View
    {
        $contents = PortalContent::latest()->paginate(10);
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
            ->whereIn('type', ['Notícia', 'Campanha'])
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('portal.news.show', compact('news', 'otherNews'));
    }

    public function newsIndex(): View
    {
        // Busca muito mais limpa e eficiente direto no banco de dados
        $news = PortalContent::where('published', true)
            ->whereIn('type', ['Notícia', 'Campanha'])
            ->latest('published_at')
            ->paginate(12);

        return view('portal.news.index', compact('news'));
    }

    public function edit(PortalContent $content): View
    {
        return view('admin.portal-edit', compact('content'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
            'published' => ['nullable', 'boolean'],
        ]);

        $coverImagePath = null;
        if ($request->hasFile('cover_image')) {
            $coverImagePath = $request->file('cover_image')->store('portal-images', 'public');
        }

        $content = PortalContent::create([
            'type' => $data['type'],
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? Str::limit(strip_tags($data['body'] ?? ''), 150),
            'body' => $data['body'],
            'cover_image' => $coverImagePath,
            'published' => (bool) ($data['published'] ?? true),
            'published_at' => now(),
        ]);

        $this->audit->log($request, 'M2', 'CRIAR_CONTEUDO', PortalContent::class, $content->id);

        return back()->with('status', 'Conteúdo publicado com sucesso.');
    }

    public function update(Request $request, PortalContent $content): RedirectResponse
    {
         $data = $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
            'published' => ['nullable', 'boolean'],
        ]);

        $updateData = [
            'type' => $data['type'],
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? Str::limit(strip_tags($data['body'] ?? ''), 150),
            'body' => $data['body'],
            'published' => (bool) ($data['published'] ?? true),
        ];

        if ($request->hasFile('cover_image')) {
            if ($content->cover_image) {
                Storage::disk('public')->delete($content->cover_image);
            }
            $updateData['cover_image'] = $request->file('cover_image')->store('portal-images', 'public');
        }

        $content->update($updateData);

        $this->audit->log($request, 'M2', 'ATUALIZAR_CONTEUDO', PortalContent::class, (string) $content->id);

        return redirect()->route('admin.portal')->with('status', 'Conteúdo atualizado com sucesso.');
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:2048',
        ]);

        $path = $request->file('image')->store('portal-images/body', 'public');

        return response()->json(['url' => Storage::url($path)]);
    }

    public function destroy(Request $request, PortalContent $content): RedirectResponse
    {
        $this->audit->log($request, 'M2', 'DELETAR_CONTEUDO', PortalContent::class, $content->id);
        
        // Garante que a imagem é deletada da pasta do servidor quando a notícia for apagada
        if ($content->cover_image) {
            Storage::disk('public')->delete($content->cover_image);
        }
        
        $content->delete();

        return back()->with('status', 'Conteúdo removido com sucesso.');
    }

    public function togglePublish(Request $request, PortalContent $content): RedirectResponse
    {
        // Inverte o status atual (Se for true vira false, se for false vira true)
        $content->update([
            'published' => !$content->published
        ]);

        $statusName = $content->published ? 'publicado' : 'ocultado';
        $this->audit->log($request, 'M2', 'ATUALIZAR_CONTEUDO', PortalContent::class, (string) $content->id);

        return back()->with('status', "Conteúdo {$statusName} com sucesso.");
    }
}