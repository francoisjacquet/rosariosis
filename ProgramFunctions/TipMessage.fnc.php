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
 * @todo Use CSS class + ID to trigger plugin and remove inline JS (data attributes) + onMouseOver + onMouseOut + onclick
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

	if ( isset( $_REQUEST['_ROSARIO_PDF'] ) )
	{
		return '<div class="tipmsg-label">' . $label . '</div>';
	}

	$tip_msg = '<script>var tipmsg' . $tip_msg_ID . '=[' .
		json_encode( (string) $title ) . ',' .
		json_encode( (string) $message ) . '];</script>';

	$tip_msg .= '<div class="tipmsg-label" onMouseOver="stm(tipmsg' . $tip_msg_ID . ');">' .
		$label . '</div>';

	$tip_msg_ID++;

	return $tip_msg;
}


/**
 * Make Student Photo Tip Message
 * Look for current & previous school year Photos
 *
 * @example require_once 'ProgramFunctions/TipMessage.fnc.php';
 *          return MakeStudentPhotoTipMessage( $THIS_RET['STUDENT_ID'], $full_name );
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
		return MakeTipMessage( '<img src="' . $picture_path . '" width="150" />', $title, $title );
	}
	else
		return $title;
}


/**
 * Make User Photo Tip Message
 * Look for current & previous school year Photos
 *
 * @example require_once 'ProgramFunctions/TipMessage.fnc.php';
 *          return MakeUserPhotoTipMessage( $THIS_RET['STAFF_ID'], $full_name );
 *
 * @since 3.8
 *
 * @uses MakeTipMessage()
 * @uses DHTML tip message JS plugin
 *
 * @see assets/js/tipmessage/
 *
 * @global $UserPicturesPath Student Pictures Path
 *
 * @param  string $staff_id Staff ID.
 * @param  string $title    Tip title & label.
 *
 * @return string User Photo Tip Message or $title if no Photo found
 */
function MakeUserPhotoTipMessage( $staff_id, $title )
{
	global $UserPicturesPath;

	if ( $UserPicturesPath
		&& ( file_exists( ( $picture_path = $UserPicturesPath . UserSyear() . '/' . $staff_id . '.jpg' ) )
			|| file_exists( ( $picture_path = $UserPicturesPath . ( UserSyear() - 1 ) . '/' . $staff_id . '.jpg' ) ) ) )
	{
		return MakeTipMessage( '<img src="' . $picture_path . '" width="150" />', $title, $title );
	}
	else
		return $title;
}
