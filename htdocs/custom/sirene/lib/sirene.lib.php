<?php
/* Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/sirene/lib/sirene.lib.php
 * 	\ingroup	sirene
 *	\brief      Functions for the module sirene
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function sirene_admin_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/sirene/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/sirene/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/sirene/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf,$langs,null,$head,$h,'sirene_admin');

    return $head;
}

/**
 * Load Code Naf CSV into table
 *
 * @return  bool
 */
function sirene_reload_codenaf_csv()
{
    global $conf, $db, $langs;

    $error = 0;
    $langs->load('sirene@sirene');

    // Insertion des entrées code NAF du fichier '/sirene/build/codenaf.csv'
    $nbInsert = 0;
    $separator = empty($conf->global->CODENAF_CSV_SEPARATOR_TO_USE)?';':$conf->global->CODENAF_CSV_SEPARATOR_TO_USE;
    $enclosure = empty($conf->global->CODENAF_CSV_ENCLOSURE_TO_USE)?'"':$conf->global->CODENAF_CSV_ENCLOSURE_TO_USE;
    $escape = empty($conf->global->CODENAF_CSV_ESCAPE_TO_USE)?'\\':$conf->global->CODENAF_CSV_ESCAPE_TO_USE;
    $codenaf_filepath = dol_osencode(dol_buildpath('/sirene/build/codenaf.csv'));
    if (file_exists($codenaf_filepath)) {
        ini_set('auto_detect_line_endings',1);	// For MAC compatibility
        $handle = fopen($codenaf_filepath, "r");
        if ($handle !== false) {
            $sql = "TRUNCATE TABLE " . MAIN_DB_PREFIX . "c_codenaf";
            $result = $db->query($sql);
            if ($result) {
                $newid = 1;
                $numline = 1;

                while (($data = fgetcsv($handle, 4096, $separator, $enclosure, $escape)) !== false) {
                    if (isset($data[0]) && ! empty($data[0]) && isset($data[1]) && ! empty($data[1])) {
                        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "c_codenaf(rowid, code, label, active)";
                        $sql .= " VALUES(" . $newid . ", '" . $db->escape(strtoupper($data[0])) . "', '" . $db->escape($data[1]) . "', 1)";
                        $result = $db->query($sql);
                        if ($result) {
                            $newid++;
                            $nbInsert++;
                        }
                    } else {
                        setEventMessage($langs->trans('SireneErrorCodeNafFetchLine', $numline, $codenaf_filepath), 'errors');
                        $error++;
                    }
                    $numline++;
                }
            }

            fclose($handle);
        } else {
            setEventMessage($langs->trans('SireneErrorCodeNafOpenFile', $codenaf_filepath), 'errors');
            $error++;
        }
    }
    if ($nbInsert > 0) {
        setEventMessage($langs->trans('SireneCodeNafNbInsert', $nbInsert));
    }

    return !$error;
}

/**
 * Get id of dictionary of the Code Naf
 *
 * @return  int     Id of dictionary 'Code NAF'
 */
function sirene_codenaf_dict_id()
{
    $taborder = array(34 => 0);
    $tabname = $tablib = $tabsql = $tabsqlsort = $tabfield = $tabfieldvalue = $tabfieldinsert = $tabrowid = $tabcond = $tabhelp = $tabfieldcheck = array(34 => 'base');
    require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
    complete_dictionary_with_modules($taborder,$tabname,$tablib,$tabsql,$tabsqlsort,$tabfield,$tabfieldvalue,$tabfieldinsert,$tabrowid,$tabcond,$tabhelp,$tabfieldcheck);

    $ids = array_flip($tabname);

    if (isset($ids[MAIN_DB_PREFIX."c_codenaf"])) {
        return $ids[MAIN_DB_PREFIX."c_codenaf"];
    }

    return 0;
}