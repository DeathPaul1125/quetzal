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

  // Menú base del core — Sistema se agrega al final (después de plugins)
  $nav = [
    ['group' => 'Panel', 'items' => [
      ['label' => 'Dashboard', 'icon' => 'ri-dashboard-fill', 'iconColor' => 'text-sky-400',   'url' => 'admin', 'controller' => 'admin', 'method' => 'index', 'permission' => null],
    ]],
  ];

  // Items del grupo Sistema — va al final del sidebar
  $sistemaItems = [
    ['label' => 'Usuarios',     'icon' => 'ri-user-3-fill',       'iconColor' => 'text-blue-400',    'url' => 'admin/usuarios',     'controller' => 'admin', 'method' => 'usuarios',    'activeMethods' => ['crear_usuario','editar_usuario','ver_usuario'], 'permission' => 'users-read'],
    ['label' => 'Roles',        'icon' => 'ri-shield-user-fill',  'iconColor' => 'text-amber-400',   'url' => 'admin/roles',        'controller' => 'admin', 'method' => 'roles',       'activeMethods' => ['crear_role','editar_role','ver_role'],          'permission' => 'admin-access'],
    ['label' => 'Permisos',     'icon' => 'ri-key-2-fill',        'iconColor' => 'text-yellow-400',  'url' => 'admin/permisos',     'controller' => 'admin', 'method' => 'permisos',    'activeMethods' => ['crear_permiso','editar_permiso','ver_permiso'], 'permission' => 'admin-access'],
    ['label' => 'Plugins',      'icon' => 'ri-plug-fill',         'iconColor' => 'text-purple-400',  'url' => 'admin/plugins',      'controller' => 'admin', 'method' => 'plugins',     'activeMethods' => ['plugins_guia'],                                 'permission' => 'admin-access'],
    ['label' => 'Generador',    'icon' => 'ri-terminal-box-fill', 'iconColor' => 'text-slate-300',   'url' => 'admin/generador',    'controller' => 'admin', 'method' => 'generador',                                                                        'permission' => 'admin-access'],
    ['label' => 'Migraciones',  'icon' => 'ri-database-2-fill',   'iconColor' => 'text-cyan-400',    'url' => 'admin/migraciones',  'controller' => 'admin', 'method' => 'migraciones',                                                                      'permission' => 'admin-access'],
    ['label' => 'Apariencia',   'icon' => 'ri-palette-fill',      'iconColor' => 'text-pink-400',    'url' => 'admin/apariencia',   'controller' => 'admin', 'method' => 'apariencia',                                                                       'permission' => 'admin-access'],
    ['label' => 'Documentación','icon' => 'ri-book-3-fill',       'iconColor' => 'text-violet-400',  'url' => 'documentacion',      'controller' => 'documentacion', 'method' => 'index',                                                                  'permission' => null],
    ['label' => 'Perfil',       'icon' => 'ri-id-card-fill',      'iconColor' => 'text-indigo-400',  'url' => 'admin/perfil',       'controller' => 'admin', 'method' => 'perfil',                                                                            'permission' => null],
  ];

  // Merge helper: añade items a un grupo existente o crea uno nuevo al final.
  // Como "Sistema" aún no está en $nav, los plugins que aporten al grupo "Sistema"
  // se consolidarán correctamente cuando lo agreguemos al final.
  $sistemaExtraItems = [];
  $mergeGroups = function(array $extraGroups) use (&$nav, &$sistemaExtraItems) {
    foreach ($extraGroups as $eg) {
      if (empty($eg['group']) || empty($eg['items'])) continue;
      // Items para "Sistema" se guardan para mergear al final
      if ($eg['group'] === 'Sistema') {
        $sistemaExtraItems = array_merge($sistemaExtraItems, $eg['items']);
        continue;
      }
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

  // Sistema siempre va al final, con items del core primero + los que aportaron plugins
  $nav[] = [
    'group' => 'Sistema',
    'items' => array_merge($sistemaItems, $sistemaExtraItems),
  ];

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
  @php
    $sb_branding = isset($branding) ? $branding : (function_exists('branding_info') ? branding_info() : ['site_name' => defined('SITE_NAME') ? SITE_NAME : 'Quetzal', 'tagline' => 'Panel admin', 'logo' => '']);
    $sb_logo = !empty($sb_branding['logo']) ? branding_asset_url($sb_branding['logo']) : (function_exists('get_quetzal_logo') ? get_quetzal_logo() : '');
  @endphp
  <div class="px-6 py-5 flex items-center gap-3 border-b border-white/10">
    @if($sb_logo)
      <img src="{{ $sb_logo }}" alt="{{ $sb_branding['site_name'] }}" class="w-9 h-9 rounded bg-white/5 p-1 object-contain">
    @endif
    <div class="leading-tight">
      <div class="text-sm font-semibold">{{ $sb_branding['site_name'] }}</div>
      <div class="text-xs opacity-60">{{ $sb_branding['tagline'] ?: 'Panel admin' }}</div>
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
              <i class="{{ $item['icon'] ?? 'ri-folder-line' }} text-lg {{ $item['iconColor'] ?? 'text-slate-400' }}"></i>
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
  // Modo accordion: solo UN grupo expandido a la vez. La key guardada es el
  // grupo abierto (string) o null si todos colapsados.
  const STORAGE_KEY = 'q-sidebar-open-group';

  const groups = Array.from(document.querySelectorAll('#q-sidebar-nav .q-group'));
  if (!groups.length) return;

  // 1) Identificar el grupo activo por la URL actual (siempre tiene precedencia)
  const activeGroup = groups.find(g => g.dataset.hasActive === '1');

  // 2) Si no hay activo, leer el último abierto desde localStorage
  let openKey = null;
  try { openKey = localStorage.getItem(STORAGE_KEY); } catch (e) {}

  // 3) Estado inicial: solo uno abierto (o ninguno)
  const initialOpen = activeGroup
    ? activeGroup.dataset.groupKey
    : (openKey && groups.some(g => g.dataset.groupKey === openKey) ? openKey : (groups[0]?.dataset.groupKey || null));

  function setExpanded(group, expanded) {
    const items   = group.querySelector('.q-group-items');
    const chevron = group.querySelector('.q-group-chevron');
    if (items)   items.style.display = expanded ? '' : 'none';
    if (chevron) chevron.style.transform = expanded ? '' : 'rotate(-90deg)';
    group.classList.toggle('q-group-open', expanded);
  }

  groups.forEach(g => setExpanded(g, g.dataset.groupKey === initialOpen));

  // 4) Click en cualquier toggle → cierra los demás y deja solo ese abierto
  //    Si el clickeado ya estaba abierto, lo cierra (todos colapsados).
  document.querySelectorAll('#q-sidebar-nav .q-group-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
      const group   = btn.closest('.q-group');
      const key     = group.dataset.groupKey;
      const isOpen  = group.classList.contains('q-group-open');

      // Cerrar todos
      groups.forEach(g => setExpanded(g, false));
      // Si estaba cerrado, abrirlo
      if (!isOpen) {
        setExpanded(group, true);
        try { localStorage.setItem(STORAGE_KEY, key); } catch (e) {}
      } else {
        try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
      }
    });
  });
})();
</script>
@endpush
