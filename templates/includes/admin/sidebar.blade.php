@php
  // Helper local: determina si la ruta actual matchea el slug del menu
  $current = defined('CONTROLLER') ? CONTROLLER : '';
  $method  = defined('METHOD')     ? METHOD     : '';
  $isActive = function($controller, $methodSlug = null, $extra = []) use ($current, $method) {
    if ($controller !== $current) return false;
    if ($methodSlug === null) return true;
    if ($method === $methodSlug) return true;
    return in_array($method, (array) $extra, true);
  };

  // Construye el menú. Los items respetan el permiso declarado; si está null,
  // se muestra siempre a usuarios autenticados.
  $nav = [
    ['group' => 'Panel', 'items' => [
      ['label' => 'Dashboard', 'icon' => 'ri-dashboard-line', 'url' => 'admin', 'controller' => 'admin', 'method' => 'index', 'permission' => null],
    ]],
    ['group' => 'Gestión', 'items' => [
      ['label' => 'Usuarios',   'icon' => 'ri-user-line',      'url' => 'admin/usuarios',   'controller' => 'admin', 'method' => 'usuarios',   'activeMethods' => ['crear_usuario','editar_usuario','ver_usuario'],    'permission' => 'users-read'],
      ['label' => 'Productos',  'icon' => 'ri-archive-line',   'url' => 'admin/productos',  'controller' => 'admin', 'method' => 'productos',  'activeMethods' => ['crear_producto','editar_producto','ver_producto'], 'permission' => 'products-read'],
    ]],
    ['group' => 'Sistema', 'items' => [
      ['label' => 'Roles',        'icon' => 'ri-shield-user-line', 'url' => 'admin/roles',        'controller' => 'admin', 'method' => 'roles',       'activeMethods' => ['crear_role','editar_role','ver_role'],         'permission' => 'admin-access'],
      ['label' => 'Permisos',     'icon' => 'ri-key-2-line',       'url' => 'admin/permisos',     'controller' => 'admin', 'method' => 'permisos',    'activeMethods' => ['crear_permiso','editar_permiso','ver_permiso'], 'permission' => 'admin-access'],
      ['label' => 'Plugins',      'icon' => 'ri-plug-line',        'url' => 'admin/plugins',      'controller' => 'admin', 'method' => 'plugins',     'activeMethods' => ['plugins_guia'], 'permission' => 'admin-access'],
      ['label' => 'Generador',    'icon' => 'ri-terminal-box-line','url' => 'admin/generador',    'controller' => 'admin', 'method' => 'generador',   'permission' => 'admin-access'],
      ['label' => 'Migraciones',  'icon' => 'ri-database-2-line',  'url' => 'admin/migraciones',  'controller' => 'admin', 'method' => 'migraciones', 'permission' => 'admin-access'],
      ['label' => 'Apariencia',   'icon' => 'ri-palette-line',     'url' => 'admin/apariencia',   'controller' => 'admin', 'method' => 'apariencia',  'permission' => 'admin-access'],
      ['label' => 'Perfil',       'icon' => 'ri-id-card-line',     'url' => 'admin/perfil',       'controller' => 'admin', 'method' => 'perfil',      'permission' => null],
    ]],
  ];
@endphp

<aside id="q-sidebar" class="hidden lg:flex lg:flex-col w-full lg:w-64 flex-shrink-0">
  <div class="px-6 py-5 flex items-center gap-3 border-b border-white/10">
    <img src="{{ get_quetzal_logo() }}" alt="{{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}" class="w-9 h-9 rounded bg-white/5 p-1">
    <div class="leading-tight">
      <div class="text-sm font-semibold">{{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}</div>
      <div class="text-xs opacity-60">Panel admin</div>
    </div>
  </div>

  <nav class="flex-1 overflow-y-auto py-3">
    @foreach($nav as $section)
      <div class="px-6 pt-4 pb-1 text-[10px] uppercase tracking-wider opacity-50 font-semibold">
        {{ $section['group'] }}
      </div>
      @foreach($section['items'] as $item)
        @php
          $canSee = $item['permission'] === null ? true : user_can($item['permission']);
        @endphp
        @if($canSee)
          <a href="{{ $item['url'] }}"
             class="flex items-center gap-3 px-6 py-2.5 text-sm transition {{ $isActive($item['controller'], $item['method'], $item['activeMethods'] ?? []) ? 'active' : '' }}">
            <i class="{{ $item['icon'] }} text-lg"></i>
            <span>{{ $item['label'] }}</span>
          </a>
        @endif
      @endforeach
    @endforeach
  </nav>

  <div class="px-6 py-4 border-t border-white/10 text-xs opacity-70">
    @isset($user['username'])
      Conectado como <strong>{{ $user['username'] }}</strong>
    @endisset
  </div>
</aside>
