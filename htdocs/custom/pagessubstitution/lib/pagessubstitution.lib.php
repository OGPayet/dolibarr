<?php
/* Copyright (C) 2017      Open-DSI                 <support@open-dsi.fr>
 * Copyright (C) 2017      fatpratmatt              <fatpratmatt@gmail.com>
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
 *	\file       htdocs/pagessubstitution/lib/pagessubstitution.lib.php
 * 	\ingroup	pagessubstitution
 *	\brief      Functions for the module pagessubstitution
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function pagessubstitution_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/pagessubstitution/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/pagessubstitution/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/pagessubstitution/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf,$langs,null,$head,$h,'pagessubstitution_admin');

    return $head;
}

/**
 * Get substitution url if exist
 *
 * @param   string  $path   Relative path from the root of Dolibarr of the page to be substituted.
 *
 * @return  string  substitution url or empty
 */
function pagessubstitution_get_substitution_url($path) {
    global $conf;

    $const_name = pagessubstitution_get_const_name_from_substitution_path($path);

    if (!empty($conf->global->{$const_name})) {
        $path_dst = '/pagessubstitution/substitutions' . pagessubstitution_get_entity_path(). $path;
        $real_path_dst = dol_buildpath($path_dst);

        if (file_exists($real_path_dst)) {
            $url_path_dst = dol_buildpath($path_dst, 2);

            return $url_path_dst;
        }
    }

    return '';
}

/**
 * Is substitution file
 *
 * @param   string  $path   Relative path from the root of Dolibarr of the page to be substituted.
 *
 * @return  bool
 */
function pagessubstitution_is_substitution_page($path) {
    global $dolibarr_main_url_root_alt;

    if (preg_match('/^\/(|'.preg_quote(trim($dolibarr_main_url_root_alt, "/"),'/').'\/)pagessubstitution/i', $path) == 1) {
        return true;
    }

    return false;
}

/**
 * Get const name from substitution path
 *
 * @param   string  $path   Relative path from the root of Dolibarr of the page to be substituted.
 *
 * @return  string          Substitution url or empty
 */
function pagessubstitution_get_const_name_from_substitution_path($path) {
    $const_name = 'PAGESSUBSTITUTION_ACTIVE'.strtoupper(str_replace('/', '_', str_replace('.php', '', $path)));

    return $const_name;
}

/**
 * Get entity path
 *
 * @return  string  '/id_name' if multientities or empty
 */
function pagessubstitution_get_entity_path() {
    global $conf, $db;

    if (!empty($conf->global->MAIN_MODULE_MULTICOMPANY)) {
        $sql = "SELECT e.label";
        $sql .= " FROM " . MAIN_DB_PREFIX . "entity AS e";
        $sql .= " WHERE e.rowid = " . $conf->entity;
        $resql = $db->query($sql);
        if ($resql) {
            $obj = $db->fetch_object($resql);

            return '/'.$conf->entity.'_'.trim(strtolower($obj->label));
        }
    }

    return '';
}

/**
 * Get list files in directory and subdirectory
 * write by fatpratmatt@gmail.com in http://php.net/manual/fr/function.scandir.php
 *
 * @return  array  list files
 */
function pagessubstitution_scanDirectories($rootDir, $allData=array()) {
    // set filenames invisible if you want
    $invisibleFileNames = array(".", "..", ".htaccess", ".htpasswd");
    // run through content of root directory
    $dirContent = scandir($rootDir);
    foreach($dirContent as $key => $content) {
        // filter all files not accessible
        $path = $rootDir.'/'.$content;
        if(!in_array($content, $invisibleFileNames)) {
            // if content is file & readable, add to array
            if(is_file($path) && is_readable($path)) {
                // save file name with path
                $allData[] = $path;
                // if content is a directory and readable, add path and name
            }elseif(is_dir($path) && is_readable($path)) {
                // recursive callback to open new directory
                $allData = pagessubstitution_scanDirectories($path, $allData);
            }
        }
    }
    return $allData;
}
