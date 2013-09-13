<?php

$max_cols = 3;
$max_rows = 10;
$to_family = Localize('colon',_('To the parents of'));

if(isset($_REQUEST['modfunc']) && $_REQUEST['modfunc']=='save')
{
	if(count($_REQUEST['st_arr']))
	{
		$st_list = '\''.implode('\',\'',$_REQUEST['st_arr']).'\'';
		$extra['WHERE'] = " AND s.STUDENT_ID IN ($st_list)";

		$_REQUEST['mailing_labels']='Y';
		if($_REQUEST['to_address'])
			$_REQUEST['residence']='Y';
		Widgets('mailing_labels');
		$extra['SELECT'] .= ",s.FIRST_NAME AS NICK_NAME";
		$extra['group'] = array('ADDRESS_ID');
		$RET = GetStuList($extra);

		if(count($RET))
		{
			$handle = PDFstart();
			//echo '<!-- MEDIA SIZE 8.5x11in -->';
			echo '<!-- MEDIA TOP 0.5in -->';
			echo '<!-- MEDIA BOTTOM 0.25in -->';
			echo '<!-- MEDIA LEFT 0.25in -->';
			echo '<!-- MEDIA RIGHT 0.25in -->';
			echo '<!-- FOOTER RIGHT "" -->';
			echo '<!-- FOOTER LEFT "" -->';
			echo '<!-- FOOTER CENTER "" -->';
			echo '<!-- HEADER RIGHT "" -->';
			echo '<!-- HEADER LEFT "" -->';
			echo '<!-- HEADER CENTER "" -->';
			echo '<table style="height: 100%" class="width-100p cellspacing-0 cellpadding-0">';

			$cols = 0;
			$rows = 0;
			for($i=-(($_REQUEST['start_row']-1)*$max_cols+$_REQUEST['start_col']-1);$i<count($RET);$i++)
			{
				if($i>=0)
				{
					$addresses = current($RET);
					next($RET);
					if($_REQUEST['to_address']=='student')
					{
						foreach($addresses as $key=>$address)
						{
							if($_REQUEST['student_name']=='given')
								$name = $address['LAST_NAME'].', '.$address['FIRST_NAME'].' '.$address['MIDDLE_NAME'];
							elseif($_REQUEST['student_name']=='given_natural')
								$name = $address['FIRST_NAME'].' '.$address['LAST_NAME'];
							else
								$name = $address['FULL_NAME'];
							$addresses[$key]['MAILING_LABEL'] = $name.'<BR />'.mb_substr($address['MAILING_LABEL'],mb_strpos($address['MAILING_LABEL'],'<!-- -->'));
						}
					}
					elseif($_REQUEST['to_address']=='family')
					{
						// if grouping by address, replace people list in mailing labels with students list
						$lasts = array();
						foreach($addresses as $address)
							$lasts[$address['LAST_NAME']][] = $address['FIRST_NAME'];
						$students = '';
						foreach($lasts as $last=>$firsts)
						{
							$student = '';
							$previous = '';
							foreach($firsts as $first)
							{
								if($student && $previous)
									$student .= ', '.$previous;
								elseif($previous)
									$student = $previous;
								$previous = $first;
							}
							if($student)
								$student .= ' & '.$previous.' '.$last;
							else
								$student = $previous.' '.$last;
							$students .= $student.', ';
						}
						$addresses = array(1=>array('MAILING_LABEL'=>''.$to_family.'<BR />'.mb_substr($students,0,-2).'<BR />'.mb_substr($addresses[1]['MAILING_LABEL'],mb_strpos($addresses[1]['MAILING_LABEL'],'<!-- -->'))));
					}
				}
				else
					$addresses = array(1=>array('MAILING_LABEL'=>' '));

				foreach($addresses as $address)
				{
					if(!$address['MAILING_LABEL'])
						continue;

					if($cols<1)
						echo '<tr>';
					echo '<td style="text-align:center; width:33%; vertical-align: middle;">';
					echo $address['MAILING_LABEL'];
					echo '</td>';

					$cols++;

					if($cols==$max_cols)
					{
						echo '</tr>';
						$rows++;
						$cols = 0;
					}

					if($rows==$max_rows)
					{
						echo '</table><!--NEW PAGE -->';
						echo '<table style="height: 100%" class="width-100p cellspacing-0 cellpadding-0">';
						$rows = 0;
					}
				}
			}

			if ($cols==0 && $rows==0)
			{}
			else
			{
				while ($cols!=0 && $cols<$max_cols)
				{
					echo '<td style="text-align:center; width:33%; height:86px; vertical-align: middle;">&nbsp;</td>';
					$cols++;
				}
				if ($cols==$max_cols)
					echo '</tr>';
				echo '</table>';
			}
			//echo '</body></html>';
			echo '</body>';
			PDFstop($handle);
		}
		else
			BackPrompt(_('No Students were found.'));
	}
	else
		BackPrompt(_('You must choose at least one student.'));
}

