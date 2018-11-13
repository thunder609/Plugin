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

//require_model('beneficio.php');

/**
 * Clase beneficios
 *
 * Calcula la diferencia entre precio de venta y precio de coste para cada artículo para tener obtener el beneficio
 */
class beneficios extends fs_controller
{
    /**
     * Objeto modelo
     * @var beneficio
     */
    public $beneficio;

    /**
     * Almacena un array de documentos/articulos de venta
     * @var array
     */
    public $documentos;

    /**
     * Almacena un array de documentos existentes en la bdd
     * @var array
     */
    public $documentos_bdd;

    /**
     * Almacena un array de cantidades de articulos
     * @var array
     */
    public $cantidades;

    /**
     * Almacena un array con los datos a guardar
     * @var array
     */
    public $datos;

    /**
     * Almacena el total neto de nueva_venta
     * @var float
     */
    public $neto;

    /**
     * Almacena la tabla donde se encuentran los $documentos
     * @var float
     */
    public $table;

    /**
     * Acumula el neto de documentos de venta
     * @var float
     */
    public $total_neto;

    /**
     * Acumula el precio de coste de los articulos que hay del array de documentos de venta
     * @var float
     */
    public $total_coste;

    /**
     * Diferencia entre total_neto y total_coste
     * @var float
     */
    public $total_beneficio;

    /**
     * Acumula el precio de coste de los articulos en nueva_venta
     * @var float
     */
    public $total_coste_art;

    /**
     * Trabajar en modo test
     * @var bool
     */
    public $test_mode;

    /**
     * Información para modo test
     * @var string
     */
    public $test;

    /**
     * Información para modo test
     * @var string
     */
    public $test2;

    /**
     * Página recibida de la vista
     * @var string
     */
    public $pagina;

    /**
     * Valor del tipo de coste
     * @var string
     */
    //public $tipo_coste;


    /**
     * Constructor del controlador (heredado de fs_controller)
     *
     * Crea una entrada 'Beneficios' dentro del menú 'informes'
     */
    public function __construct()
    {
        /* Como no necesita aparecer en el menú añadimos los parametros opcionales FALSE */
        parent::__construct(__CLASS__, 'Beneficios', 'informes', false, false);
    }

    /**
     * Devuelve la tabla donde están los documentos
     *
     * @param $array_documentos
     *
     * @return string
     */
    public function table($array_documentos)
    {
        $value = array_shift($array_documentos);
        $sql = "SELECT idfactura FROM facturascli WHERE codigo='$value'";
        $data = $this->db->select("$sql");
        if ($data) {
            $data = 'facturascli';
        } else {
            $sql = "SELECT idalbaran FROM albaranescli WHERE codigo='$value'";
            $data = $this->db->select("$sql");
            if ($data) {
                $data = 'albaranescli';
            } else {
                $sql = "SELECT idpedido FROM pedidoscli WHERE codigo='$value'";
                $data = $this->db->select("$sql");
                if ($data) {
                    $data = 'pedidoscli';
                } else {
                    $data = 'presupuestoscli';
                }
            }
        }

        return $data;
    }

