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

function sepamandatmanager_completesubstitutionarray(&$substitutionarray, $langs, $object, $parameters) {
    global $db, $langs;

    if ($object->element == 'societe' && $parameters['needforkey'] == 'SUBSTITUTION_SEPAMANDATTABLABEL') {
		$nbOfMandat = 1;
		$result =  $langs->trans("SepaMandatTab");
		if($nbOfMandat > 0) {
			$result .=  ' <span class="badge">' . ($nbOfMandat) . '</span>';
		}
        $substitutionarray['SEPAMANDATTABLABEL'] = $result;
    }
}
