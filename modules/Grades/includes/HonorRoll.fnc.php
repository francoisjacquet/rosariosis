<?php
/**
 * Honor Roll functions
 */

/**
 * Honor Roll PDF
 *
 * @since 4.0
 *
 * @param array   $student_array   Students list.
 * @param boolean $is_list         Is list? Else is Certificate.
 * @param string  $honor_roll_text Honor Roll Certificate HTML.
 */
function HonorRollPDF( $student_array, $is_list, $honor_roll_text )
{
	$student_list = "'" . implode( "','", $student_array ) . "'";

	$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $student_list . ")";

	$mp_RET = DBGet( "SELECT TITLE,END_DATE
		FROM school_marking_periods
		WHERE MP='QTR'
		AND MARKING_PERIOD_ID='" . UserMP() . "'" );

	// SELECT s.* Custom Fields for Substitutions.
	$extra['SELECT'] = ",s.*";

	$extra['SELECT'] .= ",(SELECT SORT_ORDER FROM school_gradelevels WHERE ID=ssm.GRADE_ID) AS SORT_ORDER";

	$extra['SELECT'] .= "," . db_case( [ "exists(SELECT rg.GPA_VALUE
	FROM student_report_card_grades sg,course_periods cp,report_card_grades rg
	WHERE sg.STUDENT_ID=s.STUDENT_ID
	AND cp.SYEAR=ssm.SYEAR
	AND sg.SYEAR=ssm.SYEAR
	AND sg.MARKING_PERIOD_ID='" . UserMP() . "'
	AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
	AND cp.DOES_HONOR_ROLL='Y'
	AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
	AND sg.REPORT_CARD_GRADE_ID=rg.ID
	AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM report_card_grade_scales WHERE ID=rg.GRADE_SCALE_ID))", 'true', 'NULL', "'Y'" ] ) . " AS HIGH_HONOR";

	//$extra['SELECT'] .= ",(SELECT TITLE FROM schools WHERE ID=ssm.SCHOOL_ID AND SYEAR=ssm.SYEAR) AS SCHOOL";
	//$extra['SELECT'] .= ",(SELECT PRINCIPAL FROM schools WHERE ID=ssm.SCHOOL_ID AND SYEAR=ssm.SYEAR) AS PRINCIPAL";
	//FJ multiple school periods for a course period
	//$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . " FROM staff st,course_periods cp,school_periods p,schedule ss WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER IS NULL,p.SORT_ORDER LIMIT 1) AS TEACHER";
	$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . "
	FROM staff st,course_periods cp,schedule ss
	WHERE st.STAFF_ID=cp.TEACHER_ID
	AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
	AND ss.STUDENT_ID=s.STUDENT_ID
	AND ss.SYEAR='" . UserSyear() . "'
	AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', DBDate(), false ) ) . ")
	AND (ss.START_DATE<='" . DBDate() . "' AND (ss.END_DATE>='" . DBDate() . "' OR ss.END_DATE IS NULL)) LIMIT 1) AS TEACHER";

	$extra['ORDER_BY'] = 'HIGH_HONOR,SORT_ORDER DESC,FULL_NAME';

	if ( $is_list )
	{
		$extra['group'] = [ 'HIGH_HONOR' ];
	}

	$RET = GetStuList( $extra );

	if ( $is_list )
	{
		$handle = PDFStart();

		DrawHeader( sprintf( _( '%s Honor Roll' ), SchoolInfo( 'TITLE' )  ) );

		DrawHeader( $mp_RET[1]['TITLE'] . ' - ' . date( 'F j, Y', strtotime( $mp_RET[1]['END_DATE'] ) ) );

		$columns = [
			'FULL_NAME' => _( 'Student' ),
			'GRADE_ID' => _( 'Grade Level' ),
			'TEACHER' => _( 'Teacher' ),
		];

		foreach ( [ 'Y', '' ] AS $high )
		{
			if ( ! empty( $RET[ $high ] ) )
			{
				DrawHeader(
					'<b>' . ( $high === 'Y' ? _( 'High Honor Roll' ) : _( 'Honor Roll' ) ) . '</b>'
				);

				ListOutput(
					$RET[ $high ],
					$columns
				);
			}
		}

		PDFStop( $handle );
	}
	else
	{
		// Is Certificate.
		SaveTemplate( DBEscapeString( SanitizeHTML( $honor_roll_text ) ) );

		$honor_roll_text_template = GetTemplate();

		$no_margins = [ 'top' => 0, 'bottom' => 0, 'left' => 0, 'right' => 0 ];

		$pdf_options = [
			'css' => false,
			'margins' => $no_margins,
		];

		// @since 6.7 Remove PDF Header Footer plugin action.
		remove_action( 'functions/PDF.php|pdf_start', 'PDFHeaderFooterTriggered' ); // @deprecated.
		remove_action( 'functions/PDF.php|pdf_start', 'PDFHeaderTriggered' );
		remove_action( 'functions/PDF.php|pdf_start', 'PDFFooterTriggered' );

		$handle = PDFStart( $pdf_options );

		$_SESSION['orientation'] = 'landscape';

		$frame_image_css = '';

		if ( ! empty( $_FILES['frame']['name'] ) )
		{
			$base64_frame_image = HonorRollFrame( $_FILES['frame'] );

			if ( $base64_frame_image )
			{
				$frame_image_css = 'background:url(' . $base64_frame_image . ') no-repeat;
					background-size:100% 100%;';
			}
		}

		// Frame height is a few pixels below page height & depends on page format (A4 or US Letter)
		$frame_height = Preferences( 'PAGE_SIZE' ) === 'A4' ? '992' : '1085';

		echo '<style type="text/css">
			body {
				margin:0;
				padding:0;
			}
			.pdf-frame {
				width: 1405px;
				height: ' . $frame_height . 'px;
				page-break-before: always;
				' . $frame_image_css . '
			}
		</style>';

		foreach ( (array) $RET as $student)
		{
			echo '<div class="pdf-frame"><table style="margin:auto auto;">';

			$substitutions = [
				'__FULL_NAME__' => $student['FULL_NAME'],
				'__FIRST_NAME__' => $student['FIRST_NAME'],
				'__LAST_NAME__' => $student['LAST_NAME'],
				'__MIDDLE_NAME__' => $student['MIDDLE_NAME'],
				'__GRADE_ID__' => $student['GRADE_ID'],
				'__SCHOOL_ID__' => SchoolInfo( 'TITLE' ),
			];

			$substitutions += SubstitutionsCustomFieldsValues( 'STUDENT', $student );

			$honor_roll_text = SubstitutionsTextMake( $substitutions, $honor_roll_text_template );

			$honor_roll_text = ( $student['HIGH_HONOR'] === 'Y' ?
				str_replace( _( 'Honor Roll' ), _( 'High Honor Roll' ), $honor_roll_text ) :
				$honor_roll_text
			);

			echo '<tr><td>' . $honor_roll_text . '</td></tr></table>';

			echo '<br /><table style="margin:auto auto; width:70%;">';

			echo '<tr><td><span style="font-size:x-large;">' . $student['TEACHER'] . '</span><br />
				<span style="font-size:medium;">' . _( 'Teacher' ) . '</span></td>';

			echo '<td><span style="font-size:x-large;">' . $mp_RET[1]['TITLE'] . '</span><br />
				<span style="font-size:medium;">' . _( 'Marking Period' ) . '</span></td></tr>';

			echo '<tr><td><span style="font-size:x-large;">' .
				SchoolInfo( 'PRINCIPAL' ) .
				'</span><br />
				<span style="font-size:medium;">' . _( 'Principal' ) . '</span></td>';

			echo '<td><span style="font-size:x-large;">' .
				ProperDate( $mp_RET[1]['END_DATE'] ) .
				'</span><br />
				<span style="font-size:medium;">' . _( 'Date' ) . '</span></td></tr>';

			echo '</table></div>';
		}

		PDFStop( $handle );
	}
}


