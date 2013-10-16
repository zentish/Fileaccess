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

 
/**
 * Smarty function to display admin links for the Fileaccess module
 * based on the user's permissions
 * 
 * Fileaccess
 * <!--[Fileaccessadminlinks start="[" end="]" seperator="|" class="z-menuitem-title"]-->
 * 
 * @author       Andreas Krapohl
 * @since        10/01/04
 * @see          function.Fileaccessadminlinks.php::smarty_function_Fileaccessadminlinks()
 * @param        array       $params      All attributes passed to this function from the template
 * @param        object      &$smarty     Reference to the Smarty object
 * @param        string      $start       start string
 * @param        string      $end         end string
 * @param        string      $seperator   link seperator
 * @param        string      $class       CSS class
 * @return       string      the results of the module function
 */
function smarty_function_Fileaccessadminlinks($params, &$smarty) 
{
    extract($params); 
	unset($params);
    
	// set some defaults
	if (!isset($start)) {
		$start = '[';
	}
	if (!isset($end)) {
		$end = ']';
	}
	if (!isset($seperator)) {
		$seperator = '|';
	}
  if (!isset($class)) {
    $class = '.z-menuitem-title';
	}

    $adminlinks = "<span class=\"$class\">$start ";
	
    if (SecurityUtil::checkPermission('Fileaccess::', '::', ACCESS_READ)) {
		$adminlinks .= "<a href=\"" . DataUtil::formatForDisplayHTML(ModUtil::url('Fileaccess', 'user', 'displaylist')) . "\">View Fileaccess Files</a> ";
    }
//    if (SecurityUtil::checkPermission( 'Fileaccess::', '::', ACCESS_ADD)) {
//		$adminlinks .= "$seperator <a href=\"" . DataUtil::formatForDisplayHTML(ModUtil::url('Fileaccess', 'admin', 'new')) . "\">" . _NEW . "</a> ";
//    }
    if (SecurityUtil::checkPermission('Fileaccess::', '::', ACCESS_ADMIN)) {
		$adminlinks .= "$seperator <a href=\"" . DataUtil::formatForDisplayHTML(ModUtil::url('Fileaccess', 'admin', 'view')) . "\">Modify Fileaccess Configuration</a> ";
    }

	$adminlinks .= "$end</span>\n";

    return $adminlinks;
}

?>