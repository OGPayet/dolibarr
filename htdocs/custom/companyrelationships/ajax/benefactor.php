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
 *       \file       htdocs/companyrelationships/ajax/benefactor.php
 *       \brief      File to load benefactors combobox
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

$id       = GETPOST('id','int'); // socid
$action   = GETPOST('action','alpha');
$htmlname = GETPOST('htmlname','alpha');

// more_data
$fk_soc_benefactor = GETPOST('fk_soc_benefactor', 'int');
$origin = GETPOST('origin', 'alpha');
$originid = GETPOST('originid', 'int');


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

    $companies = $form->select_thirdparty_list('', $htmlname, '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 1, 0, 0, null, '', 1);

    dol_include_once('/companyrelationships/class/companyrelationships.class.php');
    $companyrelationships = new CompanyRelationships($db);
    $benefactor_ids = $companyrelationships->getRelationshipsThirdparty($id, CompanyRelationships::RELATION_TYPE_BENEFACTOR, 1);
    $benefactor_ids = is_array($benefactor_ids) ? $benefactor_ids : array();

    // determine selected company id by default
    if (!empty($fk_soc_benefactor)) {
        $selectedCompanyId = $fk_soc_benefactor;
    } else {
        if (!empty($origin) && !empty($originid)) {
            $selectedCompanyId = $id;
        } else {
            if (count($benefactor_ids) > 0) {
                $selectedCompanyId = $benefactor_ids[0];
            } else {
                $selectedCompanyId = $id;
            }
        }
    }

    $arrayresult = [];
    $others = [];
    foreach ($companies as $company) {
        if (in_array($company['key'], $benefactor_ids)) {
            $selected = '';
            if ($company['key'] == $selectedCompanyId) {
                $selected = ' selected="selected"';
            }

            $arrayresult[] = '<option value="' . $company['key'] . '"' . $selected . '>'.(preg_match('/\s\*$/',$company['label']) !== false ? $company['label'] . ' *' : $company['label']).'</option>';
        } else {
            $selected = '';
            if ($company['key'] == $selectedCompanyId) {
                $selected = ' selected="selected"';
            }
            $others[] = '<option value="' . $company['key'] . '"' . $selected . '>' . $company['label'] . '</option>';
        }
    }
    //$options = array_merge($arrayresult, array('<option value="0">&nbsp;</option>'), $others);
    $options = array_merge($arrayresult, $others);

    $return['value']	= implode('', $options);
    $return['num']		= $form->result['nbofthirdparties'];
    $return['error']	= $form->error;

    echo json_encode($return);
}

$db->close();
