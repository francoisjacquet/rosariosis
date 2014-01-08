<?php

function Config($item)
{	global $_ROSARIO,$DefaultSyear;

	if(empty($_ROSARIO['Config']) || !isset($_ROSARIO['Config'][$item]))
	{
		$QI=DBQuery("SELECT TITLE, CONFIG_VALUE FROM CONFIG WHERE ".(UserSchool() < 1 ? '' : "SCHOOL_ID='".UserSchool()."' OR")." SCHOOL_ID='0'");
		$_ROSARIO['Config'] = DBGet($QI, array(), array('TITLE'));
		$_ROSARIO['Config']['SYEAR'][1]['CONFIG_VALUE'] = $DefaultSyear;
	}

	return $_ROSARIO['Config'][$item][1]['CONFIG_VALUE'];
}
?>