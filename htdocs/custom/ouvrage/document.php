<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Simon TOSSER          <simon@kornog-computing.com>
 * Copyright (C) 2013      Florian Henry          <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
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
 *       \file       htdocs/ouvrage/document.php
 *       \ingroup    product
 *       \brief      Page des documents joints sur les ouvrages
 */

$res = 0;
if (!$res && file_exists("../main.inc.php"))
    $res = @include '../main.inc.php';     // to work if your module directory is into dolibarr root htdocs directory
if (!$res && file_exists("../../main.inc.php"))
    $res = @include '../../main.inc.php';   // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../main.inc.php"))
    $res = @include '../../../main.inc.php';   // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php"))
    $res = @include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php"))
    $res = @include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (!$res)
    die("Include of main fails");

global $langs, $user;
dol_include_once('/ouvrage/class/ouvrage.class.php');

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';


require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

if (! empty($conf->propal->enabled))     require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->facture->enabled))    require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (! empty($conf->commande->enabled))   require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
if (! empty($conf->product->enabled))    require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


$langs->load("other");
$langs->load("products");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);
// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
//$hookmanager->initHooks(array('productdocuments'));

// Get parameters
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0;
}     // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="position_name";




$object = new Ouvrage($db);
if ($id > 0)
{
    $result = $object->fetch($id);

    $upload_dir = $conf->ouvrage->multidir_output[$conf->entity].'/'.get_exdir(0, 0, 0, 0, $object, 'ouvrage').dol_sanitizeFileName($object->ref);
}

$modulepart='ouvrage';


/*
 * Actions
 */

$parameters=array('id'=>$id);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	//Delete line if product propal merge is linked to a file
	if (!empty($conf->global->PRODUIT_PDF_MERGE_PROPAL))
	{
		if ($action == 'confirm_deletefile' && $confirm == 'yes')
		{
			//extract file name
			$urlfile = GETPOST('urlfile', 'alpha');
			$filename = basename($urlfile);
			$filetomerge = new Propalmergepdfproduct($db);
			$filetomerge->fk_product=$object->id;
			$filetomerge->file_name=$filename;
			$result=$filetomerge->delete_by_file($user);
			if ($result<0) {
				setEventMessages($filetomerge->error, $filetomerge->errors, 'errors');
			}
		}
	}

        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
	// Action submit/delete file/link
	include_once DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';
}

if ($action=='filemerge')
{
	$is_refresh = GETPOST('refresh');
	if (empty($is_refresh)) {
		$filetomerge_file_array = GETPOST('filetoadd');

		$filetomerge_file_array = GETPOST('filetoadd');

		if ($conf->global->MAIN_MULTILANGS) {
			$lang_id = GETPOST('lang_id', 'aZ09');
		}

		// Delete all file already associated
		$filetomerge = new Propalmergepdfproduct($db);

		if ($conf->global->MAIN_MULTILANGS) {
			$result=$filetomerge->delete_by_product($user, $object->id, $lang_id);
		} else {
			$result=$filetomerge->delete_by_product($user, $object->id);
		}

		if ($result<0) {
			setEventMessages($filetomerge->error, $filetomerge->errors, 'errors');
		}

		// for each file checked add it to the product
		if (is_array($filetomerge_file_array)) {
			foreach ($filetomerge_file_array as $filetomerge_file) {
				$filetomerge->fk_product = $object->id;
				$filetomerge->file_name = $filetomerge_file;

				if ($conf->global->MAIN_MULTILANGS) {
					$filetomerge->lang = $lang_id;
				}

				$result=$filetomerge->create($user);
				if ($result<0) {
					setEventMessages($filetomerge->error, $filetomerge->errors, 'errors');
				}
			}
		}
	}
}



/*
 *	View
 */

$form = new Form($db);

$title = $langs->trans($conf->global->OUVRAGE_TYPE.'FICHEOUVRAGE');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);

llxHeader('', $langs->trans($conf->global->OUVRAGE_TYPE.'FICHEOUVRAGE'), '', '', '', '', $morejs, $morecss, 0, 0);
    print load_fiche_titre($langs->trans($conf->global->OUVRAGE_TYPE.'FICHEOUVRAGE'), '', 'title_products.png');


$h = 0;
$head = array();

$head[$h][0] = dol_buildpath("/ouvrage/card.php?id=".$id, 1);
$head[$h][1] = $langs->trans("Card");
$head[$h][2] = 'card';
$h++;

$head[$h][0] = dol_buildpath("/ouvrage/document.php?id=".$id, 1);
$head[$h][1] = $langs->trans("Documents");
$head[$h][2] = 'documents';
$h++;

