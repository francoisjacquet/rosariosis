<?php
/**
 * Spanish Help texts
 *
 * Texts are organized by:
 * - Module
 * - Profile
 *
 * @author François Jacquet
 * 
 * @uses Heredoc syntax
 * @see  http://php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc
 */

// DEFAULT
if ( User( 'PROFILE' ) === 'admin' )
	$help['default'] = <<<HTML
<p>
	Como administrador, usted puede crear las instituciones en el sistema, modificar estudiantes y usuarios, y acceder a los reportes esenciales sobre los estudiantes.
</p>
<p>
	Usted tiene acceso a cualquier institución en el sistema. Para escoger una institución para trabajar, seleccione la institución desde el menú desplegable en el marco izquierdo. El programa refrescara automáticamente el espacio de trabajo con la nueva institución. De la misma manera, usted también puede cambiar el año escolar y el periodo a calificar.
</p>
<p>
	Usando RosarioSIS, usted vera aparecer otras opciones en el marco izquierdo. Cuando usted selecciona un estudiante para trabajar, el nombre del estudiante aparece debajo del menú desplegable del periodo a calificar. Cambiando de servicio, usted sigue trabajando con el estudiante. Si usted quiere trabajar con otro estudiante, haga click sobre la cruz roja al lado del nombre del estudiante. Usted también puede acceder rápidamente a los Datos Personales del estudiante haciendo click sobre el nombre del estudiante. Igualmente ocurre, cuando usted selecciona un usuario para trabajar.
</p>
<p>
	Cuando usted hace click sobre cualquier icono del marco izquierdo, podrá ver una lista de los servicios disponibles en el módulo. Haciendo click sobre cualquier titulo de servicio, este se abrirá en el marco principal, y se actualizará el marco de ayuda (abajo).
</p>
<p>
	En muchos lugares de RosarioSIS, usted podrá ver listas de datos que están modificables. A menudo, usted deberá hacer click primero sobre el dato que usted quiere cambiar para tener acceso a el campo de entrada.
</p>
<p>
	Usted puede salir de RosarioSIS en cualquier momento haciendo click sobre el enlace «Salir» en el marco inferior.
</p>
HTML;

elseif ( User( 'PROFILE' ) === 'teacher' )
	$help['default'] = <<<HTML
<p>
	Como docente, usted puede ver datos de los estudiantes y horarios de los estudiantes que tiene, y con los cuales usted trabaja asistencia, calificaciones y elegibilidad. Usted tiene también un servicio de libro de calificaciones para hacer seguimiento de las calificaciones. El Libro de Calificaciones está integrado en el servicio Entrar Calificaciones Finales como también en el servicio Elegibilidad. Desde el Libro de Calificaciones, Usted también puede imprimir reportes de progreso par cualquier de sus estudiantes.
</p>
<p>
	Para escoger una clase para trabajar, seleccionela en el menú desplegable del marco izquierdo. El programa refrescara automáticamente el espacio de trabajo con la nueva clase. De la misma manera, usted puede también cambiar el año escolar y el periodo a calificar.
</p>
<p>
	Usando RosarioSIS, usted vera aparecer otras opciones en el marco izquierdo. Cuando usted selecciona un estudiante para trabajar, el nombre del estudiante aparece debajo del menú desplegable del periodo a calificar. Moviendose entre los servicios, usted sigue trabajando con el estudiante. Si usted quiere trabajar con otro estudiante, haga click sobre la cruz roja al lado del nombre del estudiante. Usted también puede acceder rápidamente a los Datos Personales del estudiante haciendo click sobre el nombre del estudiante.
</p>
<p>
	Cuando usted hace click sobre cualquier icono del marco izquierdo, usted podrá ver una lista de los servicios disponibles para usted en el módulo. Haciendo click sobre cualquier título de servicio, este se abrirá en el marco principal, y se actualizara el marco de ayuda (abajo).
</p>
<p>
	En el libro de calificaciones, usted podrá ver listas de datos que están modificables. A menudo, usted deberá hacer click primero sobre el dato que usted quiere cambiar para tener acceso a un campo de entrada.
</p>
<p>
	Usted puede salir de RosarioSIS en cualquier momento haciendo click sobre el enlace «Salir» en el marco inferior.
</p>
HTML;

elseif ( User( 'PROFILE' ) === 'parent' )
	$help['default'] = <<<HTML
<p>
	Como padre, usted puede ver los datos de sus estudiantes, los horarios, la elegibilidad, y la asistencia.
</p>
<p>
	Para escoger un estudiante, seleccione el nombre del estudiante en el menú desplegable en el marco izquierdo. El programa refrescara automáticamente el espacio de trabajo con el nuevo estudiante. De la misma manera, usted puede también cambiar el año escolar y el periodo a calificar.
</p>
<p>
	Usando RosarioSIS, usted vera aparecer otras opciones en el marco izquierdo. Cuando usted hace click sobre cualquier icono del marco izquierdo, podrá ver una lista de los programas disponibles para usted en el módulo. Haciendo click sobre cualquier titulo de servicio, este se abrirá en el marco principal, y se actualizara el marco de ayuda (abajo).
</p>
<p>
	Usted puede salir de RosarioSIS en cualquier momento haciendo click sobre el enlace «Salir» en el marco inferior.
</p>
HTML;

elseif ( User( 'PROFILE' ) === 'student' )
	$help['default'] = <<<HTML
<p>
	Como estudiante, usted puede ver sus datos, horario, calificaciones, elegibilidad, y asistencia.
</p>
<p>
	Usted puede cambiar el año escolar y el periodo a calificar en los menús desplegables en el marco izquierdo.
</p>
<p>
	Usando RosarioSIS, usted vera aparecer otras opciones en el marco izquierdo. Cuando usted hace click sobre cualquier icono del marco izquierdo, usted podrá ver una lista de los servicios disponibles para usted en el módulo. Haciendo click sobre cualquier titulo de servicio, este se abrirá en el marco principal, y se actualizara el marco de ayuda (abajo).
</p>
<p>
	Usted puede salir de RosarioSIS en cualquier momento haciendo click sobre el enlace «Salir» en el marco inferior.
</p>
HTML;


// SCHOOL SETUP ---
if ( User( 'PROFILE' ) === 'admin' )
{
	$help['School_Setup/Schools.php'] = <<<HTML
<p>
	<i>Información de la Institución</i> le permite cambiar el nombre, la dirección y el director de la institución. Haga click sobre cualquier dato para cambiarlo. Después de haber hecho los cambios necesarios para su institución, presione el botón «Guardar» para guardar sus cambios.
</p>
HTML;

	$help['School_Setup/Schools.php&new_school=true'] = <<<HTML
<p>
	<i>Agregar una Institución</i> le permite agregar una institución al sistema. Llena los datos de la institución, y presiona el botón «Guardar».
</p>
<p>
	Para cambiar a la nueva institución, cambie el menú desplegable de las instituciones en el marco izquierdo.
</p>
HTML;

	$help['School_Setup/CopySchool.php'] = <<<HTML
<p>
	<i>Copiar una Institución</i> es un buen método para agregar otra institución RosarioSIS, donde las Horas, los Períodos a Calificar, los Grados, las Calificaciones del Boletín de Calificaciones y los Códigos de Asistencia son similares a la institución copiada. Usted puede, por supuesto, hacer cambios en la configuración después de haber "copiado" la institución.
</p>
<p>
	Si no quiere copiar uno o más de estos elementos, quite la marca de la casilla corespondiente al elemento.
</p>
<p>
	Asegúrese de entrar el nombre de la institución que esta creando en el campo de texto "Nuevo Nombre de la Institución ".
</p>
<p>
	Finalmente, presione "OK" para crear la nueva institución con los datos de la institución existente.
</p>
HTML;

	$help['School_Setup/MarkingPeriods.php'] = <<<HTML
<p>
	<i>Períodos a Calificar</i> le permite configurar los períodos a calificar de la institución. Hay tres tercios de períodos a calificar: algo como Semestres, Bimestres y Períodos intermedios está sugerido. A pesar de sus nombres, puede ser menos de 2 semestres y mas o menos de 4 bimestres.
</p>
<p>
	Para agregar un período a calificar, haga click sobre el icono «+» en la columna del tipo de período a calificar que usted quiere agregar. Luego, completa los datos del período a calificar en los campos de arriba y presione el botón «Guardar».
</p>
<p>
	Para cambiar un período a calificar, haga click sobre el período a calificar que usted quiere cambiar, y haga click sobre cualquier valor que usted quiere cambiar en la zona de arriba. Luego, cambie el valor y presione el botón «Guardar».
</p>
<p>
	Para eliminar un período a calificar, seleccione lo haciendo click sobre su titulo en la lista y presione el botón «Eliminar» en la parte superior de la pantalla. Se le preguntará si desea confirmar la eliminación.
</p>
<p>
	Note que dos períodos a calificar o dos periodos de publicación de calificaciones no se pueden cruzar. También, los períodos a calificar de un mismo tercio deben tener un orden diferente.
</p>
HTML;

	$help['School_Setup/Calendar.php'] = <<<HTML
<p>
	<i>Calendario</i> le permite configurar el calendario de su institución para el año. El calendario muestra el mes actual por defecto. El mes y el año mostrado se pueden cambiar en los menús desplegables del mes y del año en la parte superior de la pantalla.
</p>
<p>
	En días completos de institución, la casilla en la esquina superior derecha del día debería estar marcada. Para los días parciales, la casilla no debería estar marcada y el número de minutos de asistencia a la institución, debería estar en el campo de texto al lado de la casilla. Para los días sin institución, la casilla no debería estar marcada y el campo de texto vacío. Para desactivar la casilla o cambiar el número de minutos en el día, usted debe primero hacer click sobre el valor que desee cambiar. Después de hacer un cambio al calendario, presione el botón «Actualizar» en la parte superior de la pantalla.
</p>
<p>
	Si la institución usa una rotación de días enumerados, el numero del día aparece en el cuadro de los días de institución.
</p>
<p>
	Para configurar su calendario al comienzo del año, usted debería usar «Crear un nuevo calendario». Haciendo click sobre este enlace, usted puede configurar todos los días en un transcurso especificado como días completos de clase. Usted puede también seleccionar cuales días de la semana su institución trabaja. Después de seleccionar las fechas de comienzo y fin del año escolar y los días de trabajo de la institución, presione el botón «OK». Usted ya puede navegar por el calendario y marcar las vacaciones y días parciales.
</p>
<p>
	El calendario muestra también los eventos de la institución. Esto puede incluir todo, desde los días de servicio de los docentes hasta los eventos de deporte. Estos eventos están visibles por los otros administradores y también por los padres y docentes de la institución.
</p>
<p>
	Para agregar un evento, haga click sobre el icono «+» en la esquina inferior izquierda de la fecha del evento. En la ventana emergente que aparece, entrar los datos del evento y presione el botón «Guardar». La ventana emergente se cerrara, y el calendario será actualizado para mostrar el evento agregado.
</p>
<p>
	Para cambiar un evento, haga click sobre el evento que quiere modificar, y cambie los datos del evento en la ventana emergente que aparece. Luego, presione el botón «Guardar». La ventana se cerrara y el calendario será actualizado para mostrar el cambio.
</p>
HTML;

	$help['School_Setup/Periods.php'] = <<<HTML
<p>
	<i>Horas</i> le permite configurar las horas de la institución.
</p>
<p>
	Para agregar una hora, llene el titulo de la hora, su nombre corto, orden, y duración en minutos en los campos vacíos al pié de la lista de horas y presione el botón «Guardar».
</p>
<p>
	Para cambiar una hora, haga click sobre cualquiera dato de la hora, cambie el dato, y presione el botón «Guardar».
</p>
<p>
	Para eliminar una hora, haga click sobre el icono «-» al lado de la hora que quiere eliminar. Se le preguntará si desea confirmar la eliminación.
</p>
HTML;

	$help['School_Setup/GradeLevels.php'] = <<<HTML
<p>
	<i>Grados</i> le permite configurar los grados de su institución.
</p>
<p>
	Para agregar un grado, llene el titulo del grado, su nombre corto, orden, y grado siguiente en los campos vacíos al pié de la lista de grados y presione el botón «Guardar». El campo «Grado Siguiente» indica el grado al cual pasarán los estudiantes en el año escolar siguiente.
</p>
<p>
	Para cambiar un grado, haga click sobre cualquier dato del grado, cambie el valor, y presione el botón «Guardar».
</p>
<p>
	Para eliminar un grado, haga click sobre el icono «-» al lado del grado que quiere eliminar. Se le preguntará si desea confirmar la eliminación.
</p>
HTML;

	$help['School_Setup/Rollover.php'] = <<<HTML
<p>
	<i>Transferir</i> copia los datos del año actual al siguiente año escolar. Los estudiantes están matriculados en el siguiente grado, y cada dato de la institución está duplicado para el año siguiente.
</p>
<p>
	Los datos copiados incluyen horas, períodos a calificar, usuarios, cursos, matrícula de los estudiantes, códigos de grados del boletín de calificaciones, códigos de matricula, códigos de asistencia, y actividades de elegibilidad.
</p>
HTML;

	$help['School_Setup/Configuration.php'] = <<<HTML
<p>
	<i>Configuración de la Institución</i> le ofrece varios grupos de opciones de configuración para ayudarle a configurar:
</p>
<ul>
	<li>RosarioSIS mismo:
		<ul>
			<li>
				<i>Titulo del Programa</i> &amp; <i>Título del Programa</i>: renombrar RosarioSIS
			</li>
			<li>Definir el <i>Diseño por Defecto</i>
			</li>
			<li>
				<i>Crear Cuenta de Usuario</i> &amp; <i>Crear Cuenta de Estudiante</i>: activar el registro en línea. Enlaces "Crear Cuenta de Usuario / Estudiante" estaran agregados a la pagína de entrada.
			</li>
		</ul>
	</li>
	<li>La Institución:
		<ul>
			<li>
				<i>Año escolar sobre dos años calendarios</i>: si el año escolar deberia ser de la forma "2014" o "2014-2015"
			</li>
			<li>
				<i>Logo de la Institución (.jpg)</i>: subir el logo de la institución (expuesto en Boletines de Calificaciones, Expedientes Académicos, Información de la Institución &amp; Imprimir Información del Estudiante)
			</li>
			<li>
				<i>Símbolo Monetario</i>: el símbolo de la moneda usada en los módulos Contabilidad &amp; Cobros
			</li>
		</ul>
	</li>
	<li>El módulo Estudiantes:
		<ul>
			<li>
				<i>Mostrar Dirección de Correo</i>: si guardar y mostrar la dirección de correo del estudiante como una dirección aparte.
			</li>
			<li>
				<i>Marcar Recorrida / Paradero del Transporte Escolar por defecto</i>: si marcar las casillas Recorrida / Paradero del Transporte Escolar por defecto al momento de entrar la dirección del estudiante
			</li>
			<li>
				<i>Activar la Antigua Información de Contacto</i>: la capacidad de agregar información a los contactos del estudiante
			</li>
			<li>
				<i>Usar Comentarios Semestrales en lugar de Comentarios Bimestrales</i>: tener un nuevo campo de comentarios cada semestre en lugar de cada bimestre
			</li>
		</ul>
	</li>
	<li>El módulo Calificaciones:
		<ul>
			<li>
				<i>Calificaciones</i>: si su escuela usa los porcentajes, las calificaciones de letra, o los dos. Los porcentajes o las calificaciones de letra estaran ocultas según su elección.
			</li>
			<li>
				<i>Ocultar el comentario de la calificación exceptuando los cursos con asistencia</i>: si ocultar el comentario de la calificación para los cursos sin asistencia
			</li>
			<li>
				<i>Dejar los Docentes editar las calificaciones después del período de publicación de calificaciones</i>: el período de publicación de calificaciones de cada período a calificar se define en el programa Institución &gt; Períodos a Calificar
			</li>
			<li>
				<i>Activar las Estadísticas Anónimas de Calificaciones para los Padres y Estudiantes / los Administradores y Docentes</i>: las Estadísticas Anónimas de Calificaciones están expuestas en el programa Calificaciones de los Estudiantes
			</li>
		</ul>
	</li>
	<li>El módulo Asistencia:
		<ul>
			<li>
				<i>Minutos en un Día de Escuela Completo</i>: si un estudiante asiste a clases por 300 mínutos o más, RosarioSIS lo marcara automaticamente Presente para el día. Si un estudiante asiste a clases entre 150 mínutos y 299 mínutos, RosarioSIS lo marcara presente Medio Día. Si un estudiante asiste a clases por menos de 150 mínutos, RosarioSIS lo marcara Ausente. Si su Día de Escuela no es 300 mínutos de largo, entonces por favor ajuste los Minutos en un Día de Escuela Completo
			</li>
			<li>
				<i>Número de días antes / después de la fecha por los cuales los docentes pueden editar la asistencia</i>: dejar el campo en blanco para permitir siempre
			</li>
		</ul>
	</li>
	<li>El módulo Servicio de Comida:
		<ul>
			<li>
				<i>Saldo mínimo del Servicio de Comida para el aviso</i>: definir el saldo mínimo por debajo del cual un aviso será dado al estudiante y sus padres en el Portal y para generar Avisos
			</li>
			<li>
				<i>Saldo mínimo del Servicio de Comida</i>: definir el saldo mínimo autorizado
			</li>
			<li>
				<i>Saldo meta del Servicio de Comida</i>: definir el saldo meta para calcular el deposito mínimo
			</li>
		</ul>
	</li>
</ul>
<p>
	Pestaña <b>Módulos</b>: gestionar los módulos de RosarioSIS. Desactivar cualquier módulo que no se usa o instalar nuevos módulos.
</p>
<p>
	Pestaña <b>Plugins</b>: gestionar los plugins de RosarioSIS. Activar, desactivar y configurar los plugins. Haga click sobre el título del plugin para obtener más información.
</p>
HTML;
}
else
{
	$help['School_Setup/Schools.php'] = <<<HTML
<p>
	<i>Información de la Institución</i> muestra el nombre, la dirección, y el director de la institución.
</p>
HTML;

	$help['School_Setup/Calendar.php'] = <<<HTML
<p>
	<i>Calendario</i> muestra los eventos de la institución y las tareas de sus estudiantes. El calendario muestra también los días de institución. Por defecto, el calendario muestra el mes actual. El mes y el año mostrados pueden ser cambiados en los menús desplegables del mes y del año en la parte superior de la pantalla.
</p>
<p>
	Los títulos de los eventos y de las tareas están en cada caja del día. Haciendo click sobre estos títulos abrirá una ventana emergente que muestra mas información sobre el evento o la tarea.
</p>
<p>
	En días completos de institución, el día aparece en verde. Para los días parciales, el número de minutos de asistencia en la institución aparece en la esquina superior derecha. Para los días sin institución, el día aparece en rosado.
</p>
<p>
	Si la institución usa una rotación de días enumerados, el número del día aparece en la caja de los días de institución.
</p>
HTML;
}


