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

  // ====== ITEMS APORTADOS POR PLUGINS (vía hook) ======
  $pluginGroups = [];
  if (class_exists('QuetzalHookManager')) {
    foreach (QuetzalHookManager::getHookData('admin_sidebar_items') as $extra) {
      if (is_array($extra)) $pluginGroups = array_merge($pluginGroups, $extra);
    }
  }

  // ====== ITEMS CUSTOM DEL USUARIO (sidebar.json) ======
  // Guardados por el CRUD generator, el Visual Builder o edición manual.
  // Cada item trae su 'group' — los agrupamos para merge.
  $customItems = function_exists('load_sidebar_items') ? load_sidebar_items() : [];
  $customGroups = [];
  foreach ($customItems as $item) {
    if (empty($item['label']) || empty($item['url'])) continue;
    $g = $item['group'] ?? 'Gestión';
    if (!isset($customGroups[$g])) $customGroups[$g] = ['group' => $g, 'items' => []];
    $customGroups[$g]['items'][] = $item;
  }

  // Menú base del core
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

  // Merge helper: añade items a un grupo existente o crea uno nuevo al final
  $mergeGroups = function(array $extraGroups) use (&$nav) {
    foreach ($extraGroups as $eg) {
      if (empty($eg['group']) || empty($eg['items'])) continue;
      $foundIndex = null;
      foreach ($nav as $i => $existing) {
        if (($existing['group'] ?? null) === $eg['group']) { $foundIndex = $i; break; }
      }
      if ($foundIndex !== null) {
        $nav[$foundIndex]['items'] = array_merge($nav[$foundIndex]['items'], $eg['items']);
      } else {
        $nav[] = $eg;
      }
    }
  };

  $mergeGroups($pluginGroups);
  $mergeGroups(array_values($customGroups));

  // ¿Hay algún item activo en cada grupo? → lo necesitamos para auto-expandir
  // el grupo activo por default (aunque el usuario haya colapsado otros).
  $groupHasActive = [];
  foreach ($nav as $section) {
    $hasActive = false;
    foreach ($section['items'] as $item) {
      if ($isActive($item['controller'] ?? '', $item['method'] ?? null, $item['activeMethods'] ?? [])) {
        $hasActive = true; break;
      }
    }
    $groupHasActive[$section['group']] = $hasActive;
  }
@endphp

<aside id="q-sidebar" class="hidden lg:flex lg:flex-col w-full lg:w-64 flex-shrink-0">
  <div class="px-6 py-5 flex items-center gap-3 border-b border-white/10">
    <img src="{{ get_quetzal_logo() }}" alt="{{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}" class="w-9 h-9 rounded bg-white/5 p-1">
    <div class="leading-tight">
      <div class="text-sm font-semibold">{{ defined('SITE_NAME') ? SITE_NAME : 'Quetzal' }}</div>
      <div class="text-xs opacity-60">Panel admin</div>
    </div>
  </div>

  <nav class="flex-1 overflow-y-auto py-2" id="q-sidebar-nav">
    @foreach($nav as $section)
      @php
        // Filtrar items visibles por permiso — si ninguno es visible, no renderizar el grupo
        $visibleItems = array_filter($section['items'], fn($i) => ($i['permission'] ?? null) === null || user_can($i['permission']));
        if (empty($visibleItems)) continue;

        $groupKey  = 'q-group-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($section['group']));
        $hasActive = $groupHasActive[$section['group']] ?? false;
      @endphp
      <div class="q-group" data-group-key="{{ $groupKey }}" data-has-active="{{ $hasActive ? '1' : '0' }}">
        <button type="button"
                class="q-group-toggle w-full flex items-center justify-between px-6 pt-3 pb-1 text-[10px] uppercase tracking-wider opacity-60 hover:opacity-90 font-semibold transition">
          <span>{{ $section['group'] }}</span>
          <i class="ri-arrow-down-s-line q-group-chevron text-base opacity-70 transition-transform"></i>
        </button>
        <div class="q-group-items">
          @foreach($visibleItems as $item)
            <a href="{{ $item['url'] }}"
               class="flex items-center gap-3 px-6 py-2.5 text-sm transition {{ $isActive($item['controller'] ?? '', $item['method'] ?? null, $item['activeMethods'] ?? []) ? 'active' : '' }}">
              <i class="{{ $item['icon'] ?? 'ri-folder-line' }} text-lg"></i>
              <span>{{ $item['label'] }}</span>
            </a>
          @endforeach
        </div>
      </div>
    @endforeach
  </nav>

  <div class="px-6 py-4 border-t border-white/10 text-xs opacity-70">
    @isset($user['username'])
      Conectado como <strong>{{ $user['username'] }}</strong>
    @endisset
  </div>
</aside>

@push('scripts')
<script>
(function() {
  'use strict';
  const STORAGE_KEY = 'q-sidebar-collapsed';

  // Cargar estado previo
  let collapsed = {};
  try {
    collapsed = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}');
  } catch (e) { collapsed = {}; }

  // Inicializar cada grupo
  document.querySelectorAll('#q-sidebar-nav .q-group').forEach(group => {
    const key       = group.dataset.groupKey;
    const hasActive = group.dataset.hasActive === '1';
    const items     = group.querySelector('.q-group-items');
    const chevron   = group.querySelector('.q-group-chevron');

    // Reglas: si tiene item activo → forzar expandido (independiente del guardado)
    //         si no, respetar el estado guardado (default expandido)
    const isCollapsed = hasActive ? false : !!collapsed[key];

    if (isCollapsed) {
      items.style.display = 'none';
      if (chevron) chevron.style.transform = 'rotate(-90deg)';
    }
  });

  // Toggle
  document.querySelectorAll('#q-sidebar-nav .q-group-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const group   = btn.closest('.q-group');
      const key     = group.dataset.groupKey;
      const items   = group.querySelector('.q-group-items');
      const chevron = group.querySelector('.q-group-chevron');

      const willCollapse = items.style.display !== 'none';
      items.style.display = willCollapse ? 'none' : '';
      if (chevron) chevron.style.transform = willCollapse ? 'rotate(-90deg)' : '';

      collapsed[key] = willCollapse;
      try { localStorage.setItem(STORAGE_KEY, JSON.stringify(collapsed)); } catch (e) {}
    });
  });
})();
</script>
@endpush
