<?php
/*
 * Copyright (C) - 2013-2016    Jean-François FERRY    <jfefe@aternatik.fr>
 *                    2016            Christophe Battarel <christophe@altairis.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *     Card of ticket
 *
 *    @package ticketsup
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res = 0;
if (file_exists("../main.inc.php")) {
    $res = include "../main.inc.php"; // From htdocs directory
} elseif (!$res && file_exists("../../main.inc.php")) {
    $res = include "../../main.inc.php"; // From "custom" directory
} else {
    die("Include of main fails");
}

require_once 'class/actions_ticketsup.class.php';
require_once 'class/html.formticketsup.class.php';
require_once 'lib/ticketsup.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
if (!empty($conf->projet->enabled)) {
    include DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
    include_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
}
if (!empty($conf->contrat->enabled)) {
    include_once DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php';
    include_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formcontract.class.php';
}

if (!class_exists('Contact')) {
    include DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
}

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");
$langs->load("ticketsup@ticketsup");

// Get parameters
$id = GETPOST('id', 'int');
$track_id = GETPOST('track_id', 'alpha', 3);
$action = GETPOST('action', 'alpha', 3);
$ref = GETPOST('ref', 'alpha');
$projectid = GETPOST('projectid', 'int');

$object = new ActionsTicketsup($db);
$object->doActions($action);

$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($object->dao->table_element);

if (!$action) {
    $action = 'view';
}
//Select mail models is same action as add_message
if (GETPOST('modelselected')) {
    $action = 'add_message';
}

// Store current page url
$url_page_current = dol_buildpath('/ticketsup/card.php', 1);

/***************************************************
 * PAGE
 *
 ****************************************************/

$userstat = new User($db);
$form = new Form($db);
$formticket = new FormTicketsup($db);