    /**
     * Lógica privada principal del controlador (heredado de fs_controller)
     *
     */
    public function private_core()
    {
        $this->share_extension();
        /// SOLO CAMBIAR EN MODO DESARROLLO
        $this->test_mode = FS_DB_HISTORY;
        // Mensajes por defecto
        $this->test = 'No se han recibido datos';
        $this->test2 = 'No se ha recibido pagina';

        $this->test = '';
        $this->documentos = filter_input(INPUT_POST, 'docs', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $this->cantidades = filter_input(INPUT_POST, 'cantidades', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
        $this->pagina = filter_input(INPUT_POST, 'page');
        // Obtenemos que valor de coste a coger, medio o manual
        /*if ($GLOBALS['config2']['cost_is_average']){
            $this->tipo_coste = "costemedio";
        } else {
            $this->tipo_coste = "preciocoste";
        }*/

        // Si guardamos un documento actualizamos o insertamos en la bdd
        if (isset($_POST['array_beneficios'])) {
            $this->datos = filter_input(INPUT_POST, 'array_beneficios', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);
            $this->guardar($this->pagina);
        } else {
            // Si nos pasan cantidades estamos creando o editando un documento
            if (!empty($this->cantidades)) {
                $this->neto = filter_input(INPUT_POST, 'neto', FILTER_DEFAULT);
                $this->total_neto = $this->neto;
                $this->total_coste = $this->calcTotalCoste($this->documentos, $this->cantidades);
                $this->total_beneficio = $this->calcBeneficio($this->total_neto, $this->total_coste);


                if ($this->test_mode) {
                    // Testear recepción de datos
                    if (!empty($this->documentos)) {
                        $this->test = json_encode($this->documentos);
                    }
                    $this->test2 = json_encode($this->pagina);
                }
            } else {
                if (!empty($this->documentos)) {
                    $totalneto_bdd = 0;
                    $totalcoste_bdd = 0;
                    $totalbeneficio_bdd = 0;

                    // Comprovar códigos existentes en la bdd
                    $ben = new beneficio();
                    $this->documentos_bdd = $ben->getByCodigo($this->documentos, $this->pagina);
                    // Recogemos los datos de la bdd y sumamos
                    foreach ($this->documentos_bdd as $d) {
                        $totalneto_bdd += $d->precioneto;
                        $totalcoste_bdd += $d->preciocoste;
                        $totalbeneficio_bdd += $d->beneficio;
                        $codigo = $ben->getCodigoNombre($this->pagina);
                        // Quitamos del array los códigos que ya están en la bdd beneficios
                        if (($key = array_search($d->$codigo, $this->documentos, false)) !== false) {
                            unset($this->documentos[$key]);
                        }
                    }

                    // Calculamos valores que no están en la bdd sobre el precio de coste actual del artículo
                    $this->table = $this->table($this->documentos);
                    $this->total_neto = $this->calcTotalNeto($this->documentos);
                    $this->total_coste = $this->calcTotalCoste($this->documentos, $this->cantidades);
                    $this->total_beneficio = $this->calcBeneficio($this->total_neto, $this->total_coste);

                    // Sumamos los valores que están en la bdd y los que no están
                    $this->total_neto += $totalneto_bdd;
                    $this->total_coste += $totalcoste_bdd;
                    $this->total_beneficio += $totalbeneficio_bdd;

                    if ($this->test_mode) {
                        // Testear recepción de datos
                        $this->test = json_encode($this->documentos);
                        $this->test2 = json_encode($this->pagina);
                    }
                } else {
                    if ($this->test_mode) {
                        // Testear recepción de datos
                        if (!empty($this->documentos_bdd)) {
                            $this->test = json_encode($this->documentos_bdd);
                        }
                        $this->test2 = json_encode($this->pagina);
                    }
                }
            }
        }
    }

    /**
     * Devuelve el importe total neto del array de documentos recibido
     *
     * @param array $array_documentos
     *
     * @return float
     */
    private function calcTotalNeto($array_documentos)
    {
        $totalneto = 0;

        // Buscamos los netos de las facturas recibidas en $array_documentos
        $sql = 'SELECT neto FROM ' . $this->table
            . " WHERE codigo IN ('" . implode("', '", $array_documentos) . "')";
        $data = $this->db->select($sql);
        if ($data) {
            foreach ($data as $d) {
                $totalneto += $d['neto'];
            }
        }

        return (float)$totalneto;
    }

    /**
     * Devuelve el cálculo del beneficio
     *
     * @param float $total_neto
     * @param float $total_coste
     *
     * @return float
     */
    private function calcBeneficio($total_neto, $total_coste)
    {
        return (float)($total_neto - $total_coste);
    }

    /**
     * Almacena beneficios en la bdd
     */
    private function guardar($pagina)
    {
        $this->beneficio = new beneficio();
        $campo = $this->beneficio->getCodigoNombre($pagina);
        $code = $this->datos[0];
        $array = $this->beneficio->getByCodigo($this->datos, $pagina);

        //Si ya existe en la bdd, cargamos sus datos
        if(!empty($array)){
            $this->beneficio = $array[0];
        }
        else{
            //no existe en la bdd, necesitamos insertar
            //si tenemos parte de la tabla en vez del código estamos guardando un nuevo doc y necesitamos buscar su código con lastcod()
            //si ya tenemos el código solamente necesitamos saber a qué tabla pertenece
            switch ($code) {
                case 'presupuesto':
                    $this->beneficio->codigo_pre = $this->beneficio->lastcod('presupuesto');
                    break;
                case 'pedido':
                    $this->beneficio->codigo_ped = $this->beneficio->lastcod('pedido');
                    break;
                case 'albaran':
                    $this->beneficio->codigo_alb = $this->beneficio->lastcod('albaran');
                    break;
                case 'factura':
                    $this->beneficio->codigo_fac = $this->beneficio->lastcod('factura');
                    break;
                default:
                    switch($campo) {
                        case 'codigo_pre':
                            $this->beneficio->codigo_pre = $code;
                            break;
                        case 'codigo_ped':
                            $this->beneficio->codigo_ped = $code;
                            break;
                        case 'codigo_alb':
                            $this->beneficio->codigo_alb = $code;
                            break;
                        case 'codigo_fac':
                            $this->beneficio->codigo_fac = $code;
                            break;
                    }
            }
        }


        $this->beneficio->precioneto = $this->datos[1];
        $this->beneficio->preciocoste = $this->datos[2];
        $this->beneficio->beneficio = $this->datos[3];
        $this->beneficio->save();
    }

    /**
     * Devuelve el importe total de coste del array de documentos recibido
     *
     * @param array $array_documentos
     * @param array $array_cantidades
     *
     * @return float
     */
    private function calcTotalCoste($array_documentos, $array_cantidades)
    {
        $totalcoste = 0;

        //si hay información en $array_cantidades estamos en nueva_venta
        if (!empty($this->cantidades)) {
            // Buscamos los costes de los articulos recibidos en $array_documentos
            foreach ($array_documentos as $key => $document) {
                if (!empty($document)) {
                    $sql = "SELECT preciocoste FROM articulos WHERE referencia = '" . $document . "'";
                    $data = $this->db->select($sql);

                    foreach ($data as $d) {
                        $totalcoste += ($d['preciocoste'] * $array_cantidades[$key]);
                    }
                }
            }
        } else {
            // Si no hay información en $array_cantidades estamos tratando con documentos guardados y
            // necesitamos saber a qué tabla pertenecen
            switch ($this->table) {
                case 'facturascli':
                    $doc = 'factura';
                    break;
                case 'albaranescli':
                    $doc = 'albaran';
                    break;
                case 'pedidoscli':
                    $doc = 'pedido';
                    break;
                case 'presupuestoscli':
                    $doc = 'presupuesto';
                    break;
            }

            // Buscamos la referencia, preciocoste, cantidad y pvptotal de las facturas recibidas en $array_facturas
            $sql = 'SELECT articulos.referencia, articulos.preciocoste, lineas' . $this->table . '.cantidad, lineas' . $this->table . '.pvptotal'
                . ' FROM articulos, ' . $this->table
                . ' LEFT JOIN lineas' . $this->table . ' ON lineas' . $this->table . '.id' . $doc . ' = ' . $this->table . '.id' . $doc
                . ' WHERE lineas' . $this->table . '.referencia = articulos.referencia AND '
                . $this->table . ".codigo IN ('" . implode("', '", $array_documentos) . "')";


            $data = $this->db->select($sql);
            if ($data) {
                foreach ($data as $d) {
					$preciocoste = $d['preciocoste'];
                    $cantidad = $d['cantidad'];
                    $costeporcantidad = $preciocoste * $cantidad;
                    $totalcoste += $costeporcantidad;
                }
            }
        }

        return (float)$totalcoste;
    }

    /**
     * Extensión para integrarse en otras páginas (heredado de fs_controller)
     */
    private function share_extension()
    {
        $jsPath = '<script type="text/javascript" src="' . FS_PATH . 'plugins/beneficios/view/js/beneficios.js">';
        $jsPath .= '</script>';

        $extensions = [
            [
                'name' => 'beneficios_facturas',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_facturas',
                'type' => 'head',
                'text' => $jsPath,
                'params' => ''
            ],
            [
                'name' => 'beneficios_albaranes',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_albaranes',
                'type' => 'head',
                'text' => $jsPath,
                'params' => ''
            ],
            [
                'name' => 'beneficios_pedidos',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_pedidos',
                'type' => 'head',
                'text' => $jsPath,
                'params' => ''
            ],
            [
                'name' => 'beneficios_presupuestos',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_presupuestos',
                'type' => 'head',
                'text' => $jsPath,
                'params' => ''
            ],
            [
                'name' => 'beneficios_factura',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_factura',
                'type' => 'head',
                'text' => $jsPath,
                'params' => ''
            ],
            [
                'name' => 'beneficios_albaran',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_albaran',
                'type' => 'head',
                'text' => $jsPath,
                'params' => ''
            ],
            [
                'name' => 'beneficios_pedido',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_pedido',
                'type' => 'head',
                'text' => $jsPath,
                'params' => ''
            ],
            [
                'name' => 'beneficios_presupuesto',
                'page_from' => __CLASS__,
                'page_to' => 'ventas_presupuesto',
                'type' => 'head',
                'text' => $jsPath,
                'params' => ''
            ],
            [
                'name' => 'beneficios_nueva_venta',
                'page_from' => __CLASS__,
                'page_to' => 'nueva_venta',
                'type' => 'head',
                'text' => $jsPath,
                'params' => ''
            ],
            [
                'name' => 'beneficios_editar_factura',
                'page_from' => __CLASS__,
                'page_to' => 'editar_factura',
                'type' => 'head',
                'text' => $jsPath,
                'params' => ''
            ]
        ];
        foreach ($extensions as $ext) {
            $fsext = new fs_extension($ext);
            $fsext->save();
        }
    }
}
