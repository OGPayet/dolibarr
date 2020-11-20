<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *   	\file       digitalsignaturerequest_card.php
 *		\ingroup    digitalsignaturemanager
 *		\brief      Page to create/edit/view digitalsignaturerequest
 */

//if (! defined('NOREQUIREDB'))              define('NOREQUIREDB','1');					// Do not create database handler $db
//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER','1');				// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC','1');				// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN','1');				// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION','1');		// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION','1');		// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK','1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL','1');				// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK','1');				// Do not check style html tag into posted data
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU','1');				// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML','1');				// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX','1');       	  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN",'1');						// If this page is public (can be called outside logged session). This include the NOIPCHECK too.
//if (! defined('NOIPCHECK'))                define('NOIPCHECK','1');					// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT','auto');					// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE','aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN',1);		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("FORCECSP"))                 define('FORCECSP','none');					// Disable all Content Security Policies


// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
dol_include_once('/digitalsignaturemanager/class/digitalsignaturerequest.class.php');
dol_include_once('/digitalsignaturemanager/lib/digitalsignaturemanager_digitalsignaturerequest.lib.php');
dol_include_once('/digitalsignaturemanager/class/html.formdigitalsignaturerequest.class.php');

// Load translation files required by the page
$langs->loadLangs(array("digitalsignaturemanager@digitalsignaturemanager", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'digitalsignaturerequestcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');

// Initialize technical objects
$object = new DigitalSignatureRequest($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->digitalsignaturemanager->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array('digitalsignaturerequestcard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_' . $key, 'alpha')) $search[$key] = GETPOST('search_' . $key, 'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action = 'view';

// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.


$permissiontoread = $user->rights->digitalsignaturemanager->request->read;
$permissiontoadd = $user->rights->digitalsignaturemanager->request->create; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontoedit = $user->rights->digitalsignaturemanager->request->edit;
$permissiontodelete = $object->isEditable() && ($user->rights->digitalsignaturemanager->request->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT));
$permissionnote = $user->rights->digitalsignaturemanager->request->edit; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->digitalsignaturemanager->request->edit; // Used by the include of actions_dellink.inc.php
$permissioncreate = $permissiontoadd && $object->status == $object::STATUS_DRAFT; //Used by actions_builddoc.inc.php to remove files
$permissionToAddAndDelFiles = $permissioncreate;

$upload_dir = $object->getBaseUploadDir();

if (!$permissiontoread) accessforbidden();


/*
 * Actions
 */

//Action on digitalsignaturedocument
dol_include_once('/digitalsignaturemanager/class/html.formdigitalsignaturedocument.class.php');
$formDigitalSignatureDocument = new FormDigitalSignatureDocument($db);

//Action on digitalsignaturepeople
dol_include_once('/digitalsignaturemanager/class/html.formdigitalsignaturepeople.class.php');
$formDigitalSignaturePeople = new FormDigitalSignaturePeople($db);

//Action on digital signature signatory field
dol_include_once('/digitalsignaturemanager/class/html.formdigitalsignaturesignatoryfield.class.php');
$formDigitalSignatureSignatoryField = new FormDigitalSignatureSignatoryField($db);

//Action on digital signature check box
dol_include_once('/digitalsignaturemanager/class/html.formdigitalsignaturecheckbox.class.php');
$formDigitalSignatureCheckBox = new FormDigitalSignatureCheckBox($db);

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/digitalsignaturemanager/digitalsignaturerequest_list.php', 1);

	if ((empty($backtopage) || ($cancel && empty($id))) && (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__')))) {
		if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
			$backtopage = $backurlforlist;
		}
		else {
			$backtopage = dol_buildpath('/digitalsignaturemanager/digitalsignaturerequest_card.php', 1) . '?id=' . ($id > 0 ? $id : '__ID__');
		}
	}
	$triggermodname = 'DIGITALSIGNATUREMANAGER_DIGITALSIGNATUREREQUEST_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT . '/core/actions_printing.inc.php';

	// Action to build doc
	include DOL_DOCUMENT_ROOT . '/core/actions_builddoc.inc.php';

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, 'DIGITALSIGNATUREREQUEST_MODIFY');
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('projectid', 'int'));
	}

	if ($action == 'setref_client' && $permissiontoedit) {
		$result = $object->setValueFrom('ref_client', GETPOST('ref_client'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'confirm_validateAndCreateRequestToProvider' && $permissioncreate && $confirm == 'yes' && $object->status == $object::STATUS_DRAFT && $permissiontoadd) {
		$result = $object->validateAndCreateRequestOnTheProvider($user);
		if ($result <= 0) {
			setEventMessages($langs->trans('DigitalSignatureManagerErrorWhileCreatingRequestOnProvider'), $object->errors, 'errors');
		}
		else {
			setEventMessages($langs->trans('DigitalSignatureManagerSucessfullyCreatedOnProvider'), array());
		}
	}

	if($action == 'confirm_cancelTransaction' && $permissiontoedit && $confirm == 'yes' && ($object->isInProgress() || $object->status == $object::STATUS_FAILED)) {
		if($object->cancelRequest($user) > 0) {
			setEventMessages($langs->trans('DigitalSignatureManagerSuccessfullyCanceled'), $object->errors);
		}
		else {
			setEventMessages($langs->trans('DigitalSignatureManagerErrorWhileCanceling'), $object->errors, 'errors');
		}
	}

	if($action == 'refreshDataFromProvider' && $object->isInProgress() && $permissiontoread)
	{
		$result = $object->updateDataFromExternalService($user);
		if ($result <= 0) {
			setEventMessages($langs->trans('DigitalSignatureManagerErrorWhileRefreshingData'), $object->errors, 'errors');
		}
		else {
			setEventMessages($langs->trans('DigitalSignatureManagerSuccesfullyRefreshedData'), array());
		}
	}

	//Action to manage delete of digitalsignaturedocument
	if ($permissiontodelete) {
		$formDigitalSignatureDocument->manageDeleteAction($action, $confirm, $user);
	}

	//Action to manage addition of digitalsignaturedocument
	if ($permissiontoadd) {
		$formDigitalSignatureDocument->manageAddAction($action, $object, $user);
	}

	//Action to manage save of digitalsignaturedocument
	if ($permissiontoedit) {
		$formDigitalSignatureDocument->manageSaveAction($action, $object, $user);
	}

	//Action to manage edit of digitalsignaturedocument
	if ($permissiontoedit) {
		$currentEditedDocumentLine = $formDigitalSignatureDocument->getCurrentAskedEditedElementId($action);
	}

	//Action to manage delete of digital signature people
	if ($permissiontodelete) {
		$formDigitalSignaturePeople->manageDeleteAction($object, $action, $confirm, $user);
	}

	//Action to manage addition of digital signature people
	if ($permissiontoadd) {
		$formDigitalSignaturePeople->manageAddAction($action, $object, $user);
		$formDigitalSignaturePeople->manageAddFromContactAction($action, $object, $user);
		$formDigitalSignaturePeople->manageAddFromUserAction($action, $object, $user);
	}

	//Action to manage save of digital signature people
	if ($permissiontoedit) {
		$formDigitalSignaturePeople->manageSaveAction($action, $object, $user);
	}

	//Action to manage edit of digital signature people
	if ($permissiontoedit) {
		$currentPeopleIdEdited = $formDigitalSignaturePeople->getCurrentAskedEditedElementId($action);
	}

	//Action to manage delete of digitalsignaturesignatoryfield
	if ($permissiontodelete) {
		$formDigitalSignatureSignatoryField->manageDeleteAction($object, $action, $confirm, $user);
	}

	//Action to manage addition of digitalsignaturedocument
	if ($permissiontoadd) {
		$formDigitalSignatureSignatoryField->manageAddAction($action, $object, $user);
	}

	//Action to manage save of digitalsignaturedocument
	if ($permissiontoedit) {
		$formDigitalSignatureSignatoryField->manageSaveAction($action, $object, $user);
	}

	//Action to manage edit of digitalsignaturedocument
	if ($permissiontoedit) {
		$currentSignatoryFieldEditedId = $formDigitalSignatureSignatoryField->getCurrentAskedEditedElementId($action);
	}

		//Action to manage delete of digital check box
	if ($permissiontodelete) {
		$formDigitalSignatureCheckBox->manageDeleteAction($object, $action, $confirm, $user);
	}

		//Action to manage addition of digital check box
	if ($permissiontoadd) {
		$formDigitalSignatureCheckBox->manageAddAction($action, $object, $user);
	}

		//Action to manage save of digital check box
	if ($permissiontoedit) {
		$formDigitalSignatureCheckBox->manageSaveAction($action, $object, $user);
	}

		//Action to manage edit of digital check box
	if ($permissiontoedit) {
		$currentCheckBoxEditedId = $formDigitalSignatureCheckBox->getCurrentAskedEditedElementId($action);
	}
}
//we remove errors from actions
$object->errors = array();
// Load object
include DOL_DOCUMENT_ROOT . '/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

if(!empty($object->errors)) {
	setEventMessages('', $object->errors, 'errors');
}
/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formdigitalsignaturerequest = new FormDigitalSignatureRequest($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("DigitalSignatureRequest");
$help_url = '';
llxHeader('', $title, $help_url);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("DigitalSignatureRequestNewObject"), '', 'object_' . $object->picto);

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="add">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

	dol_fiche_head(array(), '');

	// Set some default values
	//if (! GETPOSTISSET('fieldname')) $_POST['fieldname'] = 'myvalue';

	print '<table class="border centpercent tableforfieldcreate">' . "\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

	print '</table>' . "\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="' . dol_escape_htmltag($langs->trans("Create")) . '">';
	print '&nbsp; ';
	print '<input type="' . ($backtopage ? "submit" : "button") . '" class="button" name="cancel" value="' . dol_escape_htmltag($langs->trans("Cancel")) . '"' . ($backtopage ? '' : ' onclick="javascript:history.go(-1)"') . '>'; // Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("DigitalSignatureRequest"), '', 'object_' . $object->picto);

	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="' . $object->id . '">';
	if ($backtopage) print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
	if ($backtopageforcancel) print '<input type="hidden" name="backtopageforcancel" value="' . $backtopageforcancel . '">';

	dol_fiche_head();

	print '<table class="border centpercent tableforfieldedit">' . "\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="save" value="' . $langs->trans("Save") . '">';
	print ' &nbsp; <input type="submit" class="button" name="cancel" value="' . $langs->trans("Cancel") . '">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_optionals();

	$head = digitalsignaturerequestPrepareHead($object);
	dol_fiche_head($head, 'card', $langs->trans("DigitalSignatureRequest"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteDigitalSignatureRequest'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DigitalSignatureManagerConfirmClone'), $langs->trans('DigitalSignatureManagerConfirmCloneDescription', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation to create request on provider side
	if ($action == 'validateAndCreateRequestToProvider' && $permissioncreate) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DigitalSignatureRequestValidate'), $langs->trans('DigitalSignatureRequestValidateDetails'), 'confirm_validateAndCreateRequestToProvider', '', 0, 1);
	}

	//Confirmation to cancel request from opsy
	if ($action == 'cancelTransaction' && $permissiontoedit) {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DigitalSignatureRequestCancelTransactionTitle'), $langs->trans('DigitalSignatureRequestCancelTransactionContent'), 'confirm_cancelTransaction', '', 0, 1);
	}

	//Form confirm for digital signature request
	$formconfirm = $formDigitalSignatureDocument->getDeleteFormConfirm($action, $object, $formconfirm);

	//form confirm for digital signature people
	$formconfirm = $formDigitalSignaturePeople->getDeleteFormConfirm($action, $object, $formconfirm);

	//form confirm for digital signature signatory field
	$formconfirm = $formDigitalSignatureSignatoryField->getDeleteFormConfirm($action, $object, $formconfirm);

	//form confirm for digital signature check box
	$formconfirm = $formDigitalSignatureCheckBox->getDeleteFormConfirm($action, $object, $formconfirm);

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/digitalsignaturemanager/digitalsignaturerequest_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';

	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $permissiontoedit, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $permissiontoedit, 'string', '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>' . $langs->trans('ThirdParty') . ' : ' . (is_object($object->thirdparty) ? $object->thirdparty->getNomUrl(1) : '');
	// Project
	if (!empty($conf->projet->enabled)) {
		$langs->load("projects");
		$morehtmlref .= '<br>' . $langs->trans('Project') . ' ';
		if ($permissiontoadd) {
			$morehtmlref .= ' : ';
			if ($action == 'classify') {
				$morehtmlref .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="' . newToken() . '">';
				$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="' . $langs->trans("Modify") . '">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
			}
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= ': ' . $proj->getNomUrl();
			} else {
				$morehtmlref .= '';
			}
		}
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">' . "\n";

	// Common attributes
	//$keyforbreak='fieldkeytoswitchonsecondcolumn';	// We change column just before this field
	unset($object->fields['fk_project']);				// Hide field already shown in banner
	unset($object->fields['fk_soc']);					// Hide field already shown in banner
	unset($object->fields['ref_client']);					// Hide field already shown in banner

	include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	dol_fiche_end();

	/*
	 * List Of Digital Signature Documents
	 */
	$formdigitalsignaturerequest->showDocumentLines($object, $currentEditedDocumentLine, !$object->isEditable(), $permissionToAddAndDelFiles, $permissionToAddAndDelFiles, $permissionToAddAndDelFiles);

	/*
	*  List Of Digital Signature People
	*/
	$formdigitalsignaturerequest->showPeopleLines($object, $currentPeopleIdEdited, !$object->isEditable(), $permissiontoedit, $permissiontoedit, $permissiontoedit);

	/*
	*  List Of Digital Signature People
	*/
	$formdigitalsignaturerequest->showSignatoryFieldLines($object, $currentSignatoryFieldEditedId, !$object->isEditable(), $permissiontoedit, $permissiontoedit, $permissiontoedit);

	/**
	 * List Of Digital Signature Checkbox
	 */
	$formdigitalsignaturerequest->showCheckBoxLines($object, $currentCheckBoxEditedId, !$object->isEditable(), $permissiontoedit, $permissiontoedit, $permissiontoedit);

	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">' . "\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) {
			// Modify
			if ($permissiontoedit && $object->status == $object::STATUS_DRAFT) {
				print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit">' . $langs->trans("Modify") . '</a>' . "\n";
			} elseif (!$permissiontoedit) {
				print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Modify') . '</a>' . "\n";
			}

			// Validate
			if ($object->status == $object::STATUS_DRAFT && $permissiontoadd) {
					print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=validateAndCreateRequestToProvider">' . $langs->trans("DigitalSignatureRequestValidateButton") . '</a>';
			}

			// Clone
			if ($permissiontoadd) {
				print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&socid=' . $object->socid . '&action=clone&object=digitalsignaturerequest">' . $langs->trans("ToClone") . '</a>' . "\n";
			}

			//Update information from the provider
			if($permissiontoread && ($object->isInProgress() || $object->status == $object::STATUS_FAILED)) {
				print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=refreshDataFromProvider">' . $langs->trans("DigitalSignatureRequestRefreshData") . '</a>';
			}

			//Delete transaction on provider
			if($object->isInProgress()) {
				if ($permissiontoedit) {
					print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=cancelTransaction">' . $langs->trans('DigitalSignatureManagerCancelTransaction') . '</a>' . "\n";
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('DigitalSignatureManagerCancelTransaction') . '</a>' . "\n";
				}
			}
			// Delete (need delete permission, or if draft, just need create/modify permission)
			if($object->isEditable()) {
				if ($permissiontodelete || $object->status == $object::STATUS_DRAFT) {
					print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete">' . $langs->trans('Delete') . '</a>' . "\n";
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Delete') . '</a>' . "\n";
				}
			}
		}
		print '</div>' . "\n";
	}

	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<a name="builddoc"></a>'; // ancre


	$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
	// signed files
	print $formfile->showdocuments('digitalsignaturemanager', $object->getRelativePathForSignedFiles(), $object->getUploadDirOfSignedFiles(), $urlsource, 0, 0, $object->model_pdf, 1, 0, 0, 28, 0, '', $langs->trans('DigitalSignatureRequestListOfSignedFiles'), '', $langs->defaultlang, null, $object, 0);

	// Show links to link elements
	$somethingshown = $form->showLinkedObjectBlock($object, $form->showLinkToObjectBlock($object, null, array('digitalsignaturerequest')));


	print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	$MAXEVENT = 30;

	// List of actions on element
	$somethingshown = $formdigitalsignaturerequest->showActions($object, (is_object($object->thirdparty) ? $object->thirdparty->id : 0), 1, '', $MAXEVENT, '', $morehtmlright);

	print '</div></div></div>';
}

// End of page
llxFooter();
$db->close();
