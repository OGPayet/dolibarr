<?php
/* Copyright (c) 2020  Alexis LAURIER    <contact@alexislaurier.fr>
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
 *	\file       digitalsignaturemanager/class/html.formdigitalsignaturemanager.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components
 */

dol_include_once('/digitalsignaturemanager/lib/digitalsignaturedocument.helper.php');
dol_include_once('/digitalsignaturemanager/class/extendedEcm.class.php');
/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 */
class FormDigitalSignatureRequestTemplate
{
	/**
	 * @var DoliDb		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var Form  Instance of the form
	 */
	public $form;

	/**
	 * @var array
	 */
	public $errors = array();

	/**
	 * @var FormHelperDigitalSignatureManager  Instance of the form
	 */
	public $helper;

	/**
	 * @var FormDigitalSignatureDocument  Instance of the form
	 */
	public $formDigitalSignatureDocument;

	/**
	 * @var FormDigitalSignaturePeople  Instance of the form
	 */
	public $formDigitalSignaturePeople;

	/**
	 * @var FormDigitalSignatureManager Instance of the form
	 */
	public $formDigitalSignatureManager;

	/**
	 * @var FormDigitalSignatureSignatoryField Instance of the form
	 */
	public $formDigitalSignatureSignatoryField;

	/**
	 * @var FormDigitalSignatureCheckBox Instance of the form
	 */
	public $formDigitalSignatureCheckBox;

	/**
	 * @var Formfile Instance of the form
	 */
	public $formFile;

	/**
	 * @var string Create request from object action name
	 */
	const CREATE_FROM_OBJECT_ACTION_NAME = 'requestSign';

	/**
	 * @var string Create request from object action name
	 */
	const CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME = 'setSigners';

	/**
	 * @var string Files selection html name
	 */
	const SELECTED_FILES_HTML_NAME = 'selectedFiles';

	/**
	 * @var string Files selection html name
	 */
	const INVITATION_MESSAGE_HTML_NAME = 'invitationMessage';

	/**
	 * @var string Files selection html name
	 */
	const CREATE_DRAFT_OPTION_FIELD_NAME = 'createInDraftMode';

	/**
	 * @var string Base contact signatory selection html name
	 */
	const BASE_SIGNATORY_FIELDS_FROM_CONTACT_HTML_NAME = 'contactIdForSignatory';

	/**
	 * @var string Base user signatory selection html name
	 */
	const BASE_SIGNATORY_FIELDS_FROM_USER_HTML_NAME = 'userIdForSignatory';

	/**
	 * @var string Base free signatory firstname field
	 */
	const BASE_FREE_SIGNATORY_FIRSTNAME_HTML_NAME = 'freeFirstName';

	/**
	 * @var string Base free signatory lastname field
	 */
	const BASE_FREE_SIGNATORY_LASTNAME_HTML_NAME = 'freeLastName';

	/**
	 * @var string Base free signatory lastname field
	 */
	const BASE_FREE_SIGNATORY_MAIL_HTML_NAME = 'freeMail';

	/**
	 * @var string Base free signatory lastname field
	 */
	const BASE_FREE_SIGNATORY_PHONE_HTML_NAME = 'freePhone';

	/**
	 * @var string Create request from object action name
	 */
	const CONFIRM_CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME = 'confirmSetSigners';

