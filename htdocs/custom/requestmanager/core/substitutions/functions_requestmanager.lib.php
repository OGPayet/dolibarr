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
 *	\file       htdocs/requestmanager/lib/functions_requestmanager.lib.php
 *	\brief      Ensemble de fonctions de substitutions pour le module Request Manager
 * 	\ingroup	requestmanager
 */

function requestmanager_completesubstitutionarray(&$substitutionarray, $langs, $object, $parameters) {
    global $conf, $db, $langs;

    if ($object->element == 'product' && $parameters['needforkey'] == 'SUBSTITUTION_REQUESTMANAGERTABLABEL') {
        $nbrequests = 0;
        $sql = "SELECT COUNT(rm.rowid) as nb FROM ".MAIN_DB_PREFIX."requestmanager as rm LEFT JOIN ".MAIN_DB_PREFIX."element_element as ee ON ee.fk_source =  WHERE rm.entity = {$conf->entity} AND fk_product = {$object->id}";
        $resql = $db->query($sql);
        if ($resql) {
            if ($obj = $db->fetch_object($resql)) {
                $nbrequests = $obj->nb;
            }
        } else {
            dol_print_error($db);
        }

        $substitutionarray['REQUESTMANAGERTABLABEL'] = $langs->trans("RequestManagerRequestTab") . ($nbrequests > 0 ? ' <span class="badge">' . ($nbrequests) . '</span>' : '');
    } elseif ($object->element == 'societe' && $parameters['needforkey'] == 'SUBSTITUTION_RMLISTTABLABEL') {
        $nbrequests = 0;
        $sql = "SELECT COUNT(rm.rowid) as nb FROM ".MAIN_DB_PREFIX."requestmanager as rm WHERE rm.entity = {$conf->entity} AND (rm.fk_soc_origin = {$object->id} OR rm.fk_soc = {$object->id} OR rm.fk_soc_benefactor = {$object->id})";
        $resql = $db->query($sql);
        if ($resql) {
            if ($obj = $db->fetch_object($resql)) {
                $nbrequests = $obj->nb;
            }
        } else {
            dol_print_error($db);
        }

        $substitutionarray['RMLISTTABLABEL'] = $langs->trans("RequestManagerRequestListTab") . ($nbrequests > 0 ? ' <span class="badge">' . ($nbrequests) . '</span>' : '');
    } elseif ($object->element == 'societe' && $parameters['needforkey'] == 'SUBSTITUTION_RMPLANNINGLISTTABLABEL') {
            $nbrequests = 0;
            $request_types_planned = !empty($conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) ? explode(',', $conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) : array();

            if (count($request_types_planned) > 0) {
                $sql = "SELECT COUNT(rm.rowid) as nb FROM " . MAIN_DB_PREFIX . "requestmanager as rm WHERE rm.entity = {$conf->entity} AND (rm.fk_soc_origin = {$object->id} OR rm.fk_soc = {$object->id} OR rm.fk_soc_benefactor = {$object->id})";
                $status_filter = array();
                foreach ($request_types_planned as $request_type_id) {
                    if ($conf->global->{'REQUESTMANAGER_PLANNING_REQUEST_STATUS_TO_PLAN_' . $request_type_id} > 0) {
                        $status_filter[] = $conf->global->{'REQUESTMANAGER_PLANNING_REQUEST_STATUS_TO_PLAN_' . $request_type_id};
                    }
                }
                $sql .= " AND rm.fk_status IN (" . implode(',', $status_filter) . ")";
                $resql = $db->query($sql);
                if ($resql) {
                    if ($obj = $db->fetch_object($resql)) {
                        $nbrequests = $obj->nb;
                    }
                } else {
                    dol_print_error($db);
                }
            }

            $substitutionarray['RMPLANNINGLISTTABLABEL'] = $langs->trans("RequestManagerRequestPlanningListTab") . ($nbrequests > 0 ? ' <span class="badge">' . ($nbrequests) . '</span>' : '');
        }
}