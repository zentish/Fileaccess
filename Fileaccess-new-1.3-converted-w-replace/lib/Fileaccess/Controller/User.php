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


class Fileaccess_Controller_User extends Zikula_AbstractController
{
	
	public function postInitialize() {
		$this->view->setCaching(false);
	}

	public function main($args)
	{
		$debugging = FormUtil::getPassedValue('debugging');
		if ($debugging=="yes" ) {
			SessionUtil::setVar('FileaccessDebugging',$debugging);
		} else if ( $debugging=="no") {
			SessionUtil::setVar('FileaccessDebugging',"");
			$debugging="";
		} else {
			$debugging=SessionUtil::getVar('FileaccessDebugging');
		}
		$this->view->caching=false;

		if (!SecurityUtil::checkPermission( 'Fileaccess::', '::', ACCESS_OVERVIEW)) {
			return DataUtil::formatForDisplayHTML(_MODULENOAUTH." in main");
		}

		return $this->displaylist($args);

	}

	public function displaylist($args)
	{
		$debugging = FormUtil::getPassedValue('debugging');
		if ($debugging=="yes" ) {
			SessionUtil::setVar('FileaccessDebugging',$debugging);
		} else if ( $debugging=="no") {
			SessionUtil::setVar('FileaccessDebugging',"");
			$debugging="";
		} else {
			$debugging=SessionUtil::getVar('FileaccessDebugging');
		}
		$this->view->caching=false;

		// Security check - important to do this as early as possible to avoid
		// potential security holes or just too much wasted processing
		if (!SecurityUtil::checkPermission( 'Fileaccess::', '::', ACCESS_OVERVIEW)) {
			return DataUtil::formatForDisplayHTML(_MODULENOAUTH);
		}

		// Get parameters from whatever input we need.
		$file = FormUtil::getPassedValue('file');
		$sortby= FormUtil::getPassedValue('sortby');
		if ($sortby=="nametoggle") {
			ModUtil::apiFunc('Fileaccess','user','sortby_name_toggle');
		}

		extract($args);
		
		//disable debugging for these calls
		$olddebugging=SessionUtil::getVar('FileaccessDebugging');
		SessionUtil::setVar('FileaccessDebugging',"");
		//get the list of files
		$items = ModUtil::apiFunc('Fileaccess','user','getall',
		array('sortbyname'=>SessionUtil::getVar('FileaccessSortbyName'))
		);
		SessionUtil::setVar('FileaccessDebugging',$olddebugging);

		// The return value of the function is checked here, and if the function
		// failed then an appropriate message is posted.
		if (!$items) {
			return DataUtil::formatForDisplayHTML('Sorry! Failed to retrieve any items'."<br />".SessionUtil::getVar('FileaccessDebugMsg')."<br />".SessionUtil::getVar('FileaccessErrorMsg'));
		}

		$Fileaccessitems = array();
		$files=array();
		foreach ($items as $item) {
			//if (Fileaccess_userapi_hack_SecurityUtil_checkPermission(array('file'=>"$entity",'access'=>ACCESS_READ)) ) {
			if ( ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
			array('file'=>"$item[vfilename]",'access'=>ACCESS_READ)) ) {
				//set the icon to use
				if( $item['opendir'] ) {
					$item['icon']=ModUtil::getVar('Fileaccess','iconfolderopen');
				} elseif( $item['type']=='dir' ) {
					$item['icon']=ModUtil::getVar('Fileaccess','iconfolder');
				} else {
					$item['icon']=ModUtil::getVar('Fileaccess','iconfile');
				}
				//determine the access level for this entry ACCESS_ADMIN ACCESS_DELETE ACCESS_ADD
				if ( ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
				array('file'=>"$item[vfilename]",'access'=>ACCESS_DELETE))
				&& $item['type']=='file'
				&& $item['basename'] != "/" ) {
					//only admins can delete directories
					$item['delete']=1;
				}
				if ( ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
				array('file'=>"$item[vfilename]",'access'=>ACCESS_ADMIN))
				&& $item['type']=='dir' ) {
					//admins can delete anything
					if( $item['basename'] != "/" ) {
						//no one can delete the root
						$item['delete']=1;
					}
					$item['admin']=1;
					$item['mkdir']=1;
				}
				if ( ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
				array('file'=>"$item[vfilename]",'access'=>ACCESS_ADD))
				&& $item['type']=='dir' ) {
					//uploads are only available for folders anyway
					$item['add']=1;
				}
				$files[]=$item;
			}
		}

		//set the dynamic portions of the forms up
		$this->view->assign('indentchar',"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
		$this->view->assign('items',$files);
		$this->view->assign('bold',ModUtil::getVar('Fileaccess','bold'));
		$this->view->assign('itemsperbold',ModUtil::getVar('Fileaccess','itemsperbold'));
		//save the main list of files to the array of display items
		$Fileaccessitems[] = $this->view->fetch('Fileaccess_user_list.htm');

		//Add the admin form if perms are right
		if (SecurityUtil::checkPermission( 'Fileaccess::', '::', ACCESS_ADMIN)) {
			$Fileaccessitems[] = $this->view->fetch('Fileaccess_user_admin_form.htm');
		}

		// change if you want to see this dump
		if ($debugging && 0) {
			$Fileaccessitems[] = "<pre>".SessionUtil::getVar('FileaccessDebugMsg')."\n".SessionUtil::getVar('FileaccessErrorMsg')."</pre>";
			$Fileaccessitems[] = "<br />ITEMS:<br /><pre>".$this->var_dump_ret($items)."</pre>";
			$Fileaccessitems[] = "<br />FILES:<br /><pre>".$this->var_dump_ret($files)."</pre>";
		}

		// The items that are displayed on this overview page depend on the individual
		// user permissions. Therefor, we can not cache the whole page.
		// The single entries are cached, though.
		$this->view->caching=false;

		// Display the entries
		$this->view->assign('items', $Fileaccessitems);
		$this->view->assign('statusmsg', SessionUtil::getVar('FileaccessStatusMsg'));
		$this->view->assign('errormsg', SessionUtil::getVar('FileaccessErrorMsg'));


		//clear the session display variables for use on the next round
		SessionUtil::setVar('FileaccessStatusMsg','');
		SessionUtil::setVar('FileaccessErrorMsg','');
		SessionUtil::setVar('FileaccessDebugMsg','');
		// Return the output that has been generated by this function
		return $this->view->fetch('Fileaccess_user_view.htm');
	}

	public function display($args)
	{
		$debugging = FormUtil::getPassedValue('debugging');
		if ($debugging=="yes" ) {
			SessionUtil::setVar('FileaccessDebugging',$debugging);
		} else if ( $debugging=="no") {
			SessionUtil::setVar('FileaccessDebugging',"");
			$debugging="";
		} else {
			$debugging=SessionUtil::getVar('FileaccessDebugging');
		}
		
    //$csrftoken = FormUtil::getPassedValue('csrftoken');
    //$this->checkCsrfToken($csrftoken);
		
		$this->view->caching=false;
		$file = FormUtil::getPassedValue('file');
		extract($args);
		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"display invoked, file=$file"));

		if (empty($file)) {
			$file = $objectid;
		}

		//check for pollution of the passed variable
		$fullfilename=str_replace("//","/",ModUtil::getVar('Fileaccess','rootdir')."/".$file);
		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"display invoked fullfilename=$fullfilename"));
		if ( ModUtil::apiFunc('Fileaccess','user','taintsafe',array('string'=>$file))
		&& file_exists($fullfilename) ) {
			//check permissions
			if (ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',array('file'=>"$file",'access'=>ACCESS_READ))) {
				//now is it a file or a folder
				if (filetype($fullfilename)=="file") {
					//if a file is requested then just send it on down
					$this->get_file(array('file'=>$file));
				} elseif (filetype($fullfilename)=="dir") {
					//if a folder is requested then toggle the opendirs session variable
					//and redisplay this page
					if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"display invoked $fullfilename is a folder - toggling opendirs"));
					ModUtil::apiFunc('Fileaccess','user','add_opendir',array('newdir'=>$file));
				}
			} else {
				ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"Unauthorized request for '$file'"));
				//SessionUtil::setVar('statusmsg', DataUtil::formatForDisplayHTML("Unauthorized request for $file"));
				//write_log("download for $entity failed");
			}
		} else {
			ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"Unauthorized request for '$file'"));
		}
		$this->view->caching=false;

		return $this->displaylist($args);
	}

	/**
	* view log
	*
	*/
	public function viewlog($args)
	{
		$debugging = FormUtil::getPassedValue('debugging');
		if ($debugging=="yes" ) {
			SessionUtil::setVar('FileaccessDebugging',$debugging);
		} else if ( $debugging=="no") {
			SessionUtil::setVar('FileaccessDebugging',"");
			$debugging="";
		} else {
			$debugging=SessionUtil::getVar('FileaccessDebugging');
		}
		
//		$csrftoken = FormUtil::getPassedValue('csrftoken');
//		$this->checkCsrfToken($csrftoken);
		
		//$view->caching=false;
		extract($args);
		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"viewlog invoked"));

		// Create output object - this object will store all of our output so that
		// we can return it easily when required
		//$view =& new view('Fileaccess');

		$fullfilename=str_replace("//","/",ModUtil::getVar('Fileaccess','rootdir')."/.logfile.txt");
		if ( file_exists($fullfilename) ) {
			//check permissions
			if (SecurityUtil::checkPermission( 'Fileaccess::', '::', ACCESS_ADMIN)) {
				if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"display invoked for $fullfilename"));
				$this->get_file(array('file'=>".logfile.txt",'mimetype'=>"text/plain",'attachment'=>"none"));
			} else {
				ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"Unauthorized viewlog request"));
				//SessionUtil::setVar('statusmsg', DataUtil::formatForDisplayHTML("Unauthorized request for $file"));
				//write_log("download for $entity failed");
			}
		}
		//exit;
		return true;
	}

	/**
	* delete the log
	*
	*/
	public function deletelog($args)
	{
		extract($args);
		$debugging = FormUtil::getPassedValue('debugging');
		if ($debugging=="yes" ) {
			SessionUtil::setVar('FileaccessDebugging',$debugging);
		} else if ( $debugging=="no") {
			SessionUtil::setVar('FileaccessDebugging',"");
			$debugging="";
		} else {
			$debugging=SessionUtil::getVar('FileaccessDebugging');
		}

    $csrftoken = FormUtil::getPassedValue('csrftoken');
    $this->checkCsrfToken($csrftoken);

		$this->view->caching=false;
		//$file = FormUtil::getPassedValue('file');
		//$confirm = FormUtil::getPassedValue('confirm');
		//$debugging=1;

		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"deletelog invoked"));

		$fullfilename=str_replace("//","/",ModUtil::getVar('Fileaccess','rootdir')."/.logfile.txt");
		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"delete invoked fullfilename=$fullfilename"));
		if (SecurityUtil::checkPermission( 'Fileaccess::', '::', ACCESS_ADMIN)) {
			//now we delete the log file
			$rc=unlink($fullfilename);
			ModUtil::apiFunc('Fileaccess','user','status_message_add',array('message'=>"log file deleted"));
			ModUtil::apiFunc('Fileaccess','user','write_log',array('message'=>"deleted log file"));
		}
		return $this->displaylist($args);
	}


	/**
	* the mkdir function makes a subfolder in the parent folder
	* if the newfolder variable isn't passed then it displays a form to be filled in
	* the form the re-invokes this function
	*
	*/
	public function mkdir($args)
	{
		$debugging = FormUtil::getPassedValue('debugging');
		if ($debugging=="yes" ) {
			SessionUtil::setVar('FileaccessDebugging',$debugging);
		} else if ( $debugging=="no") {
			SessionUtil::setVar('FileaccessDebugging',"");
			$debugging="";
		} else {
			$debugging=SessionUtil::getVar('FileaccessDebugging');
		}
		
    $csrftoken = FormUtil::getPassedValue('csrftoken');
    $this->checkCsrfToken($csrftoken);
		
		$this->view->caching=false;
		$parent = FormUtil::getPassedValue('file');
		$newfolder= FormUtil::getPassedValue('newfolder');
		extract($args);
		//figure some stuff out
		$fullparent=str_replace("//","/", ModUtil::getVar('Fileaccess','rootdir')."/".$parent);

		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"mkdir invoked, parent=$parent, newfolder=$newfolder, fullparent=$fullparent"));

		//check for garbage in the input, writable parent, and postnuke perms
		if ( $parent
		&& ModUtil::apiFunc('Fileaccess','user','taintsafe',array('string'=>$newfolder))
		&& ModUtil::apiFunc('Fileaccess','user','taintsafe',array('string'=>$parent))
		&& is_writable($fullparent)
		&& ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
		array('file'=>"$parent",'access'=>ACCESS_ADMIN)) ) {
			if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"mkdir passed auth"));
			//ok so far, was newfolder specified?
			if ($newfolder !="" ) {
				if(ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
				array('file'=>"$parent/$newfolder",'access'=>ACCESS_ADMIN))) {
					$fullnewfolder=str_replace("//","/", $fullparent."/".$newfolder);
					if (!file_exists($fullnewfolder)) {
						//go ahead and create the new folder
						if (mkdir($fullnewfolder)) {
							//change to global write
							chmod("$fullnewfolder",0777);
							ModUtil::apiFunc('Fileaccess','user','status_message_add',array('message'=>"Folder $newfolder created"));
							ModUtil::apiFunc('Fileaccess','user','write_log',array('message'=>"created folder $newfolder"));
						} else {
							ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"Folder $newfolder creation failed, please contact your administrator."));
						}
					} else {
						ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"Something named $newfolder already exists, cannot create it again."));
					}
				} else {
					ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"User not authorized to create \"$newfolder\" in \"$parent\", please contact your administrator."));
				}
			} else {
				//newfolder not specified, add the form to the status message
				$this->view->assign('mkdir_file',$parent);
				$mkdir_form=$this->view->fetch('Fileaccess_user_mkdir_form.htm');
				ModUtil::apiFunc('Fileaccess','user','status_message_add',array('message'=>$mkdir_form));
			}
		} else {
			if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"mkdir AUTH ISSUE"));
			ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"User not authorized to mkdir in \"$parent\", please contact your administrator."));
		}

		return $this->displaylist($args);
	}

	/**
	* the move function moves or renames a file
	* if the newfolder variable isn't passed then it displays a form to be filled in
	* the form the re-invokes this function
	*
	*/
	public function move($args)
	{
		$debugging = FormUtil::getPassedValue('debugging');
		if ($debugging=="yes" ) {
			SessionUtil::setVar('FileaccessDebugging',$debugging);
		} else if ( $debugging=="no") {
			SessionUtil::setVar('FileaccessDebugging',"");
			$debugging="";
		} else {
			$debugging=SessionUtil::getVar('FileaccessDebugging');
		}
		
    $csrftoken = FormUtil::getPassedValue('csrftoken');
    $this->checkCsrfToken($csrftoken);
		
		$this->view->caching=false;
		$destdir= FormUtil::getPassedValue('destdir');
		$source = FormUtil::getPassedValue('source');
		$destname = FormUtil::getPassedValue('destname');
		//when multiple moves are supported
		//$sources[] = FormUtil::getPassedValue('files[]');

		extract($args);
		//figure some stuff out
		$fullsource=str_replace("//","/", ModUtil::getVar('Fileaccess','rootdir')."/".$source);

		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"move invoked, source=$source, destdir=$destdir, destname=$destname"));

		//check for garbage in the input, writable parent, and postnuke perms
		if ( $source && file_exists($fullsource)
		&& ModUtil::apiFunc('Fileaccess','user','taintsafe',array('string'=>$source))
		&& is_writeable(dirname($fullsource))
		&& ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
		array('file'=>"$source",'access'=>ACCESS_DELETE))
		) {
			if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"move passed auth"));
			//ok so far, was destination specified?
			if ($destdir
			&& ModUtil::apiFunc('Fileaccess','user','taintsafe',array('string'=>$destdir))
			&& ModUtil::apiFunc('Fileaccess','user','taintsafe',array('string'=>$destname))
			) {
				$fulldestdir=str_replace("//","/", ModUtil::getVar('Fileaccess','rootdir')."/".$destdir);
				$fulldestination=str_replace("//","/",$fulldestdir."/".$destname);
				if ( ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
				array('file'=>"$destdir",'access'=>ACCESS_ADD))
				&& is_writable($fulldestdir)
				) {
					if (file_exists($fulldestdir) && filetype($fulldestdir)=='dir') {
						if(!file_exists($fulldestination)) {
							//go ahead and move the file to the new location
							if (rename($fullsource,$fulldestination)) {
								ModUtil::apiFunc('Fileaccess','user','status_message_add',array('message'=>"\"$source\" successfully moved to \"$destdir/$destname\""));
								ModUtil::apiFunc('Fileaccess','user','write_log',array('message'=>"moved \"$source\" to \"$destdir/$destname\""));
							} else {
								ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"\"$source\" move to \"$destdir/$destname\" failed, please contact your administrator"));
							}
						} else {
							ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"$destname already exists in $destdir, cannot execute the move/rename."));
						}
					} else {
						ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"$destdir doesn't exist, cannot execute the move/rename."));
					}
				} else {
					ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"User not authorized to move \"$source\" to \"$destdir/$destname\", please contact your administrator."));
				}
			} else {
				//destination not specified, add the form to the status message
				//create the list of currently displayed folders as possible destinations
				$folderlist = ModUtil::apiFunc('Fileaccess','user','getall',array('access_check'=>ACCESS_ADD));
				//remove folders we can't add stuff too from the list
				foreach ($folderlist as $key => $value) {
					if (!ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
					array('file'=>$value['vfilename'],'access'=>ACCESS_ADD)) ){
						$folderlist[$key][$value]['type']='ignore';
					}
				}
				$this->view->assign('source',$source);
				$this->view->assign('folderlist',$folderlist);
				$this->view->assign('destdir',dirname($source));
				$this->view->assign('destname',basename($source));
				//	    	$this->view->assign('sources',$sources);
				$move_form=$this->view->fetch('Fileaccess_user_move_form.htm');
				ModUtil::apiFunc('Fileaccess','user','status_message_add',array('message'=>$move_form));
			}
		} else {
			if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"move AUTH ISSUE"));
			ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"User not authorized to move \"$source\", please contact your administrator."));
		}
		return $this->displaylist($args);
	}

	/**
	* the add function allows you to upload a file
	* if the newfolder variable isn't passed then it displays a form to be filled in
	* the form the re-invokes this function
	*
	*/
	public function add($args)
	{
		$debugging = FormUtil::getPassedValue('debugging');
		if ($debugging=="yes" ) {
			SessionUtil::setVar('FileaccessDebugging',$debugging);
		} else if ( $debugging=="no") {
			SessionUtil::setVar('FileaccessDebugging',"");
			$debugging="";
		} else {
			$debugging=SessionUtil::getVar('FileaccessDebugging');
		}

		$parent = FormUtil::getPassedValue('file');
		extract($args);
		$this->view->caching=false;
		//figure some stuff out
		$newfile=basename($_FILES['userfile']['name']);
		$fullparent=str_replace("//","/", ModUtil::getVar('Fileaccess','rootdir')."/".$parent);

		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"add invoked, parent=$parent, newfile=$newfile, fullparent=$fullparent"));
		
		//checks for the edge case of PHP stopping the upload and dropping all the POST data
		//couldn't figure out which Zik API calls were equivalent of _POST and where I could find the content length header
		//the code based on this primitive:
		//		if($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST) &&
		//		     empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0)
		$request = $this->serviceManager->getService('request');
		if( $request->getMethod()=='POST' && empty($_POST)
			&& empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0)
		{
		  $displayMaxSize = ini_get('post_max_size');
		  switch(substr($displayMaxSize,-1))
		  {
		    case 'G':
		      $displayMaxSize = $displayMaxSize * 1024;
		    case 'M':
		      $displayMaxSize = $displayMaxSize * 1024;
		    case 'K':
		       $displayMaxSize = $displayMaxSize * 1024;
		  }
		  $posterror = 'Posted data for file is too large. '.
				$_SERVER[CONTENT_LENGTH].
				' bytes exceeds the maximum size of '.
				$displayMaxSize.' bytes set in php.ini - contact your administrator.';
		  ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"$posterror"));
		}

		//check for garbage in the input, writable parent, and postnuke perms
		if ( ModUtil::apiFunc('Fileaccess','user','taintsafe',array('string'=>$newfile))
				&& ModUtil::apiFunc('Fileaccess','user','taintsafe',array('string'=>$parent))
				&& is_writable($fullparent)
				&& ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
				array('file'=>"$parent",'access'=>ACCESS_ADD)) ) {
			if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"add passed auth"));
			//ok so far, was newfile specified?
			if ($newfile
					&& ModUtil::apiFunc('Fileaccess','user','taintsafe',array('string'=>$parent."/".$newfile))
					&& ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
					array('file'=>$parent."/".$newfile,'access'=>ACCESS_ADD))) 
				{
				//check for the csrf token once it is a clean post
		    $csrftoken = FormUtil::getPassedValue('csrftoken');
		    $this->checkCsrfToken($csrftoken);

				$fullnewfile=str_replace("//","/", $fullparent."/".$newfile);
				if ( !file_exists($fullnewfile) ) {
					//go ahead and upload the new file
					if ($_FILES['userfile']['size'] > 10 && $_FILES['userfile']['name']!="" && is_uploaded_file($_FILES['userfile']['tmp_name']) && $_FILES['userfile']['error'] == UPLOAD_ERR_OK ) {
						if (move_uploaded_file($_FILES['userfile']['tmp_name'], $fullnewfile)) {
							//change permissions to allow the web server to write here
							chmod("$fullnewfile",0777);
							ModUtil::apiFunc('Fileaccess','user','status_message_add',array('message'=>"File '$parent/$newfile' uploaded "));
							ModUtil::apiFunc('Fileaccess','user','write_log',array('message'=>"uploaded file '$parent/$newfile'"));
							if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"file '$fullnewfile' uploaded"));
						} else {
							ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"Unknown: File upload of '$parent/$newfile' failed please contact your administrator."));
						}
					} else {
						ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"Problem processing upload for ".$_FILES['userfile']['name']."(".$_FILES['userfile']['tmp_name']."). ".ModUtil::apiFunc('Fileaccess','user','fileErrorCodeToMessage',$_FILES['userfile']['error'])));
					}
				} else {
					ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"Something named '$newfile' already exists, delete it first."));
				}

			} else {
				//newfile not specified, add the upload form to the status message
				$this->view->assign('file',$parent);
				$this->view->assign('filemaxsize',ModUtil::getVar('Fileaccess', 'filemaxsize') );
				$add_form=$this->view->fetch('Fileaccess_user_upload_form.htm');
				ModUtil::apiFunc('Fileaccess','user','status_message_add',array('message'=>$add_form));
			}
		} else {
			if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"add AUTH ISSUE"));
			ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"User not authorized to add $newfile into in $parent, please contact your administrator."));
		}
		return $this->displaylist($args);
	}


	/**
	* delete
	*
	*/
	public function delete($args)
	{
		extract($args);
		$debugging = FormUtil::getPassedValue('debugging');
		if ($debugging=="yes" ) {
			SessionUtil::setVar('FileaccessDebugging',$debugging);
		} else if ( $debugging=="no") {
			SessionUtil::setVar('FileaccessDebugging',"");
			$debugging="";
		} else {
			$debugging=SessionUtil::getVar('FileaccessDebugging');
		}
		
    $csrftoken = FormUtil::getPassedValue('csrftoken');
    $this->checkCsrfToken($csrftoken);


		$this->view->caching=false;
		$file = FormUtil::getPassedValue('file');
		$confirm = FormUtil::getPassedValue('confirm');
		//$debugging=1;

		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"delete invoked, file=$file"));

		if (empty($file)) {
			$file = $objectid;
		}

		$fullfilename=str_replace("//","/",ModUtil::getVar('Fileaccess','rootdir')."/".$file);
		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"delete invoked fullfilename=$fullfilename"));
		//now we delete the file
		if ($confirm=="yes") {
			$rc=ModUtil::apiFunc('Fileaccess','user','delete',array('file'=>$file));
		} else {
			//confirm not sent, display the confirm form in the status section
			$this->view->assign('file',$file);
			$add_form=$this->view->fetch('Fileaccess_user_deleteconfirm_form.htm');
			ModUtil::apiFunc('Fileaccess','user','status_message_add',array('message'=>$add_form));
		}

		return $this->displaylist($args);
	}

	function var_dump_pre($mixed = null) {
		echo '<pre>';
		var_dump($mixed);
		echo '</pre>';
		return null;
	}
	function var_dump_ret($mixed = null) {
		ob_start();
		var_dump($mixed);
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	//GET FILE
	//pass me the virtual file name and I'll send it to the browser as a download in another window
	private function get_file($args) {
		extract($args);

		$debugging=SessionUtil::getVar('FileaccessDebugging');
		$rc=0;
		$fullfilename=ModUtil::getVar('Fileaccess','rootdir')."/".$file;
		if (!$mimetype) {
			//fixes a problem with different versions of PHP's support for mime_content_type
			if ( !function_exists('mime_content_type')) {
				$mimetype=trim ( exec ('file -bi ' . escapeshellarg ( $fullfilename ) ) ) ;
			} else {
				$mimetype=mime_content_type( $fullfilename );
			}
		}
		header("Content-type: $mimetype");
		header("Content-Length: ".filesize($fullfilename));
		if ($debugging) {header("debug: $fullfilename - $mimetype - $attachment");}
		if ($attachment!="none") {
			header("Content-Disposition: attachment; filename=".basename($file));
		}
		readfile($fullfilename);
		//ModUtil::apiFunc('Fileaccess','user','status_message_add',array('message'=>"File '$file' downloaded"));
		ModUtil::apiFunc('Fileaccess','user','write_log',array('message'=>"downloaded file '$file'"));
		//	$rc=1;
		//	return $rc;
		//we exit from postnuke because we're done here and
		//the present state of the display doesn't need to change.
		//exit;
		return true;
	}
}
?>