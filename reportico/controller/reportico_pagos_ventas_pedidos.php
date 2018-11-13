<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2016  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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

/**
 * Clase para integrar Reportes de Reportico en Artículos de FacturaScripts
 * 
 * http://www.reportico.org/yii2/web/index.php/quickstart/embedding-reports
 */
class reportico_pagos_ventas_pedidos extends fs_controller {

   public $titulo_menu;
   public $pagina_origen;
   public $tipo_enlace;
   public $archivo_xml;

   public function __construct() {
      /* Inicio zona de código a modificar */
      /// Título del menu, aunque por defecto está oculto
      $this->titulo_menu = 'Reportes de pagos de ' . FS_PEDIDOS;
      /// En la página desde la que se va a llegar a este informe
      $this->pagina_origen = 'ventas_pedido';
      /// Tipo de enlace
      $this->tipo_enlace = 'tab';
      /// Nombre del reporte XML a utilizar
      $this->archivo_xml = "30-AdelantosPedidosClientes";
      /* Fin zona de código a modificar */

      parent::__construct(__CLASS__, $this->titulo_menu, 'Reportes', FALSE, FALSE);
   }

   protected function private_core() {
      $this->share_extensions();
      $this->template = false;

      /// Comprobación para que al activar el plugin no se cargue el controlador
      if (isset($_GET['access_' . __CLASS__])) {
         require_once("plugins/reportico/reportico-src/reportico.php");

         ///   Para crear un informe desde un XML
         $fsvar = new fs_var();
         $q = new reportico();
         $q->allow_debug = true;
         // Opciones: FULL, ONEPROJECT, ONEREPORT, REPORTOUTPUT (Por defecto: FULL)
         $q->access_mode = "ONEREPORT";
         // Opciones: ADMIN, MENU, PREPARE, EXECUTE, MANTAIN (Por defecto: ADMIN)
         $q->initial_execute_mode = "PREPARE";
         // Opciones: HTML, PDF, CSV (Por defecto HTML)
         //$q->initial_output_format = "HTML";
         $q->initial_project = "FacturaScripts";
         $q->initial_project_password = $fsvar->simple_get('reportico_password');
         $q->initial_report = $this->archivo_xml;
         $q->initial_execution_parameters = array();
         $q->initial_execution_parameters['idpedido'] = intval($_GET['id']);
         $q->embedded_report = true;
         //$q->session_namespace = __CLASS__ . '_' . $this->user->nick;
         $q->force_reportico_mini_maintains = true;
         $q->bootstrap_styles = "3";
         $q->bootstrap_preloaded = false;
         $q->reportico_ajax_mode = true;
         $q->reportico_ajax_script_url = $fsvar->simple_get('reportico_http_urlhost') . "plugins/reportico/reportico-src/run.php";
         $q->clear_reportico_session = true;
         $_REQUEST["clear_session"] = 1;
         /* Indicamos los botones que queremos mostrar y activar */
         // Mostrar/Ocultar Estilo Tabla/Forma
         $q->output_template_parameters["show_hide_prepare_page_style"] = "hide";
         // Mostrar/Ocultar HTML Imprimible
         $q->output_template_parameters["show_hide_prepare_print_html_button"] = "show";
         // Mostrar/Ocultar Generar informe en HTML
         $q->output_template_parameters["show_hide_prepare_html_button"] = "show";
         // Mostrar/Ocultar Generar informe en PDF
         $q->output_template_parameters["show_hide_prepare_pdf_button"] = "show";
         // Mostrar/Ocultar Generar informe en CSV
         $q->output_template_parameters["show_hide_prepare_csv_button"] = "show";
         // Mostrar/Ocultar (NO HE VISTO QUE CAMBIA)
         $q->output_template_parameters["show_hide_navigation_menu"] = "hide";
         // Mostrar/Ocultar (NO HE VISTO QUE CAMBIA)
         $q->output_template_parameters["show_hide_dropdown_menu"] = "hide";
         // Mostrar/Ocultar 
         $q->output_template_parameters["show_hide_report_output_title"] = "show";
         // Mostrar/Ocultar las opciones en Mostrar (las listadas a continuación)
         $q->output_template_parameters["show_hide_prepare_section_boxes"] = "hide";
         // Activar/Desactivar Mostrar los criterios
         $q->initial_show_criteria = "hide";
         // Activar/Desactivar Mostrar los detalles
         $q->initial_show_detail = "show";
         // Activar/Desactivar 
         $q->initial_show_graph = "show";
         // Activar/Desactivar Grupo de cabeceras
         $q->initial_show_group_headers = "show";
         // Activar/Desactivar Grupo de trailers
         $q->initial_show_group_trailers = "show";
         // Activar/Desactivar Cabeceras de columnas
         $q->initial_show_column_headers = "show";
         // Ejecutamos la generación del informe
         $q->execute();
      }
   }

   private function share_extensions() {
      $extensions = array(
          array(
              'name' => __CLASS__,
              'page_from' => __CLASS__,
              'page_to' => $this->pagina_origen,
              'type' => $this->tipo_enlace,
              'text' => '<span class="fa fa-file-text-o" aria-hidden="true" title="' . $this->titulo_menu . '"></span><span class="hidden-xs"> &nbsp; ' . $this->titulo_menu . '</span>',
              'params' => '&access_' . __CLASS__ . '=TRUE&clear_session=1'
          )
      );

      foreach ($extensions as $ext) {
         $fsext = new fs_extension($ext);
         $fsext->save();
      }
   }

}
