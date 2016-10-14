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
 * @see Users & Students modules Search.inc.php files
 *
 * @global $_ROSARIO Used in Search.inc.php
 *
 * @param  string $type  student_id|staff_id|general_info|staff_fields|staff_fields_all|student_fields|student_fields_all.
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

			if ( ( isset( $_REQUEST['bottom_back'] )
					&& $_REQUEST['bottom_back'] == true )
				|| ( User( 'PROFILE' ) !== 'student'
					&& User( 'PROFILE' ) !== 'parent'
					&& $_REQUEST['search_modfunc'] ) )
			{
				unset( $_SESSION['student_id'] );
			}

			if ( isset( $_REQUEST['student_id'] )
				&& ! empty( $_REQUEST['student_id'] ) )
			{
				if ( $_REQUEST['student_id'] !== 'new'
					&& $_REQUEST['student_id'] != UserStudentID() )
				{
					if ( ! empty( $_REQUEST['school_id'] )
						&& $_REQUEST['school_id'] != UserSchool() )
					{
						$_SESSION['UserSchool'] = $_REQUEST['school_id'];
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
				|| $extra['new'] == true )
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
				$extra = array( 'profile' => $extra );
			}

			if ( ( isset( $_REQUEST['bottom_back'] )
					&& $_REQUEST['bottom_back'] == true )
				|| ( User( 'PROFILE' ) !== 'parent'
					&& $_REQUEST['search_modfunc'] ) )
			{
				unset( $_SESSION['staff_id'] );
			}

			if ( isset( $_REQUEST['staff_id'] )
				&& ! empty( $_REQUEST['staff_id'] ) )
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
				|| $extra['new'] == true )
			{
				if ( UserStaffID() )
				{
					unset( $_SESSION['staff_id'] );
				}

				$_REQUEST['next_modname'] = $_REQUEST['modname'];

				require_once 'modules/Users/Search.inc.php';
			}

		break;

		case 'general_info':

			echo '<tr><td><label for="last">' . _( 'Last Name' ) . '</label></td><td>
				<input type="text" name="last" id="last" size="30" maxlength="50" autofocus />
				</td></tr>';

			echo '<tr><td><label for="first">' . _( 'First Name' ) . '</label></td><td>
				<input type="text" name="first" id="first" size="30" maxlength="50" />
				</td></tr>';

			echo '<tr><td><label for="stuid">' . sprintf( _( '%s ID' ), Config( 'NAME' ) ) .
				'</label></td><td>
				<input type="text" name="stuid" id="stuid" size="30" maxlength="50" />
				</td></tr>';

			echo '<tr><td><label for="addr">' . _( 'Address' ) . '</label></td><td>
				<input type="text" name="addr" id="addr" size="30" maxlength="255" />
				</td></tr>';

			$list = DBGet( DBQuery( "SELECT ID,TITLE,SHORT_NAME
				FROM SCHOOL_GRADELEVELS
				WHERE SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER" ) );

			if ( $_REQUEST['advanced'] === 'Y'
				|| is_array( $extra ) )
			{
				echo '<tr><td>' . _( 'Grade Levels' ) . '</td>
				<td><label class="nobr"><input type="checkbox" name="grades_not" value="Y" />&nbsp;' .
					_( 'Not' ) . '</label> &nbsp;
				<label class="nobr"><input type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.checked,\'grades\');">&nbsp;' .
					_( 'Check All' ) . '</label>
				</td></tr>
				<tr><td></td><td>';

				$i = 0;

				foreach ( (array) $list as $value )
				{
					$checked = ( is_array( $extra ) ?
						( $extra[ $value['ID'] ] ? ' checked' : '' ) :
						( $extra == $value['ID'] ? ' checked' : '' )
					);

					echo '<label class="nobr">
					<input type="checkbox" name="grades[' . $value['ID'] . ']" value="Y"' . $checked . ' />&nbsp;' .
						$value['SHORT_NAME'] . '</label> &nbsp;';

					$i++;

					if ( $i%4 === 0 )
					{
						echo '<br /><br />';
					}
				}

				echo '</td></tr>';
			}
			else
			{
				echo '<tr><td><label for="grade">' . _( 'Grade Level' ) . '</label>
				</td><td>
				<select name="grade" id="grade">
				<option value="">' . _( 'Not Specified' ) . '</option>';

				foreach ( (array) $list as $value )
				{
					echo '<option value="' . $value['ID'] . '"' . ( $extra == $value['ID'] ? ' selected' : '' ) . '>' .
						$value['TITLE'] . '</option>';
				}

				echo '</select></td></tr>';
			}

		break;

		case 'staff_fields':
		case 'staff_fields_all':
		case 'student_fields':
		case 'student_fields_all':

			if ( $type === 'staff_fields_all' )
			{
				$categories_SQL = "SELECT sfc.ID,sfc.TITLE AS CATEGORY_TITLE,
				'CUSTOM_'||cf.ID AS COLUMN_NAME,cf.TYPE,cf.TITLE,SELECT_OPTIONS
				FROM STAFF_FIELD_CATEGORIES sfc,STAFF_FIELDS cf
				WHERE (SELECT CAN_USE
					FROM " . ( User( 'PROFILE_ID' ) ?
						"PROFILE_EXCEPTIONS WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'" :
						"STAFF_EXCEPTIONS WHERE USER_ID='" . User( 'STAFF_ID' ) . "'" ) . "
					AND MODNAME='Users/User.php&category_id='||sfc.ID)='Y'
				AND cf.CATEGORY_ID=sfc.ID
				AND NOT exists( SELECT ''
					FROM PROGRAM_USER_CONFIG
					WHERE PROGRAM='StaffFieldsSearch'
					AND TITLE=cast(cf.ID AS TEXT)
					AND USER_ID='" . User( 'STAFF_ID' ) . "' AND VALUE='Y')
				ORDER BY sfc.SORT_ORDER,sfc.TITLE,cf.SORT_ORDER,cf.TITLE";
			}
			elseif ( $type === 'staff_fields' )
			{
				$categories_SQL = "SELECT '0' AS ID,'' AS CATEGORY_TITLE,
				'CUSTOM_'||cf.ID AS COLUMN_NAME,cf.TYPE,cf.TITLE,cf.SELECT_OPTIONS
				FROM STAFF_FIELDS cf
				WHERE (SELECT CAN_USE
					FROM " . ( User( 'PROFILE_ID' ) ?
						"PROFILE_EXCEPTIONS WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'" :
						"STAFF_EXCEPTIONS WHERE USER_ID='" . User( 'STAFF_ID' ) . "'") . "
					AND MODNAME='Users/User.php&category_id='||cf.CATEGORY_ID)='Y'
				AND ((SELECT VALUE
					FROM PROGRAM_USER_CONFIG
					WHERE TITLE=cast(cf.ID AS TEXT)
					AND PROGRAM='StaffFieldsSearch'
					AND USER_ID='" . User( 'STAFF_ID' ) . "')='Y')
				ORDER BY cf.SORT_ORDER,cf.TITLE";
			}
			elseif ( $type === 'student_fields_all' )
			{
				$categories_SQL = "SELECT sfc.ID,sfc.TITLE AS CATEGORY_TITLE,
				'CUSTOM_'||cf.ID AS COLUMN_NAME,cf.TYPE,cf.TITLE,SELECT_OPTIONS
				FROM STUDENT_FIELD_CATEGORIES sfc,CUSTOM_FIELDS cf
				WHERE (SELECT CAN_USE
					FROM " . ( User( 'PROFILE_ID' ) ?
						"PROFILE_EXCEPTIONS WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'" :
						"STAFF_EXCEPTIONS WHERE USER_ID='" . User( 'STAFF_ID' ) . "'") . "
					AND MODNAME='Students/Student.php&category_id='||sfc.ID)='Y'
				AND cf.CATEGORY_ID=sfc.ID
				AND NOT exists(SELECT ''
					FROM PROGRAM_USER_CONFIG
					WHERE PROGRAM='StudentFieldsSearch'
					AND TITLE=cast(cf.ID AS TEXT)
					AND USER_ID='" . User( 'STAFF_ID' ) . "'
					AND VALUE='Y')
				ORDER BY sfc.SORT_ORDER,sfc.TITLE,cf.SORT_ORDER,cf.TITLE";
			}
			else
			{
				$categories_SQL = "SELECT '0' AS ID,'' AS CATEGORY_TITLE,
				'CUSTOM_'||cf.ID AS COLUMN_NAME,cf.TYPE,cf.TITLE,cf.SELECT_OPTIONS
				FROM CUSTOM_FIELDS cf
				WHERE (SELECT CAN_USE
					FROM " . ( User( 'PROFILE_ID' ) ?
						"PROFILE_EXCEPTIONS WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'" :
						"STAFF_EXCEPTIONS WHERE USER_ID='" . User( 'STAFF_ID' ) . "'") . "
					AND MODNAME='Students/Student.php&category_id='||cf.CATEGORY_ID)='Y'
				AND ((SELECT VALUE
					FROM PROGRAM_USER_CONFIG
					WHERE TITLE=cast(cf.ID AS TEXT)
					AND PROGRAM='StudentFieldsSearch'
					AND USER_ID='" . User( 'STAFF_ID' ) . "')='Y')
				ORDER BY cf.SORT_ORDER,cf.TITLE";
			}

			$categories_RET = ParseMLArray(
				DBGet(
					DBQuery( $categories_SQL ),
					array(),
					array( 'ID', 'TYPE' ) ),
				array( 'CATEGORY_TITLE', 'TITLE' )
			);

			if ( $type === 'staff_fields_all' )
			{
				// User Fields: search Email Address & Phone.
				$general_info_category_title_RET = DBGet( DBQuery( "SELECT sfc.TITLE
					FROM STAFF_FIELD_CATEGORIES sfc
					WHERE sfc.ID=1" ) );

				$general_info_category_title = ParseMLField( $general_info_category_title_RET[1]['TITLE'] );

				if ( isset( $categories_RET[1] ) )
				{
					$i = count( $categories_RET[1]['text'] ) ? 1 : count( $categories_RET[1]['text'] );
				}
				else
				{
					$i = 1;
				}

				if ( Preferences( 'EMAIL', 'StaffFieldsSearch' ) !== 'Y' )
				{
					if ( ! isset( $categories_RET[1] ) )
					{
						// Empty General Info category.
						$categories_RET[1] = array();
					}

					// Add Email Address to Staff General Info.
					$categories_RET[1]['text'][ $i++ ] = array(
						'ID' => '1',
						'CATEGORY_TITLE' => $general_info_category_title,
						'COLUMN_NAME' => 'EMAIL',
						'TYPE' => 'text',
						'TITLE' => _( 'Email Address' ),
						'SELECT_OPTIONS' => null,
					);
				}

				if ( Preferences( 'PHONE', 'StaffFieldsSearch' ) !== 'Y' )
				{
					if ( ! isset( $categories_RET[1] ) )
					{
						// Empty General Info category.
						$categories_RET[1] = array();
					}

					// Add Phone Number to Staff General Info.
					$categories_RET[1]['text'][ $i++ ] = array(
						'ID' => '1',
						'CATEGORY_TITLE' => $general_info_category_title,
						'COLUMN_NAME' => 'PHONE',
						'TYPE' => 'text',
						'TITLE' => _( 'Phone Number' ),
						'SELECT_OPTIONS' => null,
					);
				}
			}
			elseif ( $type === 'staff_fields' )
			{
				if ( Preferences( 'EMAIL', 'StaffFieldsSearch' ) === 'Y' )
				{
					// Add Email Address to Find a User form.
					$categories_RET[1]['text'][ $i++ ] = array(
						'ID' => '1',
						'CATEGORY_TITLE' => '',
						'COLUMN_NAME' => 'EMAIL',
						'TYPE' => 'text',
						'TITLE' => _( 'Email Address' ),
						'SELECT_OPTIONS' => null,
					);
				}

				if ( Preferences( 'PHONE', 'StaffFieldsSearch' ) === 'Y' )
				{
					// Add Phone Number to Find a User form.
					$categories_RET[1]['text'][ $i++ ] = array(
						'ID' => '1',
						'CATEGORY_TITLE' => '',
						'COLUMN_NAME' => 'PHONE',
						'TYPE' => 'text',
						'TITLE' => _( 'Phone Number' ),
						'SELECT_OPTIONS' => null,
					);
				}
			}

			foreach ( (array) $categories_RET as $category )
			{
				$TR_classes = '';

				if ( $type === 'student_fields_all'
					|| $type === 'staff_fields_all' )
				{
					echo '<a onclick="switchMenu(this); return false;" href="#" class="switchMenu">
					<b>' . $category[ key( $category ) ][1]['CATEGORY_TITLE'] . '</b></a>
					<br />
					<table class="widefat width-100p cellspacing-0 col1-align-right hide">';

					$TR_classes .= 'st';
				}

				// Text.
				foreach ( (array) $category['text'] as $col )
				{
					$name = 'cust[' . $col['COLUMN_NAME'] . ']';

					$id = GetInputID( $name );

					echo '<tr class="' . $TR_classes . '"><td>
					<label for="' . $id . '">' . $col['TITLE'] . '</label>
					</td><td>
					<input type="text" name="' . $name . '" id="' . $id . '" size="30" maxlength="255" />
					</td></tr>';
				}

				// Numeric.
				foreach ( (array) $category['numeric'] as $col )
				{
					echo '<tr class="' . $TR_classes . '"><td>' . $col['TITLE'] . '</td><td>
					<span class="sizep2">&ge;</span>
					<input type="text" name="cust_begin[' . $col['COLUMN_NAME'] . ']" size="3" maxlength="11" />
					<span class="sizep2">&le;</span>
					<input type="text" name="cust_end[' . $col['COLUMN_NAME'] . ']" size="3" maxlength="11" />
					<label>' . _( 'No Value' ) .
					' <input type="checkbox" name="cust_null[' . $col['COLUMN_NAME'] . ']" /></label>&nbsp;
					</td></tr>';
				}

				// Merge select, autos, edits, exports & codeds
				// (same or similar SELECT output).
				$category['select_autos_edits_exports_codeds'] = array_merge(
					(array) $category['select'],
					(array) $category['autos'],
					(array) $category['edits'],
					(array) $category['exports'],
					(array) $category['codeds']
				);

				// Select.
				foreach ( (array) $category['select_autos_edits_exports_codeds'] as $col )
				{
					$options = array();

					$col_name = $col['COLUMN_NAME'];

					if ( $col['SELECT_OPTIONS'] )
					{
						$options = explode(
							"\r",
							str_replace( array( "\r\n", "\n" ), "\r", $col['SELECT_OPTIONS'] )
						);
					}

					$name = 'cust[' . $col_name . ']';

					$id = GetInputID( $name );

					echo '<tr class="' . $TR_classes . '">
					<td><label for="' . $id . '">' . $col['TITLE'] . '</label></td><td>
					<select name="' . $name . '" id="' . $id . '">
						<option value="">' . _( 'N/A' ) . '</option>
						<option value="!">' . _( 'No Value' ) . '</option>';

					foreach ( (array) $options as $option )
					{
						$value = $option;

						// Exports specificities.
						if ( $col['TYPE'] === 'exports' )
						{
							$option = explode( '|', $option );

							$option = $value = $option[0];
						}
						// Codeds specificities.
						elseif ( $col['TYPE'] === 'codeds' )
						{
							list( $value, $option ) = explode( '|', $option );
						}

						if ( $value !== ''
							&& $option !== '' )
						{
							echo '<option value="' . $value . '">' . $option . '</option>';
						}
					}

					// Edits specificities.
					if ( $col['TYPE'] === 'edits' )
						echo '<option value="~">' . _( 'Other Value' ) . '</option>';

					// Get autos / edits pull-down edited options.
					if ( $col['TYPE'] === 'autos'
						|| $col['TYPE'] === 'edits' )
					{
						if ( mb_strpos( $type, 'student' ) !== false )
						{
							$sql_options = "SELECT DISTINCT s." . $col_name . ",upper(s." . $col_name . ") AS SORT_KEY
								FROM STUDENTS s,STUDENT_ENROLLMENT sse
								WHERE sse.STUDENT_ID=s.STUDENT_ID
								AND sse.SYEAR='" . UserSyear() . "'
								AND s." . $col_name . " IS NOT NULL
								AND s." . $col_name . " != ''
								ORDER BY SORT_KEY";
						}
						else // Staff.
						{
							$sql_options = "SELECT DISTINCT s." . $col_name . ",upper(s." . $col_name . ") AS KEY
								FROM STAFF s WHERE s.SYEAR='" . UserSyear() . "'
								AND s." . $col_name . " IS NOT NULL
								AND s." . $col_name . " != ''
								ORDER BY KEY";
						}

						$options_RET = DBGet( DBQuery( $sql_options ) );

						// Add the 'new' option, is also the separator.
						echo '<option value="---">-' . _( 'Edit' ) . '-</option>';

						foreach ( (array) $options_RET as $option )
						{
							if ( ! in_array( $option[ $col_name ], $options ) )
							{
								echo '<option value="' . $option[ $col_name ] . '">' .
									$option[ $col_name ] . '</option>';
							}
						}
					}

					echo '</select></td></tr>';
				}

				// Date.
				foreach ( (array) $category['date'] as $col )
				{
					echo '<tr class="' . $TR_classes . '"><td>' . $col['TITLE'] . '<br />
					<label>' . _( 'No Value' ) .
					'&nbsp;<input type="checkbox" name="cust_null[' . $col['COLUMN_NAME'] . ']" /></label>
					</td>
					<td><table class="cellspacing-0">
					<tr><td><span class="sizep2">&ge;</span>&nbsp;</td>
					<td>' . PrepareDate(
						'',
						'_cust_begin[' . $col['COLUMN_NAME'] . ']',
						true,
						array( 'short' => true )
					) . '</td></tr>
					<tr><td><span class="sizep2">&le;</span>&nbsp;</td>
					<td>' . PrepareDate(
						'',
						'_cust_end[' . $col['COLUMN_NAME'] . ']',
						true,
						array( 'short' => true )
					) . '</td></tr>
					</table></td></tr>';
				}

				// Radio.
				foreach ( (array) $category['radio'] as $col )
				{
					$name = 'cust[' . $col['COLUMN_NAME'] . ']';

					$id = GetInputID( $name );

					echo '<tr class="' . $TR_classes . '"><td>' . $col['TITLE'] . '</td>
					<td><table class="cellspacing-0">
					<tr><td><label for="' . $id . '">' . _( 'All' ) . '</label></td>
					<td><label for="' . $id . '_Y">' . _( 'Yes' ) . '</label></td>
					<td><label for="' . $id . '_N">' . _( 'No' ) . '</label></td></tr>
					<tr class="center"><td>
					<input name="' . $name . '" id="' . $id . '" type="radio" value="" checked />
					</td><td>
					<input name="' . $name . '" id="' . $id . '_Y" type="radio" value="Y" />
					</td><td>
					<input name="' . $name . '" id="' . $id . '_N" type="radio" value="N" />
					</td></tr></table></td></tr>';
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
