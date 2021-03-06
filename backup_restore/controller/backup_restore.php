<?php

/*
 * This file is part of FacturaScripts
 * Copyright (C) 2016  Francesc Pineda Segarra     shawe.ewahs@gmail.com
 * Copyright (C) 2016  Joe Nilson                  joenilson@gmail.com
 * Copyright (C) 2016  Rafael Salas Venero         rsalas.match@gmail.com
 * Copyright (C) 2017  Carlos García Gómez         neorazorx@gmail.com
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

require_once __DIR__ . '/../vendor/FacturaScripts/DatabaseManager.php';

use FacturaScripts\DatabaseManager;

/*
 *  Thanks to:
 *  http://php.net/manual/en/function.mime-content-type.php#87856
 */
if (!function_exists('mime_content_type')) {

   function mime_content_type($filename) {
      /* Como sólo soportamos copias en ZIP, no necesitamos más tipos */
      $mime_types = array(
          'zip' => 'application/zip',
      );

      $ext = strtolower(array_pop(explode('.', $filename)));
      if (array_key_exists($ext, $mime_types)) {
         return $mime_types[$ext];
      } elseif (function_exists('finfo_open')) {
         $finfo = finfo_open(FILEINFO_MIME);
         $mimetype = finfo_file($finfo, $filename);
         finfo_close($finfo);
         return $mimetype;
      } else {
         return 'application/octet-stream';
      }
   }

}

class backup_restore extends fs_controller {

   const backups_path = "backups";
   const sql_path = "sql";
   const fs_files_path = "archivos";

   public $backup_setup;
   public $backupdb_file_now;
   public $backupfs_file_now;
   public $backup_comando;
   public $basepath;
   public $db_version;
   public $db_version_number;
   public $files;
   public $fsvar;
   public $fs_backup_files;
   public $path;
   public $restore_comando;
   public $restore_comando_data;
   public $sql_backup_files;
   public $loop_horas;
   public $backup_cron;
   /* Opciones para impedir descargar/subir backups */
   public $disable_configure_backups;
   public $disable_delete_backups;
   public $disable_download_backups;
   public $disable_upload_backups;

   public function __construct() {
      parent::__construct(__CLASS__, 'Copias de seguridad', 'admin', FALSE, TRUE);
   }

