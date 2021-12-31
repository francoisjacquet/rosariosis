<?php
$sql = "SELECT a.attnum,a.attname AS field,t.typname AS type,
					a.attlen AS length,a.atttypmod AS lengthvar,
					a.attnotnull AS notnull,c.relname
				FROM pg_class c, pg_attribute a, pg_type t
				WHERE
					a.attnum > 0 and a.attrelid = c.oid
					and c.relkind='r' and c.relname not like 'pg\_%' and a.attname not like '...%'
					and a.atttypid = t.oid ORDER BY c.relname";
$RET = DBGet( $sql, [], [ 'RELNAME' ] );

$PDF = PDFStart();
echo '<table>';

foreach ( (array) $RET as $table => $columns )
{
	if ( $i % 2 == 0 )
	{
		echo '<tr><td class="valign-top">';
	}

	echo '<b>' . $table . '</b>';
	echo '<table>';

	foreach ( (array) $columns as $column )
	{
		echo '<tr><td style="width:15px;">&nbsp; &nbsp; </td><td>' . $column['FIELD'] . '</td><td>' . $column['TYPE'] . '</td></tr>';
	}

	echo '</table>';

	if ( $i % 2 == 0 )
	{
		echo '</td><td class="valign-top">';
	}
	else
	{
		echo '</td></tr>';
	}

	$i++;
}

echo '</table>';
PDFStop( $PDF );
