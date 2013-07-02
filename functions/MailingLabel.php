<?php

function MailingLabel($address_id)
{	global $THIS_RET,$_ROSARIO;

	$student_id = $THIS_RET['STUDENT_ID'];
	if($address_id && !$_ROSARIO['MailingLabel'][$address_id][$student_id])
	{
		$people_RET = DBGet(DBQuery("SELECT p.FIRST_NAME,p.MIDDLE_NAME,p.LAST_NAME,
			coalesce(a.MAIL_ADDRESS,a.ADDRESS) AS ADDRESS,coalesce(a.MAIL_CITY,a.CITY) AS CITY,coalesce(a.MAIL_STATE,a.STATE) AS STATE,coalesce(a.MAIL_ZIPCODE,a.ZIPCODE) AS ZIPCODE
			FROM ADDRESS a JOIN STUDENTS_JOIN_ADDRESS sja ON (a.ADDRESS_ID=sja.ADDRESS_ID) LEFT OUTER JOIN STUDENTS_JOIN_PEOPLE sjp ON (sjp.ADDRESS_ID=sja.ADDRESS_ID AND sjp.STUDENT_ID=sja.STUDENT_ID AND (sjp.CUSTODY='Y' OR sja.RESIDENCE IS NULL)) LEFT OUTER JOIN PEOPLE p ON (p.PERSON_ID=sjp.PERSON_ID)
			WHERE sja.STUDENT_ID='".$student_id."' AND sja.ADDRESS_ID='".$address_id."'
			ORDER BY sjp.STUDENT_RELATION"),array(),array('LAST_NAME'));

		if(count($people_RET))
		{
			foreach($people_RET as $last_name=>$people)
			{
				for($i=1;$i<count($people);$i++)
					$return .= $people[$i]['FIRST_NAME'].' &amp; ';
				$return .= $people[$i]['FIRST_NAME'].' '.$people[$i]['LAST_NAME'].'<BR />';
			}
			// mab - this is a bit of a kludge but insert an html comment so people and address can be split later
			$return .= '<!-- -->'.$people[$i]['ADDRESS'].'<BR />'.$people[$i]['CITY'].($people[$i]['STATE'] ? ', '.$people[$i]['STATE'] : '').($people[$i]['ZIPCODE'] ? ' '.$people[$i]['ZIPCODE'] : '');
		}

		$_ROSARIO['MailingLabel'][$address_id][$student_id] = $return;
	}

	return $_ROSARIO['MailingLabel'][$address_id][$student_id];
}
?>
