<?php

function Localize( $type, $string='' ) {
    global $locale;
    
    if (empty($locale)) $locale='en_US';
    switch($type) {
        case 'colon':
            if ($locale == "fr_FR") $ret = $string.' :';
            else $ret = $string.':';
            break;
        
        case 'time':
            if ($locale == 'en_US') {
                // Anglo-saxon time
                if ($string['hour'] > 12) {
                    $string['hour'] -=12; $suffix = 'PM';
                } else
                    $suffix = 'AM';
                $ret = $string['hour'].':'.$string['minute'].' '.$suffix;
            } else {
                // European time
                $ret = $string['hour'].':'.$string['minute'];
            }
            break;
            
        case 'weekdays':
            if (in_array($locale, array('en_US','he_IL'))) {
                // Week starts on Sunday
                if (empty($string))
                    $ret = array(_('Sunday'),_('Monday'),_('Tuesday'),_('Wednesday'),_('Thursday'),_('Friday'),_('Saturday'));
                else
                    $ret = 0; // Index of first day of the week
            } else {
                // Week starts on Monday
                if (empty($string))
                    $ret = array(_('Monday'),_('Tuesday'),_('Wednesday'),_('Thursday'),_('Friday'),_('Saturday'),_('Sunday'));
                else
                    $ret = 1; // Index of first day of the week
            }
            break;
            
        default:
            $ret = "ERROR: not a valid type of localization";
    }
    return $ret;
}
?>