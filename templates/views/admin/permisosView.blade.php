@extends('includes.admin.layout')

@section('title', 'Permisos')
@section('page_title', 'Permisos del sistema')

@php
  $protectedSlugs = ['admin-access'];
@endphp

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- Listado de permisos --}}
  <div class="lg:col-span-2 space-y-4">
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800 flex items-center gap-2">
          <i class="ri-key-2-line text-primary"></i> Permisos registrados
        </h2>
        <span class="text-xs text-slate-500">{{ count($permissions) }} permisos</span>
      </div>

      @if(empty($permissions))
        <div class="p-8 text-center text-sm text-slate-500">
          No hay permisos registrados aún. Crea el primero desde el formulario.
        </div>
      @else
        <div class="divide-y divide-slate-100">
          @foreach($permissions as $p)
            @php $isProtected = in_array($p['slug'], $protectedSlugs, true); @endphp
            <div class="p-5 hover:bg-slate-50/60">
              <details class="group">
                <summary class="flex items-center gap-3 cursor-pointer list-none">
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                      <span class="font-medium text-slate-800">{{ $p['nombre'] }}</span>
                      @if($isProtected)
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[10px] font-medium">
                          <i class="ri-lock-line"></i> protegido
                        </span>
                      @endif
                      <code class="text-xs bg-slate-100 px-2 py-0.5 rounded">{{ $p['slug'] }}</code>
                    </div>
                    @if(!empty($p['descripcion']))
                      <div class="text-sm text-slate-500 mt-1 truncate">{{ $p['descripcion'] }}</div>
                    @endif
                  </div>
                  <div class="flex items-center gap-3 text-xs text-slate-500 whitespace-nowrap">
                    <span class="inline-flex items-center gap-1">
                      <i class="ri-shield-user-line"></i> {{ $p['role_count'] ?? 0 }} role(s)
                    </span>
                    <i class="ri-arrow-down-s-line text-slate-400 group-open:rotate-180 transition"></i>
                  </div>
                </summary>

                {{-- Edit inline --}}
                <form method="post" action="admin/post_permiso_editar" class="mt-4 pt-4 border-t border-slate-100 space-y-3">
                  @csrf
                  <input type="hidden" name="id" value="{{ $p['id'] }}">
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                      <label class="block text-xs font-medium text-slate-600 mb-1">Nombre</label>
                      <input type="text" name="nombre" value="{{ $p['nombre'] }}" required minlength="3"
                             class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
                    </div>
                    <div>
                      <label class="block text-xs font-medium text-slate-600 mb-1">Slug <span class="text-slate-400 font-normal">(no editable)</span></label>
                      <input type="text" value="{{ $p['slug'] }}" disabled
                             class="w-full rounded-lg border-slate-200 bg-slate-50 text-slate-500 text-sm font-mono">
                    </div>
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Descripción</label>
                    <input type="text" name="descripcion" value="{{ $p['descripcion'] ?? '' }}"
                           class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
                  </div>
                  <div class="flex items-center justify-end gap-2">
                    @if(!$isProtected)
                      <a href="{{ build_url('admin/borrar_permiso/' . $p['id']) }}"
                         onclick="return confirm('¿Eliminar el permiso &quot;{{ $p['nombre'] }}&quot;? Se removerá de todos los roles que lo tengan.')"
                         class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs bg-red-50 hover:bg-red-100 text-red-600">
                        <i class="ri-delete-bin-line"></i> Borrar
                      </a>
                    @endif
                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md text-xs btn-primary font-semibold">
                      <i class="ri-save-line"></i> Guardar
                    </button>
                  </div>
                </form>
              </details>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  {{-- Crear permiso --}}
  <div class="lg:col-span-1">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
      <h3 class="font-semibold text-slate-800 mb-3 flex items-center gap-2">
        <i class="ri-add-circle-line text-primary"></i> Nuevo permiso
      </h3>
      <form method="post" action="admin/post_permiso" class="space-y-3">
        @csrf
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nombre</label>
          <input type="text" name="nombre" required minlength="3" maxlength="100"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
                 placeholder="Ej. Exportar reportes">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Slug</label>
          <input type="text" name="slug" required pattern="^[a-z0-9-]{3,50}$"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono"
                 placeholder="ej. export-reports">
          <p class="text-xs text-slate-500 mt-1">Solo minúsculas, números y guiones.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Descripción</label>
          <textarea name="descripcion" rows="2"
                    class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
                    placeholder="¿Qué habilita este permiso?"></textarea>
        </div>
        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg btn-primary font-semibold text-sm">
          <i class="ri-add-line"></i> Crear permiso
        </button>
      </form>

      <div class="mt-5 pt-5 border-t border-slate-100 text-xs text-slate-500 space-y-1.5">
        <p><i class="ri-information-line"></i> El slug es la clave que usas en <code>user_can('slug')</code>.</p>
        <p><i class="ri-information-line"></i> El permiso <code>admin-access</code> es protegido del sistema.</p>
      </div>
    </div>
  </div>
</div>
@endsection
