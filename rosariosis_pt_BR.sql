--
-- PostgreSQL & MySQL data update
--
-- Translates database fields to Portuguese (Brazil) by Emerson Barros
--
-- Note: Uncheck "Paginate results" when importing with phpPgAdmin
--

--
-- Data for Name: schools; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE schools
SET title='Exemplo de Instituição', address='Rua 16', city='São Paulo', state=NULL, zipcode='28037111', principal='Sr. Diretor', www_address='www.rosariosis.org', reporting_gp_scale=5
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
SET config_value='Rosario Student Information System|pt_BR.utf8:Sistema de informações do aluno de Rosario'
WHERE title='TITLE';

--
-- Data for Name: student_enrollment_codes; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE student_enrollment_codes
SET title='Local alterado', short_name='MUDAR'
WHERE id=1;

UPDATE student_enrollment_codes
SET title='Expulso', short_name='EXP'
WHERE id=2;

UPDATE student_enrollment_codes
SET title='Início do Ano', short_name='ANO'
WHERE id=3;

UPDATE student_enrollment_codes
SET title='Outro local', short_name='OUTRO'
WHERE id=4;

UPDATE student_enrollment_codes
SET title='Transferido', short_name='TRAN'
WHERE id=5;

UPDATE student_enrollment_codes
SET title='Transferido', short_name='MAO'
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
SET title='Ano Completo', short_name='Ano'
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
SET title='Dia completo', short_name='DIA'
WHERE period_id=1;

UPDATE school_periods
SET title='Manhã', short_name='AM'
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
<div style="text-align: center;"><span style="font-size: xx-large;"><strong>__SCHOOL_ID__</strong><br /></span><br /><span style="font-size: xx-large;">N&oacute;s, por meio deste, reconhecemos<br /><br /></span></div>
<div style="text-align: center;"><span style="font-size: xx-large;"><strong>__FIRST_NAME__ __LAST_NAME__</strong><br /><br /></span></div>
<div style="text-align: center;"><span style="font-size: xx-large;">Quem completou todos os requisitos acad&ecirc;micos para o <br />Quadro de Honra</span></div>'
WHERE modname='Grades/HonorRoll.php';

UPDATE templates
SET template='<div style="text-align: center;">__CLIPART__<br /><br /><strong><span style="font-size: xx-large;">__SCHOOL_ID__<br /></span></strong><br /><span style="font-size: xx-large;">Levando em conta que<br /><br /></span></div>
<div style="text-align: center;"><strong><span style="font-size: xx-large;">__FIRST_NAME__ __LAST_NAME__<br /><br /></span></strong></div>
<div style="text-align: center;"><span style="font-size: xx-large;">Ele foi premiado com Excel&ecirc;ncia Acad&ecirc;mica em<br />__SUBJECT__</span></div>'
WHERE modname='Grades/HonorRollSubject.php';

UPDATE templates
SET template='<h2 style="text-align: center;">Certificado de Estudos</h2>
<p>O Reitor e o Secret&aacute;rio abaixo assinados certificam:</p>
<p>Que __FIRST_NAME__ __LAST_NAME__ identificado com DI __SSSECURITY__ estudou nesta escola para a s&eacute;rie __GRADE_ID__ durante o ano __YEAR__ com as notas e intensidade de horas detalhadas abaixo.</p>
<p>O aluno &eacute; promovido &agrave; s&eacute;rie __NEXT_GRADE_ID__.</p>
<p>__BLOCK2__</p>
<p>&nbsp;</p>
<table style="border-collapse: collapse; width: 100%;" border="0" cellpadding="10"><tbody><tr>
<td style="width: 50%; text-align: center;"><hr />
<p>Assinatura</p>
<p>&nbsp;</p><hr />
<p>Título</p></td>
<td style="width: 50%; text-align: center;"><hr />
<p>Assinatura</p>
<p>&nbsp;</p><hr />
<p>Título</p></td></tr></tbody></table>'
WHERE modname='Notas/Transcripts.php';

UPDATE templates
SET template='Caro __PARENT_NAME__,

Uma conta de pai ou respons&aacute;vel para __SCHOOL_ID__ foi criada para acessar as informa&ccedil;&otilde;es da institui&ccedil;&otilde;o e dos seguintes alunos:
__ASSOCIADOS_ESTUDANTES__

