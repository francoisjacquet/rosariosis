<?php

DrawHeader( ProgramTitle() );

if ( $_REQUEST['modfunc'] === 'save' )
{
	if ( ! empty( $_REQUEST['activity_id'] ) )
	{
		if ( ! empty( $_REQUEST['student'] ) )
		{
			// Fix bug add the same activity more than once.
			// $current_RET = DBGet( "SELECT STUDENT_ID FROM student_eligibility_activities WHERE ACTIVITY_ID='".$_SESSION['activity_id']."' AND SYEAR='".UserSyear()."'",array(),array('STUDENT_ID'));
			$current_RET = DBGet( "SELECT STUDENT_ID
				FROM student_eligibility_activities
				WHERE ACTIVITY_ID='" . (int) $_REQUEST['activity_id'] . "'
				AND SYEAR='" . UserSyear() . "'", [], [ 'STUDENT_ID' ] );

			// Group SQL inserts.
			$sql = '';

			foreach ( (array) $_REQUEST['student'] as $student_id )
			{
				if ( empty( $current_RET[$student_id] ) )
				{
					$sql .= DBInsertSQL(
						'student_eligibility_activities',
						[
							'SYEAR' => UserSyear(),
							'STUDENT_ID' => (int) $student_id,
							'ACTIVITY_ID' => (int) $_REQUEST['activity_id'],
						]
					);
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
	echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';
	DrawHeader( '', SubmitButton( _( 'Add Activity to Selected Students' ) ) );
	echo '<br />';

	PopTable( 'header', _( 'Add Activity' ) );

	$activities_RET = DBGet( "SELECT ID,TITLE
		FROM eligibility_activities
		WHERE SYEAR='" . UserSyear() . "'
		AND SCHOOL_ID='" . UserSchool() . "'" );

	echo '<label><select name="activity_id" required><option value="">' . _( 'N/A' ) . '</option>';

	foreach ( (array) $activities_RET as $activity )
	{
		echo '<option value="' . AttrEscape( $activity['ID'] ) . '">' . $activity['TITLE'] . '</option>';
	}

	echo '</select>' . FormatInputTitle( _( 'Activity' ) ) . '</label>';

	PopTable( 'footer' );

	echo '<br />';
}

//FJ fix bug no Search when student already selected
$extra['link'] = [ 'FULL_NAME' => false ];
$extra['SELECT'] = ",NULL AS CHECKBOX";
$extra['functions'] = [ 'CHECKBOX' => 'MakeChooseCheckbox' ];
$extra['columns_before'] = [ 'CHECKBOX' => MakeChooseCheckbox( '', 'STUDENT_ID', 'student' ) ];
$extra['new'] = true;
Widgets( 'activity' );
Widgets( 'course' );

Search( 'student_id', $extra );

if ( $_REQUEST['search_modfunc'] === 'list' )
{
	echo '<br /><div class="center">' . SubmitButton( _( 'Add Activity to Selected Students' ) ) . '</div></form>';
}
