@extends('includes.admin.layout')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')

@php
  // Visibilidad por defecto de los bloques del dashboard. Los plugins pueden
  // sobrescribir esta decisión devolviendo ['stats' => bool, 'welcome' => bool]
  // desde el hook 'dashboard_show_defaults'.
  $showDefaultStats   = true;
  $showDefaultWelcome = true;

  if (class_exists('QuetzalHookManager')) {
    foreach (QuetzalHookManager::getHookData('dashboard_show_defaults') as $res) {
      if (is_array($res)) {
        if (isset($res['stats']))   $showDefaultStats   = (bool) $res['stats'];
        if (isset($res['welcome'])) $showDefaultWelcome = (bool) $res['welcome'];
      }
    }
  }
@endphp

@section('content')
<div class="space-y-6">

  {{-- Hook: contenido ANTES del dashboard (ej. alertas de plugins) --}}
  @if(class_exists('QuetzalHookManager'))
    @foreach(QuetzalHookManager::getHookData('dashboard_before') as $html)
      @if(is_string($html) && $html !== '')
        {!! $html !!}
      @endif
    @endforeach
  @endif

  {{-- Stats cards (por defecto) --}}
  @if($showDefaultStats)
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    @php
      $stats = [
        ['label' => 'Usuarios',    'value' => method_exists('userModel','count') ? userModel::count() : '—', 'icon' => 'ri-user-line',     'tint' => 'bg-blue-50 text-blue-600'],
        ['label' => 'Productos',   'value' => method_exists('productoModel','count') ? productoModel::count() : '—', 'icon' => 'ri-archive-line',  'tint' => 'bg-emerald-50 text-emerald-600'],
        ['label' => 'Roles',       'value' => count((new QuetzalRoleManager())->getRoles() ?: []), 'icon' => 'ri-shield-user-line', 'tint' => 'bg-amber-50 text-amber-600'],
        ['label' => 'Plugins',     'value' => count(QuetzalPluginManager::getInstance()->getEnabled()), 'icon' => 'ri-plug-line', 'tint' => 'bg-purple-50 text-purple-600'],
      ];
    @endphp
    @foreach($stats as $s)
      <div class="bg-white rounded-xl border border-slate-200 p-5 flex items-center justify-between">
        <div>
          <div class="text-xs uppercase tracking-wider text-slate-500">{{ $s['label'] }}</div>
          <div class="text-2xl font-bold mt-1">{{ $s['value'] }}</div>
        </div>
        <div class="w-12 h-12 rounded-lg flex items-center justify-center {{ $s['tint'] }}">
          <i class="{{ $s['icon'] }} text-xl"></i>
        </div>
      </div>
    @endforeach
  </div>
  @endif

  {{-- Hook: widgets aportados por plugins (cards, charts, etc.) --}}
  @if(class_exists('QuetzalHookManager'))
    @foreach(QuetzalHookManager::getHookData('dashboard_widgets') as $html)
      @if(is_string($html) && $html !== '')
        {!! $html !!}
      @endif
    @endforeach
  @endif

  {{-- Welcome card (por defecto) --}}
  @if($showDefaultWelcome)
  <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8">
    <div class="flex flex-col sm:flex-row sm:items-start gap-6">
      <div class="flex-1">
        <h2 class="text-xl font-bold mb-2">
          ¡Hola, {{ $user['username'] ?? 'admin' }}! <span class="ml-1">👋</span>
        </h2>
        <p class="text-slate-600 text-sm leading-relaxed">
          Bienvenido al panel de administración de <strong>{{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}</strong>.
          Desde aquí puedes gestionar usuarios, productos, configurar la apariencia del sistema
          y más.
        </p>

        <div class="flex flex-wrap gap-2 mt-4">
          <a href="admin/perfil" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-primary text-white text-sm font-medium hover:opacity-90 transition btn-primary">
            <i class="ri-id-card-line"></i> Mi perfil
          </a>
          @if(user_can('admin-access'))
            <a href="admin/apariencia" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-slate-200 text-slate-700 text-sm font-medium hover:bg-slate-50 transition">
              <i class="ri-palette-line"></i> Personalizar colores
            </a>
          @endif
        </div>
      </div>

      <div class="hidden sm:block text-slate-200 text-8xl leading-none">
        <i class="ri-quill-pen-line"></i>
      </div>
    </div>
  </div>
  @endif

  {{-- Hook: contenido DESPUÉS del dashboard --}}
  @if(class_exists('QuetzalHookManager'))
    @foreach(QuetzalHookManager::getHookData('dashboard_after') as $html)
      @if(is_string($html) && $html !== '')
        {!! $html !!}
      @endif
    @endforeach
  @endif
</div>
@endsection
