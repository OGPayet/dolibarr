<?php
/* Copyright (C) 2020 Alexis LAURIER
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        core/dictionaries/digitalsignaturedocumenttypemanager.class.php
 * \ingroup     digitalsignaturemanager
 * \brief       Class aims at managing document type
 */
class DigitalSignatureDocumentTypeManager
{

	/**
	 * @var string[] Core Module part managed by this dictionary
	 */
	const MANAGED_MODULE_PART = array('propal', 'sepamandatmanager_sepamandat');

	/**
	 * @var string[] Core Module part managed by this dictionary
	 */
	const MANAGED_CUSTOM_MODULE_PART = array('sepamandatmanager:sepamandat');

	/**
	 * @var string Linked document free type selected value
	 */
	const FREE_DOCUMENT_TYPE = 'freeDocument';

	/**
	 * Function that list all available mask of the current installation
	 * @param DoliDB $db Database instance to use
	 * @return array
	 */
	public static function getManagedDocumentSourceType($db)
	{
		global $langs;
		dol_include_once('/digitalsignaturemanager/lib/digitalsignaturemanagermaskgeneratorenumerator.class.php');
		$arrayOfMask = array();
		foreach (self::MANAGED_MODULE_PART as $modulePart) {
			$maskOfThisModulePart = DigitalSignatureManagerMaskGeneratorEnumerator::getAvailableModelsForCoreModulePart($db, $modulePart);
			$displayedMaskOfThisModulePart = array();
			foreach ($maskOfThisModulePart as $key => $displayName) {
				$displayedMaskOfThisModulePart[$key] = $modulePart . ' - ' . $displayName;
			}
			$arrayOfMask = array_merge($arrayOfMask, $displayedMaskOfThisModulePart);
		}
		foreach (self::MANAGED_CUSTOM_MODULE_PART as $modulePart) {
			$maskOfThisModulePart = DigitalSignatureManagerMaskGeneratorEnumerator::getAvailableModelsForCustomModulePart($db, $modulePart);
			$displayedMaskOfThisModulePart = array();
			foreach ($maskOfThisModulePart as $key => $displayName) {
				$displayedMaskOfThisModulePart[$key] = $modulePart . ' - ' . $displayName;
			}
			$arrayOfMask = array_merge($arrayOfMask, $displayedMaskOfThisModulePart);
		}
		$arrayOfMask[self::FREE_DOCUMENT_TYPE] = $langs->trans("DigitalSignatureManagerUploadedDocumentType");
		return $arrayOfMask;
	}
}
