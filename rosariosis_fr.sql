--
-- PostgreSQL & MySQL data update
--
-- Translates database fields to French
--
-- Note: Uncheck "Paginate results" when importing with phpPgAdmin
--

--
-- Data for Name: schools; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE schools
SET title='École exemple', address='13 rue Jules Ferry', city='Paris', state=NULL, zipcode='75001', principal='M. Principal', www_address='www.rosariosis.org/fr', reporting_gp_scale=10
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
SET config_value='Rosario Student Information System|fr_FR.utf8:Logiciel de gestion scolaire Rosario'
WHERE title='TITLE';

UPDATE config
SET config_value=','
WHERE title='DECIMAL_SEPARATOR';

UPDATE config
SET config_value='&nbsp;'
WHERE title='THOUSANDS_SEPARATOR';


--
-- Data for Name: student_enrollment_codes; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE student_enrollment_codes
SET title='Départ', short_name='DEP'
WHERE id=1;

UPDATE student_enrollment_codes
SET title='Expulsé', short_name='EXP'
WHERE id=2;

UPDATE student_enrollment_codes
SET title='Début d''année', short_name='DEB'
WHERE id=3;

UPDATE student_enrollment_codes
SET title='Autre district', short_name='AUTR'
WHERE id=4;

UPDATE student_enrollment_codes
SET title='Transfert', short_name='TRAN'
WHERE id=5;

UPDATE student_enrollment_codes
SET title='Transfert', short_name='MAN'
WHERE id=6;


--
-- Data for Name: report_card_grade_scales; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE report_card_grade_scales
SET title='Principale', gp_scale=10, gp_passing_value=5
WHERE id=1;


--
-- Data for Name: report_card_grades; Type: TABLE DATA; Schema: public; Owner: postgres
--


UPDATE report_card_grades
SET title='10.0', gpa_value=10.0, break_off=100, comment='Très bien'
WHERE id=1;

UPDATE report_card_grades
SET title='9.5', gpa_value=9.5, break_off=95, comment='Très bien'
WHERE id=2;

UPDATE report_card_grades
SET title='9.0', gpa_value=9.0, break_off=90, comment='Très bien'
WHERE id=3;

UPDATE report_card_grades
SET title='8.5', gpa_value=8.5, break_off=85, comment='Très bien'
WHERE id=4;

UPDATE report_card_grades
SET title='8.0', gpa_value=8.0, break_off=80, comment='Bien'
WHERE id=5;

UPDATE report_card_grades
SET title='7.5', gpa_value=7.5, break_off=75, comment='Bien'
WHERE id=6;

UPDATE report_card_grades
SET title='7.0', gpa_value=7.0, break_off=70, comment='Bien'
WHERE id=7;

UPDATE report_card_grades
SET title='6.5', gpa_value=6.5, break_off=65, comment='Assez bien'
WHERE id=8;

UPDATE report_card_grades
SET title='6.0', gpa_value=6.0, break_off=60, comment='Assez bien'
WHERE id=9;

UPDATE report_card_grades
SET title='5.5', gpa_value=5.5, break_off=55, comment='Passable'
WHERE id=10;

UPDATE report_card_grades
SET title='5.0', gpa_value=5.0, break_off=50, comment='Passable'
WHERE id=11;

UPDATE report_card_grades
SET title='4.5', gpa_value=4.5, break_off=45, comment='Médiocre'
WHERE id=12;

UPDATE report_card_grades
SET title='4.0', gpa_value=4.0, break_off=40, comment='Médiocre'
WHERE id=13;

UPDATE report_card_grades
SET title='3.5', gpa_value=3.5, break_off=35, comment='Médiocre'
WHERE id=14;

UPDATE report_card_grades
SET title='3.0', gpa_value=3.0, break_off=30, comment='Médiocre'
WHERE id=15;

INSERT INTO report_card_grades (syear, school_id, title, sort_order, gpa_value, break_off, comment, grade_scale_id, unweighted_gp)
VALUES ((SELECT syear FROM schools WHERE id=1 LIMIT 1), 1, '2.5', 16, 2.5, 25, 'Insuffisant', 1, NULL);

INSERT INTO report_card_grades (syear, school_id, title, sort_order, gpa_value, break_off, comment, grade_scale_id, unweighted_gp)
VALUES ((SELECT syear FROM schools WHERE id=1 LIMIT 1), 1, '2.0', 17, 2.0, 20, 'Insuffisant', 1, NULL);

INSERT INTO report_card_grades (syear, school_id, title, sort_order, gpa_value, break_off, comment, grade_scale_id, unweighted_gp)
VALUES ((SELECT syear FROM schools WHERE id=1 LIMIT 1), 1, '1.5', 18, 1.5, 15, 'Insuffisant', 1, NULL);

