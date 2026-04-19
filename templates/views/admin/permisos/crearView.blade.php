@extends('includes.admin.layout')

@section('title', 'Nuevo permiso')
@section('page_title', 'Nuevo permiso')

@section('content')
<div class="max-w-2xl space-y-4">

  <div>
    <a href="admin/permisos" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al listado
    </a>
  </div>

  <form method="post" action="admin/post_permiso" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 space-y-6">
    @csrf

    <div class="flex items-start gap-4 border-b border-slate-100 pb-5">
      <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
        <i class="ri-key-2-line text-primary text-xl"></i>
      </div>
      <div>
        <h3 class="text-lg font-semibold text-slate-800">Nuevo permiso</h3>
        <p class="text-sm text-slate-500 mt-0.5">Se asignará a roles desde el editor de roles.</p>
      </div>
    </div>

    <div class="space-y-5">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nombre <span class="text-red-500">*</span></label>
        <input type="text" name="nombre" required minlength="3" maxlength="100"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
               placeholder="Ej. Exportar reportes">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Slug <span class="text-red-500">*</span></label>
        <input type="text" name="slug" required pattern="^[a-z0-9-]{3,50}$"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono"
               placeholder="ej. export-reports">
        <p class="text-xs text-slate-500 mt-1">
          Solo minúsculas, números y guiones. Es la clave que usas en <code class="bg-slate-100 px-1 rounded">user_can('slug')</code> y <strong>no podrás cambiarla después</strong>.
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Descripción</label>
        <textarea name="descripcion" rows="3"
                  class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
                  placeholder="¿Qué habilita este permiso?"></textarea>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="admin/permisos" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Cancelar</a>
      <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
        <i class="ri-add-line"></i> Crear permiso
      </button>
    </div>
  </form>
</div>
@endsection
