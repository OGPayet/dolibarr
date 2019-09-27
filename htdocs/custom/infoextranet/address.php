<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	\file       address.php
 *	\ingroup    infoextranet
 *	\brief      Home page of address top menu
 */

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

require_once 'lib/infoextranet.lib.php';
require_once 'lib/output.lib.php';
require_once 'lib/address.lib.php';
require_once 'class/address.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

$langs->loadLangs(array("infoextranet@infoextranet"));

$action=GETPOST('action', 'alpha');


// Securite acces client
if (! $user->rights->infoextranet->read) accessforbidden();
if (! $user->rights->societe->lire) accessforbidden();
$socid=GETPOST('socid','int');

if (isset($user->societe_id) && $user->societe_id > 0)
{
    $action = '';
    $socid = $user->societe_id;
}

$now=dol_now();

$object = new Societe($db);
if (!empty($socid))
    $object->fetch($socid);

/*
 * Actions
 */

$addaddressid = GETPOST('fk_ip', 'int');

// Address add / delete action
if ($action == 'addAddress' && !empty($addaddressid) && !empty(GETPOST('add')))
{
    $tmpobj = new Address($db);
    if ($tmpobj->fetch($addaddressid) > 0) {
        $ret = $tmpobj->addAddress($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('AddressAdded'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('AddressAlreadyExist'), '', 'errors');
        else
            setEventMessages($langs->trans('AddressNotAdded'), '', 'errors');
    }

    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

// Address delete action
if ($action == 'deleteAddress' && !empty($addaddressid))
{
    $tmpobj = new Address($db);
    if ($tmpobj->fetch($addaddressid) > 0) {
        $ret = $tmpobj->deleteAddress($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('AddressDeleted'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('AddressDonotExist'), '', 'errors');
        else
            setEventMessages($langs->trans('AddressNotDeleted'), '', 'errors');
    }
    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$title=$langs->trans("ThirdParty");

if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$langs->trans('Card');

llxHeader("",$title);

if (!empty($socid))
{
    $head = societe_prepare_head($object);

    dol_fiche_head($head, 'infoExtranetAddress', $langs->trans("ThirdParty"), -1, 'company');

    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

    printCustomHeader($socid, $object, $form);

    // Ending
    dol_fiche_end();


    // Address maintain
    $addresssmaintain = getAddressMaintainByThirdparty($socid);

    print '<div class="mDivRow">';

    print '<div><h2><i class="fa fa-mobile"></i> '.$langs->trans('AddresssMaintain').'</h2></div>';
    print '<table class="noborder" style="text-align: center">';
    print_liste_field_titre($langs->trans('Name'));
    print_liste_field_titre($langs->trans('Version'));
    print_liste_field_titre($langs->trans('TiersMaintenance'));
    print_liste_field_titre($langs->trans('ContactMaintenance'));
    print_liste_field_titre($langs->trans('UnderContract'));
    print_liste_field_titre($langs->trans('Role'));
    print_liste_field_titre($langs->trans('Status'));
    foreach($addresssmaintain as $key => $field) {
        $address = new AddressExtra($db);
        $address->fetch($field['fk_ip']);
        $socmaintenance = new Societe($db);
        $socmaintenance->fetch($address->fk_soc_maintenance);
        print '<tr>';
        print '<td>'.$address->getNomUrl(1).'</td>';
        print '<td>'.$address->version.'</td>';
        print '<td>'.$socmaintenance->getNomUrl(1).'</td>';

        // Contact for maintenance
        print '<td>';
        $contactid = getContactForMaintenance($address->fk_soc_maintenance);
        $contact = new Contact($db);
        if ($contact->fetch($contactid[0]['fk_socpeople']) > 0)
            print $contact->getNomUrl(1);
        print '</td>';

        // Under contract
        print '<td>';
        if ($address->under_contract)
            print '<i class="fa fa-check"></i>';
        else
            print '<i class="fa fa-times" style="opacity: 0.5;"></i>';

        print '</td>';

        print '</tr>';
    }
    if (count($addresssmaintain) == 0)
        print '<tr><td colspan="7" class="opacitymedium">Aucun</td></tr>';

    print '</table>';

    print '</div>';
    print '<div style="clear:both"></div>';

    // Address owned
    $addresss = getAddressOfThirdparty($socid);

    print '<div class="mDivRow">';

    print '<div><h2><i class="fa fa-mobile"></i> '.$langs->trans('AddresssOwned').'</h2></div>';
    print '<table class="noborder" style="text-align: center">';
    print_liste_field_titre($langs->trans('Name'));
    print_liste_field_titre($langs->trans('Version'));
    print_liste_field_titre($langs->trans('TiersMaintenance'));
    print_liste_field_titre($langs->trans('ContactMaintenance'));
    print_liste_field_titre($langs->trans('UnderContract'));
    print_liste_field_titre($langs->trans('Role'));
    print_liste_field_titre($langs->trans('Status'));
    print_liste_field_titre($langs->trans('Delete'));
    foreach($addresss as $key => $field) {
        $address = new AddressExtra($db);
        $address->fetch($field['fk_ip']);
        $socmaintenance = new Societe($db);
        $socmaintenance->fetch($address->fk_soc_maintenance);
        print '<tr>';
        print '<td>'.$address->getNomUrl(1).'</td>';
        print '<td>'.$address->version.'</td>';
        print '<td>'.$socmaintenance->getNomUrl(1).'</td>';

        // Contact for maintenance
        print '<td>';
        $contactid = getContactForMaintenance($address->fk_soc_maintenance);
        $contact = new Contact($db);
        if ($contact->fetch($contactid[0]['fk_socpeople']) > 0)
            print $contact->getNomUrl(1);
        print '</td>';

        // Under contract
        print '<td>';
        if ($address->under_contract)
            print '<i class="fa fa-check"></i>';
        else
            print '<i class="fa fa-times" style="opacity: 0.5;"></i>';

        print '</td>';

        print '</tr>';
    }
    if (count($addresss) == 0)
        print '<tr><td colspan="7" class="opacitymedium">Aucun</td></tr>';

    print '</table>';

    // Address select and add section
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="addAddress">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="socid" value="'.$object->id.'">';

    $val = array('type'=>'integer:AddressExtra:infoextranet/class/address.class.php', 'label'=>'Address', 'visible'=>1);
    $key = 'fk_ip';
    print '<div class="center">';
    print $object->showInputField($val, $key, '');
    print '<input type="submit" class="butAction" name="add" value="'.$langs->trans("AddAddress").'">';
    print '</div>';
    print '</form>';

    print '</div>';
    print '<div style="clear:both"></div>';

}

llxFooter();

$db->close();
