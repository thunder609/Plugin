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
require_model('articulo.php');
require_model('cliente.php');
require_model('divisa.php');
require_model('forma_pago.php');
require_model('presupuesto_cliente.php');
require_model('pais.php');

class impresion_tab_presupuesto extends fs_controller
{  
   public $allow_delete;
   public $cliente;
   public $divisa;
   public $forma_pago;
   public $presupuesto;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Imprimir continuo', 'ventas', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
      $this->share_extensions();
      
      $this->ppage = $this->page->get('ventas_presupuesto');
      $this->cliente = FALSE;
      $this->divisa = new divisa();
      $presupuesto = new presupuesto_cliente();
      $this->presupuesto = FALSE;
      $this->forma_pago = new forma_pago();
      
      if( isset($_GET['id']) )
      {
         $this->presupuesto = $presupuesto->get($_GET['id']);
      }
      
      if($this->presupuesto)
      {
         $this->page->title = $this->presupuesto->codigo;        
         
         /// cargamos el cliente
         $cliente = new cliente();
         $this->cliente = $cliente->get($this->presupuesto->codcliente);
           
      }
      else
         $this->new_error_msg("¡Presupuesto de cliente no encontrado!");
   }
   
   public function url()
   {
      if( !isset($this->presupuesto) )
      {
         return parent::url ();
      }
      else if($this->presupuesto)
      {
         return $this->presupuesto->url();
      }
      else
         return $this->ppage->url();
   } 
   
   private function share_extensions()
   {
      $fsext = new fs_extension();
      $fsext->name = 'btn_imprimir_continuo';
      $fsext->from = __CLASS__;
      $fsext->to = 'ventas_presupuesto';
      $fsext->type = 'pdf';
      $fsext->text = '<span class="glyphicon glyphicon-print"></span>&nbsp; Papel continuo';
      $fsext->params = '&presupuesto=TRUE';
      $fsext->save();
   }
}