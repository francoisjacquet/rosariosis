<?php
function _makeIncomesRemove( $value, $column )
{
	global $THIS_RET;

	return button(
		'remove',
		'',
		URLEscape( 'Modules.php?modname=' . $_REQUEST['modname'] .
			'&modfunc=remove&id=' . $THIS_RET['ID'] . '&staff_id=' . UserStaffID() )
	);
}

function _makeSalariesRemove( $value, $column )
{
	return _makeIncomesRemove( $value, $column );
}

function _makePaymentsRemove( $value, $column )
{
	return _makeIncomesRemove( $value, $column );
}

function _makeIncomesTextInput( $value, $column )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
		$div = 'force';
	}
	else
	{
		$id = 'new';
		$div = false;
	}

	$extra = 'maxlength=255';

	if ( $column === 'AMOUNT' )
	{
		$extra = ' type="number" step="0.01" max="999999999999" min="-999999999999"';
	}
	elseif ( ! $value )
	{
		$extra .= ' size=15';
	}

	return TextInput( $value, 'values[' . $id . '][' . $column . ']', '', $extra, $div );
}

function _makePaymentsTextInput( $value, $column )
{
	return _makeIncomesTextInput( $value, $column );
}

function _makeSalariesTextInput( $value, $name )
{
	return _makeIncomesTextInput( $value, $name );
}


function _makeIncomesDateInput( $value, $column )
{
	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
		$id = 'new';

	return DateInput( $value, 'values[' . $id . '][' . $column . ']', '', ( $id !== 'new' ), false );
}

function _makePaymentsDateInput( $value, $name )
{
	return _makeIncomesDateInput( $value, $name );
}

function _makeSalariesDateInput( $value, $name )
{	global $THIS_RET;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];
	}
	else
	{
		$id = 'new';
	}

	$name = 'values[' . $id . '][' . $name . ']';

	return DateInput( $value, $name );
}


/**
 * Make Payments Comments Input
 * Add Salaries dropdown to reconcile Payment:
 * Automatically fills the Comments & Amount inputs.
 *
 * @since 5.1
 * @since 7.7 Remove Salaries having a Payment (same Amount & Comments (Title), after or on Assigned Date).
 *
 * @uses _makePaymentsTextInput()
 *
 * @param  string $value Comments value.
 * @param  string $name  Column name, 'COMMENTS'.
 *
 * @return string Text input if not new or if no Salaries found, else Text input & Salaries dropdown.
 */
function _makePaymentsCommentsInput( $value, $name )
{
	global $THIS_RET;

	$text_input = _makePaymentsTextInput( $value, $name );

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		return $text_input;
	}

	// Add Salaries dropdown to reconcile Payment.
	$salaries_RET = DBGet( "SELECT ID,TITLE,ASSIGNED_DATE,DUE_DATE,AMOUNT
		FROM accounting_salaries sal
		WHERE STAFF_ID='" . UserStaffID() . "'
		AND SYEAR='" . UserSyear() . "'
		AND NOT EXISTS(SELECT 1
			FROM accounting_payments
			WHERE STAFF_ID='" . UserStaffID() . "'
			AND SYEAR='" . UserSyear() . "'
			AND AMOUNT=sal.AMOUNT
			AND (COMMENTS=sal.TITLE OR COMMENTS LIKE CONCAT('%',sal.TITLE) OR COMMENTS LIKE CONCAT(sal.TITLE,'%'))
			AND PAYMENT_DATE>=sal.ASSIGNED_DATE)
		ORDER BY ASSIGNED_DATE DESC
		LIMIT 20" );

	if ( ! $salaries_RET )
	{
		return $text_input;
	}

	$salaries_options = [];

	foreach ( $salaries_RET as $salary )
	{
		$salaries_options[ $salary['AMOUNT'] . '|' . $salary['TITLE'] ] = ProperDate( $salary['ASSIGNED_DATE'], 'short' ) .
			' — ' . Currency( $salary['AMOUNT'] ) .
			' — ' . $salary['TITLE'];
	}

	// JS automatically fills the Comments & Amount inputs.
	ob_start();
	?>
	<script>
		var accountingPaymentsSalariesReconcile = function( amountComments ) {
			var separatorIndex = amountComments.indexOf( '|' ),
				amount = amountComments.substring( 0, separatorIndex ),
				comments = amountComments.substring( separatorIndex + 1 );

			$('#valuesnewAMOUNT').val( amount );
			$('#valuesnewCOMMENTS').val( comments );
		};
	</script>
	<?php
	$js = ob_get_clean();

	// Select so we can search Salaries by date, amount, & title.
	$select_input = SelectInput(
		'',
		'accounting_salaries',
		'',
		$salaries_options,
		'N/A',
		'onchange="accountingPaymentsSalariesReconcile(this.value);" style="width: 250px;"'
	);

	return $text_input . ' ' . $js . $select_input;
}

