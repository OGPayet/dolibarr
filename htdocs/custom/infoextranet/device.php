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
 *	\file       htdocs/infoextranet/template/index.php
 *	\ingroup    infoextranet
 *	\brief      Home page of infoextranet top menu
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
require_once 'lib/device.lib.php';
require_once 'class/device.class.php';
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

$adddeviceid = GETPOST('fk_device', 'int');

// Device add / delete action
if ($action == 'addDevice' && !empty($adddeviceid) && !empty(GETPOST('add')))
{
    $tmpobj = new Device($db);
    if ($tmpobj->fetch($adddeviceid) > 0) {
        $ret = $tmpobj->addDevice($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('DeviceAdded'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('DeviceAlreadyExist'), '', 'errors');
        else
            setEventMessages($langs->trans('DeviceNotAdded'), '', 'errors');
    }

    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

// device delete action
if ($action == 'deleteDevice' && !empty($adddeviceid))
{
    $tmpobj = new Device($db);
    if ($tmpobj->fetch($adddeviceid) > 0) {
        $ret = $tmpobj->deleteDevice($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('DeviceDeleted'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('DeviceDonotExist'), '', 'errors');
        else
            setEventMessages($langs->trans('DeviceNotDeleted'), '', 'errors');
    }
    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}



// app delete action
if ($action == 'deleteDeviceThirdparty' && !empty($adddeviceid))
{
    $tmpobj = new Device($db);
    if ($tmpobj->fetch($adddeviceid) > 0) {
        $ret = $tmpobj->deleteDeviceThirdparty($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('DeviceDeleted'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('DeviceDonotExist'), '', 'errors');
        else
            setEventMessages($langs->trans('DeviceNotDeleted'), '', 'errors');
    }
    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

if ($action == 'addDeviceThirdparty' && !empty($adddeviceid) && !empty(GETPOST('add')))
{

    $tmpobj = new Device($db);
    if ($tmpobj->fetch($adddeviceid) > 0) {
        $ret = $tmpobj->addDeviceThirdparty($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('DeviceAdded'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('DeviceAlreadyExist'), '', 'errors');
        else
            setEventMessages($langs->trans('DeviceNotAdded'), '', 'errors');
    }

    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

// app delete action on contract
if ($action == 'deleteDeviceOnContractByThirdparty' && !empty($adddeviceid))
{
    $tmpobj = new Device($db);
    if ($tmpobj->fetch($adddeviceid) > 0) {
        $ret = $tmpobj->deleteDeviceOnContractByThirdparty($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('DeviceDeleted'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('DeviceDonotExist'), '', 'errors');
        else
            setEventMessages($langs->trans('DeviceNotDeleted'), '', 'errors');
    }
    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

if ($action == 'addDeviceOnContractByThirdparty' && !empty($adddeviceid) && !empty(GETPOST('add')))
{

    $tmpobj = new Device($db);
    if ($tmpobj->fetch($adddeviceid) > 0) {
        $ret = $tmpobj->addDeviceOnContractByThirdparty($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('DeviceAdded'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('DeviceAlreadyExist'), '', 'errors');
        else
            setEventMessages($langs->trans('DeviceNotAdded'), '', 'errors');
    }

    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

if ($action == 'createDevice')
{
    header("Location: /custom/infoextranet/device_card.php?action=create&backtopage=device.php?socid=" . $socid);
}

//// Device add / delete action
//if ($action == 'filter' && && !empty($check) && !empty(GETPOST('filter')))
//{
//    $tmpobj = new Device($db);
//    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
//}

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

    dol_fiche_head($head, 'infoExtranetDevice', $langs->trans("ThirdParty"), -1, 'company');

    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

    printCustomHeader($socid, $object, $form);

    // Ending
    dol_fiche_end();

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    print '<div class="tabBar">';

    print '<div class="right"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&action=createDevice">Créer un équipement</a></div>';


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Device ON CONTRACT
    $devicesmaintain = getDeviceOnContractByThirdparty($socid);

   // print '<div class="mDivRow">';
    $maintain_counter = 0;
    if ($devicesmaintain != null) {
        foreach ($devicesmaintain as $key => $field)
            $maintain_counter++;
    }
    print '<div><h2><i class="fa fa-cube"></i> '.$langs->trans('DevicesOnContract').'&nbsp;'."(".$maintain_counter.")".'</h2></div>';
    print '<table class="table-thirdparty noborder">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Name'));
    print_liste_field_titre($langs->trans('Type'));
    print_liste_field_titre($langs->trans('TiersMaintenance'));
    print_liste_field_titre($langs->trans('ContactMaintenance'));
    print_liste_field_titre($langs->trans('UnderContract'), '', '','', '', 'align="center"');
    print_liste_field_titre($langs->trans('Delete'), '', '','', '', 'align="center"');
    print '</tr>';

    if ($devicesmaintain != null) {
        foreach ($devicesmaintain as $key => $field) {
            $device = new Device($db);
            $device->fetch($field['rowid']);
            $socmaintenance = new Societe($db);
            $socmaintenance->fetch($device->fk_soc_maintenance);
            $type = getTypeName($device->types);
            print '<tr>';
            print '<td align="left">' . $device->getNomUrl(1) . '</td>';
            print '<td align="left">' . $type[0] . '</td>';
            print '<td align="left">' . $socmaintenance->getNomUrl(1) . '</td>';

            // Contact for maintenance
            print '<td align="left">';
            $contactid = getContactForMaintenance($device->fk_soc_maintenance);
            $contact = new Contact($db);
            if ($contact->fetch($contactid[0]['fk_socpeople']) > 0)
                print $contact->getNomUrl(1);
            print '</td>';

            // Under contract
            print '<td align="center">';
            if ($device->under_contract)
                print '<i class="fa fa-check"></i>';
            else
                print '<i class="fa fa-times" style="opacity: 0.5;"></i>';

            print '</td>';
            print '<td align="center"><a class="deleteDeviceOnContractByThirdparty" href="' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id . '&action=deleteDeviceOnContractByThirdparty&fk_device=' . $device->rowid . '"><i class="fa fa-trash"></i></a></td>';
            print '</tr>';
        }
    }
    if (count($devicesmaintain) == 0) {
        print '<tr><td align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="center" class="opacitymedium">Aucun</td>';
        print '<td  align="center" class="opacitymedium">Aucun</td></tr>';
    }

    print '</table>';

    // Device select and add section
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="addDeviceOnContractByThirdparty">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="socid" value="'.$object->id.'">';

    $val = array('type'=>'integer:Device:infoextranet/class/device.class.php', 'label'=>'Device', 'visible'=>1);
    $key = 'fk_device';
    print '<div class="center">';
    print $object->showInputField($val, $key, '');
    print '<input type="submit" class="butList" name="add" value="'.$langs->trans("AddDevice").'">';
    print '</div>';
    print '</form>';

  //  print '</div>';
    print '<div style="clear:both"></div>';


    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // Device owned
    $devices = getDeviceOfThirdparty($socid);
   // die(var_dump($devices));

    //print '<div class="mDivRow">';
    $owned_counter = 0;

    if ($devices != null) {
        foreach ($devices as $key => $field)
            $owned_counter++;
    }
    print '<div><h2><i class="fa fa-cube"></i> '.$langs->trans('DevicesOwned').'&nbsp;'."(".$owned_counter.")".'</h2>';
    print'</div>';
    print '<div style="clear:both"></div>';


    // Device select a type to display

    print '<table class="table-thirdparty noborder">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Name'));
    print_liste_field_titre($langs->trans('Type'));
    print_liste_field_titre($langs->trans('TiersMaintenance'));
    print_liste_field_titre($langs->trans('ContactMaintenance'));
    print_liste_field_titre($langs->trans('UnderContract'), '', '','', '', 'align="center"');
    print_liste_field_titre($langs->trans('Delete'), '', '','', '', 'align="center"');
    print '</tr>';
    if ($devices != null) {
        foreach ($devices as $key => $field) {
            $device = new Device($db);
            $device->fetch($field['rowid']);
            $socmaintenance = new Societe($db);
            $socmaintenance->fetch($device->fk_soc_maintenance);
            $type = getTypeName($device->types);
            if ($check == $device->types || $check == 0) {
                print '<tr>';
                print '<td align="left">' . $device->getNomUrl(1) . '</td>';
                print '<td align="left">' . $type[0] . '</td>';
                print '<td align="left">' . $socmaintenance->getNomUrl(1) . '</td>';

                // Contact for maintenance
                print '<td align="left">';
                $contactid = getContactForMaintenance($device->fk_soc_maintenance);
                $contact = new Contact($db);
                if ($contact->fetch($contactid[0]['fk_socpeople']) > 0)
                    print $contact->getNomUrl(1);
                print '</td>';

                // Under contract
                print '<td align="center">';
                if ($device->under_contract)
                    print '<i class="fa fa-check"></i>';
                else
                    print '<i class="fa fa-times" style="opacity: 0.5;"></i>';
                print '</td>';
                print '<td align="center"><a class="deleteDevice" href="' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id . '&action=deleteDevice&fk_device=' . $device->rowid . '"><i class="fa fa-trash"></i></a></td>';
                print '</tr>';
            }
        }
    }
    if (count($devices) == 0) {
        print '<tr><td align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="center" class="opacitymedium">Aucun</td>';
        print '<td  align="center" class="opacitymedium">Aucun</td></tr>';
    }
    print '</table>';

    // Device select and add section
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="addDevice">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="socid" value="'.$object->id.'">';

    $val = array('type'=>'integer:Device:infoextranet/class/device.class.php', 'label'=>'Device', 'visible'=>1);
    $key = 'fk_device';
    print '<div class="center">';
    print $object->showInputField($val, $key, '');
    print '<input type="submit" class="butList" name="add" value="'.$langs->trans("AddDevice").'">';
    print '</div>';
    print '</form>';

   // print '</div>';
    print '<div style="clear:both"></div>';

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Device maintain
        $devicesmaintain = getDeviceMaintainByThirdparty($socid);

       // print '<div class="mDivRow">';


        $maintain_counter = 0;
        if ($devicesmaintain != null) {
            foreach ($devicesmaintain as $key => $field)
                $maintain_counter++;
        }
        print '<div><h2><i class="fa fa-cube"></i> '.$object->name.'&nbsp;'.$langs->trans('DevicesMaintain').'&nbsp;'."(".$maintain_counter.")".'</h2></div>';
        print '<table class="table-thirdparty noborder">';
        print '<tr class="liste_titre">';
        print_liste_field_titre($langs->trans('Name'));
        print_liste_field_titre($langs->trans('Type'));
        print_liste_field_titre($langs->trans('TiersMaintenance'));
        print_liste_field_titre($langs->trans('ContactMaintenance'));
        print_liste_field_titre($langs->trans('UnderContract'), '', '','', '', 'align="center"');
        print_liste_field_titre($langs->trans('Delete'), '', '','', '', 'align="center"');
        print '</tr>';

        if ($devicesmaintain != null) {

            foreach ($devicesmaintain as $key => $field) {
                $device = new Device($db);
                $device->fetch($field['rowid']);
                $socmaintenance = new Societe($db);
                $socmaintenance->fetch($device->fk_soc_maintenance);
                $type = getTypeName($device->types);
                print '<tr>';
                print '<td align="left">' . $device->getNomUrl(1) . '</td>';
                print '<td align="left">' . $type[0] . '</td>';
                print '<td align="left">' . $socmaintenance->getNomUrl(1) . '</td>';

            // Contact for maintenance
                print '<td align="left">';
                $contactid = getContactForMaintenance($device->fk_soc_maintenance);
                $contact = new Contact($db);
                if ($contact->fetch($contactid[0]['fk_socpeople']) > 0)
                    print $contact->getNomUrl(1);
                print '</td>';

            // Under contract
                print '<td align="center">';
                if ($device->under_contract)
                    print '<i class="fa fa-check"></i>';
                else
                    print '<i class="fa fa-times" style="opacity: 0.5;"></i>';

                print '</td>';
                print '<td align="center"><a class="deleteDeviceThirdparty" href="' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id . '&action=deleteDeviceThirdparty&fk_device=' . $device->rowid . '"><i class="fa fa-trash"></i></a></td>';
                print '</tr>';
            }
        }
        if (count($devicesmaintain) == 0) {
            print '<tr><td align="left" class="opacitymedium">Aucun</td>';
            print '<td  align="left" class="opacitymedium">Aucun</td>';
            print '<td  align="left" class="opacitymedium">Aucun</td>';
            print '<td  align="left" class="opacitymedium">Aucun</td>';
            print '<td  align="center" class="opacitymedium">Aucun</td>';
            print '<td  align="center" class="opacitymedium">Aucun</td></tr>';
        }
        print '</table>';

        // Device select and add section
        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
        print '<input type="hidden" name="action" value="addDeviceThirdparty">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="socid" value="'.$object->id.'">';

        $val = array('type'=>'integer:Device:infoextranet/class/device.class.php', 'label'=>'Device', 'visible'=>1);
        $key = 'fk_device';
        print '<div class="center">';
        print $object->showInputField($val, $key, '');
        print '<input type="submit" class="butList" name="add" value="'.$langs->trans("AddDevice").'">';
        print '</div>';
        print '</form>';

       // print '</div>';
        print '<div style="clear:both"></div>';

    llxFooter();

    $db->close();
}