	/**
	 * Constructor
	 *
	 * @param   DoliDB $db Database handler
	 */
	public function __construct(DoliDb $db)
	{
		$this->db = $db;

		require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
		$this->form = new Form($db);

		dol_include_once('/digitalsignaturemanager/class/helper.formdigitalsignaturemanager.class.php');
		$this->helper = new FormHelperDigitalSignatureManager($db);

		dol_include_once('/digitalsignaturemanager/class/html.formdigitalsignaturedocument.class.php');
		$this->formDigitalSignatureDocument = new FormDigitalSignatureDocument($db);

		dol_include_once('/digitalsignaturemanager/class/html.formdigitalsignaturepeople.class.php');
		$this->formDigitalSignaturePeople = new FormDigitalSignaturePeople($db);

		dol_include_once('/digitalsignaturemanager/class/html.formdigitalsignaturemanager.class.php');
		$this->formDigitalSignatureManager = new FormDigitalSignatureManager($db);

		dol_include_once('/digitalsignaturemanager/class/html.formdigitalsignaturesignatoryfield.class.php');
		$this->formDigitalSignatureSignatoryField = new FormDigitalSignatureSignatoryField($db);

		dol_include_once('/digitalsignaturemanager/class/html.formdigitalsignaturecheckbox.class.php');
		$this->formDigitalSignatureCheckBox = new FormDigitalSignatureCheckBox($db);

		dol_include_once('/digitalsignaturemanager/class/digitalsignaturerequest.class.php');
		$this->elementStatic = new DigitalSignatureRequest($db);

		dol_include_once('/digitalsignaturemanager/class/digitalsignaturerequestlinkedobject.class.php');

		require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
		$this->formFile = new FormFile($db);
	}

	/**
	 * Function to get button to be displayed in order to create request from an object
	 * @param string $objectId Id of the object from which create button
	 * @return string
	 */
	public function getCreateFromObjectButton($objectId)
	{
		global $langs;
		$out = '<div class="inline-block divButAction"><a class="butAction" href="' . $this->formDigitalSignatureManager->buildActionUrlForLine($objectId, self::CREATE_FROM_OBJECT_ACTION_NAME) . '"';
		$out .= '>' . $langs->trans("DigitalSignatureManagerRequestSign") . '</a></div>';
		return $out;
	}

	/**
	 * Function to manage CREATE_FROM_OBJECT_ACTION_NAME sign action
	 * @param CommonObject $object source object instance
	 * @param string $action - current action name
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return string HTML content to be displayed
	 */
	public function displayCreateFromObject(&$object, &$action, $hookmanager)
	{
		global $langs;
		$digitalSignatureRequestLinkedObject = new DigitalSignatureRequestLinkedObject($object);
		$filesToBeDisplayed = $digitalSignatureRequestLinkedObject->getEcmFiles();

		$destinationUrl = $this->formDigitalSignatureManager->buildActionUrlForLine($object->id);
		$title = $langs->trans('DigitalSignatureManagerSelectFilesToSignTitle');
		$selectedFileIds = $this->getSelectedFileIds();
		if (empty($selectedFileIds) && !empty($filesToBeDisplayed)) {
			//We select the most recent file by default
			$selectedFileIds[] = array_keys($filesToBeDisplayed)[0];
		}
		$questions = array(
			self::SELECTED_FILES_HTML_NAME =>
			array(
				'name' => self::SELECTED_FILES_HTML_NAME,
				'type' => 'other',
				'label' => $langs->trans("DigitalSignatureManagerSelectFilesToSign"),
				'value' => '<div style="max-width:500px">' . $this->getHtmlMultipleFileSelect($selectedFileIds, $filesToBeDisplayed) . '</div>'
				)
		);
		$parameters = array('question' => $questions);
		$reshook = $hookmanager->executeHooks('addMoreFormQuestion', $parameters, $object, $action);
		if (count($filesToBeDisplayed) == 1 && ($reshook < 0 || ($reshook == 0 && count($hookmanager->resArray) == 0))) {
			$action = self::CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME;
			return $this->displayCreateFromSelectedFiles($object, $filesToBeDisplayed, $action, $hookmanager);
		} else {
			return $this->formDigitalSignatureManager->formConfirmWithHook($object, $action, $hookmanager, $destinationUrl, $title, null, self::CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME, $questions, null, 1, 'auto', 'auto', 1, 1);
		}
	}

	/**
	 * Function to manage Create from Object
	 * @param string $action action name
	 * @param CommonObject $object source object instance
	 * @return void
	 */
	public function manageCreateFromObject(&$action, &$object)
	{
		global $langs;
		$selectedFiles = $this->getSelectedFiles($object);
		if (empty($selectedFiles)) {
			$action = self::CREATE_FROM_OBJECT_ACTION_NAME;
			setEventMessages('', $langs->trans('DigitalSignatureManagerNoDocumentSelected'), 'errors');
			return null;
		}
	}

