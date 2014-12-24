<?php
error_reporting(1);

include('Warehouse.php');

$old_school = UserSchool();
$old_syear = UserSyear();
$old_period = UserCoursePeriod();

$addJavascripts = '';

if(isset($_REQUEST['school']) && $_REQUEST['school']!=$old_school)
{
	unset($_SESSION['student_id']);
	$_SESSION['unset_student'] = true;
	unset($_SESSION['staff_id']);
	unset($_SESSION['UserMP']);
	unset($_REQUEST['mp']);
}

if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='update' && is_array($_POST))
{
	if((User('PROFILE')=='admin' || User('PROFILE')=='teacher') && $_REQUEST['school']!=$old_school)
	{
		$_SESSION['UserSchool'] = $_REQUEST['school'];
		DBQuery("UPDATE STAFF SET CURRENT_SCHOOL_ID='".UserSchool()."' WHERE STAFF_ID='".User('STAFF_ID')."'");
	}

	if (isset($_REQUEST['syear']))
		$_SESSION['UserSyear'] = $_REQUEST['syear'];
		
	if (isset($_REQUEST['period']))
		$_SESSION['UserCoursePeriod'] = $_REQUEST['period'];
		
	if (isset($_REQUEST['mp']))
		$_SESSION['UserMP'] = $_REQUEST['mp'];
		
	if(User('PROFILE')=='parent')
	{
		if(UserStudentID()!=$_REQUEST['student_id'])
			unset($_SESSION['UserMP']);

		SetUserStudentID($_REQUEST['student_id']);
	}
	$addJavascripts .= 'var body_link = document.createElement("a"); body_link.href = "'.str_replace('&amp;','&',PreparePHP_SELF($_SESSION['_REQUEST_vars'])).'"; body_link.target = "body"; ajaxLink(body_link);';
}

if(!UserSyear())
	$_SESSION['UserSyear'] = Config('SYEAR');

if(!UserStudentID() && User('PROFILE')=='student')
	SetUserStudentID($_SESSION['STUDENT_ID']);

if(!UserStaffID() && User('PROFILE')=='parent')
	SetUserStaffID($_SESSION['STAFF_ID']);

if(!UserSchool())
{
	if((User('PROFILE')=='admin' || User('PROFILE')=='teacher') && (!User('SCHOOLS') || mb_strpos(User('SCHOOLS'),','.User('CURRENT_SCHOOL_ID').',')!==false))
		$_SESSION['UserSchool'] = User('CURRENT_SCHOOL_ID');
	elseif(User('PROFILE')=='student')
		$_SESSION['UserSchool'] = trim(User('SCHOOLS'),',');
}

if((!UserMP() || (isset($_REQUEST['school']) && $_REQUEST['school']!=$old_school) || (isset($_REQUEST['syear']) && $_REQUEST['syear']!=$old_syear)) && User('PROFILE')!='parent')
	$_SESSION['UserMP'] = GetCurrentMP('QTR',DBDate(),false);

if((isset($_REQUEST['school']) && $_REQUEST['school']!=$old_school) || (isset($_REQUEST['syear']) && $_REQUEST['syear']!=$old_syear))
{
	unset($_SESSION['UserPeriod']);
	unset($_SESSION['UserCoursePeriod']);
}

if(isset($_REQUEST['student_id']) && $_REQUEST['student_id']=='new')
{
	unset($_SESSION['student_id']);
	unset($_SESSION['_REQUEST_vars']['student_id']);
	unset($_SESSION['_REQUEST_vars']['search_modfunc']);
	$addJavascripts .= 'var body_link = document.createElement("a"); body_link.href = "'.str_replace('&amp;','&',PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('advanced'))).'"; body_link.target = "body"; ajaxLink(body_link);';
}
if(isset($_REQUEST['staff_id']) && $_REQUEST['staff_id']=='new')
{
	unset($_SESSION['staff_id']);
	unset($_SESSION['_REQUEST_vars']['staff_id']);
	unset($_SESSION['_REQUEST_vars']['search_modfunc']);
	$addJavascripts .= 'var body_link = document.createElement("a"); body_link.href = "'.str_replace('&amp;','&',PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('advanced'))).'"; body_link.target = "body"; ajaxLink(body_link);';
}
unset($_REQUEST['modfunc']);

