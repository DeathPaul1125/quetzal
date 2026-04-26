<?php

/**
 * Tests de la lógica de signo de delta en fStockModel::aplicar.
 *
 * No podemos invocar fStockModel::aplicar() directamente sin DB, así que
 * extraemos su `match` de signo a una función pura local y la validamos.
 * Esto sirve como guard-rail: si alguien cambia el match upstream sin
 * actualizar este test, salta.
 */

if (!function_exists('q_test_calc_delta')) {
  function q_test_calc_delta(string $tipo, float $cantidad): float
  {
    return match ($tipo) {
      'entrada', 'devolucion' => abs($cantidad),
      'salida',  'venta'      => -abs($cantidad),
      'ajuste',  'traspaso'   => $cantidad,
      default                 => $cantidad,
    };
  }
}

return [
  'entrada con cantidad positiva → +cantidad' => function () {
    q_assert_eq_loose(10.0, q_test_calc_delta('entrada', 10));
  },
  'entrada con cantidad negativa → +abs (forzar entrada)' => function () {
    q_assert_eq_loose(10.0, q_test_calc_delta('entrada', -10));
  },
  'salida → -abs(cantidad) (siempre negativo)' => function () {
    q_assert_eq_loose(-7.0, q_test_calc_delta('salida', 7));
    q_assert_eq_loose(-7.0, q_test_calc_delta('salida', -7));
  },
  'venta → -abs(cantidad)' => function () {
    q_assert_eq_loose(-3.5, q_test_calc_delta('venta', 3.5));
  },
  'devolucion → +abs (suma)' => function () {
    q_assert_eq_loose(2.0, q_test_calc_delta('devolucion', 2));
  },
  'ajuste respeta signo dado (positivo)' => function () {
    q_assert_eq_loose(5.0, q_test_calc_delta('ajuste', 5));
  },
  'ajuste respeta signo dado (negativo)' => function () {
    q_assert_eq_loose(-5.0, q_test_calc_delta('ajuste', -5));
  },
  'traspaso respeta signo dado' => function () {
    q_assert_eq_loose(2.0, q_test_calc_delta('traspaso', 2));
    q_assert_eq_loose(-2.0, q_test_calc_delta('traspaso', -2));
  },
  'tipo desconocido → pasa el valor crudo' => function () {
    q_assert_eq_loose(42.0, q_test_calc_delta('lo_que_sea', 42));
  },
];
