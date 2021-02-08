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
class FormDigitalSignatureManager
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
	 *  Show list of actions for element
	 *
	 *  @param	int		$digitalSignatureRequestId id of the digital request manager on which we are moving elements
	 *  @param	int		$lineId id of the element on which we display the action button
	 *  @param	int		$currentLineIndex	Current index for the line
	 *	@param	int		$numberOfDocumentLines	number of document of the linked request
	 *  @param 	string 	$upActionName name of the action allowing up move action
	 *  @param	string  $downActionName name of the action allowing down move action
	 * 	@param	string  $paramLineIdName name of the field where is stored moved line id
	 *	@return	void
	 */
	public function showMoveActionButtonsForLine($digitalSignatureRequestId, $lineId, $currentLineIndex, $numberOfDocumentLines, $upActionName, $downActionName, $paramLineIdName)
	{
		global $conf;
		if (!empty($conf->browser->phone)) {
			print '<td align="center" class="linecolmove tdlineupdown">';
			if ($currentLineIndex > 0) {
				print '<a class="lineupdown" href="' . $_SERVER["PHP_SELF"] . '?id=' . $digitalSignatureRequestId . '&amp;action=' . $upActionName . '&amp;' . $paramLineIdName . '=' . $lineId . '">';
				print img_up('default', 0, 'imgupforline');
				print '</a>';
			}

			if ($currentLineIndex != $numberOfDocumentLines - 1) {
				print '<a class="lineupdown" href="' . $_SERVER["PHP_SELF"] . '?id=' . $digitalSignatureRequestId . '&amp;action=' . $downActionName . '&amp;' . $paramLineIdName . '=' . $lineId . '">';
				print img_down('default', 0, 'imgdownforline');
				print '</a>';
			}
			print '</td>';
		} elseif ($numberOfDocumentLines > 1) {
			print '<td align="center" class="linecolmove tdlineupdown"></td>';
		}
	}

	/**
	 *	Return list of all contacts (for a third party or all)
	 *
	 *	@param	int		$socid      	Id ot third party or 0 for all
	 *	@param  string	$selected   	Id contact pre-selectionne
	 *	@param  string	$htmlname  	    Name of HTML field ('none' for a not editable field)
	 *	@param  int		$showempty     	0=no empty value, 1=add an empty value, 2=add line 'Internal' (used by user edit)
	 *	@param  string	$exclude        List of contacts id to exclude
	 *	@param	string	$limitto		Disable answers that are not id in this array list
	 *	@param	integer	$showfunction   Add function into label
	 *	@param	string	$moreclass		Add more class to class style
	 *	@param	bool	$options_only	Return options only (for ajax treatment)
	 *	@param	integer	$showsoc	    Add company into label
	 * 	@param	int		$forcecombo		Force to use combo box
	 *  @param	array	$events			Event options. Example: array(array('method'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'params'=>array('add-customer-contact'=>'disabled')))
	 *  @param bool 	$hideDisabledItem should we hide disabled choice
	 *	@return	 int					<0 if KO, Nb of contact in list if OK
	 */
	public function selectcontacts($socid, $selected = '', $htmlname = 'contactid', $showempty = 0, $exclude = '', $limitto = '', $showfunction = 0, $moreclass = '', $options_only = false, $showsoc = 0, $forcecombo = 0, $events = array(), $hideDisabledItem = false)
	{
		global $conf, $langs;

		$langs->load('companies');

		$out = '';

		// On recherche les societes
		$sql = "SELECT sp.rowid, sp.lastname, sp.statut, sp.firstname, sp.poste";
		if ($showsoc > 0) $sql .= " , s.nom as company";
		$sql .= " FROM " . MAIN_DB_PREFIX . "socpeople as sp";
		if ($showsoc > 0) $sql .= " LEFT OUTER JOIN  " . MAIN_DB_PREFIX . "societe as s ON s.rowid=sp.fk_soc";
		$sql .= " WHERE sp.entity IN (" . getEntity('societe') . ")";
		if ($socid > 0) $sql .= " AND sp.fk_soc=" . $socid;
		if (!empty($conf->global->CONTACT_HIDE_INACTIVE_IN_COMBOBOX)) $sql .= " AND sp.statut <> 0";
		$sql .= " ORDER BY sp.lastname ASC";

		dol_syslog(get_class($this) . "::select_contacts", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->db->num_rows($resql);

			if ($conf->use_javascript_ajax && !$forcecombo && !$options_only) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$comboenhancement = ajax_combobox($htmlname, $events, $conf->global->CONTACT_USE_SEARCH_TO_SELECT);
				$out .= $comboenhancement;
			}

			if ($htmlname != 'none' || $options_only) $out .= '<select class="flat' . ($moreclass ? ' ' . $moreclass : '') . '" id="' . $htmlname . '" name="' . $htmlname . '">';
			if ($showempty == 1) $out .= '<option value="0"' . ($selected == '0' ? ' selected' : '') . '></option>';
			if ($showempty == 2) $out .= '<option value="0"' . ($selected == '0' ? ' selected' : '') . '>' . $langs->trans("Internal") . '</option>';
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num) {
				include_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
				$contactstatic = new Contact($this->db);

				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);

					$contactstatic->id = $obj->rowid;
					$contactstatic->lastname = $obj->lastname;
					$contactstatic->firstname = $obj->firstname;
					if ($obj->statut == 1) {
						if ($htmlname != 'none') {
							$disabled = 0;
							if (is_array($exclude) && count($exclude) && in_array($obj->rowid, $exclude)) $disabled = 1;
							if (is_array($limitto) && count($limitto) && !in_array($obj->rowid, $limitto)) $disabled = 1;
							if ($disabled == 1 && $hideDisabledItem) {
								continue;
							}
							if ($selected && $selected == $obj->rowid) {
								$out .= '<option value="' . $obj->rowid . '"';
								if ($disabled) $out .= ' disabled';
								$out .= ' selected>';
								$out .= $contactstatic->getFullName($langs);
								if ($showfunction && $obj->poste) $out .= ' (' . $obj->poste . ')';
								if (($showsoc > 0) && $obj->company) $out .= ' - (' . $obj->company . ')';
								$out .= '</option>';
							} else {
								$out .= '<option value="' . $obj->rowid . '"';
								if ($disabled) $out .= ' disabled';
								$out .= '>';
								$out .= $contactstatic->getFullName($langs);
								if ($showfunction && $obj->poste) $out .= ' (' . $obj->poste . ')';
								if (($showsoc > 0) && $obj->company) $out .= ' - (' . $obj->company . ')';
								$out .= '</option>';
							}
						} else {
							if ($selected == $obj->rowid) {
								$out .= $contactstatic->getFullName($langs);
								if ($showfunction && $obj->poste) $out .= ' (' . $obj->poste . ')';
								if (($showsoc > 0) && $obj->company) $out .= ' - (' . $obj->company . ')';
							}
						}
					}
					$i++;
				}
			} else {
				$out .= '<option value="-1"' . ($showempty == 2 ? '' : ' selected') . ' disabled>' . $langs->trans($socid ? "NoContactDefinedForThirdParty" : "NoContactDefined") . '</option>';
			}
			if ($htmlname != 'none' || $options_only) {
				$out .= '</select>';
			}

			$this->num = $num;
			return $out;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 * Function to get column title
	 * @param string $columnTitle Title of the column
	 * @param string $infoBoxContent Content of the helper info box
	 * @param bool $displayInfoBox should we display infobox helper
	 * @return string html content ready to be displayed
	 */
	public function getColumnTitle($columnTitle, $infoBoxContent = "", $displayInfoBox = false)
	{
		$out = '<td class="linecoldescription"><div style="display: flex;">';
		if ($displayInfoBox && !empty($infoBoxContent)) {
			$out .= $this->getInfoBox(true, $infoBoxContent);
		}
		$out .= $columnTitle;
		$out .= '</div></td>';
		return $out;
	}

	/**
	 * Function to display input column
	 * @param string $fieldName Input post field name
	 * @param string $fieldValue value to be displayed
	 * @param bool $displayInfoBox should we display info box
	 * @param string $infoBoxContent helper text to be displayed
	 * @param bool $displayWarning should we display warning box
	 * @param string[] $warningsContent warning content texts
	 * @param string $inputValueType html input value type
	 * @param string $moreInputParameter more input value type
	 * @return string html content
	 */
	public function getInputFieldColumn($fieldName, $fieldValue, $displayInfoBox, $infoBoxContent, $displayWarning, $warningsContent, $inputValueType = 'text', $moreInputParameter = '')
	{
		$out = '<td>';
		$out .= '<div style="display: flex;">';
		$shouldInfoBoxBeingDisplayed = $displayInfoBox && !empty($infoBoxContent);
		if ($shouldInfoBoxBeingDisplayed) {
			$out .= $this->form->textwithpicto('', $infoBoxContent, 1, 'help', '', 0, 2);
		}
		$out .= '<input class="flat" type="' . $inputValueType . '" name="' . $fieldName . '" value="' . $fieldValue . '" ' . $moreInputParameter . ' style="width: -webkit-fill-available;">';
		$out .= $this->getWarningInfoBox($displayWarning, $warningsContent);
		$out .= '</div>';
		$out .= '</td>';
		return $out;
	}

	/**
	 * Function to display select field into column
	 * @param string $fieldName Input post field name
	 * @param string $fieldValue value to be displayed
	 * @param string[] $arrayOfItemIntoKeyArray array of string with values key=>displayValue
	 * @param bool $displayInfoBox should we display info box
	 * @param string $infoBoxContent helper text to be displayed
	 * @param bool $displayWarning should we display warning box
	 * @param string[] $warningsContent warning content texts
	 * @return string html content
	 */
	public function getSelectFieldColumn($fieldName, $fieldValue, $arrayOfItemIntoKeyArray, $displayInfoBox, $infoBoxContent, $displayWarning, $warningsContent)
	{
		$out = '<td>';
		$out .= '<div style="display: flex;">';
		$shouldInfoBoxBeingDisplayed = $displayInfoBox && !empty($infoBoxContent);
		if ($shouldInfoBoxBeingDisplayed) {
			$out .= $this->form->textwithpicto('', $infoBoxContent, 1, 'help', '', 0, 2);
		}
		$out .= $this->form->selectarray($fieldName, $arrayOfItemIntoKeyArray, $fieldValue, 0, 0, 0, 'style="width: 95%;"', 0, 0, 0, '', '', 1);
		$out .= $this->getWarningInfoBox($displayWarning, $warningsContent);
		$out .= '</div>';
		$out .= '</td>';
		return $out;
	}


	/**
	 * Function to get tooltip box
	 * @param bool $displayToolTip should we display warning box
	 * @param string[]|string $content warning content texts
	 * @param string $toolTipPictoName name of the picto to use
	 * @return string html content
	 */
	public function getTooltipBox($displayToolTip, $content, $toolTipPictoName = 'info')
	{
		$out = "";
		if (!empty($content) && !is_array($content)) {
			$content = array($content);
		}
		$shouldWarningBoxBeingDisplayed = $displayToolTip && !empty($content);
		if ($shouldWarningBoxBeingDisplayed) {
			$out .= $this->form->textwithpicto('', implode('<br>', $content), 1, $toolTipPictoName, '', 0, 2);
		}
		return $out;
	}


	/**
	 * Function to get info box
	 * @param bool $displayInfo should we display warning box
	 * @param string[] $informationContent warning content texts
	 * @return string html content
	 */
	public function getInfoBox($displayInfo, $informationContent)
	{
		return $this->getTooltipBox($displayInfo, $informationContent, 'info');
	}

	/**
	 * Function to get warning tooltip box
	 * @param bool $displayWarning should we display warning box
	 * @param string[] $warningsContent warning content texts
	 * @return string html content
	 */
	public function getWarningInfoBox($displayWarning, $warningsContent)
	{
		return $this->getTooltipBox($displayWarning, $warningsContent, 'warning');
	}

	/**
	 * Get current digital signature people id on which an action is performed
	 * @param string $postFieldName name of the post field to get values from
	 * @return int
	 */
	public function getFormElementId($postFieldName)
	{
		$result = null;
		if (!empty($postFieldName)) {
			$result = GETPOST($postFieldName);
		}
		return !empty($result) ? $result : null;
	}

	/**
	 * Function to get current digital signature people id edited on page using showDocument
	 * @param string $action current action name on card
	 * @param string $editElementAction name of the edit action to check
	 * @return bool
	 */
	public function isAnElementBeingEdited($action, $editElementAction)
	{
		return $action == $editElementAction;
	}

	/**
	 * Function to get a row from a table with text center on it
	 * @param string $textToDisplay text to be displayed
	 * @param int $colspan needed colspan for row to take full table width
	 * @return int
	 */
	public function getRowWithTextOnCenter($textToDisplay, $colspan = 1)
	{
		return '<tr style="text-align:center;"><td colspan="' . $colspan . '">' . $textToDisplay . '</tr>';
	}


	/**
	 * Function to build an url for subItem action
	 * @param int $objectId Digital signature request id
	 * @param string $actionName Name of the asked action
	 * @param int $subObjectIdFieldName name of the sub Object parameter which will contain id field
	 * @param int $subObjectId value of the sub Object Id
	 * @param string $htmlRowPrefix Value of the prefix of the html item to scroll
	 * @return string action url with parameters
	 */
	public function buildActionUrlForLine($objectId, $actionName = null, $subObjectIdFieldName = null, $subObjectId = null, $htmlRowPrefix = null)
	{
		$out = $_SERVER["PHP_SELF"];
		$parameters = array('action' => $actionName, 'id' => $objectId);
		if ($subObjectIdFieldName && $subObjectId) {
			$parameters[$subObjectIdFieldName] = $subObjectId;
		}
		$parameters = array_filter($parameters);

		$stringValues = array();
		foreach ($parameters as $key => $value) {
			$stringValues[] = $key . '=' . $value;
		}

		if (!empty($stringValues)) {
			$out .= '?';
		}

		$out .= implode('&amp;', $stringValues);

		if ($htmlRowPrefix) {
			$out .= '#' . $htmlRowPrefix;
			if ($subObjectId) {
				$out .= '-' . $subObjectId;
			}
		}
		return $out;
	}

	/**
	 * Function to get select user form
	 * @param Object $object on which we are displaying user selection
	 * @param int $selectedUserId Already selected user id
	 * @param string $htmlName html name of the form
	 * @param int[] $excludeFollowingUserIds user ids to exclude from user selection
	 * @param bool $purposeEmptyChoice Should we allow empty choice (none selected)
	 * @return string
	 */
	public function selectUser($object, $selectedUserId, $htmlName, $excludeFollowingUserIds = array(), $purposeEmptyChoice = false)
	{
		global $hookmanager;
		$hookmanager->initHooks(array('digitalsignaturemanager'));
		$parameters = array('excludedUserIds' => &$excludeFollowingUserIds);
		$reshook = $hookmanager->executeHooks('availableUserListId', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0 && is_array($hookmanager->resPrint)) {
			$listOfUserId = $hookmanager->resPrint;
		} else {
			$listOfUserId = '';
		}
		return $this->form->select_dolusers($selectedUserId, $htmlName, (int) $purposeEmptyChoice, $excludeFollowingUserIds, 0, $listOfUserId, '', null, null, null, null, null, null, 'fullwidth minwidth200', 1);
	}

	/**
	 * Function to get select contact form
	 * @param Object $object on which we are displaying user selection
	 * @param int $selectedContactId Already selected user id
	 * @param string $htmlName html name of the form
	 * @param int $filterToFollowingSocId filter contact to following soc id
	 * @param int[] $filterToFollowingContactIds keep only contact with these id
	 * @param int[] $excludeFollowingContactIds Remove these contact ids
	 * @return string
	 */
	public function selectContact($object, $selectedContactId, $htmlName, $filterToFollowingSocId, $filterToFollowingContactIds = null, $excludeFollowingContactIds = array(), $purposeEmptyChoice = false)
	{
		global $hookmanager;
		$hookmanager->initHooks(array('digitalsignaturemanager'));
		$parameters = array('filterToFollowingSocId' => &$filterToFollowingSocId, 'filterToFollowingContactIds' => &$filterToFollowingContactIds, 'excludeFollowingContactIds' => &$excludeFollowingContactIds);
		$reshook = $hookmanager->executeHooks('availableContactListId', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
		return $this->selectcontacts(
			is_array($filterToFollowingContactIds) ? 0 : $filterToFollowingSocId,
			$selectedContactId,
			$htmlName,
			(int) $purposeEmptyChoice,
			$excludeFollowingContactIds,
			$filterToFollowingContactIds,
			0,
			'fullwidth minwidth200',
			null,
			null,
			null,
			null,
			true
		);
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

		if (is_array($formquestion) && !empty($formquestion)) {
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
			$more .= '<table class="paddingtopbottomonly" width="100%; max-height:600px">' . "\n";
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
		}

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
				$formconfirm .= '<div class="confirmquestions" style="max-height:600px">' . $more . '</div>';
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
	/**
	 * Function to display linked digital signature request
	 * @param DigitalSignatureRequest[] $listOfDigitalSignatureRequest digital signature requests to be displayed
	 * @return string
	 */
	public function showLinkedDigitalSignatureBlock($listOfDigitalSignatureRequest)
	{
		global $langs;
		$out = "<br>";
		$out .= load_fiche_titre($langs->trans('DigitalSignatureManagerLinkedRequestBlock'));
		$out .= '<table class="noborder allwidth"><tr class="liste_titre">';
		$out .= '<td>' . $langs->trans("DigitalSignatureManagerRequestSign") . '</td>';
		$out .= '<td style="text-align:right">' . $langs->trans("Statut") . '</td>';
		$out .= '</tr>';
		if (!empty($listOfDigitalSignatureRequest)) {
			foreach ($listOfDigitalSignatureRequest as $request) {
				$out .= '<tr>';
				$out .= '<td>' . $request->getNomUrl(1) . $this->getWarningInfoBox($request->is_staled_according_to_source_object, $langs->trans("DigitalSignatureManagerStaledData")) . '</td>';
				$out .= '<td style="text-align:right">' . $request->getLibStatut(5) . '</td>';
				$out .= '</tr>';
			}
		} else {
			$out .= '<td colspan="2" style="text-align:center">' . $langs->trans("DigitalSignatureManagerNoneLinked") . '</td>';
		}
		$out .= '</table>';
		return $out;
	}

	/**
	 * Function to generate button
	 * @param string $label label of the button
	 * @param bool $permissionToRequestAction is user able to click on this button
	 * @param int $objectId id of the card object
	 * @param string $actionName name of the action launched by this button
	 * @return string
	 */
	public function generateButton($label, $permissionToRequestAction, $objectId, $actionName)
	{
		global $langs;
		if ($permissionToRequestAction) {
			$out = '<a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $objectId . '&amp;action=' . $actionName . '">' . $label . '</a>' . "\n";
		} else {
			$out = '<a class="butActionRefused classfortooltip" href="#" title="' . dol_escape_htmltag($langs->trans("NotEnoughPermissions")) . '">' . $label . '</a>' . "\n";
		}
		return $out;
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
	 * 		With use of hookmanager
	 *     @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 *     @param   string          $action         Current action (if set). Generally create or edit or null
	 *     @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
	 *     @param  	string		$page        	   	Url of page to call if confirmation is OK
	 *     @param	string		$title       	   	Title
	 *     @param	string		$question    	   	Question
	 *     @param 	string		$confirmAction      	   	Action
	 *	   @param  	array		$formquestions	   	An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
	 * 	   @param  	string		$selectedchoice  	"" or "no" or "yes"
	 * 	   @param  	int			$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
	 *     @param  	int			$height          	Force height of box
	 *     @param	int			$width				Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
	 *     @param	int			$post				Send by form POST.
	 *     @param	int			$resizable			Resizable box (0=no, 1=yes).
	 *     @return 	string      	    			HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
	 */
	public function formConfirmWithHook(&$object, &$action, &$hookmanager, $page, $title, $question, $confirmAction, $formquestions = array(), $selectedchoice = "", $useajax = 0, $height = 200, $width = 500, $post = 0, $resizable = 0)
	{
		$parameters = array('question' => $formquestions);
		$reshook = $hookmanager->executeHooks('addMoreFormQuestion', $parameters, $object, $action);
		if (empty($reshook)) {
			$effectiveQuestions = array_merge($formquestions, is_array($hookmanager->resArray) ? $hookmanager->resArray : array());
		} elseif ($reshook > 0) {
			$effectiveQuestions = is_array($hookmanager->resArray) ? $hookmanager->resArray : array();
		} else {
			$effectiveQuestions = $formquestions;
		}
		return $this->formconfirm($page, $title, $question, $confirmAction, $effectiveQuestions, $selectedchoice, $useajax, $height, $width, $post, $resizable);
	}

	/**
	 * Function to get Text editor
	 * @param string $htmlName Html name of the input field
	 * @param string $value Content of the input field
	 * @return string
	 */
	public function getTextEditor($htmlName, $value)
	{
		require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
		$doleditor = new DolEditor($htmlName, $value, 'auto', 100, 'dolibarr_notes', 'In', false, false, 0, ROWS_5, '90%');
		return $doleditor->Create(1);
	}

	/**
	 *	Show a multiselect form from an array with order.
	 *
	 *	@param	string	$htmlname		Name of select
	 *	@param	array	$array			Array with key+value
	 *	@param	array	$selected		Array with key+value preselected
	 *	@param	int		$key_in_label   1 pour afficher la key dans la valeur "[key] value"
	 *	@param	int		$value_as_key   1 to use value as key
	 *	@param  string	$morecss        Add more css style
	 *	@param  int		$translate		Translate and encode value
	 *  @param	int		$width			Force width of select box. May be used only when using jquery couch. Example: 250, 95%
	 *  @param	string	$moreattrib		Add more options on select component. Example: 'disabled'
	 *  @param	string	$elemtype		Type of element we show ('category', ...)
	 *	@return	string					HTML multiselect string
	 *  @see selectarray
	 */
	public static function multiSelectArrayWithOrder($htmlname, $array, $selected = array(), $key_in_label = 0, $value_as_key = 0, $morecss = '', $translate = 0, $width = 0, $moreattrib = '', $elemtype = '')
	{
		global $conf, $langs;

		$out = '';

		// Add code for jquery to use multiselect
		if (!empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) || defined('REQUIRE_JQUERY_MULTISELECT')) {
			//$tmpplugin=empty($conf->global->MAIN_USE_JQUERY_MULTISELECT)?constant('REQUIRE_JQUERY_MULTISELECT'):$conf->global->MAIN_USE_JQUERY_MULTISELECT;
			$tmpplugin = "select2Sortable";
			$out .= '<!-- JS CODE TO ENABLE ' . $tmpplugin . ' for id ' . $htmlname . ' -->
			<script type="text/javascript">';
			$out .= '$(document).ready(function () {
					$(\'#' . $htmlname . '\').' . $tmpplugin . '({ width: "resolve" });
				});
			</script>';
		}

		// Try also magic suggest

		$out .= '<select id="' . $htmlname . '" class="' . ($morecss ? ' ' . $morecss : '') . '" multiple name="' . $htmlname . '[]"' . ' style="min-width:200px!important">' . "\n";
		if (is_array($array) && !empty($array)) {
			if ($value_as_key) $array = array_combine($array, $array);

			if (!empty($array)) {
				foreach ($array as $key => $value) {
					$out .= '<option value="' . $key . '"';
					if (is_array($selected) && !empty($selected) && in_array($key, $selected) && !empty($key)) {
						$out .= ' selected';
					}
					$out .= '>';

					$newval = ($translate ? $langs->trans($value) : $value);
					$newval = ($key_in_label ? $key . ' - ' . $newval : $newval);
					$out .= dol_htmlentitiesbr($newval);
					$out .= '</option>' . "\n";
				}
			}
		}
		$out .= '</select>' . "\n";

		return $out;
	}
}
