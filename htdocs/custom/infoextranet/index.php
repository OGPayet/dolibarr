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

$extrafields = new ExtraFields($db);
$extrafields->fetch_name_optionals_label($object->table_element);

/*
 * Actions
 */

// Get Code 42 extrafields

$sql = "SELECT name, label, type FROM ".MAIN_DB_PREFIX."extrafields WHERE name LIKE 'c42%'";

$resql = $db->query($sql);
$extra = getExtrafields();

if ($action == 'update' && !empty($socid) &&  $user->rights->societe->creer)
{
    $toupdate = array();
    if (!empty(GETPOST('save')))
    {
        foreach ($extra as $key => $field)
        {
            $toupdate[$field['name']] = GETPOST('options_'.$field['name']);
        }
        updateExtrafields($object->id, $toupdate);
        exit(header("Location:".$_SERVER['PHP_SELF'].'?socid='.$socid));
    }
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

    if ($action == 'edit')
    {
        print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'?socid='.$object->id.'" method="post" name="formsoc">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="socid" value="'.$object->id.'">';
    }

    dol_fiche_head($head, 'infoExtranet', $langs->trans("ThirdParty"), -1, 'company');

    $linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

    dol_banner_tab($object, 'socid', $linkback, ($user->societe_id?0:1), 'rowid', 'nom');

    printCustomHeader($socid, $object, $form);

    print '</div class="tabBar">';

    /*
     * Section Outils externes
     */

    print '<div class="tabBar">';
    print '<div class="fichecenter sectionO">';

    print '<div><h2><i class="fa fa-briefcase"></i> '.$langs->trans('TitleO').'</h2></div>';

    // Get section Outils externe
    $extraO = getSectionFromExtrafields($extra, 'c42O_');
    $size = count($extraO);

    // Display section Outils externes
    $lastpos = 0;
    print '<div class="mDivBegin">';

    $i = 0;
    $pair = true;
    foreach ($extraO as $key => $field)
    {
        $url = $object->array_options['options_'.$field['name']];
        if ($action == 'edit')
        {
            if ($i % 3 == 0)
            {
                // For background color
                $pair = !$pair;

                if ($field['type'] != 'text')
                    print '</div><div class="mDivRow '.($pair==true ? 'mPair' : 'mImpair').'">';
                else
                    print '</div><div class="mDivRow mTextDivRow '.($pair==true ? 'mPair' : 'mImpair').'">';
            }

            print '<div class="mDivTd">';
            print '<div class="mTitle">' . $field['label'] . '</div>';
            print $extrafields->showInputField($field['name'], $object->array_options['options_'.$field['name']]);
            print '</div>';

            $i++;
        }
        else
        {
            if (!empty($url) || $field['name'] == 'c42O_zendesk_orga' || $field['name'] == 'c42O_ocs')
            {
                print '<div class="mDivLogoLink '.$field['name'].'">';

                print '<div class="mTitle '.(empty($url) ? 'disabled' : '').'">' . $field['label'] . '</div>';

                // Get software name for logo
                $soft = getBetween($field['name'], "_", "_");

                // Try png, jpg and svg (Logo needs to be formated like logo-name.png, logo-name.jpg or logo-name.svg)
                $logo = 'img/logo-'.$soft.'.png';
                if (!is_file($logo))
                    $logo = 'img/logo-'.$soft.'.jpg';
                if (!is_file($logo))
                    $logo = 'img/logo-'.$soft.'.svg';

                // Get the URL form field
                $url = $object->array_options['options_'.$field['name']];

                // OCS specific case
                if($field['name'] == 'c42O_ocs'){
			if(empty($url)){
						// Get thd CLIxxx customer ID
						if(!empty($object->array_options['options_tiers_numcli'])){
							$cli_id = $object->array_options['options_tiers_numcli'];
							$url = 'https://i.code42.fr/ocsreports/index.php?function=visu_computers&filtre=a.TAG&value='.$cli_id;
						}
					}
				}


                print '<div class="mInput">';
                print '<a href="'.$url.'" target="_blank" class="'.(empty($url) ? 'disabled' : '').'">';
                print '<img src="'.$logo.'" alt="Logo" height="30" width="30" class="'.(empty($url) ? 'disabled' : '').'">';
                print '</a>';
                //print '<div class="mInput">' . mShowOutput($field['name'], $object->array_options['options_'.$field['name']], $extrafields) . '</div>';
                print '</div>';
                print '</div>';
            }
        }
    }

    // Close div begin
    print '</div>';
    // Close div center
    print '</div>';
    print '<div style="clear:both"></div>';
    /* End Section Outils externes */

    /*
     * Section Maintenance
     */



    // Get section Maintenance
    $extraM = getSectionFromExtrafields($extra, 'c42M_');
    $size = count($extraM);

    if ($extraM) {
        if ($action != 'edit'
            && ((!$object->array_options['options_c42M_contract']
            && !$object->array_options['options_c42M_nb_serv_contract']
            && !$object->array_options['options_c42M_nb_post_contract']
            && !$object->array_options['options_c42M_public_note']
            && !$object->array_options['options_c42M_nb_serv_nocontract']
            && !$object->array_options['options_c42M_nb_post_nocontract']) || $object->array_options['options_c42M_nb_post_nocontract'] < 1))
            print '<div class="fichecenter sectionM" style="display: none">';
        else
            print '<div class="fichecenter sectionM">';

        print '<div><h2><i class="fa fa-wrench"></i> ' . $langs->trans('TitleM') . '</h2></div>';

        // Display section Maintenance
        $lastpos = 0;
        print '<div class="mDivBegin">';
        $pair = true;
        foreach ($extraM as $key => $field) {

            if ($field['pos'] != $lastpos && $object->array_options['options_' . $field['name']] != NULL)
            {
                // For background color
                $pair = !$pair;

                if ($field['type'] != 'text')
                    print '</div><div class="mDivRow ' . ($pair == true ? 'mPair' : 'mImpair') . '">';
                else
                    print '</div><div class="mDivRow mTextDivRow ' . ($pair == true ? 'mPair' : 'mImpair') . '">';
            }

            if ($field['type'] != 'text' && ($object->array_options['options_' . $field['name']] != NULL || $action == 'edit'))
                print '<div class="mDivTd ' . $field['name'] . '">';

            if ($object->array_options['options_' . $field['name']] != NULL)
                print '<div class="mTitle">' . $field['label'] . '</div>';
            else if ($action == 'edit')
                print '<div class="mTitle">' . $field['label'] . '</div>';

            if ($action == 'edit') {
                print '<div class="mInput" target="_blank">' . $extrafields->showInputField($field['name'], $object->array_options['options_' . $field['name']]) . '</div>';
            } else if ($object->array_options['options_' . $field['name']] != NULL) {
                print '<div class="mInput" target="_blank">' . mShowOutput($field['name'], $object->array_options['options_' . $field['name']], $extrafields) . '</div>';
            }
            // Close mDivTd
            if ($field['type'] != 'text' && ($object->array_options['options_' . $field['name']] != NULL || $action == 'edit'))
                print '</div>';

            $lastpos = $field['pos'];
        }
        // Close mDivTr
        print '</div>';
        // Close div center
        print '</div>';
        print '<div style="clear:both"></div>';
        /* End Section Maintenance */
    }

    /*
     * Section Poste
     */
    if ($action != 'edit'
        && ((!$object->array_options['options_c42P_contract']
        && !$object->array_options['options_c42P_nb_serv']
        && !$object->array_options['options_c42P_nb_post']
        && !$object->array_options['options_c42P_public_note']
        && !$object->array_options['options_c42P_soft_backup']
        && !$object->array_options['options_c42P_dest_backup']
        && !$object->array_options['options_c42P_dir_backup']
        && !$object->array_options['options_c42P_dir_backup_ext']
        && !$object->array_options['options_c42P_soft_backup_ext']
        && !$object->array_options['options_c42P_volume_backup_ext']) || $object->array_options['options_c42P_contract'] < 1))
        print '<div class="fichecenter sectionP" style="display: none">';
    else
        print '<div class="fichecenter sectionP">';

    print '<div><h2><i class="fa fa-desktop"></i> '.$langs->trans('TitleP').'</h2></div>';

    // Get section Poste
    $extraP = getSectionFromExtrafields($extra, 'c42P_');
    $size = count($extraP);

    // Display section Poste
    $lastpos = 0;
    print '<div class="mDivBegin">';
    $pair = true;
    foreach($extraP as $key => $field)
    {
        if ($field['pos'] != $lastpos && $object->array_options['options_' . $field['name']] != NULL)
        {
            // For background color
            $pair = !$pair;

            if ($field['type'] != 'text')
                print '</div><div class="mDivRow '.($pair==true ? 'mPair' : 'mImpair').'">';
            else
                print '</div><div class="mDivRow mTextDivRow '.($pair==true ? 'mPair' : 'mImpair').'">';
        }

        if ($field['type'] != 'text' && ($object->array_options['options_' . $field['name']] != NULL || $action == 'edit'))
            print '<div class="mDivTd '.$field['name'].'">';

        if ($object->array_options['options_' . $field['name']] != NULL)
            print '<div class="mTitle">' . $field['label'] . '</div>';
        else if ($action == 'edit')
            print '<div class="mTitle">' . $field['label'] . '</div>';

        if ($action == 'edit') {
            print '<div class="mInput" target="_blank">' . $extrafields->showInputField($field['name'], $object->array_options['options_' . $field['name']]) . '</div>';
        } else if ($object->array_options['options_' . $field['name']] != NULL)
            print '<div class="mInput" target="_blank">' . mShowOutput($field['name'], $object->array_options['options_'.$field['name']], $extrafields) . '</div>';

        // Close mDivTd
        if ($field['type'] != 'text' && ($object->array_options['options_' . $field['name']] != NULL || $action == 'edit'))
            print '</div>';

        $lastpos = $field['pos'];
    }
    // Close mDivTr
    print '</div>';
    // Close div center
    print '</div>';
    print '<div style="clear:both"></div>';
    /* End Section Poste */


    //die(var_dump($object->array_options));
    /*
     * Section Réseau
     */
    if ($action != 'edit'
        && ((!$object->array_options['options_c42R_contract']
        && !$object->array_options['options_c42R_vlan_management']
        && !$object->array_options['options_c42R_vpn_ipsec_buro']
        && !$object->array_options['options_c42R_vpn_ssl_cli']
        && !$object->array_options['options_c42R_vpn_ssl_roc']
        && !$object->array_options['options_c42R_vpn_ipsec_roc']
        && !$object->array_options['options_c42R_wifi_site']
        && !$object->array_options['options_c42R_wifi_guest']
        && !$object->array_options['options_c42R_multiple_wan']
        && !$object->array_options['options_c42R_wan1_operator']
        && !$object->array_options['options_c42R_wan2_operator']
        && !$object->array_options['options_c42R_wan1_ip']
        && !$object->array_options['options_c42R_wan2_ip']
        && !$object->array_options['options_c42R_firewall1']
        && !$object->array_options['options_c42R_firewall2']
        && !$object->array_options['options_c42R_acces_point1']
        && !$object->array_options['options_c42R_acces_point2']
        && !$object->array_options['options_c42R_switch1']
        && !$object->array_options['options_c42R_switch2']
        && !$object->array_options['options_c42R_public_note']
        && !$object->array_options['options_c42R_voip_operator']
        && !$object->array_options['options_c42R_voip_details']) || $object->array_options['options_c42R_contract'] < 1))
        print '<div class="fichecenter sectionR" style="display: none">';
    else
        print '<div class="fichecenter sectionR">';

    print '<div><h2><i class="fa fa-wifi"></i> '.$langs->trans('TitleR').'</h2></div>';

    // Get section Réseau
    $extraR = getSectionFromExtrafields($extra, 'c42R_');
    $size = count($extraR);

    // Display section Réseau
    $lastpos = 0;
    print '<div class="mDivBegin">';
    $pair = true;
    foreach($extraR as $key => $field)
    {
        if ($field['pos'] != $lastpos && $object->array_options['options_' . $field['name']] != NULL)
        {
            // For background color
            $pair = !$pair;

            if ($field['type'] != 'text')
                print '</div><div class="mDivRow '.($pair==true ? 'mPair' : 'mImpair').'">';
            else
                print '</div><div class="mDivRow mTextDivRow '.($pair==true ? 'mPair' : 'mImpair').'">';
        }

        if ($field['type'] != 'text' && ($object->array_options['options_' . $field['name']] != NULL || $action == 'edit'))
            print '<div class="mDivTd '.$field['name'].'">';

        if ($object->array_options['options_' . $field['name']] != NULL)
            print '<div class="mTitle">' . $field['label'] . '</div>';
        else if ($action == 'edit')
            print '<div class="mTitle">' . $field['label'] . '</div>';

        if ($action == 'edit')

            print '<div class="mInput" target="_blank">' . $extrafields->showInputField($field['name'], $object->array_options['options_'.$field['name']]) . '</div>';
        else if ($object->array_options['options_' . $field['name']] != NULL)
            print '<div class="mInput" target="_blank">' . mShowOutput($field['name'], $object->array_options['options_'.$field['name']], $extrafields) . '</div>';

        // Close mDivTd
        if ($field['type'] != 'text' && ($object->array_options['options_' . $field['name']] != NULL || $action == 'edit'))
            print '</div>';

        $lastpos = $field['pos'];
    }
    // Close mDivTr
    print '</div>';
    // Close div center
    print '</div>';
    print '<div style="clear:both"></div>';
    /* End Section Réseau */

    /*
     * Section SIhosting
     */
    if ($action != 'edit'
        && ((!$object->array_options['options_c42SI_contract']
        && !$object->array_options['options_c42SI_hebergeur_si']
        && !$object->array_options['options_c42SI_nb_serv']
        && !$object->array_options['options_c42SI_dedicated_vlan']
        && !$object->array_options['options_c42SI_vpn_ssl_roc']
        && !$object->array_options['options_c42SI_vpn_ipsec_roc']
        && !$object->array_options['options_c42SI_websitepanl_url']
        && !$object->array_options['options_c42SI_public_note']) || $object->array_options['options_c42SI_contract'] < 1))
        print '<div class="fichecenter sectionSI" style="display: none">';
    else
        print '<div class="fichecenter sectionSI">';

    print '<div><h2><i class="fa fa-server"></i> '.$langs->trans('TitleSI').'</h2></div>';

    // Get section SIhosting
    $extraSI = getSectionFromExtrafields($extra, 'c42SI_');
    $size = count($extraSI);

    // Display section SIhosting
    $lastpos = 0;
    print '<div class="mDivBegin">';
    $pair = true;
    foreach($extraSI as $key => $field)
    {
        if ($field['pos'] != $lastpos && $object->array_options['options_' . $field['name']] != NULL)
        {
            // For background color
            $pair = !$pair;

            if ($field['type'] != 'text')
                print '</div><div class="mDivRow '.($pair==true ? 'mPair' : 'mImpair').'">';
            else
                print '</div><div class="mDivRow mTextDivRow '.($pair==true ? 'mPair' : 'mImpair').'">';
        }

        if ($field['type'] != 'text' && ($object->array_options['options_' . $field['name']] != NULL || $action == 'edit'))
            print '<div class="mDivTd '.$field['name'].'">';

        if ($object->array_options['options_' . $field['name']] != NULL)
            print '<div class="mTitle">' . $field['label'] . '</div>';
        else if ($action == 'edit')
            print '<div class="mTitle">' . $field['label'] . '</div>';

        if ($action == 'edit')
            print '<div class="mInput" target="_blank">' . $extrafields->showInputField($field['name'], $object->array_options['options_'.$field['name']]) . '</div>';
        else if ($object->array_options['options_' . $field['name']] != NULL)
            print '<div class="mInput" target="_blank">' . mShowOutput($field['name'], $object->array_options['options_'.$field['name']], $extrafields) . '</div>';

        // Close mDivTd
        if ($field['type'] != 'text' && ($object->array_options['options_' . $field['name']] != NULL || $action == 'edit'))
            print '</div>';

        $lastpos = $field['pos'];
    }
    // Close mDivTr
    print '</div>';
    // Close div center
    print '</div>';
    print '<div style="clear:both"></div>';
    /* End Section SIhosting */

    /*
     * Section webhosting
     */


    // Get section webhosting
    $extraH = getSectionFromExtrafields($extra, 'c42H_');
    $size = count($extraH);

    if ($extraH) {

    if ($action != 'edit'
        && ((!$object->array_options['options_c42H_contract']
        && !$object->array_options['options_c42H_nb_domain']
        && !$object->array_options['options_c42H_dns_host']
        && !$object->array_options['options_c42H_admin_dns_url']
        && !$object->array_options['options_c42H_web_host']
        && !$object->array_options['options_c42H_admin_web_url']
        && !$object->array_options['options_c42H_mail_host']
        && !$object->array_options['options_c42H_websitepanl_url']
        && !$object->array_options['options_c42H_nb_exchange']
        && !$object->array_options['options_c42H_nb_pop']
        && !$object->array_options['options_c42H_public_note']) || $object->array_options['options_c42H_contract'] < 1))
        print '<div class="fichecenter sectionH" style="display: none">';
    else
        print '<div class="fichecenter sectionH">';

    print '<div><h2><i class="fa fa-globe"></i> '.$langs->trans('TitleH').'</h2></div>';

    // Display section Webhosting
    $lastpos = 0;
    print '<div class="mDivBegin">';
    $pair = true;
    foreach($extraH as $key => $field)
    {
        if ($field['pos'] != $lastpos && $object->array_options['options_' . $field['name']] != NULL)
        {
            // For background color
            $pair = !$pair;

            if ($field['type'] != 'text')
                print '</div><div class="mDivRow '.($pair==true ? 'mPair' : 'mImpair').'">';
            else
                print '</div><div class="mDivRow mTextDivRow '.($pair==true ? 'mPair' : 'mImpair').'">';
        }

        if ($field['type'] != 'text' && ($object->array_options['options_' . $field['name']] != NULL || $action == 'edit'))
            print '<div class="mDivTd '.$field['name'].'">';

        if ($object->array_options['options_' . $field['name']] != NULL)
            print '<div class="mTitle">' . $field['label'] . '</div>';
        else if ($action == 'edit')
            print '<div class="mTitle">' . $field['label'] . '</div>';

        if ($action == 'edit')
            print '<div class="mInput" target="_blank">' . $extrafields->showInputField($field['name'], $object->array_options['options_'.$field['name']]) . '</div>';
        else if ($object->array_options['options_' . $field['name']] != NULL)
            print '<div class="mInput" target="_blank">' . mShowOutput($field['name'], $object->array_options['options_'.$field['name']], $extrafields) . '</div>';

        // Close mDivTd
        if ($field['type'] != 'text' && ($object->array_options['options_' . $field['name']] != NULL || $action == 'edit'))
            print '</div>';

        $lastpos = $field['pos'];
    }
    // Close mDivTr
    print '</div>';
    // Close div center
    print '</div>';
    print '<div style="clear:both"></div>';
    /* End Section webhosting */
}
    // Ending
    dol_fiche_end();

    if ($action == 'edit')
    {
        print '<div align="center">';
        print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
        print ' &nbsp; &nbsp; ';
        print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
        print '</div>';

        print '</form>';
    }
    else
    {
        if ($user->rights->societe->creer)
        {
            print '<div class="tabsAction">';
            print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'&amp;action=edit">'.$langs->trans('Update').'</a></div>';
            print '</div>';
        }
        else
        {
            print '<div class="tabsAction">';
            print '<div class="inline-block divButAction"><a class="butActionRefused" href="'.$_SERVER['PHP_SELF'].'?socid='.$object->id.'&amp;action=edit">'.$langs->trans('Update').'</a></div>';
            print '</div>';
        }

    }
}

llxFooter();

$db->close();
