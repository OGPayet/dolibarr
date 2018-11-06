<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *       \file       htdocs/warehousechild/ajax/benefactor.php
 *       \brief      File to load equipement options (select) from order supplier dispatch
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");

$id       = GETPOST('id','int'); // id of supplier order
$action   = GETPOST('action','alpha');
$htmlname = GETPOST('htmlname','alpha');

// more_data
$fk_product         = GETPOST('fk_product', 'int');
$fk_entrepot        = GETPOST('fk_entrepot', 'int');
$id_equipement_list = GETPOST('id_equipement_list', 'array') ? GETPOST('id_equipement_list', 'array') : array();

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (!empty($id) && !empty($action) && !empty($htmlname) && $fk_product>0 && $fk_entrepot>0)
{
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

    $return     = array();
    $out        = '';
    //if (empty($showempty)) $showempty=0;

    $langs->load('equipement@equipement');

    // find all equipments for a product and warehouse
    $sql  = "SELECT e.rowid";
    $sql .= ", e.ref";
    $sql .= " FROM " . MAIN_DB_PREFIX . "equipement e";
    $sql .= " WHERE e.fk_statut >= 1";
    $sql .= " AND e.entity = " . $conf->entity;
    $sql .= " AND e.fk_product = " . $fk_product;
    $sql .= " AND e.fk_entrepot = " . $fk_entrepot;
    $sql .= " AND e.fk_commande_fourn = " . $id;
    $sql .= " ORDER BY e.ref DESC";

    $resql = $db->query($sql);
    if (!$resql) {
        $num = -1;
        $errorMsg = $db->lasterror();
    } else {
        $form = new Form($db);

        $optionList = array();
        $num = $db->num_rows($resql);
        if ($num) {
            while ($obj = $db->fetch_object($resql)) {
                $optionList[$obj->rowid] = $obj->ref;
            }
        } else {
            //$optionList[-1] = $langs->trans("NoEquipementsFind");
        }

        $out = Form::multiselectarray($htmlname, $optionList, $id_equipement_list, 0, 0, '', 0, 200);
    }

    $return['value'] = $out;
    $return['num']	 = $num;
    $return['error'] = $errorMsg;

    echo json_encode($return);
}

$db->close();