// STUDENTS ---
if ( User( 'PROFILE' ) === 'admin' )
{
	$help['Students/Student.php&include=General_Info&student_id=new'] = <<<HTML
<p>
	<i>Agregar un Estudiante</i> le permite agregar un estudiante y matricularlo.
</p>
<p>
	Para agregar el estudiante, entre su fecha de nacimiento, etnicidad, sexo, lugar de nacimiento y grado. Luego, seleccione la fecha de matricula del estudiante y el código de matricula desde el menú desplegable al pié de la pagina. Si usted quiere especificar un ID para este estudiante, entre lo en el campo de texto «ID RosarioSIS». Si usted deja este campo blanco, RosarioSIS generara un ID para el estudiante. Finalmente, presione el botón «Guardar» en la parte superior de la pantalla.
</p>
HTML;

	$help['Students/AddUsers.php'] = <<<HTML
<p>
	<i>Asociar Padres con Estudiantes</i> le permite relacionar padres con estudiantes.
</p>
<p>
	Una vez que una cuenta de padre esté lista, sus hijos deben ser relacionados con este programa. Si usted ya no ha seleccionado un estudiante para la sesión, seleccione uno con la pantalla de búsqueda «Encontrar un Estudiante». Luego, busque un usuario para relacionar con la cuenta del estudiante. Desde el resultado de la búsqueda, usted puede seleccionar cualquier número de padres. Después de seleccionar cada padre deseado desde la lista, presione el botón «Agregar los Padres Seleccionados» en la parte superior de la pantalla.
</p>
<p>
	Después de seleccionar un estudiante, usted puede ver los padres que ya están relacionados con el estudiante. Estos padres pueden ser disociados de este usuario haciendo click sobre el icono «-» al lado del el padre. Se le preguntará si desea confirmar la acción.
</p>
HTML;

	$help['Students/AssignOtherInfo.php'] = <<<HTML
<p>
	<i>Asignar Información de Estudiante en Conjunto</i> lle permite asignar datos a cualquier campo de dato a un grupo de estudiantes de una vez.
</p>
<p>
	Primero, busca estudiantes. Desde el resultado de la búsqueda, usted puede seleccionar cualquier número de estudiantes. Usted puede seleccionar todos los estudiantes de la lista marcando la casilla en el encabezado de la lista. Después de seleccionar estudiantes, llene cualquier de los campos de datos de arriba. Los campos que están en blanco no afectaran los estudiantes seleccionados. Finalmente, presione el botón «Guardar» en la parte superior de la pantalla.
</p>
HTML;

	$help['Students/Letters.php'] = <<<HTML
<p>
	<i>Imprimir Cartas</i> le permite imprimir un modelo de carta para varios estudiantes.
</p>
<p>
	Primero, busca estudiantes. Después de seleccionar estudiantes desde el resultado de la búsqueda, entre el texto de la carta en el campo de texto «Texto de la Carta». Arriba de este campo de texto se encuentran varias opciones de formato del texto.
</p>
<p>
	Usted puede insertar algunos datos del estudiante en la carta con variables especiales:
</p>
<ul>
	<li>
		<b>Nombre Completo:</b> __FULL_NAME__
	</li>
	<li>
		<b>Nombre:</b> __FIRST_NAME__
	</li>
	<li>
		<b>Segundo Nombre:</b> __MIDDLE_NAME__
	</li>
	<li>
		<b>Apellido:</b> __LAST_NAME__
	</li>
	<li>
		<b>ID RosarioSIS:</b> __STUDENT_ID__
	</li>
	<li>
		<b>Institución:</b> __SCHOOL_ID__
	</li>
	<li>
		<b>Grado:</b> __GRADE_ID__
	</li>
	<li>
		<b>Docente de Asistencia:</b> __TEACHER__
	</li>
	<li>
		<b>Salón de Asistencia:</b> __ROOM__
	</li>
</ul>
<p>
	También puede escoger imprimir la cartas con etiquetas de correo. Las cartas tendrán etiquetas de correo posicionadas de una manera visible para un sobre con ventana cuando la hoja está doblada en tercios. Mas de una carta puede ser impreza por estudiante si el estudiante tiene padres residiendo en mas de una dirección.
</p>
<p>
	Las cartas serán automáticamente creadas en el formato imprimible PDF cuando usted presione el botón «Enviar».
</p>
HTML;

	$help['Students/Datos Personales'] = <<<HTML
<p>
	<i>Datos Personales</i> muestra los datos fundamentales del estudiante. Usted puede cambiar cualquier dato haciendo click sobre el valor que quiere cambiar, cambiando el valor, y presionando el botón «Guardar» en la parte superior de la pantalla.
</p>
HTML;

	$help['Students/Direcciones & Contactos'] = <<<HTML
<p>
	<i>Direcciones &amp; Contactos</i> muestra las direcciones y los contactos del estudiante.
</p>
<p>
	Un estudiante puede tener mas de una dirección. Para agregar una dirección, llene los campos vacíos abajo, y presione el botón «Guardar» en la parte superior de la pantalla.
</p>
<p>
	Ahora, usted puede agregar un contacto a esta dirección. Para hacerlo, llene el nombre del contacto, y presione el botón «Guardar».
</p>
<p>
	Usted puede ahora añadir mas información sobre este contacto marcando las casillas «Custodia» y «Emergencia» después de hacer click sobre la cruz roja. Los contactos marcados como «Custodia» del estudiante reciben los correos, y los contactos marcados como «Emergencia» pueden ser contactados en caso de emergencia.
</p>
<p>
	Usted puede agregar otras informaciones sobre este contacto, como su teléfono, profesión, etc.
</p>
<p>
	Contactos e informaciones sobre un contacto pueden ser eliminados haciendo click sobre el icono «-» al lado de la información a eliminar. Cualquier información en la pantalla puede ser modificada haciendo click sobre la información primero, luego cambiando el dato, y finalmente presionando el botón «Guardar» en la parte superior de la pantalla.
</p>
HTML;

	$help['Students/Medical'] = <<<HTML
<p>
	<i>Médico</i> muestra la información médica de un estudiante.
</p>
<p>
	Esta incluye el médico del estudiante, el teléfono del médico, el hospital preferido, cualquier comentario médico, si el estudiante tiene una nota del médico o no, y comentarios sobre la nota. Para cambiar cualquier valor, haga click sobre el dato que quiere cambiar, cambielo, y presione el botón «Guardar» en la parte superior de la pantalla.
</p>
<p>
	Usted también puede agregar entradas para cada inmunización o chequeo que recibe el estudiante así como alertas médicas como enfermedades o alergias.
</p>
<p>
	Para agregar una vacuna, un chequeo, o una alerta médica, llene los campos vacíos al final de la lista apropiada, y presione el botón «Guardar» en la parte superior de la pantalla.
</p>
<p>
	Para cambiar una vacuna, un chequeo, o una alerta médica, haga click sobre el dato que desee cambiar, cambielo, y presione el botón «Guardar» en la parte superior de la pantalla.
</p>
<p>
	Para eliminar una vacuna, un chequeo, o una alerta médica, haga click sobre el icono «-» al lado del artículo que quiere eliminar. Se le preguntará si desea confirmar la eliminación.
</p>
HTML;

	$help['Matrícula'] = <<<HTML
<p>
	<i>Matrícula</i> puede ser usado para matricular o retirar un estudiante de una institución. Un estudiante puede tener solamente una clave de matrícula activa a la vez.
</p>
<p>
	Para retirar un estudiante, cambie la fecha y razón de «Retirado» para la fecha efectiva como la razón de su retiro. Presione el botón «Guardar» en la parte superior de la pantalla.
</p>
<p>
	Usted puede ahora matricular de nuevo el estudiante. Para hacerlo, seleccione la fecha efectiva de matrícula del estudiante y la razón desde la linea blanca al fin de la lista. Seleccione también la institución donde el estudiante será matriculado y presione el botón «Guardar» en la parte superior de la pantalla.
</p>
<p>
	Las fechas de matrícula y retiro y las razones se pueden cambiar haciendo click sobre los datos, cambiando los, y presionando el botón «Guardar» en la parte superior de la pantalla.
</p>
HTML;

	$help['Students/AdvancedReport.php'] = <<<HTML
<p>
	<i>Reporte Avanzado</i> es una herramienta para reportes que le ayudara a crear cualquier reporte que necesite.
</p>
<p>
	En la parte izquierda de la página, marque las casillas al lado de las columnas que desee ver en su reporte. Estos elementos, en el orden que escogió, aparecerán en la lista en la parte arriba de la pantalla.
</p>
<p>
	Para obtener la lista de estudiantes que cumplen años en una fecha especifica, seleccione la fecha usando los menús desplegables "Mes de Nacimiento" y "Día de Nacimiento" en la caja "Encontrar un Estudiante".
</p>
HTML;

	$help['Students/AddDrop.php'] = <<<HTML
<p>
	<i>Reporte de Añadidos / Retiros</i> es un reporte que muestra un listado de todos los estudiantes matriculados o retirados durante el transcurso situado en la parte superior de la pantalla. Para consultar otros transcursos, cambie las fechas arriba y presione el botón "Ir" al lado derecho de la fecha de fin.
</p>
HTML;

	$help['Students/MailingLabels.php'] = <<<HTML
<p>
	<i>Imprimir Etiquetas de Correo</i> le permite generar etiquetas de correo para los estudiantes, los padres o las familias.
</p>
<p>
	Usted debe primero seleccionar un estudiante usando la pantalla de búsqueda «Encontrar un Estudiante».
</p>
<p>
	Luego, usted puede seleccionar a quien desee mandar el correo. Usted puede seleccionar de imprimir la etiqueta con el nombre del estudiante, usando diferentes formatos como "Smith, John Peter" (Apellido, Nombre Segundo Nombre) o "John Smith" (Nombre Apellido).
</p>
HTML;

	$help['Students/StudentLabels.php'] = <<<HTML
<p>
	<i>Imprimir Etiquetas con el Estudiante</i> le permite imprimir etiquetas para el archivo del estudiante.
</p>
<p>
	Usted debe primero seleccionar un estudiante usando la pantalla de búsqueda «Encontrar un Estudiante».
</p>
<p>
	Luego, seleccione los estudiantes y que imprimir en la etiqueta: use las casillas en la parte izquierdea del listado de estudiantes para seleccionar los estudiantes, y use la sección "Incluir Sobre las Etiquetas" en la parte superior para seleccionar otros datos que podría desear imprimir sobre la etiqueta del archivo. Usted puede seleccionar de imprimir la etiqueta con el nombre del estudiante, usando diferentes formatos como "Smith, John Peter" (Apellido, Nombre Segundo Nombre) o "John Smith" (Nombre Apellido).
</p>
HTML;

	$help['Students/PrintStudentInfo.php'] = <<<HTML
<p>
	<i>Imprimir Información del Estudiante</i> producirá un reporte de varias páginas con los datos de las pesatañas del programa Información del Estudiante.
</p>
<p>
	Usted debe primero seleccionar un estudiante usando la pantalla de búsqueda «Encontrar un Estudiante».
</p>
<p>
	Luego, seleccione los estudiantes y los datos del reporte: use las casillas en la parte izquierdea del listado de estudiantes para seleccionar los estudiantes, y luego, en la parte superior de la pantalla, haga clic sobre las casillas de las pestañas del programa Información del Estudiante que desee incluir. Se puede también hacer clic sobre "Etiquetas de Correo", así se añadirá la dirección al reporte para enviar lo por correo. Cuando esta satisfecho con sus elecciones, haga clic sobre "Imprimir Información para los Estudiantes Seleccionados".
</p>
HTML;

	$help['Custom/MyReport.php'] = <<<HTML
<p>
	<i>Mi Reporte</i> le proveera un archivo Excel, para descargar en el escritorio, con datos de contacto completos.
</p>
<p>
	Usted debe primero seleccionar un estudiante usando la pantalla de búsqueda «Encontrar un Estudiante».
</p>
<p>
	Este reporte proveera un producto imprimible, o más bien un documento con los estudiantes y sus datos de contacto que se puede usar en un directorio.
</p>
<p>
	Haga clic sobre el icono Descargar arriba del listado para exportar el reporte como archivo de Excel.
</p>
HTML;

	$help['Students/StudentFields.php'] = $help['Students/PeopleFields.php'] = $help['Students/AddressFields.php'] = <<<HTML
<p>
	<i>Campos de Estudiantes / Dirección / Contacto</i> le permite configurar campos de datos personalizados para su institución. Estos campos están usados para guardar información sobre un estudiante en las pestañas «Datos Personales» o «Direcciones y Contactos» del programa Información del Estudiante.
</p>
<p>
	Categorías de Campo de Datos
</p>
<p>
	RosarioSIS le permite agregar categorías personales que tomaran la forma de nuevas "Pestañas" de Campos de Datos en el programa Estudiantes &gt; Información del Estudiante. Para crear una nueva categoría o "pestaña", haga clic sobre el icono "+" debajo de la Categorías existentes.
</p>
<p>
	Nueva Categoría
</p>
<p>
	Se puede ahora entrar el nombre de la nueva categoría en el/los campo(s) "Título" presentes. Agregue una Orden (orden en la cual las pestañas aparecerán en el programa Información del Estudiante), y un número de columnas de la pestaña (opcional). Presione "Guardar" cuando ha terminado.
</p>
<p>
	Agregar un Nuevo Campo
</p>
<p>
	Haga clic sobre el icono "+" debajo del texto "No se encontró ningún(a) Campo de Datos.". Llene el/los campo(s) "Nombre del Campo", y luego escoge el tipo de campo que desee con el menú desplegable "Tipo de Dato".
</p>
<p>
	Los campos "Menú Desplegable" crean un menú a partir del cual se puede escoger una opción. Para crear este tipo de campo, haga lic sobre "Menú Desplegable" y luego entre las opciones (una por línea) en el campo de texto "Menú Desplegable/Menú Desplegable Automático/Menú Desplegable Codificado/Selección de Opción Múltiple".
</p>
<p>
	Los campos "Menú Desplegable Automático" crean un menú a partir del cual se puede escoger una opción y agregar opciones. Se agregan las opciones escogiendo el opción "-Editar-" en el menú y presionando "Guardar". Se puede editar el campo quitando el "-Editar-" rojo del campo, entrando la información correcta. RosarioSIS toma todas las opciones que han sido agregadas a este campo para crear el Menú Desplegable.
</p>
<p>
	Los campos "Menú Desplegable Editable" son similares a los campos Menú Desplegable Automático.
</p>
<p>
	Los campos de "Texto" crean un campo de texto alfanumérico con una capacidad máxima de 255 caracteres.
</p>
<p>
	Los campos "Casilla" crean casillas. Cuando marcada, significa "Sí", y cuando no marcado "No".
</p>
<p>
	Los campos "Menú Desplegable Codificado" son creados agregando opciones al grande campo de texto de la siguiente manera: "opción mostrada"|"opción guardada en la base de datos" (donde | es el carácter separador). Por ejemplo: "Dos|2", donde "Dos" está expuesto en la pantalla, o un documento descargado, y "2" está almacenado en la base de datos.
</p>
<p>
	Los campos "Menú Desplegable Exportable" son creados agregando opciones respectando la misma convención usado para los campos "Menú Desplegable Codificado" ("opción mostrada"|"opción guardada en la base de datos"). Por ejemplo: "Dos|2", donde "Dos" está expuesto en la pantalla, y "2" es el valor del documento descargado, pero "Dos" está almacenado en la base de datos.
</p>
<p>
	Los campos "Número" crean campos de texto que almacenan valores numéricos solamente.
</p>
<p>
	Los campos "Selección de Opción Múltiple" crean casillas múltiples para escoger una o varias opciones.
</p>
<p>
	Los campos "Fecha" crean menús desplegables para escoger una fecha.
</p>
<p>
	Los campos "Texto Largo" crean grandes cajas de texto alfanumérico con una capacidad máxima de 5000 caracteres.
</p>
<p>
	La casilla "Obligatorio", si marcada, volverá el campo requerido y un error sera generado si el campo está vació al momento de guardar.
</p>
<p>
	La "Orden" determina la orden en la cual los campos son mostrados en la pestaña del programa Información del Estudiante.
</p>
<p>
	Eliminar un campo
</p>
<p>
	Se puede eliminar cualquier Campo de Datos o Categoría simplemente presionando el botón "Eliminar" en la parte arriba de la pantalla. Por favor nota bien que se perderá toda la información si se elimina un campo o una categoría ya usada.
</p>
HTML;

	$help['Students/EnrollmentCodes.php'] = <<<HTML
<p>
	<i>Códigos de Matrícula</i> le permite configurar los códigos de matrícula de su institución. Los códigos de matrícula son usados en la pantalla de Matrícula del estudiante, y especifica la razón de matrícula o retiro de un estudiante de una institución. Estos códigos aplican para todas las instituciones del sistema.
</p>
<p>
	Para agregar un código de matrícula, llene el titulo del código, su nombre corto, y tipo en los campos vacíos al fin de la lista de códigos de matrícula. Presione el botón «Guardar».
</p>
<p>
	Para cambiar un código de matrícula, haga click sobre cualquiera información del código de matrícula, cambie el dato, y presione el botón «Guardar».
</p>
<p>
	Para eliminar un código de matrícula, haga click sobre el icono «-» al lado del código de matrícula que quiere eliminar. Se le preguntará si desea confirmar la eliminación.
</p>
HTML;
}
else
{
	$help['Students/Datos Personales'] = <<<HTML
<p>
	<i>Datos Personales</i> muestra los datos fundamentales del estudiante.
</p>
HTML;

	$help['Students/Direcciones & Contactos'] = <<<HTML
<p>
	<i>Direcciones &amp; Contactos</i> muestra las direcciones y los contactos del estudiante.
</p>
<p>
	Un estudiante puede tener mas de una dirección.
</p>
HTML;

	$help['Students/Matrícula'] = <<<HTML
<p>
	<i>Matrícula</i> muestra el historial de matrícula del estudiante.
</p>
HTML;
}


