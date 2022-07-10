<?php
/**
 * Student Billing module Portal Alerts
 *
 * @package RosarioSIS
 * @subpackage modules/Student_Billing
 */

/**
 * Student Billing module Portal Alerts
 * Student Billing new fee note.
 *
 * @since 5.4
 *
 * @uses misc/Portal.php|portal_alerts hook
 *
 * @return true if new referrals note, else false.
 */
function StudentBillingPortalAlerts()
{
	global $note;

	if ( User( 'PROFILE' ) !== 'parent'
		|| ! AllowUse( 'Student_Billing/StudentFees.php' )
		|| ! $_SESSION['LAST_LOGIN'] )
	{
		return false;
	}

	$last_login_date = mb_substr( $_SESSION['LAST_LOGIN'], 0, 10 );

	$extra = [];

	$extra['SELECT_ONLY'] = 'count(*) AS COUNT';

	$extra['FROM'] = ',billing_fees bf ';

	$extra['WHERE'] = " AND bf.STUDENT_ID=ssm.STUDENT_ID AND bf.SYEAR=ssm.SYEAR
		AND bf.SCHOOL_ID=ssm.SCHOOL_ID AND
		bf.ASSIGNED_DATE BETWEEN '" . $last_login_date . "' AND '" . DBDate() . "'";

	$student_billing_fees_RET = GetStuList( $extra );

	if ( ! empty( $student_billing_fees_RET[1]['COUNT'] ) )
	{
		$message = '<a href="Modules.php?modname=Student_Billing/StudentFees.php">
			<span class="module-icon Student_Billing"></span> ';

		$message .= sprintf(
			ngettext( '%d new fee', '%d new fees', $student_billing_fees_RET[1]['COUNT'] ),
			$student_billing_fees_RET[1]['COUNT']
		);

		$message .= '</a>';

		$note[] = $message;

		return true;
	}

	return false;
}

add_action( 'misc/Portal.php|portal_alerts', 'StudentBillingPortalAlerts', 0 );
