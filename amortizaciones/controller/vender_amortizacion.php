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
require_model('linea_amortizacion.php');
require_model('factura_cliente.php');
require_model('ejercicio.php');
require_model('asiento.php');


/**
 * Class nueva_amortizacion
 */
class vender_amortizacion extends fs_controller
{
    /**
     * @var
     */
    public $amortizacion;
    /**
     * @var
     */
    public $ultima_linea;
    /**
     * @var
     */
    public $fecha_ultima_linea;
    /**
     * @var
     */
    public $valor_ultima_linea;
    /**
     * @var
     */
    public $factura;
    /**
     * @int
     */
    public $sin_amortizar;
    /**
     * @int
     */
    public $amortizado;
    
    /**
     * vender_amortizacion constructor.
     */
    public function __construct()
    {
        parent::__construct(__CLASS__, 'vender amortización', 'contabilidad', false, false);
    }

    /**
     * TODO PHPDoc
     */
    protected function private_core() {
        $factura = new factura_cliente();
        $amortizacion = new amortizacion();
        
        $this->amortizacion = $amortizacion->get_by_amortizacion(filter_input(INPUT_POST, 'id_amortizacion', FILTER_VALIDATE_INT));
        if ( $factura->get_by_codigo(filter_input(INPUT_POST, 'factura_codigo'))) {
            $this->factura = $factura->get_by_codigo(filter_input(INPUT_POST, 'factura_codigo'));
            $this->calcular($this->amortizacion->id_amortizacion, $this->factura->fecha);
        } else {
            $this->new_error_msg('Factura no válida');
        }
    }

    /**
     * @param $id_amortizacion
     * @param $fecha
     */
    private function calcular($id_amortizacion, $fecha)
    {
        $lin = new linea_amortizacion();
        $eje = new ejercicio();
        $amor = new amortizacion();
        $ejercicio = $eje->get_by_fecha($this->factura->fecha);
        
        $periodo_fecha_inicio = $amor->periodo_por_fecha($fecha, $ejercicio->fechafin, $ejercicio->fechainicio, $this->amortizacion->contabilizacion);
        $periodo = $periodo_fecha_inicio['periodo'];
        $fecha_inicio_ultima = $periodo_fecha_inicio['fecha_inicio_periodo'];
        $ano = (int)(Date('Y', strtotime($ejercicio->fechainicio)));
        $this->ultima_linea = $lin->get_by_id_amor_ano_periodo($id_amortizacion, $ano, $periodo);
        $dias_periodo = $amor->diferencia_dias($fecha_inicio_ultima, $this->ultima_linea->fecha) + 1;
        $dias_a_amortizar = $amor->diferencia_dias($fecha_inicio_ultima, $fecha);
        $this->fecha_ultima_linea = date('d-m-Y', strtotime($fecha . '- 1 day'));
        
        $this->valor_ultima_linea = round($this->ultima_linea->cantidad/$dias_periodo * $dias_a_amortizar,2);
        $this->sin_amortizar = $this->ultima_linea->cantidad - $this->valor_ultima_linea;
        $this->amortizado = $this->valor_ultima_linea;
                
        $lineas = $lin->get_by_amortizacion($id_amortizacion);
        $amortizado = 0;
        $sin_amortizar = 0;
        
        foreach ($lineas as $value) {
            if ($value->id_linea == $this->ultima_linea->id_linea) {  
            }
            elseif (strtotime ($value->fecha) <= strtotime ($fecha)) {
                $this->amortizado += $value->cantidad;
            } else {
                $this->sin_amortizar += $value->cantidad;
            }
        }
    }

}
