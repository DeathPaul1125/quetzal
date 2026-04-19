@extends('includes.admin.layout')

@section('title', $user['username'] ?? 'Usuario')
@section('page_title', 'Detalle del usuario')

@php $isMe = (int)$user['id'] === (int)(get_user('id') ?? 0); @endphp

@section('content')
<div class="space-y-4">

  <div class="flex items-center justify-between flex-wrap gap-3">
    <a href="admin/usuarios" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al listado
    </a>
    <div class="flex items-center gap-2">
      <a href="admin/editar_usuario/{{ $user['id'] }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium">
        <i class="ri-edit-line"></i> Editar
      </a>
      @if(!$isMe && !empty($user['auth_token']))
        <a href="{{ build_url('admin/destruir_sesion/' . $user['id']) }}"
           onclick="return confirm('¿Cerrar la sesión de {{ $user['username'] }}?')"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-amber-200 text-amber-700 hover:bg-amber-50 text-sm font-medium">
          <i class="ri-logout-box-r-line"></i> Cerrar sesión
        </a>
      @endif
      @if(!$isMe)
        <a href="{{ build_url('admin/borrar_usuario/' . $user['id']) }}"
           onclick="return confirm('¿Eliminar a {{ $user['username'] }}?')"
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-sm font-medium">
          <i class="ri-delete-bin-line"></i> Eliminar
        </a>
      @endif
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Tarjeta principal --}}
    <div class="lg:col-span-1">
      <div class="bg-white rounded-xl border border-slate-200 p-6 text-center">
        <span class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-primary text-white text-3xl font-bold">
          {{ strtoupper(substr($user['username'] ?? '?', 0, 1)) }}
        </span>
        <h2 class="mt-4 text-xl font-bold text-slate-800">
          {{ $user['username'] }}
          @if($isMe)<span class="ml-1 text-xs font-medium text-primary">(tú)</span>@endif
        </h2>
        <p class="text-sm text-slate-500 break-all">{{ $user['email'] }}</p>
        <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-sm font-medium text-slate-700">
          <i class="ri-shield-user-line"></i> {{ $user['role'] ?? '—' }}
        </div>
      </div>
    </div>

    {{-- Información detallada --}}
    <div class="lg:col-span-2 space-y-4">

      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100">
          <h3 class="font-semibold text-slate-800 flex items-center gap-2">
            <i class="ri-information-line text-primary"></i> Información
          </h3>
        </div>
        <dl class="divide-y divide-slate-100">
          <div class="flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4">
            <dt class="sm:w-1/3 text-slate-500">ID</dt>
            <dd class="font-mono font-medium text-slate-800">#{{ $user['id'] }}</dd>
          </div>
          <div class="flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4">
            <dt class="sm:w-1/3 text-slate-500">Usuario</dt>
            <dd class="font-medium text-slate-800">{{ $user['username'] }}</dd>
          </div>
          <div class="flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4">
            <dt class="sm:w-1/3 text-slate-500">Email</dt>
            <dd class="font-medium text-slate-800 break-all">{{ $user['email'] }}</dd>
          </div>
          <div class="flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4">
            <dt class="sm:w-1/3 text-slate-500">Role</dt>
            <dd class="font-medium text-slate-800">{{ $user['role'] ?? '—' }}</dd>
          </div>
          <div class="flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4">
            <dt class="sm:w-1/3 text-slate-500">Registrado</dt>
            <dd class="font-medium text-slate-800">
              {{ !empty($user['created_at']) ? date('d/m/Y H:i', strtotime($user['created_at'])) : '—' }}
            </dd>
          </div>
          <div class="flex flex-col sm:flex-row sm:items-center px-5 py-3 text-sm gap-1 sm:gap-4">
            <dt class="sm:w-1/3 text-slate-500">Sesión activa</dt>
            <dd>
              @if(!empty($user['auth_token']))
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-medium">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Sesión iniciada
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-medium">
                  Sin sesión activa
                </span>
              @endif
            </dd>
          </div>
        </dl>
      </div>

      {{-- Permisos del role --}}
      <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-slate-100 flex items-center justify-between">
          <h3 class="font-semibold text-slate-800 flex items-center gap-2">
            <i class="ri-key-2-line text-primary"></i> Permisos del role
          </h3>
          <span class="text-xs text-slate-500">{{ count($permissions) }} permiso(s)</span>
        </div>
        <div class="p-5">
          @if(empty($permissions))
            <p class="text-sm text-slate-500 text-center py-4">
              Este role no tiene permisos asignados.
              <a href="admin/roles" class="text-primary hover:underline">Asignar permisos →</a>
            </p>
          @else
            <div class="flex flex-wrap gap-1.5">
              @foreach($permissions as $p)
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium" title="{{ $p['descripcion'] ?? '' }}">
                  <i class="ri-check-line text-emerald-600"></i> {{ $p['slug'] }}
                </span>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
