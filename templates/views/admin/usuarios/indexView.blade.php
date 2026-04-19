@extends('includes.admin.layout')

@section('title', 'Usuarios')
@section('page_title', 'Usuarios')

@php
  $rows          = $users['rows'] ?? [];
  $currentUserId = (int)(get_user('id') ?? 0);
  $f             = $filters ?? ['q' => '', 'role' => '', 'sort' => 'id', 'dir' => 'desc'];

  // Helper para generar URL de orden (toggle asc/desc) conservando filtros
  $sortUrl = function(string $col) use ($f) {
    $dir = ($f['sort'] === $col && $f['dir'] === 'asc') ? 'desc' : 'asc';
    return 'admin/usuarios?' . http_build_query([
      'q' => $f['q'], 'role' => $f['role'], 'sort' => $col, 'dir' => $dir,
    ]);
  };

  $sortIcon = function(string $col) use ($f) {
    if ($f['sort'] !== $col) return 'ri-expand-up-down-line text-slate-300';
    return $f['dir'] === 'asc' ? 'ri-arrow-up-line text-primary' : 'ri-arrow-down-line text-primary';
  };
@endphp

@section('content')
<div class="space-y-4">

  {{-- Toolbar --}}
  <div class="bg-white rounded-xl border border-slate-200 p-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-2">
        <h2 class="font-semibold text-slate-800">Listado</h2>
        <span class="inline-flex items-center justify-center min-w-[1.75rem] px-1.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">
          {{ $users['total'] ?? 0 }}
        </span>
      </div>
      <a href="admin/crear_usuario" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg btn-primary text-sm font-semibold">
        <i class="ri-user-add-line"></i> Nuevo usuario
      </a>
    </div>

    {{-- Filtros --}}
    <form method="get" action="admin/usuarios" class="mt-4 grid grid-cols-1 sm:grid-cols-12 gap-3">
      <div class="sm:col-span-6">
        <div class="relative">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="ri-search-line"></i></span>
          <input type="text" name="q" value="{{ $f['q'] }}" placeholder="Buscar por nombre o email..."
                 class="w-full pl-10 pr-3 py-2 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
        </div>
      </div>
      <div class="sm:col-span-3">
        <select name="role" class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
          <option value="">Todos los roles</option>
          @foreach($roles as $r)
            <option value="{{ $r['slug'] }}" @if($f['role'] === $r['slug']) selected @endif>{{ $r['nombre'] }}</option>
          @endforeach
        </select>
      </div>
      <div class="sm:col-span-3 flex gap-2">
        <button type="submit" class="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 rounded-lg btn-primary text-sm font-medium">
          <i class="ri-filter-2-line"></i> Filtrar
        </button>
        @if($f['q'] !== '' || $f['role'] !== '')
          <a href="admin/usuarios" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm" title="Limpiar filtros">
            <i class="ri-close-line"></i>
          </a>
        @endif
        {{-- Mantener orden al filtrar --}}
        <input type="hidden" name="sort" value="{{ $f['sort'] }}">
        <input type="hidden" name="dir"  value="{{ $f['dir'] }}">
      </div>
    </form>
  </div>

  {{-- Tabla --}}
  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    @if(empty($rows))
      <div class="p-12 text-center">
        <i class="ri-user-search-line text-5xl text-slate-300 mb-2 block"></i>
        <p class="text-sm text-slate-500">
          @if($f['q'] !== '' || $f['role'] !== '')
            No hay usuarios que coincidan con tu búsqueda.
          @else
            No hay usuarios registrados todavía.
          @endif
        </p>
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr class="text-xs uppercase tracking-wider text-slate-500">
              <th class="text-left px-5 py-3 font-semibold">
                <a href="{{ $sortUrl('id') }}" class="inline-flex items-center gap-1 hover:text-slate-800">
                  ID <i class="{{ $sortIcon('id') }}"></i>
                </a>
              </th>
              <th class="text-left px-5 py-3 font-semibold">
                <a href="{{ $sortUrl('username') }}" class="inline-flex items-center gap-1 hover:text-slate-800">
                  Usuario <i class="{{ $sortIcon('username') }}"></i>
                </a>
              </th>
              <th class="text-left px-5 py-3 font-semibold">
                <a href="{{ $sortUrl('email') }}" class="inline-flex items-center gap-1 hover:text-slate-800">
                  Email <i class="{{ $sortIcon('email') }}"></i>
                </a>
              </th>
              <th class="text-center px-5 py-3 font-semibold">
                <a href="{{ $sortUrl('role') }}" class="inline-flex items-center gap-1 hover:text-slate-800">
                  Role <i class="{{ $sortIcon('role') }}"></i>
                </a>
              </th>
              <th class="text-center px-5 py-3 font-semibold">Sesión</th>
              <th class="text-right px-5 py-3 font-semibold">
                <a href="{{ $sortUrl('created_at') }}" class="inline-flex items-center gap-1 hover:text-slate-800">
                  Registrado <i class="{{ $sortIcon('created_at') }}"></i>
                </a>
              </th>
              <th class="px-5 py-3 font-semibold w-12"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach($rows as $u)
              @php $isMe = (int)$u['id'] === $currentUserId; @endphp
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-5 py-3 text-slate-400 font-mono text-xs">#{{ $u['id'] }}</td>
                <td class="px-5 py-3">
                  <a href="admin/ver_usuario/{{ $u['id'] }}" class="flex items-center gap-3 group">
                    <span class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-primary text-white text-sm font-semibold flex-shrink-0">
                      {{ strtoupper(substr($u['username'] ?? '?', 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                      <div class="font-medium text-slate-800 group-hover:text-primary truncate">
                        {{ $u['username'] }}
                        @if($isMe)<span class="ml-1 text-[10px] font-medium text-primary">(tú)</span>@endif
                      </div>
                    </div>
                  </a>
                </td>
                <td class="px-5 py-3 text-slate-600">{{ $u['email'] ?? '—' }}</td>
                <td class="px-5 py-3 text-center">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
                    <i class="ri-shield-user-line"></i> {{ $u['role'] ?? '—' }}
                  </span>
                </td>
                <td class="px-5 py-3 text-center">
                  @if(!empty($u['auth_token']))
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-medium">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> activa
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-medium">
                      inactiva
                    </span>
                  @endif
                </td>
                <td class="px-5 py-3 text-right text-xs text-slate-500 whitespace-nowrap">
                  {{ !empty($u['created_at']) ? date('d/m/Y', strtotime($u['created_at'])) : '—' }}
                </td>
                {{-- Acciones dropdown --}}
                <td class="px-3 py-3 text-right">
                  <div class="hs-dropdown relative inline-flex">
                    <button type="button" class="hs-dropdown-toggle inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition">
                      <i class="ri-more-2-fill"></i>
                    </button>
                    <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[10rem] bg-white shadow-lg rounded-xl p-1 mt-2 border border-slate-200 z-20">
                      <a href="admin/ver_usuario/{{ $u['id'] }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-slate-100 text-slate-700">
                        <i class="ri-eye-line text-slate-400"></i> Ver detalle
                      </a>
                      <a href="admin/editar_usuario/{{ $u['id'] }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-slate-100 text-slate-700">
                        <i class="ri-edit-line text-slate-400"></i> Editar
                      </a>
                      @if(!$isMe && !empty($u['auth_token']))
                        <a href="{{ build_url('admin/destruir_sesion/' . $u['id']) }}"
                           onclick="return confirm('¿Cerrar la sesión de {{ $u['username'] }}?')"
                           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-amber-50 text-amber-700">
                          <i class="ri-logout-box-r-line"></i> Cerrar sesión
                        </a>
                      @endif
                      @if(!$isMe)
                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="{{ build_url('admin/borrar_usuario/' . $u['id']) }}"
                           onclick="return confirm('¿Eliminar a {{ $u['username'] }}? Esta acción no se puede deshacer.')"
                           class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-red-50 text-red-600">
                          <i class="ri-delete-bin-line"></i> Eliminar
                        </a>
                      @endif
                    </div>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Footer con paginación --}}
      <div class="px-5 py-3 border-t border-slate-100 flex items-center justify-between flex-wrap gap-3">
        <div class="text-xs text-slate-500">
          Mostrando
          <span class="font-medium text-slate-700">{{ ($users['offset'] ?? 0) + 1 }}</span>
          —
          <span class="font-medium text-slate-700">{{ min(($users['offset'] ?? 0) + count($rows), $users['total'] ?? 0) }}</span>
          de
          <span class="font-medium text-slate-700">{{ $users['total'] ?? 0 }}</span>
        </div>
        @if(!empty($users['pagination']))
          <div>{!! $users['pagination'] !!}</div>
        @endif
      </div>
    @endif
  </div>
</div>
@endsection
