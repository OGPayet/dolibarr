<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/massupdaterights/lib/massupdaterights.lib.php
 *	\brief      Ensemble de fonctions pour le module massupdaterights
 * 	\ingroup	massupdaterights
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function massupdaterights_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/massupdaterights/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/massupdaterights/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/massupdaterights/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf,$langs,null,$head,$h,'massupdaterights_admin');

    return $head;
}

/**
 * Export CSV for groups rights
 *
 * @return  File                 Export file
 */
function massupdaterights_export()
{
    global $conf, $langs;

    $langs->load("admin");
    $langs->load('massupdaterights@massupdaterights');

    $dol_v6 = versioncompare(explode('.',DOL_VERSION), explode('.','6.0.0')) >= 0;

    $separator  = !empty($conf->global->MASSUPDATERIGHTS_CSV_SEPARATOR_TO_USE)?$conf->global->MASSUPDATERIGHTS_CSV_SEPARATOR_TO_USE:';';
    $enclosure  = !empty($conf->global->MASSUPDATERIGHTS_CSV_ENCLOSURE_TO_USE)?$conf->global->MASSUPDATERIGHTS_CSV_ENCLOSURE_TO_USE:'"';
    $escape     = !empty($conf->global->MASSUPDATERIGHTS_CSV_ESCAPE_TO_USE)?$conf->global->MASSUPDATERIGHTS_CSV_ESCAPE_TO_USE:'\\';

    $nom_fic = 'massupdaterights-groups-'.dol_print_date(dol_now(),'%Y%m%d%H%M%S').'.csv';
    $newfic = DOL_DATA_ROOT.'/'.$nom_fic; // dans le rep de travail

    $fp = @fopen($newfic,"w");
    if ($fp) {
        $headers = array(
            0 => $langs->transnoentities('MassUpdateRightsRightId'),
            1 => $langs->transnoentities('MassUpdateRightsRightModule'),
            2 => $langs->transnoentities('MassUpdateRightsRightLabel'),
        );
        $lines = array();

        $groups = massupdaterights_get_groups();
        $idxGroup = 3;
        foreach ($groups as $group) {
            $headers[$idxGroup] = $group->nom;

            if ($dol_v6) {
                $entity = $conf->entity;
                if (!empty($conf->multicompany->enabled)) {
                    if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
                        $entity = (GETPOST('entity', 'int') ? GETPOST('entity', 'int') : $conf->entity);
                    else
                        $entity = (!empty($group->entity) ? $group->entity : $conf->entity);
                }
            } else {
                $entity = $group->entity;
            }

            $modules = massupdaterights_load_modules($entity);
            $rights = massupdaterights_get_rights($group->id, $entity);

            foreach($rights as $right) {
                // Si la ligne correspond a un module qui n'existe plus (absent de includes/module), on l'ignore
                if (empty($modules[$right->module])) continue;

                if (isset($lines[$right->id])) {
                    $line = $lines[$right->id];
                } else {
                    $perm_libelle=($conf->global->MAIN_USE_ADVANCED_PERMS && ($langs->transnoentities("PermissionAdvanced".$right->id)!=("PermissionAdvanced".$right->id))?$langs->transnoentities("PermissionAdvanced".$right->id):(($langs->transnoentities("Permission".$right->id)!=("Permission".$right->id))?$langs->transnoentities("Permission".$right->id):$langs->transnoentities($right->libelle)));
                    $line = array(
                        0 => $right->id,
                        1 => html_entity_decode($modules[$right->module]->getName()),
                        2 => html_entity_decode($perm_libelle),
                    );
                }

                $line[$idxGroup] = $right->permission;

                $lines[$right->id] = $line;
            }
            $idxGroup++;
        }

        // Ecriture de l'entete
        fputcsv($fp, $headers, $separator, $enclosure, $escape);

        // Ecriture des lignes
        foreach ($lines as $line) {
            fputcsv($fp, $line, $separator, $enclosure, $escape);
        }

        fclose($fp);

        // Send file
        $type = dol_mimetype($newfic);
        header('Content-Description: File Transfer');
        header('Content-Type: '.$type.'; charset="'.$conf->file->character_set_client);
        header('Content-Disposition: inline; filename="'.$nom_fic.'"');
        header('Content-Length: ' . dol_filesize($newfic));
        header('Cache-Control: Public, must-revalidate');
        header('Pragma: public');

        readfile($newfic);

        unlink($newfic);

        exit();
    }
}

/**
 * Import CSV for groups rights
 *
 * @param   string      $file       Text CVS
 * @return  bool                    <0 if not ok, >0 if ok
 */
