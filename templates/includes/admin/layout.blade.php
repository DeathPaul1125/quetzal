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

  {{-- Tailwind + Preline --}}
  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/preline@2.4.1/dist/preline.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.min.css">

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
  </style>

  @stack('head')
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased">

<div class="min-h-screen flex flex-col lg:flex-row">
  @include('includes.admin.sidebar', ['user' => $user, 'roleSlug' => $roleSlug])

  <div class="flex-1 flex flex-col min-w-0">
    @include('includes.admin.topbar', ['user' => $user])

    <main class="flex-1 p-4 sm:p-6 lg:p-8">
      @if(class_exists('Flasher'))
        {!! Flasher::flash() !!}
      @endif

      @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white py-4 px-6 text-center text-xs text-slate-500">
      © {{ date('Y') }} {{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}
      · {{ defined('QUETZAL_NAME') ? QUETZAL_NAME : 'Quetzal' }} v{{ defined('QUETZAL_VERSION') ? QUETZAL_VERSION : '' }}
    </footer>
  </div>
</div>

{{-- Preline runtime --}}
<script src="https://cdn.jsdelivr.net/npm/preline@2.4.1/dist/preline.js"></script>
<script>
  // Toggle del sidebar en mobile
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-q-sidebar-toggle]');
    if (!btn) return;
    document.getElementById('q-sidebar').classList.toggle('hidden');
  });
</script>
@stack('scripts')
</body>
</html>
