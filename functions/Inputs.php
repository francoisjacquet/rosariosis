<?php

function DateInput($value,$name,$title='',$div=true,$allow_na=true)
{
	if(Preferences('HIDDEN')!='Y')
		$div = false;

	if(AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
	{
		if($value=='' || $div==false)
			return PrepareDate($value,'_'.$name,$allow_na).($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');
		else
		{
			$return = '<DIV id="div'.$name.'"><div class="onclick" onclick=\'javascript:addHTML("';
			
			$toEscape = PrepareDate($value,'_'.$name,$allow_na,array('Y'=>1,'M'=>1,'D'=>1)).($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').str_replace("'",'&#39;',$title).(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':''):'');
			$return .=  str_replace('"','\"',$toEscape);
			
			$return .= '","div'.$name.'",true)\'><span class="underline-dots">'.($value!=''?ProperDate($value):'-').'</span>'.($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'').'</div></DIV>';
			return $return;
		}
	}
	else
		return ($value!=''?ProperDate($value):'-').($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');
}

function TextInput($value,$name,$title='',$options='',$div=true)
{
	if(Preferences('HIDDEN')!='Y')
		$div = false;

	// mab - support array style $option values
	if(AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
	{
		$value1 = is_array($value) ? $value[1] : $value;
		$value = is_array($value) ? $value[0] : $value;

		if(mb_strpos($options,'size')===false && $value!='')
			$options .= ' size='.mb_strlen($value);
		elseif(mb_strpos($options,'size')===false)
			$options .= ' size=10';

		if(trim($value)=='' || $div==false)
			return '<INPUT type="text" name="'.$name.'" id="'.$name.'" '.($value || $value==='0'?'value="'.str_replace(array("'",'"'),array('&#39;','&quot;'),$value).'"':'').' '.$options.' />'.($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').'<label for="'.$name.'">'.$title.'</label>'.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');
		else
		{
			$return = '<DIV id="div'.$name.'"><div class="onclick" onclick=\'javascript:addHTML(html'.str_replace(array('[',']'),'',$name);
			
			$toEscape = '<INPUT type="text" id="input'.$name.'" name="'.$name.'" '.($value||$value==='0'?'value="'.htmlspecialchars($value).'"':'').' '.$options.' />'.($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').'<label for="input'.$name.'">'.str_replace("'",'&#39;',$title).'</label>'.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');
			$return = '<script type="text/javascript">var html'.str_replace(array('[',']'),'',$name).'=\''.$toEscape.'\';</script>'.$return;
			
			$return .= ',"div'.$name.'",true); document.getElementById("input'.$name.'").focus();\'><span class="underline-dots">'.($value!=''?$value1:'-').'</span>'.($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'').'</div></DIV>';
			return $return;
		}
	}
	else
		return (((is_array($value)?$value[1]:$value)!='')?(is_array($value)?$value[1]:$value):'-').($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');
}

function MLTextInput($value,$name,$title='',$options='',$div=true)
{   global $RosarioLocales;

    if (sizeof($RosarioLocales) < 2)
        return TextInput($value,$name,$title,$options,$div);
        
    if(Preferences('HIDDEN')!='Y')
        $div = false;

    // mab - support array style $option values
    if(AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
    {
        $value1 = is_array($value) ? $value[1] : $value;
        $value = is_array($value) ? $value[0] : $value;

        if(mb_strpos($options,'size')===false && $value!='')
            $options .= ' size='.(mb_strlen($value) / (mb_substr_count($value, '|') + 1));
        elseif(mb_strpos($options,'size')===false)
            $options .= ' size=10';

        // ng - foreach possible language
		$ret = '<script type="text/javascript">
function setMLvalue(id,loc,value){
	res = document.getElementById(id).value.split("|");
	if(loc=="") {
		if (value == "") {
			alert("The first translation string cannot be empty.");
			value = "Something";
		}
		res[0] = value;
	} else {
		found = 0;
		for (i=1;i<res.length;i++) {
			if (res[i].substring(0,loc.length) == loc) {
				found = 1;
				if (value == "") {
					for (j=i+1;j<res.length;j++)
						res[j-1] = res[j];
					res.pop();
				} else {
					res[i] = loc+":"+value;
				}
			}
		}    
		if ((found == 0) && (value != "")) res.push(loc+":"+value);
	}
	document.getElementById(id).value = res.join("|");                                
}
</script>';
        $ret .= '<DIV><INPUT type="hidden" id="'.$name.'" name="'.$name.'" value="'.$value.'" />';
        
        foreach ($RosarioLocales as $id=>$loc) {
            $ret .= '<label><IMG src="assets/flags/'.$loc.'.png" height="24" style="vertical-align:middle;" /> ';
            $ret .= TextInput(ParseMLField($value, $loc),'ML_'.$name.'['.$loc.']','',$options." onchange=\"javascript:setMLvalue('$name','".($id==0?'':$loc)."',this.value);\"",false);
            $ret .= '</label><BR />';
        }
        $ret .= '</DIV>';
    }
//modif FRancois: css WPadmin
//    $ret .= ($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');
    $ret .= ($title!=''?(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':''):'');
    return $ret;
}

function TextAreaInput($value,$name,$title='',$options='',$div=true)
{
	if(Preferences('HIDDEN')!='Y')
		$div = false;

	if(AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
	{
		if(mb_strpos($options,'cols')===false)
			$options .= ' cols=30';
		if(mb_strpos($options,'rows')===false)
			$options .= ' rows=4';
		$rows = mb_substr($options,mb_strpos($options,'rows')+5,2)*1;
		$cols = mb_substr($options,mb_strpos($options,'cols')+5,2)*1;

		if($value=='' || $div==false)
			return '<TEXTAREA name="'.$name.'" id="'.$name.'" '.$options.'>'.$value.'</TEXTAREA>'.($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').'<label for="'.$name.'">'.$title.'</label>'.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');
		else
//modif Francois: remove ereg
		{
			$return = '<DIV id="div'.$name.'"><div class="onclick" onclick=\'javascript:addHTML(html'.str_replace(array('[',']'),'',$name);
			
			$toEscape = '<TEXTAREA id="textarea'.$name.'" name="'.$name.'" '.$options.'>'.preg_replace("/[\n\r]/",'\u000D\u000A',str_replace("\r\n",'\u000D\u000A',str_replace("'",'&#39;',$value))).'</TEXTAREA>'.($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').'<label for="'.$name.'">'.str_replace("'",'&#39;',$title).'</label>'.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');
			$return = '<script type="text/javascript">var html'.str_replace(array('[',']'),'',$name).'=\''.$toEscape.'\';</script>'.$return;
			
			$return .= ',"div'.$name.'",true); document.getElementById("textarea'.$name.'").value=unescape(document.getElementById("textarea'.$name.'").value);\'>'.'<TABLE style="height:100%;"><TR><TD>'.((mb_substr_count($value,"\r\n")>$rows)?'<DIV style="overflow:auto; height:'.(15*$rows).'px; width:'.($cols*9).'; padding-right:16px;" class="underline-dots">'.nl2br($value).'</DIV>':'<DIV style="overflow:auto; width:'.($cols*9).'; padding-right:16px;" class="underline-dots">'.nl2br($value).'</DIV>').'</TD></TR></TABLE>'.($title!=''?''.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').str_replace("'",'&#39;',$title).(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'').'</div></DIV>';
			return $return;
		}
			
	}
	else
		return ($value!=''?nl2br($value):'-').($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');
}

function CheckboxInput($value,$name,$title='',$checked='',$new=false,$yes='Yes',$no='No',$div=true,$extra='')
{
	// $checked has been deprecated -- it remains only as a placeholder
	if(Preferences('HIDDEN')!='Y')
		$div = false;

	if($div==false || $new==true)
	{
		if($value && $value!='N')
			$checked = 'checked';
		else
			$checked = '';
	}

	if(AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
	{
		if($new || $div==false)
//modif Francois: add <label> on checkbox
//			return "<INPUT type=checkbox name=$name value=Y $checked $extra>".($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');
			return '<label><INPUT type="checkbox" name="'.$name.'" value="Y" '.$checked.' '.$extra.' />&nbsp;'.$title.'</label>';
		else
		{
//			return "<DIV id='div$name'><div class=\"onclick\" onclick='javascript:addHTML(\"<INPUT type=hidden name=$name value=\\\"\\\"><INPUT type=checkbox name=$name ".($value?'checked':'')." value=Y ".str_replace('"','\"',$extra).">".($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').str_replace(array("'",'"'),array('&#39;','\"'),$title).(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'')."\",\"div$name\",true)'><span class='underline-dots'>".($value?$yes:$no).'</span>'.($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'')."</div></DIV>";
			$return = '<DIV id="div'.$name.'"><div class="onclick" onclick=\'javascript:addHTML("';
			
			$toEscape = '<INPUT type="hidden" name="'.$name.'" value="" /><label><INPUT type="checkbox" name="'.$name.'" '.($value?'checked':'').' value="Y" '.$extra.' /> '.str_replace("'",'&#39;',$title).'</label>';
			$return .=  str_replace('"','\"',$toEscape);
			
			$return .= '","div'.$name.'",true)\'>'.'<span class="underline-dots">'.($value?($yes=='Yes'?_('Yes'):$yes):($no=='No'?_('No'):$no)).'</span>&nbsp;'.$title.'</div></DIV>';
			return $return;
		}
	}
	else
//		return ($value?$yes:$no).($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');
		return ($value?($yes=='Yes'?_('Yes'):$yes):($no=='No'?_('No'):$no)).($title!=''?$title:'');
}

function SelectInput($value,$name,$title='',$options,$allow_na='N/A',$extra='',$div=true)
{
	if(Preferences('HIDDEN')!='Y')
		$div = false;

	// mab - support array style $option values
	// mab - append current val to select list if not in list
	if (is_array($value))
		$value = $value[0];
	if ($value!='' && !array_key_exists($value,$options))
		$options[$value] = array($value,'<span style="color:red">'.$value.'</span>');

	if(AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
	{
		if($value!='' && $div)
			$return = '<DIV id="div'.$name.'"><div class="onclick" onclick=\'javascript:addHTML(html'.str_replace(array('[',']'),'',$name);
		
		$select = '<SELECT name="'.$name.'" id="'.$name.'" '.$extra.'>';

		if($allow_na!==false)
		{
//modif Francois: add translation
			$select .= '<OPTION value="">'.str_replace("'",'&#39;',($allow_na=='N/A'?_('N/A'):$allow_na)).'</OPTION>';
		}
		if(count($options))
		{
			foreach($options as $key=>$val)
			{
				$key .= '';
				$select .= '<OPTION value="'.htmlspecialchars($key).'"'.($value==$key && (!($value==false && $value!==$key) || ($value===0 && $key==='0'))?' SELECTED="SELECTED"':'').'>'.str_replace("'",'&#39;',(is_array($val)?$val[0]:$val)).'</OPTION>';
			}
		}
		$select .= '</SELECT>';
		
		$select .= ($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').'<label for="'.$name.'">'.str_replace("'",'&#39;',$title).'</label>'.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':''):'');
		
		if($value!='' && $div)
		{
			$return = '<script type="text/javascript">var html'.str_replace(array('[',']'),'',$name).'=\''.$select.'\';</script>'.$return;

			$return .= ',"div'.$name.'",true)\'><span class="underline-dots">'.(is_array($options[$value])?$options[$value][1]:$options[$value]).'</span>'.($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').str_replace("'",'&#39;',$title).(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'').'</div></DIV>';
		}
		else
			$return = $select;
	}
	else
		$return = (((is_array($options[$value])?$options[$value][1]:$options[$value])!='')?(is_array($options[$value])?$options[$value][1]:$options[$value]):($allow_na!==false?($allow_na?('N/A'?_($allow_na):$allow_na):'-'):'-')).($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');

	return $return;
}

function MLSelectInput($value,$name,$title='',$options,$allow_na='N/A',$extra='',$div=true)
{
   global $RosarioLocales, $locale;

    if (sizeof($RosarioLocales) < 2)
        return SelectInput($value,$name,$title,$options,$div);
        
    if(Preferences('HIDDEN')!='Y')
        $div = false;

    // mab - support array style $option values
    // mab - append current val to select list if not in list
    if ($value!='' && $options[$value]=='')
        $options[$value] = array($value,'<span style="color:red">'.$value.'</span>');

    if(AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
    {
		if($value!='' && $div)
			$return = '<DIV id="div'.$name.'"><div class="onclick" onclick=\'javascript:addHTML(html'.str_replace(array('[',']'),'',$name);
			
		$select = '<SELECT name="'.$name.'" id="'.$name.'" '.$extra.'>';
			
        if($allow_na!==false)
        {
			$select .= '<OPTION value="">'.str_replace("'",'&#39;',($allow_na=='N/A'?_('N/A'):$allow_na)).'</OPTION>';
        }
        if(count($options))
        {
            foreach($options as $key=>$val)
            {
                $key .= '';
                $select .= '<OPTION value="'.htmlspecialchars($key).'"'.($value==$key && (!($value==false && $value!==$key) || ($value===0 && $key==='0'))?' SELECTED="SELECTED"':'').'>'.str_replace("'",'&#39;',(is_array($val)?ParseMLField($val[0], $locale):ParseMLField($val, $locale))).'</OPTION>';
            }
        }
        $select .= '</SELECT>';
		
		$select .= '<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').'<label for="'.$name.'">'.$title.'</label>'.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'';
			
        if($value!='' && $div)
		{
			$return = '<script type="text/javascript">var html'.str_replace(array('[',']'),'',$name).'=\''.$select.'\';</script>'.$return;

            $return .= ',"div'.$name.'",true)\'><span class="underline-dots">'.ParseMLField((is_array($options[$value])?$options[$value][1]:$options[$value]), $locale).'</span>'.($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').str_replace("'",'&#39;',$title).(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'').'</div></DIV>';
		}
		else
			$return = $select;
    }
    else
        $return = ParseMLField((((is_array($options[$value])?$options[$value][1]:$options[$value])!='')?(is_array($options[$value])?$options[$value][1]:$options[$value]):($allow_na!==false?($allow_na?$allow_na:'-'):'-')),$locale).($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');

    return $return;
}

function RadioInput($value,$name,$title='',$options,$allow_na='N/A',$extra='',$div=true)
{
	if(Preferences('HIDDEN')!='Y')
		$div = false;

	if ($value!='' && $options[$value]=='')
		$options[$value] = array($value,'<span style="color:red">'.$value.'</span>');

	if(AllowEdit() && !isset($_REQUEST['_ROSARIO_PDF']))
	{
		if($value!='' && $div)
			$return = '<DIV id="div'.$name.'"><div class="onclick" onclick=\'javascript:addHTML(html'.str_replace(array('[',']'),'',$name);
		
		$table = '<TABLE class="cellpadding-0 cellspacing-0" '.$extra.'><TR class="center">';
			
		if($allow_na!==false)
		{
//modif Francois: add <label> on radio
			$table .= '<TD><label><INPUT type="radio" name="'.$name.'" value=""'.($value==''?' checked':'').' /> '.str_replace("'",'&#39;',($allow_na=='N/A'?_('N/A'):$allow_na)).'</label></TD>';
		}
		if(count($options))
		{
			foreach($options as $key=>$val)
			{
				$key .= '';
				$table .= '<TD><label><INPUT type="radio" name="'.$name.'" value="'.htmlspecialchars($key).'" '.($value==$key && (!($value==false && $value!==$key) || ($value==='0' && $key===0))?'checked':'').' /> '.str_replace("'",'&#39;',(is_array($val)?$val[0]:$val)).'</label></TD>';
			}
		}
		$table .= '</TR></TABLE>';
		
		$table .= ''.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').'<label for="'.$name.'">'.$title.'</label>'.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'';
			
		if($value!='' && $div)
		{
			$return = '<script type="text/javascript">var html'.str_replace(array('[',']'),'',$name).'=\''.$table.'\';</script>'.$return;

            $return .= ',"div'.$name.'",true)\'><span class="underline-dots">'.(is_array($options[$value])?$options[$value][1]:$options[$value]).'</span>'.($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').str_replace("'",'&#39;',$title).(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'').'</div></DIV>';
		}
		else
			$return = $table;
	}
	else
		$return = (((is_array($options[$value])?$options[$value][1]:$options[$value])!='')?(is_array($options[$value])?$options[$value][1]:$options[$value]):($allow_na!==false?($allow_na?$allow_na:'-'):'-')).($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span class="legend-gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':'').'':'');

	return $return;
}

function NoInput($value,$title='')
{
	return ($value!=''?$value:'-').($title!=''?'<BR />'.(mb_strpos(mb_strtolower($title),'<span ')===false?'<span style="color:gray">':'').$title.(mb_strpos(mb_strtolower($title),'<span ')===false?'</span>':''):'');
}

function CheckBoxOnclick($name)
{
	static $link_nb = 0;
	$return .= '<script type="text/javascript">var CheckBoxOnclick'.$link_nb.' = document.createElement("a"); CheckBoxOnclick'.$link_nb.'.href = "'.PreparePHP_SELF($_REQUEST,array(),($_REQUEST[$name]=='Y'?array($name=>''):array($name=>'Y'))).'"; CheckBoxOnclick'.$link_nb.'.target = "body";</script>';
	$return .= '<INPUT type="checkbox" name="'.$name.'" value="Y"'.($_REQUEST[$name]=='Y'?' checked':'').' onclick=\'ajaxLink(CheckBoxOnclick'.$link_nb.');\' />';
	$link_nb++;
	return $return;
}
?>