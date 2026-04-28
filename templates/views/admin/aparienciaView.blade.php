@extends('includes.admin.layout')

@section('title', 'Apariencia')
@section('page_title', 'Apariencia del sistema')

@push('head')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
@endpush

@section('content')
<div class="max-w-5xl space-y-6" x-data="{ tab: 'identidad' }">

  {{-- Hero header --}}
  <div class="bg-gradient-to-br from-primary/10 via-white to-sky-50 rounded-xl border border-primary/20 p-5 sm:p-6 relative overflow-hidden">
    <i class="ri-paint-brush-fill" style="position:absolute;right:-20px;bottom:-20px;font-size:140px;color:var(--q-primary);opacity:.1;"></i>
    <div class="flex items-start gap-4 relative z-10">
      <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
        <i class="ri-palette-fill text-2xl"></i>
      </div>
      <div class="flex-1">
        <h2 class="text-lg font-bold text-slate-800">Apariencia del sistema</h2>
        <p class="text-sm text-slate-600 mt-0.5">Personalizá identidad visual, colores, logos y assets de marca. Los cambios se aplican inmediatamente.</p>
      </div>
      <div class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-medium self-start">
        <i class="ri-shield-star-line"></i> Requiere <code>admin-access</code>
      </div>
    </div>
  </div>

  {{-- Tabs --}}
  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="border-b border-slate-100 px-2 sm:px-4 flex flex-wrap gap-1 text-sm">
      @php
        $tabs = [
          ['id' => 'identidad',   'label' => 'Identidad',     'icon' => 'ri-quill-pen-line'],
          ['id' => 'branding',    'label' => 'Logos & marca', 'icon' => 'ri-image-line'],
          ['id' => 'colores',     'label' => 'Colores',       'icon' => 'ri-palette-line'],
          ['id' => 'login',       'label' => 'Login',         'icon' => 'ri-login-box-line'],
          ['id' => 'mobile',      'label' => 'App móvil',     'icon' => 'ri-smartphone-line'],
        ];
      @endphp
      @foreach($tabs as $t)
        <button type="button" @click="tab='{{ $t['id'] }}'"
                :class="tab === '{{ $t['id'] }}' ? 'text-primary border-primary' : 'text-slate-500 border-transparent hover:text-slate-700'"
                class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-3 border-b-2 font-medium transition">
          <i class="{{ $t['icon'] }}"></i> {{ $t['label'] }}
        </button>
      @endforeach
    </div>

    <form method="post" action="admin/post_apariencia" enctype="multipart/form-data" class="p-5 sm:p-6 space-y-6" id="theme-form">
      @csrf

      {{-- ============ TAB IDENTIDAD ============ --}}
      <div x-show="tab === 'identidad'" x-cloak class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Nombre del sitio</label>
          <input type="text" name="site_name" maxlength="100" value="{{ $branding['site_name'] }}"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
          <p class="text-xs text-slate-500 mt-1">Aparece en el título del navegador y en el footer.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Tagline</label>
          <input type="text" name="tagline" maxlength="200" value="{{ $branding['tagline'] }}"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
          <p class="text-xs text-slate-500 mt-1">Frase corta que describe tu negocio o sistema.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Texto del footer</label>
          <input type="text" name="footer_text" maxlength="300" value="{{ $branding['footer_text'] }}"
                 placeholder="(opcional — si vacío usa © {{ date('Y') }} {{ $branding['site_name'] }})"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
          <p class="text-xs text-slate-500 mt-1">Aparece al final de cada página del panel.</p>
        </div>
      </div>

      {{-- ============ TAB BRANDING (logos & favicon) ============ --}}
      <div x-show="tab === 'branding'" x-cloak class="space-y-6">
        @php
          $assets = [
            ['key' => 'logo',     'label' => 'Logo',                 'help' => 'Aparece en el sidebar y la página de login. PNG/SVG recomendado, máx. 2MB. Idealmente fondo transparente.', 'accept' => '.png,.jpg,.jpeg,.svg,.webp', 'preview_class' => 'h-16 max-w-[180px] object-contain bg-slate-50 p-2 rounded-lg border border-slate-200'],
            ['key' => 'favicon',  'label' => 'Favicon',              'help' => 'Icono de pestaña del navegador. ICO/PNG/SVG, máx. 512KB. Tamaño ideal 32×32 o 64×64.',  'accept' => '.ico,.png,.svg', 'preview_class' => 'h-12 w-12 object-contain bg-slate-50 p-1 rounded border border-slate-200'],
            ['key' => 'login_bg', 'label' => 'Imagen de login',      'help' => 'Imagen lateral en la pantalla de login. JPG/PNG/WEBP, máx. 4MB. Recomendado mínimo 1200×800.', 'accept' => '.jpg,.jpeg,.png,.webp', 'preview_class' => 'h-32 max-w-[260px] object-cover rounded-lg border border-slate-200'],
          ];
        @endphp

        @foreach($assets as $a)
          <div class="rounded-lg border border-slate-200 p-4 bg-slate-50/50">
            <div class="flex flex-col sm:flex-row sm:items-start gap-4">
              <div class="flex-shrink-0">
                @if(!empty($branding[$a['key']]))
                  <img src="{{ branding_asset_url($branding[$a['key']]) }}" alt="{{ $a['label'] }}" class="{{ $a['preview_class'] }}">
                @else
                  <div class="{{ $a['preview_class'] }} flex items-center justify-center text-slate-300">
                    <i class="ri-image-add-line text-2xl"></i>
                  </div>
                @endif
              </div>
              <div class="flex-1 min-w-0">
                <label class="block text-sm font-semibold text-slate-700 mb-1">{{ $a['label'] }}</label>
                <p class="text-xs text-slate-500 mb-2">{{ $a['help'] }}</p>
                <div class="flex items-center gap-2 flex-wrap">
                  <input type="file" name="{{ $a['key'] }}" accept="{{ $a['accept'] }}"
                         class="text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-primary file:text-white file:font-semibold file:px-3 file:py-1.5 hover:file:opacity-90">
                  @if(!empty($branding[$a['key']]))
                    <label class="inline-flex items-center gap-1.5 text-xs text-red-600 cursor-pointer hover:underline">
                      <input type="checkbox" name="remove_{{ $a['key'] }}" value="1" class="rounded text-red-600">
                      Quitar al guardar
                    </label>
                  @endif
                </div>
                @if(!empty($branding[$a['key']]))
                  <p class="text-xs text-slate-400 mt-1 font-mono truncate">{{ $branding[$a['key']] }}</p>
                @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>

      {{-- ============ TAB COLORES ============ --}}
      <div x-show="tab === 'colores'" x-cloak class="space-y-6">
        {{-- Presets rápidos --}}
        <div>
          <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Presets</h3>
          <div class="grid grid-cols-4 sm:grid-cols-8 gap-2">
            @foreach($presets as $p)
              <button type="button"
                      class="preset-btn group relative aspect-square rounded-lg ring-1 ring-slate-200 hover:ring-2 hover:ring-offset-1 transition"
                      style="background: {{ $p['primary'] }}"
                      data-primary="{{ $p['primary'] }}"
                      data-dark="{{ $p['dark'] }}"
                      title="{{ $p['label'] }}">
                <span class="sr-only">{{ $p['label'] }}</span>
                @if($p['primary'] === $colors['primary'])
                  <span class="absolute inset-0 flex items-center justify-center text-white text-xl">
                    <i class="ri-check-line"></i>
                  </span>
                @endif
              </button>
            @endforeach
          </div>
        </div>

        {{-- Inputs manuales --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
          @php
            $fields = [
              ['key' => 'primary',      'label' => 'Color primario',          'help' => 'Botones, enlaces, acentos.'],
              ['key' => 'primary_dark', 'label' => 'Primario oscuro (hover)', 'help' => 'Estado hover del color primario.'],
              ['key' => 'sidebar_bg',   'label' => 'Fondo del sidebar',       'help' => 'Color de fondo del panel lateral.'],
              ['key' => 'sidebar_fg',   'label' => 'Texto del sidebar',       'help' => 'Color del texto del panel lateral.'],
            ];
          @endphp
          @foreach($fields as $f)
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-2">{{ $f['label'] }}</label>
              <div class="flex items-center gap-3">
                <input type="color" name="{{ $f['key'] }}" value="{{ $colors[$f['key']] }}"
                       class="w-14 h-10 rounded-lg border border-slate-200 cursor-pointer bg-white"
                       data-bind-hex="{{ $f['key'] }}-hex">
                <input type="text" id="{{ $f['key'] }}-hex" value="{{ $colors[$f['key']] }}"
                       class="flex-1 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono uppercase"
                       pattern="^#[0-9a-fA-F]{3,8}$" maxlength="9">
              </div>
              <p class="text-xs text-slate-500 mt-1">{{ $f['help'] }}</p>
            </div>
          @endforeach
        </div>

        {{-- Preview --}}
        <div class="border-t border-slate-100 pt-5">
          <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Vista previa</h3>
          <div class="rounded-lg border border-slate-200 p-5 flex flex-wrap items-center gap-3">
            <button type="button" class="btn-primary px-4 py-2 rounded-lg text-sm font-medium">Botón primario</button>
            <a href="#" class="text-primary text-sm font-medium hover:underline">Enlace con primario</a>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary text-white text-xs font-medium">
              <i class="ri-star-line"></i> Badge
            </span>
            <span class="border-l-4 border-primary bg-slate-50 pl-3 py-1 text-sm text-slate-600">Borde con primario</span>
          </div>
        </div>

        <div>
          <button type="button" id="reset-defaults" class="text-sm text-slate-500 hover:text-red-600 inline-flex items-center gap-1">
            <i class="ri-refresh-line"></i> Restaurar colores por defecto
          </button>
        </div>
      </div>

      {{-- ============ TAB LOGIN ============ --}}
      <div x-show="tab === 'login'" x-cloak class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Mensaje de bienvenida</label>
          <input type="text" name="login_welcome" maxlength="100" value="{{ $branding['login_welcome'] }}"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
          <p class="text-xs text-slate-500 mt-1">Aparece grande en la página de login.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">Subtítulo</label>
          <input type="text" name="login_subtitle" maxlength="200" value="{{ $branding['login_subtitle'] }}"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
          <p class="text-xs text-slate-500 mt-1">Frase debajo del mensaje de bienvenida.</p>
        </div>
        <div class="rounded-lg bg-sky-50 border border-sky-200 p-3 text-xs text-sky-900">
          <i class="ri-information-line"></i>
          La <strong>imagen lateral del login</strong> se configura desde la pestaña <strong>Logos & marca</strong>.
        </div>
      </div>

      {{-- ============ TAB MOBILE / APK ============ --}}
      <div x-show="tab === 'mobile'" x-cloak class="space-y-5">
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-xs text-emerald-900 flex items-start gap-2">
          <i class="ri-android-line text-base mt-0.5"></i>
          <div>
            Habilitá un botón <strong>"Descargar APK"</strong> en el topbar del panel para que tus usuarios instalen la app móvil del sistema.
          </div>
        </div>
        <label class="flex items-center gap-2 p-3 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
          <input type="checkbox" name="apk_enabled" value="1" {{ $branding['apk_enabled'] ? 'checked' : '' }}
                 class="rounded border-slate-300 text-primary focus:ring-primary">
          <div>
            <div class="text-sm font-medium">Mostrar botón en el topbar</div>
            <div class="text-xs text-slate-500">Si está activo y la URL es válida, aparece un botón "Descargar APK" para todos los usuarios autenticados.</div>
          </div>
        </label>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5">URL del APK</label>
          <input type="text" name="apk_url" maxlength="500" value="{{ $branding['apk_url'] }}"
                 class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono">
          <p class="text-xs text-slate-500 mt-1">Path relativo desde la raíz (ej. <code>assets/downloads/mi-app.apk</code>) o URL absoluta. El default es el APK que ya viene con el sistema.</p>
        </div>
      </div>

      {{-- Submit --}}
      <div class="flex justify-end items-center pt-5 border-t border-slate-100">
        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
          <i class="ri-save-line"></i> Guardar todos los cambios
        </button>
      </div>
    </form>
  </div>
</div>

<style>
  [x-cloak] { display: none !important; }
</style>

@push('scripts')
<script>
(function() {
  const form = document.getElementById('theme-form');
  if (!form) return;

  // Sync color picker ↔ hex input
  form.querySelectorAll('input[type=color]').forEach(picker => {
    const hexId = picker.dataset.bindHex;
    const hex = document.getElementById(hexId);
    if (!hex) return;
    picker.addEventListener('input', () => { hex.value = picker.value.toUpperCase(); });
    hex.addEventListener('input', () => {
      if (/^#[0-9a-f]{6}$/i.test(hex.value)) picker.value = hex.value;
    });
  });

  // Presets
  form.querySelectorAll('.preset-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const primary = btn.dataset.primary;
      const dark    = btn.dataset.dark;
      const setPair = (key, val) => {
        const picker = form.querySelector(`input[type=color][name="${key}"]`);
        const hex    = document.getElementById(`${key}-hex`);
        if (picker) picker.value = val;
        if (hex) hex.value = val.toUpperCase();
      };
      setPair('primary', primary);
      setPair('primary_dark', dark);
    });
  });

  // Reset (valores Guatemala por defecto)
  document.getElementById('reset-defaults')?.addEventListener('click', () => {
    if (!confirm('¿Restaurar los colores por defecto?')) return;
    const defaults = { primary:'#4997D0', primary_dark:'#2D6CA3', sidebar_bg:'#0F2E5C', sidebar_fg:'#e5e7eb' };
    Object.entries(defaults).forEach(([key, val]) => {
      const picker = form.querySelector(`input[type=color][name="${key}"]`);
      const hex    = document.getElementById(`${key}-hex`);
      if (picker) picker.value = val;
      if (hex) hex.value = val.toUpperCase();
    });
  });
})();
</script>
@endpush
@endsection
