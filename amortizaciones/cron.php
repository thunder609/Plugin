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

require_model('ejercicio.php');
require_model('subcuenta.php');
require_model('amortizacion.php');
require_model('linea_amortizacion.php');
require_model('partida.php');
require_model('asiento.php');

class cron_amortizaciones
{
    private $db;
    private $empresa;

    public function __construct(&$db, &$empresa) {
        $this->db = $db;
        $this->empresa = $empresa;

        $lin_amor = new linea_amortizacion();
        $amor = new amortizacion();
        $listado = $lin_amor->today();
        
        foreach ($listado as $value) {
            if ($value->contabilizada == 0) {
                $amortizacion = $amor->get_by_amortizacion($value->id_amortizacion);
                if ($amortizacion->fin_vida_util == 0 && $amortizacion->amortizando == 1 && $amortizacion->vendida == 0) {
                    $this->contabilizar($value->id_linea);
                }
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
            echo "\nEste amortizado ya ha cumplido su vida útil y se crearon los asientos correspondientes";
        } elseif (!$ejercicio_model->get_by_fecha($linea->fecha, TRUE, FALSE)) {
            echo "\nEl ejercicio no esta creado, crealo y añade el plan contable para poder realizar los apuntes correctamente";
        } elseif ($amortizacion->cod_subcuenta_debe == 0 || $amortizacion->cod_subcuenta_haber == 0) {
            echo "\nEl campo SUBCUENTA DEBE o SUBCUENTA HABER no tienen puesta la subcuenta para crear los asientos contables";           
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
                    echo "\nError al contabilizar la amortización";
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
                    echo "\nSeguramente no esteń importados los datos del plan contable en el ejercicio en el que intentas amortizar";
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
                        echo "\nError al contabilizar la amortización";
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
                        echo "\nLínea contabilizada, asiento creado correctamente";
                        $linea_amor->count($linea->id_linea, $idasiento);
                        $asiento->idasiento = null;
                        $partidadebe->idpartida = null;
                        $partidahaber->idpartida = null;
                    } else {
                        echo "\nError al contabilizar la amortización";
                        $asiento->delete();
                    }
                }
            } else {
                echo "\nLa línea ya estába contabilizada";
            }
        }
    }
}

new cron_amortizaciones($db, $empresa);
