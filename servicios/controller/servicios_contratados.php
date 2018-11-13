<?php
/*
 * This file is part of FacturaScripts
 * Copyright (C) 2015-2017    Carlos Garcia Gomez         neorazorx@gmail.com
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
 * Description of servicios_contratados
 *
 * @author carlos
 */
class servicios_contratados extends fs_controller
{

    private $cliente;
    public $grupo;
    public $mostrar;
    public $nuevocli_setup;
    public $offset;
    public $resultados;
    public $total;

    public function __construct()
    {
        parent::__construct(__CLASS__, 'Servicios contratados', 'Ventas', FALSE, FALSE);
    }

    protected function private_core()
    {
        $this->share_extensions();

        $this->cliente = new cliente();
        $this->grupo = new grupo_clientes();

        // cargamos la configuración
        $fsvar = new fs_var();
        $this->nuevocli_setup = $fsvar->array_get(
            array(
            'nuevocli_cifnif_req' => 0,
            'nuevocli_direccion' => 0,
            'nuevocli_direccion_req' => 0,
            'nuevocli_codpostal' => 0,
            'nuevocli_codpostal_req' => 0,
            'nuevocli_pais' => 0,
            'nuevocli_pais_req' => 0,
            'nuevocli_provincia' => 0,
            'nuevocli_provincia_req' => 0,
            'nuevocli_ciudad' => 0,
            'nuevocli_ciudad_req' => 0,
            'nuevocli_telefono1' => 0,
            'nuevocli_telefono1_req' => 0,
            'nuevocli_telefono2' => 0,
            'nuevocli_telefono2_req' => 0,
            'nuevocli_codgrupo' => '',
            'cal_inicio' => "09:00",
            ), FALSE
        );

        $this->mostrar = 'todo';
        if (isset($_GET['mostrar'])) {
            $this->mostrar = $_GET['mostrar'];
        }

        $this->offset = 0;
        if (isset($_GET['offset'])) {
            $this->offset = intval($_GET['offset']);
        }

        $contrato = new contrato_servicio();

        if (isset($_POST['cliente'])) {
            $cliente_s = $this->cliente->get($_POST['cliente']);

            /**
             * Nuevo cliente
             */
            if (isset($_POST['nuevo_cliente'])) {
                if ($_POST['nuevo_cliente'] != '') {
                    $cliente_s = FALSE;
                    if ($_POST['nuevo_cifnif'] != '') {
                        $cliente_s = $this->cliente->get_by_cifnif($_POST['nuevo_cifnif']);
                        if ($cliente_s) {
                            $this->new_advice('Ya existe un cliente con ese ' . FS_CIFNIF . '. Se ha seleccionado.');
                        }
                    }

                    if (!$cliente_s) {
                        $cliente_s = new cliente();
                        $cliente_s->codcliente = $cliente_s->get_new_codigo();
                        $cliente_s->nombre = $cliente_s->razonsocial = $_POST['nuevo_cliente'];
                        $cliente_s->cifnif = $_POST['nuevo_cifnif'];
                        $cliente_s->codserie = $this->empresa->codserie;

                        if (isset($_POST['nuevo_grupo'])) {
                            if ($_POST['nuevo_grupo'] != '') {
                                $cliente_s->codgrupo = $_POST['nuevo_grupo'];
                            }
                        }

                        if (isset($_POST['nuevo_telefono1'])) {
                            $cliente_s->telefono1 = $_POST['nuevo_telefono1'];
                        }

                        if (isset($_POST['nuevo_telefono2'])) {
                            $cliente_s->telefono2 = $_POST['nuevo_telefono2'];
                        }

                        if ($cliente_s->save()) {
                            $dircliente = new direccion_cliente();
                            $dircliente->codcliente = $cliente_s->codcliente;
                            $dircliente->codpais = $this->empresa->codpais;
                            $dircliente->provincia = $this->empresa->provincia;
                            $dircliente->ciudad = $this->empresa->ciudad;
                            $dircliente->descripcion = 'Principal';

                            if (isset($_POST['nuevo_pais'])) {
                                $dircliente->codpais = $_POST['nuevo_pais'];
                            }

                            if (isset($_POST['nuevo_provincia'])) {
                                $dircliente->provincia = $_POST['nuevo_provincia'];
                            }

                            if (isset($_POST['nuevo_ciudad'])) {
                                $dircliente->ciudad = $_POST['nuevo_ciudad'];
                            }

                            if (isset($_POST['nuevo_codpostal'])) {
                                $dircliente->codpostal = $_POST['nuevo_codpostal'];
                            }

                            if (isset($_POST['nuevo_direccion'])) {
                                $dircliente->direccion = $_POST['nuevo_direccion'];
                            }

                            if ($dircliente->save()) {
                                $this->new_message('Cliente agregado correctamente.');
                            }
                        } else
                            $this->new_error_msg("¡Imposible guardar la dirección del cliente!");
                    }
                }
            }

            $con = new contrato_servicio();
            $con->codcliente = $cliente_s->codcliente;
            $con->codagente = $this->user->codagente;
            $con->codpago = $cliente_s->codpago;
            if ($con->save()) {
                $this->new_message('Contrato guardado correctamente.');
                header('Location: ' . $con->url());
            } else {
                $this->new_error_msg('Error al guardar el contrato.');
            }
        } else if (isset($_GET['test'])) {
            $cli0 = new cliente();
            foreach ($cli0->all(mt_rand(0, 1000)) as $cliente) {
                $con = new contrato_servicio();
                $con->codcliente = $cliente->codcliente;
                $con->codagente = $this->user->codagente;
                $con->codpago = $this->empresa->codpago;
                $con->fecha_alta = date(mt_rand(1, 29) . '-3-Y');
                $con->fecha_renovacion = date(mt_rand(1, 29) . '-11-Y');
                $con->importe_anual = mt_rand(600, 60000);
                $con->observaciones = $this->random_string();
                $con->periodo = '+' . mt_rand(7, 120) . 'days';
                $con->fsiguiente_servicio = date('d-m-Y', strtotime($con->fecha_alta . ' ' . $con->periodo));
                $con->save();
            }
        } else if (isset($_GET['delete'])) {
            $con = $contrato->get($_GET['delete']);
            if ($con) {
                if ($con->delete()) {
                    $this->new_message('Contrato eliminado correctamente.');
                } else {
                    $this->new_error_msg('Error al eliminar el contrato.');
                }
            } else {
                $this->new_error_msg('Contrato no encontrado.');
            }
        }

        if (isset($_REQUEST['buscar_cliente'])) {
            $this->buscar_cliente();
        } else if (isset($_GET['minicron'])) {
            $this->minicron();
        } else if ($this->mostrar == 'renovacion') {
            $this->resultados = $contrato->all($this->offset, 'fecha_renovacion ASC');
        } else if ($this->mostrar == 'servicio') {
            $this->resultados = $contrato->all($this->offset, 'fsiguiente_servicio ASC');
        } else {
            $this->resultados = $contrato->all($this->offset);
        }

        $this->total = $contrato->count();
    }

