<?php

/**
 * Tests del cliente HTTP de Pacifiko.
 *
 * Cubrimos los métodos puros (sin red): URLs por ambiente, chunking, validación
 * de items, y el helper de enmascaramiento. Las llamadas HTTP reales no se
 * prueban acá (las cubre un test de integración manual contra QA).
 */

require_once __DIR__ . '/../lib/ModelStub.php';
if (!class_exists('QuetzalHookManager')) {
  // Stub: en tests no necesitamos el sistema de hooks real
  eval('class QuetzalHookManager {
    public static function registerHook($name, $cb) { return true; }
    public static function getHookData($name, ...$args) { return []; }
  }');
}

require_once __DIR__ . '/../../plugins/Pacifiko/Init.php';
require_once __DIR__ . '/../../plugins/Pacifiko/classes/PacifikoClient.php';

if (!function_exists('q_pacifiko_client')) {
  function q_pacifiko_client(): PacifikoClient {
    return new PacifikoClient([
      'environment' => 'qa',
      'api_user'    => 'TEST_USER',
      'api_key'     => 'TEST_KEY_abcdefghij',
    ]);
  }
}

return [

  // ============= URLs por ambiente =============
  'urlFor: qa apunta a apimarketplace.qa.pacifiko.com' => function () {
    q_assert_eq('https://apimarketplace.qa.pacifiko.com/marketplace', PacifikoClient::urlFor('qa'));
  },
  'urlFor: stg apunta a apimarketplace.stg.pacifiko.com' => function () {
    q_assert_eq('https://apimarketplace.stg.pacifiko.com/marketplace', PacifikoClient::urlFor('stg'));
  },
  'urlFor: prod apunta a apimarketplace.pacifiko.com' => function () {
    q_assert_eq('https://apimarketplace.pacifiko.com/marketplace', PacifikoClient::urlFor('prod'));
  },
  'urlFor: ambiente desconocido tira excepción' => function () {
    q_assert_throws(fn() => PacifikoClient::urlFor('sandbox'), 'Ambiente desconocido');
  },

  // ============= Constructor =============
  'constructor: sin api_user/api_key tira excepción' => function () {
    q_assert_throws(
      fn() => new PacifikoClient(['environment' => 'qa', 'api_user' => '', 'api_key' => '']),
      'no está configurado'
    );
  },
  'constructor: ambiente case-insensitive' => function () {
    $c = new PacifikoClient(['environment' => 'QA', 'api_user' => 'u', 'api_key' => 'k']);
    q_assert_eq('qa', $c->environment());
    q_assert_eq('https://apimarketplace.qa.pacifiko.com/marketplace', $c->baseUrl());
  },

  // ============= Headers =============
  'buildHeaders: incluye los 4 obligatorios + las llaves' => function () {
    $h = q_pacifiko_client()->buildHeaders();
    q_assert_contains('Content-Type: application/json', $h);
    q_assert_contains('Accept: application/json', $h);
    q_assert_contains('X-API-USER: TEST_USER', $h);
    q_assert_contains('X-API-KEY: TEST_KEY_abcdefghij', $h);
  },

  // ============= Chunking =============
  'chunkBatch: array < 100 → 1 chunk' => function () {
    $items = array_fill(0, 50, ['upc' => 'X']);
    $out = PacifikoClient::chunkBatch($items);
    q_assert_eq(1, count($out));
    q_assert_eq(50, count($out[0]));
  },
  'chunkBatch: 100 ítems → 1 chunk de 100' => function () {
    $items = array_fill(0, 100, ['upc' => 'X']);
    $out = PacifikoClient::chunkBatch($items);
    q_assert_eq(1, count($out));
    q_assert_eq(100, count($out[0]));
  },
  'chunkBatch: 250 ítems → 3 chunks (100/100/50)' => function () {
    $items = array_fill(0, 250, ['upc' => 'X']);
    $out = PacifikoClient::chunkBatch($items);
    q_assert_eq(3, count($out));
    q_assert_eq(100, count($out[0]));
    q_assert_eq(100, count($out[1]));
    q_assert_eq(50,  count($out[2]));
  },
  'chunkBatch: tamaño custom' => function () {
    $items = array_fill(0, 25, ['upc' => 'X']);
    $out = PacifikoClient::chunkBatch($items, 10);
    q_assert_eq(3, count($out));
    q_assert_eq(10, count($out[0]));
    q_assert_eq(5,  count($out[2]));
  },

  // ============= Validación de items =============
  'validateItems: lote vacío → excepción' => function () {
    q_assert_throws(fn() => q_pacifiko_client()->validateItems([], ['upc','model']), 'vacío');
  },
  'validateItems: > MAX_BATCH (101) → excepción' => function () {
    $items = array_fill(0, 101, ['upc' => 'X']);
    q_assert_throws(fn() => q_pacifiko_client()->validateItems($items, ['upc','model']), 'excede');
  },
  'validateItems: item sin id → excepción' => function () {
    q_assert_throws(
      fn() => q_pacifiko_client()->validateItems([['price' => 100]], ['upc','model']),
      'identificador'
    );
  },
  'validateItems: mezcla upc + model en el mismo lote → excepción' => function () {
    q_assert_throws(
      fn() => q_pacifiko_client()->validateItems([
        ['upc'   => 'A', 'price' => 10],
        ['model' => 'B', 'price' => 20],
      ], ['upc','model'], ['price']),
      'No mezclar UPC y MODEL'
    );
  },
  'validateItems: falta price → excepción' => function () {
    q_assert_throws(
      fn() => q_pacifiko_client()->validateItems([['upc' => 'A']], ['upc','model'], ['price']),
      "falta 'price'"
    );
  },
  'validateItems: price no numérico → excepción' => function () {
    q_assert_throws(
      fn() => q_pacifiko_client()->validateItems([['upc' => 'A', 'price' => 'caro']], ['upc','model'], ['price']),
      "no es numérico"
    );
  },
  'validateItems: quantity negativa → excepción' => function () {
    q_assert_throws(
      fn() => q_pacifiko_client()->validateItems([['upc' => 'A', 'quantity' => -1]], ['upc','model'], ['quantity']),
      "entero >= 0"
    );
  },
  'validateItems: quantity como string → excepción' => function () {
    q_assert_throws(
      fn() => q_pacifiko_client()->validateItems([['upc' => 'A', 'quantity' => '5']], ['upc','model'], ['quantity']),
      "entero >= 0"
    );
  },
  'validateItems: lote válido (todos UPC + price) no tira' => function () {
    q_pacifiko_client()->validateItems([
      ['upc' => 'A', 'price' => 10.50],
      ['upc' => 'B', 'price' => 20.00],
    ], ['upc','model'], ['price']);
    q_assert_true(true); // ningun throw
  },

  // ============= Normalización de precios =============
  'normalizePrices: redondea a 2 decimales' => function () {
    $out = PacifikoClient::normalizePrices([
      ['upc' => 'A', 'price' => 199.999],
      ['upc' => 'B', 'price' => 22000],
    ]);
    q_assert_eq_loose(200.00, $out[0]['price']);
    q_assert_eq_loose(22000.00, $out[1]['price']);
  },

  // ============= Masking =============
  'p_mask_key: enmascara excepto últimos 4 chars' => function () {
    q_assert_eq('****jLfF', p_mask_key('xxxxjLfF'));
    q_assert_eq('***********0123', p_mask_key('abcdefghijk0123'));
  },
  'p_mask_key: vacío → "—"' => function () {
    q_assert_eq('—', p_mask_key(''));
    q_assert_eq('—', p_mask_key(null));
  },
  'p_mask_key: muy corto → todo asteriscos' => function () {
    q_assert_eq('***', p_mask_key('abc'));
  },
];
