@extends('includes.admin.layout')

@section('title', 'Mi perfil')
@section('page_title', 'Mi perfil')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- Card de identidad --}}
  <div class="lg:col-span-1">
    <div class="bg-white rounded-xl border border-slate-200 p-6 text-center">
      <div class="mx-auto w-24 h-24 rounded-full bg-primary text-white flex items-center justify-center text-3xl font-bold">
        {{ strtoupper(substr($user['username'] ?? '?', 0, 1)) }}
      </div>
      <h2 class="mt-4 text-lg font-bold">{{ $user['username'] ?? '' }}</h2>
      <p class="text-sm text-slate-500">{{ $user['email'] ?? '' }}</p>
      <div class="mt-3 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-xs font-medium text-slate-700">
        <i class="ri-shield-user-line"></i> {{ $user['role'] ?? '—' }}
      </div>

      <dl class="mt-6 divide-y divide-slate-100 border-t border-slate-100 text-sm text-left">
        <div class="flex justify-between py-2.5">
          <dt class="text-slate-500">ID</dt>
          <dd class="font-medium">#{{ $user['id'] ?? '' }}</dd>
        </div>
        <div class="flex justify-between py-2.5">
          <dt class="text-slate-500">Registrado</dt>
          <dd class="font-medium">{{ !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '—' }}</dd>
        </div>
        @isset($permissions)
          <div class="flex justify-between py-2.5">
            <dt class="text-slate-500">Permisos</dt>
            <dd class="font-medium">{{ count($permissions) }}</dd>
          </div>
        @endisset
      </dl>
    </div>

    @isset($permissions)
      @if(count($permissions))
        <div class="bg-white rounded-xl border border-slate-200 p-5 mt-4">
          <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Permisos del role</h3>
          <div class="flex flex-wrap gap-1.5">
            @foreach($permissions as $p)
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-xs font-medium text-slate-700" title="{{ $p['descripcion'] ?? '' }}">
                {{ $p['slug'] }}
              </span>
            @endforeach
          </div>
        </div>
      @endif
    @endisset
  </div>

  {{-- Formulario de edición --}}
  <div class="lg:col-span-2">
    <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8">
      <h3 class="text-lg font-semibold mb-4">Editar información</h3>

      <form method="post" action="admin/post_perfil" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Nombre de usuario</label>
            <input type="text" name="username" value="{{ $user['username'] ?? '' }}"
                   class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
                   required minlength="3" maxlength="50">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" value="{{ $user['email'] ?? '' }}"
                   class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
                   required>
          </div>
        </div>

        <div class="border-t border-slate-100 pt-5">
          <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
            <i class="ri-lock-line"></i> Cambiar contraseña
            <span class="text-xs font-normal text-slate-400">(opcional)</span>
          </h4>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Nueva contraseña</label>
              <input type="password" name="password" autocomplete="new-password"
                     class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
                     placeholder="Mínimo 6 caracteres">
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">Confirmar contraseña</label>
              <input type="password" name="password_confirm" autocomplete="new-password"
                     class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
            </div>
          </div>
        </div>

        <div class="flex justify-between items-center pt-2">
          <a href="admin" class="text-slate-500 hover:text-slate-800 text-sm">← Volver al dashboard</a>
          <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
            <i class="ri-save-line"></i> Guardar cambios
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
