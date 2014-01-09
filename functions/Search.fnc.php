<?php

function Search($type,$extra=null)
{	global $_ROSARIO,$modname,$program_loaded;

	switch($type)
	{
		case 'student_id':
			if($_REQUEST['bottom_back'])
			{
				if (mb_strpos($modname,'Search.php')===false)
				{
					//echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'?modname='.rawurlencode($program_loaded).'"; menu_link.target = "menu"; ajaxLink(menu_link);</script>';
					if (isset($_SESSION['student_id']))
						echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname="'.$program_loaded.'"; ajaxLink(menu_link);</script>';
					else
						echo '<script type="text/javascript">modname="'.$program_loaded.'"; openMenu(modname);</script>';
				}
				unset($_SESSION['student_id']);
			}
			if($_SESSION['unset_student'])
			{
				unset($_REQUEST['student_id']);
				unset($_SESSION['unset_student']);
			}

			if($_REQUEST['student_id'])
			{
				if($_REQUEST['student_id']!='new')
				{
					$_SESSION['student_id'] = $_REQUEST['student_id'];
					if($_REQUEST['school_id'])
						$_SESSION['UserSchool'] = $_REQUEST['school_id'];
					if(!isset($_REQUEST['_ROSARIO_PDF']))
						echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname="'.$program_loaded.'"; ajaxLink(menu_link);</script>';
				}
				elseif(isset($_SESSION['student_id']))
				{
					unset($_SESSION['student_id']);
					echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname="'.$program_loaded.'&include=General_Info&student_id=new"; ajaxLink(menu_link);</script>';
				}
			}
			elseif(!UserStudentID() || $extra['new']==true)
			{
				if(UserStudentID())
				{
					//modif Francois: fix bug no student found when student/parent logged in
					if (User('PROFILE')!=='student' && User('PROFILE')!=='parent')
					{
						unset($_SESSION['student_id']);
						echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
					}
				}
				$_REQUEST['next_modname'] = $_REQUEST['modname'];
				include('modules/Students/Search.inc.php');
			}
		break;

		case 'staff_id':
			// convert profile string to array for legacy compatibility
			if (!is_array($extra)) $extra = array('profile'=>$extra);
			if($_REQUEST['bottom_back'])
			{
				if (mb_strpos($modname,'Search.php')===false)
				{
					//echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'?modname='.rawurlencode($program_loaded).'"; menu_link.target = "menu"; ajaxLink(menu_link);</script>';
					if (isset($_SESSION['staff_id']))
						echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname="'.$program_loaded.'"; ajaxLink(menu_link);</script>';
					else
						echo '<script type="text/javascript">modname="'.$program_loaded.'"; openMenu(modname);</script>';
				}
				unset($_SESSION['staff_id']);
			}

			if($_REQUEST['staff_id'])
			{
				if($_REQUEST['staff_id']!='new')
				{
					$_SESSION['staff_id'] = $_REQUEST['staff_id'];
					if($_REQUEST['school_id'])
						$_SESSION['UserSchool'] = $_REQUEST['school_id'];
					if(!isset($_REQUEST['_ROSARIO_PDF']))
						echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname="'.$program_loaded.'"; ajaxLink(menu_link);</script>';
				}
				elseif(isset($_SESSION['staff_id']))
				{
					unset($_SESSION['staff_id']);
					echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname="'.$program_loaded.'&staff_id=new"; ajaxLink(menu_link);</script>';
				}
			}
			elseif(!UserStaffID() || $extra['new']==true)
			{
				if(UserStaffID())
				{
					unset($_SESSION['staff_id']);
					echo '<script type="text/javascript">var menu_link = document.createElement("a"); menu_link.href = "'.$_SESSION['Side_PHP_SELF'].'"; menu_link.target = "menu"; modname=document.getElementById("modname_input").value; ajaxLink(menu_link);</script>';
				}
				//if(empty($_REQUEST['modfunc']))
				$_REQUEST['modfunc'] = 'search_fnc';
				$_REQUEST['next_modname'] = $_REQUEST['modname'];
				//if(!$_REQUEST['modname']) $_REQUEST['modname'] = 'Users/Search.php';
				include('modules/Users/Search.inc.php');
			}
		break;

		case 'general_info':
			echo '<TR><TD style="text-align:right;"><label for="last">'._('Last Name').'</label></TD><TD><input type="text" name="last" id="last" size="30"></TD></TR>';
			echo '<TR><TD style="text-align:right;"><label for="first">'._('First Name').'</label></TD><TD><input type="text" name="first" id="first" size="30"></TD></TR>';
			echo '<TR><TD style="text-align:right;"><label for="stuid">'._('RosarioSIS ID').'</label></TD><TD><input type="text" name="stuid" id="stuid" size="30"></TD></TR>';
			echo '<TR><TD style="text-align:right;"><label for="addr">'._('Address').'</label></TD><TD><input type="text" name="addr" id="addr" size="30"></TD></TR>';

			$list = DBGet(DBQuery("SELECT ID,TITLE,SHORT_NAME FROM SCHOOL_GRADELEVELS WHERE SCHOOL_ID='".UserSchool()."' ORDER BY SORT_ORDER"));
			if($_REQUEST['advanced']=='Y' || is_array($extra))
			{
//modif Francois: add <label> on checkbox
				echo '<TR><TD style="text-align:right;">'._('Grade Levels').'</TD><TD><label><span class="nobr"><INPUT type="checkbox" name="grades_not" value="Y">&nbsp;'._('Not').'</span></label><BR /><label><span class="nobr"><INPUT type="checkbox" value="Y" name="controller" onclick="checkAll(this.form,this.form.controller.checked,\'grades[\');">&nbsp;'._('Check All').'</span></label></TD></TR><TR><TD colspan="2" style="max-width:120px;">';
				foreach($list as $value)
				{
                    echo '<label><span class="nobr"><INPUT type="checkbox" name="grades['.$value['ID'].']" value="Y"'.(is_array($extra)?($extra[$value['ID']]?' checked':''):($extra==$value['ID']?' checked':'')).'>&nbsp;'.$value['SHORT_NAME'].'</span></label> ';
				}
				echo '</TD></TR>';
			}
			else
			{
				echo '<TR><TD style="text-align:right;"><label for="grade">'._('Grade Level').'</label></TD><TD><SELECT name="grade" id="grade"><OPTION value="">'._('Not Specified').'</OPTION>';
				foreach($list as $value)
                    echo '<OPTION value="'.$value['ID'].'"'.($extra==$value['ID']?' SELECTED="SELECTED"':'').'>'.$value['TITLE'].'</OPTION>';
				echo '</SELECT></TD></TR>';
			}
		break;

		case 'staff_fields':
		case 'staff_fields_all':
		case 'student_fields':
		case 'student_fields_all':
			if($type=='staff_fields_all')
				$categories_RET = ParseMLArray(DBGet(DBQuery("SELECT sfc.ID,sfc.TITLE AS CATEGORY_TITLE,'CUSTOM_'||cf.ID AS COLUMN_NAME,cf.TYPE,cf.TITLE,SELECT_OPTIONS FROM STAFF_FIELD_CATEGORIES sfc,STAFF_FIELDS cf WHERE (SELECT CAN_USE FROM ".(User('PROFILE_ID')?"PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."'":"STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."'")." AND MODNAME='Users/User.php&category_id='||sfc.ID)='Y' AND cf.CATEGORY_ID=sfc.ID AND NOT exists(SELECT '' FROM PROGRAM_USER_CONFIG WHERE PROGRAM='StaffFieldsSearch' AND TITLE=cast(cf.ID AS TEXT) AND USER_ID='".User('STAFF_ID')."' AND VALUE='Y') ORDER BY sfc.SORT_ORDER,sfc.TITLE,cf.SORT_ORDER,cf.TITLE"),array(),array('ID','TYPE')),array('CATEGORY_TITLE','TITLE'));
			elseif($type=='staff_fields')
				$categories_RET = ParseMLArray(DBGet(DBQuery("SELECT '0' AS ID,'' AS CATEGORY_TITLE,'CUSTOM_'||cf.ID AS COLUMN_NAME,cf.TYPE,cf.TITLE,cf.SELECT_OPTIONS FROM STAFF_FIELDS cf WHERE (SELECT CAN_USE FROM ".(User('PROFILE_ID')?"PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."'":"STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."'")." AND MODNAME='Users/User.php&category_id='||cf.CATEGORY_ID)='Y'  AND ((SELECT VALUE FROM PROGRAM_USER_CONFIG WHERE TITLE=cast(cf.ID AS TEXT) AND PROGRAM='StaffFieldsSearch' AND USER_ID='".User('STAFF_ID')."')='Y') ORDER BY cf.SORT_ORDER,cf.TITLE"),array(),array('ID','TYPE')),array('CATEGORY_TITLE','TITLE'));
			elseif($type=='student_fields_all')
				$categories_RET = ParseMLArray(DBGet(DBQuery("SELECT sfc.ID,sfc.TITLE AS CATEGORY_TITLE,'CUSTOM_'||cf.ID AS COLUMN_NAME,cf.TYPE,cf.TITLE,SELECT_OPTIONS FROM STUDENT_FIELD_CATEGORIES sfc,CUSTOM_FIELDS cf WHERE (SELECT CAN_USE FROM ".(User('PROFILE_ID')?"PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."'":"STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."'")." AND MODNAME='Students/Student.php&category_id='||sfc.ID)='Y' AND cf.CATEGORY_ID=sfc.ID AND NOT exists(SELECT '' FROM PROGRAM_USER_CONFIG WHERE PROGRAM='StudentFieldsSearch' AND TITLE=cast(cf.ID AS TEXT) AND USER_ID='".User('STAFF_ID')."' AND VALUE='Y') ORDER BY sfc.SORT_ORDER,sfc.TITLE,cf.SORT_ORDER,cf.TITLE"),array(),array('ID','TYPE')),array('CATEGORY_TITLE','TITLE'));
			else
				$categories_RET = ParseMLArray(DBGet(DBQuery("SELECT '0' AS ID,'' AS CATEGORY_TITLE,'CUSTOM_'||cf.ID AS COLUMN_NAME,cf.TYPE,cf.TITLE,cf.SELECT_OPTIONS FROM CUSTOM_FIELDS cf WHERE (SELECT CAN_USE FROM ".(User('PROFILE_ID')?"PROFILE_EXCEPTIONS WHERE PROFILE_ID='".User('PROFILE_ID')."'":"STAFF_EXCEPTIONS WHERE USER_ID='".User('STAFF_ID')."'")." AND MODNAME='Students/Student.php&category_id='||cf.CATEGORY_ID)='Y'  AND ((SELECT VALUE FROM PROGRAM_USER_CONFIG WHERE TITLE=cast(cf.ID AS TEXT) AND PROGRAM='StudentFieldsSearch' AND USER_ID='".User('STAFF_ID')."')='Y') ORDER BY cf.SORT_ORDER,cf.TITLE"),array(),array('ID','TYPE')),array('CATEGORY_TITLE','TITLE'));

			foreach($categories_RET as $search_fields_RET)
			{
				if($type=='student_fields_all' || $type=='staff_fields_all')
				{
//modif Francois: css WPadmin
					echo '<TR><TD colspan="2"><TABLE style="border-collapse:separate; border-spacing:2px" class="width-100p cellpadding-2"><TR><TD colspan="2">&nbsp;<A onclick="switchMenu(\'fields_'.$search_fields_RET[key($search_fields_RET)][1]['ID'].'_table\'); return false;" href="#"><IMG SRC="assets/arrow_right.gif" id="fields_'.$search_fields_RET[key($search_fields_RET)][1]['ID'].'_table_arrow" height="12"> <B>'.$search_fields_RET[key($search_fields_RET)][1]['CATEGORY_TITLE'].'</B></A><BR />';
					echo '<TABLE id="fields_'.$search_fields_RET[key($search_fields_RET)][1]['ID'].'_table" style="display:none;" class="widefat width-100p cellspacing-0">';
				}

				if(count($search_fields_RET['text']))
				{
					foreach($search_fields_RET['text'] as $column)
						echo '<TR><TD style="text-align:right;"><label for="cust['.$column['COLUMN_NAME'].']">'.$column['TITLE'].'</label></TD><TD><INPUT type="text" name="cust['.$column['COLUMN_NAME'].']" id="cust['.$column['COLUMN_NAME'].']" size="30"></TD></TR>';
				}
				if(count($search_fields_RET['numeric']))
				{
					foreach($search_fields_RET['numeric'] as $column)
						echo '<TR><TD style="text-align:right;">'.$column['TITLE'].'</TD><TD><span class="sizep2">&ge;</span> <INPUT type="text" name="cust_begin['.$column['COLUMN_NAME'].']" size="3" maxlength="11"> <span class="sizep2">&le;</span> <INPUT type="text" name="cust_end['.$column['COLUMN_NAME'].']" size="3" maxlength="11"> <label>'._('No Value').' <INPUT type="checkbox" name="cust_null['.$column['COLUMN_NAME'].']"></label>&nbsp;</TD></TR>';
				}
				if(count($search_fields_RET['codeds']))
				{
					foreach($search_fields_RET['codeds'] as $column)
					{
						$column['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$column['SELECT_OPTIONS']));
						$options = explode("\r",$column['SELECT_OPTIONS']);

						echo '<TR><TD style="text-align:right;">'.$column['TITLE'].'</TD><TD>';
						echo '<SELECT name="cust['.$column['COLUMN_NAME'].'] style="max-width:250;"><OPTION value="">'._('N/A').'</OPTION><OPTION value="!">'._('No Value').'</OPTION>';
						foreach($options as $option)
						{
							$option = explode('|',$option);
							if($option[0]!='' && $option[1]!='')
								echo '<OPTION value="'.$option[0].'">'.$option[1].'</OPTION>';
						}
						echo '</SELECT>';
						echo '</TD></TR>';
					}
				}
				if(count($search_fields_RET['exports']))
				{
					foreach($search_fields_RET['exports'] as $column)
					{
						$column['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$column['SELECT_OPTIONS']));
						$options = explode("\r",$column['SELECT_OPTIONS']);

						echo '<TR><TD style="text-align:right;">'.$column['TITLE'].'</TD><TD>';
						echo '<SELECT name="cust['.$column['COLUMN_NAME'].'] style="max-width:250;"><OPTION value="">'._('N/A').'</OPTION><OPTION value="!">'._('No Value').'</OPTION>';
						foreach($options as $option)
						{
							$option = explode('|',$option);
							if($option[0]!='')
								echo '<OPTION value="$option[0]">'.$option[0].'</OPTION>';
						}
						echo '</SELECT>';
						echo '</TD></TR>';
					}
				}
				if(count($search_fields_RET['select']))
				{
					foreach($search_fields_RET['select'] as $column)
					{
						$column['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$column['SELECT_OPTIONS']));
						$options = explode("\r",$column['SELECT_OPTIONS']);

						echo '<TR><TD style="text-align:right;">'.$column['TITLE'].'</TD><TD>';
						echo '<SELECT name="cust['.$column['COLUMN_NAME'].'] style="max-width:250;"><OPTION value="">'._('N/A').'</OPTION><OPTION value="!">'._('No Value').'</OPTION>';
						foreach($options as $option)
							echo '<OPTION value="'.$option.'">'.$option.'</OPTION>';
						echo '</SELECT>';
						echo '</TD></TR>';
					}
				}
				if(count($search_fields_RET['autos']))
				{
					foreach($search_fields_RET['autos'] as $column)
					{
						if($column['SELECT_OPTIONS'])
						{
							$column['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$column['SELECT_OPTIONS']));
							$options_RET = explode("\r",$column['SELECT_OPTIONS']);
						}
						else
							$options_RET = array();

						echo '<TR><TD style="text-align:right;">'.$column['TITLE'].'</TD><TD>';
						echo '<SELECT name="cust['.$column['COLUMN_NAME'].'] style="max-width:250;"><OPTION value="">'._('N/A').'</OPTION><OPTION value="!">'._('No Value').'</OPTION>';
						$options = array();
						foreach($options_RET as $option)
						{
							echo '<OPTION value="'.$option.'">'.$option.'</OPTION>';
							$options[$option] = true;
						}
//modif Francois: new option
						echo '<OPTION value="---">-'. _('Edit') .'-</OPTION>';
						$options['---'] = true;
						// add values found in current and previous year
						$options_RET = DBGet(DBQuery("SELECT DISTINCT s.$column[COLUMN_NAME],upper(s.$column[COLUMN_NAME]) AS KEY FROM STUDENTS s,STUDENT_ENROLLMENT sse WHERE sse.STUDENT_ID=s.STUDENT_ID AND (sse.SYEAR='".UserSyear()."' OR sse.SYEAR='".(UserSyear()-1)."') AND $column[COLUMN_NAME] IS NOT NULL ORDER BY KEY"));
						foreach($options_RET as $option)
							if($option[$column['COLUMN_NAME']]!='' && !$options[$option[$column['COLUMN_NAME']]])
							{
								echo '<OPTION value="'.$option[$column['COLUMN_NAME']].'">'.$option[$column['COLUMN_NAME']].'</OPTION>';
								$options[$option[$column['COLUMN_NAME']]] = true;
							}
						echo '</SELECT>';
						echo '</TD></TR>';
					}
				}
				if(count($search_fields_RET['edits']))
				{
					foreach($search_fields_RET['edits'] as $column)
					{
						if($column['SELECT_OPTIONS'])
						{
							$column['SELECT_OPTIONS'] = str_replace("\n","\r",str_replace("\r\n","\r",$column['SELECT_OPTIONS']));
							$options_RET = explode("\r",$column['SELECT_OPTIONS']);
						}
						else
							$options_RET = array();

						echo '<TR><TD style="text-align:right;">'.$column['TITLE'].'</TD><TD>';
						echo '<SELECT name="cust['.$column['COLUMN_NAME'].'] style="max-width:250;"><OPTION value="">'._('N/A').'</OPTION><OPTION value="!">'._('No Value').'</OPTION>';
						$options = array();
						foreach($options_RET as $option)
							echo '<OPTION value="'.$option.'">'.$option.'</OPTION>';
//modif Francois: new option
//						echo "<OPTION value=\"---\">---</OPTION>";
						echo '<OPTION value="---">-'. _('Edit') .'-</OPTION>';
						echo '<OPTION value="~">'._('Other Value').'</OPTION>';
						echo '</SELECT>';
						echo '</TD></TR>';
					}
				}
				if(count($search_fields_RET['date']))
				{
					foreach($search_fields_RET['date'] as $column)
						echo '<TR><TD style="text-align:right;">'.$column['TITLE'].'<BR /><label>'._('No Value').'&nbsp;<INPUT type="checkbox" name="cust_null['.$column['COLUMN_NAME'].']"></label></TD><TD><table class="cellpadding-0 cellspacing-0"<tr><td><span class="sizep2">&ge;</span>&nbsp;</td><td>'.PrepareDate('','_cust_begin['.$column['COLUMN_NAME'].']',true,array('short'=>true)).'</td></tr><tr><td><span class="sizep2">&le;</span>&nbsp;</td><td>'.PrepareDate('','_cust_end['.$column['COLUMN_NAME'].']',true,array('short'=>true)).'</td></tr></table></TD></TR>';
				}
				if(count($search_fields_RET['radio']))
				{
					echo '<TR><TD colspan="2"><TABLE class="cellspacing-0">';

					echo '<TR><TD style="width:120px;"></TD><TD><TABLE class="cellpadding-0 cellspacing-0"><tr><td style="width:25px;"><b>'._('All').'</b></td><td style="width:30px;"><b>'._('Yes').'</b></td><td style="width:25px;"><b>'._('No').'</b></td></tr></table></TD>';
					if(count($search_fields_RET['radio'])>1)
						echo '<TD style="width:120px;"></TD><TD><TABLE class="cellpadding-0 cellspacing-0"><tr><td style="width:25px;"><b>'._('All').'</b></td><td style="width:30px;"><b>'._('Yes').'</b></td><td style="width:25px;"><b>'._('No').'</b></td></tr></table></TD>';
					echo '</TR>';

					$side = 0;
					foreach($search_fields_RET['radio'] as $cust)
					{
						if(!$side)
							echo '<TR>';
						echo '<TD style="text-align:right; width:120px">'.$cust['TITLE'].'</TD><TD>
						<TABLE class="cellpadding-0 cellspacing-0"><tr><td style="text-align:center; width:25px;">
						<input name="cust['.$cust['COLUMN_NAME'].']" type="radio" value="" checked />
						</td><td style="text-align:center; width:30px;">
						<input name="cust['.$cust['COLUMN_NAME'].']" type="radio" value="Y" />
						</td><td style="text-align:center; width:25px;">
						<input name="cust['.$cust['COLUMN_NAME'].']" type="radio" value="N" />
						</td></tr></table>
						</TD>';
						if($side)
							echo '</TR>';
						$side = 1-$side;
					}
					if($side)
						echo '</TR>';
					echo '</TABLE></TD></TR>';
				}
				if($type=='student_fields_all' || $type=='staff_fields_all')
//					echo '</TABLE>';
					echo '</TABLE></TD></TR></TABLE></TD></TR>';
			}
		break;
	}
}
?>