// USERS
if ( User( 'PROFILE' ) === 'admin' )
{
	$help['Users/User.php'] = <<<HTML
<p>
	<i>Datos Personales</i> muestra los datos fundamentales de un usuario. Si usted es administrador, usted puede cambiar cualquier dato haciendo click sobre el dato que quiere cambiar, cambiando el dato, y presionando el botón «Guardar» en la parte superior de la pantalla. Usted puede eliminar un usuario presionando el botón «Eliminar» en la parte superior de la pantalla. Note que usted nunca debería eliminar un docente después que el ha dado una clase, porque el usuario debe quedar para que el nombre del docente pueda aparecer en los expedientes académicos.
</p>
HTML;

	$help['Users/User.php&staff_id=new'] = <<<HTML
<p>
	<i>Agregar un Usuario</i> le permite agregar un usuario al sistema. Esto incluye administradores, docentes y padres. Llena simplemente el nombre, nombre de usuario, perfil, institución, email y teléfono del usuario. Presione el botón «Guardar».
</p>
HTML;

	$help['Users/AddStudents.php'] = <<<HTML
<p>
	<i>Relacionar Padres con Estudiantes</i> le permite relacionar estudiantes con padres.
</p>
<p>
	Una vez que una cuenta de padre esté lista, sus estudiantes deben ser relacionados con este servicio. Si usted ya no ha seleccionado un usuario para la sesión, seleccione uno con la pantalla de búsqueda «Encontrar un Usuario». Luego, busque un estudiante para relacionar con la cuenta del usuario. Desde el resultado de la búsqueda, usted puede seleccionar cualquier número de estudiantes. Después de seleccionar cada estudiante deseado desde la lista, presione el botón «Agregar los Estudiantes Seleccionados» en la parte superior de la pantalla.
</p>
<p>
	Después de seleccionar un usuario, usted puede ver los estudiantes que ya están relacionados con el usuario. Estos estudiantes pueden ser disociados de este usuario haciendo click sobre el icono «-» al lado del el estudiante. Se le preguntará si desea confirmar la acción.
</p>
HTML;

	$help['Users/Preferences.php'] = <<<HTML
<p>
	<i>Mis Preferencias</i> le permite cambiar varios aspectos de RosarioSIS para satisfacer sus gustos. Se puede también cambiar su contraseña, y configurar RosarioSIS para mostrar datos que son importantes para usted.
</p>
<p>
	Pestaña Opciones de Visualización
</p>
<p>
	Le permite cambiar el diseño de RosarioSIS, con combinaciones de colores diferentes que le permite hacer RosarioSIS suyo. Se puede cambiar el color de resalte. Se puede cambiar también el formato de fecha por el formato que le gusta, como cambiar el mes a "Enero", o "Ene" o "01". "Deshabilitar alertas de entrada", cuando no marcado, muestra los datos faltantes de asistencia de los Docentes, las nuevas Sanciones de disciplina y las alertas de los saldos del Serivcio de Comida en el Portal (primera página despues de entrar).
</p>
<p>
	Pestaña Lista de Estudiantes
</p>
<p>
	"Clasificación de Estudiantes" le permite escoger ver a los estudiantes listados en los varios listados de estudiante solamente por el "Nombre" o por el Grado y Nombre. "Tipo de Archivo a Exportar" le permite escoger entre archivos delimitados por tabulaciones, los cuales funcionan mejor con Excel, o archivos CSV (comma-separated values), los cuales funcionan mejor con Open Office. "Formato de Exportación de Fecha" le permite escoger diferentes formatos de fecha cuando se exportan campos, usando el icono Descargar. "Mostrar pantalla de búsqueda de estudiante" deberia quedarse marcado, a menos de ser invitado a lo contrario.
</p>
<p>
	Pestaña Contraseña
</p>
<p>
	Le ayude a cambiar su contraseña. Simplemente entre su contraseña actual en el primer campo de texto, y su nueva contraseña en los dos campos de texto siguientes, y luego presione "Guardar".
</p>
<p>
	Pestaña Campos de Estudiantes
</p>
<p>
	Las dos columnas en la parte derecha de la pagina le permite escoger campos de datos (en la columna Campo) para mostrar o en la pantalla "Encontrar un Estudiante" o en la "Vista Ampliada". Marcando la casilla "Buscar" usted puede agregar campos usados a menudo a la pantalla "Encontrar un Estudiante", evitando así hacer clic sobre "Vista Ampliada" para usar un campo que es importante para su trabajo. Marcar la casilla "Vista Ampliada" agrega este campo a su reporte Vista Ampliada, para personalizarlo. Se pueden agregar o quitar campos cuando lo quiere, personalizando la pantalla "Encontrar un Estudiante" y el reporte "Vista Ampliada".
</p>
HTML;

	$help['Users/Profiles.php'] = <<<HTML
<p>
	<i>Perfiles de Usuario</i> le ayuda asegurarse de que los usuarios correctos tienen acceso a la información que necesitan, y deberían tener.
</p>
<p>
	RosarioSIS viene con cuatro grupos principales como perfiles: Administrador, Docente, Padre &amp; Estudiante. El perfil de Administrador tiene lo más de permisos, y los otros perfiles son restringidos como les corresponde. Por favor nota bien que los docentes pueden solamente ver los datos de los estudiantes en sus clases, que los padres pueden ver solamente los datos de sus hijos, y que los estudiantes pueden ver solamente sus datos personales
</p>
<p>
	Perfil Administrador
</p>
<p>
	Si le de clic en uno de los Perfiles, usted vera la página de Permisiones. Está página muestra a cuales páginas el perfil tiene acceso para LEER (Puede Usar) o para ESCRIBIR (Se Puede Editar) datos en esta página en particular.
</p>
<p>
	El perfil de Administrador tiene acceso a cuasi todas la páginas, para leer y escribir. Si quita la marca de "Se Puede Editar", el usuario con este perfil vera el programa en el menú, podrá hacer clic sobre el, y vera los datos en está página. Pero el usuario NO podrá cambiar ningún dato de esta página.
</p>
<p>
	Si quita la marca de "Puede Usar" de una página, entonces este perfil no vera el programa en el menú de izquierda, y por supuesto, no podrá ver o cambiar ningún dato de esta página.
</p>
<p>
	Los Administradores no pueden ver la pestaña "Comentarios" del archivo del estudiante, pero pueden ver y modificar cualquier otra página.
</p>
<p>
	Perfil Docente
</p>
<p>
	Los Docentes tienen acceso a una sección más limitada de las páginas de RosarioSIS, y sus habilidades para editar estas páginas están mas restringidas. Por defecto, los docentes no pueden cambiar los datos de un estudiante con la EXCEPCION de la pestaña Comentarios.
</p>
<p>
	Perfil Padre
</p>
<p>
	Los Padres están aun más limitados. Los Padres tienen acceso a la información que es específicamente de interés para ellos, los datos de los estudiantes, la asistencia y las calificaciones.
</p>
<p>
	Agregar un Perfil de Usuario
</p>
<p>
	Se pueden agregar Perfiles a RosarioSIS, en cualquier de los "Tipos" de Perfiles. Lo recomendamos agregar un perfil "admin" a "Administrador" para limitar el acceso que los administradores tienen en el sistema. No es necesario que TODOS los administradores puedan Agregar una Institución, Copiar una Institución, cambiar los Periodos a Calificar o cambiar las Escalas de Calificaciones, etc. Una vez que la institución este configurada, cambios en la configuración por usuarios si los conocimientos necesarios pueden resultar en daños y problemas.
</p>
<p>
	Para agregar un nuevo perfil, entre su nombre en el campo "Título" y seleccione su "Tipo" de Perfil del cual sera un subgrupo. Presione el botón "Guardar" en la parte superior de la pantalla.
</p>
<p>
	Configurar las Permisiones
</p>
<p>
	Una buena practica es de entrar a RosarioSIS con un usuario de prueba para ayudar a definir sus permisos de acceso. Demasiado acceso podría causar muchos mas problemas que un acceso limitado.
</p>
HTML;

	$help['Users/Exceptions.php'] = <<<HTML
<p>
	<i>Permisos de Usuario</i> le permite prohibir acceso y/o privilegios de edición a cualquier servicio para un usuario con permisos configurados (Ver los Datos Personales del usuario).
</p>
<p>
	Para asignar privilegios a un usuario, seleccionelo primero, y haga click sobre su nombre en la lista. Luego, use las casillas para definir cuales servicios el usuario puede visualizar y cuales servicios el puede editar. Si un usuario no puede usar un servicio especifico, este servicio no será mostrado en su menú. Si el usuario puede usar el servicio, pero no puede editar información con el servicio, el servicio mostrara los datos, pero no le dejara cambiar nada. Después de marcar las casillas, presione el botón «Guardar» para guardar los permisos.
</p>
HTML;

	$help['Users/UserFields.php'] = <<<HTML
<p>
	<i>User Fields</i> le permite agregar nuevos campos y pestañas a la pantalla Información del Usuario.
</p>
<p>
	Categorías de Campo de Usuario
</p>
<p>
	RosarioSIS le permite agregar categorías personales que tomaran la forma de nuevas "Pestañas" de Campos de Usuario en el programa Usuarios &gt; Información del Usuario. Para crear una nueva categoría o "pestaña", haga clic sobre el icono "+" debajo de la Categorías existentes.
</p>
<p>
	Nueva Categoría
</p>
<p>
	Se puede ahora entrar el nombre de la nueva categoría en el/los campo(s) "Título" presentes. Agregue una Orden (orden en la cual las pestañas aparecerán en el programa Información del Usuario), y un número de columnas de la pestaña (opcional). Presione "Guardar" cuando ha terminado.
</p>
<p>
	Agregar un Nuevo Campo
</p>
<p>
	Haga clic sobre el icono "+" debajo del texto "No se encontró ningún(a) Campo de Usuario.". Llene el/los campo(s) "Nombre del Campo", y luego escoge el tipo de campo que desee con el menú desplegable "Tipo de Dato".
</p>
<p>
	Los campos "Menú Desplegable" crean un menú a partir del cual se puede escoger una opción. Para crear este tipo de campo, haga lic sobre "Menú Desplegable" y luego entre las opciones (una por línea) en el campo de texto "Menú Desplegable/Menú Desplegable Automático/Menú Desplegable Codificado/Selección de Opción Múltiple".
</p>
<p>
	Los campos "Menú Desplegable Automático" crean un menú a partir del cual se puede escoger una opción y agregar opciones. Se agregan las opciones escogiendo el opción "-Editar-" en el menú y presionando "Guardar". Se puede editar el campo quitando el "-Editar-" rojo del campo, entrando la información correcta. RosarioSIS toma todas las opciones que han sido agregadas a este campo para crear el Menú Desplegable.
</p>
<p>
	Los campos "Menú Desplegable Editable" son similares a los campos Menú Desplegable Automático.
</p>
<p>
	Los campos de "Texto" crean un campo de texto alfanumérico con una capacidad máxima de 255 caracteres.
</p>
<p>
	Los campos "Casilla" crean casillas. Cuando marcada, significa "Sí", y cuando no marcado "No".
</p>
<p>
	Los campos "Menú Desplegable Codificado" son creados agregando opciones al grande campo de texto de la siguiente manera: "opción mostrada"|"opción guardada en la base de datos" (donde | es el carácter separador). Por ejemplo: "Dos|2", donde "Dos" está expuesto en la pantalla, o un documento descargado, y "2" está almacenado en la base de datos.
</p>
<p>
	Los campos "Menú Desplegable Exportable" son creados agregando opciones respectando la misma convención usado para los campos "Menú Desplegable Codificado" ("opción mostrada"|"opción guardada en la base de datos"). Por ejemplo: "Dos|2", donde "Dos" está expuesto en la pantalla, y "2" es el valor del documento descargado, pero "Dos" está almacenado en la base de datos.
</p>
<p>
	Los campos "Número" crean campos de texto que almacenan valores numéricos solamente.
</p>
<p>
	Los campos "Selección de Opción Múltiple" crean casillas múltiples para escoger una o varias opciones.
</p>
<p>
	Los campos "Fecha" crean menús desplegables para escoger una fecha.
</p>
<p>
	Los campos "Texto Largo" crean grandes cajas de texto alfanumérico con una capacidad máxima de 5000 caracteres.
</p>
<p>
	La casilla "Obligatorio", si marcada, volverá el campo requerido y un error sera generado si el campo está vació al momento de guardar.
</p>
<p>
	La "Orden" determina la orden en la cual los campos son mostrados en la pestaña del programa Información del Usuario.
</p>
<p>
	Eliminar un campo
</p>
<p>
	Se puede eliminar cualquier Campo de Usuario o Categoría simplemente presionando el botón "Eliminar" en la parte arriba de la pantalla. Por favor nota bien que se perderá toda la información si se elimina un campo o una categoría ya usada.
</p>
HTML;

	$help['Users/TeacherPrograms.php&include=Grades/InputFinalGrades.php'] = <<<HTML
<p>
	<i>Programas Docente - Entrar Calificaciones Finales</i> le permite entrar la calificaciones de los bimestres, semestres, y exámenes de semestre para todos los estudiantes del docente seleccionado en la clase actual. Por defecto, el servicio listara los estudiantes de la primera clase del docente seleccionado para el bimestre actual. Usted puede cambiar la clase con el menú desplegable de las clases en la parte superior de la pantalla. Usted también puede cambiar el periodo a calificar con el menú desplegable de los periodos a calificar en el marco izquierdo. Además, usted puede seleccionar el semestre actual en el menú desplegable en la parte superior de la pantalla.
</p>
<p>
	Una vez que usted esté en el periodo a calificar adecuado, usted puede entrar las calificaciones de los estudiantes seleccionando la calificación para cada estudiante y entrando comentarios como desee. Una vez que todas las calificaciones y los comentarios han sido entrados, presione el botón «Guardar» en la parte superior de la pantalla.
</p>
<p>
	Si el docente seleccionado usa el Libro de Calificaciones, RosarioSIS puede calcular las calificaciones de cada estudiante para el bimestre haciendo click sobre el enlace «Obtener las Calificaciones del Libro de Calificaciones.» de arriba.
</p>
HTML;

	$help['Users/TeacherPrograms.php&include=Grades/Grades.php'] = <<<HTML
<p>
	<i>Programas Docente - Calificaciones del Libro de Calificaciones</i> le permite consultar y modificar cualquier calificación de los libros de calificaciones de los estudiantes. Se pueden seleccionar las clases del docente usando el menú desplegable de clases en la parte superior de la página. Las Calificaciones del Libro de Calificaciones de la clase seran mostradas. Como administrador, usted puede escoger un estudiante, o los totales de una categoría de tareas, o todos los estudiantes para una o todas las tareas. El menú desplegable "Todo" le permite seleccionar una categoría de tareas, o alternativamente se pueden usar las pestañas arriba del listado de calificaciones. El menú desplegable "Totales" le permite seleccionar una tearea en particular o el "total" de todas las tareas.
</p>
HTML;

	$help['Users/TeacherPrograms.php&include=Grades/AnomalousGrades.php'] = <<<HTML
<p>
	<i>Programas Docente - Calificaciones con Anomalía</i> en un reporte que ayuda a los docentes a hacer seguimiento de las calificaciones faltantes, inapropriadas, y los excusados. Las calificaciones que aparecen en el reporte NO son problematicas, pero un docente QUISIERA revisarlas. Las calificaciones faltantes, negativas, los excusados, o las calificaciones con crédito extra o que exceden 100% son mostradas. La columna "Problema" indica la razon por la cual la calificación es irregular.
</p>
<p>
	Usted puede seleccionar las clases del docente usando el menú desplegable de clases en la parte superior de la página. Usted puede tambien seleccionar cual tipo de calificación con anomalía quiere que reporte muestra.
</p>
HTML;

	$help['Users/TeacherPrograms.php&include=Attendance/TakeAttendance.php'] = <<<HTML
<p>
	<i>Programas Docente - Tomar Asistencia</i> le permite entrar la asistencia de clase para todos los estudiantes del docente. Por defecto, este servicio listara los estudiantes de la primera clase del docente seleccionado. Usted puede cambiar la clase actual en el menú desplegable de las clases en la parte superior de la pantalla.
</p>
<p>
	Una vez que usted esté en la clase adecuada, usted puede tomar la asistencia seleccionando el código de asistencia para cada estudiante. Una vez que la asistencia ha sido tomada para todos los estudiantes, presione el botón «Guardar» en la parte superior de la pantalla.
</p>
HTML;

	$help['Users/TeacherPrograms.php&include=Eligibility/EnterEligibility.php'] = <<<HTML
<p>
	<i>Programas Docente - Entrar Elegibilidad</i> le permite entrar calificaciones de elegibilidad para todos los estudiantes del docente seleccionado. Por defecto, este servicio listara los estudiantes de la primera clase del docente seleccionado. Usted puede cambiar la clase actual en el menú desplegable de las clases en la parte superior de la pantalla.
</p>
<p>
	Una vez que usted esté en la clase adecuada, usted puede entrar la elegibilidad seleccionando el código de elegibilidad para cada estudiante. Una vez que la elegibilidad ha sido entrada para todos los estudiantes, presione el botón «Guardar» en la parte superior de la pantalla.
</p>
<p>
	Si el docente seleccionado usa el Libro de Calificaciones, RosarioSIS puede calcular la elegibilidad de cada estudiante haciendo click sobre el enlace «Usar la Calificaciones del Libro de Calificaciones» de arriba.
</p>
<p>
	Usted debe entrar la elegibilidad cada semana durante el transcurso especificado por el administrador de su institución.
</p>
HTML;
}


