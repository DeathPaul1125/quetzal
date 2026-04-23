@extends('includes.admin.layout')

@section('title', 'Tu cuenta')
@section('page_title', 'Tu cuenta')

@push('head')
<style>
  .w11-hero {
    background: linear-gradient(135deg, #0078d4 0%, #005fa3 40%, #003e7e 100%);
    color: white;
    border-radius: 12px;
    padding: 28px 32px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 8px 24px rgba(0, 120, 212, 0.25);
  }
  .w11-avatar {
    width: 96px; height: 96px; border-radius: 50%;
    background: rgba(255,255,255,0.22);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    display: flex; align-items: center; justify-content: center;
    font-size: 42px; font-weight: 700;
    border: 3px solid rgba(255,255,255,0.4);
    text-transform: uppercase;
  }
  .w11-card {
    background: white;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    padding: 20px 24px;
    transition: border-color .15s;
  }
  .w11-card:hover { border-color: #cbd5e1; }
  .w11-card-head {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 14px;
  }
  .w11-card-icon {
    width: 40px; height: 40px; border-radius: 8px;
    background: rgba(0, 120, 212, 0.1);
    color: #0078d4;
    display: flex; align-items: center; justify-content: center;
    font-size: 20px;
  }
  .w11-card-title { font-weight: 600; font-size: 15px; }
  .w11-card-sub   { font-size: 12px; color: #64748b; }
  .w11-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 10px 0; border-bottom: 1px solid #f1f5f9;
  }
  .w11-row:last-child { border-bottom: none; }
  .w11-row .k { font-size: 13px; color: #475569; }
  .w11-row .v { font-size: 13px; font-weight: 500; color: #0f172a; }
  .w11-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 999px;
    font-size: 11px; font-weight: 600;
  }
  .w11-input {
    background: white;
    border: 1px solid #cbd5e1;
    border-radius: 4px;
    padding: 6px 10px;
    font-size: 13px;
    transition: border-color .15s;
    width: 100%;
  }
  .w11-input:focus {
    outline: none;
    border-color: #0078d4;
    box-shadow: 0 0 0 1px #0078d4;
  }
  .w11-label {
    display: block; font-size: 12px; color: #475569; margin-bottom: 4px;
  }
  .w11-btn {
    background: #0078d4; color: white;
    padding: 8px 16px; border-radius: 4px;
    font-size: 13px; font-weight: 500;
    border: none; cursor: pointer;
    transition: background .15s;
    display: inline-flex; align-items: center; gap: 6px;
  }
  .w11-btn:hover { background: #106ebe; }
  .w11-btn-secondary {
    background: white; color: #0f172a;
    border: 1px solid #cbd5e1;
  }
  .w11-btn-secondary:hover { background: #f8fafc; }
  .w11-perm-chip {
    display: inline-flex; align-items: center;
    padding: 3px 10px; border-radius: 999px;
    font-size: 11px; background: #f1f5f9; color: #475569;
    margin: 2px;
  }
</style>
@endpush

@section('content')
<div class="space-y-4 max-w-5xl">

  {{-- HERO: banner estilo Win11 Settings --}}
  <div class="w11-hero">
    <div class="w11-avatar">{{ strtoupper(substr($user['username'] ?? 'U', 0, 1)) }}</div>
    <div class="flex-1">
      <div style="font-size: 24px; font-weight: 700;">{{ $user['username'] ?? 'Usuario' }}</div>
      <div style="font-size: 14px; opacity: 0.9; margin-top: 2px;">{{ $user['email'] ?? '' }}</div>
      <div style="margin-top: 10px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
        <span class="w11-badge" style="background: rgba(255,255,255,0.22); color: white;">
          <i class="ri-shield-user-line"></i> {{ ucfirst($user['role'] ?? '—') }}
        </span>
        <span style="font-size: 12px; opacity: 0.85;">
          ID #{{ $user['id'] ?? '' }} · Activo desde {{ !empty($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '—' }}
        </span>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Info de cuenta + cambiar contraseña --}}
    <div class="lg:col-span-2 space-y-4">
      <div class="w11-card">
        <div class="w11-card-head">
          <div class="w11-card-icon"><i class="ri-user-settings-line"></i></div>
          <div>
            <div class="w11-card-title">Información de la cuenta</div>
            <div class="w11-card-sub">Nombre de usuario y correo de inicio de sesión</div>
          </div>
        </div>
        <form method="post" action="admin/post_perfil" class="space-y-4">
          @csrf
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="w11-label">Nombre de usuario</label>
              <input type="text" name="username" value="{{ $user['username'] ?? '' }}" required minlength="3" maxlength="50" class="w11-input">
            </div>
            <div>
              <label class="w11-label">Correo electrónico</label>
              <input type="email" name="email" value="{{ $user['email'] ?? '' }}" required class="w11-input">
            </div>
          </div>

          <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid #f1f5f9;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
              <i class="ri-lock-line" style="color: #0078d4;"></i>
              <span style="font-weight: 600; font-size: 14px;">Opciones de inicio de sesión</span>
              <span style="font-size: 11px; color: #64748b;">(dejá en blanco para no cambiar)</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="w11-label">Nueva contraseña</label>
                <input type="password" name="password" autocomplete="new-password" placeholder="Mínimo 6 caracteres" class="w11-input">
              </div>
              <div>
                <label class="w11-label">Confirmar contraseña</label>
                <input type="password" name="password_confirm" autocomplete="new-password" class="w11-input">
              </div>
            </div>
          </div>

          <div style="display: flex; justify-content: flex-end;">
            <button type="submit" class="w11-btn"><i class="ri-save-line"></i> Guardar cambios</button>
          </div>
        </form>
      </div>

      @isset($permissions)
        @if(count($permissions))
          <div class="w11-card">
            <div class="w11-card-head">
              <div class="w11-card-icon" style="background: rgba(139, 92, 246, 0.1); color: #7c3aed;"><i class="ri-key-2-line"></i></div>
              <div>
                <div class="w11-card-title">Permisos asignados</div>
                <div class="w11-card-sub">{{ count($permissions) }} permiso(s) concedidos por el rol</div>
              </div>
            </div>
            <div style="max-height: 180px; overflow-y: auto;">
              @foreach($permissions as $p)
                <span class="w11-perm-chip" title="{{ $p['descripcion'] ?? '' }}">{{ $p['slug'] }}</span>
              @endforeach
            </div>
          </div>
        @endif
      @endisset
    </div>

    {{-- Sidebar cards --}}
    <div class="space-y-4">
      <div class="w11-card">
        <div class="w11-card-head">
          <div class="w11-card-icon" style="background: rgba(16, 185, 129, 0.1); color: #059669;"><i class="ri-shield-check-line"></i></div>
          <div>
            <div class="w11-card-title">Detalles</div>
          </div>
        </div>
        <div class="w11-row"><span class="k">ID de usuario</span><span class="v">#{{ $user['id'] ?? '' }}</span></div>
        <div class="w11-row"><span class="k">Rol</span><span class="v" style="text-transform: capitalize;">{{ $user['role'] ?? '—' }}</span></div>
        <div class="w11-row"><span class="k">Registrado</span><span class="v">{{ !empty($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '—' }}</span></div>
        @isset($permissions)
          <div class="w11-row"><span class="k">Permisos activos</span><span class="v">{{ count($permissions) }}</span></div>
        @endisset
      </div>

      <div class="w11-card">
        <div class="w11-card-head">
          <div class="w11-card-icon" style="background: rgba(239, 68, 68, 0.1); color: #dc2626;"><i class="ri-logout-box-r-line"></i></div>
          <div>
            <div class="w11-card-title">Sesión</div>
            <div class="w11-card-sub">Cerrar la sesión en este dispositivo</div>
          </div>
        </div>
        <a href="logout" class="w11-btn w11-btn-secondary" style="width: 100%; justify-content: center; text-decoration: none;">
          <i class="ri-shut-down-line" style="color: #dc2626;"></i> Cerrar sesión
        </a>
      </div>

      <div class="w11-card" style="background: linear-gradient(135deg, #eff6ff, #dbeafe);">
        <div class="w11-card-head">
          <div class="w11-card-icon"><i class="ri-information-line"></i></div>
          <div>
            <div class="w11-card-title">¿Olvidaste algo?</div>
            <div class="w11-card-sub">Para cambiar tu rol o permisos, contactá al administrador del sistema.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
