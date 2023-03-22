--
-- PostgreSQL & MySQL data update
--
-- Translates database fields to Spanish
--
-- Note: Uncheck "Paginate results" when importing with phpPgAdmin
--

--
-- Data for Name: schools; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE schools
SET title='Institución Ejemplo', address='Calle 13', city='Madrid', state=NULL, zipcode='28037', principal='Sr. Principal', www_address='www.rosariosis.org/es', reporting_gp_scale=5
WHERE id=1;


--
-- Data for Name: attendance_calendars; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE attendance_calendars
SET title='Principal'
WHERE calendar_id=1;


--
-- Data for Name: config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE config
SET config_value='Rosario Student Information System|es_ES.utf8:Sistema de Información Estudiantil Rosario'
WHERE title='TITLE';

--
-- Data for Name: student_enrollment_codes; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE student_enrollment_codes
SET title='Cambió de Locación', short_name='CAMB'
WHERE id=1;

UPDATE student_enrollment_codes
SET title='Expulsado', short_name='EXP'
WHERE id=2;

UPDATE student_enrollment_codes
SET title='Comienzo de Año', short_name='ANO'
WHERE id=3;

UPDATE student_enrollment_codes
SET title='De otra Locación', short_name='OTRA'
WHERE id=4;

UPDATE student_enrollment_codes
SET title='Transferencia', short_name='TRAN'
WHERE id=5;

UPDATE student_enrollment_codes
SET title='Transferencia', short_name='MANO'
WHERE id=6;


--
-- Data for Name: report_card_grade_scales; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE report_card_grade_scales
SET title='Principal', gp_scale=5, gp_passing_value=3
WHERE id=1;


--
-- Data for Name: report_card_grades; Type: TABLE DATA; Schema: public; Owner: postgres
--


UPDATE report_card_grades
SET title='5.0', gpa_value=5.0, break_off=100, comment='Superior'
WHERE id=1;

UPDATE report_card_grades
SET title='4.5', gpa_value=4.5, break_off=90, comment='Superior'
WHERE id=2;

UPDATE report_card_grades
SET title='4.0', gpa_value=4.0, break_off=80, comment='Alto'
WHERE id=3;

UPDATE report_card_grades
SET title='3.5', gpa_value=3.5, break_off=70, comment='Básico'
WHERE id=4;

UPDATE report_card_grades
SET title='3.0', gpa_value=3.0, break_off=60, comment='Básico'
WHERE id=5;

UPDATE report_card_grades
SET title='2.5', gpa_value=2.5, break_off=50, comment='Insuficiente'
WHERE id=6;

UPDATE report_card_grades
SET title='2.0', gpa_value=2.0, break_off=40, comment='Insuficiente'
WHERE id=7;

UPDATE report_card_grades
SET title='1.5', gpa_value=1.5, break_off=30, comment='Insuficiente'
WHERE id=8;

UPDATE report_card_grades
SET title='1.0', gpa_value=1.0, break_off=20, comment='Insuficiente'
WHERE id=9;

UPDATE report_card_grades
SET title='0.5', gpa_value=0.5, break_off=10, comment='Insuficiente'
WHERE id=10;

UPDATE report_card_grades
SET title='0.0', gpa_value=0.0, break_off=0, comment='Insuficiente'
WHERE id=11;

UPDATE report_card_grades
SET title='I', gpa_value=0.0, break_off=0, comment='Incompleto'
WHERE id=12;

UPDATE report_card_grades
SET title='N/A', gpa_value=NULL, break_off=NULL, comment=NULL
WHERE id=13;

DELETE FROM report_card_grades WHERE id IN(14,15);


--
-- Data for Name: school_marking_periods; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE school_marking_periods
SET title='Año Completo', short_name='Año'
WHERE marking_period_id=1;

UPDATE school_marking_periods
SET title='Semestre 1', short_name='S1'
WHERE marking_period_id=2;

UPDATE school_marking_periods
SET title='Semestre 2', short_name='S2'
WHERE marking_period_id=3;

UPDATE school_marking_periods
SET title='Trimestre 1', short_name='T1'
WHERE marking_period_id=4;

UPDATE school_marking_periods
SET title='Trimestre 2', short_name='T2'
WHERE marking_period_id=5;

