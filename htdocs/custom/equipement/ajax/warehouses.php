<?php
/* Copyright (C) 2012 Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2016 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Open-DSI            <support@open-dsi.fr>
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
 *       \file       htdocs/custom/equipement/ajax/warehouses.php
 *       \brief      File to load warehouses combobox
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

dol_include_once('/factory/class/html.factoryformproduct.class.php');

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$htmlname	= GETPOST('htmlname','alpha');
$showempty	= GETPOST('showempty','int');


/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (! empty($id) && ! empty($action) && ! empty($htmlname))
{
    if ($action == 'getWarehouses') {
        $factoryFormProduct = new FactoryFormProduct($db);

        $return=array();

        $return['value'] = $factoryFormProduct->selectWarehouses('', 'add_product_entrepot_id', 'warehouseopen,warehouseinternal', 0, 0, $id, '', 0, 1, null, '',  '', 1, TRUE);
        $return['num']   = $factoryFormProduct->num;
        $return['error'] = $factoryFormProduct->error;

        echo json_encode($return);
    }
}