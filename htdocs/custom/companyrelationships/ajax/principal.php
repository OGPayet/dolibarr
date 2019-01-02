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
 *       \file       htdocs/companyrelationships/ajax/principal.php
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

// more data
$formName = GETPOST('form_name','alpha');
$urlSrc   = GETPOST('url_src','alpha');

/*
 * View
 */

top_httphead();

//print '<!-- Ajax page called with url '.dol_escape_htmltag($_SERVER["PHP_SELF"]).'?'.dol_escape_htmltag($_SERVER["QUERY_STRING"]).' -->'."\n";

// Load original field value
if (! empty($id) && ! empty($action) && ! empty($htmlname) && ! empty($urlSrc))
{
    $return = array();

    dol_include_once('/companyrelationships/class/companyrelationships.class.php');
    dol_include_once('/companyrelationships/class/html.formcompanyrelationships.class.php');

    $langs->load('companyrelationships@companyrelationships');

    $out = '';

    $formcompanyrelationships = new FormCompanyRelationships($db);

    // get principal company
    $companyRelationships = new CompanyRelationships($db);
    $principalCompanyList = $companyRelationships->getRelationshipsThirdparty($id, CompanyRelationships::RELATION_TYPE_BENEFACTOR, 0, 1);
    $principalCompanyList = is_array($principalCompanyList) ? $principalCompanyList : array();
    if (count($principalCompanyList) > 0) {
        $principalCompanySelectArray = array();

        // format options in select principal company
        foreach($principalCompanyList as $companyId => $company) {
            $principalCompanySelectArray[$companyId] = $company->getFullName($langs);
        }

        $formQuestionList = array();
        $formQuestionList[] = array('name' => 'companyrelationships_fk_soc_benefactor', 'type' => 'hidden', 'value' => $id);
        $formQuestionList[] = array('label' => $langs->trans('CompanyRelationshipsPrincipalCompany'), 'name' => 'companyrelationships_socid', 'type' => 'select', 'values' => $principalCompanySelectArray, 'default' => '');

        // form confirm to choose the principal company
        $out .= $formcompanyrelationships->formconfirm_socid($urlSrc, $langs->trans('CompanyRelationshipsConfirmPrincipalCompanyTitle'), $langs->trans('CompanyRelationshipsConfirmPrincipalCompanyChoice'), 'companyrelationships_confirm_socid', $formQuestionList, '', 1, 200, 500, $formName);
    }

    $return['value'] = $out;
    $return['num']   = $formcompanyrelationships->form->result['nbofthirdparties'];
    $return['error'] = $formcompanyrelationships->form->error;

    echo json_encode($return);
}

$db->close();
