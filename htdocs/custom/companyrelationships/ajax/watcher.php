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
 *       \file       htdocs/companyrelationships/ajax/watcher.php
 *       \brief      File to load watchers combobox
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
            // Parse element/subelement (ex: project_task)
            $element = $subelement = $origin;
            if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
                $element = $regs [1];
                $subelement = $regs [2];
            }

            // For compatibility
            if ($element == 'order') {
                $element = $subelement = 'commande';
            }
            if ($element == 'propal') {
                $element = 'comm/propal';
                $subelement = 'propal';
            }
            if ($element == 'contract') {
                $element = $subelement = 'contrat';
            }
            if ($element == 'inter') {
                $element = $subelement = 'ficheinter';
            }
            if ($element == 'shipping') {
                $element = $subelement = 'expedition';
            }

            dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

            $classname = ucfirst($subelement);
            $objectsrc = new $classname($db);
            $objectsrc->fetch($originid);

            $objectsrc_thirdparty = $objectsrc->fetch_thirdparty();
            $selectedCompanyId = $objectsrc_thirdparty->id;
        } else {
            if (count($relation_ids) > 0) {
                $selectedCompanyId = current($relation_ids);
            } else {
                $selectedCompanyId = '';
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
	$arrayEmpty = array();
    if ($showempty) {
        if ($hasAtLeastOneSelected) {
            $arrayEmpty[] = '<option value="-1">&nbsp;</option>';
        } else {
            $arrayEmpty[] = '<option value="-1" selected="selected">&nbsp;</option>';
        }
	}
	$options = array_merge($arrayEmpty, $arrayresult, $others);
    $return['value'] = implode('', $options);
    $return['num']   = $form->result['nbofthirdparties'];
    $return['error'] = $form->error;

    echo json_encode($return);
}

$db->close();
