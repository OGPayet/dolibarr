<?php
 /* Copyright (C) 2020-2021 Alexis LAURIER <contact@alexislaurier.fr>
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
dol_include_once('/sepamandatmanager/class/sepamandat.class.php');
/**
 *	\file       htdocs/companyrelationships/lib/functions_companyrelationships.lib.php
 *	\brief      Ensemble de fonctions de substitutions pour le module Company Relationships
 * 	\ingroup	companyrelationships
 */
function sepamandatmanager_completesubstitutionarray(&$substitutionarray, $langs, $object, $parameters)
{
	global $db, $langs;

	if ($object->element == 'societe' && $parameters['needforkey'] == 'SUBSTITUTION_SEPAMANDATTABLABEL') {

		$staticObject = new SepaMandat($db);
		$mandatesOfThisThirdparty = $staticObject->fetchAll('', '', 0, 0, array('fk_soc' => $object->id), 'AND');
		$nbOfMandat = count($mandatesOfThisThirdparty);
		$result =  $langs->trans("SepaMandatTab");
		if ($nbOfMandat > 0) {
			$result .=  ' <span class="badge">' . ($nbOfMandat) . '</span>';
		}
		$substitutionarray['SEPAMANDATTABLABEL'] = $result;
	}
}
