<?php
/**
 * Simplesonic - Denis Nossevitch 2017-03-01
 * Perform SQL queries needed for a server-side processing request involving
 * a very large table. Utilizes the helper functions of this class similar
 * to simple(). Demands primary key, whether or not it is requested as a
 * column. This allows for values with large offsets (when viewing the
 * rows 10,000 - 11,000 for example) to load just as quickly as initial rows.
 * Returns JSON encodable response to an SSP request.
 *
 *  @param  array 	$request	    Data sent to server by DataTables
 *  @param  array 	$sql_details	SQL connection details - see sql_connect()
 *  @param  string 	$table	        SQL table to query
 *  @param  string 	$primary	    Key Primary key of the table
 *  @param  array 	$columns	    Column information array
 *  @return array 			        Server-side processing response array
 */
function simplesonic ()
{

    $request = $this->params['request'];
    $sql_details = $this->params['sql_details'];
    $table = $this->params['table'];
    $primaryKey = $this->params['primaryKey'];
    $columns = $this->params['columns'];
    $selectWhere = $this->params['selectWhere'];

    $bindings = [];
    $db = self::sql_connect( $sql_details );

    // Build the SQL query string from the request
    $limit = $this->limit( $request, $columns );
    $order = $this->order( $request, $columns );
    $where = $this->filter( $request, $columns, $bindings, $selectWhere );

    // Determine primary key, requested as column or not
    $cols = $columns;
    foreach ($cols as &$c) {
        if ($c['db'] == $primaryKey) {
            $c['db'] = 't.'.$c['db'];
            $is_requested = TRUE;
            $pk = "";
        }
    }
    if (!isset($is_requested)) {
        $pk = $primaryKey;
    }

    // Main query to actually get the data
    $query = "SELECT $pk ".implode(", ", self::pluck($cols, 'db'))."
        FROM (
            SELECT $primaryKey
            FROM $table
            $where
            $order
            $limit
        ) q
        JOIN $table t
        ON t.$primaryKey = q.$primaryKey";

    $data = self::sql_exec( $db, $bindings, $query);

    // Data set length after filtering
    $resFilterLength = self::sql_exec( $db, $bindings,
        "SELECT COUNT(*) FROM ".$table." ".$where
    );
    $recordsFiltered = $resFilterLength[0][0];

    // Total data set length
    $length_query = "SELECT COUNT(`{$primaryKey}`) FROM ".$table;
    $length_query .= ($selectWhere !== FALSE) ? " WHERE ".$selectWhere : "";

    $resTotalLength = self::sql_exec( $db, $bindings, $length_query);
    $recordsTotal = $resTotalLength[0][0];


    /*
     * Output
     */
    return [
        "draw"            => intval( $request['draw'] ),
        "recordsTotal"    => intval( $recordsTotal ),
        "recordsFiltered" => intval( $recordsFiltered ),
        "data"            => self::data_output( $columns, $data )
    ];
}

?>
