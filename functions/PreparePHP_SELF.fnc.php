<?php

function PreparePHP_SELF($tmp_REQUEST='',$remove=array(),$add=array())
{
	if(!$tmp_REQUEST)
		$tmp_REQUEST = $_REQUEST;

	foreach($_COOKIE as $key=>$value)
		unset($tmp_REQUEST[$key]);

	foreach($remove as $key)
		unset($tmp_REQUEST[$key]);

	foreach($add as $key=>$value)
		$tmp_REQUEST[$key] = $value;

	$PHP_tmp_SELF = 'Modules.php?modname=' . $tmp_REQUEST['modname'];

	unset($tmp_REQUEST['modname']);

	if(count($tmp_REQUEST))
	{
		foreach($tmp_REQUEST as $key=>$value)
		{
			if(is_array($value))
			{
				foreach($value as $key1=>$value1)
				{
					if(is_array($value1))
					{
						foreach($value1 as $key2=>$value2)
						{
							if(is_array($value2))
							{
								foreach($value2 as $key3=>$value3)
								{
									$PHP_tmp_SELF .= '&amp;'.$key.'['.$key1.']['.$key2.']['.$key3.']='.urlencode(str_replace('\"','"',$value3));
								}
							}
							else
								$PHP_tmp_SELF .= '&amp;'.$key.'['.$key1.']['.$key2.']='.urlencode(str_replace('\"','"',$value2));
						}
					}
					else
						$PHP_tmp_SELF .= '&amp;'.$key.'['.$key1.']='.urlencode(str_replace('\"','"',$value1));
				}
			}
			else
			{
				if($value != '')
					$PHP_tmp_SELF .= '&amp;' . $key . "=" . urlencode(str_replace('\"','"',$value));
			}
		}
	}

	return $PHP_tmp_SELF;
}
?>