<h1>Plugin "Amortizaciones"</h1>
Plugin para FacturaScripts que permite crear amortizaciones del inmovilizado a través de las facturas de compra.
https://www.facturascripts.com
<br/>

<strong>Características:</strong>
<ul>
   <li>Crear amortizaciones desde las facturas de compra.</li>
   <li>Posibilidad de realizar la amortización sobre un artículo en concreto o sobre el total de la factura.</li>
   <li>Contabilización anual, trimestral o mensual.</li>
</ul>

<br/>
<strong>Tareas pendientes:</strong>
<ul>
   <li>Introducir más tipos de contabilización, actualmente solo se soporta "Constante", hay que añadir el método de "Suma de digitos".</li>
   <li>Terrenos y bienes naturales, habría que poner la 281 Amortización Acumulada Inmovilizado Material</li>
   <li>Fondo de comercio, habría que poner la 2804 Amortización acumulada del fondo de comercio</li>
   <li>La cuenta 281 y la 2804 no están en el plan contable de FacturaScripts</li>
   <li>Mejorar la paginación</li>
   <li>Agregar más pestañas en "amortizaciones", a parte de "pendientes", "anuladas", "completadas", "vida útil finalizada", o un desplagable que actue de filtro.</li>
   <li>-Si nos referimos a la paginación de la página "amortizaciones" no tiene mucho lio, sería coger cualquier otra paginación y adaptarla, la de los articulos por ejemplo.</li>
</ul>

<br/>
<strong>Errores:</strong>
<ul>
   <li>Si un asiento se elimina desde la contabidad/asientos, la amortización no lo tendra en cuenta, y este aparecera como contabilizado en "editar_amortizacion".</li>
   <li>Idea: Comprobar si todos los asientos de las líneas contabilizadas existen con CRON.</li>
   <li>Dentro de "editar_amortizacion", si le das a "Modificar, cambiar fecha" y despues pulsas en el botón "Ver periodos para los diferentes amortizados",
   el boton "Modificar" y todos los menus desplegables dejan de funcionar, también ocurre al crear una amortización y darle a los botones verdes que sacan ventanas emergentes, 
   estos botones acceden a un html externo a traves de javascript, es un error muy raro, porque a veces funciona y a veces no</li>
</ul>

<br/>
<strong>Soluciones:</strong>
<ul>
   <li>Si al crear o eliminar un asiento desde editar_amortizacion sale un error como este "Error al eliminar tmp/7Yxb2c6WwSlX1OKUJHmM/libro_mayor/143.pdf".</li>
   <li>Es un error de permisos, y se debe ejecutar el siguiente comando.</li>
   <li>chmod -R o+w /donde/este/la/carpeta</li>
</ul>

<br/>
<strong>v4</strong> 28-07-2017
<ul>
   <li>Posibilidad de elegir entre contabilización ANUAL, TRIMESTRAL o MENSUAL.</li>
   <li>Ahora el inicio del año fiscal no es siempre el 1 de enero, sino que coge la fecha inicial del EJERCICIO que coincida con el inicio de la amortización.</li>
   <li>Genera asientos contables.</li>
   <li>Permite FINALIZAR la vida de un amortizado, creando los asientos correspondientes.</li>
   <li>Se han introducido 2 botones que permiten ver en el periodo MÄXIMO de años establecido para 
   cada tipo de amortizado, y las SUBCUENTAS contables de cada tipo de amortizado. 
   Estos tables están en 2 HTML SEPARADOS, con la intención de que sea más sencillo adaptarlo al plan contable de cualquier país.</li>
</ul>

<br/>
<strong>v5</strong> 24-08-2017
<ul>
   <li>Menú, ahora el enlace a las amortizaciones se encuentra en "Contabilidad" en lugar de en "Compras".</li>
   <li>Autocompletado al poner las subcuentas contables, como en los artículos.</li>
   <li>Si contabilizamos una línea y el ejercicio contable no esta creado, no lo creara, sino que te dira que lo creas e importes los datos contables.</li>
   <li>Asistente para aumentar el valor de las amortizaciones a partir de una fecha concreta, por si hay que realizar alguna reparación en un amortizado.</li>
   <li>Asistente para aumentar y disminuir los años o periodos de una amortización a partir de una fecha concreta.</li>
</ul>

<br/>
<strong>v6</strong> 24-08-2017
<ul>
   <li>Solucionar un pequeño error al visualizar las tablas de periodos de las amortizaciones y las tablas de subcuentas.</li>
</ul>

<br/>
<strong>v7</strong> 
<ul>
   <li>Venta de amortizaciones soportada</li>
   <li>Mejora del reponsive, ahora se adapta mejor a todo tipo de pantallas</li>
   <li>Solucionado error al crear el asiento de "Finalizar vida útil" de una amortizacion</li>
</ul>
<br/>
<strong>Aviso</strong> 
<ul>
   <li>El asiento de finalizar vida útil se generaba mal, si habeis finalizado la vida útil de un amortizado, debeis entrar en esa amortización, reanudarla, y finalizarla de nuevo</li>
   <li>Asi el asiento de generara bien</li>
</ul>

<br/>
<strong>v8</strong> 
<ul>
   <li>Genera asientos automaticamente mediante CRON</li>
   <li>Paginación para las líneas al crear o editar amortizaciones</li>
   <li>Mejorar la visualización de las amortizaciones con colores</li>
   <li>Solucionado error al crear el asiento de "Finalizar vida útil" de una amortizacion</li>
</ul>
<br/>

<br/>
<strong>v9</strong> 
<ul>
   <li></li>
</ul>
<strong>Aviso</strong> 
<ul>
   <li>Mejoras de código</li>
</ul>
<br/>

<br/>
<strong>v10</strong> 
<ul>
   <li>Mejorar la descripción</li>
   <li>Mensaje de aviso si no hay ninguna amortización creada</li>
</ul>
<br/>

<br/>
<strong>v11</strong>
<ul>
   <li>Añadri un par de cuentas a la tabla de subcuentas</li>
   <li>Eliminar la comprobación int de las subcuentas, porque los sistemas de 32bits no admiten números tan altos, 
   sobre todo en windows, porque XAMPP solo está disponible en 32 bits para Windows</li>
</ul>

<br/>
<strong>v11</strong>
<ul>
   <li>Eliminar la comprobación int de las subcuentas, porque los sistemas de 32bits no admiten números tan altos, 
   sobre todo en windows, porque XAMPP solo está disponible en 32 bits para Windows, no se porque no lo hice en la versión anterior</li>
</ul>