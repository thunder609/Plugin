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
require_model('partida.php');
require_model('asiento.php');
require_model('ejercicio.php');
require_model('factura_proveedor.php');
require_model('factura_cliente.php');
require_model('subcuenta.php');
require_model('articulo.php');
require_model('articulo_propiedad.php');

/**
 * Class amortizaciones
 */
class amortizaciones extends fs_controller
{
    /**
     * @var
     */
    public $ano_antes;
    /**
     * @var
     */
    public $listado;
    /**
     * @var
     */
    public $listado_lineas;
    /**
     * @var
     */
    public $listado_pendientes;
    /**
     * @var
     */
    public $offset;
    /**
     * @var
     */
    public $limite;


    /**
     * amortizaciones constructor.
     */
    public function __construct()
    {
        parent::__construct(__CLASS__, 'amortizaciones', 'contabilidad');
    }

    /**
     * TODO PHPDoc
     */
    protected function private_core()
    {
        $amor = new amortizacion();
        $linea_amor = new linea_amortizacion();
        $this->offset = 0;
        $this->limite = FS_ITEM_LIMIT;

        if (filter_input(INPUT_GET, 'offset') !== null) {
            $this->offset = (int)filter_input(INPUT_GET, 'offset', FILTER_VALIDATE_INT);
        }
        if (filter_input(INPUT_POST, 'fecha_inicio') != null) {
            $this->anadir_amortizacion();
        } elseif (filter_input(INPUT_GET, 'delete') !== null) {
            $this->eliminar_amortizacion();
        } elseif (filter_input(INPUT_GET, 'cancel') !== null) {
            $amor->cancel(filter_input(INPUT_GET, 'cancel'));
        } elseif (filter_input(INPUT_GET, 'restart') !== null) {
            $amor->restart(filter_input(INPUT_GET, 'restart'));
        } elseif (filter_input(INPUT_GET, 'endlife') !== null) {
            $this->finalizar_vida_util(filter_input(INPUT_POST, 'id_amortizacion'), filter_input(INPUT_POST, 'fecha'));
        } elseif (filter_input(INPUT_GET, 'sale') !== null || filter_input(INPUT_POST, 'sale') !== null) {
            $this->vender(
                    filter_input(INPUT_POST, 'id_linea', FILTER_VALIDATE_INT), 
                    filter_input(INPUT_POST, 'valor_ultima_linea', FILTER_VALIDATE_FLOAT), 
                    filter_input(INPUT_POST, 'fecha_ultima_linea'),
                    filter_input(INPUT_POST, 'id_factura', FILTER_VALIDATE_INT),
                    filter_input(INPUT_POST, 'amortizado', FILTER_VALIDATE_FLOAT),
                    filter_input(INPUT_POST, 'valor_venta', FILTER_VALIDATE_FLOAT),
                    filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_FLOAT),
                    filter_input(INPUT_POST, 'id_amortizacion', FILTER_VALIDATE_INT),
                    filter_input(INPUT_POST, 'referencia')
                    );
        } elseif (filter_input(INPUT_GET, 'count') !== null) {
            $this->contabilizar(filter_input(INPUT_GET, 'count'));
        } elseif (filter_input(INPUT_GET, 'count_by_date') !== null) {
            $lineas = $linea_amor->get_by_date_and_amort(
                    filter_input(INPUT_POST, 'id_amortizacion', FILTER_VALIDATE_INT), 
                    filter_input(INPUT_POST, 'fecha_inicial'), 
                    filter_input(INPUT_POST, 'fecha_final')
                    );
            
            foreach ($lineas as $key => $value) {
                $this->contabilizar($value->id_linea);
            }
        }
        
        $this->listado = $amor->all($this->offset, $this->limite);
        $linea_amor = new linea_amortizacion();
        $this->listado_lineas = $linea_amor->this_year();

