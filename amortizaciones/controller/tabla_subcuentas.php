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

/**
 * Class editar_amortizacion
 */
class tabla_subcuentas extends fs_controller
{
   
    /**
     * etabla_amortizaciones constructor.
     */
    public function __construct()
    {
        parent::__construct(__CLASS__, 'tabla de subcuentas', 'contabilidad', false, false);
    }

    /**
     * TODO PHPDoc
     */
    protected function private_core() 
    {
    }

}
