/*
 * This file is part of Beneficios
 * Copyright (C) 2017  Albert Dilmé
 * Copyright (C) 2017  Francesc Pineda Segarra  shawe.ewahs@gmail.com
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

//array que controla los observers
var observers = [];

$(document).ready(function () {

    // Recogemos los parámetros de la URL que está visitando el usuario, ya que contiene page
    var userQuery = getQuery();
    var page = userQuery.page;

    // Recogemos los codigos de los documentos en el listado y los ponemos en el array docs
    var dataCodigo= $("[data-codigo]");

    var docs=[];
    dataCodigo.each(function(){
        docs.push($(this).attr("data-codigo"));
    });


    // Añadimos el div donde irá la información
    var html = '<div id="beneficios"></div>';
    if (!page.endsWith("s")){
        $("#lineas, #lineas_a, #lineas_f, #lineas_p").append(html);
    }
    else $(".table-responsive").append(html);

    if (!page.endsWith("s")){
		//Añadimos eventos a los descuentos finales para controlar cambios
	    var adtpor1 =  document.getElementById('adtopor1');
	    if (adtpor1!== null){
		    adtpor1.addEventListener(
		'change',
		function () {
		    show_msg();
		},
		true
	    );
	    var adtpor2 =  document.getElementById('adtopor2');
	    adtpor2.addEventListener(
		'change',
		function () {
		    show_msg();
		},
		true
	    );
	    var adtpor3 =  document.getElementById('adtopor3');
	    adtpor3.addEventListener(
		'change',
		function () {
		    show_msg();
		},
		true
	    );
	    var adtpor4 =  document.getElementById('adtopor4');
	    adtpor4.addEventListener(
		'change',
		function () {
		    show_msg();
		},
		true
	    );
	    var adtpor5 =  document.getElementById('adtopor5');
	    adtpor5.addEventListener(
		'change',
		function () {
		    show_msg();
		},
		true
	    );
	    }	    
    }

    // Consulta AJAX para generar la tabla de beneficios
    $.ajax({
        url: 'index.php?page=beneficios',
        type: "post",
        data: ({docs: docs, page:page}),
        dataType: 'html',
        success: finished
    });

    //************************************************************************
    //Controlamos las mutaciones (var global)
    counter = 0;
    //Donde hay que observar las mutaciones
    var target = $("#lineas_doc" ).get(0);

    if (target != null) {
        // crear instancia observer
        var observer = new MutationObserver(function (mutations) {
            mutation_observer_callback(mutations);
        });

        // enviar el nodo target y las opciones para observer
        observer.observe(target, {
            attributes: true,
            childList: true,
            characterData: false,
            subtree: true
        });
    }

    //***************************************************************************

    //Guardar datos en la bdd cuando pulsamos el botón Guardar de un documento ya creado
    $($('button.btn-primary')[1]).click(function () {
        var bcodigo = dataCodigo.attr("data-codigo");

        if (bcodigo) {
            //quitamos espacios (separadores de millares) i sustituímos comas por puntos (separadores de decimales)
            var bneto = $('#b_neto').text().replace(/\s/g, '');
            bneto = parseFloat(bneto.replace(',', '.'));
            var bcoste = $('#b_coste').text().replace(/\s/g, '');
            bcoste = parseFloat(bcoste.replace(',', '.'));
            var bbeneficio = $('#b_beneficio').text().replace(/\s/g, '');
            bbeneficio = parseFloat(bbeneficio.replace(',', '.'));
            var array_beneficios = [bcodigo, bneto, bcoste, bbeneficio];
            $.ajax({
                url: 'index.php?page=beneficios',
                type: "post",
                data: ({array_beneficios: array_beneficios, page:page}),
                dataType: 'html'
            });
        }
    });


    //Guardar datos en la bdd cuando pulsamos el botón Guardar en nueva_venta
    $('#btn_guardar1, #btn_guardar2').click(function () {

        var bcodigo = $('input[name="tipo"]:checked').val();

        if (bcodigo !== '') {
            //quitamos espacios (separadores de millares) i sustituímos comas por puntos (separadores de decimales)
            var bneto = $('#b_neto').text().replace(/\s/g, '');
            bneto = parseFloat(bneto.replace(',', '.'));
            var bcoste = $('#b_coste').text().replace(/\s/g, '');
            bcoste = parseFloat(bcoste.replace(',', '.'));
            var bbeneficio = $('#b_beneficio').text().replace(/\s/g, '');
            bbeneficio = parseFloat(bbeneficio.replace(',', '.'));
            var array_beneficios = [bcodigo, bneto, bcoste, bbeneficio];
            $.ajax({
                url: 'index.php?page=beneficios',
                type: "post",
                data: ({array_beneficios: array_beneficios}),
                dataType: 'html'
            });
        }
    });


});


// Función que controla las mutaciones
function mutation_observer_callback(mutations) {

    // acciones a realizar por cada mutación
    var mutationRecord = mutations[0];
    // acciones a realizar por cada mutación
    if (mutationRecord.addedNodes[0] !== undefined) {

        if(!(observers.indexOf('cantidad_' + counter)+1)) {
            //agregar onchange
            var cantidad = document.getElementById('cantidad_' + counter);
            // Control adicional por si el elemento fuera null
            if (cantidad != null) {
                observers.push('cantidad_' + counter);
                cantidad.addEventListener(
                    'change',
                    function () {
                        show_msg();
                    },
                    true
                );
            }
        }


        if(!(observers.indexOf('pvp_' + counter)+1)) {
            //agregar onchange
            var pvp = document.getElementById('pvp_' + counter);
            // Control adicional por si el elemento fuera null
            if (pvp != null) {
                observers.push('pvp_' + counter);
                pvp.addEventListener(
                    'change',
                    function () {
                        show_msg();
                    },
                    true
                );
            }
        }


        if(!(observers.indexOf('dto_' + counter)+1)) {
            //agregar onchange
            var dto = document.getElementById('dto_' + counter);
            // Control adicional por si el elemento fuera null
            if (dto != null) {
                observers.push('dto_' + counter);
                dto.addEventListener(
                    'change',
                    function () {
                        show_msg();
                    },
                    true
                );
            }
        }


        //lanzar el mensaje e incrementar el contador
        show_msg();
        counter++;
    } else if (mutationRecord.removedNodes[0] !== undefined) {
        show_msg();
    } else {
        if (counter == 0) {
            //si no se han añadido ni borrado líneas estamos en un documento ya creado y hay que contar las lineas y añadir eventos
            var rowCount = $('#lineas_doc tr').length;

            for (var i = 0; i < rowCount; i++) {
                var lineacant = document.getElementById('cantidad_' + i);
                // Control adicional por si el elemento fuera null
                if (lineacant !== null) {
                    if(!(observers.indexOf('cantidad_' + i)+1)) {
                        //agregar onchange
                        lineacant.addEventListener(
                            'change',
                            function () {
                                show_msg();
                            },
                            true
                        );
                    }
                }

                var lineapvp = document.getElementById('pvp_' + i);
                // Control adicional por si el elemento fuera null
                if (lineapvp !== null) {
                    if(!(observers.indexOf('pvp_' + i)+1)) {
                        //agregar onchange
                        lineapvp.addEventListener(
                            'change',
                            function () {
                                show_msg();
                            },
                            true
                        );
                    }
                }

                var lineadto = document.getElementById('dto_' + i);
                // Control adicional por si el elemento fuera null
                if (lineadto !== null) {
                    if(!(observers.indexOf('dto_' + i)+1)) {
                        //agregar onchange
                        lineadto.addEventListener(
                            'change',
                            function () {
                                show_msg();
                            },
                            true
                        );
                    }
                }

                counter++;
            }
        }
    }

}

//Funcion para enviar los datos de beneficios
function show_msg() {

    //variable que contiene la refererncia del articulo
    var match = $("[data-ref]");

    // Array con los codigos de todos los articulos
    var docs = [];
    $(match).each(function () {
        docs.push($(this).attr("data-ref"));
    });

    //variable que contiene el neto
    var neto = parseFloat($('#aneto').text());
    //variable que contiene las cantidades del articulo
    var cantidad = document.querySelectorAll('input[id^="cantidad_"]');

    //array con todas las cantidades
    var cantidades = [];
    for (var index = 0; index < cantidad.length; index++) {
        cantidades.push(cantidad[index].value);
    }

    //borrar el div beneficios (si existe)
    $('#beneficios').remove();

    // Añadimos el div donde irá la información
    var html = '<div id="beneficios" class="table-responsive"></div>';
    $("#lineas, #lineas_a, #lineas_p").append(html);

    // Consulta AJAX para generar la tabla de beneficios
    $.ajax({
        url: 'index.php?page=beneficios',
        type: "post",
        data: ({docs: docs, cantidades: cantidades, neto: neto}),
        dataType: 'html',
        success: finished
    });
}

//función para insertar el resultado
function finished(result) {
    var div = $('#beneficios');
    //controlamos que no exista ya información para evitar duplicidades
    if (div.is(':empty')) {
        //insertamos el resultado
        div.append(result);
    }

}

//función que devuelve los parámetros de la URL visitada
function getQuery() {
    var userQuery = {};
    location.search.substr(1).split('&').forEach(function (item) {
        userQuery[item.split('=')[0]] = item.split('=')[1];
    });
    return userQuery;
}
