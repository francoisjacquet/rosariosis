<?php

DrawHeader(_(ProgramTitle()));

if (empty($_REQUEST['modfunc']))
{
	if ( $_REQUEST['search_modfunc']=='list')
	{
		$custom_fields_RET = DBGet(DBQuery("SELECT ID,TITLE,TYPE FROM CUSTOM_FIELDS WHERE ID IN (200000000, 200000001)"));
		$address_fields_RET = DBGet(DBQuery("SELECT ID,TITLE,TYPE FROM ADDRESS_FIELDS"));
		$people_fields_RET = DBGet(DBQuery("SELECT ID,TITLE,TYPE FROM PEOPLE_FIELDS"));
		foreach ( (array) $custom_fields_RET as $field)
			$extra['SELECT'] .= ",s.CUSTOM_".$field['ID'];
		$extra['SELECT'] .= ",a.ADDRESS,a.CITY,a.STATE,a.ZIPCODE,a.MAIL_ADDRESS,a.MAIL_CITY,a.MAIL_STATE,a.MAIL_ZIPCODE,a.PHONE";
		foreach ( (array) $address_fields_RET as $field)
			$extra['SELECT'] .= ",a.CUSTOM_".$field['ID']." AS ADDRESS_".$field['ID'];
		$extra['SELECT'] .= ",p.PERSON_ID,p.FIRST_NAME||' '||p.LAST_NAME AS PERSON_NAME";
		foreach ( (array) $people_fields_RET as $field)
			$extra['SELECT'] .= ",p.CUSTOM_".$field['ID']." AS PEOPLE_".$field['ID'];
		$extra['functions'] = array();
		for ( $i=1; $i<=10; $i++)
		{
			$extra['SELECT'] .= ",NULL AS TITLE_$i,NULL AS VALUE_$i";
			$extra['functions'] += array('TITLE_'.$i => '_makeTV','VALUE_'.$i => '_makeTV');
		}
		$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sja ON (sja.STUDENT_ID=ssm.STUDENT_ID AND sja.ADDRESS_ID!='0') LEFT OUTER JOIN ADDRESS a ON (a.ADDRESS_ID=sja.ADDRESS_ID)";
		$extra['FROM'] = " LEFT OUTER JOIN STUDENTS_JOIN_ADDRESS sja ON (sja.STUDENT_ID=ssm.STUDENT_ID) LEFT OUTER JOIN ADDRESS a ON (a.ADDRESS_ID=sja.ADDRESS_ID)";
		$extra['FROM'] .= " LEFT OUTER JOIN STUDENTS_JOIN_PEOPLE sjp ON (sjp.STUDENT_ID=ssm.STUDENT_ID AND sjp.ADDRESS_ID=a.ADDRESS_ID) LEFT OUTER JOIN PEOPLE p ON (p.PERSON_ID=sjp.PERSON_ID)";
		//$extra['WHERE'] = " AND (a.ADDRESS_ID IS NULL OR a.ADDRESS_ID=sja.ADDRESS_ID)";

		if ( $_REQUEST['address_group'])
		{
			$extra['SELECT'] .= ",coalesce((SELECT ADDRESS_ID FROM STUDENTS_JOIN_ADDRESS WHERE STUDENT_ID=ssm.STUDENT_ID AND RESIDENCE='Y' LIMIT 1),-ssm.STUDENT_ID) AS FAMILY_ID";
			$extra['group'] = $LO_group = array('FAMILY_ID','STUDENT_ID');
			//$LO_group = array(array('FAMILY_ID','STUDENT_ID'));
			$LO_columns = array('FAMILY_ID' => _('Address ID'));
			if ( !isset($_REQUEST['_ROSARIO_PDF']))
				$header_left = '<a href="'.PreparePHP_SELF($_REQUEST,array(),array('address_group' => '')).'">'._('Ungroup by Family').'</a>';
		}
		else
		{
			$extra['group'] = $LO_group = array('STUDENT_ID');
			$LO_columns = array();
			if ( !isset($_REQUEST['_ROSARIO_PDF']))
				$header_left = '<a href="'.PreparePHP_SELF($_REQUEST,array(),array('address_group' => 'Y')).'">'._('Group by Family').'</a>';
		}
		$students_RET = GetStuList($extra);
		$LO_columns += array('FULL_NAME' => _('Student'),'STUDENT_ID'=>sprintf(_('%s ID'),Config('NAME')),'GRADE_ID' => _('Grade Level'));
		foreach ( (array) $custom_fields_RET as $field)
			$LO_columns += array('CUSTOM_'.$field['ID']=>ParseMLField($field['TITLE']));
		
//FJ disable mailing address display
		if (Config('STUDENTS_USE_MAILING'))
			$LO_columns += array('ADDRESS' => _('Street'),'CITY' => _('City'),'STATE' => _('State'),'ZIPCODE' => _('Zipcode'),'PHONE' => _('Phone'),'MAIL_ADDRESS' => _('Mailing Street'),'MAIL_CITY' => _('Mailing City'),'MAIL_STATE' => _('Mailing State'),'MAIL_ZIPCODE' => _('Mailing Zipcode'));
		else
			$LO_columns += array('ADDRESS' => _('Street'),'CITY' => _('City'),'STATE' => _('State'),'ZIPCODE' => _('Zipcode'),'PHONE' => _('Phone'));
		foreach ( (array) $address_fields_RET as $field)
//FJ add translation
			$LO_columns += array('ADDRESS_'.$field['ID']=>ParseMLField($field['TITLE']));
		$LO_columns += array('PERSON_NAME' => _('Person Name'));
		foreach ( (array) $people_fields_RET as $field)
			$LO_columns += array('PEOPLE_'.$field['ID']=>ParseMLField($field['TITLE']));
		for ( $i=1; $i<=$maxTV; $i++)
                        $LO_columns += array('TITLE_'.$i => _('Title').' '.$i,'VALUE_'.$i => _('Value').' '.$i);
		DrawHeader($header_left);
		DrawHeader(str_replace('<br />','<br /> &nbsp;',mb_substr($_ROSARIO['SearchTerms'],0,-6)));
		if ( !$_REQUEST['LO_save'])
		{
			$_SESSION['List_PHP_SELF'] = PreparePHP_SELF($_SESSION['_REQUEST_vars'],array('bottom_back'));
			if ( $_SESSION['Back_PHP_SELF']!='student')
			{
				$_SESSION['Back_PHP_SELF'] = 'student';
				unset($_SESSION['Search_PHP_SELF']);
			}
			echo '<script>ajaxLink("Bottom.php"); old_modname="";</script>';
		}
		ListOutput($students_RET,$LO_columns,'Student','Students',false,$LO_group);
	}
	else
	{
		$extra['new'] = true;

		Search('student_id',$extra);
	}
}

function _makeTV($value,$column)
{	global $maxTV,$THIS_RET,$person_id,$person_RET;

	if ( $THIS_RET['PERSON_ID']!=$person_id)
	{
		$person_RET = DBGet(DBQuery("SELECT TITLE,VALUE FROM PEOPLE_JOIN_CONTACTS WHERE PERSON_ID='".$THIS_RET['PERSON_ID']."' LIMIT 10"));
		if (count($person_RET)>$maxTV)
			$maxTV = count($person_RET);
		$person_id = $THIS_RET['PERSON_ID'];
		//echo '<pre>'; var_dump($person_RET); echo '</pre>';
	}

	$tv = mb_substr($column,0,5);
	$i = mb_substr($column,6);
	return $person_RET[ $i ][ $tv ];
}