function massupdaterights_import($file)
{
    global $conf, $db, $langs;

    $langs->load("admin");
    $langs->load('massupdaterights@massupdaterights');

    $dol_v6 = versioncompare(explode('.',DOL_VERSION), explode('.','6.0.0')) >= 0;

    $separator  = !empty($conf->global->MASSUPDATERIGHTS_CSV_SEPARATOR_TO_USE)?$conf->global->MASSUPDATERIGHTS_CSV_SEPARATOR_TO_USE:';';
    $enclosure  = !empty($conf->global->MASSUPDATERIGHTS_CSV_ENCLOSURE_TO_USE)?$conf->global->MASSUPDATERIGHTS_CSV_ENCLOSURE_TO_USE:'"';
    $escape     = !empty($conf->global->MASSUPDATERIGHTS_CSV_ESCAPE_TO_USE)?$conf->global->MASSUPDATERIGHTS_CSV_ESCAPE_TO_USE:'\\';

    $fp = @fopen($file,"r");
    if ($fp) {
        $groupsCsv = array();
        $idxLine = 0;
        $nbColumn = 0;
        $idxGroup = 3;
        while ($line = fgetcsv($fp, 4096, $separator, $enclosure, $escape)) {
            if ($idxLine == 0) {
                $nbColumn = count($line);
                for ($i = $idxGroup; $i < $nbColumn; $i++) {
                    $groupsCsv[$i] = array('name' => $line[$i]);
                }
            } else {
                for ($i = $idxGroup; $i < $nbColumn; $i++) {
                    $groupsCsv[$i][intval($line[0])] = $line[$i];
                }
            }
            $idxLine++;
        }
        fclose($fp);

        $groups = massupdaterights_get_groups();
        $idxGroup = 2;
        foreach ($groups as $group) {
            $idxGroup++;
            if ($groupsCsv[$idxGroup]['name'] != $group->nom) {
                setEventMessage($langs->trans("MassUpdateRightsBadColumnGroup", $idxGroup, $groupsCsv[$idxGroup]['name'], $group->nom), "warnings");
                continue;
            }

            $objGroup = new Usergroup($db);
            $result=$objGroup->fetch($group->id);
            if ($result < 0) {
                setEventMessages($objGroup->error, $objGroup->errors, "warnings");
                continue;
            }

            if ($dol_v6) {
                $entity = $conf->entity;
                if (!empty($conf->multicompany->enabled)) {
                    if (!empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
                        $entity = (GETPOST('entity', 'int') ? GETPOST('entity', 'int') : $conf->entity);
                    else
                        $entity = (!empty($objGroup->entity) ? $objGroup->entity : $conf->entity);
                }
            } else {
                $entity = $group->entity;
            }

            $modules = massupdaterights_load_modules($entity);
            $rights = massupdaterights_get_rights($group->id, $entity);

            foreach($rights as $right) {
                // Si la ligne correspond a un module qui n'existe plus (absent de includes/module), on l'ignore
                if (empty($modules[$right->module])) continue;

                if (isset($groupsCsv[$idxGroup][$right->id])) {
                    if (! empty($groupsCsv[$idxGroup][$right->id])) {
                        if ($dol_v6) {
                            $objGroup->addrights($right->id, $right->module, '', $entity);
                        } else {
                            $objGroup->addrights($right->id, $right->module);
                        }
                    } else {
                        if ($dol_v6) {
                            $objGroup->delrights($right->id, $right->module, '', $entity);
                        } else {
                            $objGroup->delrights($right->id, $right->module);
                        }
                    }
                } else {
                    // Erreur Droit non définit => null
                    setEventMessage($langs->trans("MassUpdateRightsRightNotDefined", $group->nom, $modules[$right->module]->getName(), $right->id), "warnings");
                    if ($dol_v6) {
                        $objGroup->delrights($right->id, $right->module, '', $entity);
                    } else {
                        $objGroup->delrights($right->id, $right->module);
                    }
                }
            }
        }
    }

    return 1;
}

/**
 * Load modules of the entity
 *
 * @param   int      $entity        ID of entity
 * @return  array                   List of module
 */
function massupdaterights_load_modules($entity)
{
    global $conf, $db, $langs;

    // Charge les modules soumis a permissions
    $modules = array();
    $modulesdir = dolGetModulesDirs();

    $dol_prev_v6 = versioncompare(explode('.',DOL_VERSION), explode('.','6.0.0')) < 0;

    $db->begin();

    foreach ($modulesdir as $dir) {
        // Load modules attributes in arrays (name, numero, orders) from dir directory
        //print $dir."\n<br>";
        $handle = @opendir(dol_osencode($dir));
        if (is_resource($handle)) {
            while (($file = readdir($handle)) !== false) {
                if (is_readable($dir . $file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php') {
                    $modName = substr($file, 0, dol_strlen($file) - 10);

                    if ($modName) {
                        include_once $dir . "/" . $file;
                        $objMod = new $modName($db);
                        // Load all lang files of module
                        if (isset($objMod->langfiles) && is_array($objMod->langfiles)) {
                            foreach ($objMod->langfiles as $domain) {
                                $langs->load($domain);
                            }
                        }
                        // Load all permissions
                        if ($objMod->rights_class) {
                            if ($dol_prev_v6) {
                                $entity = ((!empty($conf->multicompany->enabled) && !empty($entity)) ? $entity : null);
                            }
                            $ret = $objMod->insert_permissions(0, $entity);
                            $modules[$objMod->rights_class] = $objMod;
                        }
                    }
                }
            }
        }
    }

    $db->commit();

    return $modules;
}

/**
 * Get groups list
 *
 * @return  array                    List of groups
 */
function massupdaterights_get_groups()
{
    global $conf, $db;

    // Lecture des droits groupes
    $groups = array();

    $dol_v6 = versioncompare(explode('.',DOL_VERSION), explode('.','6.0.0')) >= 0;

    $sql = "SELECT ug.rowid AS id, ug.nom, ug.entity";
    $sql.= " FROM ".MAIN_DB_PREFIX."usergroup as ug";
    if ($dol_v6) {
        if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && ($conf->global->MULTICOMPANY_TRANSVERSE_MODE || ($user->admin && !$user->entity))) {
            $sql .= " WHERE ug.entity IS NOT NULL";
        } else {
            $sql .= " WHERE ug.entity IN (0," . $conf->entity . ")";
        }
    } else {
        if (!empty($conf->multicompany->enabled) && !empty($conf->multicompany->transverse_mode)) {
            $sql .= " WHERE ug.entity IN (0,1)";
        } else {
            $sql .= " WHERE ug.entity = " . $conf->entity;
        }
    }
    $sql.= " ORDER BY ug.nom";

    $result=$db->query($sql);
    if ($result) {
        $num = $db->num_rows($result);
        $i = 0;
        while ($i < $num) {
            $obj = $db->fetch_object($result);
            $groups[$obj->id] = $obj;
            $i++;
        }
        $db->free($result);
    } else {
        dol_print_error($db);
    }

    return $groups;
}

/**
 * Get rights list for the group
 *
 * @param   int      $group_id       ID of the group
 * @param   int      $entity         ID of entity
 * @return  array  List of rights
 */
function massupdaterights_get_rights($group_id, $entity)
{
    global $conf, $db;

    // Lecture des droits groupes
    $rights = array();

    $dol_v6 = versioncompare(explode('.',DOL_VERSION), explode('.','6.0.0')) >= 0;

    $permissions = array();
    $sql = "SELECT ugr.fk_id";
    $sql.= " FROM ".MAIN_DB_PREFIX."usergroup_rights as ugr";
    $sql.= " WHERE ugr.fk_usergroup=".$group_id;
    if ($dol_v6) {
        $sql .= " AND ugr.entity = " . $entity;
    }
    $result=$db->query($sql);
    if ($result) {
        while ($obj = $db->fetch_object($result)) {
            $permissions[$obj->fk_id] = true;
        }
        $db->free($result);
    } else {
        dol_print_error($db);
    }

    $sql = "SELECT r.id, r.libelle, r.module";
    $sql.= " FROM ".MAIN_DB_PREFIX."rights_def as r";
    $sql.= " WHERE r.libelle NOT LIKE 'tou%'";    // On ignore droits "tous"
    if ($dol_v6) {
        $sql.= " AND r.entity = " . $entity;
    } else {
        if (!empty($conf->multicompany->enabled)) {
            if (empty($conf->multicompany->transverse_mode)) {
                $sql .= " AND r.entity = " . $entity;
            } else {
                $sql .= " AND r.entity IN (0,1)";
            }
        } else {
            $sql .= " AND r.entity = " . $conf->entity;
        }
    }
    if (empty($conf->global->MAIN_USE_ADVANCED_PERMS)) $sql.= " AND r.perms NOT LIKE '%_advance'";  // Hide advanced perms if option is disable
    $sql.= " ORDER BY r.module, r.id";

    $result=$db->query($sql);
    if ($result) {
        while ($obj = $db->fetch_object($result)) {
            $rights[$obj->id] = $obj;
            $rights[$obj->id]->permission = isset($permissions[$obj->id])?'X':'';
        }
        $db->free($result);
    } else {
        dol_print_error($db);
    }

    return $rights;
}
