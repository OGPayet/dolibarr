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
class FormDigitalSignatureCheckBox
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
	 * @var array
	 */
	public static $errors = array();

	/**
	 * @var string Delete Document Action Name
	 */
	const DELETE_ACTION_NAME = 'deleteCheckBox';

	/**
	 * @var string confirm Delete Document Action
	 */
	const CONFIRM_DELETE_ACTION_NAME = 'confirmDeleteCheckBox';

	/**
	 * @var string Edit Document Action Name
	 */
	const EDIT_ACTION_NAME = 'editCheckBox';

	/**
	 * @var string Edit Document Action Name
	 */
	const SAVE_ACTION_NAME = 'saveCheckBox';

	/**
	 * @var string Add Document Action Name
	 */
	const ADD_ACTION_NAME = 'addCheckBox';

	/**
	 * @var string move up Document Action Name
	 */
	const MOVE_UP_ACTION_NAME = 'moveUpCheckBox';

	/**
	 * @var string move up Document Action Name
	 */
	const MOVE_DOWN_ACTION_NAME = 'moveDownCheckBox';

	/**
	 * @var string name of the post field containing document id
	 */
	const ELEMENT_POST_ID_FIELD_NAME = 'checkBoxId';

	/**
	 * @var string name of label document field in post request
	 */
	const ELEMENT_POST_LABEL_FIELD_NAME = "checkBoxLabel";

	/**
	 * @var string prefix id of existing document row
	 */
	const ELEMENT_PREFIX_ROW = "checkBox";

	/**
	 * @var string id of row for adding element
	 */
	const ELEMENT_PREFIX_NEW_ROW = "newCheckBox";

	/**
	 * @var string id of row for adding element
	 */
	const ELEMENT_SAVE_BUTTON_NAME = "saveCheckBox";

	/**
	 * @var DigitalSignatureCheckBox static element
	 */
	public $elementObjectStatic;

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

		dol_include_once('/digitalsignaturemanager/class/digitalsignaturecheckbox.class.php');
		$this->elementObjectStatic = new DigitalSignatureCheckBox($db);
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

		if ($reshook == 0) {
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

			//we display input form
			print $this->getInputForm($this->elementObjectStatic);

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
	 *  @param  DigitalSignatureCheckBox $checkBox Document being edited
	 *  @param int $numberOfActionColumns number of column used by actions
	 *  @param bool $userCanMoveLine is user allowed to move lines
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function showEditForm($object, $checkBox, $numberOfActionColumns, $userCanMoveLine)
	{
		global $hookmanager, $action;
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

		$colspan = 0; //used for extrafields
		global $conf, $langs;

		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_ROW . '-' . $checkBox->id . '" class="oddeven drag drop">';

		print '<form action="' . $this->formDigitalSignatureManager->buildActionUrlForLine($object->id, null, null, null, $checkBox->id, self::ELEMENT_PREFIX_ROW) . '" enctype="multipart/form-data" method="post">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';

		//We display number column
		if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
			$colspan++;
		}
		// We show edit form
		print $this->getInputForm($checkBox, true);

		// Show save and cancel button
		$colSpanForActionButton = $numberOfActionColumns - 1;
		if ($userCanMoveLine > 1) {
			$colSpanForActionButton -= 1;
		}
		print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="' . $colSpanForActionButton . '">';
		print '<input type="hidden" name="action" value="' . self::SAVE_ACTION_NAME . '">';
		print '<input type="hidden" name="' . self::ELEMENT_POST_ID_FIELD_NAME . '" value="' . $checkBox->id . '">';
		print '<input type="submit" class="button" name="' . self::ELEMENT_SAVE_BUTTON_NAME . '" value="' . $langs->trans('Save') . '">';
		print '<input type="submit" class="button" name="cancel" value="' . $langs->trans('Cancel') . '">';
		print '</td>';
		$colspan++;

		//Show move button
		if ($userCanMoveLine) {
			$this->formDigitalSignatureManager->showMoveActionButtonsForLine($object->id, $checkBox->id, $checkBox->position, count($object->documents), self::MOVE_UP_ACTION_NAME, self::MOVE_DOWN_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME);
			$colspan++;
		}

		//We end row
		print '</form>';
		print '</tr>';
	}


	/**
	 *  Display form to add a new document into card lines
	 *
	 *  @param  DigitalSignatureCheckBox $checkBox Document being edited
	 *  @param bool $userCanAskToEditLine display edit button
	 *  @param bool $userCanAskToDeleteLine display delete button
	 *  @param bool $userCanMoveLine display move button
	 *  @param int  $numberOfActionColumns number of column into action
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function show($checkBox, $userCanAskToEditLine, $userCanAskToDeleteLine, $userCanMoveLine, $numberOfActionColumns)
	{
		$colspan = 0; //used for extrafields
		$digitalSignatureRequestId = $checkBox->digitalSignatureRequest->id;
		global $conf;

		$numberOfColumnDisplayed = count(array_filter(array($userCanAskToEditLine, $userCanAskToDeleteLine, $userCanMoveLine)));
		$colSpanForFirstCell = 1;
		if ($numberOfActionColumns > $numberOfColumnDisplayed) {
			$colSpanForFirstCell += $numberOfActionColumns - $numberOfColumnDisplayed;
		}

		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_ROW . '-' . $checkBox->id . '" class="oddeven drag drop">';
		//We display number column
		if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
			$colspan++;
		}

		//We show Label field
		print '<td>';
		print $checkBox->label;
		print $this->formDigitalSignatureManager->getWarningInfoBox($userCanAskToEditLine, $checkBox->checkLabelValidity());
		print '</td>';


		// Show edit button
		if ($userCanAskToEditLine) {
			print '<td class="linecoledit" align="center">';
			print '<a href="' . $this->formDigitalSignatureManager->buildActionUrlForLine($digitalSignatureRequestId, self::EDIT_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME, $checkBox->id, self::ELEMENT_PREFIX_ROW) . '">';
			print img_edit();
			print '</td>';
			$colspan++;
		}

		//show delete button
		if ($userCanAskToDeleteLine) {
			print '<td class="linecoldelete" align="center">';
			print '<a href="' . $this->formDigitalSignatureManager->buildActionUrlForLine($digitalSignatureRequestId, self::DELETE_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME, $checkBox->id, self::ELEMENT_PREFIX_ROW) . '">';
			print img_delete();
			print '</td>';
			$colspan++;
		}

		//show move button
		if ($userCanMoveLine) {
			$this->formDigitalSignatureManager->showMoveActionButtonsForLine($digitalSignatureRequestId, $checkBox->id, $checkBox->position, count($checkBox->digitalSignatureRequest->availableCheckBox), self::MOVE_UP_ACTION_NAME, self::MOVE_DOWN_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME);
			$colspan++;
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
			$digitalSignatureCheckBox = $this->elementObjectStatic;
			global $langs;
			if ($digitalSignatureCheckBox->fetch($idToDelete) < 0 || $object->id != $digitalSignatureCheckBox->fk_digitalsignaturerequest) {
				setEventMessages($langs->trans('DigitalSignatureCHeckBoxNotFoundOrAlreadyDeleted'), $digitalSignatureCheckBox->errors, 'errors');
			} elseif ($digitalSignatureCheckBox->delete($user) > 0) {
				setEventMessages($langs->trans('DigitalSignatureManagerCheckBoxSuccessfullyDeleted'), $digitalSignatureCheckBox->errors);
			} else {
				setEventMessages($langs->trans('DigitalSignatureManagerCheckBoxErrorWhileDeleting'), $digitalSignatureCheckBox->errors, 'errors');
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
	public function manageAddAction($action, $digitalSignatureRequest, $user)
	{
		if ($action == self::ADD_ACTION_NAME) {
			global $langs;
			$payload = self::updateFromPost($this->elementObjectStatic);
			$payload->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
			$payload->position = $payload::getLastPosition($digitalSignatureRequest->availableCheckBox) + 1;
			if ($payload->create($user) > 0) {
				setEventMessages($langs->trans('DigitalSignatureRequestCheckBoxSuccesfullyAdded'), array());
				$action = null;
			} else {
				setEventMessages($langs->trans('DigitalSignatureRequestCheckBoxErrorWhenAdded'), $payload->errors, 'errors');
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
			$payload = $this->elementObjectStatic;
			if ($payload->fetch($this->getFormElementId()) < 0 || $object->id != $payload->fk_digitalsignaturerequest) {
				setEventMessages($langs->trans('DigitalSignatureCheckBoxNotFound'), $payload->errors, 'errors');
			} else {
				$payload = self::updateFromPost($payload);
				if ($payload->update($user) < 0) {
					setEventMessages($langs->trans('DigitalSignatureCheckBoxErrorWhileSaving'), $payload->errors, 'errors');
					$action = self::EDIT_ACTION_NAME;
				} else {
					setEventMessages($langs->trans('DigitalSignatureCheckBoxSuccessfullySaved'), array());
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
			$this->elementObjectStatic->fetch($this->getFormElementId());
			$checkBox = $this->elementObjectStatic;
			return $this->form->formconfirm($this->formDigitalSignatureManager->buildActionUrlForLine($object->id), $langs->trans('DigitalSignatureRequestCheckBoxConfirmDeleteTitle'), $langs->trans('DigitalSignatureRequestCheckBoxConfirmDeleteDescription', $checkBox->label), self::CONFIRM_DELETE_ACTION_NAME, $formquestion, 0, 1, 220);
		}
		return $formconfirm;
	}

	/**
	 * Function to update digital signature people from POST
	 * @param DigitalSignatureCheckBox $digitalSignatureCheckBox object instance to be updated with data from post
	 * @param bool $fillOnlyIfFieldIsPresentOnPost - fill field only field that are in POST
	 * @return DigitalSignatureCheckBox updated object
	 */
	public static function updateFromPost($digitalSignatureCheckBox, $fillOnlyIfFieldIsPresentOnPost = false)
	{
		$arrayOfPostParameterAndDigitalSignaturePropertyName = array(
			self::ELEMENT_POST_LABEL_FIELD_NAME => 'label'
		);
		foreach ($arrayOfPostParameterAndDigitalSignaturePropertyName as $postFieldName => $propertyName) {
			if (!$fillOnlyIfFieldIsPresentOnPost || isset($_POST[$postFieldName])) {
				$digitalSignatureCheckBox->$propertyName = GETPOST($postFieldName);
			}
		}
		return $digitalSignatureCheckBox;
	}

	/**
	 * get input form of digital signature people
	 * @param DigitalSignatureCheckBox $digitalSignatureCheckBox - object to be displayed
	 * @param bool $displayWarnings should warning be displayed displaying data validation result
	 * @param bool $displayInformation should we display helper about fields
	 * @return string html output to be printed
	 */
	public function getInputForm($digitalSignatureCheckBox, $displayWarnings = false, $displayInformation = true)
	{
		global $langs;
		//We show label field
		return $this->formDigitalSignatureManager->getInputFieldColumn(
			self::ELEMENT_POST_LABEL_FIELD_NAME,
			$digitalSignatureCheckBox->label,
			$displayInformation,
			$langs->trans('DigitalSignatureCheckBoxLabelInfoBox'),
			$displayWarnings,
			$digitalSignatureCheckBox->checkLabelValidity()
		);
	}
}
