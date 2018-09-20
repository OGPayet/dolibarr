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

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (! empty($id) && ! empty($action) && ! empty($htmlname))
{
    $form = new Form($db);

    $return=array();
    if (empty($showempty)) $showempty=0;

    $companies = $form->select_thirdparty_list('', $htmlname, 'status=1', 1, 0, 0, null, '', 1);

    dol_include_once('/companyrelationships/class/companyrelationships.class.php');
    $companyrelationships = new CompanyRelationships($db);
    $benefactor_ids = $companyrelationships->getRelationships($id, 1);
    $benefactor_ids = is_array($benefactor_ids) ? $benefactor_ids : array();

    $arrayresult = [];
    $others = [];
    foreach ($companies as $company) {
        if (in_array($company['key'], $benefactor_ids)) {
            $arrayresult[] = '<option value="' . $company['key'] . '">'.(preg_match('/\s\*$/',$company['label']) !== false ? $company['label'] . ' *' : $company['label']).'</option>';
        } else {
            $others[] = '<option value="' . $company['key'] . '">' . $company['label'] . '</option>';
        }
    }
    $options = array_merge($arrayresult, array('<option value="-1">&nbsp;</option>'), $others);

    $return['value']	= implode('', $options);
    $return['num']		= $form->result['nbofthirdparties'];
    $return['error']	= $form->error;

    echo json_encode($return);
}

$db->close();