INSERT INTO report_card_grades (syear, school_id, title, sort_order, gpa_value, break_off, comment, grade_scale_id, unweighted_gp)
VALUES ((SELECT syear FROM schools WHERE id=1 LIMIT 1), 1, '1.0', 19, 1.0, 10, 'Insuffisant', 1, NULL);

INSERT INTO report_card_grades (syear, school_id, title, sort_order, gpa_value, break_off, comment, grade_scale_id, unweighted_gp)
VALUES ((SELECT syear FROM schools WHERE id=1 LIMIT 1), 1, '0.5', 20, 0.5, 5, 'Insuffisant', 1, NULL);

INSERT INTO report_card_grades (syear, school_id, title, sort_order, gpa_value, break_off, comment, grade_scale_id, unweighted_gp)
VALUES ((SELECT syear FROM schools WHERE id=1 LIMIT 1), 1, '0.0', 21, 0.0, 0, 'Insuffisant', 1, NULL);

INSERT INTO report_card_grades (syear, school_id, title, sort_order, gpa_value, break_off, comment, grade_scale_id, unweighted_gp)
VALUES ((SELECT syear FROM schools WHERE id=1 LIMIT 1), 1, 'I', 22, 0.0, 0, 'Incomplet', 1, NULL);

INSERT INTO report_card_grades (syear, school_id, title, sort_order, gpa_value, break_off, comment, grade_scale_id, unweighted_gp)
VALUES ((SELECT syear FROM schools WHERE id=1 LIMIT 1), 1, 'N/D', 23, NULL, NULL, NULL, 1, NULL);


--
-- Data for Name: school_marking_periods; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE school_marking_periods
SET title='Année Complète', short_name='Année'
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
SET title='Journée complète', short_name='JOUR'
WHERE period_id=1;

UPDATE school_periods
SET title='Matin', short_name='AM'
WHERE period_id=2;

UPDATE school_periods
SET title='Après-midi', short_name='PM'
WHERE period_id=3;

UPDATE school_periods
SET title='Heure 1', short_name='01'
WHERE period_id=4;

UPDATE school_periods
SET title='Heure 2', short_name='02'
WHERE period_id=5;

UPDATE school_periods
SET title='Heure 3', short_name='03'
WHERE period_id=6;

UPDATE school_periods
SET title='Heure 4', short_name='04'
WHERE period_id=7;

UPDATE school_periods
SET title='Heure 5', short_name='05'
WHERE period_id=8;

UPDATE school_periods
SET title='Heure 6', short_name='06'
WHERE period_id=9;

UPDATE school_periods
SET title='Heure 7', short_name='07'
WHERE period_id=10;

UPDATE school_periods
SET title='Heure 8', short_name='08'
WHERE period_id=11;


--
-- Data for Name: templates; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE templates
SET template='<br /><br /><br />
<div style="text-align: center;"><span style="font-size: xx-large;"><strong>__SCHOOL_ID__</strong><br /></span><br /><span style="font-size: xx-large;">Nous reconnaissons par la présente<br /><br /></span></div>
<div style="text-align: center;"><span style="font-size: xx-large;"><strong>__FIRST_NAME__ __LAST_NAME__</strong><br /><br /></span></div>
<div style="text-align: center;"><span style="font-size: xx-large;">Qui a obtenu les <br />mentions</span></div>'
WHERE modname='Grades/HonorRoll.php';

UPDATE templates
SET template='<div style="text-align: center;">__CLIPART__<br /><br /><strong><span style="font-size: xx-large;">__SCHOOL_ID__<br /></span></strong><br /><span style="font-size: xx-large;">Nous reconnaissons par la présente<br /><br /></span></div>
<div style="text-align: center;"><strong><span style="font-size: xx-large;">__FIRST_NAME__ __LAST_NAME__<br /><br /></span></strong></div>
<div style="text-align: center;"><span style="font-size: xx-large;">Qui a obtenu les mentions pour<br />__SUBJECT__</span></div>'
WHERE modname='Grades/HonorRollSubject.php';

UPDATE templates
SET template='<h2 style="text-align: center;">Certificat d''Études</h2>
<p>Le Recteur et le Secrétariat certifient:</p>
<p>Que __FIRST_NAME__ __LAST_NAME__ identifié avec le numéro __SSECURITY__ a suivi les études dans cet établissement correspondant au niveau __GRADE_ID__ pour l''année __YEAR__ et a obtenu les notes ici mentionnées.</p>
<p>L''Élève est promu au niveau __NEXT_GRADE_ID__.</p>
<p>__BLOCK2__</p>
<p>&nbsp;</p>
<table style="border-collapse: collapse; width: 100%;" border="0" cellpadding="10"><tbody><tr>
<td style="width: 50%; text-align: center;"><hr />
<p>Signature</p>
<p>&nbsp;</p><hr />
<p>Titre</p></td>
<td style="width: 50%; text-align: center;"><hr />
<p>Signature</p>
<p>&nbsp;</p><hr />
<p>Titre</p></td></tr></tbody></table>'
WHERE modname='Grades/Transcripts.php';

