@extends('includes.admin.layout')

@section('title', $role['nombre'])
@section('page_title', 'Detalle del role')

@section('content')
<div class="space-y-4">

  <div class="flex items-center justify-between flex-wrap gap-3">
    <a href="admin/roles" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al listado
    </a>
    <div class="flex items-center gap-2">
      <a href="admin/editar_role/{{ $role['id'] }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium">
        <i class="ri-edit-line"></i> Editar
      </a>
      @if(!$isProtected && count($users) === 0)
        <a href="{{ build_url('admin/borrar_role/' . $role['id']) }}"
           onclick="return confirm('¿Eliminar el role &quot;{{ $role['nombre'] }}&quot;?')"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium">
          <i class="ri-delete-bin-line"></i> Eliminar
        </a>
      @endif
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Card principal --}}
    <div class="lg:col-span-1">
      <div class="bg-white rounded-xl border border-slate-200 p-6 text-center">
        <div class="mx-auto w-20 h-20 rounded-2xl bg-primary/10 flex items-center justify-center">
          <i class="ri-shield-user-line text-primary text-3xl"></i>
        </div>
        <h2 class="mt-4 text-xl font-bold text-slate-800">{{ $role['nombre'] }}</h2>
        <code class="text-xs text-slate-500 font-mono">{{ $role['slug'] }}</code>
        @if($isProtected)
          <div class="mt-3 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-medium">
            <i class="ri-lock-line"></i> Role del sistema
          </div>
        @endif

        <dl class="mt-6 divide-y divide-slate-100 border-t border-slate-100 text-sm text-left">
          <div class="flex justify-between py-2.5">
            <dt class="text-slate-500">ID</dt>
            <dd class="font-mono font-medium">#{{ $role['id'] }}</dd>
          </div>
          <div class="flex justify-between py-2.5">
            <dt class="text-slate-500">Permisos</dt>
            <dd class="font-medium">{{ count($permissions) }}</dd>
          </div>
          <div class="flex justify-between py-2.5">
            <dt class="text-slate-500">Usuarios</dt>
            <dd class="font-medium">{{ count($users) }}</dd>
          </div>
          <div class="flex justify-between py-2.5">
            <dt class="text-slate-500">Creado</dt>
            <dd class="font-medium">{{ !empty($role['creado']) ? date('d/m/Y', strtotime($role['creado'])) : '—' }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="lg:col-span-2 space-y-4">
      {{-- Permisos --}}
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-semibold text-slate-800 flex items-center gap-2">
            <i class="ri-key-2-line text-primary"></i> Permisos asignados
          </h3>
          <span class="text-xs text-slate-500">{{ count($permissions) }}</span>
        </div>
        <div class="p-5">
          @if(empty($permissions))
            <p class="text-sm text-slate-500 text-center py-4">
              Este role no tiene permisos.
              <a href="admin/editar_role/{{ $role['id'] }}" class="text-primary hover:underline">Asignar →</a>
            </p>
          @else
            <div class="flex flex-wrap gap-1.5">
              @foreach($permissions as $p)
                <a href="admin/ver_permiso/{{ $p['id'] }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 hover:bg-primary/10 text-slate-700 text-xs font-medium transition" title="{{ $p['descripcion'] ?? '' }}">
                  <i class="ri-check-line text-emerald-600"></i> {{ $p['slug'] }}
                </a>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      {{-- Usuarios con este role --}}
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-semibold text-slate-800 flex items-center gap-2">
            <i class="ri-user-line text-primary"></i> Usuarios con este role
          </h3>
          <span class="text-xs text-slate-500">{{ count($users) }}</span>
        </div>
        @if(empty($users))
          <div class="p-5 text-center text-sm text-slate-500">
            Ningún usuario tiene este role asignado.
          </div>
        @else
          <ul class="divide-y divide-slate-100">
            @foreach($users as $u)
              <li class="px-5 py-2.5 flex items-center gap-3 hover:bg-slate-50/60">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary text-white text-xs font-semibold">
                  {{ strtoupper(substr($u['username'] ?? '?', 0, 1)) }}
                </span>
                <div class="flex-1 min-w-0">
                  <a href="admin/ver_usuario/{{ $u['id'] }}" class="text-sm font-medium text-slate-800 hover:text-primary">{{ $u['username'] }}</a>
                  <div class="text-xs text-slate-500 truncate">{{ $u['email'] }}</div>
                </div>
              </li>
            @endforeach
          </ul>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