   protected function private_core() {

      $this->check_flags();

      if (!$this->user->admin) {
         $this->new_error_msg('Sólo un administrador puede realizar todas las acciones en esta página.');
      }

      $this->db_version = $this->db->version();
      //Para garantizar las variables de cada perfil de db debemos de colocar esta opcion
      if (FS_DB_TYPE == 'POSTGRESQL') {
         $dbinfo = \pg_version();
         $this->db_version_number = $dbinfo['server'];
      } else {
         $dbinfo = $this->db->select("SELECT version();");
         $this->db_version_number = $dbinfo[0]['version()'];
      }
      $this->fsvar = new fs_var();
      $this->loop_horas = array();
      //Si no existe el backups_path lo creamos
      if (!file_exists(self::backups_path)) {
         mkdir(self::backups_path);
      }

      //Si no existe el backups_path/sql_path lo creamos
      if (!file_exists(self::backups_path . DIRECTORY_SEPARATOR . self::sql_path)) {
         mkdir(self::backups_path . DIRECTORY_SEPARATOR . self::sql_path);
      }

      //Si no existe el backups_path/fs_files_path lo creamos
      if (!file_exists(self::backups_path . DIRECTORY_SEPARATOR . self::fs_files_path)) {
         mkdir(self::backups_path . DIRECTORY_SEPARATOR . self::fs_files_path);
      }

      //Buscamos los binarios necesarios en las rutas normales
      $this->configure();
      $this->basepath = dirname(dirname(dirname(__DIR__)));
      $this->path = self::backups_path;

      //Creamos un array para el selector de horas para cron
      for ($x = 0; $x < 25; $x++) {
         $this->loop_horas[] = str_pad($x, 2, "0", STR_PAD_LEFT);
      }

      // Interfaz para cargar
      $dbInterface = ucfirst(strtolower(FS_DB_TYPE));
      require_once 'plugins/backup_restore/vendor/FacturaScripts/DBProcess/' . $dbInterface . 'Process.php';

      $this->backup_comando = $this->backup_setup['backup_comando'];
      $this->restore_comando = $this->backup_setup['restore_comando'];
      $this->restore_comando_data = $this->backup_setup['restore_comando_data'];
      $this->backup_cron = $this->backup_setup['backup_cron'];
      $accion = filter_input(INPUT_POST, 'accion');
      if ($accion) {
         $info = array(
             'dbms' => FS_DB_TYPE,
             'host' => FS_DB_HOST,
             'port' => FS_DB_PORT,
             'user' => FS_DB_USER,
             'pass' => FS_DB_PASS,
             'dbname' => FS_DB_NAME,
             'dbms_version' => $this->db_version_number,
             'command' => ($accion == 'backupdb') ? $this->backup_comando : $this->restore_comando,
             'backupdir' => $this->basepath . DIRECTORY_SEPARATOR . self::backups_path . DIRECTORY_SEPARATOR . self::sql_path
         );
         switch ($accion) {
            case "subirarchivo":
               $this->upload_file();
               break;
            case "backupdb":
               $this->backup_db($info);
               break;
            case "restaurardb":
               $this->restore_db($info);
               $this->clean_cache();
            case "configuracion":
               $this->configure();
               break;
            case "backupfs":
               $this->backup_fs();
               break;
            case "restaurarfs":
               $this->restore_fs();
               $this->clean_cache();
               break;
            case "eliminar":
               $this->delete_file();
               break;
            case "programar_backup":
               $this->programar_backup();
               break;
            default:
               break;
         }
         //Verificamos los comandos luego de cualquier actualización
         $this->backup_comando = $this->backup_setup['backup_comando'];
         $this->restore_comando = $this->backup_setup['restore_comando'];
         $this->restore_comando_data = $this->backup_setup['restore_comando_data'];
         $this->backup_cron = $this->backup_setup['backup_cron'];
      }

      //Verificamos si existe un backup con la fecha actual para mostrarlo en el view
      $this->backupdb_file_now = file_exists(self::backups_path . DIRECTORY_SEPARATOR . self::sql_path . DIRECTORY_SEPARATOR . FS_DB_TYPE . '_' . FS_DB_NAME . "_" . \date("Ymd") . ".zip");
      $this->backupfs_file_now = file_exists(self::backups_path . DIRECTORY_SEPARATOR . self::fs_files_path . DIRECTORY_SEPARATOR . "FS_" . \date("Ymd") . ".zip");

      $this->sql_backup_files = $this->getFiles(self::backups_path . DIRECTORY_SEPARATOR . self::sql_path);
      $this->fs_backup_files = $this->getFiles(self::backups_path . DIRECTORY_SEPARATOR . self::fs_files_path);
   }

   /**
    * Configura los comándos y parámetros del plugin desde el botón "Configuración"
    */
   private function configure() {
      //Inicializamos la configuracion
      $this->backup_setup = $this->fsvar->array_get(
              array(
          'backup_comando' => '',
          'restore_comando' => '',
          'restore_comando_data' => '',
          'backup_ultimo_proceso' => '',
          'backup_cron' => '',
          'backup_procesandose' => 'FALSE',
          'backup_usuario_procesando' => ''
              ), TRUE
      );

      $cmd1 = $this->findCommand(filter_input(INPUT_POST, 'backup_comando'), true, false);
      $comando_backup = ($cmd1) ? trim($cmd1) : $this->backup_setup['backup_comando'];

      $cmd2 = $this->findCommand(filter_input(INPUT_POST, 'restore_comando'), false, false);
      $comando_restore = ($cmd2) ? trim($cmd2) : $this->backup_setup['restore_comando'];

      $cmd3 = $this->findCommand(filter_input(INPUT_POST, 'restore_comando_data'), false, true);
      $comando_restore_data = ($cmd3) ? trim($cmd3) : $this->backup_setup['restore_comando_data'];

      $backup_config = array(
          'backup_comando' => $comando_backup,
          'restore_comando' => $comando_restore,
          'restore_comando_data' => $comando_restore_data
      );
      $this->fsvar->array_save($backup_config);
   }

   /**
    * Función para programar backups
    */
   private function programar_backup() {
      $op_backup_cron = \filter_input(INPUT_POST, 'backup_cron');
      $backup_cron = ($op_backup_cron == 'TRUE') ? "TRUE" : "FALSE";
      $backup_config = array(
          'backup_cron' => $backup_cron,
      );
      if ($this->fsvar->array_save($backup_config)) {
         $this->new_message('¡Backup programado correctamente!');
      } else {
         $this->new_error_msg('Ocurrió un error intentando guardar la información, intentelo nuevamente.');
      }
      $this->configure();
   }

