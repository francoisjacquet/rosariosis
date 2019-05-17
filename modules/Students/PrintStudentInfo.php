<?php

if ( $_REQUEST['modfunc'] === 'save'
	&& AllowEdit() )
{
	if ( ! empty( $_REQUEST['st_arr'] ) )
	{
		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

		$extra['functions'] = array( 'GRADE_ID' => '_grade_id' );

		if ( $_REQUEST['mailing_labels'] === 'Y' )
		{
			Widgets( 'mailing_labels' );
		}

		$RET = GetStuList( $extra );

		if ( ! empty( $RET ) )
		{
			require_once 'ProgramFunctions/StudentsUsersInfo.fnc.php';
			$categories_RET = DBGet( "SELECT ID,TITLE,INCLUDE
				FROM STUDENT_FIELD_CATEGORIES
				ORDER BY SORT_ORDER,TITLE", array(), array( 'ID' ) );

			// get the address and contacts custom fields, create the select lists and expand select and codeds options
			$address_categories_RET = DBGet( "SELECT c.ID AS CATEGORY_ID,c.TITLE AS CATEGORY_TITLE,
				c.RESIDENCE,c.MAILING,c.BUS,f.ID,f.TITLE,f.TYPE,f.SELECT_OPTIONS,f.DEFAULT_SELECTION,f.REQUIRED
				FROM ADDRESS_FIELD_CATEGORIES c,ADDRESS_FIELDS f
				WHERE f.CATEGORY_ID=c.ID
				ORDER BY c.SORT_ORDER,c.TITLE,f.SORT_ORDER,f.TITLE", array(), array( 'CATEGORY_ID' ) );

			$people_categories_RET = DBGet( "SELECT c.ID AS CATEGORY_ID,c.TITLE AS CATEGORY_TITLE,
				c.CUSTODY,c.EMERGENCY,f.ID,f.TITLE,f.TYPE,f.SELECT_OPTIONS,f.DEFAULT_SELECTION,f.REQUIRED
				FROM PEOPLE_FIELD_CATEGORIES c,PEOPLE_FIELDS f
				WHERE f.CATEGORY_ID=c.ID
				ORDER BY c.SORT_ORDER,c.TITLE,f.SORT_ORDER,f.TITLE", array(), array( 'CATEGORY_ID' ) );

			explodeCustom( $address_categories_RET, $address_custom, 'a' );
			explodeCustom( $people_categories_RET, $people_custom, 'p' );

			$_REQUEST['modfunc'] = false;

			$handle = PDFStart();

			foreach ( (array) $RET as $student )
			{
				SetUserStudentID( $student['STUDENT_ID'] );

				unset( $_ROSARIO['DrawHeader'] );

				if ( $_REQUEST['mailing_labels'] == 'Y' )
				{
					echo '<br /><br /><br />';
				}

				//School logo
				$logo_pic = 'assets/school_logo_' . UserSchool() . '.jpg';

				if ( file_exists( $logo_pic ) )
				{
					DrawHeader( _( 'Student Info' ), '<img src="' . $logo_pic . '" width="280" />' );
				}
				else
				{
					DrawHeader( _( 'Student Info' ), '<div style="height:100px;">&nbsp;</div>' );
				}

				DrawHeader( $student['FULL_NAME'], SchoolInfo( 'TITLE' ) );
				DrawHeader( $student['STUDENT_ID'], GetGrade( $student['GRADE_ID'] ) );
				DrawHeader( ProperDate( DBDate() ) );

				if ( $_REQUEST['mailing_labels'] == 'Y' )
				{
					echo '<br /><br /><table class="width-100p"><tr><td style="width:50px;"> &nbsp; </td><td>' . $student['MAILING_LABEL'] . '</td></tr></table><br />';
				}

				if ( ! empty( $_REQUEST['category']['1'] ) )
				{
					require 'modules/Students/includes/General_Info.inc.php';
					echo '<div style="page-break-after: always;"></div>';
				}

				if ( ! empty( $_REQUEST['category']['3'] ) )
				{
					$_ROSARIO['DrawHeader'] = '';

					DrawHeader( ParseMLField( $categories_RET['3'][1]['TITLE'] ) );

					echo '<br />';

					$addresses_RET = DBGet( "SELECT a.ADDRESS_ID,sjp.STUDENT_RELATION,a.ADDRESS,
						a.CITY,a.STATE,a.ZIPCODE,a.PHONE,a.MAIL_ADDRESS,a.MAIL_CITY,a.MAIL_STATE,
						A.MAIL_ZIPCODE,sjp.CUSTODY,sja.MAILING,sja.RESIDENCE,sja.BUS_PICKUP,
						sja.BUS_DROPOFF," . db_case( array( 'a.ADDRESS_ID', "'0'", '1', '0' ) ) . "AS SORT_ORDER" . $address_custom .
						" FROM ADDRESS a,STUDENTS_JOIN_ADDRESS sja,STUDENTS_JOIN_PEOPLE sjp
						WHERE a.ADDRESS_ID=sja.ADDRESS_ID
						AND sja.STUDENT_ID='" . UserStudentID() . "'
						AND a.ADDRESS_ID=sjp.ADDRESS_ID
						AND sjp.STUDENT_ID=sja.STUDENT_ID
						UNION
						SELECT a.ADDRESS_ID,'No Contacts' AS STUDENT_RELATION,a.ADDRESS,a.CITY,a.STATE,a.ZIPCODE,a.PHONE,a.MAIL_ADDRESS,a.MAIL_CITY,a.MAIL_STATE,A.MAIL_ZIPCODE,'' AS CUSTODY,sja.MAILING,sja.RESIDENCE,sja.BUS_PICKUP,sja.BUS_DROPOFF," . db_case( array( 'a.ADDRESS_ID', "'0'", '1', '0' ) ) . " AS SORT_ORDER" . $address_custom .
						" FROM ADDRESS a,STUDENTS_JOIN_ADDRESS sja
						WHERE a.ADDRESS_ID=sja.ADDRESS_ID
						AND sja.STUDENT_ID='" . UserStudentID() . "'
						AND NOT EXISTS (SELECT '' FROM STUDENTS_JOIN_PEOPLE sjp
							WHERE sjp.STUDENT_ID=sja.STUDENT_ID
							AND sjp.ADDRESS_ID=a.ADDRESS_ID)
						ORDER BY SORT_ORDER,RESIDENCE,CUSTODY,STUDENT_RELATION" );

					$address_previous = "x";

					foreach ( (array) $addresses_RET as $address )
					{
						$address_current = $address['ADDRESS'];

						if ( $address_current != $address_previous )
						{
							echo $address['ADDRESS'] . '<br />' . ( $address['CITY'] ? $address['CITY'] . ', ' : '' ) . $address['STATE'] . ( $address['ZIPCODE'] ? ' ' . $address['ZIPCODE'] : '' ) . '<br />';

							foreach ( (array) $address_categories_RET as $categories )
							{
								echo '<br /><table>';

								if ( ! $categories[1]['RESIDENCE'] && ! $categories[1]['MAILING'] && ! $categories[1]['BUS'] || $categories[1]['RESIDENCE'] == 'Y' && $address['RESIDENCE'] == 'Y' || $categories[1]['MAILING'] == 'Y' && $address['MAILING'] == 'Y' || $categories[1]['BUS'] == 'Y' && ( $address['BUS_PICKUP'] == 'Y' || $address['BUS_DROPOFF'] == 'Y' ) )
								{
									printCustom( $categories, $address );
								}

								echo '</table>';
							}

							$contacts_RET = DBGet( "SELECT p.PERSON_ID,p.FIRST_NAME,
							p.MIDDLE_NAME,p.LAST_NAME,sjp.CUSTODY,sjp.EMERGENCY,
							sjp.STUDENT_RELATION" . $people_custom . "
							FROM PEOPLE p,STUDENTS_JOIN_PEOPLE sjp
							WHERE p.PERSON_ID=sjp.PERSON_ID
							AND sjp.STUDENT_ID='" . UserStudentID() . "'
							AND sjp.ADDRESS_ID='" . $address['ADDRESS_ID'] . "'" );

							foreach ( (array) $contacts_RET as $contact )
							{
								echo '<br /><b>' . DisplayName(
									$contact['FIRST_NAME'],
									$contact['LAST_NAME'],
									$contact['MIDDLE_NAME']
								) .
									( $contact['STUDENT_RELATION'] ? ': ' . $contact['STUDENT_RELATION'] : '' ) .
									' &nbsp;</b><br />';

								$info_RET = DBGet( "SELECT ID,TITLE,VALUE
								FROM PEOPLE_JOIN_CONTACTS
								WHERE PERSON_ID='" . $contact['PERSON_ID'] . "'" );

								echo '<table>';

								foreach ( (array) $info_RET as $info )
								{
									echo '<tr><td>&nbsp;</td>';
									echo '<td>' . $info['TITLE'] . '</td>';
									echo '<td>' . $info['VALUE'] . '</td>';
									echo '</tr>';
								}

								foreach ( (array) $people_categories_RET as $categories )
								{
									if ( ! $categories[1]['CUSTODY'] && ! $categories[1]['EMERGENCY'] || $categories[1]['CUSTODY'] == 'Y' && $contact['CUSTODY'] == 'Y' || $categories[1]['EMERGENCY'] == 'Y' && $contact['EMERGENCY'] == 'Y' )
									{
										printCustom( $categories, $contact );
									}
								}

								echo '</table>';
							}

							echo '<br />&nbsp;<br />';
						}

						$address_previous = $address_current;
					}

					echo '<div style="page-break-after: always;"></div>';
				}

				if ( ! empty( $_REQUEST['category']['2'] ) )
				{
					$_ROSARIO['DrawHeader'] = '';
					DrawHeader( ParseMLField( $categories_RET['2'][1]['TITLE'] ) );
					echo '<br />';
					require 'modules/Students/includes/Medical.inc.php';
					echo '<div style="page-break-after: always;"></div>';
				}

				if ( ! empty( $_REQUEST['category']['4'] ) )
				{
					$_ROSARIO['DrawHeader'] = '';
					DrawHeader( ParseMLField( $categories_RET['4'][1]['TITLE'] ) );
					echo '<br />';
					require 'modules/Students/includes/Comments.inc.php';
					echo '<div style="page-break-after: always;"></div>';
				}

				foreach ( (array) $categories_RET as $id => $category )
				{
					if ( $id != '1' && $id != '3' && $id != '2' && $id != '4' && $_REQUEST['category'][$id] )
					{
						$_REQUEST['category_id'] = $id;
						$_ROSARIO['DrawHeader'] = '';
						DrawHeader( ParseMLField( $category[1]['TITLE'] ) );
						echo '<br />';
						$separator = '';

						if ( ! $category[1]['INCLUDE'] )
						{
							require 'modules/Students/includes/Other_Info.inc.php';
						}
						elseif ( ! mb_strpos( $category[1]['INCLUDE'], '/' ) )
						{
							require 'modules/Students/includes/' . $category[1]['INCLUDE'] . '.inc.php';
						}
						else
						{
							if ( file_exists( 'plugins/' . $category[1]['INCLUDE'] . '.inc.php' ) )
							{
								// @since 4.5 Include Student/User Info tab from custom plugin.
								require 'plugins/' . $category[1]['INCLUDE'] . '.inc.php';
							}
							else
							{
								require 'modules/' . $category[1]['INCLUDE'] . '.inc.php';
							}

							$separator = '<hr />';
							require 'modules/Students/includes/Other_Info.inc.php';
						}

						echo '<div style="page-break-after: always;"></div>';
					}
				}
			}

			PDFStop( $handle );
		}
		else
		{
			BackPrompt( _( 'No Students were found.' ) );
		}
	}
	else
	{
		BackPrompt( _( 'You must choose at least one student.' ) );
	}

	unset( $_SESSION['student_id'] );
	//echo '<pre>'; var_dump($_REQUEST['modfunc']); echo '</pre>';
	$_REQUEST['modfunc'] = true;
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&include_inactive=' . $_REQUEST['include_inactive'] . '&_search_all_schools=' . $_REQUEST['_search_all_schools'] . '&_ROSARIO_PDF=true" method="POST">';
		$extra['header_right'] = SubmitButton( _( 'Print Info for Selected Students' ) );

		$extra['extra_header_left'] = '<table>';
		Widgets( 'mailing_labels' );
		$extra['extra_header_left'] .= $extra['search'];
		$extra['search'] = '';
		$extra['extra_header_left'] .= '</table>';

		if ( User( 'PROFILE_ID' ) )
		{
			$can_use_RET = DBGet( "SELECT MODNAME
				FROM PROFILE_EXCEPTIONS
				WHERE PROFILE_ID='" . User( 'PROFILE_ID' ) . "'
				AND CAN_USE='Y'", array(), array( 'MODNAME' ) );
		}
		else
		{
			$can_use_RET = DBGet( "SELECT MODNAME
				FROM STAFF_EXCEPTIONS
				WHERE USER_ID='" . User( 'STAFF_ID' ) . "'
				AND CAN_USE='Y'", array(), array( 'MODNAME' ) );
		}

		$categories_RET = DBGet( "SELECT ID,TITLE,INCLUDE
			FROM STUDENT_FIELD_CATEGORIES
			ORDER BY SORT_ORDER,TITLE" );

		$extra['extra_header_right'] = '<table>';

		foreach ( (array) $categories_RET as $category )
		{
			if ( $can_use_RET['Students/Student.php&category_id=' . $category['ID']] )
			{
				$extra['extra_header_right'] .= '<tr><td><label>' . ParseMLField( $category['TITLE'] ) . '&nbsp;<input type="checkbox" name="category[' . $category['ID'] . ']" value="Y" checked /></label></td></tr>';
			}
		}

		$extra['extra_header_right'] .= '</table>';
	}

	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' ) );
	$extra['options']['search'] = false;
	$extra['new'] = true;

	//Widgets('course');
	//Widgets('request');
	//Widgets('activity');
	//Widgets('absences');
	//Widgets('gpa');
	//Widgets('class_rank');
	//Widgets('letter_grade');
	//Widgets('eligibility');

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . SubmitButton( _( 'Print Info for Selected Students' ) ) . '</div>';
		echo '</form>';
	}
}

// GetStuList by default translates the grade_id to the grade title which we don't want here.
// One way to avoid this is to provide a translation function for the grade_id so here we
// provide a passthru function just to avoid the translation.
/**
 * @param $value
 * @return mixed
 */
function _grade_id( $value )
{
	return $value;
}


/**
 * @param $categories_RET
 * @param $custom
 * @param $prefix
 */
function explodeCustom( &$categories_RET, &$custom, $prefix )
{
	foreach ( (array) $categories_RET as $id => $category )
	{
		foreach ( (array) $category as $i => $field )
		{
			$custom .= ',' . $prefix . '.CUSTOM_' . $field['ID'];

			if ( $field['TYPE'] == 'select' )
			{
				$select_options = explode( "\r", str_replace( array( "\r\n", "\n" ), "\r", $field['SELECT_OPTIONS'] ) );

				$options = array();

				foreach ( (array) $select_options as $option )
				{
					$options[$option] = $option;
				}

				$categories_RET[$id][$i]['SELECT_OPTIONS'] = $options;
			}
		}
	}
}

/**
 * @param $categories
 * @param $values
 */
function printCustom( &$categories, &$values )
{
	echo '<tr><td colspan="3"><b>' . ParseMLField( $categories[1]['CATEGORY_TITLE'] ) . '<b></td></tr>';

	foreach ( (array) $categories as $field )
	{
		echo '<tr><td>&nbsp;</td>';
		echo '<td>' . ( $field['REQUIRED'] && $values['CUSTOM_' . $field['ID']] == '' ? '<span style="color:red">' : '' ) . ParseMLField( $field['TITLE'] ) . ( $field['REQUIRED'] && $values['CUSTOM_' . $field['ID']] == '' ? '</span>' : '' ) . '</td>';

		if ( $field['TYPE'] == 'select' )
		{
			echo '<td>' . ( $field['SELECT_OPTIONS'][$values['CUSTOM_' . $field['ID']]] != '' ? '' : '<span style="color:red">' ) . $values['CUSTOM_' . $field['ID']] . ( $field['SELECT_OPTIONS'][$values['CUSTOM_' . $field['ID']]] != '' ? '' : '</span>' ) . '</td>';
		}
		elseif ( $field['TYPE'] == 'codeds' )
		{
			echo '<td>' . ( $field['SELECT_OPTIONS'][$values['CUSTOM_' . $field['ID']]] != '' ? $field['SELECT_OPTIONS'][$values['CUSTOM_' . $field['ID']]] : '<span style="color:red">' . $values['CUSTOM_' . $field['ID']] . '</span>' ) . '</td>';
		}
		else
		{
			echo '<td>' . $values['CUSTOM_' . $field['ID']] . '</td>';
		}

		echo '</tr>';
	}
}
