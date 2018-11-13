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
require_model('linea_amortizacion.php');
require_model('factura_proveedor.php');
require_model('factura_cliente');
require_model('ejercicio.php');


/**
 * Class editar_amortizacion
 */
class editar_amortizacion extends fs_controller
{
    /**
     * @var
     */
    public $ano_fiscal;
    /**
     * @int
     */
    public $aumentar;
    /**
     * @int
     */
    public $cambiar_periodos;
    /**
     * @var
     */
    public $fecha_cambio;
    /**
     * @var
     */
    public $ejercicio_actual;
    /**
     * @var
     */
    public $factura;
    /**
     * @var
     */
    public $factura_venta;
    /**
     * @var
     */
    public $fecha_factura;
    /**
     * @var
     */
    public $amortizacion;
    /**
     * @var
     */
    public $listado_lineas;
    /**
     * @var
     */
    public $lineas;
    /**
     * @float
     */
    public $amortizado;
    /**
     * @float
     */
    public $sin_amortizar;
    /**
     * @var int
     */
    public $periodo_inicial;
    /**
     * @var int
     */
    public $periodos = 0;
    /**
     * @var int
     */
    public $periodo_minimo = 0;
    /**
     * @var
     */
    public $falta_amortizar;
    /**
     * @int
     */
    public $cod_subcuenta_beneficios;
    /**
     * @int
     */
    public $cod_subcuenta_cierre;
    /**
     * @int
     */
    public $cod_subcuenta_debe;
    /**
     * @int
     */
    public $cod_subcuenta_haber;
    /**
     * @int
     */
    public $cod_subcuenta_perdidas;

    /**
     * editar_amortizacion constructor.
     */
    public function __construct()
    {
        parent::__construct(__CLASS__, 'editar amortizacion', 'contabilidad', false, false);
    }

