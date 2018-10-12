<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/requestmanager/lib/requestmanagermessage.lib.php
 * 	\ingroup	requestmanager
 *	\brief      Functions for the module Request Manager Message
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

/**
 * Scan a directory and init $_SESSION to manage uploaded files with list of all found files.
 * Note: Only email module seems to use this. Other feature initialize the $_SESSION doing $formrequestmanagermessage->clear_attached_files(); $formrequestmanagermessage->add_attached_files()
 *
 * @param   RequestManager  $requestmanager         RequestManager handle
 * @param	string	        $pathtoscan				Path to scan
 * @return	void
 */
function requestmanagermessage_init_file_process(&$requestmanager, $pathtoscan='')
{
	$listofpaths=array();
	$listofnames=array();
	$listofmimes=array();

	if ($pathtoscan)
	{
		$listoffiles=dol_dir_list($pathtoscan,'files');
		foreach($listoffiles as $key => $val)
		{
			$listofpaths[]=$val['fullname'];
			$listofnames[]=$val['name'];
			$listofmimes[]=dol_mimetype($val['name']);
		}
	}

	$_SESSION["listofpaths-rm".$requestmanager->id]=join(';',$listofpaths);
	$_SESSION["listofnames-rm".$requestmanager->id]=join(';',$listofnames);
	$_SESSION["listofmimes-rm".$requestmanager->id]=join(';',$listofmimes);
}


/**
 * Get and save an upload file (for example after submitting a new file a mail form).
 * All information used are in db, conf, langs, user and _FILES.
 * Note: This function can be used only into a HTML page context.
 *
 * @param   RequestManager  $requestmanager         RequestManager handle
 * @param	string	        $upload_dir				Directory where to store uploaded file (note: used to forge $destpath = $upload_dir + filename)
 * @param	int		        $allowoverwrite			1=Allow overwrite existing file
 * @param	int		        $donotupdatesession		1=Do no edit _SESSION variable but update database index. 0=Update _SESSION and not database index.
 * @param	string	        $varfiles				_FILES var name
 * @param	string	        $savingdocmask			Mask to use to define output filename. For example 'XXXXX-__YYYYMMDD__-__file__'
 * @param	string	        $link					Link to add (to add a link instead of a file)
 * @param	int		        $generatethumbs			1=Generate also thumbs for uploaded image files
 * @return	int                                     <=0 if KO, >0 if OK
 */
