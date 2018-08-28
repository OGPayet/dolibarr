<?php
/* Copyright (C) 2018      Open-DSI              <support@open-dsi.fr>
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
 *       \file       htdocs/comm/propal/document.php
 *       \ingroup    propal
 *       \brief      Management page of documents attached to a business proposal
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../main.inc.php")) $res=@include '../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
dol_include_once('/requestmanager/class/requestmanager.class.php');
dol_include_once('/requestmanager/lib/requestmanager.lib.php');

$langs->load('other');
$langs->load('requestmanager@requestmanager');

$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$id			= GETPOST('id','int');
$ref		= GETPOST('ref','alpha');

// Security check
$result = restrictedArea($user, 'requestmanager', $id);
if (!$user->rights->requestmanager->read_file)
    accessforbidden();

// Get parameters
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="name";

$object = new RequestManager($db);
// Load object
if ($id > 0 || !empty($ref)) {
	$ret = $object->fetch($id, $ref);
    if ($ret > 0) {
    } elseif ($ret < 0) {
        dol_print_error('', $object->error);
    } else {
        print $langs->trans('NoRecordFound');
        exit();
    }
}


/*
 * Actions
 */

if ($object->id > 0)
{
    $object->fetch_thirdparty();
    $upload_dir = $conf->requestmanager->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
    include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';
}


/*
 * View
 */

llxHeader('', $langs->trans('RequestManagerRequest'), 'EN:Request_Manager_En|FR:Request_Manager_Fr|ES:Request_Manager_Es');

$form = new Form($db);

if ($object->id > 0)
{
	$upload_dir = $conf->requestmanager->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);

    $head = requestmanager_prepare_head($object);
	dol_fiche_head($head, 'document', $langs->trans('RequestManagerCard'), -1, 'requestmanager@requestmanager');

	// Construit liste des fichiers
	$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview.*\.png)$',$sortfield,(strtolower($sortorder)=='desc'?SORT_DESC:SORT_ASC),1);
	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}


	// Proposal card

    $linkback = '<a href="' . dol_buildpath('/requestmanager/list.php', 1) . '?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref='<div class="refidno">';
	// External Reference
    $morehtmlref.='<br>'.$langs->trans('RequestManagerExternalReference') . ' : ' . $object->ref_ext;
    $morehtmlref.='</div>';

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border" width="100%">';

	// Files infos
	print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td>'.count($filearray).'</td></tr>';
	print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td>'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';

	print "</table>\n";

	print '</div>';


	dol_fiche_end();

	$modulepart = 'requestmanager';
	$permission = $user->rights->requestmanager->creer;
	$permtoedit = $user->rights->requestmanager->creer;
	$param = '&id=' . $object->id;
	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';
}
else
{
	print $langs->trans("ErrorUnknown");
}

llxFooter();
$db->close();