   /**
    * Busca el $comando recibido, distinguiendo:
    *    - Si $backup es TRUE, el comando será para backup
    *    - Si $backup es FALSE, el comando será para restore
    * Y $onlydata:
    *    - Si $onlydata es FALSE, será para estructura + datos
    *    - Si $onlydata es TRUE, será para sólo datos
    * 
    * @param type $comando
    * @param type $backup
    * @param type $onlydata
    * @return boolean
    */
   private function findCommand($comando, $backup = TRUE, $onlydata = FALSE) {
      if (isset($comando)) {
         $resultado = array();
         exec("$comando --version", $resultado);
         if (!empty($resultado[0])) {
            return $comando;
         } else {
            return false;
         }
      } else {
         $paths = $this->osPath($backup, $onlydata);

         foreach ($paths as $cmd) {
            $lanza_comando = '"' . "$cmd" . '"' . " --version";
            exec($lanza_comando, $resultado);
            if (!empty($resultado[0])) {
               return '"' . "$cmd" . '"';
            }
         }
      }
   }

   /**
    * Se busca en determinadas rutas predefinidas
    * 
    * @param type $backup
    * @param type $onlydata
    * @return string
    */
   private function osPath($backup = TRUE, $onlydata = FALSE) {
      $paths = array();
      $db_version = explode(" ", $this->db->version());
      $version[0] = substr($db_version[1], 0, 1);
      $version[1] = intval(substr($db_version[1], 1, 2));
      if (PHP_OS == "WINNT") {
         $comando = (FS_DB_TYPE == 'POSTGRESQL') ? array('pg_dump.exe', 'pg_restore.exe', 'pg_restore.exe') : array('mysqldump.exe', 'mysql.exe', 'mysqlimport.exe');
         if ($backup == TRUE) {
            $comando = $comando[0];
         } else {
            $comando = ($onlydata) ? $comando = $comando[2] : $comando = $comando[1];
         }
         $base_dir = str_replace(" (x86)", "", getenv("PROGRAMFILES")) . "\\";
         $base_dirx86 = getenv("PROGRAMFILES") . "\\";
         $paths[] = $base_dir . ucfirst(strtolower($db_version[0])) . "\\" . ucfirst(strtolower($db_version[0])) . " Server " . $version[0] . "." . $version[1] . "\\bin\\" . $comando;
         $paths[] = $base_dirx86 . ucfirst(strtolower($db_version[0])) . "\\" . ucfirst(strtolower($db_version[0])) . " Server " . $version[0] . "." . $version[1] . "\\bin\\" . $comando;
         $paths[] = $base_dir . ucfirst(strtolower($db_version[0])) . "\\" . ucfirst(strtolower($db_version[0])) . " Server " . $version[0] . "." . $version[1] . "\\exe\\" . $comando;
         $paths[] = $base_dirx86 . ucfirst(strtolower($db_version[0])) . "\\" . ucfirst(strtolower($db_version[0])) . " Server " . $version[0] . "." . $version[1] . "\\exe\\" . $comando;
      } else {
         $comando = (FS_DB_TYPE == 'POSTGRESQL') ? array('pg_dump', 'pg_restore', 'pg_restore') : array('mysqldump', 'mysql', 'mysqlimport');
         if ($backup == TRUE) {
            $comando = $comando[0];
         } else {
            $comando = ($onlydata) ? $comando = $comando[2] : $comando = $comando[1];
         }
         $paths[] = "/usr/bin/" . $comando;
      }
      return $paths;
   }

   /**
    * Devuelve una lista ordenada de los archivos para el directorio indicado
    * 
    * @param type $dir
    * @return \stdClass
    */
   private function getFiles($dir) {
      $results = array();
      $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
      foreach ($it as $file) {
         if ($file->isFile()) {
            //verificamos si el archivo es un zip y si tiene un config.json
            $informacion = $this->getConfigFromFile($dir, $file);
            $archivo = new stdClass();
            $archivo->filename = $file->getFilename();
            $archivo->path = $file->getPathName();
            // FIXME Revisar para no tener que pasar el valor por duplicado
            $archivo->escaped_path = addslashes($file->getPathName());
            $archivo->size = self::tamano(filesize($file->getPathName()));
            $archivo->date = date('Y-m-d', filemtime($file->getPathName()));
            $archivo->type = $file->getExtension();
            $archivo->file = TRUE;
            $archivo->conf = $informacion;
            $results[] = $archivo;
         } else {
            continue;
         }
      }
      $ordenable = Array();
      foreach ($results as &$columnaorden) {
         $ordenable[] = &$columnaorden->date;
      }
      array_multisort($ordenable, SORT_DESC, SORT_STRING, $results);
      return $results;
   }

