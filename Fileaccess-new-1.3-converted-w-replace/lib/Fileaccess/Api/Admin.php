<?php
/**
* Fileaccess Module
*
* The Fileaccess module displays and allows for management of
* a file and folder heirarchy on the host computer.
* @author       Craig Nelson <craig@sagestudio.com>
* @link         http://www.sagestudio.com
* @copyright    Copyright (C) 2004-2013 by SageStudio
* @license      http://www.gnu.org/copyleft/gpl.html GNU General Public License
*/
// ----------------------------------------------------------------------
// Based on code from:
// PHP-NUKE Web Portal System - http://phpnuke.org/
// POSTNUKE
// Zikula open Application Framework - http://zikula.org/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
class Filaccess_Api_Admin extends Zikula_AbstractApi
{
    // get available admin panel links
    public function getlinks()
    {
        $links = array();
        $links[] = array('url' => ModUtil::url('Fileaccess', 'user', 'main'), 'text' => $this->__('Manage Files'), 'class' => 'z-icon-es-list');
        $links[] = array('url' => ModUtil::url('Fileaccess', 'admin', 'main'), 'text' => $this->__('Module configuration'), 'class' => 'z-icon-es-config');
        // return output
        return $links;
    }
}
 

?>