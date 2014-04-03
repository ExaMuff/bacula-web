<?php

/*
  +-------------------------------------------------------------------------+
  | Copyright 2010-2014, Davide Franco                                              |
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

class RouterController {
    private $context = null;
    private $default_context;

    public function __construct($default_context) {
        $this->default_context = $default_context;

        if (isset( $_GET['page']))
            $this->context = $_GET['page'];
        else {
            $this->context = $this->default_context;
        }
    }

    private function getContext() {
        return $this->context;
    }

    public function getController() {   
        $classname = ucfirst($this->context) . '_Controller';

        if( !class_exists($classname) )
            throw new Exception( "Controller class ($classname) do not exist" );
 
        return $classname;
    }

    public function getView() {
        $classname = ucfirst($this->context) . '_View';

        if( !class_exists($classname) )
            throw new Exception( "View class ($classname) do not exist" );

        return $classname;
    }

    public function getModel() {
        $classname = ucfirst($this->context) . '_Model';

        if( !class_exists($classname) )
            throw new Exception( "Model class ($classname) do not exist" );

        return $classname;
    }
}
?>

