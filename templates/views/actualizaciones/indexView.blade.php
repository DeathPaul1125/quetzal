@extends('includes.admin.layout')

@section('title', 'Actualizaciones')
@section('page_title', 'Actualizaciones del sistema')

@php
  $av = $available ?? null;
  $hayUpdate = is_array($av) && !empty($av['version']);
@endphp

@section('content')
<div class="space-y-4">

  {{-- Banner de estado --}}
  @if($hayUpdate)
    <div class="bg-gradient-to-r from-emerald-50 to-emerald-100 border border-emerald-300 rounded-xl p-5">
      <div class="flex items-start gap-4">
        <div class="bg-emerald-500 text-white rounded-full p-3 flex-shrink-0">
          <i class="ri-download-cloud-2-fill text-2xl"></i>
        </div>
        <div class="flex-1 min-w-0">
          <h2 class="text-lg font-semibold text-emerald-900">
            Hay una nueva versión disponible: <span class="font-mono">{{ $av['version'] }}</span>
          </h2>
          <p class="text-sm text-emerald-800 mt-1">
            Estás corriendo la versión <span class="font-mono font-semibold">{{ $current['version'] }}</span>.
            @if(!empty($av['released_at']))
              Publicada el {{ $av['released_at'] }}.
            @endif
          </p>
          @if(!empty($av['changelog']))
            <details class="mt-3">
              <summary class="cursor-pointer text-sm font-semibold text-emerald-900 hover:text-emerald-700">Ver changelog</summary>
              <pre class="mt-2 p-3 bg-white/70 border border-emerald-200 rounded-lg text-xs text-slate-700 whitespace-pre-wrap">{{ $av['changelog'] }}</pre>
            </details>
          @endif
          <form method="post" action="actualizaciones/post_apply" class="mt-4 inline-block"
                onsubmit="return confirm('Esto descargará y aplicará la actualización a {{ $av['version'] }}. Se creará un backup automático antes. ¿Continuar?');">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow">
              <i class="ri-download-cloud-2-line"></i>
              Actualizar ahora a {{ $av['version'] }}
            </button>
          </form>
        </div>
      </div>
    </div>
  @else
    <div class="bg-white border border-slate-200 rounded-xl p-5">
      <div class="flex items-start gap-4">
        <div class="bg-slate-100 text-slate-500 rounded-full p-3 flex-shrink-0">
          <i class="ri-shield-check-line text-2xl"></i>
        </div>
        <div class="flex-1">
          <h2 class="text-lg font-semibold text-slate-800">
            Sistema al día
          </h2>
          <p class="text-sm text-slate-600 mt-1">
            Estás corriendo la versión <span class="font-mono font-semibold">{{ $current['version'] }}</span>.
            @if(!empty($current['released_at']))
              <span class="text-slate-400">— Publicada el {{ $current['released_at'] }}</span>
            @endif
          </p>
          @if(!empty($checkError))
            <p class="text-xs text-red-600 mt-2"><i class="ri-error-warning-line"></i> {{ $checkError }}</p>
          @endif
          <form method="post" action="actualizaciones/post_check" class="mt-3 inline-block">
            @csrf
            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 text-xs font-medium">
              <i class="ri-refresh-line"></i> Buscar actualización ahora
            </button>
          </form>
        </div>
      </div>
    </div>
  @endif

  {{-- Configuración del servidor --}}
  <div class="bg-white border border-slate-200 rounded-xl">
    <div class="px-5 py-3 border-b border-slate-100 flex items-center gap-2">
      <i class="ri-settings-3-line text-slate-500"></i>
      <h3 class="font-semibold text-slate-800 text-sm">Servidor de actualizaciones</h3>
      @if(!$tokenSet)
        <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-medium">
          <i class="ri-alert-line"></i> Sin token configurado
        </span>
      @else
        <span class="ml-auto text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-medium">
          <i class="ri-check-line"></i> Token configurado
        </span>
      @endif
    </div>
    <form method="post" action="actualizaciones/post_settings" class="p-5 space-y-3">
      @csrf
      <div>
        <label class="text-xs font-semibold uppercase text-slate-500 block mb-1">URL del servidor</label>
        <input type="url" name="endpoint" value="{{ $endpoint }}" required
               class="w-full px-3 py-2 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono"
               placeholder="https://system.facturagt.com/quetzal-updates">
        <p class="text-xs text-slate-400 mt-1">Endpoint base. El cliente consulta <code>{endpoint}/check</code> y descarga desde <code>{endpoint}/download/{version}</code>.</p>
      </div>
      <div>
        <label class="text-xs font-semibold uppercase text-slate-500 block mb-1">
          Token de cliente
          @if($tokenSet)<span class="text-emerald-600 normal-case font-normal">(ya configurado — escribí uno nuevo solo si querés reemplazarlo)</span>@endif
        </label>
        <input type="password" name="token" autocomplete="new-password"
               class="w-full px-3 py-2 rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono"
               placeholder="{{ $tokenSet ? '••••••••••••••••' : 'Pega aquí el token que te asignó el admin del servidor' }}">
        <p class="text-xs text-slate-400 mt-1">Se envía como header <code>X-Update-Token</code>. Solicítalo al administrador de <a href="{{ $endpoint }}" target="_blank" class="text-primary hover:underline">{{ $endpoint }}</a>.</p>
      </div>
      <div class="flex items-center gap-2">
        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg btn-primary text-sm font-semibold">
          <i class="ri-save-line"></i> Guardar configuración
        </button>
      </div>
    </form>
  </div>

  {{-- Backups --}}
  <div class="bg-white border border-slate-200 rounded-xl">
    <div class="px-5 py-3 border-b border-slate-100 flex items-center gap-2">
      <i class="ri-archive-line text-slate-500"></i>
      <h3 class="font-semibold text-slate-800 text-sm">Backups recientes</h3>
      <span class="ml-auto text-xs text-slate-400">Se generan automáticamente antes de cada update.</span>
    </div>

    @if(empty($backups))
      <div class="p-8 text-center text-sm text-slate-500">
        <i class="ri-folder-open-line text-3xl text-slate-300 block mb-2"></i>
        Aún no hay backups. El primero se creará al aplicar la próxima actualización.
      </div>
    @else
      <div class="divide-y divide-slate-100">
        @foreach($backups as $b)
          <div class="px-5 py-3 flex items-center gap-3 hover:bg-slate-50">
            <i class="ri-file-zip-line text-slate-400 text-lg"></i>
            <div class="flex-1 min-w-0">
              <div class="font-mono text-sm text-slate-700 truncate">{{ $b['name'] }}</div>
              <div class="text-xs text-slate-400">{{ $b['date'] }} · {{ number_format($b['size'] / 1024 / 1024, 2) }} MB</div>
            </div>
            <form method="post" action="actualizaciones/post_rollback" class="inline"
                  onsubmit="return confirm('Esto restaurará el sistema desde {{ $b['name'] }}, sobreescribiendo los archivos actuales. ¿Continuar?');">
              @csrf
              <input type="hidden" name="backup" value="{{ $b['name'] }}">
              <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border border-amber-200 text-amber-700 hover:bg-amber-50 font-medium inline-flex items-center gap-1">
                <i class="ri-arrow-go-back-line"></i> Restaurar
              </button>
            </form>
          </div>
        @endforeach
      </div>
    @endif
  </div>

  {{-- Info técnica --}}
  <details class="bg-slate-50 border border-slate-200 rounded-xl">
    <summary class="cursor-pointer px-5 py-3 text-xs font-semibold uppercase tracking-wider text-slate-600">
      <i class="ri-information-line"></i> Detalles técnicos
    </summary>
    <div class="px-5 pb-4 text-xs text-slate-600 space-y-1">
      <div><strong>Versión instalada:</strong> <span class="font-mono">{{ $current['version'] }}</span></div>
      <div><strong>Canal:</strong> <span class="font-mono">{{ $current['channel'] }}</span></div>
      @if(!empty($current['min_php']))
        <div><strong>PHP mínimo declarado:</strong> <span class="font-mono">{{ $current['min_php'] }}</span></div>
      @endif
      <div><strong>PHP en este server:</strong> <span class="font-mono">{{ phpversion() }}</span></div>
      <div class="pt-2 border-t border-slate-200 mt-2">
        <strong>Lo que NO se actualiza (siempre se preserva):</strong>
        <ul class="list-disc ml-5 mt-1 text-slate-500">
          <li><code>plugins/</code> y todo su contenido</li>
          <li><code>app/config/.env</code> (credenciales)</li>
          <li><code>app/config/sidebar.json</code> y <code>plugins.json</code></li>
          <li><code>assets/uploads/</code> (archivos subidos por usuarios)</li>
          <li><code>app/cache/</code> y <code>app/logs/</code></li>
        </ul>
      </div>
    </div>
  </details>

</div>
@endsection
