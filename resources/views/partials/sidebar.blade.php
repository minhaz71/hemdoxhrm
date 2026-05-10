{{--
    Dynamic Sidebar Partial
    Rendered once per request; menu list is cached via MenuService.
--}}
@auth
@php
    $sidebarMenus = app(\App\Services\MenuService::class)->forUser(auth()->user());
@endphp

@foreach($sidebarMenus as $menu)

    @if($menu->type === 'header')
        <div class="nav-label">{{ $menu->label }}</div>

    @elseif($menu->type === 'divider')
        <hr style="border-color:rgba(255,255,255,.08);margin:6px 16px;">

    @else{{-- link --}}
        <a href="{{ $menu->url() }}"
           class="nav-link {{ $menu->isActive() ? 'active' : '' }}">
            @if($menu->icon)
                <i class="bi {{ $menu->icon }}"></i>
            @endif
            {{ $menu->label }}
        </a>

    @endif

@endforeach
@endauth