    private function share_extensions()
    {
        $fsext = new fs_extension();
        $fsext->name = 'btn_servicios';
        $fsext->from = __CLASS__;
        $fsext->to = 'ventas_servicios';
        $fsext->type = 'button';
        $fsext->text = '<span class="glyphicon glyphicon-file" aria-hidden="true"></span>'
            . '<span class="hidden-xs">&nbsp; Contratos</span>';
        $fsext->save();

        $fsext2 = new fs_extension();
        $fsext2->name = 'minicron';
        $fsext2->from = __CLASS__;
        $fsext2->to = 'ventas_servicios';
        $fsext2->type = 'minicron';
        $fsext2->params = '&minicron=TRUE';
        $fsext2->save();
    }

    public function anterior_url()
    {
        $url = '';

        if ($this->offset > 0) {
            $url = $this->url() . '&mostrar=' . $this->mostrar . '&offset=' . ($this->offset - FS_ITEM_LIMIT);
        }

        return $url;
    }

    public function siguiente_url()
    {
        $url = '';

        if (count($this->resultados) == FS_ITEM_LIMIT) {
            $url = $this->url() . '&mostrar=' . $this->mostrar . '&offset=' . ($this->offset + FS_ITEM_LIMIT);
        }

        return $url;
    }

    public function aux_class_fservicio($date)
    {
        $time = strtotime($date);

        if (is_null($date)) {
            return 'bg-info';
        } else if ($time < time()) {
            return 'bg-danger';
        } else if ($time - time() < 604800) {
            return 'bg-warning';
        } else {
            return 'bg-success';
        }
    }