UPDATE templates
SET template='Cher __PARENT_NAME__,

Un compte Parent pour l''école __SCHOOL_ID__ a été créé pour accéder aux informations de l''école et des élèves suivants :
__ASSOCIATED_STUDENTS__

Vos identifiants :
Nom d''utilisateur : __USERNAME__
Mot de passe : __PASSWORD__

Un lien vers le site du logiciel de gestion scolaire et les instructions pour y accéder sont disponibles sur le site de l''école.__BLOCK2__Cher __PARENT_NAME__,

Les élèves suivants ont été associé à votre compte parent dans le logiciel de gestion scolaire:
__ASSOCIATED_STUDENTS__'
WHERE modname='Custom/CreateParents.php';

UPDATE templates
SET template='Cher __PARENT_NAME__,

Un compte Parent pour l''école __SCHOOL_ID__ a été créé pour accéder aux informations de l''école et des élèves suivants :
__ASSOCIATED_STUDENTS__

Vos identifiants :
Nom d''utilisateur : __USERNAME__
Mot de passe : __PASSWORD__

Un lien vers le site du logiciel de gestion scolaire et les instructions pour y accéder sont disponibles sur le site de l''école.'
WHERE modname='Custom/NotifyParents.php';


--
-- Name: students; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace:
--

UPDATE student_field_categories
SET title='General Info|fr_FR.utf8:Infos générales'
WHERE id=1;

UPDATE student_field_categories
SET title='Medical|fr_FR.utf8:Médical'
WHERE id=2;

UPDATE student_field_categories
SET title='Addresses & Contacts|fr_FR.utf8:Adresses et contacts'
WHERE id=3;

UPDATE student_field_categories
SET title='Comments|fr_FR.utf8:Commentaires'
WHERE id=4;

UPDATE student_field_categories
SET title='Food Service|fr_FR.utf8:Cantine'
WHERE id=5;


--
-- Data for Name: staff_field_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE staff_field_categories
SET title='General Info|fr_FR.utf8:Infos générales'
WHERE id=1;

UPDATE staff_field_categories
SET title='Schedule|fr_FR.utf8:Emploi du temps'
WHERE id=2;

UPDATE staff_field_categories
SET title='Food Service|fr_FR.utf8:Cantine'
WHERE id=3;


--
-- Data for Name: custom_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE custom_fields
SET title='Gender|fr_FR.utf8:Sexe', select_options='Masculin
Féminin'
WHERE id=200000000;

UPDATE custom_fields
SET title='Ethnicity|fr_FR.utf8:Origine ethnique', select_options='Blanc, non hispanique
Noir, non hispanique
Asiatique
Hispanique
Autre'
WHERE id=200000001;

UPDATE custom_fields
SET title='Common Name|fr_FR.utf8:Surnom'
WHERE id=200000002;

UPDATE custom_fields
SET title='Identification Number|fr_FR.utf8:Numéro d''identification'
WHERE id=200000003;

UPDATE custom_fields
SET title='Birthdate|fr_FR.utf8:Date de naissance'
WHERE id=200000004;

UPDATE custom_fields
SET title='Language|fr_FR.utf8:Langue', select_options='Français
Anglais'
WHERE id=200000005;

UPDATE custom_fields
SET title='Physician|fr_FR.utf8:Médecin'
WHERE id=200000006;

UPDATE custom_fields
SET title='Physician Phone|fr_FR.utf8:Téléphone médecin'
WHERE id=200000007;

UPDATE custom_fields
SET title='Preferred Hospital|fr_FR.utf8:Hôpital préféré'
WHERE id=200000008;

UPDATE custom_fields
SET title='Comments|fr_FR.utf8:Commentaires'
WHERE id=200000009;

UPDATE custom_fields
SET title='Has Doctor''s Note|fr_FR.utf8:A un mot du docteur'
WHERE id=200000010;

UPDATE custom_fields
SET title='Doctor''s Note Comments|fr_FR.utf8:Commentaires du mot du docteur'
WHERE id=200000011;


--
-- Data for Name: staff_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE staff_fields
SET title='Email Address|fr_FR.utf8:Adresse email'
WHERE id=200000000;

UPDATE staff_fields
SET title='Phone Number|fr_FR.utf8:Numéro de téléphone'
WHERE id=200000001;


