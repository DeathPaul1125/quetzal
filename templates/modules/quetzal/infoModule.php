<?php require_once INCLUDES . 'header.php'; ?>
<?php require_once INCLUDES . 'quetzal_navbar.php'; ?>

<div class="container py-5 main-wrapper">
  <div class="row">
    <div class="col-12">
      <?php echo Flasher::flash(); ?>
    </div>
  </div>
  <div class="row">
    <div class="col-12 text-center mb-5">
      <h2><a href="<?php echo get_base_url(); ?>"><img src="<?php echo get_quetzal_logo(); ?>" alt="Quetzal" class="img-fluid" style="width: 100px;"></a></h2>
    </div>

    <div class="col-12">
      <p>Todos los parámetros mostrados a continuación son la configuración actual de tu instancia de Quetzal, puedes encontrar más información en <code>quetzalController.php</code></p>
      <table class="table table-striped table-hover table-bordered">
        <?php foreach ($d as $k => $v) : ?>
          <tr>
            <th class="bg-light"><?php echo $k; ?></th>
            <td><?php echo $v; ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
</div>

<?php require_once INCLUDES . 'footer.php'; ?>