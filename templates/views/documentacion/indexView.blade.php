@extends('includes.admin.layout')
@section('title', 'Documentación')
@section('page_title', 'Documentación del sistema')

@push('head')
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
<style>[x-cloak] { display: none !important; }</style>
@endpush

@section('content')
<div class="space-y-5" x-data="{ active: '{{ $active }}' }" x-cloak>

  {{-- Hero --}}
  <div class="bg-gradient-to-br from-violet-600 via-purple-600 to-fuchsia-600 rounded-xl p-5 text-white relative overflow-hidden">
    <i class="ri-book-3-fill" style="position:absolute;right:24px;top:50%;transform:translateY(-50%);font-size:120px;opacity:.15;"></i>
    <div class="relative z-10">
      <div class="text-xs uppercase tracking-widest opacity-80 font-semibold">Centro de documentación</div>
      <h2 class="text-xl font-bold mt-0.5">Manuales y guías de los plugins</h2>
      <p class="text-xs opacity-90 mt-1">Solo se muestran los plugins habilitados ({{ count($plugins) }}). Para ver más, andá a <a href="admin/plugins" class="underline">/admin/plugins</a>.</p>
    </div>
  </div>

  @if(empty($plugins))
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-500">
      <i class="ri-book-line text-5xl block mb-2"></i>
      No hay plugins habilitados todavía. Activá alguno desde <a href="admin/plugins" class="text-primary hover:underline">/admin/plugins</a>.
    </div>
  @else
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

      {{-- Sidebar de tabs --}}
      <aside class="lg:col-span-3">
        <div class="bg-white rounded-xl border border-slate-200 p-2 sticky top-4">
          <div class="px-3 py-2 text-xs font-semibold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
            <i class="ri-plug-fill text-primary"></i> Plugins activos
          </div>
          <nav class="space-y-1">
            @foreach($plugins as $name => $p)
              @php $hasDocs = !empty($p['sources']); @endphp
              <button type="button" @click="active = '{{ $name }}'; window.history.replaceState(null,'','documentacion?p={{ urlencode($name) }}')"
                      :class="active === '{{ $name }}' ? 'bg-primary/10 text-primary border-primary/20' : 'border-transparent hover:bg-slate-50 text-slate-700'"
                      class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium border transition text-left">
                <i class="ri-book-mark-line {{ $hasDocs ? 'opacity-100' : 'opacity-40' }}"></i>
                <span class="flex-1 truncate">{{ $name }}</span>
                @if($hasDocs)
                  <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-100 text-slate-600">{{ count($p['sources']) }}</span>
                @else
                  <i class="ri-question-line text-slate-300 text-xs" title="Sin documentación"></i>
                @endif
              </button>
            @endforeach
          </nav>
          <div class="mt-3 pt-3 border-t border-slate-100 px-2 text-[10px] text-slate-400">
            Tip: cada plugin puede aportar docs en <code>plugins/&lt;Plugin&gt;/docs/manual.md</code> o vía hook <code>plugin_documentation</code>.
          </div>
        </div>
      </aside>

      {{-- Contenido --}}
      <main class="lg:col-span-9 space-y-4">
        @foreach($plugins as $name => $p)
          <div x-show="active === '{{ $name }}'" x-cloak class="space-y-4">
            {{-- Cabecera del plugin --}}
            <div class="bg-white rounded-xl border border-slate-200 p-5">
              <div class="flex items-start justify-between flex-wrap gap-3">
                <div>
                  <div class="text-xs uppercase tracking-wider text-slate-500">Plugin</div>
                  <h2 class="text-2xl font-bold text-slate-800">{{ $name }} <span class="text-slate-400 text-base font-mono">v{{ $p['version'] }}</span></h2>
                  @if($p['description'])<p class="text-sm text-slate-600 mt-1">{{ $p['description'] }}</p>@endif
                </div>
                @if($p['author'])
                  <div class="text-right text-xs text-slate-500">
                    <div class="uppercase tracking-wider">Autor</div>
                    <div class="font-medium">{{ $p['author'] }}</div>
                  </div>
                @endif
              </div>
            </div>

            {{-- Sources --}}
            @if(empty($p['sources']))
              <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-amber-900 text-sm flex items-start gap-3">
                <i class="ri-information-line text-xl mt-0.5"></i>
                <div>
                  <strong>Este plugin todavía no tiene documentación.</strong>
                  <p class="mt-1 text-xs opacity-90">Para agregarla, creá un archivo <code>plugins/{{ $name }}/docs/manual.md</code> con tu manual en Markdown, o registrá el hook <code>plugin_documentation</code> desde el <code>Init.php</code> del plugin.</p>
                </div>
              </div>
            @else
              @foreach($p['sources'] as $src)
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
                  <div class="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center justify-between gap-2 flex-wrap">
                    <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                      <i class="{{ isset($src['url']) ? 'ri-external-link-line' : 'ri-file-text-line' }} text-primary"></i>
                      {{ $src['title'] }}
                    </h3>
                    @if(!empty($src['source_path']))
                      <code class="text-[10px] text-slate-400 font-mono">{{ $src['source_path'] }}</code>
                    @endif
                  </div>
                  <div class="p-5">
                    @if(isset($src['url']))
                      <a href="{{ $src['url'] }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg btn-primary text-sm font-semibold">
                        <i class="ri-external-link-line"></i> Abrir manual completo
                      </a>
                    @elseif(!empty($src['content_html']))
                      <article class="max-w-none">{!! $src['content_html'] !!}</article>
                    @endif
                  </div>
                </div>
              @endforeach
            @endif
          </div>
        @endforeach
      </main>
    </div>
  @endif
</div>
@endsection
