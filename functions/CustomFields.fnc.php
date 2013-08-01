<?php

/*
	Call in an SQL statement to select students based on custom fields
	Use in the where section of the query by CustomFIelds('where')
*/

//modif Francois: fix error Warning: Missing argument 3 for CustomFields()
//function CustomFields($location,$type='student',$extra)
function CustomFields($location,$type='student',$extra=array())
{	global $_ROSARIO;
	if(count($_REQUEST['month_cust_begin']))
	{
		foreach($_REQUEST['month_cust_begin'] as $field_name=>$month)
		{
			$_REQUEST['cust_begin'][$field_name] = $_REQUEST['day_cust_begin'][$field_name].'-'.$month.'-'.$_REQUEST['year_cust_begin'][$field_name];
			if(!VerifyDate($_REQUEST['cust_begin'][$field_name]))
				unset($_REQUEST['cust_begin'][$field_name]);
		}
	}
	unset($_REQUEST['month_cust_begin']); unset($_REQUEST['year_cust_begin']); unset($_REQUEST['day_cust_begin']);
	if(count($_REQUEST['month_cust_end']))
	{
		foreach($_REQUEST['month_cust_end'] as $field_name=>$month)
		{
			$_REQUEST['cust_end'][$field_name] = $_REQUEST['day_cust_end'][$field_name].'-'.$month.'-'.$_REQUEST['year_cust_end'][$field_name];
			if(!VerifyDate($_REQUEST['cust_end'][$field_name]))
				unset($_REQUEST['cust_end'][$field_name]);
		}
	}
	unset($_REQUEST['month_cust_end']); unset($_REQUEST['year_cust_end']); unset($_REQUEST['day_cust_end']);
	if(count($_REQUEST['cust']))
	{
		foreach($_REQUEST['cust'] as $key=>$value)
		{
			if($value=='')
				unset($_REQUEST['cust'][$key]);
		}
	}
	switch($location)
	{
		case 'from':
		break;

		case 'where':
		if(count($_REQUEST['cust']) || count($_REQUEST['cust_begin'] || count($_REQUEST['cust_null'])))
			$fields = ParseMLArray(DBGet(DBQuery("SELECT TITLE,ID,TYPE,SELECT_OPTIONS FROM ".($type=='staff'?'STAFF':'CUSTOM')."_FIELDS"),array(),array('ID')),'TITLE');

		if(count($_REQUEST['cust']))
		{
			foreach($_REQUEST['cust'] as $field_name => $value)
			{
				if($value!='')
				{
					switch($fields[mb_substr($field_name,7)][1]['TYPE'])
					{
						case 'radio':
							if(!$extra['NoSearchTerms'])
								$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].': </b></span>';
							if($value=='Y')
							{
								$string .= " AND s.$field_name='$value' ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= _('Yes');
							}
							elseif($value=='N')
							{
								$string .= " AND (s.$field_name!='Y' OR s.$field_name IS NULL) ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= _('No');
							}
							if(!$extra['NoSearchTerms'])
								$_ROSARIO['SearchTerms'] .= '<BR />';
						break;

						case 'codeds':
							if(!$extra['NoSearchTerms'])
								$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].': </b></span>';
							if($value=='!')
							{
								$string .= " AND (s.$field_name='' OR s.$field_name IS NULL) ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= _('No Value');
							}
							else
							{
								$string .= " AND s.$field_name='$value' ";
								if(!$extra['NoSearchTerms'])
								{
									$select_options = str_replace("\n","\r",str_replace("\r\n","\r",$fields[mb_substr($field_name,7)][1]['SELECT_OPTIONS']));
									$select_options = explode("\r",$select_options);
									foreach($select_options as $option)
									{
										$option = explode('|',$option);
										if($option[0]!='' && $option[1]!='' && $value==$option[0])
										{
											$value = $option[1];
											break;
										}
									}
									$_ROSARIO['SearchTerms'] .= $value;
								}
							}
							if(!$extra['NoSearchTerms'])
								$_ROSARIO['SearchTerms'] .= '<BR />';
							break;

						case 'exports':
							if(!$extra['NoSearchTerms'])
								$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].': </b></span>';
							if($value=='!')
							{
								$string .= " AND (s.$field_name='' OR s.$field_name IS NULL) ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= _('No Value');
							}
							else
							{
								$string .= " AND s.$field_name='$value' ";
								if(!$extra['NoSearchTerms'])
								{
									$select_options = str_replace("\n","\r",str_replace("\r\n","\r",$fields[mb_substr($field_name,7)][1]['SELECT_OPTIONS']));
									$select_options = explode("\r",$select_options);
									foreach($select_options as $option)
									{
										$option = explode('|',$option);
										if($option[0]!='' && $value==$option[0])
										{
											$value = $option[0];
											break;
										}
									}
									$_ROSARIO['SearchTerms'] .= $value;
								}
							}
							if(!$extra['NoSearchTerms'])
								$_ROSARIO['SearchTerms'] .= '<BR />';
							break;

						case 'select':
							if(!$extra['NoSearchTerms'])
								$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].': </b></span>';
							if($value=='!')
							{
								$string .= " AND (s.$field_name='' OR s.$field_name IS NULL) ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= _('No Value');
							}
							else
							{
								$string .= " AND s.$field_name='$value' ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= $value;
							}
							if(!$extra['NoSearchTerms'])
								$_ROSARIO['SearchTerms'] .= '<BR />';
							break;

						case 'autos':
							if(!$extra['NoSearchTerms'])
								$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].': </b></span>';
							if($value=='!')
							{
								$string .= " AND (s.$field_name='' OR s.$field_name IS NULL) ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= _('No Value');
							}
							else
							{
								$string .= " AND s.$field_name='$value' ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= $value;
							}
							if(!$extra['NoSearchTerms'])
								$_ROSARIO['SearchTerms'] .= '<BR />';
							break;

						case 'edits':
							if(!$extra['NoSearchTerms'])
								$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].': </b></span>';
							if($value=='!')
							{
								$string .= " AND (s.$field_name='' OR s.$field_name IS NULL) ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= _('No Value');
							}
							elseif($value=='~')
							{
								$string .= " AND position('\r'||s.$field_name||'\r' IN '\r'||(SELECT SELECT_OPTIONS FROM ".($type=='staff'?'STAFF':'CUSTOM')."_FIELDS WHERE ID='".mb_substr($field_name,7)."')||'\r')=0 ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= _('Other Value');
							}
							else
							{
								$string .= " AND s.$field_name='$value' ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= $value;
							}
							if(!$extra['NoSearchTerms'])
								$_ROSARIO['SearchTerms'] .= '<BR />';
							break;

						case 'text':
							if($value=='!')
							{
								$string .= " AND (s.$field_name='' OR s.$field_name IS NULL) ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].': </b></span>'._('No Value').'<BR />';
							}
							elseif(mb_substr($value,0,2)=='\"' && mb_substr($value,-2)=='\"')
							{
								$string .= " AND s.$field_name='".mb_substr($value,2,-2)."' ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].': </b></span>'.mb_substr($value,2,-2).'<BR />';
							}
							else
							{
								$string .= " AND LOWER(s.$field_name) LIKE '".mb_strtolower($value)."%' ";
								if(!$extra['NoSearchTerms'])
									$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].'&nbsp;'.Localize('colon',_('starts with')).' </b></span>'.str_replace("''", "'", $value).'<BR />';
							}
						break;
					}
				}
			}
		}
		if(count($_REQUEST['cust_begin']))
		{
			foreach($_REQUEST['cust_begin'] as $field_name=>$value)
			{
				if($fields[mb_substr($field_name,7)][1]['TYPE']=='numeric')
					$value = preg_replace('/[^0-9.-]+/','',$value);

				if($value!='')
				{
					$string .= " AND s.$field_name >= '$value' ";
					if(!$extra['NoSearchTerms'])
						if($fields[mb_substr($field_name,7)][1]['TYPE']=='date')
							$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].' <span class="sizep2">&ge;</span> </b></span>'.ProperDate($value).'<BR />';
						else
							$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].' <span class="sizep2">&ge;</span> </b></span>'.$value.'<BR />';
				}
			}
		}
		if(count($_REQUEST['cust_end']))
		{
			foreach($_REQUEST['cust_end'] as $field_name=>$value)
			{
				if($fields[mb_substr($field_name,7)][1]['TYPE']=='numeric')
					$value = preg_replace('/[^0-9.-]+/','',$value);

				if($value!='')
				{
					$string .= " AND s.$field_name <= '$value' ";
					if(!$extra['NoSearchTerms'])
						if($fields[mb_substr($field_name,7)][1]['TYPE']=='date')
							$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].' <span class="sizep2">&le;</span> </b></span>'.ProperDate($value).'<BR />';
						else
							$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].' <span class="sizep2">&le;</span> </b></span>'.$value.'<BR />';
				}
			}
		}
		if(count($_REQUEST['cust_null']))
		{
			foreach($_REQUEST['cust_null'] as $field_name=>$y)
			{
				$string .= " AND s.$field_name IS NULL ";
				if(!$extra['NoSearchTerms'])
					$_ROSARIO['SearchTerms'] .= '<span style="color:gray"><b>'.$fields[mb_substr($field_name,7)][1]['TITLE'].': </b></span>'._('No Value').'<BR />';
			}
		}

		break;
	}
		return $string;
}
?>