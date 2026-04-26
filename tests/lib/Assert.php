<?php

/**
 * Helpers mínimos de aserción para los tests. No usamos phpunit (no está
 * instalado) — sólo funciones que tiran AssertionError cuando algo falla.
 *
 * Cada función imprime contexto al fallar para que el reporte sea legible.
 */

if (!function_exists('q_assert_true')) {
  function q_assert_true($v, string $msg = ''): void
  {
    if ($v !== true && (bool) $v !== true) {
      throw new AssertionError(($msg !== '' ? $msg . ' — ' : '') . 'esperaba true, recibí ' . var_export($v, true));
    }
  }
}

if (!function_exists('q_assert_eq')) {
  function q_assert_eq($expected, $actual, string $msg = ''): void
  {
    if ($expected !== $actual) {
      throw new AssertionError(
        ($msg !== '' ? $msg . ' — ' : '') .
        'esperaba ' . var_export($expected, true) . ', recibí ' . var_export($actual, true)
      );
    }
  }
}

if (!function_exists('q_assert_eq_loose')) {
  function q_assert_eq_loose($expected, $actual, string $msg = ''): void
  {
    if ($expected != $actual) {
      throw new AssertionError(
        ($msg !== '' ? $msg . ' — ' : '') .
        'esperaba ~' . var_export($expected, true) . ', recibí ' . var_export($actual, true)
      );
    }
  }
}

if (!function_exists('q_assert_contains')) {
  function q_assert_contains($needle, $haystack, string $msg = ''): void
  {
    if (is_string($haystack)) {
      if (strpos($haystack, (string) $needle) === false) {
        throw new AssertionError(($msg !== '' ? $msg . ' — ' : '') . '"' . $needle . '" no está en "' . $haystack . '"');
      }
      return;
    }
    if (is_array($haystack)) {
      if (!in_array($needle, $haystack, true)) {
        throw new AssertionError(($msg !== '' ? $msg . ' — ' : '') . var_export($needle, true) . ' no está en el array');
      }
      return;
    }
    throw new AssertionError($msg . ' — q_assert_contains: tipo no soportado');
  }
}

if (!function_exists('q_assert_throws')) {
  function q_assert_throws(callable $fn, string $expectMsgFragment = '', string $msg = ''): void
  {
    try {
      $fn();
    } catch (Throwable $e) {
      if ($expectMsgFragment !== '' && strpos($e->getMessage(), $expectMsgFragment) === false) {
        throw new AssertionError(
          ($msg !== '' ? $msg . ' — ' : '') .
          'esperaba excepción con "' . $expectMsgFragment . '", recibí "' . $e->getMessage() . '"'
        );
      }
      return;
    }
    throw new AssertionError(($msg !== '' ? $msg . ' — ' : '') . 'esperaba excepción, no hubo ninguna');
  }
}

if (!function_exists('q_assert_array_has_key')) {
  function q_assert_array_has_key(string $key, array $arr, string $msg = ''): void
  {
    if (!array_key_exists($key, $arr)) {
      throw new AssertionError(($msg !== '' ? $msg . ' — ' : '') . 'falta key "' . $key . '" en array');
    }
  }
}
