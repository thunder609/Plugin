<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017    Carlos Garcia Gomez         neorazorx@gmail.com
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
 * Description of editar_contrato_servicio
 *
 * @author carlos
 */
class editar_contrato_servicio extends fs_controller
{

    public $agente;
    public $allow_delete;
    public $cliente;
    public $cliente_s;
    public $contrato;
    public $forma_pago;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Editar contrato', 'ventas', FALSE, FALSE);
    }

    protected function private_core()
    {
        /// ¿El usuario tiene permiso para eliminar en esta página?
        $this->allow_delete = $this->user->allow_delete_on(__CLASS__);

        $this->agente = new agente();
        $this->cliente = new cliente();
        $this->cliente_s = FALSE;
        $this->forma_pago = new forma_pago();

        $contrato = new contrato_servicio();
        $this->contrato = FALSE;
        if (isset($_REQUEST['id'])) {
            $this->contrato = $contrato->get($_REQUEST['id']);
        }

        if ($this->contrato) {
            if (isset($_POST['codcliente'])) {
                $this->contrato->codcliente = $_POST['codcliente'];
                $this->contrato->codagente = $_POST['codagente'];
                $this->contrato->fecha_alta = $_POST['fecha_alta'];

                $this->contrato->fecha_renovacion = NULL;
                if ($_POST['fecha_renovacion']) {
                    $this->contrato->fecha_renovacion = $_POST['fecha_renovacion'];
                }

                $this->contrato->importe_anual = floatval($_POST['importe_anual']);
                $this->contrato->codpago = $_POST['codpago'];

                $this->contrato->periodo = NULL;
                if ($_POST['periodo'] == '') {
                    $this->contrato->fsiguiente_servicio = NULL;
                    if ($_POST['fsiguiente_servicio'] != '') {
                        $this->contrato->fsiguiente_servicio = $_POST['fsiguiente_servicio'];
                    }
                } else {
                    $this->contrato->periodo = $_POST['periodo'];
                    $this->contrato->fsiguiente_servicio = date('d-m-Y', strtotime($this->contrato->periodo));
                }

                $this->contrato->observaciones = $_POST['observaciones'];

                if ($this->contrato->save()) {
                    $this->new_message('Datos guardados correctamente.');
                } else {
                    $this->new_error_msg('Error al guardar los datos.');
                }
            }

            $this->cliente_s = $this->cliente->get($this->contrato->codcliente);
            $this->comprobar_upload();
        } else {
            $this->new_error_msg('Contrato no encontrado.');
        }
    }

    public function periodos()
    {
        $peridos = array(
            '+1day' => 'día',
            '+2days' => '2 días',
            '+3days' => '3 días',
            '+4days' => '4 días',
            '+5days' => '5 días',
            '+6days' => '6 días',
            '+7days' => '7 días',
            '+10days' => '10 días',
            '+15days' => '15 días',
            '+1month' => 'mes',
            '+2months' => '2 meses',
            '+3months' => '3 meses',
            '+6months' => '6 meses',
        );

        return $peridos;
    }

    private function comprobar_upload()
    {
        if (!file_exists('tmp/' . FS_TMP_NAME . 'contratosservicios')) {
            mkdir('tmp/' . FS_TMP_NAME . 'contratosservicios');
        }

        if (file_exists('tmp/' . FS_TMP_NAME . 'contratosservicios/' . $this->contrato->idcontrato)) {
            foreach ($this->get_documentos() as $doc) {
                if (isset($_GET['deletef'])) {
                    if ($_GET['deletef'] == $doc['name']) {
                        unlink($doc['fullname']);
                        $this->new_message('Archivo ' . $doc['name'] . ' eliminado.');
                    }
                }
            }
        } else {
            mkdir('tmp/' . FS_TMP_NAME . 'contratosservicios/' . $this->contrato->idcontrato);
        }

        if (isset($_FILES['fcontrato'])) {
            if (is_uploaded_file($_FILES['fcontrato']['tmp_name'])) {
                copy($_FILES['fcontrato']['tmp_name'], 'tmp/' . FS_TMP_NAME . 'contratosservicios/' . $this->contrato->idcontrato . '/' . $_FILES['fcontrato']['name']);
            }
        }
    }

    public function get_documentos()
    {
        $doclist = array();
        $folder = 'tmp/' . FS_TMP_NAME . 'contratosservicios/' . $this->contrato->idcontrato;

        if (file_exists($folder)) {
            foreach (scandir($folder) as $f) {
                if ($f != '.' AND $f != '..') {
                    $doclist[] = array(
                        'name' => (string) $f,
                        'fullname' => $folder . '/' . $f,
                        'filesize' => $this->human_filesize(filesize(getcwd() . '/' . $folder . '/' . $f)),
                        'date' => date("d-m-Y H:i:s", filemtime(getcwd() . '/' . $folder . '/' . $f))
                    );
                }
            }
        }

        return $doclist;
    }

    private function human_filesize($bytes, $decimals = 2)
    {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }
}
