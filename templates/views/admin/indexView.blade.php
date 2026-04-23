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
        ['label' => 'Usuarios',  'value' => method_exists('userModel','count') ? userModel::count() : '—',            'icon' => 'ri-user-3-fill',       'gradient' => 'from-sky-500 to-blue-700'],
        ['label' => 'Productos', 'value' => method_exists('productoModel','count') ? productoModel::count() : '—',   'icon' => 'ri-archive-fill',      'gradient' => 'from-emerald-500 to-teal-700'],
        ['label' => 'Roles',     'value' => count((new QuetzalRoleManager())->getRoles() ?: []),                     'icon' => 'ri-shield-user-fill',  'gradient' => 'from-amber-500 to-orange-700'],
        ['label' => 'Plugins',   'value' => count(QuetzalPluginManager::getInstance()->getEnabled()),                'icon' => 'ri-plug-fill',         'gradient' => 'from-purple-500 to-fuchsia-700'],
      ];
    @endphp
    @foreach($stats as $s)
      <div class="bg-gradient-to-br {{ $s['gradient'] }} text-white rounded-xl p-5 shadow-lg relative overflow-hidden">
        <i class="{{ $s['icon'] }}" style="position:absolute;right:-10px;bottom:-10px;font-size:96px;opacity:.12;"></i>
        <div class="w-11 h-11 rounded-lg bg-white/20 backdrop-blur flex items-center justify-center mb-3 relative z-10">
          <i class="{{ $s['icon'] }} text-xl"></i>
        </div>
        <div class="text-xs uppercase tracking-wider opacity-90 font-semibold relative z-10">{{ $s['label'] }}</div>
        <div class="text-3xl font-bold mt-1 relative z-10">{{ $s['value'] }}</div>
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
  @php
    $hora = (int) date('H');
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
  @endphp
  <div class="bg-gradient-to-br from-primary/10 via-white to-sky-50 rounded-xl border border-primary/20 p-6 sm:p-8 relative overflow-hidden">
    <i class="ri-quill-pen-fill" style="position:absolute;right:-30px;bottom:-30px;font-size:180px;color:var(--q-primary);opacity:.08;"></i>
    <div class="flex flex-col sm:flex-row sm:items-center gap-6 relative z-10">
      <div class="w-20 h-20 rounded-full bg-gradient-to-br from-primary to-primary-dark text-white flex items-center justify-center text-3xl font-bold shadow-lg flex-shrink-0">
        {{ strtoupper(substr($user['username'] ?? 'U', 0, 1)) }}
      </div>
      <div class="flex-1">
        <div class="text-xs uppercase tracking-widest font-semibold text-primary/80 mb-1">{{ $saludo }}</div>
        <h2 class="text-2xl font-bold text-slate-800">
          ¡Hola, {{ $user['username'] ?? 'admin' }}!
        </h2>
        <p class="text-slate-600 text-sm leading-relaxed mt-2">
          Bienvenido al panel de <strong>{{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}</strong>.
          Gestioná usuarios, productos, personalizá colores y mucho más desde acá.
        </p>

        <div class="flex flex-wrap gap-2 mt-4">
          <a href="admin/perfil" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-primary text-white text-sm font-semibold hover:opacity-90 transition shadow-sm">
            <i class="ri-id-card-fill"></i> Mi perfil
          </a>
          @if(user_can('admin-access'))
            <a href="admin/apariencia" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition shadow-sm">
              <i class="ri-palette-fill"></i> Personalizar
            </a>
            <a href="admin/plugins" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-sm font-semibold hover:bg-slate-50 transition shadow-sm">
              <i class="ri-plug-fill"></i> Plugins
            </a>
          @endif
        </div>
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
