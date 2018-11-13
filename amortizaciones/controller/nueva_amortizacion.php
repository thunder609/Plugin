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

require_model('factura_proveedor.php');
require_model('ejercicio.php');
require_model('subcuenta.php');
require_model('amortizacion.php');

/**
 * Class nueva_amortizacion
 */
class nueva_amortizacion extends fs_controller
{
    /**
     * @var
     */
    public $amortizacion;
    /**
     * @var
     */
    public $ano_fiscal;
    /**
     * @var
     */
    public $cod_divisa;
    /**
     * @var
     */
    public $cod_serie;
    /**
     * @int
     */
    public $id_factura;
    /**
     * @var
     */
    public $inicio_ejercicio;
    /**
     * @var
     */
    public $lineas;
    /**
     * @var
     */
    public $fecha;
    /**
     * @var
     */
    public $fecha_fin;
    /**
     * @var
     */
    public $datos;
    /**
     * @var
     */
    public $documento;
    /**
     * @int
     */
    public $periodos;
    /**
     * @var
     */
    public $periodo_inicial;
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
     * nueva_amortizacion constructor.
     */
    public function __construct()
    {
        parent::__construct(__CLASS__, 'nueva amortización', 'contabilidad', false, false);
    }

    /**
     * TODO PHPDoc
     */
    protected function private_core() {
        $this->share_extension();
        $this->amortizacion = false;
        $ejercicio = new ejercicio();

        if (isset($_REQUEST['buscar_subcuenta'])) {
                    /// esto es para el autocompletar las subcuentas de la vista
                    $this->buscar_subcuenta();
                } else {
                    $sc = new subcuenta();
                    $this->cod_subcuenta_cierre = $sc;
                    $this->cod_subcuenta_debe = $sc;
                    $this->cod_subcuenta_haber = $sc;
                    $this->cod_subcuenta_perdidas = $sc;
                    $this->cod_subcuenta_beneficios = $sc;
                }
        
        if (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) !== null) {
            $this->id_factura = (filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT));         
            $factura = new factura_proveedor();
            $this->amortizacion = $factura->get($this->id_factura);            
            $this->fecha = date('d-m-Y', strtotime($this->amortizacion->fecha));
            $this->cod_divisa = $this->amortizacion->coddivisa;
            $this->cod_serie = $this->amortizacion->codserie;
            $this->documento = $this->amortizacion->codigo;
        } 

        if (filter_input(INPUT_POST, 'periodos', FILTER_VALIDATE_INT) !== null) {
            $this->id_factura = filter_input(INPUT_POST, 'id_factura', FILTER_VALIDATE_INT);
            $this->periodos = filter_input(INPUT_POST, 'periodos', FILTER_VALIDATE_INT);
            
            if ($this->periodos == 0) {
                $this->new_error_msg('No se han podido generar las líneas de amortización, porque el periodo de años estaba a 0');
            } elseif ($ejercicio->get_by_fecha(filter_input(INPUT_POST,'fecha_inicio'), FALSE, FALSE)->estado == 'CERRADO') {
                $this->new_error_msg('No se han podido recalcular las líneas de amortización, la fecha introducida pertenece a un ejercicio CERRADO');
                $this->periodos = 0;
            } else {
                
                $this->datos = array(
                    'descripcion' => filter_input(INPUT_POST,'descripcion'),
                    'tipo' => filter_input(INPUT_POST,'tipo'),
                    'contabilizacion' => filter_input(INPUT_POST,'contabilizacion'),
                    'cod_divisa' => filter_input(INPUT_POST,'cod_divisa'),
                    'cod_serie' => filter_input(INPUT_POST,'cod_serie'),
                    'documento' => filter_input(INPUT_POST,'documento'),
                    'valor' => filter_input(INPUT_POST,'valor',FILTER_VALIDATE_FLOAT),
                    'residual' => filter_input(INPUT_POST,'residual'),
                    'periodos' => filter_input(INPUT_POST,'periodos', FILTER_VALIDATE_INT),
                    'fecha_inicio' => filter_input(INPUT_POST,'fecha_inicio'),
                );                
                
                $this->fecha_fin = date('d-m-Y', strtotime($this->datos['fecha_inicio'] . '+' . $this->datos['periodos'] . ' year - 1 day'));
                
                if ($this->datos['contabilizacion'] == 'mensual' && $this->datos['periodos'] > 30) {
                    $this->new_error_msg('Has elegido contabilización MENSUAL y ' .$this->datos['periodos']. ' periodos
                            </br>Si le das a GUARDAR, funcionará, pero las operaciones al guardar las líneas o entrar en la amortización se ralentizaran
                            </br>Quizas sea mejor que elijas un método de contabilización TRIMESTRAL o ANUAL');
                }
                
                if (isset($_REQUEST['buscar_subcuenta'])) {
                    /// esto es para el autocompletar las subcuentas de la vista
                    $this->buscar_subcuenta();
                } else {
                    $sc = new subcuenta();
                    $this->cod_subcuenta_cierre = $sc;
                    $this->cod_subcuenta_debe = $sc;
                    $this->cod_subcuenta_haber = $sc;
                    $this->cod_subcuenta_perdidas = $sc;
                    $this->cod_subcuenta_beneficios = $sc;
                }

                if ($this->datos['tipo'] == 'constante') {
                    $this->constante();
                }
            }
        }
    }

    /**
     * TODO PHPDoc
     */
    private function share_extension() //Botón de amortizar en las facturas de compra
    {
        $fsext = new fs_extension();
        $fsext->name = 'nueva_amortizacion';
        $fsext->from = __CLASS__;
        $fsext->to = 'compras_factura';
        $fsext->type = 'button';
        $fsext->text = 'Amortizar';
        $fsext->save();
    }

    /**
     * TODO PHPDoc
     */
    private function constante()
    {
        $ejercicio = new ejercicio;
        $amor = new amortizacion();
        $ejercicio_factura = $ejercicio->get_by_fecha($this->datos['fecha_inicio']);
        $this->inicio_ejercicio = date('d-m-Y', strtotime($ejercicio_factura->fechainicio));
                
        $contador = 0;
        $this->lineas = array();
        $amortizable = $this->datos['valor'] - $this->datos['residual'];
        $this->ano_fiscal = (int) (Date('Y', strtotime($ejercicio_factura->fechainicio)));
        $dias = $amor->diferencia_dias($ejercicio_factura->fechainicio, $this->datos['fecha_inicio']);
        $fecha = $ejercicio_factura->fechainicio;
        $total = 0;

        $mes = (int) (Date('m', strtotime($ejercicio_factura->fechafin)));
        if ($mes != 12) {
            $mes_final = 12 - (int) (Date('m', strtotime($ejercicio_factura->fechafin)));
            $mes_inicio = (int) (Date('m', strtotime($this->datos['fecha_inicio'])));
            $mes_fiscal = $mes_inicio + $mes_final - 12;
            if ($mes_fiscal < 1) {
                $mes_fiscal = $mes_fiscal + 12;
            }
        } else {
            $mes_fiscal = (int) (Date('m', strtotime($this->datos['fecha_inicio'])));
        }
        
        if ($this->datos['contabilizacion'] == 'anual') {
            $this->periodo_inicial = 1;
            $periodos_ano = 1;
            $periodo = 1;
            $fecha = date('d-m-Y', strtotime($fecha . '+1 year'));
            $meses = 12;
        } elseif ($this->datos['contabilizacion'] == 'trimestral') {
            $this->periodo_inicial = ceil($mes_fiscal / 3);
            $periodos_ano = 4;
            $periodo = $this->periodo_inicial * 3;
            $fecha = date('d-m-Y', strtotime($fecha . '+' . $periodo . ' month'));
            $meses = 3;
        } elseif ($this->datos['contabilizacion'] == 'mensual') {
            $this->periodo_inicial = $mes_fiscal;
            $periodos_ano = 12;
            $periodo = $mes_fiscal;
            $fecha = date('d-m-Y', strtotime($fecha . '+' .$periodo. ' month'));
            $meses = 1;
        }
        
        $grupos = 1;
        $contador_grupos = 0;
        
        if ($dias != 0) {
            

            $dias_ano_fiscal = $amor->diferencia_dias($ejercicio_factura->fechainicio, $ejercicio_factura->fechafin) + 1;
            $valor = ($amortizable / $this->datos['periodos'] / $dias_ano_fiscal * ($dias_ano_fiscal - $dias)) - ((($amortizable / $this->datos['periodos']) / $periodos_ano) * ($periodos_ano - $this->periodo_inicial));
            $this->lineas[$grupos][$this->ano_fiscal + $contador . '_' . $this->periodo_inicial] = array(
                'ano' => $this->ano_fiscal + $contador,
                'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                'valor' => round($valor, 2),
                'periodo' => $this->periodo_inicial
            );
            $total = $total + round($valor, 2);
            $periodo = $this->periodo_inicial + 1;
            $contador_grupos++;

            while ($periodo <= $periodos_ano) {

                $fecha = date('d-m-Y', strtotime($fecha . '+' . $meses . ' month'));

                $this->lineas[$grupos][$this->ano_fiscal + $contador . '_' . $periodo] = array(
                    'ano' => $this->ano_fiscal + $contador,
                    'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                    'valor' => round(($amortizable / $this->datos['periodos']) / $periodos_ano, 2),
                    'periodo' => $periodo
                );
                $total = $total + round(($amortizable / $this->datos['periodos']) / $periodos_ano, 2);
                $periodo++;
                $contador_grupos++;
            }
            $contador++;
        }

        while ($contador < $this->datos['periodos']) {
            $periodo = 1;
            while ($periodo <= $periodos_ano) {

                $fecha = date('d-m-Y', strtotime($fecha .'+' . $meses . ' month'));

                if ($contador == $this->datos['periodos'] - 1 && $periodo == $periodos_ano && $dias == 0) {
                    $this->lineas[$grupos][$this->ano_fiscal + $contador . '_' . $periodo] = array(
                        'ano' => $this->ano_fiscal + $contador,
                        'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                        'valor' => round($amortizable - $total, 2),
                        'periodo' => $periodo
                    );
                } else {
                    $this->lineas[$grupos][$this->ano_fiscal + $contador . '_' . $periodo] = array(
                        'ano' => $this->ano_fiscal + $contador,
                        'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                        'valor' => round(($amortizable / $this->datos['periodos']) / $periodos_ano, 2),
                        'periodo' => $periodo
                    );
                    $total = $total + round(($amortizable / $this->datos['periodos']) / $periodos_ano, 2);
                }
                $periodo++;
                $contador_grupos++;
                if ($contador_grupos % 26 == 0) $grupos++;
            }
            $contador++;
        }

        if ($dias != 0) {
            $periodo = 1;
            while ($periodo < $this->periodo_inicial) {

                $fecha = date('d-m-Y', strtotime($fecha . '+' . $meses . ' month'));

                $this->lineas[$grupos][$this->ano_fiscal + $contador . '_' . $periodo] = array(
                    'ano' => $this->ano_fiscal + $contador,
                    'fecha' => date('d-m-Y', strtotime($fecha . '- 1 day')),
                    'valor' => round(($amortizable / $this->datos['periodos']) / $periodos_ano, 2),
                    'periodo' => $periodo
                );
                $total = $total + round(($amortizable / $this->datos['periodos']) / $periodos_ano, 2);
                $periodo++;
                $contador_grupos++;
                if ($contador_grupos % 26 == 0) $grupos++;
            }

            $fecha = date('d-m-Y', strtotime($fecha . '+' . $meses . ' month'));
            $valor = $amortizable / $this->datos['periodos'] / $periodos_ano - $valor;
            $this->lineas[$grupos][$this->ano_fiscal + $contador . '_' . $this->periodo_inicial] = array(
                'ano' => $this->ano_fiscal + $contador,
                'fecha' => $this->fecha_fin,
                'valor' => round($amortizable - $total, 2),
                'periodo' => $this->periodo_inicial
            );
        }
    }
    
    /**
     * TODO PHPDoc
     */
    public function url() {
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
}
