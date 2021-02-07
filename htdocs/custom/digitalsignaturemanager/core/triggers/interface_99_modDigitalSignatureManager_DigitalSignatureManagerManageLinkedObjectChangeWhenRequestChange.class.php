<?php
/* Copyright (C) 2020 Alexis LAURIER <contact@alexislaurier.fr>
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
 * \file    core/triggers/interface_99_modDigitalSignatureManager_DigitalSignatureManagerTriggers.class.php
 * \ingroup digitalsignaturemanager
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modDigitalSignatureManager_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';
dol_include_once("/digitalsignaturemanager/class/digitalsignaturerequestlinkedobject.class.php");

/**
 *  Class of triggers for DigitalSignatureManager module
 */
class InterfaceDigitalSignatureManagerManageLinkedObjectChangeWhenRequestChange extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

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
		$this->description = "DigitalSignatureManager triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'digitalsignaturemanager@digitalsignaturemanager';
		global $langs;
		$langs->load("digitalsignaturemanager@digitalsignaturemanager");
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
	 * @param DigitalSignatureRequest 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		dol_include_once("/digitalsignaturemanager/class/digitalsignaturerequest.class.php");
		if (empty($conf->digitalsignaturemanager->enabled) || $object->element != DigitalSignatureRequest::$staticElement) {
			return 0; // If module is not enabled or object not a digital signature request, we do nothing
		}

		$linkedObjectsInDolibarr = $object->getLinkedObjects();
		if (!$linkedObjectsInDolibarr) {
			return 0;
		}
		$errors = array();

		foreach ($linkedObjectsInDolibarr as $linkedObjectInDolibarr) {
			switch ($action) {
				case 'DIGITALSIGNATUREREQUEST_INPROGRESS':
					$label = $langs->trans("DigitalSignatureRequestStartAndSendByMail", $object->ref);
					$description = $langs->trans("DigitalSignatureRequestStartAndSendByMailDescription", $object->getNomUrl(1, '', 1));
					break;
				case 'DIGITALSIGNATUREREQUEST_CANCELEDBYSIGNERS':
					$label = $langs->trans("DigitalSignatureRequestCanceledBySigners", $object->ref);
					$description = $langs->trans("DigitalSignatureRequestCanceledBySignersDescription", $object->getNomUrl(1, '', 1));
					$this->manageRefusedLinkedSignature($object, $linkedObjectInDolibarr, $user);
					break;
				case 'DIGITALSIGNATUREREQUEST_EXPIRED':
					$label = $langs->trans("DigitalSignatureRequestExpired", $object->ref);
					$description = $langs->trans("DigitalSignatureRequestDescription", $object->getNomUrl(1, '', 1));
					$this->manageRefusedLinkedSignature($object, $linkedObjectInDolibarr, $user);
					break;
				case 'DIGITALSIGNATUREREQUEST_CANCELEDBYOPSY':
					$label = $langs->trans("DigitalSignatureRequestCanceledByOpsy", $object->ref);
					$description = $langs->trans("DigitalSignatureRequestCanceledByOpsyDescription", $object->getNomUrl(1, '', 1));
					break;
					break;
				case 'DIGITALSIGNATUREREQUEST_SUCCESS':
					$label = $langs->trans("DigitalSignatureRequestSuccesfullySigned", $object->ref);
					$description = $langs->trans("DigitalSignatureRequestSuccesfullySignedDescription", $object->getNomUrl(1, '', 1));
					$this->manageSuccessLinkedSignature($object, $linkedObjectInDolibarr, $user);
					break;
				case 'DIGITALSIGNATUREREQUEST_FAILED':
					break;
				case 'DIGITALSIGNATUREREQUEST_EXPIRED':
					break;
				case 'DIGITALSIGNATUREREQUEST_DELETEDINPROVIDER':
					break;
				default:
					dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
					break;
			}

			if (!empty($label)) {
				// Insertion action
				$now = dol_now();
				dol_include_once('/comm/action/class/actioncomm.class.php');
				$actioncomm = new ActionComm($this->db);
				$actioncomm->type_code   = "AC_OTH_AUTO";		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
				$actioncomm->code        = 'AC_' . $action;
				$actioncomm->label       = $label;
				$actioncomm->note        = $description . "<br>" . $langs->trans('DigitalSignatureManagerEventAuthor', $user->login);
				$actioncomm->fk_project  = $object->fk_project;
				$actioncomm->datep       = $now;
				$actioncomm->datef       = $now;
				$actioncomm->percentage  = -1;   // Not applicable
				$actioncomm->socid       = $linkedObjectInDolibarr->fk_soc ?? $linkedObjectInDolibarr->socid;;
				$actioncomm->authorid    = $user->id;   // User saving action
				$actioncomm->userownerid = $user->id;	// Owner of action
				$actioncomm->fk_element  = $linkedObjectInDolibarr->id;
				$actioncomm->elementtype = $linkedObjectInDolibarr->table_element;
				$actioncomm->create($user);       // User creating action
				$errors = array_merge($errors, $actioncomm->errors);
			}
		}

		$this->errors = array_merge($this->errors, $errors);
		return empty($errors) ? 0 : -1;
	}

	/**
	 * Function to change status of linked object on digital signature request when request is successfully signed
	 * @param DigitalSignatureRequest $digitalSignatureRequest digital signature request which has just been successfully signed
	 * @param CommonObject $linkedObject Dolibarr object instance on which we should change status
	 * @param User $user User requesting action
	 * @return bool
	 */
	private function manageSuccessLinkedSignature($digitalSignatureRequest, $linkedObject, $user)
	{
		global $langs;
		if ($linkedObject->element == 'propal') {
			$linkedObject->cloture($user, $linkedObject::STATUS_SIGNED, $langs->trans("DigitalSignatureRequestSuccesfullySignedDescription", $digitalSignatureRequest->ref));
		}
	}

	/**
	 * Function to change status of linked object on digital signature request when signer refused to sign
	 * @param DigitalSignatureRequest $digitalSignatureRequest digital signature request which has just been successfully signed
	 * @param CommonObject $linkedObject Dolibarr object instance on which we should change status
	 * @param User $user User requesting action
	 * @return bool
	 */
	private function manageRefusedLinkedSignature($digitalSignatureRequest, $linkedObject, $user)
	{
		global $langs;
		if ($linkedObject->element == 'propal') {
			$linkedObject->cloture($user, $linkedObject::STATUS_NOTSIGNED, $langs->trans("DigitalSignatureRequestCanceledByOpsyDescription", $digitalSignatureRequest->ref));
		}
	}
}
