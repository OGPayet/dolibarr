<?php
/* Copyright (C) 2020      Open-DSI             <support@open-dsi.fr>
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
 *       \file       htdocs/core/ajax/search_result.php
 *       \brief      File to load search result
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');

$res=0;
if (!$res && file_exists('../../main.inc.php')) $res=@include '../../main.inc.php';
if (!$res && file_exists('../../../main.inc.php')) $res=@include '../../../main.inc.php';
if (!$res) die('Include of main fails');

$socid = GETPOST('socid', 'int');
$sirene_only_open = GETPOST('sirene_only_open', 'int');
$sirene_company_name = GETPOST('sirene_company_name', 'alpha');
$sirene_siren = GETPOST('sirene_siren', 'alpha');
$sirene_siret = GETPOST('sirene_siret', 'alpha');
$sirene_naf = GETPOST('sirene_naf', 'alpha');
$sirene_zipcode = GETPOST('sirene_zipcode', 'alpha');
$sirene_number = GETPOSTISSET('sirene_number') ? GETPOST('sirene_number', 'int') : 20;

/*
 * View
 */

top_httphead();

$return = array(
    'html' => '',
    'error' => 0
);

if (!empty($sirene_company_name) || !empty($sirene_siret) || !empty($sirene_siren) || !empty($sirene_naf) || !empty($sirene_zipcode))
{
    //chargement des variables globales
    global $db, $langs;

    dol_include_once('/sirene/class/sirene.class.php');

    //chargement du fichier de langues
    $langs->load('sirene@sirene');

    $sirene = new Sirene($db);
    //$object->idprof2;
    // Connection to sirene API
    $result = $sirene->connection();
    if ($result < 0) {
        $return['error']++;
        $return['html'] = $langs->trans('SireneErrorWhileConnect');
    }

    $result = $sirene->getCompanies($sirene_company_name, $sirene_siren, $sirene_siret, $sirene_naf, $sirene_zipcode, $sirene_number, $sirene_only_open);
    if ($result < 0) {
        $return['error']++;
        $return['html'] = $langs->trans('SireneErrorWhileGetCompanies');
    } elseif (empty($sirene->companies_results)) {
        $return['error']++;
        $return['html'] = $langs->trans('SireneCompaniesNotFound');
    } else {
        $table_choices = '<table id="sirene_table" class="noborder" width="100%">' . "\n";
        $table_choices .= '<tr class="liste_titre">' . "\n";
        $table_choices .= '<td width="20px"></td>' . "\n";
        $table_choices .= '<td>' . $langs->trans("SireneCompanyName") . '</td>' . "\n";
        $table_choices .= '<td>' . $langs->trans("Address") . '</td>' . "\n";
        $table_choices .= '<td>' . $langs->trans("SireneCreateDate") . '</td>' . "\n";
        $table_choices .= '<td>' . $langs->trans("SireneCloseDate") . '</td>' . "\n";
        $table_choices .= '<td>' . $langs->trans("SireneNaf") . '</td>' . "\n";
        $table_choices .= '<td>' . $langs->trans("SireneSiren") . '</td>' . "\n";
        $table_choices .= '<td>' . $langs->trans("SireneSiret") . '</td>' . "\n";
        $table_choices .= '</tr>' . "\n";

        $checked = false;
        $nbLines = count($sirene->companies_results);
        foreach ($sirene->companies_results as $key => $company_infos) {
            $idLine = $key + 1;
            $table_choices .= '<tr class="oddeven">' . "\n";
            $table_choices .= '<td><input type="radio" name="sirene_choice" ';//checked="checked"
            if ($company_infos['status'] == "A" and !$checked) {
                $table_choices .= ' checked';
                $checked = true;
            } elseif ($company_infos['status'] == "F" and $checked) {
                $table_choices .= ' unchecked';
                $checked = true;
            }
            $table_choices .= ' value="' . $idLine . '"></td>' . "\n";
            $table_choices .= '<td><input type="hidden" id="sirene_company_name_' . $idLine . '" value="' . $company_infos['company_name'] . '" />' . $company_infos['company_name_all'] . '</td>' . "\n";
            $table_choices .= '<td>';
            $table_choices .= '<input type="hidden" id="sirene_address_' . $idLine . '" value="' . $company_infos['address'] . '" />';
            $table_choices .= '<input type="hidden" id="sirene_zipcode_' . $idLine . '" value="' . $company_infos['zipcode'] . '" />';
            $table_choices .= '<input type="hidden" id="sirene_town_' . $idLine . '" value="' . $company_infos['town'] . '" />';
            $table_choices .= '<input type="hidden" id="sirene_country_' . $idLine . '" value="' . $company_infos['country'] . '" />';
            $table_choices .= '<input type="hidden" id="sirene_country_id_' . $idLine . '" value="' . $company_infos['country_id'] . '" />';
            $table_choices .= '<input type="hidden" id="sirene_tva_intra_' . $idLine . '" value="' . $company_infos['sirene_tva_intra'] . '" />';
            $table_choices .= $company_infos['address_all'];
            $table_choices .= '</td>' . "\n";
            $table_choices .= '<td><input type="hidden" id="sirene_date_creation_' . $idLine . '" value="' . $company_infos['date_creation'] . '" />' . $company_infos['date_creation'] . '</td>' . "\n";
            $table_choices .= '<td><input type="hidden" id="sirene_status_' . $idLine . '" value="' . $company_infos['status'] . '" />' . $company_infos['status_all'] . '</td>' . "\n";
            $table_choices .= '<td><input type="hidden" id="sirene_naf_' . $idLine . '" value="' . $company_infos['codenaf_san'] . '" />' . $company_infos['codenaf_all'] . '</td>' . "\n";
            $table_choices .= '<td><input type="hidden" id="sirene_siren_' . $idLine . '" value="' . $company_infos['siren'] . '" />' . $company_infos['siren'] . '</td>' . "\n";
            $table_choices .= '<td><input type="hidden" id="sirene_siret_' . $idLine . '" value="' . $company_infos['siret'] . '" />' . $company_infos['siret'] . '</td>' . "\n";

            $table_choices .= '</tr>' . "\n";

        }
        $table_choices .= '<tr>' . "\n";
        $table_choices .= '<td colspan="8" class="center"><input type="submit" id="sirene_choice_valid" name="sirene_choice_valid" class="button" value="' . $langs->trans("Valid") . '"></td>' . "\n";
        $table_choices .= '</tr>' . "\n";
        $table_choices .= '</table>' . "\n";


        $outJSAjax  = '';
        $outJSAjax .= '     var idLineSelected = jQuery("input[name=\"sirene_choice\"]:checked").val();';
        $outJSAjax .= '     jQuery.ajax({';
        $outJSAjax .= '         method: "post",';
        $outJSAjax .= '         dataType: "json",';
        $outJSAjax .= '         data: {';
        $outJSAjax .= '             socid: "' . dol_escape_js($socid) . '",';
        $outJSAjax .= '             sirene_company_name: jQuery("#sirene_company_name_" + idLineSelected).val(),';
        $outJSAjax .= '             sirene_address: jQuery("#sirene_address_" + idLineSelected).val(),';
        $outJSAjax .= '             sirene_zipcode: jQuery("#sirene_zipcode_" + idLineSelected).val(),';
        $outJSAjax .= '             sirene_town: jQuery("#sirene_town_" + idLineSelected).val(),';
        $outJSAjax .= '             sirene_country: jQuery("#sirene_country_" + idLineSelected).val(),';
        $outJSAjax .= '             sirene_country_id: jQuery("#sirene_country_id_" + idLineSelected).val(),';
        $outJSAjax .= '             sirene_tva_intra: jQuery("#sirene_tva_intra_" + idLineSelected).val(),';
        $outJSAjax .= '             sirene_siren: jQuery("#sirene_siren_" + idLineSelected).val(),';
        $outJSAjax .= '             sirene_siret: jQuery("#sirene_siret_" + idLineSelected).val(),';
        $outJSAjax .= '             sirene_naf: jQuery("#sirene_naf_" + idLineSelected).val(),';
        $outJSAjax .= '         },';
        $outJSAjax .= '         url: "' . dol_buildpath('/sirene/ajax/search_result_sirene.php', 1) . '",';
        $outJSAjax .= '         error: function(response){';
        $outJSAjax .= '         },';
        $outJSAjax .= '         success: function(response){';
        $outJSAjax .= '             if (response.error > 0) {';
        $outJSAjax .= '                 jQuery("#sirene_search_result").html(response.html)';
        $outJSAjax .= '             } else {';
        $outJSAjax .= '                 jQuery("#sirene_search_result").html(response.html)';
        $outJSAjax .= '             }';
        $outJSAjax .= '         }';
        $outJSAjax .= '     });';
        $outJSAjax .= ' });';

        $outJS = '<script type="text/javascript">';
        $outJS .= 'jQuery(document).ready(function(){';
        // on dom ready
        if ($nbLines === 1) {
            $outJS .= $outJSAjax;
        } else {
            // button choice valid
            $outJS .= ' jQuery("#sirene_choice_valid").click(function(){';
            $outJS .= $outJSAjax;
            $outJS .= '});';
        }
        $outJS .= '</script>';

        $table_choices .= $outJS;

        $return['html'] = $table_choices;
    }
}

echo json_encode($return);
