<?php
/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2016  Carlos Garcia Gomez  neorazorx@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_model('md_numero.php');
require_model('articulo.php');
require_model('asiento.php');
require_model('asiento_factura.php');
require_model('cliente.php');
require_model('cuenta_banco_cliente.php');
require_model('divisa.php');
require_model('ejercicio.php');
require_model('factura_cliente.php');
require_model('forma_pago.php');
require_model('pais.php');
require_model('partida.php');
require_model('serie.php');
require_model('subcuenta.php');
require_model('modelonumero.php');
require_model('articulo_traza.php');


class impresion_tab_factura extends fs_controller
{
   public $agente;
   public $agentes;
   public $allow_delete;
   public $cliente;
   public $divisa;
   public $ejercicio;
   public $factura;
   public $forma_pago;
   public $mostrar_boton_pagada;
   public $pais;
   public $rectificada;
   public $rectificativa;
   public $serie;
   public $modelonumero;
   private $articulo_traza;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Imprimir continuo', 'ventas', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
      $this->share_extensions();
      
      $this->modelonumero = new modelonumero();
      $this->ppage = $this->page->get('ventas_facturas');
      $this->ejercicio = new ejercicio();
      $this->agente = FALSE;
      $this->agentes = array();
      $this->cliente = FALSE;
      $this->divisa = new divisa();
      $factura = new factura_cliente();
      $this->factura = FALSE;
      $this->forma_pago = new forma_pago();
      $this->pais = new pais();
      $this->rectificada = FALSE;
      $this->rectificativa = FALSE;
      $this->serie = new serie();
      $this->articulo_traza = new articulo_traza();
      
      if( isset($_GET['id']) )
      {
         $this->factura = $factura->get($_GET['id']);
      }
      
      if($this->factura)
      {
         $this->page->title = $this->factura->codigo;
         
         /// cargamos el agente
         $agente = new agente();
         if( !is_null($this->factura->codagente) )
         {
            $this->agente = $agente->get($this->factura->codagente);
         }
         $this->agentes = $agente->all();
         
         /// cargamos el cliente
         $cliente = new cliente();
         $this->cliente = $cliente->get($this->factura->codcliente);
         
         if($this->factura->idfacturarect)
         {
            $this->rectificada = $factura->get($this->factura->idfacturarect);
         }
         else
         {
            $this->get_factura_rectificativa();
         }
      }
      else
         $this->new_error_msg("¡Factura de cliente no encontrada!");
   }

   public function generar_trazabilidad($linea)
   {
      $lineast = array();

         $lineast = $this->articulo_traza->all_from_linea('idlfacventa', $linea);
      
      
      $txt = '';
      foreach($lineast as $lt)
      {
         $txt .= "\n";
         if($lt->numserie)
         {
            $txt .= 'N/S: '.$lt->numserie.' ';
         }
         
         if($lt->lote)
         {
            $txt .= 'Lote: '.$lt->lote;
         }
      }
      
      return $txt;
   }
   public function url()
   {
      if( !isset($this->factura) )
      {
         return parent::url ();
      }
      else if($this->factura)
      {
         return $this->factura->url();
      }
      else
         return $this->ppage->url();
   }
   
   private function get_factura_rectificativa()
   {
      $sql = "SELECT * FROM facturascli WHERE idfacturarect = ".$this->factura->var2str($this->factura->idfactura);
      
      $data = $this->db->select($sql);
      if($data)
      {
         $this->rectificativa = new factura_cliente($data[0]);
      }
   }
   
   public function get_cuentas_bancarias()
   {
      $cuentas = array();
      
      $cbc0 = new cuenta_banco_cliente();
      foreach($cbc0->all_from_cliente($this->factura->codcliente) as $cuenta)
      {
         $cuentas[] = $cuenta;
      }
      
      return $cuentas;
   }
   
   private function share_extensions()
   {
      $fsext = new fs_extension();
      $fsext->name = 'btn_imprimir_continuo';
      $fsext->from = __CLASS__;
      $fsext->to = 'ventas_factura';
      $fsext->type = 'pdf';
      $fsext->text = '<span class="glyphicon glyphicon-print"></span>&nbsp; Papel continuo';
      $fsext->save();
   }
}