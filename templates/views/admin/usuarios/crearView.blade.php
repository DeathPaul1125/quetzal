@extends('includes.admin.layout')

@section('title', 'Nuevo usuario')
@section('page_title', 'Nuevo usuario')

@section('content')
<div class="max-w-3xl space-y-4">

  <div>
    <a href="admin/usuarios" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al listado
    </a>
  </div>

  <form method="post" action="admin/post_crear_usuario" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 space-y-6">
    @csrf

    <div class="flex items-start gap-4 border-b border-slate-100 pb-5">
      <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
        <i class="ri-user-add-line text-primary text-xl"></i>
      </div>
      <div>
        <h3 class="text-lg font-semibold text-slate-800">Datos del usuario</h3>
        <p class="text-sm text-slate-500 mt-0.5">Completa la información para crear un nuevo usuario en el sistema.</p>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nombre de usuario <span class="text-red-500">*</span></label>
        <input type="text" name="username" required minlength="5" maxlength="20" pattern="^[a-zA-Z0-9]{5,20}$"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono"
               placeholder="ej. juanperez">
        <p class="text-xs text-slate-500 mt-1">5-20 caracteres alfanuméricos (sin espacios).</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email <span class="text-red-500">*</span></label>
        <input type="email" name="email" required
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
               placeholder="usuario@ejemplo.com">
      </div>

      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Role <span class="text-red-500">*</span></label>
        <select name="role" required class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
          @foreach($roles as $r)
            <option value="{{ $r['slug'] }}" @if($r['slug'] === 'worker') selected @endif>
              {{ $r['nombre'] }} — <span class="font-mono">{{ $r['slug'] }}</span>
            </option>
          @endforeach
        </select>
        <p class="text-xs text-slate-500 mt-1">El role define los permisos del usuario. Gestiona roles en <a href="admin/roles" class="text-primary hover:underline">Roles</a>.</p>
      </div>

      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Contraseña <span class="text-red-500">*</span></label>
        <div class="relative">
          <input type="password" name="password" required minlength="5" maxlength="20" id="pw-field"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono pr-10">
          <button type="button" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600"
                  onclick="(function(){var i=document.getElementById('pw-field');i.type=i.type==='password'?'text':'password';})()">
            <i class="ri-eye-line"></i>
          </button>
        </div>
        <p class="text-xs text-slate-500 mt-1">
          5-20 caracteres. Debe incluir: 1 minúscula, 1 mayúscula, 1 dígito y 1 especial de <code class="bg-slate-100 px-1 rounded">!@#$%^&*_-</code>
        </p>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="admin/usuarios" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Cancelar</a>
      <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
        <i class="ri-save-line"></i> Crear usuario
      </button>
    </div>
  </form>
</div>
@endsection
