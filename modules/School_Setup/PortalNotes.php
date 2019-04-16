<?php
require_once 'ProgramFunctions/PortalPollsNotes.fnc.php';
require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

DrawHeader( ProgramTitle() );

// Add eventual Dates to $_REQUEST['values'].
AddRequestedDates( 'values', 'post' );

$profiles_RET = DBGet( "SELECT ID,TITLE FROM USER_PROFILES ORDER BY ID" );

if ( $_REQUEST['modfunc'] === 'update'
	&& (  ( $_REQUEST['profiles']
		&& $_POST['profiles'] )
		|| ( $_REQUEST['values']
			&& $_POST['values'] ) )
	&& AllowEdit() )
{
	$notes_RET = DBGet( "SELECT ID FROM PORTAL_NOTES WHERE SCHOOL_ID='" . UserSchool() . "' AND SYEAR='" . UserSyear() . "'" );

	foreach ( (array) $notes_RET as $note_id )
	{
		$note_id = $note_id['ID'];
		$_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] = '';

		foreach ( array( 'admin', 'teacher', 'parent' ) as $profile_id )
		{
			if ( ! empty( $_REQUEST['profiles'][$note_id][$profile_id] ) )
			{
				$_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] .= ',' . $profile_id;
			}
		}

		if ( ! empty( $_REQUEST['profiles'][$note_id] ) )
		{
			foreach ( (array) $profiles_RET as $profile )
			{
				$profile_id = $profile['ID'];

				if ( ! empty( $_REQUEST['profiles'][$note_id][$profile_id] ) )
				{
					$_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] .= ',' . $profile_id;
				}
			}
		}

		if ( ! empty( $_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] ) )
		{
			$_REQUEST['values'][$note_id]['PUBLISHED_PROFILES'] .= ',';
		}
	}
}

if ( $_REQUEST['modfunc'] === 'update'
	&& $_REQUEST['values']
	&& $_POST['values']
	&& AllowEdit() )
{
	foreach ( (array) $_REQUEST['values'] as $id => $columns )
	{
		// FJ fix SQL bug invalid sort order.

		if ( empty( $columns['SORT_ORDER'] ) || is_numeric( $columns['SORT_ORDER'] ) )
		{
			// FJ textarea fields MarkDown sanitize.

			if ( isset( $columns['CONTENT'] ) )
			{
				$columns['CONTENT'] = SanitizeMarkDown( $_POST['values'][$id]['CONTENT'] );
			}

			if ( $id !== 'new' )
			{
				$sql = "UPDATE PORTAL_NOTES SET ";

				foreach ( (array) $columns as $column => $value )
				{
					$sql .= DBEscapeIdentifier( $column ) . "='" . $value . "',";
				}

				$sql = mb_substr( $sql, 0, -1 ) . " WHERE ID='" . $id . "'";

				DBQuery( $sql );

				//hook
				do_action( 'School_Setup/PortalNotes.php|update_portal_note' );
			}

			// New: check for Title.
			elseif ( $columns['TITLE'] )
			{
				foreach ( array( 'admin', 'teacher', 'parent' ) as $profile_id )
				{
					if ( isset( $_REQUEST['profiles']['new'][$profile_id] )
						&& $_REQUEST['profiles']['new'][$profile_id] )
					{
						$_REQUEST['values']['new']['PUBLISHED_PROFILES'] .= $profile_id . ',';
					}
				}

				foreach ( (array) $profiles_RET as $profile )
				{
					$profile_id = $profile['ID'];

					if ( isset( $_REQUEST['profiles']['new'][$profile_id] )
						&& $_REQUEST['profiles']['new'][$profile_id] )
					{
						$_REQUEST['values']['new']['PUBLISHED_PROFILES'] .= $profile_id . ',';
					}
				}

				$columns['PUBLISHED_PROFILES'] = $_REQUEST['values']['new']['PUBLISHED_PROFILES'] ?
				',' . $_REQUEST['values']['new']['PUBLISHED_PROFILES'] :
				'';

				$sql = "INSERT INTO PORTAL_NOTES ";

				//FJ file attached to portal notes
				$fields = 'ID,SCHOOL_ID,SYEAR,PUBLISHED_DATE,PUBLISHED_USER,';

				$portal_note_id = DBSeqNextID( 'PORTAL_NOTES_SEQ' );

				//$values = db_seq_nextval('PORTAL_NOTES_SEQ').",'".UserSchool()."','".UserSyear()."',CURRENT_TIMESTAMP,'".User('STAFF_ID')."',";
				$values = $portal_note_id . ",'" . UserSchool() . "','" . UserSyear() . "',CURRENT_TIMESTAMP,'" . User( 'STAFF_ID' ) . "',";

				if ( $columns['FILE_OR_EMBED'] == 'FILE' )
				{
					$columns['FILE_ATTACHED'] = FileUpload(
						'FILE_ATTACHED_FILE',
						$PortalNotesFilesPath,
						FileExtensionWhiteList(),
						0,
						$error
					);
				}
				elseif ( $columns['FILE_OR_EMBED'] == 'EMBED' )
				{
					if ( filter_var( $columns['FILE_ATTACHED_EMBED'], FILTER_VALIDATE_URL ) !== false )
					{
						$columns['FILE_ATTACHED'] = $columns['FILE_ATTACHED_EMBED'];
					}
				}

				unset( $columns['FILE_ATTACHED_EMBED'], $columns['FILE_OR_EMBED'] );

				$go = 0;

				foreach ( (array) $columns as $column => $value )
				{
					if ( ! empty( $value ) || $value == '0' )
					{
						$fields .= DBEscapeIdentifier( $column ) . ',';
						$values .= "'" . $value . "',";
						$go = true;
					}
				}

				$sql .= '(' . mb_substr( $fields, 0, -1 ) . ') values(' . mb_substr( $values, 0, -1 ) . ')';

				if ( $go && empty( $error ) )
				{
					DBQuery( $sql );

					//hook
					do_action( 'School_Setup/PortalNotes.php|create_portal_note' );
				}
			}
		}
		else
		{
			$error[] = _( 'Please enter a valid Sort Order.' );
		}
	}

	// Unset modfunc & values & profiles & redirect URL.
	RedirectURL( array( 'modfunc', 'values', 'profiles' ) );
}

