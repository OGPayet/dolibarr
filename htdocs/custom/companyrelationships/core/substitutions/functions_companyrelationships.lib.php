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
 *  \file       htdocs/companyrelationships/lib/functions_companyrelationships.lib.php
 *  \brief      Ensemble de fonctions de substitutions pour le module Company Relationships
 *  \ingroup    companyrelationships
 */
dol_include_once("/companyrelationships/class/companyrelationships.class.php");

function companyrelationships_completesubstitutionarray(&$substitutionarray, $langs, $object, $parameters)
{
    global $conf, $db, $langs;

    if ($object->element == 'societe' && $parameters['needforkey'] == 'SUBSTITUTION_COMPANIESRELATIONSHIPLABEL') {
        $nbmaincompanies = 0;
        $sql = "SELECT COUNT(cr.rowid) as nb FROM " . MAIN_DB_PREFIX . "companyrelationships as cr WHERE cr.fk_soc_benefactor = {$object->id}";
        $resql = $db->query($sql);
        if ($resql) {
            if ($obj = $db->fetch_object($resql)) {
                $nbmaincompanies = $obj->nb;
            }
        } else {
            dol_print_error($db);
        }

        $nbbenefactorcompanies = 0;
        $sql = "SELECT COUNT(cr.rowid) as nb FROM " . MAIN_DB_PREFIX . "companyrelationships as cr WHERE cr.fk_soc = {$object->id}";
        $resql = $db->query($sql);
        if ($resql) {
            if ($obj = $db->fetch_object($resql)) {
                $nbbenefactorcompanies = $obj->nb;
            }
        } else {
            dol_print_error($db);
        }

        $substitutionarray['COMPANIESRELATIONSHIPLABEL'] = $langs->trans("CompanyRelationshipsTab") . ($nbmaincompanies > 0 || $nbbenefactorcompanies > 0 ? ' <span class="badge">' . ($nbmaincompanies) . '|' . ($nbbenefactorcompanies) . '</span>' : '');
    }

    $thirdPartySubstitutionArray = array(
        'ID' => 'id',
        'NAME' => 'name',
        'NAME_ALIAS' => 'name_alias',
        'CODE_CLIENT' => 'code_client',
        'CODE_FOURNISSEUR' => 'code_fournisseur',
        'EMAIL' => 'email',
        'PHONE' => 'phone',
        'FAX' => 'fax',
        'ADDRESS' => 'address',
        'ZIP' => 'zip',
        'TOWN' => 'town',
        'COUNTRY_ID' => 'country_id',
        'COUNTRY_CODE' => 'country_code',
        'IDPROF1' => 'idprof1',
        'IDPROF2' => 'idprof2',
        'IDPROF3' => 'idprof3',
        'IDPROF4' => 'idprof4',
        'IDPROF5' => 'idprof5',
        'IDPROF6' => 'idprof6',
        'TVAINTRA' => 'tva_intra',
        'NOTE_PUBLIC' => 'note_public',
        'NOTE_PRIVATE' => 'note_private'
    );
    $benefactor = new Societe($db);
    $watcher = new Societe($db);
    $benefactor->fetch($object->array_options['options_companyrelationships_fk_soc_benefactor']);
    $watcher->fetch($object->array_options['options_companyrelationships_fk_soc_watcher']);
    $companyObjects = array('BENEFACTOR' => $benefactor, 'WATCHER' => $watcher);
    foreach ($companyObjects as $prefix => $payload) {
        foreach ($thirdPartySubstitutionArray as $substitutionKey => $field) {
            $key = '__' . $prefix . '_' . $substitutionKey . '__';
            $substitutionarray[$key] = $key;
            if ($object->element) {
                $substitutionarray[$key] = '';
            }
            if (in_array($object->element, CompanyRelationships::$psa_element_list)) {
                $substitutionarray[$key] = $payload->$field;
            }
            if (in_array($field, array('note_public', 'note_private'))) {
                $substitutionarray[$key] = dol_htmlentitiesbr($substitutionarray[$key]);
            }
        }
    }
}
