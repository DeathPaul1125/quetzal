<?php

/**
 * Tests de fProductoModel — validan la firma estática y la presencia de
 * métodos clave. No invocamos métodos que peguen a BD; sólo verificamos
 * que existan y que las constantes apunten a las tablas correctas.
 *
 * Como fProductoModel `extends Model`, stubbeamos Model con una clase vacía
 * para poder cargar el archivo sin levantar el framework completo.
 */

// Model viene del stub compartido cargado en run.php (tests/lib/ModelStub.php).

require_once __DIR__ . '/../../plugins/Facturador/models/fProductoModel.php';

return [
  'fProductoModel::variantes_disponibles existe y es callable' => function () {
    q_assert_true(method_exists('fProductoModel', 'variantes_disponibles'));
    q_assert_true(is_callable(['fProductoModel', 'variantes_disponibles']));
  },

  'fProductoModel::variantes() y variantes_count() existen' => function () {
    q_assert_true(method_exists('fProductoModel', 'variantes'));
    q_assert_true(method_exists('fProductoModel', 'variantes_count'));
  },

  'fProductoModel::stock_total existe' => function () {
    q_assert_true(method_exists('fProductoModel', 'stock_total'));
  },

  'Constantes de tabla apuntan a los nombres correctos' => function () {
    q_assert_eq('fact_productos',           fProductoModel::$t1);
    q_assert_eq('fact_producto_variantes',  fProductoModel::$t2);
  },

  'variantes_disponibles con stub de Model devuelve false (no hay BD)' => function () {
    // Con el stub Model::query siempre devuelve [], así que la detección
    // de tabla/columna falla → variantes_disponibles = false. Esto valida
    // que el código defensivo funciona cuando la migración no corrió.
    $r = fProductoModel::variantes_disponibles();
    q_assert_eq(false, $r);
  },

  'variantes() con migración no corrida → array vacío' => function () {
    q_assert_eq([], fProductoModel::variantes(1));
  },

  'variantes_count() con migración no corrida → 0' => function () {
    q_assert_eq(0, fProductoModel::variantes_count(1));
  },
];
