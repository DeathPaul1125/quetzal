<?php

/**
 * Tests del parser de slugs de permisos:
 * adminController::parsePermissionSlug() detecta (recurso, accion) desde
 * convenciones múltiples.
 *
 * Stubea las clases mínimas que adminController necesita para cargarse.
 */

// Stubs de clases del framework para que adminController se cargue sin levantar el sistema
foreach (['MODELS','CONTROLLERS','VIEWS','APP','DS','PLUGINS_PATH','ROOT'] as $c) {
  if (!defined($c)) define($c, sys_get_temp_dir() . DIRECTORY_SEPARATOR);
}
if (!class_exists('Controller'))              eval('class Controller { protected array $data = []; public function setTitle($t){} public function addToData($k,$v){$this->data[$k]=$v;} public function setView($v){} public function render(){} }');
if (!interface_exists('ControllerInterface')) eval('interface ControllerInterface {}');
if (!class_exists('Auth'))                    eval('class Auth { public static function validate(){return true;} }');
if (!class_exists('Csrf'))                    eval('class Csrf { public static function validate($t){return true;} }');
if (!class_exists('Flasher'))                 eval('class Flasher { public static function new($m,$l="info"){} public static function error($m){} public static function success($m){} }');
if (!class_exists('Redirect'))                eval('class Redirect { public static function to($u){} public static function back(){} }');
if (!function_exists('user_can'))             eval('function user_can($p){return true;}');
if (!function_exists('sanitize_input'))       eval('function sanitize_input($s){return is_string($s)?trim($s):$s;}');
if (!function_exists('get_quetzal_message'))  eval('function get_quetzal_message($i){return "msg";}');
if (!function_exists('build_url'))            eval('function build_url($p){return $p;}');
if (!function_exists('now'))                  eval('function now(){return date("Y-m-d H:i:s");}');
if (!function_exists('get_user'))             eval('function get_user($k=null){return null;}');
if (!defined('DB_NAME'))                      define('DB_NAME', 'test_db');
if (!class_exists('QuetzalRoleManager'))      eval('class QuetzalRoleManager { public function __construct($s=null){} public function setRole($s){} public function getPermissions(){return [];} public static function syncPermissions(){return ["added"=>[],"skipped"=>[],"assigned"=>[],"errors"=>[]];} public function allow($p){} public function deny($p){} public function updateRole($id,$n,$s){} public function addRole($n,$s){} public function getRoles(){return [];} public function removeRole($s){} public function addPermission($n,$s,$d=null){} public function removePermission($p){} public function can($p){return true;} public function getRole(){return [];} public function getRoleName(){return "";} public function getRoleSlug(){return "";} }');
if (!class_exists('QuetzalPluginManager'))    eval('class QuetzalPluginManager { public static function getInstance(){return new self();} public function getEnabled(){return [];} public function listAll(){return [];} }');
if (!class_exists('QuetzalHookManager'))      eval('class QuetzalHookManager { public static function registerHook($n,$cb){} public static function getHookData($n,...$a){return [];} }');
if (!class_exists('PaginationHandler'))       eval('class PaginationHandler { public static function paginate($sql,$p,$pp){return ["rows"=>[],"total"=>0,"pagination"=>""];} }');

require_once __DIR__ . '/../../app/controllers/adminController.php';

$parse = ['adminController', 'parsePermissionSlug'];

return [

  // ============= Convención recurso.accion =============
  'recurso.accion: clientes.crear → crear' => function () use ($parse) {
    $r = $parse('clientes.crear');
    q_assert_eq('clientes', $r['recurso']);
    q_assert_eq('crear',    $r['accion_key']);
  },
  'recurso.accion: productos.editar → editar' => function () use ($parse) {
    $r = $parse('productos.editar');
    q_assert_eq('productos', $r['recurso']);
    q_assert_eq('editar',    $r['accion_key']);
  },
  'recurso.accion anidado: caex.guias.crear → recurso=caex.guias' => function () use ($parse) {
    $r = $parse('caex.guias.crear');
    q_assert_eq('caex.guias', $r['recurso']);
    q_assert_eq('crear',      $r['accion_key']);
  },

  // ============= Convención recurso-accion =============
  'recurso-accion: users-read → ver' => function () use ($parse) {
    $r = $parse('users-read');
    q_assert_eq('users', $r['recurso']);
    q_assert_eq('ver',   $r['accion_key']);
  },
  'recurso-accion: roles-write → editar' => function () use ($parse) {
    $r = $parse('roles-write');
    q_assert_eq('roles',  $r['recurso']);
    q_assert_eq('editar', $r['accion_key']);
  },

  // ============= Convención accion-recurso (inversa) =============
  'accion-recurso: read-users → ver' => function () use ($parse) {
    $r = $parse('read-users');
    q_assert_eq('users', $r['recurso']);
    q_assert_eq('ver',   $r['accion_key']);
  },

  // ============= Sinónimos español/inglés =============
  'sinónimo descargar: facturas.exportar → descargar' => function () use ($parse) {
    $r = $parse('facturas.exportar');
    q_assert_eq('facturas',  $r['recurso']);
    q_assert_eq('descargar', $r['accion_key']);
  },
  'sinónimo descargar: facturas-pdf → descargar' => function () use ($parse) {
    $r = $parse('facturas-pdf');
    q_assert_eq('descargar', $r['accion_key']);
  },
  'sinónimo eliminar: x.borrar → eliminar' => function () use ($parse) {
    q_assert_eq('eliminar', $parse('x.borrar')['accion_key']);
  },
  'sinónimo eliminar: x.destroy → eliminar' => function () use ($parse) {
    q_assert_eq('eliminar', $parse('x.destroy')['accion_key']);
  },
  'sinónimo crear: x.add → crear' => function () use ($parse) {
    q_assert_eq('crear', $parse('x.add')['accion_key']);
  },
  'sinónimo crear: x.new → crear' => function () use ($parse) {
    q_assert_eq('crear', $parse('x.new')['accion_key']);
  },
  'sinónimo ver: x.list → ver' => function () use ($parse) {
    q_assert_eq('ver', $parse('x.list')['accion_key']);
  },

  // ============= Convención underscore =============
  'recurso_accion: x_editar → editar' => function () use ($parse) {
    q_assert_eq('editar', $parse('x_editar')['accion_key']);
  },

  // ============= Slug 3 segmentos con accion en medio =============
  'facturas.exportar.pdf → recurso=facturas.pdf, accion=descargar' => function () use ($parse) {
    $r = $parse('facturas.exportar.pdf');
    q_assert_eq('descargar', $r['accion_key']);
    // El recurso queda como "facturas.pdf" (perdimos "exportar" porque era la acción)
    q_assert_contains('facturas', $r['recurso']);
  },

  // ============= No reconocidos =============
  'admin-access (legacy): no parsea como acción estándar' => function () use ($parse) {
    $r = $parse('admin-access');
    q_assert_eq(null, $r['accion_key']);
  },
  'slug vacío → null/null' => function () use ($parse) {
    $r = $parse('');
    q_assert_eq(null, $r['recurso']);
    q_assert_eq(null, $r['accion_key']);
  },
  'slug sin separadores: foobar → no reconocido' => function () use ($parse) {
    $r = $parse('foobar');
    q_assert_eq(null, $r['accion_key']);
  },

  // ============= Case-insensitive =============
  'CASE-INSENSITIVE: PRODUCTOS.CREAR → crear' => function () use ($parse) {
    q_assert_eq('crear', $parse('PRODUCTOS.CREAR')['accion_key']);
  },
];
