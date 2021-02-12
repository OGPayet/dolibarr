<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2020-2021 Alexis LAURIER <contact@alexislaurier.fr>
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
 *   	\file       sepamandat_card.php
 *		\ingroup    sepamandatmanager
 *		\brief      Page to create/edit/view sepamandat
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
dol_include_once('/sepamandatmanager/class/sepamandat.class.php');
dol_include_once('/sepamandatmanager/lib/sepamandatmanager_sepamandat.lib.php');

// Load translation files required by the page
$langs->loadLangs(array("sepamandatmanager@sepamandatmanager", "other"));

// Get parameters
$id = GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm    = GETPOST('confirm', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'sepamandatcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');
$backtopageforcancel = GETPOST('backtopageforcancel', 'alpha');
//$lineid   = GETPOST('lineid', 'int');

// Initialize technical objects
$object = new Sepamandat($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->sepamandatmanager->dir_output . '/temp/massgeneration/' . $user->id;
$hookmanager->initHooks(array('sepamandatcard', 'globalcard')); // Note that conf->hooks_modules contains array

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

$permissiontoread = $user->rights->sepamandatmanager->sepamandat->read;
$permissiontoadd = $user->rights->sepamandatmanager->sepamandat->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->sepamandatmanager->sepamandat->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->rights->sepamandatmanager->sepamandat->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->sepamandatmanager->sepamandat->write; // Used by the include of actions_dellink.inc.php
$upload_dir = $conf->sepamandatmanager->multidir_output[isset($object->entity) ? $object->entity : 1];
$permissioncreate = $permissiontoadd; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php

$isObjectAbleToBeSent = $object->status == $object::STATUS_SIGNED || $object->status == $object::STATUS_TOSIGN;

if ($object->status != $object::STATUS_DRAFT) {
	$permissiontoadd = false;
	$permissiontodelete = false;
	$permissioncreate = false;
}

if (!$permissiontoread || ($object->id > 0 && !in_array($object->entity, explode(',', getEntity('sepamandat'))))) accessforbidden();

dol_include_once('/sepamandatmanager/class/html.formsepamandate.class.php');
$formSepaMandate = new FormSepaMandate($db);


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/sepamandatmanager/sepamandat_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) $backtopage = $backurlforlist;
			else $backtopage = dol_buildpath('/sepamandatmanager/sepamandat_card.php', 1) . '?id=' . ($id > 0 ? $id : '__ID__');
		}
	}
	$triggermodname = 'SEPAMANDATMANAGER_SEPAMANDAT_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, update_extras, confirm_validate, confirm_delete, confirm_deleteline, confirm_clone, confirm_close, confirm_setdraft, confirm_reopen
	include DOL_DOCUMENT_ROOT . '/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT . '/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT . '/core/actions_printing.inc.php';

	// Action to build doc
	$permissioncreate = $user->rights->sepamandatmanager->sepamandat->read && ($object->status == $object::STATUS_DRAFT || $object->status == $object::STATUS_TOSIGN);	// If you can read, you can build the PDF to read content
	include DOL_DOCUMENT_ROOT . '/core/actions_builddoc.inc.php';

	// Actions to send emails
	$triggersendname = 'SEPAMANDAT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_SEPAMANDAT_TO';
	$trackid = 'sepamandat' . $object->id;
	include DOL_DOCUMENT_ROOT . '/core/actions_sendmails.inc.php';

	$formSepaMandate->manageValidateAction($action, $object, $user->rights->sepamandatmanager->sepamandat->write, $user);
	$formSepaMandate->manageSignedAction($action, $object, $user->rights->sepamandatmanager->sepamandat->write, $user);
	$formSepaMandate->manageStaledAction($action, $object, $user->rights->sepamandatmanager->sepamandat->write, $user);
	$formSepaMandate->manageCanceledAction($action, $object, $user->rights->sepamandatmanager->sepamandat->write, $user);
	$formSepaMandate->manageBackToDraftAction($action, $object, $user->rights->sepamandatmanager->sepamandat->write, $user);
	$formSepaMandate->manageBackToToSignAction($action, $object, $user->rights->sepamandatmanager->sepamandat->write, $user);
	$formSepaMandate->manageBackToSignedAction($action, $object, $user->rights->sepamandatmanager->sepamandat->write, $user);
}




