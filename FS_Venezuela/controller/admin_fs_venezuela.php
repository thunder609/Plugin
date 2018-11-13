<?php

/**
 * This file is part of FacturaSctipts
 * Copyright (C) 2015-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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
require_model('impuesto.php');
require_model('ejercicio.php');
require_model('subcuenta.php');

/**
 * Admin FS_Venezuela
 *
 * @author Dixon Martinez
 */
class admin_fs_venezuela extends fs_controller {

    public function __construct() {
        parent::__construct(__CLASS__, 'FS Venezuela', 'admin');
    }

    private $taxes = array(
        "iva12" => array(
            "value" => "IVA12",
            "desc" => "IVA 12%",
            "porcRecar" => 0,
            "porc" => 12
        ), "exc" => array(
            "value" => "EXENTO",
            "desc" => "EXENTO",
            "porcRecar" => 0,
            "porc" => 0
        ), "iva8" => array(
            "value" => "IVA8",
            "desc" => "IVA 8%",
            "porcRecar" => 0,
            "porc" => 8
        ),
    );

    protected function private_core() {
        $value = filter_input(INPUT_GET, "value");
        echo json_encode(filter_input(INPUT_GET, "opc"));
        echo json_encode($value);
        // exit();
        if (!empty(filter_input(INPUT_GET, "opc"))) {
            switch (filter_input(INPUT_GET, "opc")) {
                case "country": $this->set_country_code($value);                    
                    break;
                case "currency" && !empty($value): $this->set_currency($value);
                    break;
                case "taxes": $this->set_taxes();
                    break;
                case "regimen": $this->set_regimen();
                    break;
            }
            $this->check_accounting_year();
            $this->share_extensions();
        }
    }

    private function set_regimen() {
        $this->new_message("Estableciendo Regímenes de Impuesto");
        $fsvar = new \fs_var(); // Si hay una lista personalizada en fs_vars, la usamos
        $fsvar->simple_save('cliente::regimenes_iva', 'IVA, EXCENTO, IVA8') ? $this->new_message('Datos guardados correctamente.') : $this->new_message('Los Datos no fueron guardados.');
    }

    private function set_country_code($value) {
        $this->empresa->codpais = $value;
        $msg = $this->empresa->save() ? "Empresa actualizada correctamente" : "Ya es el país por defecto ó no seleccionastes algún país";
        $this->new_message($msg);
    }

    private function set_currency($value) {
        $this->empresa->coddivisa = $value;
        $msg = $this->empresa->save() ? "Empresa acutalizada correctamente" : "Ya es la moneda por defecto ó no seleccionastes alguna moneda";
        $this->new_message($msg);
    }

    /**
     * Export/Import Documents Account
     * Exportar/Importar Documentos de Cuenta Contable
     */
    private function share_extensions() {
        $fsext = new fs_extension();
        $fsext->name = 'puc_venezuela';
        $fsext->from = __CLASS__;
        $fsext->to = 'contabilidad_ejercicio';
        $fsext->type = 'fuente';
        $fsext->text = 'PUC Venezuela';
        $fsext->params = 'plugins/FS_Venezuela/extras/venezuela.xml';
        $fsext->save();
    }

    /**
     * Verify is Accounting Year
     */
    private function check_accounting_year() {
        $ejer = new ejercicio();
        foreach ($ejer->all_abiertos() as $accountingYear) {
            if ($accountingYear->longsubcuenta != 6) {
                $accountingYear->longsubcuenta = 6;
                $accountingYear->save() ?
                                $this->new_message('Datos del ejercicio ' . $accountingYear->codejercicio . ' modificados correctamente.') : $this->new_error_msg('Error al modificar el ejercicio.');
            }
        }
    }

    /**
     * Set Taxes
     * Set taxes by Venezuelan
     */
    private function set_taxes() {
        $this->new_message("Configurando Impuestos");
        $allTaxes = new impuesto(); //  Delete taxes existing
        foreach ($allTaxes->all() as $tax) {
            $tax->delete();
        }
        foreach ($this->taxes as $value) {
            $tax = new impuesto();
            $tax->codimpuesto = $value["value"];
            $tax->descripcion = $value["desc"];
            $tax->recargo = $value["porcRecar"];
            $tax->iva = $value["porc"];
            $tax->save();
        }
        $this->new_message("Impuestos agregados correctamente");
    }

    /**
     * Verify if Currency is Aligne Right
     * @return boolean
     */
    public function is_format_currency() {
        if (FS_POS_DIVISA == 'right') {
            return TRUE;
        }
        return FALSE;
    }

    /**
     * Verify if exists Tax of Venezuelan
     * @return boolean
     */
    public function is_set_tax() {
        $opc = false;
        foreach ($this->taxes as $value) {
            $tax = new impuesto(); //   Instancio al impuesto
            $tax->codimpuesto = $value['value']; // Inicializo el codigo del impuesto NOTA: Averiguar una mejor forma de consultar a la base de datos
            $opc = $tax->exists(); //   Verifico si existe el impuesto y lo asigno a  la variable
            //  Nota mejorar método para consultar si existen
        }
        return $opc;
    }

    public function is_set_regimenes() {
        $fsvar = new \fs_var(); // Si hay una lista personalizada en fs_vars, la usamos
        if (!$fsvar->simple_get("cliente::regimenes_iva")) {
            return FALSE;
        }
        return TRUE;
    }

    /**
     * Estable el plan de cuentas
     * @return boolean
     */
    public function is_set_accounting_year() {
        $accountingYear0 = new ejercicio();
        $accountingYear = $accountingYear0->get_by_fecha($this->today());
        if ($accountingYear) {
            $subAccountingYear0 = new subcuenta();
            if (!empty($subAccountingYear0->all_from_ejercicio($accountingYear->codejercicio))) {
                return TRUE;
            }
        }
        return FALSE;
    }

}
