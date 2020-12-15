<?php
/* Copyright (C) 2020 Alexis LAURIER
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


/**
 *  Class of triggers for DigitalSignatureManager module
 */
class InterfaceSepaMandatManagerAutomaticEventCreationTriggers extends DolibarrTriggers
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
		$this->description = "SepaMandatManager triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = 'development';
		$this->picto = 'sepamandatmanager@sepamandatmanager';
		global $langs;
		$langs->load("sepamandatmanager@sepamandatmanager");
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

		dol_include_once("/sepamandatmanager/class/sepamandat.class.php");

		if ($object->element == SepaMandat::$staticElement) {
			$arrayOfTriggerCodeConfigurationAndEventContent = array(
				'SEPAMANDAT_CREATE' => array(
					'activated' => $conf->global->SEPAMANDATE_SEPAMANDATEEVENT_CREATION,
					'title' => $langs->trans('SepaMandatCreateEventTitle', $object->ref),
					'description' => $langs->trans('SepaMandatCreateEventDescription', $object->ref)
				),
				'SEPAMANDAT_TOSIGN' => array(
					'activated' => $conf->global->SEPAMANDATE_SEPAMANDATEEVENT_TOSIGN,
					'title' => $langs->trans('SepaMandatToSignEventTitle', $object->ref),
					'description' => $langs->trans('SepaMandatToSignEventDescription', $object->ref)
				),
				'SEPAMANDAT_UNVALIDATE' => array(
					'activated' => $conf->global->SEPAMANDATE_SEPAMANDATEEVENT_UNVALIDATE,
					'title' => $langs->trans('SepaMandatUnValidateEventTitle', $object->ref),
					'description' => $langs->trans('SepaMandatUnValidateEventDescription', $object->ref)
				),
				'SEPAMANDAT_UNSIGNED' => array(
					'activated' => $conf->global->SEPAMANDATE_SEPAMANDATEEVENT_UNSIGNED,
					'title' => $langs->trans('SepaMandatSetBackToToSignEventTitle', $object->ref),
					'description' => $langs->trans('SepaMandatSetBackToToSignEventDescription', $object->ref)
				),
				'SEPAMANDAT_SIGNED' => array(
					'activated' => $conf->global->SEPAMANDATE_SEPAMANDATEEVENT_SIGNED,
					'title' => $langs->trans('SepaMandatSignedEventTitle', $object->ref),
					'description' => $langs->trans('SepaMandatSignedEventDescription', $object->ref)
				),
				'SEPAMANDAT_CANCELED' => array(
					'activated' => $conf->global->SEPAMANDATE_SEPAMANDATEEVENT_CANCELED,
					'title' => $langs->trans('SepaMandatCanceledEventTitle', $object->ref),
					'description' => $langs->trans('SepaMandatCanceledEventDescription', $object->ref)
				),
				'SEPAMANDAT_STALE' => array(
					'activated' => $conf->global->SEPAMANDATE_SEPAMANDATEEVENT_STALE,
					'title' => $langs->trans('SepaMandatStaleEventTitle', $object->ref),
					'description' => $langs->trans('SepaMandatStaleEventDescription', $object->ref)
				),
				'SEPAMANDAT_UNSTALE' => array(
					'activated' => $conf->global->SEPAMANDATE_SEPAMANDATEEVENT_UNSTALE,
					'title' => $langs->trans('SepaMandatUnStaleEventTitle', $object->ref),
					'description' => $langs->trans('SepaMandatUnStaleEventDescription', $object->ref)
				),
				'SEPAMANDAT_UNCANCELED' => array(
					'activated' => $conf->global->SEPAMANDATE_SEPAMANDATEEVENT_UNCANCELED,
					'title' => $langs->trans('SepaMandatUnCanceledEventTitle', $object->ref),
					'description' => $langs->trans('SepaMandatUnCanceledEventDescription', $object->ref)
				),
			);
		}

		if (!empty($arrayOfTriggerCodeConfigurationAndEventContent[$action]['activated'])) {
			$titleAndDescription = $arrayOfTriggerCodeConfigurationAndEventContent[$action];

			// Insertion action
			$now = dol_now();
			dol_include_once('/comm/action/class/actioncomm.class.php');
			$actioncomm = new ActionComm($this->db);
			$actioncomm->type_code   = "AC_OTH_AUTO";		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
			$actioncomm->code        = 'AC_' . $action;
			$actioncomm->label       = $titleAndDescription['title'];
			$actioncomm->note        = $titleAndDescription['description'] . "<br>" . $langs->trans('SepaMandatEventAuthor', $user->login);
			//$actioncomm->fk_project  = $object->getLinkedProjectId();
			$actioncomm->datep       = $now;
			$actioncomm->datef       = $now;
			$actioncomm->percentage  = -1;   // Not applicable
			$actioncomm->socid       = $object->fk_soc;
			//$actioncomm->contactid   = $object->fk_people_type == 'contact' ? $object->fk_people_object : null;
			$actioncomm->authorid    = $user->id;   // User saving action
			$actioncomm->userownerid = $user->id;	// Owner of action
			$actioncomm->fk_element  = $object->id;
			$actioncomm->elementtype = $object->element;
			$ret = $actioncomm->create($user);       // User creating action
			return $ret;
		}
		dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
		return 0;
	}
}
