<?php
 /*
  +-------------------------------------------------------------------------+
  | Copyright 2010-2014, Davide Franco			                            |
  |                                                                         |
  | This program is free software; you can redistribute it and/or           |
  | modify it under the terms of the GNU General Public License             |
  | as published by the Free Software Foundation; either version 2          |
  | of the License, or (at your option) any later version.                  |
  |                                                                         |
  | This program is distributed in the hope that it will be useful,         |
  | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
  | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
  | GNU General Public License for more details.                            |
  +-------------------------------------------------------------------------+
 */

 class Database_Model extends Model {
 
 	// ==================================================================================
	// Function: 	get_Size()
	// Parameters:	$pdo_connection - valid PDO object instance
	// Return:		Database size
	// ==================================================================================
	
	public static function get_Size( $pdo_connection, $catalog_id ) {
		$db_name	= FileConfig::get_Value( 'db_name', $catalog_id);
		$statment 	= null;
		$result	 	= null;
		
		switch( CDB::getDriverName() )
		{
			case 'mysql':
				// Return N/A for MySQL server prior version 5 (no information_schemas)
				if( version_compare( CDB::getServerVersion(), '5.0.0') >= 0 ) {
					// Prepare SQL statment
					$statment = array( 'table'   => 'information_schema.TABLES', 
									   'fields'  => array("table_schema AS 'database', sum( data_length + index_length) AS 'dbsize'"),
									   'where'   => array( "table_schema = '$db_name'" ),
									   'groupby' => 'table_schema' );
					$statment 	= CDBQuery::get_Select($statment, $pdo_connection);
				}else
					echo 'dbsize() unsupported ('.CDB::getServerVersion().') <br />';
			break;
			case 'pgsql':
				$statment	= "SELECT pg_database_size('$db_name') AS dbsize";
			break;
			case 'sqlite':
				$db_size 	= filesize( FileConfig::get_Value( 'db_name', $catalog_id) );
				return CUtils::Get_Human_Size($db_size);
			break;
		}
		// Execute SQL statment
		$result   = CDBUtils::runQuery($statment, $pdo_connection);
		$db_size  = $result->fetch();
		
		return CUtils::Get_Human_Size( $db_size['dbsize'] );	
	}
 }
