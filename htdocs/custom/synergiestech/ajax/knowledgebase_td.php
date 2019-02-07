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
 * \file htdocs/product/ajax/products.php
 * \brief File to return Ajax response on product list request
 */
if (! defined('NOTOKENRENEWAL'))
	define('NOTOKENRENEWAL', 1); // Disables token renewal
if (! defined('NOREQUIREMENU'))
	define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))
	define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))
	define('NOREQUIREAJAX', '1');
if (! defined('NOREQUIRESOC'))
	define('NOREQUIRESOC', '1');
if (! defined('NOCSRFCHECK'))
	define('NOCSRFCHECK', '1');
if (empty($_GET ['keysearch']) && ! defined('NOREQUIREHTML'))
	define('NOREQUIREHTML', '1');

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';		// to work if your module directory is into a subdir of root htdocs directory
if (! $res) die("Include of main fails");

$request_type = GETPOST('request_type', 'int');
$selected_tags_categories = GETPOST('selected_tags_categories', 'array');

/*
 * View
 */
dol_syslog(join(',', $_GET));

$error = 0;
$out = '';
$texts = array();
if ($request_type > 0) {
    $langs->load('requestmanager@requestmanager');
    dol_include_once('/synergiestech/class/html.formsynergiestechmessage.class.php');
    $formsynergiestechmessage = new FormSynergiesTechMessage($db);

    $result = $formsynergiestechmessage->fetchAllKnowledgeBase($request_type, $selected_tags_categories);
    if ($result < 0) {
        $out .= '<option value="none">' . $formsynergiestechmessage->error . '</option>' . "\n";
        $error++;
    } else {
        $modelknowledgebase_array = array();

        // Get selected knowledge base
        //----------------------------------
        if (count($formsynergiestechmessage->knowledge_base_list) > 0) {
            require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
            $out .= '<option class="optiongrey" value="-1">&nbsp;</option>' . "\n";
            foreach ($formsynergiestechmessage->knowledge_base_list as $line) {
                $out .= '<option value="' . $line->id . '">' . $line->fields['title'] . '</option>' . "\n";
                $texts[$line->id] = $line->fields['description'];
            }
        } else {
            // Do not put disabled on option, it is already on select and it makes chrome crazy.
            $out .= '<option value="none">' . $langs->trans("RequestManagerNoKnowledgeBaseDefined") . '</option>' . "\n";
            $error++;
        }
    }
} else {
    $langs->load('errors');
    $out .= '<option value="none">' . $langs->transnoentitiesnoconv('ErrorBadParameters') . '</option>' . "\n";
    $error++;
}

print json_encode(array('values' => $out, 'texts' => $texts, 'error' => ($error > 0)));
