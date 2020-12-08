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
	 * Function to get list of files to sign for a digital signature manager request
	 * @param string $directory Directory to be watched
	 * @return ExtendedEcm[]
	 */
	public function getEcmListForDirectory($directory)
	{
		$relativePathToDolDataRoot = ExtendedEcm::getRelativeDirectoryOfADirectory($directory);
		$result = array();
		if ($relativePathToDolDataRoot) {
			global $user;
			ExtendedEcm::cleanEcmFileDatabase($this->db, $relativePathToDolDataRoot, $user);
			$extendedEcm = new ExtendedEcm($this->db);
			$crudeResult = $extendedEcm->fetchAll('DESC', 'GREATEST(date_c, date_m) DESC, rowid ', 0, 0, array('filepath' => $relativePathToDolDataRoot, 'filename' => '.pdf'));
			foreach ($crudeResult as &$ecm) {
				$result[$ecm->id] = $ecm;
			}
		}
		return $result;
	}

	/**
	 * Function to get directory where search files for propal
	 * @param Propal $object object on which search where are stored files
	 * @return string|null
	 */
	private function getPropalDirectory(&$object)
	{
		if ($object->ref) {
			global $conf;
			return $conf->propal->dir_output . "/" . dol_sanitizeFileName($object->ref);
		}
		return null;
	}

	/**
	 * Function to get selectable files for an object
	 * @param CommonObject $object object instance on which list files
	 * @return ExtendedEcm[]
	 */
	public function getEcmFilesOfAnObject($object)
	{
		$result = array();
		if ($object->element == 'propal') {
			$result = $this->getEcmListForDirectory($this->getPropalDirectory($object));
		}
		return $result;
	}


	/**
	 * Function to manage CREATE_FROM_OBJECT_ACTION_NAME sign action
	 * @param string $action action name
	 * @param CommonObject $object source object instance
	 * @return string HTML content to be displayed
	 */
	public function displayCreateFromObject(&$action, &$object)
	{
		global $langs;
		if ($action == self::CREATE_FROM_OBJECT_ACTION_NAME) {
			$filesToBeDisplayed = $this->getEcmFilesOfAnObject($object);
			$destinationUrl = $this->formDigitalSignatureManager->buildActionUrlForLine($object->id);
			$title = $langs->trans('DigitalSignatureManagerSelectFilesToSignTitle');
			$selectedFileIds = $this->getSelectedFileIds();
			if (empty($selectedFileIds) && !empty($filesToBeDisplayed)) {
				//We select the most recent file by default
				$selectedFileIds[] = array_keys($filesToBeDisplayed)[0];
			}
			$questions = array(
				'filesToSelect' => array('name' => self::SELECTED_FILES_HTML_NAME, 'type' => 'other', 'label' => $langs->trans("DigitalSignatureManagerSelectFilesToSign"), 'value' => $this->getHtmlMultipleFileSelect($selectedFileIds, $filesToBeDisplayed))
			);
			return $this->formDigitalSignatureManager->formconfirm($destinationUrl, $title, null, self::CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME, $questions, null, 1, 'auto', 'auto', 1, 1);
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
		if ($action == self::CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME && empty($selectedFiles)) {
			$action = self::CREATE_FROM_OBJECT_ACTION_NAME;
			setEventMessages('', $langs->trans('DigitalSignatureManagerNoDocumentSelected'), 'errors');
			return null;
		}
	}

	/**
	 * Function to manage Create from Selected Fiels
	 * @param string $action action name
	 * @param CommonObject $object source object instance
	 * @param User $user user requesting create from files
	 * @return void
	 */
	public function manageCreateFromFiles(&$action, &$object, $user)
	{
		global $langs;
		if ($action == self::CONFIRM_CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME) {
			$this->db->begin();
			$errors = array();
			$selectedFiles = $this->getSelectedFiles($object);
			if (empty($selectedFiles)) {
				$errors[] = $langs->trans('DigitalSignatureManagerNoDocumentSelected');
			}
			$formValidationErrors = $this->checkContentFromCreateFromSelectedFiles($object);
			$errors = array_merge($errors, $formValidationErrors);
			if(empty($errors)) {
				$digitalSignatureRequest = $this->createDigitalSignatureRequestFromPost($object, $user);
				$errors = array_merge($errors, $this->errors);
				$this->errors = array();
			}

			if(empty($errors) && $digitalSignatureRequest) {
				$digitalSignatureRequest->validateAndCreateRequestOnTheProvider($user);
				$errors = array_merge($errors, $digitalSignatureRequest->errors);
				if(!empty($digitalSignatureRequest->errors)) {
					//We have to delete files as we will remove entries from database
					$digitalSignatureRequest->deleteFilesToSign();
				}
			}

			if(!empty($errors)) {
				setEventMessages('', $errors, 'errors');
				$this->db->rollback();
				$action = self::CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME;
			}
			else {
				$this->db->commit();
				setEventMessages($langs->trans("DigitalSignatureManagerSucessfullyCreatedOnProvider"), array());
			}
		}
	}

	/**
	 * Function to manage CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME action
	 * @param string $action action name
	 * @param CommonObject $object source object instance
	 * @return string HTML content to be displayed
	 */
	public function displayCreateFromSelectedFiles(&$action, &$object)
	{
		global $langs;
		if ($action == self::CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME) {
			$formquestion = array();
			$selectedFiles = $this->getSelectedFiles($object);

			foreach ($selectedFiles as $ecmFile) {
				$formquestion[] = array('type' => 'hidden', 'name' => self::SELECTED_FILES_HTML_NAME . '[]', 'value' => $ecmFile->id);
				$dictionaryItems = $this->getDigitalSignatureSignatoryFieldsDictionaryLinesForFile($ecmFile);
				if (empty($dictionaryItems)) {
					$labelValue = '<div><h3 class="error">' . $langs->trans("DigitalSignatureManagerNoSettingForDocument", $ecmFile->filename) . '</h3></div>';
				} else {
					$labelValue = '<div class="titre"><h2 style="text-align:center;">' . $langs->trans("DigitalSignatureManagerSignersForDocument", $ecmFile->filename) . '</h2></div>';
				}
				$formquestion[] = array(
					'type' => 'onecolumn',
					'value' => $labelValue
				);
				foreach ($dictionaryItems as $dictionaryItem) {
					$formquestion = array_merge($formquestion, $this->displaySignatoryFieldSelectionFromDictionary($dictionaryItem, $object, $ecmFile->id));
				}
			}
			$destinationUrl = $this->formDigitalSignatureManager->buildActionUrlForLine($object->id);
			$title = $langs->trans('DigitalSignatureManagerSelectSignatoryToSignTitle');
			return $this->formDigitalSignatureManager->formconfirm($destinationUrl, $title, null, self::CONFIRM_CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME, $formquestion, null, 1, 'auto', 'auto', 1, 1);
		}
	}

	/**
	 * Function to check that all needed information asked on displayCreateFromSelectedFiles have been asked
	 * @param CommonObject $object Object from which we create request
	 * @return string[] array of validation errors
	 */
	public function checkContentFromCreateFromSelectedFiles($object)
	{
		global $langs;
		$errors = array();
		$selectedFiles = $this->getSelectedFiles($object);
		foreach($selectedFiles as $ecmFile) {
			$dictionaryItems = $this->getDigitalSignatureSignatoryFieldsDictionaryLinesForFile($ecmFile);
			foreach($dictionaryItems as $dictionaryItem) {
				$selectedSignatory = $this->getSignatoryInformationFromPost($dictionaryItem->c_rowid, $ecmFile->id, $dictionaryItem->linkedContactType);
				$isFreeSignatoryAllowed = in_array(DigitalSignaturePeople::LINKED_OBJECT_FREE_TYPE, $dictionaryItem->linkedContactType);
				$isContactOrUserSignatorySourceAllowed = in_array(DigitalSignaturePeople::LINKED_OBJECT_USER_TYPE, $dictionaryItem->linkedContactType) || in_array(DigitalSignaturePeople::LINKED_OBJECT_CONTACT_TYPE, $dictionaryItem->linkedContactType);
				if(!$selectedSignatory && !$isFreeSignatoryAllowed) {
					$errors[] = $langs->trans('DigitalSignatureManagerNoSignatorySelected', $dictionaryItem->label, $ecmFile->filename);
				} elseif($isFreeSignatoryAllowed && !$isContactOrUserSignatorySourceAllowed && !empty($selectedSignatory->checkDataValidForCreateRequestOnProvider())) {
					$errors[] = $langs->trans('DigitalSignatureManagerFreeSignatoryIncomplete', $dictionaryItem->label, $ecmFile->filename);
				}
			}
		}
		return $errors;
	}

	/**
	 * Function to get selected files ids
	 * @return int[]
	 */
	public function getSelectedFileIds()
	{
		$contentPost = GETPOST(self::SELECTED_FILES_HTML_NAME);
		$properContentPost = !empty($contentPost) ? $contentPost : array();
		return array_map('intval', $properContentPost);
	}

	/**
	 * Function to get selected files instance
	 * @param Object $object Object on which we are looking effective selected files
	 * @return ExtendedEcm[]
	 */
	public function getSelectedFiles($object)
	{
		$selectedFilesId = $this->getSelectedFileIds();
		$selectableFiles = $this->getEcmFilesOfAnObject($object);
		$filesEffectivelyChosen = array();
		foreach ($selectedFilesId as $id) {
			if ($selectableFiles[$id]) {
				$filesEffectivelyChosen[] = $selectableFiles[$id];
			}
		}
		return $filesEffectivelyChosen;
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
		return $this->form->multiselectarray(self::SELECTED_FILES_HTML_NAME, $displayedName, $selectedEcmFileIds, 0, 0, 'fullwidth minwidth200');
	}

	/**
	 * Function to get signatory field from dictionary according to document file origin
	 * @param ExtendedEcm $ecmFile ecm file on which search predefined signatory fields
	 * @return DigitalSignatureSignatoryFieldsDictionary[]
	 */
	public function getDigitalSignatureSignatoryFieldsDictionaryLinesForFile($ecmFile)
	{
		$digitalSignatureDocument = new DigitalSignatureDocument($this->db);
		$digitalSignatureDocument->fk_ecm = $ecmFile->id;
		$digitalSignatureDocument->ecmFile = $ecmFile;
		return $digitalSignatureDocument->getDictionarySignatoryFieldsOfThisDocument();
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
	 * Function to create digital signature request instance with document and signatory fields according to post content
	 * @param CommonObject $object Linked object from which we are creating request
	 * @param User $user user requesting creation of request
	 * @return DigitalSignatureRequest|null
	 */
	public function createDigitalSignatureRequestFromPost($object, $user)
	{
		$this->db->begin();
		global $langs;
		$errors = array();
		$digitalSignatureRequest = new DigitalSignatureRequest($this->db);
		$digitalSignatureRequest->elementtype = $object->table_element;
		$digitalSignatureRequest->fk_object = $object->id;
		$digitalSignatureRequest->fk_soc = $object->socid;

		if ($digitalSignatureRequest->create($user) < 0) {
			$errors = array_merge($errors, $digitalSignatureRequest->errors);
		}

		$arrayOfOriginalAndCopiedEcmFile = array();
		//We build digitalsignaturedocument array for this request
		$selectedFiles = $this->getSelectedFiles($object);
		foreach ($selectedFiles as $ecmFile) {
			if (empty($errors)) {
				$digitalSignatureDocument = new DigitalSignatureDocument($this->db);
				$copyEcmFile = $ecmFile->copyFileTo($digitalSignatureRequest->getRelativePathToDolDataRootForFilesToSign());
				if (!$copyEcmFile) {
					$errors[] = $langs->trans("DigitalSignatureManagerErrorWhileCopyingFile", $ecmFile->filename);
				}
				else {
					$arrayOfOriginalAndCopiedEcmFile[$copyEcmFile->id] = $ecmFile;
					$digitalSignatureDocument->ecmFile = $copyEcmFile;
					$digitalSignatureDocument->fk_ecm = $copyEcmFile->id;
					$digitalSignatureDocument->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
					$digitalSignatureDocument->digitalSignatureRequest = $digitalSignatureRequest;
					$digitalSignatureDocument->create($user);
					$errors = array_merge($errors, $digitalSignatureDocument->errors);
					$digitalSignatureRequest->documents[] = $digitalSignatureDocument;
				}
			}
		}


		//We build digitalsignaturepeople array for this request
		$listOfSignatoryByIdentifier = array();
		$listOfSignatoryIdentifierByEcmFileAndSignatoryFieldDictionnaryId = array();
		foreach ($selectedFiles as $ecmFile) {
			foreach ($this->getDigitalSignatureSignatoryFieldsDictionaryLinesForFile($ecmFile) as $dictionaryItem) {
				$signatorySelected = $this->getSignatoryInformationFromPost($dictionaryItem->c_rowid, $ecmFile->id, $dictionaryItem->linkedContactType);
				if ($signatorySelected && $signatorySelected->generateUniqueIdentifier()) {
					$signatoryIdentifier = $signatorySelected->generateUniqueIdentifier();
					if (!$listOfSignatoryByIdentifier[$signatoryIdentifier]) {
						$signatorySelected->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
						$signatorySelected->digitalSignatureRequest = $digitalSignatureRequest;
						$signatorySelected->create($user);
						$errors = array_merge($errors, $signatorySelected->errors);
						$listOfSignatoryByIdentifier[$signatoryIdentifier] = $signatorySelected;
					}
					$listOfSignatoryIdentifierByEcmFileAndSignatoryFieldDictionnaryId[$dictionaryItem->c_rowid][$ecmFile->id] = $signatoryIdentifier;
				}
			}
		}

		$digitalSignatureRequest->people = array_values($listOfSignatoryByIdentifier);

		//We create signatory fields

		foreach ($digitalSignatureRequest->documents as $document) {
			if(empty($errors)) {
				$originalEcmFile = $arrayOfOriginalAndCopiedEcmFile[$document->fk_ecm];
				$dictionaryItems = $this->getDigitalSignatureSignatoryFieldsDictionaryLinesForFile($originalEcmFile);
				foreach ($dictionaryItems as $dictionaryItem) {
					$digitalSignatureSignatoryField = new DigitalSignatureSignatoryField($this->db);
					$digitalSignatureSignatoryField->c_rowid = $dictionaryItem->c_rowid;
					$digitalSignatureSignatoryField->label = $dictionaryItem->label;
					$digitalSignatureSignatoryField->page = $document->correctPageNumber($dictionaryItem->pageNumber);
					$digitalSignatureSignatoryField->x = (int) $dictionaryItem->x;
					$digitalSignatureSignatoryField->y = (int) $dictionaryItem->y;
					$digitalSignatureSignatoryField->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
					$digitalSignatureSignatoryField->fk_chosen_digitalsignaturedocument = $document->id;
					$signatoryIdentifierForThisField = $listOfSignatoryIdentifierByEcmFileAndSignatoryFieldDictionnaryId[$dictionaryItem->c_rowid][$originalEcmFile->id];
					$signatoryOfThisField = $listOfSignatoryByIdentifier[$signatoryIdentifierForThisField];
					$digitalSignatureSignatoryField->fk_chosen_digitalsignaturepeople = $signatoryOfThisField->id;
					$digitalSignatureSignatoryField->create($user);
					$errors = array_merge($errors, $digitalSignatureSignatoryField->errors);
					$digitalSignatureRequest->signatoryFields[] = $digitalSignatureSignatoryField;
				}
			}
		}

		$this->errors = array_merge($this->errors, $errors);
		if(empty($errors)) {
			$this->db->commit();
			//dolibarr bullshit adaptation - as createCommon change some values into database but do not reflect it on object
			$digitalSignatureRequest->fetch($digitalSignatureRequest->id);
			return $digitalSignatureRequest;
		}
		else {
			//We remove files
			$digitalSignatureRequest->deleteFilesToSign();
			$this->db->rollback();
			return null;
		}
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
		$arrayOfPropertyNameAndHtmlBaseBame = array(
			'firstName' => self::BASE_FREE_SIGNATORY_FIRSTNAME_HTML_NAME,
			'lastName' => self::BASE_FREE_SIGNATORY_LASTNAME_HTML_NAME,
			'mail' => self::BASE_FREE_SIGNATORY_MAIL_HTML_NAME,
			'phoneNumber' => self::BASE_FREE_SIGNATORY_PHONE_HTML_NAME
		);
		foreach ($arrayOfPropertyNameAndHtmlBaseBame as $propertyName => $baseHtmlName) {
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
}
