<!DOCTYPE html>
<html lang="<?php echo get_site_lang(); ?>">

<head>
  <base href="<?php echo get_basepath(); ?>">
  <meta charset="<?php echo get_site_charset(); ?>">
  <title><?php echo isset($d->title) ? $d->title . ' - ' . get_sitename() : 'Dashboard - ' . get_sitename(); ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <?php echo get_favicon(); ?>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            honey: { 50:'#fffbeb',100:'#fef3c7',200:'#fde68a',300:'#fcd34d',400:'#fbbf24',500:'#f59e0b',600:'#d97706',700:'#b45309',800:'#92400e',900:'#78350f' }
          }
        }
      }
    }
  </script>

  <!-- Font Awesome (para iconos usados por el framework) -->
  <?php echo get_fontawesome(); ?>

  <!-- Estilos para alertas Flasher del framework (mantiene compatibilidad con Bootstrap) -->
  <style>
    .alert { padding:.75rem 1rem; border-radius:.5rem; border:1px solid; margin-bottom:1rem; font-size:.875rem; }
    .alert-success { background:#ecfdf5; border-color:#a7f3d0; color:#065f46; }
    .alert-danger,.alert-error { background:#fef2f2; border-color:#fecaca; color:#991b1b; }
    .alert-warning { background:#fffbeb; border-color:#fde68a; color:#92400e; }
    .alert-info,.alert-primary { background:#eff6ff; border-color:#bfdbfe; color:#1e40af; }
    [x-cloak]{display:none!important}
  </style>

  <!-- Estilos registrados -->
  <?php echo load_styles(); ?>
  <?php echo get_page_og_meta_tags(); ?>
</head>
