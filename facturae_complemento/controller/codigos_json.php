<?php
/**
 * @author Pedro Javier López Sánchez     info@takeonme.es
 * @copyright 2017, Pedro Javier López Sánchez. All Rights Reserved.
**/

class codigos_json extends fs_controller
{
  public $cliente;
  public $factura_cliente;
  public $codigos_facturae;

  public function __construct()
  {
    parent::__construct(__CLASS__,'Eventsjson','G. Comercial',FALSE,FALSE);
  }

  protected function private_core()
  {
    $this->template = FALSE;

    if($_GET['page'] === 'codigos_json'){

      $this->cliente = new cliente;
      $this->factura_cliente = new factura_cliente;
      $this->codigos_facturae = new codigos_facturae;

      if(isset($_GET['id']) and is_numeric($_GET['id'])){
        $this->factura_cliente->id = trim($_GET['id']);
      }

      $datos_factura = $this->factura_cliente->get($this->factura_cliente->id);

      $codigos = $this->codigos_facturae->get_by_codcliente($datos_factura->codcliente);

      $cadena = '{"success": 1,';
        $cadena .='"result": [';
        foreach($codigos as $codigo_ar){
          $cadena .= '{';
            foreach($codigo_ar as $key=>$codigo){
              $cadena .= '"'.$key.'": "'.$codigo.'",';
            }
            $cadena = trim($cadena,',');
            $cadena .= '},';

        }

        $cadena_f = ']}';

        $cadena = trim($cadena,',');

        header('Content-Type: application/json');
        echo $cadena.$cadena_f;

      }

    }

  }
