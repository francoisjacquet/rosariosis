
/**********************************************************
 delete.sql file
 Required if install.sql file present
 - Delete profile exceptions
 - Delete program config options if any (to every schools)
 - Delete module specific tables 
 (and their eventual sequences & indexes) if any
***********************************************************/

--
-- Delete profile exceptions
--

DELETE FROM profile_exceptions WHERE modname='Example/ExampleWidget.php';
DELETE FROM profile_exceptions WHERE modname='Example/Setup.php';
DELETE FROM profile_exceptions WHERE modname='Example/Resources.php';
DELETE FROM profile_exceptions WHERE modname='Example/ExampleResource.php';


--
-- Delete program config options
--


DELETE FROM program_config WHERE program='example';

