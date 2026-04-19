@extends('includes.admin.layout')

@section('title', 'Editar permiso')
@section('page_title', 'Editar permiso')

@section('content')
<div class="max-w-2xl space-y-4">

  <div>
    <a href="admin/ver_permiso/{{ $permiso['id'] }}" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al detalle
    </a>
  </div>

  <form method="post" action="admin/post_permiso_editar" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 space-y-6">
    @csrf
    <input type="hidden" name="id" value="{{ $permiso['id'] }}">

    <div class="flex items-start gap-4 border-b border-slate-100 pb-5">
      <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
        <i class="ri-key-2-line text-primary text-xl"></i>
      </div>
      <div>
        <h3 class="text-lg font-semibold text-slate-800">{{ $permiso['nombre'] }}</h3>
        <code class="text-xs text-slate-500 font-mono">{{ $permiso['slug'] }}</code>
      </div>
    </div>

    <div class="space-y-5">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nombre</label>
        <input type="text" name="nombre" value="{{ $permiso['nombre'] }}" required minlength="3" maxlength="100"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">
          Slug <span class="text-slate-400 font-normal">(no editable)</span>
        </label>
        <input type="text" value="{{ $permiso['slug'] }}" disabled
               class="w-full rounded-lg border-slate-200 bg-slate-50 text-slate-500 text-sm font-mono">
        <p class="text-xs text-slate-500 mt-1">
          El slug es inmutable para no romper código que lo verifique con <code class="bg-slate-100 px-1 rounded">user_can('{{ $permiso['slug'] }}')</code>.
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Descripción</label>
        <textarea name="descripcion" rows="3"
                  class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm">{{ $permiso['descripcion'] ?? '' }}</textarea>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="admin/ver_permiso/{{ $permiso['id'] }}" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Cancelar</a>
      <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
        <i class="ri-save-line"></i> Guardar
      </button>
    </div>
  </form>
</div>
@endsection
