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
class FormDigitalSignatureRequest
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
    }


	/**
     *  Show list of actions for element
     *
     *  @param	Object	$object			Object
     *	@param	int		$socid			socid of user
     *  @param	int		$forceshowtitle	Show title even if there is no actions to show
     *  @param  string  $morecss        More css on table
     *  @param	int		$max			Max number of record
     *  @param	string	$moreparambacktopage	More param for the backtopage
     *	@return	int						<0 if KO, >=0 if OK
     */
    function showActions($object, $socid = 0, $forceshowtitle = 0, $morecss = 'listactions', $max = 0, $moreparambacktopage = '')
    {
		global $langs,$conf;

		$listofactions = $this->helper->getActions($socid, $object->id,  '', '', '', ($max?($max+1):0));
		if (! is_array($listofactions)) dol_print_error($this->db, 'FailedToGetActions');

        $num = count($listofactions);
        if ($num || $forceshowtitle)
        {
			$title=$langs->trans("Actions");

            $urlbacktopage=$_SERVER['PHP_SELF'].'?id='.$object->id.($moreparambacktopage?'&'.$moreparambacktopage:'');

			if ($conf->agenda->enabled) {
				$buttontoaddnewevent = '<a href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create&datep=' . dol_print_date(dol_now(), 'dayhourlog') . '&origin=' . $typeelement . '&originid=' . $object->id . '&socid=' . $object->socid . '&projectid=' . $object->fk_project . '&backtopage=' . urlencode($urlbacktopage) . '">';
				$buttontoaddnewevent.= $langs->trans("AddEvent");
				$buttontoaddnewevent.= '</a>';
			}
			print load_fiche_titre($title, $buttontoaddnewevent, '');

		$page=0; $param=''; $sortfield='a.datep';

		$total = 0;

		print '<div class="div-table-responsive">';
		print '<table class="noborder'.($morecss?' '.$morecss:'').'" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre('Ref', $_SERVER["PHP_SELF"], '', $page, $param, '');
		print_liste_field_titre('Action', $_SERVER["PHP_SELF"], '', $page, $param, '');
		print_liste_field_titre('Type', $_SERVER["PHP_SELF"], '', $page, $param, '');
		print_liste_field_titre('Date', $_SERVER["PHP_SELF"], '', $page, $param, 'align="center"');
		print_liste_field_titre('By', $_SERVER["PHP_SELF"], '', $page, $param, '');
		print_liste_field_titre('', $_SERVER["PHP_SELF"], '', $page, $param, 'align="right"');
		print '</tr>';
		print "\n";

		$userstatic = new User($this->db);

		$cursorevent = 0;
		foreach($listofactions as $action)
		{
			if ($max && $cursorevent >= $max) break;
				if(empty($action->id)) {
					continue;
				}
			$ref=$action->getNomUrl(1, -1);
			$label=$action->getNomUrl(0, 38);

			print '<tr class="oddeven">';
				print '<td>'.$ref.'</td>';
			print '<td>'.$label.'</td>';
			print '<td>';
			if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
			{
			    if ($action->type_picto) print img_picto('', $action->type_picto);
			    else {
			        if ($action->type_code == 'AC_RDV')   print img_picto('', 'object_group').' ';
			        if ($action->type_code == 'AC_TEL')   print img_picto('', 'object_phoning').' ';
			        if ($action->type_code == 'AC_FAX')   print img_picto('', 'object_phoning_fax').' ';
			        if ($action->type_code == 'AC_EMAIL') print img_picto('', 'object_email').' ';
			    }
			}
			print $action->type;
			print '</td>';
			print '<td align="center">'.dol_print_date($action->datep, 'dayhour');
			if ($action->datef)
			{
				$tmpa=dol_getdate($action->datep);
				$tmpb=dol_getdate($action->datef);
				if ($tmpa['mday'] == $tmpb['mday'] && $tmpa['mon'] == $tmpb['mon'] && $tmpa['year'] == $tmpb['year'])
				{
					if ($tmpa['hours'] != $tmpb['hours'] || $tmpa['minutes'] != $tmpb['minutes'] && $tmpa['seconds'] != $tmpb['seconds']) print '-'.dol_print_date($action->datef, 'hour');
				}
				else print '-'.dol_print_date($action->datef, 'dayhour');
			}
			print '</td>';
			print '<td>';
			if (! empty($action->author->id))
			{
				$userstatic->id = $action->author->id;
				$userstatic->firstname = $action->author->firstname;
				$userstatic->lastname = $action->author->lastname;
				print $userstatic->getNomUrl(1, '', 0, 0, 16, 0, '', '');
			}
			print '</td>';
			print '<td align="right">';
			if (! empty($action->author->id))
			{
				print $action->getLibStatut(3);
			}
			print '</td>';
			print '</tr>';

			$cursorevent++;
		}

		if ($max && $num > $max)
		{
			print '<tr class="oddeven"><td colspan="6">'.$langs->trans("More").'...</td></tr>';
		}

		print '</table>';
		print '</div>';
        }

        return $num;
	}

	/**
	 * Function to display documents file lines
	 * @param DigitalSignatureRequest $object Parent object of lines to display
	 * @param int $currentDocumentIdEdited current document id which is edited
	 * @param bool $readOnly - force readonly mode
	 * @param bool $permissionToEdit - boolean indicating if user can edit document
	 * @param bool $permissionToDelete - boolean indicating if user can delete document
	 * @return void
	 */
	public function showDocumentLines($object, $currentDocumentIdEdited, $readOnly, $permissionToEdit, $permissionToDelete) {
		global $conf, $hookmanager;

		$documents = $object->documents;
		$isALineBeingEdited = empty($readOnly) && empty($currentDocumentIdEdited);
		$userCanChangeOrder = empty($readOnly) && !empty($permissionToEdit) && count($documents) > 0 && !$isALineBeingEdited;
		$userCanAskToEditDocumentLine = empty($readOnly) && !empty($permissionToEdit);
		$userCanAskToDeleteDocumentLine = empty($readOnly) && !empty($permissionToDelete);
		$userCanSeeFormToAddNewLine = empty($readOnly) && !$isALineBeingEdited && !empty($permissionToEdit);

		print '	<form name="documentForm" id="documentForm" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.($isALineBeingEdited ? '#addline' : '#line_'.$currentDocumentIdEdited).'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="' . ($isALineBeingEdited ? 'addline' : 'updateline').'">
		';

		if (!empty($conf->use_javascript_ajax) && $userCanChangeOrder) {
			//toDo ajust param for ajaxrow.tpl.php
			include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
		}

		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelines" class="noborder noshadow" width="100%">';

		//display document lines headers

		//display each document lines


		// Form to add new line
		if ($userCanSeeFormToAddNewLine)
		{
			$object->formAddObjectLine(1, $mysoc, $soc);
			$parameters = array();
			$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		}
		print '</table>';
		print '</div>';
		print "</form>\n";
	}
}
