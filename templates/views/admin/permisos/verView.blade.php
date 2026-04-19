@extends('includes.admin.layout')

@section('title', $permiso['nombre'])
@section('page_title', 'Detalle del permiso')

@section('content')
<div class="space-y-4">

  <div class="flex items-center justify-between flex-wrap gap-3">
    <a href="admin/permisos" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al listado
    </a>
    <div class="flex items-center gap-2">
      <a href="admin/editar_permiso/{{ $permiso['id'] }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium">
        <i class="ri-edit-line"></i> Editar
      </a>
      @if(!$isProtected)
        <a href="{{ build_url('admin/borrar_permiso/' . $permiso['id']) }}"
           onclick="return confirm('¿Eliminar el permiso &quot;{{ $permiso['nombre'] }}&quot;? Se removerá de todos los roles que lo tengan.')"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium">
          <i class="ri-delete-bin-line"></i> Eliminar
        </a>
      @endif
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    <div class="lg:col-span-1">
      <div class="bg-white rounded-xl border border-slate-200 p-6 text-center">
        <div class="mx-auto w-20 h-20 rounded-2xl bg-primary/10 flex items-center justify-center">
          <i class="ri-key-2-line text-primary text-3xl"></i>
        </div>
        <h2 class="mt-4 text-xl font-bold text-slate-800">{{ $permiso['nombre'] }}</h2>
        <code class="text-xs text-slate-500 font-mono break-all">{{ $permiso['slug'] }}</code>
        @if($isProtected)
          <div class="mt-3 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-medium">
            <i class="ri-lock-line"></i> Permiso del sistema
          </div>
        @endif

        <dl class="mt-6 divide-y divide-slate-100 border-t border-slate-100 text-sm text-left">
          <div class="flex justify-between py-2.5">
            <dt class="text-slate-500">ID</dt>
            <dd class="font-mono font-medium">#{{ $permiso['id'] }}</dd>
          </div>
          <div class="flex justify-between py-2.5">
            <dt class="text-slate-500">Usado en</dt>
            <dd class="font-medium">{{ count($roles) }} role(s)</dd>
          </div>
          <div class="flex justify-between py-2.5">
            <dt class="text-slate-500">Creado</dt>
            <dd class="font-medium">{{ !empty($permiso['creado']) ? date('d/m/Y', strtotime($permiso['creado'])) : '—' }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="lg:col-span-2 space-y-4">
      {{-- Descripción --}}
      @if(!empty($permiso['descripcion']))
        <div class="bg-white rounded-xl border border-slate-200 p-5">
          <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Descripción</h3>
          <p class="text-sm text-slate-700 leading-relaxed">{{ $permiso['descripcion'] }}</p>
        </div>
      @endif

      {{-- Código de uso --}}
      <div class="bg-white rounded-xl border border-slate-200 p-5">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Uso en código</h3>
        <pre class="bg-slate-900 text-slate-100 rounded-lg p-4 text-xs overflow-x-auto"><code>@@if(user_can('{{ $permiso['slug'] }}'))
    {{-- contenido visible solo si el usuario tiene este permiso --}}
@@endif</code></pre>
      </div>

      {{-- Roles que lo tienen --}}
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-semibold text-slate-800 flex items-center gap-2">
            <i class="ri-shield-user-line text-primary"></i> Roles que lo tienen
          </h3>
          <span class="text-xs text-slate-500">{{ count($roles) }}</span>
        </div>
        @if(empty($roles))
          <div class="p-5 text-center text-sm text-slate-500">
            Ningún role tiene este permiso asignado aún.
          </div>
        @else
          <ul class="divide-y divide-slate-100">
            @foreach($roles as $r)
              <li class="px-5 py-2.5 flex items-center justify-between hover:bg-slate-50/60">
                <div>
                  <a href="admin/ver_role/{{ $r['id'] }}" class="text-sm font-medium text-slate-800 hover:text-primary">{{ $r['nombre'] }}</a>
                  <code class="ml-2 text-xs text-slate-500">{{ $r['slug'] }}</code>
                </div>
                <a href="admin/editar_role/{{ $r['id'] }}" class="text-xs text-slate-500 hover:text-primary">
                  <i class="ri-edit-line"></i>
                </a>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
