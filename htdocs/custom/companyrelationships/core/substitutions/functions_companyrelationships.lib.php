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
 *	\file       htdocs/companyrelationships/lib/functions_companyrelationships.lib.php
 *	\brief      Ensemble de fonctions de substitutions pour le module Company Relationships
 * 	\ingroup	companyrelationships
 */

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

    if ($object->array_options['options_companyrelationships_fk_soc_benefactor']) {
        $societe = new Societe($db);
        $societe->fetch($object->array_options['options_companyrelationships_fk_soc_benefactor']);

        $substitutionarray['__BENEFACTOR_ID__'] = (is_object($societe) ? $societe->id : '');
        $substitutionarray['__BENEFACTOR_NAME__'] = (is_object($societe) ? $societe->name : '');
        $substitutionarray['__BENEFACTOR_NAME_ALIAS__'] = (is_object($societe) ? $societe->name_alias : '');
        $substitutionarray['__BENEFACTOR_CODE_CLIENT__'] = (is_object($societe) ? $societe->code_client : '');
        $substitutionarray['__BENEFACTOR_CODE_FOURNISSEUR__'] = (is_object($societe) ? $societe->code_fournisseur : '');
        $substitutionarray['__BENEFACTOR_EMAIL__'] = (is_object($societe) ? $societe->email : '');
        $substitutionarray['__BENEFACTOR_PHONE__'] = (is_object($societe) ? $societe->phone : '');
        $substitutionarray['__BENEFACTOR_FAX__'] = (is_object($societe) ? $societe->fax : '');
        $substitutionarray['__BENEFACTOR_ADDRESS__'] = (is_object($societe) ? $societe->address : '');
        $substitutionarray['__BENEFACTOR_ZIP__'] = (is_object($societe) ? $societe->zip : '');
        $substitutionarray['__BENEFACTOR_TOWN__'] = (is_object($societe) ? $societe->town : '');
        $substitutionarray['__BENEFACTOR_COUNTRY_ID__'] = (is_object($societe) ? $societe->country_id : '');
        $substitutionarray['__BENEFACTOR_COUNTRY_CODE__'] = (is_object($societe) ? $societe->country_code : '');
        $substitutionarray['__BENEFACTOR_IDPROF1__'] = (is_object($societe) ? $societe->idprof1 : '');
        $substitutionarray['__BENEFACTOR_IDPROF2__'] = (is_object($societe) ? $societe->idprof2 : '');
        $substitutionarray['__BENEFACTOR_IDPROF3__'] = (is_object($societe) ? $societe->idprof3 : '');
        $substitutionarray['__BENEFACTOR_IDPROF4__'] = (is_object($societe) ? $societe->idprof4 : '');
        $substitutionarray['__BENEFACTOR_IDPROF5__'] = (is_object($societe) ? $societe->idprof5 : '');
        $substitutionarray['__BENEFACTOR_IDPROF6__'] = (is_object($societe) ? $societe->idprof6 : '');
        $substitutionarray['__BENEFACTOR_TVAINTRA__'] = (is_object($societe) ? $societe->tva_intra : '');
        $substitutionarray['__BENEFACTOR_NOTE_PUBLIC__'] = (is_object($societe) ? dol_htmlentitiesbr($societe->note_public) : '');
        $substitutionarray['__BENEFACTOR_NOTE_PRIVATE__'] = (is_object($societe) ? dol_htmlentitiesbr($societe->note_private) : '');
    }

    if ($object->array_options['options_companyrelationships_fk_soc_watcher']) {
        $societe = new Societe($db);
        $societe->fetch($object->array_options['options_companyrelationships_fk_soc_watcher']);

        $substitutionarray['__WATCHER_ID__'] = (is_object($societe) ? $societe->id : '');
        $substitutionarray['__WATCHER_NAME__'] = (is_object($societe) ? $societe->name : '');
        $substitutionarray['__WATCHER_NAME_ALIAS__'] = (is_object($societe) ? $societe->name_alias : '');
        $substitutionarray['__WATCHER_CODE_CLIENT__'] = (is_object($societe) ? $societe->code_client : '');
        $substitutionarray['__WATCHER_CODE_FOURNISSEUR__'] = (is_object($societe) ? $societe->code_fournisseur : '');
        $substitutionarray['__WATCHER_EMAIL__'] = (is_object($societe) ? $societe->email : '');
        $substitutionarray['__WATCHER_PHONE__'] = (is_object($societe) ? $societe->phone : '');
        $substitutionarray['__WATCHER_FAX__'] = (is_object($societe) ? $societe->fax : '');
        $substitutionarray['__WATCHER_ADDRESS__'] = (is_object($societe) ? $societe->address : '');
        $substitutionarray['__WATCHER_ZIP__'] = (is_object($societe) ? $societe->zip : '');
        $substitutionarray['__WATCHER_TOWN__'] = (is_object($societe) ? $societe->town : '');
        $substitutionarray['__WATCHER_COUNTRY_ID__'] = (is_object($societe) ? $societe->country_id : '');
        $substitutionarray['__WATCHER_COUNTRY_CODE__'] = (is_object($societe) ? $societe->country_code : '');
        $substitutionarray['__WATCHER_IDPROF1__'] = (is_object($societe) ? $societe->idprof1 : '');
        $substitutionarray['__WATCHER_IDPROF2__'] = (is_object($societe) ? $societe->idprof2 : '');
        $substitutionarray['__WATCHER_IDPROF3__'] = (is_object($societe) ? $societe->idprof3 : '');
        $substitutionarray['__WATCHER_IDPROF4__'] = (is_object($societe) ? $societe->idprof4 : '');
        $substitutionarray['__WATCHER_IDPROF5__'] = (is_object($societe) ? $societe->idprof5 : '');
        $substitutionarray['__WATCHER_IDPROF6__'] = (is_object($societe) ? $societe->idprof6 : '');
        $substitutionarray['__WATCHER_TVAINTRA__'] = (is_object($societe) ? $societe->tva_intra : '');
        $substitutionarray['__WATCHER_NOTE_PUBLIC__'] = (is_object($societe) ? dol_htmlentitiesbr($societe->note_public) : '');
        $substitutionarray['__WATCHER_NOTE_PRIVATE__'] = (is_object($societe) ? dol_htmlentitiesbr($societe->note_private) : '');
    }
}
