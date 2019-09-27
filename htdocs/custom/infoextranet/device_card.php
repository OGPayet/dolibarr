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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       device_card.php
 *		\ingroup    infoextranet
 *		\brief      Page to create/edit/view Device
 */

//if (! defined('NOREQUIREUSER'))          define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))            define('NOREQUIREDB','1');
//if (! defined('NOREQUIRESOC'))           define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))          define('NOREQUIRETRAN','1');
//if (! defined('NOSCANGETFORINJECTION'))  define('NOSCANGETFORINJECTION','1');			// Do not check anti CSRF attack test
//if (! defined('NOSCANPOSTFORINJECTION')) define('NOSCANPOSTFORINJECTION','1');		// Do not check anti CSRF attack test
//if (! defined('NOCSRFCHECK'))            define('NOCSRFCHECK','1');			// Do not check anti CSRF attack test done when option MAIN_SECURITY_CSRF_WITH_TOKEN is on.
//if (! defined('NOSTYLECHECK'))           define('NOSTYLECHECK','1');			// Do not check style html tag into posted data
//if (! defined('NOTOKENRENEWAL'))         define('NOTOKENRENEWAL','1');		// Do not check anti POST attack test
//if (! defined('NOREQUIREMENU'))          define('NOREQUIREMENU','1');			// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))          define('NOREQUIREHTML','1');			// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))          define('NOREQUIREAJAX','1');         // Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                define("NOLOGIN",'1');				// If this page is public (can be called outside logged session)

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php');
include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php');
dol_include_once('/infoextranet/class/device.class.php');
dol_include_once('/infoextranet/lib/device.lib.php');
dol_include_once('/infoextranet/class/device.class.php');
dol_include_once('/infoextranet/class/address.class.php');
dol_include_once('/infoextranet/class/role.class.php');
dol_include_once('/infoextranet/lib/output.lib.php');

$conf->use_javascript_ajax = true;

// Load traductions files requiredby by page
$langs->loadLangs(array("infoextranet@infoextranet","other"));

