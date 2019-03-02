<?php

// set comments Marking Period
$comments_MP = UserMP();

// if Semester comment
if ( ProgramConfig( 'students', 'STUDENTS_SEMESTER_COMMENTS' ) )
{
	$comments_MP = GetParentMP( 'SEM', UserMP() );
}

if ( AllowEdit()
	&& isset( $_POST['values'] )
	&& trim( $_POST['values']['STUDENT_MP_COMMENTS'][ UserStudentID() ]['COMMENT'] ) !== '' )
{
	require_once 'ProgramFunctions/MarkDownHTML.fnc.php';

	// Sanitize MarkDown.
	$comment = SanitizeMarkDown( $_POST['values']['STUDENT_MP_COMMENTS'][ UserStudentID() ]['COMMENT'] );

	if ( $comment )
	{
		// FJ add time and user to comments "comment thread" like.
		$comment = array( array(
			'date' => date( 'Y-m-d G:i:s' ),
			'staff_id' => User( 'STAFF_ID' ),
			'comment' => $comment,
		) );

		$existing_RET = DBGet( "SELECT STUDENT_ID, COMMENT
			FROM STUDENT_MP_COMMENTS
			WHERE STUDENT_ID='" . UserStudentID() . "'
			AND SYEAR='" . UserSyear() . "'
			AND MARKING_PERIOD_ID='" . $comments_MP . "'" );

		if ( isset( $existing_RET[1]['COMMENT'] ) )
		{
			// Add Comment to Existing ones.
			$comment = array_merge( $comment, (array) unserialize( $existing_RET[1]['COMMENT'] ) );
		}
		else
		{
			// Insert empty comment (SaveData wont INSERT unless $id == 'new').
			DBQuery( "INSERT INTO STUDENT_MP_COMMENTS
				(STUDENT_ID, SYEAR, MARKING_PERIOD_ID, COMMENT)
				VALUES ('" . UserStudentID() . "',
				'" . UserSyear() . "',
				'" . $comments_MP . "',
				'')" );
		}

		$_REQUEST['values']['STUDENT_MP_COMMENTS'][ UserStudentID() ]['COMMENT'] = DBEscapeString( serialize( $comment ) );

		SaveData(
			array(
				'STUDENT_MP_COMMENTS' => "STUDENT_ID='" . UserStudentID() . "'
				AND SYEAR='" . UserSyear() . "'
				AND MARKING_PERIOD_ID='" . $comments_MP . "'",
				'fields' => array(
					'STUDENT_MP_COMMENTS' => 'STUDENT_ID,SYEAR,MARKING_PERIOD_ID,',
				),
				'values' => array(
					'STUDENT_MP_COMMENTS' => "'" . UserStudentID() . "','" . UserSyear() . "','" . $comments_MP . "',",
				)
			),
			array( 'COMMENT' => _( 'Comment' ) )
		);
	}
}

if ( ! $_REQUEST['modfunc'] )
{
	$comments_RET = DBGet( "SELECT COMMENT
		FROM STUDENT_MP_COMMENTS
		WHERE STUDENT_ID='" . UserStudentID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND MARKING_PERIOD_ID='" . $comments_MP . "'" );

	?>

	<table>
		<tr><td>
			<?php echo TextAreaInput(
				'',
				'values[STUDENT_MP_COMMENTS][' . UserStudentID() . '][COMMENT]',
				GetMP( $comments_MP, 'TITLE' ) . ' ' . _( 'Comments' ),
				'rows="10"' . ( AllowEdit() ? '' : ' readonly' ),
				false
			); ?>
		</td></tr>
	<?php
	//echo '<br /><b>* '._('If more than one teacher will be adding comments for this student').':</b><br />';
	//echo '<ul><li>'._('Type your name above the comments you enter.').'</li></ul>';
	//echo '<li>'._('Leave space for other teachers to enter their comments.').'</li></ul>';
	//FJ add time and user to comments "comment thread" like
	?>
		<tr><td id="student-comments">
	<?php
	if ( ( $comments = unserialize( $comments_RET[1]['COMMENT'] ) ) )
	{
		$comments_HTML = $staff_name = array();

		foreach ( (array) $comments as $comment )
		{
			$id = $comment['staff_id'];

			if ( !isset( $staff_name[ $id ] ) )
			{
				if ( User('STAFF_ID') === $id )
				{
					$staff_name[ $id ] = User( 'NAME' );
				}
				else
				{
					$staff_name_RET = DBGet( "SELECT " . DisplayNameSQL() . " AS NAME
						FROM STAFF
						WHERE SYEAR='" . UserSyear() . "'
						AND USERNAME=(
							SELECT USERNAME
							FROM STAFF
							WHERE SYEAR='" . Config( 'SYEAR' ) . "'
							AND STAFF_ID='" . $id . "'
						)" );

					$staff_name[ $id ] = $staff_name_RET[1]['NAME'];
				}
			}

			// Comment meta data: "Date hour, User name:"
			$comment_meta = '<span>' .
				ProperDateTime( $comment['date'] ) . ', ' .
				$staff_name[ $id ] .
				':</span>';

			// convert MarkDown to HTML
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
