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

class Fileaccess_Api_User extends Zikula_AbstractApi
{

	public function getall($args)
	{
		// Get arguments from argument array - all arguments to this public function
		// should be obtained from the $args array, getting them from other
		// places such as the environment is not allowed, as that makes
		// assumptions that will not hold in future versions of PostNuke
		extract($args);
		$debugging=SessionUtil::getVar('FileaccessDebugging');
		if ($access_check=="") {$access_check=ACCESS_READ;}

		$items = array();

		// Security check - important to do this as early on as possible to
		// avoid potential security holes or just too much wasted processing
		if (!SecurityUtil::checkPermission( 'Fileaccess::', '::', ACCESS_OVERVIEW)) {
			//failed so we return nothing
			return $items;
		}

		$rootdir=ModUtil::getVar('Fileaccess','rootdir');

		$items =
		ModUtil::apiFunc('Fileaccess',
			'user',
			'get_FileList',
			array(
			'root' => ModUtil::getVar('Fileaccess','rootdir'),
			'depth'=>1,
			'FileEntityListRef'=>&$FileEntityList,
			'access_check'=>$access_check,
			'sortbyname'=>$sortbyname
		));
		// Return the items
		return $items;
	}

	//DELETE
	// pass file=>filename in the array to delete a file or folder
	//
	public function delete($args) {
		extract($args);
		$rc=0;
		$debugging=SessionUtil::getVar('FileaccessDebugging');

		//is there anything strange in the filename?
		if (!ModUtil::apiFunc('Fileaccess','user','taintsafe',array('string'=>$file))) {
			//yikes! folder is tainted so we dont trust it
			ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"$file is not valid"));
		} else {
			$fullfilename=str_replace("//","/", ModUtil::getVar('Fileaccess','rootdir')."/".$file);
			if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"userapi_delete attempting to delete $file - $fullfilename"));
			if (filetype($fullfilename) == "dir") {
				//request to delete a folder
				//if (hack_SecurityUtil_checkPermission($fullfilename,ACCESS_ADMIN)) {
				if (ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
				array('file'=>$fullfilename,'access'=>ACCESS_ADMIN)) ){
					if (rmdir($fullfilename)) {
						ModUtil::apiFunc('Fileaccess','user','status_message_add',array('message'=>"file '$file' deleted"));
						ModUtil::apiFunc('Fileaccess','user','write_log',array('message'=>"deleted file '$file'"));
					} else {
						ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"failed to delete file '$file',<br>delete the files first"));
					}
				} else {
					ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"NOT AUTHORIZED TO DELETE $file"));
				}
			} elseif (filetype($fullfilename) == "file") {
				//request to delete a file
				//if (hack_SecurityUtil_checkPermission($fullfilename,ACCESS_ADMIN)) {
				if (ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
				array('file'=>$fullfilename,'access'=>ACCESS_DELETE)) ){
					if (unlink($fullfilename)) {
						ModUtil::apiFunc('Fileaccess','user','status_message_add',array('message'=>"file '$file' deleted"));
						ModUtil::apiFunc('Fileaccess','user','write_log',array('message'=>"deleted file '$file'"));
					} else {
						ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"failed to delete file '$file'"));
					}
				} else {
					ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"USER NOT AUTHORIZED TO DELETE file '$file'"));
				}
			} else {
				ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"ERROR: Something strange happened trying to process '$file'"));
			}
		}

		return $rc;
	}

	//checks the passed directory name against the FileaccessOpenDirs session var
	//returns true if the passed name or any parent matches any token in opendirs
	public function check_opendirs($args) {
		extract($args);
		$debugging=0;
		//allow an override of the session var for testing
		if ($opendirs=="") {
			$opendirs=SessionUtil::getVar('FileaccessOpenDirs');
		}
		$rc=0;

		//special case if dir is blank or "/" treat it as open
		if ($dir=="" || $dir=="/") {
			$rc=1;
		}

		$tok=strtok($opendirs,":");
		while ($tok !== false && $rc==0 ) {
			$pattern1='/^'.str_replace('/','\/',$dir).'\/.*/';
			$pattern2='/^'.str_replace('/','\/',$dir).'$/';
			if (preg_match($pattern1,$tok) || preg_match($pattern2,$tok) ) {
				$rc=1;
				last;
			}
			$tok=strtok(":");
		}

		//  /junk:/junk1/junk2:/junk3
		//$pattern1='/[\b\:]'.str_replace('/','\/',$dir).'[\b\:\/]/';
		//    $pattern1='/[\s\:]'.str_replace('/','\/',$dir).'[\:\/\s]/';
		//    if (preg_match($pattern1,$opendirs)) {
		//   	$rc=1;
		//    }
		//    ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"check_opendirs dir=$dir  opendirs=$opendirs pattern1=$pattern1 pattern2=$pattern2"));
		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"check_opendirs dir=$dir  opendirs=$opendirs returns $rc"));
		return $rc;
	}


	//adds a directory to the session veriable list of open directories
	//if the parent already exists, it overwrites the parent
	//FileaccessOpenDirs is a colon delimited list of open folders
	//if newdir is already in the list, it is removed and replaced with it's parent
	//if newdir's parent is already in the list, the parent is replaced with newdir
	//otherwise, newdir is added to the list
	public function add_opendir($args) {
		extract($args);
		$debugging=SessionUtil::getVar('FileaccessDebugging');
		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"Fileaccess_user_add_diropen started with ".SessionUtil::getVar('FileaccessOpenDirs')));
		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"Fileaccess_user_add_diropen called with $newdir"));
		//skip it if newdir is blank or root
		if ( $newdir != "" && $newdir!="/") {
			//create an array of the tokens so we can re-assemble it
			$opendirs = array();
			$tok=strtok(SessionUtil::getVar('FileaccessOpenDirs'),":");
			while ($tok !== false ) {
				if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>" Fileaccess_user_add_diropen pushing \"$tok\" into the opendirs array"));
				$opendirs[]=$tok;
				$tok=strtok(":");
			}
			//new output arrray (avoids unset calls)
			$newopendirs=array();
			//flag if any edits were done
			$opendirseditflag=0;
			//step through each token of opendirs
			foreach ( $opendirs as $dir) {
				if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>" Fileaccess_user_add_diropen testing newdir=$newdir against entry $dir"));
				$pattern1='/^'.str_replace('/','\/',$newdir).'\/.*/';
				$pattern2='/^'.str_replace('/','\/',$dir).'\/.*/';
				if (strcmp($newdir,$dir)==0 || preg_match($pattern1,$dir) ) {
					//is it or its child already in the list? then its being closed - replace with parent
					$newopendirs[]=dirname($newdir);
					$opendirseditflag=1;
					if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>" Fileaccess_user_add_diropen $dir cmp to $newdir - $dir replaced with parent"));
				} elseif ( preg_match($pattern2,$newdir) ) {
					// If it's parent already in the list - then replace the parent with the new child
					$newopendirs[]=$newdir;
					$opendirseditflag=1;
					if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>" Fileaccess_user_add_diropen $dir cmp to $newdir - $dir replaced with $newdir"));
				} else {
					//preserve this entry and move on
					$newopendirs[]=$dir;
					if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>" Fileaccess_user_add_diropen $dir cmp to $newdir - notfound, $dir left alone<br> patern1=$pattern1<br> pattern2=$pattern2"));
				}
			}
			//if nothing was found in the step through, its a net-new add to the list
			if ( $opendirseditflag!=1 ) {
				$newopendirs[]=$newdir;
				if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>" not found - $newdir added"));
			}
			//strictly to help debug
			natcasesort($newopendirs);
			//save the updated session variable after removing dups
			SessionUtil::setVar('FileaccessOpenDirs',implode(":",array_unique($newopendirs)));
		} elseif ( $newdir == "/") {
			//special case closes all the children down
			SessionUtil::setVar('FileaccessOpenDirs',"");
		} else {
			if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"Fileaccess_user_add_diropen processing skipped for $newdir"));
		}
		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"Fileaccess_user_add_diropen ended with ".SessionUtil::getVar('FileaccessOpenDirs')));
		return null;
	}

	public function debug_message_add($args) {
		extract($args);
		$oldmsg=SessionUtil::getVar('FileaccessDebugMsg');
		$newmsg=$oldmsg."<br>".$message;
		SessionUtil::setVar('FileaccessDebugMsg',$newmsg);
	}

	public function error_message_add($args) {
		extract($args);
		$oldmsg=SessionUtil::getVar('FileaccessErrorMsg');
		if ($oldmsg) {
			$oldmsg=$oldmsg."<br>";
		}
		$newmsg=$oldmsg.$message;
		SessionUtil::setVar('FileaccessErrorMsg',$newmsg);
	}

	public function status_message_add($args) {
		extract($args);
		$oldmsg=SessionUtil::getVar('FileaccessStatusMsg');
		$newmsg=$oldmsg."<br>".$message;
		SessionUtil::setVar('FileaccessStatusMsg',$newmsg);
	}

	//toggle the sort form forward to reverse
	public function sortby_name_toggle() {
		//extract($args);
		if(SessionUtil::getVar('FileaccessSortbyName')=="desc") {
			SessionUtil::setVar('FileaccessSortbyName',"asc");
		} else {
			SessionUtil::setVar('FileaccessSortbyName',"desc");
		}
	}

	//returns flase if a string has any untoward characters, true if it doesn't
	public function taintsafe($args) {
		extract($args);
		$rc=0;
		if ( $string == "" ) {
			//no more to test
			$rc=1;
		} elseif (is_string($string) && ereg('^([-_ A-Za-z0-9\/\.]*)$',$string)
		&& !ereg('(\.\.)',$string)
		&& !ereg('(\/\.)',$string)
		&& !ereg('^\.',$string)
		) {
			#print "$string is safe<br>";
			$rc=1;
		}
		return $rc;
	}

	public function stripslash($string) {
		//strips the first slash from a string
		if (ereg('^/.*',$string) ) {
			//remove the slash
			$string=substr( $string, 1);
		}
		return $string;
	}

	public function stripslashers($string) {
		//strips the all duplicate slashes from a string
		while (ereg('\/\/',$string) ) {
			$string=str_replace("//","/", $string);
		}
		return $string;
	}

	public function stripdots($string) {
		//strips the first dots from a string
		if (ereg('^\..*',$string) ) {
			//remove the slash
			$string=substr( $string, 1);
		}
		return $string;
	}

	//writes a message to the log
	public function write_log($args) {
		extract($args);
		//PHP4 compat
		if(!function_exists('file_put_contents')) {
			define('FILE_APPEND', 1);
			function file_put_contents($filename, $data, $file_append = false) {
				$fp = fopen($filename, (!$file_append ? 'w+' : 'a+'));
				if(!$fp) {
					trigger_error('file_put_contents cannot write in file '.$filename, E_USER_ERROR);
					return FALSE;
				}
				fputs($fp, $data);
				fclose($fp);
				return TRUE;
			}
		}
		$LogFile=ModUtil::getVar('Fileaccess','rootdir')."/.logfile.txt";
		if (!file_put_contents($LogFile,date("Y-m-d H:i:s")." ".UserUtil::getVar('uname')." $message\n",FILE_APPEND)) {
			ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"unable to write to the logfile: $LogFile"));
		}
	}

	//rewrites the auth request, translating the slashes in the filename
	//into colons so they can be used in the SecurityUtil::checkPermission call
	public function hack_SecurityUtil_checkPermission($args) {
		//expects args to contain filename, and access flag
		extract($args);
		$debugging=SessionUtil::getVar('FileaccessDebugging');

		$rc=FALSE;
		#print stripslash($file)."<br>";
		$file=rawurldecode($file);
		$instance=strtr($this->stripslash($this->stripslashers($file)),"/",":");
		#print "checking $instance <br>";
		$ModName_check="Fileaccess::";
		$instance_check=$instance.":.*";
		if (SecurityUtil::checkPermission( $ModName_check, $instance_check, $access) ) {
			$rc=TRUE;
		}
		//if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',
		//	array('message'=>"hack_SecurityUtil_checkPermission invoked, mod=$ModName_check, file=$file, instance=$instance_check, access=$access, returned $rc"));

		//if ($file == "/") { $rc=true;}
		return $rc;
	}

	//converts a passed "real" filename
	//into an array used for file entries in the get_FileList public function
	public function get_filearray($args){
		//expects args to contain filename, root, depth, and an opendir flag
		extract($args);

		$fileentry=array();
		$fileentry['opendir']=$opendir;
		$fileentry['depth']=$depth;
		$fileentry['root']=$root;
		$fileentry['filename']=$filename;
		$fileentry['type']=filetype($filename);
		$fileentry['vfilename']=str_replace($root, "", $filename);
		$fileentry['basename']=basename($fileentry['vfilename']);
		//special case deals with the root
		if ($fileentry['vfilename']=="") {
			$fileentry['vfilename']="/";
			$fileentry['basename']="/";
		}
		//stat info
		if ($stats=stat($filename)) {
			$fileentry['size']=$stats['size'];
			$fileentry['time']=$stats['mtime'];
		} else {
			$fileentry['size']=1;
			$fileentry['time']=1;
		}
		return $fileentry;
	}

	//public function get_FileList($root,$entity,$current,$depth,$Fileaccess_FileEntityList)
	// root is the first folder in the call and maintained throughout the recursion
	// entity is not used?
	// current is the 'real' directory that is presently being checked
	// depth is how far we've come so far
	// FileEntityListRef is a reference to an array of arrays,
	// each entry representing an item to potentially be displayed
	//   array(
	//		filename=>"full filesystem filename"
	//		basename=>"short filename or basename"
	//		vfilename=>"full name minus the root - relative to the root"
	//		root=>"root of this file - for future use"
	//		depth=>"int depth"
	//		type=>"dir|file"
	//		opendir=>"0|1" indicates whether a folder's files are being displayed or not
	//		)
	//pass root, entity, current, depth, opendirs, and FileEntityListRef (a ref to fileentitylist) as an array of args
	//only show files and subdirs for dirs found in the opendirs string
	public function get_FileList($args) {
		extract($args);
		if ($current=="") {$current=$root;}
		$FileEntityList=& $FileEntityListRef;
		$debugging=SessionUtil::getVar('FileaccessDebugging');
		if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"get_FileList invoked, root=$root, depth=$depth, current=$current"));
		//clean these up
		$current=$this->stripslashers($current);
		$folderfull=$current;
		$folder=rawurldecode($current);
		$vcurrent=str_replace($root, "", $current);
		//trim spaces
		$folder=ltrim(chop($folder));
		if ($access_check=="") {$access_check=ACCESS_READ;}
		//is there anything strange in the filename?
		if (!ModUtil::apiFunc('Fileaccess','user','taintsafe',array('string'=>$folder)))  {
			//yikes! folder is tainted so we dont trust it
			ModUtil::apiFunc('Fileaccess','user','error_message_add',array('message'=>"folder=$folder is tainted!"));
			return array();
		}
		$opendir=0;
		//special case leaves the root crumb
		if ( $current == $root ) {
			$FileEntityList[]= ModUtil::apiFunc('Fileaccess',
			'user',
			'get_filearray',
			array(
			'filename'=>$root,
			'depth'=>0,
			'root'=>$root,
			'opendir'=>1
			)
			);
		}

		if (filetype($current) == "dir" ) {
			//working on a folder that has been opened
			$FileList=array();
			$DirFH=opendir ($current);
			if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',
			array('message'=>"get_FileList reading folder $current"));
			//read the current dir into an array
			while ($file = readdir($DirFH) ) {
				//drop anything that starts with a dot
				$vfile=$vcurrent."/".$file;
				if ( !ereg('^\.',$file)
				&& ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
				array('file'=>"$vfile",'access'=>$access_check))
				) {
					if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',
					array('message'=>"get_FileList found \"$vfile\" and marked for processing"));
					$FileList[]=$file;
				}
			}
			closedir ($DirFH);
			if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',
			array('message'=>"get_FileList starting work on sorting the folder"));
			//sort the list of files
			natcasesort($FileList);
			//reverse the sort if directed
			if ($sortbyname=="desc") {
				rsort($FileList);
			}
			//now run through the sorted list, list the folders first, then the files
			foreach ($FileList as $file) {
				//if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',
				//		array('message'=>"get_FileList is it a folder $folderfull/$file"));
				$FileEntry=array();
				$FileEntry=ModUtil::apiFunc('Fileaccess',
				'user',
				'get_filearray',
				array(
				'filename'=>"$current/$file",
				'depth'=>$depth,
				'root'=>$root,
				)
				);
				//recurse only if the depth is less than 30
				if ($FileEntry['type'] == "dir" && $depth <30) {
					//is it an opendir?
					$opendirflag=0;
					if( ModUtil::apiFunc('Fileaccess','user','check_opendirs', array('dir'=>$FileEntry['vfilename']))){
						$FileEntry['opendir']=1;
						$opendirflag=1;
					}
					//store the folder name
					if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',
					array('message'=>"get_FileList storing folder $folderfull/$file"));
					if (ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
					array('file'=>$FileEntry['vfilename'],'access'=>$access_check)) ){
						if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',
						array('message'=>"get_FileList $folderfull/$file passed security check"));
						$FileEntityList[] = $FileEntry;
					}
					//if the current dir or stuff leading up to it is found in opendirs then we open it up.
					// and we keep going
					if ( $opendirflag
					&& ModUtil::apiFunc('Fileaccess','user','hack_SecurityUtil_checkPermission',
					array('file'=>$FileEntry['vfilename'],'access'=>$access_check))
					) {
						if ($debugging) ModUtil::apiFunc('Fileaccess','user','debug_message_add',
						array('message'=>"get_FileList diving into $folderfull/$file"));
						$opendir=1;
						$getfiles=1;
						// mark it as an open folder
						//						end($FileEntityList);
						//						$FileEntityList[key($FileEntityList)]->set_ent("dirtype","diropen");
						// and go down one more level
						$FileEntityList=
						ModUtil::apiFunc('Fileaccess',
						'user',
						'get_FileList',
						array(
						'root' => $root,
						'current'=>$current."/".$file,
						'depth'=>$depth+1,
						'$opendir'=>1,
						'access_check'=>$access_check,
						'FileEntityListRef'=>&$FileEntityList,
						));
					}
				}
			}
			//now run through the files in this folder
			foreach ($FileList as $file) {
				if (filetype($folderfull."/".$file) == "file" && $depth <30) {
					if ($debugging){ ModUtil::apiFunc('Fileaccess','user','debug_message_add',array('message'=>"get_FileList storing file $folderfull/$file"));}
					//					$FileEntityList[]=$FileEntry;
					$FileEntityList[]= ModUtil::apiFunc('Fileaccess',
						'user',
						'get_filearray',
						array(
						'filename'=>"$current/$file",
						'depth'=>$depth,
						'root'=>$root
						)
					);
				}
			}
		}
		return $FileEntityList;
	}

	function fileErrorCodeToMessage($code)
	{
		switch ($code) {
			case UPLOAD_ERR_OK:
				$message = "File upload success";
				break;
			case UPLOAD_ERR_INI_SIZE:
				$message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
				break;
			case UPLOAD_ERR_FORM_SIZE:
				$message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
				break;
			case UPLOAD_ERR_PARTIAL:
				$message = "The uploaded file was only partially uploaded";
				break;
			case UPLOAD_ERR_NO_FILE:
				$message = "No file was uploaded";
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$message = "Missing a temporary folder";
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$message = "Failed to write file to disk";
				break;
			case UPLOAD_ERR_EXTENSION:
				$message = "File upload stopped by extension";
				break;
			default:
				$message = "Unknown upload error";
				break;
		}
		return $message;
	}

}
?>