// Get parameters
$id			= GETPOST('id', 'int');
$ref        = GETPOST('ref', 'alpha');
$action		= GETPOST('action', 'alpha');
$cancel     = GETPOST('cancel', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');

// Initialize technical objects
$object=new Device($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction=$conf->infoextranet->dir_output . '/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('devicecard'));     // Note that conf->hooks_modules contains array
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('device');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all",'alpha'));
$search=array();
foreach($object->fields as $key => $val)
{
    if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action='view';

// Security check - Protection if external user
//if ($user->societe_id > 0) access_forbidden();
//if ($user->societe_id > 0) $socid = $user->societe_id;
//$result = restrictedArea($user, 'infoextranet', $id);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals


//TODO Modify Const by another way

$confkeyforautocompletemode = 'COMPANY_USE_SEARCH_TO_SELECT';
$stock = $conf->global->$confkeyforautocompletemode;
$conf->global->$confkeyforautocompletemode = 0;



/*
 * Actions
 *
 * Put here all code to do according to value of "action" parameter
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	$error=0;

	$permissiontoadd = $user->rights->infoextranet->write;
	$permissiontodelete = $user->rights->infoextranet->delete;
	$backurlforlist = dol_buildpath('/infoextranet/device_list.php',1);


	// Hack for date : if not IMP date is provided, use a default one (EPOC)
    if($action == 'add'){
        if(GETPOST('imp_time') === ''){
            $_POST['imp_timeday'] = '01';
            $_POST['imp_timemonth'] = '01';
            $_POST['imp_timeyear'] = '2010';
        }
    }


	// Actions cancel, add, update or delete
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$trigger_name='MYOBJECT_SENTBYMAIL';
	$autocopy='MAIN_MAIL_AUTOCOPY_MYOBJECT_TO';
	$trackid='device'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

}

$addroleid = GETPOST('fk_role', 'int');

// Role add / delete action
if ($action == 'addRole' && !empty($addroleid) && !empty(GETPOST('add')))
{
    $ret = $object->addRole($id, $addroleid);
    if ($ret > 0)
        setEventMessages($langs->trans('RoleAdded'), '', 'mesgs');
    else if ($ret == 0)
        setEventMessages($langs->trans('RoleAlreadyExist'), '', 'errors');
    else
        setEventMessages($langs->trans('RoleNotAdded'), '', 'errors');

    exit(header("Location: ".$_SERVER['PHP_SELF']."?id=".$id));
}

// Role delete action
if ($action == 'deleteRole' && !empty($addroleid))
{
    $ret = $object->deleteRole($id, $addroleid);
    if ($ret > 0)
        setEventMessages($langs->trans('RoleDeleted'), '', 'mesgs');
    else if ($ret == 0)
        setEventMessages($langs->trans('RoleDonotExist'), '', 'errors');
    else
        setEventMessages($langs->trans('RoleNotDeleted'), '', 'errors');

    exit(header("Location: ".$_SERVER['PHP_SELF']."?id=".$id));
}

// Add action (Address to Listing)
if ($action == 'addAddress'){

	$adresseId = GETPOST('fk_ip', 'int');
	$deviceId = GETPOST('deviceid', 'int');

	if($adresseId && $deviceId){

		$adress = new AddressExtra($db);
		$adress->fetch($adresseId);

		if($adress->addAddressToDevice($deviceId)){
			setEventMessages($langs->trans('AddressAdded'), '', 'mesgs');
		}else{
			setEventMessages($langs->trans('AddressNotAdded'), '', 'errors');
		}
		exit(header('Location: '.$_SERVER['PHP_SELF'].'?id='.$deviceId));
	}

}
// Delete action (Address to Listing)
if ($action == 'deleteAddress'){

	$adresseId = GETPOST('addressid', 'int');
	$deviceId = GETPOST('id', 'int');

	if($adresseId && $deviceId){

		$adress = new AddressExtra($db);
		$adress->fetch($adresseId);

		if($adress->deleteAddressFromDevice($deviceId)) {
			setEventMessages($langs->trans('AddressDeleted'), '', 'mesgs');
		} else {
			setEventMessages($langs->trans('AddressNotDeleted'), '', 'errors');
		}
		exit(header('Location: '.$_SERVER['PHP_SELF'].'?id='.$deviceId));
	}
}

// Link that redirect to Address creation card
if ($action == 'createAddress')
{
    $deviceId = GETPOST('id', 'int');
    header("Location: /custom/infoextranet/address_card.php?action=create&backtopage=device_card.php?id=" .$deviceId);
}

// Link that redirect to Thirdpartu creation card
if ($action == 'createTier')
{
    header("Location: /societe/card.php?action=create&backtopage=/custom/infoextranet/device_card.php?action=create");
}


/*
 * View
 *
 * Put here all code to build page
 */

$form=new Form($db);
$formfile=new FormFile($db);

llxHeader('','Device','');

// Part to create
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("NewDevice"));

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
    //Hidden input to define the value of the device type
    print '<input type="hidden" name="device_type" value="basic">';

    dol_fiche_head(array(), '');

	$obj = new Device($db);

	print '<table class="border centpercent">'."\n";
	// Common attributes
	include DOL_DOCUMENT_ROOT . '/custom/infoextranet/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_add.tpl.php';

	print '</table>'."\n";

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
    print '&nbsp; ';
	print '<input type="'.($backtopage?"submit":"button").'" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage?'':' onclick="javascript:history.go(-1)"').'>';	// Cancel for create does not post form if we don't know the backtopage
	print '</div>';

	print '</form>';
}

// Part to edit record or clone
if (($id || $ref) && ($action == 'edit'|| $action == 'clone'))
{
	print load_fiche_titre($langs->trans("Device"));
	if ($action == 'clone')
    {
        $object = $object->cloneUser($user, $id);
    }

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';

	dol_fiche_head();

	print '<table class="border centpercent">'."\n";

	// Common attributes
	include DOL_DOCUMENT_ROOT . '/custom/infoextranet/commonfields_edit.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

	print '</table>';

	dol_fiche_end();

	print '<div class="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';

	print '</form>';
}

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create' && $action != 'clone')))
{
    $res = $object->fetch_optionals($object->id, $extralabels);

	$head = devicePrepareHead($object);
	dol_fiche_head($head, 'card', $langs->trans("Device"), -1, 'device@infoextranet');

	$formconfirm = '';
	// Confirmation to delete
	if ($action == 'delete')
	{
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteDevice'), $langs->trans('ConfirmDeleteDevice'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation of action xxxx
	if ($action == 'xxx')
	{
	    $formquestion=array();
	    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}
	if (! $formconfirm) {
	    $parameters = array('lineid' => $lineid);
	    $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	    if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
	    elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="' .dol_buildpath('/infoextranet/device_list.php',1) . '?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';
    $where = 'device';
    $party  = new Societe($db);
    $party->fetch($object->owner);
    $det = goToThirdparty(1,'','','','', $party->id,  $party->name,  $party->picto,  $where);
    $morehtmlref = '</br><text style="font-size: small">Détenu par :'.$det;'</text>';
    dol_banner_tab_card($object, 'id', $linkback,1, 'rowid', 'name', $morehtmlref,'','','','','1', '');
    $object->fields['owner']['visible'] = 0;


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent">'."\n";

	// Common attributes
	$keyforbreak='garantee_time';
    include DOL_DOCUMENT_ROOT . '/custom/infoextranet/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';

	print '</div>';

	dol_fiche_end();

    print '<div class="fichecenter" style="text-align: right">';

    // Buttons for actions
    if ($action != 'presend' && $action != 'editline') {
        $parameters=array();
        $reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
        if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
        if (empty($reshook))
        {
            // Create new object
            if ($user->rights->infoextranet->write) {
                print '<a class="button" style="background: green" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=create">'.$langs->trans("Nouveau").'</a>'."\n";
            } else {
                print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Nouveau').'</a>'."\n";
            }
            // Modify current object
            if ($user->rights->infoextranet->write) {
                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>'."\n";
            } else {
                print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Modify').'</a>'."\n";
            }
            // Delete current object
            if ($user->rights->infoextranet->delete) {
                print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>'."\n";
            } else {
                print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Delete').'</a>'."\n";
            }
            // Clone current object
            if ($user->rights->infoextranet->clone) {
                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=clone">'.$langs->trans('Cloner').'</a>'."\n";
            } else {
                print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotEnoughPermissions")).'">'.$langs->trans('Cloner').'</a>'."\n";
            }
        }
    }
    print '</div>';
    print '</div>';

    print '<div class="clearboth"></div>';

	// Address listing
    print '<div class="tabBar">';
    print '<div class="DivRow">';

    $addresses = $object->getAllLinkedAddresses();
	$maintain_counter = 0;

	if($addresses) {
		foreach($addresses as $key => $field)
			$maintain_counter++;
	}
    print '<div class="right"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=createAddress&id='.$id.'">Créer une adresse</a></div>';
	print '<div><h2><i class="fa fa-cube"></i> Adresses IP liées ('.$maintain_counter.')</h2></div>';
    print '<table class="table-thirdparty noborder">';
    print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('Type'));
	print_liste_field_titre($langs->trans('Adresse'));
    print_liste_field_titre($langs->trans('Note (Publique)'));
    print_liste_field_titre($langs->trans('Delete'), '', '','', '', 'align="center"');
    print '</tr>';
    if($addresses) {
		foreach($addresses as $key => $field) {
			$address = new AddressExtra($db);
			$address->fetch($field);

			print '<tr>';
			print '<td>'.$address->getAddressTypeName().'</td>';			// Type
			print '<td>'.$address->getNomUrl(1).'</td>';			// Nom
            print '<td>'.$address->getAddressPublicNote().'</td>';			// Note
            print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deleteAddress&addressid='.$address->id.'"><i class="fa fa-trash"></i></a></td>';
			print '</tr>';
		}
	} else {
		print '<tr>';
		print '<td class="opacitymedium">Aucune</td>';
		print '<td class="opacitymedium">Aucune</td>';
		print '<td class="opacitymedium">Aucune</td>';
		print '<td class="opacitymedium" align="center">Aucune</td>';
		print '</tr>';
	}
	print '</table>';

	// Address select and add section
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="action" value="addAddress">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="deviceid" value="'.$object->id.'">';
	$val = array('type'=>'integer:AddressExtra:infoextranet/class/address.class.php', 'label'=>'Device', 'visible'=>1);
	$key = 'fk_ip';
    print '<div class="center">';
	print $object->showInputField($val, $key, '', '', '','','maxwidth200');
	print '<input type="submit" class="butList" name="add" value="'.$langs->trans("AddAddresse").'">';
	print '</div>';
	print '</form>';

	print '</div>';
	print '<div style="clear:both"></div>';


    // Role Listing
    $roles = getRolesOfDevice($id);
    $role_counter = 0;

    if($roles) {
        foreach($roles as $key => $field)
            $role_counter++;
    }
    print '<div><h2><i class="fa fa-sticky-note-o"></i> Rôles liés ('.$role_counter.')</h2></div>';
    print '<table class="table-thirdparty noborder">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Name'));
    print_liste_field_titre($langs->trans('Type'));
    print_liste_field_titre($langs->trans('Delete'), '', '','', '', 'align="center"');
    print '</tr>';
    if ($roles) {
        foreach ($roles as $key => $roleid) {
            $role = new Role($db);
            if ($role->fetch($roleid) > 0) {
                print '<tr>';
                print '<td>' . $role->getNomUrl(1) . '</td>';   // Name
                print '<td>' . $role->showOutputField($role->fields['roletype'], 'roletype', $role->roletype) . '</td>'; // role type
                print '<td align="center"><a class="deleteRole" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=deleteRole&fk_role=' . $roleid . '"><i class="fa fa-trash"></i></a></td>';
                print '</tr>';
            }
        }
    } else {
        print '<tr>';
        print '<td class="opacitymedium">Aucune</td>';
        print '<td class="opacitymedium">Aucune</td>';
        print '<td class="opacitymedium" align="center">Aucune</td>';
        print '</tr>';
    }
    print '</table>';

    // Role select and add section
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="addRole">';
    print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
    print '<input type="hidden" name="id" value="'.$object->id.'">';

    $val = array('type'=>'integer:Role:infoextranet/class/role.class.php', 'label'=>'Role', 'visible'=>1);
    $key = 'fk_role';
    print '<div class="center" style="width: 50%; height: 50%">';
    print $object->showInputField($val, $key, '', '', '','','maxwidth200');
    print '<input type="submit" class="butList" name="add" value="'.$langs->trans("AddRole").'">';
    print '</div>';
    print '</form>';

    print '</div>';

	// Event short listing
	if (GETPOST('modelselected')) {
	    $action = 'presend';
	}
	if ($action != 'presend')
	{
	    print '<div class="fichecenter"><div class="fichehalfright">';
	    print '<a name="builddoc"></a>'; // ancre

	    $MAXEVENT = 10;

	    $morehtmlright = '<a href="'.dol_buildpath('/infoextranet/device_agenda.php', 1).'?id='.$object->id.'">';
	    $morehtmlright.= $langs->trans("SeeAll");
	    $morehtmlright.= '</a>';

	    // List of actions on element
	    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
	    $formactions = new FormActions($db);
	    $somethingshown = $formactions->showactions($object, 'device', $socid, 1, '', $MAXEVENT, '', $morehtmlright);

	    print '</div>';

        print '<div style="clear:both"></div>';

    }
}
// End of page



$conf->global->$confkeyforautocompletemode = $stock;





llxFooter();
$db->close();
