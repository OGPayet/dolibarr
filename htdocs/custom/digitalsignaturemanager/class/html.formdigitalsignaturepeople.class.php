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
class FormDigitalSignaturePeople
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
	 * @var DigitalSignaturePeople Shared Instance of DigitalSignaturePeople
	 */
	public $elementObjectStatic;

	/**
	 * @var array
	 */
	public static $errors = array();

	/**
	 * @var string Delete Element Action Name
	 */
	const DELETE_ACTION_NAME = 'deletePeople';

	/**
	 * @var string confirm Delete Element Action
	 */
	const CONFIRM_DELETE_ACTION_NAME = 'confirmDeletePeople';

	/**
	 * @var string Edit Element Action Name
	 */
	const EDIT_ACTION_NAME = 'editPeople';

	/**
	 * @var string Edit Element Action Name
	 */
	const SAVE_ACTION_NAME = 'savePeople';

	/**
	 * @var string Add Element Action Name from User
	 */
	const ADD_ACTION_NAME_FROM_USER = 'addPeopleFromUser';

	/**
	 * @var string Add Element Action Name
	 */
	const ADD_ACTION_NAME_FROM_CONTACT = 'addPeopleFromContact';

	/**
	 * @var string Add Element Action Name
	 */
	const ADD_ACTION_NAME = 'addFreePeople';

	/**
	 * @var string move up Element Action Name
	 */
	const MOVE_UP_ACTION_NAME = 'moveUpPeople';

	/**
	 * @var string move up Element Action Name
	 */
	const MOVE_DOWN_ACTION_NAME = 'moveDownPeople';

	/**
	 * @var string name of the post field containing Element id
	 */
	const ELEMENT_POST_ID_FIELD_NAME = 'peopleId';

	/**
	 * @var string name of the post field containing Element id
	 */
	const ELEMENT_POST_FIRSTNAME_FIELD_NAME = 'peopleFirstName';

	/**
	 * @var string name of the post field containing Element id
	 */
	const ELEMENT_POST_LASTNAME_FIELD_NAME = 'peopleLastName';

	/**
	 * @var string name of the post field containing Element id
	 */
	const ELEMENT_POST_MAIL_FIELD_NAME = 'peopleMail';

	/**
	 * @var string name of the post field containing Element id
	 */
	const ELEMENT_POST_PHONE_FIELD_NAME = 'peoplePhone';

	/**
	 * @var string prefix id of existing document row
	 */
	const ELEMENT_PREFIX_ROW = "digitalsignaturepeople";

	/**
	 * @var string id of row for adding element
	 */
	const ELEMENT_PREFIX_NEW_ROW_FROM_CONTACT = "newpeoplesignaturefromcontact";

	/**
	 * @var string id of row for adding element
	 */
	const ELEMENT_PREFIX_NEW_ROW_FROM_USER = "newpeoplesignaturefromuser";

	/**
	 * @var string id of row for adding element
	 */
	const ELEMENT_PREFIX_NEW_FREE_ROW = "newpeoplesignature";

	/**
	 * @var string id of row for adding element
	 */
	const ELEMENT_SAVE_BUTTON_NAME = "savePeople";

	/**
	 * @var string post field name containing selected contact
	 */
	const ELEMENT_POST_FOREIGN_KEY_LINKED_CONTACT = "linkedObjectContactId";

	/**
	 * @var string post field name containing selected user
	 */
	const ELEMENT_POST_FOREIGN_KEY_LINKED_USER = "linkedObjectUserId";

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

		dol_include_once('/digitalsignaturemanager/class/digitalsignaturepeople.class.php');
		$this->elementObjectStatic = new DigitalSignaturePeople($this->db);
	}

	/**
	 * Contact Select Form to add signers
	 * @param DigitalSignatureRequest $object Object
	 * @param int $selectedContactId Current selected id
	 * @return string String containing html to be displayed
	 */
	public function getAvailableContactSelectForm($object, $selectedContactId)
	{
		$listOfContactIdAlreadyLinked = $this->elementObjectStatic->getIdOfContactIntoThesePeople($object->people);
		return $this->formDigitalSignatureManager->selectContact($object, $selectedContactId, self::ELEMENT_POST_FOREIGN_KEY_LINKED_CONTACT, $object->getLinkedThirdpartyId(), null, $listOfContactIdAlreadyLinked);
	}

	/**
	 * User Select Form to add signers
	 * @param DigitalSignatureRequest $object Object
	 * @param int $selectedUserId Current selected id
	 * @return string String containing html to be displayed
	 */
	public function getAvailableUserSelectForm($object, $selectedUserId)
	{
		$listOfUserIdAlreadyLinked = $this->elementObjectStatic->getIdOfUserIntoThesePeople($object->people);
		return $this->formDigitalSignatureManager->selectUser($object, $selectedUserId, self::ELEMENT_POST_FOREIGN_KEY_LINKED_USER, $listOfUserIdAlreadyLinked);
	}

	/**
	 *  Display form to add a new people into card lines from contact
	 *
	 *  @param	DigitalSignatureRequest	$object			Object
	 *  @param int $numberOfColumnOfContentTable number of column into parent table
	 *  @param int $numberOfActionColumnOfTheTable number of column into parent table
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function showFromContactAddForm($object, $numberOfColumnOfContentTable, $numberOfActionColumnOfTheTable)
	{
		global $hookmanager, $action;
		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_NEW_ROW_FROM_CONTACT . '" class="nodrag nodrop nohoverpair liste_titre_create oddeven">';
		print '<form action="' . $this->formDigitalSignatureManager->buildActionUrlForLine($object->id, null, null, null, self::ELEMENT_PREFIX_NEW_ROW_FROM_CONTACT) . '" enctype="multipart/form-data" method="post">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

		if($reshook == 0) {
			$colspan = 0; //used for extrafields
			global $conf, $langs;
			//We display number column
			if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
				print '<td class="linecolnum" align="center"></td>';
				$colspan++;
			}
			// We show contact select form
			print '<td colspan="' . $numberOfColumnOfContentTable . '">';
			print '<input type="hidden" name="action" value="' . self::ADD_ACTION_NAME_FROM_CONTACT . '">';
			global $action;
			$selectedContactId = $action == self::ADD_ACTION_NAME_FROM_CONTACT ? GETPOST(self::ELEMENT_POST_FOREIGN_KEY_LINKED_CONTACT) : null;
			print $this->getAvailableContactSelectForm($object, $selectedContactId);
			print '</td>';

			// Show add button
			$numberOfActionColumnOfTheTable = $numberOfActionColumnOfTheTable < 1 ? 1 : $numberOfActionColumnOfTheTable;
			print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="' . $numberOfActionColumnOfTheTable . '">';
			print '<input type="submit" class="button" value="' . $langs->trans('DigitalSignatureManagerAddPeopleFromContact') . '">';
			print '</td>';
		}

		//We end row
		print '</form>';
		print '</tr>';
	}

	/**
	 *  Display form to add a new element from Userinto card lines
	 *  @param	DigitalSignatureRequest	$object			Object
	 *  @param int $numberOfColumnOfContentTable number of column into parent table for content
	 *  @param int $numberOfActionColumnOfTheTable number of column into parent table for actions
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function showFromUserAddForm($object, $numberOfColumnOfContentTable, $numberOfActionColumnOfTheTable)
	{
		global $hookmanager, $action;
		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_NEW_ROW_FROM_USER . '" class="nodrag nodrop nohoverpair liste_titre_create oddeven">';
		print '<form action="' . $this->formDigitalSignatureManager->buildActionUrlForLine($object->id, null, null, null, self::ELEMENT_PREFIX_NEW_ROW_FROM_USER) . '" enctype="multipart/form-data" method="post">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

		if($reshook == 0) {
			$colspan = 0; //used for extrafields
			global $conf, $langs;
			//We display number column
			if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
				print '<td class="linecolnum" align="center"></td>';
				$colspan++;
			}
			// We show user select form
			print '<td colspan="' . $numberOfColumnOfContentTable . '">';
			print '<input type="hidden" name="action" value="' . self::ADD_ACTION_NAME_FROM_USER . '">';
			global $action;
			$selectedUserId = $action == self::ADD_ACTION_NAME_FROM_USER ? GETPOST(self::ELEMENT_POST_FOREIGN_KEY_LINKED_USER) : null;
			print $this->getAvailableUserSelectForm($object, $selectedUserId);
			print '</td>';

			// Show add button
			$numberOfActionColumnOfTheTable = $numberOfActionColumnOfTheTable < 1 ? 1 : $numberOfActionColumnOfTheTable;
			print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="' . $numberOfActionColumnOfTheTable . '">';
			print '<input type="submit" class="button" value="' . $langs->trans('DigitalSignatureManagerAddPeopleFromUser') . '">';
			print '</td>';
		}

		//We end row
		print '</form>';
		print '</tr>';
	}

	/**
	 *  Display form to add a new free element
	 *
	 *  @param	DigitalSignatureRequest	$object			Object
	 *  @param int $numberOfColumnOfContentTable number of column into parent table for content
	 *  @param int $numberOfActionColumnOfTheTable number of column into parent table for action button
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function showFreeAddForm($object, $numberOfColumnOfContentTable, $numberOfActionColumnOfTheTable)
	{
		global $hookmanager, $action;
		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_NEW_FREE_ROW . '" class="nodrag nodrop nohoverpair liste_titre_create oddeven">';
		print '<form action="' . $this->formDigitalSignatureManager->buildActionUrlForLine($object->id, null, null, null, self::ELEMENT_PREFIX_NEW_FREE_ROW) . '" enctype="multipart/form-data" method="post">';
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

			for ($i = 4; $i < $numberOfColumnOfContentTable; $i++) {
				print '<td class="content"></td>';
			}

			global $action;
			if ($action == self::ADD_ACTION_NAME) {
				$this->elementObjectStatic = self::updateFromPost($this->elementObjectStatic);
			}
			$digitalSignaturePeople = $this->elementObjectStatic;

			//we display input form
			print $this->getInputForm($digitalSignaturePeople);

			// Show add button
			$numberOfActionColumnOfTheTable = $numberOfActionColumnOfTheTable < 1 ? 1 : $numberOfActionColumnOfTheTable;
			print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="' . $numberOfActionColumnOfTheTable . '">';
			print '<input type="submit" class="button" value="' . $langs->trans('DigitalSignatureManagerAddFreePeople') . '">';
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
	 *  @param  DigitalSignatureSigner $digitalSignaturePeople People being edited
	 *  @param string $userCanMoveLine can user ask to move line ?
	 *  @param int $numberOfColumnOfContentTable number of column into parent table for content
	 *  @param int $numberOfActionColumnOfTheTable number of column into parent table for action button
	 *  @param bool $showPreviewColumn should we display Column for linked object getNomUrl
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function showEditForm($object, $digitalSignaturePeople, $userCanMoveLine, $numberOfColumnOfContentTable, $numberOfActionColumnOfTheTable, $showPreviewColumn)
	{
		global $hookmanager, $action;
		$parameters = array();
		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_ROW . '-' . $digitalSignaturePeople->id . '" class="oddeven drag drop">';

		print '<form action="' . $this->formDigitalSignatureManager->buildActionUrlForLine($object->id, null, null, $digitalSignaturePeople->id, self::ELEMENT_PREFIX_ROW) . '" enctype="multipart/form-data" method="post">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="action" value="' . self::SAVE_ACTION_NAME . '">';
		print '<input type="hidden" name="' . self::ELEMENT_POST_ID_FIELD_NAME . '" value="' . $digitalSignaturePeople->id . '">';

		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		global $conf, $langs;

		$digitalSignaturePeople = self::updateFromPost($digitalSignaturePeople, true);

		if($reshook == 0) {
			//We display number column
			if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
				print '<td class="linecolnum" align="center"></td>';
			}

			for ($i = 5; $i < $numberOfColumnOfContentTable; $i++) {
				print '<td class="content"></td>';
			}

			//We display linked object column
			if ($showPreviewColumn) {
				// We show linked object
				print '<td>';
				print self::showPeopleLinkedObject($digitalSignaturePeople);
				print '</td>';
			}

			//we display input form
			print $this->getInputForm($digitalSignaturePeople, true);

			if ($userCanMoveLine) {
				$numberOfActionColumnOfTheTable -= 1;
			}
			$numberOfActionColumnOfTheTable = $numberOfActionColumnOfTheTable < 1 ? 1 : $numberOfActionColumnOfTheTable;
			print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="' . $numberOfActionColumnOfTheTable . '">';
			print '<input type="submit" class="button" name="' . self::ELEMENT_SAVE_BUTTON_NAME . '" value="' . $langs->trans('Save') . '">';
			print '<input type="submit" class="button" name="cancel" value="' . $langs->trans('Cancel') . '">';
			print '</td>';

			//Show move button
			if ($userCanMoveLine) {
				$this->formDigitalSignatureManager->showMoveActionButtonsForLine($object->id, $digitalSignaturePeople->id, $digitalSignaturePeople->position, count($object->people), self::MOVE_UP_ACTION_NAME, self::MOVE_DOWN_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME);
			}
		}
		//We end row
		print '</form>';
		print '</tr>';
	}


	/**
	 * get input form of digital signature people
	 * @param DigitalSignaturePeople $digitalSignaturePeople - object to be displayed
	 * @param bool $displayWarnings should warning be displayed displaying data validation result
	 * @param bool $displayInformation should we display helper about fields
	 * @return string html output to be printed
	 */
	public function getInputForm($digitalSignaturePeople, $displayWarnings = false, $displayInformation = true)
	{
		global $langs;
		$out = '';
		// We show Last Name Field
		$out .= $this->formDigitalSignatureManager->getInputFieldColumn(
			self::ELEMENT_POST_LASTNAME_FIELD_NAME,
			$digitalSignaturePeople->lastName,
			$displayInformation,
			$langs->trans('DigitalSignatureManagerLastNameInfoBox'),
			$displayWarnings,
			$digitalSignaturePeople->checkLastNameValidity()
		);
		//We show first name field
		$out .= $this->formDigitalSignatureManager->getInputFieldColumn(
			self::ELEMENT_POST_FIRSTNAME_FIELD_NAME,
			$digitalSignaturePeople->firstName,
			$displayInformation,
			$langs->trans('DigitalSignatureManagerFirstNameInfoBox'),
			$displayWarnings,
			$digitalSignaturePeople->checkFirstNameValidity()
		);

		//We show mail field
		$out .= $this->formDigitalSignatureManager->getInputFieldColumn(
			self::ELEMENT_POST_MAIL_FIELD_NAME,
			$digitalSignaturePeople->mail,
			$displayInformation,
			$langs->trans('DigitalSignatureManagerMailInfoBox'),
			$displayWarnings,
			$digitalSignaturePeople->checkMailValidity()
		);

		//We show phone field
		$out .= $this->formDigitalSignatureManager->getInputFieldColumn(
			self::ELEMENT_POST_PHONE_FIELD_NAME,
			$digitalSignaturePeople->phoneNumber,
			$displayInformation,
			$langs->trans('DigitalSignatureManagerPhoneInfoBox'),
			$displayWarnings,
			$digitalSignaturePeople->checkPhoneNumberValidity()
		);
		return $out;
	}


	/**
	 *  Display form to display digital signature people into card lines
	 *
	 *  @param  DigitalSignaturePeople $digitalSignaturePeople being edited
	 *  @param bool $userCanAskToEditLine display edit button
	 *  @param bool $userCanAskToDeleteLine display delete button
	 *  @param bool $userCanMoveLine display move button
	 *  @param int $numberOfActionColumnOfTheTable number of columns for action on the parent table
	 *  @param bool $showPreviewColumn display linked object getNomUrl
	 * 	@param bool $showStatus should we display item status
	 *	@return	int						<0 if KO, >=0 if OK
	 */
	public function showPeople($digitalSignaturePeople, $userCanAskToEditLine, $userCanAskToDeleteLine, $userCanMoveLine, $numberOfActionColumnOfTheTable, $showPreviewColumn, $showStatus)
	{
		$digitalSignatureRequestId = $digitalSignaturePeople->digitalSignatureRequest->id;
		global $conf;

		//We display row
		print '<tr id="' . self::ELEMENT_PREFIX_ROW . '-' . $digitalSignaturePeople->id . '" class="oddeven drag drop">';
		//We display number column
		if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
		}

		if ($showPreviewColumn) {
			// We show linked object
			print '<td>';
			print self::showPeopleLinkedObject($digitalSignaturePeople);
			print '</td>';
		}

		// We show Last Name Field
		print '<td>';
		print $digitalSignaturePeople->lastName;
		print $this->formDigitalSignatureManager->getWarningInfoBox($userCanAskToEditLine, $digitalSignaturePeople->checkLastNameValidity());
		print '</td>';

		//We show first name field
		print '<td>';
		print $digitalSignaturePeople->firstName;
		print $this->formDigitalSignatureManager->getWarningInfoBox($userCanAskToEditLine, $digitalSignaturePeople->checkFirstNameValidity());
		print '</td>';

		//We show mail field
		print '<td>';
		print $digitalSignaturePeople->mail;
		print $this->formDigitalSignatureManager->getWarningInfoBox($userCanAskToEditLine, $digitalSignaturePeople->checkMailValidity());
		print '</td>';

		//We show phone field
		print '<td>';
		print $digitalSignaturePeople->phoneNumber;
		print $this->formDigitalSignatureManager->getWarningInfoBox($userCanAskToEditLine, $digitalSignaturePeople->checkPhoneNumberValidity());
		print '</td>';

		//We show status if needed
		if($showStatus) {
			print '<td>';
			print $digitalSignaturePeople->getLibStatut(6);
			print '</td>';
		}

		$nbOfActionColumn = count(array_filter(array($userCanAskToEditLine, $userCanAskToDeleteLine, $userCanMoveLine)));

		for ($i = $nbOfActionColumn; $i < $numberOfActionColumnOfTheTable; $i++) {
			print '<td class="linecoledit" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		// Show edit button
		if ($userCanAskToEditLine) {
			print '<td class="linecoledit" align="center">';
			print '<a href="' . $this->formDigitalSignatureManager->buildActionUrlForLine($digitalSignatureRequestId, self::EDIT_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME, $digitalSignaturePeople->id, self::ELEMENT_PREFIX_ROW) . '">';
			print img_edit();
			print '</td>';
		}
		//Show delete button
		if ($userCanAskToDeleteLine) {
			print '<td class="linecoldelete" align="center">';
			print '<a href="' . $this->formDigitalSignatureManager->buildActionUrlForLine($digitalSignatureRequestId, self::DELETE_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME, $digitalSignaturePeople->id, self::ELEMENT_PREFIX_ROW) . '">';
			print img_delete();
			print '</td>';
		}
		//Show move button
		if ($userCanMoveLine) {
			$this->formDigitalSignatureManager->showMoveActionButtonsForLine($digitalSignatureRequestId, $digitalSignaturePeople->id, $digitalSignaturePeople->position, count($digitalSignaturePeople->digitalSignatureRequest->people), self::MOVE_UP_ACTION_NAME, self::MOVE_DOWN_ACTION_NAME, self::ELEMENT_POST_ID_FIELD_NAME);
		}

		//We end row
		print '</tr>';
	}

	/**
	 * Function to display document file by its filename with link to download it and ability to preview it
	 * @param DigitalSignaturePeople $digitalSignaturePeople Given digital signature comment to be previewed
	 * @return string
	 */
	public static function showPeopleLinkedObject($digitalSignaturePeople)
	{
		$out = "";
		global $langs;
		$linkedSourceObject = $digitalSignaturePeople->getLinkedSourceObject();
		if ($linkedSourceObject) {
			$out = $linkedSourceObject->getNomUrl(1);
		} elseif (!$digitalSignaturePeople->fk_people_object) {
			$out = $langs->trans('DigitalSignatureManagerNoSourceObject');
		} else {
			$out = $langs->trans("DigitalSignatureManagerSourceObjectNotFound");
		}
		return $out;
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
			$digitalSignaturePeople = $this->elementObjectStatic;
			global $langs;
			if ($digitalSignaturePeople->fetch($idToDelete) < 0 || $object->id != $digitalSignaturePeople->fk_digitalsignaturerequest) {
				setEventMessages($langs->trans('DigitalSignaturePeopleNotFoundOrAlreadyDeleted'), $digitalSignaturePeople->errors, 'errors');
			} elseif ($digitalSignaturePeople->delete($user) > 0) {
				setEventMessages($langs->trans('DigitalSignatureManagerPeopleSuccessfullyDeleted', $digitalSignaturePeople->displayName()), $digitalSignaturePeople->errors);
			} else {
				setEventMessages($langs->trans('DigitalSignatureManagerPeopleErrorWhileDeletingPeople', $digitalSignaturePeople->displayName()), $digitalSignaturePeople->errors, 'errors');
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
			$payload->position = $payload::getLastPosition($digitalSignatureRequest->people) + 1;
			if ($payload->create($user) > 0) {
				setEventMessages($langs->trans('DigitalSignatureRequestPeopleSuccesfullyAdded'), array());
				$action = null;
			} else {
				setEventMessages($langs->trans('DigitalSignatureRequestPeopleErrorWhenAdded'), $payload->errors, 'errors');
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
	public function manageAddFromUserAction(&$action, $digitalSignatureRequest, $user)
	{
		if ($action == self::ADD_ACTION_NAME_FROM_USER) {
			global $langs;
			$payload = $this->elementObjectStatic;
			$payload->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
			$linkedUserIdToBeAddedToPeople = GETPOST(self::ELEMENT_POST_FOREIGN_KEY_LINKED_USER);
			$listOfUserIdAlreadyLinked = $this->elementObjectStatic->getIdOfUserIntoThesePeople($digitalSignatureRequest->people);
			$payload->position = $payload::getLastPosition($digitalSignatureRequest->people) + 1;
			if (empty($linkedUserIdToBeAddedToPeople)) {
				setEventMessages($langs->trans('DigitalSignatureErrorNoUserSelected'), $payload->errors, 'errors');
			} elseif (in_array($linkedUserIdToBeAddedToPeople, $listOfUserIdAlreadyLinked)) {
				setEventMessages($langs->trans('DigitalSignatureErrorUserAlreadyAdded'), $payload->errors, 'errors');
			} elseif ($payload->fillDataFromUserId(GETPOST(self::ELEMENT_POST_FOREIGN_KEY_LINKED_USER), true) < 0) {
				setEventMessages($langs->trans('DigitalSignatureErrorWhenFillingDataFromUser'), $payload->errors, 'errors');
			} elseif ($payload->create($user) > 0) {
				setEventMessages($langs->trans('DigitalSignatureRequestPeopleSuccesfullyAddedFromUser', $payload->displayName()), array());
				$action = null;
			} else {
				setEventMessages($langs->trans('DigitalSignatureRequestPeopleErrorWhenAddedFromUser'), $payload->errors, 'errors');
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
	public function manageAddFromContactAction(&$action, $digitalSignatureRequest, $user)
	{
		if ($action == self::ADD_ACTION_NAME_FROM_CONTACT) {
			global $langs;
			$payload = $this->elementObjectStatic;
			$payload->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
			$linkedContactIdToBeAddedToPeople = GETPOST(self::ELEMENT_POST_FOREIGN_KEY_LINKED_CONTACT);
			$listOfContactAlreadyLinked = $this->elementObjectStatic->getIdOfContactIntoThesePeople($digitalSignatureRequest->people);
			$payload->position = $payload::getLastPosition($digitalSignatureRequest->people) + 1;
			if (empty($linkedContactIdToBeAddedToPeople)) {
				setEventMessages($langs->trans('DigitalSignatureErrorNoContactSelected'), $payload->errors, 'errors');
			} elseif (in_array($linkedContactIdToBeAddedToPeople, $listOfContactAlreadyLinked)) {
				setEventMessages($langs->trans('DigitalSignatureErrorContactAlreadyAdded'), $payload->errors, 'errors');
			} elseif ($payload->fillDataFromContactId($linkedContactIdToBeAddedToPeople, true) < 0) {
				setEventMessages($langs->trans('DigitalSignatureErrorWhenFillingDataFromContact'), $payload->errors, 'errors');
			} elseif ($payload->create($user) > 0) {
				setEventMessages($langs->trans('DigitalSignatureRequestPeopleSuccesfullyAddedFromContact', $payload->displayName()), array());
				$action = null;
			} else {
				setEventMessages($langs->trans('DigitalSignatureRequestPeopleErrorWhenAddedFromContact'), $payload->errors, 'errors');
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
				setEventMessages($langs->trans('DigitalSignaturePeopleNotFound'), $payload->errors, 'errors');
			} else {
				$payload = self::updateFromPost($payload);
				if ($payload->update($user) < 0) {
					setEventMessages($langs->trans('DigitalSignaturePeopleErrorWhileSaving', $payload->displayName()), $payload->errors, 'errors');
					$action = self::EDIT_ACTION_NAME;
				} else {
					setEventMessages($langs->trans('DigitalSignaturePeopleSuccessfullySaved', $payload->displayName()), array());
				}
			}
		}
	}

	/**
	 * Function to get current digital signature people id edited on page using showDocument
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
			return $this->form->formconfirm($this->formDigitalSignatureManager->buildActionUrlForLine($object->id), $langs->trans('DigitalSignatureManagerPeopleConfirmDeleteTitle'), $langs->trans('DigitalSignatureManagerPeopleConfirmDeleteDescription', $documentStatic->displayName()), self::CONFIRM_DELETE_ACTION_NAME, $formquestion, 0, 1, 220);
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
			self::ELEMENT_POST_FIRSTNAME_FIELD_NAME => 'firstName',
			self::ELEMENT_POST_LASTNAME_FIELD_NAME => 'lastName',
			self::ELEMENT_POST_MAIL_FIELD_NAME => 'mail',
			self::ELEMENT_POST_PHONE_FIELD_NAME => 'phoneNumber'
		);
		foreach ($arrayOfPostParameterAndDigitalSignaturePropertyName as $postFieldName => $propertyName) {
			if (!$fillOnlyIfFieldIsPresentOnPost || isset($_POST[$postFieldName])) {
				$digitalSignaturePeople->$propertyName = GETPOST($postFieldName);
			}
		}
		return $digitalSignaturePeople;
	}
}