function _makeSalariesAmount( $value, $column )
{
	global $salaries_total;

	$salaries_total += $value;

	return Currency( $value );
}

function _makeIncomesAmount( $value, $column )
{
	global $incomes_total;

	$incomes_total += $value;

	return Currency( $value );
}

function _makePaymentsAmount( $value, $column )
{
	global $payments_total;

	$payments_total += $value;

	return Currency( $value );
}

/**
 * Make Salaries File Attached Input
 *
 * @since 8.1
 * @since 10.4 Add File Attached Input for existing Salaries
 *
 * @param  string $value File path value.
 * @param  string $name  Column name, 'FILE_ATTACHED'.
 *
 * @return string        File Input HTML or link to download File.
 */
function _makeSalariesFileInput( $value, $column )
{
	global $THIS_RET;

	if ( empty( $THIS_RET['ID'] ) )
	{
		return InputDivOnclick(
			'FILE_ATTACHED',
			FileInput( 'FILE_ATTACHED' ),
			button( 'add' ),
			''
		);
	}

	if ( empty( $value )
		|| ! file_exists( $value ) )
	{
		if ( isset( $_REQUEST['_ROSARIO_PDF'] )
			|| ! AllowEdit() )
		{
			return '';
		}

		// Add hidden FILE_ATTACHED input so it gets saved even if no other columns to save.
		return '<input type="hidden" name="values[' . $THIS_RET['ID'] . '][FILE_ATTACHED]" value="" />' .
			InputDivOnclick(
				'FILE_ATTACHED_' . $THIS_RET['ID'],
				FileInput( 'FILE_ATTACHED_' . $THIS_RET['ID'] ),
				button( 'add' ),
				''
			);
	}

	if ( ! empty( $_REQUEST['LO_save'] ) )
	{
		// Export list.
		return $value;
	}

	$file_path = $value;

	$file_name = basename( $file_path );

	$file_size = HumanFilesize( filesize( $file_path ) );

	$file = button(
		'download',
		'',
		'"' . URLEscape( $file_path ) . '" target="_blank" title="' . AttrEscape( $file_name . ' (' . $file_size . ')' ) . '"',
		'bigger'
	);

	return $file;
}

/**
 * Make Payments File Attached Input
 *
 * @since 8.3
 *
 * @param  string $value File path value.
 * @param  string $name  Column name, 'FILE_ATTACHED'.
 *
 * @return string        File Input HTML or link to download File.
 */
function _makePaymentsFileInput( $value, $column )
{
	return _makeSalariesFileInput( $value, $column );
}

/**
 * Make Incomes File Attached Input
 *
 * @since 8.4
 *
 * @param  string $value File path value.
 * @param  string $name  Column name, 'FILE_ATTACHED'.
 *
 * @return string        File Input HTML or link to download File.
 */
function _makeIncomesFileInput( $value, $column )
{
	return _makeSalariesFileInput( $value, $column );
}

/**
 * Make Payments Category Select Input
 *
 * @since 11.0
 *
 * @param  string $value  Category ID.
 * @param  string $column Column name, 'CATEGORY_ID'.
 *
 * @return string         Select Input HTML.
 */
