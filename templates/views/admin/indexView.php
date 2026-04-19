<?php require_once INCLUDES . 'admin/dashboardTop.php'; ?>

<!-- Welcome banner -->
<div class="mb-6 rounded-2xl bg-gradient-to-r from-honey-400 to-honey-500 text-slate-900 p-6 shadow-lg shadow-honey-200/50">
  <div class="flex items-center gap-4">
    <img src="<?php echo IMAGES; ?>quetzal.svg" alt="" class="w-16 h-16 flex-shrink-0">
    <div>
      <h2 class="text-xl font-bold">¡Bienvenido a Quetzal!</h2>
      <p class="text-sm text-slate-800/80 mt-1">Tu instalación está lista. Este es tu panel de administración con Tailwind CSS.</p>
    </div>
  </div>
</div>

<!-- Stat cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <?php
  $stats = [
    ['label' => 'Ingresos (mes)',  'value' => '$40,000', 'icon' => 'fa-calendar',       'color' => 'from-honey-400 to-honey-500'],
    ['label' => 'Ingresos (año)',  'value' => '$215,000','icon' => 'fa-dollar-sign',    'color' => 'from-emerald-400 to-emerald-500'],
    ['label' => 'Tareas',          'value' => '50%',     'icon' => 'fa-clipboard-list', 'color' => 'from-sky-400 to-sky-500'],
    ['label' => 'Pendientes',      'value' => '18',      'icon' => 'fa-comments',       'color' => 'from-rose-400 to-rose-500'],
  ];
  foreach ($stats as $s): ?>
    <div class="bg-white rounded-xl p-5 shadow-sm ring-1 ring-slate-200/50 hover:shadow-md transition">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs font-semibold uppercase tracking-wider text-slate-500"><?php echo $s['label']; ?></p>
          <p class="text-2xl font-bold text-slate-800 mt-1"><?php echo $s['value']; ?></p>
        </div>
        <div class="h-12 w-12 rounded-xl bg-gradient-to-br <?php echo $s['color']; ?> flex items-center justify-center text-white shadow-md">
          <i class="fas <?php echo $s['icon']; ?> text-lg"></i>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- Activity / projects -->
  <div class="lg:col-span-2 bg-white rounded-xl shadow-sm ring-1 ring-slate-200/50 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-slate-800">Proyectos</h3>
      <span class="text-xs text-slate-500">Estado actual</span>
    </div>
    <div class="space-y-4">
      <?php
      $projects = [
        ['name' => 'Server Migration',   'pct' => 20, 'bar' => 'bg-rose-500'],
        ['name' => 'Sales Tracking',     'pct' => 40, 'bar' => 'bg-amber-500'],
        ['name' => 'Customer Database',  'pct' => 60, 'bar' => 'bg-sky-500'],
        ['name' => 'Payout Details',     'pct' => 80, 'bar' => 'bg-indigo-500'],
        ['name' => 'Account Setup',      'pct' => 100,'bar' => 'bg-emerald-500'],
      ];
      foreach ($projects as $p): ?>
        <div>
          <div class="flex justify-between text-sm mb-1">
            <span class="font-medium text-slate-700"><?php echo $p['name']; ?></span>
            <span class="text-slate-500"><?php echo $p['pct']; ?>%</span>
          </div>
          <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full <?php echo $p['bar']; ?> rounded-full transition-all" style="width: <?php echo $p['pct']; ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Quick info -->
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200/50 p-6">
    <h3 class="font-bold text-slate-800 mb-4">Atajos rápidos</h3>
    <div class="space-y-2">
      <a href="admin/usuarios" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition group">
        <div class="h-10 w-10 rounded-lg bg-sky-100 text-sky-600 flex items-center justify-center group-hover:bg-sky-500 group-hover:text-white transition"><i class="fas fa-users"></i></div>
        <div class="text-sm">
          <div class="font-medium text-slate-700">Gestionar usuarios</div>
          <div class="text-xs text-slate-500">Ver y editar usuarios</div>
        </div>
      </a>
      <a href="admin/productos" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition group">
        <div class="h-10 w-10 rounded-lg bg-honey-100 text-honey-700 flex items-center justify-center group-hover:bg-honey-500 group-hover:text-white transition"><i class="fas fa-box"></i></div>
        <div class="text-sm">
          <div class="font-medium text-slate-700">Productos</div>
          <div class="text-xs text-slate-500">Inventario y catálogo</div>
        </div>
      </a>
      <a href="admin/perfil" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition group">
        <div class="h-10 w-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center group-hover:bg-emerald-500 group-hover:text-white transition"><i class="fas fa-user-cog"></i></div>
        <div class="text-sm">
          <div class="font-medium text-slate-700">Mi perfil</div>
          <div class="text-xs text-slate-500">Preferencias de cuenta</div>
        </div>
      </a>
      <a href="<?php echo get_base_url(); ?>" class="flex items-center gap-3 p-3 rounded-lg hover:bg-slate-50 transition group">
        <div class="h-10 w-10 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center group-hover:bg-slate-600 group-hover:text-white transition"><i class="fas fa-external-link-alt"></i></div>
        <div class="text-sm">
          <div class="font-medium text-slate-700">Ver sitio público</div>
          <div class="text-xs text-slate-500">Abrir en nueva pestaña</div>
        </div>
      </a>
    </div>
  </div>
</div>

<!-- Info cards -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200/50 p-6">
    <h3 class="font-bold text-slate-800 mb-2">Documentación</h3>
    <p class="text-sm text-slate-600 mb-4">Esta plantilla usa Tailwind CSS desde CDN. Puedes personalizar los colores, iconos y layout editando los archivos en <code class="text-xs bg-slate-100 px-1 py-0.5 rounded">templates/includes/admin/</code>.</p>
    <a href="https://tailwindcss.com/docs" target="_blank" class="text-sm text-honey-600 font-medium hover:underline">Abrir docs de Tailwind →</a>
  </div>
  <div class="bg-white rounded-xl shadow-sm ring-1 ring-slate-200/50 p-6">
    <h3 class="font-bold text-slate-800 mb-2">Acciones rápidas</h3>
    <p class="text-sm text-slate-600 mb-4">Administra tu sitio desde los accesos directos del panel lateral.</p>
    <div class="flex gap-2 flex-wrap">
      <a href="admin/usuarios" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-xs font-medium transition"><i class="fas fa-users"></i> Usuarios</a>
      <a href="admin/productos" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-honey-500 hover:bg-honey-600 text-white text-xs font-medium transition"><i class="fas fa-box"></i> Productos</a>
    </div>
  </div>
</div>

<?php require_once INCLUDES . 'admin/dashboardBottom.php'; ?>
