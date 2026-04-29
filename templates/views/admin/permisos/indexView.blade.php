@extends('includes.admin.layout')

@section('title', 'Permisos')
@section('page_title', 'Permisos')

@php
  $protectedSlugs = ['admin-access'];
  $f = $filters ?? ['q' => ''];
@endphp

@section('content')
<div class="space-y-4">

  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-2">
        <h2 class="font-semibold text-slate-800">Listado</h2>
        <span class="inline-flex items-center justify-center min-w-[1.75rem] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">
          {{ count($permissions) }}
        </span>
      </div>
      <div class="flex items-center gap-2">
        <form method="post" action="admin/post_sync_permisos" class="inline"
              onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').innerHTML='<i class=\'ri-loader-4-line animate-spin\'></i> Sincronizando...';">
          @csrf
          <button type="submit"
                  class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-semibold"
                  title="Detecta y registra permisos declarados por plugins habilitados">
            <i class="ri-refresh-line"></i> Sincronizar desde plugins
          </button>
        </form>
        <a href="admin/crear_permiso" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg btn-primary text-sm font-semibold">
          <i class="ri-add-line"></i> Nuevo permiso
        </a>
      </div>
    </div>

    <form method="get" action="admin/permisos" class="mt-4 grid grid-cols-1 sm:grid-cols-12 gap-3">
      <div class="sm:col-span-9">
        <div class="relative">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="ri-search-line"></i></span>
          <input type="text" name="q" value="{{ $f['q'] }}" placeholder="Buscar por nombre, slug o descripción..."
                 class="w-full pl-10 pr-3 py-2 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
        </div>
      </div>
      <div class="sm:col-span-3 flex gap-2">
        <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 rounded-lg btn-primary text-sm font-medium">
          <i class="ri-filter-2-line"></i> Buscar
        </button>
        @if($f['q'] !== '')
          <a href="admin/permisos" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm">
            <i class="ri-close-line"></i>
          </a>
        @endif
      </div>
    </form>
  </div>

  @if(empty($permissions))
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
      <i class="ri-key-2-line text-5xl text-slate-300 mb-2 block"></i>
      <p class="text-sm text-slate-500">
        @if($f['q'] !== '')
          No hay permisos que coincidan con tu búsqueda.
        @else
          No hay permisos registrados.
          <strong>Hacé clic en "Sincronizar desde plugins"</strong> para descubrir los permisos aportados por los plugins habilitados.
        @endif
      </p>
    </div>
  @else
    @php
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
    <div class="space-y-4">
      @foreach($permsByPlugin as $groupName => $group)
        @php
          $matrix   = $group['matrix']   ?? [];
          $unparsed = $group['unparsed'] ?? [];
        @endphp
        <details class="bg-white rounded-xl border border-slate-200 overflow-hidden" {{ $groupName !== 'Core' ? 'open' : '' }}>
          <summary class="cursor-pointer flex items-center gap-3 px-5 py-3 bg-slate-50 hover:bg-slate-100 transition border-b border-slate-200">
            <i class="{{ $group['icon'] ?? 'ri-folder-line' }} text-primary text-lg"></i>
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-slate-800 text-sm">
                {{ $groupName }}
                @if($groupName !== 'Core')<span class="ml-2 text-xs font-normal text-slate-500">(plugin)</span>@endif
              </h3>
              @if(!empty($group['description']))<p class="text-xs text-slate-500 mt-0.5 truncate">{{ $group['description'] }}</p>@endif
            </div>
            <span class="text-xs font-mono px-2 py-0.5 rounded-full bg-white border border-slate-200 text-slate-600">{{ count($group['perms']) }} permiso(s)</span>
            <i class="ri-arrow-down-s-line text-slate-400 text-lg"></i>
          </summary>

          @if(!empty($matrix))
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="bg-white border-b border-slate-100">
                  <tr class="text-[10px] uppercase tracking-wider text-slate-500">
                    <th class="text-left px-4 py-2 font-semibold">Recurso</th>
                    @foreach($actions as $aKey => $a)
                      <th class="px-2 py-2 font-semibold text-center">
                        <div class="flex flex-col items-center gap-0.5">
                          <i class="{{ $a['icon'] }} {{ $a['color'] }} text-base"></i>
                          <span>{{ $a['label'] }}</span>
                        </div>
                      </th>
                    @endforeach
                    <th class="text-center px-3 py-2 font-semibold">Roles</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  @foreach($matrix as $resource => $byAction)
                    <tr class="hover:bg-slate-50/60">
                      <td class="px-4 py-2.5">
                        <div class="font-medium text-slate-800">{{ ucfirst(str_replace(['.', '-', '_'], ' ', $resource)) }}</div>
                        <code class="text-[10px] text-slate-400 font-mono">{{ $resource }}.*</code>
                      </td>
                      @foreach($actions as $aKey => $a)
                        @php $perm = $byAction[$aKey] ?? null; @endphp
                        <td class="px-2 py-2.5 text-center">
                          @if($perm)
                            <a href="admin/editar_permiso/{{ $perm['id'] }}" title="{{ $perm['nombre'] }} ({{ $perm['slug'] }})"
                               class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 hover:bg-emerald-200 transition">
                              <i class="ri-check-line"></i>
                            </a>
                          @else
                            <span class="text-slate-200">—</span>
                          @endif
                        </td>
                      @endforeach
                      <td class="px-3 py-2.5 text-center text-xs text-slate-500">
                        @php
                          $maxRoles = 0;
                          foreach ($byAction as $p) { $maxRoles = max($maxRoles, (int)($p['role_count'] ?? 0)); }
                        @endphp
                        @if($maxRoles > 0)<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100"><i class="ri-shield-user-line"></i> hasta {{ $maxRoles }}</span>@endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif

          @if(!empty($unparsed))
            <div class="border-t border-slate-100 px-5 py-3 bg-slate-50/50">
              <div class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold mb-2">Otros permisos</div>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                @foreach($unparsed as $p)
                  <a href="admin/editar_permiso/{{ $p['id'] }}" class="flex items-center gap-2 p-2 rounded border border-slate-200 hover:border-primary hover:bg-white transition text-xs">
                    <i class="ri-key-2-line text-slate-400"></i>
                    <div class="flex-1 min-w-0">
                      <div class="font-medium text-slate-700 truncate">{{ $p['nombre'] }}</div>
                      <code class="text-[10px] text-slate-400 font-mono break-all">{{ $p['slug'] }}</code>
                    </div>
                    <span class="text-[10px] text-slate-400">{{ $p['role_count'] ?? 0 }} roles</span>
                  </a>
                @endforeach
              </div>
            </div>
          @endif
        </details>
      @endforeach
    </div>
  @endif
</div>
@endsection
