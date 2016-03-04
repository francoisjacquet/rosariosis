<?php

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

if (isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if (count($_REQUEST['st_arr']))
	{
		if (empty($_REQUEST['list']))//certificate
		{
			$REQUEST_honor_roll_text = SanitizeHTML( $_POST['honor_roll_text'] );
		}

		$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
		$extra['WHERE'] = " AND s.STUDENT_ID IN (".$st_list.")";

		$mp_RET = DBGet(DBQuery("SELECT TITLE,END_DATE FROM SCHOOL_MARKING_PERIODS WHERE MP='QTR' AND MARKING_PERIOD_ID='".UserMP()."'"));
		$school_info_RET = DBGet(DBQuery("SELECT TITLE,PRINCIPAL FROM SCHOOLS WHERE ID='".UserSchool()."' AND SYEAR='".UserSyear()."'"));

		$extra['SELECT'] = ",s.FIRST_NAME AS NICK_NAME";
		$extra['SELECT'] .= ",(SELECT SORT_ORDER FROM SCHOOL_GRADELEVELS WHERE ID=ssm.GRADE_ID) AS SORT_ORDER";
		$extra['SELECT'] .= ",".db_case(array("exists(SELECT rg.GPA_VALUE
		FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg
		WHERE sg.STUDENT_ID=s.STUDENT_ID
		AND cp.SYEAR=ssm.SYEAR
		AND sg.SYEAR=ssm.SYEAR
		AND sg.MARKING_PERIOD_ID='".UserMP()."'
		AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
		AND cp.DOES_HONOR_ROLL='Y'
		AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
		AND sg.REPORT_CARD_GRADE_ID=rg.ID
		AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))",'true','NULL',"'Y'"))." AS HIGH_HONOR";

		//$extra['SELECT'] .= ",(SELECT TITLE FROM SCHOOLS WHERE ID=ssm.SCHOOL_ID AND SYEAR=ssm.SYEAR) AS SCHOOL";
		//$extra['SELECT'] .= ",(SELECT PRINCIPAL FROM SCHOOLS WHERE ID=ssm.SCHOOL_ID AND SYEAR=ssm.SYEAR) AS PRINCIPAL";
		//FJ multiple school periods for a course period
		//$extra['SELECT'] .= ",(SELECT coalesce(st.TITLE||' ',' ')||st.FIRST_NAME||coalesce(' '||st.MIDDLE_NAME||' ',' ')||st.LAST_NAME FROM STAFF st,COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss WHERE st.STAFF_ID=cp.TEACHER_ID AND cp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y' AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID AND ss.STUDENT_ID=s.STUDENT_ID AND ss.SYEAR='".UserSyear()."' AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).") AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS TEACHER";
		$extra['SELECT'] .= ",(SELECT st.FIRST_NAME||coalesce(' '||st.MIDDLE_NAME||' ',' ')||st.LAST_NAME
		FROM STAFF st,COURSE_PERIODS cp,SCHEDULE ss
		WHERE st.STAFF_ID=cp.TEACHER_ID
		AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
		AND ss.STUDENT_ID=s.STUDENT_ID
		AND ss.SYEAR='".UserSyear()."'
		AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).")
		AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) LIMIT 1) AS TEACHER";

		$extra['SELECT'] .= ",(SELECT cp.ROOM
		FROM COURSE_PERIODS cp,SCHOOL_PERIODS p,SCHEDULE ss,COURSE_PERIOD_SCHOOL_PERIODS cpsp
		WHERE cp.COURSE_PERIOD_ID=cpsp.COURSE_PERIOD_ID
		AND cpsp.PERIOD_id=p.PERIOD_ID AND p.ATTENDANCE='Y'
		AND cp.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID
		AND ss.STUDENT_ID=s.STUDENT_ID
		AND ss.SYEAR='".UserSyear()."'
		AND ss.MARKING_PERIOD_ID IN (".GetAllMP('QTR',GetCurrentMP('QTR',DBDate(),false)).")
		AND (ss.START_DATE<='".DBDate()."' AND (ss.END_DATE>='".DBDate()."' OR ss.END_DATE IS NULL)) ORDER BY p.SORT_ORDER LIMIT 1) AS ROOM";

		$extra['ORDER_BY'] = 'HIGH_HONOR,SORT_ORDER DESC,ROOM,FULL_NAME';

		if ( $_REQUEST['list'])
			$extra['group'] = array('HIGH_HONOR');

		$RET = GetStuList($extra);

		if ( $_REQUEST['list'])
		{
			$handle = PDFStart();
			echo '<table class="center" style="width:80%;">';
			echo '<tr class="center"><td colspan="6"><b>'.sprintf(_('%s Honor Roll'),$school_info_RET[1]['TITLE']).' </b> - '.$mp_RET[1]['TITLE'].' - '.date('F j, Y',strtotime($mp_RET[1]['END_DATE'])).'</td></tr>';
			echo '<tr class="center"><td colspan="6">&nbsp;</td></tr>';

			foreach ( array('Y','') AS $high)
			{
				if ( $n = count($RET[ $high ]))
				{
					$n = (int) (($n+1)/2);
					echo '<tr class="center"><td colspan="6" style="background-color:#C0C0C0;"><b>'.($high=='Y'?_('High Honor Roll'):_('Honor Roll')).'</b></td></tr>';

					for ( $i=1; $i<=$n; $i++)
					{
						echo '<tr><td>&nbsp;</td>';
						$student = $RET[ $high ][ $i ];
						echo '<td>'.$student['NICK_NAME'].' '.$student['LAST_NAME'].'</td><td>'.$student['ROOM'].'</td>';
						echo '<td>&nbsp;</td>';
						$student = $RET[ $high ][$i+$n];
						echo '<td>'.$student['NICK_NAME'].' '.$student['LAST_NAME'].'</td><td>'.$student['ROOM'].'</td></tr>';
					}

					echo '<tr class="center"><td colspan="6">&nbsp;</td></tr>';
				}
			}

			echo '</table>';
			PDFStop($handle);
		}
		else
		{
			//FJ add Template
			$template_update = DBGet(DBQuery("SELECT 1 FROM TEMPLATES WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID = '".User('STAFF_ID')."'"));

			if ( ! $template_update)
				DBQuery("INSERT INTO TEMPLATES (MODNAME, STAFF_ID, TEMPLATE) VALUES ('".$_REQUEST['modname']."', '".User('STAFF_ID')."', '".$REQUEST_honor_roll_text."')");
			else
				DBQuery("UPDATE TEMPLATES SET TEMPLATE = '".$REQUEST_honor_roll_text."' WHERE MODNAME = '".$_REQUEST['modname']."' AND STAFF_ID = '".User('STAFF_ID')."'");

			$no_margins = array( 'top' => 0, 'bottom' => 0, 'left' => 0, 'right' => 0 );

			$pdf_options = array(
				'css' => false,
				'margins' => $no_margins,
			);

			$handle = PDFStart( $pdf_options );

			$_SESSION['orientation'] = 'landscape';

			foreach ( (array) $RET as $student)
			{
				echo '<style type="text/css">
					body {
						margin:0;
						padding:0;
						width:100%;
						height:100%;';

				if ( isset( $_REQUEST['frame'] ) )
				{
					echo 'background:url(assets/Frames/' . $_REQUEST['frame'] . ') no-repeat;
						background-size:100% 100%;';
				}

				echo '}</style>';

				echo '<table style="margin:auto auto;">';
				
				$honor_roll_text = $REQUEST_honor_roll_text;

				$honor_roll_text = str_replace(array('__FULL_NAME__',
				'__FIRST_NAME__',
				'__LAST_NAME__',
				'__MIDDLE_NAME__',
				'__GRADE_ID__',
				'__SCHOOL_ID__',
				'__SUBJECT__'),
				array($student['FULL_NAME'],
				$student['FIRST_NAME'],
				$student['LAST_NAME'],
				$student['MIDDLE_NAME'],
				$student['GRADE_ID'],
				$school_info_RET[1]['TITLE'],
				$_REQUEST['subject']),$honor_roll_text);

				$honor_roll_text = ($student['HIGH_HONOR']=='Y'? str_replace(_('Honor Roll'),_('High Honor Roll'),$honor_roll_text) : $honor_roll_text);
				
				echo '<tr><td>'.$honor_roll_text.'</td></tr></table>';
				
				echo '<br /><table style="margin:auto auto; width:80%;">';

				echo '<tr><td><span style="font-size:x-large;">'.$student['TEACHER'].'</span><br /><span style="font-size:medium;">'._('Teacher').'</span></td>';
				echo '<td><span style="font-size:x-large;">'.$mp_RET[1]['TITLE'].'</span><br /><span style="font-size:medium;">'._('Marking Period').'</span></td></tr>';

				echo '<tr><td><span style="font-size:x-large;">'.$school_info_RET[1]['PRINCIPAL'].'</span><br /><span style="font-size:medium;">'._('Principal').'</span></td>';
				echo '<td><span style="font-size:x-large;">' .
					ProperDate( $mp_RET[1]['END_DATE'] ) .
					'</span><br />
					<span style="font-size:medium;">' . _( 'Date' ) . '</span></td></tr>';

				echo '</table></div>';

				echo '<div style="page-break-after: always;"></div>';
			}

			PDFStop($handle);
		}
	}
	else
		BackPrompt(_('You must choose at least one student.'));
}