	/**
	 * Function to manage create of request from post informations
	 * @param CommonObject $object source object instance
	 * @param User $user user requesting create from files
	 * @param ExtendedEcm[] $selectedFiles Create request from these file
	 * @param String $invitationMessage Message to use to invite users
	 * @return DigitalSignatureRequest|null
	 */
	private function createRequestWithPostInformation(&$object, $user, $selectedFiles, $invitationMessage)
	{
		global $langs;
		$this->db->begin();
		$errors = array();

		$digitalSignatureRequestLinkedObject = new DigitalSignatureRequestLinkedObject($object);

		$requestedSignatoryInformation = $this->getAllSignaturePeopleInformationFromPost($object, $selectedFiles);

		$formValidationErrors = $digitalSignatureRequestLinkedObject->checkContentFromSelectedFiles($selectedFiles, $requestedSignatoryInformation);
		$errors += $formValidationErrors;
		if (empty($errors)) {
			$digitalSignatureRequest = $digitalSignatureRequestLinkedObject->createDigitalSignatureRequestFromLinkedObject($user, $selectedFiles, $requestedSignatoryInformation, $invitationMessage);
			$errors += $digitalSignatureRequestLinkedObject->errors;
		}
		if (empty($errors)) {
			$this->db->commit();
			return $digitalSignatureRequest;
		} else {
			$this->errors += $errors;
			$this->db->rollback();
			return null;
		}
	}

	/**
	 * Function to manage Create from Selected Fiels
	 * @param string $action action name
	 * @param CommonObject $object source object instance
	 * @param User $user user requesting create from files
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return void
	 */
	public function manageCreateFromFiles(&$action, &$object, $user, $hookmanager)
	{
		global $langs;
		$this->db->begin();
		$errors = array();
		$selectedFiles = $this->getSelectedFiles($object);
		if (empty($selectedFiles) || empty($this->getSelectedFileIds())) {
			$errors[] = $langs->trans('DigitalSignatureManagerNoDocumentSelected');
			$this->db->rollback();
			$action = self::CREATE_FROM_OBJECT_ACTION_NAME;
			return;
		}

		$parameters = array('selectedFiles' => $selectedFiles);
		$reshook = $hookmanager->executeHooks('addMoreDocuments', $parameters, $object, $action);
		if (empty($reshook)) {
			$addedContent = is_array($hookmanager->resArray) ? $hookmanager->resArray : array();
			$selectedFiles += $addedContent;
		} elseif ($reshook > 0) {
			$selectedFiles = is_array($hookmanager->resArray) ? $hookmanager->resArray : array();
		}
		else {
			$errors[] = $langs->trans("DigitalSignatureManagerErrorIntoHookAddMoreDocuments");
			$errors += is_array($hookmanager->errors) ? $hookmanager->errors : array();
		}

		$requestOptions = $this->getSelectedOptions();
		$onlyCreateADraft = $requestOptions[self::CREATE_DRAFT_OPTION_FIELD_NAME];
		$invitationMessage = $requestOptions[self::INVITATION_MESSAGE_HTML_NAME];

		$digitalSignatureRequest = $this->createRequestWithPostInformation($object, $user, $selectedFiles, $invitationMessage);
		$errors += $this->errors;
		if ($digitalSignatureRequest && !$onlyCreateADraft) {
			$digitalSignatureRequest->validateAndCreateRequestOnTheProvider($user);
			$errors += $digitalSignatureRequest->errors;
		}

		if (!empty($errors)) {
			setEventMessages('', $errors, 'errors');
			$this->db->rollback();
			$action = self::CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME;
		} else {
			$this->db->commit();
			if ($onlyCreateADraft) {
				setEventMessages($langs->trans("DigitalSignatureManagerDraftSuccessfullyCreated"), array());
			} else {
				setEventMessages($langs->trans("DigitalSignatureManagerSucessfullyCreatedOnProvider"), array());
			}
		}
	}

