<?php

/**
 * This file is part of FacturaScripts
 * Copyright (C) 2016  David Ruiz Eguizábal       davidruegui@gmail.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require_model('amortizacion.php');
require_model('asiento.php');

class linea_amortizacion extends fs_model
{
    /**
     * @var int
     */
    public $ano;
    /**
     * @var int
     */
    public $contabilizada;
    /**
     * @var int
     */
    public $cantidad;
    /**
     * @var false|string
     */
    public $fecha;
    /**
     * @var null
     */
    public $id_amortizacion;
    /**
     * @var null
     */
    public $id_asiento;
    /**
     * @var null
     */
    public $id_linea;
    /**
     * @var null
     */
    public $periodo;

    /**
     * linea_amortizacion constructor.
     * @param bool $t
     */
    public function __construct($t = false)
    {
        parent::__construct('lineasamortizaciones');
        if ($t) {
            $this->ano = $t['ano'];
            $this->contabilizada = $this->str2bool($t['contabilizada']);
            $this->cantidad = $t['cantidad'];
            $this->fecha = Date('d-m-Y', strtotime($t['fecha']));
            $this->id_amortizacion = $t['idamortizacion'];
            $this->id_asiento = $t['idasiento'];
            $this->id_linea = $t['idlinea'];
            $this->periodo = $t['periodo'];
        } else {
            $this->ano = 0;
            $this->contabilizada = 0;
            $this->cantidad = 0;
            $this->fecha = Date('d-m-Y');
            $this->id_amortizacion = null;
            $this->id_asiento = null;
            $this->id_linea = null;
            $this->periodo = null;
        }
    }

    /**
     * @return bool
     */
    public function exists()
    {
        if (is_null($this->id_linea)) {
            return false;
        } else {
            return $this->db->select("SELECT * FROM lineasamortizaciones WHERE idlinea = " . $this->var2str($this->id_linea) . ";");
        }
    }

    /**
     * @return bool
     */
    public function save()
    {
        if ($this->exists()) {
            $sql = "UPDATE lineasamortizaciones SET 
                 fecha = " . $this->var2str($this->fecha) . ", 
                 cantidad = " . $this->var2str($this->cantidad) . "
                 WHERE idlinea = " . $this->var2str($this->id_linea) . ";";
            return $this->db->exec($sql);
        } else {
            $sql = "INSERT INTO lineasamortizaciones (ano,cantidad,fecha,idamortizacion,periodo) VALUES ("
                . $this->var2str($this->ano) . ","
                . $this->var2str($this->cantidad) . ","
                . $this->var2str($this->fecha) . ","
                . $this->var2str($this->id_amortizacion) . ","
                . $this->var2str($this->periodo) . ");";

            if ($this->db->exec($sql)) {
                $this->id_linea = $this->db->lastval();
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        return $this->db->exec("DELETE FROM lineasamortizaciones WHERE idlinea = " . $this->var2str($this->id_linea) . ";");
    }

    /**
     * @return mixed
     */
    public function delete_by_amor()
    {
        return $this->db->exec("DELETE FROM lineasamortizaciones WHERE idamortizacion = " . $this->var2str($this->id_amortizacion) . ";");
    }
    
    /**
     * @return array
     */
    public function all()
    {
        $lista = array();

        $sql = $this->db->select("SELECT * FROM lineasamortizaciones ORDER BY ano;");
        if ($sql) {
            foreach ($sql as $d) {
                $lista[] = new linea_amortizacion ($d);
            }
        }
        return $lista;
    }

    /**
     * @return array
     */
    public function this_year()
    {
        $lista = array();
        $ano_fiscal = date('Y');

        $sql = $this->db->select("SELECT * FROM lineasamortizaciones WHERE ano<=" . $this->var2str($ano_fiscal) . " AND contabilizada = '0' ORDER BY ano;");
        if ($sql) {
            foreach ($sql as $d) {
                $lista[] = new linea_amortizacion ($d);
            }
        }
        return $lista;
    }

    /**
     * @param $id_linea
     * @return array
     */
    public function get_by_id_linea($id_linea)
    {
        $lista = array();

        $sql = $this->db->select("SELECT * FROM lineasamortizaciones WHERE idlinea=" . $this->var2str($id_linea) . ";");
        if ($sql) {
            return new \linea_amortizacion($sql[0]);
        } else {
            return false;
        }
    }
    
    /**
     * @param $id_linea
     * @param $ano
     * @param $periodo
     * @return array
     */
    public function get_by_id_amor_ano_periodo($id_amor,$ano,$periodo)
    {
        $lista = array();

        $sql = $this->db->select("SELECT * FROM lineasamortizaciones WHERE 
                idamortizacion=" . $this->var2str($id_amor) . " AND 
                ano=" . $this->var2str($ano) . " AND 
                periodo=" . $this->var2str($periodo) . ";");
        if ($sql) {
            return new \linea_amortizacion($sql[0]);
        } else {
            return false;
        }
    }
    
    /**
     * @param $id_amor
     * @param $fecha_inicial
     * @param $fecha_final
     * @return array
     */
    public function get_by_date_and_amort($id_amor,$fecha_inicial,$fecha_final)
    {
        $lista = array();

        $sql = $this->db->select("SELECT * FROM lineasamortizaciones
                WHERE idamortizacion=" . $this->var2str($id_amor) . " AND fecha >= " . $this->var2str($fecha_inicial) . " AND fecha <= " . $this->var2str($fecha_final) . ";");
        if ($sql) {
            foreach ($sql as $d) {
                $lista[] = new linea_amortizacion ($d);
            }
        }
        return $lista;
    }
    
    /**
     * @param $id_amor
     * @return array
     */
    public function get_by_amortizacion($id_amor)
    {
        $lista = array();

        $sql = $this->db->select("SELECT * FROM lineasamortizaciones WHERE idamortizacion=" . $this->var2str($id_amor) . " ORDER BY fecha;");
        if ($sql) {
            foreach ($sql as $d) {
                $lista[] = new linea_amortizacion ($d);
            }
        }
        return $lista;
    }

    /**
     * @param $id
     * @param $id_asiento
     * @return mixed
     */
    public function count($id, $id_asiento)
    {
        return $this->db->exec("UPDATE lineasamortizaciones SET
                contabilizada = TRUE, 
                idasiento = " . $this->var2str($id_asiento) . "
                WHERE idlinea = " . $this->var2str($id) . ";");
    }
    
    /**
     * @param $id
     * @return mixed
     */ 
    public function discount($id)
    {
        return $this->db->exec("UPDATE lineasamortizaciones SET
                contabilizada = FALSE, 
                idasiento = NULL
                WHERE idlinea = " . $this->var2str($id) . ";");
    }

    /**
     * @param $id
     * @return mixed
     */
    public function sale($id)
    {
        return $this->db->exec("UPDATE lineasamortizaciones SET contabilizada = TRUE WHERE idamortizacion = " . $this->var2str($id) . ";");
    }

    /**
     * @param $id_amor
     * @param $ano
     * @return bool|linea_amortizacion
     */
    public function get_to_count($id_amor, $ano)
    {
        $sql = $this->db->select("SELECT * FROM lineasamortizaciones WHERE idamortizacion = " . $this->var2str($id_amor) . " AND ano = " . $this->var2str($ano) . ";");
        if ($sql) {
            return new \linea_amortizacion($sql[0]);
        } else {
            return false;
        }
    }
        
    /**
     * @param $fecha
     * @param $ano_antes
     * @return array
     */
    public function slope($fecha,$ano_antes)
    {
        $lista = array();

        $sql = $this->db->select("SELECT * FROM lineasamortizaciones WHERE 
                                 fecha >= " . $this->var2str($ano_antes) . " AND 
                                 fecha <= " . $this->var2str($fecha) . " AND 
                                 contabilizada = FALSE
                                ORDER BY fecha;");
        if ($sql) {
            foreach ($sql as $d) {
                $lista[] = new linea_amortizacion ($d);
            }
        }
        return $lista;
    }
    
    public function today()
    {
        $lista = array();
        $fecha = date('Y-m-d');
        $tres_meses = date('Y-m-d', strtotime($fecha . '- 3 month'));
        
        $sql = $this->db->select("SELECT * FROM lineasamortizaciones WHERE 
                                 fecha >= " . $this->var2str($tres_meses) . " AND 
                                 fecha <= " . $this->var2str($fecha) . " AND 
                                 contabilizada = FALSE
                                ORDER BY fecha;");
        if ($sql) {
            foreach ($sql as $d) {
                $lista[] = new linea_amortizacion ($d);
            }
        }
        return $lista;
    }

    /**
     * @param $id_linea
     */
    public function eliminar_asiento($id_linea)
    {
        $lineas_amortizaciones = new linea_amortizacion();
        $asiento = new asiento();
        $linea = $lineas_amortizaciones->get_by_id_linea($id_linea);
        $asiento_amortizacion = $asiento->get($linea->id_asiento);
        
        if (!$asiento_amortizacion) {
            $lineas_amortizaciones->discount($id_linea);
            return TRUE;
        } elseif ($asiento_amortizacion->delete()) {
            $lineas_amortizaciones->discount($id_linea);
            return TRUE;
        } else {
            $this->new_message('No se ha podido eliminar el asiento');
            return FALSE;
        }
    }
    
    /**
     * @return string
     */
    protected function install()
    {
        return '';
    }

}