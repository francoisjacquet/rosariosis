<?php

function Search( $type, $extra = null )
{
	global $_ROSARIO,
		$modname;

	switch( $type )
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
				&& !empty( $_REQUEST['student_id'] ) )
			{
				if ( $_REQUEST['student_id'] !== 'new'
					&& $_REQUEST['student_id'] != UserStudentID() )
				{
					if ( !empty( $_REQUEST['school_id'] )
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
			elseif ( !UserStudentID()
				|| $extra['new'] == true )
			{
				if ( UserStudentID() )
				{
					//FJ fix bug no student found when student/parent logged in
					if ( User('PROFILE') !== 'student'
						&& User( 'PROFILE' ) !== 'parent' )
					{
						unset( $_SESSION['student_id'] );
					}
				}

				$_REQUEST['next_modname'] = $_REQUEST['modname'];

				include( 'modules/Students/Search.inc.php' );
			}

		break;

		case 'staff_id':

			// convert profile string to array for legacy compatibility
			if ( !is_array( $extra ) )
				$extra = array( 'profile' => $extra );

			if ( ( isset( $_REQUEST['bottom_back'] )
					&& $_REQUEST['bottom_back'] == true )
				|| ( User( 'PROFILE' ) !== 'parent'
					&& $_REQUEST['search_modfunc'] ) )
			{
				unset( $_SESSION['staff_id'] );
			}

			if ( isset( $_REQUEST['staff_id'] )
				&& !empty( $_REQUEST['staff_id'] ) )
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
			elseif ( !UserStaffID()
				|| $extra['new'] == true )
			{
				if ( UserStaffID() )
				{
					unset( $_SESSION['staff_id'] );
				}
					
				$_REQUEST['next_modname'] = $_REQUEST['modname'];
				
				include( 'modules/Users/Search.inc.php' );
			}

		break;

		case 'general_info':

			echo '<TR><TD><label for="last">' . _( 'Last Name' ) . '</label></TD>
				<TD><input type="text" name="last" id="last" size="30" maxlength="50" /></TD></TR>';

			echo '<TR><TD><label for="first">' . _( 'First Name' ) . '</label></TD>
				<TD><input type="text" name="first" id="first" size="30" maxlength="50" /></TD></TR>';

			echo '<TR><TD><label for="stuid">' . sprintf( _( '%s ID' ), Config( 'NAME' ) ) . '</label></TD>
				<TD><input type="text" name="stuid" id="stuid" size="30" maxlength="50" /></TD></TR>';

			echo '<TR><TD><label for="addr">' . _( 'Address' ) . '</label></TD>
				<TD><input type="text" name="addr" id="addr" size="30" maxlength="255" /></TD></TR>';

			$list = DBGet( DBQuery( "SELECT ID,TITLE,SHORT_NAME
				FROM SCHOOL_GRADELEVELS
				WHERE SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SORT_ORDER" ) );

			if ( $_REQUEST['advanced'] === 'Y'
				|| is_array( $extra ) )
			{
				echo '<TR><TD>' . _( 'Grade Levels' ) . '</TD>
				<TD><label class="nobr"><INPUT type="checkbox" name="grades_not" value="Y" />&nbsp;' . _( 'Not' ) . '</label>
				<BR />
				<label class="nobr"><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'grades[\');">&nbsp;' . _( 'Check All' ) . '</label>
				</TD></TR>
				<TR><TD colspan="2">';

				foreach ( (array)$list as $value )
				{
					$checked = ( is_array( $extra ) ? ( $extra[ $value['ID'] ] ? ' checked' : '' ) : ( $extra == $value['ID'] ? ' checked' : '' ) );

					echo '<label class="nobr"><INPUT type="checkbox" name="grades[' . $value['ID'] . ']" value="Y"' . $checked . ' />&nbsp;' . $value['SHORT_NAME'] . '</label> ';
				}

				echo '</TD></TR>';
			}
			else
			{
				echo '<TR><TD><label for="grade">' . _( 'Grade Level' ) . '</label>
				</TD><TD>
				<SELECT name="grade" id="grade"><OPTION value="">' . _( 'Not Specified' ) . '</OPTION>';

				foreach ( (array)$list as $value )
				{
					echo '<OPTION value="' . $value['ID'] . '"' . ( $extra == $value['ID'] ? ' SELECTED' : '' ) . '>' .
						$value['TITLE'] . '</OPTION>';
				}

				echo '</SELECT></TD></TR>';
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
						"PROFILE_EXCEPTIONS WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'":
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
						"PROFILE_EXCEPTIONS WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'":
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
						"PROFILE_EXCEPTIONS WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'":
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

			foreach ( (array)$categories_RET as $category )
			{
				$TR_classes = '';

				if ( $type === 'student_fields_all'
					|| $type === 'staff_fields_all' )
				{
					echo '<TR><TD colspan="2">
					<TABLE class="width-100p">
					<TR><TD colspan="2">&nbsp;<A onclick="switchMenu(this); return false;" href="#" class="switchMenu"><B>' . $category[key($category)][1]['CATEGORY_TITLE'] . '</B></A>
					<BR />
					<TABLE class="widefat width-100p cellspacing-0 col1-align-right hide">';

					$TR_classes .= 'st';
				}

				foreach ( (array)$category['text'] as $col )
				{
					$name = 'cust[' . $col['COLUMN_NAME'] . ']';

					$id = GetInputID( $name );

					echo '<TR class="' . $TR_classes . '"><TD>
					<label for="' . $id . '">' . $col['TITLE'] . '</label>
					</TD><TD>
					<INPUT type="text" name="' . $name . '" id="' . $id . '" size="30" maxlength="255" />
					</TD></TR>';
				}

				foreach ( (array)$category['numeric'] as $col )
				{
					echo '<TR class="' . $TR_classes . '"><TD>' . $col['TITLE'] . '</TD><TD>
					<span class="sizep2">&ge;</span> <INPUT type="text" name="custb[' . $col['COLUMN_NAME'] . ']" size="3" maxlength="11" /> 
					<span class="sizep2">&le;</span> <INPUT type="text" name="custe[' . $col['COLUMN_NAME'] . ']" size="3" maxlength="11" /> 
					<label>' . _( 'No Value' ) . ' <INPUT type="checkbox" name="custn[' . $col['COLUMN_NAME'] . ']" /></label>&nbsp;
					</TD></TR>';
				}

				// merge select, autos, edits, exports & codeds
				// (same or similar SELECT output)
				$category['select_autos_edits_exports_codeds'] = array_merge(
					(array)$category['select'],
					(array)$category['autos'],
					(array)$category['edits'],
					(array)$category['exports'],
					(array)$category['codeds']
				);

				foreach ( (array)$category['select_autos_edits_exports_codeds'] as $col )
				{
					$options = array();

					$col_name = $col['COLUMN_NAME'];

					if ( $col['SELECT_OPTIONS'] )
					{
						$options = explode(
							'<br />',
							nl2br( $col['SELECT_OPTIONS'] )
						);
					}

					$name = 'cust[' . $col_name . ']';

					$id = GetInputID( $name );

					echo '<TR class="' . $TR_classes . '">
					<TD><label for="' . $id . '">' . $col['TITLE'] . '</label></TD><TD>
					<SELECT name="' . $name . '" id="' . $id . '">
						<OPTION value="">' . _( 'N/A' ) . '</OPTION>
						<OPTION value="!">' . _( 'No Value' ) . '</OPTION>';

					foreach ( (array)$options as $option )
					{
						$value = $option;

						// exports specificities
						if ( $col['TYPE'] === 'exports' )
						{
							$option = explode( '|', $option );

							$option = $value = $option[0];
						}
						// codeds specificities
						elseif ( $col['TYPE'] === 'codeds' )
						{
							list( $value, $option ) = explode( '|', $option );
						}

						if ( $value !== ''
							&& $option !== '' )
							echo '<OPTION value="' . $value . '">' . $option . '</OPTION>';
					}

					// edits specificities
					if ( $col['TYPE'] === 'edits' )
						echo '<OPTION value="~">' . _( 'Other Value' ) . '</OPTION>';

					// Get autos / edits pull-down edited options
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
						else // staff
						{
							$sql_options = "SELECT DISTINCT s." . $col_name . ",upper(s." . $col_name . ") AS KEY
								FROM STAFF s WHERE s.SYEAR='" . UserSyear() . "'
								AND s." . $col_name . " IS NOT NULL
								AND s." . $col_name . " != ''
								ORDER BY KEY";
						}

						$options_RET = DBGet( DBQuery( $sql_options ) );

						// add the 'new' option, is also the separator
						echo '<OPTION value="---">-' . _( 'Edit' ) . '-</OPTION>';

						foreach ( (array)$options_RET as $option )
							if ( !in_array( $option[$col_name], $options ) )
								echo '<OPTION value="' . $option[$col_name] . '">' . $option[$col_name] . '</OPTION>';
					}

					echo '</SELECT></TD></TR>';
				}

				foreach ( (array)$category['date'] as $col )
				{
					echo '<TR class="' . $TR_classes . '"><TD>' . $col['TITLE'] . '<BR />
					<label>' . _( 'No Value' ) . '&nbsp;<INPUT type="checkbox" name="custn[' . $col['COLUMN_NAME'] . ']" /></label>
					</TD>
					<TD><table class="cellspacing-0">
					<tr><td><span class="sizep2">&ge;</span>&nbsp;</td>
					<td>' . PrepareDate( '', '_custb[' . $col['COLUMN_NAME'] . ']', true, array( 'short' => true ) ) . '</td></tr>
					<tr><td><span class="sizep2">&le;</span>&nbsp;</td>
					<td>' . PrepareDate( '', '_custe[' . $col['COLUMN_NAME'] . ']', true, array( 'short' => true ) ) . '</td></tr>
					</table></TD></TR>';
				}

				foreach ( (array)$category['radio'] as $col )
				{
					$name = 'cust[' . $col['COLUMN_NAME'] . ']';

					$id = GetInputID( $name );

					echo '<TR class="' . $TR_classes . '"><TD>' . $col['TITLE'] . '</TD>
					<TD><TABLE class="cellspacing-0">
					<tr><td><label for="' . $id . '">' . _( 'All' ) . '</label></td>
					<td><label for="' . $id . '_Y">' . _( 'Yes' ) . '</label></td>
					<td><label for="' . $id . '_N">' . _( 'No' ) . '</label></td></tr>
					<tr class="center"><td>
					<input name="' . $name . '" id="' . $id . '" type="radio" value="" checked />
					</td><td>
					<input name="' . $name . '" id="' . $id . '_Y" type="radio" value="Y" />
					</td><td>
					<input name="' . $name . '" id="' . $id . '_N" type="radio" value="N" />
					</td></tr></table></TD></TR>';
				}

				if ( $type === 'student_fields_all'
					|| $type === 'staff_fields_all' )
					echo '</TABLE></TD></TR></TABLE></TD></TR>';
			}

		break;
	}
}