if (empty($_REQUEST['modfunc']))
{
	DrawHeader(ProgramTitle());

	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<form action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&include_inactive='.$_REQUEST['include_inactive'].'&_ROSARIO_PDF=true" method="POST">';
		$extra['header_right'] = SubmitButton(_('Create Honor Roll for Selected Students'));

		$extra['extra_header_left'] = '<table>';

		//FJ add <label> on radio
		$extra['extra_header_left'] .= '<tr><td><label><input type="radio" name="list" value="list"> '._('List').'</label></td></tr>';

		$extra['extra_header_left'] .= '<tr><td><label><input type="radio" name="list" value="" checked /> '._('Certificates').':</label></td></tr>';

		//FJ add Template
		$templates = DBGet( DBQuery( "SELECT TEMPLATE, STAFF_ID
			FROM TEMPLATES WHERE MODNAME = '" . $_REQUEST['modname'] . "'
			AND STAFF_ID IN (0,'" . User( 'STAFF_ID' ) . "')" ), array(), array( 'STAFF_ID' ) );

		//FJ add TinyMCE to the textarea
		$extra['extra_header_left'] .= '<tr><td>&nbsp;</td></tr>
		<tr class="st"><td style="vertical-align: top;">'._('Text').'</td>
		<td>' .
		TinyMCEInput(
			( isset( $templates[ User( 'STAFF_ID' ) ] ) ?
				$templates[ User( 'STAFF_ID' ) ][1]['TEMPLATE'] :
				$templates[0][1]['TEMPLATE'] ),
			'honor_roll_text'
		) . '</td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td style="vertical-align: top;">'._('Substitutions').':</td><td><table><tr class="st">';
		$extra['extra_header_left'] .= '<td>__FULL_NAME__</td><td>= '._('Last, First M').'</td><td>&nbsp;</td>';
		$extra['extra_header_left'] .= '<td>__LAST_NAME__</td><td>= '._('Last Name').'</td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td>__FIRST_NAME__</td><td>= '._('First Name').'</td><td>&nbsp;</td>';
		$extra['extra_header_left'] .= '<td>__MIDDLE_NAME__</td><td>= '._('Middle Name').'</td></tr>';

		$extra['extra_header_left'] .= '<tr class="st"><td>__SCHOOL_ID__</td><td>= '._('School').'</td><td>&nbsp;</td>';
		$extra['extra_header_left'] .= '<td>__GRADE_ID__</td><td>= '._('Grade Level').'</td>';
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
				//if ( $i % 5 == 0) //change table row each five thumbnails
					//$extra['extra_header_left'] .= '</tr><tr>';
				$extra['extra_header_left'] .= '<td class="image-radio-list"><label class="image-radio-list"><input type="radio" name="frame" value="'.$frame.'" /> <img src="assets/Frames/'.$frame.'" class="image-radio-list" title="'.UCWords(str_replace(array('_', '.jpg', '.jpeg', '.png', '.gif'),array(' ', ''), $frame)).'" /></label></td>';
				$i++;
			}
		}

		$extra['extra_header_left'] .= '</tr></table></div></td></tr></table>';

		$extra['extra_header_left'] .= '<script>if (isTouchDevice()) {touchScroll(document.getElementById(\'framesList\'));}</script>';

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

	Widgets('course');
	MyWidgets('honor_roll');

	if ( $for_news_web)
		$extra['student_fields'] = array('search'=>"'".$for_news_web."'",'view'=>"'".$for_news_web."'");
				
	Search('student_id',$extra);

	if ( $_REQUEST['search_modfunc']=='list')
	{
		echo '<br /><div class="center">' . SubmitButton(_('Create Honor Roll for Selected Students')) . '</div>';
		echo '</form>';
	}
}

