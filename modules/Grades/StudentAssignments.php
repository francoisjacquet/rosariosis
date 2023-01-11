<?php
/**
 * Student Assignments
 *
 * Consult & submit assignments
 *
 * @since 2.9
 *
 * @package RosarioSIS
 * @subpackage modules/Grades
 */

// Include Student Assignments functions.
require_once 'modules/Grades/includes/StudentAssignments.fnc.php';

if ( ! empty( $_REQUEST['assignment_id'] )
	&& ! empty( $_REQUEST['marking_period_id'] ) )
{
	// Outside link: Assignment is in the current MP?
	if ( $_REQUEST['marking_period_id'] != UserMP() )
	{
		// Reset current MarkingPeriod.
		$_SESSION['UserMP'] = $_REQUEST['marking_period_id'];
	}

	RedirectURL( 'marking_period_id' );
}

DrawHeader( ProgramTitle() . ' - ' . GetMP( UserMP() ) );

if ( ! empty( $_REQUEST['assignment_id'] )
	&& ! GetAssignment( $_REQUEST['assignment_id'] ) )
{
	// @since 10.7 Check Assignment is in current MP.
	RedirectURL( 'assignment_id' );
}

if ( ! empty( $_REQUEST['assignment_id'] ) )
{
	if ( isset( $_POST['submit_assignment'] ) )
	{
		$submitted = StudentAssignmentSubmit( $_REQUEST['assignment_id'], $error );

		if ( $submitted )
		{
			$note[] = button( 'check', '', '', 'bigger' ) . '&nbsp;' . _( 'Assignment submitted.' );

			echo ErrorMessage( $note, 'note' );
		}

		RedirectURL( 'message' );

		echo ErrorMessage( $error );
	}

	$assignments_link = PreparePHP_SELF( $_REQUEST, [ 'search_modfunc', 'assignment_id' ] );

	DrawHeader( '<a href="' . URLEscape( $assignments_link ) . '">&laquo; ' . _( 'Back' ) . '</a>' );

	$_ROSARIO['allow_edit'] = true;

	$form_action = PreparePHP_SELF( $_REQUEST, [], [ 'modfunc' => 'submit' ] );

	echo '<form method="POST" action="">';

	StudentAssignmentSubmissionOutput( $_REQUEST['assignment_id'] );

	echo '</form>';
}
else
{
	// Output Current Quarter's Assignments List.
	StudentAssignmentsListOutput();
}
