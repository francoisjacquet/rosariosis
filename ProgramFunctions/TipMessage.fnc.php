<?php
/**
 * Tip Message functions
 *
 * @uses DHTML tip message JS plugin
 *
 * @see assets/js/tipmessage/
 *
 * @package RosarioSIS
 * @subpackage ProgramFunctions
 */

/**
 * Make Tip Message
 *
 * @example makeTipMessage( '<img src="' . $picture_path . '" width="150" />', $title, $title );
 *
 * @uses DHTML tip message JS plugin
 *
 * @see assets/js/tipmessage/
 *
 * @param  string $message Tip message.
 * @param  string $title   Tip title.
 * @param  string $label   Tip label.
 *
 * @return string Tip Message
 */
function MakeTipMessage( $message, $title, $label )
{
	static $tip_msg_ID = 1;

	$tip_msg = '<script>var tipmsg' . $tip_msg_ID . '=[' .
		json_encode( (string) $title ) . ',' .
		json_encode( (string) $message ) . '];</script>';

	$tip_msg .= '<div class="tipmsg-label" onMouseOver="stm(tipmsg' . $tip_msg_ID . ');" onMouseOut="htm();" onclick="return false;">' .
		$label . '</div>';

	$tip_msg_ID++;

	return $tip_msg;
}


/**
 * Make Student Photo Tip Message
 * Look for current & previous school year Photos
 *
 * @example require_once 'ProgramFunctions/TipMessage.fnc.php';
 *          return makeStudentPhotoTipMessage( $THIS_RET['STUDENT_ID'], $full_name );
 *
 * @uses MakeTipMessage()
 * @uses DHTML tip message JS plugin
 *
 * @see assets/js/tipmessage/
 *
 * @global $StudentPicturesPath Student Pictures Path
 *
 * @param  string $student_id Student ID.
 * @param  string $title      Tip title & label.
 *
 * @return string Student Photo Tip Message or $title if no Photo found
 */
function MakeStudentPhotoTipMessage( $student_id, $title )
{
	global $StudentPicturesPath;

	if ( $StudentPicturesPath
		&& ( file_exists( ( $picture_path = $StudentPicturesPath . UserSyear() . '/' . $student_id . '.jpg' ) )
			|| file_exists( ( $picture_path = $StudentPicturesPath . ( UserSyear() - 1 ) . '/' . $student_id . '.jpg' ) ) ) )
	{
		return makeTipMessage( '<img src="' . $picture_path . '" width="150" />', $title, $title );
	}
	else
		return $title;
}
