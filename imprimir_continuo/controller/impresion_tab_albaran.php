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
require_model('pais.php');

class impresion_tab_albaran extends fs_controller
{  
   public $allow_delete;
   public $cliente;
   public $divisa;
   public $forma_pago;
   public $albaran;
   
   public function __construct()
   {
      parent::__construct(__CLASS__, 'Imprimir continuo', 'ventas', FALSE, FALSE);
   }
   
   protected function private_core()
   {
      /// ¿El usuario tiene permiso para eliminar en esta página?
      $this->allow_delete = $this->user->allow_delete_on(__CLASS__);
      
      $this->share_extensions();
      
      $this->ppage = $this->page->get('ventas_albaran');
      $this->cliente = FALSE;
      $this->divisa = new divisa();
      $albaran = new albaran_cliente();
      $this->albaran = FALSE;
      $this->forma_pago = new forma_pago();
      
      if( isset($_GET['id']) )
      {
         $this->albaran = $albaran->get($_GET['id']);
      }
      
      if($this->albaran)
      {
         $this->page->title = $this->albaran->codigo;        
         
         /// cargamos el cliente
         $cliente = new cliente();
         $this->cliente = $cliente->get($this->albaran->codcliente);
           
      }
      else
         $this->new_error_msg("¡Albarán de cliente no encontrado!");
   }
   
   public function url()
   {
      if( !isset($this->albaran) )
      {
         return parent::url ();
      }
      else if($this->albaran)
      {
         return $this->albaran->url();
      }
      else
         return $this->ppage->url();
   } 
   
   private function share_extensions()
   {
      $fsext = new fs_extension();
      $fsext->name = 'btn_imprimir_continuo';
      $fsext->from = __CLASS__;
      $fsext->to = 'ventas_albaran';
      $fsext->type = 'pdf';
      $fsext->text = '<span class="glyphicon glyphicon-print"></span>&nbsp; Papel continuo';
      $fsext->params = '&albaran=TRUE';
      $fsext->save();
   }
}