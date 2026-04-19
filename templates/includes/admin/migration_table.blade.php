@if(empty($items))
  <div class="p-8 text-center text-sm text-slate-500">
    No hay migraciones en este target.
  </div>
@else
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
        <tr>
          <th class="text-left px-5 py-2.5 font-semibold">Migración</th>
          <th class="text-center px-5 py-2.5 font-semibold">Estado</th>
          <th class="text-center px-5 py-2.5 font-semibold">Batch</th>
          <th class="text-right px-5 py-2.5 font-semibold">Ejecutada</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @foreach($items as $m)
          <tr class="hover:bg-slate-50/60">
            <td class="px-5 py-2.5 font-mono text-xs text-slate-700 break-all">
              {{ $m['name'] }}
            </td>
            <td class="px-5 py-2.5 text-center">
              @if($m['status'] === 'ran')
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-medium">
                  <i class="ri-check-line"></i> Ejecutada
                </span>
              @elseif($m['status'] === 'pending')
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-medium">
                  <i class="ri-time-line"></i> Pendiente
                </span>
              @elseif($m['status'] === 'missing')
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-red-50 text-red-700 text-xs font-medium" title="Registrada como ejecutada pero el archivo fue borrado del disco">
                  <i class="ri-error-warning-line"></i> Huérfana
                </span>
              @endif
            </td>
            <td class="px-5 py-2.5 text-center text-xs text-slate-500">
              {{ $m['batch'] !== null ? '#' . $m['batch'] : '—' }}
            </td>
            <td class="px-5 py-2.5 text-right text-xs text-slate-500">
              {{ $m['executed_at'] ? date('Y-m-d H:i', strtotime($m['executed_at'])) : '—' }}
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif
