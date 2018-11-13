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

class opciones_reportico extends fs_controller {

   public $reportico_setup;
   public $reportico_admin_password;
   public $reportico_password;
   public $reportico_http_basedir;
   public $reportico_http_urlhost;
   public $reportico_project_title;
   public $reportico_db_type;
   public $reportico_db_user;
   public $reportico_db_password;
   public $reportico_db_host;
   public $reportico_db_database;
   public $reportico_db_dateformat;
   public $reportico_pdf_header_image;
   public $reportico_pdf_header_xpos;
   public $reportico_pdf_header_ypos;
   public $reportico_pdf_header_width;
   public $reportico_language;
   public $fs_logo;
   public $paths;

   public function __construct() {
      parent::__construct(__CLASS__, 'Opciones', 'Reportes');
   }

   protected function private_core() {
      $this->check_menu();
      $this->configurar();

      $cambios = $this->recursive_copy($this->paths[1], 'plugins/reportico/controller/');
      if (!$cambios) {
         $this->new_advice('No ha sido necesario copiar ningún controlador');
      }
      $cambios = $this->recursive_copy($this->paths[2], 'plugins/reportico/reportico-src/projects/FacturaScripts/');
      if (!$cambios) {
         $this->new_advice('No ha sido necesario copiar ningún XML');
      }
   }

   private function recursive_copy($src, $dst) {
      $changes = FALSE;
      if(!is_dir($src)) {
         @mkdir($src,0777,true);
      }
      $dir = opendir($src);
      if(!is_dir($dst)) {
         @mkdir($dst);
      }
      while (false !== ( $file = readdir($dir))) {
         if (( $file != '.' ) && ( $file != '..' )) {
            if (is_dir($src . '/' . $file)) {
               $source = $src . '/' . $file;
               $destiny = $dst . '/' . $file;
               if (!file_exists($destiny)) {
                  $this->recursive_copy($source, $destiny);
                  $this->new_message('Se ha copiado el archivo ' . $file . ' a su ubicación.');
                  $changes = TRUE;
               } elseif (md5_file($source) != md5_file($destiny)) {
                  $this->recursive_copy($source, $destiny);
                  $this->new_message('Se ha copiado el archivo ' . $file . ' a su ubicación.');
                  $changes = TRUE;
               }
            } else {
               $source = $src . '/' . $file;
               $destiny = $dst . '/' . $file;
               if (!file_exists($destiny)) {
                  copy($source, $destiny);
                  $this->new_message('Se ha copiado el archivo ' . $file . ' a su ubicación.');
                  $changes = TRUE;
               } elseif (md5_file($source) != md5_file($destiny)) {
                  copy($source, $destiny);
                  $this->new_message('Se ha copiado el archivo ' . $file . ' a su ubicación.');
                  $changes = TRUE;
               }
            }
         }
      }
      closedir($dir);
      return $changes;
   }

   private function configurar() {
      $this->paths = array(
          getcwd().'/'.FS_MYDOCS.'documentos/reportico',
          getcwd().'/'.FS_MYDOCS.'documentos/reportico/controller',
          getcwd().'/'.FS_MYDOCS.'documentos/reportico/xml'
      );
      
      foreach ($this->paths as $path) {
         if (is_dir($path)) {
            if (!is_writable($path)) {
               chmod($path, 0777);
            }
         } else {
            mkdir($path, 0777, true);
            $this->new_message('Se ha creado la carpeta ' . $path);
            $path = getcwd().'/'.FS_MYDOCS.'documentos/reportico';
            if (is_dir($path)) {
               if (!is_writable($path)) {
                  chmod($path, 0777);
               }
            } else {
               mkdir($path, 0777, true);
               $this->new_message('Se ha creado la carpeta ' . $path);
            }
         }
      }

      /// cargamos la configuración
      $fsvar = new fs_var();
      if (strtolower(FS_DB_TYPE) == 'postgresql') {
         $dbtype = 'pdo_pgsql';
      } elseif (strtolower(FS_DB_TYPE) == 'mysql') {
         $dbtype = 'pdo_mysql';
      } else {
         $dbtype = 'unknow';
      }

      $full_url = ( (empty($_SERVER['HTTPS']) ? 'http://' : 'https://' ) . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT']  . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], 'index.php')) );
      $this->fs_logo = FS_MYDOCS . 'images/logo.png';

      if (isset($_POST['reportico_setup'])) {
         $this->reportico_admin_password = $_POST['reportico_admin_password'];
         $this->reportico_password = $_POST['reportico_password'];
         $this->reportico_http_basedir = $_POST['reportico_http_basedir'];
         $this->reportico_http_urlhost = $_POST['reportico_http_urlhost'];
         $this->reportico_project_title = $_POST['reportico_project_title'];
         $this->reportico_db_type = $_POST['reportico_db_type'];
         $this->reportico_db_user = $_POST['reportico_db_user'];
         $this->reportico_db_password = $_POST['reportico_db_password'];
         $this->reportico_db_host = $_POST['reportico_db_host'];
         $this->reportico_db_database = $_POST['reportico_db_database'];
         $this->reportico_db_dateformat = $_POST['reportico_db_dateformat'];
         if (file_exists($_POST['reportico_pdf_header_image'])) {
            $this->reportico_pdf_header_image = '../../../' . $_POST['reportico_pdf_header_image'];
         } else {
            $this->reportico_pdf_header_image = '';
         }
         $this->reportico_pdf_header_xpos = $_POST['reportico_pdf_header_xpos'];
         $this->reportico_pdf_header_ypos = $_POST['reportico_pdf_header_ypos'];
         $this->reportico_pdf_header_width = $_POST['reportico_pdf_header_width'];
         $this->reportico_language = $_POST['reportico_language'];

         $this->reportico_setup['reportico_admin_password'] = $this->reportico_admin_password;
         $this->reportico_setup['reportico_password'] = $this->reportico_password;
         $this->reportico_setup['reportico_http_basedir'] = $this->reportico_http_basedir;
         $this->reportico_setup['reportico_http_urlhost'] = $this->reportico_http_urlhost;
         $this->reportico_setup['reportico_project_title'] = $this->reportico_project_title;
         $this->reportico_setup['reportico_db_type'] = $this->reportico_db_type;
         $this->reportico_setup['reportico_db_user'] = $this->reportico_db_user;
         $this->reportico_setup['reportico_db_password'] = $this->reportico_db_password;
         $this->reportico_setup['reportico_db_host'] = $this->reportico_db_host;
         $this->reportico_setup['reportico_db_database'] = $this->reportico_db_database;
         $this->reportico_setup['reportico_db_dateformat'] = $this->reportico_db_dateformat;
         $this->reportico_setup['reportico_pdf_header_image'] = $this->reportico_pdf_header_image;
         $this->reportico_setup['reportico_pdf_header_xpos'] = $this->reportico_pdf_header_xpos;
         $this->reportico_setup['reportico_pdf_header_ypos'] = $this->reportico_pdf_header_ypos;
         $this->reportico_setup['reportico_pdf_header_width'] = $this->reportico_pdf_header_width;
         $this->reportico_setup['reportico_language'] = $this->reportico_language;
         $this->reportico_setup['reportico_pdf_header_image'] = str_replace("../../../", "", $this->reportico_setup['reportico_pdf_header_image']);

         if ($fsvar->array_save($this->reportico_setup)) {
            $this->generate_config();
            $this->new_message('Datos guardados correctamente.');
         } else {
            $this->new_error_msg('Error al guardar los datos.');
         }
      } else {
         $this->reportico_admin_password = 'admin_' . $this->random_string(20);
         $this->reportico_password = $this->random_string(20);
         //$this->reportico_http_basedir = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], 'index.php')).'plugins/reportico/reportico-src/';
         $this->reportico_http_basedir = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], 'index.php'));
         $this->reportico_http_urlhost = $full_url;
         $this->reportico_project_title = 'Reportes para FacturaScripts';
         $this->reportico_db_type = $dbtype;
         $this->reportico_db_user = FS_DB_USER;
         $this->reportico_db_password = FS_DB_PASS;
         $this->reportico_db_host = FS_DB_HOST;
         $this->reportico_db_database = FS_DB_NAME;
         $this->reportico_db_dateformat = 'YYYY-MM-DD';
         if (file_exists($this->fs_logo)) {
            $this->reportico_pdf_header_image = '../../../' . $this->fs_logo;
         } else {
            $this->reportico_pdf_header_image = '';
         }
         $this->reportico_pdf_header_xpos = '10';
         $this->reportico_pdf_header_ypos = '10';
         $this->reportico_pdf_header_width = '100';
         $this->reportico_language = 'es_es';

         $this->reportico_setup = $fsvar->array_get(
                 array(
             'reportico_admin_password' => $this->reportico_admin_password,
             'reportico_password' => $this->reportico_password,
             'reportico_http_basedir' => $this->reportico_http_basedir,
             'reportico_http_urlhost' => $this->reportico_http_urlhost,
             'reportico_project_title' => $this->reportico_project_title,
             'reportico_db_type' => $this->reportico_db_type,
             'reportico_db_user' => $this->reportico_db_user,
             'reportico_db_password' => $this->reportico_db_password,
             'reportico_db_host' => $this->reportico_db_host,
             'reportico_db_database' => $this->reportico_db_database,
             'reportico_db_dateformat' => $this->reportico_db_dateformat,
             'reportico_pdf_header_image' => $this->reportico_pdf_header_image,
             'reportico_pdf_header_xpos' => $this->reportico_pdf_header_xpos,
             'reportico_pdf_header_ypos' => $this->reportico_pdf_header_ypos,
             'reportico_pdf_header_width' => $this->reportico_pdf_header_width,
             'reportico_language' => $this->reportico_language,
                 ), FALSE
         );

         $this->reportico_setup['reportico_pdf_header_image'] = str_replace("../../../", "", $this->reportico_setup['reportico_pdf_header_image']);

         $nombre_archivo = "plugins/reportico/reportico-src/projects/FacturaScripts/config.php";
         if (!file_exists($nombre_archivo)) {
            if ($fsvar->array_save($this->reportico_setup)) {
               $this->generate_config();
               $this->new_message('Datos guardados correctamente.');
            } else {
               $this->new_error_msg('Error al guardar los datos.');
            }
         }            
      }
   }

   private function generate_config() {
      $carpeta_templates_c = "plugins/reportico/reportico-src/templates_c";
      /// Comprobamos si podemos solucionar los permisos del scripts de forma automatica
      if (ini_get('safe_mode')) {
         // PHP Safe Mode activado
         echo "<b>ATENCIÓN:</b> PHP Safe Mode Activado\n";
         echo "   El archivo '" . $carpeta_templates_c . "' necesita permisos 755 para funcionar.";
         die();
      } else {
         // PHP Safe Mode desactivado
         /// Le damos todos los permisos al usuario, y lectura y ejecución a grupo y otros

         if (is_dir($carpeta_templates_c)) {
            if (!is_writable($carpeta_templates_c)) {
               chmod($carpeta_templates_c, 0755);
            }
         } else {
            mkdir($carpeta_templates_c, 0755);
         }
      }

      $nombre_archivo = "plugins/reportico/reportico-src/projects/FacturaScripts/config.php";

      /// Comprobamos si podemos solucionar los permisos del scripts de forma automatica
      if (ini_get('safe_mode')) {
         // PHP Safe Mode activado
         echo "<b>ATENCIÓN:</b> PHP Safe Mode Activado\n";
         echo "   El archivo '" . $nombre_archivo . "' necesita permisos 755 para funcionar.";
         die();
      } else {
         // PHP Safe Mode desactivado
         /// Le damos todos los permisos al usuario, y lectura y ejecución a grupo y otros
         if (!is_writable(str_replace("config.php", "", $nombre_archivo))) {
            chmod(str_replace("config.php", "", $nombre_archivo), 0755);
            if (file_exists($nombre_archivo)) {
               if (!is_writable($nombre_archivo)) {
                  chmod($nombre_archivo, 0755);
               }
            }
         }
      }
      
      $archivo = fopen($nombre_archivo, "w");
      if ($archivo) {
         fwrite($archivo, "<?php\n");
         fwrite($archivo, "// -----------------------------------------------------------------------------\n");
         fwrite($archivo, "// -- Reportico config file generated from FacturaScripts ----------------------\n");
         fwrite($archivo, "// -----------------------------------------------------------------------------\n");
         fwrite($archivo, "// Module : config.php\n");
         fwrite($archivo, "//\n");
         fwrite($archivo, "// General User Configuration Settings for Reportico Operation\n");
         fwrite($archivo, "// -----------------------------------------------------------------------------\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Password required to gain access to the project\n");
         fwrite($archivo, "define('SW_PROJECT_PASSWORD', '" . $this->reportico_password . "');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Location of Reportico Top Level Directory From Browser Point of View\n");
         fwrite($archivo, "define('SW_HTTP_BASEDIR', '" . $this->reportico_http_basedir . "');\n");
         fwrite($archivo, "define('SW_HTTP_URLHOST', '" . $this->reportico_http_urlhost . "');\n");
         fwrite($archivo, "define('SW_DEFAULT_PROJECT', 'FacturaScripts');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Project Title used at the top of menus\n");
         fwrite($archivo, "define('SW_PROJECT_TITLE', '" . $this->reportico_project_title . "');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Identify whether to always run in into Debug Mode\n");
         fwrite($archivo, "define('SW_ALLOW_OUTPUT', true);\n");
         fwrite($archivo, "define('SW_ALLOW_DEBUG', true);\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Identify whether Show Criteria is default option\n");
         fwrite($archivo, "define('SW_DEFAULT_SHOWCRITERIA', false);\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Specification of Safe Mode. Turn on SAFE mode by specifying true.\n");
         fwrite($archivo, "// In SAFE mode, design of reports is allowed but Code and SQL Injection\n");
         fwrite($archivo, "// are prevented. This means that the designer prevents entry of potentially\n");
         fwrite($archivo, "// cdangerous ustom PHP source in the Custom Source Section or potentially\n");
         fwrite($archivo, "// dangerous SQL statements in Pre-Execute Criteria sections\n");
         fwrite($archivo, "define('SW_SAFE_DESIGN_MODE',false);\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// If false prevents any designing of reports\n");
         fwrite($archivo, "define('SW_ALLOW_MAINTAIN', true);\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Identify whether to use AJAX handling. Enabling with enable Data Pickers,\n");
         fwrite($archivo, "// loading of partial form elements and quicker-ti-use design mode\n");
         fwrite($archivo, "define('AJAX_ENABLED', true);\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Location of Reportico Top Level Directory From Browser Point of View\n");
         fwrite($archivo, "// DB connection details for ADODB\n");
         fwrite($archivo, "define('SW_DB_TYPE', '" . $this->reportico_db_type . "');\n");
         fwrite($archivo, "// If connecting to existing framework db then use\n");
         fwrite($archivo, "// db parameters from external framework\n");
         fwrite($archivo, "if ( SW_DB_TYPE == \"framework\" )\n");
         fwrite($archivo, "{\n");
         fwrite($archivo, "define('SW_DB_DRIVER', SW_FRAMEWORK_DB_DRIVER);\n");
         fwrite($archivo, "define('SW_DB_USER', SW_FRAMEWORK_DB_USER);\n");
         fwrite($archivo, "define('SW_DB_PASSWORD', SW_FRAMEWORK_DB_PASSWORD);\n");
         fwrite($archivo, "define('SW_DB_HOST', SW_FRAMEWORK_DB_HOST);\n");
         fwrite($archivo, "define('SW_DB_DATABASE', SW_FRAMEWORK_DB_DATABASE);\n");
         fwrite($archivo, "}\n");
         fwrite($archivo, "else\n");
         fwrite($archivo, "{\n");
         fwrite($archivo, "define('SW_DB_DRIVER', SW_DB_TYPE);\n");
         fwrite($archivo, "define('SW_DB_USER', '" . $this->reportico_db_user . "');\n");
         fwrite($archivo, "define('SW_DB_PASSWORD', '" . $this->reportico_db_password . "');\n");
         fwrite($archivo, "define('SW_DB_HOST', '" . $this->reportico_db_host . "');\n");
         fwrite($archivo, "define('SW_DB_DATABASE', '" . $this->reportico_db_database . "');\n");
         fwrite($archivo, "}\n");
         fwrite($archivo, "define('SW_DB_CONNECT_FROM_CONFIG', true);\n");
         fwrite($archivo, "define('SW_DB_DATEFORMAT', '" . $this->reportico_db_dateformat . "');\n");
         fwrite($archivo, "define('SW_PREP_DATEFORMAT', '" . $this->reportico_db_dateformat . "');\n");
         fwrite($archivo, "define('SW_DB_SERVER', '');\n");
         fwrite($archivo, "define('SW_DB_PROTOCOL', '');\n");
         fwrite($archivo, "define('SW_DB_ENCODING', 'UTF8');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "//HTML Output Encoding\n");
         fwrite($archivo, "define('SW_OUTPUT_ENCODING', 'UTF8');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Identify temp area\n");
         fwrite($archivo, "define('SW_TMP_DIR', \"tmp\");\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// SOAP Environment\n");
         fwrite($archivo, "define('SW_SOAP_NAMESPACE', 'reportico.org');\n");
         fwrite($archivo, "define('SW_SOAP_SERVICEBASEURL', 'http://www.reportico.co.uk/swsite/site/tutorials');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Parameter Defaults\n");
         fwrite($archivo, "define('SW_DEFAULT_PageSize', 'A4');\n");
         fwrite($archivo, "define('SW_DEFAULT_PageOrientation', 'Portrait');\n");
         fwrite($archivo, "define('SW_DEFAULT_TopMargin', \"1cm\");\n");
         fwrite($archivo, "define('SW_DEFAULT_BottomMargin', \"2cm\");\n");
         fwrite($archivo, "define('SW_DEFAULT_LeftMargin', \"1cm\");\n");
         fwrite($archivo, "define('SW_DEFAULT_RightMargin', \"1cm\");\n");
         fwrite($archivo, "define('SW_DEFAULT_pdfFont', \"Helvetica\");\n");
         fwrite($archivo, "define('SW_DEFAULT_pdfFontSize', \"10\");\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// FPDF parameters\n");
         fwrite($archivo, "define('FPDF_FONTPATH', 'fpdf/font/');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Include an image in your PDF output\n");
         fwrite($archivo, "// This defalt places icon top right of a portrait image and sizes it to 100 pixels wide\n");
         if (!empty($this->reportico_pdf_header_image)) {
            fwrite($archivo, "define('PDF_HEADER_IMAGE', '" . $this->reportico_pdf_header_image . "');\n");
            fwrite($archivo, "define('PDF_HEADER_XPOS', '" . $this->reportico_pdf_header_xpos . "'); \n");
            fwrite($archivo, "define('PDF_HEADER_YPOS', '" . $this->reportico_pdf_header_ypos . "');\n");
            fwrite($archivo, "define('PDF_HEADER_WIDTH', '" . $this->reportico_pdf_header_width . "');\n");
         } else {
            fwrite($archivo, "//define('PDF_HEADER_IMAGE', '" . $this->reportico_pdf_header_image . "');\n");
            fwrite($archivo, "//define('PDF_HEADER_XPOS', '" . $this->reportico_pdf_header_xpos . "'); \n");
            fwrite($archivo, "//define('PDF_HEADER_YPOS', '" . $this->reportico_pdf_header_ypos . "');\n");
            fwrite($archivo, "//define('PDF_HEADER_WIDTH', '" . $this->reportico_pdf_header_width . "');\n");
         }
         fwrite($archivo, "\n");
         fwrite($archivo, "// Graph Defaults\n");
         fwrite($archivo, "// Default Charting Engine is JpGraph. A slightly modified version 3.0.7 of jpGraph is supplied\n");
         fwrite($archivo, "// within Reportico. \n");
         fwrite($archivo, "// \n");
         fwrite($archivo, "// Reportico also supports pChart but the pChart package is not currently provided\n");
         fwrite($archivo, "// as part of the Reportico bundle. To use pChart you will need to unpack the pChart\n");
         fwrite($archivo, "// application into the reportico folder named pChart. pChart 2.1.3\n");
         fwrite($archivo, "// You can get pChart from http://www.pchart.net/\n");
         fwrite($archivo, "//\n");
         fwrite($archivo, "define(\"SW_GRAPH_ENGINE\", \"PCHART\" );\n");
         fwrite($archivo, "if ( !defined(\"SW_GRAPH_ENGINE\") || SW_GRAPH_ENGINE == \"JPGRAPH\" )\n");
         fwrite($archivo, "{\n");
         fwrite($archivo, "define('SW_DEFAULT_Font', \"Arial\");\n");
         fwrite($archivo, "//advent_light\n");
         fwrite($archivo, "//Bedizen\n");
         fwrite($archivo, "//Mukti_Narrow\n");
         fwrite($archivo, "//calibri\n");
         fwrite($archivo, "//Forgotte\n");
         fwrite($archivo, "//GeosansLight\n");
         fwrite($archivo, "//MankSans\n");
         fwrite($archivo, "//pf_arma_five\n");
         fwrite($archivo, "//Silkscreen\n");
         fwrite($archivo, "//verdana\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphWidth', 800);\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphHeight', 400);\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphWidthPDF', 500);\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphHeightPDF', 250);\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphColor', \"white\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginTop', \"40\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginBottom', \"90\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginLeft', \"60\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginRight', \"50\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginColor', \"white\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XTickLabelInterval', \"1\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTickLabelInterval', \"2\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XTickInterval', \"1\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTickInterval', \"1\");\n");
         fwrite($archivo, "define('SW_DEFAULT_GridPosition', \"back\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XGridDisplay', \"none\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XGridColor', \"gray\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YGridDisplay', \"none\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YGridColor', \"gray\");\n");
         fwrite($archivo, "define('SW_DEFAULT_TitleFont', SW_DEFAULT_Font);\n");
         fwrite($archivo, "define('SW_DEFAULT_TitleFontStyle', \"Normal\");\n");
         fwrite($archivo, "define('SW_DEFAULT_TitleFontSize', \"12\");\n");
         fwrite($archivo, "define('SW_DEFAULT_TitleColor', \"black\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XTitleFont', SW_DEFAULT_Font);\n");
         fwrite($archivo, "define('SW_DEFAULT_XTitleFontStyle', \"Normal\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XTitleFontSize', \"10\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XTitleColor', \"black\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTitleFont', SW_DEFAULT_Font);\n");
         fwrite($archivo, "define('SW_DEFAULT_YTitleFontStyle', \"Normal\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTitleFontSize', \"10\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTitleColor', \"black\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisFont', SW_DEFAULT_Font);\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisFontStyle', \"Normal\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisFontSize', \"10\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisFontColor', \"black\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisColor', \"black\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisFont', SW_DEFAULT_Font);\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisFontStyle', \"Normal\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisFontSize', \"8\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisFontColor', \"black\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisColor', \"black\");\n");
         fwrite($archivo, "}\n");
         fwrite($archivo, "else // Use jpgraph\n");
         fwrite($archivo, "{\n");
         fwrite($archivo, "define('SW_DEFAULT_Font', \"Mukti_Narrow.ttf\");\n");
         fwrite($archivo, "//advent_light.ttf\n");
         fwrite($archivo, "//Bedizen.ttf\n");
         fwrite($archivo, "//calibri.ttf\n");
         fwrite($archivo, "//Forgotte.ttf\n");
         fwrite($archivo, "//GeosansLight.ttf\n");
         fwrite($archivo, "//MankSans.ttf\n");
         fwrite($archivo, "//pf_arma_five.ttf\n");
         fwrite($archivo, "//Silkscreen.ttf\n");
         fwrite($archivo, "//verdana.ttf\n");
         fwrite($archivo, "define('SW_DEFAULT_FontSize', \"8\");\n");
         fwrite($archivo, "define('SW_DEFAULT_FontColor', \"#303030\");\n");
         fwrite($archivo, "define('SW_DEFAULT_LineColor', \"#303030\");\n");
         fwrite($archivo, "define('SW_DEFAULT_BackColor', \"#eeeeff\");\n");
         fwrite($archivo, "define('SW_DEFAULT_FontStyle', \"Normal\");\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphWidth', 800);\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphHeight', 400);\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphWidthPDF', 500);\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphHeightPDF', 300);\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphColor', SW_DEFAULT_BackColor);\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginTop', \"50\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginBottom', \"80\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginLeft', \"70\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginRight', \"40\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginColor', SW_DEFAULT_BackColor);\n");
         fwrite($archivo, "define('SW_DEFAULT_XTickLabelInterval', \"AUTO\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTickLabelInterval', \"2\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XTickInterval', \"1\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTickInterval', \"1\");\n");
         fwrite($archivo, "define('SW_DEFAULT_GridPosition', \"back\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XGridDisplay', \"none\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XGridColor', SW_DEFAULT_LineColor);\n");
         fwrite($archivo, "define('SW_DEFAULT_YGridDisplay', \"none\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YGridColor', SW_DEFAULT_LineColor);\n");
         fwrite($archivo, "define('SW_DEFAULT_TitleFont', SW_DEFAULT_Font);\n");
         fwrite($archivo, "define('SW_DEFAULT_TitleFontStyle', SW_DEFAULT_FontStyle);\n");
         fwrite($archivo, "define('SW_DEFAULT_TitleFontSize', 12); \n");
         fwrite($archivo, "define('SW_DEFAULT_TitleColor', SW_DEFAULT_LineColor);\n");
         fwrite($archivo, "define('SW_DEFAULT_XTitleFont', SW_DEFAULT_Font);\n");
         fwrite($archivo, "define('SW_DEFAULT_XTitleFontStyle', SW_DEFAULT_FontStyle);\n");
         fwrite($archivo, "define('SW_DEFAULT_XTitleFontSize', SW_DEFAULT_FontSize);\n");
         fwrite($archivo, "define('SW_DEFAULT_XTitleColor', SW_DEFAULT_LineColor);\n");
         fwrite($archivo, "define('SW_DEFAULT_YTitleFont', SW_DEFAULT_Font);\n");
         fwrite($archivo, "define('SW_DEFAULT_YTitleFontStyle', SW_DEFAULT_FontStyle);\n");
         fwrite($archivo, "define('SW_DEFAULT_YTitleFontSize', SW_DEFAULT_FontSize);\n");
         fwrite($archivo, "define('SW_DEFAULT_YTitleColor', SW_DEFAULT_LineColor);\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisFont', SW_DEFAULT_Font);\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisFontStyle', SW_DEFAULT_FontStyle);\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisFontSize', SW_DEFAULT_FontSize);\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisFontColor', SW_DEFAULT_FontColor);\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisColor', SW_DEFAULT_LineColor);\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisFont', SW_DEFAULT_Font);\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisFontStyle', SW_DEFAULT_FontStyle);\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisFontSize', SW_DEFAULT_FontSize);\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisFontColor', SW_DEFAULT_LineColor);\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisColor', SW_DEFAULT_LineColor);\n");
         fwrite($archivo, "}\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Automatic addition of parameter SW_LANGUAGE\n");
         fwrite($archivo, "define('SW_LANGUAGE', '" . $this->reportico_language . "');\n");
         fwrite($archivo, "?>\n");
      }

      $nombre_archivo = "plugins/reportico/reportico-src/projects/admin/config.php";

      /// Comprobamos si podemos solucionar los permisos del scripts de forma automatica
      if (ini_get('safe_mode')) {
         // PHP Safe Mode activado
         echo "<b>ATENCIÓN:</b> PHP Safe Mode Activado\n";
         echo "   El archivo '" . $nombre_archivo . "' necesita permisos 755 para funcionar.";
         die();
      } else {
         // PHP Safe Mode desactivado
         /// Le damos todos los permisos al usuario, y lectura y ejecución a grupo y otros
         if (!is_writable(str_replace("config.php", "", $nombre_archivo))) {
            chmod(str_replace("config.php", "", $nombre_archivo), 0755);
            if (file_exists($nombre_archivo)) {
               if (!is_writable($nombre_archivo)) {
                  chmod($nombre_archivo, 0755);
               }
            }
         }
      }

      $archivo = fopen($nombre_archivo, "w");
      if ($archivo) {
         fwrite($archivo, "<?php\n");
         fwrite($archivo, "// -----------------------------------------------------------------------------\n");
         fwrite($archivo, "// -- Reportico config file generated from FacturaScripts ----------------------\n");
         fwrite($archivo, "// -----------------------------------------------------------------------------\n");
         fwrite($archivo, "// Module : config.php\n");
         fwrite($archivo, "//\n");
         fwrite($archivo, "// General User Configuration Settings for Reportico Operation\n");
         fwrite($archivo, "// -----------------------------------------------------------------------------\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Password required to gain access to Administration Panel\n");
         fwrite($archivo, "// Set to Blank to allow reset from browser\n");
         fwrite($archivo, "define('SW_ADMIN_PASSWORD','" . $this->reportico_admin_password . "');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Default System Language\n");
         fwrite($archivo, "define('SW_LANGUAGE','" . $this->reportico_language . "');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "define('SW_DEFAULT_PROJECT', 'reports');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Project Title used at the top of menus\n");
         fwrite($archivo, "define('SW_PROJECT_TITLE','Página de Administración de Reportico');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Identify whether to always run in into Debug Mode\n");
         fwrite($archivo, "define('SW_ALLOW_OUTPUT', false);\n");
         fwrite($archivo, "define('SW_ALLOW_DEBUG', true);\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Identify whether Show Criteria is default option\n");
         fwrite($archivo, "define('SW_DEFAULT_SHOWCRITERIA', false);\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Specification of Safe Mode. Turn on SAFE mode by specifying true.\n");
         fwrite($archivo, "// In SAFE mode, design of reports is allowed but Code and SQL Injection\n");
         fwrite($archivo, "// are prevented. This means that the designer prevents entry of potentially\n");
         fwrite($archivo, "// cdangerous ustom PHP source in the Custom Source Section or potentially\n");
         fwrite($archivo, "// dangerous SQL statements in Pre-Execute Criteria sections\n");
         fwrite($archivo, "define('SW_SAFE_DESIGN_MODE', false);\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// If false prevents any designing of reports\n");
         fwrite($archivo, "define('SW_ALLOW_MAINTAIN', true);\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// DB connection details for ADODB\n");
         fwrite($archivo, "define('SW_DB_DRIVER', 'none');\n");
         fwrite($archivo, "define('SW_DB_USER', '');\n");
         fwrite($archivo, "define('SW_DB_PASSWORD', '');\n");
         fwrite($archivo, "define('SW_DB_HOST', 'localhost');\n");
         fwrite($archivo, "define('SW_DB_DATABASE', '');\n");
         fwrite($archivo, "define('SW_DB_CONNECT_FROM_CONFIG', true);\n");
         fwrite($archivo, "define('SW_DB_DATEFORMAT', 'Y-m-d');\n");
         fwrite($archivo, "define('SW_PREP_DATEFORMAT', 'Y-m-d');\n");
         fwrite($archivo, "define('SW_DB_SERVER', '');\n");
         fwrite($archivo, "define('SW_DB_PROTOCOL', '');\n");
         fwrite($archivo, "define('SW_DB_ENCODING', 'None');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "//HTML Output Encoding\n");
         fwrite($archivo, "define('SW_OUTPUT_ENCODING', 'UTF8');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Identify temp area\n");
         fwrite($archivo, "define('SW_TMP_DIR', \"tmp\");\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// SOAP Environment\n");
         fwrite($archivo, "define('SW_SOAP_NAMESPACE', 'reportico.org');\n");
         fwrite($archivo, "define('SW_SOAP_SERVICEBASEURL', 'http://www.reportico.co.uk/swsite/site/tutorials');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Parameter Defaults\n");
         fwrite($archivo, "define('SW_DEFAULT_PageSize', 'A4');\n");
         fwrite($archivo, "define('SW_DEFAULT_PageOrientation', 'Landscape');\n");
         fwrite($archivo, "define('SW_DEFAULT_TopMargin', \"1cm\");\n");
         fwrite($archivo, "define('SW_DEFAULT_BottomMargin', \"2cm\");\n");
         fwrite($archivo, "define('SW_DEFAULT_LeftMargin', \"1cm\");\n");
         fwrite($archivo, "define('SW_DEFAULT_RightMargin', \"1cm\");\n");
         fwrite($archivo, "define('SW_DEFAULT_pdfFont', \"Helvetica\");\n");
         fwrite($archivo, "define('SW_DEFAULT_pdfFontSize', \"10\");\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// FPDF parameters\n");
         fwrite($archivo, "define('FPDF_FONTPATH', './fpdf/font/');\n");
         fwrite($archivo, "\n");
         fwrite($archivo, "// Graph Defaults\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphWidth', 800);\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphHeight', 400);\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphWidthPDF', 500);\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphHeightPDF', 250);\n");
         fwrite($archivo, "define('SW_DEFAULT_GraphColor', \"yellow\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginTop', \"20\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginBottom', \"80\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginLeft', \"50\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginRight', \"50\");\n");
         fwrite($archivo, "define('SW_DEFAULT_MarginColor', \"red\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XTickLabelInterval', \"4\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTickLabelInterval', \"2\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XTickInterval', \"1\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTickInterval', \"1\");\n");
         fwrite($archivo, "define('SW_DEFAULT_GridPosition', \"back\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XGridDisplay', \"none\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XGridColor', \"gray\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YGridDisplay', \"major\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YGridColor', \"gray\");\n");
         fwrite($archivo, "define('SW_DEFAULT_TitleFont', \"Font2\");\n");
         fwrite($archivo, "define('SW_DEFAULT_TitleFontStyle', \"Normal\");\n");
         fwrite($archivo, "define('SW_DEFAULT_TitleFontSize', \"12\");\n");
         fwrite($archivo, "define('SW_DEFAULT_TitleColor', \"black\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XTitleFont', \"Font1\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XTitleFontStyle', \"Normal\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XTitleFontSize', \"12\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XTitleColor', \"black\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTitleFont', \"Font1\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTitleFontStyle', \"Normal\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTitleFontSize', \"12\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YTitleColor', \"black\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisFont', \"Font1\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisFontStyle', \"Normal\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisFontSize', \"12\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisFontColor', \"black\");\n");
         fwrite($archivo, "define('SW_DEFAULT_XAxisColor', \"black\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisFont', \"Font1\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisFontStyle', \"Normal\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisFontSize', \"12\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisFontColor', \"black\");\n");
         fwrite($archivo, "define('SW_DEFAULT_YAxisColor', \"black\");\n");
         fwrite($archivo, "?>\n");
      }
   }

   private function check_menu() {
      if (!$this->page->get('reportico_articulos')) {
         if (file_exists(__DIR__)) {
            /// activamos las páginas del plugin
            foreach (scandir(__DIR__) as $f) {
               if( $f != '.' AND $f != '..' AND is_string($f) AND strlen($f) > 4 AND !is_dir($f) AND $f != __CLASS__.'.php' ) {
                  $page_name = substr($f, 0, -4);

                  require_once __DIR__ . '/' . $f;
                  $new_fsc = new $page_name();

                  if (!$new_fsc->page->save()) {
                     $this->new_error_msg("Imposible guardar la página " . $page_name);
                  }

                  unset($new_fsc);
               }
            }
         } else {
            $this->new_error_msg('No se encuentra el directorio ' . __DIR__);
         }

         $this->load_menu(TRUE);
      }
   }

   public function random_string($length = 20) {
      return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
   }

}
