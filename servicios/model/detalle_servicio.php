<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2014  Francisco Javier Trujillo   javier.trujillo.jimenez@gmail.com
 * Copyright (C) 2014-2015  Carlos Garcia Gomez         neorazorx@gmail.com
 * Copyright (C) 2015  Luis Miguel Pérez Romero  luismipr@gmail.com
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

class detalle_servicio extends fs_model
{

    public $id;
    public $descripcion;
    public $idservicio;
    public $fecha;
    public $nick;
    public $hora;

    public function __construct($s = FALSE)
    {
        parent::__construct('detalles_servicios');
        if ($s) {
            $this->id = intval($s['id']);
            $this->descripcion = $s['descripcion'];
            $this->idservicio = intval($s['idservicio']);
            $this->fecha = date('d-m-Y', strtotime($s['fecha']));
            $this->nick = $s['nick'];
            $this->hora = date('H:i:s', strtotime($s['hora']));
        } else {
            $this->id = NULL;
            $this->descripcion = '';
            $this->idservicio = NULL;
            $this->fecha = date('d-m-Y');
            $this->nick = NULL;
            $this->hora = Date('H:i:s');
        }
    }

    public function install()
    {
        return '';
    }

    public function get($id)
    {
        $data = $this->db->select("SELECT * FROM detalles_servicios WHERE id = " . $this->var2str($id) . ";");
        if ($data) {
            return new detalle_servicio($data[0]);
        } else
            return FALSE;
    }

    public function exists()
    {
        if (is_null($this->id)) {
            return FALSE;
        } else {
            return $this->db->select("SELECT * FROM detalles_servicios WHERE id = " . $this->var2str($this->idservicio) . ";");
        }
    }

    public function save()
    {
        $this->descripcion = $this->no_html($this->descripcion);

        if ($this->exists()) {
            $sql = "UPDATE detalles_servicios SET descripcion = " . $this->var2str($this->descripcion) .
                ", fecha = " . $this->var2str($this->fecha) .
                ", hora = " . $this->var2str($this->hora) .
                ", idservicio = " . $this->var2str($this->idservicio) .
                ", nick = " . $this->var2str($this->nick) .
                " WHERE id = " . $this->var2str($this->id) . ";";

            return $this->db->exec($sql);
        } else {
            $sql = "INSERT INTO detalles_servicios (descripcion,fecha,hora,idservicio,nick) VALUES (" .
                $this->var2str($this->descripcion) . "," .
                $this->var2str($this->fecha) . "," .
                $this->var2str($this->hora) . "," .
                $this->var2str($this->idservicio) . "," .
                $this->var2str($this->nick) . ");";

            if ($this->db->exec($sql)) {
                $this->id = $this->db->lastval();
                return TRUE;
            } else
                return FALSE;
        }
    }

    public function delete()
    {
        return $this->db->exec("DELETE FROM detalles_servicios WHERE id = " . $this->var2str($this->id) . ";");
    }

    public function all()
    {
        $detalleslist = array();

        $sql = "SELECT d.id,d.descripcion,d.idservicio,d.fecha,d.hora,d.nick FROM servicioscli s, detalles_servicios d" .
            " WHERE d.idservicio = s.idservicio ORDER BY d.fecha ASC, d.id ASC;";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d)
                $detalleslist[] = new detalle_servicio($d);
        }

        return $detalleslist;
    }

    public function all_from_servicio($idservicio)
    {
        $detalleslist = array();

        $sql = "SELECT d.id,d.descripcion,d.idservicio,d.fecha,d.hora,d.nick FROM servicioscli s, detalles_servicios d" .
            " WHERE d.idservicio = s.idservicio AND d.idservicio = " . $this->var2str($idservicio) . " ORDER BY d.fecha DESC, d.id DESC;";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d)
                $detalleslist[] = new detalle_servicio($d);
        }

        return $detalleslist;
    }

    public function show_hora_detalle($s = TRUE)
    {
        if ($s) {
            return Date('H:i:s', strtotime($this->hora));
        } else
            return Date('H:i', strtotime($this->hora));
    }
}