	/**
	 * Function to get request options set from displayCreateFromSelectedFiles
	 * @return array
	 */
	public function getSelectedOptions()
	{
		return array(
			self::CREATE_DRAFT_OPTION_FIELD_NAME => !empty(GETPOST(self::CREATE_DRAFT_OPTION_FIELD_NAME)),
			self::INVITATION_MESSAGE_HTML_NAME => $this->getInvitationMessage()
		);
	}


	/**
	 * Function to manage CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME action
	 * @param CommonObject $object source object instance
	 * @param ExtendedEcm[] $selectedFiles instance files selected
	 * @param string $action - current action name
	 * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 * @return string HTML content to be displayed
	 */
	public function displayCreateFromSelectedFiles(&$object, $selectedFiles = null, &$action, &$hookmanager)
	{
		global $langs;
		$formquestion = array();
		if (!$selectedFiles) {
			$selectedFiles = $this->getSelectedFiles($object);
		}

		$parameters = array('selectedFiles' => $selectedFiles);
		$reshook = $hookmanager->executeHooks('addMoreDocuments', $parameters, $object, $action);
		if (empty($reshook)) {
			$selectedFiles += is_array($hookmanager->resArray) ? $hookmanager->resArray : array();
		} elseif ($reshook > 0) {
			$selectedFiles = is_array($hookmanager->resArray) ? $hookmanager->resArray : array();
		} else {
			setEventMessages($langs->trans("DigitalSignatureManagerErrorIntoHookAddMoreDocuments"), $hookmanager->errors, 'errors');
		}

		$isThereMissingParameters = false;
		foreach ($selectedFiles as $id => $ecmFile) {
			$formquestion[] = array('type' => 'hidden', 'name' => self::SELECTED_FILES_HTML_NAME . '[]', 'value' => $id);
			$dictionaryItems = $this->getDictionaryLinesForFile($object, $ecmFile);
			$formquestion[] = array(
				'type' => 'onecolumn',
				'value' => '<div class="titre"><h2 style="text-align:center;">' . $langs->trans("DigitalSignatureManagerSignersForDocument", $ecmFile->filename) . '</h2></div>'
			);
			if (empty($dictionaryItems)) {
				$formquestion[] = array(
					'type' => 'onecolumn',
					'value' => '<div><h3 class="error">' . $langs->trans("DigitalSignatureManagerNoSettingForDocument", $ecmFile->filename) . '</h3></div>'
				);
				$isThereMissingParameters = true;
			}
			foreach ($dictionaryItems as $dictionaryItem) {
				$formquestion = array_merge($formquestion, $this->displaySignatoryFieldSelectionFromDictionary($dictionaryItem, $object, $id));
			}
		}

		// Request creation option
		$requestedOptions = $this->getSelectedOptions();
		$formquestion[] = array(
			'type' => 'onecolumn',
			'value' => '<div class="titre"><h2 style="text-align:center;">' . $langs->trans("DigitalSignatureDocumentTemplateOptions") . '</h2></div>'
		);
		$displayedCheckboxState = $requestedOptions[self::CREATE_DRAFT_OPTION_FIELD_NAME] || $isThereMissingParameters;
		$createOnlyADraftQuestion = array(
			'type' => 'checkbox',
			'name' => self::CREATE_DRAFT_OPTION_FIELD_NAME,
			'label' => $langs->trans("DigitalSignatureManagerCreateOnlyDraft"),
			'value' => $displayedCheckboxState,
		);
		if ($isThereMissingParameters) {
			$createOnlyADraftQuestion['disabled'] = true;
		}
		$formquestion[] = $createOnlyADraftQuestion;

		$invitationMessageTitle = array(
			'type' => 'other',
			'label' => $langs->trans("DigitalSignatureInvitationMessage")
		);
		$invitationMessage = array(
			'type' => 'onecolumn',
			'name' => self::INVITATION_MESSAGE_HTML_NAME,
			'value' => $this->formDigitalSignatureManager->getTextEditor(self::INVITATION_MESSAGE_HTML_NAME, $requestedOptions[self::INVITATION_MESSAGE_HTML_NAME])
		);

		$formquestion[] = $invitationMessageTitle;
		$formquestion[] = $invitationMessage;

		$destinationUrl = $this->formDigitalSignatureManager->buildActionUrlForLine($object->id);
		$title = $langs->trans('DigitalSignatureManagerSelectSignatoryToSignTitle');
		return $this->formDigitalSignatureManager->formConfirmWithHook($object, $action, $hookmanager, $destinationUrl, $title, null, self::CONFIRM_CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME, $formquestion, null, 1, 'auto', 'auto', 1, 1);
	}

