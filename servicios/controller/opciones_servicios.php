<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017    Carlos Garcia Gomez        neorazorx@gmail.com
 * Copyright (C) 2015         Luis Miguel Pérez Romero   luismipr@gmail.com
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
 * Description of opciones_servicios
 *
 * @author carlos
 */
class opciones_servicios extends fs_controller
{

    public $allow_delete;
    public $estado;
    public $maps_api_key;
    public $servicios_setup;
    public $st;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Opciones', 'Servicios', FALSE, FALSE);
    }

    protected function private_core()
    {
        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);

        $this->check_menu();
        $this->share_extensions();

        $this->estado = new estado_servicio();

        /// cargamos la configuración
        $fsvar = new fs_var();
        $this->servicios_setup = $fsvar->array_get(
            array(
            'servicios_diasfin' => 10,
            'servicios_material' => 0,
            'servicios_mostrar_material' => 0,
              'servicios_marca' => 0,
            'servicios_mostrar_marca' => 0,
                'servicios_modelo' => 0,
            'servicios_mostrar_modelo' => 0,
                'servicios_serial' => 0,
            'servicios_mostrar_serial' => 0,
            'servicios_material_estado' => 0,
            'servicios_mostrar_material_estado' => 0,
            'servicios_accesorios' => 0,
            'servicios_mostrar_accesorios' => 0,
            'servicios_descripcion' => 0,
            'servicios_mostrar_descripcion' => 0,
            'servicios_solucion' => 0,
            'servicios_mostrar_solucion' => 0,
            'servicios_fechafin' => 0,
            'servicios_mostrar_fechafin' => 0,
            'servicios_fechainicio' => 0,
            'servicios_mostrar_fechainicio' => 0,
            'servicios_mostrar_garantia' => 0,
            'servicios_garantia' => 0,
            'servicios_linea' => 0,
            'servicios_linea1' => 0,
            'servicios_material_linea' => 0,
               'servicios_marca_linea' => 0,
                    'servicios_modelo_linea' => 0,
                         'servicios_serail_linea' => 0,
            'servicios_material_estado_linea' => 0,
            'servicios_accesorios_linea' => 0,
            'servicios_descripcion_linea' => 0,
            'servicios_solucion_linea' => 0,
            'servicios_fechainicio_linea' => 0,
            'servicios_fechafin_linea' => 0,
            'servicios_garantia_linea' => 0,
            'servicios_condiciones' => "Condiciones del deposito:\nLos presupuestos realizados tienen una" .
            " validez de 15 días.\nUna vez avisado al cliente para que recoja el producto este dispondrá" .
            " de un plazo máximo de 2 meses para recogerlo, de no ser así y no haber aviso por parte del" .
            " cliente se empezará a cobrar 1 euro al día por gastos de almacenaje.\nLos accesorios y" .
            " productos externos al equipo no especificados en este documento no podrán ser reclamados en" .
            " caso de disconformidad con el técnico.",
            'st_servicio' => "Servicio",
            'st_servicios' => "Servicios",
            'st_material' => "Material",
                  'st_marca' => "Marca",
                   'st_modelo' => "Modelo",
                    'st_serial' => "Serial",
            'st_material_estado' => "Estado del material entregado",
            'st_accesorios' => "Accesorios que entrega",
            'st_descripcion' => "Descripción de la averia",
            'st_solucion' => "Solución",
            'st_fechainicio' => "Fecha de inicio",
            'st_fechafin' => "Fecha de finalización",
            'st_garantia' => "Garantía",
            'cal_inicio' => "09:00",
            'cal_fin' => "20:00",
            'cal_intervalo' => "30",
            'usar_direccion' => 0
            ), FALSE
        );

        if (isset($_POST['servicios_setup'])) {
            $this->servicios_setup['servicios_diasfin'] = intval($_POST['diasfin']);
            $this->servicios_setup['servicios_material'] = ( isset($_POST['servicios_material']) ? 1 : 0 );
               $this->servicios_setup['servicios_marca'] = ( isset($_POST['servicios_marca']) ? 1 : 0 );
              $this->servicios_setup['servicios_modelo'] = ( isset($_POST['servicios_modelo']) ? 1 : 0 );
                $this->servicios_setup['servicios_serial'] = ( isset($_POST['servicios_serial']) ? 1 : 0 );   
            $this->servicios_setup['servicios_material_estado'] = ( isset($_POST['servicios_material_estado']) ? 1 : 0 );
            $this->servicios_setup['servicios_accesorios'] = ( isset($_POST['servicios_accesorios']) ? 1 : 0 );
            $this->servicios_setup['servicios_descripcion'] = ( isset($_POST['servicios_descripcion']) ? 1 : 0 );
            $this->servicios_setup['servicios_solucion'] = ( isset($_POST['servicios_solucion']) ? 1 : 0 );
            $this->servicios_setup['servicios_fechafin'] = ( isset($_POST['servicios_fechafin']) ? 1 : 0 );
            $this->servicios_setup['servicios_fechainicio'] = ( isset($_POST['servicios_fechainicio']) ? 1 : 0 );
            $this->servicios_setup['servicios_garantia'] = ( isset($_POST['servicios_garantia']) ? 1 : 0 );
            $this->servicios_setup['servicios_mostrar_material'] = ( isset($_POST['servicios_mostrar_material']) ? 1 : 0 );
             $this->servicios_setup['servicios_mostrar_marca'] = ( isset($_POST['servicios_mostrar_marca']) ? 1 : 0 );
              $this->servicios_setup['servicios_mostrar_modelo'] = ( isset($_POST['servicios_mostrar_modelo']) ? 1 : 0 );
               $this->servicios_setup['servicios_mostrar_serial'] = ( isset($_POST['servicios_mostrar_serial']) ? 1 : 0 );
            $this->servicios_setup['servicios_mostrar_material_estado'] = ( isset($_POST['servicios_mostrar_material_estado']) ? 1 : 0 );
            $this->servicios_setup['servicios_mostrar_accesorios'] = ( isset($_POST['servicios_mostrar_accesorios']) ? 1 : 0 );
            $this->servicios_setup['servicios_mostrar_descripcion'] = ( isset($_POST['servicios_mostrar_descripcion']) ? 1 : 0 );
            $this->servicios_setup['servicios_mostrar_solucion'] = ( isset($_POST['servicios_mostrar_solucion']) ? 1 : 0 );
            $this->servicios_setup['servicios_mostrar_fechafin'] = ( isset($_POST['servicios_mostrar_fechafin']) ? 1 : 0 );
            $this->servicios_setup['servicios_mostrar_fechainicio'] = ( isset($_POST['servicios_mostrar_fechainicio']) ? 1 : 0 );
            $this->servicios_setup['servicios_mostrar_garantia'] = ( isset($_POST['servicios_mostrar_garantia']) ? 1 : 0 );
            $this->servicios_setup['servicios_condiciones'] = $fsvar->no_html($_POST['condiciones']);
            $this->servicios_setup['servicios_linea'] = ( isset($_POST['servicios_linea']) ? 1 : 0 );
            $this->servicios_setup['servicios_linea1'] = ( isset($_POST['servicios_linea1']) ? 1 : 0 );
            $this->servicios_setup['servicios_material_linea'] = ( isset($_POST['servicios_material_linea']) ? 1 : 0 );
              $this->servicios_setup['servicios_marca_linea'] = ( isset($_POST['servicios_marca_linea']) ? 1 : 0 );
                $this->servicios_setup['servicios_modelo_linea'] = ( isset($_POST['servicios_modelo_linea']) ? 1 : 0 );
                  $this->servicios_setup['servicios_serial_linea'] = ( isset($_POST['servicios_serial_linea']) ? 1 : 0 );
            $this->servicios_setup['servicios_material_estado_linea'] = ( isset($_POST['servicios_material_estado_linea']) ? 1 : 0 );
            $this->servicios_setup['servicios_accesorios_linea'] = ( isset($_POST['servicios_accesorios_linea']) ? 1 : 0 );
            $this->servicios_setup['servicios_descripcion_linea'] = ( isset($_POST['servicios_descripcion_linea']) ? 1 : 0 );
            $this->servicios_setup['servicios_solucion_linea'] = ( isset($_POST['servicios_solucion_linea']) ? 1 : 0 );
            $this->servicios_setup['servicios_fechainicio_linea'] = ( isset($_POST['servicios_fechainicio_linea']) ? 1 : 0 );
            $this->servicios_setup['servicios_fechafin_linea'] = ( isset($_POST['servicios_fechafin_linea']) ? 1 : 0 );
            $this->servicios_setup['servicios_garantia_linea'] = ( isset($_POST['servicios_garantia_linea']) ? 1 : 0 );
            $this->servicios_setup['st_servicio'] = $_POST['st_servicio'];
            $this->servicios_setup['st_servicios'] = $_POST['st_servicios'];
            $this->servicios_setup['st_material'] = $_POST['st_material'];
              $this->servicios_setup['st_marca'] = $_POST['st_marca'];
                   $this->servicios_setup['st_modelo'] = $_POST['st_modelo'];
                             $this->servicios_setup['st_serial'] = $_POST['st_serial'];
            $this->servicios_setup['st_material_estado'] = $_POST['st_material_estado'];
            $this->servicios_setup['st_accesorios'] = $_POST['st_accesorios'];
            $this->servicios_setup['st_descripcion'] = $_POST['st_descripcion'];
            $this->servicios_setup['st_solucion'] = $_POST['st_solucion'];
            $this->servicios_setup['st_fechainicio'] = $_POST['st_fechainicio'];
            $this->servicios_setup['st_fechafin'] = $_POST['st_fechafin'];
            $this->servicios_setup['st_garantia'] = $_POST['st_garantia'];
            $this->servicios_setup['cal_inicio'] = $_POST['cal_inicio'];
            $this->servicios_setup['cal_fin'] = $_POST['cal_fin'];
            $this->servicios_setup['cal_intervalo'] = $_POST['cal_intervalo'];
            $this->servicios_setup['usar_direccion'] = ( isset($_POST['usar_direccion']) ? 1 : 0 );

            if ($fsvar->array_save($this->servicios_setup)) {
                $this->new_message('Datos guardados correctamente.');
            } else
                $this->new_error_msg('Error al guardar los datos.');
        }
        else if (isset($_GET['delete_estado'])) {
            $estado = $this->estado->get($_GET['delete_estado']);
            if ($estado) {
                if ($estado->delete()) {
                    $this->new_message('Estado eliminado correctamente.');
                } else
                    $this->new_error_msg('Error al eliminar el estado.');
            } else
                $this->new_error_msg('Estado no encontrado.');
        }
        else if (isset($_POST['id_estado'])) {
            $estado = $this->estado->get($_POST['id_estado']);
            if (!$estado) {
                $estado = new estado_servicio();
                $estado->id = intval($_POST['id_estado']);
            }
            $estado->descripcion = $_POST['descripcion'];
            $estado->color = $_POST['color'];
            $estado->activo = isset($_POST['activo']);
            $estado->albaran = isset($_POST['albaran']);

            if ($estado->save()) {
                $this->new_message('Estado guardado correctamente.');
            } else
                $this->new_error_msg('Error al guardar el estado.');
        }
    }

    private function share_extensions()
    {
        $fsext = new fs_extension();
        $fsext->name = 'opciones_servicios';
        $fsext->from = __CLASS__;
        $fsext->to = 'ventas_servicios';
        $fsext->type = 'button';
        $fsext->text = '<span class="glyphicon glyphicon-wrench" aria-hidden="true">'
            . '</span><span class="hidden-xs">&nbsp; Opciones</span>';
        $fsext->save();
    }

    private function check_menu()
    {
        if (!$this->page->get('ventas_servicios')) {
            if (file_exists(__DIR__)) {
                /// activamos las páginas del plugin
                foreach (scandir(__DIR__) as $f) {
                    if ($f != '.' AND $f != '..' AND is_string($f) AND strlen($f) > 4 AND ! is_dir($f) AND $f != __CLASS__ . '.php') {
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
}