   /**
    * Devuelve una url generada por la clase padre
    *  
    * @return type
    */
   public function url() {
      return parent::url();
   }

   /**
    * Devuelte el tamaño en unidades legibles por humanos
    * 
    * @param type $tamano
    * @return type
    */
   public function tamano($tamano) {
      /* https://es.wikipedia.org/wiki/Mebibyte */
      $bytes = $tamano;
      $decimals = 2;
      $sz = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
      $factor = floor((strlen($bytes) - 1) / 3);
      return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $sz[$factor];
   }

   /**
    * Devuelve el contenido de config.json de dentro de un ZIP si existe, 
    * y sino devuelve FALSE
    * 
    * @param type $dir
    * @param type $file
    * @return boolean
    */
   private function getConfigFromFile($dir, $file) {
      $filePath = $dir . '/' . $file->getFilename();
      if (strcmp(mime_content_type($filePath), 'application/zip') == 0) {
         $z = new ZipArchive();
         if ($z->open($filePath)) {
            $contents = '';
            $fp = $z->getStream('config.json');
            if ($fp) {
               while (!feof($fp)) {
                  $contents .= fread($fp, 2);
               }
               fclose($fp);
               return json_decode($contents);
            } else {
               return false;
            }
         } else {
            return false;
         }
      } else {
         return false;
      }
   }

   /**
    * Genera un archivo ZIP desde una ruta indicada.
    * 
    * Como el zip se guarda dentro de la propia estructura que se recibirá,
    * se omite dicho directorio en particular.
    * 
    * @param type $folder
    * @param type $zipFile
    * @param type $exclusiveLength
    */
   private static function folderToZip($folder, &$zipFile, $exclusiveLength) {
      $handle = opendir($folder);
      while (false !== $f = readdir($handle)) {
         if ($f != '.' && $f != '..') {
            $filePath = "$folder/$f";
            // Remove prefix from file path before add to zip.
            $localPath = substr($filePath, $exclusiveLength);
            if (is_file($filePath)) {
               $zipFile->addFile($filePath, $localPath);
            } elseif (is_dir($filePath)) {
               if (strpos($localPath, self::fs_files_path) !== false) {
                  // Contiene self::fs_files_path
                  // No queremos backup de backups, pero si queremos backups de sql
               } else {
                  // Add sub-directory.
                  $zipFile->addEmptyDir($localPath);
                  self::folderToZip($filePath, $zipFile, $exclusiveLength);
               }
            }
         }
      }
      closedir($handle);
   }

   /**
    * Procesa la subida de un archivo ZIP indicado por el usuario, y se encarga 
    * de moverlo a la ruta correcta en función de si se trata de una copia de SQL 
    * o de datos.
    */
   private function upload_file() {
      if (is_uploaded_file($_FILES['archivo']['tmp_name'])) {
         // Revisamos si el fichero tiene el json con información
         $fichero = new SplFileInfo($_FILES['archivo']['tmp_name']);
         $dir = $fichero->getPath();
         // Si tiene información es un backup de SQL, sino de datos de FS
         $informacion = $this->getConfigFromFile($dir, $fichero);

         if ($informacion) {
            $destino = self::backups_path . DIRECTORY_SEPARATOR . self::sql_path . DIRECTORY_SEPARATOR . $_FILES['archivo']['name'];
         } else {
            $destino = self::backups_path . DIRECTORY_SEPARATOR . self::fs_files_path . DIRECTORY_SEPARATOR . $_FILES['archivo']['name'];
         }

         if (copy($_FILES['archivo']['tmp_name'], $destino)) {
            $this->new_message('Archivo ' . $_FILES['archivo']['name'] . ' añadido correctamente.');
         } else {
            $this->new_error_msg('Error al mover el archivo ' . $_FILES['archivo']['name'] . '.');
         }
      }
   }

