<?php

/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017  Albert Dilmé
 * Copyright (C) 2017  Francesc Pineda Segarra <shawe.ewahs@gmail.com>
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
 * Clase beneficio
 */
class beneficio extends fs_model
{
    /**
     * Id autoincremental del registro clave primaria
     * @var null
     */
    public $id;
    /**
     * Código del presupuesto
     * @var string
     */
    public $codigo_pre;
    /**
     * Código del pedido
     * @var string
     */
    public $codigo_ped;
    /**
     * Código del albarán
     * @var string
     */
    public $codigo_alb;
    /**
     * Código de la factura
     * @var string
     */
    public $codigo_fac;
    /**
     * Total neto del documento
     * @var float
     */
    public $precioneto;
    /**
     * Total coste del documento
     * @var float
     */
    public $preciocoste;
    /**
     * Total beneficio del documento
     * @var float
     */
    public $beneficio;

    /**
     * beneficio constructor.
     *
     * @param bool|beneficio $d
     */
    public function __construct($d = false)
    {
        //si no posa els valors a null ell solet posar-ho aixi--> $this->codigo_pre = isset($d['codigo_pre'])? $d['codigo_pre'] : null;
        parent::__construct('beneficios');
        if ($d) {
            $this->id = $d['id'];
            $this->codigo_pre = $d['codigo_pre'];
            $this->codigo_ped = $d['codigo_ped'];
            $this->codigo_alb = $d['codigo_alb'];
            $this->codigo_fac = $d['codigo_fac'];
            $this->precioneto = (float)$d['precioneto'];
            $this->preciocoste = (float)$d['preciocoste'];
            $this->beneficio = (float)$d['beneficio'];
        } else {
            /// valores predeterminados
            $this->id = null;
            $this->codigo_pre = null;
            $this->codigo_ped = null;
            $this->codigo_alb = null;
            $this->codigo_fac = null;
            $this->precioneto = 0;
            $this->preciocoste = 0;
            $this->beneficio = 0;
        }
    }

    /**
     *
     * @return string
     */
    public function install()
    {
        return '';
    }

    /**
     * Devuelve si el registro existe o no en la tabla
     *
     * @return bool
     */
    public function exists()
    {
        if ($this->id !== null) {
            $sql = 'SELECT * FROM beneficios WHERE id = ' . $this->var2str($this->id) . ';';

            return $this->db->select($sql);
        }
        return false;
    }

