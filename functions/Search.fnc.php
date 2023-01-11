<?php
/**
 * Search Students or Staff function
 *
 * @package RosarioSIS
 * @subpackage functions
 */

/**
 * Search Students or Staff
 *
 * @example Search( 'student_id' ); // Display Find a Student form or Search students if submitted
 *
 * @example Search( 'staff_id' ); // Display Find a User form or Search users if submitted
 *
 * @since 4.8 Search Parents by Student Grade Level.
 * @since 5.1 Medical Immunization or Physical Widget.
 *
 * @see Users & Students modules Search.inc.php files
 *
 * @global $_ROSARIO Used in Search.inc.php
 *
 * @param  string $type  student_id|staff_id|general_info|staff_general_info|staff_fields|staff_fields_all|student_fields|student_fields_all.
 * @param  array  $extra Search.inc.php extra (HTML, functions...) (optional). Defaults to null.
 *
 * @return void
 */
function Search( $type, $extra = null )
{
	global $_ROSARIO;

	switch ( (string) $type )
	{
		case 'student_id':

			if ( ! empty( $_REQUEST['bottom_back'] )
				|| ( User( 'PROFILE' ) !== 'student'
					&& User( 'PROFILE' ) !== 'parent'
					&& ! empty( $_REQUEST['search_modfunc'] )
					&& $_REQUEST['search_modfunc'] === 'list' ) )
			{
				unset( $_SESSION['student_id'] );
			}

			if ( ! empty( $_REQUEST['student_id'] ) )
			{
				if ( $_REQUEST['student_id'] !== 'new'
					&& $_REQUEST['student_id'] != UserStudentID() )
				{
					if ( ! empty( $_REQUEST['school_id'] )
						&& $_REQUEST['school_id'] != UserSchool() )
					{
						$_SESSION['UserSchool'] = DBGetOne( "SELECT ID FROM schools
							WHERE SYEAR='" . UserSyear() . "'
							AND ID='" . (int) $_REQUEST['school_id'] . "'" );
					}

					SetUserStudentID( $_REQUEST['student_id'] );
				}
				elseif ( $_REQUEST['student_id'] === 'new'
					&& UserStudentID() )
				{
					unset( $_SESSION['student_id'] );
				}
			}
			elseif ( ! UserStudentID()
				|| ! empty( $extra['new'] ) )
			{
				if ( UserStudentID() )
				{
					// FJ fix bug no student found when student/parent logged in.
					if ( User( 'PROFILE' ) !== 'student'
						&& User( 'PROFILE' ) !== 'parent' )
					{
						unset( $_SESSION['student_id'] );
					}
				}

				$_REQUEST['next_modname'] = $_REQUEST['modname'];

				require_once 'modules/Students/Search.inc.php';
			}

		break;

		case 'staff_id':

			// Convert profile string to array for legacy compatibility.
			if ( ! is_array( $extra ) )
			{
				$extra = [ 'profile' => $extra ];
			}

			if ( ! empty( $_REQUEST['bottom_back'] )
				|| ( User( 'PROFILE' ) !== 'parent'
					&& ! empty( $_REQUEST['search_modfunc'] )
					&& $_REQUEST['search_modfunc'] === 'list' ) )
			{
				unset( $_SESSION['staff_id'] );
			}

			if ( ! empty( $_REQUEST['staff_id'] ) )
			{
				if ( $_REQUEST['staff_id'] !== 'new'
					&& $_REQUEST['staff_id'] != UserStaffID() )
				{
					SetUserStaffID( $_REQUEST['staff_id'] );
				}
				elseif ( $_REQUEST['staff_id'] === 'new'
					&& UserStaffID() )
				{
					unset( $_SESSION['staff_id'] );
				}
			}
			elseif ( ! UserStaffID()
				|| ! empty( $extra['new'] ) )
			{
				if ( UserStaffID() )
				{
					unset( $_SESSION['staff_id'] );
				}

				$_REQUEST['next_modname'] = $_REQUEST['modname'];

				require_once 'modules/Users/Search.inc.php';
			}

		break;

		// Find a Student form General Info & Grade Level.
		case 'general_info':
			// TODO:
			// http://ux.stackexchange.com/questions/85050/what-is-the-best-practice-for-password-field-placeholders
			echo '<tr><td><label for="last">' . _( 'Last Name' ) . '</label></td><td>
				<input type="text" name="last" id="last" size="24" maxlength="50" autofocus>
				</td></tr>';

			echo '<tr><td><label for="first">' . _( 'First Name' ) . '</label></td><td>
				<input type="text" name="first" id="first" size="24" maxlength="50">
				</td></tr>';

			echo '<tr><td><label for="stuid">' . sprintf( _( '%s ID' ), Config( 'NAME' ) ) .
				'</label></td><td>
				<input type="text" name="stuid" id="stuid" size="24" maxlength="5000">
				</td></tr>';

			echo '<tr><td><label for="addr">' . _( 'Address' ) . '</label></td><td>
				<input type="text" name="addr" id="addr" size="24" maxlength="255">
				</td></tr>';

			// Grade Level.
			$grade_levels_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME
				FROM school_gradelevels
				WHERE SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

			if ( isset( $_REQUEST['advanced'] )
				&& $_REQUEST['advanced'] === 'Y'
				|| ! empty( $extra ) && is_array( $extra ) )
			{
				echo '<tr><td>' . _( 'Grade Levels' ) . '</td>
				<td>&nbsp;<label class="nobr"><input type="checkbox" name="grades_not" value="Y">&nbsp;' .
					_( 'Not' ) . '</label> &nbsp;
				<label class="nobr"><input type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.checked,\'grades\');">&nbsp;' .
					_( 'Check All' ) . '</label>
				</td></tr>
				<tr><td></td><td><table class="cellpadding-5"><tr>';

				$i = 0;

				foreach ( $grade_levels_RET as $grade_level )
				{
					$id = $grade_level['ID'];

					$checked = ! empty( $extra[ $id ] ) || $extra == $id ? ' checked' : '';

					echo '<td><label class="nobr">
					<input type="checkbox" name="' . AttrEscape( 'grades[' . $id . ']' ) . '" value="Y"' . $checked . '>&nbsp;' .
						$grade_level['SHORT_NAME'] . '</label></td>';

					$i++;

					if ( $i%4 === 0 )
					{
						echo '</tr><tr>';
					}
				}

				echo '</tr></table></td></tr>';
			}
			else
			{
				echo '<tr><td><label for="grade">' . _( 'Grade Level' ) . '</label>
				</td><td>
				<select name="grade" id="grade">
				<option value="">' . _( 'Not Specified' ) . '</option>';

				foreach ( $grade_levels_RET as $grade_level )
				{
					$id = $grade_level['ID'];

					echo '<option value="' . AttrEscape( $id ) . '"' . ( $extra == $id ? ' selected' : '' ) . '>' .
						$grade_level['TITLE'] . '</option>';
				}

				echo '</select></td></tr>';
			}

		break;

		// Find a User form General Info & Profile.
		case 'staff_general_info':

			echo '<tr><td><label for="staff_last">' . _( 'Last Name' ) . '</label></td><td>
				<input type="text" name="staff_last" id="staff_last" size="24" maxlength="50" autofocus>
				</td></tr>';

			echo '<tr><td><label for="staff_first">' . _( 'First Name' ) . '</label></td><td>
				<input type="text" name="staff_first" id="staff_first" size="24" maxlength="50">
				</td></tr>';

			echo '<tr><td><label for="usrid">' . _( 'User ID' ) .
				'</label></td><td>
				<input type="text" name="usrid" id="usrid" size="24" maxlength="5000">
				</td></tr>';

			echo '<tr><td><label for="username">' . _( 'Username' ) .
				'</label></td><td>
				<input type="text" name="username" id="username" size="24" maxlength="255">
				</td></tr>';

			$options = [
				'' => _( 'N/A' ),
				'teacher' => _( 'Teacher' ),
				'parent' => _( 'Parent' ),
			];

			// Profile.
			if ( User( 'PROFILE' ) === 'admin' )
			{
				$options = [
					'' => _( 'N/A' ),
					'admin' => _( 'Administrator' ),
					'teacher' => _( 'Teacher' ),
					'parent' => _( 'Parent' ),
					'none' => _( 'No Access' ),
				];
			}

			if ( ! empty( $extra['profile'] ) )
			{
				$options = [ $extra['profile'] => $options[ $extra['profile'] ] ];
			}

			echo '<tr><td><label for="profile">' . _( 'Profile' ) . '</label></td>
				<td><select name="profile" id="profile" onchange="_selectStudentGradeLevel(this);" autocomplete="off">';

			foreach ( $options as $key => $val )
			{
				echo '<option value="' . AttrEscape( $key ) . '">' . $val . '</option>';
			}

			echo '</select></td></tr>';

			// @since 4.8 Search Parents by Student Grade Level.
			$grade_levels_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME
				FROM school_gradelevels
				WHERE SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER IS NULL,SORT_ORDER" );

			// Do not hide in case first Profile is "Parent".
			$maybe_hide = key( $options ) === 'parent' ? '' : ' class="hide"';

			echo '<tr id="student_grade_level_row"' . $maybe_hide . '><td>
				<label for="student_grade_level">' . _( 'Student Grade Level' ) . '</label></td>
				<td><select name="student_grade_level" id="student_grade_level">
				<option value="">' . _( 'Not Specified' ) . '</option>';

			foreach ( $grade_levels_RET as $grade_level )
			{
				echo '<option value="' . AttrEscape( $grade_level['ID'] ) . '">' .
					$grade_level['TITLE'] . '</option>';
			}

			echo '</select></td></tr>';

			// Show Student Grade Level when selected Profile is "Parent".
			echo '<script>
				var _selectStudentGradeLevel = function( select ) {
					var show = select.value === "parent";

					return $("#student_grade_level_row").toggle( show );
				};
			</script>';

		break;

		case 'staff_fields':
		case 'staff_fields_all':
		case 'student_fields':
		case 'student_fields_all':

			if ( $type === 'staff_fields_all' )
			{
				$categories_SQL = "SELECT sfc.ID,sfc.TITLE AS CATEGORY_TITLE,
				CONCAT('CUSTOM_', cf.ID) AS COLUMN_NAME,cf.TYPE,cf.TITLE,SELECT_OPTIONS
				FROM staff_field_categories sfc,staff_fields cf
				WHERE (SELECT CAN_USE
					FROM " . ( User( 'PROFILE_ID' ) ?
						"profile_exceptions WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'" :
						"staff_exceptions WHERE USER_ID='" . User( 'STAFF_ID' ) . "'" ) . "
					AND MODNAME=CONCAT('Users/User.php&category_id=', sfc.ID)
					LIMIT 1)='Y'
				AND cf.CATEGORY_ID=sfc.ID
				AND NOT EXISTS(SELECT ''
					FROM program_user_config
					WHERE PROGRAM='StaffFieldsSearch'
					AND TITLE=cast(cf.ID AS char(10))
					AND USER_ID='" . User( 'STAFF_ID' ) . "'
					AND VALUE='Y')
				AND cf.TYPE<>'files'
				ORDER BY sfc.SORT_ORDER IS NULL,sfc.SORT_ORDER,sfc.TITLE,cf.SORT_ORDER IS NULL,cf.SORT_ORDER,cf.TITLE";
			}
			elseif ( $type === 'staff_fields' )
			{
				$categories_SQL = "SELECT '0' AS ID,'' AS CATEGORY_TITLE,
				CONCAT('CUSTOM_', cf.ID) AS COLUMN_NAME,cf.TYPE,cf.TITLE,cf.SELECT_OPTIONS
				FROM staff_fields cf
				WHERE (SELECT CAN_USE
					FROM " . ( User( 'PROFILE_ID' ) ?
						"profile_exceptions WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'" :
						"staff_exceptions WHERE USER_ID='" . User( 'STAFF_ID' ) . "'") . "
					AND MODNAME=CONCAT('Users/User.php&category_id=', cf.CATEGORY_ID)
					LIMIT 1)='Y'
				AND (SELECT VALUE
					FROM program_user_config
					WHERE TITLE=cast(cf.ID AS char(10))
					AND PROGRAM='StaffFieldsSearch'
					AND USER_ID='" . User( 'STAFF_ID' ) . "'
					LIMIT 1)='Y'
				ORDER BY cf.SORT_ORDER IS NULL,cf.SORT_ORDER,cf.TITLE";
			}
			elseif ( $type === 'student_fields_all' )
			{
				$categories_SQL = "SELECT sfc.ID,sfc.TITLE AS CATEGORY_TITLE,
				CONCAT('CUSTOM_', cf.ID) AS COLUMN_NAME,cf.TYPE,cf.TITLE,SELECT_OPTIONS
				FROM student_field_categories sfc,custom_fields cf
				WHERE (SELECT CAN_USE
					FROM " . ( User( 'PROFILE_ID' ) ?
						"profile_exceptions WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'" :
						"staff_exceptions WHERE USER_ID='" . User( 'STAFF_ID' ) . "'") . "
					AND MODNAME=CONCAT('Students/Student.php&category_id=', sfc.ID)
					LIMIT 1)='Y'
				AND cf.CATEGORY_ID=sfc.ID
				AND NOT exists(SELECT ''
					FROM program_user_config
					WHERE PROGRAM='StudentFieldsSearch'
					AND TITLE=cast(cf.ID AS char(10))
					AND USER_ID='" . User( 'STAFF_ID' ) . "'
					AND VALUE='Y')
				AND cf.TYPE<>'files'
				ORDER BY sfc.SORT_ORDER IS NULL,sfc.SORT_ORDER,sfc.TITLE,cf.SORT_ORDER IS NULL,cf.SORT_ORDER,cf.TITLE";
			}
			else
			{
				$categories_SQL = "SELECT '0' AS ID,'' AS CATEGORY_TITLE,
				CONCAT('CUSTOM_', cf.ID) AS COLUMN_NAME,cf.TYPE,cf.TITLE,cf.SELECT_OPTIONS
				FROM custom_fields cf
				WHERE (SELECT CAN_USE
					FROM " . ( User( 'PROFILE_ID' ) ?
						"profile_exceptions WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'" :
						"staff_exceptions WHERE USER_ID='" . User( 'STAFF_ID' ) . "'") . "
					AND MODNAME=CONCAT('Students/Student.php&category_id=', cf.CATEGORY_ID)
					LIMIT 1)='Y'
				AND (SELECT VALUE
					FROM program_user_config
					WHERE TITLE=cast(cf.ID AS char(10))
					AND PROGRAM='StudentFieldsSearch'
					AND USER_ID='" . User( 'STAFF_ID' ) . "'
					LIMIT 1)='Y'
				ORDER BY cf.SORT_ORDER IS NULL,cf.SORT_ORDER,cf.TITLE";
			}

			$categories_RET = ParseMLArray(
				DBGet(
					$categories_SQL,
					[],
					[ 'ID', 'TYPE' ] ),
				[ 'CATEGORY_TITLE', 'TITLE' ]
			);

			if ( $type === 'student_fields_all' )
			{
				// Student Fields: search Username.
				$general_info_category_title = ParseMLField( DBGetOne( "SELECT sfc.TITLE
					FROM student_field_categories sfc
					WHERE sfc.ID=1" ) );

				$i = empty( $categories_RET[1]['text'] ) ? 1 : count( $categories_RET[1]['text'] );

				if ( Preferences( 'USERNAME', 'StudentFieldsSearch' ) !== 'Y' )
				{
					if ( ! isset( $categories_RET[1] ) )
					{
						// Empty General Info category.
						$categories_RET[1] = [];
					}

					// Add Username to Staff General Info.
					$categories_RET[1]['text'][ ++$i ] = [
						'ID' => '1',
						'CATEGORY_TITLE' => $general_info_category_title,
						'COLUMN_NAME' => 'USERNAME',
						'TYPE' => 'text',
						'TITLE' => _( 'Username' ),
						'SELECT_OPTIONS' => null,
					];
				}
			}
			elseif ( $type === 'student_fields' )
			{
				$i = isset( $i ) ? $i : 0;

				if ( Preferences( 'USERNAME', 'StudentFieldsSearch' ) === 'Y' )
				{
					// Add Username to Find a User form.
					$categories_RET[1]['text'][ $i++ ] = [
						'ID' => '1',
						'CATEGORY_TITLE' => '',
						'COLUMN_NAME' => 'USERNAME',
						'TYPE' => 'text',
						'TITLE' => _( 'Username' ),
						'SELECT_OPTIONS' => null,
					];
				}
			}
			elseif ( $type === 'staff_fields_all' )
			{
				$i = 1;
			}
			elseif ( $type === 'staff_fields' )
			{
				$i = isset( $i ) ? $i : 0;
			}

			foreach ( $categories_RET as $category )
			{
				$TR_classes = '';

				$category_default = [
					'text' => [],
					'numeric' => [],
					'select' => [],
					'autos' => [],
					'exports' => [],
					'date' => [],
					'radio' => [],
				];

				$category = array_replace_recursive( $category_default, (array) $category );

				foreach ( $category as $cols )
				{
					if ( ! empty( $cols[1]['CATEGORY_TITLE'] ) )
					{
						$category_title = $cols[1]['CATEGORY_TITLE'];

						break;
					}
				}

				if ( $type === 'student_fields_all'
					|| $type === 'staff_fields_all' )
				{
					echo '<a onclick="switchMenu(this); return false;" href="#" class="switchMenu">
					<b>' . $category_title . '</b></a>
					<br>
					<table class="widefat width-100p col1-align-right hide">';

					$TR_classes .= 'st';

					if ( $type === 'student_fields_all'
						&& isset( $category['text'][1]['ID'] )
						&& $category['text'][1]['ID'] === '2' )
					{
						$extra['search'] = '';

						// @since 5.1 Medical Immunization or Physical Widget.
						Widgets( 'medical_date', $extra );

						echo $extra['search'];
					}
				}

				// Text.
				foreach ( (array) $category['text'] as $col )
				{
					if ( ( $type === 'staff_fields'
							|| $type === 'staff_fields_all' )
						&& $col['COLUMN_NAME'] === 'CUSTOM_200000000' )
					{
						// @since 5.9 Move Email & Phone Staff Fields to custom fields.
						$col['COLUMN_NAME'] = 'EMAIL';
					}

					$name = 'cust[' . $col['COLUMN_NAME'] . ']';

					$id = GetInputID( $name );

					echo '<tr class="' . $TR_classes . '"><td>
					<label for="' . $id . '">' . $col['TITLE'] . '</label>
					</td><td>
					<input type="text" name="' . AttrEscape( $name ) . '" id="' . $id . '" size="24" maxlength="1000">
					</td></tr>';
				}

				// Numeric.
				foreach ( (array) $category['numeric'] as $col )
				{
					echo '<tr class="' . AttrEscape( $TR_classes ) . '"><td>' . $col['TITLE'] . '</td><td>
					<span class="sizep2">&ge;</span>
					<input type="text" name="' . AttrEscape( 'cust_begin[' . $col['COLUMN_NAME'] . ']' ) . '" size="3" maxlength="11">
					<span class="sizep2">&le;</span>
					<input type="text" name="' . AttrEscape( 'cust_end[' . $col['COLUMN_NAME'] . ']' ) . '" size="3" maxlength="11">
					<label><input type="checkbox" name="' . AttrEscape( 'cust_null[' . $col['COLUMN_NAME'] . ']' ) . '"> ' . _( 'No Value' ) .
					'</label>&nbsp;
					</td></tr>';
				}

				// Merge select, autos, edits, exports & codeds
				// (same or similar SELECT output).
				$category['select_autos_exports'] = array_merge(
					(array) $category['select'],
					(array) $category['autos'],
					(array) $category['exports']
				);

				// Select.
				foreach ( $category['select_autos_exports'] as $col )
				{
					$options = [];

					$col_name = $col['COLUMN_NAME'];

					if ( $col['SELECT_OPTIONS'] )
					{
						$options = explode(
							"\r",
							str_replace( [ "\r\n", "\n" ], "\r", $col['SELECT_OPTIONS'] )
						);
					}

					$name = 'cust[' . $col_name . ']';

					$id = GetInputID( $name );

					echo '<tr class="' . AttrEscape( $TR_classes ) . '">
					<td><label for="' . $id . '">' . $col['TITLE'] . '</label></td><td>
					<select name="' . AttrEscape( $name ) . '" id="' . $id . '">
						<option value="">' . _( 'N/A' ) . '</option>
						<option value="!">' . _( 'No Value' ) . '</option>';

					foreach ( $options as $option )
					{
						$value = $option;

						// Exports specificities.
						if ( $col['TYPE'] === 'exports' )
						{
							$option = explode( '|', $option );

							$option = $value = $option[0];
						}

						if ( $value !== ''
							&& $option !== '' )
						{
							echo '<option value="' . AttrEscape( $value ) . '">' . $option . '</option>';
						}
					}

					if ( $col['TYPE'] === 'autos' )
					{
						// Autos specificities.
						echo '<option value="~">' . _( 'Other Value' ) . '</option>';

						// Get autos edited options.
						if ( mb_strpos( $type, 'student' ) !== false )
						{
							$sql_options = "SELECT DISTINCT s." . $col_name . ",upper(s." . $col_name . ") AS SORT_KEY
								FROM students s,student_enrollment sse
								WHERE sse.STUDENT_ID=s.STUDENT_ID
								AND sse.SYEAR='" . UserSyear() . "'
								AND s." . $col_name . " IS NOT NULL
								AND s." . $col_name . " != ''
								ORDER BY SORT_KEY";
						}
						else // Staff.
						{
							$sql_options = "SELECT DISTINCT s." . $col_name . ",upper(s." . $col_name . ") AS SORT_KEY
								FROM staff s WHERE s.SYEAR='" . UserSyear() . "'
								AND s." . $col_name . " IS NOT NULL
								AND s." . $col_name . " != ''
								ORDER BY SORT_KEY";
						}

						$options_RET = DBGet( $sql_options );

						// Add the 'new' option, is also the separator.
						echo '<option value="---">-' . _( 'Edit' ) . '-</option>';

						foreach ( $options_RET as $option )
						{
							if ( ! in_array( $option[ $col_name ], $options ) )
							{
								echo '<option value="' . AttrEscape( $option[ $col_name ] ) . '">' .
									$option[ $col_name ] . '</option>';
							}
						}
					}

					echo '</select></td></tr>';
				}

				// Date.
				foreach ( (array) $category['date'] as $col )
				{
					echo '<tr class="' . AttrEscape( $TR_classes ) . '"><td>' . $col['TITLE'] . '<br>
					<label>&nbsp;<input type="checkbox" name="' . AttrEscape( 'cust_null[' . $col['COLUMN_NAME'] . ']' ) . '"> ' .
					_( 'No Value' ) . '</label>
					</td>
					<td><table class="cellspacing-0">
					<tr><td><span class="sizep2">&ge;</span>&nbsp;</td>
					<td>' . PrepareDate(
						'',
						'_cust_begin[' . $col['COLUMN_NAME'] . ']',
						true,
						[ 'short' => true ]
					) . '</td></tr>
					<tr><td><span class="sizep2">&le;</span>&nbsp;</td>
					<td>' . PrepareDate(
						'',
						'_cust_end[' . $col['COLUMN_NAME'] . ']',
						true,
						[ 'short' => true ]
					) . '</td></tr>
					</table></td></tr>';
				}

				// Radio.
				foreach ( (array) $category['radio'] as $col )
				{
					$name = 'cust[' . $col['COLUMN_NAME'] . ']';

					echo '<tr class="' . AttrEscape( $TR_classes ) . '"><td>' . $col['TITLE'] . '</td>
					<td><label><input name="' . AttrEscape( $name ) . '" type="radio" value="" checked> ' .
					_( 'All' ) . '</label> &nbsp;
					<label><input name="' . AttrEscape( $name ) . '" type="radio" value="Y"> ' .
					_( 'Yes' ) . '</label> &nbsp;
					<label><input name="' . AttrEscape( $name ) . '" type="radio" value="N"> ' .
					_( 'No' ) . '</label></td></tr>';
				}

				if ( $type === 'student_fields_all'
					|| $type === 'staff_fields_all' )
				{
					echo '</table>';
				}
			}

		break;
	}
}




