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
 *       \file       htdocs/core/ajax/search_result_sirene.php
 *       \brief      File to load search result sirene
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');

$res=0;
if (!$res && file_exists('../../main.inc.php')) $res=@include '../../main.inc.php';
if (!$res && file_exists('../../../main.inc.php')) $res=@include '../../../main.inc.php';
if (!$res) die('Include of main fails');


$socid = GETPOST('socid', 'int');
$company_infos = array(
    'company_name' => GETPOST('sirene_company_name', 'alpha'),
    'address' => GETPOST('sirene_address', 'alpha'),
    'zipcode' => GETPOST('sirene_zipcode', 'alpha'),
    'town' => GETPOST('sirene_town', 'alpha'),
    'siren' => GETPOST('sirene_siren', 'alpha'),
    'siret' => GETPOST('sirene_siret', 'alpha'),
    'codenaf_san' => GETPOST('sirene_naf', 'alpha'),
    'sirene_tva_intra' => GETPOST('sirene_tva_intra', 'alpha'),
    'country' => GETPOST('sirene_country', 'alpha'),
    'country_id' => GETPOST('sirene_country_id', 'alpha'),

);


/*
 * View
 */

top_httphead();

$return = array(
    'html' => '',
    'error' => 0
);

if ($socid > 0 && !empty($company_infos['siret']))
{
    //chargement des variables globales
    global $db, $langs;

    require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

    $langs->load('sirene@sirene');

    $societe = new Societe($db);
    $societe->fetch($socid);

    $correspondance = array(
        'company_name' => array('title' => $langs->trans("SireneCompanyName"), 'dolibarr' => $societe->name, 'sirene' => $company_infos['company_name']),
        'address' => array('title' => $langs->trans("Address"), 'dolibarr' => $societe->address, 'sirene' => $company_infos['address']),
        'zipcode' => array('title' => $langs->trans("CompanyZip"), 'dolibarr' => $societe->zip, 'sirene' => $company_infos['zipcode']),
        'town' => array('title' => $langs->trans("SireneTown"), 'dolibarr' => $societe->town, 'sirene' => $company_infos['town']),
        'siren' => array('title' => $langs->trans("SireneSiren"), 'dolibarr' => $societe->idprof1, 'sirene' => $company_infos['siren']),
        'siret' => array('title' => $langs->trans("SireneSiret"), 'dolibarr' => $societe->idprof2, 'sirene' => $company_infos['siret']),
        'codenaf_san' => array('title' => $langs->trans("SireneNaf"), 'dolibarr' => $societe->idprof3, 'sirene' => $company_infos['codenaf_san']),
        'sirene_tva_intra' => array('title' => $langs->trans("SireneTvaIntra"), 'dolibarr' => $societe->tva_intra, 'sirene' => $company_infos['sirene_tva_intra']),
        'country' => array('title' => $langs->trans("SireneCountry"), 'dolibarr' => $societe->country, 'sirene' => $company_infos['country']),
        'country_id' => array('title' => $langs->trans("SireneCountryISO"), 'dolibarr' => $societe->country_id, 'sirene' => $company_infos['country_id']),
    );

    //chargement du fichier de langues
    $langs->load('sirene@sirene');
    $table_choices  = '';
    $table_choices .= '<form action="' . dol_buildpath('/societe/card.php', 1) . '?socid=' .  $socid . '" name="sirene_update_form" method="post">';
    $table_choices .= '<input type="hidden" name="token" value="' . newToken() .'" />';
    $table_choices .= '<input type="hidden" name="action" value="confirm_sirene_update" />';
    $table_choices .= '<input type="hidden" name="confirm" value="yes" />';
    $table_choices .= '<div class="div-table-responsive">';
    $table_choices .= '<table class="noborder centpercent">' . "\n";

    $table_choices .= '<tr class="liste_titre">' . "\n";
    $table_choices .= '<td>' .'<span class="liste_titre" style="font-size: 12px; font-size: large;" title="Nom des champs à mettre à jour">Nom du champs</span>'. '</td>' . "\n";
    $table_choices .= '<td>' .'<span class="liste_titre" style="font-size: 12px; font-size: large;" title="Données dans la base de données Dolibarr">Données existantes dans Dolibarr</span>'. '</td>' . "\n";
    $table_choices .= '<td>' .'<span class="fas fa-caret-left marginleftonlyshort valignmiddle" style="font-size: 28px" title="Cocher les différentes cases pour mettre à jour les informations du tiers"></span>' .'</td>' . "\n";
    $table_choices .= '<td><input type="checkbox" id="checkallactions" value="1"></td>' . "\n";
    $table_choices .= '<td>' .'<span class="liste_titre" style="font-size: 12px; font-size: large;" title="Données récupéré via Sirene">Résultat extrait de la base Sirene</span>' .'</td>' . "\n";
    $table_choices .= '</tr>' . "\n";

    $table_choices .=  '<br><br>';

    foreach ($correspondance as $key => $infos) {
        $table_choices .= '<input type="hidden" id="' . $key . '" name="' . $key . '" value="' . $infos['sirene']  . '" />';
        if ($key != 'country_id') {
            $table_choices .= '<tr>';
            $table_choices .= '<td>' . $infos['title'] . '</td>';
            $table_choices .= '<td>' . $infos['dolibarr'] . '</td>';
            $table_choices .= '<td>' . $infos['<-'] . '</td>';

            //select all checkbox
            if ($key == 'country') {
                $table_choices .= '<td>' . '<input type="checkbox" id="choice_' . $key . '_id" name="choice_' . $key . '_id" class="checkforselect" value="1" ' . ($infos['dolibarr'] == $infos['sirene'] ? 'disabled="disabled"' : '') . '>' . '</td>';
            } else {
                $table_choices .= '<td>' . '<input type="checkbox" id="choice_' . $key . '" name="choice_' . $key . '" class="checkforselect" value="1" ' . ($infos['dolibarr'] == $infos['sirene'] ? 'disabled="disabled"' : '') . '>' . '</td>';
            }
            $table_choices .= '<td>' . $infos['sirene'] . '</td>';
            $table_choices .= '</tr>' . "\n";
        }
    }
    $table_choices .= '<div class="center">' . "\n";
    $table_choices .= '<td colspan="5" class="center"><input type="submit" id="sirene_update_valid" name="sirene_update_valid" class="button" value="' . $langs->trans("Valid") . '"></td>' . "\n";
    $table_choices .= '</div>' . "\n";

    $table_choices .= '</table>' . "\n";
    $table_choices .= '</div>' . "\n";
    $table_choices .= '</form>';

    require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
    $path = '';        // This value may be used in future for external module to overwrite theme
    $theme = 'eldy';    // Value of theme
    if (!empty($conf->global->MAIN_OVERWRITE_THEME_RES)) {
        $path = '/' . $conf->global->MAIN_OVERWRITE_THEME_RES;
        $theme = $conf->global->MAIN_OVERWRITE_THEME_RES;
    }

    include dol_buildpath($path . '/theme/' . $theme . '/theme_vars.inc.php');
    // Case of option availables only if THEME_ELDY_ENABLE_PERSONALIZED is on
    $colorbackhmenu1 = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $conf->global->THEME_ELDY_TOPMENU_BACK1) : (empty($user->conf->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $user->conf->THEME_ELDY_TOPMENU_BACK1);

    // Set text color to black or white
    $colorbackhmenu1 = join(',', colorStringToArray($colorbackhmenu1));    // Normalize value to 'x,y,z'
    $tmppart = explode(',', $colorbackhmenu1);
    $tmpval = (!empty($tmppart[0]) ? $tmppart[0] : 0) + (!empty($tmppart[1]) ? $tmppart[1] : 0) + (!empty($tmppart[2]) ? $tmppart[2] : 0);
    if ($tmpval <= 460) $colortextbackhmenu = 'FFFFFF';
    else $colortextbackhmenu = '000000';
    // script to select all checkbox
    $outJS = <<<SCRIPT
    <script type="text/javascript">
        $(document).ready(function() {
            $("#checkallactions").click(function() {
                if($(this).is(':checked')){
                    $(".checkforselect:not(:disabled)").prop('checked', true).trigger('change');
                } else {
                    $(".checkforselect").prop('checked', false).trigger('change');
                }
            });
        	
            $(".checkforselect").change(function() {
                $(this).closest("tr").toggleClass("highlight", this.checked);
            });      
            $('#dialog-confirm').on("dialogopen", function( event, ui ) {
        $('#dialog-confirm').parent().find('.liste_titre').addClass('sirene_colortitre');
        });
 	    });

    </script>
            <style>
            .sirene_title {
                background-color: rgb($colorbackhmenu1) !important;
                color: #$colortextbackhmenu !important;
            }
            .sirene_colortitre{
            background-color: rgb($colorbackhmenu1) !important;
                color: #$colortextbackhmenu !important;
            }
            
        </style>


SCRIPT;
    $table_choices .= $outJS;

    $return['html'] = $table_choices;
}

echo json_encode($return);
