{{-- Sidebar Navigation --}}
@php
    $user = Auth::user();
    $role = $user->role ?? '';
    $navItems = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'chart-bar', 'roles' => '*'],
        ['route' => 'reception.index', 'match' => 'reception.*', 'label' => 'Recepção', 'icon' => 'clipboard-list', 'roles' => 'recepcionista,admin_secti'],
        ['route' => 'triage.index', 'match' => 'triage.*', 'label' => 'Triagem', 'icon' => 'heart', 'roles' => 'enfermeiro,admin_secti'],
        ['route' => 'prescriptions.index', 'match' => 'prescriptions.*', 'label' => 'Prescrições', 'icon' => 'document-text', 'roles' => 'medico_ubs,medico_hospital,admin_secti'],
        ['route' => 'pharmacy.index', 'match' => 'pharmacy.*', 'label' => 'Farmácia', 'icon' => 'beaker', 'roles' => 'farmaceutico,admin_secti'],
        ['route' => 'hospital.index', 'match' => 'hospital.*', 'label' => 'Hospital', 'icon' => 'building-office', 'roles' => 'medico_hospital,enfermeiro,admin_secti'],
        ['route' => 'deliveries.index', 'match' => 'deliveries.*', 'label' => 'Entregas', 'icon' => 'truck', 'roles' => 'entregador,admin_secti,farmaceutico'],
        ['route' => 'reports.conformity', 'match' => 'reports.*', 'label' => 'Relatórios', 'icon' => 'chart-pie', 'roles' => 'admin_secti,gestor,auditor'],
        ['route' => 'admin.users', 'match' => 'admin.*', 'label' => 'Administração', 'icon' => 'cog-6-tooth', 'roles' => 'admin_secti'],
    ];
@endphp

{{-- Desktop sidebar --}}
<aside class="sa-sidebar hidden lg:flex" id="sa-sidebar-desktop">
    {{-- Brand --}}
    <div class="px-5 py-5 flex items-center gap-3 border-b border-white/10">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-extrabold text-sm"
             style="background: var(--sa-primary);">
            SA
        </div>
        <div>
            <p class="text-white font-bold text-sm leading-tight">Saúde Assaí</p>
            <p class="text-gray-400 text-[10px] uppercase tracking-widest">SECTI</p>
        </div>
    </div>

    {{-- Nav Links --}}
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        @foreach ($navItems as $item)
            @php
                $allowed = $item['roles'] === '*' || collect(explode(',', $item['roles']))->contains($role);
                $active = request()->routeIs($item['match']);
            @endphp
            @if ($allowed)
                <a href="{{ route($item['route']) }}"
                   class="sa-sidebar-link {{ $active ? 'active' : '' }}">
                    @include('layouts.partials.nav-icon', ['icon' => $item['icon']])
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    {{-- User Footer --}}
    <div class="px-4 py-4 border-t border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold text-white"
                 style="background: var(--sa-primary);">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-white text-sm font-medium truncate">{{ $user->name }}</p>
                <p class="text-gray-400 text-[11px] truncate">{{ str_replace('_', ' ', ucfirst($role)) }}</p>
            </div>
            <x-dropdown align="bottom-end" width="48">
                <x-slot name="trigger">
                    <button class="text-gray-400 hover:text-white transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zm0 5.25a.75.75 0 110-1.5.75.75 0 010 1.5zm0 5.25a.75.75 0 110-1.5.75.75 0 010 1.5z"/>
                        </svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">Meu Perfil</x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                            Sair
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</aside>

{{-- Mobile top-bar --}}
<div class="lg:hidden fixed top-0 left-0 right-0 z-50 flex items-center justify-between h-14 px-4 bg-sa-ink text-white" id="sa-mobile-topbar">
    <div class="flex items-center gap-2">
        <button @click="$store.sidebar.open = !$store.sidebar.open" class="p-1.5 rounded-lg hover:bg-white/10 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
            </svg>
        </button>
        <span class="font-bold text-sm">Saúde Assaí</span>
    </div>
    <div class="text-xs text-gray-400">{{ $user->name }}</div>
</div>

{{-- Mobile slide-in sidebar --}}
<aside x-data x-show="$store.sidebar.open"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="-translate-x-full"
       x-transition:enter-end="translate-x-0"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="translate-x-0"
       x-transition:leave-end="-translate-x-full"
       class="sa-sidebar lg:hidden"
       style="display:none;">
    <div class="px-5 py-5 flex items-center justify-between border-b border-white/10">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-extrabold text-sm"
                 style="background: var(--sa-primary);">SA</div>
            <p class="text-white font-bold text-sm">Saúde Assaí</p>
        </div>
        <button @click="$store.sidebar.open = false" class="text-gray-400 hover:text-white transition p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        @foreach ($navItems as $item)
            @php
                $allowed = $item['roles'] === '*' || collect(explode(',', $item['roles']))->contains($role);
                $active = request()->routeIs($item['match']);
            @endphp
            @if ($allowed)
                <a href="{{ route($item['route']) }}"
                   class="sa-sidebar-link {{ $active ? 'active' : '' }}"
                   @click="$store.sidebar.open = false">
                    @include('layouts.partials.nav-icon', ['icon' => $item['icon']])
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>
</aside>
