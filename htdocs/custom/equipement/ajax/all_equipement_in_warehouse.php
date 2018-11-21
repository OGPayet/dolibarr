<?php
/* Copyright (C) 2012 Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2016 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2018 Open-DSI            <support@open-dsi.fr>
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
 *       \file       htdocs/custom/equipement/ajax/all_equipement_in_warehouse.php.php
 *       \brief      File to load all equipments in a warehouse
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

//require '../../main.inc.php';
$res=0;
if (! $res && file_exists("../../main.inc.php"))
    $res=@include("../../main.inc.php");		// For root directory
if (! $res && file_exists("../../../main.inc.php"))
    $res=@include("../../../main.inc.php");	// For "custom" directory

dol_include_once('/equipement/class/equipement.class.php');

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$htmlname	= GETPOST('htmlname','alpha');
$showempty	= GETPOST('showempty','int');

$id_component_product = GETPOST('id_component_product','int');
$id_entrepot = GETPOST('id_entrepot','int');

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (! empty($id) && ! empty($action) && ! empty($htmlname))
{
    if ($action == 'getAllEquipementInWarehouse') {
        $return = array();

        $num = 0;
        $errorMsg = '';

        $equipementStatic = new Equipement($db);

        // find all equipments for a product and warehouse
        $equipementList   = array();
        $idEquipementList = array();

        $resql = $equipementStatic->findAllByFkProductAndFkEntrepot($id_component_product, $id_entrepot);
        if (!$resql) {
            $num = -1;
            $errorMsg = 'Error : ' . $db->lasterror();
        } else {
            while ($obj = $db->fetch_object($resql)) {
                $equipementList[$obj->rowid] = $obj->ref;
                $num++;
            }
        }

        $return['value'] = Form::multiselectarray($htmlname, $equipementList, $idEquipementList, 0, 0, '', 0, 200);
        $return['num']   = $num;
        $return['error'] = $errorMsg;

        echo json_encode($return);
    }
}

$db->close();