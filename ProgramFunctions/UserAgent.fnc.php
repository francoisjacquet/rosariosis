<?php
/**
 * User Agent functions
 *
 * @link http://php.net/get-browser
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Get OS from UserAgent string
 *
 * @since 3.0
 *
 * @param $user_agent User Agent.
 *
 * @return string OS name or empty string if not found.
 */
function GetUserAgentOS( $user_agent )
{
	if ( strpos( $user_agent, 'Windows' )
		|| strpos( $user_agent, 'Win32' ) )
	{
		return 'Windows';
	}
	elseif ( strpos( $user_agent, 'Android' ) )
	{
		return 'Android';
	}
	elseif ( strpos( $user_agent, 'Linux' )
		|| strpos( $user_agent, 'X11' ) )
	{
		return 'Linux';
	}
	elseif ( strpos( $user_agent, 'iPhone' )
		|| strpos( $user_agent, 'iPad' )
		|| strpos( $user_agent, 'iPod' ) )
	{
		return 'iOS';
	}
	elseif ( strpos( $user_agent, 'Macintosh' )
		|| strpos( $user_agent, ' Mac' ) )
	{
		return 'Mac OS';
	}
	elseif ( stripos( $user_agent, 'bot' ) )
	{
		return 'Search Bot';
	}

	return '';
}


/**
 * Get Browser from UserAgent string
 *
 * @since 3.0
 *
 * @param $user_agent User Agent.
 *
 * @return string Browser name or empty string if not found.
 */
function GetUserAgentBrowser( $user_agent )
{
	if ( strpos( $user_agent, 'Opera' )
		|| strpos( $user_agent, 'OPR/' ) )
	{
		return 'Opera';
	}
	elseif ( strpos( $user_agent, 'Edg' ) )
	{
		return 'Edge';
	}
	elseif ( strpos( $user_agent, 'Chrome' ) )
	{
		return 'Chrome';
	}
	elseif ( strpos( $user_agent, 'Safari' ) )
	{
		return 'Safari';
	}
	elseif ( strpos( $user_agent, 'Firefox' ) )
	{
		return 'Firefox';
	}
	elseif ( strpos( $user_agent, 'MSIE' )
		|| strpos( $user_agent, 'Trident/7' ) )
	{
		return 'Internet Explorer';
	}

	return '';
}