        $this->listar_pendientes();
    }

    /**
     * @param $id_linea_amortizacion
     * @param $valor_ultima_linea
     * @param $fecha_ultima_linea
     * @param $id_factura
     * @param $amortizado
     * @param $valor_venta
     * @param $cantidad
     * @param $id_amortizacion
     * @param $referencia
     */
    private function vender($id_linea_amortizacion,$valor_ultima_linea,$fecha_ultima_linea,$id_factura,$amortizado,$valor_venta,$cantidad,$id_amortizacion,$referencia) 
    {        
        $amor = new amortizacion();
        $linea_amor = new linea_amortizacion();
        $eje = new ejercicio();
        $fact_cli = new factura_cliente();
        $art = new articulo();
        $ap = new articulo_propiedad();
        $subcuenta = new subcuenta();
        $amortizacion = $amor->get_by_amortizacion($id_amortizacion);
        $factura_cliente = $fact_cli->get($id_factura);
        $ejercicio = $eje->get_by_fecha($factura_cliente->fecha);
        $articulo = $art->get($referencia);
        $valor_venta = $valor_venta * $cantidad;
        
        $ultima_linea = $linea_amor->get_by_id_linea($id_linea_amortizacion);
        
        if ($ultima_linea->contabilizada == 1) {
            $linea_amor->eliminar_asiento($id_linea_amortizacion);
        }
        if ($valor_ultima_linea != 0) {
            $this->contabilizar_fin_vida($id_linea_amortizacion, $valor_ultima_linea, $fecha_ultima_linea);
        }
                
        //Haya la subcuenta de venta del articulo
        $propiedades = $ap->array_get($articulo->referencia);
        if (isset($propiedades['codsubcuentaventa'])) {
            $cod_subcuenta_ventas = $propiedades['codsubcuentaventa'];
        } 
        if ($cod_subcuenta_ventas == NULL) {
            $subcuenta_ventas_todo = $subcuenta->get_cuentaesp('VENTAS', $ejercicio->codejercicio);
            $cod_subcuenta_ventas = $subcuenta_ventas_todo->codsubcuenta;
        }

        //$amortizado = $amortizacion->valor - $sin_amortizar;
        $sin_amortizar= $amortizacion->valor - $amortizado;
        
        $partidadebe = new partida();
        $subcuenta_debe = $subcuenta->get_by_codigo(
                $amortizacion->cod_subcuenta_haber, $ejercicio->codejercicio
        );
        
        $partidadiferencia = new partida();
        if ($valor_venta > $sin_amortizar) {
            $diferencia = $valor_venta - $sin_amortizar;
            $resultado = 'beneficios';
            $subcuenta_diferencia = $subcuenta->get_by_codigo(
                    $amortizacion->cod_subcuenta_beneficios, $ejercicio->codejercicio
            );
        } elseif ($valor_venta < $sin_amortizar) {
            $diferencia = $sin_amortizar - $valor_venta;
            $resultado = 'perdidas';
            $subcuenta_diferencia = $subcuenta->get_by_codigo(
                    $amortizacion->cod_subcuenta_perdidas, $ejercicio->codejercicio
            );
        } else {
            $resultado = 'igual';
            $subcuenta_diferencia = $subcuenta->get_by_codigo(
                    $amortizacion->cod_subcuenta_perdidas, $ejercicio->codejercicio
            );
        }
        
        $partidahabertotal = new partida();
        $subcuenta_haber_total = $subcuenta->get_by_codigo(
                $amortizacion->cod_subcuenta_cierre, $ejercicio->codejercicio
        );
        
        $partidaventas = new partida();
        $subcuenta_ventas = $subcuenta->get_by_codigo(
                $cod_subcuenta_ventas, $ejercicio->codejercicio
        );
        
        $asiento = new asiento();
        //Genera la fila en la tabla co_asientos
        if ($resultado == 'beneficios') {
            $asiento->importe = $valor_venta + $amortizado;
        } else {
            $asiento->importe = $amortizacion->valor;
        }
        $asiento->codejercicio = $ejercicio->codejercicio;
        $asiento->concepto = $amortizacion->descripcion;
        $asiento->documento = $amortizacion->documento;
        $asiento->editable = false;
        $asiento->fecha = $factura_cliente->fecha;
        $asiento->numero = $asiento->new_numero();
        $asiento->tipodocumento = 'Amortización';

        if ($asiento->save()) { //Grava los datos en co_asientos
            $idasiento = $asiento->idasiento;
        } else {
            $this->new_error_msg('Error al contabilizar la amortización');
        }
        
        if (!$subcuenta_debe || !$subcuenta_haber_total || !$subcuenta_diferencia || !$subcuenta_ventas) {
            $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
            $asiento->delete();
        } else {
            //DEBE
            $partidadebe->debe = $amortizado;
            $partidadebe->coddivisa = $amortizacion->coddivisa;
            $partidadebe->codserie = $factura_cliente->codserie;
            $partidadebe->codsubcuenta = $amortizacion->cod_subcuenta_haber;
            $partidadebe->concepto = $amortizacion->descripcion;
            $partidadebe->idasiento = $asiento->idasiento;
            $partidadebe->idsubcuenta = $subcuenta_debe->idsubcuenta;

            if ($partidadebe->save()) {
                
            } else {
                $this->new_error_msg('Error al contabilizar la amortización');
                $asiento->delete();
                $linea_amor->discount($id_linea_amortizacion);
            }
            //HABER TOTAL
            $partidahabertotal->haber = $amortizacion->valor;
            $partidahabertotal->coddivisa = $amortizacion->coddivisa;
            $partidahabertotal->codserie = $factura_cliente->codserie;
            $partidahabertotal->codsubcuenta = $amortizacion->cod_subcuenta_cierre;
            $partidahabertotal->concepto = $amortizacion->descripcion;
            $partidahabertotal->idasiento = $asiento->idasiento;
            $partidahabertotal->idsubcuenta = $subcuenta_haber_total->idsubcuenta;

            if ($partidahabertotal->save()) {
                
            } else {
                $this->new_error_msg('Error al contabilizar la amortización');
                $asiento->delete();
                $linea_amor->discount($id_linea_amortizacion);
            }
            
            //DIFERENCIA
            if ($resultado == 'beneficios'){
                $partidadiferencia->haber = $diferencia;
            } elseif ($resultado == 'perdidas') {
                $partidadiferencia->debe = $diferencia;
            } 
            $partidadiferencia->coddivisa = $amortizacion->coddivisa;
            $partidadiferencia->codserie = $factura_cliente->codserie;
            $partidadiferencia->codsubcuenta = $subcuenta_diferencia->codsubcuenta;
            $partidadiferencia->concepto = $amortizacion->descripcion;
            $partidadiferencia->idasiento = $asiento->idasiento;
            $partidadiferencia->idsubcuenta = $subcuenta_diferencia->idsubcuenta;

            if ($resultado != 'igual'){
                if ($partidadiferencia->save()) {
                } else {
                    $this->new_error_msg('Error al contabilizar la amortización');
                    $asiento->delete();
                    $linea_amor->discount($id_linea_amortizacion);
                }
            }

            //VENTAS DEBE
            $partidaventas->debe = $valor_venta;
            $partidaventas->coddivisa = $amortizacion->coddivisa;
            $partidaventas->codserie = $factura_cliente->codserie;
            $partidaventas->codsubcuenta = $cod_subcuenta_ventas;
            $partidaventas->concepto = $amortizacion->descripcion;
            $partidaventas->idasiento = $asiento->idasiento;
            $partidaventas->idsubcuenta = $subcuenta_ventas->idsubcuenta;

            if ($partidaventas->save()) {
                $amor->sale_invoice($id_amortizacion, $factura_cliente->idfactura);
                $amor->sale($id_amortizacion);
                $amor->date_end_life($id_amortizacion, $factura_cliente->fecha);
                $amor->end_life_count($id_amortizacion, $asiento->idasiento);
                $this->new_message('Asiento de venta del amortizado creado correctamente');
            } else {
                $this->new_error_msg('Error al contabilizar la amortización');
                $asiento->delete();
                $linea_amor->discount($id_linea_amortizacion);
            }
        }                      
    }
    
    /**
     * TODO PHPDoc
     */
    private function anadir_amortizacion()
    {
        $amor = new amortizacion();
        
        if (filter_input(INPUT_GET, 'editar') != null) {
            $amortizacion = new amortizacion();
            $amor = $amortizacion->get_by_amortizacion(filter_input(INPUT_POST, 'id_amortizacion', FILTER_VALIDATE_INT));
            $amor->descripcion = filter_input(INPUT_POST, 'descripcion');
            $amor->valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
            $amor->periodos = filter_input(INPUT_POST, 'periodos', FILTER_VALIDATE_INT);
            $amor->fecha_fin = filter_input(INPUT_POST, 'fecha_fin');
        } else {
            //Cuando se pase todo a 64 bits, dentro de un tiempo añadir FILTER_VALIDATE_INT filter_input(INPUT_POST, 'cod_subcuenta_beneficios', FILTER_VALIDATE_INT) para comprobar las subcuentas
            $amor->cod_subcuenta_beneficios = filter_input(INPUT_POST, 'cod_subcuenta_beneficios');
            $amor->cod_subcuenta_cierre = filter_input(INPUT_POST, 'cod_subcuenta_cierre');
            $amor->cod_subcuenta_debe = filter_input(INPUT_POST, 'cod_subcuenta_debe');
            $amor->cod_subcuenta_haber = filter_input(INPUT_POST, 'cod_subcuenta_haber');
            $amor->cod_subcuenta_perdidas = filter_input(INPUT_POST, 'cod_subcuenta_perdidas');
            //Hasta aquí
            $amor->contabilizacion = filter_input(INPUT_POST, 'contabilizacion');
            $amor->descripcion = filter_input(INPUT_POST, 'descripcion');
            $amor->fecha_fin = filter_input(INPUT_POST, 'fecha_fin');
            $amor->fecha_inicio = filter_input(INPUT_POST, 'fecha_inicio');
            $amor->id_factura = filter_input(INPUT_POST, 'id_factura', FILTER_VALIDATE_INT);
            $amor->periodos = filter_input(INPUT_POST, 'periodos', FILTER_VALIDATE_INT);
            $amor->residual = filter_input(INPUT_POST, 'residual', FILTER_VALIDATE_FLOAT);
            $amor->tipo = filter_input(INPUT_POST, 'tipo');
            $amor->valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
            $amor->coddivisa = filter_input(INPUT_POST, 'cod_divisa');
            $amor->codserie = filter_input(INPUT_POST, 'cod_serie');
            $amor->documento = filter_input(INPUT_POST, 'documento');
        }

        $inicio_ejercicio = filter_input(INPUT_POST, 'inicio_ejercicio');
        $inicio_amortizacion = filter_input(INPUT_POST, 'fecha_inicio');
        
        if ($inicio_ejercicio == $inicio_amortizacion) {
            if ($amor->contabilizacion == 'anual') {
                $amor->periodo_final = 1;
            } elseif ($amor->contabilizacion == 'trimestral') {
                $amor->periodo_final = 4;
            } elseif ($amor->contabilizacion == 'mensual') {
                $amor->periodo_final = 12;
            }
        } else {
            $amor->periodo_final = filter_input(INPUT_POST, 'periodo_inicial');
        }
        
        if ($amor->save()) {
            $this->anadir_lineas($amor);
            if (filter_input(INPUT_GET, 'editar') != null) {
                $this->new_message("La amortización se ha modificado con exito");
            } else {
                $this->new_message("La amortización se ha creado con exito"); //Este mensaje aparece aunque la creación de las líneas fallen
            }
        } else {
            $this->new_error_msg("Error al crear la amortización");
        }

    }

    /**
     * TODO PHPDoc
     */
    private function anadir_lineas($amor)
    {
        $linea_model = new linea_amortizacion();
        $linea_amor = new linea_amortizacion();
        $this->listado_lineas = array();
        $contador = 0;
        $periodo_inicial = filter_input(INPUT_POST, 'periodo_inicial', FILTER_VALIDATE_INT);
        $periodo = filter_input(INPUT_POST, 'periodo_inicial', FILTER_VALIDATE_INT);
        $total_periodos = filter_input(INPUT_POST, 'periodos', FILTER_VALIDATE_INT);
        $ano = (int)(Date('Y', strtotime(filter_input(INPUT_POST, 'fecha_inicio'))));
        $ano_fiscal = (filter_input(INPUT_POST, 'ano_fiscal'));
        $ano_fiscal_final = filter_input(INPUT_POST, 'periodos') + $ano_fiscal;
        $ano = (filter_input(INPUT_POST, 'ano_fiscal'));
        $eliminar = false;    
        
        $inicio_ejercicio = (Date('m-d', strtotime(filter_input(INPUT_POST, 'inicio_ejercicio'))));
        $inicio_amortizacion = (Date('m-d', strtotime(filter_input(INPUT_POST, 'fecha_inicio'))));
        
        if ($inicio_ejercicio == $inicio_amortizacion) {
            $contador = 1;
        }

        if ($amor->contabilizacion == 'anual') {
            $periodos_ano = 1;
        } elseif ($amor->contabilizacion == 'trimestral') {
            $periodos_ano = 4;
        } elseif ($amor->contabilizacion == 'mensual') {
            $periodos_ano = 12;
        }
        
        if (filter_input(INPUT_POST, 'disminuir_periodos', FILTER_VALIDATE_FLOAT) != null && filter_input(INPUT_POST, 'disminuir_periodos', FILTER_VALIDATE_FLOAT) < 0) {
            $disminuyendo = true;
            $total_periodos -= filter_input(INPUT_POST, 'disminuir_periodos', FILTER_VALIDATE_INT);
            $ano_fiscal_final -= filter_input(INPUT_POST, 'disminuir_periodos', FILTER_VALIDATE_INT);
        }
        
                
        while ($contador <= $total_periodos) {
            
            if ($ano_fiscal_final == $ano) {
                $periodo = 1;
                $periodos_ano = $periodo_inicial;
            } elseif ($ano_fiscal != $ano) {
                $periodo = 1;
            } 
            
            $linea_amor->id_amortizacion = $amor->id_amortizacion;
            while ($periodo <= $periodos_ano) {
                if ($linea_model->get_by_id_linea(filter_input(INPUT_POST,'id_linea_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_INT))) {
                    $linea_amor = $linea_model->get_by_id_linea(filter_input(INPUT_POST, 'id_linea_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_INT));
                    $linea_amor->fecha = filter_input(INPUT_POST, 'fecha_' . $ano . '_' . $periodo . '');
                    $linea_amor->cantidad = round(filter_input(INPUT_POST, 'cantidad_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_FLOAT), 2);
                } elseif (isset ($disminuyendo)){
                    $eliminar = true;
                    $linea_amor = $linea_model->get_by_id_amor_ano_periodo($amor->id_amortizacion, $ano, $periodo);
                } else {
                    $linea_amor->ano = filter_input(INPUT_POST, 'ano_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_INT);
                    $linea_amor->cantidad = round(filter_input(INPUT_POST, 'cantidad_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_FLOAT), 2);
                    $linea_amor->fecha = filter_input(INPUT_POST, 'fecha_' . $ano . '_' . $periodo . '');
                    $linea_amor->periodo = filter_input(INPUT_POST, 'periodo_' . $ano . '_' . $periodo . '', FILTER_VALIDATE_INT);
                }
                
                if (filter_input(INPUT_POST, 'fecha_cambio') != null) {
                    if (strtotime($linea_amor->fecha) >= strtotime(filter_input(INPUT_POST, 'fecha_cambio'))) {
                        $linea_model->eliminar_asiento($linea_amor->id_linea);
                    }
                }

                if ($eliminar) {
                    $eliminar = false;
                    $linea_amor->delete();
                    $linea_amor->id_linea = null;
                } elseif ($linea_amor->save()) {
                    $linea_amor->id_linea = null;
                } else {
                    if (filter_input(INPUT_GET, 'nueva') != null) {
                        $amor->delete();
                        $linea_amor->delete_by_amor();
                    }
                    $this->new_error_msg("Error al crear la línea de amortización");
                }
                $periodo++;
            }
            $contador++;
            $ano++;
        }
    }

    /**
     * TODO PHPDoc
     */
    private function eliminar_amortizacion()
    {
        $amor = new amortizacion();
        $linea_amor = new linea_amortizacion();
        $amor->id_amortizacion = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
        $linea_amor->id_amortizacion = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

        if ($amor->delete()) {
            $this->new_message("La amortización se ha eliminado con exito");

            if ($linea_amor->delete_by_amor()) {
            } else {
                $this->new_error_msg("Error al eliminar la línea de la amortización");
            }
        } else {
            $this->new_error_msg("Error al eliminar la amortización");
        }
    }

    /**
     * TODO PHPDoc
     */
    private function listar_pendientes()
    {
        $ejercicio = new ejercicio;
        $amor = new amortizacion();
        $linea_amor = new linea_amortizacion();
        $ejercicio_actual = $ejercicio->get_by_fecha($this->today());
        $this->ano_antes = date('d-m-Y', strtotime($ejercicio_actual->fechainicio . '- 1 year'));
        
        $listado = $linea_amor->slope($this->today(), $this->ano_antes);      
        
        if (filter_input(INPUT_GET, 'count_slope') !== null) {

            $this->listado_pendientes = array();
            foreach ($listado as $key => $value) {
                $amortizacion = $amor->get_by_amortizacion($value->id_amortizacion);
                if ($amortizacion->fin_vida_util == 0 && $amortizacion->amortizando == 1  && $amortizacion->vendida == 0) {
                    $this->listado_pendientes[] = array(
                        'ano' => $value->ano,
                        'cantidad' => $value->cantidad,
                        'fecha' => $value->fecha,
                        'id_amortizacion' => $value->id_amortizacion,
                        'id_linea' => $value->id_linea,
                        'descripcion' => $amortizacion->descripcion,
                        'periodo' => $value->periodo
                    );
                }
            }

            foreach ($this->listado_pendientes as $key => $value) {
                $this->contabilizar($value['id_linea']);
            }
            $listado = array();
            $listado = $linea_amor->slope($this->today(), $this->ano_antes);
        }
        
        $this->listado_pendientes = array();
        foreach ($listado as $key => $value) {
            $amortizacion = $amor->get_by_amortizacion($value->id_amortizacion);
            if ($amortizacion->fin_vida_util == 0 && $amortizacion->amortizando == 1  && $amortizacion->vendida == 0) {
                $this->listado_pendientes[] = array(
                    'ano' => $value->ano,
                    'cantidad' => $value->cantidad,
                    'fecha' => $value->fecha,
                    'id_amortizacion' => $value->id_amortizacion,
                    'id_linea' => $value->id_linea,
                    'descripcion' => $amortizacion->descripcion,
                    'periodo' => $value->periodo
                );
            }
        }   
    }

    /**
     * @param $id_linea
     */
    private function contabilizar($id_linea)
    {
        $amor = new amortizacion();
        $linea_amor = new linea_amortizacion();
        $linea = $linea_amor->get_by_id_linea($id_linea);
        $amortizacion = $amor->get_by_amortizacion($linea->id_amortizacion);
        $ejercicio_model = new ejercicio();
        
        if ($amortizacion->fin_vida_util == 1 || $amortizacion->vendida == 1){
            $this->new_error_msg('Este amortizado ya ha cumplido su vida útil y se crearon los asientos correspondientes');
        } elseif (!$ejercicio_model->get_by_fecha($linea->fecha, TRUE, FALSE)) {
            $this->new_error_msg('El ejercicio no esta creado, crealo y añade el plan contable para poder realizar los apuntes correctamente');
        } elseif ($amortizacion->cod_subcuenta_debe == 0 || $amortizacion->cod_subcuenta_haber == 0) {
            $this->new_error_msg('El campo SUBCUENTA DEBE o SUBCUENTA HABER no tienen puesta la subcuenta para crear los asientos contables');           
        } else {
            $ejercicio = $ejercicio_model->get_by_fecha($linea->fecha);

            //completar amortizacion
            if ($ejercicio_model->get_by_fecha($amortizacion->fecha_fin, TRUE, FALSE)) {
                $ejercicio_final = $ejercicio_model->get_by_fecha($amortizacion->fecha_fin);
                $ano_final = (int) (Date('Y', strtotime($ejercicio_final->fechainicio)));
                if ($ano_final == $linea->ano && $linea->periodo == $amortizacion->periodo_final) {
                    $amor->complete($amortizacion->id_amortizacion);
                }
            }
            //Fin de completar amortizacion

            if ($linea->contabilizada != 1) {

                $asiento = new asiento();
                //Genera la fila en la tabla co_asientos
                $asiento->codejercicio = $ejercicio->codejercicio;
                $asiento->concepto = $amortizacion->descripcion;
                $asiento->documento = $amortizacion->documento;
                $asiento->editable = false;
                $asiento->fecha = $linea->fecha;
                $asiento->importe = $linea->cantidad;
                $asiento->numero = $asiento->new_numero();
                $asiento->tipodocumento = 'Amortización';

                if ($asiento->save()) { //Grava los datos en co_asientos
                    $idasiento = $asiento->idasiento;
                } else {
                    $this->new_error_msg('Error al contabilizar la amortización');
                }

                
                $partidadebe = new partida();
                $subcuenta = new subcuenta();
                $subcuenta_debe = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_debe, $ejercicio->codejercicio
                );
                $partidahaber = new partida();
                $subcuenta_haber = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_haber, $ejercicio->codejercicio
                );
                
                if (!$subcuenta_debe || !$subcuenta_haber) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                    $asiento->delete();
                } else {
                    //DEBE
                    $partidadebe->debe = $linea->cantidad;
                    $partidadebe->coddivisa = $amortizacion->coddivisa;
                    $partidadebe->codserie = $amortizacion->codserie;
                    $partidadebe->codsubcuenta = $amortizacion->cod_subcuenta_debe;
                    $partidadebe->concepto = $amortizacion->descripcion;
                    $partidadebe->idasiento = $idasiento;
                    $partidadebe->idsubcuenta = $subcuenta_debe->idsubcuenta;

                    if ($partidadebe->save()) {
                        
                    } else {
                        $this->new_error_msg('Error al contabilizar la amortización');
                        $asiento->delete();
                        $linea_amor->discount($linea->id_linea);
                    }

                    //HABER
                    $partidahaber->haber = $linea->cantidad;
                    $partidahaber->coddivisa = $amortizacion->coddivisa;
                    $partidahaber->codserie = $amortizacion->codserie;
                    $partidahaber->codsubcuenta = $amortizacion->cod_subcuenta_haber;
                    $partidahaber->concepto = $amortizacion->descripcion;
                    $partidahaber->idasiento = $idasiento;
                    $partidahaber->idsubcuenta = $subcuenta_haber->idsubcuenta;

                    if ($partidahaber->save()) {
                        $this->new_message('Línea contabilizada, asiento creado correctamente');
                        $linea_amor->count($linea->id_linea, $idasiento);
                        $asiento->idasiento = null;
                        $partidadebe->idpartida = null;
                        $partidahaber->idpartida = null;
                    } else {
                        $this->new_error_msg('Error al contabilizar la amortización');
                        $asiento->delete();
                    }
                }
            } else {
                $this->new_error_msg('La línea ya está contabilizada');
            }
        }
    }

    /**
     * @param $id_inea
     * @param $cantidad
     * @param $fecha
     */
    private function contabilizar_fin_vida($id_linea,$cantidad,$fecha)
    {
        $amor = new amortizacion();
        $linea_amor = new linea_amortizacion();
        $linea = $linea_amor->get_by_id_linea($id_linea);
        $amortizacion = $amor->get_by_amortizacion($linea->id_amortizacion);
        $ejercicio_model = new ejercicio();
        
        if ($amortizacion->fin_vida_util == 1 || $amortizacion->vendida == 1){
            $this->new_error_msg('Este amortizado ya ha cumplido su vida útil y se crearon los asientos correspondientes');
        } elseif (!$ejercicio_model->get_by_fecha($fecha, TRUE, FALSE)) {
            $this->new_error_msg('El ejercicio no esta creado, crealo y añade el plan contable para poder realizar los apuntes correctamente');
        } elseif ($amortizacion->cod_subcuenta_debe == 0 || $amortizacion->cod_subcuenta_haber == 0) {
            $this->new_error_msg('El campo SUBCUENTA DEBE o SUBCUENTA HABER no tienen puesta la subcuenta para crear los asientos contables');
        } else {

            $ejercicio = $ejercicio_model->get_by_fecha($fecha);

            if ($linea->contabilizada != 1) {

                $asiento = new asiento();
                //Genera la fila en la tabla co_asientos
                $asiento->codejercicio = $ejercicio->codejercicio;
                $asiento->concepto = $amortizacion->descripcion;
                $asiento->documento = $amortizacion->documento;
                $asiento->editable = false;
                $asiento->fecha = $fecha;
                $asiento->importe = $cantidad;
                $asiento->numero = $asiento->new_numero();
                $asiento->tipodocumento = 'Amortización';

                if ($asiento->save()) { //Grava los datos en co_asientos
                    $idasiento = $asiento->idasiento;
                } else {
                    $this->new_error_msg('Error al contabilizar la amortización');
                    return FALSE;
                }

                
                $partidadebe = new partida();
                $subcuenta = new subcuenta();
                $subcuenta_debe = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_debe, $ejercicio->codejercicio
                );
                $partidahaber = new partida();
                $subcuenta_haber = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_haber, $ejercicio->codejercicio
                );

                if (!$subcuenta_debe || !$subcuenta_haber) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                    $asiento->delete();
                    return FALSE;
                } else {
                    $partidadebe->debe = $cantidad;
                    $partidadebe->coddivisa = $amortizacion->coddivisa;
                    $partidadebe->codserie = $amortizacion->codserie;
                    $partidadebe->codsubcuenta = $amortizacion->cod_subcuenta_debe;
                    $partidadebe->concepto = $amortizacion->descripcion;
                    $partidadebe->idasiento = $idasiento;
                    $partidadebe->idsubcuenta = $subcuenta_debe->idsubcuenta;

                    if ($partidadebe->save()) {
                        
                    } else {
                        $this->new_error_msg('Error al contabilizar la amortización');
                        $asiento->delete();
                        return FALSE;
                    }
                    //HABER
                    $partidahaber->haber = $cantidad;
                    $partidahaber->coddivisa = $amortizacion->coddivisa;
                    $partidahaber->codserie = $amortizacion->codserie;
                    $partidahaber->codsubcuenta = $amortizacion->cod_subcuenta_haber;
                    $partidahaber->concepto = $amortizacion->descripcion;
                    $partidahaber->idasiento = $idasiento;
                    $partidahaber->idsubcuenta = $subcuenta_haber->idsubcuenta;

                    if ($partidahaber->save()) {
                        $this->new_message('Línea contabilizada, asiento creado correctamente');
                        $linea_amor->count($linea->id_linea, $idasiento);
                        return TRUE;
                    } else {
                        $this->new_error_msg('Error al crear la línea de partida');
                        $asiento->delete();
                        return FALSE;
                    }
                }
            } else {
                $this->new_error_msg('La línea ya está contabilizada');
            }
        }
    }
    
    /**
     * @param $id
     * @param $fecha
     */
    private function finalizar_vida_util($id, $fecha)
    {
        //Crea el asiento
        $amor = new amortizacion();
        $linea_amor = new linea_amortizacion();
        $amortizacion = $amor->get_by_amortizacion($id);
        $ejercicio_model = new ejercicio();

        if ($amortizacion->fin_vida_util == 1 || $amortizacion->vendida == 1) {
            $this->new_error_msg('Este amortizado ya ha cumplido su vida útil y se crearon los asientos correspondientes');
        } elseif (!$ejercicio_model->get_by_fecha($fecha, FALSE, FALSE)) {
            $this->new_error_msg('El ejercicio no esta creado, crealo y añade el plan contable para poder realizar los apuntes correctamente');
        } elseif (!$ejercicio_model->get_by_fecha($fecha, TRUE, FALSE)) {
            $this->new_error_msg('El ejercicio perteneciente a la fecha de finalización está CERRADO');
        } elseif ($amortizacion->cod_subcuenta_cierre == 0 || $amortizacion->cod_subcuenta_haber == 0 || $amortizacion->cod_subcuenta_perdidas == 0) {
            $this->new_error_msg('El campo SUBCUENTA CIERRE, SUBCUENTA HABER o SUBCUENTA PERDIDAS no tienen puesta la subcuenta para crear los asientos contables');
        } else {
            $ejercicio = $ejercicio_model->get_by_fecha($fecha);

            if ($amortizacion->residual != 0) $sin_amortizar = TRUE;
            
            $sin_amortizar = $amortizacion->residual;
            $amortizado = 0;
            $lineas = $linea_amor->get_by_amortizacion($id);

            if (strtotime($lineas[0]->fecha) > strtotime($fecha)) $sin_amortizar = TRUE;
                        
            foreach ($lineas as $key => $value) {
                if (strtotime($value->fecha) >= strtotime($fecha)) {
                    $sin_amortizar = TRUE;
                } else {
                    $amortizado = $amortizado + $value->cantidad;
                }
            }
            
            if ($sin_amortizar) {

                //saca el perido al que pertenece la fecha
                $periodo_fecha_inicio = $amor->periodo_por_fecha($fecha, $ejercicio->fechafin, $ejercicio->fechainicio, $amortizacion->contabilizacion);

                $ano_fiscal = (int) (date('Y', strtotime($ejercicio->fechainicio)));
                $linea = $linea_amor->get_by_id_amor_ano_periodo($amortizacion->id_amortizacion, $ano_fiscal, $periodo_fecha_inicio['periodo']);
                $fecha_inicio = $periodo_fecha_inicio['fecha_inicio_periodo'];
                $primer_ejercicio = $ejercicio_model->get_by_fecha($amortizacion->fecha_inicio);
                $primer_ano_fiscal = (int) (date('Y', strtotime($primer_ejercicio->fechainicio)));
                $periodo_fecha_inicio = $amor->periodo_por_fecha($amortizacion->fecha_inicio, $ejercicio->fechafin, $ejercicio->fechainicio, $amortizacion->contabilizacion);

                if ($periodo_fecha_inicio['periodo'] == $linea->periodo && $primer_ano_fiscal == $linea->ano) {
                    $fecha_inicio = $amortizacion->fecha_inicio;
                }

                $dias_periodo = $amor->diferencia_dias($fecha_inicio, $linea->fecha) + 1;
                $dias_amortizado = $amor->diferencia_dias($fecha_inicio, $fecha) + 1;

                $valor = round($linea->cantidad / $dias_periodo * $dias_amortizado, 2);

                $amortizado = round($amortizado + $valor, 2);

                //Genera la fila en la tabla co_asientos
                $asiento = new asiento();
                $asiento->codejercicio = $ejercicio->codejercicio;
                $asiento->concepto = $amortizacion->descripcion;
                $asiento->documento = $amortizacion->documento;
                $asiento->editable = false;
                $asiento->fecha = $fecha;
                $asiento->importe = $amortizacion->valor;
                $asiento->numero = $asiento->new_numero();
                $asiento->tipodocumento = 'Fin de vida útil';

                if ($asiento->save()) {
                    $idasiento = $asiento->idasiento;
                } else {
                    $this->new_error_msg('Error al contabilizar la amortización');
                }

                $subcuenta = new subcuenta();

                $partidadebe = new partida();
                $subcuenta_debe = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_haber, $ejercicio->codejercicio
                );
                $partidadebe_perdidas = new partida();
                $subcuenta_debe_perdidas = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_perdidas, $ejercicio->codejercicio
                );
                $partidahaber = new partida();
                $subcuenta_haber = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_cierre, $ejercicio->codejercicio
                );

                if (!$subcuenta_debe || !$subcuenta_haber || !$subcuenta_debe_perdidas) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                    $asiento->delete();
                } else {

                    //DEBE AMORTIZADO
                    $partidadebe->debe = $amortizado;
                    $partidadebe->coddivisa = $amortizacion->coddivisa;
                    $partidadebe->codserie = $amortizacion->codserie;
                    $partidadebe->codsubcuenta = $amortizacion->cod_subcuenta_haber;
                    $partidadebe->concepto = $amortizacion->descripcion;
                    $partidadebe->idasiento = $idasiento;
                    $partidadebe->idsubcuenta = $subcuenta_debe->idsubcuenta;

                    if ($partidadebe->save()) {
                        
                    } else {
                        $this->new_error_msg('Error al crear la línea de partida');
                        $asiento->delete();
                    }

                    //DEBE PERDIDAS
                    $partidadebe_perdidas->debe = $amortizacion->valor - $amortizado;
                    $partidadebe_perdidas->coddivisa = $amortizacion->coddivisa;
                    $partidadebe_perdidas->codserie = $amortizacion->codserie;
                    $partidadebe_perdidas->codsubcuenta = $amortizacion->cod_subcuenta_perdidas;
                    $partidadebe_perdidas->concepto = $amortizacion->descripcion;
                    $partidadebe_perdidas->idasiento = $idasiento;
                    $partidadebe_perdidas->idsubcuenta = $subcuenta_debe_perdidas->idsubcuenta;

                    if ($partidadebe_perdidas->save()) {
                        
                    } else {
                        $this->new_error_msg('Error al crear la línea de partida');
                        $asiento->delete();
                        $linea_amor->discount($linea->id_linea);
                    }

                    //HABER
                    $partidahaber->haber = $amortizacion->valor;
                    $partidahaber->coddivisa = $amortizacion->coddivisa;
                    $partidahaber->codserie = $amortizacion->codserie;
                    $partidahaber->codsubcuenta = $amortizacion->cod_subcuenta_cierre;
                    $partidahaber->concepto = $amortizacion->descripcion;
                    $partidahaber->idasiento = $idasiento;
                    $partidahaber->idsubcuenta = $subcuenta_haber->idsubcuenta;

                    if ($partidahaber->save()) {
                        if ($this->contabilizar_fin_vida($linea->id_linea, $valor, $fecha)) {
                            $amor->end_life($id);
                            $amor->date_end_life($id, $fecha);
                            $amor->end_life_count($id, $idasiento);
                            $this->new_message('Finalizada la vida útil del amortizado');
                        } else {
                            $asiento->delete();
                        }
                    } else {
                        $this->new_error_msg('Error al crear la línea de partida');
                        $asiento->delete();
                    }
                }
            } else {
                //Genera la fila en la tabla co_asientos
                $asiento = new asiento();
                $asiento->codejercicio = $ejercicio->codejercicio;
                $asiento->concepto = $amortizacion->descripcion;
                $asiento->documento = $amortizacion->documento;
                $asiento->editable = false;
                $asiento->fecha = $fecha;
                $asiento->importe = $amortizacion->valor;
                $asiento->numero = $asiento->new_numero();
                $asiento->tipodocumento = 'Fin de vida útil';

                if ($asiento->save()) {
                    $idasiento = $asiento->idasiento;
                } else {
                    $this->new_error_msg('Error al contabilizar la amortización');
                }

                $subcuenta = new subcuenta();
                $partidadebe = new partida();
                $subcuenta_debe = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_haber, $ejercicio->codejercicio
                );
                $partidahaber = new partida();
                $subcuenta_haber = $subcuenta->get_by_codigo(
                        $amortizacion->cod_subcuenta_cierre, $ejercicio->codejercicio
                );

                if (!$subcuenta_debe || !$subcuenta_haber) {
                    $this->new_error_msg('Seguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar');
                    $asiento->delete();
                } else {
                    //DEBE
                    $partidadebe->debe = $amortizacion->valor;
                    $partidadebe->coddivisa = $amortizacion->coddivisa;
                    $partidadebe->codserie = $amortizacion->codserie;
                    $partidadebe->codsubcuenta = $amortizacion->cod_subcuenta_haber;
                    $partidadebe->concepto = $amortizacion->descripcion;
                    $partidadebe->idasiento = $idasiento;
                    $partidadebe->idsubcuenta = $subcuenta_debe->idsubcuenta;

                    if ($partidadebe->save()) {
                        
                    } else {
                        $this->new_error_msg('Error al crear la línea de partida');
                        $asiento->delete();
                    }

                    //HABER
                    $partidahaber->haber = $amortizacion->valor;
                    $partidahaber->coddivisa = $amortizacion->coddivisa;
                    $partidahaber->codserie = $amortizacion->codserie;
                    $partidahaber->codsubcuenta = $amortizacion->cod_subcuenta_cierre;
                    $partidahaber->concepto = $amortizacion->descripcion;
                    $partidahaber->idasiento = $idasiento;
                    $partidahaber->idsubcuenta = $subcuenta_haber->idsubcuenta;

                    if ($partidahaber->save()) {
                        $amor->end_life($id);
                        $amor->date_end_life($id, $fecha);
                        $amor->end_life_count($id, $asiento->idasiento);
                        $this->new_message('Finalizada la vida útil del amortizado');
                    } else {
                        $this->new_error_msg('Error al crear la línea de partida');
                        $asiento->delete();
                        $linea_amor->discount($linea->id_linea);
                    }
                }
            }
        }
    }
    
}
