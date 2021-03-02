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
        $benefactor_name = $societe->nom;

        $substitutionarray['__BENEFACTOR_NAME__'] = $benefactor_name;
    }

    if ($object->array_options['options_companyrelationships_fk_soc_watcher']) {
        $societe = new Societe($db);
        $societe->fetch($object->array_options['options_companyrelationships_fk_soc_watcher']);
        $watcher_name = $societe->nom;

        $substitutionarray['__WATCHER_NAME__'] = $watcher_name;
    }
}