// SCHEDULING
if ( User( 'PROFILE' ) === 'admin' )
{
	$help['Scheduling/Schedule.php'] = <<<HTML
<p>
	<i>Horario Estudiante</i> le permite cambiar el horario de un estudiante.
</p>
<p>
	Usted debe primero seleccionar un estudiante usando la pantalla de búsqueda «Encontrar un Estudiante». Usted puede buscar estudiantes que han solicitado un curso especifico haciendo clic sobre el enlace «Escoger» al lado de las opciones de búsqueda «Curso» y «Solicitud» y escogiendo un curso desde la ventana emergente que aparece.
</p>
<p>
	Para agregar un curso al horario del estudiante, haga click sobre el enlace «Agregar un Curso» al lado del icono «+» y seleccione el curso desde la ventana emergente que aparece. La pantalla se refrescara automáticamente para mostrar el curso.
</p>
<p>
	Para retirar un curso existente, haga click sobre el icono «-» al lado del curso que quiere retirar del horario del estudiante.
</p>
<p>
	Para cambiar una clase de un curso de un estudiante, haga clic sobre «Hora - Docente» del curso que quiere cambiar y seleccione la nueva clase. Usted también puede cambiar el periodo de la misma manera.
</p>
<p>
	Todas las adiciones, eliminaciones, modificaciones al horario de un estudiante no permanecen a menos que usted presiona el botón «Guardar».
</p>
HTML;

	$help['Scheduling/Requests.php'] = <<<HTML
<p>
	<i>Solicitudes de Estudiante</i> le permite especificar cuales cursos un estudiante debe tomar para el siguiente año escolar. Esas solicitudes son usadas por el Programador para completar el horario de un estudiante.
</p>
<p>
	Usted debe primero seleccionar un estudiante usando la pantalla de búsqueda «Encontrar un Estudiante». Usted puede buscar estudiantes que han solicitado un curso especifico haciendo click sobre el enlace «Escoger» al lado de la opción de búsqueda «Solicitud» y escogiendo un curso desde la ventana emergente que aparece.
</p>
<p>
	Usted puede agregar una solicitud seleccionando el curso que usted quiere agregar buscando la materia. Usted puede agregar otras solicitudes desde cada materia de la misma manera. Una vez que usted ha agregado todas las solicitudes deseadas, presione el botón «Guardar» en la parte superior de la pantalla.
</p>
<p>
	Además, usted tiene la posibilidad de especificar un docente o una hora y de excluir un docente o una hora. Para hacerlo, seleccione el docente o la hora desde los menús desplegables «Con» y «Sin».
</p>
<p>
	Para eliminar una solicitud, haga click sobre el icono «-» al lado de la solicitud. Se le preguntará si desea confirmar la eliminación.
</p>
HTML;

	$help['Scheduling/MassSchedule.php'] = <<<HTML
<p>
	<i>Programar en Grupo</i> le permite agregar un curso a un grupo de estudiantes de una vez.
</p>
<p>
	Usted debe primero seleccionar un (grupo de) estudiante(s) usando la pantalla de búsqueda «Encontrar un Estudiante». Usted puede buscar estudiantes que han solicitado un curso especifico haciendo clic sobre el enlace «Escoger» al lado de las opciones de búsqueda «Curso» y «Solicitud» y escogiendo un curso desde la ventana emergente que aparece.
</p>
<p>
	Para agregar un curso al horario de los estudiantes, haga clic sobre el enlace «Escoger un Curso» en la parte superior de la pantalla y seleccione el curso desde la ventana emergente que aparece. La pantalla se refrescara automáticamente para mostrar el curso.
</p>
<p>
	Luego, seleccione la "Fecha de Inicio" adecuada (la fecha en la cual los estudiantes empezaran la clase), y el "Período a Calificar" apropiado.
</p>
<p>
	Desde los resultados de la búsqueda, usted puede escoger cualquier número de estudiantes. Para seleccionar todos los estudiantes en la lista, marque la casilla en el encabezado de la lista. Finalmente, presione el botón «Agregar Curso para los Estudiantes Seleccionados» en la parte superior de la pantalla.
</p>
HTML;

	$help['Scheduling/MassRequests.php'] = <<<HTML
<p>
	<i>Asignar Varias Solicitudes</i> le permite agregar una solicitud a un grupo de estudiantes de una vez.
</p>
<p>
	Usted debe primero seleccionar un (grupo de) estudiante(s) usando la pantalla de búsqueda «Encontrar un Estudiante». Usted puede buscar estudiantes que han solicitado un curso especifico haciendo clic sobre el enlace «Escoger» al lado de la opción de búsqueda «Solicitud» y escogiendo un curso desde la ventana emergente que aparece.
</p>
<p>
	Para agregar una solicitud al estudiante, haga clic sobre el enlace «Escoger un Curso» en la parte superior de la pantalla y seleccione el curso desde la ventana emergente que aparece. La pantalla se refrescara automáticamente para mostrar el curso.
</p>
<p>
	Luego, seleccione el docente "Con" o "Sin", y la Hora correcta.
</p>
<p>
	Desde los resultados de la búsqueda, usted puede escoger cualquier número de estudiantes. Para seleccionar todos los estudiantes en la lista, marca la casilla en el encabezado de la lista. Luego, seleccione el curso para agregar como solicitud haciendo clic sobre el enlace «Escoger un Curso» en la parte superior de la pantalla y escogiendo el curso desde la ventana emergente que aparece. Luego, presione el botón «Agregar Solicitud a los Estudiantes Seleccionados» en la parte superior de la pantalla.
</p>
HTML;

	$help['Scheduling/MassDrops.php'] = <<<HTML
<p>
	<i>Retirar Varios</i> le permite retirar un grupo de estudiantes de un curso de una vez.
</p>
<p>
	Usted debe primero seleccionar un (grupo de) estudiante(s) usando la pantalla de búsqueda «Encontrar un Estudiante». Usted puede buscar estudiantes que han solicitado un curso especifico haciendo clic sobre el enlace «Escoger» al lado de las opciones de búsqueda «Curso» y «Solicitud» y escogiendo un curso desde la ventana emergente que aparece.
</p>
<p>
	Para seleccionar la clase que quiere retirar, haga clic sobre el enlace «Escoger un Curso» en la parte superior de la pantalla y seleccione el curso desde la ventana emergente que aparece. La pantalla se refrescara automáticamente para mostrar el curso.
</p>
<p>
	Luego, seleccione la "Fecha de Retiro" adecuada (la fecha en la cual los estudiantes estarán retirados de la clase), y el "Período a Calificar" apropiado.
</p>
<p>
	Desde los resultados de la búsqueda, usted puede escoger cualquier número de estudiantes. Para seleccionar todos los estudiantes en la lista, marque la casilla en el encabezado de la lista. Finalmente, presione el botón «Eliminar Curso para los Estudiantes Seleccionados» en la parte superior de la pantalla.
</p>
HTML;

	$help['Scheduling/PrintSchedules.php'] = <<<HTML
<p>
	<i>Imprimir Horarios</i> le permite imprimir los horarios para cualquier número de estudiantes.
</p>
<p>
	Usted puede buscar estudiantes que solicitaron o están en un curso especifico haciendo click sobre el enlace «Escoger» al lado de las opciones «Solicitud» y «Curso» y escogiendo un curso desde la ventana emergente que aparece.
</p>
<p>
	También puede escoger imprimir las solicitudes con etiquetas de correo. Las solicitudes tendrán etiquetas de correo posicionadas de una manera visible para un sobre con ventana cuando la hoja está doblada en tercios. Mas de una carta puede ser impreza por estudiante si el estudiante tiene padres residiendo en mas de una dirección.
</p>
<p>
	Las solicitudes serán automáticamente creadas en el formato imprimible PDF cuando usted presione el botón «Enviar».
</p>
HTML;

	$help['Scheduling/PrintClassLists.php'] = <<<HTML
<p>
	<i>Imprimir Listas de Clase</i> le permitirá imprimir un reporte de los estudiantes en ciertas clases. Se pueden escoger las clases según el Docente o la Materia o la Hora o la Clase.
</p>
<p>
	Primero, seleccione las Clases
</p>
<p>
	Seleccionar un "Docente" mostrara todas las clases de ese mismo docente. Seleccionar una "Materia" mostrara todas las clases de esa mismo materia. Seleccionar una "Hora" mostrara todas las clases de esta misma hora. Seleccionar una "Clase" con el enlace "Escoger" mostrara la única clase con un docente.
</p>
<p>
	Luego, en la parte izquierda de la página, marque las columnas que quiere ver en la lista. Los campos, en la orden de selección, aparecerán en la lista en la parte superior de la página.
</p>
<p>
	Finalmente, seleccione las Clases para listar en el reporte en la parte inferior de la página y presione "Crear las Listas de Clase para las Clases Seleccionadas".
</p>
<p>
	Las Listas de Clase, con las columnas seleccionadas, estarán generadas como documento PDF que puede ser impreso o enviado por email.
</p>
HTML;

	$help['Scheduling/PrintRequests.php'] = <<<HTML
<p>
	<i>Imprimir Solicitudes</i> le permite imprimir hojas de solicitudes para cualquier número de estudiantes.
</p>
<p>
	Se puede buscar estudiantes que solicitaron un curso especifico haciendo click sobre el enlace «Escoger» al lado de la opción «Solicitud» y escogiendo un curso desde la ventana emergente que aparece.
</p>
<p>
	Also, you can choose to print the requests sheets with mailing labels. The requests sheets will have mailing labels positioned in such a way as to be visible in a windowed envelope when the sheet is folded in thirds. More than one request sheet may be printed per student if the student has guardians residing at more than one address.
</p>
<p>
	The request sheets will be automatically downloaded to your computer in the printable PDF format when you click the "Submit" button.
</p>
HTML;

	$help['Scheduling/ScheduleReport.php'] = <<<HTML
<p>
	<i>Reporte de Horario</i> es un reporte que muestra los estudiantes programados en cada curso, los estudiantes que solicitaron el curso pero que no fueron programados, el número de solicitudes, los cupos abiertos, y el total de asientos en cada curso.
</p>
<p>
	Para navegar en este reporte, haga click primero sobre una de las materias. Usted vera cada curso en esta materia también como el número de solicitudes para este curso, los cupos abiertos y el total de asientos. Si usted escoge un curso haciendo click sobre este, usted vera una lista de los estudiantes programados en este curso o una lista de los estudiantes que solicitaron el curso pero que no fueron programados haciendo click sobre el enlace «Listar Estudiantes no Programados».
</p>
<p>
	Cualquier momento después de seleccionar una materia, usted puede navegar atrás haciendo click sobre los enlaces que aparezcan en la parte superior de la pantalla.
</p>
HTML;

	$help['Scheduling/RequestsReport.php'] = <<<HTML
<p>
	<i>Reporte de Solicitudes</i> es un reporte que muestra el número de estudiantes que solicitaron cada curso y el número total de asientos en este curso. Los cursos son agrupados por materia.
</p>
<p>
	Ese reporte está útil para crear el horario maestro porque le ayuda a determinar el número de clases necesario para cada curso en proporción a la demanda para el curso.
</p>
HTML;

	$help['Scheduling/UnfilledRequests.php'] = <<<HTML
<p>
	<i>Solicitudes Incompletas</i> es un reporte de las solicitudes no completadas de un grupo de estudiantes.
</p>
<p>
	Usted debe primero seleccionar un (grupo de) estudiante(s) usando la pantalla de búsqueda «Encontrar un Estudiante».
</p>
<p>
	El reporte muestra la información del estudiante junto con los detalles de la solicitud incompleta (el docente y la hora solicitados) y el numero de secciones o clases que han sido creadas para el curso (en el programa Horarios &gt; Cursos). Se puede también chequear el número de cupos disponibles marcando "Mostrar Cupos Disponibles" en la parte superior de la pantalla.
</p>
<p>
	Haciendo clic sobre el nombre del estudiante le redireccionara hacia el programa Solicitudes de Estudiante.
</p>
HTML;

	$help['Scheduling/IncompleteSchedules.php'] = <<<HTML
<p>
	<i>Horarios Incompletos</i> es un reporte de los estudiantes que no tienen clase programada en una hora especifica.
</p>
<p>
	Usted debe primero seleccionar un (grupo de) estudiante(s) usando la pantalla de búsqueda «Encontrar un Estudiante». Usted puede buscar estudiantes que han solicitado un curso especifico haciendo clic sobre el enlace «Escoger» al lado de las opciones de búsqueda «Curso» y «Solicitud» y escogiendo un curso desde la ventana emergente que aparece.
</p>
<p>
	Luego, los estudiantes de la lista no están programados en las horas correspondientes a las columnas donde tienen un icono "X" rojo. Si la columna de la hora tiene un icono "visto" verde, el estudiante tiene una clase programada para esta hora. Un icono "X" rojo indica entonces una hora libre o no programada que puede ser programada.
</p>
HTML;

	$help['Scheduling/AddDrop.php'] = <<<HTML
<p>
	<i>Reporte de Añadidos / Retiros</i> es un reporte de los estudiantes que han tenido clases añadidas a o retiradas de sus horarios durante el transcurso seleccionado. Usted puede seleccionar un transcurso distinto cambiando las fechas en la parte superior de la pantalla y presionando el botón "Ir". El reporte muestra los datos de los estudiantes junto con el Curso, la Clase, y las fechas de Añadido y Retiro. Se puede exportar el reporte como hoja de Excel usando el icono "Descargar".
</p>
HTML;

	$help['Scheduling/Courses.php'] = <<<HTML
<p>
	<i>Cursos</i> le permite configurar los cursos de su institución. Hay tres tercios: Materias, Cursos, Clases.
</p>
<p>
	Para agregar cualquier de esos tres tercios, haga click sobre el icono «+» en la columna correspondiendo a lo que quiere agregar. Luego, llene la información necesaria en los campos arriba de las listas y presione el botón «Guardar».
</p>
<p>
	Para cambiar cualquier de esos tres tercios, haga click sobre el articulo que quiere cambiar, y haga click sobre cualquier dato que quiere cambiar en la parte superior de las listas. Luego, cambie el dato y presione el botón «Guardar».
</p>
<p>
	Finalmente, para eliminar algo, seleccione lo haciendo click sobre su titulo en la lista y presione el botón «Eliminar» en la parte superior de la pantalla. Se le preguntará si desea confirmar la eliminación.
</p>
HTML;

	$help['Scheduling/Scheduler.php'] = <<<HTML
<p>
	<i>Correr Programador</i> servicio cada estudiante en la institución siguiendo las solicitudes entradas para ellos.
</p>
<p>
	Usted debe primero confirmar correr el Programador. Aquí, también puede escoger correr el Programador en «Modo de Prueba» que no guardara los horarios de los estudiantes.
</p>
<p>
	Una vez que el programador ha corrido, lo que puede tomar algunos minutos, le mostrara las Solicitudes Incompletas.
</p>
HTML;
}
elseif ( User( 'PROFILE' ) === 'teacher' )
{
	$help['Scheduling/Schedule.php'] = <<<HTML
<p>
	<i>Horario Estudiante</i> muestra el horario de los estudiantes.
</p>
<p>
	Usted debe primero seleccionar un estudiante usando la pantalla de búsqueda «Encontrar un Estudiante».
</p>
HTML;
}
else
	$help['Scheduling/Schedule.php'] = <<<HTML
