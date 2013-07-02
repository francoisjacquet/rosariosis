<?php
$menu['Food_Service']['admin'] = array(
						'Food_Service/Accounts.php'=>_('Accounts'),
						'Food_Service/Statements.php'=>_('Statements'),
						'Food_Service/Transactions.php'=>_('Transactions'),
						'Food_Service/ServeMenus.php'=>_('Serve Meals'),
						1=>_('Reports'),
						'Food_Service/ActivityReport.php'=>_('Activity Report'),
						'Food_Service/TransactionsReport.php'=>_('Transactions Report'),
						'Food_Service/MenuReports.php'=>_('Meal Reports'),
//                        'Food_Service/BalanceReport.php'=>_('Balance Report'),
						'Food_Service/Reminders.php'=>_('Reminders'),
						2=>_('Setup'),
						'Food_Service/DailyMenus.php'=>_('Daily Menus'),
						'Food_Service/MenuItems.php'=>_('Meal Items'),
						'Food_Service/Menus.php'=>_('Meals'),
						'Food_Service/Kiosk.php'=>_('Kiosk Preview')
//						3=>'Utilities',
//						'Food_Service/AssignSchool.php'=>'Assign School'
					);

$menu['Food_Service']['teacher'] = array(
						'Food_Service/Accounts.php'=>_('Accounts'),
						'Food_Service/Statements.php'=>_('Statements'),
						1=>_('Setup'),
						'Food_Service/DailyMenus.php'=>_('Daily Menus'),
						'Food_Service/MenuItems.php'=>_('Meal Items')
					);

$menu['Food_Service']['parent'] = array(
						'Food_Service/Accounts.php'=>_('Accounts'),
						'Food_Service/Statements.php'=>_('Statements'),
						1=>_('Setup'),
						'Food_Service/DailyMenus.php'=>_('Daily Menus'),
						'Food_Service/MenuItems.php'=>_('Meal Items')
					);

$exceptions['Food_Service'] = array(
						'Food_Service/ServeMenus.php'=>true
					);
?>
