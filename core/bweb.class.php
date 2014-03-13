<?php
/* 
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004 Juan Luis Franc�s Jim�nez				  			   |
 | Copyright 2010-2014, Davide Franco			                  		   |
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

	require_once( 'core/global.inc.php' );

	class Bweb
	{
		public $translate;		// Translation class instance
		private $catalogs = array();	// Catalog array
		
		private $view;			// Template class

		public  $db_link;		// Database connection
		private $db_driver;		// Database connection driver
		
		public  $catalog_nb;		// Catalog count
		public	$catalog_current_id;	// Current catalog

		function __construct( &$view )
		{             
			// Template engine initalization
			$this->view = $view;
			
			// Initialize smarty gettext function
			$language = FileConfig::get_Value('language');
				
			$this->translate = new CTranslation( $language );
			$this->translate->set_Language( $this->view );
			
			// Hey !!! Code below is the job of the controller ...
			// Check catalog id
			if( !is_null(CHttpRequest::get_Value('catalog_id') ) ) {
				$this->catalog_current_id = CHttpRequest::get_Value('catalog_id');
				$_SESSION['catalog_id'] = $this->catalog_current_id;
			}elseif( isset( $_SESSION['catalog_id'] ) )
				$this->catalog_current_id = $_SESSION['catalog_id'];
			else {
				$this->catalog_current_id = 0;
				$_SESSION['catalog_id'] = $this->catalog_current_id;
			}

			$this->view->assign( 'catalog_current_id', $this->catalog_current_id );
			
            // Establish database connection
            // Getting driver name from PDO connection
			$this->db_driver = FileConfig::get_Value( 'db_type', $this->catalog_current_id);
            
            $db_config = array();
            $db_config['dsn']    = FileConfig::get_DataSourceName( $this->catalog_current_id );
            $db_config['driver'] = $this->db_driver;
            
            // Set database options
            $db_options = array( PDO::ATTR_CASE => PDO::CASE_LOWER,
                                 PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                                 PDO::ATTR_STATEMENT_CLASS => array('CDBResult', array($this)) );

            // MySQL connection specific parameter
            if ($this->db_driver == 'mysql')
                $db_options[] = array(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true);

            // Define username and password for MySQL and postgreSQL
            if( FileConfig::get_Value( 'db_type', $this->catalog_current_id) != 'sqlite' ) {
                $db_config['username'] = FileConfig::get_Value( 'login', $this->catalog_current_id);
                $db_config['password'] = FileConfig::get_Value( 'password', $this->catalog_current_id);
            }

            // Create PDO object
            $this->db_link = new DatabaseAdapter( $db_config, $db_options );

			// Bacula catalog selection		
			if( $this->catalog_nb > 1 ) {
				// Catalogs list
				$this->view->assign('catalogs', FileConfig::get_Catalogs() );
				// Catalogs count
				$this->view->assign('catalog_nb', $this->catalog_nb );
			}
		}
					
		// ==================================================================================
		// Function: 	GetVolumeList()
		// Parameters: 	none
		// Return:		array of volumes ordered by poolid and volume name
		// ==================================================================================
		
		public function GetVolumeList() 
		{
				$pools        = '';
				$volumes      = '';
				$volumes_list = array();
				$query        = "";
				$debug	      = false;
				
				foreach( Pools_Model::getPools( $this->db_link ) as $pool ) {
					switch( $this->db_driver )
					{
						case 'sqlite':
						case 'mysql':
							$query  = "SELECT Media.volumename, Media.volbytes, Media.volstatus, Media.mediatype, Media.lastwritten, Media.volretention
									FROM Media LEFT JOIN Pool ON Media.poolid = Pool.poolid
									WHERE Media.poolid = '". $pool['poolid'] . "' ORDER BY Media.volumename";
						break;
						case 'pgsql':
							$query  = "SELECT media.volumename, media.volbytes, media.volstatus, media.mediatype, media.lastwritten, media.volretention
									FROM media LEFT JOIN pool ON media.poolid = pool.poolid
									WHERE media.poolid = '". $pool['poolid'] . "' ORDER BY media.volumename";
						break;
					} // end switch
					
					//$volumes = $this->db_link->runQuery($query);
					$volumes  = CDBUtils::runQuery( $query, $this->db_link );
				
					if( !array_key_exists( $pool['name'], $volumes_list) )
						$volumes_list[ $pool['name'] ] = array();
					
					foreach( $volumes->fetchAll() as $volume ) {
						if( $volume['lastwritten'] != "0000-00-00 00:00:00" ) {
							
							// Calculate expiration date if the volume is Full
							if( $volume['volstatus'] == 'Full' ) {
								$expire_date     = strtotime($volume['lastwritten']) + $volume['volretention'];
								$volume['expire'] = strftime("%Y-%m-%d", $expire_date);
							}else {
								$volume['expire'] = 'N/A';
							}
							
							// Media used bytes in a human format
							$volume['volbytes'] = CUtils::Get_Human_Size( $volume['volbytes'] );
						} else {
							$volume['lastwritten'] = "N/A";
							$volume['expire']      = "N/A";
							$volume['volbytes'] 	  = "0 KB";
						}
						
						// Odd or even row
						if( count(  $volumes_list[ $pool['name'] ] ) % 2)
							$volume['odd_even'] = 'even';

						// Add the media in pool array
						array_push( $volumes_list[ $pool['name']], $volume);
					} // end foreach volumes
				} // end foreach pools
				
				return $volumes_list;
		} // end function GetVolumeList()
		
} // end class Bweb
?>
