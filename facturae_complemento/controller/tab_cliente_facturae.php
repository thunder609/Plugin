<?php
/**
 * @author Pedro Javier López Sánchez     info@takeonme.es
 * @copyright 2017, Pedro Javier López Sánchez. All Rights Reserved.
**/

class tab_cliente_facturae extends fs_controller
{
   public $cliente;
   public $codigos_cliente;

   public $tipos;

   public function __construct()
   {
      parent::__construct(__CLASS__,'Facturae','Facturae',FALSE,FALSE);
   }

   protected function private_core()
   {
     $this->cliente = new cliente;
     $this->codigos_cliente = new codigos_facturae;

     if(!isset($_GET['cod']) OR empty(trim($_GET['cod']))){
       $this->codigos_cliente->codcliente = NULL;
     }else{
       $codcliente = trim($_GET['cod']);
       $this->codigos_cliente->codcliente = strip_tags($codcliente);
     }

     if(isset($_GET['delete']) AND is_numeric($_GET['delete'])){
       $this->codigos_cliente->id = strip_tags(trim($_GET['delete']));
       $this->codigos_cliente->delete();
       unset($this->codigos_cliente->id);
     }

     $this->cliente->codcliente = $this->codigos_cliente->codcliente;

     $this->tipos = array(
       'codoficina' => 'Oficina Contable',
       'codorgano'  => 'Órgano Gestor',
       'codunidad'  => 'Unidad Tramitadora',
       'codorganop' => 'Órgano Proponente',
     );

     $this->share_extension();

     $this->guarda_codigo();
   }

   public function guarda_codigo(){
     if(isset($_POST['nombre']) AND isset($_POST['codigo']) AND isset($_POST['tipo'])){
       foreach($_POST as $key=>$campo){
         $campo = trim($campo);
         if(empty($campo)){
           return FALSE;
         }else{
           if($key == 'nombre'){
             $this->codigos_cliente->nombre = $campo;
           }
           if($key == 'codigo'){
             $this->codigos_cliente->codigo = $campo;
           }
           if($key == 'tipo'){
             $this->codigos_cliente->tipo = $campo;
           }
           if($key == 'id_edit'){
             $this->codigos_cliente->id = $campo;
           }
         }
       }
       if($this->cliente->exists()){
         $this->codigos_cliente->save();
       }else{
         return FALSE;
       }
     }
   }

   public function get_cliente($cod){
     $this->cliente = new cliente;
     return $this->cliente->get($cod);
   }

   public function share_extension(){
      $enlace_js = '<script src="'.FS_PATH.'plugins/facturae_complemento/view/js/js.js?v=0"></script>';
      $extensiones = array(
          array(
              'name' => 'tab_cliente_facturae',
              'page_from' => __CLASS__,
              'page_to' => 'ventas_cliente',
              'type' => 'tab',
              'text' => '<i class="fa fa-list-alt" aria-hidden="true"></i> &nbsp; Facturae',
              'params' => ''
          ),
          array(
              'name' => 'scripts_facturae',
              'page_from' => __CLASS__,
              'page_to' => 'facturae',
              'type' => 'head',
              'text' => $enlace_js,
              'params' => ''
          ),
      );
      foreach($extensiones as $ext){
         $fsext = new fs_extension($ext);
         $fsext->save();
      }
    }
}