UPDATE school_marking_periods
SET title='Trimestre 3', short_name='T3'
WHERE marking_period_id=6;

UPDATE school_marking_periods
SET title='Trimestre 4', short_name='T4'
WHERE marking_period_id=7;


--
-- Data for Name: school_periods; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE school_periods
SET title='Día Completo', short_name='DIA'
WHERE period_id=1;

UPDATE school_periods
SET title='Mañana', short_name='AM'
WHERE period_id=2;

UPDATE school_periods
SET title='Tarde', short_name='PM'
WHERE period_id=3;

UPDATE school_periods
SET title='Hora 1', short_name='01'
WHERE period_id=4;

UPDATE school_periods
SET title='Hora 2', short_name='02'
WHERE period_id=5;

UPDATE school_periods
SET title='Hora 3', short_name='03'
WHERE period_id=6;

UPDATE school_periods
SET title='Hora 4', short_name='04'
WHERE period_id=7;

UPDATE school_periods
SET title='Hora 5', short_name='05'
WHERE period_id=8;

UPDATE school_periods
SET title='Hora 6', short_name='06'
WHERE period_id=9;

UPDATE school_periods
SET title='Hora 7', short_name='07'
WHERE period_id=10;

UPDATE school_periods
SET title='Hora 8', short_name='08'
WHERE period_id=11;


--
-- Data for Name: templates; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE templates
SET template='<br /><br /><br />
<div style="text-align: center;"><span style="font-size: xx-large;"><strong>__SCHOOL_ID__</strong><br /></span><br /><span style="font-size: xx-large;">Nosotros por medio de la presente reconocimos<br /><br /></span></div>
<div style="text-align: center;"><span style="font-size: xx-large;"><strong>__FIRST_NAME__ __LAST_NAME__</strong><br /><br /></span></div>
<div style="text-align: center;"><span style="font-size: xx-large;">Quien ha completado todos los requisitos acad&eacute;micos para el <br />Cuadro de Honor</span></div>'
WHERE modname='Grades/HonorRoll.php';

UPDATE templates
SET template='<div style="text-align: center;">__CLIPART__<br /><br /><strong><span style="font-size: xx-large;">__SCHOOL_ID__<br /></span></strong><br /><span style="font-size: xx-large;">Teniendo en Cuenta que<br /><br /></span></div>
<div style="text-align: center;"><strong><span style="font-size: xx-large;">__FIRST_NAME__ __LAST_NAME__<br /><br /></span></strong></div>
<div style="text-align: center;"><span style="font-size: xx-large;">Obtuvo la Excelencia Acad&eacute;mica en<br />__SUBJECT__</span></div>'
WHERE modname='Grades/HonorRollSubject.php';

UPDATE templates
SET template='<h2 style="text-align: center;">Certificado de Estudios</h2>
<p>La suscrita Rectora y Secretaria certifican:</p>
<p>Que __FIRST_NAME__ __LAST_NAME__ identificada con D.I. __SSECURITY__ cursó en este plantel los estudios correspondientes al grado __GRADE_ID__ durante el año __YEAR__ con las calificaciones e intensidad horaria que a continuación detallamos.</p>
<p>El Estudiante es promovido a grado __NEXT_GRADE_ID__.</p>
<p>__BLOCK2__</p>
<p>&nbsp;</p>
<table style="border-collapse: collapse; width: 100%;" border="0" cellpadding="10"><tbody><tr>
<td style="width: 50%; text-align: center;"><hr />
<p>Firma</p>
<p>&nbsp;</p><hr />
<p>Título</p></td>
<td style="width: 50%; text-align: center;"><hr />
<p>Firma</p>
<p>&nbsp;</p><hr />
<p>Título</p></td></tr></tbody></table>'
WHERE modname='Grades/Transcripts.php';

UPDATE templates
SET template='Estimado __PARENT_NAME__,

Una cuenta de padres para el __SCHOOL_ID__ ha sido creada para acceder a la información de la institución y de los siguientes estudiantes:
__ASSOCIATED_STUDENTS__

Sus datos de cuenta son:
Nombre de usuario: __USERNAME__
Contraseña: __PASSWORD__

Un enlace hacia el Sistema de Información Académica e instrucciones para el acceso están disponibles en el sitio de la institución.__BLOCK2__Estimado __PARENT_NAME__,