   /**
    * Realiza una copia de seguridad de la base de datos indicado por el usuario
    * 
    * @param type $info
    */
   private function backup_db($info) {
      $this->template = false;
      $crear_db = filter_input(INPUT_POST, 'crear_db');
      $estructura = filter_input(INPUT_POST, 'estructura');
      $solo_datos = filter_input(INPUT_POST, 'solo_datos');
      //Colocamos en el DatabaseManager las variables específicas para hacer el backup
      $manager = new DatabaseManager($info);
      $manager->createdb = ($crear_db) ? true : false;
      $manager->onlydata = ($estructura) ? false : true;
      $manager->nodata = ($solo_datos) ? false : true;
      try {
         $backup = $manager->createBackup('full');
         if (file_exists($backup)) {
            $file = str_replace($this->basepath . '/backups/sql/', '', $backup);
            header('Content-Type: application/json');
            echo json_encode(array('success' => true, 'mensaje' => 'Backup de base de datos realizado correctamente: ' . $file));
         } else {
            header('Content-Type: application/json');
            echo json_encode(array('success' => false, 'mensaje' => 'Algo salió mal realizando el backup de base de datos: ' . $file));
         }
      } catch (Exception $e) {
         header('Content-Type: application/json');
         echo json_encode(array('success' => false, 'mensaje' => 'Ocurrio un error interno al intentar crear el backup:' . $e->getMessage()));
      }
   }

   /**
    * Realiza una restauración de la base de datos indicado por el usuario
    * 
    * @param type $info
    */
   private function restore_db($info) {
      $archivo = realpath(\filter_input(INPUT_POST, 'restore_file'));
      if (file_exists($archivo)) {
         $fichero = new SplFileInfo($archivo);
         $dir = $fichero->getPath();
         $informacion = $this->getConfigFromFile($dir, $fichero);
         $manager = new DatabaseManager($info);
         $manager->createdb = $informacion->configuracion->{'create_database'};
         if (!$manager->createdb) {
            $manager->command = $this->restore_comando_data;
         }
         $backup = $manager->restoreBackup($archivo, $informacion->configuracion);
         if ($backup) {
            $file = str_replace($this->basepath . '/backups/sql/', '', $backup);
            $this->new_error_msg('Ocurrió un error al querer restaurar el backup de base de datos: ' . $file);
         } else {
            $this->new_message('¡Backup de base de datos restaurado con exito!');
         }
      } else {
         $this->new_error_msg('¡No se indicó un backup de base de datos para realizar la restauración!');
      }
   }

   /**
    * Realiza una copia de seguridad de los archivos indicado por el usuario
    */
   private function backup_fs() {
      $this->file = self::backups_path . DIRECTORY_SEPARATOR . self::fs_files_path . DIRECTORY_SEPARATOR . 'FS_' . date("Ymd") . '.zip';
      $this->destino = $this->basepath . DIRECTORY_SEPARATOR . $this->file;
      $zip = new \ZipArchive();

      if ($zip->open($this->destino, \ZipArchive::CREATE) !== TRUE) {
         echo json_encode(array('success' => false, 'mensaje' => "No se puede escribir el archivo " . $this->destino));
      } else {
         $zip->open($this->destino, \ZipArchive::CREATE);
         self::folderToZip($this->basepath, $zip, strlen("$this->basepath/"));
         $zip->close();

         $this->template = false;
         header('Content-Type: application/json');
         if (file_exists($this->destino)) {
            /**/
            $file = str_replace('backups/archivos/', '', $this->file);
            echo json_encode(array('success' => true, 'mensaje' => "Backup de archivos realizado correctamente: " . $file));
         } else {
            echo json_encode(array('success' => false, 'mensaje' => "Backup de archivos no realizado!"));
         }
      }
   }

