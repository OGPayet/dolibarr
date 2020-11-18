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


/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 */
class FormDigitalSignatureDocument
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
	 * @var FormFile Instance of the formFile
	 */
	public $formFile;

	/**
	 * @var FormDigitalSignatureManager Instance of the shared form
	 */
	public $formDigitalSignatureManager;

	/**
	 * @var DigitalSignatureDocument Shared Instance of DigitalSignatureDocument
	 */
	public $elementObjectStatic;

	/**
	 * @var array
	 */
	public static $errors = array();

	/**
	 * @var string Delete Document Action Name
	 */
	const DELETE_ACTION_NAME = 'deleteDocument';

	/**
	 * @var string confirm Delete Document Action
	 */
	const CONFIRM_DELETE_ACTION_NAME = 'confirmDeleteDocument';

	/**
	 * @var string Edit Document Action Name
	 */
	const EDIT_ACTION_NAME = 'editDocument';

	/**
	 * @var string Edit Document Action Name
	 */
	const SAVE_ACTION_NAME = 'saveDocument';

	/**
	 * @var string Add Document Action Name
	 */
	const ADD_ACTION_NAME = 'addDocument';

	/**
	 * @var string move up Document Action Name
	 */
	const MOVE_UP_ACTION_NAME = 'moveUpDocument';

	/**
	 * @var string move up Document Action Name
	 */
	const MOVE_DOWN_ACTION_NAME = 'moveDownDocument';

	/**
	 * @var string name of the post field containing document id
	 */
	const ELEMENT_POST_ID_FIELD_NAME = 'documentId';

	/**
	 * @var string name of the post field containing checkbox ids
	 */
	const ELEMENT_POST_CHECKBOX_IDS_FIELD_NAME = 'chosenCheckBoxIds';

	/**
	 * @var string name of label document field in post request
	 */
	const ELEMENT_POST_LABEL_FIELD_NAME = "documentLabelName";

	/**
	 * @var string name of file post document field in post request
	 */
	const ELEMENT_POST_FILE_FIELD_NAME = "addedFileForDocument";

	/**
	 * @var string prefix id of existing document row
	 */
	const ELEMENT_PREFIX_ROW = "document";

	/**
	 * @var string id of row for adding element
	 */
	const ELEMENT_PREFIX_NEW_ROW = "newDocumentSignature";

	/**
	 * @var string id of row for adding element
	 */
	const ELEMENT_SAVE_BUTTON_NAME = "saveDocument";

	/**
	 * Constructor
	 *
	 * @param   DoliDB $db Database handler
	 */
	public function __construct(DoliDb $db)
	{
		$this->db = $db;
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
		dol_include_once('/core/class/html.form.class.php');
		$this->form = new Form($db);
		dol_include_once('/core/class/html.formfile.class.php');
		$this->formFile = new FormFile($db);

		dol_include_once('/digitalsignaturemanager/class/html.formdigitalsignaturemanager.class.php');
		$this->formDigitalSignatureManager = new FormDigitalSignatureManager($db);

		dol_include_once('/digitalsignaturemanager/class/digitalsignaturedocument.class.php');
		$this->elementObjectStatic = new DigitalSignatureDocument($this->db);
	}

	/**
	 *  Display form to add a new document into card lines
	 *
	 *  @param	DigitalSignatureRequest	$object			Object
	 *  @param int $numberOfActionColumns number of column used by actions
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function showDocumentAddForm($object, $numberOfActionColumns)
	{
		global $hookmanager, $action;
		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_NEW_ROW . '" class="nodrag nodrop nohoverpair liste_titre_create oddeven">';
		print '<form action="' . $this->formDigitalSignatureManager->buildActionUrlForLine($object->id, null, null, null, self::ELEMENT_PREFIX_NEW_ROW) . '" enctype="multipart/form-data" method="post">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

		$colspan = 0; //used for extrafields
		global $conf, $langs;
		//We display number column
		if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
			$colspan++;
		}

		if($action == self::ADD_ACTION_NAME) {
			$document = self::updateFromPost($this->elementObjectStatic, true);
		}

		// We show upload file form
		print '<td>';
		print '<input type="hidden" name="action" value="' . self::ADD_ACTION_NAME . '">';
		print '<input type="hidden" name="max_file_size" value="' . 1024 * 1024 * 1024 . '">'; //Value must be given in B
		print '<input class="flat minwidth400" type="file" name="' . self::ELEMENT_POST_FILE_FIELD_NAME . '" accept=".pdf">';
		print '</td>';
		$colspan++;

		//we Show check boxes multi select array
		print '<td>';
		print $this->getCheckBoxInputForm($object, $document->check_box_ids, true);
		print '</td>';

		// Show add button
		print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="' . $numberOfActionColumns . '">';
		print '<input type="submit" class="button" value="' . $langs->trans('Add') . '">';
		print '</td>';
		$colspan++;

		//We end row
		print '</form>';
		print '</tr>';
	}

	/**
	 *  Display form to edit a new document into card lines
	 *
	 *  @param	DigitalSignatureRequest	$object			Object
	 *  @param  DigitalSignatureDocument $document Document being edited
	 *  @param int $numberOfActionColumnsOnParentTable number of column used by actions
	 *  @param string $userCanMoveLine
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function showDocumentEditForm($object, $document, $numberOfActionColumnsOnParentTable, $userCanMoveLine)
	{
		global $hookmanager, $action;
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

		$colspan = 0; //used for extrafields
		global $conf, $langs;

		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_ROW . '-' . $document->id . '" class="oddeven drag drop">';

		print '<form action="' . $this->formDigitalSignatureManager->buildActionUrlForLine($object->id, null, null, $document->id, self::ELEMENT_PREFIX_ROW) . '" enctype="multipart/form-data" method="post">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';

		//We display number column
		if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
			$colspan++;
		}
		if($action == self::EDIT_ACTION_NAME) {
			$document = self::updateFromPost($document, true);
		}
		// We show upload file name edit form
		$labelToShow = GETPOST(self::ELEMENT_POST_LABEL_FIELD_NAME) ? GETPOST(self::ELEMENT_POST_LABEL_FIELD_NAME) : $document->getDocumentName();
		print '<td>';
		print '<input class="flat minwidth400" type="text" name="' . self::ELEMENT_POST_LABEL_FIELD_NAME . '" value="' . $labelToShow . '" style="width:100%">';
		print '</td>';
		$colspan++;

		//we Show check boxes multi select array
		print '<td>';
		print $this->getCheckBoxInputForm($object, $document->check_box_ids);
		print '</td>';

		// Show save and cancel button
		$colSpanForActionButton = $numberOfActionColumnsOnParentTable - 1;
		if ($userCanMoveLine > 1) {
			$colSpanForActionButton -= 1;
		}
		print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="' . $colSpanForActionButton . '">';
		print '<input type="hidden" name="action" value="' . self::SAVE_ACTION_NAME . '">';
		print '<input type="hidden" name="' . self::ELEMENT_POST_ID_FIELD_NAME . '" value="' . $document->id . '">';
		print '<input type="submit" class="button" name="' . self::ELEMENT_SAVE_BUTTON_NAME . '" value="' . $langs->trans('Save') . '">';
		print '<input type="submit" class="button" name="cancel" value="' . $langs->trans('Cancel') . '">';
		print '</td>';
		$colspan++;

		//Show move button
		if ($userCanMoveLine) {
			$this->formDigitalSignatureManager->showMoveActionButtonsForLine($object->id, $document->id, $document->position, count($object->documents), self::MOVE_UP_ACTION_NAME, self::MOVE_DOWN_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME);
			$colspan++;
		}

		//We end row
		print '</form>';
		print '</tr>';
	}


	/**
	 *  Display form to add a new document into card lines
	 *
	 *  @param  DigitalSignatureDocument $document Document being edited
	 *  @param bool $userCanAskToEditLine display edit button
	 *  @param bool $userCanAskToDeleteLine display delete button
	 *  @param bool $userCanMoveLine display move button
	 *  @param int  $numberOfActionColumnOfTheTable number of column into action
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function showDocument($document, $userCanAskToEditLine, $userCanAskToDeleteLine, $userCanMoveLine, $numberOfActionColumnOfTheTable)
	{
		$digitalSignatureRequestId = $document->digitalSignatureRequest->id;
		global $conf;

		$numberOfActionColumnDisplayed = count(array_filter(array($userCanAskToEditLine, $userCanAskToDeleteLine, $userCanMoveLine)));

		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_ROW . '-' . $document->id . '" class="oddeven drag drop">';
		//We display number column
		if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
		}
		// We show uploaded file
		print '<td>';
		print $this->getDocumentLinkAndPreview($document);
		print '</td>';

		// We show Chosen Check Boxes
		print '<td>';
		$displayCheckBoxLabels = array();
		foreach($document->checkBoxes as $checkBox) {
			$displayCheckBoxLabels[] = $checkBox->label;
		}
		print implode('<br>', $displayCheckBoxLabels);
		print '</td>';

		for ($i = $numberOfActionColumnDisplayed; $i < $numberOfActionColumnOfTheTable; $i++) {
			print '<td class="linecoledit" style="width: 10px"></td>';
			$numberOfActionColumnDisplayed++;
		}

		// Show edit button
		if ($userCanAskToEditLine) {
			print '<td class="linecoledit" align="center">';
			print '<a href="' . $this->formDigitalSignatureManager->buildActionUrlForLine($digitalSignatureRequestId, self::EDIT_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME, $document->id, self::ELEMENT_PREFIX_ROW) . '">';
			print img_edit();
			print '</td>';
		}

		if ($userCanAskToDeleteLine) {
			print '<td class="linecoldelete" align="center">';
			print '<a href="' . $this->formDigitalSignatureManager->buildActionUrlForLine($digitalSignatureRequestId, self::DELETE_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME, $document->id, self::ELEMENT_PREFIX_ROW) . '">';
			print img_delete();
			print '</td>';
		}

		if ($userCanMoveLine) {
			$this->formDigitalSignatureManager->showMoveActionButtonsForLine($digitalSignatureRequestId, $document->id, $document->position, count($document->digitalSignatureRequest->documents), self::MOVE_UP_ACTION_NAME, self::MOVE_DOWN_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME);
		}
		//We end row
		print '</tr>';
	}

	/**
	 * Function to display document file by its filename with link to download it and ability to preview it
	 * @param DigitalSignatureDocument $digitalSignatureDocument Given digital signature comment to be previewed
	 * @return string
	 */
	public function getDocumentLinkAndPreview($digitalSignatureDocument)
	{
		global $conf, $langs;
		//We prepare data to use elements from form file, as done by dolibarr core
		$documentUrl = DOL_URL_ROOT . '/document.php';
		$modulePart = 'digitalsignaturemanager';
		$relativePath = $digitalSignatureDocument->getLinkedFileRelativePath();
		$fileName = $digitalSignatureDocument->getDocumentName();
		$entityOfThisDocument = $digitalSignatureDocument->getEntity() ? $digitalSignatureDocument->getEntity() : $conf->entity;
		$entityParam = '&entity=' . $entityOfThisDocument;
		$arrayWithFileInformation = array('name' => $fileName);


		$out = '<a class="documentdownload paddingright" href="' . $documentUrl . '?modulepart=' . $modulePart . '&amp;file=' . urlencode($relativePath) . $entityParam;

		$mime = dol_mimetype($relativePath, '', 0);
		if (preg_match('/text/', $mime)) {
			$out .= ' target="_blank"';
		}
		$out .= '>';
		$out .= img_mime($fileName, $langs->trans("File") . ': ' . $fileName);
		$out .= dol_trunc($fileName, 150);
		$out .= '</a>' . "\n";
		$out .= $this->formFile->showPreview($arrayWithFileInformation, $modulePart, $relativePath, 0, $entityParam);
		$out .= '</td>';
		return $out;
	}

	/**
	 * Function to manage delete on page which called showDocument methods
	 * @param string $action current action name on card
	 * @param string	$confirm user action confirmation
	 * @param User $user User doing actions
	 * @return void
	 */
	public function manageDeleteAction($action, $confirm, $user)
	{
		if ($action == self::CONFIRM_DELETE_ACTION_NAME && $confirm == 'yes') {
			$idToDelete = $this->getFormElementId();
			$object = $this->elementObjectStatic;
			$result = $object->fetch($idToDelete);
			global $langs;
			if ($result < 0) {
				setEventMessages($langs->trans('DigitalSignatureRequestDocumentAlreadyDeleted'), array(), 'errors');
			}
			if ($result > 0 && $object->delete($user) > 0) {
				setEventMessages($langs->trans('DigitalSignatureManagerFileSuccessfullyDeleted', $object->getDocumentName()), array());
			}
			if (!empty($object->errors) || !empty($object->error)) {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	/**
	 * Function to manage addition of a file on page which called showDocumentEditForm methods
	 * @param string $action current action name on card
	 * @param DigitalSignatureRequest $digitalSignatureRequest digital signature request instance on which action are did
	 * @param User $user User doing actions
	 * @return void
	 */
	public function manageAddAction(&$action, $digitalSignatureRequest, $user)
	{
		if ($action == self::ADD_ACTION_NAME) {
			global $langs;
			$TFile = $_FILES[self::ELEMENT_POST_FILE_FIELD_NAME];
			if (empty($TFile['name'])) {
				setEventMessages($langs->trans('DigitalSignatureManagerErrorFileRequired'), array(), 'errors');
			} else {
				$result = dol_add_file_process($digitalSignatureRequest->getUploadDirOfFilesToSign(), 0, 1, self::ELEMENT_POST_FILE_FIELD_NAME);
				if ($result < 0) {
					setEventMessages($langs->trans('DigitalSignatureManagerErrorWhileSavingFile'), array(), 'errors');
				} else {
					//We have to get filename of the uploaded file
					if (!is_array($TFile['name'])) {
						foreach ($TFile as $key => &$val) {
							$val = array($val);
						}
					}
					$filename = $TFile['name'][0];
					//Now we  are able to find its ecm instance
					$ecmFile = DigitalSignatureDocument::getEcmInstanceOfFile($this->db, $digitalSignatureRequest->getRelativePathForFilesToSign(), $filename);
					//With ecm instance we can get ecm file id
					if ($ecmFile) {
						$newDigitalSignatureDocument = $this->elementObjectStatic;
						$newDigitalSignatureDocument->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
						$newDigitalSignatureDocument->fk_ecm = $ecmFile->id;
						$newDigitalSignatureDocument->position = $newDigitalSignatureDocument::getLastPositionOfDocument($digitalSignatureRequest->documents) + 1;
						//We may add here property elements from the form
						$newDigitalSignatureDocument = self::updateFromPost($newDigitalSignatureDocument);
						//We create document
						$result = $newDigitalSignatureDocument->create($user);
						if ($result < 0) {
							setEventMessages($langs->trans('DigitalSignatureManagerErrorWhileAddingFileToSignatureRequest'), $newDigitalSignatureDocument->errors, 'errors');
						} else {
							setEventMessages($langs->trans('DigitalSignatureManagerFileSuccessfullyAddedToRequest', $newDigitalSignatureDocument->getDocumentName()), array());
							$action = null;
						}
					} else {
						setEventMessages($langs->trans('DigitalSignatureManagerFileCantFindIntoEcmDatabase'), array(), 'errors');
					}
				}
			}
		}
	}

	/**
	 * Function to manage edition of a document line
	 * @param string $action current action name on card
	 * @param DigitalSignatureRequest $object current action name on card
	 * @param User $user User doing actions
	 * @return void
	 */
	public function manageSaveAction(&$action, $object, $user)
	{
		global $langs;
		if ($action == self::SAVE_ACTION_NAME && GETPOST(self::ELEMENT_SAVE_BUTTON_NAME, 'alpha') == $langs->trans("Save")) {
			$newFileName = GETPOST(self::ELEMENT_POST_LABEL_FIELD_NAME);
			if (empty($newFileName)) {
				setEventMessages($langs->trans('DigitalSignatureManagerErrorDocumentNameRequired'), array(), 'errors');
				$action = self::EDIT_ACTION_NAME;
			} else {
				$documentToEdit = $this->elementObjectStatic;
				$documentToEdit->digitalSignatureRequest = $object;
				if($documentToEdit->fetch($this->getFormElementId()) < 0 ||  $documentToEdit->fk_digitalsignaturerequest != $object->id) {
					setEventMessages($langs->trans('DigitalSignatureManagerDocumentNotFound'), $documentToEdit->errors, 'errors');
				}
				elseif (!$documentToEdit->renameDocumentFilename($newFileName, $user)) {
					$action = self::EDIT_ACTION_NAME;
					setEventMessages($langs->trans('DigitalSignatureManagerErrorWhileSavingEditedDocument'), array(), 'errors');
				} else {
					//We update fk_ecm property as Dolibarr doesn't fucking keep id on file move
					$ecmFile = DigitalSignatureDocument::getEcmInstanceOfFile($this->db, $object->getRelativePathForFilesToSign(), $newFileName);
					if(!$ecmFile) {
						setEventMessages($langs->trans('DigitalSignatureManagerErrorWhileMovingFiles'), array());
					}
					else {
						//We update other fields and save it
						$documentToEdit = self::updateFromPost($documentToEdit);
						$documentToEdit->fk_ecm = $ecmFile->id;
						if ($documentToEdit->update($user) > 0) {
							setEventMessages($langs->trans('DigitalSignatureManagerDocumentSuccesfullyUpdate'), array());
						} else {
							setEventMessages($langs->trans('DigitalSignatureManagerDocumentErrorWhileUpdating'), $documentToEdit->errors);
						}
					}
				}
			}
		}
	}

	/**
	 * Function to get current document id edited on page using showDocument
	 * @param string $action current action name on card
	 * @return int|null
	 */
	public function getCurrentAskedEditedElementId($action)
	{
		return $this->formDigitalSignatureManager->isAnElementBeingEdited($action, self::EDIT_ACTION_NAME) ? $this->getFormElementId() : null;
	}

	/**
	 * Get current digital signature people id on which an action is performed
	 * @return int
	 */
	public function getFormElementId()
	{
		return $this->formDigitalSignatureManager->getFormElementId(self::ELEMENT_POST_ID_FIELD_NAME);
	}


	/**
	 * Get formConfirm for delete action
	 * @param string $action current card name
	 * @param DigitalSignatureRequest $object Current edited request signature
	 * @param string $formconfirm previous form confirm generated
	 * @return string Content to be printed
	 */
	public function getDeleteFormConfirm($action, $object, $formconfirm = "")
	{
		if ($action == self::DELETE_ACTION_NAME) {
			global $langs;
			$formquestion = array(
				array('type' => 'hidden', 'name' => self::ELEMENT_POST_ID_FIELD_NAME, 'value' => $this->getFormElementId())
			);
			$documentStatic = $this->elementObjectStatic;
			$documentStatic->fetch($this->getFormElementId());
			return $this->form->formconfirm($this->formDigitalSignatureManager->buildActionUrlForLine($object->id), $langs->trans('DigitalSignatureRequestDocumentConfirmDeleteTitle'), $langs->trans('DigitalSignatureRequestDocumentConfirmDeleteDescription', $documentStatic->getDocumentName()), self::CONFIRM_DELETE_ACTION_NAME, $formquestion, 0, 1, 220);
		}
		return $formconfirm;
	}

	/**
	 * Get multi select form of checkboxes
	 * @param DigitalSignatureRequest $digitalSignatureRequest Request on which we have to select checkbox
	 * @param int[] $chosenCheckBoxIds array of chosen check box ids
	 * @return string
	 */
	public function getCheckBoxInputForm($digitalSignatureRequest, $chosenCheckBoxIds, $hideEmptyCheckBox = false)
	{
		$arrayOfKeyValue = array();
		foreach($digitalSignatureRequest->availableCheckBox as $checkBox) {
			if(!$hideEmptyCheckBox || !empty($checkBox->label)) {
				$arrayOfKeyValue[$checkBox->id] = $checkBox->label;
			}
		}
		return $this->form->multiselectarray(self::ELEMENT_POST_CHECKBOX_IDS_FIELD_NAME, $arrayOfKeyValue, $chosenCheckBoxIds, 0, 0, 'width95');
	}

	/**
	 * Function to update digital signature people from POST
	 * @param digitalSignatureDocument $digitalSignatureDocument object instance to be updated with data from post
	 * @param bool $fillOnlyIfFieldIsPresentOnPost - fill field only field that are in POST
	 * @return digitalSignatureDocument updated object
	 */
	public static function updateFromPost($digitalSignatureDocument, $fillOnlyIfFieldIsPresentOnPost = false)
	{
		if (!$fillOnlyIfFieldIsPresentOnPost || isset($_POST[self::ELEMENT_POST_CHECKBOX_IDS_FIELD_NAME])) {
			$arrayOfCheckBoxIds = array();
			foreach(GETPOST(self::ELEMENT_POST_CHECKBOX_IDS_FIELD_NAME) as $checkBoxId) {
				$arrayOfCheckBoxIds[] = (int) $checkBoxId;
			}
			$digitalSignatureDocument->check_box_ids = $arrayOfCheckBoxIds;
		}
		return $digitalSignatureDocument;
	}
}
