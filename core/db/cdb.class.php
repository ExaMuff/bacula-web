<?php

/*
  +-------------------------------------------------------------------------+
  | Copyright 2010-2014, Davide Franco			                          |
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

class DatabaseAdapter {

    private $link;

    private $dsn;
    private $username
    private $password;
    
    private $options;

    // ==================================================================================
    // Function: 	__construct()
    // Parameters: 	none
    // Return:		none
    // ==================================================================================
    
    private function __construct( $connection = array() ) {
        $this->options  = $connection['options'];
        $this->dsn      = $connection['dsn'];

        if( $connection['driver'] != 'sqlite' ) {
          $this->username = $connection['username'];
          $this->password = $connection['password'];
          $this->connection = new PDO{$this->dsn, $this->username, $this->password}
        }else {
            $this->connection = new PDO($this->dsn)
        }
    }

    // ==================================================================================
    // Function: 	connect()
    // Parameters: 	none
    // Return:		valid PDO connection
    // ==================================================================================
    
    public static function connect( $dsn, $user = null, $password = null ) {

        try {
            if ( is_null( self::$connection ) ) {
                self::$connection = new PDO($dsn, $user, $password);
            }
        }catch (PDOException $e) {
            CErrorHandler::displayError($e);
        }
        
        return self::$connection;
    }

    // ==================================================================================
    // Function: 	getDriverName()
    // Parameters: 	none
    // Return:		driver name (eg: mysql, pgsql, sqlite, etc.)
    // ==================================================================================
    
    public static function getDriverName() {
        return self::$connection->getAttribute( PDO::ATTR_DRIVER_NAME );
    }

    // ==================================================================================
    // Function: 	getServerVersion()
    // Parameters: 	none
    // Return:		database server version
    // ==================================================================================
    
    public static function getServerVersion() {
        $server_version = self::$connection->getAttribute( PDO::ATTR_SERVER_VERSION );
        $server_version = explode(':', $server_version);
        return $server_version[0];
    }
}
?>
