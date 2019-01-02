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

$socid    = GETPOST('id','int'); // socid
$action   = GETPOST('action','alpha');
$htmlname = GETPOST('htmlname','alpha');

// more_data
$relation_type  = GETPOST('relation_type', 'int');
$relation_socid = GETPOST('relation_socid', 'int');
$origin         = GETPOST('origin', 'alpha');
$originid       = GETPOST('originid', 'int');
$showempty      = GETPOST('showempty', 'int');


/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (! empty($socid) && ! empty($action) && ! empty($htmlname))
{
    $form = new Form($db);

    $return=array();
    if (empty($showempty)) $showempty=0;

    dol_include_once('/companyrelationships/class/companyrelationships.class.php');
    $companyrelationships = new CompanyRelationships($db);
    $relation_ids = $companyrelationships->getRelationshipsThirdparty($socid, $relation_type, 1);
    $relation_ids = is_array($relation_ids) ? $relation_ids : array();

    // determine selected company id by default
    if ($relation_socid=='' ||  $relation_socid>0) {
        $selectedCompanyId = $relation_socid;
    } else {
        if (!empty($origin) && !empty($originid)) {
            $selectedCompanyId = $socid;
        } else {
            if (count($relation_ids) > 0) {
                $selectedCompanyId = current($relation_ids);
            } else {
                $selectedCompanyId = $socid;
            }
        }
    }

    $companies = $form->select_thirdparty_list($selectedCompanyId, $htmlname, '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1', 1, 0, 0, null, '', 1);

    $hasAtLeastOneSelected = FALSE;
    $arrayresult = [];
    $others = [];
    foreach ($companies as $company) {
        if (in_array($company['key'], $relation_ids)) {
            $selected = '';
            if ($company['key'] == $selectedCompanyId) {
                $hasAtLeastOneSelected = TRUE;
                $selected = ' selected="selected"';
            }

            $arrayresult[] = '<option value="' . $company['key'] . '"' . $selected . '>'.(preg_match('/\s\*$/',$company['label']) !== false ? $company['label'] . ' *' : $company['label']).'</option>';
        } else {
            $selected = '';
            if ($company['key'] == $selectedCompanyId) {
                $hasAtLeastOneSelected = TRUE;
                $selected = ' selected="selected"';
            }
            $others[] = '<option value="' . $company['key'] . '"' . $selected . '>' . $company['label'] . '</option>';
        }
    }
    if ($showempty) {
        $options = array_merge($arrayresult, array('<option value=""' . ($hasAtLeastOneSelected ? '' : ' selected="selected"') . '>&nbsp;</option>'), $others);
    } else {
        $options = array_merge($arrayresult, $others);
    }

    $return['value']	= implode('', $options);
    $return['num']		= $form->result['nbofthirdparties'];
    $return['error']	= $form->error;

    echo json_encode($return);
}

$db->close();