    public function nombrecliente($cod)
    {
        $nombre = '-';

        $cliente = $this->cliente->get($cod);
        if ($cliente) {
            $nombre = $cliente->nombre;
        }

        return $nombre;
    }

    private function buscar_cliente()
    {
        /// desactivamos la plantilla HTML
        $this->template = FALSE;

        $json = array();
        foreach ($this->cliente->search($_REQUEST['buscar_cliente']) as $cli) {
            $json[] = array('value' => $cli->nombre, 'data' => $cli->codcliente);
        }

        header('Content-Type: application/json');
        echo json_encode(array('query' => $_REQUEST['buscar_cliente'], 'suggestions' => $json));
    }

    private function minicron()
    {
        $this->template = FALSE;

        $contrato = new contrato_servicio();
        $eje0 = new ejercicio();
        $estado = new estado_servicio();
        $idestado = NULL;
        foreach ($estado->all() as $est) {
            $idestado = $est->id;
            break;
        }
        $offset = 0;
        $contratos = $contrato->all($offset, 'fsiguiente_servicio ASC');
        while ($contratos) {
            foreach ($contratos as $con) {
                if ($con->fsiguiente_servicio) {
                    if (strtotime($con->fsiguiente_servicio) > strtotime($con->fecha_renovacion)) {
                        /// caducado
                    } else if (strtotime($con->fsiguiente_servicio) < strtotime('+1month')) {
                        $cliente = $this->cliente->get($con->codcliente);
                        if ($cliente) {
                            $ejercicio = $eje0->get_by_fecha($con->fsiguiente_servicio);
                            if ($ejercicio) {
                                $servicio = new servicio_cliente();
                                $servicio->codcliente = $cliente->codcliente;
                                $servicio->cifnif = $cliente->cifnif;
                                $servicio->nombrecliente = $cliente->razonsocial;
                                $servicio->codagente = $con->codagente;
                                $servicio->coddivisa = $this->empresa->coddivisa;
                                $servicio->codejercicio = $ejercicio->codejercicio;
                                $servicio->codpago = $con->codpago;
                                $servicio->codserie = $cliente->codserie;
                                $servicio->fecha = $con->fsiguiente_servicio;
                                $servicio->fechainicio = $con->fsiguiente_servicio . ' ' . $this->nuevocli_setup['cal_inicio'];
                                $servicio->idestado = $idestado;

                                foreach ($cliente->get_direcciones() as $dir) {
                                    if ($dir->domfacturacion) {
                                        $servicio->direccion = $dir->direccion;
                                        $servicio->codpostal = $dir->codpostal;
                                        $servicio->ciudad = $dir->ciudad;
                                        $servicio->provincia = $dir->provincia;
                                        $servicio->codpais = $dir->codpais;
                                        break;
                                    }
                                }

                                if ($servicio->save()) {
                                    $con->fsiguiente_servicio = NULL;
                                    if ($con->periodo) {
                                        $con->fsiguiente_servicio = date('d-m-Y', strtotime($servicio->fechainicio . ' ' . $con->periodo));
                                    }

                                    $con->save();
                                } else {
                                    echo "Error al crear el servicio.\n";

                                    foreach ($this->get_errors() as $err) {
                                        echo $err . "\n";
                                    }
                                }
                            }
                        }
                    } else {
                        echo "Cliente no encontrado.\n";
                    }
                }

                $offset++;
            }

            $contratos = $contrato->all($offset, 'fsiguiente_servicio ASC');
        }
    }
}
