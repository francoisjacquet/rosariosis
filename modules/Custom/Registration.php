<?php
/**
 * Registration
 * Admin can customize form for Parents.
 */

require_once 'ProgramFunctions/MarkDownHTML.fnc.php';
require_once 'ProgramFunctions/FileUpload.fnc.php';
require_once 'ProgramFunctions/Fields.fnc.php';
require_once 'modules/Custom/includes/Registration.fnc.php';

$_REQUEST['student_id'] = UserStudentID();

DrawHeader( ProgramTitle() );

if ( User( 'PROFILE' ) === 'admin' )
{
	require_once 'modules/Custom/includes/RegistrationAdmin.fnc.php';

	if ( $_REQUEST['modfunc'] === 'save' )
	{
		$values = [
			'parent' => $_REQUEST['parent'],
			'address' => issetVal( $_REQUEST['address'], [] ),
			'contact' => issetVal( $_REQUEST['contact'], [] ),
			'student' => issetVal( $_REQUEST['student'], [] ),
		];

		if ( RegistrationFormConfigSave( $values ) )
		{
			$note[] = button( 'check' ) . '&nbsp;' . _( 'The Registration form was saved.' );
		}

		// Delete modfunc, values & redirect URL.
		RedirectURL( [ 'modfunc', 'parent', 'address', 'contact', 'student' ] );
	}

	if ( $_REQUEST['modfunc'] === 'preview' )
	{
		// Back header.
		DrawHeader( RegistrationAdminPreviewHeader() );

		DrawHeader( RegistrationIntroHeader() );

		echo '<br />';

		PopTable( 'header', _( 'Registration' ) );

		$config = RegistrationFormConfig();

		RegistrationFormOutput( $config );

		PopTable( 'footer' );
	}

	if ( ! $_REQUEST['modfunc'] )
	{
		echo ErrorMessage( $note, 'note' );

		echo ErrorMessage( $error );

		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';

		// Preview header.
		DrawHeader( RegistrationAdminPreviewHeader(), SubmitButton() );

		echo '<br />';

		PopTable( 'header', _( 'Configuration' ) );

		$config = RegistrationFormConfig();

		RegistrationAdminFormOutput( $config );

		PopTable( 'footer' );

		echo '<br /><div class="center">' . SubmitButton() . '</div></form>';
	}
}

else
{
	if ( $_REQUEST['modfunc'] === 'save' )
	{
		require_once 'modules/Custom/includes/RegistrationSave.fnc.php';

		// @since 13.1 Prevent submitting Registration form twice
		$registration_done = DBGetOne( "SELECT 1
			FROM students_join_address
			WHERE STUDENT_ID='" . UserStudentID() . "'" );

		if ( ! $registration_done )
		{
			// Add eventual Dates to $_REQUEST['parent'].
			AddRequestedDates( 'parent' );

			// Add eventual Dates to $_REQUEST['address'].
			AddRequestedDates( 'address' );

			// Add eventual Dates to $_REQUEST['contact'].
			AddRequestedDates( 'contact' );

			// Add eventual Dates to $_REQUEST['students'].
			AddRequestedDates( 'students' );

			$values = [
				'parent' => issetVal( $_REQUEST['parent'], [] ),
				'address' => issetVal( $_REQUEST['address'], [] ),
				'contact' => issetVal( $_REQUEST['contact'], [] ),
				'student' => issetVal( $_REQUEST['students'], [] ),
			];

			$config = RegistrationFormConfig();

			if ( ! empty( $_REQUEST['sibling_use_contacts_address'] ) )
			{
				// Security fix check sibling belongs to the authenticated parent
				$sibling_id = RegistrationSiblingRegistered();

				$save_ok = RegistrationSaveSibling( $config, $values, $sibling_id );
			}
			else
			{
				$save_ok = RegistrationSave( $config, $values );
			}
		}

		if ( ! empty( $save_ok ) )
		{
			// @todo Move to ProgramFunctions/SendNotification.fnc.php.
			// Send New Registration email to Notify.
			if ( filter_var( $RosarioNotifyAddress, FILTER_VALIDATE_EMAIL ) )
			{
				$student_name = DBGetOne( "SELECT " . DisplayNameSQL() . " AS FULL_NAME
					FROM students
					WHERE STUDENT_ID='" . UserStudentID() . "'" );

				$message = sprintf(
					_( 'New Registration %s (%d) has been registered by %s.' ),
					$student_name,
					UserStudentID(),
					User( 'NAME' )
				);

				(new RosarioSIS\Functions\Email)->send( $RosarioNotifyAddress, _( 'New Registration' ), $message );
			}
		}

		// Delete modfunc, values & redirect URL.
		RedirectURL( [ 'modfunc', 'parent', 'address', 'contact', 'student' ] );
	}

	// Student check.
	if ( ! UserStudentID() )
	{
		$error[] = _( 'No Students were found.' );

		echo ErrorMessage( $error, 'error' );
	}

	$registration_done = DBGetOne( "SELECT 1
		FROM students_join_address
		WHERE STUDENT_ID='" . UserStudentID() . "'" );

	// Registration check.
	if ( $registration_done )
	{
		$note[] = button( 'check' ) . ' ' .
			( User( 'STAFF_ID' ) ?
				_( 'Your child has been registered.' ) :
				_( 'Your parents have been registered.' ) );

		echo ErrorMessage( $note, 'note' );
	}

	if ( ! $_REQUEST['modfunc']
		&& UserStudentID()
		&& ! $registration_done )
	{
		$_ROSARIO['allow_edit'] = true;

		echo ErrorMessage( $error );

		echo '<form action="' . URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] . '&modfunc=save' ) . '" method="POST">';

		DrawHeader( RegistrationIntroHeader(), SubmitButton() );

		echo '<br />';

		PopTable( 'header', _( 'Registration' ) );

		$config = RegistrationFormConfig();

		RegistrationFormOutput( $config );

		PopTable( 'footer' );

		echo '<br /><div class="center">' . SubmitButton() . '</div></form>';
	}
}
