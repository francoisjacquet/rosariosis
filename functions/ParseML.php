<?php 
/*
   This function extracts the translation of a user-entered field value
   Users must enter thetext in the following format:
      default_string|lang_code:translated string
   Example:
      My vacation by the sea|fr_CA:My vacation by the sea|de_DE:Mein urlaub am rand des meeres
   Will display the English text unless the current language is either fr_CA or de_DE
*/
function ParseMLField($field, $loc='') {
    global $locale;
    
    if (empty($loc)) $loc = $locale;
    
    // If no separator found, return untouched input
    $endpos = mb_strpos($field,"|");
    if ($endpos === FALSE) return $field;
    
    // If no locale defined, return default string
    if (empty($loc)) return mb_substr($field, 0, $endpos);

    // If no current language tag, return default string
    $begpos = mb_strpos($field, "|".$loc.":");
    if ($begpos === FALSE) return mb_substr($field, 0, $endpos);
    
    // We've found a translation ...
    // skip language tag in itself
    $begpos = mb_strpos($field, ":", $begpos) + 1;
    // go to end of translated string (ie. next tag or end of field)
    $endpos = mb_strpos($field, "|", $begpos);
    if ($endpos === FALSE) $endpos = mb_strlen($field);
    return mb_substr($field, $begpos, $endpos-$begpos);
}

/*
    Parse an array of any depth for keys that contain ML strings and replaces those with localized strings
*/
function ParseMLArray($array, $keys) {
    
	//modify loop: use for instead of foreach
	$k = array_keys($array);
	$size = sizeOf($k);
	for ($i=0; $i<$size; $i++)
	{
		if (is_array($array[$k[$i]]))
			$array[$k[$i]] = ParseMLArray($array[$k[$i]], $keys);
        else {
            if (!is_array($keys)) $keys = array($keys);
            foreach ($keys as $key)
                if ($k[$i] == $key) $array[$k[$i]] = ParseMLField($array[$k[$i]]);
        }
	}

	/*foreach ($array as $k => $v) {
        if (is_array($v))
            $array[$k] = ParseMLArray($v, $keys);
        else {
            if (!is_array($keys)) $keys = array($keys);
            foreach ($keys as $key)
                if ($k == $key) $array[$k] = ParseMLField($v);
        }
    }*/
    return $array;
}
