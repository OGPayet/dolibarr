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
				$out = $this->formconfirm(
					$_SERVER["PHP_SELF"] . '?id=' . $id,
					$title,
					$description,
					$confirmActionName,
					array(),
					'yes',
					1,
					200,
					500,
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

	/**
	 *     Show a confirmation HTML form or AJAX popup.
	 *     Easiest way to use this is with useajax=1.
	 *     If you use useajax='xxx', you must also add jquery code to trigger opening of box (with correct parameters)
	 *     just after calling this method. For example:
	 *       print '<script type="text/javascript">'."\n";
	 *       print 'jQuery(document).ready(function() {'."\n";
	 *       print 'jQuery(".xxxlink").click(function(e) { jQuery("#aparamid").val(jQuery(this).attr("rel")); jQuery("#dialog-confirm-xxx").dialog("open"); return false; });'."\n";
	 *       print '});'."\n";
	 *       print '</script>'."\n";
	 *
	 *     @param  	string		$page        	   	Url of page to call if confirmation is OK
	 *     @param	string		$title       	   	Title
	 *     @param	string		$question    	   	Question
	 *     @param 	string		$action      	   	Action
	 *	   @param  	array		$formquestion	   	An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
	 * 	   @param  	string		$selectedchoice  	"" or "no" or "yes"
	 * 	   @param  	int			$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
	 *     @param  	int			$height          	Force height of box
	 *     @param	int			$width				Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
	 *     @param	int			$post				Send by form POST.
	 *     @param	int			$resizable			Resizable box (0=no, 1=yes).
	 *     @return 	string      	    			HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
	 */
	public function formconfirm($page, $title, $question, $action, $formquestion = array(), $selectedchoice = "", $useajax = 0, $height = 200, $width = 500, $post = 0, $resizable = 0)
	{
		global $langs, $conf, $form;
		global $useglobalvars;

		if (!is_object($form)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
			$form = new Form($this->db);
		}

		$more = '';
		$formconfirm = '';
		$inputok = array();
		$inputko = array();

		// Clean parameters
		$newselectedchoice = empty($selectedchoice) ? "no" : $selectedchoice;
		if ($conf->browser->layout == 'phone') $width = '95%';

		if ($post) {
			$more .= '<form id="form_dialog_confirm" name="form_dialog_confirm" action="' . $page . '" method="POST" enctype="multipart/form-data">';
			$more .= '<input type="hidden" id="confirm" name="confirm" value="yes">' . "\n";
			$more .= '<input type="hidden" id="action" name="action" value="' . $action . '">' . "\n";
		}
		// First add hidden fields and value
		foreach ($formquestion as $key => $input) {
			if (is_array($input) && !empty($input)) {
				if ($post && ($input['name'] == "confirm" || $input['name'] == "action")) continue;
				if ($input['type'] == 'hidden') {
					$more .= '<input type="hidden" id="' . $input['name'] . '" name="' . $input['name'] . '" value="' . dol_escape_htmltag($input['value'], 1, 1) . '">' . "\n";
				}
			}
		}

		// Now add questions
		$more .= '<table class="paddingtopbottomonly" width="100%">' . "\n";
		$more .= '<tr><td colspan="3">' . (!empty($formquestion['text']) ? $formquestion['text'] : '') . '</td></tr>' . "\n";
		foreach ($formquestion as $key => $input) {
			if (is_array($input) && !empty($input)) {
				$size = (!empty($input['size']) ? ' size="' . $input['size'] . '"' : '');

				if ($input['type'] == 'text') {
					$more .= '<tr class="oddeven"><td class="titlefield">' . $input['label'] . '</td><td colspan="2" align="left"><input type="text" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '"' . $size . ' value="' . $input['value'] . '" /></td></tr>' . "\n";
				} else if ($input['type'] == 'password') {
					$more .= '<tr class="oddeven"><td class="titlefield">' . $input['label'] . '</td><td colspan="2" align="left"><input type="password" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '"' . $size . ' value="' . $input['value'] . '" /></td></tr>' . "\n";
				} else if ($input['type'] == 'select') {
					$more .= '<tr class="oddeven"><td class="titlefield">';
					if (!empty($input['label'])) $more .= $input['label'] . '</td><td valign="top" colspan="2" align="left">';
					$more .= $form->selectarray($input['name'], $input['values'], $input['default'], 1);
					$more .= '</td></tr>' . "\n";
				} else if ($input['type'] == 'checkbox') {
					$more .= '<tr class="oddeven">';
					$more .= '<td class="titlefield">' . $input['label'] . ' </td><td align="right">';
					$more .= '<input type="checkbox" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '"';
					if (!is_bool($input['value']) && $input['value'] != 'false') $more .= ' checked';
					if (is_bool($input['value']) && $input['value']) $more .= ' checked';
					if (isset($input['disabled'])) $more .= ' disabled';
					$more .= ' /></td>';
					$more .= '<td align="left">&nbsp;</td>';
					$more .= '</tr>' . "\n";
				} else if ($input['type'] == 'radio') {
					$i = 0;
					foreach ($input['values'] as $selkey => $selval) {
						$more .= '<tr class="oddeven">';
						if ($i == 0) $more .= '<td class="tdtop titlefield">' . $input['label'] . '</td>';
						else $more .= '<td>&nbsp;</td>';
						$more .= '<td width="20"><input type="radio" class="flat" id="' . $input['name'] . '" name="' . $input['name'] . '" value="' . $selkey . '"';
						if ($input['disabled']) $more .= ' disabled';
						$more .= ' /></td>';
						$more .= '<td align="left">';
						$more .= $selval;
						$more .= '</td></tr>' . "\n";
						$i++;
					}
				} else if ($input['type'] == 'date') {
					$more .= '<tr class="oddeven"><td class="titlefield">' . $input['label'] . '</td>';
					$more .= '<td colspan="2" align="left">';
					$more .= $form->select_date($input['value'], $input['name'], $input['hour'] ? 1 : 0, $input['minute'] ? 1 : 0, 0, '', 1, $input['addnowlink'] ? 1 : 0, 1);
					$more .= '</td></tr>' . "\n";
					$formquestion[] = array('name' => $input['name'] . 'day');
					$formquestion[] = array('name' => $input['name'] . 'month');
					$formquestion[] = array('name' => $input['name'] . 'year');
					$formquestion[] = array('name' => $input['name'] . 'hour');
					$formquestion[] = array('name' => $input['name'] . 'min');
				} else if ($input['type'] == 'other') {
					$more .= '<tr class="oddeven"><td class="titlefield">';
					if (!empty($input['label'])) $more .= $input['label'] . '</td><td colspan="2" align="left">';
					$more .= $input['value'];
					$more .= '</td></tr>' . "\n";
				} else if ($input['type'] == 'onecolumn') {
					$more .= '<tr class="oddeven"><td class="titlefield" colspan="3" align="left">';
					$more .= $input['value'];
					$more .= '</td></tr>' . "\n";
				}
			}
		}
		$more .= '</table>' . "\n";
		if ($post) $more .= '</form>';

		// JQUI method dialog is broken with jmobile, we use standard HTML.
		// Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
		// See page product/card.php for example
		if (!empty($conf->dol_use_jmobile)) $useajax = 0;
		if (empty($conf->use_javascript_ajax)) $useajax = 0;

		if ($useajax) {
			$autoOpen = true;
			$dialogconfirm = 'dialog-confirm';
			$button = '';
			if (!is_numeric($useajax)) {
				$button = $useajax;
				$useajax = 1;
				$autoOpen = false;
				$dialogconfirm .= '-' . $button;
			}
			$pageyes = $page . (preg_match('/\?/', $page) ? '&' : '?') . 'action=' . $action . '&confirm=yes';
			$pageno = ($useajax == 2 ? $page . (preg_match('/\?/', $page) ? '&' : '?') . 'action=' . $action . '&confirm=no' : '');
			// Add input fields into list of fields to read during submit (inputok and inputko)
			if (is_array($formquestion)) {
				foreach ($formquestion as $key => $input) {
					//print "xx ".$key." rr ".is_array($input)."<br>\n";
					if (is_array($input) && isset($input['name'])) {
						// Modification Open-DSI - Begin
						if (is_array($input['name'])) $inputok = array_merge($inputok, $input['name']);
						else array_push($inputok, $input['name']);
						// Modification Open-DSI - End
					}
					if (isset($input['inputko']) && $input['inputko'] == 1) array_push($inputko, $input['name']);
				}
			}
			// Show JQuery confirm box. Note that global var $useglobalvars is used inside this template
			$formconfirm .= '<div id="' . $dialogconfirm . '" title="' . dol_escape_htmltag($title) . '" style="display: none;">';
			if (!empty($more)) {
				$formconfirm .= '<div class="confirmquestions">' . $more . '</div>';
			}
			$formconfirm .= ($question ? '<div class="confirmmessage">' . img_help('', '') . ' ' . $question . '</div>' : '');
			$formconfirm .= '</div>' . "\n";

			$formconfirm .= "\n<!-- begin ajax form_confirm page=" . $page . " -->\n";
			$formconfirm .= '<script type="text/javascript">' . "\n";
			$formconfirm .= 'jQuery(document).ready(function() {
            $(function() {
		$( "#' . $dialogconfirm . '" ).dialog(
		{
                    autoOpen: ' . ($autoOpen ? "true" : "false") . ',';
			if ($newselectedchoice == 'no') {
				$formconfirm .= '
						open: function() {
					$(this).parent().find("button.ui-button:eq(2)").focus();
						},';
			}
			if ($post) {
				$formconfirm .= '
                    resizable: ' . ($resizable ? 'true' : 'false') . ',
                    height: "' . $height . '",
                    width: "' . $width . '",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        "' . dol_escape_js($langs->transnoentities("Yes")) . '": function() {
                            var form_dialog_confirm = $("form#form_dialog_confirm");
                            form_dialog_confirm.find("input#confirm").val("yes");
							form_dialog_confirm.submit();
                            $(this).dialog("close");
                        },
                        "' . dol_escape_js($langs->transnoentities("No")) . '": function() {
                            if (' . ($useajax == 2 ? '1' : '0') . ' == 1) {
                                var form_dialog_confirm = $("form#form_dialog_confirm");
                                form_dialog_confirm.find("input#confirm").val("no");
                                form_dialog_confirm.submit();
                            }
                            $(this).dialog("close");
                        }
                    }
                }
                );

		var button = "' . $button . '";
		if (button.length > 0) {
			$( "#" + button ).click(function() {
				$("#' . $dialogconfirm . '").dialog("open");
				});
                }
            });
            });
            </script>';
			} else {
				$formconfirm .= '
                    resizable: false,
                    height: "' . $height . '",
                    width: "' . $width . '",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        "' . dol_escape_js($langs->transnoentities("Yes")) . '": function() {
				var options="";
				var inputok = ' . json_encode($inputok) . ';
				var pageyes = "' . dol_escape_js(!empty($pageyes) ? $pageyes : '') . '";
				if (inputok.length>0) {
					$.each(inputok, function(i, inputname) {
						var more = "";
						if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
					    if ($("#" + inputname).attr("type") == "radio") { more = ":checked"; }
						var inputvalue = $("#" + inputname + more).val();
						if (typeof inputvalue == "undefined") { inputvalue=""; }
						options += "&" + inputname + "=" + urlencode(inputvalue);
					});
				}
				var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
				//alert(urljump);
					if (pageyes.length > 0) { location.href = urljump; }
                            $(this).dialog("close");
                        },
                        "' . dol_escape_js($langs->transnoentities("No")) . '": function() {
				var options = "";
				var inputko = ' . json_encode($inputko) . ';
				var pageno="' . dol_escape_js(!empty($pageno) ? $pageno : '') . '";
				if (inputko.length>0) {
					$.each(inputko, function(i, inputname) {
						var more = "";
						if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
						var inputvalue = $("#" + inputname + more).val();
						if (typeof inputvalue == "undefined") { inputvalue=""; }
						options += "&" + inputname + "=" + urlencode(inputvalue);
					});
				}
				var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
				//alert(urljump);
					if (pageno.length > 0) { location.href = urljump; }
                            $(this).dialog("close");
                        }
                    }
                }
                );

		var button = "' . $button . '";
		if (button.length > 0) {
			$( "#" + button ).click(function() {
				$("#' . $dialogconfirm . '").dialog("open");
				});
                }
            });
            });
            </script>';
			}
			$formconfirm .= "<!-- end ajax form_confirm -->\n";
		} else {
			$formconfirm .= "\n<!-- begin form_confirm page=" . $page . " -->\n";

			$formconfirm .= '<form method="POST" action="' . $page . '" class="notoptoleftroright">' . "\n";
			$formconfirm .= '<input type="hidden" name="action" value="' . $action . '">' . "\n";
			$formconfirm .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">' . "\n";

			$formconfirm .= '<table width="100%" class="valid">' . "\n";

			// Line title
			$formconfirm .= '<tr class="validtitre"><td class="validtitre" colspan="3">' . img_picto('', 'recent') . ' ' . $title . '</td></tr>' . "\n";

			// Line form fields
			if ($more) {
				$formconfirm .= '<tr class="valid"><td class="valid" colspan="3">' . "\n";
				$formconfirm .= $more;
				$formconfirm .= '</td></tr>' . "\n";
			}

			// Line with question
			$formconfirm .= '<tr class="valid">';
			$formconfirm .= '<td class="valid">' . $question . '</td>';
			$formconfirm .= '<td class="valid">';
			$formconfirm .= $form->selectyesno("confirm", $newselectedchoice);
			$formconfirm .= '</td>';
			$formconfirm .= '<td class="valid" align="center"><input class="button valignmiddle" type="submit" value="' . $langs->trans("Validate") . '"></td>';
			$formconfirm .= '</tr>' . "\n";

			$formconfirm .= '</table>' . "\n";

			$formconfirm .= "</form>\n";
			$formconfirm .= '<br>';

			$formconfirm .= "<!-- end form_confirm -->\n";
		}

		return $formconfirm;
	}
}
