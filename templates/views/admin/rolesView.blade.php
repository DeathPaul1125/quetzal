@extends('includes.admin.layout')

@section('title', 'Roles')
@section('page_title', 'Roles y permisos')

@php
  $protectedSlugs = ['admin', 'developer', 'worker'];
@endphp

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- Listado de roles --}}
  <div class="lg:col-span-2">
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800 flex items-center gap-2">
          <i class="ri-shield-user-line text-primary"></i> Roles del sistema
        </h2>
        <span class="text-xs text-slate-500">{{ count($roles) }} roles</span>
      </div>

      @if(empty($roles))
        <div class="p-8 text-center text-sm text-slate-500">
          No hay roles registrados.
        </div>
      @else
        <table class="w-full text-sm">
          <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
            <tr>
              <th class="text-left px-5 py-2.5 font-semibold">Nombre</th>
              <th class="text-left px-5 py-2.5 font-semibold">Slug</th>
              <th class="text-center px-5 py-2.5 font-semibold">Permisos</th>
              <th class="text-center px-5 py-2.5 font-semibold">Usuarios</th>
              <th class="text-right px-5 py-2.5 font-semibold">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @foreach($roles as $r)
              @php $isProtected = in_array($r['slug'], $protectedSlugs, true); @endphp
              <tr class="hover:bg-slate-50/60">
                <td class="px-5 py-3">
                  <div class="font-medium">{{ $r['nombre'] }}</div>
                  @if($isProtected)
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 mt-1 rounded-full bg-amber-50 text-amber-700 text-[10px] font-medium">
                      <i class="ri-lock-line"></i> protegido
                    </span>
                  @endif
                </td>
                <td class="px-5 py-3"><code class="text-xs bg-slate-100 px-2 py-0.5 rounded">{{ $r['slug'] }}</code></td>
                <td class="px-5 py-3 text-center">
                  <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full bg-slate-100 text-xs font-medium">
                    {{ $r['permiso_count'] ?? 0 }}
                  </span>
                </td>
                <td class="px-5 py-3 text-center">
                  <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-xs font-medium">
                    {{ $r['user_count'] ?? 0 }}
                  </span>
                </td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                  <a href="admin/editar_role/{{ $r['id'] }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs bg-slate-100 hover:bg-slate-200 text-slate-700" title="Editar y asignar permisos">
                    <i class="ri-edit-line"></i> Editar
                  </a>
                  @if(!$isProtected && ($r['user_count'] ?? 0) == 0)
                    <a href="{{ build_url('admin/borrar_role/' . $r['id']) }}"
                       onclick="return confirm('¿Eliminar el role &quot;{{ $r['nombre'] }}&quot;?')"
                       class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs bg-red-50 hover:bg-red-100 text-red-600" title="Borrar">
                      <i class="ri-delete-bin-line"></i>
                    </a>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @endif
    </div>
  </div>

  {{-- Formulario de creación --}}
  <div class="lg:col-span-1">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
      <h3 class="font-semibold text-slate-800 mb-3 flex items-center gap-2">
        <i class="ri-add-circle-line text-primary"></i> Nuevo role
      </h3>
      <form method="post" action="admin/post_role" class="space-y-3">
        @csrf
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
          <input type="text" name="nombre" required minlength="3" maxlength="100"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
                 placeholder="Ej. Contador">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Slug</label>
          <input type="text" name="slug" required pattern="^[a-z0-9-]{3,50}$"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono"
                 placeholder="ej. contador">
          <p class="text-xs text-slate-500 mt-1">Solo minúsculas, números y guiones.</p>
        </div>
        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg btn-primary font-semibold text-sm">
          <i class="ri-add-line"></i> Crear role
        </button>
      </form>

      <div class="mt-5 pt-5 border-t border-slate-100 text-xs text-slate-500 space-y-1.5">
        <p><i class="ri-information-line"></i> Los roles <code>admin</code>, <code>worker</code> y <code>developer</code> son del sistema y no pueden renombrarse.</p>
        <p><i class="ri-information-line"></i> No puedes eliminar un role con usuarios asignados.</p>
      </div>
    </div>

    <div class="mt-4 bg-white rounded-xl border border-slate-200 p-5 text-sm">
      <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Permisos totales</h4>
      <div class="text-2xl font-bold">{{ count($permissions) }}</div>
      <a href="admin/permisos" class="text-xs text-primary hover:underline mt-1 inline-flex items-center gap-1">
        Gestionar permisos <i class="ri-arrow-right-line"></i>
      </a>
    </div>
  </div>
</div>
@endsection