/**
 * Search (custom) (staff) Field SQL
 * Call in an SQL statement to select students / staff based on this field
 * Also sets $_ROSARIO['SearchTerms'] to display search term
 *
 * @since 3.0
 * @since 10.0 SQL rename $field COLUMN (reserved keyword) to COLUMN_NAME for MySQL compatibility
 *
 * @see appendSQL(), appendStaffSQL() & CustomFields() for use cases.
 *
 * Use in the where section of the query:
 * @example $return .= SearchField( $first_name, 'student', $extra );
 *
 * Searching "Attendance Start" date >= to value, use PART => 'begin':
 * @example $sql .= SearchField( [ 'COLUMN_NAME' => 'ENROLLED_BEGIN', 'VALUE' => '2017-02-15', 'TYPE' => 'date', 'PART' => 'begin', 'TITLE' => _( 'Attendance Start' ) ], 'student', $extra );
 * Same applies for numeric fields.
 * PART can be 'begin' (greater than or equal) or 'end' (lower than or equal), defaults to equal.
 *
 * @global array  $_ROSARIO Sets $_ROSARIO['SearchTerms']
 *
 * @param  array  $field  Field data: must include COLUMN_NAME|VALUE|TYPE|TITLE, may include SELECT_OPTIONS|PART.
 * @param  string $type   student|staff (optional).
 * @param  array  $extra  disable search terms: array( 'NoSearchTerms' => true ) (optional).
 *
 * @return string         (Custom) Field SQL WHERE
 */
