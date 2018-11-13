<?php

/*
 * This file is part of FacturaSctipts
 * Copyright (C) 2013-2017  Carlos Garcia Gomez  neorazorx@gmail.com
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

require_once 'base/fs_model.php';

/**
 * Un país, por ejemplo España.
 */
class modelonumero
{

   public function numtoletras($xcifra)
   {
      $xarray = array(
          0 => "Cero", 1 => "UN", "DOS", "TRES", "CUATRO", "CINCO", "SEIS", "SIETE", "OCHO", "NUEVE",
          "DIEZ", "ONCE", "DOCE", "TRECE", "CATORCE", "QUINCE", "DIECISEIS", "DIECISIETE", "DIECIOCHO", "DIECINUEVE",
          "VEINTI", 30 => "TREINTA", 40 => "CUARENTA", 50 => "CINCUENTA", 60 => "SESENTA", 70 => "SETENTA", 80 => "OCHENTA", 90 => "NOVENTA",
          100 => "CIENTO", 200 => "DOSCIENTOS", 300 => "TRESCIENTOS", 400 => "CUATROCIENTOS", 500 => "QUINIENTOS", 600 => "SEISCIENTOS",
          700 => "SETECIENTOS", 800 => "OCHOCIENTOS", 900 => "NOVECIENTOS"
      );
      //
      $xcifra = trim($xcifra);
      $xlength = strlen($xcifra);
      $xpos_punto = strpos($xcifra, ".");
      $xaux_int = $xcifra;
      $xdecimales = "00";
      if(!($xpos_punto === false))
      {
         if($xpos_punto == 0)
         {
            $xcifra = "0" . $xcifra;
            $xpos_punto = strpos($xcifra, ".");
         }
         $xaux_int = substr($xcifra, 0, $xpos_punto); // obtengo el entero de la cifra a convertir
         $xdecimales = substr($xcifra . "00", $xpos_punto + 1, 2); // obtengo los valores decimales
      }

      $XAUX = str_pad($xaux_int, 18, " ", STR_PAD_LEFT); // ajusto la longitud de la cifra, para que sea divisible por centenas de miles (grupos de 6)
      $xcadena = "";
      for($xz = 0; $xz < 3; $xz++)
      {
         $xaux = substr($XAUX, $xz * 6, 6);
         $xi = 0;
         $xlimite = 6; // inicializo el contador de centenas xi y establezco el límite a 6 dígitos en la parte entera
         $xexit = true; // bandera para controlar el ciclo del While
         while($xexit) {
            if($xi == $xlimite)
            { // si ya ha llegado al límite máximo de enteros
               break; // termina el ciclo
            }

            $x3digitos = ($xlimite - $xi) * -1; // comienzo con los tres primeros digitos de la cifra, comenzando por la izquierda
            $xaux = substr($xaux, $x3digitos, abs($x3digitos)); // obtengo la centena (los tres dígitos)
            for($xy = 1; $xy < 4; $xy++)
            { // ciclo para revisar centenas, decenas y unidades, en ese orden
               switch ($xy)
               {
                  case 1: // checa las centenas
                     if(substr($xaux, 0, 3) < 100)
                     { // si el grupo de tres dígitos es menor a una centena ( < 99) no hace nada y pasa a revisar las decenas
                     }
                     else
                     {
                        $key = (int) substr($xaux, 0, 3);
                        if(TRUE === array_key_exists($key, $xarray))
                        {  // busco si la centena es número redondo (100, 200, 300, 400, etc..)
                           $xseek = $xarray[$key];
                           $xsub = $this->subfijo($xaux); // devuelve el subfijo correspondiente (Millón, Millones, Mil o nada)
                           if(substr($xaux, 0, 3) == 100)
                              $xcadena = " " . $xcadena . " CIEN " . $xsub;
                           else
                              $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                           $xy = 3; // la centena fue redonda, entonces termino el ciclo del for y ya no reviso decenas ni unidades
                        }
                        else
                        { // entra aquí si la centena no es numero redondo (101, 253, 120, 980, etc.)
                           $key = (int) substr($xaux, 0, 1) * 100;
                           $xseek = $xarray[$key]; // toma el primer caracter de la centena y lo multiplica por cien y lo busca en el arreglo (para que busque 100,200,300, etc)
                           $xcadena = " " . $xcadena . " " . $xseek;
                        } // ENDIF ($xseek)
                     } // ENDIF (substr($xaux, 0, 3) < 100)
                     break;
                  case 2: // Chequear las decenas (con la misma lógica que las centenas)
                     if(substr($xaux, 1, 2) < 10)
                     {
                        
                     }
                     else
                     {
                        $key = (int) substr($xaux, 1, 2);
                        if(TRUE === array_key_exists($key, $xarray))
                        {
                           $xseek = $xarray[$key];
                           $xsub = $this->subfijo($xaux);
                           if(substr($xaux, 1, 2) == 20)
                              $xcadena = " " . $xcadena . " VEINTE " . $xsub;
                           else
                              $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                           $xy = 3;
                        } else
                        {
                           $key = (int) substr($xaux, 1, 1) * 10;
                           $xseek = $xarray[$key];
                           if(20 == substr($xaux, 1, 1) * 10)
                              $xcadena = " " . $xcadena . " " . $xseek;
                           else
                              $xcadena = " " . $xcadena . " " . $xseek . " Y ";
                        } // ENDIF ($xseek)
                     } // ENDIF (substr($xaux, 1, 2) < 10)
                     break;
                  case 3: // Chequear las unidades
                     if(substr($xaux, 2, 1) < 1)
                     { // si la unidad es cero, ya no hace nada
                     }
                     else
                     {
                        $key = (int) substr($xaux, 2, 1);
                        $xseek = $xarray[$key]; // obtengo directamente el valor de la unidad (del uno al nueve)
                        $xsub = $this->subfijo($xaux);
                        $xcadena = " " . $xcadena . " " . $xseek . " " . $xsub;
                     } // ENDIF (substr($xaux, 2, 1) < 1)
                     break;
               } // END SWITCH
            } // END FOR
            $xi = $xi + 3;
         } // ENDDO

         if(substr(trim($xcadena), -5, 5) == "ILLON") // si la cadena obtenida termina en MILLON o BILLON, entonces le agrega al final la conjuncion DE
            $xcadena .= " DE";

         if(substr(trim($xcadena), -7, 7) == "ILLONES") // si la cadena obtenida en MILLONES o BILLONES, entoncea le agrega al final la conjuncion DE
            $xcadena .= " DE";

         // ----------- esta línea la puedes cambiar de acuerdo a tus necesidades o a tu país -------
         if(trim($xaux) != "")
         {
            switch ($xz)
            {
               case 0:
                  if(trim(substr($XAUX, $xz * 6, 6)) == "1")
                     $xcadena .= "UN BILLON ";
                  else
                     $xcadena .= " BILLONES ";
                  break;
               case 1:
                  if(trim(substr($XAUX, $xz * 6, 6)) == "1")
                     $xcadena .= "UN MILLON ";
                  else
                     $xcadena .= " MILLONES ";
                  break;
               case 2:
                  if($xcifra < 1)
                  {
                     $xcadena = "CERO con $xdecimales/100";
                  }
                  if($xcifra >= 1 && $xcifra < 2)
                  {
                     $xcadena = "UNO con $xdecimales/100";
                  }
                  if($xcifra >= 2)
                  {
                     $xcadena .= " con $xdecimales/100";
                  }
                  break;
            } // endswitch ($xz)
         } // ENDIF (trim($xaux) != "")

         $xcadena = str_replace("VEINTI ", "VEINTI", $xcadena); // quito el espacio para el VEINTI, para que quede: VEINTICUATRO, VEINTIUN, VEINTIDOS, etc
         $xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
         $xcadena = str_replace("UN UN", "UN", $xcadena); // quito la duplicidad
         $xcadena = str_replace("  ", " ", $xcadena); // quito espacios dobles
         $xcadena = str_replace("BILLON DE MILLONES", "BILLON DE", $xcadena); // corrigo la leyenda
         $xcadena = str_replace("BILLONES DE MILLONES", "BILLONES DE", $xcadena); // corrigo la leyenda
         $xcadena = str_replace("DE UN", "UN", $xcadena); // corrigo la leyenda
      } // ENDFOR ($xz)

      $xcadena = str_replace("UN MIL ", "MIL ", $xcadena); // quito el BUG de UN MIL
      return trim($xcadena);
   }