--
-- Data for Name: school_gradelevels; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE school_gradelevels
SET short_name='CP', title='Cours Primaire'
WHERE id=1;

UPDATE school_gradelevels
SET short_name='CE1', title='Cours Élémentaire 1'
WHERE id=2;

UPDATE school_gradelevels
SET short_name='CE2', title='Cours Élémentaire 2'
WHERE id=3;

UPDATE school_gradelevels
SET short_name='CM1', title='Cours Moyen 1'
WHERE id=4;

UPDATE school_gradelevels
SET short_name='CM2', title='Cours Moyen 2'
WHERE id=5;

UPDATE school_gradelevels
SET short_name='6e', title='Sixième'
WHERE id=6;

UPDATE school_gradelevels
SET short_name='5e', title='Cinquième'
WHERE id=7;

UPDATE school_gradelevels
SET short_name='4e', title='Quatrième'
WHERE id=8;

UPDATE school_gradelevels
SET short_name='3e', title='Troisième'
WHERE id=9;


--
-- Data for Name: students; Type: TABLE DATA; Schema: public; Owner: centrecolrosbog
--

UPDATE students
SET last_name='Élève', first_name='Student', custom_200000000='Masculin', custom_200000001='Hispanique', custom_200000005='Français'
WHERE student_id=1;


--
-- Data for Name: staff; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE staff
SET last_name='Administrateur'
WHERE staff_id=1;

UPDATE staff
SET last_name='Enseignant'
WHERE staff_id=2;

UPDATE staff
SET last_name='Parent'
WHERE staff_id=3;


--
-- Data for Name: attendance_codes; Type: TABLE DATA; Schema: public; Owner: rosariosis
--


UPDATE attendance_codes
SET title='Absent', short_name='A'
WHERE id=1;

UPDATE attendance_codes
SET title='Présent', short_name='P'
WHERE id=2;

UPDATE attendance_codes
SET title='Retard', short_name='R'
WHERE id=3;

UPDATE attendance_codes
SET title='Absence justifiée', short_name='AJ'
WHERE id=4;


--
-- Data for Name: discipline_field_usage; Type: TABLE DATA;
--

UPDATE discipline_field_usage
SET title='Parents contactés par l''enseignant'
WHERE id=1;

UPDATE discipline_field_usage
SET title='Parents contactés par l''administrateur'
WHERE id=2;

UPDATE discipline_field_usage
SET title='Commentaires'
WHERE id=3;

UPDATE discipline_field_usage
SET title='Violation', select_options='Absent du cours
Injures, vulgarité, language offensif
Insubordination (désobéissance, comportement irrespectueux)
Ivre (alcool ou drogues)
Parle sans avoir la parole
Harcèlement
Se bat
Autre'
WHERE id=4;

UPDATE discipline_field_usage
SET title='Sanction', select_options='10 Minutes
20 Minutes
30 Minutes
Exclusion envisagée'
WHERE id=5;

UPDATE discipline_field_usage
SET title='Exclusions (secrétariat)', select_options='Demi-journée
Retenue à l''école
1 Jour
2 Jours
3 Jours
5 Jours
7 Jours
Expulsion'
WHERE id=6;


--
-- Data for Name: report_card_comments; Type: TABLE DATA; Schema: public; Owner: postgres
--

UPDATE report_card_comments
SET title='^n n''apprend pas ses leçons'
WHERE id=1;

UPDATE report_card_comments
SET title='^n ne fait pas ses devoirs'
WHERE id=2;

UPDATE report_card_comments
SET title='^n a une influence positive'
WHERE id=3;


--
-- Data for Name: food_service_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE food_service_categories
SET title='Éléments du repas'
WHERE category_id=1;


--
-- Data for Name: food_service_items; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE food_service_items
SET description='Repas élève'
WHERE item_id=1;

UPDATE food_service_items
SET description='Lait'
WHERE item_id=2;

UPDATE food_service_items
SET description='Sandwich'
WHERE item_id=3;

UPDATE food_service_items
SET description='Pizza extra'
WHERE item_id=4;


--
-- Data for Name: food_service_menus; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE food_service_menus
SET title='Repas'
WHERE menu_id=1;


--
-- Data for Name: resources; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

UPDATE resources
SET title='Imprimer manuel utilisateur', link='Help.php'
WHERE id=1;

UPDATE resources
SET title='Guide de configuration rapide', link='https://www.rosariosis.org/fr/quick-setup-guide/'
WHERE id=2;

UPDATE resources
SET title='Forum', link='https://www.rosariosis.org/forum/t/francais'
WHERE id=3;

UPDATE resources
SET title='Contribuer', link='https://www.rosariosis.org/fr/contribute/'
WHERE id=4;
