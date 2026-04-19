<!DOCTYPE html>
<html lang="<?php echo get_site_lang(); ?>">
<head>
  <base href="<?php echo get_basepath(); ?>">
  <meta charset="<?php echo get_site_charset(); ?>">
  <title>Iniciar sesión - <?php echo get_sitename(); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php echo get_favicon(); ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: { extend: { colors: { honey: { 50:'#fffbeb',100:'#fef3c7',200:'#fde68a',300:'#fcd34d',400:'#fbbf24',500:'#f59e0b',600:'#d97706',700:'#b45309' } } } }
    }
  </script>
  <?php echo get_fontawesome(); ?>
  <style>
    body { background: linear-gradient(135deg,#fffbeb 0%, #ffffff 55%, #fef9c3 100%); min-height:100vh; }
    .alert { padding:.75rem 1rem; border-radius:.5rem; border:1px solid; margin-bottom:1rem; font-size:.875rem; }
    .alert-success { background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
    .alert-danger,.alert-error { background:#fef2f2; border-color:#fecaca; color:#991b1b; }
    .alert-warning { background:#fffbeb; border-color:#fde68a; color:#92400e; }
    .alert-info,.alert-primary { background:#eff6ff; border-color:#bfdbfe; color:#1e40af; }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 py-10 text-slate-800">

  <div class="w-full max-w-md">
    <div class="text-center mb-6">
      <a href="<?php echo get_base_url(); ?>" class="inline-flex items-center justify-center">
        <img src="<?php echo IMAGES; ?>quetzal.svg" alt="Quetzal" style="width:96px;height:auto">
      </a>
      <h1 class="text-2xl font-bold text-honey-600 mt-2"><?php echo get_sitename(); ?></h1>
      <p class="text-sm text-slate-500 mt-1">Inicia sesión para acceder al panel</p>
    </div>

    <div class="bg-white rounded-2xl shadow-xl shadow-honey-100/50 ring-1 ring-slate-200/60 p-6 sm:p-8">
      <h2 class="text-lg font-bold text-slate-800 mb-4">Acceso administrativo</h2>

      <?php echo Flasher::flash(); ?>

      <form action="login/post_login" method="post" novalidate class="space-y-4">
        <?php echo insert_inputs(); ?>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1" for="usuario">Usuario</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="fas fa-user text-sm"></i></span>
            <input type="text" id="usuario" name="usuario" required autofocus
                   class="w-full pl-10 pr-3 py-2 rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm"
                   placeholder="admin">
          </div>
          <?php if (is_demo() || is_local()): ?>
            <p class="text-xs text-slate-500 mt-1">Usuario por defecto: <code class="bg-slate-100 px-1 py-0.5 rounded">admin</code></p>
          <?php endif; ?>
        </div>

        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1" for="password">Contraseña</label>
          <div class="relative">
            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i class="fas fa-lock text-sm"></i></span>
            <input type="password" id="password" name="password" required
                   class="w-full pl-10 pr-3 py-2 rounded-lg border-slate-300 focus:border-honey-500 focus:ring-honey-500 text-sm"
                   placeholder="••••••••">
          </div>
          <?php if (is_demo() || is_local()): ?>
            <p class="text-xs text-slate-500 mt-1">Contraseña por defecto: <code class="bg-slate-100 px-1 py-0.5 rounded">123456</code></p>
          <?php endif; ?>
        </div>

        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-honey-400 hover:bg-honey-500 text-slate-900 font-semibold transition shadow-sm">
          <i class="fas fa-fingerprint"></i> Ingresar
        </button>
      </form>
    </div>

    <p class="text-center text-xs text-slate-400 mt-6">
      Powered by <span class="text-honey-600 font-medium">Quetzal</span>
    </p>
  </div>

<?php echo load_quetzal_obj(); ?>
<?php echo load_scripts(); ?>
</body>
</html>
