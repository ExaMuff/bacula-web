<?php
/* 
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004 Juan Luis Franc�s Jim�nez				  			   |
 | Copyright 2010-2015, Davide Franco			                  		   |
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
    public $translate;                    // Translation class instance
    private $catalogs = array();        // Catalog array
        
    private $view;                        // Template class

    public $db_link;                    // Database connection
    private $db_driver;                    // Database connection driver
        
    public $catalog_nb;                // Catalog count
    public $catalog_current_id = 0;    // Selected or default catalog id

    public function __construct(&$view)
    {
     // Loading configuration file parameters
        try {
            if (!FileConfig::open(CONFIG_FILE)) {
                throw new Exception("The configuration file is missing");
            } else {
                      $this->catalog_nb = FileConfig::count_Catalogs();
            }
        } catch (Exception $e) {
            CErrorHandler::displayError($e);
        }
                
     // Template engine initalization
        $this->view = $view;
            
     // Checking template cache permissions
        if (!is_writable(VIEW_CACHE_DIR)) {
            throw new Exception("The template cache folder <b>" . VIEW_CACHE_DIR . "</b> must be writable by Apache user");
        }
                
     // Initialize smarty gettext function
        $language = FileConfig::get_Value('language');
        if (!$language) {
            throw new Exception("Language translation problem");
        }
                
        $this->translate = new CTranslation($language);
        $this->translate->set_Language($this->view);
            
     // Get catalog_id from http $_GET request
        if (!is_null(CHttpRequest::get_Value('catalog_id'))) {
            if (FileConfig::catalogExist(CHttpRequest::get_Value('catalog_id'))) {
                $this->catalog_current_id = CHttpRequest::get_Value('catalog_id');
                $_SESSION['catalog_id'] = $this->catalog_current_id;
            } else {
                $_SESSION['catalog_id']    = 0;
                $this->catalog_current_id = 0;
                throw new Exception('The catalog_id value provided does not correspond to a valid catalog, please verify the config.php file');
            }
        } else {
            if (isset($_SESSION['catalog_id'])) {
             // Stick with previously selected catalog_id
                    $this->catalog_current_id = $_SESSION['catalog_id'];
            }
        }
            
            // Define catalog id and catalog label
        $this->view->assign('catalog_current_id', $this->catalog_current_id);
            $this->view->assign('catalog_label', FileConfig::get_Value('label', $this->catalog_current_id));
            
        // Getting database connection paremeter from configuration file
        $dsn = FileConfig::get_DataSourceName($this->catalog_current_id);
        $driver = FileConfig::get_Value('db_type', $this->catalog_current_id);
        $user = '';
        $pwd = '';

        if ($driver != 'sqlite') {
            $user    = FileConfig::get_Value('login', $this->catalog_current_id);
            $pwd    = FileConfig::get_Value('password', $this->catalog_current_id);
        }

        switch($driver) {
            case 'mysql':
            case 'pgsql':
                $this->db_link = CDB::connect($dsn, $user, $pwd);
                break;
            case 'sqlite':
                $this->db_link = CDB::connect($dsn);
                break;
        }
            
     // Getting driver name from PDO connection
        $this->db_driver = CDB::getDriverName();

     // Set PDO connection options
        $this->db_link->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        $this->db_link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db_link->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('CDBResult', array($this)));
            
     // MySQL connection specific parameter
        if ($driver == 'mysql') {
            $this->db_link->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }

     // Bacula catalog selection
        if ($this->catalog_nb > 1) {
         // Catalogs list
            $this->view->assign('catalogs', FileConfig::get_Catalogs());
         // Catalogs count
            $this->view->assign('catalog_nb', $this->catalog_nb);
        }
    }
                    
        // ==================================================================================
        // Function: 	GetVolumeList()
        // Parameters: 	none
        // Return:	array of volumes ordered by poolid and volume name
        // ==================================================================================
        
    public function GetVolumeList()
    {
        $volumes      = '';
        $volumes_list = array();
        $query        = "";
//MKO Aggiunto per calcolo TTL
        $time         = time();
        $dtF          = new DateTime("@0");
//END MKO
                
        foreach (Pools_Model::getPools($this->db_link) as $pool) {
            switch($this->db_driver)
            {
                case 'sqlite':
                case 'mysql':
//MKO Aggiunto per firstwritten per calcolo TTL
                    $query  = "SELECT Media.voluseduration, Media.inchanger, Media.slot, Media.firstwritten, Media.volumename, Media.volbytes, Media.volstatus, Media.mediatype, Media.lastwritten, Media.volretention
									FROM Media LEFT JOIN Pool ON Media.poolid = Pool.poolid
									WHERE Media.poolid = '". $pool['poolid'] . "' ORDER BY Media.volumename";
                    break;
                case 'pgsql':
                    $query  = "SELECT media.voluseduration, media.inchanger, media.slot, media.firstwritten, media.volumename, media.volbytes, media.volstatus, media.mediatype, media.lastwritten, media.volretention
									FROM media LEFT JOIN pool ON media.poolid = pool.poolid
									WHERE media.poolid = '". $pool['poolid'] . "' ORDER BY media.volumename";
//END MKO
                    break;
            } // end switch
                    
            $volumes  = CDBUtils::runQuery($query, $this->db_link);
                
            if (!array_key_exists($pool['name'], $volumes_list)) {
                $volumes_list[ $pool['name'] ] = array();
            }
                    
            foreach ($volumes->fetchAll() as $volume) {
                if ($volume['lastwritten'] != "0000-00-00 00:00:00") {
                 // Calculate expiration date if the volume is Full
                    if ($volume['volstatus'] == 'Full') {
                        $expire_date     = strtotime($volume['lastwritten']) + $volume['volretention'];
                        $volume['expire'] = strftime("%Y-%m-%d", $expire_date);
                    } else {
                        $volume['expire'] = 'N/A';
                    }
                  //MKO Calcolo Append Life
                    $timediff = $time - strtotime($volume['firstwritten']);
                    if ($volume['volstatus'] == 'Append') {
                        $remaining = $volume['voluseduration'] - $timediff;
                        $dtT = new DateTime("@$remaining");
                        $volume['volstatus'] = $volume['volstatus'] . ' (' . $dtF->diff($dtT)->format('%ad %Hh %im %ss') . ')';
                    }
                 //MKO Calcolo TTL
                    $ttl = $volume['volretention'] - $timediff;
                    $dtT = new DateTime("@$ttl");
                    ($ttl>0) ? $volume['ttl'] = $dtF->diff($dtT)->format('%ad %Hh %im %ss') : $volume['ttl'] = 'Expired';
                 //MKO In Changer
                    ($volume['inchanger']) ? $volume['changer'] = 'Slot: ' . $volume['slot'] : $volume['changer'] = 'No';

                 // Media used bytes in a human format
                    $volume['volbytes'] = CUtils::Get_Human_Size($volume['volbytes']);
                } else {
                    $volume['lastwritten'] = "N/A";
                    $volume['expire']      = "N/A";
                    $volume['volbytes']       = "0 KB";
                }
                $volume['volretention'] = $volume['volretention'] / 60 / 60 / 24 . 'd'; 
             // Add the media in pool array
                array_push($volumes_list[ $pool['name']], $volume);
            } // end foreach volumes
        } // end foreach pools
                
        return $volumes_list;
    } // end function GetVolumeList()
} // end class Bweb