   // END FUNCTION

   public function subfijo($xx)
   { // esta función genera un subfijo para la cifra
      $xx = trim($xx);
      $xstrlen = strlen($xx);
      if($xstrlen == 1 || $xstrlen == 2 || $xstrlen == 3)
         $xsub = "";
      //
      if($xstrlen == 4 || $xstrlen == 5 || $xstrlen == 6)
         $xsub = "MIL";
      //
      return $xsub;
   }

   function traducefecha($fecha)
   {
      $fecha = strtotime($fecha); // convierte la fecha de formato mm/dd/yyyy a marca de tiempo 
      $diasemana = date("w", $fecha); // optiene el número del dia de la semana. El 0 es domingo 
      switch ($diasemana)
      {
         case "0":
            $diasemana = "Domingo";
            break;
         case "1":
            $diasemana = "Lunes";
            break;
         case "2":
            $diasemana = "Martes";
            break;
         case "3":
            $diasemana = "Miércoles";
            break;
         case "4":
            $diasemana = "Jueves";
            break;
         case "5":
            $diasemana = "Viernes";
            break;
         case "6":
            $diasemana = "Sábado";
            break;
      }
      $dia = date("d", $fecha); // día del mes en número 
      $mes = date("m", $fecha); // número del mes de 01 a 12 
      switch ($mes)
      {
         case "01":
            $mes = "Enero";
            break;
         case "02":
            $mes = "Febrero";
            break;
         case "03":
            $mes = "Marzo";
            break;
         case "04":
            $mes = "Abril";
            break;
         case "05":
            $mes = "Mayo";
            break;
         case "06":
            $mes = "Junio";
            break;
         case "07":
            $mes = "Julio";
            break;
         case "08":
            $mes = "Agosto";
            break;
         case "09":
            $mes = "Septiembre";
            break;
         case "10":
            $mes = "Octubre";
            break;
         case "11":
            $mes = "Noviembre";
            break;
         case "12":
            $mes = "Diciembre";
            break;
      }
      $ano = date("y", $fecha); // optenemos el año en formato 4 digitos 
      $fecha = $dia . " &nbsp;&nbsp;&nbsp;  &nbsp;&nbsp;&nbsp;   " . $mes . "&nbsp;&nbsp;"
              . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
              . "&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
              . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;    " . $ano; // unimos el resultado en una unica cadena $diasemana.
      return $fecha; //enviamos la fecha al programa 
   }

   public function descripcion_divisa($cod)
   {
      $div0 = new divisa();
      $divisa = $div0->get($cod);
      if($divisa)
         return $divisa->descripcion;
   }
}
