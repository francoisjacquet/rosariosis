<?php
/**
 * Side
 *
 * Side menu
 *
 * @package RosarioSIS
 */

require_once 'Warehouse.php';

$old_school = UserSchool();
$old_syear = UserSyear();
$old_period = UserCoursePeriod() . '.' . UserCoursePeriodSchoolPeriod();

$unset_student = $unset_staff = $update_body = false;

$addJavascripts = '';

/**
 * Change current
 * School / SchoolYear / MarkingPeriod / CoursePeriod / Student
 * from menu
 */
if ( isset( $_REQUEST['sidefunc'] )
	&& $_REQUEST['sidefunc'] === 'update'
	&& ( isset( $_REQUEST['side_student_id'] )
		|| isset( $_REQUEST['side_staff_id'] )
		|| $_POST ) )
{
	// Update Admin & Teachers's current School.
	if ( ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' )
		&& isset( $_POST['school'] )
		&& $_POST['school'] != $old_school )
	{
		$unset_student = true;
		$unset_staff = true;

		$_SESSION['UserSchool'] = $_POST['school'];

		DBQuery( "UPDATE STAFF
			SET CURRENT_SCHOOL_ID='" . UserSchool() . "'
			WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'" );

		// Reset current MarkingPeriod.
		$_SESSION['UserMP'] = GetCurrentMP( 'QTR', DBDate(), false );
	}

	// Update current SchoolYear.
	elseif ( isset( $_POST['syear'] )
		&& $_POST['syear'] != $old_syear )
	{
		$_SESSION['UserSyear'] = $_POST['syear'];

		// Reset current MarkingPeriod.
		$_SESSION['UserMP'] = GetCurrentMP( 'QTR', DBDate(), false );

		/**
		 * If current User
		 * update user ID according to new SchoolYear
		 * OR remove if does not exist.
		 */
		if ( ( User( 'PROFILE' ) === 'admin'
				|| User( 'PROFILE' ) === 'teacher' )
			&& UserStaffID() )
		{
			// Search User in next SchoolYear.
			if ( $old_syear == UserSyear() - 1 )
			{
				$new_staff_id_RET = DBGet( "SELECT STAFF_ID
					FROM STAFF
					WHERE ROLLOVER_ID='" . UserStaffID() . "'" );
			}
			// Search User in previous SchoolYear.
			elseif ( $old_syear == UserSyear() + 1 )
			{
				$new_staff_id_RET = DBGet( "SELECT ROLLOVER_ID AS STAFF_ID
					FROM STAFF WHERE
					STAFF_ID='" . UserStaffID() . "'" );
			}
			// More than 1 year difference, search User by USERNAME... (could have changed).
			else
			{
				$new_staff_id_RET = DBGet( "SELECT STAFF_ID
					FROM STAFF
					WHERE USERNAME=
						(SELECT USERNAME
							FROM STAFF
							WHERE STAFF_ID='" . UserStaffID() . "')
					AND SYEAR='" . UserSyear() . "'" );
			}

			if ( $new_staff_id_RET
				&& $new_staff_id_RET[1]['STAFF_ID'] )
			{
				SetUserStaffID( $new_staff_id_RET[1]['STAFF_ID'] );

				// Remove staff_id from URL.
				unset( $_SESSION['_REQUEST_vars']['staff_id'] );
			}
			else
				$unset_staff = true;
		}

		// If current Student not enrolled in new SchoolYear, remove.
		if ( in_array( User( 'PROFILE' ), array( 'admin', 'teacher', 'parent' ) )
			&& UserStudentID() )
		{
			$is_student_enrolled_sql = "SELECT 'ENROLLED'
				FROM STUDENT_ENROLLMENT
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND STUDENT_ID='" . UserStudentID() . "'";

			/**
			 * Remove Student if Teacher:
			 * the student should not be currently scheduled in a course in new SchoolYear
			 * OR remove Student if not enrolled in new SchoolYear.
			 */
			if ( User( 'PROFILE' ) === 'teacher'
				|| ! count( DBGet( $is_student_enrolled_sql ) ) )
			{
				$unset_student = true;
			}
		}
	}

	// Update current MarkingPeriod.
	elseif ( isset( $_POST['mp'] )
		&& $_POST['mp'] != $_SESSION['UserMP'] )
	{
		$_SESSION['UserMP'] = $_POST['mp'];
	}

	// Update Teacher's current CoursePeriod.
	elseif ( User( 'PROFILE' ) === 'teacher'
		&& isset( $_POST['period'] )
		&& $_POST['period'] != $old_period )
	{
		list(
			$_SESSION['UserCoursePeriod'],
			$_SESSION['UserCoursePeriodSchoolPeriod'] ) = explode( '.', $_POST['period'] );

		// If current student.
		if ( UserStudentID() )
		{
			$is_student_scheduled = DBGet( "SELECT 'SCHEDULED'
				FROM SCHEDULE
				WHERE STUDENT_ID='" . UserStudentID() . "'
				AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
				AND '" . DBDate() . "'>=START_DATE
				AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL)" );

			// If student not scheduled in new CoursePeriod, remove.
			if ( ! count( $is_student_scheduled ) )
			{
				$unset_student = true;
			}
		}
	}

	// Update Parent's current Student.
	elseif ( User( 'PROFILE' ) === 'parent'
		&& isset( $_POST['student_id'] )
		&& UserStudentID() != $_POST['student_id'] )
	{
		SetUserStudentID( $_POST['student_id'] );
	}


	/**
	 * If current School OR SchoolYear changed from menu,
	 * reset Admin & Teacher's CoursePeriod
	 */
	if ( ( UserSchool() != $old_school
			|| UserSyear() != $old_syear )
		&& ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' ) )
	{
		unset( $_SESSION['UserPeriod'] );
		unset( $_SESSION['UserCoursePeriod'] );
	}

	// Remove current Student/User from menu if user clicked on red cross.
	if ( ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' )
		&& ( ( $new_student = isset( $_REQUEST['side_student_id'] )
				&& $_REQUEST['side_student_id'] === 'new' )
			|| ( $new_staff = isset( $_REQUEST['side_staff_id'] )
				&& $_REQUEST['side_staff_id'] === 'new' ) ) )
	{
		if ( $new_student )
		{
			$unset_student = true;
		}
		elseif ( $new_staff )
		{
			$unset_staff = true;
		}

		unset( $_SESSION['_REQUEST_vars']['search_modfunc'] );
	}

	// Update "#body" Module page.
	$update_body = true;
}

/**
 * Set current
 * SchoolYear / Student / User / School / MarkingPeriod
 * after login
 */
else
{
	// Set current Student (if user is student).
	if ( ! UserStudentID()
		&& User( 'PROFILE' ) === 'student' )
	{
		SetUserStudentID( $_SESSION['STUDENT_ID'] );
	}

	// Set current User (if user is parent).
	if ( ! UserStaffID()
		&& User( 'PROFILE' ) === 'parent' )
	{
		SetUserStaffID( $_SESSION['STAFF_ID'] );
	}

	// Set current School.
	if ( ! UserSchool() )
	{
		// If user is admin or teacher.
		if ( ( User( 'PROFILE' ) === 'admin'
				|| User( 'PROFILE' ) === 'teacher' )
			&& ( ! User( 'SCHOOLS' )
				|| mb_strpos( User( 'SCHOOLS' ), ',' . User( 'CURRENT_SCHOOL_ID' ) . ',' ) !== false ) )
		{
			$_SESSION['UserSchool'] = User( 'CURRENT_SCHOOL_ID' );
		}
		// If user is student.
		elseif ( User( 'PROFILE' ) === 'student' )
		{
			$_SESSION['UserSchool'] = trim( User( 'SCHOOLS' ), ',' );
		}
		// Do not set here if user is parent (set later on depending on current Student).
	}

	// Set current MarkingPeriod (Quarter).
	if ( ! UserMP() )
	{
		$_SESSION['UserMP'] = GetCurrentMP( 'QTR', DBDate(), false );
	}
}

// Unset current Student.
if ( $unset_student )
{
	unset( $_SESSION['student_id'] );

	// Remove student_id from URL.
	unset( $_SESSION['_REQUEST_vars']['student_id'] );
}

// Unset current User.
if ( $unset_staff )
{
	unset( $_SESSION['staff_id'] );

	// Remove staff_id from URL.
	unset( $_SESSION['_REQUEST_vars']['staff_id'] );
}

// Update "#body" Module page.
if ( $update_body )
{
	/**
	 * If last mod is popup, redirect to Portal!
	 *
	 * Happens when Current Student / SYear... update while popup opened
	 * Preserves integrity and prevents bugs
	 */
	if ( isPopup( $_SESSION['_REQUEST_vars']['modname'], $_SESSION['_REQUEST_vars']['modfunc'] ) )
	{
		$ajax_link = 'Modules.php?modname=misc/Portal.php';
	}
	else
	{
		$ajax_link = PreparePHP_SELF( $_SESSION['_REQUEST_vars'], array( 'advanced' ) );
	}

	$addJavascripts .= 'ajaxLink("' . $ajax_link .	'");';
}

/**
 * Set menu
 * Student / User / School / Marking Period / CoursePeriod
 * verify if have been changed in Warehouse.php
 */
$addJavascripts .= 'var menuStudentID = "' . UserStudentID() . '",
	menuStaffID = "' . UserStaffID() . '",
	menuSchool = "' . UserSchool() . '",
	menuMP = "' . UserMP() . '",
	menuCoursePeriod = "' . UserCoursePeriod() . '";';

?>
<div id="menu-top">
	<script><?php echo $addJavascripts; ?></script>

	<?php // User Information. ?>

	<a href="Modules.php?modname=misc/Portal.php" class="center">
		<img src="assets/themes/<?php echo Preferences( 'THEME' ); ?>/logo.png" class="logo" alt="Logo" />
	</a>
	<form action="Side.php?sidefunc=update" method="POST" target="menu-top">
		<span class="username br-after"><?php echo User( 'NAME' ); ?></span>
		<?php
			// Localized today's date.
			echo '<span class="today-date size-1">' . strftime( '%A %B %d, %Y' ) . '</span>';
		?>
		<br />
		<?php // School SELECT (Admins & Teachers only).
		if ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' ) :

			$schools = mb_substr( str_replace( ',', "','", User( 'SCHOOLS' ) ), 2, -2 );

			$schools_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME
				FROM SCHOOLS
				WHERE SYEAR='" . UserSyear() . "'" .
				( $schools ? " AND ID IN (" . $schools . ")" : '' ) .
				" ORDER BY TITLE" );

			// Set current School.
			if ( ! UserSchool() )
			{
				$_SESSION['UserSchool'] = $schools_RET[1]['ID'];

				DBQuery( "UPDATE STAFF
					SET CURRENT_SCHOOL_ID='" . UserSchool() . "'
					WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'" );
			} ?>

			<span class="br-after">
				<label for="school" class="a11y-hidden"><?php echo _( 'School' ); ?></label>
				<select name="school" id="school" autocomplete="off" onChange="ajaxPostForm(this.form,true);">
			<?php foreach ( (array) $schools_RET as $school ) : ?>
				<option value="<?php echo $school['ID']; ?>"<?php echo ( ( UserSchool() == $school['ID'] ) ? ' selected' : '' ); ?>><?php
					echo ( $school['SHORT_NAME'] ? $school['SHORT_NAME'] : $school['TITLE'] );
				?></option>
			<?php endforeach; ?>
				</select>
			</span>

		<?php endif;

		// Student SELECT (Parents only).
		if ( User( 'PROFILE' ) === 'parent' ) :

			$students_RET = DBGet( "SELECT sju.STUDENT_ID,
				" . DisplayNameSQL( 's' ) . " AS FULL_NAME,se.SCHOOL_ID
				FROM STUDENTS s,STUDENTS_JOIN_USERS sju,STUDENT_ENROLLMENT se
				WHERE s.STUDENT_ID=sju.STUDENT_ID
				AND sju.STAFF_ID='" . User( 'STAFF_ID' ) . "'
				AND se.SYEAR='" . UserSyear() . "'
				AND se.STUDENT_ID=sju.STUDENT_ID
				AND ('" . DBDate() . "'>=se.START_DATE
					AND ('" . DBDate() . "'<=se.END_DATE
						OR se.END_DATE IS NULL ) )" );

			// Set current Student.
			if ( ! UserStudentID() )
			{
				// Note: do not use SetUserStudentID() here as this is safe.
				$_SESSION['student_id'] = $students_RET[1]['STUDENT_ID'];
			}
			?>

			<span class="br-after">
				<label for="student_id" class="a11y-hidden"><?php echo _( 'Student' ); ?></label>
				<select name="student_id" id="student_id" autocomplete="off" onChange="ajaxPostForm(this.form,true);">
			<?php foreach ( (array) $students_RET as $student ) : ?>
				<option value="<?php echo $student['STUDENT_ID']; ?>"<?php echo ( ( UserStudentID() == $student['STUDENT_ID'] ) ? ' selected' : '' ); ?>><?php
					echo $student['FULL_NAME'];
				?></option>
				<?php // Set current School.
				if ( UserStudentID() == $student['STUDENT_ID'] )
				{
					$_SESSION['UserSchool'] = $student['SCHOOL_ID'];
				}

			endforeach; ?>
				</select>
			</span>

			<?php
			// No student associated to parent.
			// Set current School.
			if ( ! UserSchool() )
			{
				$schools_RET = DBGet( "SELECT ID,TITLE
					FROM SCHOOLS
					WHERE SYEAR='" . UserSyear() . "' LIMIT 1" );

				$_SESSION['UserSchool'] = $schools_RET[1]['ID'];
			}

		endif;

		// SchoolYear SELECT.
		if ( User( 'PROFILE' ) !== 'student' )
		{
			$sql = "SELECT sy.SYEAR
				FROM SCHOOLS sy,STAFF s
				WHERE sy.ID='" . UserSchool() . "'
				AND s.SYEAR=sy.SYEAR
				AND (s.SCHOOLS IS NULL OR position(','||sy.ID||',' IN s.SCHOOLS)>0)
				AND s.USERNAME=(SELECT USERNAME
					FROM STAFF
					WHERE STAFF_ID='" . $_SESSION['STAFF_ID'] . "')";
		}
		else
		{
			// FJ limit school years to the years the student was enrolled.
			//$sql = "SELECT DISTINCT sy.SYEAR FROM SCHOOLS sy,STUDENT_ENROLLMENT s WHERE s.SYEAR=sy.SYEAR";
			$sql = "SELECT DISTINCT sy.SYEAR
				FROM SCHOOLS sy,STUDENT_ENROLLMENT s
				WHERE s.SYEAR=sy.SYEAR
				AND s.STUDENT_ID='" . UserStudentID() . "'";
		}

		$sql .= " ORDER BY sy.SYEAR DESC";

		$years_RET = DBGet( $sql ); ?>

		<span class="br-after">
			<label for="syear" class="a11y-hidden"><?php echo _( 'School Year' ); ?></label>
			<select name="syear" id="syear" autocomplete="off" onChange="ajaxPostForm(this.form,true);">
		<?php foreach ( (array) $years_RET as $year ) : ?>
			<option value="<?php echo $year['SYEAR']; ?>"<?php echo ( ( UserSyear() == $year['SYEAR'] ) ? ' selected' : '' ); ?>><?php
				echo FormatSyear( $year['SYEAR'], Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) );
			?></option>
		<?php endforeach; ?>
			</select>
		</span>

		<?php // MarkingPeriod SELECT.
		$RET = DBGet( "SELECT MARKING_PERIOD_ID,TITLE
			FROM SCHOOL_MARKING_PERIODS
			WHERE MP='QTR'
			AND SCHOOL_ID='" . UserSchool() . "'
			AND SYEAR='" . UserSyear() . "'
			ORDER BY SORT_ORDER" );
		?>

		<span class="br-after">
			<label for="mp" class="a11y-hidden"><?php echo _( 'Marking Period' ); ?></label>
			<select name="mp" id="mp" autocomplete="off" onChange="ajaxPostForm(this.form,true);">
		<?php if ( count( $RET ) ) :

			$mp_array = array();

			foreach ( $RET as $quarter ) : ?>
				<option value="<?php echo $quarter['MARKING_PERIOD_ID']; ?>"<?php echo ( UserMP() == $quarter['MARKING_PERIOD_ID'] ? ' selected' : '' ); ?>><?php
					echo $quarter['TITLE'];
				?></option>
			<?php $mp_array[] = $quarter['MARKING_PERIOD_ID'];

			endforeach;

			// Reset UserMP if invalid.
			if ( ! UserMP()
				|| ! in_array( UserMP(), $mp_array ) ) :

				$_SESSION['UserMP'] = $RET[1]['MARKING_PERIOD_ID'];
			endif;

		// Error if no quarters.
		else : ?>

				<option value=""><?php
					echo _( 'Error' ) . ': ' . _( 'No quarters found' );
				?></option>

		<?php endif; ?>

			</select>
		</span>

		<?php // CoursePeriod SELECT (Teachers only).
		if ( User( 'PROFILE' ) == 'teacher' ) : ?>


		<?php // Error if no quarters.
			if ( ! count( $RET ) )
			{
				$all_MP = '0';
			}
			else
				$all_MP = GetAllMP( 'QTR', UserMP() );

			// FJ multiple school periods for a course period.
			//$QI = DBQuery("SELECT cp.PERIOD_ID,cp.COURSE_PERIOD_ID,sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cp.DAYS,c.TITLE AS COURSE_TITLE FROM COURSE_PERIODS cp, SCHOOL_PERIODS sp,COURSES c WHERE c.COURSE_ID=cp.COURSE_ID AND cp.PERIOD_ID=sp.PERIOD_ID AND cp.SYEAR='".UserSyear()."' AND cp.SCHOOL_ID='".UserSchool()."' AND cp.TEACHER_ID='".User('STAFF_ID')."' AND cp.MARKING_PERIOD_ID IN (".GetAllMP('QTR',UserMP()).") ORDER BY sp.SORT_ORDER");
			$cp_RET = DBGet( "SELECT cpsp.PERIOD_ID,cp.COURSE_PERIOD_ID,cpsp.COURSE_PERIOD_SCHOOL_PERIODS_ID,sp.TITLE,sp.SHORT_NAME,cp.MARKING_PERIOD_ID,cpsp.DAYS,c.TITLE AS COURSE_TITLE, cp.SHORT_NAME AS CP_SHORT_NAME
				FROM COURSE_PERIODS cp,SCHOOL_PERIODS sp,COURSES c,COURSE_PERIOD_SCHOOL_PERIODS cpsp
				WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
				AND c.COURSE_ID=cp.COURSE_ID
				AND cpsp.PERIOD_ID=sp.PERIOD_ID
				AND cp.SYEAR='" . UserSyear() . "'
				AND cp.SCHOOL_ID='" . UserSchool() . "'
				AND cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'
				AND cp.MARKING_PERIOD_ID IN (" . $all_MP . ")
				ORDER BY cp.SHORT_NAME, sp.SORT_ORDER" );

			/**
			 * Get the Full Year marking period id
			 * there should be exactly one fy marking period per school.
			 */
			$fy_RET = DBGet( "SELECT MARKING_PERIOD_ID
				FROM SCHOOL_MARKING_PERIODS
				WHERE MP='FY'
				AND SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'" );

			// Set current CoursePeriod after login.
			if ( ! UserCoursePeriod()
				&& isset( $cp_RET[1] ) )
			{
				$_SESSION['UserCoursePeriod'] = $cp_RET[1]['COURSE_PERIOD_ID'];
				$_SESSION['UserCoursePeriodSchoolPeriod'] = $cp_RET[1]['COURSE_PERIOD_SCHOOL_PERIODS_ID'];
			} ?>

			<select name="period" id="period" autocomplete="off" onChange="ajaxPostForm(this.form,true);">
			<?php $optgroup = $current_cp_found = false;

			foreach ( (array) $cp_RET as $period )
			{
				// FJ add optroup to group periods by course periods.
				if ( ! empty( $period['COURSE_TITLE'] )
					&& $optgroup != $period['COURSE_TITLE'] ) : // New optgroup. ?>

					<optgroup label="<?php echo htmlspecialchars( $optgroup = $period['COURSE_TITLE'] ); ?>">

				<?php endif;

				if ( $optgroup !== FALSE
					&& $optgroup != $period['COURSE_TITLE'] ) : // Close optgroup. ?>

					</optgroup>

				<?php endif;

				if ( UserCoursePeriodSchoolPeriod() == $period['COURSE_PERIOD_SCHOOL_PERIODS_ID'] )
				{
					$selected = ' selected';

					$_SESSION['UserPeriod'] = $period['PERIOD_ID'];

					$current_cp_found = true;
				}
				else
					$selected = '';

				// FJ days display to locale.
				$days_convert = array(
					'U' => _( 'Sunday' ),
					'M' => _( 'Monday' ),
					'T' => _( 'Tuesday' ),
					'W' => _( 'Wednesday' ),
					'H' => _( 'Thursday' ),
					'F' => _( 'Friday' ),
					'S' => _( 'Saturday' ),
				);

				// FJ days numbered.
				if ( SchoolInfo( 'NUMBER_DAYS_ROTATION' ) !== null )
				{
					$days_convert = array(
						'U' => '7',
						'M' => '1',
						'T' => '2',
						'W' => '3',
						'H' => '4',
						'F' => '5',
						'S' => '6',
					);
				}

				$period_days = '';

				$days_strlen = mb_strlen( $period['DAYS'] );

				for ( $i = 0; $i < $days_strlen; $i++ )
				{
					$period_days .= mb_substr( $days_convert[ $period['DAYS'][ $i ] ], 0, 3 ) . '.';
				}

				$period_days_text = '';

				if ( mb_strlen( $period['DAYS'] ) < 5 )
				{
					$period_days_text = ' ' .
						( mb_strlen( $period['DAYS'] ) < 2 ? _( 'Day' ) : _( 'Days' ) ) .
						' ' . $period_days;
				}

				$mp_text = '';

				if ( $period['MARKING_PERIOD_ID'] != $fy_RET[1]['MARKING_PERIOD_ID'] )
				{
					$mp_text = GetMP( $period['MARKING_PERIOD_ID'], 'SHORT_NAME' ) . ' - ';
				}
				?>
				<option value="<?php echo $period['COURSE_PERIOD_ID']; ?>.<?php echo $period['COURSE_PERIOD_SCHOOL_PERIODS_ID']; ?>"<?php echo $selected; ?>><?php
					echo $period['TITLE'] . ' - ' .
						$period_days_text .
						$mp_text .
						$period['CP_SHORT_NAME'];
				?></option>

				<?php
			}

			// Error if no courses.
			if ( ! $cp_RET ) : ?>

					<option value=""><?php
						echo _( 'No courses found' );
					?></option>

			<?php endif;

			/**
			 * Error: current CoursePeriod not found
			 * reset current CoursePeriod
			 * and unset current Student
			 */
			if ( ! $current_cp_found )
			{
				$_SESSION['UserCoursePeriod'] = $cp_RET[1]['COURSE_PERIOD_ID'];
				$_SESSION['UserPeriod'] = $cp_RET[1]['PERIOD_ID'];

				unset( $_SESSION['student_id'] );
			}
			?>
			</select>

		<?php endif; ?>

	</form>

	<?php // Display current Student (Admins & Teachers only).
	if ( UserStudentID()
		&& ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' ) ) :

		$current_student_RET = DBGet( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
			FROM STUDENTS
			WHERE STUDENT_ID='" . UserStudentID() . "'" ); ?>

		<div class="current-person student">
			<a href="Side.php?sidefunc=update&amp;side_student_id=new" target="menu-top" title="<?php echo _( 'Clear working student' ); ?>">
				<?php echo button( 'x', '', '', 'bigger' ); ?>
			</a>
			<?php if ( AllowUse( 'Students/Student.php' ) ) : ?>
				<a href="Modules.php?modname=Students/Student.php&amp;student_id=<?php echo UserStudentID(); ?>" title="<?php echo _( 'Student Info' ); ?>">
					<?php echo $current_student_RET[1]['FULL_NAME']; ?>
				</a>
			<?php else : ?>
				<?php echo $current_student_RET[1]['FULL_NAME']; ?>
			<?php endif; ?>
		</div>

	<?php endif;

	// Display current User (Admins & Teachers only).
	if ( UserStaffID()
		&& ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' ) ) :

		$current_user_RET = DBGet( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
			FROM STAFF
			WHERE STAFF_ID='" . UserStaffID() . "'" ); ?>

		<div class="current-person <?php echo ( UserStaffID() == User( 'STAFF_ID' ) ? 'self' : 'staff' ); ?>">
			<a href="Side.php?sidefunc=update&amp;side_staff_id=new" target="menu-top" title="<?php echo _( 'Clear working user' ); ?>">
				<?php echo button( 'x', '', '', 'bigger' ); ?>
			</a>
			<?php if ( AllowUse( 'Users/User.php' ) ) : ?>
				<a href="Modules.php?modname=Users/User.php&amp;staff_id=<?php echo UserStaffID(); ?>" title="<?php echo _( 'User Info' ); ?>">
					<?php echo $current_user_RET[1]['FULL_NAME']; ?>
				</a>
			<?php else : ?>
				<?php echo $current_user_RET[1]['FULL_NAME']; ?>
			<?php endif; ?>
		</div>

	<?php endif; ?>
</div><!-- #menu-top -->

<?php
if ( ! isset( $_REQUEST['sidefunc'] )
	|| $_REQUEST['sidefunc'] !== 'update' ) : ?>

<ul class="adminmenu">

<?php // Generate Menu.
	require_once 'Menu.php';

	// Modify loop: use for instead of foreach.
	$menu_key = array_keys( $_ROSARIO['Menu'] );
	$size_menu = count( $menu_key );

	global $RosarioCoreModules;

	for ( $i = 0; $i < $size_menu; $i++ ) :

		$menu_i = $menu_key[ $i ];

		if ( count( $modcat_menu = $_ROSARIO['Menu'][ $menu_i ] ) ) :

			$modcat_class = mb_strtolower( str_replace( '_', '-', $menu_i ) ); ?>
		<li class="menu-module <?php echo $modcat_class; ?>">
			<a href="Modules.php?modname=<?php echo $modcat_menu['default']; ?>" class="menu-top">

				<span class="module-icon <?php echo $menu_i; ?>"
				<?php if ( ! in_array( $menu_i, $RosarioCoreModules ) ) :
					// Modcat is addon module, set custom module icon. ?>
					style="background-image: url(modules/<?php echo $menu_i; ?>/icon.png);"
				<?php endif; ?>
				></span>&nbsp;<?php echo $modcat_menu['title']; ?>
			</a>
			<ul id="menu_<?php echo $menu_i; ?>" class="wp-submenu">
			<?php
			unset(
				$modcat_menu['default'],
				$modcat_menu['title']
			);

			$modcat_key = array_keys( $modcat_menu );
			$size_modcat = count( $modcat_key );

			for ( $j = 0; $j < $size_modcat; $j++ )
			{
				$modcat_j = $modcat_key[ $j ];

				$title = $_ROSARIO['Menu'][ $menu_i ][ $modcat_j ];

				// If URL, not a program.
				/*if ( mb_stripos( $modcat_j, 'http' ) !== false ) : ?>
					<li><a href="<?php echo $modcat_j; ?>" target="_blank"><?php
						echo $title;
					?></a></li>
				<?php
				else*/
				if ( ! is_numeric( $modcat_j ) ) :

					// If PDF, open in new tab.
					$target = ( mb_strpos( $modcat_j, '_ROSARIO_PDF' ) !== false ?
						' target="_blank"' :
						''
					); ?>
					<li><a href="Modules.php?modname=<?php echo $modcat_j; ?>"<?php echo $target; ?>><?php
						echo $title;
					?></a></li>
				<?php // If is a section.
				elseif ( isset( $modcat_key[ $j + 1 ] )
					&& ! is_numeric( $modcat_key[ $j + 1 ] ) ) : ?>

					<li class="menu-inter"><?php
						echo $title;
					?></li>
				<?php endif;
			}
			?>
			</ul>
		</li>
		<?php endif;
	endfor; ?>

</ul><!-- .adminmenu -->

<?php endif;
