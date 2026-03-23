<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saude Assai - Portal Publico</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
    <style>
        :root {
            --ink: #071b2a;
            --ink-soft: #25465f;
            --paper: #eef5fa;
            --glass: rgba(255, 255, 255, 0.7);
            --line: rgba(7, 27, 42, 0.12);
            --primary: #0a8f7b;
            --primary-deep: #056e60;
            --accent: #2db5ff;
            --warning: #ff8f3d;
        }

        * {
            font-family: 'Manrope', sans-serif;
        }

        body {
            background:
                radial-gradient(circle at 15% 10%, rgba(45, 181, 255, 0.28), transparent 36%),
                radial-gradient(circle at 88% 20%, rgba(10, 143, 123, 0.26), transparent 34%),
                linear-gradient(180deg, #f6fbff 0%, #edf4fa 42%, #edf4fa 100%);
            color: var(--ink);
            min-height: 100vh;
        }

        .headline {
            font-family: 'Sora', sans-serif;
            letter-spacing: -0.02em;
        }

        .glass {
            background: var(--glass);
            backdrop-filter: blur(10px);
            border: 1px solid var(--line);
            box-shadow: 0 16px 34px rgba(7, 27, 42, 0.08);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 1rem;
        }

        .metric-card {
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            width: 130px;
            height: 130px;
            background: radial-gradient(circle, rgba(45, 181, 255, 0.22) 0%, transparent 72%);
            top: -34px;
            right: -34px;
        }

        .pulse {
            position: relative;
        }

        .pulse::after {
            content: '';
            position: absolute;
            inset: -1px;
            border-radius: inherit;
            border: 1px solid rgba(255, 143, 61, 0.45);
            animation: pulse 2.4s ease-out infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.98); opacity: 0.8; }
            70% { transform: scale(1.02); opacity: 0.08; }
            100% { transform: scale(1.02); opacity: 0; }
        }

        .card-reveal {
            animation: reveal 0.6s ease both;
        }

        .card-reveal:nth-child(2) { animation-delay: 0.08s; }
        .card-reveal:nth-child(3) { animation-delay: 0.16s; }
        .card-reveal:nth-child(4) { animation-delay: 0.24s; }

        @keyframes reveal {
            from { transform: translateY(12px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        @media (max-width: 980px) {
            .hero-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="px-4 py-5 md:px-8">
        <div class="max-w-7xl mx-auto flex items-center justify-between glass rounded-2xl px-4 py-3">
            <div>
                <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Rede Municipal Inteligente</p>
                <h1 class="headline text-xl md:text-2xl font-bold">Saude Assai</h1>
            </div>
            <a href="{{ route('login') }}" class="rounded-xl bg-[var(--primary)] hover:bg-[var(--primary-deep)] text-white px-4 py-2 font-semibold transition">Acesso Profissional</a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 pb-10 md:px-8 space-y-6 md:space-y-8">
        <section class="hero-grid">
            <article class="glass rounded-3xl p-6 md:p-8">
                <p class="text-xs uppercase tracking-[0.24em] text-[var(--primary-deep)]">Portal do Cidadao</p>
                <h2 class="headline text-3xl md:text-5xl font-extrabold mt-2 leading-tight">Saude publica conectada, humana e transparente.</h2>
                <p class="mt-4 text-[var(--ink-soft)] text-base md:text-lg max-w-2xl">
                    Acompanhe noticias, alertas de saude e unidades de atendimento do municipio em uma experiencia moderna, clara e acessivel em qualquer dispositivo.
                </p>

                <div class="grid sm:grid-cols-3 gap-3 mt-6">
                    <div class="metric-card card-reveal glass rounded-2xl p-4">
                        <p class="text-xs uppercase tracking-widest text-slate-500">Atendimentos no mes</p>
                        <p class="headline text-3xl font-bold mt-1">{{ $publicMetrics['atendimentos_mes'] }}</p>
                    </div>
                    <div class="metric-card card-reveal glass rounded-2xl p-4">
                        <p class="text-xs uppercase tracking-widest text-slate-500">Entregas no mes</p>
                        <p class="headline text-3xl font-bold mt-1">{{ $publicMetrics['entregas_mes'] }}</p>
                    </div>
                    <div class="metric-card card-reveal glass rounded-2xl p-4">
                        <p class="text-xs uppercase tracking-widest text-slate-500">CO2 evitado</p>
                        <p class="headline text-3xl font-bold mt-1">{{ $publicMetrics['co2_nao_emitido_kg'] }} <span class="text-lg">kg</span></p>
                    </div>
                </div>
            </article>

            <aside class="space-y-4">
                <div class="glass rounded-3xl p-5 md:p-6">
                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Status da Rede</p>
                    <h3 class="headline text-2xl font-bold mt-2">Acesso rapido aos servicos</h3>
                    <div class="mt-4 space-y-2 text-sm text-[var(--ink-soft)]">
                        <p>Noticias atualizadas sobre campanhas e programas de saude.</p>
                        <p>Lista de unidades para atendimento no municipio.</p>
                        <p>Alertas prioritarios publicados pela administracao.</p>
                    </div>
                </div>

                @if ($featuredAlert)
                    <div class="pulse rounded-3xl bg-gradient-to-r from-orange-500 to-orange-400 text-white p-5 md:p-6 shadow-xl">
                        <p class="text-xs uppercase tracking-[0.24em]">Alerta em destaque</p>
                        <h4 class="headline text-xl font-bold mt-2">{{ $featuredAlert->title }}</h4>
                        <p class="mt-2 text-sm text-orange-50">{{ \Illuminate\Support\Str::limit($featuredAlert->body ?? '', 160) }}</p>
                    </div>
                @else
                    <div class="glass rounded-3xl p-5 md:p-6 border-dashed border-2 border-slate-300">
                        <p class="text-sm text-slate-600">Sem alerta prioritario no momento.</p>
                    </div>
                @endif
            </aside>
        </section>

        <section class="grid lg:grid-cols-3 gap-4 md:gap-6">
            <article class="lg:col-span-2 glass rounded-3xl p-5 md:p-6">
                <div class="flex items-center justify-between gap-2 mb-4">
                    <h3 class="headline text-2xl font-bold">Noticias da Saude</h3>
                    <span class="text-xs uppercase tracking-[0.2em] text-slate-500">Atualizacoes publicas</span>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    @forelse ($news as $item)
                        <article class="card-reveal rounded-2xl border border-[var(--line)] bg-white/75 p-4">
                            <p class="text-[10px] uppercase tracking-[0.2em] text-[var(--primary-deep)]">{{ strtoupper($item->type) }}</p>
                            <h4 class="headline text-lg font-semibold mt-2">{{ $item->title }}</h4>
                            <p class="text-sm text-slate-600 mt-2">{{ \Illuminate\Support\Str::limit($item->body ?? '', 180) }}</p>
                            <p class="text-xs text-slate-500 mt-3">{{ optional($item->published_at)->format('d/m/Y H:i') }}</p>
                        </article>
                    @empty
                        <p class="text-slate-600 sm:col-span-2">Sem noticias publicadas no momento.</p>
                    @endforelse
                </div>
            </article>

            <aside class="glass rounded-3xl p-5 md:p-6">
                <h3 class="headline text-2xl font-bold">Avisos e Comunicados</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($notices as $item)
                        <article class="rounded-xl bg-white/80 border border-[var(--line)] p-3">
                            <p class="text-[10px] uppercase tracking-[0.2em] text-slate-500">{{ strtoupper($item->type) }}</p>
                            <p class="font-semibold mt-1">{{ $item->title }}</p>
                            @if ($item->body)
                                <p class="text-sm text-slate-600 mt-1">{{ \Illuminate\Support\Str::limit($item->body, 90) }}</p>
                            @endif
                        </article>
                    @empty
                        <p class="text-sm text-slate-600">Sem comunicados no momento.</p>
                    @endforelse
                </div>
            </aside>
        </section>

        <section class="glass rounded-3xl p-5 md:p-6">
            <div class="flex items-end justify-between gap-3 mb-4">
                <div>
                    <p class="text-xs uppercase tracking-[0.24em] text-slate-500">Cobertura municipal</p>
                    <h3 class="headline text-2xl font-bold">Locais de atendimento</h3>
                </div>
                <span class="text-sm text-slate-600">{{ $healthUnits->count() }} unidade(s) ativa(s)</span>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse ($healthUnits as $unit)
                    <article class="rounded-2xl bg-white/80 border border-[var(--line)] p-4">
                        <p class="text-xs uppercase tracking-[0.2em] text-[var(--primary-deep)]">{{ $unit->kind }}</p>
                        <h4 class="headline text-lg font-semibold mt-1">{{ $unit->name }}</h4>
                        <p class="text-sm text-slate-600 mt-2">{{ $unit->address ?: 'Endereco nao informado.' }}</p>
                        <p class="text-sm text-slate-600 mt-1">{{ $unit->phone ?: 'Telefone nao informado.' }}</p>
                    </article>
                @empty
                    <p class="text-slate-600 sm:col-span-2 lg:col-span-3">Nenhuma unidade ativa cadastrada ate o momento.</p>
                @endforelse
            </div>
        </section>
    </main>
</body>
</html>