if ( $_REQUEST['modfunc'] === 'remove'
	&& AllowEdit() )
{
	if ( DeletePrompt( _( 'Note' ) ) )
	{
		// FJ file attached to portal notes.
		$file_to_remove = DBGetOne( "SELECT FILE_ATTACHED
			FROM PORTAL_NOTES
			WHERE ID='" . $_REQUEST['id'] . "'" );

		if ( $file_to_remove
			&& file_exists( $file_to_remove ) )
		{
			unlink( $file_to_remove );
		}

		DBQuery( "DELETE FROM PORTAL_NOTES WHERE ID='" . $_REQUEST['id'] . "'" );

		//hook
		do_action( 'School_Setup/PortalNotes.php|delete_portal_note' );

		// Unset modfunc & ID & redirect URL.
		RedirectURL( array( 'modfunc', 'id' ) );
	}
}

echo ErrorMessage( $error );

if ( ! $_REQUEST['modfunc'] )
{
	// FJ file attached to portal notes.
	$sql = "SELECT ID,SORT_ORDER,TITLE,CONTENT,START_DATE,END_DATE,PUBLISHED_PROFILES,FILE_ATTACHED,
	CASE WHEN END_DATE IS NOT NULL AND END_DATE<CURRENT_DATE THEN 'Y' ELSE NULL END AS EXPIRED
	FROM PORTAL_NOTES
	WHERE SCHOOL_ID='" . UserSchool() . "'
	AND SYEAR='" . UserSyear() . "'
	ORDER BY EXPIRED DESC,SORT_ORDER,PUBLISHED_DATE DESC";

	$QI = DBQuery( $sql );
	$notes_RET = DBGet( $QI, array( 'TITLE' => '_makeTextInput', 'CONTENT' => '_makeContentInput', 'SORT_ORDER' => '_makeTextInput', 'FILE_ATTACHED' => 'makeFileAttached', 'START_DATE' => 'makePublishing' ) );

	$columns = array( 'TITLE' => _( 'Title' ), 'CONTENT' => _( 'Note' ), 'SORT_ORDER' => _( 'Sort Order' ), 'FILE_ATTACHED' => _( 'File Attached' ), 'START_DATE' => _( 'Publishing Options' ) );
	//,'START_TIME' => 'Start Time','END_TIME' => 'End Time'
	$link['add']['html'] = array( 'TITLE' => _makeTextInput( '', 'TITLE' ), 'CONTENT' => _makeContentInput( '', 'CONTENT' ), 'SHORT_NAME' => _makeTextInput( '', 'SHORT_NAME' ), 'SORT_ORDER' => _makeTextInput( '', 'SORT_ORDER' ), 'FILE_ATTACHED' => makeFileAttached( '', 'FILE_ATTACHED' ), 'START_DATE' => makePublishing( '', 'START_DATE' ) );
	$link['remove']['link'] = 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=remove';
	$link['remove']['variables'] = array( 'id' => 'ID' );

	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=update" method="POST" enctype="multipart/form-data" onsubmit="if (document.getElementById(\'FILE_ATTACHED_FILE\').value) document.getElementById(\'loading\').innerHTML=\'<span class=loading></span>\';">';

	DrawHeader( '', SubmitButton() );

	ListOutput( $notes_RET, $columns, 'Note', 'Notes', $link );

	echo '<br /><div class="center">' . SubmitButton() . '</div>';
	echo '</form>';
}

/**
 * @param $value
 * @param $name
 */
function _makeTextInput( $value, $name )
{
	global $THIS_RET;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	if ( $name !== 'TITLE' )
	{
		$extra = 'size=5 maxlength=10';
	}
	elseif ( $id !== 'new' )
	{
		$extra = 'required';
	}

	return TextInput(
		( $name == 'TITLE' && $THIS_RET['EXPIRED'] ?
			array( $value, '<span style="color:red">' . $value . '</span>' ) :
			$value ),
		'values[' . $id . '][' . $name . ']',
		'',
		$extra
	);
}

/**
 * @param $value
 * @param $name
 * @return mixed
 */
function _makeContentInput( $value, $name )
{
	global $THIS_RET;

	if ( $THIS_RET['ID'] )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	$return .= '<div id="divNoteContent' . $id . '" class="rt2colorBox">' .
	TextAreaInput( $value, "values[" . $id . "][" . $name . "]", '', 'rows=5' ) .
		'</div>';

	return $return;
}
