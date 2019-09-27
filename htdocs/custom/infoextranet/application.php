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
require_once 'lib/application.lib.php';
require_once 'class/application.class.php';
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

$addappid = GETPOST('fk_app', 'int');

// Application add / delete action
if ($action == 'addApp' && !empty($addappid) && !empty(GETPOST('add')))
{
    $tmpobj = new Application($db);
    if ($tmpobj->fetch($addappid) > 0) {
        $ret = $tmpobj->addApp($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('AppAdded'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('AppAlreadyExist'), '', 'errors');
        else
            setEventMessages($langs->trans('AppNotAdded'), '', 'errors');
    }

    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

// app delete action
if ($action == 'deleteApp' && !empty($addappid))
{
    $tmpobj = new Application($db);
    if ($tmpobj->fetch($addappid) > 0) {
        $ret = $tmpobj->deleteApp($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('AppDeleted'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('AppDonotExist'), '', 'errors');
        else
            setEventMessages($langs->trans('AppNotDeleted'), '', 'errors');
    }
    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

// app delete action
if ($action == 'deleteAppThirdparty' && !empty($addappid))
{

    $tmpobj = new Application($db);
    if ($tmpobj->fetch($addappid) > 0) {
        $ret = $tmpobj->deleteAppThirdparty($socid);
        if ($ret > 0)
            setEventMessages($langs->trans('AppDeleted'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('AppDonotExist'), '', 'errors');
        else
            setEventMessages($langs->trans('AppNotDeleted'), '', 'errors');
    }
    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

if ($action == 'addAppThirdparty' && !empty($addappid) && !empty(GETPOST('add')))
{

    $tmpobj = new Application($db);
    if ($tmpobj->fetch($addappid) > 0) {
        $ret = $tmpobj->addAppThirdparty($socid);


        if ($ret > 0)
            setEventMessages($langs->trans('AppAdded'), '', 'mesgs');
        else if ($ret == 0)
            setEventMessages($langs->trans('AppAlreadyExist'), '', 'errors');
        else
            setEventMessages($langs->trans('AppNotAdded'), '', 'errors');
    }

    exit(header("Location: ".$_SERVER['PHP_SELF']."?socid=".$socid));
}

if ($action == 'createApplication')
{
    header("Location: /custom/infoextranet/application_card.php?action=create&backtopage=application.php?socid=" . $socid);
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

    dol_fiche_head($head, 'infoExtranetApp', $langs->trans("ThirdParty"), -1, 'company');

    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

    printCustomHeader($socid, $object, $form);

    // Ending
    dol_fiche_end();

    print '<div class="tabBar">';


    print '<div class="right"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'&action=createApplication">Créer une application</a></div>';



    // App owned
    $apps = getAppOfThirdparty($socid);

    print '<div class="mDivRow">';

    $owned_counter = 0;
    if ($apps != NULL) {
        foreach ($apps as $key => $field)
            $owned_counter++;
    }
    print '<div><h2><i class="fa fa-mobile"></i> '.$langs->trans('ApplicationsOwned').'&nbsp;'."(".$owned_counter.")".'</h2></div>';
    print '<table class="table-thirdparty noborder">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Name'));
    print_liste_field_titre($langs->trans('Version'));
    print_liste_field_titre($langs->trans('Environement'));
    print_liste_field_titre($langs->trans('TiersMaintenance'));
    print_liste_field_titre($langs->trans('ContactMaintenance'));
    print_liste_field_titre($langs->trans('Role'), '', '','', '', 'align="center"');
    print_liste_field_titre($langs->trans('UnderContract'), '', '','', '', 'align="center"');
    print_liste_field_titre($langs->trans('Status'), '', '','', '', 'align="center"');
    print_liste_field_titre($langs->trans('Delete'), '', '','', '', 'align="center"');
    print '</tr>';
    if ($apps != NULL) {
        foreach ($apps as $key => $field) {
            $application = new Application($db);
            $application->fetch($field['fk_app']);
            $socmaintenance = new Societe($db);
            $socmaintenance->fetch($application->fk_soc_maintenance);
            $env = getEnvName($application->environment);
            print '<tr>';
            print '<td>' . $application->getNomUrl(1) . '</td>';
            print '<td>' . $application->version . '</td>';
            print '<td>' . $env[0] . '</td>';
            print '<td>' . $socmaintenance->getNomUrl(1) . '</td>';

            // Contact for maintenance
            print '<td style="text-align: left" >';
            $contactid = getContactForMaintenance($application->fk_soc_maintenance);
            $contact = new Contact($db);
            if ($contact->fetch($contactid[0]['fk_socpeople']) > 0)
                print $contact->getNomUrl(1);
            print '</td>';

            // Role
            $roles = getRolesOfApp($application->rowid);
            print '<td align="center">' . printShortenRoles($roles) . '</td>';

            // Under contract
            print '<td align="center">';
            if ($application->under_contract)
                print '<i class="fa fa-check"></i>';
            else
                print '<i class="fa fa-times" style="opacity: 0.5;"></i>';

            print '</td>';

            print '<td align="center">' . $application->getLibStatut(3) . '</td>';

            print '<td align="center"><a class="deleteApp" href="' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id . '&action=deleteApp&fk_app=' . $application->rowid . '"><i class="fa fa-trash"></i></a></td>';
            print '</tr>';
        }
    }
    if (count($apps) == 0) {
        print '<tr><td align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="center" class="opacitymedium">Aucun</td>';
        print '<td  align="center" class="opacitymedium">Aucun</td>';
        print '<td  align="center" class="opacitymedium">Aucun</td>';
        print '<td  align="center" class="opacitymedium">Aucun</td></tr>';
    }

    print '</table>';

    // App select and add section
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="addApp">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="socid" value="'.$object->id.'">';

    $val = array('type'=>'integer:Application:infoextranet/class/application.class.php', 'label'=>'Application', 'visible'=>1);
    $key = 'fk_app';
    print '<div class="center">';
    print $object->showInputField($val, $key, '');
    print '<input type="submit" class="butList" name="add" value="'.$langs->trans("AddApp").'">';
    print '</div>';
    print '</form>';

    print '</div>';
    print '<div style="clear:both"></div>';

    $appsmaintain = getAppMaintainByThirdparty($socid);
    print '<div class="mDivRow">';
    $maintain_counter = 0;
    if ($appsmaintain != NULL) {
        foreach ($appsmaintain as $key => $field)
            $maintain_counter++;
    }
    print '<div><h2><i class="fa fa-mobile"></i> '.$object->name.'&nbsp;'.$langs->trans('ApplicationsMaintain').'&nbsp;'."(".$maintain_counter.")".'</h2></div>';
    print '<table class="table-thirdparty noborder">';
    print '<tr class="liste_titre">';
    print_liste_field_titre($langs->trans('Name'));
    print_liste_field_titre($langs->trans('Version'));
    print_liste_field_titre($langs->trans('Environement'));
    print_liste_field_titre($langs->trans('TiersMaintenance'));
    print_liste_field_titre($langs->trans('ContactMaintenance'));
    print_liste_field_titre($langs->trans('Role'), '', '','', '', 'align="center"');
    print_liste_field_titre($langs->trans('UnderContract'), '', '','', '', 'align="center"');
    print_liste_field_titre($langs->trans('Status'), '', '','', '', 'align="center"');
    print_liste_field_titre($langs->trans('Delete'), '', '','', '', 'align="center"');
    print '</tr>';
    if ($appsmaintain != NULL) {
        foreach ($appsmaintain as $key => $field) {
            $application = new Application($db);
            $application->fetch($field['rowid']);
            $socmaintenance = new Societe($db);
            $socmaintenance->fetch($application->fk_soc_maintenance);
            $env = getEnvName($application->environment);
            print '<tr>';
            print '<td>' . $application->getNomUrl(1) . '</td>';
            print '<td>' . $application->version . '</td>';
            print '<td>' . $env[0] . '</td>';
            print '<td>' . $socmaintenance->getNomUrl(1) . '</td>';

            // Contact for maintenance
            print '<td style="text-align: left">';
            $contactid = getContactForMaintenance($application->fk_soc_maintenance);
            $contact = new Contact($db);
            if ($contact->fetch($contactid[0]['fk_socpeople']) > 0)
                print $contact->getNomUrl(1);
            print '</td>';

            // Role
            $roles = getRolesOfApp($application->rowid);
            print '<td align="center">' . printShortenRoles($roles) . '</td>';

            // Under contract
            print '<td align="center">';
            if ($application->under_contract)
                print '<i class="fa fa-check"></i>';
            else
                print '<i class="fa fa-times" style="opacity: 0.5;"></i>';

            print '</td>';

            print '<td align="center">' . $application->getLibStatut(3) . '</td>';
            print '<td align="center"><a class="deleteAppThirdparty" href="' . $_SERVER["PHP_SELF"] . '?socid=' . $object->id . '&action=deleteAppThirdparty&fk_app=' . $application->rowid . '"><i class="fa fa-trash"></i></a></td>';

            print '</tr>';
        }
    }
    if (count($appsmaintain) == 0) {
        print '<tr><td align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="left" class="opacitymedium">Aucun</td>';
        print '<td  align="center" class="opacitymedium">Aucun</td>';
        print '<td  align="center" class="opacitymedium">Aucun</td>';
        print '<td  align="center" class="opacitymedium">Aucun</td>';
        print '<td  align="center" class="opacitymedium">Aucun</td></tr>';
    }

    print '</table>';
    // App select and add section
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="addAppThirdparty">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="socid" value="'.$object->id.'">';

    $val = array('type'=>'integer:Application:infoextranet/class/application.class.php', 'label'=>'Application', 'visible'=>1);
    $key = 'fk_app';
    print '<div class="center">';
    print $object->showInputField($val, $key, '');
    print '<input type="submit" class="butList" name="add" value="'.$langs->trans("AddApp").'">';
    print '</div>';
    print '</form>';

    print '</div>';
    print '<div style="clear:both"></div>';

}

llxFooter();

$db->close();
