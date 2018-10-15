<?php
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/extendedemail/lib/extendedemail.lib.php
 * 	\ingroup	extendedemail
 *	\brief      Functions for the module extendedemail
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function extendedemail_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/extendedemail/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/extendedemail/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/extendedemail/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf,$langs,null,$head,$h,'extendedemail_admin');

    return $head;
}

/**
 * Get all users email with name in a array
 *
 * @return  array				Array of users email: array(array('name' => Name, 'email' => Email), ...)
 */
function extendedemail_get_users_email()
{
    global $conf, $db, $langs;

    $user_static = new User($db);
    $users = array();
    $hide_no_email = !empty($conf->global->EXTENDEDEMAIL_HIDE_NO_EMAIL);

    // Init $this->users array
    $sql = "SELECT DISTINCT u.rowid";    // Distinct reduce pb with old tables with duplicates
    $sql .= " FROM " . MAIN_DB_PREFIX . "user as u";
    $sql .= " WHERE u.entity IN (" . getEntity('user', 1) . ")";
    if (!empty($conf->global->FILTER_EXTERNAL)) $sql .= " AND (u.fk_soc IS NULL OR u.fk_soc = 0)";

    $resql = $db->query($sql);
    if ($resql) {
        $idx = 0;
        while ($obj = $db->fetch_object($resql)) {
            $ret = $user_static->fetch($obj->rowid);
            if ($ret > 0) {
                if ($hide_no_email && empty($user_static->email))
                    continue;
                $tmp = array('email' => $user_static->email, 'name' => $user_static->getFullName($langs));
                if (empty($user_static->email)) {
                    $tmp['email'] = $idx++;
                    $tmp['disabled'] = true;
                }
                $users[] = $tmp;
            }
        }
    }

    usort($users, function ($a, $b) {
        return strnatcasecmp($a['name'], $b['name']);
    });

    return $users;
}

/**
 * Get all contacts email of thirdparty parent with name in a array
 *
 * @param   int     $soc_id     Id if thirdparty
 * @return  array				Array of contacts email of thirdparty parent: array(array('name' => Name, 'email' => Email), ...)
 */
function extendedemail_get_contacts_thirdparty_parent_email($soc_id, $contacts=null, $idx=0)
{
    global $conf, $db, $langs;

    $firstLevel = false;
    if (!isset($contacts)) { $contacts = array(); $firstLevel = true; }

    if (isset($soc_id) && $soc_id > 0) {
        require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
        $thirdparty_static = new Societe($db);
        $res = $thirdparty_static->fetch($soc_id);
        if ($res > 0) {
            $hide_no_email = !empty($conf->global->EXTENDEDEMAIL_HIDE_NO_EMAIL);
            require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
            $contact_static = new Contact($db);

            // Init $this->users array
            $sql = "SELECT p.rowid, p.civility, p.lastname, p.firstname, p.email";
            $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
            $sql .= " WHERE p.fk_soc = ".$soc_id;
            $sql .= " AND p.statut = 1";

            $resql = $db->query($sql);
            if ($resql) {
                while ($obj = $db->fetch_object($resql)) {
                    if ($hide_no_email && empty($obj->email))
                        continue;

                    $contact_static->civility_id = $obj->civility;
                    $contact_static->lastname = $obj->lastname;
                    $contact_static->firstname = $obj->firstname;

                    $tmp = array('email' => $obj->email, 'name' => $contact_static->getFullName($langs));
                    if (empty($obj->email)) {
                        $tmp['email'] = $idx++;
                        $tmp['disabled'] = true;
                    }
                    $contacts[$tmp['email']] = $tmp;
                }
            }

            $contacts = array_merge($contacts, extendedemail_get_contacts_thirdparty_parent_email($thirdparty_static->parent, $contacts, $idx));
        }
    }

    if ($firstLevel) {
        usort($contacts, function ($a, $b) {
            return strnatcasecmp($a['name'], $b['name']);
        });
    }

    return $contacts;
}

/**
 * Get all generic emails affected for the user ID
 *
 * @param   int		    $user_id	User ID
 * @param   string	    &$error		Message error
 * @return  array|false				List of generic email affected or false if error
 */
function extendedemail_get_generic_emails_affected($user_id, &$error) {
    global $conf, $db, $user;

    $generic_emails = [];

    $sql = "SELECT cge.rowid, cge.name, cge.email, uge.entity FROM " . MAIN_DB_PREFIX . "extentedemail_user_generic_email AS uge";
    $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_extentedemail_generic_email AS cge ON cge.rowid = uge.fk_generic_email";
    $sql .= " WHERE uge.fk_user = {$user_id}";
    if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
        $sql .= " AND uge.entity IS NOT NULL";
    } else {
        $sql .= " AND uge.entity IN (0," . $conf->entity . ")";
    }
    $sql .= " ORDER BY cge.name";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            if (!array_key_exists($obj->rowid, $generic_emails)) {
                $generic_emails[$obj->rowid] = [
                    'id' => $obj->rowid,
                    'email' => (!empty($obj->name) ? "{$obj->name} &lt;{$obj->email}&gt;" : "{$obj->email}"),
                ];
                $generic_emails[$obj->rowid]->entities[] = $obj->entity;
            }
        }
        $db->free($resql);

        return $generic_emails;
    } else {
        $error = $db->lasterror();
        return false;
    }
}

/**
 * Get all generic email and defined send mail for user in a array
 *
 * @param   FormMail	$formmail	    Form mail object
 * @return  array				        Array of generic email: array(array('name' => Name, 'email' => Email), ...)
 */
function extendedemail_get_senders_email($formmail)
{
    global $conf, $db, $langs, $user;

    $senders = [];

    if (! empty($formmail->withfrom) /*&& empty($formmail->withfromreadonly)*/) {
        // Get generic email affected
        $sql = "SELECT cge.rowid, cge.name, cge.email FROM " . MAIN_DB_PREFIX . "extentedemail_user_generic_email AS uge";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_extentedemail_generic_email AS cge ON cge.rowid = uge.fk_generic_email";
        $sql .= " WHERE uge.fk_user = {$user->id}";
        $sql .= " AND uge.entity IN (0," . $conf->entity . ")";
        $sql .= " GROUP BY cge.email";

        $resql = $db->query($sql);
        if ($resql) {
            $nb_generic_emails = $db->num_rows($resql);
            if ($nb_generic_emails > 0) {
                // Add defined sender
                //----------------------
                if (empty($conf->global->EXTENDEDEMAIL_HIDE_NO_EMAIL) || !empty($formmail->frommail)) {
                    if ($formmail->fromtype == 'user' && $formmail->fromid > 0) {
                        $langs->load("users");
                        $fuser = new User($db);
                        $fuser->fetch($formmail->fromid);
                        $fromname = $fuser->getFullName($langs);
                    } else {
                        $fromname = $formmail->fromname;
                    }
                    $tmp = ['email' => $formmail->frommail, 'name' => $fromname];
                    if (empty($formmail->frommail)) {
                        $tmp['email'] = 1;
                        $tmp['disabled'] = true;
                    }
                    $senders[] = $tmp;
                }

                // Add generic email affected
                //------------------------------
                while ($obj = $db->fetch_object($resql)) {
                    $senders[] = ['email' => $obj->email, 'name' => $obj->name];
                }

                usort($senders, function ($a, $b) {
                    return strnatcasecmp($a['name'], $b['name']);
                });
            }
            $db->free($resql);
        } else {
            setEventMessages($db->lasterror(), null, 'errors');
        }
    }

    return $senders;
}
