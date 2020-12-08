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
class FormDigitalSignatureSignatoryField
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
	 * @var FormDigitalSignatureDocument Instance of a form digital signature document to properly display them
	 */
	public $formDigitalSignatureDocument;

	/**
	 * @var DigitalSignatureSignatoryField Shared Instance of DigitalSignaturePeople
	 */
	public $elementObjectStatic;

	/**
	 * @var array
	 */
	public static $errors = array();

	/**
	 * @var string Delete Element Action Name
	 */
	const DELETE_ACTION_NAME = 'deleteSignatoryField';

	/**
	 * @var string confirm Delete Element Action
	 */
	const CONFIRM_DELETE_ACTION_NAME = 'confirmDeleteSignatoryField';

	/**
	 * @var string Edit Element Action Name
	 */
	const EDIT_ACTION_NAME = 'editSignatoryField';

	/**
	 * @var string Edit Element Action Name
	 */
	const SAVE_ACTION_NAME = 'saveSignatoryField';

	/**
	 * @var string Add Element Action Name from User
	 */
	const ADD_ACTION_NAME = 'addSignatoryField';

	/**
	 * @var string name of the post field containing Element id
	 */
	const ELEMENT_POST_ID_FIELD_NAME = 'signatoryFieldId';

	/**
	 * @var string name of the post field linked Document id
	 */
	const ELEMENT_POST_DOCUMENT_ID_FIELD_NAME = 'signatoryFieldDocumentId';

	/**
	 * @var string name of the post field containing signatory id
	 */
	const ELEMENT_POST_SIGNATORY_ID_FIELD_NAME = 'signatoryDocumentId';

	/**
	 * @var string name of the post field containing Element id
	 */
	const ELEMENT_POST_PAGE_FIELD_NAME = 'signatoryFieldPageNumber';

	/**
	 * @var string name of the post field containing Element id
	 */
	const ELEMENT_POST_LABEL_FIELD_NAME = 'signatoryFieldLabel';

	/**
	 * @var string name of the post field containing Element id
	 */
	const ELEMENT_POST_X_AXIS_FIELD_NAME = 'signatoryFieldXAxis';

	/**
	 * @var string name of the post field containing Element id
	 */
	const ELEMENT_POST_Y_AXIS_FIELD_NAME = 'signatoryFieldYAxis';

	/**
	 * @var string prefix id of existing document row
	 */
	const ELEMENT_PREFIX_ROW = "digitalSignatoryField";

	/**
	 * @var string id of row for adding element
	 */
	const ELEMENT_PREFIX_NEW_ROW = "newDigitalSignatoryField";

	/**
	 * @var string id of row for adding element
	 */
	const ELEMENT_SAVE_BUTTON_NAME = "saveDigitalSignatoryField";

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

		dol_include_once('/digitalsignaturemanager/class/digitalsignaturesignatoryfield.class.php');
		$this->elementObjectStatic = new DigitalSignatureSignatoryField($this->db);

		dol_include_once('/digitalsignaturemanager/class/html.formdigitalsignaturedocument.class.php');
		$this->formDigitalSignatureDocument = new FormDigitalSignatureDocument($db);
	}

	/**
	 *  Display form to add a new free element
	 *
	 *  @param	DigitalSignatureRequest	$object			Object
	 *  @param int $numberOfColumnOfContentTable number of column into parent table for content
	 *  @param int $numberOfActionColumnOfTheTable number of column into parent table for action button
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function showAddForm($object, $numberOfColumnOfContentTable, $numberOfActionColumnOfTheTable)
	{
		global $hookmanager, $action;
		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_NEW_ROW . '" class="nodrag nodrop nohoverpair liste_titre_create oddeven">';
		print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '#' . self::ELEMENT_PREFIX_NEW_ROW . '" enctype="multipart/form-data" method="post">';
		print '<input type="hidden" name="action" value="' . self::ADD_ACTION_NAME . '">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

		if($reshook == 0) {
			global $conf, $langs;
			//We display number column
			if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
				print '<td class="linecolnum" align="center"></td>';
			}

			for ($i = 5; $i < $numberOfColumnOfContentTable; $i++) {
				print '<td class="content"></td>';
			}

			global $action;
			if ($action == self::ADD_ACTION_NAME) {
				$this->elementObjectStatic = self::updateFromPost($this->elementObjectStatic);
			}
			$digitalSignatureSignatoryField = $this->elementObjectStatic;

			//we display input form
			print $this->getInputForm($object, $digitalSignatureSignatoryField);

			// Show add button
			$numberOfActionColumnOfTheTable = $numberOfActionColumnOfTheTable < 1 ? 1 : $numberOfActionColumnOfTheTable;
			print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="' . $numberOfActionColumnOfTheTable . '">';
			print '<input type="submit" class="button" value="' . $langs->trans('DigitalSignatureAddSignatoryField') . '">';
			print '</td>';
		}

		//We end row
		print '</form>';
		print '</tr>';
	}


	/**
	 *  Display form to edit a new document into card lines
	 *
	 *  @param	DigitalSignatureRequest	$object			Object
	 *  @param  DigitalSignatureSignatoryField $digitalSignatureSignatoryField People being edited
	 *  @param string $userCanMoveLine can user ask to move line ?
	 *  @param int $numberOfColumnOfContentTable number of column into parent table for content
	 *  @param int $numberOfActionColumnOfTheTable number of column into parent table for action button
	 *  @param bool $showPreviewColumn should we display Column for linked object getNomUrl
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function showEditForm($object, $digitalSignatureSignatoryField, $numberOfColumnOfContentTable, $numberOfActionColumnOfTheTable)
	{
		global $hookmanager, $action;
		$parameters = array();
		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_ROW . '-' . $digitalSignatureSignatoryField->id . '" class="oddeven drag drop">';
		print '<form action="' . $this->formDigitalSignatureManager->buildActionUrlForLine($object->id, null, null, $digitalSignatureSignatoryField->id, self::ELEMENT_PREFIX_ROW) . '" enctype="multipart/form-data" method="post">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="' . self::SAVE_ACTION_NAME . '">';
		print '<input type="hidden" name="' . self::ELEMENT_POST_ID_FIELD_NAME . '" value="' . $digitalSignatureSignatoryField->id . '">';

		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		global $conf, $langs;

		$digitalSignatureSignatoryField = self::updateFromPost($digitalSignatureSignatoryField, true);

		if($reshook == 0) {
			//We display number column
			if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
				print '<td class="linecolnum" align="center"></td>';
			}

			for ($i = 5; $i < $numberOfColumnOfContentTable; $i++) {
				print '<td class="content"></td>';
			}

			//we display input form
			print $this->getInputForm($object, $digitalSignatureSignatoryField, true);

			$numberOfActionColumnOfTheTable = $numberOfActionColumnOfTheTable < 1 ? 1 : $numberOfActionColumnOfTheTable;
			print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="' . $numberOfActionColumnOfTheTable . '">';
			print '<input type="submit" class="button" name="' . self::ELEMENT_SAVE_BUTTON_NAME . '" value="' . $langs->trans('Save') . '">';
			print '<input type="submit" class="button" name="cancel" value="' . $langs->trans('Cancel') . '">';
			print '</td>';
		}

		//We end row
		print '</form>';
		print '</tr>';
	}


	/**
	 * get input form field for select linked document
	 * @param DigitalSignatureRequest $digitalSignatureRequest Digital Signature Request on which we are editing information
	 * @param DigitalSignatureSignatoryField $digitalSignatureSignatoryField current edited digital signature signatory field
	 * @param bool $displayInformation should we display helper about fields
	 * @param bool $displayWarnings should warning be displayed displaying data validation result
	 * @return void
	 */
	public function getDocumentInputForm($digitalSignatureRequest, $digitalSignatureSignatoryField, $displayInformation, $displayWarnings)
	{
		$arrayOfElements = array();
		global $langs;
		$arrayOfAvailableDocuments = $digitalSignatureRequest->getLinkedDocuments();
		foreach($arrayOfAvailableDocuments as $document) {
			$arrayOfElements[$document->id] = $document->getDocumentName();
		}
		return $this->formDigitalSignatureManager->getSelectFieldColumn(
			self::ELEMENT_POST_DOCUMENT_ID_FIELD_NAME,
			$digitalSignatureSignatoryField->fk_chosen_digitalsignaturedocument,
			$arrayOfElements,
			$displayInformation,
			$langs->trans('DigitalSignatureManagerDocumentSelectionInfoBox'),
			$displayWarnings,
			$digitalSignatureSignatoryField->checkLinkedDocumentValidity()
		);
	}

	/**
	 * get input form field for select linked document
	 * @param DigitalSignatureRequest $digitalSignatureRequest Digital Signature Request on which we are editing information
	 * @param DigitalSignatureSignatoryField $digitalSignatureSignatoryField current edited digital signature signatory field
	 * @param bool $displayInformation should we display helper about fields
	 * @param bool $displayWarnings should warning be displayed displaying data validation result
	 * @return void
	 */
	public function getPeopleInputForm($digitalSignatureRequest, $digitalSignatureSignatoryField, $displayInformation, $displayWarnings)
	{
		$arrayOfElements = array();
		global $langs;
		$arrayOfAvailablePeople = $digitalSignatureRequest->getLinkedPeople();
		foreach($arrayOfAvailablePeople as $people) {
			$arrayOfElements[$people->id] = $people->displayName();
		}
		return $this->formDigitalSignatureManager->getSelectFieldColumn(
			self::ELEMENT_POST_SIGNATORY_ID_FIELD_NAME,
			$digitalSignatureSignatoryField->fk_chosen_digitalsignaturepeople,
			$arrayOfElements,
			$displayInformation,
			$langs->trans('DigitalSignatureManagerSignatorySelectionInfoBox'),
			$displayWarnings,
			$digitalSignatureSignatoryField->checkLinkedSignatoryValidity()
		);
	}

	/**
	 * get input form of digital signature people
	 * @param DigitalSignatureRequest $digitalSignatureRequest Digital Signature Request on which we are editing information
	 * @param DigitalSignatureSignatoryField $digitalSignatureSignatoryField - object to be displayed
	 * @param bool $displayWarnings should warning be displayed displaying data validation result
	 * @param bool $displayInformation should we display helper about fields
	 * @return string html output to be printed
	 */
	public function getInputForm($digitalSignatureRequest, $digitalSignatureSignatoryField, $displayWarnings = false, $displayInformation = true)
	{
		global $langs;
		$out = '';
		// We show signatory selection Field
		$out .= $this->getPeopleInputForm(
			$digitalSignatureRequest,
			$digitalSignatureSignatoryField,
			$displayInformation,
			$displayWarnings
		);

		//We show document selection field
		$out .= $this->getDocumentInputForm(
			$digitalSignatureRequest,
			$digitalSignatureSignatoryField,
			$displayInformation,
			$displayWarnings
		);

		//We show label field
		$out .= $this->formDigitalSignatureManager->getInputFieldColumn(
			self::ELEMENT_POST_LABEL_FIELD_NAME,
			$digitalSignatureSignatoryField->label,
			$displayInformation,
			'',
			$displayWarnings,
			''
		);

		//We show page number field
		$out .= $this->formDigitalSignatureManager->getInputFieldColumn(
			self::ELEMENT_POST_PAGE_FIELD_NAME,
			$digitalSignatureSignatoryField->page,
			$displayInformation,
			$langs->trans('DigitalSignatureSignatoryFieldPageInfoBox'),
			$displayWarnings,
			$digitalSignatureSignatoryField->checkPageNumberValidity(),
			'number'
		);

		//We show X axis field
		$out .= $this->formDigitalSignatureManager->getInputFieldColumn(
			self::ELEMENT_POST_X_AXIS_FIELD_NAME,
			$digitalSignatureSignatoryField->x,
			$displayInformation,
			$langs->trans('DigitalSignatureSignatoryFieldXAxisInfoBox'),
			$displayWarnings,
			$digitalSignatureSignatoryField->checkXAxisValidity(),
			'number'
		);

		//We show Y axis field
		$out .= $this->formDigitalSignatureManager->getInputFieldColumn(
			self::ELEMENT_POST_Y_AXIS_FIELD_NAME,
			$digitalSignatureSignatoryField->y,
			$displayInformation,
			$langs->trans('DigitalSignatureSignatoryFieldYAxisInfoBox'),
			$displayWarnings,
			$digitalSignatureSignatoryField->checkYAxisValidity(),
			'number'
		);
		return $out;
	}


	/**
	 *  Display form to display digital signature people into card lines
	 *
	 *  @param  DigitalSignatureSignatoryField $digitalSignatureSignatoryField being shown
	 *  @param bool $userCanAskToEditLine display edit button
	 *  @param bool $userCanAskToDeleteLine display delete button
	 *  @param int $numberOfActionColumnOfTheTable number of columns for action on the parent table
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function show($digitalSignatureSignatoryField, $userCanAskToEditLine, $userCanAskToDeleteLine, $numberOfActionColumnOfTheTable)
	{
		$digitalSignatureRequestId = $digitalSignatureSignatoryField->digitalSignatureRequest->id;
		global $conf, $langs;

		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_ROW . '-' . $digitalSignatureSignatoryField->id . '" class="oddeven drag drop">';
		//We display number column
		if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
		}

		// We show Linked Signatory Field
		print '<td>';
		$digitalSignaturePeople = $digitalSignatureSignatoryField->getChosenDigitalSignaturePeople();
		if($digitalSignaturePeople) {
			print $digitalSignaturePeople->displayName();
			print $this->formDigitalSignatureManager->getWarningInfoBox($userCanAskToEditLine, $digitalSignatureSignatoryField->checkLinkedSignatoryValidity());
		}
		else {
			print $langs->trans('DigitalSignatureManagerSignatoryFieldChosenSignatoryDoesNotExistAnymore');
		}
		print '</td>';

		//We show Linked Document Field
		print '<td>';
		$digitalSignatureDocument = $digitalSignatureSignatoryField->getChosenDigitalSignatureDocument();
		if($digitalSignatureDocument) {
			print $this->formDigitalSignatureDocument->getDocumentLinkAndPreview($digitalSignatureDocument);
			print $this->formDigitalSignatureManager->getWarningInfoBox($userCanAskToEditLine, $digitalSignatureSignatoryField->checkLinkedDocumentValidity());
		}
		else {
			print $langs->trans('DigitalSignatureManagerSignatoryFieldChosenDocumentDoesNotExistAnymore');
		}
		print '</td>';


		//We label Page field
		print '<td>';
		print $digitalSignatureSignatoryField->label;
		print '</td>';

		//We show Page field
		print '<td>';
		print $digitalSignatureSignatoryField->page;
		print $this->formDigitalSignatureManager->getWarningInfoBox($userCanAskToEditLine, $digitalSignatureSignatoryField->checkPageNumberValidity());
		print '</td>';

		//We show X Axis Field
		print '<td>';
		print $digitalSignatureSignatoryField->x;
		print $this->formDigitalSignatureManager->getWarningInfoBox($userCanAskToEditLine, $digitalSignatureSignatoryField->checkXAxisValidity());
		print '</td>';

		//We show Y Axis Field
		print '<td>';
		print $digitalSignatureSignatoryField->y;
		print $this->formDigitalSignatureManager->getWarningInfoBox($userCanAskToEditLine, $digitalSignatureSignatoryField->checkYAxisValidity());
		print '</td>';

		$nbOfActionColumn = count(array_filter(array($userCanAskToEditLine, $userCanAskToDeleteLine)));

		for ($i = $nbOfActionColumn; $i < $numberOfActionColumnOfTheTable; $i++) {
			print '<td class="linecoledit" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		// Show edit button
		if ($userCanAskToEditLine) {
			print '<td class="linecoledit" align="center">';
			print '<a href="' . $this->formDigitalSignatureManager->buildActionUrlForLine($digitalSignatureRequestId, self::EDIT_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME, $digitalSignatureSignatoryField->id, self::ELEMENT_PREFIX_ROW) . '">';
			print img_edit();
			print '</td>';
		}
		//Show delete button
		if ($userCanAskToDeleteLine) {
			print '<td class="linecoldelete" align="center">';
			print '<a href="' . $this->formDigitalSignatureManager->buildActionUrlForLine($digitalSignatureRequestId, self::DELETE_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME, $digitalSignatureSignatoryField->id, self::ELEMENT_PREFIX_ROW) . '">';
			print img_delete();
			print '</td>';
		}
		//We end row
		print '</tr>';
	}

	/**
	 * Function to manage delete on page which called showDocument methods
	 * @param DigitalSignatureRequest $object $linked digital signature request
	 * @param string $action current action name on card
	 * @param string	$confirm user action confirmation
	 * @param User $user User doing actions
	 * @return void
	 */
	public function manageDeleteAction($object, $action, $confirm, $user)
	{
		if ($action == self::CONFIRM_DELETE_ACTION_NAME && $confirm == 'yes') {
			$idToDelete = $this->getFormElementId();
			$digitalSignatureSignatoryField = $this->elementObjectStatic;
			global $langs;
			if ($digitalSignatureSignatoryField->fetch($idToDelete) < 0 || $object->id != $digitalSignatureSignatoryField->fk_digitalsignaturerequest) {
				setEventMessages($langs->trans('DigitalSignatureSignatoryFieldNotFoundOrAlreadyDeleted'), $digitalSignatureSignatoryField->errors, 'errors');
			} elseif ($digitalSignatureSignatoryField->delete($user) > 0) {
				setEventMessages($langs->trans('DigitalSignatureManagerSignatoryFielduccessfullyDeleted'), $digitalSignatureSignatoryField->errors);
			} else {
				setEventMessages($langs->trans('DigitalSignatureManagerSignatoryFieldErrorWhileDeleting'), $digitalSignatureSignatoryField->errors, 'errors');
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
			$payload = self::updateFromPost($this->elementObjectStatic);
			$payload->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
			if ($payload->create($user) > 0) {
				setEventMessages($langs->trans('DigitalSignatureRequestSignatoryFieldSuccesfullyAdded'), array());
				$action = null;
			} else {
				setEventMessages($langs->trans('DigitalSignatureRequestSignatoryFieldeErrorWhenAdded'), $payload->errors, 'errors');
			}
		}
	}

	/**
	 * Function to manage edition of a signatory field line
	 * @param string $action current action name on card
	 * @param DigitalSignatureRequest $object current action name on card
	 * @param User $user User doing actions
	 * @return void
	 */
	public function manageSaveAction(&$action, $object, $user)
	{
		global $langs;
		if ($action == self::SAVE_ACTION_NAME && GETPOST(self::ELEMENT_SAVE_BUTTON_NAME, 'alpha') == $langs->trans("Save")) {
			$payload = $this->elementObjectStatic;
			if ($payload->fetch($this->getFormElementId()) < 0 || $object->id != $payload->fk_digitalsignaturerequest) {
				setEventMessages($langs->trans('DigitalSignatureSignatoryFieldNotFound'), $payload->errors, 'errors');
			} else {
				$payload = self::updateFromPost($payload);
				if ($payload->update($user) < 0) {
					setEventMessages($langs->trans('DigitalSignatureSignatoryFieldErrorWhileSaving'), $payload->errors, 'errors');
					$action = self::EDIT_ACTION_NAME;
				} else {
					setEventMessages($langs->trans('DigitalSignatureSignatoryFieldSuccessfullySaved'), array());
				}
			}
		}
	}

	/**
	 * Function to get current digital signature signatory field id edited on page using show
	 * @param string $action current action name on card
	 * @return int|null
	 */
	public function getCurrentAskedEditedElementId($action)
	{
		$currentDocumentIdEdited = null;
		if ($action == self::EDIT_ACTION_NAME) {
			$currentDocumentIdEdited = $this->getFormElementId();
		}
		return !empty($currentDocumentIdEdited) ? $currentDocumentIdEdited : null;
	}

	/**
	 * Get current digital signature signatory field id on which an action is performed
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
			$signatoryField = $this->elementObjectStatic;
			if($signatoryField->fetch($this->getFormElementId()) > 0) {
				return $this->form->formconfirm($this->formDigitalSignatureManager->buildActionUrlForLine($object->id), $langs->trans('DigitalSignatureManagerSignatoryFieldConfirmDeleteTitle'), $langs->trans('DigitalSignatureManagerSignatoryFieldConfirmDeleteDescription'), self::CONFIRM_DELETE_ACTION_NAME, $formquestion, 0, 1, 220);
			}
		}
		return $formconfirm;
	}

	/**
	 * Function to update digital signature people from POST
	 * @param DigitalSignaturePeople $digitalSignaturePeople object instance to be updated with data from post
	 * @param bool $fillOnlyIfFieldIsPresentOnPost - fill field only field that are in POST
	 * @return DigitalSignaturePeople updated object
	 */
	public static function updateFromPost($digitalSignaturePeople, $fillOnlyIfFieldIsPresentOnPost = false)
	{
		$arrayOfPostParameterAndDigitalSignaturePropertyName = array(
			self::ELEMENT_POST_DOCUMENT_ID_FIELD_NAME => 'fk_chosen_digitalsignaturedocument',
			self::ELEMENT_POST_SIGNATORY_ID_FIELD_NAME => 'fk_chosen_digitalsignaturepeople',
			self::ELEMENT_POST_LABEL_FIELD_NAME => 'label',
			self::ELEMENT_POST_PAGE_FIELD_NAME => 'page',
			self::ELEMENT_POST_X_AXIS_FIELD_NAME => 'x',
			self::ELEMENT_POST_Y_AXIS_FIELD_NAME => 'y'
		);
		foreach ($arrayOfPostParameterAndDigitalSignaturePropertyName as $postFieldName => $propertyName) {
			if (!$fillOnlyIfFieldIsPresentOnPost || isset($_POST[$postFieldName])) {
				$digitalSignaturePeople->$propertyName = GETPOST($postFieldName);
			}
		}
		return $digitalSignaturePeople;
	}
}