	/**
	 * Function to get selected files ids
	 * @return int[]
	 */
	public function getSelectedFileIds()
	{
		$contentPost = GETPOST(self::SELECTED_FILES_HTML_NAME);
		if (is_array($contentPost)) {
			$properContent = $contentPost;
		} elseif (!empty($contentPost)) {
			$properContent = explode(',', $contentPost);
		}
		$properContentPost = !empty($properContent) ? $properContent : array();
		return array_values(array_map('intval', $properContentPost));
	}

	/**
	 * Function to get HTML output to select multiple files from an array of ECM instance
	 * @param int[] $selectedEcmFileIds already selected files
	 * @param ExtendedEcm[] $selectableEcmFiles selectable files
	 * @return string
	 */
	public function getHtmlMultipleFileSelect($selectedEcmFileIds, $selectableEcmFiles)
	{
		$displayedName = array();
		foreach ($selectableEcmFiles as $id => $ecmFile) {
			$displayedName[$id] = $ecmFile->filename;
		}
		return $this->formDigitalSignatureManager->multiSelectArrayWithOrder(self::SELECTED_FILES_HTML_NAME, $displayedName, $selectedEcmFileIds, 0, 0, 'flat minwidth200 maxwidth200');
	}

	/**
	 * Function to display selection of signatory fields according to dictionary item
	 * @param DigitalSignatureSignatoryFieldsDictionary $dictionaryItem dictionary item from which create form
	 * @param CommonObject $object object on which we are selecting items
	 * @param int $ecmFileId ecm file id of the document on which we are looking for dictionary line id
	 * @return array formquestion
	 */
	public function displaySignatoryFieldSelectionFromDictionary($dictionaryItem, $object, $ecmFileId)
	{
		global $langs;
		$result = array();
		//We display title of the signatory field
		$result[] = array(
			'type' => 'onecolumn',
			'value' =>  '<div class="titre"><h3>' . $langs->trans("DigitalSignatureManagerSignatoryFieldTitle", $dictionaryItem->label) . '</h3></div>'
		);
		if (in_array(DigitalSignaturePeople::LINKED_OBJECT_USER_TYPE, $dictionaryItem->linkedContactType)) {
			$userHtmlName = $this->generateHtmlName($dictionaryItem->c_rowid, $ecmFileId, self::BASE_SIGNATORY_FIELDS_FROM_USER_HTML_NAME);
			$result[] = array(
				'type' => 'other',
				'label' => $langs->trans('DigitalSignatureManagerAddPeopleFromUser'),
				'name' => $userHtmlName,
				'value' => $this->formDigitalSignatureManager->selectUser($object, $this->getSelectedUserIdForDictionaryLineId($dictionaryItem->c_rowid, $ecmFileId) ?? -1, $userHtmlName, null, true)
			);
		}
		if (in_array(DigitalSignaturePeople::LINKED_OBJECT_CONTACT_TYPE, $dictionaryItem->linkedContactType)) {
			$contactHtmlName = $this->generateHtmlName($dictionaryItem->c_rowid, $ecmFileId, self::BASE_SIGNATORY_FIELDS_FROM_CONTACT_HTML_NAME);
			$result[] = array(
				'type' => 'other',
				'label' => $langs->trans('DigitalSignatureManagerAddPeopleFromContact'),
				'name' => $contactHtmlName,
				'value' => $this->formDigitalSignatureManager->selectContact($object, $this->getSelectedContactIdForDictionaryLineId($dictionaryItem->c_rowid, $ecmFileId) ?? -1, $contactHtmlName, $object->socid, null, null, true)
			);
		}
		if (in_array(DigitalSignaturePeople::LINKED_OBJECT_FREE_TYPE, $dictionaryItem->linkedContactType)) {
			$arrayOfFreeProperty = array(
				self::BASE_FREE_SIGNATORY_FIRSTNAME_HTML_NAME => $langs->trans('DigitalSignaturePeopleFirstname'),
				self::BASE_FREE_SIGNATORY_LASTNAME_HTML_NAME => $langs->trans('DigitalSignaturePeopleLastname'),
				self::BASE_FREE_SIGNATORY_MAIL_HTML_NAME => $langs->trans('DigitalSignaturePeopleMail'),
				self::BASE_FREE_SIGNATORY_PHONE_HTML_NAME => $langs->trans('DigitalSignaturePeopleMobilePhoneNumber')
			);
			foreach ($arrayOfFreeProperty as $baseHtmlName => $fieldLabel) {
				$htmlName =  $this->generateHtmlName($dictionaryItem->c_rowid, $ecmFileId, $baseHtmlName);
				$result[] = array(
					'type' => 'text',
					'label' => $fieldLabel,
					'name' => $htmlName,
					'value' => GETPOST($htmlName)
				);
			}
		}
		return $result;
	}