Os dados da sua conta s&atilde;o:
Name de usu&aacute;rio: __USERNAME__
Senha: __PASSWORD__

Um link para o Sistema de Informações Académicas e instruções para acesso estão disponíveis no site da instituição.__BLOCK2__Caro __PARENT_NAME__,

Os seguintes alunos foram adicionados &agrave; conta dos pais ou respons&aacute;vel no Sistema de Informa&ccedil;&otilde;es Acad&ecirc;micas:
__ASSOCIADOS_ESTUDANTES__'
WHERE modname='Custom/CreateParents.php';

UPDATE templates
SET template='Caro __PARENT_NAME__,

Uma conta de pai ou respons&aacute;vel para __SCHOOL_ID__ foi criada para acessar as informa&ccedil;&otilde;es da institui&ccedil;&atilde;o e dos seguintes alunos:
__ASSOCIADOS_ESTUDANTES__

Os dados da sua conta s&atilde; o:
Name de usu&aacute;rio: __USERNAME__
Senha: __PASSWORD__

O link para o Sistema de Informação Académica e as instruções de acesso estão disponíveis no site da instituição.'
WHERE modname='Custom/NotifyParents.php';


--
-- Name: students; Type: TABELA; Schema: public; Owner: rosariosis; Tablespace:
--

UPDATE student_field_categories
SET title='General Info|pt_BR.utf8:Dados pessoais'
WHERE id=1;

UPDATE student_field_categories
SET title='Medical|pt_BR.utf8:Médico'
WHERE id=2;

UPDATE student_field_categories
SET title='Addresses & Contacts|pt_BR.utf8:Endereços & contatos'
WHERE id=3;

UPDATE student_field_categories
SET title='Comments|pt_BR.utf8:Comentários'
WHERE id=4;

UPDATE student_field_categories
SET title='Food Service|pt_BR.utf8:Serviço de alimentação'
WHERE id=5;


--
-- Data for Name: staff_field_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE staff_field_categories
SET title='General Info|pt_BR.utf8:Dados pessoais'
WHERE id=1;

UPDATE staff_field_categories
SET title='Schedule|pt_BR.utf8:Horário'
WHERE id=2;

UPDATE staff_field_categories
SET title='Food Service|pt_BR.utf8:Serviço de alimentação'
WHERE id=3;


--
-- Data for Name: custom_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE custom_fields
SET title='Gender|pt_BR.utf8:Sexo', select_options='Masculino
Feminino'
WHERE id=200000000;

UPDATE custom_fields
SET title='Ethnicity|pt_BR.utf8:Etnia', select_options='Branca, Não Hispânica
Negro, não hispânico
Índio Americano ou Nativo do Alasca
Asiático ou ilhéu do Pacífico
Hispânico
Outro'
WHERE id=200000001;

UPDATE custom_fields
SET title='Common Name|pt_BR.utf8:Apelido'
WHERE id=200000002;

UPDATE custom_fields
SET title='Identification Number|pt_BR.utf8:Número de Identificação'
WHERE id=200000003;

UPDATE custom_fields
SET title='Birthdate|pt_BR.utf8:Data de nascimento'
WHERE id=200000004;

UPDATE custom_fields
SET title='Language|pt_BR.utf8:Idioma', select_options='Espanhol
Inglês
Português'
WHERE id=200000005;

UPDATE custom_fields
SET title='Physician|pt_BR.utf8:Médico'
WHERE id=200000006;

UPDATE custom_fields
SET title='Physician Phone|pt_BR.utf8:Telefone do médico'
WHERE id=200000007;

UPDATE custom_fields
SET title='Preferred Hospital|pt_BR.utf8:Hospital preferido'
WHERE id=200000008;

UPDATE custom_fields
SET title='Comments|pt_BR.utf8:Comentários'
WHERE id=200000009;

UPDATE custom_fields
SET title='Has Doctor''s Note|pt_BR.utf8:Tem atestado médico'
WHERE id=200000010;

UPDATE custom_fields
SET title='Doctor''s Note Comments|pt_BR.utf8:Comentários da nota médica'
WHERE id=200000011;


--
-- Data for Name: staff_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE staff_fields
SET title='Email Address|pt_BR.utf8:E-mail'
WHERE id=200000000;

