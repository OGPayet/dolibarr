<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2017      Open-Dsi             <support@open-dsi.fr>
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
 *      \file       htdocs/extendedemail/user/association_tab.php
 *      \ingroup    extendedemail
 *      \brief      Association utilisateur / adresse generique
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
dol_include_once('/extendedemail/class/html.formextentedemail.class.php');
dol_include_once('/extendedemail/lib/extendedemail.lib.php');

$id = GETPOST('id','int');
$action = GETPOST('action');

$genericemailid = GETPOST('genericemailid', 'int');

$langs->load("companies");
$langs->load("members");
$langs->load("bills");
$langs->load("users");
$langs->load("extendedemail@extendedemail");

$object = new User($db);
$object->fetch($id);

// If user is not user read and no permission to read other users, we stop
if (($object->id != $user->id) && (! $user->rights->user->user->lire)) accessforbidden();
if (!$user->rights->extendedemail->user_generic_email->read) accessforbidden();

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');
if ($user->id == $id) $feature2=''; // A user can always read its own card
$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

$caneditgenericemail = $user->rights->extendedemail->user_generic_email->create;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('usercard','globalcard'));

/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    if (($action == 'addgenericemail' || $action == 'removegenericemail') && $genericemailid > 0 && $caneditgenericemail) {
        // Get generic email
        $generic_email = null;
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . "c_extentedemail_generic_email";
        $sql .= " WHERE rowid = {$genericemailid}";
        $resql = $db->query($sql);
        if ($resql) {
            $generic_email = $db->fetch_object($resql);
            $db->free($resql);
        }

        if ($generic_email) {
            $result = true;
            $error = "";
            $entity = $conf->multicompany->transverse_mode ? GETPOST("entity", 'int') : $generic_email->entity;

            if ($action == 'addgenericemail') {
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "extentedemail_user_generic_email(fk_user, fk_generic_email, entity)";
                $sql .= " VALUES({$object->id}, {$generic_email->rowid}, {$entity})";
                $resql = $db->query($sql);
                if (!$resql) {
                    $result = false;
                    $error = $db->lasterror();
                }
            }
            if ($action == 'removegenericemail') {
                $sql = "DELETE FROM " . MAIN_DB_PREFIX . "extentedemail_user_generic_email";
                $sql .= " WHERE fk_user = {$object->id} AND fk_generic_email = {$generic_email->rowid} AND entity = {$entity}";
                $resql = $db->query($sql);
                if (!$resql) {
                    $result = false;
                    $error = $db->lasterror();
                }
            }

            if ($result > 0) {
                header("Location: " . $_SERVER['PHP_SELF'] . '?id=' . $id);
                exit;
            } else {
                setEventMessages($error, null, 'errors');
            }
        }
    }
}


/******************************************************************************/
/* Affichage fiche                                                            */
/******************************************************************************/

llxHeader();

$form = new Form($db);
$formextentedemail = new FormExtentedEmail($db);

if ($id) {
    $head = user_prepare_head($object);

    $title = $langs->trans("User");
    dol_fiche_head($head, 'extendedemail_user_generic_email', $title, 0, 'user');

    $linkback = '';

    if ($user->rights->user->user->lire || $user->admin) {
        $linkback = '<a href="' . DOL_URL_ROOT . '/user/index.php">' . $langs->trans("BackToList") . '</a>';
    }

    dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

    print '<div class="underbanner clearboth"></div>';

    print '<table class="border" width="100%">';

    // Login
    print '<tr><td class="titlefield">' . $langs->trans("Login") . '</td><td class="valeur">' . $object->login . '&nbsp;</td></tr>';

    print "</table>";

    dol_fiche_end();

    /*
     * List of generic email
     */
    print load_fiche_titre($langs->trans("ExtendedEmailListOfGenericEmail"), '', '');

    // Get list generic email affected
    $generic_emails = extendedemail_get_generic_emails_affected($object->id, $error);

    // Excluding email of the list
    $exclude = [];
    if ($generic_emails !== false) {
        if (!(!empty($conf->multicompany->enabled) && !empty($conf->multicompany->transverse_mode))) {
            foreach ($generic_emails as $generic_email) {
                $exclude[] = $generic_email['id'];
            }
        }
    } else {
        setEventMessages($error, null, 'errors');
    }

    if ($caneditgenericemail) {
        print '<form action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '" method="POST">' . "\n";
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '" />';
        print '<input type="hidden" name="action" value="addgenericemail" />';
    }

    print '<table class="noborder" width="100%">' . "\n";
    print '<tr class="liste_titre"><th class="liste_titre" width="25%">' . $langs->trans("ExtendedEmailGenericEmail") . '</th>' . "\n";
    if (!empty($conf->multicompany->enabled) && !empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && !$user->entity) {
        print '<td class="liste_titre" width="25%">' . $langs->trans("Entity") . '</td>';
    }
    print '<th align="right">';
    if ($caneditgenericemail) {
        print $formextentedemail->select_genericemails('', 'genericemailid', 1, $exclude, 0, '', '', $object->entity);
        print ' &nbsp; ';
        // Multicompany
        if (!empty($conf->multicompany->enabled)) {
            if ($conf->entity == 1 && $conf->multicompany->transverse_mode) {
                print '</td><td>' . $langs->trans("Entity") . '</td>';
                print "<td>" . $mc->select_entities($conf->entity);
            } else {
                print '<input type="hidden" name="entity" value="' . $conf->entity . '" />';
            }
        } else {
            print '<input type="hidden" name="entity" value="' . $conf->entity . '" />';
        }
        print '<input type="submit" class="button" value="' . $langs->trans("Add") . '" />';
    }
    print '</th></tr>' . "\n";

    /*
     * Generic emails assigned to user
     */
    if (count($generic_emails) > 0) {
        $var = true;

        foreach ($generic_emails as $generic_email) {
            $var = !$var;

            print "<tr " . $bc[$var] . ">";
            print '<td>';
            print $generic_email['email'];
            print '</td>';
            if (!empty($conf->multicompany->enabled) && !empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && !$user->entity) {
                print '<td class="valeur">';
                if (!empty($group->usergroup_entity)) {
                    $nb = 0;
                    foreach ($group->usergroup_entity as $group_entity) {
                        $mc->getInfo($group_entity);
                        print ($nb > 0 ? ', ' : '') . $mc->label;
                        print '<a href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=removegenericemail&amp;genericemailid=' . $generic_email['id'] . '&amp;entity=' . $group_entity . '">';
                        print img_delete($langs->trans("RemoveFromGroup"));
                        print '</a>';
                        $nb++;
                    }
                }
            }
            print '<td align="right">';
            if ($caneditgenericemail && empty($conf->multicompany->transverse_mode)) {
                print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removegenericemail&amp;genericemailid='.$generic_email['id'].'">';
                print img_delete($langs->trans("Remove"));
                print '</a>';
            } else {
                print "&nbsp;";
            }
            print "</td></tr>\n";
        }
    } else {
        print '<tr ' . $bc[false] . '><td colspan="3" class="opacitymedium">' . $langs->trans("None") . '</td></tr>';
    }

    print "</table>";

    if ($caneditgenericemail) {
        print '</form>';
    }
    print "<br>";

    print "</div>";
}

llxFooter();

$db->close();
