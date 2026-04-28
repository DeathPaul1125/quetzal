@php
  $colors   = theme_colors();
  $branding = function_exists('branding_info') ? branding_info() : ['site_name' => defined('SITE_NAME') ? SITE_NAME : 'Quetzal', 'tagline' => '', 'login_welcome' => '¡Bienvenido!', 'login_subtitle' => 'Iniciá sesión.', 'logo' => '', 'login_bg' => '', 'favicon' => ''];
  $logoUrl  = !empty($branding['logo'])    ? branding_asset_url($branding['logo'])    : (function_exists('get_quetzal_logo') ? get_quetzal_logo() : '');
  $loginBg  = !empty($branding['login_bg']) ? branding_asset_url($branding['login_bg']) : '';
@endphp
<!DOCTYPE html>
<html lang="{{ defined('SITE_LANG') ? SITE_LANG : 'es' }}" class="h-full">
<head>
  <meta charset="{{ defined('SITE_CHARSET') ? SITE_CHARSET : 'UTF-8' }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="{{ get_base_url() }}">
  <title>{{ $title ?? 'Ingresar' }} — {{ $branding['site_name'] }}</title>

  @if(!empty($branding['favicon']))
    <link rel="icon" type="image/{{ pathinfo($branding['favicon'], PATHINFO_EXTENSION) === 'svg' ? 'svg+xml' : pathinfo($branding['favicon'], PATHINFO_EXTENSION) }}" href="{{ branding_asset_url($branding['favicon']) }}">
  @else
    {!! function_exists('get_favicon') ? get_favicon() : '' !!}
  @endif

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
  <div class="hidden lg:flex flex-col items-center justify-center p-10 relative overflow-hidden"
       @if($loginBg)
       style="background: linear-gradient(135deg, {{ $colors['primary'] }}cc 0%, {{ $colors['primary_dark'] }}cc 100%), url('{{ $loginBg }}') center/cover no-repeat;"
       @else
       style="background: linear-gradient(135deg, {{ $colors['primary'] }} 0%, {{ $colors['primary_dark'] }} 100%);"
       @endif>

    {{-- Patrón decorativo sutil (solo si NO hay imagen custom) --}}
    @if(!$loginBg)
      <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(circle at 20% 30%, #fff 1px, transparent 2px), radial-gradient(circle at 80% 70%, #fff 1px, transparent 2px); background-size: 40px 40px;"></div>
    @endif

    <div class="relative text-center text-white max-w-md">
      @if($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $branding['site_name'] }}" class="w-28 h-28 mx-auto mb-6 bg-white/15 rounded-2xl p-3 backdrop-blur ring-1 ring-white/20 object-contain">
      @endif
      <h1 class="text-3xl font-bold mb-3 drop-shadow">{{ $branding['login_welcome'] ?: $branding['site_name'] }}</h1>
      <p class="opacity-90 leading-relaxed drop-shadow">
        {{ $branding['login_subtitle'] ?: ((defined('SITE_DESC') && SITE_DESC) ? SITE_DESC : 'Framework PHP ligero, flexible y fácil de implementar.') }}
      </p>
      <div class="mt-8 text-xs opacity-75">
        Powered by {{ defined('QUETZAL_NAME') ? QUETZAL_NAME : 'Quetzal' }} v{{ defined('QUETZAL_VERSION') ? QUETZAL_VERSION : '' }}
      </div>
    </div>
  </div>

  {{-- Formulario --}}
  <div class="flex items-center justify-center p-6 sm:p-10">
    <div class="w-full max-w-md">

      {{-- Logo mobile --}}
      @if($logoUrl)
        <div class="lg:hidden text-center mb-6">
          <img src="{{ $logoUrl }}" alt="{{ $branding['site_name'] }}" class="w-16 h-16 mx-auto object-contain">
        </div>
      @endif

      <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/60 ring-1 ring-slate-200/60 p-8">
        <h2 class="text-2xl font-bold text-slate-800">{{ $branding['login_welcome'] ?: 'Ingresar' }}</h2>
        <p class="text-sm text-slate-500 mt-1">{{ $branding['login_subtitle'] ?: 'Accede a tu panel de administración.' }}</p>

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
        © {{ date('Y') }} {{ $branding['site_name'] }}
      </p>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/preline@2.4.1/dist/preline.js"></script>
</body>
</html>
