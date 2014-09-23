<?php
echo '<TABLE class="width-100p cellspacing-0 cellpadding-6">';
echo '<TR class="st"><TD style="max-width:150px;" class="valign-top">';
// IMAGE
if (AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF'])):
?>
	<script> 
	//toggle form & photo
	$('#aFormStudentPhoto').click(function () {
		$('#formStudentPhoto').toggle();
		$('#studentImg').toggle();
		return false;
	});
	</script> 
	<a href="#" id="aFormStudentPhoto"><img src="assets/plus.gif" height="9" />&nbsp;<?php echo _('Student Photo'); ?></a><br />
	<div id="formStudentPhoto" style="display:none;">
		<br />
		<input type="file" id="photo" name="photo" accept="image/*" />
		<BR /><span class="legend-gray"><?php echo _('Student Photo'); ?> (.jpg)</span>
	</div>
<?php endif;

if ($_REQUEST['student_id']!='new' && ($file = @fopen($picture_path=$StudentPicturesPath.UserSyear().'/'.UserStudentID().'.jpg','r')) || ($file = @fopen($picture_path=$StudentPicturesPath.(UserSyear()-1).'/'.UserStudentID().'.jpg','r'))):
	fclose($file);
?>
	<IMG SRC="<?php echo $picture_path.(!empty($new_photo_file)? '?cacheKiller='.rand():''); ?>" width="150" id="studentImg" />
<?php endif;
// END IMAGE

echo '</TD><TD class="valign-top">';

echo '<TABLE class="width-100p cellpadding-5"><TR class="st">';

echo '<TD>';
if(AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
//modif Francois: Moodle integrator
	if($_REQUEST['student_id']=='new' || Preferences('HIDDEN')!='Y' || $_REQUEST['moodle_create_student'])
//modif Francois: add translation
		echo '<TABLE><TR class="st"><TD>'.TextInput($student['FIRST_NAME'],'students[FIRST_NAME]',($student['FIRST_NAME']==''?'<span class="legend-red">':'')._('First Name').($student['FIRST_NAME']==''?'</span>':''),'size=12 maxlength=50 required', ($_REQUEST['moodle_create_student'] ? false : true)).'</TD><TD>'.TextInput($student['MIDDLE_NAME'],'students[MIDDLE_NAME]',_('Middle Name'),'maxlength=50').'</TD><TD>'.TextInput($student['LAST_NAME'],'students[LAST_NAME]',($student['LAST_NAME']==''?'<span class="legend-red">':'')._('Last Name').($student['LAST_NAME']==''?'</span>':''),'size=12 maxlength=50 required', ($_REQUEST['moodle_create_student'] ? false : true)).'</TD><TD>'.SelectInput($student['NAME_SUFFIX'],'students[NAME_SUFFIX]',_('Suffix'),array('Jr'=>_('Jr'),'Sr'=>_('Sr'),'II'=>_('II'),'III'=>_('III'),'IV'=>_('IV'),'V'=>_('V')),'').'</TD></TR></TABLE>';
	else
	{
		echo '<DIV id="student_name"><div class="onclick" onclick=\'addHTML("';
		
		$toEscape = '<TABLE><TR class="st"><TD>'.TextInput(str_replace("'",'&#39;',$student['FIRST_NAME']),'students[FIRST_NAME]',_('First Name'),'size=12 maxlength=50 required',false).'</TD><TD>'.TextInput(str_replace("'",'&#39;',$student['MIDDLE_NAME']),'students[MIDDLE_NAME]',_('Middle Name'),'maxlength=50',false).'</TD><TD>'.TextInput(str_replace("'",'&#39;',$student['LAST_NAME']),'students[LAST_NAME]',_('Last Name'),'size=12 maxlength=50 required',false).'</TD><TD>'.SelectInput(str_replace("'",'&#39;',$student['NAME_SUFFIX']),'students[NAME_SUFFIX]',_('Suffix'),array('Jr'=>_('Jr'),'Sr'=>_('Sr'),'II'=>_('II'),'III'=>_('III'),'IV'=>_('IV'),'V'=>_('V')),'','',false).'</TD></TR></TABLE>';
		echo str_replace('"','\"',$toEscape);
		
		echo '","student_name",true);\'><span class="underline-dots">'.$student['FIRST_NAME'].' '.$student['MIDDLE_NAME'].' '.$student['LAST_NAME'].' '.$student['NAME_SUFFIX'].'</span></div></DIV><span class="legend-gray">'._('Name').'</span>';
	}
else
	echo ($student['FIRST_NAME']!=''||$student['MIDDLE_NAME']!=''||$student['LAST_NAME']!=''||$student['NAME_SUFFIX']!=''?$student['FIRST_NAME'].' '.$student['MIDDLE_NAME'].' '.$student['LAST_NAME'].' '.$student['NAME_SUFFIX']:'-').'<BR /><span class="legend-gray">'._('Name').'</span>';
echo '</TD>';

echo '<TD>';
if($_REQUEST['student_id']=='new')
	echo TextInput('','assign_student_id',sprintf(_('%s ID'),Config('NAME')),'maxlength=10 size=10');
else
	echo NoInput(UserStudentID(),sprintf(_('%s ID'),Config('NAME')));
echo '</TD>';

echo '<TD>';
if($_REQUEST['student_id']!='new' && $student['SCHOOL_ID'])
	$school_id = $student['SCHOOL_ID'];
else
	$school_id = UserSchool();
$sql = "SELECT ID,TITLE FROM SCHOOL_GRADELEVELS WHERE SCHOOL_ID='".$school_id."' ORDER BY SORT_ORDER";
$QI = DBQuery($sql);
$grades_RET = DBGet($QI);
unset($options);
if(count($grades_RET))
{
	foreach($grades_RET as $value)
		$options[$value['ID']] = $value['TITLE'];
}
if($_REQUEST['student_id']!='new' && $student['SCHOOL_ID']!=UserSchool())
{
	$allow_edit = $_ROSARIO['allow_edit'];
	$AllowEdit = $_ROSARIO['AllowEdit'][$_REQUEST['modname']];
	$_ROSARIO['AllowEdit'][$_REQUEST['modname']] = $_ROSARIO['allow_edit'] = false;
}

if($_REQUEST['student_id']=='new')
	$student_id = 'new';
else
	$student_id = UserStudentID();

if($student_id=='new' && !VerifyDate($_REQUEST['day_values']['STUDENT_ENROLLMENT']['new']['START_DATE'].'-'.$_REQUEST['month_values']['STUDENT_ENROLLMENT']['new']['START_DATE'].'-'.$_REQUEST['year_values']['STUDENT_ENROLLMENT']['new']['START_DATE']))
	unset($student['GRADE_ID']);

echo SelectInput($student['GRADE_ID'],'values[STUDENT_ENROLLMENT]['.$student_id.'][GRADE_ID]',(!$student['GRADE_ID']?'<span class="legend-red">':'')._('Grade Level').(!$student['GRADE_ID']?'</span>':''),$options);
echo '</TD>';

if($_REQUEST['student_id']!='new' && $student['SCHOOL_ID']!=UserSchool())
{
	$_ROSARIO['allow_edit'] = $allow_edit;
	$_ROSARIO['AllowEdit'][$_REQUEST['modname']] = $AllowEdit;
}

echo '</TR><TR class="st">';

//modif Francois: Moodle integrator
//username, password required
echo '<TD>';
echo TextInput($student['USERNAME'],'students[USERNAME]',($_REQUEST['moodle_create_student'] && !$student['USERNAME']?'<span class="legend-red">':'')._('Username').($_REQUEST['moodle_create_student'] && !$student['USERNAME']?'</span>':''),($_REQUEST['moodle_create_student'] || $old_student_in_moodle ? 'required' : ''), ($_REQUEST['moodle_create_student'] ? false : true));
echo '</TD>';

echo '<TD>';
//echo TextInput($student['PASSWORD'],'students[PASSWORD]','Password');
//modif Francois: add password encryption
//echo TextInput(array($student['PASSWORD'],str_repeat('*',mb_strlen($student['PASSWORD']))),'students[PASSWORD]',($student['USERNAME']&&!$student['PASSWORD']?'<span style="color:red">':'')._('Password').($student['USERNAME']&&!$student['PASSWORD']?'</span>':''));
echo TextInput((!$student['PASSWORD'] || $_REQUEST['moodle_create_student'] ?'':str_repeat('*',8)),'students[PASSWORD]',(($student['USERNAME'] &&!$student['PASSWORD']) || $_REQUEST['moodle_create_student']?'<span class="legend-red">':'<span class="legend-gray">').($_REQUEST['moodle_create_student'] || $old_student_in_moodle?'<SPAN style="cursor:help" title="'._('The password must have at least 8 characters, at least 1 digit, at least 1 lower case letter, at least 1 upper case letter, at least 1 non-alphanumeric character').'">':'')._('Password').($_REQUEST['moodle_create_student'] || $old_student_in_moodle?'*</SPAN>':'').'</span>','autocomplete=off'.($_REQUEST['moodle_create_student'] || $old_student_in_moodle ? ' required' : ''), ($_REQUEST['moodle_create_student'] ? false : true));
echo '</TD>';

echo '<TD>';
echo NoInput(makeLogin($student['LAST_LOGIN']),_('Last Login'));
echo '</TD>';

echo '</TR></TABLE>';
echo '</TD></TR></TABLE>';

echo '<HR>';

$_REQUEST['category_id'] = '1';
include 'modules/Students/includes/Other_Info.inc.php';

if($_REQUEST['student_id']!='new' && $student['SCHOOL_ID']!=UserSchool() && $student['SCHOOL_ID'])
	$_ROSARIO['AllowEdit'][$_REQUEST['modname']] = $_ROSARIO['allow_edit'] = false;
include 'modules/Students/includes/Enrollment.inc.php';
?>