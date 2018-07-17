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
 *       \file       htdocs/core/ajax/contacts.php
 *       \brief      File to load contacts combobox
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

$id			= GETPOST('id','int');
$action		= GETPOST('action','alpha');
$htmlname	= GETPOST('htmlname','alpha');
$showempty	= GETPOST('showempty','int');

/*
 * View
 */

top_httphead();

// Load original field value
if (! empty($id) && ! empty($action) && ! empty($htmlname)) {
    dol_include_once('/requestmanager/class/html.formrequestmanager.class.php');

    $formrequestmanager = new FormRequestManager($db);

    $return = array();
    if (empty($showempty)) $showempty = 0;

    $return['value'] = $formrequestmanager->select_category($id, '', $htmlname, $showempty, 0, array(), 0, 0, 'minwidth100', '', '', 1, '', 0, array(), true);
    $return['num'] = $formrequestmanager->num;
    $return['error'] = $formrequestmanager->error;

    echo json_encode($return);
}
