<?php

/**
 * Tests de WooMapper — todas las funciones son puras (no tocan DB ni red),
 * así que cargamos sólo el archivo y validamos input → output esperado.
 */

require_once __DIR__ . '/../../plugins/WooCommerce/classes/WooMapper.php';

return [

  // ============= productoToWoo =============
  'productoToWoo: simple → type=simple, sin attributes' => function () {
    $p = ['id'=>10,'codigo'=>'PROD-001','sku'=>'SKU001','nombre'=>'Martillo','descripcion'=>'desc',
          'precio_venta'=>85.0,'activo'=>1,'afecto_iva'=>1,'rastrear_stock'=>1,'stock_total'=>5];
    $out = WooMapper::productoToWoo($p);
    q_assert_eq('simple', $out['type']);
    q_assert_eq(false, isset($out['attributes']));
    q_assert_eq('85.00', $out['regular_price']);
    q_assert_eq('publish', $out['status']);
    q_assert_eq('taxable', $out['tax_status']);
    q_assert_eq(true, $out['manage_stock']);
    q_assert_eq(5, $out['stock_quantity']);
  },

  'productoToWoo: tiene_variantes=1 sin variantes pasadas → sigue simple' => function () {
    $p = ['id'=>11,'codigo'=>'X','nombre'=>'X','tiene_variantes'=>1];
    $out = WooMapper::productoToWoo($p, []);
    q_assert_eq('simple', $out['type']);
  },

  'productoToWoo: variable con 2 variantes → type=variable + attributes' => function () {
    $p = ['id'=>20,'codigo'=>'CAM','nombre'=>'Camiseta','tiene_variantes'=>1,'rastrear_stock'=>1,'precio_venta'=>0];
    $vs = [
      ['id'=>100,'sku'=>'CAM-M','nombre'=>'Cam M','precio_venta'=>120,'activo'=>1,'atributos_arr'=>['talla'=>'M','color'=>'Rojo']],
      ['id'=>101,'sku'=>'CAM-L','nombre'=>'Cam L','precio_venta'=>125,'activo'=>1,'atributos_arr'=>['talla'=>'L','color'=>'Rojo']],
    ];
    $out = WooMapper::productoToWoo($p, $vs);
    q_assert_eq('variable', $out['type']);
    q_assert_eq(false, $out['manage_stock'], 'parent variable no maneja stock');
    q_assert_eq(null, $out['stock_quantity']);
    q_assert_eq('', $out['regular_price'], 'parent variable no tiene precio fijo');
    q_assert_eq(2, count($out['attributes']), '2 atributos únicos');

    $byName = [];
    foreach ($out['attributes'] as $a) $byName[$a['name']] = $a;
    q_assert_array_has_key('talla', $byName);
    q_assert_array_has_key('color', $byName);
    q_assert_eq(true, $byName['talla']['variation']);
    q_assert_contains('M', $byName['talla']['options']);
    q_assert_contains('L', $byName['talla']['options']);
    q_assert_contains('Rojo', $byName['color']['options']);
  },

  'productoToWoo: lee atributos desde JSON crudo si no hay atributos_arr' => function () {
    $p = ['nombre'=>'X','tiene_variantes'=>1];
    $vs = [['id'=>1,'sku'=>'A','nombre'=>'a','atributos'=>json_encode(['talla'=>'S'])]];
    $out = WooMapper::productoToWoo($p, $vs);
    q_assert_eq(1, count($out['attributes']));
    q_assert_eq('talla', $out['attributes'][0]['name']);
    q_assert_contains('S', $out['attributes'][0]['options']);
  },

  // ============= varianteToWoo =============
  'varianteToWoo: SKU + precio + atributos como [name/option]' => function () {
    $v = ['id'=>100,'sku'=>'CAM-M','nombre'=>'Cam M','precio_venta'=>120,'activo'=>1,
          'atributos_arr'=>['talla'=>'M','color'=>'Rojo']];
    $out = WooMapper::varianteToWoo($v);
    q_assert_eq('CAM-M', $out['sku']);
    q_assert_eq('120.00', $out['regular_price']);
    q_assert_eq('publish', $out['status']);
    q_assert_eq(2, count($out['attributes']));
    $byName = [];
    foreach ($out['attributes'] as $a) $byName[$a['name']] = $a['option'];
    q_assert_eq('M', $byName['talla']);
    q_assert_eq('Rojo', $byName['color']);
  },

  'varianteToWoo: activo=0 → status=private' => function () {
    $out = WooMapper::varianteToWoo(['nombre'=>'X','activo'=>0,'precio_venta'=>10,'atributos_arr'=>['k'=>'v']]);
    q_assert_eq('private', $out['status']);
  },

  'varianteToWoo: ignora atributos vacíos o sin key' => function () {
    $out = WooMapper::varianteToWoo([
      'nombre'=>'X','activo'=>1,'precio_venta'=>10,
      'atributos_arr'=>['talla'=>'M','vacio'=>'','  '=>'algo','color'=>'Rojo']
    ]);
    q_assert_eq(2, count($out['attributes']), 'sólo talla y color válidos');
  },

  // ============= varianteFromWoo =============
  'varianteFromWoo: parse atributos array → JSON' => function () {
    $w = ['id'=>9001,'sku'=>'CAM-M','description'=>'Cam M','regular_price'=>'120.00','status'=>'publish',
          'attributes'=>[['name'=>'talla','option'=>'M'],['name'=>'color','option'=>'Rojo']]];
    $d = WooMapper::varianteFromWoo($w);
    q_assert_eq('CAM-M', $d['sku']);
    q_assert_eq_loose(120.0, $d['precio_venta']);
    q_assert_eq(1, $d['activo']);
    $atrs = json_decode($d['atributos'], true);
    q_assert_eq('M', $atrs['talla']);
    q_assert_eq('Rojo', $atrs['color']);
  },

  'varianteFromWoo: sin description arma nombre desde atributos' => function () {
    $d = WooMapper::varianteFromWoo([
      'id'=>1,'sku'=>'X','description'=>'','regular_price'=>'10','status'=>'publish',
      'attributes'=>[['name'=>'talla','option'=>'M']]
    ]);
    q_assert_contains('talla', $d['nombre']);
    q_assert_contains('M', $d['nombre']);
  },

  'varianteFromWoo: status private → activo=0' => function () {
    $d = WooMapper::varianteFromWoo(['id'=>1,'description'=>'X','regular_price'=>'1','status'=>'private','attributes'=>[]]);
    q_assert_eq(0, $d['activo']);
  },

  // ============= productoFromWoo =============
  'productoFromWoo: type=variable → tiene_variantes=1' => function () {
    $w = ['id'=>500,'name'=>'Cam','description'=>'','sku'=>'CAM','type'=>'variable',
          'status'=>'publish','manage_stock'=>false,'tax_status'=>'taxable','regular_price'=>''];
    $d = WooMapper::productoFromWoo($w);
    q_assert_eq(1, $d['tiene_variantes']);
    q_assert_eq('CAM', $d['codigo']);
    q_assert_eq('CAM', $d['sku']);
  },

  'productoFromWoo: simple → sin tiene_variantes' => function () {
    $w = ['id'=>5,'name'=>'P','description'=>'','sku'=>'P','type'=>'simple',
          'status'=>'publish','manage_stock'=>false,'tax_status'=>'none','regular_price'=>'10.00'];
    $d = WooMapper::productoFromWoo($w);
    q_assert_eq(false, isset($d['tiene_variantes']));
    q_assert_eq(0, $d['afecto_iva'], 'tax none → afecto_iva=0');
    q_assert_eq_loose(10.0, $d['precio_venta']);
  },

  'productoFromWoo: status=draft → activo=0' => function () {
    $d = WooMapper::productoFromWoo(['id'=>1,'name'=>'X','status'=>'draft','sku'=>'A','description'=>'','type'=>'simple','manage_stock'=>false]);
    q_assert_eq(0, $d['activo']);
  },

  'productoFromWoo: sin SKU → codigo = WOO-{id}' => function () {
    $d = WooMapper::productoFromWoo(['id'=>42,'name'=>'X','status'=>'publish','sku'=>'','description'=>'','type'=>'simple','manage_stock'=>false]);
    q_assert_eq('WOO-42', $d['codigo']);
    q_assert_eq(null, $d['sku']);
  },

  // ============= clienteToWoo / clienteFromWoo =============
  'clienteToWoo: divide nombre en first/last + email fallback' => function () {
    $c = ['id'=>7,'nombre'=>'Juan Carlos Pérez','email'=>'','nit'=>'12345','telefono'=>'5555-1234','direccion'=>'Z1'];
    $out = WooMapper::clienteToWoo($c);
    q_assert_eq('Juan', $out['first_name']);
    q_assert_eq('Carlos Pérez', $out['last_name']);
    q_assert_contains('noreply+7', $out['email']);
    q_assert_eq('quetzal_7', $out['username']);
    q_assert_eq('GT', $out['billing']['country']);
  },

  'clienteFromWoo: lee NIT desde meta_data, default CF' => function () {
    $w = ['email'=>'a@b.com','first_name'=>'Ana','last_name'=>'López',
          'meta_data'=>[['key'=>'_quetzal_nit','value'=>'9876543']],
          'billing'=>['phone'=>'5555','address_1'=>'Calle X']];
    $d = WooMapper::clienteFromWoo($w);
    q_assert_eq('9876543', $d['nit']);
    q_assert_eq('Ana López', $d['nombre']);
    q_assert_eq('Calle X', $d['direccion']);
    q_assert_eq('5555', $d['telefono']);
  },

  'clienteFromWoo: sin meta_data NIT → CF' => function () {
    $d = WooMapper::clienteFromWoo(['email'=>'a@b.com','first_name'=>'A','last_name'=>'B']);
    q_assert_eq('CF', $d['nit']);
  },

  // ============= ordenFromWoo =============
  'ordenFromWoo: cabecera totales + items + variation_id pass-through' => function () {
    $o = [
      'id'=>1001,'number'=>'1001','date_created'=>'2026-04-26T10:00:00',
      'total'=>'112.00','total_tax'=>'12.00','discount_total'=>'0',
      'payment_method'=>'bacs','payment_method_title'=>'Transferencia',
      'customer_id'=>5,
      'billing'=>['first_name'=>'Ana','last_name'=>'L','email'=>'a@b.com','phone'=>'5555','address_1'=>'X'],
      'line_items'=>[[
        'product_id'=>200,'variation_id'=>9001,'sku'=>'CAM-M','name'=>'Cam M',
        'quantity'=>2,'subtotal'=>'200','total'=>'200','price'=>100,'taxes'=>[['id'=>1]],
      ]],
    ];
    $m = WooMapper::ordenFromWoo($o);
    q_assert_eq('WC-1001', $m['cabecera']['numero']);
    q_assert_eq_loose(100.0, $m['cabecera']['subtotal']); // 112 - 12 = 100
    q_assert_eq_loose(12.0, $m['cabecera']['iva']);
    q_assert_eq('transferencia', $m['cabecera']['metodo_pago']);
    q_assert_eq('confirmada', $m['cabecera']['estado']);
    q_assert_eq(1, count($m['items']));
    q_assert_eq(200, $m['items'][0]['woo_product_id']);
    q_assert_eq(9001, $m['items'][0]['woo_variation_id']);
    q_assert_eq_loose(2, $m['items'][0]['cantidad']);
    q_assert_eq(1, $m['items'][0]['afecto_iva']);
    q_assert_eq('a@b.com', $m['cliente_email']);
  },

  'ordenFromWoo: subtotal nunca negativo' => function () {
    $m = WooMapper::ordenFromWoo(['id'=>1,'total'=>'5','total_tax'=>'10','line_items'=>[]]);
    q_assert_eq_loose(0, $m['cabecera']['subtotal'], 'subtotal=max(0, total-iva)');
  },

  'ordenFromWoo: mapPaymentMethod (cod→efectivo, paypal→tarjeta, raro→otro)' => function () {
    q_assert_eq('efectivo',      WooMapper::mapPaymentMethod('cod'));
    q_assert_eq('transferencia', WooMapper::mapPaymentMethod('bacs'));
    q_assert_eq('cheque',        WooMapper::mapPaymentMethod('cheque'));
    q_assert_eq('tarjeta',       WooMapper::mapPaymentMethod('paypal'));
    q_assert_eq('tarjeta',       WooMapper::mapPaymentMethod('stripe'));
    q_assert_eq('otro',          WooMapper::mapPaymentMethod('cualquiera'));
    q_assert_eq('tarjeta',       WooMapper::mapPaymentMethod('PAYPAL'), 'case-insensitive');
  },

  // ============= buildAttributes =============
  'buildAttributes: agrupa opciones únicas por nombre' => function () {
    $vs = [
      ['atributos_arr'=>['talla'=>'M','color'=>'Rojo']],
      ['atributos_arr'=>['talla'=>'M','color'=>'Azul']],
      ['atributos_arr'=>['talla'=>'L','color'=>'Rojo']],
    ];
    $atrs = WooMapper::buildAttributes($vs);
    q_assert_eq(2, count($atrs));
    $byName = [];
    foreach ($atrs as $a) $byName[$a['name']] = $a;
    q_assert_eq(2, count($byName['talla']['options']));  // M, L
    q_assert_eq(2, count($byName['color']['options']));  // Rojo, Azul
  },
];
