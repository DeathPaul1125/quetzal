@extends('includes.admin.layout')

@section('title', 'Editar role: ' . $role['nombre'])
@section('page_title', 'Editar role')

@section('content')
<div class="max-w-4xl space-y-4">

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
                 required minlength="3" maxlength="100"
                 {{ $isProtected ? 'readonly' : '' }}>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Slug</label>
          <input type="text" name="slug" value="{{ $role['slug'] }}"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono {{ $isProtected ? 'bg-slate-50 text-slate-500' : '' }}"
                 pattern="^[a-z0-9-]{3,50}$"
                 {{ $isProtected ? 'readonly' : '' }}>
        </div>
      </div>
    </div>

    {{-- Permisos --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
      <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <h3 class="font-semibold text-slate-800 flex items-center gap-2">
          <i class="ri-key-2-line text-primary"></i> Permisos asignados
        </h3>
        <div class="flex items-center gap-2 text-xs">
          <button type="button" data-q-perms-select="all"  class="text-primary hover:underline">Seleccionar todos</button>
          <span class="text-slate-300">·</span>
          <button type="button" data-q-perms-select="none" class="text-slate-500 hover:underline">Ninguno</button>
        </div>
      </div>

      @if(empty($allPerms))
        <p class="text-sm text-slate-500 text-center py-6">
          No hay permisos registrados. Andá a
          <a href="admin/permisos" class="text-primary hover:underline">/admin/permisos</a> y hacé clic en
          "Sincronizar permisos" para descubrir los aportados por plugins.
        </p>
      @else
        <div class="space-y-4" id="q-perms-grid">
          @foreach($permsByPlugin as $groupName => $group)
            @php
              $groupPerms      = $group['perms'];
              $groupChecked    = array_filter($groupPerms, fn($p) => in_array($p['slug'], $rolePerms, true));
              $groupKey        = 'q-pg-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($groupName));
            @endphp
            <details class="rounded-lg border border-slate-200 overflow-hidden" {{ $groupName !== 'Core' ? 'open' : '' }}>
              <summary class="cursor-pointer flex items-center gap-3 px-4 py-3 bg-slate-50 hover:bg-slate-100 transition">
                <i class="{{ $group['icon'] ?? 'ri-folder-line' }} text-primary text-lg"></i>
                <div class="flex-1">
                  <div class="font-semibold text-slate-800 text-sm">{{ $groupName }}</div>
                  @if(!empty($group['description']))<div class="text-xs text-slate-500 mt-0.5">{{ $group['description'] }}</div>@endif
                </div>
                <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-white border border-slate-200 text-slate-600">{{ count($groupChecked) }} / {{ count($groupPerms) }}</span>
                <button type="button" data-q-group-toggle="{{ $groupKey }}" class="text-xs text-primary hover:underline" onclick="event.preventDefault();event.stopPropagation();">Marcar todos</button>
                <i class="ri-arrow-down-s-line text-slate-400 text-lg"></i>
              </summary>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 p-4 q-perm-group" data-q-group-key="{{ $groupKey }}">
                @foreach($groupPerms as $p)
                  @php $checked = in_array($p['slug'], $rolePerms, true); @endphp
                  <label class="flex items-start gap-3 p-3 rounded-lg border border-slate-200 hover:border-primary hover:bg-slate-50 transition cursor-pointer {{ $checked ? 'border-primary bg-primary/5' : '' }}">
                    <input type="checkbox" name="permisos[]" value="{{ $p['slug'] }}" {{ $checked ? 'checked' : '' }}
                           class="mt-0.5 rounded border-slate-300 text-primary focus:ring-primary">
                    <div class="flex-1 min-w-0">
                      <div class="text-sm font-medium text-slate-800">{{ $p['nombre'] }}</div>
                      <div class="text-xs text-slate-500 font-mono break-all">{{ $p['slug'] }}</div>
                      @if(!empty($p['descripcion']))<div class="text-xs text-slate-500 mt-1">{{ $p['descripcion'] }}</div>@endif
                    </div>
                  </label>
                @endforeach
              </div>
            </details>
          @endforeach
        </div>
      @endif
    </div>

    <div class="flex items-center justify-end gap-3">
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

  // Seleccionar/desmarcar TODOS
  document.querySelectorAll('[data-q-perms-select]').forEach(btn => {
    btn.addEventListener('click', () => {
      const val = btn.dataset.qPermsSelect === 'all';
      grid.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = val);
    });
  });

  // "Marcar todos" por grupo (toggle inteligente)
  document.querySelectorAll('[data-q-group-toggle]').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault(); e.stopPropagation();
      const key  = btn.dataset.qGroupToggle;
      const sec  = grid.querySelector('.q-perm-group[data-q-group-key="' + key + '"]');
      if (!sec) return;
      const all  = sec.querySelectorAll('input[type=checkbox]');
      const some = Array.from(all).some(cb => cb.checked);
      all.forEach(cb => cb.checked = !some);
    });
  });
})();
</script>
@endpush
@endsection
