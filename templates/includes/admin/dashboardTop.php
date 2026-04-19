<?php
require_once INCLUDES . 'admin/header.php';

$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$isActive = function($needle) use ($currentUri) {
  return strpos($currentUri, $needle) !== false
    ? 'bg-honey-400/20 text-honey-700 font-semibold border-l-4 border-honey-500'
    : 'text-slate-600 hover:bg-slate-100 border-l-4 border-transparent';
};
$user = Auth::$auth ?? null;
$username = is_object($user) ? ($user->username ?? 'quetzal') : 'quetzal';
?>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen">
<script>
  document.addEventListener('alpine:init', ()=>{});
</script>

<div class="flex min-h-screen">

  <!-- SIDEBAR -->
  <aside id="quetzal-sidebar" class="w-64 bg-white border-r border-slate-200 flex-shrink-0 hidden md:flex flex-col fixed md:static inset-y-0 left-0 z-30">
    <div class="h-16 flex items-center justify-center border-b border-slate-200 px-4">
      <a href="<?php echo get_base_url(); ?>" class="flex items-center gap-2">
        <img src="<?php echo IMAGES; ?>quetzal.svg" alt="" class="w-8 h-8">
        <span class="font-bold text-honey-600 truncate"><?php echo get_sitename(); ?></span>
      </a>
    </div>
    <nav class="flex-1 overflow-y-auto py-4 text-sm">
      <div class="px-4 mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Principal</div>
      <a href="admin" class="flex items-center gap-3 px-4 py-2.5 <?php echo $isActive('admin'); ?>">
        <i class="fas fa-tachometer-alt w-5 text-center"></i><span>Dashboard</span>
      </a>
      <a href="admin/perfil" class="flex items-center gap-3 px-4 py-2.5 <?php echo strpos($currentUri, 'perfil') !== false ? 'bg-honey-400/20 text-honey-700 font-semibold border-l-4 border-honey-500' : 'text-slate-600 hover:bg-slate-100 border-l-4 border-transparent'; ?>">
        <i class="fas fa-user-circle w-5 text-center"></i><span>Perfil</span>
      </a>

      <div class="px-4 mt-4 mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Gestión</div>
      <a href="admin/usuarios" class="flex items-center gap-3 px-4 py-2.5 <?php echo strpos($currentUri, 'usuarios') !== false ? 'bg-honey-400/20 text-honey-700 font-semibold border-l-4 border-honey-500' : 'text-slate-600 hover:bg-slate-100 border-l-4 border-transparent'; ?>">
        <i class="fas fa-users w-5 text-center"></i><span>Usuarios</span>
      </a>
      <a href="admin/productos" class="flex items-center gap-3 px-4 py-2.5 <?php echo strpos($currentUri, 'productos') !== false ? 'bg-honey-400/20 text-honey-700 font-semibold border-l-4 border-honey-500' : 'text-slate-600 hover:bg-slate-100 border-l-4 border-transparent'; ?>">
        <i class="fas fa-box w-5 text-center"></i><span>Productos</span>
      </a>

      <div class="px-4 mt-4 mb-2 text-xs font-semibold uppercase tracking-wider text-slate-400">Sitio</div>
      <a href="<?php echo get_base_url(); ?>" class="flex items-center gap-3 px-4 py-2.5 text-slate-600 hover:bg-slate-100 border-l-4 border-transparent">
        <i class="fas fa-home w-5 text-center"></i><span>Ver sitio</span>
      </a>
      <a href="logout" class="flex items-center gap-3 px-4 py-2.5 text-red-600 hover:bg-red-50 border-l-4 border-transparent">
        <i class="fas fa-sign-out-alt w-5 text-center"></i><span>Cerrar sesión</span>
      </a>
    </nav>
    <div class="p-4 border-t border-slate-200 text-xs text-slate-400">
      Quetzal v<?php echo defined('APP_VERSION') ? APP_VERSION : '1.0.0'; ?>
    </div>
  </aside>

  <!-- CONTENT -->
  <div class="flex-1 flex flex-col min-w-0 md:ml-0">

    <!-- TOPBAR -->
    <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-20">
      <button onclick="document.getElementById('quetzal-sidebar').classList.toggle('hidden')" class="md:hidden text-slate-600 hover:text-slate-900">
        <i class="fas fa-bars text-lg"></i>
      </button>
      <div class="flex-1"></div>
      <div class="flex items-center gap-3">
        <span class="text-sm text-slate-600 hidden sm:block">Hola, <strong class="text-slate-800"><?php echo htmlspecialchars($username); ?></strong></span>
        <div class="h-9 w-9 rounded-full bg-gradient-to-br from-honey-300 to-honey-500 flex items-center justify-center text-white font-semibold shadow-sm">
          <?php echo strtoupper(substr($username, 0, 1)); ?>
        </div>
        <a href="logout" class="text-sm text-slate-500 hover:text-red-600 transition" title="Cerrar sesión">
          <i class="fas fa-sign-out-alt"></i>
        </a>
      </div>
    </header>

    <!-- PAGE -->
    <main class="flex-1 p-4 sm:p-6 lg:p-8">
      <div class="max-w-7xl mx-auto">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-6 gap-3">
          <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-800"><?php echo $d->title ?? 'Dashboard'; ?></h1>
            <p class="text-sm text-slate-500 mt-0.5">Panel de administración</p>
          </div>
          <?php if (!empty($d->buttons ?? null) && is_array($d->buttons)): ?>
          <div class="flex gap-2 flex-wrap">
            <?php foreach ($d->buttons as $b): ?>
              <a href="<?php echo $b['url'] ?? '#'; ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-honey-400 hover:bg-honey-500 text-slate-900 font-medium text-sm transition">
                <?php if (!empty($b['icon'])): ?><i class="<?php echo $b['icon']; ?>"></i><?php endif; ?>
                <?php echo $b['text'] ?? ''; ?>
              </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <?php echo Flasher::flash(); ?>