function requestmanagermessage_add_file_process(&$requestmanager, $upload_dir, $allowoverwrite=0, $donotupdatesession=0, $varfiles='addedfile', $savingdocmask='', $link=null, $generatethumbs=1)
{
	global $db,$user,$conf,$langs;

	$res = 0;

	if (! empty($_FILES[$varfiles])) // For view $_FILES[$varfiles]['error']
	{
		dol_syslog('requestmanagermessage_add_file_process requestmanager_id='.$requestmanager->id.' upload_dir='.$upload_dir.' allowoverwrite='.$allowoverwrite.' donotupdatesession='.$donotupdatesession.' savingdocmask='.$savingdocmask, LOG_DEBUG);
		if (dol_mkdir($upload_dir) >= 0)
		{
			$TFile = $_FILES[$varfiles];
			if (!is_array($TFile['name']))
			{
				foreach ($TFile as $key => &$val)
				{
					$val = array($val);
				}
			}

			$nbfile = count($TFile['name']);

			for ($i = 0; $i < $nbfile; $i++)
			{
				// Define $destfull (path to file including filename) and $destfile (only filename)
				$destfull=$upload_dir . "/" . $TFile['name'][$i];
				$destfile=$TFile['name'][$i];

				if ($savingdocmask)
				{
					$destfull=$upload_dir . "/" . preg_replace('/__file__/',$TFile['name'][$i],$savingdocmask);
					$destfile=preg_replace('/__file__/',$TFile['name'][$i],$savingdocmask);
				}

				// dol_sanitizeFileName the file name and lowercase extension
				$info = pathinfo($destfull);
				$destfull = $info['dirname'].'/'.dol_sanitizeFileName($info['filename'].'.'.strtolower($info['extension']));
				$info = pathinfo($destfile);
				$destfile = dol_sanitizeFileName($info['filename'].'.'.strtolower($info['extension']));

				$resupload = dol_move_uploaded_file($TFile['tmp_name'][$i], $destfull, $allowoverwrite, 0, $TFile['error'][$i], 0, $varfiles);

				if (is_numeric($resupload) && $resupload > 0)   // $resupload can be 'ErrorFileAlreadyExists'
				{
					global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini;

					include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

					// Generate thumbs.
					if ($generatethumbs)
					{
						if (image_format_supported($destfull) == 1)
						{
						    // Create thumbs
						    // We can't use $object->addThumbs here because there is no $object known

						    // Used on logon for example
						    $imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
						    // Create mini thumbs for image (Ratio is near 16/9)
						    // Used on menu or for setup page for example
						    $imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
						}
					}

					// Update session
					if (empty($donotupdatesession))
					{
						dol_include_once('/requestmanager/class/html.formrequestmanagermessage.class.php');
                        $formrequestmanagermessage = new FormRequestManagerMessage($db, $requestmanager);
                        $formrequestmanagermessage->add_attached_files($destfull, $destfile, $TFile['type'][$i]);
					}

					// Update table of files
					if ($donotupdatesession)
					{
					    $rel_dir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $upload_dir);

					    if (! preg_match('/[\\/]temp[\\/]/', $rel_dir))     // If not a tmp dir
					    {
					        $filename = basename($destfile);
					        $rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
					        $rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

					    include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
					    $ecmfile=new EcmFiles($db);
					    $ecmfile->filepath = $rel_dir;
					    $ecmfile->filename = $filename;
					    $ecmfile->label = md5_file(dol_osencode($destfull));
					    $ecmfile->fullpath_orig = $TFile['name'][$i];
					    $ecmfile->gen_or_uploaded = 'uploaded';
					    $ecmfile->description = '';    // indexed content
					    $ecmfile->keyword = '';        // keyword content
					    $result = $ecmfile->create($user);
                            if ($result < 0)
                            {
                                setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
                            }
					    }
					}

					$res = 1;
					setEventMessages($langs->trans("FileTransferComplete"), null, 'mesgs');
				}
				else
				{
					$langs->load("errors");
					if ($resupload < 0)	// Unknown error
					{
						setEventMessages($langs->trans("ErrorFileNotUploaded"), null, 'errors');
					}
					else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
					{
						setEventMessages($langs->trans("ErrorFileIsInfectedWithAVirus"), null, 'errors');
					}
					else	// Known error
					{
						setEventMessages($langs->trans($resupload), null, 'errors');
					}
				}
			}

		}
	} elseif ($link) {
		require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
		$linkObject = new Link($db);
		$linkObject->entity = $conf->entity;
		$linkObject->url = $link;
		$linkObject->objecttype = GETPOST('objecttype', 'alpha');
		$linkObject->objectid = GETPOST('objectid', 'int');
		$linkObject->label = GETPOST('label', 'alpha');
		$res = $linkObject->create($user);
		$langs->load('link');
		if ($res > 0) {
			setEventMessages($langs->trans("LinkComplete"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorFileNotLinked"), null, 'errors');
		}
	}
	else
	{
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("File")), null, 'errors');
	}

	return $res;
}


/**
 * Remove an uploaded file (for example after submitting a new file a mail form).
 * All information used are in db, conf, langs, user and _FILES.
 *
 * @param   RequestManager  $requestmanager         RequestManager handle
 * @param	int		        $filenb					File nb to delete
 * @param	int		        $donotupdatesession		1=Do not edit _SESSION variable
 * @param   int		        $donotdeletefile        1=Do not delete physically file
 * @return	void
 */
function requestmanagermessage_remove_file_process(&$requestmanager, $filenb,$donotupdatesession=0,$donotdeletefile=1)
{
	global $db,$user,$conf,$langs,$_FILES;

	$keytodelete=$filenb;
	$keytodelete--;

	$listofpaths=array();
	$listofnames=array();
	$listofmimes=array();
	if (! empty($_SESSION["listofpaths-rm".$requestmanager->id])) $listofpaths=explode(';',$_SESSION["listofpaths-rm".$requestmanager->id]);
	if (! empty($_SESSION["listofnames-rm".$requestmanager->id])) $listofnames=explode(';',$_SESSION["listofnames-rm".$requestmanager->id]);
	if (! empty($_SESSION["listofmimes-rm".$requestmanager->id])) $listofmimes=explode(';',$_SESSION["listofmimes-rm".$requestmanager->id]);

	if ($keytodelete >= 0)
	{
		$pathtodelete=$listofpaths[$keytodelete];
		$filetodelete=$listofnames[$keytodelete];
		if (empty($donotdeletefile)) $result = dol_delete_file($pathtodelete,1);  // The delete of ecm database is inside the function dol_delete_file
		else $result=0;
		if ($result >= 0)
		{
			if (empty($donotdeletefile))
			{
				$langs->load("other");
				setEventMessages($langs->trans("FileWasRemoved",$filetodelete), null, 'mesgs');
			}
			if (empty($donotupdatesession))
			{
                dol_include_once('/requestmanager/class/html.formrequestmanagermessage.class.php');
                $formrequestmanagermessage = new FormRequestManagerMessage($db, $requestmanager);
                $formrequestmanagermessage->remove_attached_files($keytodelete);
			}
		}
	}
}