    /**
     * Comprueba si el registro existe, si existe, lo actualiza y sino lo inserta
     *
     * @return bool
     */
    public function save()
    {
        if ($this->exists()) {
            $sql = 'UPDATE beneficios SET '
                . 'codigo_pre = ' . $this->var2str($this->codigo_pre)
                . ', codigo_ped = ' . $this->var2str($this->codigo_ped)
                . ', codigo_alb = ' . $this->var2str($this->codigo_alb)
                . ', codigo_fac = ' . $this->var2str($this->codigo_fac)
                . ', precioneto = ' . $this->var2str($this->precioneto)
                . ', preciocoste = ' . $this->var2str($this->preciocoste)
                . ', beneficio = ' . $this->var2str($this->beneficio)
                . ' WHERE id = ' . $this->var2str($this->id) . ';';

            return $this->db->exec($sql);
        } else {
            $sql = 'INSERT INTO beneficios (codigo_pre, codigo_ped, codigo_alb, codigo_fac, precioneto, preciocoste, beneficio)'
                . ' VALUES ('
                . $this->var2str($this->codigo_pre)
                . ', ' . $this->var2str($this->codigo_ped)
                . ', ' . $this->var2str($this->codigo_alb)
                . ', ' . $this->var2str($this->codigo_fac)
                . ', ' . $this->var2str($this->precioneto)
                . ', ' . $this->var2str($this->preciocoste)
                . ', ' . $this->var2str($this->beneficio)
                . ');';

            if ($this->db->exec($sql)) {
                $this->id = $this->db->lastval();
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Elimina un registro de la tabla identificado por código
     *
     * @return mixed
     */
    public function delete()
    {
        $sql = 'DELETE FROM beneficios WHERE id = ' . $this->var2str($this->id) . ';';

        return $this->db->exec($sql);
    }

    /**
     * Recoge el ultimo codigo insertado en la tabla especificada (retrasamos un segundo para darle tiempo al insert)
     *
     * @param $tablax
     *
     * @return string
     */
    public function lastCod($tablax)
    {
        sleep(1);

        if ($tablax === 'albaran') {
            $tabla = $tablax . 'escli';
        } else {
            $tabla = $tablax . 'scli';
        }

        $lastCodigo = '';
        $sql = 'SELECT codigo, id' . $tablax . ' FROM ' . $tabla . ' ORDER BY id' . $tablax . ' DESC LIMIT 1 ;';

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $lastCodigo = $d['codigo'];
            }
        }
        return $lastCodigo;
    }

    /**
     * Devuelve el campo código a utilizar en función de la página
     *
     * @param type $pagina
     * @return string
     */
    public function getCodigoNombre($pagina) {
        switch ($pagina) {
            case 'ventas_presupuestos':
            case 'ventas_presupuesto':
                $codigo = 'codigo_pre';
                break;
            case 'ventas_pedidos':
            case 'ventas_pedido':
                $codigo = 'codigo_ped';
                break;
            case 'ventas_albaranes':
            case 'ventas_albaran':
                $codigo = 'codigo_alb';
                break;
            case 'ventas_facturas':
            case 'ventas_factura':
                $codigo = 'codigo_fac';
                break;
            case 'editar_factura':
                $codigo = 'codigo_fac';
                break;
            default:
                $codigo = '';
                break;
        }
        return $codigo;
    }

    /**
     * Recoge todos los codigos pasados en el array existentes en la bdd beneficios
     *
     * @param array $array_documentos
     * @param string $pagina
     *
     * @return array
     */
    public function getByCodigo($array_documentos, $pagina)
    {
        $codigo = $this->getCodigoNombre($pagina);

        $lista = [];
        $sql = "SELECT * FROM beneficios WHERE " . $codigo . " IN ('" . implode("', '", $array_documentos) . "')";

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new beneficio($d);
            }
        }

        return $lista;
    }

    /**
     * Recoge todos los netos de los códigos pasados en el array
     *
     * @param array $array_documentos
     * @param string $pagina
     *
     * @return float
     */
    public function getNeto($array_documentos, $pagina)
    {
        $codigo = $this->getCodigoNombre($pagina);

        $resultado = 0;
        $sql = "SELECT precioneto FROM beneficios WHERE " . $codigo. " IN ('" . implode("', '", $array_documentos) . "')";

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $resultado += $d;
            }
        }
        return (float)$resultado;
    }

    /**
     * Recoge todos los costes de los códigos pasados en el array
     *
     * @param array $array_documentos
     * @param string $pagina
     *
     * @return float
     */
    public function getCoste($array_documentos, $pagina)
    {
        $codigo = $this->getCodigoNombre($pagina);

        $resultado = 0;
        $sql = "SELECT preciocoste FROM beneficios WHERE ". $codigo ." IN ('" . implode("', '", $array_documentos) . "')";

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $resultado += $d;
            }
        }
        return (float)$resultado;
    }

    /**
     * Recoge todos los beneficios de los códigos pasados en el array
     *
     * @param array $array_documentos
     * @param string $pagina
     *
     * @return float
     */
    public function getBeneficio($array_documentos, $pagina)
    {
        $codigo = $this->getCodigoNombre($pagina);

        $resultado = 0;
        $sql = "SELECT beneficio FROM beneficios WHERE " . $codigo . " IN ('" . implode("', '", $array_documentos) . "')";

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $resultado += $d;
            }
        }
        return (float)$resultado;
    }

    /**
     * Devuelve todos los registros de beneficios
     *
     * No se está usando (pero lo dejo por si más adelante hace falta)!!
     *
     * @return array
     */
    public function all()
    {
        $lista = [];
        $sql = "SELECT * FROM beneficios";

        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $lista[] = new beneficio($d);
            }
        }

        return $lista;
    }
}
