<?php
/**
 * @author Pedro Javier López Sánchez     info@takeonme.es
 * @copyright 2017, Pedro Javier López Sánchez. All Rights Reserved.
**/

class codigos_facturae extends fs_model{

   /// clave primaria
   public $id;

   public $nombre;
   public $codigo;
   public $tipo;
   public $codcliente;

   public function __construct($d=FALSE){
      parent::__construct('codigos_facturae');
      if($d)
      {
         $this->id = $d['id'];
         $this->nombre = $d['nombre'];
         $this->codigo = $d['codigo'];
         $this->tipo = $d['tipo'];
         $this->codcliente = $d['codcliente'];
      }else{
         /// valores predeterminados
         $this->id = NULL;
         $this->nombre = NULL;
         $this->codigo = NULL;
         $this->tipo = NULL;
         $this->codcliente = NULL;
      }
   }

   public function install(){
      return '';
   }

   public function exists(){
      if( is_null($this->id) )
      {
         return FALSE;
      }
      else
      {
         return $this->db->select('SELECT * FROM codigos_facturae WHERE id = '.$this->var2str($this->id).' LIMIT 1');
      }
   }

   public function existe_comb(){
      if( is_null($this->codigo) OR is_null($this->codcliente) OR is_null($this->tipo) )
      {
         return FALSE;
      }
      else
      {
         $qry = 'SELECT * FROM codigos_facturae WHERE codigo='.$this->var2str($this->codigo).' AND ';
         $qry .='codcliente='.$this->var2str($this->codcliente).' AND tipo='.$this->var2str($this->tipo).'LIMIT 1';
         return $this->db->select($qry);
      }
   }

   public function get_by_codcliente($codcliente = 0){
      if( is_null($this->codcliente) AND empty($codcliente) ){
         return FALSE;
      }else{
        if(empty($codcliente)){
          $codcliente = $this->codcliente;
        }
        $query = "SELECT * FROM codigos_facturae WHERE codcliente=".$this->var2str($codcliente).";";
        return $this->db->select($query);
      }
   }

   public function insert(){
     $query  = "INSERT INTO codigos_facturae ";
     $query .= "(";
     $query .= "nombre,";
     $query .= "codigo,";
     $query .= "tipo,";
     $query .= "codcliente";
     $query .= ") ";
     $query .= "VALUES ";
     $query .= "(";
     $query .= $this->var2str($this->nombre).",";
     $query .= $this->var2str($this->codigo).",";
     $query .= $this->var2str($this->tipo).",";
     $query .= $this->var2str($this->codcliente);
     $query .= ");";
     //echo $query;die;
     return $this->db->exec($query);
   }

   public function update(){
     $var = array();
     $var['nombre']     = $this->nombre;
     $var['codigo']     = $this->codigo;
     $var['tipo']       = $this->tipo;
     $var['codcliente'] = $this->codcliente;
     $str_qry = 'UPDATE codigos_facturae SET ';
     foreach($var as $llave=>$valor){
       if($valor != NULL){
         $str_qry .= $llave.'='.$this->var2str($valor).',';
       }
     }
     $str_qry  = trim($str_qry,',');
     $str_qry .= ' WHERE id='.$this->var2str($this->id).';';
     //echo $str_qry;die;
     return $this->db->exec($str_qry);
   }

   public function save()
   {
      if( $this->exists() )
      {
         return $this->update();
      }
      else
      {
        return $this->insert();
      }
   }

   public function delete()
   {
      return $this->db->exec('DELETE FROM codigos_facturae WHERE id = '.$this->var2str($this->id).';');
   }

}