<p>
	<i>Horario Estudiante</i> muestra el horario de sus estudiantes.
</p>
HTML;


// GRADES
if ( User( 'PROFILE' ) === 'admin' )
{
	$help['Grades/ReportCards.php'] = <<<HTML
<p>
	<i>Boletines de Calificaciones</i> le permite imprimir boletines de calificaciones para cualquier número de estudiantes.
</p>
<p>
	Usted puede buscar estudiantes que están en un curso especifico haciendo click sobre el enlace «Escoger» al lado de la opción «Curso» y escogiendo un curso desde la ventana emergente que aparece. Usted también puede limitar su búsqueda por promedio, puesto en la clase, y calificación en la Búsqueda Avanzada. Por ejemplo, usted puede buscar los 10 mejores estudiantes de una clase, los estudiantes que fallan.
</p>
<p>
	También puede escoger imprimir los boletines con etiquetas de correo. Los boletines tendrán etiquetas de correo posicionadas de una manera visible para un sobre con ventana cuando la hoja está doblada en tercios. Mas de una carta puede ser impreza por estudiante si el estudiante tiene padres residiendo en mas de una dirección.
</p>
<p>
	Antes de imprimir los boletines, usted debe seleccionar cual periodo a calificar será en el boletín, marcando las casillas de los periodos deseados.
</p>
<p>
	Los boletines serán automáticamente creados en el formato imprimible PDF cuando usted presione el botón «Crear Boletín de Calificaciones para los Estudiantes Seleccionados».
</p>
HTML;

	$help['Grades/HonorRoll.php'] = <<<HTML
<p>
	<i>Cuadro de Honor</i> le permite crear listas o certificados de cuadro de honor.
</p>
<p>
	Los valores de promedio del Cuadro de Honor se configuran en el programa Calificaciones &gt; Escalas de Calificaciones.
</p>
<p>
	Usted debe primero seleccionar un (grupo de) estudiante(s) usando la pantalla de búsqueda «Encontrar un Estudiante». Usted puede buscar estudiantes que califican para la "Distinción" o la "Distinción de Honor" marcando las casillas respectivas. Usted puede buscar estudiantes que están en un curso especifico haciendo clic sobre el enlace «Escoger» al lado de las opción de búsqueda «Curso» y escogiendo un curso desde la ventana emergente que aparece.
</p>
<p>
	Luego, se puede generar "Certificados" or una "Lista" de los que califican seleccionando la opción correcta en la parte superior de la pantalla. El texto del Certificado puede ser personalizado modificando lo. Finalmente presione el botón "Crear Cuadro de Honor para los Estudiantes Seleccionados" para generar los certificados de Cuadro de Honor o la lista de los que califican en PDF, listo para imprimir o enviar. Alternativamente, se puede dar clic sobre el icono "Descargar" para generar un archivo Excel de estos datos.
</p>
HTML;

	$help['Grades/CalcGPA.php'] = <<<HTML
<p>
	<i>Calcular el Promedio</i> calcula y guarda el promedio y el puesto en la clase de cada estudiante en su institución basado en las calificaciones del periodo a calificar escogido.
</p>
<p>
	El servicio Calcular el Promedio calcula el promedio ponderado por cada curso multiplicando el dato promedio de la calificación ponderada por el número de créditos. Luego, el divide este dato por el número especifico como escala base de calificaciones. El promedio no ponderado se calcula de la misma manera sino que utiliza la calificación que no es ponderada. Después de calcular el promedio por cada curso, el servicio calcula el promedio del periodo a calificar. El programador clasifica estos datos para determinar el puesto en la clase. Si mas de un estudiante tiene el mismo promedio, ellos van a compartir el mismo puesto.
</p>
HTML;

	$help['Grades/Transcripts.php'] = <<<HTML
<p>
	<i>Expedientes Académicos</i> le permite imprimir expedientes académicos para cualquier número de estudiantes.
</p>
<p>
	Usted puede buscar estudiantes que están en un curso especifico haciendo click sobre el enlace «Escoger» al lado de la opción «Curso» y escogiendo un curso desde la ventana emergente que aparece. Usted también puede limitar su búsqueda por promedio, puesto en la clase, y calificación en la Búsqueda Avanzada. Por ejemplo, usted puede buscar los 10 mejores estudiantes de una clase, los estudiantes que fallan.
</p>
<p>
	Antes de imprimir los expedientes, usted debe seleccionar cuales periodos a calificar serán en el expediente marcando las casillas de los periodos a calificar. También puede escoger incluir la foto del estudiante o los comentarios asociados a cada curso.
</p>
<p>
	Los expedientes serán automáticamente creados en el formato imprimible PDF cuando usted presione el botón «Crear Expedientes Académicos para los Estudiantes Seleccionados».
</p>
HTML;

	$help['Grades/TeacherCompletion.php'] = <<<HTML
<p>
	<i>Control Docente</i> es un reporte que muestra cuales docentes no han entrado las calificaciones de cualquier periodo a calificar.
</p>
<p>
	Las cruces rojas indican que el docente no ha entrado las calificaciones del periodo a calificar actual para esta hora.
</p>
<p>
	Usted puede seleccionar el bimestre, semestre actual desde el menú desplegable en la parte superior de la pantalla. Para cambiar el bimestre actual, usa el menú desplegable de los periodos a calificar en el marco izquierdo. Usted también puede mostrar solamente una hora escogiendola en el menú desplegable en la parte superior de la pantalla.
</p>
HTML;

	$help['Grades/GradeBreakdown.php'] = <<<HTML
<p>
	<i>Análisis de Note</i> es un reporte que muestra el número de cada calificación que un docente dio.
</p>
<p>
	Usted puede seleccionar el bimestre, semestre actual desde el menú desplegable en la parte superior de la pantalla. Para cambiar el bimestre actual, usa el menú desplegable de los periodos a calificar en el marco izquierdo.
</p>
HTML;

	$help['Grades/StudentGrades.php'] = <<<HTML
<p>
	<i>Calificaciones de los Estudiantes</i> le permite ver las calificaciones de cualquier número de estudiantes.
</p>
<p>
	Usted puede buscar estudiantes que están en un curso especifico haciendo click sobre el enlace «Escoger» al lado de la opción «Curso» y escogiendo un curso desde la ventana emergente que aparece. Usted también puede limitar su búsqueda por promedio, puesto en la clase, y calificación en la Búsqueda Avanzada. Por ejemplo, usted puede buscar los 10 mejores estudiantes de una clase, los estudiantes que fallan.
</p>
HTML;

	$help['Grades/FinalGrades.php'] = <<<HTML
<p>
	<i>Calificaciones Finales</i> le permite ver las calificaciones finales de cualquier número de estudiantes.
</p>
<p>
	Usted debe primero seleccionar un (grupo de) estudiante(s) usando la pantalla de búsqueda «Encontrar un Estudiante».
</p>
<p>
	Luego, seleccione lo que quiere incluir en la Lista de Calificaciones: "Docente", "Comentarios" y "Ausencias Diarias del Año a la fecha" están pre-marcados por defecto. Si desea incluir otras columnas, marque las por favor. No olvide marcar los Períodos a Calificar que desea incluir en la Lista de Calificaciones.
</p>
<p>
	Desde los resultados de la búsqueda, usted puede seleccionar cualquier número de estudiantes. Usted puede seleccionar todos los estudiantes de la lista marcando la casilla en el encabezado de la lista.
</p>
<p>
	Finalmente, presione el botón "Crear Listas de Calificaciones para los Estudiantes Seleccionados".
</p>
<p>
	Nota bien por favor que si usted selecciona solamente UN Período a Calificar, usted podrá eliminar la Calificación Final haciendo clic sobre el icono "-" en la parte izquierda de la pantalla, y luego confirmar su elección.
</p>
HTML;

	$help['Grades/GPARankList.php'] = <<<HTML
<p>
	<i>Lista de Promedio / Puesto en la Clase</i> es un reporte que muestra el promedio ponderado, no ponderado, y puesto en la clase de cada estudiante en su institución.
</p>
<p>
	Como para cada lista en RosarioSIS, usted puede ordenar cualquier dato haciendo click sobre el titulo de la columna en el encabezado. Por ejemplo, usted puede ordenar por grado haciendo click sobre «Grado» en el encabezado.
</p>
HTML;

	$help['Grades/ReportCardGrades.php'] = <<<HTML
<p>
	<i>Escalas de Calificaciones</i> le permite configurar las escalas de calificaciones de su institución. Las calificaciones del boletín de calificaciones están usadas en el servicio Entrar Calificaciones Finales por los docentes y en la mayor parte de los reportes de Calificaciones. Las calificaciones del boletín de calificaciones incluyen calificaciones de letra así como comentarios de calificación que un docente puede escoger al momento de entrar las calificaciones.
</p>
<p>
	Para agregar una calificación del boletín de calificaciones, llene el título de la calificación, el valor de promedio y la orden en los campos vacillos al final de la lista de alificaciones y presione el boton «Guardar».
</p>
<p>
	Para agregar un comentario, entre el título del nuevo comentario en el campo al final de la lista de comentarios.
</p>
<p>
	Para modificar cualquier tipo de calificación, haga clic sobre cualquier información de la calificación, cambie el valor, y haga clic sobre el boton «Guardar».
</p>
<p>
	Para eliminar cualquier tipo de calificación, haga clic sobre el icono eliminar (-) al lado de la calificación que quiere eliminar. Se le preguntará si desea confirmar la eliminación.
</p>
HTML;

	$help['Grades/ReportCardComments.php'] = <<<HTML
<p>
	<i>Comentarios del Boletín de Calificaciones</i> le permite configurar los comentarios del boletín de calificaciones de su institución, para cada curso o todos los cursos.
</p>
<p>
	La pestaña "Todos los Cursos" es donde se crean Comentarios que aplican para Todos los Cursos, por ejemplo para calificar el comportamiento, o una cualidad de los estudiantes que todos los cursos comparten. La pestaña (+) es donde se crean los otros comentarios, específicamente las pestañas de comentarios y los comentarios propios a un curso.
</p>
<p>
	La pestaña "General" contiene los comentarios que son agregados al momento de entrar las calificaciones de los estudiantes en el programa "Entrar Calificaciones Finales". Los docentes pueden usar el menú desplegable bajo la pestaña "General" para agregar uno o varios comentarios pre-diseñados al Boletín. Por favor note bien que RosarioSIS tiene parámetros de sustitución que se pueden usar en estos comentarios: "^n" sera sustituido por el nombre del estudiante, y "^s" sera sustituido por un pronombre del genero apropiado. Por ejemplo, el comentario "^n Viene a ^s Clase sin Preparar" sera traducido por "Juan Viene a su Clase sin Preparar" en el boletín de Juan Rodriguez.
</p>
<p>
	La pestaña "Todos los Cursos" le permite crear comentarios que aplican a Todos los Cursos. Entre el nombre del Comentario y asociado a una "Escala de Códigos" (creada en el programa "Códigos de Comentarios") usando el menú desplegable. El resultado sera una nueva columna para el comentario en el programa "Entrar Calificaciones Finales", bajo la pestaña "Todos los Cursos". La columna mostrara un menú desplegable con los códigos de comentarios de la escala asociada.
</p>
<p>
	Para crear comentarios específicos a un curso, primero seleccione un curso usando los menús desplegables en la parte superior de la pantalla. Luego haga clic sobre la pestaña (+) para crear una Categoría de Comentario. Presione "Guardar" y luego una nueva pestaña con el nombre de la categoría aparecerá. Allí usted podrá agregar comentarios, uno por uno, y asociarlos a una "Escala de Códigos" (creada en el programa "Códigos de Comentarios") usando el menú desplegable. El resultado sera una nueva pestaña en el programa "Entrar Calificaciones Finales". La pestaña tendrá el nombre de la categoría de comentarios y mostrara una columna para cada comentario bajo esta categoría. Las columnas mostraran un menú desplegable con los códigos de comentarios de la escalas asociadas.
</p>
HTML;

	$help['Grades/ReportCardCommentCodes.php'] = <<<HTML
<p>
	<i>Códigos de Comentarios</i> le permite crear escalas de comentarios que muestran menús desplegables de códigos de calificaciones en el programa Entrar Calificaciones Finales. Luego, estos códigos estarán expuestos junto con el comentario asociado en el Boletín de Calificaciones.
</p>
<p>
	Para crear una nueva Escala de Comentarios, haga clic sobre la pestaña con el icono (+). Nombre su escala de comentarios, agregue un comentario opcional y luego presione "Guardar". Una nueva pestaña con el nombre de la nueva Escala de Comentarios aparecerá. Haga clic sobre la pestaña de la escala de comentarios para seleccionarla y luego se podrá agregar, uno por uno, los códigos de la escala de comentarios llenando su "Título" (entre aquí el código), "Nombre Corto" y "Comentario" (lo cual aparecerá en el Boletín de Calificaciones).
</p>
HTML;

	$help['Grades/EditHistoryMarkingPeriods.php'] = <<<HTML
<p>
	<i>Períodos a Calificar Históricos</i> le permite crear períodos a calificar para calificaciones de los años pasados.
</p>
<p>
	Use este programa primero si desea entrar calificaciones de los años pasados que fueron dadas antes de instalar RosarioSIS, o si quiere entrar las calificaciones de un estudiante transferido a su institución. Una vez el período a calificar histórico agregado, usted podrá seleccionarlo en el programa Editar Calificaciones del Estudiante.
</p>
<p>
	Por favor tome en cuenta que el campo "Fecha de Publicación de la Calificación" determina la orden de los períodos a calificar históricos al momento de entrar calificaciones o de generar el Expediente Académico, entonces debería ser entrada correctamente. También, cada período a calificar histórico se crea una sola vez.
</p>
HTML;

	$help['Grades/EditReportCardGrades.php'] = <<<HTML
<p>
	<i>Editar Calificaciones del Estudiante</i> le permite entrar las calificaciones de los años pasados de un estudiante o de un estudiante transferido.
</p>
<p>
	Usted debe primero seleccionar un estudiante usando la pantalla de búsqueda «Encontrar un Estudiante».
</p>
<p>
	Ahora, para el estudiante seleccionado, agregue el periodo a calificar (típicamente un período a calificar histórico creado con el programa Períodos a Calificar Históricos) seleccionándolo con el menú desplegable "Nuevo Período Académico". Luego, entre el Grado para el estudiante seleccionado y presione "Guardar".
</p>
<p>
	Usted puede entrar las calificaciones del estudiante en la pestaña "Calificaciones". Entre el "Curso" y las calificaciones asociadas, luego presione "Guardar". Por favor note que se puede usar una escala de calificaciones propia para los cálculos de promedio.
</p>
<p>
	RosarioSIS necesita créditos para calcular el promedio. Por favor consulte la pestaña "Créditos" y ajuste los créditos de cada curso como debe ser.
</p>
HTML;
}
elseif ( User( 'PROFILE' ) === 'teacher' )
{
	$help['Grades/InputFinalGrades.php'] = <<<HTML
<p>
	<i>Entrar Calificaciones Finales</i> le permite entrar las calificaciones del bimestre, semestre para todos sus estudiantes en la clase actual. Por defecto, el servicio lista los estudiantes en su primera clase para el bimestre actual. Usted puede cambiar el bimestre en el menú desplegable en el marco izquierdo. Usted también puede seleccionar el semestre actual en el menú desplegable en la parte superior de la pantalla.
</p>
<p>
	Una vez que esté en el periodo a calificar deseado, usted puede entrar las calificaciones de los estudiantes seleccionando la calificación para cada estudiante y entrar comentarios si lo desea. Una vez que todos los grados y comentarios están entrados, presione el botón «Guardar» en la parte superior de la pantalla.
</p>
<p>
	Si usted usa el Libro de Calificaciones, RosarioSIS puede calcular cada calificación del bimestre haciendo click sobre el enlace «Usar la Calificaciones del Libro de Calificaciones» de arriba.
</p>
<p>
	Si el mensaje «Usted no puede editar estas calificaciones.» aparece, usted no puede entrar las calificaciones finales ese día porque este no se encuentra en el transcurso de publicación de la calificaciones configurado por el periodo a calificar. Ver el servicio Períodos a Calificar para conocer las fechas de publicación de calificaciones.
</p>
HTML;

	$help['Grades/Configuration.php'] = <<<HTML
<p>
	<i>Libro de Calificaciones - Configuración</i> le permite configurar el libro de calificaciones.
</p>
<p>
	Usted puede configurar el libro de calificaciones para ponderar las calificaciones de las tareas, ocultar las calificaciones de letra para todas las tareas, calcular Elegibilidad usando las calificaciones, y los porcentajes de la calificación final del semestre.
</p>
HTML;

	$help['Grades/Assignments.php'] = <<<HTML
<p>
	<i>Libro de Calificaciones - Tareas</i> le permite configurar sus tareas. Hay tipos de tareas y tareas.
</p>
<p>
	Usted tendrá probablemente tipos de tareas llamados «Trabajo», «Quiz», o «Previa». Los tipos de tareas se crean para cada curso que usted tiene.
</p>
<p>
	Para agregar un tipo de tarea o una tarea, haga click sobre el icono «+» en la columna deseada. Luego, llene los datos en los campos arriba de las listas y presione el botón «Guardar».
</p>
<p>
	Si usted marca la casilla «Aplicar a todas las Clases para este Curso», la tarea será asignada a cada clase del curso que usted tiene, de la misma manera que están agregados los tipos de tarea.
</p>
<p>
	Para cambiar un tipo o una tarea, haga click sobre el tipo o la tarea usted quiere cambiar y luego haga click sobre el dato que usted quiere cambiar en la parte superior de las listas de tareas y tipos. Luego, cambie el dato y presione el botón «Guardar».
</p>
<p>
	Finalmente, para eliminar una tarea o un tipo, seleccionelo haciendo click sobre su titulo en la lista y presione el botón «Eliminar». Se le preguntará si desea confirmar la eliminación.
</p>
HTML;

	$help['Grades/Grades.php'] = <<<HTML
<p>
	<i>Libro de Calificaciones - Calificaciones</i> le permite entrar las calificaciones de las tareas para todos sus estudiantes en el periodo a calificar actual. Por defecto, el servicio lista los estudiantes de su primera clase. Usted puede cambiar la clase actual en el menú desplegable del marco izquierdo.
</p>
<p>
	Una vez que usted ha escogido la clase correcta, usted vera el total de puntos y la calificación ponderada de cada estudiante en su clase. Usted puede ver las calificaciones de una tarea seleccionandola en el menú desplegable de las tareas en la parte superior de la pantalla. Luego, usted puede entrar una nueva calificación entrando los puntos en el campo vacío al lado del nombre del estudiante o usted puede cambiar una calificación existente haciendo click sobre los puntos y cambiando el dato. Después de cambiar las calificaciones, presione el botón «Guardar» en la parte superior de la pantalla.
</p>
<p>
	Usted también puede ver y cambiar todas las calificaciones de un estudiante haciendo click sobre su nombre en la lista.
</p>
HTML;

	$help['Grades/ProgressReports.php'] = <<<HTML
<p>
	<i>Libro de Calificaciones - Reportes de Progreso</i> le permite imprimir reportes de progreso para cualquier número de estudiantes.
</p>
<p>
	Usted puede imprimir reportes de progreso con etiquetas de correo. Los reportes tendrán etiquetas de correo posicionadas de una manera visible para un sobre con ventana cuando la hoja está doblada en tercios. Mas de una carta puede ser impreza por estudiante si el estudiante tiene padres residiendo en mas de una dirección.
</p>
<p>
	Los reportes serán automáticamente creados en el formato imprimible PDF cuando usted presione el botón «Crear Reportes de Progreso para los Estudiantes Seleccionados».
</p>
HTML;
}
else
{
	$help['Grades/ReportCards.php'] = <<<HTML
<p>
	<i>Boletines de Calificaciones</i> le permite imprimir boletines de calificaciones para su estudiante.
</p>
<p>
	Antes de imprimir el boletín, usted debe seleccionar cuales periodos a calificar estarán en el boletín marcando las casillas de los periodos a calificar deseados.
</p>
<p>
	El boletín de calificaciones será automáticamente creado en el formato imprimible PDF cuando usted presione el botón «Crear Boletín de Calificaciones para los Estudiantes Seleccionados».
</p>
HTML;

	$help['Grades/Transcripts.php'] = <<<HTML
<p>
	<i>Expedientes Académicos</i> le permite imprimir expedientes académicos para su estudiante.
</p>
<p>
	Antes de imprimir el boletín, usted debe seleccionar cuales periodos a calificar estarán en el boletín marcando las casillas de los periodos a calificar deseados.
</p>
<p>
	Los expedientes académicos serán automáticamente creados en el formato imprimible PDF cuando usted presione el botón «Crear Expedientes Académicos para los Estudiantes Seleccionados».
</p>
HTML;

	$help['Grades/StudentGrades.php'] = <<<HTML
<p>
	<i>Calificaciones del Libro de Calificaciones</i> le permite ver las calificaciones de su estudiante.
</p>
<p>
	Usted puede cambiar el periodo a calificar en el menú desplegable del marco izquierdo.
</p>
HTML;

	$help['Grades/GPARankList.php'] = <<<HTML
<p>
	<i>Promedio / Puesto en la Clase</i> es un reporte que muestra el promedio ponderado y no ponderado, y el puesto en la clase de su estudiante.
</p>
HTML;
}


