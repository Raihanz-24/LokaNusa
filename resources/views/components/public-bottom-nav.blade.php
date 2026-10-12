@props([])

@php
    // Item bottom navbar (mobile). Peta & Favorit tidak ditampilkan di sini
    // agar tombol tidak terlalu banyak (tetap dapat diakses lewat menu).
    $items = [
        ['label' => __('ui.nav_home'), 'icon' => 'Home', 'route' => 'home', 'active' => request()->routeIs('home')],
        ['label' => __('ui.nav_destinations'), 'icon' => 'MapPin', 'route' => 'destinations.index', 'active' => request()->routeIs('destinations.*')],
        ['label' => __('ui.nav_trip_planner_short'), 'icon' => 'Sparkles', 'route' => auth()->check() ? 'trip-planner.index' : 'login', 'active' => request()->routeIs('trip-planner.*')],
        ['label' => __('ui.nav_plans'), 'icon' => 'Map', 'route' => auth()->check() ? 'itineraries.index' : 'login', 'active' => request()->routeIs('itineraries.*')],
        ['label' => __('ui.nav_profile'), 'icon' => 'UserCircle', 'route' => auth()->check() ? 'profile.edit' : 'login', 'active' => request()->routeIs('profile.*')],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-40 md:hidden"
     style="padding-bottom: env(safe-area-inset-bottom);"
     aria-label="Navigasi bawah">
    <div class="mx-auto max-w-md px-3">
        <div class="mb-2 grid grid-cols-5 gap-1 rounded-3xl border border-slate-200/70 bg-white/90 p-1.5 shadow-lg shadow-slate-900/10 ring-1 ring-black/[0.02] backdrop-blur-md">
            @foreach ($items as $item)
                <a href="{{ route($item['route']) }}"
                   @if ($item['active']) aria-current="page" @endif
                   class="group relative flex flex-col items-center gap-1 rounded-2xl px-1 py-1.5 text-[10px] font-semibold leading-none transition
                          {{ $item['active'] ? 'text-emerald-700' : 'text-slate-500 hover:text-emerald-700' }}">
                    <span class="grid h-9 w-12 place-items-center rounded-2xl transition-all duration-200
                                 {{ $item['active']
                                        ? 'bg-gradient-to-br from-emerald-500 to-emerald-700 text-white shadow-md shadow-emerald-600/30'
                                        : 'text-slate-500 group-hover:bg-slate-100 group-active:scale-95' }}">
                        <x-heroicon :name="$item['icon']" class="h-5 w-5" />
                    </span>
                    <span class="max-w-full truncate text-center">{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</nav>
