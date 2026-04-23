@extends('includes.admin.layout')

@section('title', 'Roles')
@section('page_title', 'Roles')

@php
  $protectedSlugs = ['admin', 'developer', 'worker'];
  $f = $filters ?? ['q' => ''];
@endphp

@section('content')
<div class="space-y-4">

  {{-- Toolbar --}}
  <div class="bg-gradient-to-br from-sky-500 to-indigo-700 text-white rounded-xl p-5 shadow-sm relative overflow-hidden">
    <i class="ri-shield-user-fill" style="position:absolute;right:24px;top:50%;transform:translateY(-50%);font-size:96px;opacity:.15;"></i>
    <div class="flex items-center justify-between gap-4 relative z-10">
      <div>
        <div class="text-xs uppercase tracking-widest opacity-80 font-semibold">Administración</div>
        <h3 class="text-xl font-bold mt-0.5">Roles del sistema</h3>
        <p class="text-xs opacity-90 mt-1">Gestión de roles y permisos. Los marcados como "sistema" no pueden eliminarse.</p>
      </div>
      <a href="admin/crear_role" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white text-indigo-700 text-sm font-semibold hover:bg-indigo-50 transition-colors">
        <i class="ri-add-line"></i> Nuevo role
      </a>
    </div>
  </div>

  {{-- Tabla --}}
  @if(empty($roles))
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center">
      <i class="ri-shield-user-line text-5xl text-slate-300 mb-2 block"></i>
      <p class="text-sm text-slate-500">No hay roles registrados.</p>
    </div>
  @else
    <div class="q-dt-wrap" data-title="Roles" data-icon="ri-shield-user-line" data-unit="roles">
      <div class="overflow-x-auto">
        <table class="q-data-table">
          <thead>
            <tr>
              <th data-type="number">ID</th>
              <th>Nombre</th>
              <th>Slug</th>
              <th data-type="number">Permisos</th>
              <th data-type="number">Usuarios</th>
              <th data-type="date">Creado</th>
              <th data-sortable="false" style="width:1%;"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach($roles as $r)
              @php $isProtected = in_array($r['slug'], $protectedSlugs, true); @endphp
              <tr class="hover:bg-slate-50/60 transition">
                <td class="px-5 py-3 text-slate-400 font-mono text-xs">#{{ $r['id'] }}</td>
                <td class="px-5 py-3">
                  <a href="admin/ver_role/{{ $r['id'] }}" class="font-medium text-slate-800 hover:text-primary">
                    {{ $r['nombre'] }}
                  </a>
                  @if($isProtected)
                    <span class="ml-1 inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-medium">
                      <i class="ri-lock-line"></i> sistema
                    </span>
                  @endif
                </td>
                <td class="px-5 py-3"><code class="text-xs bg-slate-100 px-2 py-0.5 rounded">{{ $r['slug'] }}</code></td>
                <td class="px-5 py-3 text-center">
                  <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
                    {{ $r['permiso_count'] ?? 0 }}
                  </span>
                </td>
                <td class="px-5 py-3 text-center">
                  <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-medium">
                    {{ $r['user_count'] ?? 0 }}
                  </span>
                </td>
                <td class="px-5 py-3 text-right text-xs text-slate-500 whitespace-nowrap">
                  {{ !empty($r['creado']) ? date('d/m/Y', strtotime($r['creado'])) : '—' }}
                </td>
                <td class="px-3 py-3 text-right">
                  <div class="hs-dropdown relative inline-flex">
                    <button type="button" class="hs-dropdown-toggle inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                      <i class="ri-more-2-fill"></i>
                    </button>
                    <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[10rem] bg-white shadow-lg rounded-xl p-1 mt-2 border border-slate-200 z-20">
                      <a href="admin/ver_role/{{ $r['id'] }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-slate-100 text-slate-700">
                        <i class="ri-eye-line text-slate-400"></i> Ver detalle
                      </a>
                      <a href="admin/editar_role/{{ $r['id'] }}" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-slate-100 text-slate-700">
                        <i class="ri-edit-line text-slate-400"></i> {{ $isProtected ? 'Asignar permisos' : 'Editar' }}
                      </a>
                      @if(!$isProtected && ($r['user_count'] ?? 0) == 0)
                        <div class="border-t border-slate-100 my-1"></div>
                        <a href="{{ build_url('admin/borrar_role/' . $r['id']) }}"
                           onclick="return confirm('¿Eliminar el role &quot;{{ $r['nombre'] }}&quot;?')"
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
    </div>
  @endif
</div>
@endsection
