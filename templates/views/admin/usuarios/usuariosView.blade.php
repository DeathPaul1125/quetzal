@extends('includes.admin.layout')

@section('title', 'Usuarios')
@section('page_title', 'Gestión de usuarios')

@php
  $rows = $users['rows'] ?? [];
  $currentUserId = (int)(get_user('id') ?? 0);
@endphp

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  {{-- Listado --}}
  <div class="lg:col-span-2">
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800 flex items-center gap-2">
          <i class="ri-user-line text-primary"></i> Usuarios registrados
        </h2>
        <span class="text-xs text-slate-500">
          {{ $users['total'] ?? 0 }} totales · página {{ $users['page'] ?? 1 }}/{{ $users['pages'] ?? 1 }}
        </span>
      </div>

      @if(empty($rows))
        <div class="p-8 text-center text-sm text-slate-500">
          No hay usuarios registrados.
        </div>
      @else
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
              <tr>
                <th class="text-left px-5 py-2.5 font-semibold">Usuario</th>
                <th class="text-left px-5 py-2.5 font-semibold">Email</th>
                <th class="text-center px-5 py-2.5 font-semibold">Role</th>
                <th class="text-center px-5 py-2.5 font-semibold">Sesión</th>
                <th class="text-right px-5 py-2.5 font-semibold">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              @foreach($rows as $u)
                @php $isMe = (int)$u['id'] === $currentUserId; @endphp
                <tr class="hover:bg-slate-50/60">
                  <td class="px-5 py-3">
                    <div class="flex items-center gap-3">
                      <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary text-white text-xs font-semibold">
                        {{ strtoupper(substr($u['username'] ?? '?', 0, 1)) }}
                      </span>
                      <div>
                        <div class="font-medium text-slate-800">
                          {{ $u['username'] }}
                          @if($isMe)
                            <span class="ml-1 text-[10px] font-medium text-primary">(tú)</span>
                          @endif
                        </div>
                        <div class="text-xs text-slate-400">
                          #{{ $u['id'] }} · {{ !empty($u['created_at']) ? date('d/m/Y', strtotime($u['created_at'])) : '—' }}
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="px-5 py-3 text-slate-600">{{ $u['email'] ?? '—' }}</td>
                  <td class="px-5 py-3 text-center">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
                      <i class="ri-shield-user-line"></i> {{ $u['role'] ?? '—' }}
                    </span>
                  </td>
                  <td class="px-5 py-3 text-center">
                    @if(!empty($u['auth_token']))
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-medium">
                        <i class="ri-circle-line text-[8px]"></i> activa
                      </span>
                    @else
                      <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-medium">
                        inactiva
                      </span>
                    @endif
                  </td>
                  <td class="px-5 py-3 text-right whitespace-nowrap">
                    @if(!$isMe)
                      @if(!empty($u['auth_token']))
                        <a href="{{ build_url('admin/destruir_sesion/' . $u['id']) }}"
                           onclick="return confirm('¿Cerrar la sesión de {{ $u['username'] }}?')"
                           class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs bg-amber-50 hover:bg-amber-100 text-amber-700"
                           title="Cerrar sesión activa">
                          <i class="ri-logout-box-r-line"></i>
                        </a>
                      @endif
                      <a href="{{ build_url('admin/borrar_usuario/' . $u['id']) }}"
                         onclick="return confirm('¿Eliminar al usuario {{ $u['username'] }}?')"
                         class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs bg-red-50 hover:bg-red-100 text-red-600"
                         title="Borrar usuario">
                        <i class="ri-delete-bin-line"></i>
                      </a>
                    @else
                      <span class="text-xs text-slate-400">—</span>
                    @endif
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif

      @if(!empty($users['pagination']))
        <div class="px-5 py-3 border-t border-slate-100">
          {!! $users['pagination'] !!}
        </div>
      @endif
    </div>
  </div>

  {{-- Form de creación --}}
  <div class="lg:col-span-1">
    <div class="bg-white rounded-xl border border-slate-200 p-5">
      <h3 class="font-semibold text-slate-800 mb-3 flex items-center gap-2">
        <i class="ri-user-add-line text-primary"></i> Nuevo usuario
      </h3>
      <form method="post" action="admin/post_usuarios" class="space-y-3">
        @csrf

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Nombre de usuario</label>
          <input type="text" name="username" required minlength="5" maxlength="20" pattern="^[a-zA-Z0-9]{5,20}$"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono">
          <p class="text-xs text-slate-500 mt-1">5-20 caracteres alfanuméricos.</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
          <input type="email" name="email" required
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
          <input type="text" name="password" required minlength="5" maxlength="20"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono">
          <p class="text-xs text-slate-500 mt-1">
            5-20 caracteres. Debe incluir: minúscula, mayúscula, dígito, y 1 especial de <code>!@#$%^&*_-</code>
          </p>
        </div>

        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg btn-primary font-semibold text-sm">
          <i class="ri-add-line"></i> Crear usuario
        </button>
      </form>

      <div class="mt-5 pt-5 border-t border-slate-100 text-xs text-slate-500 space-y-1.5">
        <p><i class="ri-information-line"></i> Los usuarios nuevos reciben el role por defecto. Asigna un role desde <a href="admin/roles" class="text-primary hover:underline">Roles</a>.</p>
        <p><i class="ri-information-line"></i> No puedes borrar tu propio usuario ni cerrar tu sesión desde aquí.</p>
      </div>
    </div>
  </div>
</div>
@endsection