	/**
	 * Function to get html name for a signatory fields input
	 * @param int $dictionaryLineId id of the line being edited
	 * @param int $ecmFileId ecm file id of the document on which we are looking for dictionary line id
	 * @param string $baseHtmlName Base html name to generate html name
	 * @return string
	 */
	public function generateHtmlName($dictionaryLineId, $ecmFileId, $baseHtmlName)
	{
		return $baseHtmlName . '-row-' . $dictionaryLineId . '-ecmFile-' . $ecmFileId;
	}

	/**
	 * Function to get selected user id for a signatory dictionary line Id and a document id
	 * @param int $dictionaryLineId signatory fields dictionary line Id
	 * @param int $ecmFileId ecm file id of the document on which we are looking for dictionary line id
	 * @return int
	 */
	public function getSelectedUserIdForDictionaryLineId($dictionaryLineId, $ecmFileId)
	{
		$postContent = GETPOST($this->generateHtmlName($dictionaryLineId, $ecmFileId, self::BASE_SIGNATORY_FIELDS_FROM_USER_HTML_NAME));
		return $postContent && $postContent != "-1" ?  $postContent : null;
	}

	/**
	 * Function to get selected contact id for a signatory dictionary line Id and a document id
	 * @param int $dictionaryLineId signatory fields dictionary line Id
	 * @param int $ecmFileId ecm file id of the document on which we are looking for dictionary line id
	 * @return int
	 */
	public function getSelectedContactIdForDictionaryLineId($dictionaryLineId, $ecmFileId)
	{
		$postContent = GETPOST($this->generateHtmlName($dictionaryLineId, $ecmFileId, self::BASE_SIGNATORY_FIELDS_FROM_CONTACT_HTML_NAME));
		return $postContent && $postContent != "-1" ?  $postContent : null;
	}

	/**
	 * Function to get free signatory information from post
	 * @param int $dictionaryLineId signatory fields dictionary line Id
	 * @param int $ecmFileId ecm file id of the document on which we are looking for dictionary line id
	 * @return DigitalSignaturePeople
	 */
	public function getFreeSignatoryInformationFromPost($dictionaryLineId, $ecmFileId)
	{
		$digitalSignaturePeople = new DigitalSignaturePeople($this->db);
		$arrayOfPropertyNameAndHtmlBaseName = array(
			'firstName' => self::BASE_FREE_SIGNATORY_FIRSTNAME_HTML_NAME,
			'lastName' => self::BASE_FREE_SIGNATORY_LASTNAME_HTML_NAME,
			'mail' => self::BASE_FREE_SIGNATORY_MAIL_HTML_NAME,
			'phoneNumber' => self::BASE_FREE_SIGNATORY_PHONE_HTML_NAME
		);
		foreach ($arrayOfPropertyNameAndHtmlBaseName as $propertyName => $baseHtmlName) {
			$digitalSignaturePeople->$propertyName = GETPOST($this->generateHtmlName($dictionaryLineId, $ecmFileId, $baseHtmlName));
		}
		return $digitalSignaturePeople;
	}

