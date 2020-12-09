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

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for DigitalSignatureManager module
 */
class InterfaceDigitalSignatureManagerAutomaticEventCreationTriggers extends DolibarrTriggers
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
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->digitalsignaturemanager->enabled))
		{
			return 0; // If module is not enabled, we do nothing
		}

		if($object->element == 'digitalsignaturerequest') {
			$arrayOfTriggerCodeConfigurationAndEventContent = array(
			'DIGITALSIGNATUREREQUEST_CREATE'=>array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_REQUESTEVENT_CREATION,
				'title'=>$langs->trans('DigitalSignatureManagerRequestCreateEventTitle', $object->ref),
				'description'=>$langs->trans('DigitalSignatureManagerRequestCreateEventDescription', $object->ref)),
			'DIGITALSIGNATUREREQUEST_INPROGRESS' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_REQUESTEVENT_INPROGRESS,
				'title'=>$langs->trans('DigitalSignatureManagerRequestInProgressEventTitle', $object->ref),
				'description'=>$langs->trans('DigitalSignatureManagerRequestInProgressEventDescription', $object->ref)),
			'DIGITALSIGNATUREREQUEST_CANCELEDBYOPSY' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_REQUESTEVENT_CANCELEDBYOPSY,
				'title'=>$langs->trans('DigitalSignatureManagerRequestCanceledByOpsyEventTitle', $object->ref),
				'description'=>$langs->trans('DigitalSignatureManagerRequestCanceledByOpsyEventDescription', $object->ref)),
			'DIGITALSIGNATUREREQUEST_CANCELEDBYSIGNERS' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_REQUESTEVENT_CANCELEDBYSIGNERS,
				'title'=>$langs->trans('DigitalSignatureManagerRequestCanceledBySignersEventTitle', $object->ref),
				'description'=>$langs->trans('DigitalSignatureManagerRequestCanceledBySignersEventDescription', $object->ref)),
			'DIGITALSIGNATUREREQUEST_SUCCESS' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_REQUESTEVENT_SUCCESS,
				'title'=>$langs->trans('DigitalSignatureManagerRequestSuccessEventTitle', $object->ref),
				'description'=>$langs->trans('DigitalSignatureManagerRequestSuccessEventDescription', $object->ref)),
			'DIGITALSIGNATUREREQUEST_FAILED' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_REQUESTEVENT_FAILED,
				'title'=>$langs->trans('DigitalSignatureManagerRequestFailedEventTitle', $object->ref),
				'description'=>$langs->trans('DigitalSignatureManagerRequestFailedEventDescription', $object->ref)),
			'DIGITALSIGNATUREREQUEST_EXPIRED' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_REQUESTEVENT_EXPIRED,
				'title'=>$langs->trans('DigitalSignatureManagerRequestExpiredEventTitle', $object->ref),
				'description'=>$langs->trans('DigitalSignatureManagerRequestExpiredEventDescription', $object->ref)),
			'DIGITALSIGNATUREREQUEST_DELETEDINPROVIDER' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_REQUESTEVENT_DELETEDINPROVIDER,
				'title'=>$langs->trans('DigitalSignatureManagerRequestDeletedInProviderEventTitle', $object->ref),
				'description'=>$langs->trans('DigitalSignatureManagerRequestDeletedInProviderEventDescription', $object->ref)),
			'DIGITALSIGNATUREREQUEST_DELETE'=>array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_REQUESTEVENT_DELETE,
				'title'=>$langs->trans('DigitalSignatureManagerRequestDeleteEventTitle', $object->ref),
				'description'=>$langs->trans('DigitalSignatureManagerRequestDeleteEventDescription', $object->ref)),
			);
		}

		if($object->element == 'digitalsignaturepeople') {
			$arrayOfTriggerCodeConfigurationAndEventContent = array(
			'DIGITALSIGNATUREPEOPLE_CREATE' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_PEOPLEEVENT_CREATE,
				'title'=>$langs->trans('DigitalSignatureManagerPeopleCreateEventTitle', $object->displayName()),
				'description'=>$langs->trans('DigitalSignatureManagerPeopleCreateEventDescription', $object->displayName())),
			'DIGITALSIGNATUREPEOPLE_WAITINGTOSIGN' =>  array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_PEOPLEEVENT_WAITINGTOSIGN,
				'title'=>$langs->trans('DigitalSignatureManagerPeopleWaitingToSignEventTitle', $object->displayName()),
				'description'=>$langs->trans('DigitalSignatureManagerPeopleWaitingToSignEventDescription', $object->displayName())),
			'DIGITALSIGNATUREPEOPLE_SHOULDSIGN' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_PEOPLEEVENT_SHOULDSIGN,
				'title'=>$langs->trans('DigitalSignatureManagerPeopleShouldSignEventTitle', $object->displayName()),
				'description'=>$langs->trans('DigitalSignatureManagerPeopleShouldSignEventDescription', $object->displayName())),
			'DIGITALSIGNATUREPEOPLE_REFUSED' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_PEOPLEEVENT_REFUSED,
				'title'=>$langs->trans('DigitalSignatureManagerPeopleRefusedEventTitle', $object->displayName()),
				'description'=>$langs->trans('DigitalSignatureManagerPeopleRefusedEventDescription', $object->displayName())),
			'DIGITALSIGNATUREPEOPLE_ACCESSED' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_PEOPLEEVENT_ACCESSED,
				'title'=>$langs->trans('DigitalSignatureManagerPeopleAccessedEventTitle', $object->displayName()),
				'description'=>$langs->trans('DigitalSignatureManagerPeopleAccessedEventDescription', $object->displayName())),
			'DIGITALSIGNATUREPEOPLE_CODESENT' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_PEOPLEEVENT_CODESENT,
				'title'=>$langs->trans('DigitalSignatureManagerPeopleCodeSentEventTitle', $object->displayName()),
				'description'=>$langs->trans('DigitalSignatureManagerPeopleCodeSentEventDescription', $object->displayName())),
			'DIGITALSIGNATUREPEOPLE_PENDINGIDDOCS' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_PEOPLEEVENT_PENDINGDDOCS,
				'title'=>$langs->trans('DigitalSignatureManagerPeoplePendingDocsEventTitle', $object->displayName()),
				'description'=>$langs->trans('DigitalSignatureManagerPeoplePendingDocsEventDescription', $object->displayName())),
			'DIGITALSIGNATUREPEOPLE_PENDINGVALIDATION' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_PEOPLEEVENT_PENDINGVALIDATION,
				'title'=>$langs->trans('DigitalSignatureManagerPeoplePendingDocsValidationEventTitle', $object->displayName()),
				'description'=>$langs->trans('DigitalSignatureManagerPeoplePendingDocsValidationEventDescription', $object->displayName())),
			'DIGITALSIGNATUREPEOPLE_SUCCESS' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_PEOPLEEVENT_SUCCESS,
				'title'=>$langs->trans('DigitalSignatureManagerPeopleSuccessEventTitle', $object->displayName()),
				'description'=>$langs->trans('DigitalSignatureManagerPeopleSuccessEventDescription', $object->displayName())),
			'DIGITALSIGNATUREPEOPLE_FAILED' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_PEOPLEEVENT_FAILED,
				'title'=>$langs->trans('DigitalSignatureManagerPeopleFailedEventTitle', $object->displayName()),
				'description'=>$langs->trans('DigitalSignatureManagerPeopleFailedEventDescription', $object->displayName())),
			'DIGITALSIGNATUREPEOPLE_PROCESSSTOPPEDBEFORE' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_PEOPLEEVENT_PROCESSSTOPPEDBEFORE,
				'title'=>$langs->trans('DigitalSignatureManagerPeopleProcessStoppedBeforeEventTitle', $object->displayName()),
				'description'=>$langs->trans('DigitalSignatureManagerPeopleProcessStoppedBeforeEventDescription', $object->displayName())),
			'DIGITALSIGNATUREPEOPLE_DELETE' => array(
				'activated'=>$conf->global->DIGITALSIGNATUREMANAGER_PEOPLEEVENT_DELETE,
				'title'=>$langs->trans('DigitalSignatureManagerPeopleDeleteEventTitle', $object->displayName()),
				'description'=>$langs->trans('DigitalSignatureManagerPeopleDeleteEventDescription', $object->displayName())),
			);
		}

		if(!empty($arrayOfTriggerCodeConfigurationAndEventContent[$action]['activated']))
		{
			$titleAndDescription = $arrayOfTriggerCodeConfigurationAndEventContent[$action];

			// Insertion action
			$now = dol_now();
			dol_include_once('/comm/action/class/actioncomm.class.php');
			$actioncomm = new ActionComm($this->db);
			$actioncomm->type_code   = "AC_OTH_AUTO";		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
			$actioncomm->code        = 'AC_'.$action;
			$actioncomm->label       = $arrayOfTriggerCodeConfigurationAndEventContent[$action]['title'];
			$actioncomm->note        = $arrayOfTriggerCodeConfigurationAndEventContent[$action]['description'] . "<br>" . $langs->trans('DigitalSignatureManagerEventAuthor', $user->login);
			$actioncomm->fk_project  = $object->getLinkedProjectId();
			$actioncomm->datep       = $now;
			$actioncomm->datef       = $now;
			$actioncomm->percentage  = -1;   // Not applicable
			$actioncomm->socid       = $object->getLinkedThirdpartyId();
			$actioncomm->contactid   = $object->fk_people_type == 'contact' ? $object->fk_people_object : null;
			$actioncomm->authorid    = $user->id;   // User saving action
			$actioncomm->userownerid = $user->id;	// Owner of action
			$actioncomm->fk_element  = $object->id;
			$actioncomm->elementtype = $object->table_element;
			$ret=$actioncomm->create($user);       // User creating action
			return $ret;
		}
		dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
		return 0;
	}
}