function SearchField( $field, $type = 'student', $extra = [] )
{
	global $_ROSARIO;

	// No empty values.
	if ( ! is_array( $field )
		|| $field['VALUE'] === '' )
	{
		return '';
	}

	$no_search_terms = isset( $extra['NoSearchTerms'] ) && $extra['NoSearchTerms'];

	if ( ! $no_search_terms )
	{
		$_ROSARIO['SearchTerms'] .= '<b>' . $field['TITLE'] . ':</b> ';
	}

	// @since 10.0 SQL rename $field COLUMN (reserved keyword) to COLUMN_NAME for MySQL compatibility
	// Keep backward compatibility with COLUMN.
	$column = isset( $field['COLUMN'] ) ? $field['COLUMN'] : $field['COLUMN_NAME'];

	$sql_col = 's.' . DBEscapeIdentifier( $column );

	$value = $field['VALUE'];

	switch ( $field['TYPE'] )
	{
		// Text
		// Enter '!' for No Value
		// Enter text inside double quotes "" for exact search.
		case 'text':

			// No value.
			if ( $value === '!' )
			{
				if ( ! $no_search_terms )
				{
					$_ROSARIO['SearchTerms'] .= _( 'No Value' ) . '<br />';
				}

				return ' AND (' . $sql_col . "='' OR " . $sql_col . " IS NULL) ";
			}

			// Matches "searched expression".
			if ( mb_substr( $value, 0, 1 ) === '"'
				&& mb_substr( $value, -1 ) === '"' )
			{
				if ( ! $no_search_terms )
				{
					$_ROSARIO['SearchTerms'] .= mb_substr( $value, 1, -1 ) . '<br />';
				}

				return ' AND ' . $sql_col . "='" . mb_substr( $value, 1, -1 ) . "' ";
			}

			// Starts with.
			if ( ! $no_search_terms )
			{
				$_ROSARIO['SearchTerms'] .= _( 'starts with' ) . ' ' .
					DBUnescapeString( $value ) . '<br />';
			}

			return ' AND LOWER(' . $sql_col . ") LIKE '" . mb_strtolower( $value ) . "%' ";

		// Checkbox.
		case 'radio':

			// Yes.
			if ( $value == 'Y' )
			{
				if ( ! $no_search_terms )
				{
					$_ROSARIO['SearchTerms'] .= _( 'Yes' ) . '<br />';
				}

				return ' AND ' . $sql_col . "='" . $value . "' ";
			}

			// No.
			if ( $value == 'N' )
			{
				if ( ! $no_search_terms )
				{
					$_ROSARIO['SearchTerms'] .= _( 'No' ) . '<br />';
				}

				return ' AND (' . $sql_col . "!='Y' OR " . $sql_col . " IS NULL) ";
			}

		break;

		case 'numeric':
		case 'date':

			if ( isset( $_REQUEST['cust_null'][ $column ] ) )
			{
				// No Value for Custom Dates & Number.
				if ( ! $no_search_terms )
				{
					$_ROSARIO['SearchTerms'] .= _( 'No Value' ) . '<br />';
				}

				return ' AND ' . $sql_col . " IS NULL ";
			}

			$value = preg_replace( '/[^0-9.-]+/', '', $value );

			if ( $value === '' )
			{
				return '';
			}

			if ( $field['TYPE'] === 'date'
				&& ! VerifyDate( $value ) )
			{
				return '';
			}

			// Default: compares to equal.
			$part = [
				'operator' => '=',
				'html' => '=',
			];

			if ( isset( $field['PART'] ) )
			{
				if ( $field['PART'] === 'begin' )
				{
					// Begin Dates / Number.
					// Compares to greater than or equal.
					$part = [
						'operator' => '>=',
						'html' => '&ge;',
					];
				}
				elseif ( $field['PART'] === 'end' )
				{
					// End Dates / Number.
					// Compares to lower than or equal.
					$part = [
						'operator' => '<=',
						'html' => '&le;',
					];
				}
			}

			if ( ! $no_search_terms )
			{
				$_ROSARIO['SearchTerms'] .= '<span class="sizep2">' . $part['html'] . '</span> ';

				if ( $field['TYPE'] === 'date' )
				{
					$_ROSARIO['SearchTerms'] .= ProperDate( $value );
				}
				else
					$_ROSARIO['SearchTerms'] .= $value;

				$_ROSARIO['SearchTerms'] .= '<br />';
			}

			return ' AND ' . $sql_col . " " . $part['operator'] . " '" . $value . "' ";

		// Export Pull-Down.
		case 'exports':

			// No Value.
			if ( $value === '!' )
			{
				if ( ! $no_search_terms )
				{
					$_ROSARIO['SearchTerms'] .= _( 'No Value' ) . '<br />';
				}

				return ' AND (' . $sql_col . "='' OR " . $sql_col . " IS NULL) ";
			}

			if ( ! $no_search_terms )
			{
				$select_options = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $field['SELECT_OPTIONS'] ) );

				foreach ( $select_options as $option )
				{
					$option = explode( '|', $option );

					if ( $field['TYPE'] == 'exports'
						&& $option[0] !== ''
						&& $value == $option[0] )
					{
						$value = $option[0];
						break;
					}
				}

				$_ROSARIO['SearchTerms'] .= $value;
			}

			return ' AND ' . $sql_col . "='" . $value . "' ";

		// Pull-Down.
		case 'select':
		// Auto Pull-Down.
		case 'autos':

			// No Value.
			if ( $value === '!' )
			{
				if ( ! $no_search_terms )
				{
					$_ROSARIO['SearchTerms'] .= _( 'No Value' ) . '<br />';
				}

				return ' AND (' . $sql_col . "='' OR " . $sql_col . " IS NULL) ";
			}

			// Other Value.
			if ( $field['TYPE'] == 'autos'
				&& $value === '~' )
			{
				if ( ! $no_search_terms )
				{
					$_ROSARIO['SearchTerms'] .= _( 'Other Value' ) . '<br />';
				}

				$select_options = explode( "\r", str_replace( [ "\r\n", "\n" ], "\r", $field['SELECT_OPTIONS'] ) );

				$select_options_list = "'" . implode( "','", $select_options ) . "'";

				// Other value = not null && value <> select options.
				return " AND " . $sql_col . " IS NOT NULL
					AND " . $sql_col . " NOT IN (" . $select_options_list . ") ";
			}

			if ( ! $no_search_terms )
			{
				$_ROSARIO['SearchTerms'] .= $value . '<br />';
			}

			return ' AND ' . $sql_col . "='" . $value . "' ";
	}

	return '';
}