/**
 * Honor Roll Subject PDF
 *
 * @since 4.0
 *
 * @param array   $student_array   Students list.
 * @param boolean $is_list         Is list? Else is Certificate.
 * @param string  $honor_roll_text Honor Roll Certificate HTML.
 */
function HonorRollSubjectPDF( $student_array, $is_list, $honor_roll_text )
{
	$student_list = "'" . implode( "','", $student_array ) . "'";

	$extra['WHERE'] = " AND s.STUDENT_ID IN (" . $student_list . ")";

	$mp_RET = DBGet( "SELECT TITLE,END_DATE
		FROM school_marking_periods
		WHERE MP='QTR'
		AND MARKING_PERIOD_ID='" . UserMP() . "'" );

	$subject_RET = DBGet( "SELECT TITLE
		FROM course_subjects
		WHERE SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'
		AND SCHOOL_ID='" . UserSchool() . "'
		AND SYEAR='" . UserSyear() . "'" );

	$extra['SELECT'] = ",(SELECT SORT_ORDER FROM school_gradelevels WHERE ID=ssm.GRADE_ID) AS SORT_ORDER";

	$extra['SELECT'] .= ",(SELECT " . DisplayNameSQL( 'st' ) . "
	FROM staff st,course_periods cp,schedule ss
	WHERE st.STAFF_ID=cp.TEACHER_ID
	AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
	AND ss.STUDENT_ID=s.STUDENT_ID
	AND ss.SYEAR='" . UserSyear() . "'
	AND ss.MARKING_PERIOD_ID IN (" . GetAllMP( 'QTR', GetCurrentMP( 'QTR', DBDate(), false ) ) . ")
	AND (ss.START_DATE<='" . DBDate() . "'AND (ss.END_DATE>='" . DBDate() . "' OR ss.END_DATE IS NULL)) LIMIT 1) AS TEACHER";

	$extra['ORDER_BY'] = 'SORT_ORDER DESC,FULL_NAME';

	$RET = GetStuList( $extra );

	if ( $is_list )
	{
		$handle = PDFStart();

		DrawHeader( sprintf( _( '%s Honor Roll' ), SchoolInfo( 'TITLE' )  ) );

		DrawHeader( $mp_RET[1]['TITLE'] . ' - ' . date( 'F j, Y', strtotime( $mp_RET[1]['END_DATE'] ) ) );

		DrawHeader( '<b>' . _( 'Honor Roll by Subject' ) . ':</b> ' . $subject_RET[1]['TITLE'] );

		$columns = [
			'FULL_NAME' => _( 'Student' ),
			'GRADE_ID' => _( 'Grade Level' ),
			'TEACHER' => _( 'Teacher' ),
		];

		ListOutput(
			$RET,
			$columns
		);

		PDFStop( $handle );
	}
	else
	{
		// Is Certificate.
		SaveTemplate( DBEscapeString( SanitizeHTML( $honor_roll_text ) ) );

		$honor_roll_text_template = GetTemplate();

		$no_margins = [ 'top' => 0, 'bottom' => 0, 'left' => 0, 'right' => 0 ];

		$pdf_options = [
			'css' => false,
			'margins' => $no_margins,
		];

		$handle = PDFStart( $pdf_options );

		$_SESSION['orientation'] = 'landscape';

		$frame_image_css = '';

		if ( ! empty( $_FILES['frame'] ) )
		{
			$base64_frame_image = HonorRollFrame( $_FILES['frame'] );

			if ( $base64_frame_image )
			{
				$frame_image_css = 'background:url(' . $base64_frame_image . ') no-repeat;
					background-size:100% 100%;';
			}
		}

		echo '<style type="text/css">
			body {
				margin:0;
				padding:0;
				width:100%;
				height:100%;
				' . $frame_image_css . '
			}
		</style>';

		foreach ( (array) $RET as $student )
		{
			echo '<table style="margin:auto auto;">';

			$substitutions = [
				'__FULL_NAME__' => $student['FULL_NAME'],
				'__FIRST_NAME__' => $student['FIRST_NAME'],
				'__LAST_NAME__' => $student['LAST_NAME'],
				'__MIDDLE_NAME__' => $student['MIDDLE_NAME'],
				'__GRADE_ID__' => $student['GRADE_ID'],
				'__SCHOOL_ID__' => SchoolInfo( 'TITLE' ),
				'__SUBJECT__' => $subject_RET[1]['TITLE'],
			];

			$substitutions += SubstitutionsCustomFieldsValues( 'STUDENT', $student );

			$honor_roll_text = SubstitutionsTextMake( $substitutions, $honor_roll_text_template );

			echo '<tr><td>' . $honor_roll_text . '</td></tr></table>';

			echo '<br /><table style="margin:auto auto; width:80%;">';
			echo '<tr><td><span style="font-size:x-large;">' . $student['TEACHER'] . '</span><br />
				<span style="font-size:medium;">' . _( 'Teacher' ) . '</span></td>';

			echo '<td><span style="font-size:x-large;">' . $mp_RET[1]['TITLE'] . '</span><br />
				<span style="font-size:medium;">' . _( 'Marking Period' ) . '</span></td></tr>';

			echo '<tr><td><span style="font-size:x-large;">' .
				SchoolInfo( 'PRINCIPAL' ) . '</span><br />
				<span style="font-size:medium;">' . _( 'Principal' ) . '</span></td>';

			echo '<td><span style="font-size:x-large;">' .
				ProperDate( $mp_RET[1]['END_DATE'] ) .
				'</span><br />
				<span style="font-size:medium;">' . _( 'Date' ) . '</span></td></tr>';

			echo '</table>';
			echo '<div style="page-break-after: always;"></div>';
		}

		PDFStop( $handle );
	}
}


/**
 * Honor Roll Widgets
 * Extends Students Search screen widgets.
 *
 * @since 4.0
 *
 * @param string $item [honor_roll|honor_roll_subject]
 */
function HonorRollWidgets( $item )
{
	global $extra,
		$_ROSARIO;

	switch ( $item )
	{
		case 'honor_roll':
		case 'honor_roll_subject':
			// Honor Roll by Subject.
			if ( ! empty( $_REQUEST['subject_id'] ) )
			{
				$extra['WHERE'] .=  " AND exists(SELECT ''
				FROM student_report_card_grades sg,course_periods cp, courses c
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='" . UserMP() . "'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y'
				AND cp.COURSE_ID=c.COURSE_ID
				AND c.SUBJECT_ID='".$_REQUEST['subject_id']."')";

				$extra['WHERE'] .= " AND NOT exists(SELECT ''
				FROM student_report_card_grades sg,course_periods cp,report_card_grades rg
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='" . UserMP() . "'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y'
				AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
				AND sg.REPORT_CARD_GRADE_ID=rg.ID
				AND rg.GPA_VALUE<(SELECT HRS_GPA_VALUE FROM report_card_grade_scales WHERE ID=rg.GRADE_SCALE_ID))";

				if ( ! $extra['NoSearchTerms'] )
				{
					$subject_RET = DBGet( "SELECT TITLE
						FROM course_subjects
						WHERE SUBJECT_ID='" . (int) $_REQUEST['subject_id'] . "'
						AND SCHOOL_ID='" . UserSchool() . "'
						AND SYEAR='" . UserSyear() . "'" );

					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Honor Roll by Subject' ) . ':</b> ' .
						$subject_RET[1]['TITLE'];

					$_ROSARIO['SearchTerms'] .= '<input type="hidden" id="subject_id" name="subject_id" value="' .
						$_REQUEST['subject_id'] . '" /><br />';
				}
			}
			elseif ( ! empty( $_REQUEST['honor_roll'] )
				&& ! empty( $_REQUEST['high_honor_roll'] ) )
			{
				$extra['SELECT'] .= ",".db_case(["exists(SELECT rg.GPA_VALUE
				FROM student_report_card_grades sg,course_periods cp,report_card_grades rg
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='" . UserMP() . "'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y'
				AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
				AND sg.REPORT_CARD_GRADE_ID=rg.ID
				AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM report_card_grade_scales WHERE ID=rg.GRADE_SCALE_ID))",'true','NULL',"'".button('check')."'"])." AS HIGH_HONOR";

				$extra['WHERE'] .=  " AND exists(SELECT ''
				FROM student_report_card_grades sg,course_periods cp
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='" . UserMP() . "'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y')";

				$extra['WHERE'] .= " AND NOT exists(SELECT ''
				FROM student_report_card_grades sg,course_periods cp,report_card_grades rg
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='" . UserMP() . "'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y'
				AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
				AND sg.REPORT_CARD_GRADE_ID=rg.ID
				AND rg.GPA_VALUE<(SELECT  HR_GPA_VALUE FROM report_card_grade_scales WHERE ID=rg.GRADE_SCALE_ID))";

				$extra['columns_after']['HIGH_HONOR'] = _( 'High Honor' );

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Honor Roll' ) . ' & ' .
						_( 'High Honor Roll' ) . '</b><br />';
				}
			}
			elseif ( ! empty( $_REQUEST['honor_roll'] ) )
			{
				$extra['WHERE'] .=  " AND exists(SELECT ''
				FROM student_report_card_grades sg,course_periods cp
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='" . UserMP() . "'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y')";

				$extra['WHERE'] .= " AND NOT exists(SELECT ''
				FROM student_report_card_grades sg,course_periods cp,report_card_grades rg
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='" . UserMP() . "'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y'
				AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
				AND sg.REPORT_CARD_GRADE_ID=rg.ID
				AND rg.GPA_VALUE<(SELECT  HR_GPA_VALUE FROM report_card_grade_scales WHERE ID=rg.GRADE_SCALE_ID))";

				$extra['WHERE'] .= " AND exists(SELECT ''
				FROM student_report_card_grades sg,course_periods cp,report_card_grades rg
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='" . UserMP() . "'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y'
				AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
				AND sg.REPORT_CARD_GRADE_ID=rg.ID
				AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM report_card_grade_scales WHERE ID=rg.GRADE_SCALE_ID))";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'Honor Roll' ) . '</b><br />';
				}
			}
			elseif ( ! empty( $_REQUEST['high_honor_roll'] ) )
			{
				$extra['WHERE'] .=  " AND exists(SELECT ''
				FROM student_report_card_grades sg,course_periods cp
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='" . UserMP() . "'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y')";

				$extra['WHERE'] .= " AND NOT exists(SELECT ''
				FROM student_report_card_grades sg,course_periods cp,report_card_grades rg
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='" . UserMP() . "'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y'
				AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
				AND sg.REPORT_CARD_GRADE_ID=rg.ID
				AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM report_card_grade_scales WHERE ID=rg.GRADE_SCALE_ID))";

				if ( ! $extra['NoSearchTerms'] )
				{
					$_ROSARIO['SearchTerms'] .= '<b>' . _( 'High Honor Roll' ) . '</b><br />';
				}
			}

			$subjects_RET = DBGet( "SELECT SUBJECT_ID,TITLE
				FROM course_subjects
				WHERE SCHOOL_ID='" . UserSchool() . "'
				AND SYEAR='" . UserSyear() . "'" );

			$select = '<select name="subject_id">
				<option value="">' . _( 'N/A' ) . '</option>';

			foreach ( (array) $subjects_RET as $subject)
			{
				$select .= '<option value="' . AttrEscape( $subject['SUBJECT_ID'] ) . '">' . $subject['TITLE'] . '</option>';
			}

			$select .= '</select>';
			$extra['search'] .= '<tr><td>' . _( 'Honor Roll by Subject' ) . '</td>
				<td>' . $select . '</td></tr>';

			$extra['search'] .= '<tr>
			<td>'. _( 'Honor Roll' ) . '</td>
			<td><label><input type="checkbox" name="honor_roll" value="Y" checked /> ' . _( 'Honor' ) . '</label> <label><input type="checkbox" name="high_honor_roll" value="Y" checked /> ' . _( 'High Honor' ) . '</label></td>
			</tr>';
		break;
	}
}