/*
 * View
 *
 * Put here all code to build page
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);

$title = $langs->trans("Sepamandat");
$help_url = '';
llxHeader('', $title, $help_url);

// Part to create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewObject", $langs->transnoentitiesnoconv("Sepamandat")), '', 'object_' . $object->picto);

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

	//dol_set_focus('input[name="ref"]');
}

// Part to edit record
if (($id || $ref) && $action == 'edit') {
	print load_fiche_titre($langs->trans("Sepamandat"), '', 'object_' . $object->picto);

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

	$head = sepamandatPrepareHead($object);
	dol_fiche_head($head, 'card', $langs->trans("Sepamandat"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteSepamandat'), $langs->trans('ConfirmDeleteObject'), 'confirm_delete', '', 0, 1);
	}

	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneAsk', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	$formconfirm = $formSepaMandate->getValidateFormConfirm($formconfirm, $action, $object, $user->rights->sepamandatmanager->sepamandat->write);
	$formconfirm = $formSepaMandate->getSignedFormConfirm($formconfirm, $action, $object, $user->rights->sepamandatmanager->sepamandat->write);
	$formconfirm = $formSepaMandate->getStaledFormConfirm($formconfirm, $action, $object, $user->rights->sepamandatmanager->sepamandat->write);
	$formconfirm = $formSepaMandate->getCanceledFormConfirm($formconfirm, $action, $object, $user->rights->sepamandatmanager->sepamandat->write);
	$formconfirm = $formSepaMandate->getBackToDraftFormConfirm($formconfirm, $action, $object, $user->rights->sepamandatmanager->sepamandat->write);
	$formconfirm = $formSepaMandate->getBackToToSignFormConfirm($formconfirm, $action, $object, $user->rights->sepamandatmanager->sepamandat->write);
	$formconfirm = $formSepaMandate->getBackToSignedFormConfirm($formconfirm, $action, $object, $user->rights->sepamandatmanager->sepamandat->write);

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) $formconfirm .= $hookmanager->resPrint;
	elseif ($reshook > 0) $formconfirm = $hookmanager->resPrint;

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' . dol_buildpath('/sepamandatmanager/sepamandat_list.php', 1) . '?restore_lastsearch_values=1' . (!empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

	$morehtmlref = '<div class="refidno">';
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">' . "\n";

	$object->fields = dol_sort_array($object->fields, 'position');
	$object->fields['iban']['warning'] = implode('<br>', $object->checkIbanValue());
	$object->fields['bic']['warning'] = implode('<br>', $object->checkBicValue());
	$object->fields['bic']['type'] = implode('<br>', $object->checkMandatType());


	foreach ($object->fields as $key => $val) {
		// Discard if extrafield is a hidden field on form
		if (abs($val['visible']) != 1 && abs($val['visible']) != 3 && abs($val['visible']) != 4 && abs($val['visible']) != 5) continue;

		if (array_key_exists('enabled', $val) && isset($val['enabled']) && !verifCond($val['enabled'])) continue; // We don't want this field
		if (in_array($key, array('ref', 'status'))) continue; // Ref and status are already in dol_banner

		$value = $object->$key;

		print '<tr><td';
		print ' class="titlefield fieldname_' . $key;
		//if ($val['notnull'] > 0) print ' fieldrequired';     // No fieldrequired on the view output
		if ($val['type'] == 'text' || $val['type'] == 'html') print ' tdtop';
		print '">';
		print $langs->trans($val['label']);
		if (!empty($val['help'])) print $form->textwithpicto("", $langs->trans($val['help']));
		if (!empty($val['warning'])) print $form->textwithpicto("", $val['warning'], 1, 'warning');
		print '</td>';
		print '<td class="valuefield fieldname_' . $key;
		if ($val['type'] == 'text') print ' wordbreak';
		print '">';

		print $object->showOutputField($val, $key, $value, '', '', '', 0);
		//print dol_escape_htmltag($object->$key, 1, 1);
		print '</td>';
		print '</tr>';
	}
	print '</table>';
	print '</div>';
	print '<div class="fichehalfright">';

	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">';


	//include DOL_DOCUMENT_ROOT . '/core/tpl/commonfields_view.tpl.php';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	dol_fiche_end();

	// Buttons for actions

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="tabsAction">' . "\n";
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

		if (empty($reshook)) {
			// Back to draft
			if ($object->status == $object::STATUS_TOSIGN) {
				if ($user->rights->sepamandatmanager->sepamandat->write) {
					print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=' . FormSepaMandate::SET_BACK_TO_DRAFT_ACTION_NAME . '">' . $langs->trans("SetToDraft") . '</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('SetToDraft') . '</a>' . "\n";
				}
			}

			// Modify
			if ($user->rights->sepamandatmanager->sepamandat->write && $object->status == $object::STATUS_DRAFT) {
				print '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=edit">' . $langs->trans("Modify") . '</a>' . "\n";
			}

			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if ($user->rights->sepamandatmanager->sepamandat->write) {
					print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=' . FormSepaMandate::TO_SIGN_ACTION_NAME . '">' . $langs->trans("SepaMandateValidate") . '</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('SepaMandateValidate') . '</a>' . "\n";
				}
			}

			// Set as signed
			if ($object->status == $object::STATUS_TOSIGN) {
				if ($user->rights->sepamandatmanager->sepamandat->write) {
					print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=' . FormSepaMandate::SIGNED_ACTION_NAME . '">' . $langs->trans("SepaMandateSetSigned") . '</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('SepaMandateSetSigned') . '</a>' . "\n";
				}
			}

			// Set back from signed to tosign
			if ($object->status == $object::STATUS_SIGNED) {
				if ($user->rights->sepamandatmanager->sepamandat->write) {
					print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=' . FormSepaMandate::SET_BACK_TO_TO_SIGN_ACTION_NAME . '">' . $langs->trans("SepaMandateSetUnSign") . '</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('SepaMandateSetUnSign') . '</a>' . "\n";
				}
			}

			// Set as canceled
			if ($object->status == $object::STATUS_TOSIGN || $object->status == $object::STATUS_SIGNED) {
				if ($user->rights->sepamandatmanager->sepamandat->write) {
					print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=' . FormSepaMandate::SET_CANCELED_ACTION_NAME . '">' . $langs->trans("SepaMandateSetCanceled") . '</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('SepaMandateSetCanceled') . '</a>' . "\n";
				}
			}

			// Set as staled
			if ($object->status == $object::STATUS_SIGNED) {
				if ($user->rights->sepamandatmanager->sepamandat->write) {
					print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=' . FormSepaMandate::SET_STALED_ACTION_NAME . '">' . $langs->trans("SepaMandateSetStaled") . '</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('SepaMandateSetStaled') . '</a>' . "\n";
				}
			}

			// Set back from stale to tosign
			if ($object->status == $object::STATUS_STALE) {
				if ($user->rights->sepamandatmanager->sepamandat->write) {
					print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=' . FormSepaMandate::SET_BACK_TO_SIGNED_ACTION_NAME . '">' . $langs->trans("SepaMandateSetUnStale") . '</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('SepaMandateSetUnStale') . '</a>' . "\n";
				}
			}

			// Set back from stale to tosign
			if ($object->status == $object::STATUS_CANCELED) {
				if ($user->rights->sepamandatmanager->sepamandat->write) {
					print '<a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&action=' . FormSepaMandate::SET_BACK_TO_TO_SIGN_ACTION_NAME . '">' . $langs->trans("SepaMandateSetUnCancel") . '</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('SepaMandateSetUnCancel') . '</a>' . "\n";
				}
			}

			// Delete (need delete permission, or if draft, just need create/modify permission)
			if ($object->status == $object::STATUS_DRAFT) {
				if ($user->rights->sepamandatmanager->sepamandat->write) {
					print '<a class="butActionDelete" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=delete">' . $langs->trans('Delete') . '</a>' . "\n";
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $langs->trans('Delete') . '</a>' . "\n";
				}
			}
		}
		print '</div>' . "\n";
	}

	// Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	if ($action != 'presend') {
		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre

		$includedocgeneration = 1;

		// Documents
		if ($includedocgeneration) {
			$objref = dol_sanitizeFileName($object->ref);
			$urlsource = $_SERVER["PHP_SELF"] . "?id=" . $object->id;
			$genallowed = $user->rights->sepamandatmanager->sepamandat->read && ($object->status == $object::STATUS_DRAFT || $object->status == $object::STATUS_TOSIGN);	// If you can read, you can build the PDF to read content
			$delallowed = $user->rights->sepamandatmanager->sepamandat->write && ($object->status == $object::STATUS_DRAFT || $object->status == $object::STATUS_TOSIGN);	// If you can create/edit, you can remove a file on card
			//print $formfile->showdocuments('sepamandatmanager', $object->getRelativePathOfFileToModuleDataRoot(), $object->getAbsolutePath(), $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang);
			print $formfile->showdocuments($genallowed ? 'sepamandatmanager:sepamandat' : 'sepamandatmanager', $object->getRelativePathOfFileToModuleDataRoot(), $object->getAbsolutePath(), $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $langs->defaultlang, '', $object);
		}

		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('sepamandat'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		$MAXEVENT = 30;

		$morehtmlright = '<a href="' . dol_buildpath('/sepamandatmanager/sepamandat_agenda.php', 1) . '?id=' . $object->id . '">';
		$morehtmlright .= $langs->trans("SeeAll");
		$morehtmlright .= '</a>';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, $object->element, null, 1, '', $MAXEVENT, '', $morehtmlright);

		print '</div></div></div>';
	}

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) $action = 'presend';

	// Presend form
	$modelmail = 'sepamandat';
	$defaulttopic = 'InformationMessage';
	$diroutput = $conf->sepamandatmanager->dir_output;
	$trackid = 'sepamandat' . $object->id;

	include DOL_DOCUMENT_ROOT . '/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
