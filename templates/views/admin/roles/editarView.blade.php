@extends('includes.admin.layout')

@section('title', 'Editar role: ' . $role['nombre'])
@section('page_title', 'Editar role')

@section('content')
<div class="max-w-6xl space-y-4">

  <div class="flex items-center justify-between">
    <a href="admin/ver_role/{{ $role['id'] }}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al detalle
    </a>
    @if($isProtected)
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-medium">
        <i class="ri-lock-line"></i> Role del sistema (solo permisos editables)
      </span>
    @endif
  </div>

  <form method="post" action="admin/post_role_editar" class="space-y-4">
    @csrf
    <input type="hidden" name="id" value="{{ $role['id'] }}">

    {{-- Datos del role --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
      <h3 class="font-semibold text-slate-800 mb-4 flex items-center gap-2">
        <i class="ri-shield-user-line text-primary"></i> Información del role
      </h3>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Nombre</label>
          <input type="text" name="nombre" value="{{ $role['nombre'] }}"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm {{ $isProtected ? 'bg-slate-50 text-slate-500' : '' }}"
                 required minlength="3" maxlength="100" {{ $isProtected ? 'readonly' : '' }}>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Slug</label>
          <input type="text" name="slug" value="{{ $role['slug'] }}"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono {{ $isProtected ? 'bg-slate-50 text-slate-500' : '' }}"
                 pattern="^[a-z0-9-]{3,50}$" {{ $isProtected ? 'readonly' : '' }}>
        </div>
      </div>
    </div>

    {{-- Permisos: matriz por plugin --}}
    @if(empty($allPerms))
      <div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-amber-900">
        <p class="font-semibold">No hay permisos registrados.</p>
        <p class="text-sm mt-1">Andá a <a href="admin/permisos" class="underline">/admin/permisos</a> y hacé clic en <strong>"Sincronizar desde plugins"</strong> para descubrir los permisos aportados por los plugins habilitados.</p>
      </div>
    @else
      @php
        // Acciones estándar (sincronizado con adminController::PERM_ACTIONS)
        $actions = [
          'ver'       => ['label' => 'Ver',         'icon' => 'ri-eye-line',         'color' => 'text-slate-500'],
          'crear'     => ['label' => 'Crear',       'icon' => 'ri-add-line',         'color' => 'text-emerald-500'],
          'editar'    => ['label' => 'Editar',      'icon' => 'ri-edit-line',        'color' => 'text-amber-500'],
          'eliminar'  => ['label' => 'Eliminar',    'icon' => 'ri-delete-bin-line',  'color' => 'text-red-500'],
          'descargar' => ['label' => 'Descargar',   'icon' => 'ri-download-2-line',  'color' => 'text-blue-500'],
          'aprobar'   => ['label' => 'Aprobar',     'icon' => 'ri-shield-check-line','color' => 'text-violet-500'],
          'admin'     => ['label' => 'Administrar', 'icon' => 'ri-shield-star-line', 'color' => 'text-fuchsia-500'],
          'acceso'    => ['label' => 'Acceso',      'icon' => 'ri-login-box-line',   'color' => 'text-cyan-500'],
        ];
      @endphp

      <div class="bg-white rounded-xl border border-slate-200 p-5 sticky top-0 z-10 shadow-sm">
        <div class="flex items-center justify-between flex-wrap gap-3">
          <div class="flex items-center gap-2">
            <i class="ri-key-2-fill text-primary"></i>
            <h3 class="font-semibold text-slate-800">Matriz de permisos</h3>
            <span class="text-xs text-slate-500">— filas = recursos, columnas = acciones</span>
          </div>
          <div class="flex items-center gap-2 text-xs">
            <button type="button" data-q-perms-select="all"  class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 text-slate-700 font-semibold">Marcar todos</button>
            <button type="button" data-q-perms-select="none" class="px-3 py-1 rounded border border-slate-200 hover:bg-slate-50 text-slate-500">Ninguno</button>
          </div>
        </div>
      </div>

      <div class="space-y-4" id="q-perms-grid">
        @foreach($permsByPlugin as $groupName => $group)
          @php
            $matrix     = $group['matrix']   ?? [];
            $unparsed   = $group['unparsed'] ?? [];
            $totalPerms = count($group['perms']);
            $checkedNum = count(array_filter($group['perms'], fn($p) => in_array($p['slug'], $rolePerms, true)));
            $groupKey   = 'g-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($groupName));
          @endphp
          <details class="bg-white rounded-xl border border-slate-200 overflow-hidden q-perm-group" data-q-group-key="{{ $groupKey }}" {{ $groupName !== 'Core' ? 'open' : '' }}>
            <summary class="cursor-pointer flex items-center gap-3 px-5 py-3 bg-slate-50 hover:bg-slate-100 transition border-b border-slate-200">
              <i class="{{ $group['icon'] ?? 'ri-folder-line' }} text-primary text-lg"></i>
              <div class="flex-1 min-w-0">
                <div class="font-semibold text-slate-800 text-sm">
                  {{ $groupName }}
                  @if($groupName !== 'Core')<span class="ml-2 text-xs font-normal text-slate-500">(plugin)</span>@endif
                </div>
                @if(!empty($group['description']))<div class="text-xs text-slate-500 mt-0.5 truncate">{{ $group['description'] }}</div>@endif
              </div>
              <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-white border border-slate-200 text-slate-600 q-group-count" data-checked="{{ $checkedNum }}" data-total="{{ $totalPerms }}">
                <span class="q-group-checked">{{ $checkedNum }}</span> / {{ $totalPerms }}
              </span>
              <i class="ri-arrow-down-s-line text-slate-400 text-lg transition-transform"></i>
            </summary>

            @if(!empty($matrix))
              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="bg-white border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                      <th class="text-left px-4 py-2 font-semibold">Recurso</th>
                      @foreach($actions as $aKey => $a)
                        <th class="px-2 py-2 font-semibold text-center">
                          <div class="flex flex-col items-center gap-0.5">
                            <i class="{{ $a['icon'] }} {{ $a['color'] }} text-base"></i>
                            <span>{{ $a['label'] }}</span>
                            <button type="button" data-q-col-toggle="{{ $groupKey }}::{{ $aKey }}" title="Marcar/desmarcar columna" class="text-[9px] text-primary/80 hover:text-primary hover:underline" onclick="event.preventDefault(); event.stopPropagation();">toggle</button>
                          </div>
                        </th>
                      @endforeach
                      <th class="text-center px-2 py-2 font-semibold w-16">
                        <div class="flex flex-col items-center gap-0.5">
                          <i class="ri-checkbox-multiple-line text-slate-400"></i>
                          <span>Fila</span>
                        </div>
                      </th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    @foreach($matrix as $resource => $byAction)
                      @php $rowKey = $groupKey . '__' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($resource)); @endphp
                      <tr class="hover:bg-slate-50/50 q-perm-row" data-q-row-key="{{ $rowKey }}">
                        <td class="px-4 py-2.5">
                          <div class="font-medium text-slate-800">{{ ucfirst(str_replace(['.', '-', '_'], ' ', $resource)) }}</div>
                          <code class="text-[10px] text-slate-400 font-mono">{{ $resource }}.*</code>
                        </td>
                        @foreach($actions as $aKey => $a)
                          @php $perm = $byAction[$aKey] ?? null; @endphp
                          <td class="px-2 py-2.5 text-center">
                            @if($perm)
                              @php $checked = in_array($perm['slug'], $rolePerms, true); @endphp
                              <label class="inline-flex items-center justify-center w-8 h-8 rounded cursor-pointer hover:bg-primary/10 transition" title="{{ $perm['nombre'] }} ({{ $perm['slug'] }})">
                                <input type="checkbox" name="permisos[]" value="{{ $perm['slug'] }}" {{ $checked ? 'checked' : '' }}
                                       data-q-col-key="{{ $groupKey }}::{{ $aKey }}"
                                       data-q-row-key="{{ $rowKey }}"
                                       data-q-group-key="{{ $groupKey }}"
                                       class="q-perm-cb rounded border-slate-300 text-primary focus:ring-primary">
                              </label>
                            @else
                              <span class="text-slate-200">—</span>
                            @endif
                          </td>
                        @endforeach
                        <td class="text-center">
                          <button type="button" data-q-row-toggle="{{ $rowKey }}" class="text-[10px] text-primary/80 hover:text-primary hover:underline px-1">toggle</button>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif

            {{-- Permisos del grupo que no encajan en la matriz --}}
            @if(!empty($unparsed))
              <div class="border-t border-slate-100 px-5 py-3 bg-slate-50/50">
                <div class="text-xs uppercase tracking-wider text-slate-500 font-semibold mb-2">Otros permisos del plugin</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                  @foreach($unparsed as $p)
                    @php $checked = in_array($p['slug'], $rolePerms, true); @endphp
                    <label class="flex items-start gap-2 p-2 rounded border border-slate-200 hover:border-primary hover:bg-white transition cursor-pointer text-xs {{ $checked ? 'border-primary bg-primary/5' : '' }}">
                      <input type="checkbox" name="permisos[]" value="{{ $p['slug'] }}" {{ $checked ? 'checked' : '' }}
                             data-q-group-key="{{ $groupKey }}"
                             class="q-perm-cb mt-0.5 rounded border-slate-300 text-primary focus:ring-primary">
                      <div class="flex-1 min-w-0">
                        <div class="font-medium text-slate-700">{{ $p['nombre'] }}</div>
                        <code class="text-[10px] text-slate-400 font-mono break-all">{{ $p['slug'] }}</code>
                      </div>
                    </label>
                  @endforeach
                </div>
              </div>
            @endif
          </details>
        @endforeach
      </div>
    @endif

    <div class="flex items-center justify-end gap-3 pt-2">
      <a href="admin/ver_role/{{ $role['id'] }}" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Cancelar</a>
      <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
        <i class="ri-save-line"></i> Guardar cambios
      </button>
    </div>
  </form>
</div>

@push('scripts')
<script>
(function() {
  const grid = document.getElementById('q-perms-grid');
  if (!grid) return;

  const allCbs = () => grid.querySelectorAll('.q-perm-cb');

  // Recalcular el contador de cada grupo
  function refreshCounts() {
    grid.querySelectorAll('.q-perm-group').forEach(group => {
      const key = group.dataset.qGroupKey;
      const cbs = group.querySelectorAll('.q-perm-cb');
      const checked = Array.from(cbs).filter(cb => cb.checked).length;
      const slot = group.querySelector('.q-group-checked');
      if (slot) slot.textContent = checked;
    });
  }

  // Marcar/desmarcar todos (botón global del header)
  document.querySelectorAll('[data-q-perms-select]').forEach(btn => {
    btn.addEventListener('click', () => {
      const val = btn.dataset.qPermsSelect === 'all';
      allCbs().forEach(cb => cb.checked = val);
      refreshCounts();
    });
  });

  // Toggle por columna (todos los recursos en esa acción dentro del plugin)
  document.querySelectorAll('[data-q-col-toggle]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation();
      const key = btn.dataset.qColToggle;
      const cbs = grid.querySelectorAll('.q-perm-cb[data-q-col-key="' + key + '"]');
      const some = Array.from(cbs).some(cb => cb.checked);
      cbs.forEach(cb => cb.checked = !some);
      refreshCounts();
    });
  });

  // Toggle por fila (todas las acciones del recurso)
  document.querySelectorAll('[data-q-row-toggle]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation();
      const key = btn.dataset.qRowToggle;
      const cbs = grid.querySelectorAll('.q-perm-cb[data-q-row-key="' + key + '"]');
      const some = Array.from(cbs).some(cb => cb.checked);
      cbs.forEach(cb => cb.checked = !some);
      refreshCounts();
    });
  });

  // Actualizar contadores al cambiar cualquier checkbox
  grid.addEventListener('change', (e) => {
    if (e.target.classList.contains('q-perm-cb')) refreshCounts();
  });

  refreshCounts();
})();
</script>
@endpush
@endsection
