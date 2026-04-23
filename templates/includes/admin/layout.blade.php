{{--
  Layout maestro del área de administración (Preline + Tailwind).
  Uso: @extends('includes.admin.layout')
--}}
@php
  $colors = theme_colors();
  $user   = get_user();
  $roleSlug = $user['role'] ?? 'guest';
@endphp
<!DOCTYPE html>
<html lang="{{ defined('SITE_LANG') ? SITE_LANG : 'es' }}" class="h-full">
<head>
  <meta charset="{{ defined('SITE_CHARSET') ? SITE_CHARSET : 'UTF-8' }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="{{ get_base_url() }}">
  <title>@yield('title', $title ?? 'Administración') — {{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}</title>

  {!! function_exists('get_favicon') ? get_favicon() : '' !!}

  {{-- Tailwind + Preline --}}
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/preline@2.4.1/dist/preline.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.min.css">

  {{-- Tablas mejoradas: búsqueda, filtros, paginación, items-por-página --}}
  <link rel="stylesheet" href="assets/css/q-data-table.css?v=1">
  <script src="assets/js/q-data-table.js?v=1" defer></script>

  <style>
    :root {
      --q-primary: {{ $colors['primary'] }};
      --q-primary-dark: {{ $colors['primary_dark'] }};
      --q-sidebar-bg: {{ $colors['sidebar_bg'] }};
      --q-sidebar-fg: {{ $colors['sidebar_fg'] }};
    }
    .btn-primary {
      background-color: var(--q-primary);
      color: #fff;
    }
    .btn-primary:hover { background-color: var(--q-primary-dark); }
    .text-primary { color: var(--q-primary); }
    .ring-primary:focus { --tw-ring-color: var(--q-primary); }
    .border-primary { border-color: var(--q-primary); }
    .bg-primary { background-color: var(--q-primary); }
    #q-sidebar { background-color: var(--q-sidebar-bg); color: var(--q-sidebar-fg); }
    #q-sidebar a { color: var(--q-sidebar-fg); }
    #q-sidebar a.active,
    #q-sidebar a:hover { background-color: rgba(255,255,255,.08); color: #fff; }
    #q-sidebar a.active { border-left: 3px solid var(--q-primary); }

    /* Flasher (Bootstrap alerts) → estilo Tailwind-ish */
    .alert { padding: .75rem 1rem; border-radius: .5rem; border: 1px solid; font-size: .875rem; display: flex; align-items: center; gap: .5rem; margin-bottom: 1rem; }
    .alert-danger, .alert-error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
    .alert-success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
    .alert-warning { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .alert-info, .alert-primary { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
    .alert .btn-close { display: none; }

    /* Paginador Bootstrap (de PaginationHandler) → estilo Tailwind horizontal */
    .pagination { display: inline-flex; flex-wrap: wrap; gap: .25rem; padding: 0; margin: 0; list-style: none; }
    .pagination .page-item { margin: 0; }
    .pagination .page-link {
      display: inline-flex; align-items: center; justify-content: center;
      min-width: 2rem; height: 2rem; padding: 0 .5rem;
      border-radius: .375rem; border: 1px solid #e2e8f0;
      color: #475569; text-decoration: none; font-size: .8125rem;
      background: #fff; transition: background-color .15s, border-color .15s;
    }
    .pagination .page-link:hover { background: #f8fafc; border-color: #cbd5e1; }
    .pagination .page-item.active .page-link {
      background: var(--q-primary); color: #fff; border-color: var(--q-primary);
    }
    .pagination .page-item.disabled .page-link {
      color: #cbd5e1; pointer-events: none; background: #f8fafc;
    }
    .quetzal-pagination-wrapper { margin-top: 0 !important; }
  </style>

  {{-- Si estamos dentro de un iframe, ocultar sidebar/topbar/footer vía CSS
       y marcar <html class="is-framed"> lo antes posible para evitar flash de
       layout completo. Esto mantiene el modo frame aún si el usuario navega
       dentro del iframe y pierde el query param frame=1. --}}
  <style>
    html.is-framed #q-sidebar,
    html.is-framed .q-topbar,
    html.is-framed .q-footer,
    html.is-framed [data-q-sidebar-toggle] { display: none !important; }
    html.is-framed .q-main { padding: 1rem !important; }
    html.is-framed body > div.min-h-screen { flex-direction: column !important; }
  </style>
  <script>
    // Ejecutar síncrono antes del render para evitar flash
    try { if (window.self !== window.top) { document.documentElement.classList.add('is-framed'); } } catch(e) {}
  </script>
  @stack('head')
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased">

@php
  // Modo "frame": renderizar sin sidebar/topbar/footer. Útil para cargar
  // páginas dentro de un iframe (ej. QuetzalOS desktop).
  $frameMode = !empty($_GET['frame']) && $_GET['frame'] === '1';
@endphp

@if($frameMode)
  <main class="p-4 sm:p-6">
    @if(class_exists('Flasher')){!! Flasher::flash() !!}@endif
    @yield('content')
  </main>
@else
<div class="min-h-screen flex flex-col lg:flex-row">
  @include('includes.admin.sidebar', ['user' => $user, 'roleSlug' => $roleSlug])

  <div class="flex-1 flex flex-col min-w-0">
    @include('includes.admin.topbar', ['user' => $user])

    <main class="q-main flex-1 p-4 sm:p-6 lg:p-8">
      {{-- Hook: plugins pueden inyectar banners/alertas globales aquí --}}
      @if(class_exists('QuetzalHookManager'))
        @foreach(QuetzalHookManager::getHookData('admin_before_content') as $html)
          @if(is_string($html) && $html !== '')
            {!! $html !!}
          @endif
        @endforeach
      @endif

      @if(class_exists('Flasher'))
        {!! Flasher::flash() !!}
      @endif

      @yield('content')
    </main>

    <footer class="q-footer border-t border-slate-200 bg-white py-4 px-6 text-center text-xs text-slate-500">
      © {{ date('Y') }} {{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}
      · {{ defined('QUETZAL_NAME') ? QUETZAL_NAME : 'Quetzal' }} v{{ defined('QUETZAL_VERSION') ? QUETZAL_VERSION : '' }}
    </footer>
  </div>
</div>
@endif

{{-- Objeto global Quetzal para JS (incluye CSRF token, URLs, etc.) --}}
{!! function_exists('load_quetzal_obj') ? load_quetzal_obj() : '' !!}

{{-- Preline runtime (cargado pero no confiamos 100%, hacemos fallbacks propios) --}}
<script src="https://cdn.jsdelivr.net/npm/preline@2.4.1/dist/preline.js"></script>
<script>
(function() {
  'use strict';

  // Toggle del sidebar en mobile
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-q-sidebar-toggle]');
    if (!btn) return;
    document.getElementById('q-sidebar').classList.toggle('hidden');
  });

  // ============================================================
  //  Dropdowns (.hs-dropdown) — handler vanilla independiente de Preline
  //  Funciona con la misma estructura HTML que Preline usa:
  //    <div class="hs-dropdown">
  //      <button class="hs-dropdown-toggle">...</button>
  //      <div class="hs-dropdown-menu hidden">...</div>
  //    </div>
  // ============================================================
  document.addEventListener('click', (e) => {
    const toggle = e.target.closest('.hs-dropdown-toggle');

    // Cerrar todos los dropdowns abiertos menos el del click
    document.querySelectorAll('.hs-dropdown.q-open').forEach(dd => {
      if (toggle && dd.contains(toggle)) return;
      closeDropdown(dd);
    });

    if (!toggle) return;
    e.preventDefault();
    const dropdown = toggle.closest('.hs-dropdown');
    if (!dropdown) return;
    dropdown.classList.contains('q-open') ? closeDropdown(dropdown) : openDropdown(dropdown);
  });

  function openDropdown(dd) {
    dd.classList.add('q-open');
    const menu = dd.querySelector('.hs-dropdown-menu');
    if (menu) {
      menu.classList.remove('hidden');
      menu.classList.add('opacity-100');
      menu.classList.remove('opacity-0');
    }
  }
  function closeDropdown(dd) {
    dd.classList.remove('q-open');
    const menu = dd.querySelector('.hs-dropdown-menu');
    if (menu) {
      menu.classList.add('hidden');
      menu.classList.remove('opacity-100');
      menu.classList.add('opacity-0');
    }
  }

  // Esc cierra dropdowns
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.hs-dropdown.q-open').forEach(closeDropdown);
    }
  });
})();
</script>
@stack('scripts')
</body>
</html>