	/**
	 * Function to get signatory from post for an ecm file id and a dictionary line id
	 * @param int $dictionaryLineId signatory fields dictionary line Id
	 * @param int $ecmFileId ecm file id of the document on which we are looking for dictionary line id
	 * @param string[] $allowedSignatorySourceNames Signatory source names list
	 * @return DigitalSignaturePeople|null
	 */
	public function getSignatoryInformationFromPost($dictionaryLineId, $ecmFileId, $allowedSignatorySourceNames)
	{
		$selectedUserId = $this->getSelectedUserIdForDictionaryLineId($dictionaryLineId, $ecmFileId);
		$selectedContactId = $this->getSelectedContactIdForDictionaryLineId($dictionaryLineId, $ecmFileId);
		$signatory = null;
		if ($selectedUserId && in_array(DigitalSignaturePeople::LINKED_OBJECT_USER_TYPE, $allowedSignatorySourceNames)) {
			$signatory = new DigitalSignaturePeople($this->db);
			$signatory->fillDataFromUserId($selectedUserId);
		} elseif ($selectedContactId && in_array(DigitalSignaturePeople::LINKED_OBJECT_CONTACT_TYPE, $allowedSignatorySourceNames)) {
			$signatory = new DigitalSignaturePeople($this->db);
			$signatory->fillDataFromContactId($selectedContactId);
		} elseif (in_array(DigitalSignaturePeople::LINKED_OBJECT_FREE_TYPE, $allowedSignatorySourceNames)) {
			$signatory = $this->getFreeSignatoryInformationFromPost($dictionaryLineId, $ecmFileId);
		}
		return $signatory;
	}

	/**
	 * Function to get Selected files from post
	 * @param CommonObject $object Object on which we are working on
	 * @return ExtendedEcm[]
	 */
	public function getSelectedFiles($object)
	{
		$digitalSignatureRequestLinkedObject = new DigitalSignatureRequestLinkedObject($object);
		return $digitalSignatureRequestLinkedObject->getFilesByIds($this->getSelectedFileIds());
	}

	/**
	 * Function to get signatories from post, order by ecmFileId and signatory field dictionary Id
	 * @param CommonObject $object Object on which we are working on
	 * @return DigitalSignaturePeople[]
	 */
	public function getAllSignaturePeopleInformationFromPost($object, $selectedFiles)
	{
		$result = array();
		foreach ($selectedFiles as $id => $ecmFile) {
			foreach ($this->getDictionaryLinesForFile($object, $ecmFile) as $dictionaryLine) {
				$result[$id][$dictionaryLine->c_rowid] = $this->getSignatoryInformationFromPost($dictionaryLine->c_rowid, $id, $dictionaryLine->linkedContactType);
			}
		}
		return $result;
	}

	/**
	 * Function to get dictionary lines that are should be set to an ecm file instance
	 * @param CommonObject $object Object on which we are working on
	 * @param ExtendedEcm $ecmFile Ecm file on which we are looking for dictionnary lines
	 * @return DigitalSignatureSignatoryField[]
	 */
	public function getDictionaryLinesForFile($object, $ecmFile)
	{
		$digitalSignatureRequestLinkedObject = new DigitalSignatureRequestLinkedObject($object);
		return $digitalSignatureRequestLinkedObject->getSignatoryFieldsDictionaryLinesForFile($ecmFile);
	}

	/**
	 * Function to get invitation message set by user from post content
	 * @return string|null
	 */
	public function getInvitationMessage()
	{
		$content = GETPOST(self::INVITATION_MESSAGE_HTML_NAME);
		return empty($content) ? null : $content;
	}
}
