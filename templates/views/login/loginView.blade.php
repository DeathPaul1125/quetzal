@php
  $colors = theme_colors();
@endphp
<!DOCTYPE html>
<html lang="{{ defined('SITE_LANG') ? SITE_LANG : 'es' }}" class="h-full">
<head>
  <meta charset="{{ defined('SITE_CHARSET') ? SITE_CHARSET : 'UTF-8' }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="{{ get_base_url() }}">
  <title>{{ $title ?? 'Ingresar' }} — {{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}</title>

  {!! function_exists('get_favicon') ? get_favicon() : '' !!}

  <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/preline@2.4.1/dist/preline.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.min.css">

  <style>
    :root {
      --q-primary: {{ $colors['primary'] }};
      --q-primary-dark: {{ $colors['primary_dark'] }};
    }
    .btn-primary { background-color: var(--q-primary); color: #fff; }
    .btn-primary:hover { background-color: var(--q-primary-dark); }
    .text-primary { color: var(--q-primary); }
    .focus\:border-primary:focus { border-color: var(--q-primary); }
    .focus\:ring-primary:focus { --tw-ring-color: var(--q-primary); }
    /* Flasher (Bootstrap alerts) → Tailwind-ish */
    .alert { padding: .75rem 1rem; border-radius: .5rem; border: 1px solid; font-size: .875rem; display: flex; align-items: center; gap: .5rem; }
    .alert-danger, .alert-error { background: #fef2f2; color: #991b1b; border-color: #fecaca; }
    .alert-success { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
    .alert-warning { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .alert-info, .alert-primary { background: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
    .alert .btn-close { display: none; }
  </style>
</head>
<body class="h-full min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 text-slate-800 antialiased">

<div class="min-h-screen grid lg:grid-cols-2">

  {{-- Panel izquierdo decorativo (solo desktop) --}}
  <div class="hidden lg:flex flex-col items-center justify-center p-10"
       style="background: linear-gradient(135deg, {{ $colors['primary'] }} 0%, {{ $colors['primary_dark'] }} 100%);">
    <div class="text-center text-white max-w-md">
      <img src="{{ get_quetzal_logo() }}" alt="{{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}" class="w-24 h-24 mx-auto mb-6 bg-white/10 rounded-xl p-3 backdrop-blur">
      <h1 class="text-3xl font-bold mb-3">{{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}</h1>
      <p class="opacity-90 leading-relaxed">
        Framework PHP ligero, flexible y fácil de implementar.
      </p>
      <div class="mt-8 text-xs opacity-75">
        {{ defined('QUETZAL_NAME') ? QUETZAL_NAME : 'Quetzal' }} v{{ defined('QUETZAL_VERSION') ? QUETZAL_VERSION : '' }}
      </div>
    </div>
  </div>

  {{-- Formulario --}}
  <div class="flex items-center justify-center p-6 sm:p-10">
    <div class="w-full max-w-md">

      {{-- Logo mobile --}}
      <div class="lg:hidden text-center mb-6">
        <img src="{{ get_quetzal_logo() }}" alt="{{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}" class="w-16 h-16 mx-auto">
      </div>

      <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 ring-1 ring-slate-200/60 p-8">
        <h2 class="text-2xl font-bold text-slate-800">Ingresar</h2>
        <p class="text-sm text-slate-500 mt-1">Accede a tu panel de administración.</p>

        @if(class_exists('Flasher'))
          <div class="mt-5">{!! Flasher::flash() !!}</div>
        @endif

        <form method="post" action="login/post_login" class="mt-6 space-y-4" novalidate>
          @csrf

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="usuario">Usuario</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <i class="ri-user-line"></i>
              </span>
              <input type="text" id="usuario" name="usuario" required autofocus
                     autocomplete="username"
                     class="w-full pl-10 pr-3 py-2.5 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
                     placeholder="admin">
            </div>
            @if((function_exists('is_demo') && is_demo()) || (function_exists('is_local') && is_local()))
              <p class="text-xs text-slate-500 mt-1">Usuario por defecto: <code class="bg-slate-100 px-1 py-0.5 rounded">admin</code></p>
            @endif
          </div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="password">Contraseña</label>
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <i class="ri-lock-line"></i>
              </span>
              <input type="password" id="password" name="password" required
                     autocomplete="current-password"
                     class="w-full pl-10 pr-10 py-2.5 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
                     placeholder="••••••••">
              <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600"
                      onclick="(function(){var i=document.getElementById('password');i.type=i.type==='password'?'text':'password';})()">
                <i class="ri-eye-line"></i>
              </button>
            </div>
            @if((function_exists('is_demo') && is_demo()) || (function_exists('is_local') && is_local()))
              <p class="text-xs text-slate-500 mt-1">Contraseña por defecto: <code class="bg-slate-100 px-1 py-0.5 rounded">123456</code></p>
            @endif
          </div>

          <button type="submit"
                  class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg btn-primary font-semibold text-sm transition">
            <i class="ri-fingerprint-line"></i> Ingresar
          </button>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-100 flex items-center justify-between text-sm">
          <a href="login" class="text-slate-500 hover:text-slate-800">¿Olvidaste tu contraseña?</a>
          <a href="{{ build_url('quetzal/generate-user') }}" class="text-primary hover:underline">Crear cuenta</a>
        </div>
      </div>

      <p class="text-center text-xs text-slate-400 mt-6">
        © {{ date('Y') }} {{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}
      </p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/preline@2.4.1/dist/preline.js"></script>
</body>
</html>
