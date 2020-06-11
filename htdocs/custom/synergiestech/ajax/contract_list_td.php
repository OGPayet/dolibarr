<?php
/* Copyright (C) 2019   Open-Dsi    <support@open-dsi.fr>
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
 * \file htdocs/product/ajax/contract_list_td.php
 * \brief File to return html select of contract list with benefactor
 */
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', 1); // Disables token renewal
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX')) define('NOREQUIREAJAX', '1');
if (!defined('NOREQUIRESOC')) define('NOREQUIRESOC', '1');
if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');
if (empty($_GET['keysearch']) && !defined('NOREQUIREHTML')) define('NOREQUIREHTML', '1');

// Change this following line to use the correct relative path (../, ../../, etc)
$res = 0;
if (!$res && file_exists("../../main.inc.php")) $res = @include '../../main.inc.php';            // to work if your module directory is into a subdir of root htdocs directory
if (!$res && file_exists("../../../main.inc.php")) $res = @include '../../../main.inc.php';        // to work if your module directory is into a subdir of root htdocs directory
if (!$res) die("Include of main fails");

$soc_id = GETPOST('soc_id', 'int');
$company_benefactor_id = GETPOST('soc_benefactor_id', 'int');
$contract_id = GETPOST('contract_id', 'int');
$contract_ids = GETPOST('contract_ids', 'array');

/*
 * View
 */
//dol_syslog(join(',', $_GET));

$values = '';
$other = '';
if ($soc_id > 0) {
    dol_include_once("/synergiestech/class/html.formsynergiestech.class.php");
    $langs->loadLangs(array("contracts", 'synergiestech@synergiestech'));
    $synergiesTechForm = new FormSynergiesTech($db);
    $contractList = $synergiesTechForm->getListOfContractLabel($soc_id, $company_benefactor_id);

    $contract_ids_match = array_intersect($contract_ids, array_keys($contractList));
    if (!in_array($contract_id, $contract_ids_match)) {
        $contract_id = count($contract_ids_match) ? $contract_ids_match[0] : '';
    }
    $values .= '<option class="optiongrey" value="-1">&nbsp;</option>';
    foreach ($contractList as $id => $label) {
        $values .= '<option value="' . $id . '"' . ($contract_id == $id ? ' selected="selected"' : '') . '>' . $label . '</option>';
    }
    if (count($contractList) == 0) {
        $other = ' &nbsp; <a href="' . DOL_URL_ROOT . '/contrat/card.php?socid=' . $soc_id . '&options_companyrelationships_fk_soc_benefactor=' . $company_benefactor_id . '&action=create">' . $langs->trans("AddContract") . '</a>';
    }
}
print json_encode(array('values' => $values, 'other' => $other));