    /**
     * TODO PHPDoc
     */
    protected function private_core()
 {
        $this->factura = false;
        $lineas_amortizaciones = new linea_amortizacion();

        if (filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT) !== null) {
            if ($lineas_amortizaciones->eliminar_asiento(filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT))) {
                $this->new_message('Asiento eliminado');
            } else {
                $this->new_error_msg('No se ha podido eliminar el asiento');
            }
        } elseif
        (filter_input(INPUT_GET, 'renew', FILTER_VALIDATE_INT) !== null) {
            $this->resucitar(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT));
        } elseif (filter_input(INPUT_GET, 'new_counts', FILTER_VALIDATE_INT) !== null) {
            $this->insertar_cuentas_contables(
                    filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT),
                    //Cuando se pase todo a 64 bits, dentro de un tiempo añadir FILTER_VALIDATE_INT filter_input(INPUT_POST, 'cod_subcuenta_beneficios', FILTER_VALIDATE_INT) para comprobar las subcuentas
                    filter_input(INPUT_POST, 'cod_subcuenta_beneficios'), 
                    filter_input(INPUT_POST, 'cod_subcuenta_cierre'), 
                    filter_input(INPUT_POST, 'cod_subcuenta_debe'), 
                    filter_input(INPUT_POST, 'cod_subcuenta_haber'), 
                    filter_input(INPUT_POST, 'cod_subcuenta_perdidas'));
                    //Hasta aquí
        } elseif (filter_input(INPUT_GET, 'increase') !== null) {
            $this->aumentar_valor(filter_input(INPUT_POST, 'fecha'), filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_INT));
        }
        
        if (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) !== null) {
            $amor = new amortizacion();
            $this->amortizacion = $amor->get_by_amortizacion(filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT));
            $this->lineas = $lineas_amortizaciones->get_by_amortizacion($this->amortizacion->id_amortizacion);
            $factura = new factura_proveedor();
            $this->factura = $factura->get($this->amortizacion->id_factura);

            $this->fecha_factura = date('d-m-Y', strtotime($this->factura->fecha));
                        
            $ejercicio = new ejercicio;
            $primer_ejercicio = $ejercicio->get_by_fecha($this->amortizacion->fecha_inicio);
            $this->ejercicio_actual = $ejercicio->get_by_fecha($this->today());
            $this->ano_fiscal = (int) (Date('Y', strtotime($primer_ejercicio->fechainicio)));
            
            //Aumentar valor
            if (filter_input(INPUT_GET, 'value', FILTER_VALIDATE_INT) !== null) {
                $this->aumentar = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
                $this->fecha_cambio = filter_input(INPUT_POST, 'fecha');
                
                if ($this->aumentar == 0) {
                    $this->new_error_msg('No se han podido recalcular las líneas de amortización, el valor a aumentar era 0');
                } elseif ($this->aumentar < 0) {
                    $this->new_error_msg('No se han podido recalcular las líneas de amortización, el valor a aumentar era negativo');
                    $this->aumentar = null;
                } elseif (strtotime($this->fecha_cambio) < strtotime($this->amortizacion->fecha_inicio) || strtotime($this->fecha_cambio) > strtotime($this->amortizacion->fecha_fin)) {
                    $this->new_error_msg('No se han podido recalcular las líneas de amortización, La fecha introducida esta fuera del intervalo de fechas de la amortización');
                    $this->aumentar = null;
                } elseif ($ejercicio->get_by_fecha($this->fecha_cambio, FALSE, FALSE)->estado == 'CERRADO') {
                    $this->new_error_msg('No se han podido recalcular las líneas de amortización, la fecha introducida pertenece a un ejercicio CERRADO');
                    $this->aumentar = null;
                } else {
                    $this->aumentar_valor($this->aumentar, $this->fecha_cambio);
                }  
            }
            
            //Cambiar periodos
            if (filter_input(INPUT_GET, 'change_date', FILTER_VALIDATE_INT) !== null) {
                $this->cambiar_periodos = filter_input(INPUT_POST, 'periodos', FILTER_VALIDATE_FLOAT);
                $this->fecha_cambio = filter_input(INPUT_POST, 'fecha');

                if ($this->cambiar_periodos == 0) {
                    $this->new_error_msg('No se han podido generar las líneas de amortización, porque has dejado periodos a 0');
                } elseif (strtotime($this->fecha_cambio) < strtotime($this->amortizacion->fecha_inicio) || strtotime($this->fecha_cambio) > strtotime($this->amortizacion->fecha_fin)) {
                    $this->new_error_msg('La fecha introducida esta fuera del intervalo de fechas de la amortización');
                    $this->cambiar_periodos = null;
                } elseif (strtotime($this->fecha_cambio) >= strtotime($this->amortizacion->fecha_fin . '+' . $this->cambiar_periodos . ' year')) {
                    $this->new_error_msg('La fecha introducida es mayor que la nueva fecha final');
                    $this->cambiar_periodos = null;
                } elseif ($ejercicio->get_by_fecha($this->fecha_cambio, FALSE, FALSE)->estado == 'CERRADO') {
                    $this->new_error_msg('No se han podido recalcular las líneas de amortización, la fecha introducida pertenece a un ejercicio CERRADO');
                    $this->cambiar_periodos = null;
                } else {
                    $this->cambiar_periodos($this->cambiar_periodos, $this->fecha_cambio);
                }
            }
            
            $this->periodo_minimo = -($this->amortizacion->periodos - 1);

            //Hallar el periodo inicial
            $periodo_fecha = $amor->periodo_por_fecha($this->amortizacion->fecha_inicio, $primer_ejercicio->fechafin, $primer_ejercicio->fechainicio, $this->amortizacion->contabilizacion);
            $this->periodo_inicial = $periodo_fecha['periodo'];

            $this->cuentas_contables();
            $this->facturas();
            $this->calcular_restante();
        } 
    }

    /**
     * TODO PHPDoc
     */
    private function calcular_restante() {
        
        $lin = new linea_amortizacion();
        $amor = new amortizacion();
        
        $periodo_fecha_inicio = $amor->periodo_por_fecha($this->today(), $this->ejercicio_actual->fechafin, $this->ejercicio_actual->fechainicio, $this->amortizacion->contabilizacion);
        $periodo = $periodo_fecha_inicio['periodo'];
        $fecha_inicio = $periodo_fecha_inicio['fecha_inicio_periodo'];
        $ano = (int)(Date('Y', strtotime($this->ejercicio_actual->fechainicio)));        
        $linea = $lin->get_by_id_amor_ano_periodo($this->amortizacion->id_amortizacion, $ano, $periodo);
        $dias_periodo = $amor->diferencia_dias($fecha_inicio, $linea->fecha) + 1;
        $dias_a_amortizar = $amor->diferencia_dias($fecha_inicio, $this->today());
        
        $valor_linea = round($linea->cantidad/$dias_periodo * $dias_a_amortizar,2);
        $this->amortizado = $valor_linea;
        
        $this->listado_lineas = array();
        
        $contador = 0;
        $grupos = 1;
        foreach ($this->lineas as $value) {
            $this->listado_lineas[$grupos][$contador] = $value;
                       
            if ($value->id_linea == $linea->id_linea) {  
            }
            elseif (strtotime ($value->fecha) <= strtotime ($this->today())) {
                $this->amortizado += $value->cantidad;
            }
            $contador++;
            if ($contador % 26 == 0) $grupos++;
        }
                
        $this->sin_amortizar = $this->amortizacion->valor - $this->amortizado;
    }
    
    /**
     * @param $periodos
     * @param $fecha
     */
    private function cambiar_periodos($periodos, $fecha) 
    {
        $amor = new amortizacion();
        if (strtotime($fecha) < strtotime($this->lineas[0]->fecha)) {
            $this->new_advice('La fecha introducida corresponde con el primer periodo de la amortización, 
                               por lo que no habrá ningún asiento contable creado, quizás sea mejor que elimines esta amortización y la crees de nuevo');
        }
        
        $ejercico = $this->ano_fiscal + $this->amortizacion->periodos;     
        $eje = new ejercicio();
        $primer_ejercicio = $eje->get_by_fecha($this->amortizacion->fecha_inicio);
        $fecha_inicio = date('d-m-Y', strtotime($primer_ejercicio->fechainicio . '+' .$this->amortizacion->periodos. ' year'));
        $fecha_fin = date('d-m-Y', strtotime($primer_ejercicio->fechafin . '+' .$this->amortizacion->periodos. ' year'));
        $fecha_periodo = $amor->periodo_por_fecha($this->amortizacion->fecha_inicio, $fecha_fin, $fecha_inicio, $this->amortizacion->contabilizacion);
        $fecha_periodo = $fecha_periodo['fecha_inicio_periodo'];
        
        $periodos_pendientes = 0;

        $this->amortizacion->periodos += $periodos;
        $this->amortizacion->fecha_fin = date('d-m-Y', strtotime($this->amortizacion->fecha_fin . '+' . $periodos . ' year'));
                        
        $pendiente = 0;
        $contador = 0;
        
        //Recorremos las lineas actuales
        foreach ($this->lineas as $key => $value) { 
            if (strtotime($value->fecha) >= strtotime($fecha)) {
                $pendiente += $value->cantidad;
                $periodos_pendientes += 1;
            }
            $contador += 1;
        }
        
        if ($this->amortizacion->contabilizacion == 'anual') {
            $periodos_ano = 1;
            $meses = 12;
        } elseif ($this->amortizacion->contabilizacion == 'trimestral') {
            $periodos_ano = 4;
            $meses = 3;
        } elseif ($this->amortizacion->contabilizacion == 'mensual') {
            $periodos_ano = 12;
            $meses = 1;
        }
                
        $fecha_periodo = date('d-m-Y', strtotime($fecha_periodo . '+' . $meses . ' month'));
                
        $ejercicio_final = $this->ano_fiscal + $this->amortizacion->periodos;
        $periodo = $this->amortizacion->periodo_final + 1;
                
        $restantes = 0;
        if ($periodos < 0) {    //PARTE MENOS periodos
            //Eliminar lineas sobrantes, SIN añadir el valor
            foreach ($this->lineas as $key => $value) {
                if ($key >= $contador - $periodos_ano * (-$periodos)) {
                    unset($this->lineas[$key]);
                } elseif (strtotime($value->fecha) >= strtotime($fecha)) {
                    $restantes += 1;
                }
            }

            //Recorremos todas las líneas para añadir el valor
            $fecha_inicio = date('d-m-Y', strtotime($primer_ejercicio->fechainicio . '+' .$this->amortizacion->periodos. ' year'));
            $fecha_fin = date('d-m-Y', strtotime($primer_ejercicio->fechafin . '+' .$this->amortizacion->periodos. ' year'));
            $fecha_periodo = $amor->periodo_por_fecha($this->amortizacion->fecha_inicio, $fecha_fin, $fecha_inicio, $this->amortizacion->contabilizacion);
            $fecha_periodo = $fecha_periodo['fecha_inicio_periodo'];
            
            $periodos_pendientes = $restantes - 1;
            $ejercicio_fecha = $eje->get_by_fecha($fecha);
            $fecha_periodo_inicial = $amor->periodo_por_fecha($fecha, $ejercicio_fecha->fechafin, $ejercicio_fecha->fechainicio, $this->amortizacion->contabilizacion);
            $dias_totales = $amor->diferencia_dias($fecha_periodo_inicial['fecha_inicio_periodo'], $this->amortizacion->fecha_fin) + 1;
            $dias_ultimo_periodo = $amor->diferencia_dias($fecha_periodo, $this->amortizacion->fecha_fin) + 1;
            $valor_ultimo_periodo = $pendiente / ($dias_totales / $dias_ultimo_periodo);
            $valor_periodo = round(($pendiente - $valor_ultimo_periodo) / $periodos_pendientes, 2);
            $total = $valor_periodo * $periodos_pendientes;
            
            foreach ($this->lineas as $key => $value) {
                if (strtotime($value->fecha) >= strtotime($fecha)) {
                    $value->contabilizada = 0;
                    $value->cantidad = $valor_periodo;
                }
            }
            $this->lineas[$contador - $periodos_ano * (-$periodos) - 1]->cantidad = round($pendiente - $total, 2);
            $this->lineas[$contador - $periodos_ano * (-$periodos) - 1]->fecha = $this->amortizacion->fecha_fin;
            
        } elseif ($periodos > 0) {  //PARTE MAS periodos
            //Añadimos las líneas nuevas, SIN añadir el valor
            $this->lineas[$contador-1]->fecha = date('d-m-Y', strtotime($fecha_periodo . '-1 day'));        
            $linea = new linea_amortizacion();
            while ($ejercico <= $ejercicio_final) {
                while ($periodo <= $periodos_ano) {
                    $nueva_linea = new linea_amortizacion();

                    $nueva_linea->ano = $ejercico;
                    $nueva_linea->contabilizada = 0;
                    $nueva_linea->id_amortizacion = $this->amortizacion->id_amortizacion;
                    $nueva_linea->periodo = $periodo;

                    if ($periodo == $this->amortizacion->periodo_final && $ejercico == $ejercicio_final) {
                        $nueva_linea->fecha = $this->amortizacion->fecha_fin;
                        $this->lineas[$contador] = $nueva_linea;
                        break;
                    } else {
                        $fecha_periodo = date('d-m-Y', strtotime($fecha_periodo . '+' . $meses . ' month'));
                        $nueva_linea->fecha = date('d-m-Y', strtotime($fecha_periodo . '-1 day'));
                    }
                    $this->lineas[$contador] = $nueva_linea;
                    $contador += 1;
                    $periodo += 1;
                }
                $ejercico += 1;
                $periodo = 1;
            }

            //Recorremos todas las líneas para añadir el valor
            $periodos_pendientes = ($periodos_pendientes + $periodos * $periodos_ano) - 1;
            $ejercicio_fecha = $eje->get_by_fecha($fecha);
            $fecha_periodo_inicial = $amor->periodo_por_fecha($fecha, $ejercicio_fecha->fechafin, $ejercicio_fecha->fechainicio, $this->amortizacion->contabilizacion);
            $dias_totales = $amor->diferencia_dias($fecha_periodo_inicial['fecha_inicio_periodo'], $this->amortizacion->fecha_fin) + 1;
            $dias_ultimo_periodo = $amor->diferencia_dias($fecha_periodo, $this->amortizacion->fecha_fin) + 1;
            $valor_ultimo_periodo = $pendiente / ($dias_totales / $dias_ultimo_periodo);
            $valor_periodo = round(($pendiente - $valor_ultimo_periodo) / $periodos_pendientes, 2);
            $total = $valor_periodo * $periodos_pendientes;
            foreach ($this->lineas as $key => $value) {
                if (strtotime($value->fecha) >= strtotime($fecha)) {
                    $value->contabilizada = 0;
                    $value->cantidad = $valor_periodo;
                }
            }
            $this->lineas[$contador]->cantidad = round($pendiente - $total, 2);
        }
    }
    
    /**
     * @param $valor
     * @param $fecha
     */
    private function aumentar_valor($valor,$fecha)
    {
        $amor = new amortizacion();
        $this->amortizacion->valor += $valor;
        $periodos_pendientes = 0;
        $periodos_anteriores = 0;
               
        foreach ($this->lineas as $key => $value) {   
            if (strtotime($value->fecha) < strtotime($fecha)) {
                $periodos_anteriores += 1;
            } else {
                $periodos_pendientes += 1;
            }
        }
        $dias_primer_periodo = $amor->diferencia_dias($fecha, $this->lineas[$periodos_anteriores]->fecha) + 1;
        $dias_totales = $amor->diferencia_dias($fecha, $this->amortizacion->fecha_fin);
        $dias_ultimo_periodo = $amor->diferencia_dias($this->lineas[$periodos_anteriores + $periodos_pendientes - 2]->fecha, $this->amortizacion->fecha_fin);
        $valor_ultimo_periodo = $valor / ($dias_totales / $dias_ultimo_periodo);
                    
        if ($amor->diferencia_dias($fecha, $this->lineas[$periodos_anteriores-1]->fecha) == 1) {
            $valor_primer_periodo = round($valor_hasta_penul/($periodos_pendientes - 2), 2);
        } else {
            $valor_primer_periodo = round($valor / ($dias_totales / $dias_primer_periodo), 2);
        }
        
        $valor_hasta_penul = $valor - $valor_ultimo_periodo - $valor_primer_periodo;
        
        $periodo = 0;
        $acumulado = 0;
        foreach ($this->lineas as $key => $value) {
            if (strtotime($value->fecha) >= strtotime($fecha)) {
                $value->contabilizada = 0;
                $periodo += 1;
                if ($periodo == $periodos_pendientes) {
                    $value->cantidad += round($valor - $acumulado, 2);
                    $acumulado += round($valor - $acumulado, 2);
                } elseif ($periodo == 1) {
                    $value->cantidad += $valor_primer_periodo;
                    $acumulado += $valor_primer_periodo;
                } else {
                    $value->cantidad += round($valor_hasta_penul/($periodos_pendientes - 2), 2);
                    $acumulado += round($valor_hasta_penul/($periodos_pendientes - 2), 2);
                }
            }
        }   
    }
    
    /**
     * @param $id_amortizacion
     * @return $array
     */
    private function resucitar($id_amortizacion)
    {
        $lineas_amortizaciones = new linea_amortizacion();
        $asiento = new asiento();
        $amor = new amortizacion();
        $amortizacion = $amor->get_by_amortizacion($id_amortizacion);
        $asiento_fin_vida = $asiento->get($amortizacion->id_asiento_fin_vida);
        
        if ($asiento_fin_vida->delete()) {
            if ($amortizacion->fin_vida_util) {
                $amor->resurrect($id_amortizacion);
            } elseif ($amortizacion->vendida) {
                $amor->resurrect_sale($id_amortizacion);
            }
            $ejercicio_model = new ejercicio();
            $ejercicio = $ejercicio_model->get_by_fecha($amortizacion->fecha_fin_vida_util);
            $periodo_fecha = $amor->periodo_por_fecha($amortizacion->fecha_fin_vida_util, $ejercicio->fechafin, $ejercicio->fechainicio, $amortizacion->contabilizacion);
            $ano = (int) (Date('Y', strtotime($ejercicio->fechainicio)));
            
            if (strtotime($amortizacion->fecha_fin_vida_util) < strtotime($amortizacion->fecha_fin)) {
                $linea = $lineas_amortizaciones->get_by_id_amor_ano_periodo($id_amortizacion, $ano, $periodo_fecha['periodo']);
                $asiento_amortizacion = $asiento->get($linea->id_asiento);

                if ($asiento_amortizacion == null){
                    $this->new_message('Amortización reanudada, se ha eliminado el asiento de venta del amortizado');
                } elseif ($asiento_amortizacion->delete()) {
                    $lineas_amortizaciones->discount($linea->id_linea);
                    $this->new_message('Amortización reanudada, se ha eliminado el asiento de fin de la vida útil del amortizado y el el asiento que contabilizo el tiempo trancurrido en ese periodo');
                } else {
                    $this->new_message('No se ha podido reanudar la amortización');
                }
            } else {
                $this->new_message('Amortización reanudada, se ha eliminado el asiento de fin de la vida útil del amortizado');
            }
        } else {
            $this->new_message('No se ha podido reanudar la amortización');
        }
    }
    
    /**
     * @param $id
     * @param $cod_subcuenta_beneficios  
     * @param $cod_subcuenta_cierre
     * @param $cod_subcuenta_debe
     * @param $cod_subcuenta_haber
     * @param $cod_subcuenta_perdidas
     */
    private function insertar_cuentas_contables($id, $cod_subcuenta_beneficios, $cod_subcuenta_cierre, $cod_subcuenta_debe, $cod_subcuenta_haber, $cod_subcuenta_perdidas) {
        $amortizacion = new amortizacion();
        if ($amortizacion->update_counts($id, $cod_subcuenta_beneficios, $cod_subcuenta_cierre, $cod_subcuenta_debe, $cod_subcuenta_haber, $cod_subcuenta_perdidas)) {
            $this->new_message('Subcuentas actualizadas correctamente');
        } else {
            $this->new_error_msg('Se produjo un error al actualizar las cuentas contables');
        }
    }

    /**
     * TODO PHPDoc
     */
    private function cuentas_contables() {
        if (isset($_REQUEST['buscar_subcuenta'])) {
            /// esto es para el autocompletar las subcuentas de la vista
            $this->buscar_subcuenta();
        } else if ($this->amortizacion) {

            $eje0 = new ejercicio();
            $ejercicio = $eje0->get_by_fecha($this->today());
            $sc = new subcuenta();
            
            $this->cod_subcuenta_cierre = $sc->get_by_codigo($this->amortizacion->cod_subcuenta_cierre, $ejercicio->codejercicio);
            $this->cod_subcuenta_debe = $sc->get_by_codigo($this->amortizacion->cod_subcuenta_debe, $ejercicio->codejercicio);
            $this->cod_subcuenta_haber = $sc->get_by_codigo($this->amortizacion->cod_subcuenta_haber, $ejercicio->codejercicio);
            $this->cod_subcuenta_perdidas = $sc->get_by_codigo($this->amortizacion->cod_subcuenta_perdidas, $ejercicio->codejercicio);
            $this->cod_subcuenta_beneficios = $sc->get_by_codigo($this->amortizacion->cod_subcuenta_beneficios, $ejercicio->codejercicio);

            
            /**
             * si alguna subcuenta no se encontrase, devuelve un false,
             * pero necesitamos una subcuenta para la vista, aunque no esté en
             * blanco y no esté en la base de datos
             */
            if (!$this->cod_subcuenta_cierre) {
                $this->cod_subcuenta_cierre = $sc;
            }
            if (!$this->cod_subcuenta_debe) {
                $this->cod_subcuenta_debe = $sc;
            }
            if (!$this->cod_subcuenta_haber) {
                $this->cod_subcuenta_haber = $sc;
            }
            if (!$this->cod_subcuenta_perdidas) {
                $this->cod_subcuenta_perdidas = $sc;
            }
            if (!$this->cod_subcuenta_beneficios) {
                $this->cod_subcuenta_beneficios = $sc;
            }
        } else {
            $this->new_error_msg('Artículo no encontrado.', 'error', FALSE, FALSE);
        }
    }
    
    /**
     * TODO PHPDoc
     */
    public function url() {
        if ($this->amortizacion) {
            return 'index.php?page=' . __CLASS__ . '&id=' . $this->amortizacion->id_amortizacion;
        } else
            return parent::url();
    }

    /**
     * TODO PHPDoc
     */
    private function buscar_subcuenta() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $subcuenta = new subcuenta();
        $eje0 = new ejercicio();
        $ejercicio = $eje0->get_by_fecha($this->today());
        $json = array();
        foreach ($subcuenta->search_by_ejercicio($ejercicio->codejercicio, $_REQUEST['buscar_subcuenta']) as $subc) {
            $json[] = array(
                'value' => $subc->codsubcuenta,
                'data' => $subc->descripcion,
                'saldo' => $subc->saldo,
                'link' => $subc->url()
            );
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_subcuenta'], 'suggestions' => $json));
    }
    
    /**
     * TODO PHPDoc
     */
    private function facturas() {
        if (isset($_REQUEST['buscar_factura'])) {
            /// esto es para el autocompletar las subcuentas de la vista
            $this->buscar_factura();
        } else if ($this->amortizacion) {

            $sc = new factura_cliente();
            $this->factura_venta = $sc;
            
        } else {
            $this->new_error_msg('Artículo no encontrado.', 'error', FALSE, FALSE);
        }
    }
    
    /**
     * TODO PHPDoc
     */
    private function buscar_factura() {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $factura = new factura_cliente();
        $eje0 = new ejercicio();
        $ejercicio_actual = $eje0->get_by_fecha($this->today());
        if ($eje0->get_by_fecha(date('d-m-Y', strtotime($this->today() . '- 1 year')), TRUE, FALSE)) {
            $fecha_inicial = $eje0->get_by_fecha(date('d-m-Y', strtotime($this->today() . '- 1 year')), TRUE, FALSE)->fechainicio;
        } else {
            $fecha_inicial = $ejercicio_actual->fechainicio;
        }
        if ($eje0->get_by_fecha(date('d-m-Y', strtotime($this->today() . '+ 1 year')), TRUE, FALSE)) {
            $fecha_final = $eje0->get_by_fecha(date('d-m-Y', strtotime($this->today() . '+ 1 year')), TRUE, FALSE)->fechafin;
        } else {
            $fecha_final = $ejercicio_actual->fechafin;
        }

        $json = array();
        foreach ($factura->all_desde($fecha_inicial, $fecha_final) as $subc) {
            $json[] = array(
                'value' => $subc->codigo,
                'cliente' => $subc->nombrecliente,
                'fecha' => $subc->fecha,
                'total' => $this->show_precio($subc->neto),
                'link' => $subc->url()
            );
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_factura'], 'suggestions' => $json));
    }
    
}

