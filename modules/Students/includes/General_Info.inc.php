<?php
echo '<TABLE class="width-100p cellspacing-0 cellpadding-6">';
echo '<TR class="st">';
// IMAGE
//modif Francois: student photo upload using jQuery form
if($_REQUEST['student_id']!='new' && $StudentPicturesPath) {
	if (AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF'])) {
?>
	<script type='text/javascript'> 
	//move form inside the main student form!
	$('#formStudentPhoto').appendTo('#divFormStudentPhoto');
	//toggle visibility of the form and photo
	var formStudentPhotoVisible = 0;
	$('#aFormStudentPhoto').click(function () {
		if (!formStudentPhotoVisible) {
			$('#formStudentPhoto').css('display', 'inline');
			$('#studentImg').css('display', 'none');
			formStudentPhotoVisible = 1;
		} else {
			$('#formStudentPhoto').css('display', 'none');
			$('#studentImg').css('display', 'inline');
			formStudentPhotoVisible = 0;
		}
	});

	setTimeout(function() {
		$('#formStudentPhoto').ajaxFormUnbind();
		$('#formStudentPhoto').ajaxForm({ //send the photo in AJAX
			beforeSubmit: function(a,f,o) {
				$('#outputStudentPhoto').html('<img src="assets/spinning.gif" />');
			},
			success: function(data) {
				if (data.indexOf('Error') == -1) {
					$('#formStudentPhoto').css('display', 'none');
					formStudentPhotoVisible = 0;
					$('#divStudentPhoto').html('<img src="'+ data +'?cacheKiller='+ Math.round(Math.random()*1000000) +'" width="150" id="studentImg" />');
					$('#outputStudentPhoto').html('');
				} else {
					$('#outputStudentPhoto').html(data);
				}
			}
		});
	}, 1);
	</script> 
<?php }
	echo '<TD style="width:150px;" class="valign-top">';
	if (AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF'])) {
?>
		<div id="divFormStudentPhoto">
			<a href="#" id="aFormStudentPhoto"><img src="assets/plus.gif" height="9" />&nbsp;<?php echo _('Student Photo'); ?></a><br />
			<?php $moveFormStudentPhotoHere = 1; ?>
		</div>
<?php } //endif (AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF'])

	if (($file = @fopen($picture_path=$StudentPicturesPath.UserSyear().'/'.UserStudentID().'.jpg','r')) || ($file = @fopen($picture_path=$StudentPicturesPath.(UserSyear()-1).'/'.UserStudentID().'.jpg','r')))
	{
		fclose($file);
?>
		<div id="divStudentPhoto">
			<IMG SRC="<?php echo $picture_path; ?>" width="150" id="studentImg" />
		</div>
	</TD><TD class="valign-top">
<?php
	}
	else
	{
?>
		<div id="divStudentPhoto">
		</div>
	</TD><TD class="valign-top">
<?php
	}
} //fin modif Francois
else 
	echo '<TD colspan="2">';

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
	echo TextInput('','assign_student_id',_('RosarioSIS ID'),'maxlength=10 size=10');
else
	echo NoInput(UserStudentID(),_('RosarioSIS ID'));
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
echo TextInput((!$student['PASSWORD'] || $_REQUEST['moodle_create_student'] ?'':str_repeat('*',8)),'students[PASSWORD]',(($student['USERNAME'] &&!$student['PASSWORD']) || $_REQUEST['moodle_create_student']?'<span class="legend-red">':'').($_REQUEST['moodle_create_student'] || $old_student_in_moodle?'<SPAN style="cursor:help" title="'._('The password must have at least 8 characters, at least 1 digit, at least 1 lower case letter, at least 1 upper case letter, at least 1 non-alphanumeric character').'">':'')._('Password').($_REQUEST['moodle_create_student'] || $old_student_in_moodle?'*</SPAN>':'').(($student['USERNAME'] &&!$student['PASSWORD']) || $_REQUEST['moodle_create_student']?'</span>':''),'autocomplete=off'.($_REQUEST['moodle_create_student'] || $old_student_in_moodle ? ' required' : ''), ($_REQUEST['moodle_create_student'] ? false : true));
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