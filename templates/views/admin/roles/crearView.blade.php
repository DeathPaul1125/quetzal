@extends('includes.admin.layout')

@section('title', 'Nuevo role')
@section('page_title', 'Nuevo role')

@section('content')
<div class="max-w-2xl space-y-4">

  <div>
    <a href="admin/roles" class="text-sm text-slate-500 hover:text-slate-800 inline-flex items-center gap-1">
      <i class="ri-arrow-left-line"></i> Volver al listado
    </a>
  </div>

  <form method="post" action="admin/post_role" class="bg-white rounded-xl border border-slate-200 p-6 sm:p-8 space-y-6">
    @csrf

    <div class="flex items-start gap-4 border-b border-slate-100 pb-5">
      <div class="w-12 h-12 rounded-lg bg-primary/10 flex items-center justify-center">
        <i class="ri-shield-user-line text-primary text-xl"></i>
      </div>
      <div>
        <h3 class="text-lg font-semibold text-slate-800">Datos del role</h3>
        <p class="text-sm text-slate-500 mt-0.5">Después de crearlo podrás asignarle permisos.</p>
      </div>
    </div>

    <div class="space-y-5">
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Nombre <span class="text-red-500">*</span></label>
        <input type="text" name="nombre" required minlength="3" maxlength="100"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm"
               placeholder="Ej. Contador">
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1.5">Slug <span class="text-red-500">*</span></label>
        <input type="text" name="slug" required pattern="^[a-z0-9-]{3,50}$"
               class="w-full rounded-lg border-slate-300 focus:border-primary focus:ring-primary text-sm font-mono"
               placeholder="ej. contador">
        <p class="text-xs text-slate-500 mt-1">Solo minúsculas, números y guiones. Se usa como identificador en código y no cambia después.</p>
      </div>
    </div>

    <div class="rounded-lg border border-amber-200 bg-amber-50 text-amber-900 p-3 text-xs">
      <i class="ri-information-line"></i> Los roles <code>admin</code>, <code>worker</code> y <code>developer</code> son del sistema y no pueden usarse como slug.
    </div>

    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
      <a href="admin/roles" class="px-4 py-2 rounded-lg text-sm text-slate-600 hover:bg-slate-100">Cancelar</a>
      <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg btn-primary font-semibold text-sm">
        <i class="ri-add-line"></i> Crear role
      </button>
    </div>
  </form>
</div>
@endsection
