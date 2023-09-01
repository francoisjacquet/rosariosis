<?php
/**
 * Side
 *
 * Side menu
 *
 * @package RosarioSIS
 */

require_once 'Warehouse.php';

if ( ! function_exists( 'SideMarkingPeriodSelect' ) ) :
/**
 * Marking Period Select Input
 * Reset UserMP if invalid
 *
 * Override this function in your add-on's functions.php file
 *
 * @since 11.1
 *
 * @return string HTML Select Input
 */
function SideMarkingPeriodSelect()
{
	$mp_RET = DBGet( "SELECT MARKING_PERIOD_ID,TITLE
		FROM school_marking_periods
		WHERE MP='QTR'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,START_DATE" );

	ob_start();

	?>
	<label for="mp" class="a11y-hidden"><?php echo _( 'Marking Period' ); ?></label>
	<select name="mp" id="mp" autocomplete="off" onChange="ajaxPostForm(this.form,true);">
	<?php if ( count( $mp_RET ) ) :

		$mp_array = [];

		foreach ( $mp_RET as $quarter ) : ?>
			<option value="<?php echo AttrEscape( $quarter['MARKING_PERIOD_ID'] ); ?>"<?php echo ( UserMP() == $quarter['MARKING_PERIOD_ID'] ? ' selected' : '' ); ?>><?php
				echo $quarter['TITLE'];
			?></option>
		<?php $mp_array[] = $quarter['MARKING_PERIOD_ID'];

		endforeach;

		// Reset UserMP if invalid.
		if ( ! UserMP()
			|| ! in_array( UserMP(), $mp_array ) ) :

			$_SESSION['UserMP'] = $mp_RET[1]['MARKING_PERIOD_ID'];
		endif;

	// Error if no quarters.
	else : ?>

			<option value=""><?php
				echo _( 'Error' ) . ': ' . _( 'No quarters found' );
			?></option>

	<?php endif; ?>
	</select>
	<?php

	return ob_get_clean();
}
endif;

$old_school = UserSchool();
$old_syear = UserSyear();
$old_period = UserCoursePeriod();

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
	// Update "#body" Module page.
	$update_body = true;

	// Update Admin & Teachers's current School.
	if ( ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' )
		&& isset( $_REQUEST['school'] )
		&& $_REQUEST['school'] != $old_school )
	{
		$unset_student = $unset_staff = true;

		$_SESSION['UserSchool'] = DBGetOne( "SELECT ID FROM schools
			WHERE SYEAR='" . UserSyear() . "'
			AND ID='" . (int) $_REQUEST['school'] . "'" );

		DBQuery( "UPDATE staff
			SET CURRENT_SCHOOL_ID='" . UserSchool() . "'
			WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'" );

		// Reset current MarkingPeriod.
		$_SESSION['UserMP'] = GetCurrentMP( 'QTR', DBDate(), false );
	}

	// Update current SchoolYear.
	elseif ( isset( $_REQUEST['syear'] )
		&& $_REQUEST['syear'] != $old_syear )
	{
		$_SESSION['UserSyear'] = DBGetOne( "SELECT SYEAR FROM schools
			WHERE SYEAR='" . (int) $_REQUEST['syear'] . "'
			AND ID='" . UserSchool() . "'" );

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
				$new_staff_id = DBGetOne( "SELECT STAFF_ID
					FROM staff
					WHERE ROLLOVER_ID='" . UserStaffID() . "'" );
			}
			// Search User in previous SchoolYear.
			elseif ( $old_syear == UserSyear() + 1 )
			{
				$new_staff_id = DBGetOne( "SELECT ROLLOVER_ID AS STAFF_ID
					FROM staff WHERE
					STAFF_ID='" . UserStaffID() . "'" );
			}
			// More than 1 year difference, search User by USERNAME... (could have changed).
			else
			{
				$new_staff_id = DBGetOne( "SELECT STAFF_ID
					FROM staff
					WHERE USERNAME=
						(SELECT USERNAME
							FROM staff
							WHERE STAFF_ID='" . UserStaffID() . "')
					AND SYEAR='" . UserSyear() . "'" );
			}

			if ( $new_staff_id )
			{
				SetUserStaffID( $new_staff_id );

				// Remove staff_id from URL.
				unset( $_SESSION['_REQUEST_vars']['staff_id'] );
			}
			else
				$unset_staff = true;
		}

		// If current Student not enrolled in new SchoolYear, remove.
		if ( in_array( User( 'PROFILE' ), [ 'admin', 'teacher', 'parent' ] )
			&& UserStudentID() )
		{
			$is_student_enrolled_sql = "SELECT 'ENROLLED'
				FROM student_enrollment
				WHERE SYEAR='" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				AND STUDENT_ID='" . UserStudentID() . "'";

			/**
			 * Remove Student if Teacher:
			 * the student should not be currently scheduled in a course in new SchoolYear
			 * OR remove Student if not enrolled in new SchoolYear.
			 */
			$unset_student = User( 'PROFILE' ) === 'teacher'
				|| ! DBGetOne( $is_student_enrolled_sql );
		}
	}

	// Update current MarkingPeriod.
	elseif ( isset( $_REQUEST['mp'] )
		&& $_REQUEST['mp'] != $_SESSION['UserMP'] )
	{
		$_SESSION['UserMP'] = (string) (int) $_REQUEST['mp'];

		// Note: Teacher may teach CP in old MP but not in current MP.
		// Remove period from URL.
		unset( $_SESSION['_REQUEST_vars']['period'] );
	}

	// Update Teacher's current CoursePeriod.
	elseif ( User( 'PROFILE' ) === 'teacher'
		&& isset( $_REQUEST['period'] )
		&& $_REQUEST['period'] != $old_period )
	{
		SetUserCoursePeriod( $_REQUEST['period'] );

		// Remove period from URL.
		unset( $_SESSION['_REQUEST_vars']['period'] );
	}

	// Update Parent's current Student.
	elseif ( User( 'PROFILE' ) === 'parent'
		&& isset( $_REQUEST['student_id'] )
		&& UserStudentID() != $_REQUEST['student_id'] )
	{
		SetUserStudentID( $_REQUEST['student_id'] );

		if ( ! empty( $_SESSION['_REQUEST_vars']['student_id'] ) )
		{
			// Fix Hacking Log when Parent switching Student.
			$_SESSION['_REQUEST_vars']['student_id'] = (string) (int) $_REQUEST['student_id'];
		}
	}

	if ( User( 'PROFILE' ) === 'teacher'
		&& ! $unset_student
		&& UserStudentID() )
	{
		// If current student and MP or Course Period were updated.
		$is_student_scheduled = DBGetOne( "SELECT 'SCHEDULED'
			FROM schedule
			WHERE STUDENT_ID='" . UserStudentID() . "'
			AND COURSE_PERIOD_ID='" . UserCoursePeriod() . "'
			AND '" . DBDate() . "'>=START_DATE
			AND ('" . DBDate() . "'<=END_DATE OR END_DATE IS NULL)
			AND MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', UserMP() ) . ")" );

		// If student not scheduled in new Course Period or MP, remove.
		$unset_student = ! $is_student_scheduled;
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
		unset( $_SESSION['UserCoursePeriod'] );

		// Remove period from URL.
		unset( $_SESSION['_REQUEST_vars']['period'] );
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
		$ajax_link = PreparePHP_SELF( $_SESSION['_REQUEST_vars'], [ 'advanced' ] );
	}

	$addJavascripts .= 'ajaxLink(' . json_encode( $ajax_link ) . ');';
}

/**
 * Set menu
 * Student / User / School / Marking Period / CoursePeriod
 * check if have been changed in Warehouse.php
 */
$addJavascripts .= 'var menuStudentID="' . UserStudentID() . '",
	menuStaffID="' . UserStaffID() . '",
	menuSchool="' . UserSchool() . '",
	menuMP="' . UserMP() . '",
	menuCoursePeriod="' . UserCoursePeriod() . '";';

if ( ! isset( $_REQUEST['sidefunc'] )
	|| $_REQUEST['sidefunc'] !== 'update' ) : ?>

<div id="menu-top">

<?php endif; ?>

	<script><?php echo $addJavascripts; ?></script>

	<?php // User Information. ?>

	<a href="Modules.php?modname=misc/Portal.php" class="center">
		<img src="assets/themes/<?php echo Preferences( 'THEME' ); ?>/logo.png" class="logo" alt="Logo">
	</a>
	<form action="Side.php?sidefunc=update" method="POST" target="menu-top">
		<span class="username br-after"><?php echo User( 'NAME' ); ?></span>
		<?php
			// Localized today's date.
			echo '<span class="today-date size-1">' . strftime_compat( '%A ' . Preferences( 'DATE' ) ) . '</span>';
		?>
		<br>
		<?php // School SELECT (Admins & Teachers only).
		if ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' ) :

			$schools = mb_substr( str_replace( ',', "','", (string) User( 'SCHOOLS' ) ), 2, -2 );

			$schools_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME
				FROM schools
				WHERE SYEAR='" . UserSyear() . "'" .
				( $schools ? " AND ID IN (" . $schools . ")" : '' ) .
				" ORDER BY TITLE" );

			// Set current School.
			if ( ! UserSchool() )
			{
				$_SESSION['UserSchool'] = $schools_RET[1]['ID'];

				DBQuery( "UPDATE staff
					SET CURRENT_SCHOOL_ID='" . UserSchool() . "'
					WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "'" );
			} ?>

			<span class="br-after">
				<label for="school" class="a11y-hidden"><?php echo _( 'School' ); ?></label>
				<select name="school" id="school" autocomplete="off" onChange="ajaxPostForm(this.form,true);">
			<?php foreach ( (array) $schools_RET as $school ) : ?>
				<option value="<?php echo AttrEscape( $school['ID'] ); ?>"<?php echo ( ( UserSchool() == $school['ID'] ) ? ' selected' : '' ); ?>><?php
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
				FROM students s,students_join_users sju,student_enrollment se,schools sch
				WHERE s.STUDENT_ID=sju.STUDENT_ID
				AND sju.STAFF_ID='" . User( 'STAFF_ID' ) . "'
				AND se.SYEAR='" . UserSyear() . "'
				AND se.STUDENT_ID=sju.STUDENT_ID
				AND sch.ID=se.SCHOOL_ID
				AND sch.SYEAR=se.SYEAR
				AND ('" . DBDate() . "'>=se.START_DATE
					AND ('" . DBDate() . "'<=se.END_DATE
						OR se.END_DATE IS NULL ) )" );

			// Set current Student.
			if ( ! UserStudentID()
				&& isset( $students_RET[1]['STUDENT_ID'] ) )
			{
				// Note: do not use SetUserStudentID() here as this is safe.
				$_SESSION['student_id'] = $students_RET[1]['STUDENT_ID'];
			}
			?>

			<span class="br-after">
				<label for="student_id" class="a11y-hidden"><?php echo _( 'Student' ); ?></label>
				<select name="student_id" id="student_id" autocomplete="off" onChange="ajaxPostForm(this.form,true);">
			<?php foreach ( (array) $students_RET as $student ) : ?>
				<option value="<?php echo AttrEscape( $student['STUDENT_ID'] ); ?>"<?php echo ( ( UserStudentID() == $student['STUDENT_ID'] ) ? ' selected' : '' ); ?>><?php
					echo $student['FULL_NAME'];
				?></option>
				<?php // Set current School.
				if ( UserStudentID() == $student['STUDENT_ID'] )
				{
					$_SESSION['UserSchool'] = $student['SCHOOL_ID'];
				}

			endforeach;

			if ( empty( $students_RET ) ) : ?>
				<option value=""><?php echo _( 'No Students were found.' ); ?></option>
			<?php endif; ?>
				</select>
			</span>

			<?php
			// No student associated to parent.
			// Set current School.
			if ( ! UserSchool() )
			{
				$schools_RET = DBGet( "SELECT ID,TITLE
					FROM schools
					WHERE SYEAR='" . UserSyear() . "' LIMIT 1" );

				$_SESSION['UserSchool'] = $schools_RET[1]['ID'];
			}

		endif;

		// SchoolYear SELECT.
		if ( User( 'STAFF_ID' ) )
		{
			$sql = "SELECT sy.SYEAR
				FROM schools sy,staff s
				WHERE sy.ID='" . UserSchool() . "'
				AND s.SYEAR=sy.SYEAR
				AND (s.SCHOOLS IS NULL OR position(CONCAT(',', sy.ID, ',') IN s.SCHOOLS)>0)
				AND s.USERNAME=(SELECT USERNAME
					FROM staff
					WHERE STAFF_ID='" . User( 'STAFF_ID' ) . "')";
		}
		else
		{
			// FJ limit school years to the years the student was enrolled.
			//$sql = "SELECT DISTINCT sy.SYEAR FROM schools sy,student_enrollment s WHERE s.SYEAR=sy.SYEAR";
			$sql = "SELECT DISTINCT sy.SYEAR
				FROM schools sy,student_enrollment s
				WHERE s.SYEAR=sy.SYEAR
				AND s.STUDENT_ID='" . UserStudentID() . "'";
		}

		$sql .= " ORDER BY sy.SYEAR DESC";

		$years_RET = DBGet( $sql ); ?>

		<span class="br-after">
			<label for="syear" class="a11y-hidden"><?php echo _( 'School Year' ); ?></label>
			<select name="syear" id="syear" autocomplete="off" onChange="ajaxPostForm(this.form,true);">
		<?php foreach ( (array) $years_RET as $year ) : ?>
			<option value="<?php echo AttrEscape( $year['SYEAR'] ); ?>"<?php echo ( ( UserSyear() == $year['SYEAR'] ) ? ' selected' : '' ); ?>><?php
				echo FormatSyear( $year['SYEAR'], Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) );
			?></option>
		<?php endforeach; ?>
			</select>
		</span>

		<span class="br-after">
			<?php // @since 11.1 Move Marking Period Select to SideMarkingPeriodSelect() function
			echo SideMarkingPeriodSelect(); ?>
		</span>

		<?php // CoursePeriod SELECT (Teachers only).
		if ( User( 'PROFILE' ) === 'teacher' ) :

			// @since 6.9 Add Secondary Teacher.
			$_SESSION['is_secondary_teacher'] = false;

			$all_mp = GetAllMP( 'QTR', UserMP() );

			$cp_RET = DBGet( "SELECT cp.COURSE_PERIOD_ID,cp.MARKING_PERIOD_ID,
				c.TITLE AS COURSE_TITLE,cp.SHORT_NAME AS CP_SHORT_NAME,cp.SECONDARY_TEACHER_ID
				FROM course_periods cp,courses c
				WHERE c.COURSE_ID=cp.COURSE_ID
				AND cp.SYEAR='" . UserSyear() . "'
				AND cp.SCHOOL_ID='" . UserSchool() . "'
				AND (cp.TEACHER_ID='" . User( 'STAFF_ID' ) . "'
					OR SECONDARY_TEACHER_ID='" . User( 'STAFF_ID' ) . "')
				AND cp.MARKING_PERIOD_ID IN (" . ( $all_mp ? $all_mp : '0' ) . ")
				ORDER BY c.TITLE,cp.SHORT_NAME" );

			/**
			 * Get the Full Year marking period id
			 * there should be exactly one fy marking period per school.
			 */
			$fy_id = GetFullYearMP();

			// Set current CoursePeriod after login.
			if ( ! UserCoursePeriod()
				&& isset( $cp_RET[1] ) )
			{
				// Do not use SetUserCoursePeriod() here as this is safe.
				$_SESSION['UserCoursePeriod'] = $cp_RET[1]['COURSE_PERIOD_ID'];
			} ?>

		<span class="br-after">
			<label for="period" class="a11y-hidden"><?php echo _( 'Course Periods' ); ?></label>
			<select name="period" id="period" autocomplete="off" onChange="ajaxPostForm(this.form,true);">
			<?php $optgroup = $current_cp_found = false;

			foreach ( (array) $cp_RET as $period ) :

				// Add optroup to group periods by course periods.
				if ( ! empty( $period['COURSE_TITLE'] )
					&& $optgroup != $period['COURSE_TITLE'] ) : // New optgroup. ?>

					<optgroup label="<?php echo AttrEscape( $optgroup = $period['COURSE_TITLE'] ); ?>">

				<?php endif;

				if ( $optgroup !== false
					&& $optgroup != $period['COURSE_TITLE'] ) : // Close optgroup. ?>

					</optgroup>

				<?php endif;

				$selected = '';

				if ( UserCoursePeriod() === $period['COURSE_PERIOD_ID'] )
				{
					$selected = ' selected';

					$current_cp_found = true;

					if ( $period['SECONDARY_TEACHER_ID'] === User( 'STAFF_ID' ) )
					{
						// @since 6.9 Add Secondary Teacher.
						$_SESSION['is_secondary_teacher'] = true;
					}
				}

				$mp_text = '';

				if ( $period['MARKING_PERIOD_ID'] != $fy_id )
				{
					$mp_text = GetMP( $period['MARKING_PERIOD_ID'], 'SHORT_NAME' ) . ' - ';
				}
				?>
				<option value="<?php echo AttrEscape( $period['COURSE_PERIOD_ID'] ); ?>"<?php echo $selected; ?>><?php
					echo $mp_text . $period['CP_SHORT_NAME'];
				?></option>

			<?php endforeach;

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
				// Do not use SetUserCoursePeriod() here as this is safe.
				$_SESSION['UserCoursePeriod'] = issetVal( $cp_RET[1]['COURSE_PERIOD_ID'] );

				unset( $_SESSION['student_id'] );
			}
			?>
			</select>
		</span>

		<?php endif; ?>

	</form>

	<?php // Display current Student (Admins & Teachers only).
	if ( UserStudentID()
		&& ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' ) ) :

		$current_student_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
			FROM students
			WHERE STUDENT_ID='" . UserStudentID() . "'" ); ?>

		<div class="current-person student">
			<a href="Side.php?sidefunc=update&amp;side_student_id=new" target="menu-top" title="<?php echo AttrEscape( _( 'Clear working student' ) ); ?>">
				<?php echo button( 'x', '', '', 'bigger' ); ?>
			</a>
			<?php if ( AllowUse( 'Students/Student.php' ) ) : ?>
				<a href="Modules.php?modname=Students/Student.php&amp;student_id=<?php echo (int) UserStudentID(); ?>" title="<?php echo AttrEscape( _( 'Student Info' ) ); ?>">
					<?php echo $current_student_name; ?>
				</a>
			<?php else : ?>
				<?php echo $current_student_name; ?>
			<?php endif; ?>
		</div>

	<?php endif;

	// Display current User (Admins & Teachers only).
	if ( UserStaffID()
		&& ( User( 'PROFILE' ) === 'admin'
			|| User( 'PROFILE' ) === 'teacher' ) ) :

		$current_user_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
			FROM staff
			WHERE STAFF_ID='" . UserStaffID() . "'" ); ?>

		<div class="current-person <?php echo ( UserStaffID() == User( 'STAFF_ID' ) ? 'self' : 'staff' ); ?>">
			<a href="Side.php?sidefunc=update&amp;side_staff_id=new" target="menu-top" title="<?php echo AttrEscape( _( 'Clear working user' ) ); ?>">
				<?php echo button( 'x', '', '', 'bigger' ); ?>
			</a>
			<?php if ( AllowUse( 'Users/User.php' ) ) : ?>
				<a href="Modules.php?modname=Users/User.php&amp;staff_id=<?php echo (int) UserStaffID(); ?>" title="<?php echo AttrEscape( _( 'User Info' ) ); ?>">
					<?php echo $current_user_name; ?>
				</a>
			<?php else : ?>
				<?php echo $current_user_name; ?>
			<?php endif; ?>
		</div>

	<?php endif;

if ( ! isset( $_REQUEST['sidefunc'] )
	|| $_REQUEST['sidefunc'] !== 'update' ) : ?>

</div><!-- #menu-top -->
<ul class="adminmenu">

<?php // Generate Menu.
	require_once 'Menu.php';

	// Modify loop: use for instead of foreach.
	$menu_key = array_keys( (array) $_ROSARIO['Menu'] );
	$size_menu = count( $menu_key );

	global $RosarioCoreModules;

	for ( $i = 0; $i < $size_menu; $i++ ) :

		$menu_i = $menu_key[ $i ];

		if ( count( $modcat_menu = $_ROSARIO['Menu'][ $menu_i ] ) ) :

			$modcat_class = mb_strtolower( str_replace( '_', '-', $menu_i ) ); ?>
		<li class="menu-module <?php echo $modcat_class; ?>">
			<a href="<?php echo URLEscape( 'Modules.php?modname=' . $modcat_menu['default'] ); ?>" class="menu-top">

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
					<li><a href="<?php echo URLEscape( $modcat_j ); ?>" target="_blank"><?php
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
					<li><a href="<?php echo URLEscape( 'Modules.php?modname=' . $modcat_j ); ?>"<?php echo $target; ?>><?php
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