//set menu Student/User/School/CoursePeriod, verify if have been changed in Modules.php
$addJavascripts .= 'var menuStudentID = "'.UserStudentID().'"; var menuStaffID = "'.UserStaffID().'"; var menuSchool = "'.UserSchool().'"; var menuCoursePeriod = "'.UserCoursePeriod().'";';
?>

		<script><?php echo $addJavascripts; ?></script>
		<div id="menushadow"></div>

		<?php // User Information ?>

		<a href="index.php" target="_top"><img src="assets/themes/<?php echo Preferences('THEME'); ?>/logo.png" id="SideLogo" /></a>
		<FORM action="Side.php?modfunc=update" method="POST" target="menu">
			<span class="br-after">&nbsp;<b><?php echo User('NAME'); ?></b></span>
			&nbsp;<?php echo mb_convert_case(iconv('','UTF-8',strftime('%A %B %d, %Y')), MB_CASE_TITLE, "UTF-8"); ?><BR />

			<?php if(User('PROFILE')=='admin' || User('PROFILE')=='teacher') :
			
				$schools = mb_substr(str_replace(",","','",User('SCHOOLS')),2,-2);
				$QI = DBQuery("SELECT ID,TITLE,SHORT_NAME FROM SCHOOLS WHERE SYEAR='".UserSyear()."'".($schools?" AND ID IN (".$schools.")":''));
				$RET = DBGet($QI);
				
				
				if(!UserSchool())
				{
					$_SESSION['UserSchool'] = $RET[1]['ID'];
					DBQuery("UPDATE STAFF SET CURRENT_SCHOOL_ID='".UserSchool()."' WHERE STAFF_ID='".User('STAFF_ID')."'");
				} ?>

				<span class="br-after"><SELECT name="school" onChange="ajaxPostForm(this.form,true);">

				<?php foreach($RET as $school) : ?>

					<OPTION value="<?php echo $school['ID']; ?>"<?php echo ((UserSchool()==$school['ID'])?' SELECTED':''); ?>><?php echo ($school['SHORT_NAME']?$school['SHORT_NAME']:$school['TITLE']); ?></OPTION>

				<?php endforeach; ?>

				</SELECT></span>

			<?php endif;

			if(User('PROFILE')=='parent') :
			
				$RET = DBGet(DBQuery("SELECT sju.STUDENT_ID,s.LAST_NAME||', '||s.FIRST_NAME AS FULL_NAME,se.SCHOOL_ID 
				FROM STUDENTS s,STUDENTS_JOIN_USERS sju,STUDENT_ENROLLMENT se 
				WHERE s.STUDENT_ID=sju.STUDENT_ID 
				AND sju.STAFF_ID='".User('STAFF_ID')."' 
				AND se.SYEAR='".UserSyear()."' 
				AND se.STUDENT_ID=sju.STUDENT_ID 
				AND ('".DBDate()."'>=se.START_DATE AND ('".DBDate()."'<=se.END_DATE OR se.END_DATE IS NULL))"));

				if(!UserStudentID())
					//note: do not use SetUserStudentID() here as this is safe
					$_SESSION['student_id'] = $RET[1]['STUDENT_ID'];
				?>

				<span class="br-after"><SELECT name="student_id" onChange="ajaxPostForm(this.form,true);">

				<?php if(count($RET)) :
				
					foreach($RET as $student) : ?>

						<OPTION value="<?php echo $student['STUDENT_ID']; ?>"<?php echo ((UserStudentID()==$student['STUDENT_ID'])?' SELECTED':''); ?>><?php echo $student['FULL_NAME']; ?></OPTION>

						<?php if(UserStudentID()==$student['STUDENT_ID'])
							$_SESSION['UserSchool'] = $student['SCHOOL_ID'];
					endforeach;
					
				endif; ?>
				</SELECT></span>

				<?php if(!UserSchool())//no student associated to parent
				{
					$schools_RET = DBGet(DBQuery("SELECT ID,TITLE FROM SCHOOLS WHERE SYEAR='".UserSyear()."' LIMIT 1"));
					$_SESSION['UserSchool'] = $schools_RET[1]['ID'];
				}
				if(!UserMP() || UserSchool()!=$old_school || UserSyear()!=$old_syear) :
					$_SESSION['UserMP'] = GetCurrentMP('QTR',DBDate(),false);
				endif;
			endif;

			if(User('PROFILE')!='student')
				$sql = "SELECT sy.SYEAR FROM SCHOOLS sy,STAFF s WHERE sy.ID='".UserSchool()."' AND s.SYEAR=sy.SYEAR AND (s.SCHOOLS IS NULL OR position(','||sy.ID||',' IN s.SCHOOLS)>0) AND s.USERNAME=(SELECT USERNAME FROM STAFF WHERE STAFF_ID='".$_SESSION['STAFF_ID']."')";
			else
				//modif Francois: limit school years to the years the student was enrolled
				//$sql = "SELECT DISTINCT sy.SYEAR FROM SCHOOLS sy,STUDENT_ENROLLMENT s WHERE s.SYEAR=sy.SYEAR";
				$sql = "SELECT DISTINCT sy.SYEAR FROM SCHOOLS sy,STUDENT_ENROLLMENT s WHERE s.SYEAR=sy.SYEAR AND s.STUDENT_ID='".UserStudentID()."'";
			$sql .= " ORDER BY sy.SYEAR DESC";
			$years_RET = DBGet(DBQuery($sql)); ?>

			<span class="br-after"><SELECT name="syear" onChange="ajaxPostForm(this.form,true);">

			<?php foreach($years_RET as $year) : ?>

				<OPTION value="<?php echo $year['SYEAR']; ?>"<?php echo ((UserSyear()==$year['SYEAR'])?' SELECTED':''); ?>><?php echo FormatSyear($year['SYEAR'],Config('SCHOOL_SYEAR_OVER_2_YEARS')); ?></OPTION>

			<?php endforeach; ?>

			</SELECT></span>

			<?php
			$RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."' ORDER BY SORT_ORDER"));

			if(User('PROFILE')=='teacher') : ?>

			<span class="br-after">

			<?php endif; ?>

			<SELECT name="mp" onChange="ajaxPostForm(this.form,true);">

			<?php if(count($RET)) {
			
				$mp_array = array();

				foreach($RET as $quarter) : ?>

					<OPTION value="<?php echo $quarter['MARKING_PERIOD_ID']; ?>"<?php echo (UserMP()==$quarter['MARKING_PERIOD_ID']?' SELECTED':''); ?>><?php echo $quarter['TITLE']; ?></OPTION>

				<?php 
					$mp_array[] = $quarter['MARKING_PERIOD_ID'];			
				endforeach;
				
				//modif Francois: update UserMP if invalid
				if(!UserMP() || !in_array(UserMP(), $mp_array))
				{
					$_SESSION['UserMP'] = $RET[1]['MARKING_PERIOD_ID'];
				}
			//modif Francois: error if no quarters
			} else { ?>
				<OPTION value=""><?php echo _('Error').': '._('No quarters found'); ?></OPTION>
			<?php } ?>

			</SELECT>

			<?php if(User('PROFILE')=='teacher') : ?>

			</span>

			<?php endif;

			if(User('PROFILE')=='teacher') :
			
				//modif Francois: multiple school periods for a course period
				//$QI = DBQuery("SELECT cp.PERIOD_ID,cp.COURSE_PERIOD_ID,sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cp.DAYS,c.TITLE AS COURSE_TITLE FROM COURSE_PERIODS cp, SCHOOL_PERIODS sp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.PERIOD_ID=sp.PERIOD_ID AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND cp.TEACHER_ID='".User('STAFF_ID')."' AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") ORDER BY sp.SORT_ORDER");
				$QI = DBQuery("SELECT cpsp.PERIOD_ID,cp.COURSE_PERIOD_ID,cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID,sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cpsp.DAYS,c.TITLE AS COURSE_TITLE, cp.SHORT_NAME AS CP_SHORT_NAME 
				FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp,COURSES c,COURSE_PERIOD_SCHOOL_PERIODS cpsp 
				WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID 
				AND c.COURSE_ID=cp.COURSE_ID 
				AND cpsp.PERIOD_ID=sp.PERIOD_ID 
				AND cp.SYEAR='".UserSyear()."' 
				AND cp.SCHOOL_ID='".UserSchool()."' 
				AND cp.TEACHER_ID='".User('STAFF_ID')."' 
				AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") 
				ORDER BY cp.SHORT_NAME, sp.SORT_ORDER");
				
				$RET = DBGet($QI);
				
				// get the fy marking period id, there should be exactly one fy marking period per school
				$fy_RET = DBGet(DBQuery("SELECT MARKING_PERIOD_ID FROM SCHOOL_MARKING_PERIODS WHERE MP='FY' AND SYEAR='".UserSyear()."' AND SCHOOL_ID='".UserSchool()."'"));

				if(isset($_REQUEST['period']))
				{
					list($CoursePeriod, $CoursePeriodSchoolPeriod) = explode('.', $_REQUEST['period']);
					$_SESSION['UserCoursePeriod'] = $CoursePeriod;
					$_SESSION['UserCoursePeriodSchoolPeriod'] = $CoursePeriodSchoolPeriod;
				}

				if(!UserCoursePeriod() && isset($RET[1]))
				{
					$_SESSION['UserCoursePeriod'] = $RET[1]['COURSE_PERIOD_ID'];
					$_SESSION['UserCoursePeriodSchoolPeriod'] = $RET[1]['COURSE_PERIOD_SCHOOL_PERIODS_ID'];
					unset($_SESSION['student_id']);
					$_SESSION['unset_student'] = true;
				} ?>

				<SELECT name="period" onChange="ajaxPostForm(this.form,true);">

				<?php $optgroup = FALSE;
				foreach($RET as $period)
				{
					//modif Francois: add optroup to group periods by course periods
					if (!empty($period['COURSE_TITLE']) && $optgroup!=$period['COURSE_TITLE']) : //new optgroup ?>

						<optgroup label="<?php echo $optgroup = $period['COURSE_TITLE']; ?>">

					<?php endif;
					
					if ($optgroup!==FALSE && $optgroup!=$period['COURSE_TITLE']) : //close optgroup ?>

						</optgroup>

					<?php endif;
					
					if(UserCoursePeriodSchoolPeriod()==$period['COURSE_PERIOD_SCHOOL_PERIODS_ID'])
					{
						$selected = ' SELECTED';
						$_SESSION['UserPeriod'] = $period['PERIOD_ID'];
						$found = true;
					}
					else
						$selected = '';

					//modif Francois: days display to locale						
					$days_convert = array('U'=>_('Sunday'),'M'=>_('Monday'),'T'=>_('Tuesday'),'W'=>_('Wednesday'),'H'=>_('Thursday'),'F'=>_('Friday'),'S'=>_('Saturday'));
					//modif Francois: days numbered
					if (SchoolInfo('NUMBER_DAYS_ROTATION') !== null)
						$days_convert = array('U'=>'7','M'=>'1','T'=>'2','W'=>'3','H'=>'4','F'=>'5','S'=>'6');
					
					$period_days = '';
					$days_strlen = mb_strlen($period['DAYS']);
					for ($i=0; $i<$days_strlen; $i++)
					{
						$period_days .= mb_substr($days_convert[$period['DAYS'][$i]],0,3).'.';
					} ?>

					<OPTION value="<?php echo $period['COURSE_PERIOD_ID']; ?>.<?php echo $period['COURSE_PERIOD_SCHOOL_PERIODS_ID']; ?>"<?php echo $selected; ?>><?php echo $period['TITLE'].(mb_strlen($period['DAYS'])<5?(mb_strlen($period['DAYS'])<2?' '._('Day').' '.$period_days.' - ':' '._('Days').' '.$period_days.' - '):' - ').($period['MARKING_PERIOD_ID']!=$fy_RET[1]['MARKING_PERIOD_ID']?GetMP($period['MARKING_PERIOD_ID'],'SHORT_NAME').' - ':'').$period['CP_SHORT_NAME']; ?></OPTION>

					<?php
				}
				if(!$found)
				{
					$_SESSION['UserCoursePeriod'] = $RET[1]['COURSE_PERIOD_ID'];
					$_SESSION['UserPeriod'] = $RET[1]['PERIOD_ID'];
					unset($_SESSION['student_id']);
					$_SESSION['unset_student'] = true;
				} ?>

				</SELECT>

			<?php endif; ?>

		</FORM>

		<?php if(UserStudentID() && (User('PROFILE')=='admin' || User('PROFILE')=='teacher')) :
		
			//$sql = "SELECT FIRST_NAME||' '||coalesce(MIDDLE_NAME,' ')||' '||LAST_NAME||' '||coalesce(NAME_SUFFIX,' ') AS FULL_NAME FROM STUDENTS WHERE STUDENT_ID='".UserStudentID()."'";
			$sql = "SELECT FIRST_NAME||' '||LAST_NAME||' '||coalesce(NAME_SUFFIX,' ') AS FULL_NAME FROM STUDENTS WHERE STUDENT_ID='".UserStudentID()."'";
			$RET = DBGet(DBQuery($sql)); ?>

			<div class="current-person" style="background-color:#333366;">
				<A HREF="Side.php?student_id=new" target="menu"><IMG SRC="assets/x_button.png"></A> <B><?php echo (AllowUse('Students/Student.php')?'<A HREF="Modules.php?modname=Students/Student.php&student_id='.UserStudentID().'" onclick="openMenu(modname=\'Students/Student.php\');" style="color:white">':''); ?><?php echo $RET[1]['FULL_NAME']; ?><?php echo (AllowUse('Students/Student.php')?'</A>':''); ?></B>
			</div>

		<?php endif;
		
		if(UserStaffID() && (User('PROFILE')=='admin' || User('PROFILE')=='teacher')) :			
			$sql = "SELECT FIRST_NAME||' '||LAST_NAME AS FULL_NAME FROM STAFF WHERE STAFF_ID='".UserStaffID()."'";
			$RET = DBGet(DBQuery($sql)); ?>

			<div class="current-person" style="background-color:<?php echo (UserStaffID()==User('STAFF_ID')?'#663333':'#336633'); ?>;">
				<A HREF="Side.php?staff_id=new" target="menu"><IMG SRC="assets/x_button.png"></A> <B><?php echo (AllowUse('Users/User.php')?'<A HREF="Modules.php?modname=Users/User.php&staff_id='.UserStaffID().'" onclick="openMenu(modname=\'Users/User.php\');" style="color:white">':''); ?><span><?php echo $RET[1]['FULL_NAME']; ?></span><?php echo (AllowUse('Users/User.php')?'</A>':''); ?></B>
			</div>

		<?php endif; ?>

		<div id="adminmenu">

		<?php // Program Information
		include('Menu.php');
		
		//modify loop: use for instead of foreach
		$key = array_keys($_ROSARIO['Menu']);
		$size = sizeOf($key);

		global $RosarioCoreModules;

		for ($i=0; $i<$size; $i++) :
			if (count($modcat_menu = $_ROSARIO['Menu'][$key[$i]])) :
				if(!in_array($key[$i], $RosarioCoreModules))
					$module_title = dgettext($key[$i], str_replace('_',' ',$key[$i]));
				else
					$module_title = _(str_replace('_',' ',$key[$i]));
			?>
			<A href="Modules.php?modname=<?php echo $modcat_menu['default']; ?>" class="menu-top"><IMG SRC="modules/<?php echo $key[$i]; ?>/icon.png" height="32" style="vertical-align:middle;">&nbsp;<?php echo $module_title; ?></A>
			<DIV id="menu_<?php echo $key[$i]; ?>" class="wp-submenu">
				<TABLE class="width-100p cellspacing-0 cellpadding-0">

				<?php unset($modcat_menu['default']);
				
				$keys_modcat = array_keys($modcat_menu);
				$size_modcat = sizeOf($keys_modcat);

				for ($j=0; $j<$size_modcat; $j++) {
				
					$title = $_ROSARIO['Menu'][$key[$i]][$keys_modcat[$j]];
					if(mb_stripos($keys_modcat[$j],'http://') !== false) : ?>

						<TR><TD><A HREF="<?php echo $keys_modcat[$j]; ?>" target="_blank"><?php echo $title; ?></A></TD></TR>
					<?php elseif(!is_numeric($keys_modcat[$j])) : ?>

						<TR><TD><A HREF="Modules.php?modname=<?php echo $keys_modcat[$j]; ?>"<?php echo (mb_stripos($keys_modcat[$j],'_ROSARIO_PDF') !== false ? ' target="_blank"' : ''); ?>><?php echo $title; ?></A></TD></TR>
					<?php elseif($keys_modcat[$j+1] && !is_numeric($keys_modcat[$j+1])) : ?>

						<TR><TD class="menu-inter">&nbsp;<?php echo $title; ?></TD></TR>
					<?php endif;
				} ?>

				</TABLE>
			</DIV>
			<?php endif;
		endfor; ?>

		</div><!-- #adminmenu -->
