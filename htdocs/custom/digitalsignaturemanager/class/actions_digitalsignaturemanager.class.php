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
 * \file    digitalsignaturemanager/class/actions_digitalsignaturemanager.class.php
 * \ingroup digitalsignaturemanager
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

dol_include_once('/digitalsignaturemanager/class/digitalsignaturerequestlinkedobject.class.php');

/**
 * Class ActionsDigitalSignatureManager
 */
class ActionsDigitalSignatureManager
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;

	/**
	 * @var FormDigitalSignatureRequestTemplate;
	 */
	public $formDigitalSignatureRequestTemplate;

	/**
	 * @var FormDigitalSignatureRequest;
	 */
	public $FormDigitalSignatureManager;

	/**
	 * @var FormDigitalSignatureRequest;
	 */
	public $formDigitalSignatureManager;

	/**
	 * Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		dol_include_once("/digitalsignaturemanager/class/html.formdigitalsignaturerequesttemplate.class.php");
		$this->formDigitalSignatureRequestTemplate = new FormDigitalSignatureRequestTemplate($this->db);
		dol_include_once("/digitalsignaturemanager/class/html.formdigitalsignaturemanager.class.php");
		$this->formDigitalSignatureManager = new FormDigitalSignatureManager($this->db);
		dol_include_once("/digitalsignaturemanager/class/html.formdigitalsignaturerequest.class.php");
		$this->formDigitalSignatureRequest = new FormDigitalSignatureRequest($this->db);
	}

	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$pdfhandler     PDF builder handler
	 * @param   string	$action         'add', 'update', 'view'
	 * @return  int 		            <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		$maskName = $pdfhandler->name;
		$fileFullPath = $parameters['file'];
		$this->updateEcmMetaData($fileFullPath, $maskName, $parameters['object']);
		return 0;
	}


	/**
	 * Execute action
	 *
	 * @param	array	$parameters     Array of parameters
	 * @param   Object	$pdfhandler     PDF builder handler
	 * @param   string	$action         'add', 'update', 'view'
	 * @return  int 		            <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterODTCreation($parameters, &$pdfhandler, &$action)
	{
		$fileFullPath = $parameters['file'];
		$object = $parameters['object'];
		$objectRef = $object->ref;
		$fileName = basename($fileFullPath);
		$fileFormat = substr($fileName, strrpos($fileName, '.') + 1);
		$startStringToRemoveOnFileName = $objectRef . '_';
		$endStringToRemoveOnFileName = '.' . $fileFormat;
		$subtring_start = strpos($fileName, $startStringToRemoveOnFileName);
		//Adding the strating index of the strating word to
		//its length would give its ending index
		$subtring_start += strlen($startStringToRemoveOnFileName);
		//Length of our required sub string
		$size = strpos($fileName, $endStringToRemoveOnFileName, $subtring_start) - $subtring_start;
		// Return the substring from the index substring_start of length size
		$maskName = substr($fileName, $subtring_start, $size);
		$this->updateEcmMetaData($fileFullPath, $maskName, $object);
		//pdf file
		$pathInfo = pathinfo($fileFullPath);
		$pdfFilePath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.pdf';
		if (file_exists($pdfFilePath)) {
			$this->updateEcmMetaData($pdfFilePath, $maskName, $object);
		}
		return 0;
	}

	/**
	 * Function to update or create ecm instance for a file and set maskName of it
	 * @param string $fileFullPath full path of the generated file
	 * @param string $maskName mask name used for the file to be generated
	 * @param CommonObject $object dolibarr object which generated files
	 * @return void
	 */
	public function updateEcmMetaData($fileFullPath, $maskName, $object)
	{
		global $user, $conf;

		if (!empty($fileFullPath)) {
			$staticEcm = new ExtendedEcm($this->db);
			$storedEcmFile = $staticEcm->getInstanceFileFromItsAbsolutePath($fileFullPath);
			$ecmFile = $storedEcmFile;
			if (!$ecmFile) {
				$ecmFile = new ExtendedEcm($this->db);
			}
			$ecmFile->gen_or_uploaded = 'generated';
			$ecmFile->mask = $maskName;
			$ecmFile->elementtype = $object->element;
			$ecmFile->fk_object = $object->id;

			if ($storedEcmFile) {
				$result = $ecmFile->update($user);
				if ($result < 0) {
					setEventMessages($ecmFile->error, $ecmFile->errors, 'errors');
				}
			} else {
				$ecmFile->entity = $conf->entity;
				$ecmFile->filepath = $ecmFile->getRelativeDirectoryOfAFile($fileFullPath);
				$ecmFile->filename = basename($fileFullPath);
				$ecmFile->label = md5_file(dol_osencode($fileFullPath)); // hash of file content
				$ecmFile->gen_or_uploaded = 'generated';
				$result = $ecmFile->create($user);
				if ($result < 0) {
					setEventMessages($ecmFile->error, $ecmFile->errors, 'errors');
				}
			}
		}
	}

	/**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array()         $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object        The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action        Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
	{
		global $user;
		$digitalSignatureRequestLinkedObject = new DigitalSignatureRequestLinkedObject($object);
		$isThereADigitalSignatureRequestInProgress = $digitalSignatureRequestLinkedObject->isThereADigitalSignatureInProgress();
		$isThereADigitalSignatureRequestInDraft = $digitalSignatureRequestLinkedObject->isThereADigitalSignatureInDraft();
		//Create request button
		$contexts = explode(':', $parameters['context']);
		if (in_array('propalcard', $contexts) && defined(get_class($object) . '::STATUS_VALIDATED') && $object->statut == $object::STATUS_VALIDATED && !$isThereADigitalSignatureRequestInProgress && $digitalSignatureRequestLinkedObject->isUserAbleToCreateRequest($user, $object) && !$isThereADigitalSignatureRequestInDraft) {
			print $this->formDigitalSignatureRequestTemplate->getCreateFromObjectButton($object->id);
		}
		if (!in_array('digitalsignaturerequestcard', $contexts) && $isThereADigitalSignatureRequestInProgress) {
			//refresh button
			print $this->formDigitalSignatureRequest->getRefreshButton($object->id, $digitalSignatureRequestLinkedObject->isUserAbleToRefreshRequest($user, $object));
			//reset request button
			if (defined(get_class($object) . '::STATUS_VALIDATED') && $object->statut == $object::STATUS_VALIDATED) {
				print $this->formDigitalSignatureRequest->getResetButton($object->id, $digitalSignatureRequestLinkedObject->isUserAbleToResetRequest($user, $object));
			}
			//cancel request button
			print $this->formDigitalSignatureRequest->getCancelButton($object->id, $digitalSignatureRequestLinkedObject->isUserAbleToCancelRequest($user, $object));
		}
		return 0;
	}

	/**
	 * Overloading the formConfirm function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function formConfirm($parameters, &$object, &$action, $hookmanager)
	{
		global $langs, $user;
		$contexts = explode(':', $parameters['context']);
		if (!in_array('digitalsignaturerequestcard', $contexts)) {
			$doesUserAskToCreateFromObject = $action == FormDigitalSignatureRequestTemplate::CREATE_FROM_OBJECT_ACTION_NAME;
			$doesUserAskToCreateFromSelectedFiles = $action == FormDigitalSignatureRequestTemplate::CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME;
			$doesUserAskToCancelRequest = $action == FormDigitalSignatureRequest::CANCEL_REQUEST_ACTION_NAME;
			$doesUserAskToResetRequest = $action == FormDigitalSignatureRequest::RESET_ACTION_NAME;

			$actionManagedHere = array($doesUserAskToCreateFromObject, $doesUserAskToCreateFromSelectedFiles, $doesUserAskToCancelRequest, $doesUserAskToResetRequest);
			$isThereAnActionManageHere = array_filter($actionManagedHere);
			if ($isThereAnActionManageHere) {
				$digitalSignatureRequestLinkedObject = new DigitalSignatureRequestLinkedObject($object);
				//Create request from linked object
				$isThereADigitalSignatureRequestInProgress = $digitalSignatureRequestLinkedObject->isThereADigitalSignatureInProgress($object);
				$isThereADigitalSignatureRequestInDraft = $digitalSignatureRequestLinkedObject->isThereADigitalSignatureInDraft($object);


				if ($doesUserAskToCreateFromObject || $doesUserAskToCreateFromSelectedFiles) {
					if (defined(get_class($object) . '::STATUS_VALIDATED') && $object->statut != $object::STATUS_VALIDATED) {
						setEventMessages($langs->trans("DigitalSignatureManagerObjectMustBeValidated"), array(), 'errors');
					} elseif ($isThereADigitalSignatureRequestInProgress || $isThereADigitalSignatureRequestInDraft) {
						setEventMessages($langs->trans("DigitalSignatureManagerAlreadyOneRequestInProgress"), array(), 'errors');
					} elseif (!$digitalSignatureRequestLinkedObject->isUserAbleToCreateRequest($user, $object)) {
						setEventMessages($langs->trans("DigitalSignatureManagerNotAllowedForUser"), array(), 'errors');
					} else {
						if ($doesUserAskToCreateFromObject) {
							$result = $this->formDigitalSignatureRequestTemplate->displayCreateFromObject($object, $action, $hookmanager);
						} elseif ($doesUserAskToCreateFromSelectedFiles) {
							$result = $this->formDigitalSignatureRequestTemplate->displayCreateFromSelectedFiles($object, null, $action, $hookmanager);
						}
					}
				}

				//Cancel request from linked object
				if ($doesUserAskToCancelRequest) {
					if (!$isThereADigitalSignatureRequestInProgress) {
						setEventMessages($langs->trans("DigitalSignatureManagerNoRequestInProgress"), array(), 'errors');
					} elseif (!$digitalSignatureRequestLinkedObject->isUserAbleToCancelRequest($user, $object)) {
						setEventMessages($langs->trans("DigitalSignatureManagerNotAllowedForUser"), array(), 'errors');
					} else {
						$result = $this->formDigitalSignatureRequest->getCancelFormConfirm($object->id);
					}
				}

				//Reset request from linked object
				if ($doesUserAskToResetRequest) {
					if (defined(get_class($object) . '::STATUS_VALIDATED') && $object->statut != $object::STATUS_VALIDATED) {
						setEventMessages($langs->trans("DigitalSignatureManagerObjectMustBeValidated"), array(), 'errors');
					} elseif (!$isThereADigitalSignatureRequestInProgress) {
						setEventMessages($langs->trans("DigitalSignatureManagerNoRequestInProgress"), array(), 'errors');
					} elseif (!$digitalSignatureRequestLinkedObject->isUserAbleToCancelRequest($user, $object) || !$digitalSignatureRequestLinkedObject->isUserAbleToCreateRequest($user, $object)) {
						setEventMessages($langs->trans("DigitalSignatureManagerNotAllowedForUser"), array(), 'errors');
					} else {
						$result = $this->formDigitalSignatureRequest->getResetFormConfirm($object->id);
					}
				}
			}
			if ($result) {
				//$this->resprints = $result;
				print $result;
				return 1;
			}
		}
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $user;
		global $langs;

		$doesUserEndWithToCreateFromObject = $action == FormDigitalSignatureRequestTemplate::CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME;
		$doesUserEndWIthCreateFromSelectedFiles = $action == FormDigitalSignatureRequestTemplate::CONFIRM_CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME;
		$doesUserConfirmToCancelRequest = $action == FormDigitalSignatureRequest::CONFIRM_CANCEL_REQUEST_ACTION_NAME;
		$doesUserConfirmToResetRequest = $action == FormDigitalSignatureRequest::CONFIRM_RESET_ACTION_NAME;
		$doesUserConfirmToRefreshData = $action == FormDigitalSignatureRequest::REFRESH_DATA_ACTION_NAME;

		$actionManagedHere = array($doesUserEndWithToCreateFromObject, $doesUserEndWIthCreateFromSelectedFiles, $doesUserConfirmToCancelRequest, $doesUserConfirmToResetRequest, $doesUserConfirmToRefreshData);
		$isThereAnActionManageHere = array_filter($actionManagedHere);

		$contexts = explode(':', $parameters['context']);

		if ($isThereAnActionManageHere && !in_array('digitalsignaturerequestcard', $contexts)) {
			$digitalSignatureRequestLinkedObject = new DigitalSignatureRequestLinkedObject($object);

			//Create request from linked object
			$isThereADigitalSignatureRequestInProgress = $digitalSignatureRequestLinkedObject->isThereADigitalSignatureInProgress($object);
			$isThereADigitalSignatureRequestInDraft = $digitalSignatureRequestLinkedObject->isThereADigitalSignatureInDraft($object);

			if ($doesUserEndWithToCreateFromObject || $doesUserEndWIthCreateFromSelectedFiles) {
				if ($isThereADigitalSignatureRequestInProgress || $isThereADigitalSignatureRequestInDraft) {
					setEventMessages($langs->trans("DigitalSignatureManagerAlreadyOneRequestInProgress"), array(), 'errors');
				} elseif (!$digitalSignatureRequestLinkedObject->isUserAbleToCreateRequest($user, $object)) {
					setEventMessages($langs->trans("DigitalSignatureManagerNotAllowedForUser"), array(), 'errors');
				} else {
					if ($doesUserEndWithToCreateFromObject) {
						$this->formDigitalSignatureRequestTemplate->manageCreateFromObject($action, $object);
					} elseif ($doesUserEndWIthCreateFromSelectedFiles) {
						$this->formDigitalSignatureRequestTemplate->manageCreateFromFiles($action, $object, $user, $hookmanager);
					}
				}
			}

			//Cancel request from linked object
			if ($doesUserConfirmToCancelRequest) {
				if (!$isThereADigitalSignatureRequestInProgress) {
					setEventMessages($langs->trans("DigitalSignatureManagerNoRequestInProgress"), array(), 'errors');
				} elseif (!$digitalSignatureRequestLinkedObject->isUserAbleToCancelRequest($user, $object)) {
					setEventMessages($langs->trans("DigitalSignatureManagerNotAllowedForUser"), array(), 'errors');
				} else {
					$this->formDigitalSignatureRequest->manageConfirmCancelAction($digitalSignatureRequestLinkedObject->getInProgressDigitalSignatureRequest($object), $user);
				}
			}

			//Reset request from linked object
			if ($doesUserConfirmToResetRequest) {
				if (!$isThereADigitalSignatureRequestInProgress) {
					setEventMessages($langs->trans("DigitalSignatureManagerNoRequestInProgress"), array(), 'errors');
				} elseif (!$digitalSignatureRequestLinkedObject->isUserAbleToResetRequest($user, $object)) {
					setEventMessages($langs->trans("DigitalSignatureManagerNotAllowedForUser"), array(), 'errors');
				} else {
					$this->formDigitalSignatureRequest->manageConfirmResetProcessAction($digitalSignatureRequestLinkedObject->getInProgressDigitalSignatureRequest($object), $user, false);
				}
			}

			//Refresh request data from linked object
			if ($doesUserConfirmToRefreshData) {
				if (!$isThereADigitalSignatureRequestInProgress) {
					setEventMessages($langs->trans("DigitalSignatureManagerNoRequestInProgress"), array(), 'errors');
				} elseif (!$digitalSignatureRequestLinkedObject->isUserAbleToRefreshRequest($user, $object)) {
					setEventMessages($langs->trans("DigitalSignatureManagerNotAllowedForUser"), array(), 'errors');
				} else {
					$this->formDigitalSignatureRequest->manageRefreshAction($digitalSignatureRequestLinkedObject->getInProgressDigitalSignatureRequest($object), $user);
				}
			}
		}

		//prevent modification of linked objects of a digital signature request
		if ($action == "addlink" || $action == "dellink") {
			$errors = array(); // Error array results
			$digitalSignatureRequestIds = array();
			if ($action == "addlink") {
				$sourceElementType = $object->element;
				$destinationElementType = GETPOST('addlink', 'alpha');
				if ($sourceElementType == DigitalSignatureRequest::$staticElement) {
					$digitalSignatureRequestIds = array($object->id);
				} elseif ($destinationElementType == DigitalSignatureRequest::$staticElement) {
					$digitalSignatureRequestIds = GETPOST('idtolinkto', 'array');
					if (empty($digitalSignatureRequestIds)) {
						$digitalSignatureRequestIds = array(GETPOST('idtolinkto', 'int'));
					}
				}
				$id = $object->id; //used for /core/actions_dellink.inc.php
			} elseif ($action == "dellink") {
				//here $action == "dellink"

				$sql = "SELECT fk_source, sourcetype, targettype, fk_target FROM " . MAIN_DB_PREFIX . "element_element WHERE rowid = " . GETPOST('dellinkid', 'int');

				$resql = $this->db->query($sql);
				if ($resql) {
					if ($obj = $this->db->fetch_object($resql)) {
						if ($obj->sourcetype == DigitalSignatureRequest::$staticElement) {
							$digitalSignatureRequestIds = array($obj->fk_source);
						} elseif ($obj->targettype == DigitalSignatureRequest::$staticElement) {
							$digitalSignatureRequestIds = array($obj->fk_target);
						}
					}
				} else {
					$errors[] = $this->db->lasterror();
				}
			}
			$digitalSignatureRequestIds = array_filter($digitalSignatureRequestIds);
			if (!empty($digitalSignatureRequestIds)) {
				$action = null;
				setEventMessages("DigitalSignatureRequestLinkAreProtected", array(), 'errors');
			}
		}
	}

	/**
	 * Overloading the showLinkedObjectBlock function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function showLinkedObjectBlock($parameters, &$object, &$action, $hookmanager)
	{
		$contexts = explode(':', $parameters['context']);
		if (!in_array('digitalsignaturerequestcard', $contexts)) {
			$digitalSignatureRequestLinkedObject = new DigitalSignatureRequestLinkedObject($object);
			//$linkedDigitalSignatureRequests = $digitalSignatureRequestLinkedObject->getLinkedDigitalSignatureRequests();
			$signedAndNotStaleLinkDigitalSignatureRequest = $digitalSignatureRequestLinkedObject->getEndedLinkedSignatureWithNoStaledData();
			if ($signedAndNotStaleLinkDigitalSignatureRequest) {
				print $this->formDigitalSignatureRequest->displayListOfSignedFiles($signedAndNotStaleLinkDigitalSignatureRequest, $_SERVER["PHP_SELF"] . "?id=" . $object->id);
			}
			//print $this->formDigitalSignatureManager->showLinkedDigitalSignatureBlock($linkedDigitalSignatureRequests);
		}
	}
	/**
	 * Overloading the showLinkedObjectBlock function : replacing the parent's function with the one below
	 *
	 * @param   array           $parameters     Hook metadatas (context, etc...)
	 * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string          $action         Current action (if set). Generally create or edit or null
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function downloadDocument($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $langs;
		$modulePart = $parameters['modulepart'];
		if ($modulePart == 'digitalsignaturemanager') {
			$ecmFile = $parameters['ecmfile'];
			if (!$ecmFile) {
				$staticEcm = new ExtendedEcm($this->db);
				$ecmFile = $staticEcm->getInstanceFileFromItsAbsolutePath($parameters['fullpath_original_file']);
			}
			if ($ecmFile) {
				$ecmFileId = $ecmFile->id;
				$digitalSignatureDocuments = array();
				if ($ecmFileId > 0) {
					//We try to find digital signature document of this file
					$staticDigitalSignatureDocument = new DigitalSignatureDocument($this->db);
					$digitalSignatureDocuments = $staticDigitalSignatureDocument->fetchAll(null, null, 0, 0, array('fk_ecm' => $ecmFileId, 'fk_ecm_signed' => $ecmFileId), 'OR');
				}
				foreach ($digitalSignatureDocuments as $document) {
					//We check that user can open linked dolibarr object on this document
					if ($document->elementtype && $document->fk_object) {
						$features = explode('_', $document->elementtype);
						$table_element = $document->elementtype; //Todo get object instance linked to document
						restrictedArea($user, $features[0], $document->fk_object, $table_element, $features[1]);
					}
				}
				return 1;
			} else {
				$this->errors[] = $langs->trans("DigitalSignatureRequestCantFindFileForDoingSecurityCheck");
				return -1;
			}
		}
	}
}
