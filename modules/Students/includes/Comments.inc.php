<?php

// Set comments Marking Period.
$comments_MP = UserMP();

// If Semester comment.
if ( ProgramConfig( 'students', 'STUDENTS_SEMESTER_COMMENTS' ) )
{
	$comments_MP = GetParentMP( 'SEM', UserMP() );
}

if ( AllowEdit()
	&& isset( $_POST['values'] )
	&& trim( $_POST['values']['student_mp_comments'][ UserStudentID() ]['COMMENT'] ) !== '' )
{
	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	// Sanitize MarkDown.
	$comment = SanitizeMarkDown( $_POST['values']['student_mp_comments'][ UserStudentID() ]['COMMENT'] );

	if ( $comment )
	{
		// Add time and user to comments "thread" like.
		$comment = [ [
			'date' => date( 'Y-m-d G:i:s' ),
			'staff_id' => User( 'STAFF_ID' ),
			'comment' => $comment,
		] ];

		$existing_comment = DBGetOne( "SELECT COMMENT
			FROM student_mp_comments
			WHERE STUDENT_ID='" . UserStudentID() . "'
			AND SYEAR='" . UserSyear() . "'
			AND MARKING_PERIOD_ID='" . (int) $comments_MP . "'" );

		if ( $existing_comment )
		{
			// @since 11.0 Move from serialize() to json_encode()
			$existing_comment_decoded = json_decode( $existing_comment, true );

			if ( json_last_error() !== JSON_ERROR_NONE )
			{
				$existing_comment_decoded = unserialize( $existing_comment );
			}

			// Add Comment to Existing ones.
			$comment = array_merge( $comment, (array) $existing_comment_decoded );
		}
		else
		{
			// Insert empty comment (SaveData wont INSERT unless $id == 'new').
			DBQuery( "INSERT INTO student_mp_comments
				(STUDENT_ID, SYEAR, MARKING_PERIOD_ID, COMMENT)
				VALUES ('" . UserStudentID() . "',
				'" . UserSyear() . "',
				'" . $comments_MP . "',
				'')" );
		}

		$_REQUEST['values']['student_mp_comments'][ UserStudentID() ]['COMMENT'] = DBEscapeString( json_encode( $comment ) );

		SaveData(
			[
				'student_mp_comments' => "STUDENT_ID='" . UserStudentID() . "'
				AND SYEAR='" . UserSyear() . "'
				AND MARKING_PERIOD_ID='" . (int) $comments_MP . "'",
				'fields' => [
					'student_mp_comments' => 'STUDENT_ID,SYEAR,MARKING_PERIOD_ID,',
				],
				'values' => [
					'student_mp_comments' => "'" . UserStudentID() . "','" . UserSyear() . "','" . $comments_MP . "',",
				]
			],
			[ 'COMMENT' => _( 'Comment' ) ]
		);
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	?>
	<table>
		<tr><td>
			<?php echo TextAreaInput(
				'',
				'values[student_mp_comments][' . UserStudentID() . '][COMMENT]',
				GetMP( $comments_MP, 'TITLE' ) . ' ' . _( 'Comments' ),
				'rows="6"' . ( AllowEdit() ? '' : ' readonly' ),
				false
			); ?>
		</td></tr>
		<tr><td id="student-comments">
	<?php
	$comments = DBGetOne( "SELECT COMMENT
		FROM student_mp_comments
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND MARKING_PERIOD_ID='" . (int) $comments_MP . "'" );

	// @since 11.0 Move from serialize() to json_encode()
	$comments_decoded = json_decode( $comments, true );

	if ( json_last_error() !== JSON_ERROR_NONE )
	{
		$comments_decoded = unserialize( $comments );
	}

	if ( $comments_decoded )
	{
		$comments_HTML = $staff_name = [];

		foreach ( (array) $comments_decoded as $comment )
		{
			$id = $comment['staff_id'];

			if ( ! isset( $staff_name[ $id ] ) )
			{
				if ( User( 'STAFF_ID' ) === $id )
				{
					$staff_name[ $id ] = User( 'NAME' );
				}
				else
				{
					$staff_name[ $id ] = DBGetOne( "SELECT " . DisplayNameSQL() . " AS NAME
						FROM staff
						WHERE SYEAR='" . UserSyear() . "'
						AND USERNAME=(
							SELECT USERNAME
							FROM staff
							WHERE SYEAR='" . Config( 'SYEAR' ) . "'
							AND STAFF_ID='" . (int) $id . "'
						)" );
				}
			}

			// Comment meta data: "Date hour, User name:".
			$comment_meta = '<span>' .
				ProperDateTime( $comment['date'], 'short' ) . ', ' .
				$staff_name[ $id ] .
				':</span>';

			// Convert MarkDown to HTML.
			$comment_MD = '<div class="markdown-to-html">' . $comment['comment'] . '</div>';

			$comments_HTML[] = $comment_meta . $comment_MD;
		}

		echo implode( "\n", $comments_HTML );
	}
	?>
		</td></tr>
	</table>
	<?php

	require_once 'modules/Students/includes/Other_Info.inc.php';
}
