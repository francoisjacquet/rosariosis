<?php

DrawHeader(ProgramTitle());

//$extra['header_left'] .= sprintf(_('Include courses active as of %s'),PrepareDate('','_include_active_date'));

MyWidgets('birthmonth');
MyWidgets('birthday');
include('modules/misc/Export.php');

function MyWidgets($item)
{	global $extra,$_ROSARIO;

	switch($item)
	{
		case 'birthmonth':
			$options = array('1'=>_('January'),'2'=>_('February'),'3'=>_('March'),'4'=>_('April'),'5'=>_('May'),'6'=>_('June'),'7'=>_('July'),'8'=>_('August'),'9'=>_('September'),'10'=>_('October'),'11'=>_('November'),'12'=>_('December'));
			if($_REQUEST['birthmonth'])
			{
				$extra['SELECT'] .= ",to_char(s.CUSTOM_200000004,'Mon DD') AS BIRTHMONTH";
				$extra['WHERE'] .= " AND extract(month from s.CUSTOM_200000004)='$_REQUEST[birthmonth]'";
				$extra['columns_after']['BIRTHMONTH'] = _('Birth Month');
				if(!$extra['NoSearchTerms'])
					$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.Localize('colon',_('Birth Month')).' </b></span>'.$options[$_REQUEST['birthmonth']].'<BR />';
			}
			$extra['search'] .= '<TR><TD style="text-align:right;"><label for="birthmonth">'._('Birth Month').'</label></TD><TD><SELECT name="birthmonth" id="birthmonth"><OPTION value="">'._('N/A');
			foreach($options as $key=>$val)
				 $extra['search'] .= '<OPTION value="'.$key.'">'.$val;
			$extra['search'] .= '</SELECT></TD></TR>';
		break;
		case 'birthday':
			$options = array('1'=>'1','2'=>'2','3'=>'3','4'=>'4','5'=>'5','6'=>'6','7'=>'7','8'=>'8','9'=>'9','10'=>'10','11'=>'11','12'=>'12','13'=>'13','14'=>'14','15'=>'15','16'=>'16','17'=>'17','18'=>'18','19'=>'19','20'=>'20','21'=>'21','22'=>'22','23'=>'23','24'=>'24','25'=>'25','26'=>'26','27'=>'27','28'=>'28','29'=>'29','30'=>'30','31'=>'31');
			if($_REQUEST['birthday'])
			{
				$extra['SELECT'] .= ",to_char(s.CUSTOM_200000004,'DD') AS BIRTHDAY";
				$extra['WHERE'] .= " AND extract(day from s.CUSTOM_200000004)='$_REQUEST[birthday]'";
				$extra['columns_after']['BIRTHDAY'] = _('Birth Day');
				if(!$extra['NoSearchTerms'])
					$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.Localize('colon',_('Birth Day')).' </b></span>'.$options[$_REQUEST['birthday']].'<BR />';
			}
			$extra['search'] .= '<TR><TD style="text-align:right;"><label for="birthday">'._('Birth Day').'</label></TD><TD><SELECT name="birthday" id="birthday"><OPTION value="">'._('N/A');
			foreach($options as $key=>$val)
				 $extra['search'] .= '<OPTION value="'.$key.'">'.$val;
			$extra['search'] .= '</SELECT></TD></TR>';
		break;
	}
}
?>