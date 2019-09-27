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
 *	\file       Networkdevice.php
 *	\ingroup    infoextranet
 *	\brief      Home page of NetworkDevice top menu
 */

// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"]))
    $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"] ."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];
$tmp2=realpath(__FILE__);
$i=strlen($tmp)-1;
$j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php"))
    $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php"))
    $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once 'lib/infoextranet.lib.php';
require_once 'lib/output.lib.php';
require_once 'lib/networkdevice.lib.php';
require_once 'class/networkdevice.class.php';
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

$addnetworkdeviceid = GETPOST('fk_networkdevice', 'int');

// NetworkDevice add / delete action
if ($action == 'addNetworkDevice' && !empty($addnetworkdeviceid) && !empty(GETPOST('add')))
{
    $tmpobj = new NetworkDevice($db);
    if ($tmpobj->fetch($addnetworkdeviceid) > 0) {
        $ret = $tmpobj->addNetworkDevice($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('NetworkDeviceAdded'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('NetworkDeviceAlreadyExist'), '', 'errors');
        else
            setEventMessages($langs->trans('NetworkDeviceNotAdded'), '', 'errors');
    }
    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

// NetworkDevice delete action
if ($action == 'deleteNetworkDevice' && !empty($addnetworkdeviceid))
{
    $tmpobj = new NetworkDevice($db);
    if ($tmpobj->fetch($addnetworkdeviceid) > 0) {
        $ret = $tmpobj->deleteNetworkDevice($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('NetworkDeviceDeleted'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('NetworkDeviceDonotExist'), '', 'errors');
        else
            setEventMessages($langs->trans('NetworkDeviceNotDeleted'), '', 'errors');
    }
    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$title=$langs->trans("ThirdParty");

if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',
        $conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$langs->trans('Card');

llxHeader("",$title);

if (!empty($socid))
{
    $head = societe_prepare_head($object);

    dol_fiche_head($head, 'infoExtranetNetworkDevice', $langs->trans("ThirdParty"), -1, 'company');

    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'
        .$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

    printCustomHeader($socid, $object, $form);

    // Ending
    dol_fiche_end();


    // networkdevice maintain
    $networkdevicesmaintain = getNetworkDeviceMaintainByThirdparty($socid);

    print '<div class="mDivRow">';

    print '<div><h2><i class="fa fa-mobile"></i> '.$langs->trans('NetworkDevicesMaintain').'</h2></div>';
    print '<table class="noborder" style="text-align: center">';
    print_liste_field_titre($langs->trans('Name'));
    print_liste_field_titre($langs->trans('Version'));
    print_liste_field_titre($langs->trans('TiersMaintenance'));
    print_liste_field_titre($langs->trans('ContactMaintenance'));
    print_liste_field_titre($langs->trans('UnderContract'));
    print_liste_field_titre($langs->trans('Role'));
    print_liste_field_titre($langs->trans('Status'));
    foreach($networkdevicesmaintain as $key => $field) {
        $networkdevice = new NetworkDevice($db);
        $networkdevice->fetch($field['fk_networkdevice']);
        $socmaintenance = new Societe($db);
        $socmaintenance->fetch($networkdevice->fk_soc_maintenance);
        print '<tr>';
        print '<td>'.$networkdevice->getNomUrl(1).'</td>';
        print '<td>'.$networkdevice->version.'</td>';
        print '<td>'.$socmaintenance->getNomUrl(1).'</td>';

        // Contact for maintenance
        print '<td>';
        $contactid = getContactForMaintenance($networkdevice->fk_soc_maintenance);
        $contact = new Contact($db);
        if ($contact->fetch($contactid[0]['fk_socpeople']) > 0)
            print $contact->getNomUrl(1);
        print '</td>';

        // Under contract
        print '<td>';
        if ($networkdevice->under_contract)
            print '<i class="fa fa-check"></i>';
        else
            print '<i class="fa fa-times" style="opacity: 0.5;"></i>';

        print '</td>';

        print '</tr>';
    }
    if (count($networkdevicesmaintain) == 0)
        print '<tr><td colspan="7" class="opacitymedium">Aucun</td></tr>';

    print '</table>';

    print '</div>';
    print '<div style="clear:both"></div>';

    // NetworkDevice owned
    $networkdevices = getNetworkDeviceOfThirdparty($socid);

    print '<div class="mDivRow">';

    print '<div><h2><i class="fa fa-mobile"></i> '.$langs->trans('NetworkDevicesOwned').'</h2></div>';
    print '<table class="noborder" style="text-align: center">';
    print_liste_field_titre($langs->trans('Name'));
    print_liste_field_titre($langs->trans('Version'));
    print_liste_field_titre($langs->trans('TiersMaintenance'));
    print_liste_field_titre($langs->trans('ContactMaintenance'));
    print_liste_field_titre($langs->trans('UnderContract'));
    print_liste_field_titre($langs->trans('Role'));
    print_liste_field_titre($langs->trans('Status'));
    print_liste_field_titre($langs->trans('Delete'));
    foreach($networkdevices as $key => $field) {
        $networkdevice = new NetworkDevice($db);
        $networkdevice->fetch($field['fk_networkdevice']);
        $socmaintenance = new Societe($db);
        $socmaintenance->fetch($networkdevice->fk_soc_maintenance);
        print '<tr>';
        print '<td>'.$networkdevice->getNomUrl(1).'</td>';
        print '<td>'.$networkdevice->version.'</td>';
        print '<td>'.$socmaintenance->getNomUrl(1).'</td>';

        // Contact for maintenance
        print '<td>';
        $contactid = getContactForMaintenance($networkdevice->fk_soc_maintenance);
        $contact = new Contact($db);
        if ($contact->fetch($contactid[0]['fk_socpeople']) > 0)
            print $contact->getNomUrl(1);
        print '</td>';

        // Under contract
        print '<td>';
        if ($networkdevice->under_contract)
            print '<i class="fa fa-check"></i>';
        else
            print '<i class="fa fa-times" style="opacity: 0.5;"></i>';

        print '</td>';

        print '</tr>';
    }
    if (count($networkdevicess) == 0)
        print '<tr><td colspan="7" class="opacitymedium">Aucun</td></tr>';

    print '</table>';

    // NetworkDevice select and add section
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="addNetworkDevice">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="socid" value="'.$object->id.'">';

    $val = array('type'=>'integer:NetworkDevice:infoextranet/class/networkdevice.class.php', 'label'=>'NetworkDevice', 'visible'=>1);
    $key = 'fk_networkdevice';
    print '<div class="center">';
    print $object->showInputField($val, $key, '');
    print '<input type="submit" class="butAction" name="add" value="'.$langs->trans("AddNetworkDevice").'">';
    print '</div>';
    print '</form>';

    print '</div>';
    print '<div style="clear:both"></div>';

}

llxFooter();

$db->close();
