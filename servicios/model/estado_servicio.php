<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2016    Carlos Garcia Gomez         neorazorx@gmail.com
 * Copyright (C) 2015         Luis Miguel Pérez Romero  luismipr@gmail.com
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
 * Description of estado_servicio
 *
 * @author carlos
 */
class estado_servicio extends fs_model
{

    /**
     * Clave primaria.
     * @var integer
     */
    public $id;

    /**
     * Descripción del estado.
     * @var type 
     */
    public $descripcion;

    /**
     * Color asociado al estado, en formato hexadecimal,
     * de 000000 a FFFFFF
     * @var type 
     */
    public $color;

    /**
     * FALSE => los servicios asociados están terminados o inactivos.
     * @var type 
     */
    public $activo;

    /**
     * TRUE => se genera un albarán a partir del servicio.
     * @var type 
     */
    public $albaran;

    public function __construct($e = FALSE)
    {
        parent::__construct('estados_servicios');
        if ($e) {
            $this->id = $this->intval($e['id']);
            $this->descripcion = $e['descripcion'];
            $this->color = $e['color'];
            $this->activo = $this->str2bool($e['activo']);
            $this->albaran = $this->str2bool($e['albaran']);
        } else {
            $this->id = NULL;
            $this->descripcion = '';
            $this->color = '000000';
            $this->activo = TRUE;
            $this->albaran = FALSE;
        }
    }

    protected function install()
    {
        return "INSERT INTO estados_servicios (id,descripcion,activo,albaran,color) VALUES" .
            " ('1','Pendiente',TRUE,FALSE,'FFFBD9')," .
            " ('2','En proceso',TRUE,FALSE,'D9EDF7')," .
            " ('100','Terminado',FALSE,TRUE,'DFF0D8');";
    }

    public function get($id)
    {
        $data = $this->db->select("SELECT * FROM estados_servicios WHERE id = " . $this->var2str($id) . ";");
        if ($data) {
            return new estado_servicio($data[0]);
        } else
            return FALSE;
    }

    public function get_nuevo_id()
    {
        $num = 1;
        $data = $this->db->select("SELECT id FROM estados_servicios;");
        if ($data) {
            foreach ($data as $d) {
                if ($d['id'] == $num) {
                    $num++;
                }
            }
        }

        return $num;
    }

    public function exists()
    {
        if (is_null($this->id)) {
            return FALSE;
        } else
            return $this->db->select("SELECT * FROM estados_servicios WHERE id = " . $this->var2str($this->id) . ";");
    }

    public function save()
    {
        $this->descripcion = $this->no_html($this->descripcion);
        $this->color = $this->no_html($this->color);

        if ($this->exists()) {
            $sql = "UPDATE estados_servicios SET descripcion = " . $this->var2str($this->descripcion) .
                ", activo = " . $this->var2str($this->activo) .
                ", albaran = " . $this->var2str($this->albaran) .
                ", color = " . $this->var2str($this->color) .
                "  WHERE id = " . $this->var2str($this->id) . ";";
        } else {
            $sql = "INSERT INTO estados_servicios (id,descripcion,activo,albaran,color) VALUES ("
                . $this->var2str($this->id) . ","
                . $this->var2str($this->descripcion) . ","
                . $this->var2str($this->activo) . ","
                . $this->var2str($this->albaran) . ","
                . $this->var2str($this->color) . ");";
        }

        return $this->db->exec($sql);
    }

    public function delete()
    {
        return $this->db->exec("DELETE FROM estados_servicios WHERE id = " . $this->var2str($this->id) . ";");
    }

    public function all()
    {
        $elist = array();

        $data = $this->db->select("SELECT * FROM estados_servicios ORDER BY id ASC;");
        if ($data) {
            foreach ($data as $d)
                $elist[] = new estado_servicio($d);
        }

        return $elist;
    }

    public function tiene_servicios($id)
    {
        $tiene = FALSE;

        if ($this->db->table_exists('servicioscli')) {
            $data = $this->db->select("SELECT * FROM servicioscli WHERE idestado = " . $id . "");
            if ($data) {
                $tiene = TRUE;
            }
        }

        return $tiene;
    }
}
