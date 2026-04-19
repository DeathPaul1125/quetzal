@extends('includes.admin.layout')

@section('title', 'Editar: ' . ($user['username'] ?? ''))
@section('page_title', 'Editar usuario')

@section('content')
<div class="max-w-3xl space-y-4">

  <div class="flex items-center justify-between">
    <a href="admin/ver_usuario/{{ $user['id'] }}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al detalle
    </a>
    <a href="{{ build_url('admin/borrar_usuario/' . $user['id']) }}"
       onclick="return confirm('¿Eliminar a {{ $user['username'] }}? Esta acción no se puede deshacer.')"
       class="text-sm text-red-600 hover:text-red-700 inline-flex items-center gap-1">
      <i class="ri-delete-bin-line"></i> Eliminar usuario
    </a>
  </div>

  <form method="post" action="admin/post_editar_usuario" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 space-y-6">
    @csrf
    <input type="hidden" name="id" value="{{ $user['id'] }}">

    <div class="flex items-start gap-4 border-b border-slate-100 pb-5">
      <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-primary text-white text-lg font-semibold">
        {{ strtoupper(substr($user['username'] ?? '?', 0, 1)) }}
      </span>
      <div>
        <h3 class="text-lg font-semibold text-slate-800">{{ $user['username'] }}</h3>
        <p class="text-sm text-slate-500 mt-0.5">
          ID #{{ $user['id'] }} · Registrado {{ !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '—' }}
        </p>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nombre de usuario</label>
        <input type="text" name="username" value="{{ $user['username'] }}" required minlength="5" maxlength="20" pattern="^[a-zA-Z0-9]{5,20}$"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
        <input type="email" name="email" value="{{ $user['email'] }}" required
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
      </div>

      <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Role</label>
        <select name="role" class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
          @foreach($roles as $r)
            <option value="{{ $r['slug'] }}" @if(($user['role'] ?? '') === $r['slug']) selected @endif>
              {{ $r['nombre'] }} — <span class="font-mono">{{ $r['slug'] }}</span>
            </option>
          @endforeach
        </select>
      </div>

      <div class="sm:col-span-2 pt-4 border-t border-slate-100">
        <h4 class="text-sm font-semibold text-slate-700 mb-3 flex items-center gap-2">
          <i class="ri-lock-line"></i> Cambiar contraseña
          <span class="text-xs font-normal text-slate-400">(opcional — déjala vacía para no cambiarla)</span>
        </h4>
        <div class="relative">
          <input type="password" name="password" id="pw-field" minlength="5" maxlength="20"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono pr-10"
                 placeholder="Nueva contraseña...">
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
      <a href="admin/ver_usuario/{{ $user['id'] }}" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Cancelar</a>
      <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
        <i class="ri-save-line"></i> Guardar cambios
      </button>
    </div>
  </form>
</div>
@endsection
