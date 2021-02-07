<?php
/* Copyright (C) 2020-2021 Alexis LAURIER <contact@alexislaurier.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modSepaMandatManager_SepaMandatManagerTriggers.class.php
 * \ingroup sepamandatmanager
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modSepaMandatManager_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for SepaMandatManager module
 */
class InterfaceSepaMandatManagerDigitalSignatureDocumentSignedTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * @var String name of the element of digital signature document
	 */
	const DIGITAL_SIGNATURE_DOCUMENT_VALUE = "digitalsignaturedocument";

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "demo";
		$this->description = "SepaMandatManager triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'sepamandatmanager@sepamandatmanager';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->sepamandatmanager->enabled)) {
			return 0; // If module is not enabled, we do nothing
		}

		if (
			$object->element == self::DIGITAL_SIGNATURE_DOCUMENT_VALUE
			&& $object->elementtype == SepaMandat::$staticElement
			&& !empty($object->fk_object)
		) {
			$sepaMandateToUpdate = new SepaMandat($this->db);
			if ($sepaMandateToUpdate->fetch($object->fk_object) > 0) {
				switch ($action) {
					case 'DIGITALSIGNATUREDOCUMENT_SIGNED':
						$sepaMandateToUpdate->setSigned($user);
						break;
					case 'DIGITALSIGNATUREDOCUMENT_REFUSED':
					case 'DIGITALSIGNATUREDOCUMENT_CANCELED':
					case 'DIGITALSIGNATUREDOCUMENT_FAILED':
					case 'DIGITALSIGNATUREDOCUMENT_EXPIRED':
					case 'DIGITALSIGNATUREDOCUMENT_DELETEDINPROVIDER':
						$sepaMandateToUpdate->setCanceled($user);
						break;
					default:
						dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
						break;
				}
			}
			$this->errors = $sepaMandateToUpdate->errors;
			return empty($sepaMandateToUpdate->errors) ? 1 : -1;
		}
		return 0;
	}
}