function _makePaymentsCategory( $value, $column )
{
	global $THIS_RET;

	$id = 'new';

	$div = false;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];

		$div = true;
	}

	// Types: common, incomes, expenses
	$category_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME
		FROM accounting_categories
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND (TYPE='common' OR TYPE='expenses')
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,SHORT_NAME" );

	$options = [ '0' => _( 'N/A' ) ];

	foreach ( (array) $category_RET as $category )
	{
		$options[$category['ID']] = $category['SHORT_NAME'];
	}

	if ( empty( $value ) )
	{
		// Set N/A value to 0 to enable $div param & search list by Category.
		$value = '0';
	}

	return SelectInput(
		$value,
		'values[' . $id . '][' . $column . ']',
		'',
		$options,
		false,
		'',
		$div
	);
}

/**
 * Make Incomes Category Select Input
 *
 * @since 11.0
 *
 * @param  string $value  Category ID.
 * @param  string $column Column name, 'CATEGORY_ID'.
 *
 * @return string         Select Input HTML.
 */
function _makeIncomesCategory( $value, $column )
{
	global $THIS_RET;

	$id = 'new';

	$div = false;

	if ( ! empty( $THIS_RET['ID'] ) )
	{
		$id = $THIS_RET['ID'];

		$div = true;
	}

	// Types: common, incomes, expenses
	$category_RET = DBGet( "SELECT ID,TITLE,SHORT_NAME
		FROM accounting_categories
		WHERE SCHOOL_ID='" . UserSchool() . "'
		AND (TYPE='common' OR TYPE='incomes')
		ORDER BY SORT_ORDER IS NULL,SORT_ORDER,SHORT_NAME" );

	$options = [ '0' => _( 'N/A' ) ];

	foreach ( (array) $category_RET as $category )
	{
		$options[$category['ID']] = $category['SHORT_NAME'];
	}

	if ( empty( $value ) )
	{
		// Set N/A value to 0 to enable $div param & search list by Category.
		$value = '0';
	}

	return SelectInput(
		$value,
		'values[' . $id . '][' . $column . ']',
		'',
		$options,
		false,
		'',
		$div
	);
}

/**
 * Save Salaries File
 *
 * @since 10.4
 * @since 10.8.2 Add datetime to filename to make it harder to predict
 *
 * @param  int|string $id Salary ID or 'new'.
 *
 * @return string     File path or empty.
 */
function _saveSalariesFile( $id )
{
	global $error,
		$FileUploadsPath;

	$input = $id === 'new' ? 'FILE_ATTACHED' : 'FILE_ATTACHED_' . $id;

	if ( ! isset( $_FILES[ $input ] ) )
	{
		return '';
	}

	$file_attached = FileUpload(
		$input,
		$FileUploadsPath . UserSyear() . '/staff_' . UserStaffID() . '/',
		FileExtensionWhiteList(),
		0,
		$error,
		'',
		FileNameTimestamp( $_FILES[ $input ]['name'] )
	);

	// Fix SQL error when quote in uploaded file name.
	return DBEscapeString( $file_attached );
}

/**
 * Save Payments File
 *
 * @since 10.4
 *
 * @param  int|string $id Payment ID or 'new'.
 *
 * @return string     File path or empty.
 */
function _savePaymentsFile( $id )
{
	return _saveSalariesFile( $id );
}

/**
 * Save Incomes File
 *
 * @since 10.4
 * @since 10.8.2 Add datetime to filename to make it harder to predict
 *
 * @param  int|string $id Income ID or 'new'.
 *
 * @return string     File path or empty.
 */
function _saveIncomesFile( $id )
{
	global $error,
		$FileUploadsPath;

	$input = $id === 'new' ? 'FILE_ATTACHED' : 'FILE_ATTACHED_' . $id;

	if ( ! isset( $_FILES[ $input ] ) )
	{
		return '';
	}

	$file_attached = FileUpload(
		$input,
		$FileUploadsPath . UserSyear() . '/staff_' . User( 'STAFF_ID' ) . '/',
		FileExtensionWhiteList(),
		0,
		$error,
		'',
		FileNameTimestamp( $_FILES[ $input ]['name'] )
	);

	// Fix SQL error when quote in uploaded file name.
	return DBEscapeString( $file_attached );
}

/**
 * Save Expenses File
 *
 * @since 10.4
 *
 * @param  int|string $id Expense ID or 'new'.
 *
 * @return string     File path or empty.
 */
function _saveExpensesFile( $id )
{
	return _saveIncomesFile( $id );
}
