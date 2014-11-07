
/**********************************************************************
 install.sql file
 Optional if the module only overrides other modules programs
 Required if the module adds programs to other modules
 Required if the module has menu entries
 - Add profile exceptions for the module to appear in the menu
 - Add program config options if any (to every schools)
 - Add module specific tables (and their eventual sequences & indexes) 
 if any: see rosariosis.sql file for examples
***********************************************************************/

/*******************************************************
 profile_id:
 	- 0: student
 	- 1: admin
 	- 2: teacher
 	- 3: parent
 modname: should match the Menu.php entries
 can_use: 'Y'
 can_edit: 'Y' or null (generally null for non admins)
*******************************************************/
--
-- Data for Name: profile_exceptions; Type: TABLE DATA; 
--

INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
1, 'Example/Resources.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
1, 'Example/ExampleResource.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
1, 'Example/ExampleWidget.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
1, 'Example/Setup.php', 'Y', 'Y');
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
0, 'Example/ExampleWidget.php', 'Y', null);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
2, 'Example/ExampleWidget.php', 'Y', null);
INSERT INTO profile_exceptions (profile_id, modname, can_use, can_edit) VALUES (
3, 'Example/ExampleWidget.php', 'Y', null);



/*********************************************************
 syear: school year (school may have various years in DB)
 school_id: may exists various schools in DB
 program: convention is module name, for ex.: 'example'
 title: for ex.: 'EXAMPLE_[your_program_config]'
 value: string
**********************************************************/
--
-- Data for Name: program_config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--


INSERT INTO program_config (syear, school_id, program, title, value) 
	SELECT sch.syear, sch.id, 'example', 'EXAMPLE_CONFIG', '5'
	FROM schools sch;

