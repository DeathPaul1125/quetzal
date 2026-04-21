@extends('includes.admin.layout')

@section('title', 'Tablas de BD')
@section('page_title', 'Tablas de base de datos')

@section('content')
<div class="space-y-6">

  {{-- Info --}}
  <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 p-4 text-sm">
    <div class="flex items-start gap-2">
      <i class="ri-alert-line text-lg mt-0.5"></i>
      <div>
        <div class="font-medium">Operaciones destructivas</div>
        <p class="mt-1">
          Aquí puedes eliminar (DROP) o vaciar (TRUNCATE) tablas de la BD <code class="bg-amber-100 px-1 rounded">{{ $dbName }}</code>.
          Estas acciones <strong>NO se pueden deshacer</strong>. Asegúrate de tener un respaldo antes de ejecutarlas.
        </p>
        <p class="mt-1 text-xs text-amber-800/80">
          Las tablas del sistema están protegidas — solo se borran si marcas <strong>forzar</strong> en el diálogo de confirmación.
        </p>
      </div>
    </div>
  </div>

  {{-- Listado --}}
  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between flex-wrap gap-3">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center">
          <i class="ri-table-line text-primary text-xl"></i>
        </div>
        <div>
          <h2 class="font-semibold text-slate-800">Tablas</h2>
          <div class="text-xs text-slate-500">
            {{ count($tables) }} tabla(s) en <code class="bg-slate-100 px-1 rounded">{{ $dbName }}</code>
          </div>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <input id="q-tbl-filter" type="text" placeholder="Filtrar por nombre..."
               class="px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:border-primary focus:ring-primary">
      </div>
    </div>

    @if(empty($tables))
      <div class="p-8 text-center text-slate-500 text-sm">
        <i class="ri-database-2-line text-4xl text-slate-300 block mb-2"></i>
        No hay tablas en la base de datos.
      </div>
    @else
    <div class="overflow-x-auto">
      <table class="w-full text-sm" id="q-tables">
        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
          <tr>
            <th class="text-left px-5 py-3">Nombre</th>
            <th class="text-right px-5 py-3">Filas (aprox)</th>
            <th class="text-right px-5 py-3">Tamaño</th>
            <th class="text-left px-5 py-3">Creada</th>
            <th class="text-right px-5 py-3">Acciones</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          @foreach($tables as $t)
            <tr class="q-tbl-row hover:bg-slate-50">
              <td class="px-5 py-3 font-mono text-sm">
                <span class="q-tbl-name">{{ $t['name'] }}</span>
                @if($t['is_protected'])
                  <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs"
                        title="Tabla del sistema — borrarla puede romper la aplicación">
                    <i class="ri-shield-line"></i> sistema
                  </span>
                @endif
              </td>
              <td class="px-5 py-3 text-right text-slate-600">{{ number_format((int)($t['rows_approx'] ?? 0)) }}</td>
              <td class="px-5 py-3 text-right text-slate-600">{{ $t['size_human'] }}</td>
              <td class="px-5 py-3 text-xs text-slate-500">{{ $t['created_at'] ?? '—' }}</td>
              <td class="px-5 py-3">
                <div class="flex items-center justify-end gap-2">

                  {{-- TRUNCATE --}}
                  <form method="post" action="admin/post_truncate_table" class="inline"
                        onsubmit="return confirm('¿VACIAR la tabla &quot;{{ $t['name'] }}&quot;?\n\nEsto elimina TODOS los registros pero mantiene la estructura. Es irreversible.');">
                    @csrf
                    <input type="hidden" name="table" value="{{ $t['name'] }}">
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-amber-200 text-amber-700 hover:bg-amber-50 text-xs font-medium"
                            title="Vacía la tabla (TRUNCATE)">
                      <i class="ri-eraser-line"></i> Vaciar
                    </button>
                  </form>

                  {{-- DROP --}}
                  @if($t['is_protected'])
                    <button type="button"
                            onclick="qDropTable('{{ $t['name'] }}', true)"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs font-medium"
                            title="Eliminar esta tabla (requiere forzar)">
                      <i class="ri-delete-bin-line"></i> Eliminar
                    </button>
                  @else
                    <button type="button"
                            onclick="qDropTable('{{ $t['name'] }}', false)"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-red-200 text-red-600 hover:bg-red-50 text-xs font-medium"
                            title="Eliminar esta tabla (DROP)">
                      <i class="ri-delete-bin-line"></i> Eliminar
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @endif
  </div>

  {{-- Form oculto que se dispara desde qDropTable() --}}
  <form id="q-drop-form" method="post" action="admin/post_drop_table" class="hidden">
    @csrf
    <input type="hidden" id="q-drop-name"  name="table" value="">
    <input type="hidden" id="q-drop-force" name="force" value="">
  </form>
</div>

@push('scripts')
<script>
(function() {
  // Filtro por nombre
  const input = document.getElementById('q-tbl-filter');
  const rows  = document.querySelectorAll('#q-tables .q-tbl-row');
  if (input) {
    input.addEventListener('input', () => {
      const q = input.value.trim().toLowerCase();
      rows.forEach(r => {
        const name = (r.querySelector('.q-tbl-name')?.textContent || '').toLowerCase();
        r.style.display = name.includes(q) ? '' : 'none';
      });
    });
  }

  // Confirmación de DROP
  window.qDropTable = function(table, isProtected) {
    const msg = isProtected
      ? '⚠️  La tabla "' + table + '" es del SISTEMA.\n\n' +
        'Eliminarla puede romper el funcionamiento de Quetzal.\n\n' +
        '¿Estás COMPLETAMENTE seguro? Escribe el nombre de la tabla para confirmar:'
      : '¿ELIMINAR la tabla "' + table + '"?\n\n' +
        'Esta acción borra la tabla y todos sus datos. Es IRREVERSIBLE.\n\n' +
        'Escribe el nombre de la tabla para confirmar:';

    const answer = prompt(msg);
    if (answer === null) return;
    if (answer !== table) {
      alert('El nombre no coincide — cancelando.');
      return;
    }

    document.getElementById('q-drop-name').value  = table;
    document.getElementById('q-drop-force').value = isProtected ? '1' : '';
    document.getElementById('q-drop-form').submit();
  };
})();
</script>
@endpush
@endsection
