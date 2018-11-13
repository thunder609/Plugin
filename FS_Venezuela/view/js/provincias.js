/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2016  Leonardo Javier Alviarez Hernández leonardoalviarez@consultorestecnologicos.com.ve
 * Copyright (C) 2016  A.C. Consultores Tecnológicos R.L. admin@consultorestecnologicos.com.ve
 * http://www.consultorestecnologicos.com.ve
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
// Listado tomado de wikipedia https://es.wikipedia.org/wiki/Anexo:Estados_federales_de_Venezuela
// consultado el 21-03-2016
var ProvinciaList = [
   {value: "Amazonas"},
   {value: "Anzoátegui"},
   {value: "Apure"},
   {value: "Aragua"},
   {value: "Barinas"},
   {value: "Bolívar"},
   {value: "Carabobo"},
   {value: "Cojedes"},
   {value: "Delta Amacuro"},
   {value: "Distrito Capital"},
   {value: "Falcón"},
   {value: "Guárico"},
   {value: "Lara"},
   {value: "Mérida"},
   {value: "Miranda"},
   {value: "Monagas"},
   {value: "Nueva Esparta"},
   {value: "Portuguesa"},
   {value: "Sucre"},
   {value: "Táchira"},
   {value: "Trujillo"},
   {value: "Vargas"},
   {value: "Yaracuy"},
   {value: "Zulia"},
   {value: "Dependencias Federales"},
];

$(document).ready(function() {
   $("#ac_provincia, #ac_provincia2").autocomplete({
      lookup: ProvinciaList,
   });
});
