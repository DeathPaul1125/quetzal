@extends('includes.admin.layout')

@section('title', 'Apariencia')
@section('page_title', 'Apariencia del sistema')

@section('content')
<div class="max-w-4xl space-y-6">

  <div class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
      <div>
        <h2 class="text-lg font-semibold">Colores del tema</h2>
        <p class="text-sm text-slate-500">Los cambios se guardan en la tabla <code class="bg-slate-100 px-1 py-0.5 rounded text-xs">options</code> y se aplican vía variables CSS a todo el panel.</p>
      </div>
      <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-medium self-start">
        <i class="ri-shield-star-line"></i> Requiere permiso <code>admin-access</code>
      </div>
    </div>

    <form method="post" action="admin/post_apariencia" class="space-y-8" id="theme-form">
      @csrf

      {{-- Presets rápidos --}}
      <div>
        <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-500 mb-3">Presets</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-2">
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
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
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
      <div class="border-t border-slate-100 pt-6">
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

      <div class="flex justify-between items-center pt-2">
        <button type="button" id="reset-defaults" class="text-sm text-slate-500 hover:text-red-600 inline-flex items-center gap-1">
          <i class="ri-refresh-line"></i> Restaurar por defecto
        </button>
        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
          <i class="ri-save-line"></i> Guardar cambios
        </button>
      </div>
    </form>
  </div>
</div>

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