if ($action == 'view' || $action == 'add_message' || $action == 'close' || $action == 'delete' || $action == 'editcustomer' || $action == 'progression' || $action == 'reopen' || $action == 'editsubject' || $action == 'edit_extrafields' || $action == 'set_extrafields' || $action == 'classify' || $action == 'sel_contract' || $action == 'edit_message_init' || $action == 'set_status' || $action == 'dellink') {
    $res = $object->fetch($id, $track_id, $ref);

    if ($res > 0) {
        // Security check
        $result = restrictedArea($user, 'ticketsup', $object->dao->id);

        // or for unauthorized internals users
        if (!$user->societe_id && ($conf->global->TICKETS_LIMIT_VIEW_ASSIGNED_ONLY && $object->dao->fk_user_assign != $user->id) && !$user->rights->ticketsup->manage) {
            accessforbidden('', 0);
        }


        $permissiondellink = $user->rights->ticketsup->write;
        include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';        // Must be include, not include_once


        $help_url = 'FR:DocumentationModuleTicket';
        $page_title = $object->getTitle($action);
        llxHeader('', $page_title, $help_url);

        // Confirmation close
        if ($action == 'close') {
            print $form->formconfirm($url_page_current . "?track_id=" . $track_id, $langs->trans("CloseATicket"), $langs->trans("ConfirmCloseAticket"), "confirm_close", '', '', 1);
            if ($ret == 'html') {
                print '<br>';
            }
        }
        // Confirmation delete
        if ($action == 'delete') {
            print $form->formconfirm($url_page_current . "?track_id=" . $track_id, $langs->trans("Delete"), $langs->trans("ConfirmDeleteTicket"), "confirm_delete_ticket", '', '', 1);
        }
        // Confirm reopen
        if ($action == 'reopen') {
            print $form->formconfirm($url_page_current . '?track_id=' . $track_id, $langs->trans('ReOpen'), $langs->trans('ConfirmReOpenTicket'), 'confirm_reopen', '', '', 1);
        }
        // Confirmation status change
        if ($action == 'set_status') {
            $new_status = GETPOST('new_status');
            print $form->formconfirm($url_page_current . "?track_id=" . $track_id . "&new_status=" . GETPOST('new_status'), $langs->trans("TicketChangeStatus"), $langs->trans("TicketConfirmChangeStatus", $langs->transnoentities($object->dao->statuts_short[$new_status])), "confirm_set_status", '', '', 1);
        }

        // project info
        if ($projectid) {
            $projectstat = new Project($db);
            if ($projectstat->fetch($projectid) > 0) {
                $projectstat->fetch_thirdparty();

                // To verify role of users
                //$userAccess = $object->restrictedProjectArea($user,'read');
                $userWrite = $projectstat->restrictedProjectArea($user, 'write');
                //$userDelete = $object->restrictedProjectArea($user,'delete');
                //print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;

                $head = project_prepare_head($projectstat);
                dol_fiche_head($head, 'ticketsup', $langs->trans("Project"), 0, ($projectstat->public ? 'projectpub' : 'project'));

                /*
                 *   Projet synthese pour rappel
                 */
                print '<table class="border" width="100%">';

                $linkback = '<a href="' . DOL_URL_ROOT . '/projet/list.php">' . $langs->trans("BackToList") . '</a>';

                // Ref
                print '<tr><td width="30%">' . $langs->trans('Ref') . '</td><td colspan="3">';
                // Define a complementary filter for search of next/prev ref.
                if (!$user->rights->projet->all->lire) {
                    $objectsListId = $projectstat->getProjectsAuthorizedForUser($user, $mine, 0);
                    $projectstat->next_prev_filter = " rowid in (" . (count($objectsListId) ? join(',', array_keys($objectsListId)) : '0') . ")";
                }
                print $form->showrefnav($projectstat, 'ref', $linkback, 1, 'ref', 'ref', '');
                print '</td></tr>';

                // Label
                print '<tr><td>' . $langs->trans("Label") . '</td><td>' . $projectstat->title . '</td></tr>';

                // Customer
                print "<tr><td>" . $langs->trans("ThirdParty") . "</td>";
                print '<td colspan="3">';
                if ($projectstat->thirdparty->id > 0) {
                    print $projectstat->thirdparty->getNomUrl(1);
                } else {
                    print '&nbsp;';
                }

                print '</td></tr>';

                // Visibility
                print '<tr><td>' . $langs->trans("Visibility") . '</td><td>';
                if ($projectstat->public) {
                    print $langs->trans('SharedProject');
                } else {
                    print $langs->trans('PrivateProject');
                }

                print '</td></tr>';

                // Statut
                print '<tr><td>' . $langs->trans("Status") . '</td><td>' . $projectstat->getLibStatut(4) . '</td></tr>';

                print "</table>";

                print '</div>';
            } else {
                print "ErrorRecordNotFound";
            }
        } elseif ($object->dao->fk_soc > 0) {
            $object->dao->fetch_thirdparty();
            $head = societe_prepare_head($object->dao->thirdparty);
            dol_fiche_head($head, 'ticketsup', $langs->trans("ThirdParty"), 0, 'company');
            dol_banner_tab($object->dao->thirdparty, 'socid', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom');
            dol_fiche_end();
        }

        if (!$user->societe_id && $conf->global->TICKETS_LIMIT_VIEW_ASSIGNED_ONLY) {
            $object->dao->next_prev_filter = "te.fk_user_assign = '" . $user->id . "'";
        } elseif ($user->societe_id > 0) {
            $object->dao->next_prev_filter = "te.fk_soc = '" . $user->societe_id . "'";
        }

        $head = ticketsup_prepare_head($object->dao);
        dol_fiche_head($head, 'tabTicketsup', $langs->trans("Ticket"), 0, 'ticketsup@ticketsup');
        $object->dao->label = $object->dao->ref;
        // Author
        if ($object->dao->fk_user_create > 0) {
            $object->dao->label .= ' - ' . $langs->trans("CreatedBy") . '  ';

            $langs->load("users");
            $fuser = new User($db);
            $fuser->fetch($object->dao->fk_user_create);
            $object->dao->label .= $fuser->getNomUrl(0);
        }
        if (!empty($object->dao->origin_email)) {
            $object->dao->label .= ' - ' . $langs->trans("CreatedBy") . ' ';
            $object->dao->label .= $object->dao->origin_email . ' <small>(' . $langs->trans("TicketEmailOriginIssuer") . ')</small>';
        }
        $linkback = '<a href="' . dol_buildpath('/ticketsup/list.php', 1) . '"><strong>' . $langs->trans("BackToList") . '</strong></a> ';
        $object->dao->ticketsup_banner_tab('ref', '', ($user->societe_id ? 0 : 1), 'ref', 'subject', '', '', '', $morehtmlleft, $linkback);

        print '<div class="fichecenter"><div class="fichehalfleft">';
        print '<div class="underbanner clearboth"></div>';
        print '<table class="border" style="width:100%">';

        // Track ID
        print '<tr><td>' . $langs->trans("TicketTrackId") . '</td><td>';
        if (!empty($object->dao->track_id)) {
            if (empty($object->dao->ref)) {
                $object->ref = $object->dao->id;
                print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'track_id');
            } else {
                print $object->dao->track_id;
            }
        } else {
            print $langs->trans('None');
        }
        print '</td></tr>';

        // Subject
        print '<tr><td>';
        print $form->editfieldkey("Subject", 'subject', $object->dao->subject, $object->dao, $user->rights->ticketsup->write && !$user->societe_id, 'string');
        print '</td><td>';
        print $form->editfieldval("Subject", 'subject', $object->dao->subject, $object->dao, $user->rights->ticketsup->write && !$user->societe_id, 'string');
        print '</td></tr>';

        // Creation date
        print '<tr><td>' . $langs->trans("DateCreation") . '</td><td>';
        print dol_print_date($object->dao->datec, 'dayhour');
        print '</td></tr>';

        // Read date
        if (!empty($object->dao->date_read)) {
            print '<tr><td>' . $langs->trans("TicketReadOn") . '</td><td>';
            print dol_print_date($object->dao->date_read, 'dayhour');
            print '</td></tr>';

            print '<tr><td>' . $langs->trans("TicketTimeToRead") . '</td><td>';
            print '<strong>' . convertSecondToTime($object->dao->date_read - $object->dao->datec) . '</strong>';
            print '</td></tr>';
        }

        // Close date
        if (!empty($object->dao->date_close)) {
            print '<tr><td>' . $langs->trans("TicketCloseOn") . '</td><td>';
            print dol_print_date($object->dao->date_close, 'dayhour');
            print '</td></tr>';
        }

        print '</td></tr>';

        // Customer
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td>';
        print $langs->trans('Customer');
        print '</td>';
        if ($action != 'editcustomer' && $object->dao->fk_statut < 8 && !$user->societe_id && $user->rights->ticketsup->write) {
            print '<td align="right"><a href="' . $url_page_current . '?action=editcustomer&amp;track_id=' . $object->dao->track_id . '">' . img_edit($langs->transnoentitiesnoconv('Edit'), 1) . '</a></td>';
        }
        print '</tr></table>';
        print '</td><td colspan="3">';

        if ($action == 'editcustomer') {
            $form->form_thirdparty($url_page_current . '?track_id=' . $object->dao->track_id, $object->dao->fk_soc, 'editcustomer', ($object->dao->fk_soc ? 's.rowid <> ' . $object->dao->fk_soc : ''), 1);
        } else {
            $form->form_thirdparty($url_page_current . '?track_id=' . $object->dao->track_id, $object->dao->fk_soc, 'none', 's.rowid <> ' . $object->dao->fk_soc, 1);
        }
        print '</td></tr>';

        // Project
        if (!empty($conf->projet->enabled)) {
            $langs->load('projects');
            print '<tr><td height="10">';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('Project');
            print '</td>';
            if ($action != 'classify' && $user->rights->ticketsup->write) {
                print '<td align="right"><a href="' . $url_page_current . '?action=classify&amp;track_id=' . $object->dao->track_id . '">' . img_edit($langs->trans('SetProject')) . '</a></td>';
            }

            print '</tr></table>';
            print '</td><td colspan="3">';
            if ($action == 'classify') {
                $form->form_project($url_page_current . '?track_id=' . $object->dao->track_id, $object->dao->socid, $object->dao->fk_project, 'projectid');
            } else {
                $form->form_project($url_page_current . '?track_id=' . $object->dao->track_id, $object->dao->socid, $object->dao->fk_project, 'none');
            }
            print '</td></tr>';
        }

        // User assigned
        print '<tr><td>' . $langs->trans("UserAssignedTo") . '</td><td>';
        if ($object->dao->fk_user_assign > 0) {
            $userstat->fetch($object->dao->fk_user_assign);
            print $userstat->getNomUrl(1);
        } else {
            print $langs->trans('None');
        }

        // Show user list to assignate one if status is "read"
        if (GETPOST('set') == "assign_ticket" && $object->dao->fk_statut < 8 && !$user->societe_id && $user->rights->ticketsup->write) {
            print '<form method="post" name="ticketsup" enctype="multipart/form-data" action="' . $url_page_current . '">';
            print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
            print '<input type="hidden" name="action" value="assign_user">';
            print '<input type="hidden" name="track_id" value="' . $object->dao->track_id . '">';
            print '<label for="fk_user_assign">' . $langs->trans("AssignUser") . '</label> ';
            print $form->select_dolusers($user->id, 'fk_user_assign', 0);
            print ' <input class="button" type="submit" name="btn_assign_user" value="' . $langs->trans("Validate") . '" />';
            print '</form>';
        }
        if ($object->dao->fk_statut < 8 && GETPOST('set') != "assign_ticket" && $user->rights->ticketsup->manage) {
            print '<a href="' . $url_page_current . '?track_id=' . $object->dao->track_id . '&action=view&set=assign_ticket">' . img_picto('', 'edit') . ' ' . $langs->trans('Modify') . '</a>';
        }
        print '</td></tr>';

        // Progression
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
        print $langs->trans('Progression') . '</td><td align="left">';
        print '</td>';
        if ($action != 'progression' && $object->dao->fk_statut < 8 && !$user->societe_id) {
            print '<td align="right"><a href="' . $url_page_current . '?action=progression&amp;track_id=' . $object->dao->track_id . '">' . img_edit($langs->trans('Modify')) . '</a></td>';
        }
        print '</tr></table>';
        print '</td><td colspan="5">';
        if ($user->rights->ticketsup->write && $action == 'progression') {
            print '<form action="' . $url_page_current . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
            print '<input type="hidden" name="track_id" value="' . $track_id . '">';
            print '<input type="hidden" name="action" value="set_progression">';
            print '<input type="text" class="flat" size="20" name="progress" value="' . $object->dao->progress . '">';
            print ' <input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print($object->dao->progress > 0 ? $object->dao->progress : '0') . '%';
        }
        print '</td>';
        print '</tr>';

        // Timing (Duration sum of linked fichinter
        $object->dao->fetchObjectLinked();
        $num = count($object->dao->linkedObjects);
        $timing = 0;
        if ($num) {
            foreach ($object->dao->linkedObjects as $objecttype => $objects) {
                if ($objecttype = "fichinter") {
                    foreach ($objects as $fichinter) {
                        $timing += $fichinter->duration;
                    }
                }
            }
        }
        print '<tr><td valign="top">';

        print $form->textwithpicto($langs->trans("TicketDurationAuto"), $langs->trans("TicketDurationAutoInfos"), 1);
        print '</td><td>';
        print convertSecondToTime($timing, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);
        print '</td></tr>';

        // Other attributes
        $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
        if (empty($reshook) && !empty($extrafields->attribute_label)) {
            if ($action == "edit_extrafields") {
                print '<form method="post" name="form_edit_extrafields" enctype="multipart/form-data" action="' . $url_page_current . '">';
                print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                print '<input type="hidden" name="action" value="set_extrafields">';
                print '<input type="hidden" name="track_id" value="' . $object->dao->track_id . '">';

                print $object->dao->showOptionals($extrafields, 'edit');
                print '<tr><td colspan="2" align="center">';
                print ' <input class="button" type="submit" name="btn_edit_extrafields" value="' . $langs->trans("Modify") . '" />';
                print ' <input class="button" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '" />';
                print '</tr>';
                print '</form>';
            } else {
                print $object->dao->showOptionals($extrafields);
                if ($user->rights->ticketsup->write) {
                    print '<tr><td colspan="2" align="center">';
                    print '<a href="' . $url_page_current . '?track_id=' . $object->dao->track_id . '&action=edit_extrafields">' . img_picto('', 'edit') . ' ' . $langs->trans('Edit') . '</a>';
                    print '</tr>';
                }
            }
        }
        print '</table>';

        // View Original message
        $object->viewTicketOriginalMessage($user, $action);



        // Fin colonne gauche et début colonne droite
        print '</div><div class="fichehalfright"><div class="ficheaddleft">';

        /***************************************************
         *
         *      Classification and actions on ticket
         *
         ***************************************************/
        /*
         * Ticket properties
         */
        print '<table class="border" style="width:100%;" >';
        print '<tr class="liste_titre">';
        print '<td colspan="2">';
        print $langs->trans('Properties');
        print '</td>';
        print '</tr>';
        if (GETPOST('set') == 'properties' && $user->rights->ticketsup->write) {
            /*
             *  Form to change ticket properties
             */
            $j = 0;
            $ticketprop[$j] = array(
                'dict' => 'type',
                'list_function' => 'selectTypesTickets',
                'label' => 'TicketChangeType',
            );
            $j++;
            $ticketprop[$j] = array(
                'dict' => 'category',
                'list_function' => 'selectCategoriesTickets',
                'label' => 'TicketChangeCategory',
            );
            $j++;
            $ticketprop[$j] = array(
                'dict' => 'severity',
                'list_function' => 'selectSeveritiesTickets',
                'label' => 'TicketChangeSeverity',
            );
            foreach ($ticketprop as $property) {
                print '<tr>';
                print '<td>';

                print '<form method="post" name="ticketsup" action="' . $url_page_current . '">';
                print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                print '<input type="hidden" name="action" value="change_property">';
                print '<input type="hidden" name="property" value="' . $property['dict'] . '">';
                print '<input type="hidden" name="track_id" value="' . $track_id . '">';
                print '<table class="nobordernopadding" style="width:100%;">';
                print '<tr>';
                print '<td width="40%">';
                print '<label for="type_code">' . $langs->trans($property['label']) . '</label> ';
                print '</td><td width="50%">';
                print $formticket->{$property['list_function']}($object->dao->type_code, 'update_value', '', 0);
                print '</td><td>';
                print ' <input class="button" type="submit" name="btn_update_ticket_prop" value="' . $langs->trans("Modify") . '" />';
                print '</td>';
                print '</tr></table>';
                print '</form>';

                print '</td>';
                print '</tr>';
            }
        } else {
            // Type
            print '<tr><td width="40%">' . $langs->trans("Type") . '</td><td>';
            print $object->dao->type_label;
            if ($user->admin && !$noadmininfo) {
                print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
            }

            print '</td></tr>';

            // Category
            print '<tr><td>' . $langs->trans("Category") . '</td><td>';
            print $object->dao->category_label;
            if ($user->admin && !$noadmininfo) {
                print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
            }

            print '</td></tr>';

            // Severity
            print '<tr><td>' . $langs->trans("TicketSeverity") . '</td><td>';
            print $object->dao->severity_label;
            if ($user->admin && !$noadmininfo) {
                print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
            }

            print '</td></tr>';
        }
        print '</table>'; // End table actions

        // Display navbar with links to change ticket status
        if (!$user->societe_id && $user->rights->ticketsup->write && $object->dao->fk_status < 8 && GETPOST('set') !== 'properties') {
            $object->viewStatusActions();
        }

        print load_fiche_titre($langs->trans('Contacts'), '', 'title_companies.png');

        print '<div class="tagtable centpercent noborder allwidth">';

        print '<div class="tagtr liste_titre">';

        print '<div class="tagtd ">' . $langs->trans("Source") . '</div>
		<div class="tagtd">' . $langs->trans("Company") . '</div>
		<div class="tagtd">' . $langs->trans("Contacts") . '</div>
		<div class="tagtd">' . $langs->trans("ContactType") . '</div>
		<div class="tagtd">' . $langs->trans("Phone") . '</div>
		<div class="tagtd" align="center">' . $langs->trans("Status") . '</div>';
        print '</div><!-- tagtr -->';

        // Contact list
        $companystatic = new Societe($db);
        $contactstatic = new Contact($db);
        $userstatic = new User($db);
        foreach (array('internal', 'external') as $source) {
            $tmpobject = $object->dao;
            $tab = $tmpobject->liste_contact(-1, $source);
            $num = count($tab);
            $i = 0;
            while ($i < $num) {
                $var = !$var;
                print '<div class="tagtr ' . ($var ? 'pair' : 'impair') . '">';

                print '<div class="tagtd" align="left">';
                if ($tab[$i]['source'] == 'internal') {
                    echo $langs->trans("User");
                }

                if ($tab[$i]['source'] == 'external') {
                    echo $langs->trans("ThirdPartyContact");
                }

                print '</div>';
                print '<div class="tagtd" align="left">';

                if ($tab[$i]['socid'] > 0) {
                    $companystatic->fetch($tab[$i]['socid']);
                    echo $companystatic->getNomUrl(1);
                }
                if ($tab[$i]['socid'] < 0) {
                    echo $conf->global->MAIN_INFO_SOCIETE_NOM;
                }
                if (!$tab[$i]['socid']) {
                    echo '&nbsp;';
                }
                print '</div>';

                print '<div class="tagtd">';
                if ($tab[$i]['source'] == 'internal') {
			if ($userstatic->fetch($tab[$i]['id'])) {
	                    print $userstatic->getNomUrl(1);
			}
                }
                if ($tab[$i]['source'] == 'external') {
			if ($contactstatic->fetch($tab[$i]['id'])) {
	                    print $contactstatic->getNomUrl(1);
			}
                }
                print ' </div>
				<div class="tagtd">' . $tab[$i]['libelle'] . '</div>';

                print '<div class="tagtd">';

                print dol_print_phone($tab[$i]['phone'], '', '', '', AC_TEL).'<br>';

                if (! empty($tab[$i]['phone_perso'])) {
                    //print img_picto($langs->trans('PhonePerso'),'object_phoning.png','',0,0,0).' ';
                    print '<br>'.dol_print_phone($tab[$i]['phone_perso'], '', '', '', AC_TEL).'<br>';
                }
                if (! empty($tab[$i]['phone_mobile'])) {
                    //print img_picto($langs->trans('PhoneMobile'),'object_phoning.png','',0,0,0).' ';
                    print dol_print_phone($tab[$i]['phone_mobile'], '', '', '', AC_TEL).'<br>';
                }
                print '</div>';

                print '<div class="tagtd" align="center">';
                if ($object->statut >= 0) {
                    echo '<a href="contacts.php?track_id=' . $object->dao->track_id . '&amp;action=swapstatut&amp;ligne=' . $tab[$i]['rowid'] . '">';
                }

                if ($tab[$i]['source'] == 'internal') {
                    $userstatic->id = $tab[$i]['id'];
                    $userstatic->lastname = $tab[$i]['lastname'];
                    $userstatic->firstname = $tab[$i]['firstname'];
                    echo $userstatic->LibStatut($tab[$i]['statuscontact'], 3);
                }
                if ($tab[$i]['source'] == 'external') {
                    $contactstatic->id = $tab[$i]['id'];
                    $contactstatic->lastname = $tab[$i]['lastname'];
                    $contactstatic->firstname = $tab[$i]['firstname'];
                    echo $contactstatic->LibStatut($tab[$i]['statuscontact'], 3);
                }
                if ($object->statut >= 0) {
                    echo '</a>';
                }

                print '</div>';

                print '</div><!-- tagtr -->';

                $i++;
            }
        }

        print '</div><!-- contact list -->';

        // Contract
        if ($action == 'sel_contract') {
            if (!empty($conf->contrat->enabled)) {
                $langs->load('contrats');
                print load_fiche_titre($langs->trans('LinkToAContract'), '', 'title_commercial.png');

                $form_contract = new FormContract($db);
                $form_contract->formSelectContract(
                    $url_page_current.'?track_id='.$object->dao->track_id,
                    $object->dao->fk_soc,
                    GETPOST('contractid'),
                    'contractid'
                );
            }
        }

        print '</div></div></div>';
        print '<div style="clear:both"></div>';

        print dol_fiche_end();

        /* ActionBar */
        print '<div class="tabsAction">';

        // Show button to mark as read
        if (($object->dao->fk_statut == '0' || empty($object->dao->date_read)) && !$user->societe_id) {
            print '<div class="inline-block divButAction">';
            print '<a class="butAction"  href="card.php?track_id=' . $object->dao->track_id . '&action=mark_ticket_read">' . img_picto('', 'mark-read@ticketsup') . ' ' . $langs->trans('MarkAsRead') . '</a>';
            print '</div';
        }

        // Show link to add a message (if read and not closed)
        if ($object->dao->fk_statut < 8 && $action != "add_message") {
            print '<div class="inline-block divButAction"><a class="butAction" href="card.php?track_id=' . $object->dao->track_id . '&action=add_message">' . $langs->trans('TicketAddMessage') . '</a></div>';
        }

        // Link to create an intervention
        // socid is needed otherwise fichinter ask it and forgot origin after form submit :\
        if (!$object->dao->fk_soc && $user->rights->ficheinter->creer) {
            print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans('UnableToCreateInterIfNoSocid') . '">' . $langs->trans('TicketAddIntervention') . '</a></div>';
        }
        if ($object->dao->fk_soc > 0 && $object->dao->fk_statut < 8 && $user->rights->ficheinter->creer) {
            print '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/fichinter/card.php', 1) . '?action=create&socid=' . $object->dao->fk_soc . '&origin=ticketsup_ticketsup&originid=' . $object->dao->id . '">' . $langs->trans('TicketAddIntervention') . '</a></div>';
        }

        //    Button to edit Properties
        if ($object->dao->fk_statut < 5 && $user->rights->ticketsup->write) {
            print '<div class="inline-block divButAction"><a class="butAction" href="card.php?track_id=' . $object->dao->track_id . '&action=view&set=properties">' . $langs->trans('TicketEditProperties') . '</a></div>';
        }

        //    Button to link to a contract
        if ($user->rights->ticketsup->write && $object->dao->fk_statut < 5 && $user->rights->contrat->creer) {
            print '<div class="inline-block divButAction"><a class="butAction" href="card.php?track_id=' . $object->dao->track_id . '&action=sel_contract">' . $langs->trans('LinkToAContract') . '</a></div>';
        }

        // Close ticket if statut is read
        if ($object->dao->fk_statut > 0 && $object->dao->fk_statut < 8 && $user->rights->ticketsup->write) {
            print '<div class="inline-block divButAction"><a class="butAction" href="card.php?track_id=' . $object->dao->track_id . '&action=close">' . $langs->trans('CloseTicket') . '</a></div>';
        }

        // Re-open ticket
        if (!$user->socid && $object->dao->fk_statut == 8 && !$user->societe_id) {
            print '<div class="inline-block divButAction"><a class="butAction" href="card.php?track_id=' . $object->dao->track_id . '&action=reopen">' . $langs->trans('ReOpen') . '</a></div>';
        }

        // Delete ticket
        if ($user->rights->ticketsup->delete && !$user->societe_id) {
            print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?track_id=' . $object->dao->track_id . '&action=delete">' . $langs->trans('Delete') . '</a></div>';
        }
        print '</div>';

        if ($action == 'view' || $action == 'edit_message_init') {
            print '<div class="fichecenter">'
                . '<div class="">';

            //print '<div style="float: left; width:49%; margin-right: 1%;">';
            // Message list
            print load_fiche_titre($langs->trans('TicketMessagesList'), '', 'messages@ticketsup');
            $show_private_message = ($user->societe_id ? 0 : 1);
            $object->viewTicketTimelineMessages($show_private_message);

            print '</div><!-- fichehalfleft --> ';

            print '</div><!-- fichecenter -->';
            print '<br style="clear: both">';
        } elseif ($action == 'add_message') {
            $action='new_message';
            $modelmail='ticketsup_send';

            print '<div>';
            print load_fiche_titre($langs->trans('TicketAddMessage'), '', 'messages@ticketsup');

            // Define output language
            $outputlangs = $langs;
            $newlang = '';
            if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) {
                $newlang = $_REQUEST['lang_id'];
            }
            if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
                $newlang = $object->default_lang;
            }

            $formticket = new FormTicketsup($db);

            $formticket->action = $action;
            $formticket->track_id = $object->dao->track_id;
            $formticket->id = $object->dao->id;

            $formticket->withfile = 2;
            $formticket->param = array('fk_user_create' => $user->id);
            $formticket->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);

            // Tableau des parametres complementaires du post
            $formticket->param['models']=$modelmail;
            $formticket->param['models_id']=GETPOST('modelmailselected', 'int');
            //$formticket->param['socid']=$object->dao->fk_soc;
            $formticket->param['returnurl']=$_SERVER["PHP_SELF"].'?track_id='.$object->dao->track_id;


            $formticket->withsubstit = 1;

            if ($object->dao->fk_soc > 0) {
                $object->dao->fetch_thirdparty();
                $formticket->substit['__THIRDPARTY_NAME__'] = $object->dao->thirdparty->name;
            }
            $formticket->substit['__SIGNATURE__'] = $user->signature;
            $formticket->substit['__TICKETSUP_TRACKID__'] = $object->dao->track_id;
            $formticket->substit['__TICKETSUP_REF__'] = $object->dao->ref;
            $formticket->substit['__TICKETSUP_SUBJECT__'] = $object->dao->subject;
            $formticket->substit['__TICKETSUP_TYPE__'] = $object->dao->type_code;
            $formticket->substit['__TICKETSUP_CATEGORY__'] = $object->dao->category_code;
            $formticket->substit['__TICKETSUP_SEVERITY__'] = $object->dao->severity_code;
            $formticket->substit['__TICKETSUP_MESSAGE__'] = $object->dao->message;
            $formticket->substit['__TICKETSUP_PROGRESSION__'] = $object->dao->progress;
            if ($object->dao->fk_user_assign > 0) {
                $userstat->fetch($object->dao->fk_user_assign);
                $formticket->substit['__TICKETSUP_USER_ASSIGN__'] = dolGetFirstLastname($userstat->firstname, $userstat->lastname);
            }

            if ($object->dao->fk_user_create > 0) {
                $userstat->fetch($object->dao->fk_user_create);
                $formticket->substit['__TICKETSUP_USER_CREATE__'] = dolGetFirstLastname($userstat->firstname, $userstat->lastname);
            }


            $formticket->showMessageForm('100%');
            print '</div>';
        }
    }
} // End action view

/***************************************************
 * LINKED OBJECT BLOCK
 *
 * Put here code to view linked object
 ****************************************************/
$somethingshown = $form->showLinkedObjectBlock($object->dao);

// End of page
llxFooter('');
$db->close();