/**
 * Honor roll Frame image
 * Get image file upload input HTML
 * Or Get base64 image file data if $_FILES['frame'] given as parameter
 *
 * @since 4.0
 *
 * @param array $frame_file Uploaded $_FILES['frame'].
 *
 * @return string Base64 frame image or Frame image upload input HTML.
 */
function HonorRollFrame( $frame_file = [] )
{
	global $error;

	// 5 MB file size limit.
	$size_limit = 5;

	if ( $frame_file )
	{
		$ext_white_list = [ '.jpg', '.jpeg', '.png', '.gif' ];

		$file_ext = mb_strtolower( mb_strrchr( $frame_file['name'], '.' ) );

		if ( ! in_array( $file_ext, $ext_white_list ) )
		{
			$error[] = _( 'Frame' ) . ': ' . sprintf(
				_( 'Wrong file type: %s (%s required)' ),
				$frame_file['type'],
				implode( ', ', $ext_white_list )
			);
		}

		if ( $frame_file['size'] > $size_limit * 1024 * 1024 )
		{
			$error[] = _( 'Frame' ) . ': ' . sprintf(
				_( 'File size > %01.2fMb: %01.2fMb' ),
				$size_limit,
				( $frame_file['size'] / 1024 ) / 1024
			);
		}

		if ( $error && isset( $_REQUEST['_ROSARIO_PDF'] ) )
		{
			BackPrompt( end( $error ) );

			return '';
		}

		$type = pathinfo( $frame_file['tmp_name'], PATHINFO_EXTENSION );

		$data = file_get_contents( $frame_file['tmp_name'] );

		$base64 = 'data:image/' . $type . ';base64,' . base64_encode( $data );

		return $base64;
	}

	$html = '<tr class="st"><td>' .
	FileInput(
		'frame',
		_( 'Frame' ) . ' (.jpg, .png, .gif)',
		'accept="image/*"',
		$size_limit
	)
	. '</td></tr>';

	return $html;
}
