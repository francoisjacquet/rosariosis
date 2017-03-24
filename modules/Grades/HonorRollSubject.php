<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

if ( $_REQUEST['modfunc'] === 'save' )
{
	if (count($_REQUEST['st_arr']))
	{
		//FJ bypass strip_tags on the $_REQUEST vars
		$REQUEST_honor_roll_text = SanitizeHTML( $_POST['honor_roll_text'] );

		//FJ add Template
		$template_update = DBGet(DBQuery("SELECT 1 FROM TEMPLATES WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID = '".User('STAFF_ID')."'"));

		if ( ! $template_update)
			DBQuery("INSERT INTO TEMPLATES (MODNAME, STAFF_ID, TEMPLATE) VALUES ('".$_REQUEST['modname']."', '".User('STAFF_ID')."', '".$REQUEST_honor_roll_text."')");
		else
			DBQuery("UPDATE TEMPLATES SET TEMPLATE = '".$REQUEST_honor_roll_text."' WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID = '".User('STAFF_ID')."'");

		$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';

		$extra['WHERE'] = " AND s.STUDENT_ID IN (".$st_list.")";

		$mp_RET = DBGet(DBQuery("SELECT TITLE,END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND MARKING_PERIOD_ID='".UserMP()."'"));

		$extra['SELECT'] .= ",(SELECT SORT_ORDER FROM SCHOOL_GRADELEVELS WHERE ID=ssm.GRADE_ID) AS SORT_ORDER";

		$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||coalesce(' '||st.MIDDLE_NAME||' ',' ')||st.LAST_NAME
		FROM STAFF st,COURSE_PERIODS cp,SCHEDULE ss
		WHERE st.STAFF_ID=cp.TEACHER_ID
		AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
		AND ss.STUDENT_ID=s.STUDENT_ID
		AND ss.SYEAR='".UserSyear()."'
		AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).")
		AND (ss.START_DATE<='".DBDate()."'AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) LIMIT 1) AS TEACHER";

		$extra['ORDER_BY'] = 'SORT_ORDER DESC,FULL_NAME';

		$RET = GetStuList($extra);

		$no_margins = array( 'top' => 0, 'bottom' => 0, 'left' => 0, 'right' => 0 );

		$pdf_options = array(
			'css' => false,
			'margins' => $no_margins,
		);

		$handle = PDFStart( $pdf_options );

		$_SESSION['orientation'] = 'landscape';

		echo '<style type="text/css">
			body {
				margin:0;
				padding:0;
				width:100%;
				height:100%;';

		if ( $_REQUEST['frame'] )
		{
			echo 'background:url(assets/Frames/' . $_REQUEST['frame'] . ') no-repeat;
				background-size:100% 100%;';
		}

		echo '}</style>';

		foreach ( (array) $RET as $student )
		{
			echo '<table style="margin:auto auto;">';

			//FJ Bugfix wkhtmltopdf ContentOperationNotPermittedError
			$clipart_html = ( $_REQUEST['clipart'] ? '<img src="assets/ClipArts/'.$_REQUEST['clipart'].'" height="200" />' : '' );

			$honor_roll_text = $REQUEST_honor_roll_text;

			$honor_roll_text = str_replace(array('__CLIPART__',
			'__FULL_NAME__',
			'__FIRST_NAME__',
			'__LAST_NAME__',
			'__MIDDLE_NAME__',
			'__GRADE_ID__',
			'__SCHOOL_ID__',
			'__SUBJECT__'),
			array($clipart_html,
			$student['FULL_NAME'],
			$student['FIRST_NAME'],
			$student['LAST_NAME'],
			$student['MIDDLE_NAME'],
			$student['GRADE_ID'],
			SchoolInfo( 'TITLE' ),
			$_REQUEST['subject']),$honor_roll_text);

			echo '<tr><td>'.$honor_roll_text.'</td></tr></table>';

			echo '<br /><table style="margin:auto auto; width:80%;">';
			echo '<tr><td><span style="font-size:x-large;">'.$student['TEACHER'].'</span><br /><span style="font-size:medium;">'._('Teacher').'</span></td>';
			echo '<td><span style="font-size:x-large;">'.$mp_RET[1]['TITLE'].'</span><br /><span style="font-size:medium;">'._('Marking Period').'</span></td></tr>';

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
	else
		BackPrompt(_('You must choose at least one student.'));
}

if ( ! $_REQUEST['modfunc'] )
{
	DrawHeader(ProgramTitle());

	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&include_inactive='.$_REQUEST['include_inactive'].'&_ROSARIO_PDF=true" method="POST">';
		$extra['header_right'] = SubmitButton(_('Create Honor Roll by Subject for Selected Students'));

		//FJ add Template
		$templates = DBGet( DBQuery( "SELECT TEMPLATE, STAFF_ID
			FROM TEMPLATES WHERE MODNAME = '" . $_REQUEST['modname'] . "'
			AND STAFF_ID IN (0,'" . User( 'STAFF_ID' ) . "')" ), array(), array( 'STAFF_ID' ) );

		$extra['extra_header_left'] = '<table><tr class="st">
		<td class="valign-top">' . _( 'Text' ) . '</td>
		<td class="width-100p">' .
		TinyMCEInput(
			( isset( $templates[ User( 'STAFF_ID' ) ] ) ?
				$templates[ User( 'STAFF_ID' ) ][1]['TEMPLATE'] :
				$templates[0][1]['TEMPLATE'] ),
			'honor_roll_text',
			'',
			'class="tinymce-horizontal"'
		) . '</td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td class="valign-top">'._('Substitutions').':</td><td><table><tr class="st">';
		$extra['extra_header_left'] .= '<td>__FULL_NAME__</td><td>= '._('Last, First M').'</td><td>&nbsp;</td>';
		$extra['extra_header_left'] .= '<td>__LAST_NAME__</td><td>= '._('Last Name').'</td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td>__FIRST_NAME__</td><td>= '._('First Name').'</td><td>&nbsp;</td>';
		$extra['extra_header_left'] .= '<td>__MIDDLE_NAME__</td><td>= '._('Middle Name').'</td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td>__SCHOOL_ID__</td><td>= '._('School').'</td><td>&nbsp;</td>';
		$extra['extra_header_left'] .= '<td>__GRADE_ID__</td><td>= '._('Grade Level').'</td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td>__CLIPART__</td><td>= '._('ClipArt').'</td><td colspan="3">&nbsp;</td>';
		$extra['extra_header_left'] .= '</tr></table></td></tr>';

		//FJ add frames choice
		$frames = array();
		if (is_dir('assets/Frames/'))
			$frames = scandir('assets/Frames/');

		//no frame first and checked
		$extra['extra_header_left'] .= '<tr class="st">
		<td style="vertical-align:top;">'._('Frame').'</td>
		<td><div style="overflow-x:auto; height:160px;" id="framesList">
			<table class="cellspacing-0"><tr>
			<td class="image-radio-list" style="height: auto;"><label class="image-radio-list"><input type="radio" name="frame" value="" checked /> '._('No frame').'</label></td>';

		//create radio list with thumbnails
		$i = 1;
		foreach ($frames as $frame)
		{
			//filter images
			if ( in_array( mb_strtolower(mb_strrchr($frame, '.')), array('.jpg', '.jpeg', '.png', '.gif') ) )
			{
				$extra['extra_header_left'] .= '<td class="image-radio-list"><label class="image-radio-list"><input type="radio" name="frame" value="'.$frame.'"> <img src="assets/Frames/'.$frame.'" class="image-radio-list" title="'.UCWords(str_replace(array('_', '.jpg', '.jpeg', '.png', '.gif'),array(' ', ''), $frame)).'" /></label></td>';
				$i++;
			}
		}

		$extra['extra_header_left'] .= '</tr></table></div></td></tr><tr><td colspan="2">&nbsp;</td></tr>';

		//FJ add clipart choice
		$cliparts = array();
		if (is_dir('assets/ClipArts/'))
			$cliparts = scandir('assets/ClipArts/');

		//no clipart first and checked
		$extra['extra_header_left'] .= '<tr class="st">
		<td style="vertical-align:top;">'._('ClipArt').'</td>
		<td><div style="overflow-x:auto; height:160px;" id="clipartsList">
			<table class="cellspacing-0"><tr>
			<td class="image-radio-list" style="height: auto;"><label class="image-radio-list"><input type="radio" name="clipart" value="" checked /> '._('No ClipArt').'</label></td>';

		//create radio list with thumbnails
		$i = 1;
		foreach ($cliparts as $clipart)
		{
			//filter images
			if ( in_array( mb_strtolower(mb_strrchr($clipart, '.')), array('.jpg', '.jpeg', '.png', '.gif') ) )
			{
				$extra['extra_header_left'] .= '<td class="image-radio-list"><label class="image-radio-list"><input type="radio" name="clipart" value="'.$clipart.'"> <img src="assets/ClipArts/'.$clipart.'" class="image-radio-list" title="'.UCWords(str_replace(array('_', '.jpg', '.jpeg', '.png', '.gif'),array(' ', ''), $clipart)).'" /></label></td>';
				$i++;
			}
		}

		$extra['extra_header_left'] .= '</tr></table></div></td></tr></table>';

		$extra['extra_header_left'] .= '<script>if (isTouchDevice()) {touchScroll(document.getElementById(\'framesList\')); touchScroll(document.getElementById(\'clipartsList\'));}</script>';
	}

	$extra['new'] = true;

	if ( !isset($_REQUEST['_ROSARIO_PDF']))
	{
		$extra['SELECT'] = ",s.STUDENT_ID AS CHECKBOX";
		$extra['functions'] = array('CHECKBOX' => '_makeChooseCheckbox');
		$extra['columns_before'] = array('CHECKBOX' => '</a><input type="checkbox" value="Y" name="controller" checked onclick="checkAll(this.form,this.checked,\'st_arr\');"><A>');
	}

	$extra['link'] = array('FULL_NAME'=>false);
	$extra['options']['search'] = false;

	MyWidgets('honor_roll_subject');

	if ( $for_news_web)
		$extra['student_fields'] = array('search'=>"'".$for_news_web."'",'view'=>"'".$for_news_web."'");

	Search('student_id',$extra);

	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<br /><div class="center">' . SubmitButton(_('Create Honor Roll by Subject for Selected Students')) . '</div>';
		echo '</form>';
	}
}

function _makeChooseCheckbox($value,$title)
{
	return '<input type="checkbox" name="st_arr[]" value="'.$value.'" checked />';
}

function MyWidgets($item)
{	global $extra,$_ROSARIO;

	switch ( $item)
	{
		case 'honor_roll_subject':
			if ( !empty($_REQUEST['subject_id']))
			{
				$extra['WHERE'] .=  " AND exists(SELECT ''
				FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp, COURSES c
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='".UserMP()."'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y'
				AND cp.COURSE_ID=c.COURSE_ID
				AND c.SUBJECT_ID='".$_REQUEST['subject_id']."')";

				$extra['WHERE'] .= " AND NOT exists(SELECT ''
				FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='".UserMP()."'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y'
				AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
				AND sg.REPORT_CARD_GRADE_ID=rg.ID
				AND rg.GPA_VALUE<(SELECT HRS_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";

				if ( ! $extra['NoSearchTerms'])
				{
					$subject_RET = DBGet(DBQuery("SELECT TITLE FROM COURSE_SUBJECTS WHERE SUBJECT_ID='".$_REQUEST['subject_id']."' AND SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));

					$_ROSARIO['SearchTerms'] .= '<b>'._('Subject').':</b> '.$subject_RET[1]['TITLE'];
					$_ROSARIO['SearchTerms'] .= '<input type="hidden" id="subject" name="subject" value="'.str_replace('"','&quot;',$subject_RET[1]['TITLE']).'" /><br />';
				}
			}

			$subjects_RET = DBGet(DBQuery("SELECT SUBJECT_ID,TITLE FROM COURSE_SUBJECTS WHERE SCHOOL_ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));
			$select = '<select name="subject_id">';

			if (count($subjects_RET))
			{
				foreach ( (array) $subjects_RET as $subject)
					$select .= '<option value="'.$subject['SUBJECT_ID'].'">'.$subject['TITLE'].'</option>';
			}

			$select .= '</select>';
			$extra['search'] .= '<tr><td>'._('Subject').'</td><td>'.$select.'</td></tr>';
		break;
	}
}
