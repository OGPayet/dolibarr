<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (c) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2013-2014 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2013-2015 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2015      Alexis LAURIER       contact@alexislaurier.fr
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
 *  \file       htdocs/custom/synergiestech/class/extendedGroup.class.php
 *	\brief      File of class to manage group
 *  \ingroup	synergiestech
 */

require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

/**
 *	Class to manage Dolibarr users and groups with custom links between user and group - we only edit values for setInLdap column to true
 */
class ExtendedUser extends User
{
    public static $cache_group_mapping_dictionnary = array();

    /**
     * Load group mapping cache dictionary
     */

    function loadCacheDictionary()
    {
        if (empty(self::$cache_group_mapping_dictionnary)) {
            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            self::$cache_group_mapping_dictionnary = Dictionary::getJSONDictionary($this->db, "synergiestech", "ActiveDirectoryGroupMapping");
        }
    }

    /**
     *  Add user into a group
     *
     *  @param	int	$group      Id of group
     *  @param  int		$entity     Entity
     *  @param  int		$notrigger  Disable triggers
     *  @return int  				<0 if KO, >0 if OK
     */

    function SetInGroup($group, $entity, $notrigger = 0)
    {
        global $conf, $langs, $user;

        $error = 0;

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "usergroup_user (entity, fk_user, fk_usergroup, setFromLdap)";
        $sql .= " VALUES (" . $entity . "," . $this->id . "," . $group . ", 1)";

        $result = $this->db->query($sql);
        if ($result) {
            if (!$error && !$notrigger) {
                $this->newgroupid = $group;    // deprecated. Remove this.
                $this->context = array('audit' => $langs->trans("UserSetInGroup"), 'newgroupid' => $group);

                // Call trigger
                $result = $this->call_trigger('USER_MODIFY', $user);
                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return 1;
            } else {
                dol_syslog(get_class($this) . "::SetInGroup " . $this->error, LOG_ERR);
                $this->db->rollback();
                return -2;
            }
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Clean all user relation set from ldap
     * @return int
     */

    function deleteAllRelationSetFromLdap()
    {
        $error = 0;

        $this->db->begin();

        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "usergroup_user";
        $sql .= " WHERE setFromLdap = 1";

        $result = $this->db->query($sql);
        if ($result) {
            if (!$error) {
                $this->db->commit();
                return 1;
            } else {
                dol_syslog(get_class($this) . "::deleteAllRelationSetFromLdap " . $this->error, LOG_ERR);
                $this->db->rollback();
                return -2;
            }
        } else {
            $this->error = $this->db->lasterror();
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *
     * Add this user to a group thanks to its ldap dn
     * @param string $dn
     * @param int $notrigger
     * @return int
     *
     */

    function addUserToGroupWithLdapDn($dn, $notrigger)
    {
        global $conf;
        $userFilter = explode(',', $dn);
        $groupAndEntity = $this->getGroupIdAndEntityIdFromLdapGroupDn($dn);
        foreach ($groupAndEntity as $entity => $arrayOfGroupId) {
            foreach ($arrayOfGroupId as $groupId => $isEnabled) {
                if ($isEnabled) {
                    $this->SetInGroup($groupId, $entity, $notrigger);
                }
            }
        }
    }

    /**
     * Get User Groups and entity linked to a group dn
     * @param string $dn
     * @return array $groupAndEntity
     */

    function getGroupIdAndEntityIdFromLdapGroupDn($dn)
    {
        $this->loadCacheDictionary();
        $result = array();
        foreach (self::$cache_group_mapping_dictionnary as $dictionaryLine) {
            $activeDirectoryGroup = explode(",", $dictionaryLine["activeDirectoryGroup"]);//List of group based on $conf->global->LDAP_GROUP_DN, which may contain cn property
            $cn=explode(",",$dn);
            $cn = $cn[0];
            $cn = explode("=", $cn);
            $cn = $cn[1];
            if (in_array($cn, $activeDirectoryGroup)) {
                $dolibarrGroupList = explode(",", $dictionaryLine["dolibarrGroup"]);
                $entityList = explode(",", $dictionaryLine["linkEntity"]);
                foreach ($entityList as $entity) {
                    if (!$result[$entity]) {
                        $result[$entity] = array();
                    }
                    foreach ($dolibarrGroupList as $dolibarrGroupId) {
                        $result[$entity][$dolibarrGroupId] = true;
                    }
                }
            }
        }
        return $result;
    }
}