function _makeChooseCheckbox($value,$title)
{
	if ( $_REQUEST['honor_roll']=='Y' || $_REQUEST['high_honor_roll']=='Y')
		return '<input type="checkbox" name="st_arr[]" value="'.$value.'" checked />';
	else
		return '';
}

function MyWidgets($item)
{	global $extra,$_ROSARIO;

	switch ( $item)
	{
		case 'honor_roll':
			if ( $_REQUEST['honor_roll']=='Y' && $_REQUEST['high_honor_roll']=='Y')
			{
				$extra['SELECT'] .= ",".db_case(array("exists(SELECT rg.GPA_VALUE
				FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='".UserMP()."'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y'
				AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
				AND sg.REPORT_CARD_GRADE_ID=rg.ID
				AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))",'true','NULL',"'".button('check')."'"))." AS HIGH_HONOR";

				$extra['WHERE'] .=  " AND exists(SELECT ''
				FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='".UserMP()."'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y')";

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
				AND rg.GPA_VALUE<(SELECT  HR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";

				$extra['columns_after']['HIGH_HONOR'] = _('High Honor');

				if ( ! $extra['NoSearchTerms'])
					//FJ add translation
					$_ROSARIO['SearchTerms'] .= '<b>'._('Honor Roll').' & '._('High Honor Roll').'</b><br />';
			}
			elseif ( $_REQUEST['honor_roll']=='Y')
			{
				$extra['WHERE'] .=  " AND exists(SELECT ''
				FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='".UserMP()."'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y')";

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
				AND rg.GPA_VALUE<(SELECT  HR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";

				$extra['WHERE'] .= " AND exists(SELECT ''
				FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp,REPORT_CARD_GRADES rg
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR
				AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='".UserMP()."'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y'
				AND rg.GRADE_SCALE_ID=cp.GRADE_SCALE_ID
				AND sg.REPORT_CARD_GRADE_ID=rg.ID
				AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";

				if ( ! $extra['NoSearchTerms'])
					$_ROSARIO['SearchTerms'] .= '<b>'._('Honor Roll').'</b><br />';
			}
			elseif ( $_REQUEST['high_honor_roll']=='Y')
			{
				$extra['WHERE'] .=  " AND exists(SELECT ''
				FROM STUDENT_REPORT_CARD_GRADES sg,COURSE_PERIODS cp
				WHERE sg.STUDENT_ID=s.STUDENT_ID
				AND cp.SYEAR=ssm.SYEAR AND sg.SYEAR=ssm.SYEAR
				AND sg.MARKING_PERIOD_ID='".UserMP()."'
				AND cp.COURSE_PERIOD_ID=sg.COURSE_PERIOD_ID
				AND cp.DOES_HONOR_ROLL='Y')";

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
				AND rg.GPA_VALUE<(SELECT HHR_GPA_VALUE FROM REPORT_CARD_GRADE_SCALES WHERE ID=rg.GRADE_SCALE_ID))";

				if ( ! $extra['NoSearchTerms'])
					$_ROSARIO['SearchTerms'] .= '<b>'._('High Honor Roll').'</b><br />';
			}

			//FJ add <label> on checkbox
			$extra['search'] .= '<tr>
			<td>'._('Honor Roll').'</td>
			<td><label><input type="checkbox" name="honor_roll" value="Y" checked /> '._('Honor').'</label> <label><input type="checkbox" name="high_honor_roll" value="Y" checked /> '._('High Honor').'</label></td>
			</tr>';
		break;
	}
}
