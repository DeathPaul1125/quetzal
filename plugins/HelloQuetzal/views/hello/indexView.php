<?php require_once INCLUDES . 'header.php'; ?>
<?php require_once INCLUDES . 'navbar.php'; ?>

<div class="container py-5">
  <div class="row">
    <div class="col-12">
      <h1>¡Hola <?php echo htmlspecialchars($d->name); ?>! 👋</h1>
      <p class="text-muted">Vista servida por el plugin <code>HelloQuetzal</code> usando el motor nativo de Quetzal.</p>
      <link rel="stylesheet" href="<?php echo plugin_asset('HelloQuetzal', 'css/hello.css'); ?>">

      <p><a href="hello/blade" class="btn btn-outline-primary">Ver versión con Blade</a></p>

      <?php if (!empty($d->messages)): ?>
        <h3 class="mt-4">Últimos mensajes</h3>
        <ul class="list-group">
          <?php foreach ($d->messages as $msg): ?>
            <li class="list-group-item"><?php echo htmlspecialchars($msg['message']); ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once INCLUDES . 'footer.php'; ?>
