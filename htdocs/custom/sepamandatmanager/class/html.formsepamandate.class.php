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
class FormSepaMandate
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
	 * @var string confirm set to sign status action name
	 */
	const CONFIRM_TO_SIGN_ACTION_NAME = 'confirm_setToSign';

	/**
	 * @var string set to sign status action name
	 */
	const TO_SIGN_ACTION_NAME = 'setToSign';

	/**
	 * @var string confirm set to sign status action name
	 */
	const CONFIRM_SIGNED_ACTION_NAME = 'confirm_setSigned';

	/**
	 * @var string set to sign status action name
	 */
	const SIGNED_ACTION_NAME = 'setSigned';

	/**
	 * @var string confirm set to sign status action name
	 */
	const SET_STALED_ACTION_NAME = 'setStaled';

	/**
	 * @var string set to sign status action name
	 */
	const CONFIRM_SET_STALED_ACTION_NAME = 'confirm_setStaled';

	/**
	 * @var string confirm set to sign status action name
	 */
	const SET_CANCELED_ACTION_NAME = 'setCanceled';

	/**
	 * @var string set to sign status action name
	 */
	const CONFIRM_SET_CANCELED_ACTION_NAME = 'confirm_setCanceled';

	/**
	 * @var string confirm set to sign status action name
	 */
	const SET_BACK_TO_DRAFT_ACTION_NAME = 'setBackToDraft';

	/**
	 * @var string set to sign status action name
	 */
	const CONFIRM_SET_BACK_TO_DRAFT_ACTION_NAME = 'confirm_setBackToDraft';

	/**
	 * @var string confirm set to sign status action name
	 */
	const SET_BACK_TO_TO_SIGN_ACTION_NAME = 'setBackToToSign';

	/**
	 * @var string set to sign status action name
	 */
	const CONFIRM_SET_BACK_TO_TO_SIGN_ACTION_NAME = 'confirm_setBackToToSign';

	/**
	 * @var string confirm set to signed status action name
	 */
	const SET_BACK_TO_SIGNED_ACTION_NAME = 'setBackToSigned';

	/**
	 * @var string set to sign status action name
	 */
	const CONFIRM_SET_BACK_TO_SIGNED_ACTION_NAME = 'confirm_setBackToSigned';

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
	}

	/**
	 * Get form confirm
	 * @param string $formconfirm current form confirm
	 * @param string $currentAction current form action name
	 * @param string $id current object id
	 * @param string $actionName action name showing formconfirm
	 * @param string $confirmActionName action name to confirm action
	 * @param string $title title of the confirmation box
	 * @param string $description description of the content box
	 * @param boolean $userPermission is user allowed to perform this action
	 * @param boolean $objectCheck Permission regarding object values
	 * @return string
	 */
	public function getFormConfirmAccordingToSettings($formconfirm, $currentAction, $id, $actionName, $confirmActionName, $title, $description, $userPermission, $objectCheck)
	{
		$out = $formconfirm ? $formconfirm : '';
		if ($currentAction == $actionName) {
			global $langs;
			if (!$userPermission) {
				setEventMessages($langs->trans("SepaMandateNotAllowed"), array(), 'errors');
			} elseif (!$objectCheck) {
				setEventMessages($langs->trans('SepaMandateNotAllowedAccordingToData'), array(), 'errors');
			} else {
				$out = $this->form->formconfirm(
					$_SERVER["PHP_SELF"] . '?id=' . $id,
					$title,
					$description,
					$confirmActionName,
					array(),
					'yes',
					1
				);
			}
		}
		return $out;
	}

	/**
	 * Function to get validate form confirm
	 * @param string $formconfirm current form confirm
	 * @param string $action current form action
	 * @param SepaMandat $object object
	 * @param bool $userPermission is user allowed to perfom action
	 * @return string HTML content to display
	 */
	public function getValidateFormConfirm($formconfirm, $action, $object, $userPermission)
	{
		global $langs;
		$nextRef = $object->ref;
		if (preg_match('/^[\(]?PROV/i', $object->ref) || empty($object->ref)) // empty should not happened, but when it occurs, the test save life
		{
			$nextRef = $object->getNextNumRef();
		}
		return $this->getFormConfirmAccordingToSettings(
			$formconfirm,
			$action,
			$object->id,
			self::TO_SIGN_ACTION_NAME,
			self::CONFIRM_TO_SIGN_ACTION_NAME,
			$langs->trans("SepaMandateSetToSignTitle"),
			$langs->trans('SepaMandateSetToSignDescription', $nextRef),
			$userPermission,
			$object->status == $object::STATUS_DRAFT
		);
	}

	/**
	 * Function to get signed form confirm
	 * @param string $formconfirm current form confirm
	 * @param string $action current form action
	 * @param SepaMandat $object object
	 * @param bool $userPermission is user allowed to perfom action
	 * @return string HTML content to display
	 */
	public function getSignedFormConfirm($formconfirm, $action, $object, $userPermission)
	{
		global $langs;
		return $this->getFormConfirmAccordingToSettings(
			$formconfirm,
			$action,
			$object->id,
			self::SIGNED_ACTION_NAME,
			self::CONFIRM_SIGNED_ACTION_NAME,
			$langs->trans("SepaMandateSetSignedTitle"),
			$langs->trans('SepaMandateSetSignedDescription'),
			$userPermission,
			$object->status == $object::STATUS_TOSIGN
		);
	}

	/**
	 * Function to get staled form confirm
	 * @param string $formconfirm current form confirm
	 * @param string $action current form action
	 * @param SepaMandat $object object
	 * @param bool $userPermission is user allowed to perfom action
	 * @return string HTML content to display
	 */
	public function getStaledFormConfirm($formconfirm, $action, $object, $userPermission)
	{
		global $langs;
		return $this->getFormConfirmAccordingToSettings(
			$formconfirm,
			$action,
			$object->id,
			self::SET_STALED_ACTION_NAME,
			self::CONFIRM_SET_STALED_ACTION_NAME,
			$langs->trans("SepaMandateSetStaledTitle"),
			$langs->trans('SepaMandateSetStaledDescription'),
			$userPermission,
			$object->status == $object::STATUS_SIGNED
		);
	}

	/**
	 * Function to get canceled form confirm
	 * @param string $formconfirm current form confirm
	 * @param string $action current form action
	 * @param SepaMandat $object object
	 * @param bool $userPermission is user allowed to perfom action
	 * @return string HTML content to display
	 */
	public function getCanceledFormConfirm($formconfirm, $action, $object, $userPermission)
	{
		global $langs;
		return $this->getFormConfirmAccordingToSettings(
			$formconfirm,
			$action,
			$object->id,
			self::SET_CANCELED_ACTION_NAME,
			self::CONFIRM_SET_CANCELED_ACTION_NAME,
			$langs->trans("SepaMandateSetCanceledTitle"),
			$langs->trans('SepaMandateSetCanceledDescription'),
			$userPermission,
			$object->status == $object::STATUS_SIGNED || $object->status == $object::STATUS_TOSIGN
		);
	}

	/**
	 * Function to get back to draft form confirm
	 * @param string $formconfirm current form confirm
	 * @param string $action current form action
	 * @param SepaMandat $object object
	 * @param bool $userPermission is user allowed to perfom action
	 * @return string HTML content to display
	 */
	public function getBackToDraftFormConfirm($formconfirm, $action, $object, $userPermission)
	{
		global $langs;
		return $this->getFormConfirmAccordingToSettings(
			$formconfirm,
			$action,
			$object->id,
			self::SET_BACK_TO_DRAFT_ACTION_NAME,
			self::CONFIRM_SET_BACK_TO_DRAFT_ACTION_NAME,
			$langs->trans("SepaMandateSetBackToDraftTitle"),
			$langs->trans('SepaMandateSetBackToDraftDescription'),
			$userPermission,
			$object->status == $object::STATUS_TOSIGN
		);
	}

	/**
	 * Function to get back to to sign form confirm (from signed or canceled status)
	 * @param string $formconfirm current form confirm
	 * @param string $action current form action
	 * @param SepaMandat $object object
	 * @param bool $userPermission is user allowed to perfom action
	 * @return string HTML content to display
	 */
	public function getBackToToSignFormConfirm($formconfirm, $action, $object, $userPermission)
	{
		global $langs;
		return $this->getFormConfirmAccordingToSettings(
			$formconfirm,
			$action,
			$object->id,
			self::SET_BACK_TO_TO_SIGN_ACTION_NAME,
			self::CONFIRM_SET_BACK_TO_TO_SIGN_ACTION_NAME,
			$langs->trans("SepaMandateSetBackToToSignTitle"),
			$langs->trans('SepaMandateSetBackToToSignDescription'),
			$userPermission,
			$object->status == $object::STATUS_SIGNED || $object->status == $object::STATUS_CANCELED
		);
	}

	/**
	 * Function to get back to to signed form confirm (from staled or canceled status)
	 * @param string $formconfirm current form confirm
	 * @param string $action current form action
	 * @param SepaMandat $object object
	 * @param bool $userPermission is user allowed to perfom action
	 * @return string HTML content to display
	 */
	public function getBackToSignedFormConfirm($formconfirm, $action, $object, $userPermission)
	{
		global $langs;
		return $this->getFormConfirmAccordingToSettings(
			$formconfirm,
			$action,
			$object->id,
			self::SET_BACK_TO_SIGNED_ACTION_NAME,
			self::CONFIRM_SET_BACK_TO_SIGNED_ACTION_NAME,
			$langs->trans("SepaMandateSetBackToSignedTitle"),
			$langs->trans('SepaMandateSetBackToSignedDescription'),
			$userPermission,
			$object->status == $object::STATUS_SIGNED || $object->status == $object::STATUS_STALE
		);
	}

	/**
	 * Function to manage confirm validate action
	 * @param string $action request action
	 * @param SepaMandat $object object on which do action
	 * @param string $userPermission Permission of the user
	 * @param User $user User requesting
	 * @return void
	 */
	public function manageValidateAction($action, $object, $userPermission, $user)
	{
		if ($action == self::CONFIRM_TO_SIGN_ACTION_NAME) {
			global $langs;
			if (!$userPermission) {
				setEventMessages($langs->trans("SepaMandateNotAllowed"), array(), 'errors');
			} elseif ($object->status != $object::STATUS_DRAFT) {
				setEventMessages($langs->trans('SepaMandateNotAllowedAccordingToData'), array(), 'errors');
			} elseif ($object->setToSign($user) <= 0) {
				setEventMessages($langs->trans("SepaMandateErrorWhileValidatingMandat"), $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans('SepaMandateSuccessfullyValidated'), array());
			}
		}
	}

	/**
	 * Function to manage confirm signed action
	 * @param string $action request action
	 * @param SepaMandat $object object on which do action
	 * @param string $userPermission Permission of the user
	 * @param User $user User requesting
	 * @return void
	 */
	public function manageSignedAction($action, $object, $userPermission, $user)
	{
		if ($action == self::CONFIRM_SIGNED_ACTION_NAME) {
			global $langs;
			if (!$userPermission) {
				setEventMessages($langs->trans("SepaMandateNotAllowed"), array(), 'errors');
			} elseif ($object->status != $object::STATUS_TOSIGN) {
				setEventMessages($langs->trans('SepaMandateNotAllowedAccordingToData'), array(), 'errors');
			} elseif ($object->setSigned($user) <= 0) {
				setEventMessages($langs->trans("SepaMandateErrorWhileSettingSignedMandat"), $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans('SepaMandateSuccessfullySetSigned'), array());
			}
		}
	}

	/**
	 * Function to manage confirm staled action
	 * @param string $action request action
	 * @param SepaMandat $object object on which do action
	 * @param string $userPermission Permission of the user
	 * @param User $user User requesting
	 * @return void
	 */
	public function manageStaledAction($action, $object, $userPermission, $user)
	{
		if ($action == self::CONFIRM_SET_STALED_ACTION_NAME) {
			global $langs;
			if (!$userPermission) {
				setEventMessages($langs->trans("SepaMandateNotAllowed"), array(), 'errors');
			} elseif ($object->status != $object::STATUS_SIGNED) {
				setEventMessages($langs->trans('SepaMandateNotAllowedAccordingToData'), array(), 'errors');
			} elseif ($object->setStale($user) <= 0) {
				setEventMessages($langs->trans("SepaMandateErrorWhileSettingToStaleMandat"), $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans('SepaMandateSuccessfullySetToStale'), array());
			}
		}
	}


	/**
	 * Function to manage confirm canceled action
	 * @param string $action request action
	 * @param SepaMandat $object object on which do action
	 * @param string $userPermission Permission of the user
	 * @param User $user User requesting
	 * @return void
	 */
	public function manageCanceledAction($action, $object, $userPermission, $user)
	{
		if ($action == self::CONFIRM_SET_CANCELED_ACTION_NAME) {
			global $langs;
			if (!$userPermission) {
				setEventMessages($langs->trans("SepaMandateNotAllowed"), array(), 'errors');
			} elseif ($object->status != $object::STATUS_SIGNED && $object->status != $object::STATUS_TOSIGN) {
				setEventMessages($langs->trans('SepaMandateNotAllowedAccordingToData'), array(), 'errors');
			} elseif ($object->setCanceled($user) <= 0) {
				setEventMessages($langs->trans("SepaMandateErrorWhileCancellingMandat"), $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans('SepaMandateSuccessfullySetToCancel'), array());
			}
		}
	}


	/**
	 * Function to manage confirm back to draft
	 * @param string $action request action
	 * @param SepaMandat $object object on which do action
	 * @param string $userPermission Permission of the user
	 * @param User $user User requesting
	 * @return void
	 */
	public function manageBackToDraftAction($action, $object, $userPermission, $user)
	{
		if ($action == self::CONFIRM_SET_BACK_TO_DRAFT_ACTION_NAME) {
			global $langs;
			if (!$userPermission) {
				setEventMessages($langs->trans("SepaMandateNotAllowed"), array(), 'errors');
			} elseif ($object->status != $object::STATUS_TOSIGN) {
				setEventMessages($langs->trans('SepaMandateNotAllowedAccordingToData'), array(), 'errors');
			} elseif ($object->setBackToDraft($user) <= 0) {
				setEventMessages($langs->trans("SepaMandateErrorWhileSettingBackToDraftMandat"), $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans('SepaMandateSuccessfullySetBackToDraft'), array());
			}
		}
	}

	/**
	 * Function to manage confirm back to draft
	 * @param string $action request action
	 * @param SepaMandat $object object on which do action
	 * @param string $userPermission Permission of the user
	 * @param User $user User requesting
	 * @return void
	 */
	public function manageBackToToSignAction($action, $object, $userPermission, $user)
	{
		if ($action == self::CONFIRM_SET_BACK_TO_TO_SIGN_ACTION_NAME) {
			global $langs;
			if (!$userPermission) {
				setEventMessages($langs->trans("SepaMandateNotAllowed"), array(), 'errors');
			} elseif ($object->status != $object::STATUS_SIGNED && $object->status != $object::STATUS_CANCELED) {
				setEventMessages($langs->trans('SepaMandateNotAllowedAccordingToData'), array(), 'errors');
			} elseif ($object->setBackToToSign($user) <= 0) {
				setEventMessages($langs->trans("SepaMandateErrorWhileSettingBackToToSignMandat"), $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans('SepaMandateSuccessfullySetBackToToSign'), array());
			}
		}
	}
		/**
	 * Function to manage confirm back to draft
	 * @param string $action request action
	 * @param SepaMandat $object object on which do action
	 * @param string $userPermission Permission of the user
	 * @param User $user User requesting
	 * @return void
	 */
	public function manageBackToSignedAction($action, $object, $userPermission, $user)
	{
		if ($action == self::CONFIRM_SET_BACK_TO_SIGNED_ACTION_NAME) {
			global $langs;
			if (!$userPermission) {
				setEventMessages($langs->trans("SepaMandateNotAllowed"), array(), 'errors');
			} elseif ($object->status != $object::STATUS_SIGNED && $object->status != $object::STATUS_STALE) {
				setEventMessages($langs->trans('SepaMandateNotAllowedAccordingToData'), array(), 'errors');
			} elseif ($object->setBackToSigned($user) <= 0) {
				setEventMessages($langs->trans("SepaMandateErrorWhileSettingBackToSignedMandat"), $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans('SepaMandateSuccessfullySetBackToSigned'), array());
			}
		}
	}
}