Los siguientes estudiantes han sido adicionados a su cuenta de padres en el Sistema de Información Académica:
__ASSOCIATED_STUDENTS__'
WHERE modname='Custom/CreateParents.php';

UPDATE templates
SET template='Estimado __PARENT_NAME__,

Una cuenta de padres para el __SCHOOL_ID__ ha sido creada para acceder a la información de la institución y de los siguientes estudiantes:
__ASSOCIATED_STUDENTS__

Sus datos de cuenta son:
Nombre de usuario: __USERNAME__
Contraseña: __PASSWORD__

Un enlace hacia el Sistema de Información Académica e instrucciones para el acceso están disponibles en el sitio de la institución.'
WHERE modname='Custom/NotifyParents.php';


--
-- Name: students; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

UPDATE student_field_categories
SET title='General Info|es_ES.utf8:Datos Personales'
WHERE id=1;

UPDATE student_field_categories
SET title='Medical|es_ES.utf8:Médico'
WHERE id=2;

UPDATE student_field_categories
SET title='Addresses & Contacts|es_ES.utf8:Direcciones & Contactos'
WHERE id=3;

UPDATE student_field_categories
SET title='Comments|es_ES.utf8:Comentarios'
WHERE id=4;

UPDATE student_field_categories
SET title='Food Service|es_ES.utf8:Servicio de Comida'
WHERE id=5;


--
-- Data for Name: staff_field_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE staff_field_categories
SET title='General Info|es_ES.utf8:Datos Personales'
WHERE id=1;

UPDATE staff_field_categories
SET title='Schedule|es_ES.utf8:Horario'
WHERE id=2;

UPDATE staff_field_categories
SET title='Food Service|es_ES.utf8:Servicio de Comida'
WHERE id=3;


--
-- Data for Name: custom_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE custom_fields
SET title='Gender|es_ES.utf8:Sexo', select_options='Masculino
Femenino'
WHERE id=200000000;

UPDATE custom_fields
SET title='Ethnicity|es_ES.utf8:Origen étnico', select_options='Blanco, No Hispano
Negro, No Hispano
Indio Americano o Nativo de Alaska
Asiático o de las Islas del Pacífico
Hispano
Otro'
WHERE id=200000001;

UPDATE custom_fields
SET title='Common Name|es_ES.utf8:Apodo'
WHERE id=200000002;

UPDATE custom_fields
SET title='Identification Number|es_ES.utf8:Numero de Identificación'
WHERE id=200000003;

UPDATE custom_fields
SET title='Birthdate|es_ES.utf8:Fecha de nacimiento'
WHERE id=200000004;

UPDATE custom_fields
SET title='Language|es_ES.utf8:Lenguaje', select_options='Español
Inglés'
WHERE id=200000005;

UPDATE custom_fields
SET title='Physician|es_ES.utf8:Médico'
WHERE id=200000006;

UPDATE custom_fields
SET title='Physician Phone|es_ES.utf8:Teléfono Médico'
WHERE id=200000007;

UPDATE custom_fields
SET title='Preferred Hospital|es_ES.utf8:Hospital Preferido'
WHERE id=200000008;

UPDATE custom_fields
SET title='Comments|es_ES.utf8:Comentarios'
WHERE id=200000009;

UPDATE custom_fields
SET title='Has Doctor''s Note|es_ES.utf8:Tiene una Nota del Doctor'
WHERE id=200000010;

UPDATE custom_fields
SET title='Doctor''s Note Comments|es_ES.utf8:Comentarios de la Nota del Doctor'
WHERE id=200000011;


--
-- Data for Name: staff_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE staff_fields
SET title='Email Address|es_ES.utf8:Email'
WHERE id=200000000;

UPDATE staff_fields
SET title='Phone Number|es_ES.utf8:Número de Teléfono'
WHERE id=200000001;


--
-- Data for Name: school_gradelevels; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE school_gradelevels
SET short_name='Jd', title='Jardin'
WHERE id=1;

UPDATE school_gradelevels
SET short_name='01', title='Primero'
WHERE id=2;

UPDATE school_gradelevels
SET short_name='02', title='Segundo'
WHERE id=3;

UPDATE school_gradelevels
SET short_name='03', title='Tercero'
WHERE id=4;

UPDATE school_gradelevels
SET short_name='04', title='Cuarto'
WHERE id=5;

