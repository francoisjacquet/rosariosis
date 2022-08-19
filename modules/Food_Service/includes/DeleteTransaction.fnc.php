<?php

/**
 * Delete Food Service Transaction
 *
 * @since 10.0 Use db_trans_*() functions
 *
 * @param $transaction_id  Transaction ID.
 * @param $type           'student' or 'staff'.
 */
function DeleteTransaction( $transaction_id, $type = 'student' )
{
	if ( $type == 'staff' )
	{
		/**
		 * Fix MySQL 5.6 error Can't specify target table for update in FROM clause.
		 *
		 * @link https://stackoverflow.com/questions/45494/mysql-error-1093-cant-specify-target-table-for-update-in-from-clause
		 */
		$staff_id = DBGetOne( "SELECT STAFF_ID
			FROM food_service_staff_transactions
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'" );

		$sql1 = "UPDATE food_service_staff_transactions
		SET BALANCE=BALANCE-(SELECT coalesce(sum(AMOUNT),0)
			FROM food_service_staff_transaction_items
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "')
		WHERE TRANSACTION_ID>='" . (int) $transaction_id . "'
		AND STAFF_ID='" . (int) $staff_id . "'";

		$sql2 = "UPDATE food_service_staff_accounts
		SET BALANCE=BALANCE-(SELECT coalesce(sum(AMOUNT),0)
			FROM food_service_staff_transaction_items
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "')
		WHERE STAFF_ID='" . (int) $staff_id . "'";

		$sql3 = "DELETE FROM food_service_staff_transaction_items
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'";

		$sql4 = "DELETE FROM food_service_staff_transactions
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'";
	}
	else
	{
		/**
		 * Fix MySQL 5.6 error Can't specify target table for update in FROM clause.
		 *
		 * @link https://stackoverflow.com/questions/45494/mysql-error-1093-cant-specify-target-table-for-update-in-from-clause
		 */
		$account_id = DBGetOne( "SELECT ACCOUNT_ID
			FROM food_service_transactions
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'" );

		$sql1 = "UPDATE food_service_transactions
			SET BALANCE=BALANCE-(SELECT coalesce(sum(AMOUNT),0)
				FROM food_service_transaction_items
				WHERE TRANSACTION_ID='" . (int) $transaction_id . "')
			WHERE TRANSACTION_ID>='" . (int) $transaction_id . "'
			AND ACCOUNT_ID='" . (int) $account_id . "'";

		$sql2 = "UPDATE food_service_accounts
		SET BALANCE=BALANCE-(SELECT coalesce(sum(AMOUNT),0)
			FROM food_service_transaction_items
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "')
		WHERE ACCOUNT_ID='" . (int) $account_id . "'";

		$sql3 = "DELETE FROM food_service_transaction_items
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'";

		$sql4 = "DELETE FROM food_service_transactions
			WHERE TRANSACTION_ID='" . (int) $transaction_id . "'";
	}

	db_trans_start();

	db_trans_query( $sql1 . '; ' . $sql2 . '; ' . $sql3 . '; ' . $sql4 . ';' );

	db_trans_commit();
}
