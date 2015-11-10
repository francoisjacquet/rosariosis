<?php
echo '<table class="width-100p valign-top fixed-col">';
echo '<tr class="st"><td rowspan="2">';
// IMAGE
if (AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF'])):
?>
	<a href="#" onclick="switchUserPhoto(); return false;"><?php echo button('add', '', '', 'smaller'); ?>&nbsp;<?php echo _('User Photo'); ?></a><br />
	<div class="user-photo-form" style="display:none;">
		<br />
		<input type="file" id="photo" name="photo" accept="image/*" /><span class="loading"></span>
		<br /><span class="legend-gray"><?php echo _('User Photo'); ?> (.jpg)</span>
	</div>
<?php endif;

if ( $_REQUEST['staff_id']!='new' && ($file = @fopen($picture_path=$UserPicturesPath.UserSyear().'/'.UserStaffID().'.jpg','r')) || ($file = @fopen($picture_path=$UserPicturesPath.(UserSyear()-1).'/'.UserStaffID().'.jpg','r'))):
	fclose($file);
?>
	<img src="<?php echo $picture_path.(!empty($new_photo_file)? '?cacheKiller='.rand():''); ?>" class="user-photo" />
<?php endif;
// END IMAGE

echo '</td><td>';

//FJ add translation
$titles_array = array('Mr' => _('Mr'),'Mrs' => _('Mrs'),'Ms' => _('Ms'),'Miss' => _('Miss'),'Dr' => _('Dr'));
$suffixes_array = array('Jr' => _('Jr'),'Sr' => _('Sr'),'II' => _('II'),'III' => _('III'),'IV' => _('IV'),'V' => _('V'));

if (AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
{
	if ( $_REQUEST['staff_id']=='new' || $_REQUEST['moodle_create_user'])
//FJ last & first name required
		echo '<table>
		<tr class="st"><td>
		'.SelectInput($staff['TITLE'],'staff[TITLE]',_('Title'),$titles_array,'').'
		</td><td>
		'.TextInput($staff['FIRST_NAME'],'staff[FIRST_NAME]',($staff['FIRST_NAME']==''?'<span class="legend-red">':'')._('First Name').($staff['FIRST_NAME']==''?'</span>':''),'maxlength=50 required', ($_REQUEST['moodle_create_user'] ? false : true)).'
		</td><td>
		'.TextInput($staff['MIDDLE_NAME'],'staff[MIDDLE_NAME]',_('Middle Name'),'maxlength=50').'
		</td><td>
		'.TextInput($staff['LAST_NAME'],'staff[LAST_NAME]',($staff['LAST_NAME']==''?'<span class="legend-red">':'')._('Last Name').($staff['LAST_NAME']==''?'</span>':''),'maxlength=50 required', ($_REQUEST['moodle_create_user'] ? false : true)).'
		</td><td>
		'.SelectInput($staff['NAME_SUFFIX'],'staff[NAME_SUFFIX]',_('Suffix'),$suffixes_array,'').'
		</td></tr>
		</table>';
	else
	{
		$user_name = '<table>
		<tr class="st"><td>
		'.SelectInput($staff['TITLE'],'staff[TITLE]',_('Title'),$titles_array,'','',false).'
		</td><td>
		'.TextInput($staff['FIRST_NAME'],'staff[FIRST_NAME]',_('First Name'),'maxlength=50 required',false).'
		</td><td>
		'.TextInput($staff['MIDDLE_NAME'],'staff[MIDDLE_NAME]',_('Middle Name'),'maxlength=50',false).'</td><td>
		'.TextInput($staff['LAST_NAME'],'staff[LAST_NAME]',_('Last Name'),'maxlength=50 required',false).'
		</td><td>
		'.SelectInput($staff['NAME_SUFFIX'],'staff[NAME_SUFFIX]',_('Suffix'),$suffixes_array,'','',false).'
		</td></tr>
		</table>';

		echo '<script>var user_name='.json_encode($user_name).';</script>';

		echo '<div id="user_name"><div class="onclick" onclick=\'addHTML(user_name';
		
		echo ',"user_name",true);\'><span class="underline-dots">'.$titles_array[$staff['TITLE']].' '.$staff['FIRST_NAME'].' '.$staff['MIDDLE_NAME'].' '.$staff['LAST_NAME'].' '.$suffixes_array[$staff['NAME_SUFFIX']].'</span></div></div><span class="legend-gray">'._('Name').'</span>';
	}
}
else
	echo ($staff['TITLE']!=''||$staff['FIRST_NAME']!=''||$staff['MIDDLE_NAME']!=''||$staff['LAST_NAME']!=''||$staff['NAME_SUFFIX']!=''?$titles_array[$staff['TITLE']].' '.$staff['FIRST_NAME'].' '.$staff['MIDDLE_NAME'].' '.$staff['LAST_NAME'].' '.$suffixes_array[$staff['NAME_SUFFIX']]:'-').'<br /><span class="legend-gray">'._('Name').'</span>';

echo '</td><td>';

echo NoInput($staff['STAFF_ID'],sprintf(_('%s ID'),Config('NAME')));

echo '</td><td>';

echo NoInput($staff['ROLLOVER_ID'],sprintf(_('Last Year %s ID'),Config('NAME')));

echo '</td></tr><tr class="st"><td>';

//FJ Moodle integrator
//username, password required

$required = $_REQUEST['moodle_create_user'] || $old_user_in_moodle || basename($_SERVER['PHP_SELF'])=='index.php';
$legend_red = $required && !$staff['USERNAME'];

echo TextInput($staff['USERNAME'],'staff[USERNAME]',($legend_red ? '<span class="legend-red">':'')._('Username').(($_REQUEST['moodle_create_user'] || $old_user_in_moodle) && !$staff['USERNAME']?'</span>':''),'size=12 maxlength=100 '.($required ? 'required' : ''),($_REQUEST['moodle_create_user'] ?false:true));

echo '</td><td>';

$required = $required;
$legend_red = $required && !$staff['PASSWORD'];

echo TextInput(
	( !$staff['PASSWORD']
		|| $_REQUEST['moodle_create_user'] ? '' : str_repeat( '*', 8 ) ),
	'staff[PASSWORD]',
	( $legend_red ? '<span class="legend-red">' : '<span class="legend-gray">' ) .
		_( 'Password' ) .
		( $_REQUEST['moodle_create_user']
		|| $old_user_in_moodle ?
		'<div class="tooltip"><i>' .
			_( 'The password must have at least 8 characters, at least 1 digit, at least 1 lower case letter, at least 1 upper case letter, at least 1 non-alphanumeric character' ) .
		'</i></div>' :
		''
		) . '</span>',
	'size=12 maxlength=42 autocomplete=off' . ( $required ? ' required' : '' ),
	( $_REQUEST['moodle_create_user'] ? false : true )
);

echo '</td><td>';

echo NoInput(makeLogin($staff['LAST_LOGIN']),_('Last Login'));


echo '</td></tr></table><hr />';

echo '<table class="width-100p valign-top">';
if (basename($_SERVER['PHP_SELF'])!='index.php')
{
	echo '<tr class="st"><td>';

	echo '<table><tr><td>';
	unset($options);
	$options = array('admin' => _('Administrator'),'teacher' => _('Teacher'),'parent' => _('Parent'),'none' => _('No Access'));
	echo SelectInput($staff['PROFILE'],'staff[PROFILE]',(!$staff['PROFILE']?'<span class="legend-red">':'')._('User Profile').(!$staff['PROFILE']?'</span>':''),$options,false,'',($_REQUEST['moodle_create_user'] ?false:true));

	echo '</td></tr><tr><td>';

	unset($profiles);
	if ( $_REQUEST['staff_id']!='new')
	{
		$profiles_RET = DBGet(DBQuery("SELECT ID,TITLE FROM USER_PROFILES WHERE PROFILE='".$staff['PROFILE']."' ORDER BY ID"));
		foreach ( (array)$profiles_RET as $profile)
//FJ add translation
			$profiles[$profile['ID']] = _($profile['TITLE']);
		$na = _('Custom');
	}
	else
		$na = _('Default');
	echo SelectInput($staff['PROFILE_ID'],'staff[PROFILE_ID]',_('Permissions'),$profiles,$na);
	echo '</td></tr></table>';

	echo '</td><td>';

	//FJ remove Schools for Parents
	if ( $staff['PROFILE']!='parent')
	{
		$sql = "SELECT ID,TITLE FROM SCHOOLS WHERE SYEAR='".UserSyear()."'";
		$QI = DBQuery($sql);
		$schools_RET = DBGet($QI);
		unset($options);
		if (count($schools_RET))
		{
			$i = 0;
			echo '<table><tr class="st">';
			foreach ( (array)$schools_RET as $value)
			{
				if ( $i%3==0)
					echo '</tr><tr class="st">';
				echo '<td>'.CheckboxInput(((mb_strpos($staff['SCHOOLS'],','.$value['ID'].',')!==false)?'Y':''),'staff[SCHOOLS]['.$value['ID'].']',$value['TITLE'], '', false, button('check'), button('x')).'</td>';
				$i++;
			}
			echo '</tr></table>';
			echo '<span class="legend-gray">'._('Schools').'</span>';
		}
		//echo SelectInput($staff['SCHOOL_ID'],'staff[SCHOOL_ID]','School',$options,'All Schools');
	}
	echo '</td></tr>';
}

echo '<tr class="st"><td>';
//FJ Moodle integrator
//email required
//echo TextInput($staff['EMAIL'],'staff[EMAIL]',_('Email Address'),'size=12 maxlength=100');
if (AllowEdit())
	echo TextInput($staff['EMAIL'],'staff[EMAIL]',(($_REQUEST['moodle_create_user'] || $old_user_in_moodle) && !$staff['EMAIL']?'<span class="legend-red">':'')._('Email Address').(($_REQUEST['moodle_create_user'] || $old_user_in_moodle) && !$staff['EMAIL']?'</span>':''),'size=12 maxlength=100'.($_REQUEST['moodle_create_user'] || $old_user_in_moodle ?' required':''),($_REQUEST['moodle_create_user'] ?false:true));
else
	echo TextInput($staff['EMAIL'],'staff[EMAIL]',_('Email Address'),'size=12 maxlength=100');

echo '</td><td>';

echo TextInput($staff['PHONE'],'staff[PHONE]',_('Phone Number'),'size=12 maxlength=100');

echo '</td></tr></table>';

$_REQUEST['category_id'] = '1';
$separator = '<hr />';

require_once 'modules/Users/includes/Other_Info.inc.php';