UPDATE school_gradelevels
SET short_name='05', title='Quinto'
WHERE id=6;

UPDATE school_gradelevels
SET short_name='06', title='Sexto'
WHERE id=7;

UPDATE school_gradelevels
SET short_name='07', title='Septimo'
WHERE id=8;

UPDATE school_gradelevels
SET short_name='08', title='Octavo'
WHERE id=9;


--
-- Data for Name: students; Type: TABLE DATA; Schema: public; Owner: centrecolrosbog
--

UPDATE students
SET last_name='Estudiante', first_name='Student', custom_200000000='Masculino', custom_200000001='Hispano', custom_200000005='Español'
WHERE student_id=1;


--
-- Data for Name: staff; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE staff
SET last_name='Administrador'
WHERE staff_id=1;

UPDATE staff
SET last_name='Docente'
WHERE staff_id=2;

UPDATE staff
SET last_name='Padre'
WHERE staff_id=3;


--
-- Data for Name: attendance_codes; Type: TABLE DATA; Schema: public; Owner: rosariosis
--


UPDATE attendance_codes
SET title='Ausente', short_name='A'
WHERE id=1;

UPDATE attendance_codes
SET title='Presente', short_name='P'
WHERE id=2;

UPDATE attendance_codes
SET title='Tarde', short_name='T'
WHERE id=3;

UPDATE attendance_codes
SET title='Ausencia Justificada', short_name='AJ'
WHERE id=4;


--
-- Data for Name: discipline_field_usage; Type: TABLE DATA;
--

UPDATE discipline_field_usage
SET title='Padres contactados por el Docente'
WHERE id=1;

UPDATE discipline_field_usage
SET title='Padres contactados por el Administrador'
WHERE id=2;

UPDATE discipline_field_usage
SET title='Comentarios'
WHERE id=3;

UPDATE discipline_field_usage
SET title='Violación', select_options='Faltar a Clases
Blasfemia, vulgaridad, languaje ofensivo
Insubordinación (Desobediencia, Comportamiento Irrespetuoso)
Ebrio (Alcohol o Drogas)
Habla fuera de Turno
Acoso
Se pelea
Demostracion Publica de Afecto
Otra'
WHERE id=4;

UPDATE discipline_field_usage
SET title='Castigo Asignado', select_options='10 Minutos
20 Minutos
30 Minutos
Discutir Suspensión'
WHERE id=5;

UPDATE discipline_field_usage
SET title='Suspensiones (Oficina Solamente)', select_options='Media Jornada
Suspensión en la Escuela
1 Día
2 Días
3 Días
5 Días
7 Días
Expulsión'
WHERE id=6;


--
-- Data for Name: report_card_comments; Type: TABLE DATA; Schema: public; Owner: postgres
--

UPDATE report_card_comments
SET title='^n falla en conocer los Requerimientos de la Clase'
WHERE id=1;

UPDATE report_card_comments
SET title='^n viene a ^s Clase sin Preparar'
WHERE id=2;

UPDATE report_card_comments
SET title='^n tiene Influencia Positiva en Clase'
WHERE id=3;


--
-- Data for Name: food_service_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE food_service_categories
SET title='Elementos del Almuerzo'
WHERE category_id=1;


--
-- Data for Name: food_service_items; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE food_service_items
SET description='Almuerzo Estudiante'
WHERE item_id=1;

UPDATE food_service_items
SET description='Leche'
WHERE item_id=2;

UPDATE food_service_items
SET description='Sanduche'
WHERE item_id=3;

UPDATE food_service_items
SET description='Pizza Extra'
WHERE item_id=4;


--
-- Data for Name: food_service_menus; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE food_service_menus
SET title='Almuerzo'
WHERE menu_id=1;


--
-- Data for Name: resources; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE resources
SET title='Imprimir Manual de Usuario', link='Help.php'
WHERE id=1;

UPDATE resources
SET title='Guía de Configuración Rápida', link='https://www.rosariosis.org/es/quick-setup-guide/'
WHERE id=2;

UPDATE resources
SET title='Foro', link='https://www.rosariosis.org/forum/t/espanol'
WHERE id=3;

UPDATE resources
SET title='Contribuir', link='https://www.rosariosis.org/es/contribute/'
WHERE id=4;