// ATTENDANCE
if ( User( 'PROFILE' ) === 'admin' )
{
	$help['Attendance/Administration.php'] = <<<HTML
<p>
	<i>Administración</i> le permite ver y cambiar los registros de asistencia de cualquier día.
</p>
<p>
	Para cambiar el estado de asistencia para cualquier hora, haga click sobre el dato y seleccione el nombre corto del código de asistencia que usted quisiera asociar a este estudiante. Después de hacer las modificaciones, presione el botón «Actualizar». Usted puede limitar la lista de estudiantes escogiendo un código de asistencia en el menú desplegable en la parte superior derecha de la pantalla. Por defecto, todos los estudiantes con cualquier código de estado «Ausente» están listados. Usted puede agregar un código de asistencia haciendo click sobre el icono «+» al lado del menú desplegable de los códigos de asistencia.
</p>
<p>
	Usted puede cambiar la fecha mostrada haciendo click sobre la fecha en la parte superior izquierda de la pantalla y cambiándola por la fecha deseada.
</p>
<p>
	Después de cambiar el código de asistencia o la fecha, presione el botón «Actualizar» para refrescar la pantalla con los nuevos parámetros.
</p>
<p>
	Usted también puede ver el código de asistencia asignado por el docente y también ver y entrar un comentario para cada hora haciendo click sobre el nombre del estudiante.
</p>
<p>
	Haciendo click sobre el enlace «Estudiante actual» en la parte superior de la pantalla mostrara los registros de asistencia del día para el estudiante actual presente en el marco izquierdo.
</p>
HTML;

	$help['Attendance/AddAbsences.php'] = <<<HTML
<p>
	<i>Agregar Ausencias</i> le permite agregar una ausencia a un grupo de estudiantes de una vez.
</p>Primero, busca estudiantes. Note que usted puede buscar para estudiantes que están tomando un curso específico o están en una actividad especial.
<p>
	Desde los resultados de la búsqueda, usted puede seleccionar cualquier número de estudiantes. Usted puede seleccionar todos los estudiantes de la lista marcando la casilla en el encabezado de la lista. Usted también puede especificar las horas, el código de ausencia, la razón de la ausencia, y la fecha. Finalmente, presione el botón «Guardar» en la parte superior de la pantalla.
</p>
HTML;

	$help['Attendance/Percent.php'] = <<<HTML
<p>
	<i>Asistencia Diaria Promedio</i> es un reporte que muestra el número de estudiantes, los días posibles, el número de estudiantes presentes por los días posibles, el número de estudiantes ausentes por los días posibles, la Asistencia Diaria Promedio, el número de estudiantes presentes por día promedio, y el número de estudiantes ausentes por día promedio. Estos números están para cada grado.
</p>
<p>
	Usted puede cambiar el periodo con los menús desplegables de las fechas en la parte superior de la pantalla y presionando el botón «Ir». Usted también puede limitar los números buscando por sexo o cualquier campo de dato haciendo click sobre el enlace «Avanzado».
</p>
HTML;

	$help['Attendance/Percent.php&list_by_day=true'] = <<<HTML
<p>
	<i>Asistencia Promedio por Día</i> es un reporte que muestra el número de estudiantes, los días posibles, el número de estudiantes presentes por los días posibles, el número de estudiantes ausentes por los días posibles, la Asistencia Diaria Promedio por día, el número de estudiantes presentes por día promedio, y el número de estudiantes ausentes por día promedio. Estos números están para cada grado.
</p>
<p>
	Usted puede cambiar el periodo con los menús desplegables de las fechas en la parte superior de la pantalla y presionando el botón «Ir». Usted también puede limitar los números buscando por sexo o cualquier campo de dato haciendo click sobre el enlace «Avanzado».
</p>
HTML;

	$help['Attendance/DailySummary.php'] = <<<HTML
<p>
	<i>Gráfico de Asistencia</i> es un reporte que muestra la asistencia diaria de cualquier número de estudiantes para cada día de un transcurso.
</p>
<p>
	Después de buscar por estudiantes, usted puede cambiar el periodo con las fechas en los menús desplegables en la parte superior de la pantalla. La lista muestra el dato de asistencia diaria de cada estudiante para cada día con un código de color. Rojo significa ausente todo el día, amarillo significa ausente medio día y verde significa presente todo el día.
</p>
<p>
	Usted puede ver los registros de asistencia para cada hora, para cualquier estudiante haciendo click sobre el nombre del estudiante. Aquí, el código está en la casilla de color.
</p>
HTML;

	$help['Attendance/StudentSummary.php'] = <<<HTML
<p>
	<i>Resumen de la Ausencia</i> es un reporte que muestra los días por lo cuales un estudiante tiene una ausencia.
</p>
<p>
	Después de seleccionar un estudiante, usted puede cambiar el periodo con las fechas en los menús desplegables en la parte superior de la pantalla y presionando el botón «Ir». La lista muestra las ausencias del estudiante para cada hora de cada día con ausencia. Una cruz roja indica una ausencia por la hora correspondiente.
</p>
HTML;

	$help['Attendance/TeacherCompletion.php'] = <<<HTML
<p>
	<i>Control Docente</i> es un reporte que muestra cuales docentes no han tomado la asistencia para cualquier día.
</p>
<p>
	Las cruces rojas indican que un docente no entró la asistencia del día para este periodo.
</p>
<p>
	Usted puede seleccionar la fecha actual en el menú desplegable en la parte superior de la pantalla. Usted también puede mostrar solamente una hora escogiendola en el menú desplegable de las horas en la parte superior de la pantalla.
</p>
HTML;

	$help['Attendance/FixDailyAttendance.php'] = <<<HTML
<p>
	<i>Recalcular Asistencia Diaria</i> es una utilidad para recalcular la asistencia diaria de un transcurso especifico.
</p>
<p>
	Seleccione el transcurso y presione "OK". Toda la asistencia estará calculada para el día completo y el medio día. Seleccione un transcurso más corto si el sistema se congela. Usando esta utilidad podrá evitar problemas relacionados con la asistencia faltante de las clases.
</p>
HTML;

	$help['Attendance/DuplicateAttendance.php'] = <<<HTML
<p>
	<i>Eliminar Asistencia Duplicada</i> es una utilidad para localizar y eliminar cualquier asistencia tomada para un estudiante DESPUÉS de su fecha de retiro.
</p>
<p>
	En el caso de que un estudiante este retirado de manera retroactiva de un curso, pero la asistencia ya ha sido tomada por docentes o administradores para fechas posteriores a la fecha de retiro.
</p>
HTML;
	
	$help['Attendance/AttendanceCodes.php'] = <<<HTML
<p>
	<i>Códigos de Asistencia</i> le permite configurar los códigos de asistencia de su institución. Los códigos de asistencia son usados en el servicio «Tomar Asistencia» del docente (también como en la mayoría de los reportes de Asistencia) y especifican si el estudiante estaba presente o no durante la hora y si no, la razón.
</p>
<p>
	Para agregar un código de asistencia, llene el titulo, nombre corto, tipo, y código del estado. Seleccione si el código debería estar o no por defecto para el docente desde los campos vacíos al pié de la lista y presione el botón «Guardar». Si el código de asistencia es del tipo «Oficina Solamente», un docente no será capaz de seleccionarlo desde su servicio «Tomar Asistencia».
</p>
<p>
	Para cambiar un código de asistencia, haga click sobre cualquier dato del código de asistencia, cambie el dato, y presione el botón «Guardar».
</p>
<p>
	Para eliminar un código de asistencia, haga click sobre el icono «-» al lado del código de asistencia que quiere eliminar. Se le preguntará si desea confirmar la eliminación.
</p>
HTML;

	$help['Custom/AttendanceSummary.php'] = <<<HTML
<p>
	<i>Resumen de la Asistencia</i> es un reporte que muestra un registro día por día de la asistencia de cada estudiante para todo el año escolar en un tablero.
</p>
<p>
	Usted debe primero seleccionar un (grupo de) estudiante(s) usando la pantalla de búsqueda «Encontrar un Estudiante». Usted puede buscar estudiantes que han solicitado un curso especifico haciendo clic sobre el enlace «Escoger» al lado de la opción de búsqueda «Curso» y escogiendo un curso desde la ventana emergente que aparece.
</p>
<p>
	Desde el resultado de la búsqueda, usted puede seleccionar cualquier número de estudiantes. Usted puede seleccionar todos los estudiantes de la lista haciendo clic sobre la casilla en el encabezado de la lista. Después de seleccionar los estudiantes, presione "Crear Reporte de Asistencia para los Estudiantes Seleccionados" para generar el reporte en formato PDF.
</p>
HTML;
}
elseif ( User( 'PROFILE' ) === 'teacher' )
{
	$help['Attendance/TakeAttendance.php'] = <<<HTML
<p>
	<i>Tomar Asistencia</i> le permite entrar la asistencia para todos los estudiantes de la clase actual. Por defecto, el servicio lista los estudiantes de su primera clase. Usted puede cambiar la clase actual en el menú desplegable en el marco izquierdo.
</p>
<p>
	Una vez que está en la clase correcta, usted puede tomar la asistencia seleccionando el código de asistencia para cada estudiante. Una vez que usted ha tomado la asistencia para todos sus estudiantes, presione el botón «Guardar» en la parte superior de la pantalla.
</p>
HTML;
}
else
	$help['Attendance/DailySummary.php'] = <<<HTML
