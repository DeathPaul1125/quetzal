<header class="q-topbar bg-white border-b border-slate-200 sticky top-0 z-30">
  <div class="flex items-center justify-between px-4 sm:px-6 py-3">
    <div class="flex items-center gap-3">
      <button type="button" data-q-sidebar-toggle class="lg:hidden p-2 rounded-lg hover:bg-slate-100" aria-label="Menú">
        <i class="ri-menu-line text-xl"></i>
      </button>
      <h1 class="text-base sm:text-lg font-semibold truncate">
        @yield('page_title', $title ?? 'Administración')
      </h1>
    </div>

    <div class="flex items-center gap-2">
      {{-- Quick actions (ejemplo) --}}
      <a href="admin" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm text-slate-600 hover:bg-slate-100" title="Inicio">
        <i class="ri-home-line"></i>
      </a>

      {{-- User menu dropdown (Preline) --}}
      <div class="hs-dropdown relative inline-flex">
        <button type="button" class="hs-dropdown-toggle inline-flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 transition">
          <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-primary text-white text-sm font-semibold">
            {{ strtoupper(substr($user['username'] ?? '?', 0, 1)) }}
          </span>
          <span class="hidden sm:block text-sm">
            <span class="font-medium block leading-tight">{{ $user['username'] ?? 'Invitado' }}</span>
            <span class="text-xs text-slate-500 block leading-tight">{{ $user['role'] ?? '—' }}</span>
          </span>
          <i class="ri-arrow-down-s-line text-slate-400"></i>
        </button>

        <div class="hs-dropdown-menu transition-[opacity,margin] duration hs-dropdown-open:opacity-100 opacity-0 hidden min-w-[14rem] bg-white shadow-lg rounded-xl p-1.5 mt-2 border border-slate-200 z-40">
          <div class="px-3 py-2 border-b border-slate-100">
            <div class="text-sm font-semibold truncate">{{ $user['username'] ?? '' }}</div>
            <div class="text-xs text-slate-500 truncate">{{ $user['email'] ?? '' }}</div>
          </div>
          <a href="admin/perfil" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-slate-100">
            <i class="ri-user-line"></i> Mi perfil
          </a>
          @if(user_can('admin-access'))
            <a href="admin/apariencia" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-slate-100">
              <i class="ri-palette-line"></i> Apariencia
            </a>
          @endif
          <div class="border-t border-slate-100 my-1"></div>
          <a href="logout" class="flex items-center gap-2 px-3 py-2 text-sm rounded-lg hover:bg-red-50 text-red-600">
            <i class="ri-logout-box-r-line"></i> Cerrar sesión
          </a>
        </div>
      </div>
    </div>
  </div>
</header>