UPDATE staff_fields
SET title='Phone Number|pt_BR.utf8:Número de telefone'
WHERE id=200000001;


--
-- Data for Name: school_gradelevels; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE school_gradelevels
SET short_name='Jd', title='Jardim'
WHERE id=1;

UPDATE school_gradelevels
SET short_name='01', title='Primeiro'
WHERE id=2;

UPDATE school_gradelevels
SET short_name='02', title='Segundo'
WHERE id=3;

UPDATE school_gradelevels
SET short_name='03', title='Terceiro'
WHERE id=4;

UPDATE school_gradelevels
SET short_name='04', title='Quarto'
WHERE id=5;

UPDATE school_gradelevels
SET short_name='05', title='Quinto'
WHERE id=6;

UPDATE school_gradelevels
SET short_name='06', title='Sexto'
WHERE id=7;

UPDATE school_gradelevels
SET short_name='07', title='Sétimo'
WHERE id=8;

UPDATE school_gradelevels
SET short_name='08', title='Oitavo'
WHERE id=9;


--
-- Data for Name: students; Type: TABLE DATA; Schema: public; Owner: centrecolrosbog
--

UPDATE students
SET last_name='Estudante', first_name='Estudante', custom_200000000='Masculino', custom_200000001='Hispânico', custom_200000005='Português'
WHERE student_id=1;


--
-- Data for Name: staff; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE staff
SET last_name='Administrador'
WHERE staff_id=1;

UPDATE staff
SET last_name='Professor'
WHERE staff_id=2;

UPDATE staff
SET last_name='Pai'
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
SET title='Ausência Justificada', short_name='AJ'
WHERE id=4;


--
-- Data for Name: discipline_field_usage; Type: TABLE DATA;
--

UPDATE discipline_field_usage
SET title='Pais ou responsáveis contatados pelo Professor'
WHERE id=1;

UPDATE discipline_field_usage
SET title='Pais ou responsáveis contatados pelo Administrador'
WHERE id=2;

UPDATE discipline_field_usage
SET title='Comentários'
WHERE id=3;

UPDATE discipline_field_usage
SET title='Violação', select_options='Faltar à aula
Palavrões, vulgaridade, linguagem ofensiva
Insubordinação (desobediência, comportamento desrespeitoso)
Bêbado (álcool ou drogas)
Fale fora de hora
Assédio
Lutas
Atentado ao pudor
Outro'
WHERE id=4;

UPDATE discipline_field_usage
SET title='Punição atribuída', select_options='10 Minutos
20 minutos
30 minutos
Discutir Suspensão'
WHERE id=5;

UPDATE discipline_field_usage
SET title='Suspensões (somente secretaria)', select_options='Tempo parcial
Suspensão na escola
1 dia
2 dias
3 dias
5 dias
7 dias
Expulsão'
WHERE id=6;


--
-- Data for Name: report_card_comments; Type: TABLE DATA; Schema: public; Owner: postgres
--

UPDATE report_card_comments
SET title='^n não atende aos requisitos de classe'
WHERE id=1;

UPDATE report_card_comments
SET title='^n não faz o dever de casa'
WHERE id=2;

UPDATE report_card_comments
SET title='^n tem influência positiva na aula'
WHERE id=3;


--
-- Data for Name: food_service_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE food_service_categories
SET title='Itens de almoço'
WHERE category_id=1;


--
-- Data for Name: food_service_items; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE food_service_items
SET description='Almoço dos estudantes'
WHERE item_id=1;

UPDATE food_service_items
SET description='Leite'
WHERE item_id=2;

UPDATE food_service_items
SET description='Sanduche'
WHERE item_id=3;

UPDATE food_service_items
SET description='Pizza extra'
WHERE item_id=4;


--
-- Data for Name: food_service_menus; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE food_service_menus
SET title='Almoço'
WHERE menu_id=1;


--
-- Data for Name: resources; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE resources
SET title='Imprimir manual do usuário', link='Help.php'
WHERE id=1;

UPDATE resources
SET title='Guia de configuração rápida', link='https://www.rosariosis.org/quick-setup-guide/'
WHERE id=2;

UPDATE resources
SET title='Fórum', link='https://www.rosariosis.org/forum/'
WHERE id=3;

UPDATE resources
SET title='Contribuir', link='https://www.rosariosis.org/contribute/'
WHERE id=4;