if(empty($_REQUEST['modfunc']))

{
	DrawHeader(ProgramTitle());

	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<FORM action="Modules.php?modname='.$_REQUEST['modname'].'&modfunc=save&include_inactive='.$_REQUEST['include_inactive'].'&_search_all_schools='.$_REQUEST['_search_all_schools'].'&_ROSARIO_PDF=true" method="POST">';
		$extra['header_right'] = '<INPUT type="submit" value="'._('Create Labels for Selected Students').'">';

		$extra['extra_header_left'] = '<TABLE>';

//modif Francois: add translation
		$extra['extra_header_left'] .= '<TR><TD colspan="5"><b>'._('Address Labels').':</b></TD></TR>';
//modif Francois: add <label> on radio
		$extra['extra_header_left'] .= '<TR class="st"><TD><label><INPUT type="radio" name="to_address" value="" checked /> '._('To Contacts').'</label></TD>';
//modif Francois: disable mailing address display
		if (Config('STUDENTS_USE_MAILING'))
		{
			$extra['extra_header_left'] .= '<TD><label><INPUT type="radio" name="residence" value="" checked /> '._('Mailing').'</label></TD>';
			$extra['extra_header_left'] .= '<TD><label><INPUT type="radio" name="residence" value="Y" /> '._('Residence').'</label></TD>';
		}
		else
		{
			$extra['extra_header_left'] .= '<INPUT type="hidden" name="residence" value="Y" />';
		}
		$extra['extra_header_left'] .= '<TD colspan="2"></TD></TR>';
		$extra['extra_header_left'] .= '<TR class="st"><TD><label><INPUT type="radio" name="to_address" value="student" /> '._('To Student').'</label></TD>';
		$extra['extra_header_left'] .= '<TD><label><INPUT type="radio" name="student_name" value="given" checked /> '._('Last, Given Middle').'</label></TD>';
		$extra['extra_header_left'] .= '<TD><label><INPUT type="radio" name="student_name" value="given_natural" /> '._('Given Last').'</label></TD>';
		$extra['extra_header_left'] .= '<TR><TD><label><INPUT type="radio" name="to_address" value="family" /> '._('To the parents of').'</label></TD>';

		$extra['extra_header_left'] .= '<TD colspan="2"></TD></TR>';
		$extra['extra_header_left'] .= '</TABLE>';
		$extra['extra_header_right'] = '<TABLE>';

		$extra['extra_header_right'] .= '<TR class="st"><TD style="text-align:right">'._('Starting row').'</TD><TD><SELECT name="start_row">';
		for($row=1; $row<=$max_rows; $row++)
			$extra['extra_header_right'] .=  '<OPTION value="'.$row.'">'.$row;
		$extra['extra_header_right'] .=  '</SELECT></TD></TR>';
		$extra['extra_header_right'] .= '<TR class="st"><TD style="text-align:right">'._('Starting column').'</TD><TD><SELECT name="start_col">';
		for($col=1; $col<=$max_cols; $col++)
			$extra['extra_header_right'] .=  '<OPTION value="'.$col.'">'.$col;
		$extra['extra_header_right'] .= '</SELECT></TD></TR>';

		$extra['extra_header_right'] .= '</TABLE>';
	}

	//Widgets('course');
	//Widgets('request');
	//Widgets('activity');
	//Widgets('absences');
	//Widgets('gpa');
	//Widgets('class_rank');
	//Widgets('letter_grade');
	//Widgets('eligibility');
	//$extra['force_search'] = true;

	$extra['SELECT'] .= ",s.STUDENT_ID AS CHECKBOX";
	$extra['link'] = array('FULL_NAME'=>false);
	$extra['functions'] = array('CHECKBOX'=>'_makeChooseCheckbox');
	$extra['columns_before'] = array('CHECKBOX'=>'</A><INPUT type="checkbox" value="Y" name="controller" checked onclick="checkAll(this.form,this.form.controller.checked,\'st_arr\');"><A>');
	$extra['options']['search'] = false;
	$extra['new'] = true;

	Search('student_id',$extra);
	if($_REQUEST['search_modfunc']=='list')
	{
		echo '<BR /><span class="center">'.SubmitButton(_('Create Labels for Selected Students')).'</span>';
		echo "</FORM>";
	}
}

function _makeChooseCheckbox($value,$title)
{
	return '&nbsp;&nbsp;<INPUT type="checkbox" name="st_arr[]" value="'.$value.'" checked />';
}
?>
