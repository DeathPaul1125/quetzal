@php $colors = theme_colors(); @endphp
<!DOCTYPE html>
<html lang="{{ defined('SITE_LANG') ? SITE_LANG : 'es' }}" class="h-full">
<head>
  <meta charset="{{ defined('SITE_CHARSET') ? SITE_CHARSET : 'UTF-8' }}">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <base href="{{ get_base_url() }}">
  <title>{{ $code ?? 'Error' }} — {{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}</title>
  {!! function_exists('get_favicon') ? get_favicon() : '' !!}
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.min.css">
  <style>
    :root { --q-primary: {{ $colors['primary'] }}; --q-primary-dark: {{ $colors['primary_dark'] }}; }
    .btn-primary { background-color: var(--q-primary); color: #fff; }
    .btn-primary:hover { background-color: var(--q-primary-dark); }
  </style>
</head>
<body class="h-full min-h-screen bg-gradient-to-br from-slate-50 via-white to-slate-100 text-slate-800 antialiased">

<div class="min-h-screen flex items-center justify-center p-6">
  <div class="text-center max-w-md">
    <a href="{{ get_base_url() }}">
      <img src="{{ get_quetzal_logo() }}" alt="{{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}" class="w-24 h-24 mx-auto mb-6 opacity-80">
    </a>

    <div class="text-7xl sm:text-8xl font-black mb-2" style="color: var(--q-primary);">
      {{ $code ?? 404 }}
    </div>
    <h1 class="text-2xl font-bold text-slate-800 mb-2">Página no encontrada</h1>
    <p class="text-slate-500 mb-8">Entraste a otra dimensión.</p>

    <a href="{{ function_exists('get_default_controller') ? get_default_controller() : get_base_url() }}"
       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg btn-primary font-semibold text-sm">
      <i class="ri-arrow-go-back-line"></i> Regresar al inicio
    </a>
  </div>
</div>

</body>
</html>