if ($object->id)
{
	dol_fiche_head($head, 'documents', $titre, -1, $picto);
        
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	// Construit liste des fichiers
        $filearray = array();
        if (file_exists($upload_dir)) {
            try {
                $filearray=dol_dir_list($upload_dir, "files");
            } catch (Exception $ex) {
                $filearray = array();
                var_dump($ex->getMessage());
            }
        }
	 


	$totalsize=0;
	foreach($filearray as $key => $file)
	{
		$totalsize+=$file['size'];
	}
        
        


    $linkback = '<a href="'.DOL_URL_ROOT.'/ouvrage/list.php">'.$langs->trans("BackToList").'</a>';

    $shownav = 1;
    if ($user->societe_id && ! in_array('ouvrage', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav=0;

    print '<div class="fichecenter">';

    print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield" width="100%">';

    print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
    print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.$totalsize.' '.$langs->trans("bytes").'</td></tr>';
    print '</table>';

    print '</div>';
    print '<div style="clear:both"></div>';

    dol_fiche_end();

    $permission = (($object->type == Product::TYPE_PRODUCT && $user->rights->produit->creer) || ($object->type == Product::TYPE_SERVICE && $user->rights->service->creer));
    $param = '&id=' . $object->id;
    include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';


    // Merge propal PDF document PDF files
    if (!empty($conf->global->PRODUIT_PDF_MERGE_PROPAL))
    {
    	$filetomerge = new Propalmergepdfproduct($db);

    	if ($conf->global->MAIN_MULTILANGS) {
    		$lang_id = GETPOST('lang_id', 'aZ09');
    		$result = $filetomerge->fetch_by_product($object->id, $lang_id);
    	} else {
    		$result = $filetomerge->fetch_by_product($object->id);
    	}

    	$form = new Form($db);


    	$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', 'name', SORT_ASC, 1);

    	if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))    // For backward compatiblity, we scan also old dirs
    	{
    		$filearray = array_merge($filearray, dol_dir_list($upload_dirold, "files", 0, '', '\.meta$', 'name', SORT_ASC, 1));
    	}

    	// For each file build select list with PDF extention
    	if (count($filearray) > 0)
    	{
    		print '<br>';
    		// Actual file to merge is :
    		if (count($filetomerge->lines) > 0) {
    			print $langs->trans('PropalMergePdfProductActualFile');
    		}

    		print '<form name="filemerge" action="' . DOL_URL_ROOT . '/product/document.php?id=' . $object->id . '" method="post">';
    		print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    		print '<input type="hidden" name="action" value="filemerge">';
    		if (count($filetomerge->lines) == 0) {
    			print $langs->trans('PropalMergePdfProductChooseFile');
    		}

    		print  '<table class="noborder">';

    		// Get language
    		if ($conf->global->MAIN_MULTILANGS) {
    			$langs->load("languages");

    			print  '<tr class="liste_titre"><td>';

    			$delauft_lang = empty($lang_id) ? $langs->getDefaultLang() : $lang_id;

    			$langs_available = $langs->get_available_languages(DOL_DOCUMENT_ROOT, 12);

			    print Form::selectarray('lang_id', $langs_available, $delauft_lang, 0, 0, 0, '', 0, 0, 0, 'ASC');

    			if ($conf->global->MAIN_MULTILANGS) {
    				print  '<input type="submit" class="button" name="refresh" value="' . $langs->trans('Refresh') . '">';
    			}

    			print  '</td></tr>';
    		}

    		$style = 'impair';
    		foreach ($filearray as $filetoadd)
    		{
    			if ($ext = pathinfo($filetoadd['name'], PATHINFO_EXTENSION) == 'pdf')
    			{
    				if ($style == 'pair') {
    					$style = 'impair';
    				} else {
    					$style = 'pair';
    				}

    				$checked = '';
    				$filename = $filetoadd['name'];

    				if ($conf->global->MAIN_MULTILANGS)
    				{
    					if (array_key_exists($filetoadd['name'] . '_' . $delauft_lang, $filetomerge->lines))
    					{
    						$filename = $filetoadd['name'] . ' - ' . $langs->trans('Language_' . $delauft_lang);
    						$checked = ' checked ';
    					}
    				}
    				else
    				{
    					if (array_key_exists($filetoadd['name'], $filetomerge->lines))
    					{
    						$checked = ' checked ';
    					}
    				}

    				print  '<tr class="' . $style . '"><td>';
    				print  '<input type="checkbox" ' . $checked . ' name="filetoadd[]" id="filetoadd" value="' . $filetoadd['name'] . '">' . $filename . '</input>';
    				print  '</td></tr>';
    			}
    		}

    		print  '<tr><td>';
    		print  '<input type="submit" class="button" name="save" value="' . $langs->trans('Save') . '">';
    		print  '</td></tr>';

    		print  '</table>';

    		print  '</form>';
    	}
    }
}
else
{
	print $langs->trans("ErrorUnknown");
}


llxFooter();
$db->close();
