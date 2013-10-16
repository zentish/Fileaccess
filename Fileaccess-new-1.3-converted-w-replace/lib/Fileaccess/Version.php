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

class Fileaccess_Version extends Zikula_AbstractVersion
{

	/**
	* Assemble and return module metadata.
	*
	* @return array Module metadata.
	*/
	public function getMetaData()
	{
		return array(
		// Be careful about version numbers. version_compare() is used to handle special situations.
		// 0.9 < 0.9.0 < 1 < 1.0 < 1.0.1 < 1.2 < 1.18 < 1.20 < 2.0 < 2.0.0 < 2.0.1
		// From this version forward, please use the major.minor.point format below.
		'version'       => '1.6.1',
		'displayname'   => $this->__('Fileaccess'),
		'description'   => $this->__('Fileaccess module allows the mapping of a filesystem hive, outside of the web site, into a typical explorer tree display'),
		'url'           => $this->__('fileaccess'),
		'core_min'=> '1.3.2',
		'core_max'=>'1.3.99',
		// Security Schema
		'securityschema'=> array('Fileaccess::' => 'folder::',
		'Fileaccess::' => 'folder::subfolder',
		'Fileaccess::' => 'folder::sub-folder::sub-sub-folder',
		),
		);
	}


}
?>