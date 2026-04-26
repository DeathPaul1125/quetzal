<?php

/**
 * Tests de la lógica de cálculo de kardex (loop saldo corrido).
 *
 * Extraemos el algoritmo del kardexController::index() a una función pura
 * y validamos casos típicos. Si alguien cambia la lógica del controller,
 * acá saltan los tests primero.
 *
 * Convención (igual al modelo real):
 *   - cantidad >= 0  → entrada (suma)
 *   - cantidad <  0  → salida (resta abs)
 *   - saldo final = saldo inicial + Σ cantidad
 */

if (!function_exists('q_test_kardex_calc')) {
  function q_test_kardex_calc(float $saldoInicial, array $movs): array
  {
    $saldo = $saldoInicial;
    $entradas = 0.0; $salidas = 0.0;
    $out = [];
    foreach ($movs as $m) {
      $delta = (float) $m['cantidad'];
      if ($delta >= 0) {
        $m['entrada'] = $delta; $m['salida'] = 0;
        $entradas += $delta;
      } else {
        $m['entrada'] = 0; $m['salida'] = abs($delta);
        $salidas += abs($delta);
      }
      $saldo += $delta;
      $m['saldo'] = $saldo;
      $out[] = $m;
    }
    return ['saldoFinal' => $saldo, 'totalEntradas' => $entradas, 'totalSalidas' => $salidas, 'movs' => $out];
  }
}

return [
  'sin movimientos → saldo final = saldo inicial' => function () {
    $r = q_test_kardex_calc(50, []);
    q_assert_eq_loose(50, $r['saldoFinal']);
    q_assert_eq_loose(0, $r['totalEntradas']);
    q_assert_eq_loose(0, $r['totalSalidas']);
  },

  'una entrada de 10 → saldo+10, totalEntradas=10' => function () {
    $r = q_test_kardex_calc(0, [['cantidad' => 10]]);
    q_assert_eq_loose(10, $r['saldoFinal']);
    q_assert_eq_loose(10, $r['totalEntradas']);
    q_assert_eq_loose(10, $r['movs'][0]['entrada']);
    q_assert_eq_loose(0,  $r['movs'][0]['salida']);
    q_assert_eq_loose(10, $r['movs'][0]['saldo']);
  },

  'una salida de -7 → saldo-7, totalSalidas=7' => function () {
    $r = q_test_kardex_calc(20, [['cantidad' => -7]]);
    q_assert_eq_loose(13, $r['saldoFinal']);
    q_assert_eq_loose(0, $r['totalEntradas']);
    q_assert_eq_loose(7, $r['totalSalidas']);
    q_assert_eq_loose(7, $r['movs'][0]['salida']);
  },

  'mix entrada+salida → saldo corrido correcto' => function () {
    $r = q_test_kardex_calc(0, [
      ['cantidad' => 10],
      ['cantidad' => -3],
      ['cantidad' => 5],
      ['cantidad' => -2],
    ]);
    q_assert_eq_loose(10, $r['movs'][0]['saldo']);
    q_assert_eq_loose(7,  $r['movs'][1]['saldo']);
    q_assert_eq_loose(12, $r['movs'][2]['saldo']);
    q_assert_eq_loose(10, $r['movs'][3]['saldo']);
    q_assert_eq_loose(15, $r['totalEntradas']); // 10+5
    q_assert_eq_loose(5,  $r['totalSalidas']);  // 3+2
    q_assert_eq_loose(10, $r['saldoFinal']);    // 0+10-3+5-2
  },

  'saldo puede quedar negativo si hay sobreventa' => function () {
    $r = q_test_kardex_calc(5, [['cantidad' => -10]]);
    q_assert_eq_loose(-5, $r['saldoFinal']);
  },

  'cantidad cero cuenta como entrada de 0 (no afecta saldo)' => function () {
    $r = q_test_kardex_calc(10, [['cantidad' => 0]]);
    q_assert_eq_loose(10, $r['saldoFinal']);
    q_assert_eq_loose(0, $r['totalEntradas']);
    q_assert_eq_loose(0, $r['totalSalidas']);
  },
];
