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

  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    @if(empty($permissions))
      <div class="p-12 text-center">
        <i class="ri-key-2-line text-5xl text-slate-300 mb-2 block"></i>
        <p class="text-sm text-slate-500">
          @if($f['q'] !== '')
            No hay permisos que coincidan con tu búsqueda.
          @else
            No hay permisos registrados. Crea el primero.
          @endif
        </p>
      </div>
    @else
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-slate-50 border-b border-slate-200">
            <tr class="text-xs uppercase tracking-wider text-slate-500">
              <th class="text-left px-5 py-3 font-semibold">Nombre</th>
              <th class="text-left px-5 py-3 font-semibold">Slug</th>
              <th class="text-left px-5 py-3 font-semibold">Descripción</th>
              <th class="text-center px-5 py-3 font-semibold">Roles</th>
              <th class="px-5 py-3 font-semibold w-12"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach($permissions as $p)
              @php $isProtected = in_array($p['slug'], $protectedSlugs, true); @endphp
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-5 py-3">
                  <a href="admin/ver_permiso/{{ $p['id'] }}" class="font-medium text-slate-800 hover:text-primary">
                    {{ $p['nombre'] }}
                  </a>
                  @if($isProtected)
                    <span class="ml-1 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-medium">
                      <i class="ri-lock-line"></i> sistema
                    </span>
                  @endif
                </td>
                <td class="px-5 py-3"><code class="text-xs bg-slate-100 px-2 py-0.5 rounded">{{ $p['slug'] }}</code></td>
                <td class="px-5 py-3 text-slate-600 text-sm truncate max-w-xs">{{ $p['descripcion'] ?? '—' }}</td>
                <td class="px-5 py-3 text-center">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
                    <i class="ri-shield-user-line"></i> {{ $p['role_count'] ?? 0 }}
                  </span>
                </td>
                <td class="px-3 py-3 text-right">
                  <div class="hs-dropdown relative inline-flex">
                    <button type="button" class="hs-dropdown-toggle inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                      <i class="ri-more-2-fill"></i>
                    </button>
                    <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[10rem] bg-white shadow-lg rounded-xl p-1 mt-2 border border-slate-200 z-20">
                      <a href="admin/ver_permiso/{{ $p['id'] }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-slate-100 text-slate-700">
                        <i class="ri-eye-line text-slate-400"></i> Ver detalle
                      </a>
                      <a href="admin/editar_permiso/{{ $p['id'] }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-slate-100 text-slate-700">
                        <i class="ri-edit-line text-slate-400"></i> Editar
                      </a>
                      @if(!$isProtected)
                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="{{ build_url('admin/borrar_permiso/' . $p['id']) }}"
                           onclick="return confirm('¿Eliminar el permiso &quot;{{ $p['nombre'] }}&quot;?')"
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
    @endif
  </div>
</div>
@endsection
