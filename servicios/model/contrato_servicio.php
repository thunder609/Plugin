<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2016  Carlos Garcia Gomez         neorazorx@gmail.com
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
 * Description of contrato_servicio
 *
 * @author carlos
 */
class contrato_servicio extends fs_model
{

    public $idcontrato;
    public $codcliente;
    public $codagente;
    public $fecha_alta;
    public $fecha_renovacion;
    public $observaciones;
    public $codpago;
    public $importe_anual;
    public $periodo;
    public $fsiguiente_servicio;

    public function __construct($c = FALSE)
    {
        parent::__construct('contratoservicioscli');
        if ($c) {
            $this->idcontrato = $this->intval($c['idcontrato']);
            $this->codcliente = $c['codcliente'];
            $this->codagente = $c['codagente'];
            $this->fecha_alta = date('d-m-Y', strtotime($c['fecha_alta']));

            $this->fecha_renovacion = NULL;
            if ($c['fecha_renovacion']) {
                $this->fecha_renovacion = date('d-m-Y', strtotime($c['fecha_renovacion']));
            }

            $this->observaciones = $c['observaciones'];
            $this->codpago = $c['codpago'];
            $this->importe_anual = floatval($c['importe_anual']);
            $this->periodo = $c['periodo'];

            $this->fsiguiente_servicio = NULL;
            if ($c['fsiguiente_servicio']) {
                $this->fsiguiente_servicio = date('d-m-Y', strtotime($c['fsiguiente_servicio']));
            }
        } else {
            $this->idcontrato = NULL;
            $this->codcliente = NULL;
            $this->codagente = NULL;
            $this->fecha_alta = date('d-m-Y');
            $this->fecha_renovacion = date('d-m-Y', strtotime('+1year'));
            $this->observaciones = NULL;
            $this->codpago = NULL;
            $this->importe_anual = NULL;
            $this->periodo = NULL;
            $this->fsiguiente_servicio = NULL;
        }
    }

    protected function install()
    {
        return '';
    }

    public function url()
    {
        return 'index.php?page=editar_contrato_servicio&id=' . $this->idcontrato;
    }

    public function observaciones($len = 60)
    {
        if (mb_strlen($this->observaciones) > $len) {
            return substr($this->observaciones, 0, $len) . '...';
        } else {
            return $this->observaciones;
        }
    }

    public function caducado()
    {
        if (is_null($this->fecha_renovacion)) {
            return FALSE;
        } else {
            return ( strtotime($this->fecha_renovacion) < time() );
        }
    }

    public function get($id)
    {
        $data = $this->db->select("SELECT * FROM contratoservicioscli WHERE idcontrato = " . $this->var2str($id) . ";");
        if ($data) {
            return new contrato_servicio($data[0]);
        } else {
            return FALSE;
        }
    }

    public function exists()
    {
        if (is_null($this->idcontrato)) {
            return FALSE;
        } else {
            return $this->db->select("SELECT * FROM contratoservicioscli WHERE idcontrato = " . $this->var2str($this->idcontrato) . ";");
        }
    }

    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE contratoservicioscli SET codcliente = " . $this->var2str($this->codcliente)
                . ", codagente = " . $this->var2str($this->codagente)
                . ", fecha_alta = " . $this->var2str($this->fecha_alta)
                . ", fecha_renovacion = " . $this->var2str($this->fecha_renovacion)
                . ", observaciones = " . $this->var2str($this->observaciones)
                . ", codpago = " . $this->var2str($this->codpago)
                . ", importe_anual = " . $this->var2str($this->importe_anual)
                . ", periodo = " . $this->var2str($this->periodo)
                . ", fsiguiente_servicio = " . $this->var2str($this->fsiguiente_servicio)
                . " WHERE idcontrato = " . $this->var2str($this->idcontrato) . ";";

            return $this->db->exec($sql);
        } else {
            $sql = "INSERT INTO contratoservicioscli (codcliente,codagente,fecha_alta,"
                . "fecha_renovacion,observaciones,codpago,importe_anual,periodo,"
                . "fsiguiente_servicio) VALUES (" . $this->var2str($this->codcliente)
                . "," . $this->var2str($this->codagente)
                . "," . $this->var2str($this->fecha_alta)
                . "," . $this->var2str($this->fecha_renovacion)
                . "," . $this->var2str($this->observaciones)
                . "," . $this->var2str($this->codpago)
                . "," . $this->var2str($this->importe_anual)
                . "," . $this->var2str($this->periodo)
                . "," . $this->var2str($this->fsiguiente_servicio) . ");";

            if ($this->db->exec($sql)) {
                $this->idcontrato = $this->db->lastval();
                return TRUE;
            } else
                return FALSE;
        }
    }

    public function delete()
    {
        return $this->db->exec("DELETE FROM contratoservicioscli WHERE idcontrato = " . $this->var2str($this->idcontrato) . ";");
    }

    public function all($offset = 0, $order = 'fecha_alta DESC')
    {
        $clist = array();

        $data = $this->db->select_limit("SELECT * FROM contratoservicioscli ORDER BY " . $order, FS_ITEM_LIMIT, $offset);
        if ($data) {
            foreach ($data as $d)
                $clist[] = new contrato_servicio($d);
        }

        return $clist;
    }

    public function count()
    {
        $data = $this->db->select("SELECT COUNT(idcontrato) as total FROM contratoservicioscli;");
        if ($data) {
            return intval($data[0]['total']);
        } else {
            return 0;
        }
    }
}
