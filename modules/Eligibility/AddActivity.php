<?php

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['activity_id'] ) )
	{
		if ( ! empty( $_REQUEST['student'] ) )
		{
			// FJ fix bug add the same activity more than once
			// $current_RET = DBGet( "SELECT STUDENT_ID FROM STUDENT_ELIGIBILITY_ACTIVITIES WHERE ACTIVITY_ID='".$_SESSION['activity_id']."' AND SYEAR='".UserSyear()."'",array(),array('STUDENT_ID'));
			$current_RET = DBGet( "SELECT STUDENT_ID
				FROM STUDENT_ELIGIBILITY_ACTIVITIES
				WHERE ACTIVITY_ID='" . $_REQUEST['activity_id'] . "'
				AND SYEAR='" . UserSyear() . "'", array(), array( 'STUDENT_ID' ) );

			// Group SQL inserts.
			$sql = '';

			foreach ( (array) $_REQUEST['student'] as $student_id )
			{
				if ( ! $current_RET[$student_id] )
				{
					$sql .= "INSERT INTO STUDENT_ELIGIBILITY_ACTIVITIES (SYEAR,STUDENT_ID,ACTIVITY_ID)
								values('" . UserSyear() . "','" . $student_id . "','" . $_REQUEST['activity_id'] . "');";
				}
			}

			if ( $sql )
			{
				DBQuery( $sql );

				$note[] = button( 'check' ) . '&nbsp;' . _( 'This activity has been added to the selected students.' );
			}
		}
		else
		{
			$error[] = _( 'You must choose at least one student.' );
		}
	}
	else
	{
		$error[] = _( 'You must choose an activity.' );
	}

	// Unset modfunc & redirect URL.
	RedirectURL( 'modfunc' );
}

echo ErrorMessage( $note, 'note' );

echo ErrorMessage( $error );

if ( $_REQUEST['search_modfunc'] === 'list' )
{
	echo '<form action="Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save" method="POST">';
	DrawHeader( '', SubmitButton( _( 'Add Activity to Selected Students' ) ) );
	echo '<br />';

//FJ css WPadmin
	echo '<table class="postbox center col1-align-right"><tr><td>' . _( 'Activity' ) . '</td>';
	echo '<td>';
	$activities_RET = DBGet( "SELECT ID,TITLE
		FROM ELIGIBILITY_ACTIVITIES
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );
	echo '<select name="activity_id"><option value="">' . _( 'N/A' ) . '</option>';

	foreach ( (array) $activities_RET as $activity )
	{
		echo '<option value="' . $activity['ID'] . '">' . $activity['TITLE'] . '</option>';
	}

	echo '</select>';
	echo '</td>';
	echo '</tr></table><br />';
}

//FJ fix bug no Search when student already selected
$extra['link'] = array( 'FULL_NAME' => false );
$extra['SELECT'] = ",CAST (NULL AS CHAR(1)) AS CHECKBOX";
$extra['functions'] = array( 'CHECKBOX' => 'MakeChooseCheckbox' );
$extra['columns_before'] = array( 'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ) );
$extra['new'] = true;
Widgets( 'activity' );
Widgets( 'course' );

Search( 'student_id', $extra );

if ( $_REQUEST['search_modfunc'] === 'list' )
{
	echo '<br /><div class="center">' . SubmitButton( _( 'Add Activity to Selected Students' ) ) . '</div></form>';
}
