<?php 

/**
 * Handler para excepciones de la clase QuetzalHttp
 * 
 * @since 1.1.4
 * 
 */
class QuetzalHttpException extends Exception{

  private $statusCode = null;

  public function __construct(string $message, int $statusCode = 400, int $code = 0, Exception $previous = null) {
    $this->statusCode = (int) $statusCode;

    parent::__construct($message, $code, $previous);
  }

  public function getStatusCode(){
    return $this->statusCode;
  }

}