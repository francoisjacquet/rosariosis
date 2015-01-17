<?php
echo '<TABLE class="width-100p">';
echo '<TR class="st"><TD rowspan="2" class="valign-top">';
// IMAGE
if (AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF'])):
?>
	<a href="#" id="aFormStudentPhoto"><?php echo button('add', '', '', 'smaller'); ?>&nbsp;<?php echo _('Student Photo'); ?></a><br />
	<div id="formStudentPhoto" style="display:none;">
		<br />
		<input type="file" id="photo" name="photo" accept="image/*" /><img src="assets/themes/<?php echo Preferences('THEME'); ?>/spinning.gif" alt="Spinner" id="loading" style="display:none;" />
		<BR /><span class="legend-gray"><?php echo _('Student Photo'); ?> (.jpg)</span>
	</div>
	<script> 
	//toggle form & photo
	$('#aFormStudentPhoto').click(function () {
		$('#formStudentPhoto').toggle();
		$('#studentImg').toggle();
		return false;
	});
	$('form[name="student"]').submit(function () {
		if ($('#photo').val())
			$('#loading').show();
	});
	</script> 
<?php endif;

if ($_REQUEST['student_id']!='new' && ($file = @fopen($picture_path=$StudentPicturesPath.UserSyear().'/'.UserStudentID().'.jpg','r')) || ($file = @fopen($picture_path=$StudentPicturesPath.(UserSyear()-1).'/'.UserStudentID().'.jpg','r'))):
	fclose($file);
?>
	<IMG SRC="<?php echo $picture_path.(!empty($new_photo_file)? '?cacheKiller='.rand():''); ?>" id="studentImg" />
<?php endif;
// END IMAGE

echo '</TD><TD>';

if(AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
//modif Francois: Moodle integrator
	if($_REQUEST['student_id']=='new' || Preferences('HIDDEN')!='Y' || $_REQUEST['moodle_create_student'])
//modif Francois: add translation
		echo '<TABLE><TR class="st"><TD>'.TextInput($student['FIRST_NAME'],'students[FIRST_NAME]',($student['FIRST_NAME']==''?'<span class="legend-red">':'')._('First Name').($student['FIRST_NAME']==''?'</span>':''),'size=12 maxlength=50 required', ($_REQUEST['moodle_create_student'] ? false : true)).'</TD><TD>'.TextInput($student['MIDDLE_NAME'],'students[MIDDLE_NAME]',_('Middle Name'),'maxlength=50').'</TD><TD>'.TextInput($student['LAST_NAME'],'students[LAST_NAME]',($student['LAST_NAME']==''?'<span class="legend-red">':'')._('Last Name').($student['LAST_NAME']==''?'</span>':''),'size=12 maxlength=50 required', ($_REQUEST['moodle_create_student'] ? false : true)).'</TD><TD>'.SelectInput($student['NAME_SUFFIX'],'students[NAME_SUFFIX]',_('Suffix'),array('Jr'=>_('Jr'),'Sr'=>_('Sr'),'II'=>_('II'),'III'=>_('III'),'IV'=>_('IV'),'V'=>_('V')),'').'</TD></TR></TABLE>';
	else
	{
		$student_name = '<TABLE><TR class="st"><TD>'.TextInput($student['FIRST_NAME'],'students[FIRST_NAME]',_('First Name'),'size=12 maxlength=50 required',false).'</TD><TD>'.TextInput($student['MIDDLE_NAME'],'students[MIDDLE_NAME]',_('Middle Name'),'maxlength=50',false).'</TD><TD>'.TextInput($student['LAST_NAME'],'students[LAST_NAME]',_('Last Name'),'size=12 maxlength=50 required',false).'</TD><TD>'.SelectInput($student['NAME_SUFFIX'],'students[NAME_SUFFIX]',_('Suffix'),array('Jr'=>_('Jr'),'Sr'=>_('Sr'),'II'=>_('II'),'III'=>_('III'),'IV'=>_('IV'),'V'=>_('V')),'','',false).'</TD></TR></TABLE>';

		echo '<script>var student_name='.json_encode($student_name).';</script>';
		
		echo '<DIV id="student_name"><div class="onclick" onclick=\'addHTML(student_name';
		
		echo ',"student_name",true);\'><span class="underline-dots">'.$student['FIRST_NAME'].' '.$student['MIDDLE_NAME'].' '.$student['LAST_NAME'].' '.$student['NAME_SUFFIX'].'</span></div></DIV><span class="legend-gray">'._('Name').'</span>';
	}
else
	echo ($student['FIRST_NAME']!=''||$student['MIDDLE_NAME']!=''||$student['LAST_NAME']!=''||$student['NAME_SUFFIX']!=''?$student['FIRST_NAME'].' '.$student['MIDDLE_NAME'].' '.$student['LAST_NAME'].' '.$student['NAME_SUFFIX']:'-').'<BR /><span class="legend-gray">'._('Name').'</span>';

echo '</TD><TD colspan="2">';

if($_REQUEST['student_id']=='new')
	echo TextInput('','assign_student_id',sprintf(_('%s ID'),Config('NAME')),'maxlength=10 size=10');
else
	echo NoInput(UserStudentID(),sprintf(_('%s ID'),Config('NAME')));
echo '</TD>';

echo '</TR><TR class="st"><TD>';

//modif Francois: Moodle integrator
//username, password required

$required = $_REQUEST['moodle_create_student'] || $old_student_in_moodle || basename($_SERVER['PHP_SELF'])=='index.php';
$legend_red = $required && !$student['USERNAME'];

echo TextInput($student['USERNAME'],'students[USERNAME]',($legend_red ? '<span class="legend-red">':'')._('Username').(($_REQUEST['moodle_create_student'] || $old_student_in_moodle) && !$student['USERNAME']?'</span>':''),($required ? 'required' : ''), ($_REQUEST['moodle_create_student'] ? false : true));

echo '</TD><TD>';

$required = $required;
$legend_red = $required && !$student['PASSWORD'];

echo TextInput((!$student['PASSWORD'] || $_REQUEST['moodle_create_student'] ?'':str_repeat('*',8)),'students[PASSWORD]',($legend_red ? '<span class="legend-red">':'<span class="legend-gray">').($_REQUEST['moodle_create_student'] || $old_student_in_moodle?'<SPAN style="cursor:help" title="'._('The password must have at least 8 characters, at least 1 digit, at least 1 lower case letter, at least 1 upper case letter, at least 1 non-alphanumeric character').'">':'')._('Password').($_REQUEST['moodle_create_student'] || $old_student_in_moodle?'*</SPAN>':'').'</span>','autocomplete=off'.($required ? ' required' : ''), ($_REQUEST['moodle_create_student'] ? false : true));

echo '</TD><TD>';

echo NoInput(makeLogin($student['LAST_LOGIN']),_('Last Login'));

echo '</TD></TR></TABLE><HR />';


$_REQUEST['category_id'] = '1';
include 'modules/Students/includes/Other_Info.inc.php';

if($_REQUEST['student_id']!='new' && $student['SCHOOL_ID']!=UserSchool() && $student['SCHOOL_ID'])
	$_ROSARIO['AllowEdit'][$_REQUEST['modname']] = $_ROSARIO['allow_edit'] = false;

if(basename($_SERVER['PHP_SELF'])!='index.php')
	include 'modules/Students/includes/Enrollment.inc.php';
//modif Francois: create account
else
{
	$schools_RET = DBGet(DBQuery("SELECT ID, TITLE FROM SCHOOLS WHERE SYEAR='".UserSyear()."' ORDER BY ID"));

	$school_options = array();

	foreach($schools_RET as $school)
	{
		$school_options[$school['ID']] = $school['TITLE'];
	}

	//add School select input
	echo SelectInput('','enrollment[SCHOOL_ID]',_('School'),$school_options,false);

	if ($PopTable_opened)
		PopTable('footer');
}
?>