<p>
	<i>Resumen Diario</i> es un reporte que muestra la asistencia diaria de su estudiante durante un transcurso.
</p>
<p>
	Usted puede modificar el periodo en los menús desplegables de las fechas en la parte superior de la pantalla y presionar el botón «Ir». La lista muestra el dato de la asistencia diaria de su estudiante para cada día con códigos de color. Una caja roja significa que el estudiante estaba ausente ese día, una caja verde que el estudiante estaba presente o tarde ese día. El código de ausencia está en la caja.
</p>
HTML;


// ELIGIBILITY
if ( User( 'PROFILE' ) === 'admin' )
{
	$help['Eligibility/Student.php'] = <<<HTML
<p>
	<i>Pantalla Estudiante</i> muestra las actividades del estudiante y las calificaciones de elegibilidad del transcurso actual. El servicio le permite también de agregar y eliminar actividades del estudiante.
</p>
<p>
	Usted debe primero seleccionar un estudiante usando la pantalla «Encontrar un estudiante». Usted puede buscar estudiantes que están en un curso especifico haciendo click sobre el enlace «Escoger» al lado de la opción «Curso» y escogiendo un curso desde la ventana emergente que aparece. Usted también puede buscar estudiantes que toman una actividad especifica y estudiantes que están inelegibles actualmente.
</p>
<p>
	Para agregar una actividad a un estudiante, seleccione la actividad deseada en el menú desplegable al lado del icono «+» y presione el botón «Agregar».
</p>
<p>
	Usted puede especificar el periodo de elegibilidad escogiendo las fechas deseadas en el menú desplegable en la parte superior de la pantalla. Estos periodos se configuran en el servicio «Tiempos de Entrada».
</p>
HTML;

	$help['Eligibility/AddActivity.php'] = <<<HTML
<p>
	<i>Agregar Actividad</i> le permite agregar una actividad a un grupo de estudiantes de una vez.
</p>
<p>
	Primero, busca estudiantes. Note que se puede buscar estudiantes que están en una actividad o en un curso especifico. Desde el resultado de la búsqueda, usted puede seleccionar cualquier número de estudiantes. Usted puede seleccionar todos los estudiantes de la lista haciendo click sobre la casilla en el encabezado de la lista. Después de seleccionar los estudiantes, seleccione una actividad en el menú desplegable en la parte superior de la pantalla. Finalmente, presione el botón «Agregar la Actividad a los Estudiantes Seleccionados».
</p>
HTML;

	$help['Eligibility/Activities.php'] = <<<HTML
<p>
	<i>Actividades</i> le permite configurar las actividades de su institución.
</p>
<p>
	Para agregar una actividad, llene su titulo, fecha de comienzo y fecha de fin en los campos vacíos al fin de la lista y presione el botón «Guardar».
</p>
<p>
	Para modificar una actividad, haga click sobre cualquier dato de la actividad, cambie el dato, y presione el botón «Guardar».
</p>
<p>
	Para eliminar una actividad, haga click sobre el icono «-» al lado de la actividad que quiere eliminar. Se le preguntará si desea confirmar la eliminación.
</p>
HTML;

	$help['Eligibility/EntryTimes.php'] = <<<HTML
<p>
	<i>Tiempos de Entrada</i> le permite configurar el transcurso semanal durante lo cual docentes pueden entrar la elegibilidad. Docentes deben entrar cada semana en este transcurso. Este transcurso está usado en el servicio «Entrar Elegibilidad» como en la mayor parte de los reportes de elegibilidad.
</p>
<p>
	Para cambiar el periodo, simplemente cambie las fechas y presione el botón «Guardar».
</p>
HTML;

	$help['Eligibility/StudentList.php'] = <<<HTML
<p>
	<i>Lista de Estudiante</i> es un reporte que muestra cada curso y calificación de elegibilidad asignado a cualquier número de estudiantes.
</p>
<p>
	Después de buscar estudiantes, usted puede especificar el periodo de elegibilidad que quiere ver. Estos transcursos se configuran en el servicio «Tiempos de Entrada».
</p>
HTML;

	$help['Eligibility/TeacherCompletion.php'] = <<<HTML
<p>
	<i>Control Docente</i> es un reporte que muestra cuales docentes no han entrado la elegibilidad para cualquier transcurso. Este transcurso se configura en el servicio «Tiempos de Entrada».
</p>
<p>
	La cruz roja indica que el docente no entró la elegibilidad del periodo actual para esta hora.
</p>
<p>
	Usted puede seleccionar el periodo actual en el menú desplegable en la parte superior de la pantalla. Usted también puede mostrar solamente una hora escogiendola desde el menú desplegable en la parte superior de la pantalla. Después de seleccionar un transcurso o una hora, presione el botón «Ir» para refrescar la lista.
</p>
HTML;
}
elseif ( User( 'PROFILE' ) === 'teacher' )
{
	$help['Eligibility/EnterEligibility.php'] = <<<HTML
<p>
	<i>Entrar Elegibilidad</i> le permite entrar las calificaciones de elegibilidad para todos sus estudiantes en la clase actual. Por defecto, el servicio lista los estudiantes de su primera clase. Usted puede cambiar la clase actual en el menú desplegable del marco izquierdo.
</p>
<p>
	Una vez que esté en la clase correcta, usted puede entrar las calificaciones de elegibilidad seleccionando el código para cada estudiante. Una vez que usted ha entrado la elegibilidad para todos sus estudiantes, presione el botón «Guardar» en la parte superior de la pantalla.
</p>
<p>
	Si usted usa el Libro de Calificaciones, RosarioSIS puede calcular la elegibilidad de cada estudiante haciendo click sobre el enlace «Usar la Calificaciones del Libro de Calificaciones» de arriba.
</p>
<p>
	Usted debe entrar la elegibilidad cada semena durante el transcurso especificado por la administración de su institución.
</p>
HTML;
}
else
{
	$help['Eligibility/Student.php'] = <<<HTML
<p>
	<i>Pantalla Estudiante</i> muestra las actividades de su estudiante y las calificaciones de elegibilidad del transcurso actual.
</p>
<p>
	Usted puede especificar el periodo de elegibilidad que quiere ver escogiendo las fechas en el menú desplegable en la parte superior de la pantalla. La elegibilidad está entrada cada semana.
</p>
HTML;

	$help['Eligibility/StudentList.php'] = <<<HTML
<p>
	<i>Lista de Estudiante</i> es un reporte que muestra todas las calificaciones y las calificaciones de elegibilidad de su estudiante.
</p>
<p>
	Usted puede especificar el transcurso de elegibilidad que quiere ver escogiendo las fechas en el menú desplegable en la parte superior de la pantalla y presionando el botón «Ir». La elegibilidad está entrada cada semana.
</p>
HTML;
}
