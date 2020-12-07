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
	public static $errors = array();

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
	 * Function to get list of files to sign for a digital signature manager
	 * @param string $directory Directory to be watched
	 * @return ExtendedEcm[]
	 */
	public function getEcmListForDirectory($directory)
	{
		$relativePathToDolDataRoot = ExtendedEcm::getRelativeDirectoryOfADirectory($directory);
		$result = array();
		if($relativePathToDolDataRoot) {
			global $user;
			ExtendedEcm::cleanEcmFileDatabase($this->db, $relativePathToDolDataRoot, $user);
			$extendedEcm = new ExtendedEcm($this->db);
			$crudeResult = $extendedEcm->fetchAll('DESC', 'GREATEST(date_c, date_m) DESC, rowid ', 0, 0, array('filepath' => $relativePathToDolDataRoot));
			foreach($crudeResult as &$ecm) {
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
		if($object->ref) {
			global $conf;
			return $conf->propal->dir_output . "/" . dol_sanitizeFileName($object->ref);
		}
		return null;
	}

	/**
	 * Function to get selectable files for an object
	 * @param Object $object object instance on which list files
	 * @return ExtendedEcm[]
	 */
	public function getEcmFilesOfAnObject($object) {
		$result = array();
		if($object->element == 'propal') {
			$result = $this->getEcmListForDirectory($this->getPropalDirectory($object));
		}
		return $result;
	}


	/**
	 * Function to manage CREATE_FROM_OBJECT_ACTION_NAME sign action
	 * @param string $action action name
	 * @param object $object source object instance
	 * @return string HTML content to be displayed
	 */
	public function manageCreateFromAction(&$action, &$object)
	{
		global $langs;
		if($action == self::CREATE_FROM_OBJECT_ACTION_NAME) {
			$filesToBeDisplayed = $this->getEcmFilesOfAnObject($object);
			$destinationUrl = $this->formDigitalSignatureManager->buildActionUrlForLine($object->id);
			$title = $langs->trans('DigitalSignatureManagerSelectFilesToSignTitle');
			$selectedFileIds = $this->getSelectedFileIds();
			if(empty($selectedFileIds) && !empty($filesToBeDisplayed)) {
				//We select the most recent file by default
				$selectedFileIds[] = array_keys($filesToBeDisplayed)[0];
			}
			$questions = array(
				'filesToSelect' => array('name'=>self::SELECTED_FILES_HTML_NAME, 'type'=>'other', 'label'=>$langs->trans("DigitalSignatureManagerSelectFilesToSign"), 'value' => $this->getHtmlMultipleFileSelect($selectedFileIds, $filesToBeDisplayed))
			);
			return $this->formDigitalSignatureManager->formconfirm($destinationUrl, $title, null, self::CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME, $questions, null, 1, 'auto', 'auto', 1, 1);
		}
	}

	/**
	 * Function to manage CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME action
	 * @param string $action action name
	 * @param object $object source object instance
	 * @return string HTML content to be displayed
	 */
	public function manageCreateFromSelectedFiles(&$action, &$object)
	{
		global $langs;
		if($action == self::CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME) {
			$formquestion = array();
			$selectedFiles = $this->getSelectedFiles($object);
			foreach($selectedFiles as $ecmFile) {
				$formquestion[] = array('type'=>'other', 'label'=>'<b>' . $langs->trans("DigitalSignatureManagerSignersForDocument", $ecmFile->filename) . '</b>');
				$dictionaryItems = $this->getDigitalSignatureSignatoryFieldsDictionaryLinesForFile($ecmFile);
				foreach($dictionaryItems as $dictionaryItem) {
					$formquestion = array_merge($formquestion, $this->displaySignatoryFieldSelectionFromDictionary($dictionaryItem, $object));
				}
			}
			$destinationUrl = $this->formDigitalSignatureManager->buildActionUrlForLine($object->id);
			$title = $langs->trans('DigitalSignatureManagerSelectSignatoryToSignTitle');
			return $this->formDigitalSignatureManager->formconfirm($destinationUrl, $title, null, self::CONFIRM_CREATE_FROM_OBJECT_SIGNER_SELECTION_ACTION_NAME, $formquestion, null, 1, 'auto', 'auto', 1, 1);
		}
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
		foreach($selectedFilesId as $id) {
			if($selectableFiles[$id]) {
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
		foreach($selectableEcmFiles as $id => $ecmFile) {
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
	 * @param DigitalSignatureSignatoryFieldsDictionary $dictionaryItem dictionnary item from which create form
	 * @param Object $object object on which we are selecting items
	 * @return array formquestion
	 */
	public function displaySignatoryFieldSelectionFromDictionary($dictionaryItem, $object)
	{
		$result = array();
		//We display title of the signatory field
		$result[] = array(
			'type'=>'other',
			'label'=>$dictionaryItem->label
		);
		global $langs;
		if(in_array(DigitalSignaturePeople::LINKED_OBJECT_USER_TYPE, $dictionaryItem->linkedContactType)) {
			$userHtmlName = $this->generateHtmlName($dictionaryItem->c_rowid, self::BASE_SIGNATORY_FIELDS_FROM_USER_HTML_NAME);
			$result[] = array(
				'type'=>'other',
				'label'=> $langs->trans('DigitalSignatureManagerSelectUserForSignatoryField'),
				'name' =>$userHtmlName,
				'value'=>$this->formDigitalSignatureManager->selectUser($object, $this->getSelectedUserIdForDictionaryLineId($dictionaryItem->id), $userHtmlName)
			);
		}
		if(in_array(DigitalSignaturePeople::LINKED_OBJECT_CONTACT_TYPE, $dictionaryItem->linkedContactType)) {
			$contactHtmlName = $this->generateHtmlName($dictionaryItem->c_rowid, self::BASE_SIGNATORY_FIELDS_FROM_CONTACT_HTML_NAME);
			$result[] = array(
				'type'=>'other',
				'label'=> $langs->trans('DigitalSignatureManagerSelectContactForSignatoryField'),
				'name' =>$contactHtmlName,
				'value'=>$this->formDigitalSignatureManager->selectContact($object, $this->getSelectedContactIdForDictionaryLineId($dictionaryItem->id), $contactHtmlName, $object->fk_soc)
			);
		}
		if(in_array(DigitalSignaturePeople::LINKED_OBJECT_FREE_TYPE, $dictionaryItem->linkedContactType)) {
			//We add firstname
			//we add lastname
			//We add phone number
			//We add mail
		}
		return $result;
	}

	/**
	 * Function to get html name for a signatory fields input
	 * @param int $dictionaryLineId id of the line being edited
	 * @param string $baseHtmlName Base html name to generate html name with id managment
	 */
	public function generateHtmlName($dictionaryLineId, $baseHtmlName)
	{
		return $baseHtmlName . '-row-' . $dictionaryLineId;
	}

	public function getSelectedUserIdForDictionaryLineId($dictionaryLineId) {
		$postContent = GETPOST($this->generateHtmlName($dictionaryLineId, self::BASE_SIGNATORY_FIELDS_FROM_USER_HTML_NAME));
		return $postContent ?? null;
	}

	public function getSelectedContactIdForDictionaryLineId($dictionaryLineId) {
		$postContent = GETPOST($this->generateHtmlName($dictionaryLineId, self::BASE_SIGNATORY_FIELDS_FROM_CONTACT_HTML_NAME));
		return $postContent ?? null;
	}
}