   /**
    * Restaura una copia de seguridad de los archivos indicado por el usuario
    */
   private function restore_fs() {
      $archivo = realpath(\filter_input(INPUT_POST, 'restore_file'));
      if (file_exists($archivo)) {
         // Es necesario eliminar algo antes de restaurar??
         $zip = new ZipArchive;
         if ($zip->open($archivo) === TRUE) {
            // Listamos todos los archivos del zip
            $filesOnZip = array();
            for ($idx = 0; $idx < $zip->numFiles; $idx++) {
               $filesOnZip[] = $zip->getNameIndex($idx);
            }
            // Lista de ficheros que no restauraremos
            $excludeItems = array('config.php', '.htaccess');
            // De los archivos en el zip, excluímos  los indicados
            foreach ($excludeItems as $excludeFile) {
               $pos = array_search($excludeFile, $filesOnZip);
               if ($pos) {
                  unset($filesOnZip[$pos]);
               }
            }
            // Restauramos en la ruta
            $zip->extractTo($this->basepath, $filesOnZip);
            $zip->close();

            $this->restore_tmp();

            $this->new_message('¡Backup de archivos de restaurado con exito!');
         } else {
            $this->new_error_msg('Ocurrió un error al querer restaurar el backup de archivos');
         }
      } else {
         $this->new_error_msg('¡No se indicó un backup de archivos para realizar la restauración!');
      }
   }

   /// restaura los archivos importantes del tmp del backup
   private function restore_tmp() {
      foreach (scandir(getcwd() . '/tmp') as $f) {
         if ($f . '/' != FS_TMP_NAME AND $f != '.' AND $f != '..' AND is_dir(getcwd() . '/tmp/' . $f)) {
            copy(getcwd() . '/tmp/' . $f . '/config2.ini', getcwd() . '/tmp/' . FS_TMP_NAME . 'config2.ini');
            copy(getcwd() . '/tmp/' . $f . '/enabled_plugins.list', getcwd() . '/tmp/' . FS_TMP_NAME . 'enabled_plugins.list');
            break;
         }
      }

      /// borramos los archivos php del directorio tmp
      foreach (scandir(getcwd() . '/tmp/' . FS_TMP_NAME) as $f) {
         if (substr($f, -4) == '.php') {
            unlink('tmp/' . FS_TMP_NAME . $f);
         }
      }

      $this->cache->clean();
   }

   /**
    * Borra el archivo indicado por el usuario
    */
   private function delete_file() {
      $archivo = realpath(\filter_input(INPUT_POST, 'delete_file'));
      if (file_exists($archivo)) {
         if (is_dir($archivo)) {
            $this->new_error_msg('No se puede eliminar ' . $archivo . ' porque es un directorio!');
         } else {
            if (unlink($archivo)) {
               $this->new_message('Archivo ' . $archivo . ' eliminado con exito!');
            } else {
               $this->new_error_msg('Ocurrió un error al intentar eliminar el archivo ' . $archivo);
            }
         }
      } else {
         $this->new_error_msg('El archivo ' . $archivo . ' no existe!');
      }
   }

   /**
    * Comprobar si la instalación tiene definidas en config.php:
    *    FS_DISABLE_CONFIGURE_BACKUP
    *    FS_DISABLE_DELETE_BACKUP
    *    FS_DISABLE_DOWNLOAD_BACKUP
    *    FS_DISABLE_UPLOAD_BACKUP
    * Da igual su valor, lo que se quiere es saber si están o no definidas para 
    * avisar al usuario y que las añada como TRUE o FALSE
    */
   private function check_flags() {
      $this->disable_configure_backups = FALSE;
      $this->disable_delete_backups = FALSE;
      $this->disable_download_backups = FALSE;
      $this->disable_upload_backups = FALSE;

      if (defined('FS_DISABLE_CONFIGURE_BACKUP')) {
         $this->disable_configure_backups = FS_DISABLE_CONFIGURE_BACKUP;
      }

      if (defined('FS_DISABLE_DELETE_BACKUP')) {
         $this->disable_delete_backups = FS_DISABLE_DELETE_BACKUP;
      }

      if (defined('FS_DISABLE_DOWNLOAD_BACKUP')) {
         $this->disable_download_backups = FS_DISABLE_DOWNLOAD_BACKUP;
      }

      if (defined('FS_DISABLE_UPLOAD_BACKUP')) {
         $this->disable_upload_backups = FS_DISABLE_UPLOAD_BACKUP;
      }
   }

   private function clean_cache() {
      /// borramos los archivos php del directorio tmp
      foreach (scandir(getcwd() . '/tmp/' . FS_TMP_NAME) as $f) {
         if (substr($f, -4) == '.php') {
            unlink('tmp/' . FS_TMP_NAME . $f);
         }
      }

      if ($this->cache->clean()) {
         $this->new_message("Cache limpiada correctamente.");
      }
   }

}
