<?php

/**
 * Controlador del plugin HelloQuetzal.
 * Ruta: /hello
 */
class helloController extends Controller implements ControllerInterface
{
  function __construct()
  {
    parent::__construct();
  }

  function index()
  {
    $this->setTitle('Hello Quetzal Plugin');
    $this->addToData('name', 'mundo');
    $this->addToData('messages', Model::query('SELECT * FROM hello_messages ORDER BY id DESC LIMIT 5') ?: []);
    $this->setView('index');
    $this->render();
  }

  function blade()
  {
    $this->setTitle('Hello Quetzal — Blade');
    $this->addToData('name', 'Blade');
    $this->setView('blade');
    $this->setEngine('blade');
    $this->render();
  }
}
