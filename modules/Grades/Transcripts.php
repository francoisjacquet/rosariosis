<?php

// Should be included first, in case modfunc is Class Rank Calculate AJAX.
require_once 'modules/Grades/includes/ClassRank.inc.php';

require_once 'ProgramFunctions/Template.fnc.php';
require_once 'ProgramFunctions/Substitutions.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['mp_type_arr'] )
		&& ! empty( $_REQUEST['st_arr'] ) )
	{
		// Limit School & Year to current ones if not admin.
		$syear_list = ( User( 'PROFILE' ) === 'admin' && $_REQUEST['syear_arr'] ?
			"'" . implode( "','", $_REQUEST['syear_arr'] ) . "'" :
			"'" . UserSyear() . "'" );

		$school_id = ( User( 'PROFILE' ) === 'admin' && $_REQUEST['SCHOOL_ID'] ? $_REQUEST['SCHOOL_ID'] : UserSchool() );

		$mp_type_list = "'" . implode( "','", $_REQUEST['mp_type_arr'] ) . "'";

		$st_list = "'" . implode( "','", $_REQUEST['st_arr'] ) . "'";

		$RET = 1;

		// FJ prevent student ID hacking.

		if ( User( 'PROFILE' ) !== 'admin' )
		{
			$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $st_list . ")";

			// Parent: associated students.
			$extra['ASSOCIATED'] = User( 'STAFF_ID' );

			$RET = GetStuList( $extra );
		}

		$t_grades = DBGet( "SELECT *
			FROM transcript_grades
			WHERE student_id IN (" . $st_list . ")
			AND mp_type in (" . $mp_type_list . ")
			AND school_id='" . $school_id . "'
			AND syear in (" . $syear_list . ")
			ORDER BY mp_type, end_date", array(), array( 'STUDENT_ID', 'SYEAR', 'MARKING_PERIOD_ID' ) );

		if ( ! empty( $t_grades ) && ! empty( $RET ) )
		{
			$syear = ( User( 'PROFILE' ) === 'admin' && $_REQUEST['syear_arr'] ?
				$_REQUEST['syear_arr'][0] :
				UserSyear() );

			$showStudentPic = isset( $_REQUEST['showstudentpic'] ) ? $_REQUEST['showstudentpic'] : null;
			$showSAT = isset( $_REQUEST['showsat'] ) ? $_REQUEST['showsat'] : null;
			//FJ add Show Grades option
			$showGrades = isset( $_REQUEST['showgrades'] ) ? $_REQUEST['showgrades'] : null;
			//FJ add Show Comments option
			$showMPcomments = isset( $_REQUEST['showmpcomments'] ) ? $_REQUEST['showmpcomments'] : null;
			//FJ add Show Credits option
			$showCredits = isset( $_REQUEST['showcredits'] ) ? $_REQUEST['showcredits'] : null;
			//FJ add Show Credit Hours option
			$showCreditHours = isset( $_REQUEST['showcredithours'] ) ? $_REQUEST['showcredithours'] : null;
			//FJ add Show Studies Certificate option
			$showCertificate = User( 'PROFILE' ) === 'admin' && $_REQUEST['showcertificate'];

			if ( $showCertificate )
			{
				SaveTemplate( $_REQUEST['inputcertificatetext'] );

				$certificateText = explode( '__BLOCK2__', $_REQUEST['inputcertificatetext'] );
			}

			$students_dataquery = "SELECT
			s.STUDENT_ID,
			s.FIRST_NAME,
			s.LAST_NAME,
			s.MIDDLE_NAME,
			" . DisplayNameSQL( 's' ) . " AS FULL_NAME";

			$custom_fields_RET = DBGet( "SELECT ID,TITLE,TYPE
				FROM CUSTOM_FIELDS WHERE ID IN (200000000, 200000003, 200000004)", array(), array( 'ID' ) );

			if ( $custom_fields_RET['200000000'] && $custom_fields_RET['200000000'][1]['TYPE'] == 'select' )
			{
				$students_dataquery .= ", s.custom_200000000 as gender";
			}

			if ( $custom_fields_RET['200000003'] )
			{
				$students_dataquery .= ", s.custom_200000003 as ssecurity";
			}

			if ( $custom_fields_RET['200000004'] && $custom_fields_RET['200000004'][1]['TYPE'] == 'date' )
			{
				$students_dataquery .= ", s.custom_200000004 as birthdate";
			}

			//, s.custom_200000012 as estgraddate
			$students_dataquery .= ", a.address
			, a.city
			, a.state
			, a.zipcode
			, a.phone
			, a.mail_address
			, a.mail_city
			, a.mail_state
			, a.mail_zipcode
			, (SELECT start_date FROM student_enrollment
				WHERE student_id=s.student_id
				ORDER BY syear, start_date
				LIMIT 1) as init_enroll
			, (SELECT sgl.title
				FROM school_gradelevels sgl JOIN student_enrollment se ON (sgl.id=se.grade_id)
				WHERE se.syear='" . $syear . "'
				AND se.student_id=s.student_id
				AND (se.end_date is null OR se.start_date < se.end_date)
				ORDER BY se.start_date desc
				LIMIT 1) as grade_level
			, (SELECT sgl2.title
				FROM school_gradelevels sgl2, school_gradelevels sgl JOIN student_enrollment se ON (sgl.id=se.grade_id)
				WHERE se.syear='" . $syear . "'
				AND se.student_id=s.student_id
				AND (se.end_date is null OR se.start_date < se.end_date)
				AND sgl2.id=sgl.next_grade_id
				ORDER BY se.start_date desc
				LIMIT 1) as next_grade_level
			FROM students s
			LEFT OUTER JOIN students_join_address sja ON (sja.student_id=s.student_id)
			LEFT OUTER JOIN address a ON (a.address_id=sja.address_id) ";

			$students_data = DBGet( $students_dataquery .
				' WHERE s.student_id IN (' . $st_list . ')
				ORDER BY LAST_NAME,FIRST_NAME', array(), array( 'STUDENT_ID' ) );

			$handle = PDFStart();

			echo '<style type="text/css"> * {font-size:large; line-height:1.2;} </style>';

			$school_info = DBGet( 'select * from schools where syear = ' . UserSyear() . ' AND id = ' . $school_id );
			$school_info = $school_info[1];

			foreach ( (array) $t_grades as $student_id => $t_sgrades )
			{
				foreach ( (array) $t_sgrades as $syear => $mps )
				{
					$student_data = $students_data[$student_id][1];

					echo '<table class="width-100p"><tr class="valign-top"><td>';
					//Student Photo
					$stu_pic = $StudentPicturesPath . Config( 'SYEAR' ) . '/' . $student_id . '.jpg';
					$stu_pic2 = $StudentPicturesPath . $syear . '/' . $student_id . '.jpg';
					$picwidth = 70;

					if ( file_exists( $stu_pic ) && $showStudentPic )
					{
						echo '<img src="' . $stu_pic . '" width="' . $picwidth . '" />';
					}
					elseif ( file_exists( $stu_pic2 ) && $showStudentPic )
					{
						echo '<img src="' . $stu_pic2 . '" width="' . $picwidth . '" />';
					}
					else
					{
						echo '&nbsp;';
					}

					echo '</td><td>';

					// Student Info.
					echo '<span style="font-size:x-large;">' . $student_data['FULL_NAME'] . '<br /></span>';

					// Translate "No Address".
					echo '<span>' . ( $student_data['ADDRESS'] === 'No Address' ?
						_( 'No Address' ) : $student_data['ADDRESS'] ) . '<br /></span>';
					echo '<span>' . $student_data['CITY'] . ( ! empty( $student_data['STATE'] ) ? ', ' . $student_data['STATE'] : '' ) . ( ! empty( $student_data['ZIPCODE'] ) ? '  ' . $student_data['ZIPCODE'] : '' ) . '</span>';

					echo '<table class="cellspacing-0 cellpadding-5" style="margin-top:10px;"><tr>';

					if ( $custom_fields_RET['200000004'] && $custom_fields_RET['200000004'][1]['TYPE'] == 'date' )
					{
						echo '<td style="border:solid black; border-width:1px 0 1px 1px;">' . ParseMLField( $custom_fields_RET['200000004'][1]['TITLE'] ) . '</td>';
					}

					if ( $custom_fields_RET['200000000'] && $custom_fields_RET['200000000'][1]['TYPE'] == 'select' )
					{
						echo '<td style="border:solid black; border-width:1px 0 1px 1px;">' . ParseMLField( $custom_fields_RET['200000000'][1]['TITLE'] ) . '</td>';
					}

					echo '<td style="border:solid black; border-width:1px;">' . _( 'Grade Level' ) . '</td>';
					echo '</tr><tr>';

					if ( $custom_fields_RET['200000004'] && $custom_fields_RET['200000004'][1]['TYPE'] == 'date' )
					{
						$dob = explode( '-', $student_data['BIRTHDATE'] );

						if ( ! empty( $dob ) )
						{
							echo '<td class="center">' . $dob[1] . '/' . $dob[2] . '/' . $dob[0] . '</td>';
						}
						else
						{
							echo '<td>&nbsp;</td>';
						}
					}

					if ( $custom_fields_RET['200000000'] && $custom_fields_RET['200000000'][1]['TYPE'] == 'select' )
					{
						echo '<td class="center">' . $student_data['GENDER'] . '</td>';
					}

					//FJ history grades in Transripts

					if ( empty( $student_data['GRADE_LEVEL'] ) )
					{
						$student_data['GRADE_LEVEL'] = $mps[key( $mps )][1]['GRADE_LEVEL_SHORT'];
					}

					echo '<td class="center">' . $student_data['GRADE_LEVEL'] . '</td>';
					echo '</tr></table>';

					echo '</td>';

					//School logo
					$logo_pic = 'assets/school_logo_' . UserSchool() . '.jpg';
					$picwidth = 120;
					echo '<td style="width:' . $picwidth . 'px;">';

					if ( file_exists( $logo_pic ) )
					{
						echo '<img src="' . $logo_pic . '" width="' . $picwidth . '" />';
					}

					echo '</td>';

					//School Info
					echo '<td style="width:384px;">';
					echo '<span style="font-size:x-large;">' . $school_info['TITLE'] . '<br /></span>';
					echo '<span>' . $school_info['ADDRESS'] . '<br /></span>';
					echo '<span>' . $school_info['CITY'] . ( ! empty( $school_info['STATE'] ) ? ', ' . $school_info['STATE'] : '' ) . ( ! empty( $school_info['ZIPCODE'] ) ? '  ' . $school_info['ZIPCODE'] : '' ) . '<br /></span>';

					if ( $school_info['PHONE'] )
					{
						echo '<span>' . _( 'Phone' ) . ': ' . $school_info['PHONE'] . '<br /></span>';
					}

					if ( $school_info['WWW_ADDRESS'] )
					{
						echo '<span>' . _( 'Website' ) . ': ' . $school_info['WWW_ADDRESS'] . '<br /></span>';
					}

					if ( $school_info['SCHOOL_NUMBER'] )
					{
						echo '<span>' . _( 'School Number' ) . ': ' . $school_info['SCHOOL_NUMBER'] . '<br /><br /></span>';
					}

					echo '<span>' . $school_info['PRINCIPAL'] . '<br /></span>';

					echo '</td></tr>';

					//Certificate Text block 1

					if ( $showCertificate )
					{
						echo '<tr><td colspan="4">';
						echo '<br /><span style="font-size:x-large;" class="center">' . _( 'Studies Certificate' ) . '<br /></span>';

						$substitutions = array(
							'__SSECURITY__' => $student_data['SSECURITY'],
							'__FULL_NAME__' => $student_data['FULL_NAME'],
							'__LAST_NAME__' => $student_data['LAST_NAME'],
							'__FIRST_NAME__' => $student_data['FIRST_NAME'],
							'__MIDDLE_NAME__' => $student_data['MIDDLE_NAME'],
							'__GRADE_ID__' => $student_data['GRADE_LEVEL'],
							'__NEXT_GRADE_ID__' => $student_data['NEXT_GRADE_LEVEL'],
							'__SCHOOL_ID__' => $school_info['TITLE'],
							'__YEAR__' => $syear,
						);

						$certificateText[0] = SubstitutionsTextMake( $substitutions, $certificateText[0] );

						echo '<span>' . nl2br( trim( $certificateText[0] ) ) . '</span>';
						echo '</td></tr>';
					}

					echo '</table>';

					//generate ListOutput friendly array
					$listOutput_RET = array();
					$total_credit_earned = 0;
					$total_credit_attempted = 0;

					$columns = array( 'COURSE_TITLE' => _( 'Course' ) );

					foreach ( (array) $mps as $mp_id => $grades )
					{
						$columns[$mp_id] = $grades[1]['SHORT_NAME'];
						//$i = 1;

						foreach ( (array) $grades as $grade )
						{
							$i = $grade['COURSE_TITLE'];

							$listOutput_RET[$i]['COURSE_TITLE'] = $grade['COURSE_TITLE'];

							if ( $showGrades )
							{
								if ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) > 0 )
								{
									$listOutput_RET[$i][$mp_id] = $grade['GRADE_PERCENT'] . '%';
								}
								elseif ( ProgramConfig( 'grades', 'GRADES_DOES_LETTER_PERCENT' ) < 0 )
								{
									$listOutput_RET[$i][$mp_id] = $grade['GRADE_LETTER'];
								}
								else
								{
									$listOutput_RET[$i][$mp_id] = $grade['GRADE_LETTER'] . '&nbsp;&nbsp;' . $grade['GRADE_PERCENT'] . '%';
								}
							}

							if ( $showCredits )
							{
								if (  ( strpos( $mp_type_list, 'year' ) !== false && $grade['MP_TYPE'] != 'quarter' && $grade['MP_TYPE'] != 'semester' ) || ( strpos( $mp_type_list, 'semester' ) !== false && $grade['MP_TYPE'] != 'quarter' ) || ( strpos( $mp_type_list, 'year' ) === false && strpos( $mp_type_list, 'semester' ) === false && $grade['MP_TYPE'] == 'quarter' ) )
								{
									$listOutput_RET[$i]['CREDIT_EARNED'] += (float) $grade['CREDIT_EARNED'];
									$total_credit_earned += $grade['CREDIT_EARNED'];
									$total_credit_attempted += $grade['CREDIT_ATTEMPTED'];
								}
							}

							if ( $showCreditHours )
							{
								if ( ! isset( $listOutput_RET[$i]['CREDIT_HOURS'] ) )
								{
									$listOutput_RET[$i]['CREDIT_HOURS'] = ( (int) $grade['CREDIT_HOURS'] == $grade['CREDIT_HOURS'] ? (int) $grade['CREDIT_HOURS'] : $grade['CREDIT_HOURS'] );
								}
							}

							if ( $showMPcomments )
							{
								$listOutput_RET[$i]['COMMENT'] = $grade['COMMENT'];
							}

							//$i++;
						}
					}

					if ( $showCredits )
					{
						$columns['CREDIT_EARNED'] = _( 'Credit' );
					}

					if ( $showCreditHours )
					{
						$columns['CREDIT_HOURS'] = _( 'C.H.' );
					}

					if ( $showMPcomments )
					{
						$columns['COMMENT'] = _( 'Comment' );
					}

					$listOutput_RET = array_values( $listOutput_RET );
					array_unshift( $listOutput_RET, 'start_array_to_1' );
					unset( $listOutput_RET[0] );
					//var_dump($listOutput_RET);exit;

					echo '<br />';
					ListOutput( $listOutput_RET, $columns, '.', '.', false );

					//School Year
					echo '<table class="width-100p"><tr><td>';
					echo '<span><br />' . _( 'School Year' ) . ': ' . FormatSyear( $syear, Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) ) . '</span>';
					echo '</td></tr>';

					// GPA and/or Class Rank.

					if ( $showGrades
						&& $grade['MP_TYPE'] !== 'quarter'
						&& ( ! empty( $grade['CUM_WEIGHTED_GPA'] ) || ! empty( $grade['CUM_RANK'] ) ) )
					{
						echo '<tr><td><span>';

						if ( ! empty( $grade['CUM_WEIGHTED_GPA'] ) )
						{
							echo sprintf(
								_( 'GPA' ) . ': %01.2f / %01.0f',
								$grade['CUM_WEIGHTED_GPA'],
								$grade['SCHOOL_SCALE'] );

							if ( ! empty( $grade['CUM_RANK'] ) )
							{
								echo ' &ndash; ';
							}
						}

						if ( ! empty( $grade['CUM_RANK'] ) )
						{
							echo _( 'Class Rank' ) . ': ' . $grade['CUM_RANK'] .
								' / ' . $grade['CLASS_SIZE'] . '</span>';
						}

						echo '</span></td></tr>';
					}

					// Total Credits.

					if ( $showCredits
						&& $total_credit_attempted > 0 )
					{
						echo '<tr><td><span>';
						echo _( 'Total' ) . ' ' . _( 'Credit' ) . ': ' .
						_( 'Credit Attempted' ) . ': ' . (float) $total_credit_attempted .
						' &ndash; ' . _( 'Credit Earned' ) . ': ' . (float) $total_credit_earned;
						echo '</span></td></tr>';
					}

					//Certificate Text block 2

					if ( $showCertificate && ! empty( $certificateText[1] ) )
					{
						$certificateText[1] = str_replace(
							array(
								'__SSECURITY__',
								'__FULL_NAME__',
								'__FIRST_NAME__',
								'__LAST_NAME__',
								'__MIDDLE_NAME__',
								'__GRADE_ID__',
								'__NEXT_GRADE_ID__',
								'__YEAR__',
								'__SCHOOL_ID__',
							),
							array(
								$student_data['SSECURITY'],
								$student_data['FULL_NAME'],
								$student_data['FIRST_NAME'],
								$student_data['LAST_NAME'],
								$student_data['MIDDLE_NAME'],
								$student_data['GRADE_LEVEL'],
								$student_data['NEXT_GRADE_LEVEL'],
								$syear,
								$school_info['TITLE'],
							),
							$certificateText[1]
						);

						echo '<tr><td><br /><span>' . nl2br( trim( $certificateText[1] ) ) . '</span></td></tr>';
					}

					echo '</table>';

					//Signatures
					echo '<br /><br /><br /><table class="width-100p" style="border-collapse:separate; border-spacing: 40px;"><tr><td style="width:50%;">';
					echo '<table class="width-100p"><tr><td style="border-top:solid black 1px;" class="center"><span style="font-size:x-small;">' . _( 'Signature' ) . '<br /><br /><br /></span></td></tr>';
					echo '<tr><td style="border-top:solid black 1px;" class="center"><span style="font-size:x-small;">' . _( 'Title' ) . '</span></td></tr></table>';
					echo '</td><td style="width:50%;">';

					//FJ add second signature for the certificate

					if ( $showCertificate )
					{
						echo '<table class="width-100p"><tr><td style="border-top:solid black 1px;" class="center"><span style="font-size:x-small;">' . _( 'Signature' ) . '<br /><br /><br /></span></td></tr>';
						echo '<tr><td style="border-top:solid black 1px;" class="center"><span style="font-size:x-small;">' . _( 'Title' ) . '</span></td></tr></table>';
					}

					echo '</td></tr></table>';

					echo '<div style="page-break-after: always;"></div>';
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
		BackPrompt( _( 'You must choose at least one student and one marking period.' ) );
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader( ProgramTitle() );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		//FJ include gentranscript.php in Transcripts.php
		//echo '<form action="modules/Grades/gentranscript.php" method="POST">';
		echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save&_ROSARIO_PDF=true" method="POST">';

		$extra['header_right'] = Buttons( _( 'Create Transcripts for Selected Students' ) );

		$extra['extra_header_left'] = '<table class="width-100p">';

		$extra['extra_header_left'] .= '<tr><td colspan="2"><b>' . _( 'Include on Transcript' ) .
		'</b><input type="hidden" name="SCHOOL_ID" value="' . UserSchool() . '" /><br /></td></tr>';

		// FJ history grades & previous school years in Transripts.

		if ( User( 'PROFILE' ) === 'admin' )
		{
			$syear_history_RET = DBGet( "SELECT DISTINCT SYEAR
				FROM HISTORY_MARKING_PERIODS
				WHERE SYEAR<>'" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				UNION SELECT DISTINCT SYEAR
				FROM SCHOOL_MARKING_PERIODS
				WHERE SYEAR<>'" . UserSyear() . "'
				AND SCHOOL_ID='" . UserSchool() . "'
				ORDER BY SYEAR DESC" );

			// If History School Years or previous school years.

			if ( $syear_history_RET )
			{
				$extra['extra_header_left'] .= '<tr class="st"><td>';

				$syoptions[UserSyear()] = FormatSyear( UserSyear(), Config( 'SCHOOL_SYEAR_OVER_2_YEARS' ) );

				// Chosen Multiple select input.
				$syextra = 'multiple';

				foreach ( (array) $syear_history_RET as $syear_history )
				{
					$syoptions[$syear_history['SYEAR']] = FormatSyear(
						$syear_history['SYEAR'],
						Config( 'SCHOOL_SYEAR_OVER_2_YEARS' )
					);
				}

				$extra['extra_header_left'] .= ChosenSelectInput(
					UserSyear(),
					'syear_arr[]',
					_( 'School Years' ),
					$syoptions,
					false,
					$syextra,
					false
				);

				$extra['extra_header_left'] .= '<hr /></td></tr>';
			}
		}

		$mp_types = DBGet( "SELECT DISTINCT MP_TYPE
			FROM MARKING_PERIODS
			WHERE NOT MP_TYPE IS NULL
			AND SCHOOL_ID='" . UserSchool() . "'", array(), array() );

		$extra['extra_header_left'] .= '<tr class="st"><td class="valign-top">';

		//FJ add translation
		$marking_periods_locale = array(
			'Year' => _( 'Year' ),
			'Semester' => _( 'Semester' ),
			'Quarter' => _( 'Quarter' ),
		);

		foreach ( (array) $mp_types as $mp_type )
		{
			//FJ add <label> on checkbox
			$extra['extra_header_left'] .= '<label><input type="checkbox" name="mp_type_arr[]" value="' . $mp_type['MP_TYPE'] . '"> ' . $marking_periods_locale[ucwords( $mp_type['MP_TYPE'] )] . '</label> ';
		}

		$extra['extra_header_left'] .= FormatInputTitle( _( 'Marking Periods' ) ) . '<hr /></td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td class="valign-top">';

		//FJ add Show Grades option
		$extra['extra_header_left'] .= '<label><input type="checkbox" name="showgrades" value="1" checked /> ' . _( 'Grades' ) . '</label>';

		$extra['extra_header_left'] .= '<br /><br /><label><input type="checkbox" name="showstudentpic" value="1"> ' . _( 'Student Photo' ) . '</label>';

		//FJ add Show Comments option
		$extra['extra_header_left'] .= '<br /><br /><label><input type="checkbox" name="showmpcomments" value="1"> ' . _( 'Comments' ) . '</label>';

		//FJ add Show Credits option
		$extra['extra_header_left'] .= '<br /><br /><label><input type="checkbox" name="showcredits" value="1" checked /> ' . _( 'Credits' ) . '</label>';

		//FJ add Show Credit Hours option
		$extra['extra_header_left'] .= '<br /><br /><label><input type="checkbox" name="showcredithours" value="1"> ' . _( 'Credit Hours' ) . '</label>';

		//FJ limit Cetificate to admin

		if ( User( 'PROFILE' ) === 'admin' )
		{
			//FJ add Show Studies Certificate option
			$field_SSECURITY = ParseMLArray( DBGet( "SELECT TITLE
				FROM CUSTOM_FIELDS
				WHERE ID = 200000003" ), 'TITLE' );

			$extra['extra_header_left'] .= '<br /><br /><label><input type="checkbox" name="showcertificate" autocomplete="off" value="1" onclick=\'javascript: document.getElementById("divcertificatetext").style.display="block"; document.getElementById("inputcertificatetext").focus();\'> ' . _( 'Studies Certificate' ) . '</label>';
		}

		//FJ limit Cetificate to admin

		if ( User( 'PROFILE' ) === 'admin' )
		{
			//FJ add Show Studies Certificate option
			$extra['extra_header_left'] .= '<div id="divcertificatetext" style="display:none">
				<br /><textarea id="inputcertificatetext" name="inputcertificatetext" cols="100" rows="5">' .
			GetTemplate() .
			'</textarea>' .
			FormatInputTitle(
				_( 'Studies Certificate Text' ),
				'inputcertificatetext'
			);

			$substitutions = array(
				'__SSECURITY__' => $field_SSECURITY[1]['TITLE'],
				'__FULL_NAME__' => _( 'Display Name' ),
				'__LAST_NAME__' => _( 'Last Name' ),
				'__FIRST_NAME__' => _( 'First Name' ),
				'__MIDDLE_NAME__' =>  _( 'Middle Name' ),
				'__GRADE_ID__' => _( 'Grade Level' ),
				'__NEXT_GRADE_ID__' => _( 'Next Grade' ),
				'__SCHOOL_ID__' => _( 'School' ),
				'__YEAR__' => _( 'School Year' ),
				'__BLOCK2__' => _( 'Text Block 2' ),
			);

			$extra['extra_header_left'] .= '<table><tr class="st"><td class="valign-top">' .
				SubstitutionsInput( $substitutions ) .
			'</td></tr>';

			$extra['extra_header_left'] .= '</table></div>';
		}

		$extra['extra_header_left'] .= '</td></tr></table>';
	}

	$extra['new'] = true;

	$extra['link'] = array( 'FULL_NAME' => false );
	$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
	$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
	$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( 'Y', '', 'st_arr' ) );
	$extra['options']['search'] = false;

	// Parent: associated students.
	$extra['ASSOCIATED'] = User( 'STAFF_ID' );

	Widgets( 'course' );
	Widgets( 'gpa' );
	Widgets( 'class_rank' );
	Widgets( 'letter_grade' );

	Search( 'student_id', $extra );

	if ( $_REQUEST['search_modfunc'] === 'list' )
	{
		echo '<br /><div class="center">' . Buttons( _( 'Create Transcripts for Selected Students' ) ) . '</div>';
		echo '</form>';

		// SYear & Semester MPs only, including History MPs.
		$mps_RET = DBGet( "SELECT MARKING_PERIOD_ID
			FROM MARKING_PERIODS
			WHERE SCHOOL_ID='" . UserSchool() . "'
			AND MP_TYPE IN ('semseter','year')" );

		foreach ( (array) $mps_RET as $mp )
		{
			// @since 4.7 Automatic Class Rank calculation.
			ClassRankMaybeCalculate( $mp['MARKING_PERIOD_ID'] );
		}
	}
}
