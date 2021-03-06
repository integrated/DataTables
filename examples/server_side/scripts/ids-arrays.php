<?php

/*
 * DataTables example server-side processing script.
 *
 * Please note that this script is intentionally extremely simply to show how
 * server-side processing can be implemented, and probably shouldn't be used as
 * the basis for a large complex system. It is suitable for simple use cases as
 * for learning.
 *
 * See http://datatables.net/usage/server-side for full details on the server-
 * side processing requirements of DataTables.
 *
 * @license MIT - http://datatables.net/license_mit
 */

/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Easy set variables
 */

// DB table to use
$table = 'datatables-demo';

// Table's primary key
$primaryKey = 'id';

// Array of database columns which should be read and sent back to DataTables.
// The `db` parameter represents the column name in the database, while the `dt`
// parameter represents the DataTables column identifier. In this case simple
// indexes + the primary key column for the id
$columns = array(
	array( 'db' => 'id',         'dt' => 'DT_RowId' ),
	array( 'db' => 'first_name', 'dt' => 0 ),
	array( 'db' => 'last_name',  'dt' => 1 ),
	array( 'db' => 'position',   'dt' => 2 ),
	array( 'db' => 'office',     'dt' => 3 ),
	array( 'db' => 'start_date', 'dt' => 4 ),
	array( 'db' => 'salary',     'dt' => 5 )
);

// SQL server connection information
$sql_details = array(
	'user' => '',
	'pass' => '',
	'db'   => '',
	'host' => ''
);


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * If you just want to use the basic configuration for DataTables with PHP
 * server-side, there is no need to edit below this line.
 *
 * It should be noted that this script could be made far more modular
 */

// REMOVE THIS BLOCK - used for DataTables test environment only!
$file = $_SERVER['DOCUMENT_ROOT'].'/datatables/mysql.php';
if ( is_file( $file ) ) {
	include( $file );
}

require( 'ssp.class.php' );
$bindings = array();
$db = SSP::sql_connect( $sql_details );

// Main query to actually get the data
$limit = SSP::limit( $_GET, $columns );
$order = SSP::order( $_GET, $columns );
$where = SSP::filter( $_GET, $columns, $bindings );

// Main query to actually get the data
$data = SSP::sql_exec( $db, $bindings,
	"SELECT SQL_CALC_FOUND_ROWS `".implode("`, `", SSP::pluck($columns, 'db'))."`
	 FROM `$table`
	 $where
	 $order
	 $limit"
);

// Data set length after filtering
$resFilterLength = SSP::sql_exec( $db,
	"SELECT FOUND_ROWS()"
);
$recordsFiltered = $resFilterLength[0][0];

// Total data set length
$resTotalLength = SSP::sql_exec( $db,
	"SELECT COUNT(`{$primaryKey}`)
	 FROM   `$table`"
);
$recordsTotal = $resTotalLength[0][0];


/*
 * Output
 */
$output = array(
	"draw"            => intval( $_GET['draw'] ),
	"recordsTotal"    => intval( $recordsTotal ),
	"recordsFiltered" => intval( $recordsFiltered ),
	"data" => array()
);

for ( $i=0, $ien=count($data) ; $i<$ien ; $i++ ) {
	$row = array();

	for ( $j=0, $jen=count($columns) ; $j<$jen ; $j++ ) {
		$column = $columns[$j];

		// Formatting of data for specific columns
		switch ( $columns[$j]['db'] ) {
			case 'salary':
				$row[ $column['dt'] ] = '$'.number_format($data[$i]['salary']);
				break;

			case 'start_date':
				$row[ $column['dt'] ] = date( 'jS M y', strtotime($data[$i]['start_date']));
				break;

			default:
				$row[ $column['dt'] ] = $data[$i][ $columns[$j]['db'] ];
				break;
		}
	}

	$output['data'][] = $row;
}

echo json_encode( $output );

