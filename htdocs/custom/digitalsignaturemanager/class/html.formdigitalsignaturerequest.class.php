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
	 * @var DigitalSignatureRequest static element
	 */
	public $elementStatic;

	/**
	 * @var string id of the table displaying row of digital signature document on card
	 *  should be only with minus character to make order ajax worked
	 */
	const DIGITALSIGNATUREDOCUMENT_TABLEID = "digitalsignaturedocumenttable";

	/**
	 * @var string id of the table displaying row of digital signature people on card
	 *  should be only with minus character to make order ajax worked
	 */
	const DIGITALSIGNATUREPEOPLE_TABLEID = "digitalsignaturepeopletable";

	/**
	 * @var string id of the table displaying row of digital signatory fields on card
	 *  should be only with minus character to make order ajax worked
	 */
	const DIGITALSIGNATURESIGNATORYFIELD_TABLEID = "digitalsignaturesignatoryfield";

	/**
	 * @var string id of the table displaying row of digital signatory fields on card
	 *  should be only with minus character to make order ajax worked
	 */
	const DIGITALSIGNATURECHECKBOX_TABLEID = "digitalsignaturecheckbox";

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
    public function showActions($object, $socid = 0, $forceshowtitle = 0, $morecss = 'listactions', $max = 0, $moreparambacktopage = '')
    {
		global $langs,$conf;

		$listofactions = $this->helper->getActions($object, $socid,  null, 'datep, id', 'ASC', ($max?($max+1):0));
		if (! is_array($listofactions)) {
			dol_print_error($this->db, 'FailedToGetActions');
		}
        $num = count($listofactions);
        if ($num || $forceshowtitle)
        {
			$title=$langs->trans("Actions");

            $urlbacktopage=$_SERVER['PHP_SELF'].'?id='.$object->id.($moreparambacktopage?'&'.$moreparambacktopage:'');

			if ($conf->agenda->enabled) {
				$buttontoaddnewevent = '<a href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create&datep=' . dol_print_date(dol_now(), 'dayhourlog') . '&origin=' . $this->elementStatic->element . '&originid=' . $object->id . '&socid=' . $object->socid . '&projectid=' . $object->fk_project . '&backtopage=' . urlencode($urlbacktopage) . '">';
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
	 * @param int $currentDocumentIdEdited current people id which is edited
	 * @param bool $readOnlyMode - force readonly mode
	 * @param bool $permissionToAdd - boolean indicating if user can edit document
	 * @param bool $permissionToEdit - boolean indicating if user can edit document
	 * @param bool $permissionToDelete - boolean indicating if user can delete document
	 * @return void
	 */
	public function showDocumentLines($object, $currentDocumentIdEdited, $readOnlyMode, $permissionToAdd, $permissionToEdit, $permissionToDelete)
	{
		global $conf, $langs;

		$documents = $object->getLinkedDocuments();
		$currentLineEdited = findObjectInArrayByProperty($documents, 'id', $currentDocumentIdEdited);
		$isALineBeingEdited = (bool) $currentLineEdited;

		$userCanChangeOrder = !$readOnlyMode && !empty($permissionToEdit) && count($documents) > 1;
		$userCanAskToEditDocumentLine = !$isALineBeingEdited && !$readOnlyMode && !empty($permissionToEdit);
		$userCanAskToDeleteDocumentLine = !$isALineBeingEdited && !$readOnlyMode && !empty($permissionToDelete);
		$userCanAddDocumentLine = !$readOnlyMode && $permissionToAdd && !$isALineBeingEdited;

		print '<div class="div-table-responsive-no-min">';
		print '<div class="titre"><h3>' . $langs->trans('DigitalSignatureManagerDocumentList') . '</h3></div>';

		print '<table id="' . self::DIGITALSIGNATUREDOCUMENT_TABLEID . '" class="noborder noshadow tabBar" width="100%">';

		$neededActionColumnForDocumentRead = count(array_filter(array($userCanAskToEditDocumentLine, $userCanAskToDeleteDocumentLine, $userCanChangeOrder)));
		$neddedActionColumnForDocumentEdition = $isALineBeingEdited ? count(array_filter(array(true, $userCanChangeOrder))) : 0;
		$neededActionColumnForDocumentCreation = $userCanAddDocumentLine ? 1 : 0;

		$neededActionColumn = max($neededActionColumnForDocumentRead, $neddedActionColumnForDocumentEdition, $neededActionColumnForDocumentCreation);

		//display document lines headers
		$nbOfActionColumn = 0;
		print '<tr class="liste_titre nodrag nodrop">';

		if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
		}

		print $this->formDigitalSignatureManager->getColumnTitle(
			$langs->trans('DigitalSignatureManagerDocumentColumnTitle')
		);

		print $this->formDigitalSignatureManager->getColumnTitle(
			$langs->trans('DigitalSignatureManagerDocumentCheckBoxColumnTitle')
		);

		if($userCanAskToEditDocumentLine) {
			print '<td class="linecoledit" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		if($userCanAskToDeleteDocumentLine) {
			print '<td class="linecoldelete" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		if($userCanChangeOrder) {
			print '<td class="linecolmove" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		for($i = $nbOfActionColumn; $i < $neededActionColumn; $i++) {
			print '<td class="linecoledit" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		print '</tr>';

		if (!empty($conf->use_javascript_ajax) && $userCanChangeOrder) {
			//toDo ajust param for ajaxrow.tpl.php
			$IdOfTableDisplayingRowToBeSorted = self::DIGITALSIGNATUREDOCUMENT_TABLEID;
			$elementType = $this->formDigitalSignatureDocument->elementObjectStatic->element;
			include dol_buildpath('digitalsignaturemanager/tpl/ajax/ajaxrow.tpl.php');
		}

		//we display documents
		foreach($documents as $document) {
			if($document->id != $currentDocumentIdEdited) {
				$this->formDigitalSignatureDocument->showDocument($document, $userCanAskToEditDocumentLine, $userCanAskToDeleteDocumentLine, $userCanChangeOrder, $nbOfActionColumn);
			}
			else {
				//We display the form to edit line
				$this->formDigitalSignatureDocument->showDocumentEditForm($object, $currentLineEdited, $nbOfActionColumn, $userCanChangeOrder);
			}
		}
		//We display form to add new document
		if ($userCanAddDocumentLine)
		{
			$this->formDigitalSignatureDocument->showDocumentAddForm($object, $nbOfActionColumn);
		}

		print '</table>';
		print '</div>';
	}

	/**
	 * Function to display digital signature signatory lines lines
	 * @param DigitalSignatureRequest $object Parent object of lines to display
	 * @param int $currentSignatoryFieldEditedId current document id which is edited
	 * @param bool $readOnlyMode - force readonly mode
	 * @param bool $permissionToAdd - boolean indicating if user can edit document
	 * @param bool $permissionToEdit - boolean indicating if user can edit document
	 * @param bool $permissionToDelete - boolean indicating if user can delete document
	 * @return void
	 */
	public function showSignatoryFieldLines($object, $currentSignatoryFieldEditedId, $readOnlyMode, $permissionToAdd, $permissionToEdit, $permissionToDelete)
	{
		global $conf, $langs;

		$listOfSignatoryField = $object->signatoryFields;
		$currentLineEdited = findObjectInArrayByProperty($listOfSignatoryField, 'id', $currentSignatoryFieldEditedId);
		$isALineBeingEdited = (bool) $currentLineEdited;

		$userCanAskToEditLine = !$isALineBeingEdited && !$readOnlyMode && !empty($permissionToEdit) && count($listOfSignatoryField) > 0;
		$userCanAskToDeleteLine = !$isALineBeingEdited && !$readOnlyMode && !empty($permissionToDelete) && count($listOfSignatoryField) > 0;
		$userCanAddLine = !$readOnlyMode && $permissionToAdd && !$isALineBeingEdited;

		$numberOfActionColumnPerComponentsDisplayed = array(
			'showSignatoryField'=>count($listOfSignatoryField) > 0 ? count(array_filter(array($userCanAskToEditLine, $userCanAskToDeleteLine))) : null,
			'editSignatoryField'=> $isALineBeingEdited ? 1 : null,
			'addSignatoryField' => $userCanAddLine ? 1 : null,
		);
		$neededActionColumn = max(array_values(array_filter($numberOfActionColumnPerComponentsDisplayed)));

		print '<div class="div-table-responsive-no-min">';
		//We print title
		print '<div class="titre">';
		print '<h3>';
		print $this->formDigitalSignatureManager->getInfoBox(true, $langs->trans('DigitalSignatureManagerSignatureSignatoryListHelperText'));
		print $langs->trans('DigitalSignatureManagerSignatureSignatoryList');
		$validationErrorsMissingSignatoryField = $object->checkThatEachDocumentHasASignatureField();
		if(!empty($validationErrorsMissingSignatoryField)) {
			print $this->formDigitalSignatureManager->getWarningInfoBox(!$readOnlyMode, $validationErrorsMissingSignatoryField);
		}
		print '</h3>';
		print '</div>';
		print '<table id="' . self::DIGITALSIGNATURESIGNATORYFIELD_TABLEID . '" class="noborder noshadow tabBar" width="100%">';

		//display people lines header

		print '<tr class="liste_titre nodrag nodrop">';

		if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
		}
		print $this->formDigitalSignatureManager->getColumnTitle(
			$langs->trans('DigitalSignatureSignatoryFieldLinkedSignatory')
		);

		print $this->formDigitalSignatureManager->getColumnTitle(
			$langs->trans('DigitalSignatureSignatoryFieldLinkedDocument')
		);

		print $this->formDigitalSignatureManager->getColumnTitle(
			$langs->trans('DigitalSignatureSignatoryFieldPage')
		);

		print $this->formDigitalSignatureManager->getColumnTitle(
			$langs->trans('DigitalSignatureSignatoryFieldXAxis')
		);

		print $this->formDigitalSignatureManager->getColumnTitle(
			$langs->trans('DigitalSignatureSignatoryFieldYAxis')
		);

		$nbOfActionColumn = 0;
		if($userCanAskToEditLine) {
			print '<td class="linecoledit" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		if($userCanAskToDeleteLine) {
			print '<td class="linecoldelete" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		for($i = $nbOfActionColumn; $i < $neededActionColumn; $i++) {
			print '<td class="linecoledit" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}
		print '</tr>';

		$nbOfContentColumn = 5;

		//we display digital signature people
		foreach($listOfSignatoryField as $signatoryField) {
			if($signatoryField->id != $currentSignatoryFieldEditedId) {
				$this->formDigitalSignatureSignatoryField->show($signatoryField, $userCanAskToEditLine, $userCanAskToDeleteLine, $neededActionColumn);
			}
			else {
				//We display the form to edit line
				$this->formDigitalSignatureSignatoryField->showEditForm($object, $signatoryField, $nbOfContentColumn, $neededActionColumn);
			}
		}
		//We display form to add new document
		if ($userCanAddLine)
		{
			$this->formDigitalSignatureSignatoryField->showAddForm($object, $nbOfContentColumn, $neededActionColumn);
		}

		print '</table>';
		print '</div>';
	}

	/**
	 * Function to display digital signature people lines
	 * @param DigitalSignatureRequest $object Parent object of lines to display
	 * @param int $currentPeopleIdEdited current document id which is edited
	 * @param bool $readOnlyMode - force readonly mode
	 * @param bool $permissionToAdd - boolean indicating if user can edit document
	 * @param bool $permissionToEdit - boolean indicating if user can edit document
	 * @param bool $permissionToDelete - boolean indicating if user can delete document
	 * @return void
	 */
	public function showPeopleLines($object, $currentPeopleIdEdited, $readOnlyMode, $permissionToAdd, $permissionToEdit, $permissionToDelete)
	{
		global $conf, $langs;

		$listOfpeople = $object->people;
		$currentLineEdited = findObjectInArrayByProperty($listOfpeople, 'id', $currentPeopleIdEdited);
		$isALineBeingEdited = (bool) $currentLineEdited;

		$userCanChangeOrder = !$readOnlyMode && !empty($permissionToEdit) && count($listOfpeople) > 1;
		$userCanAskToEditLine = !$isALineBeingEdited && !$readOnlyMode && !empty($permissionToEdit) && count($listOfpeople) > 0;
		$userCanAskToDeleteLine = !$isALineBeingEdited && !$readOnlyMode && !empty($permissionToDelete) && count($listOfpeople) > 0;
		$userCanAddLine = !$readOnlyMode && $permissionToAdd && !$isALineBeingEdited;

		$displayStatus = $readOnlyMode;

		//$numberOfContentColumnForShowPeople
		$displayLinkedObjectColumn = true;
		if (count($listOfpeople) > 0 && $this->formDigitalSignaturePeople->elementObjectStatic::isThereOnlyFreePeople($listOfpeople)) {
			$displayLinkedObjectColumn = false;
		}
		if(count($listOfpeople) == 0) {
			$numberOfContentColumnForShowPeople = null;
		}
		else {
			$numberOfContentColumnForShowPeople = $displayLinkedObjectColumn ? 5 : 4;
		}

		$numberOfContentColumnPerComponentsDisplayed = array(
			'showPeople'=>$numberOfContentColumnForShowPeople,
			'showEditForm'=> $isALineBeingEdited ? 5 : null,
			'showFromContactAddForm' => $userCanAddLine ? 1 : null,
			'showFromUserAddForm' => $userCanAddLine ? 1 : null,
			'showFreeAddForm' => $userCanAddLine ? 4:null
		);
		$neededContentColumn = max(array_values(array_filter($numberOfContentColumnPerComponentsDisplayed)));
		$neededContentColumn = $neededContentColumn < 1 ? 1 : $neededContentColumn;

		$numberOfActionColumnPerComponentsDisplayed = array(
			'showPeople'=>count($listOfpeople) > 0 ? count(array_filter(array($userCanChangeOrder , $userCanAskToEditLine, $userCanAskToDeleteLine))) : null,
			'showEditForm'=> $isALineBeingEdited ? 1 + count(array($userCanChangeOrder)) : null,
			'showFromContactAddForm' => $userCanAddLine ? 1 : null,
			'showFromUserAddForm' => $userCanAddLine ? 1 : null,
			'showFreeAddForm' => $userCanAddLine ? 1: null
		);
		$neededActionColumn = max(array_values(array_filter($numberOfActionColumnPerComponentsDisplayed)));

		print '<div class="div-table-responsive-no-min">';
		print '<div class="titre"><h3>' . $langs->trans('DigitalSignatureManagerPeopleList') . '</h3></div>';
		print '<table id="' . self::DIGITALSIGNATUREPEOPLE_TABLEID . '" class="noborder noshadow tabBar" width="100%">';

		//display people lines header

		print '<tr class="liste_titre nodrag nodrop">';

		if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
		}

		if($displayLinkedObjectColumn) {
			print $this->formDigitalSignatureManager->getColumnTitle(
				$langs->trans('DigitalSignatureLinkedObjectTitle')
			);
		}

		print $this->formDigitalSignatureManager->getColumnTitle(
			$langs->trans('DigitalSignaturePeopleLastname')
		);

		print $this->formDigitalSignatureManager->getColumnTitle(
			$langs->trans('DigitalSignaturePeopleFirstname')
		);

		print $this->formDigitalSignatureManager->getColumnTitle(
			$langs->trans('DigitalSignaturePeopleMail')
		);

		print $this->formDigitalSignatureManager->getColumnTitle(
			$langs->trans('DigitalSignaturePeopleMobilePhoneNumber')
		);

		if($displayStatus) {
			print $this->formDigitalSignatureManager->getColumnTitle(
				$langs->trans('DigitalSignaturePeopleStatus')
			);
		}

		$nbOfActionColumn = 0;
		if($userCanAskToEditLine) {
			print '<td class="linecoledit" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		if($userCanAskToDeleteLine) {
			print '<td class="linecoldelete" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		if($userCanChangeOrder) {
			print '<td class="linecolmove" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		for($i = $nbOfActionColumn; $i < $neededActionColumn; $i++) {
			print '<td class="linecoledit" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}
		print '</tr>';


		if (!empty($conf->use_javascript_ajax) && $userCanChangeOrder) {
			//toDo ajust param for ajaxrow.tpl.php
			$IdOfTableDisplayingRowToBeSorted = self::DIGITALSIGNATUREPEOPLE_TABLEID;
			$elementType = $this->formDigitalSignaturePeople->elementObjectStatic->element;
			include dol_buildpath('digitalsignaturemanager/tpl/ajax/ajaxrow.tpl.php');
		}

		//we display digital signature people
		foreach($listOfpeople as $people) {
			if($people->id != $currentPeopleIdEdited) {
				$this->formDigitalSignaturePeople->showPeople($people, $userCanAskToEditLine, $userCanAskToDeleteLine, $userCanChangeOrder, $neededActionColumn, $displayLinkedObjectColumn, $displayStatus);
			}
			else {
				//We display the form to edit line
				$this->formDigitalSignaturePeople->showEditForm($object, $people, $userCanChangeOrder, $neededContentColumn, $neededActionColumn, $displayLinkedObjectColumn);
			}
		}
		//We display form to add new document
		if ($userCanAddLine)
		{
			$this->formDigitalSignaturePeople->showFreeAddForm($object, $neededContentColumn, $neededActionColumn);
			$this->formDigitalSignaturePeople->showFromContactAddForm($object, $neededContentColumn, $neededActionColumn);
			$this->formDigitalSignaturePeople->showFromUserAddForm($object, $neededContentColumn, $neededActionColumn);
		}

		print '</table>';
		print '</div>';
	}

	/**
	 * Function to display digital signature signatory lines lines
	 * @param DigitalSignatureRequest $object Parent object of lines to display
	 * @param int $currentCheckBoxEditedId current document id which is edited
	 * @param bool $readOnlyMode - force readonly mode
	 * @param bool $permissionToAdd - boolean indicating if user can edit document
	 * @param bool $permissionToEdit - boolean indicating if user can edit document
	 * @param bool $permissionToDelete - boolean indicating if user can delete document
	 * @return void
	 */
	public function showCheckBoxLines($object, $currentCheckBoxEditedId, $readOnlyMode, $permissionToAdd, $permissionToEdit, $permissionToDelete)
	{
		global $conf, $langs;

		$listOfCheckBox = $object->availableCheckBox;
		$currentLineEdited = findObjectInArrayByProperty($listOfCheckBox, 'id', $currentCheckBoxEditedId);
		$isALineBeingEdited = (bool) $currentLineEdited;

		$userCanChangeOrder = !$readOnlyMode && !empty($permissionToEdit) && count($listOfCheckBox) > 1;
		$userCanAskToEditLine = !$isALineBeingEdited && !$readOnlyMode && !empty($permissionToEdit) && count($listOfCheckBox) > 0;
		$userCanAskToDeleteLine = !$isALineBeingEdited && !$readOnlyMode && !empty($permissionToDelete) && count($listOfCheckBox) > 0;
		$userCanAddLine = !$readOnlyMode && $permissionToAdd && !$isALineBeingEdited;

		$numberOfActionColumnPerComponentsDisplayed = array(
			'show'=>count($listOfCheckBox) > 0 ? count(array_filter(array($userCanAskToEditLine, $userCanAskToDeleteLine, $userCanChangeOrder))) : null,
			'showEditForm'=> $isALineBeingEdited ? count(array_filter(array(true, $userCanChangeOrder))) : null,
			'showAddForm' => $userCanAddLine ? 1 : null,
		);
		$neededActionColumn = max(array_values(array_filter($numberOfActionColumnPerComponentsDisplayed)));

		print '<div class="div-table-responsive-no-min">';
		print '<div class="titre"><h3>' . $langs->trans('DigitalSignatureManagerCheckBoxList') . '</h3></div>';
		print '<table id="' . self::DIGITALSIGNATURECHECKBOX_TABLEID . '" class="noborder noshadow tabBar" width="100%">';

		//display people lines header

		print '<tr class="liste_titre nodrag nodrop">';

		if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
		}
		print $this->formDigitalSignatureManager->getColumnTitle(
			$langs->trans('DigitalSignatureRequestCheckBoxLabel')
		);

		$nbOfActionColumn = 0;
		if($userCanAskToEditLine) {
			print '<td class="linecoledit" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		if($userCanAskToDeleteLine) {
			print '<td class="linecoldelete" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}

		if($userCanChangeOrder) {
			print '<td class="linecolmove" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}
		for($i = $nbOfActionColumn; $i < $neededActionColumn; $i++) {
			print '<td class="linecoledit" style="width: 10px"></td>';
			$nbOfActionColumn++;
		}
		print '</tr>';

		$nbOfContentColumn = 1;

		if (!empty($conf->use_javascript_ajax) && $userCanChangeOrder) {
			//toDo ajust param for ajaxrow.tpl.php
			$IdOfTableDisplayingRowToBeSorted = self::DIGITALSIGNATURECHECKBOX_TABLEID;
			$elementType = $this->formDigitalSignatureCheckBox->elementObjectStatic->element;
			include dol_buildpath('digitalsignaturemanager/tpl/ajax/ajaxrow.tpl.php');
		}

		//we display digital signature people
		foreach($listOfCheckBox as $checkBox) {
			if($checkBox->id != $currentCheckBoxEditedId) {
				$this->formDigitalSignatureCheckBox->show($checkBox, $userCanAskToEditLine, $userCanAskToDeleteLine, $userCanChangeOrder, $neededActionColumn);
			}
			else {
				//We display the form to edit line
				$this->formDigitalSignatureCheckBox->showEditForm($object, $checkBox, $nbOfContentColumn, $neededActionColumn);
			}
		}
		//We display form to add new document
		if ($userCanAddLine)
		{
			$this->formDigitalSignatureCheckBox->showAddForm($object, $nbOfContentColumn, $neededActionColumn);
		}

		print '</table>';
		print '</div>';
	}

	/**
	 * Function to display signed files of a digital signature request
	 * @param DigitalSignatureRequest $digitalSignatureRequest Digital signature request on which we should show signed files
	 * @param String $urlSource Url of the page displaying files
	 * @return string
	 */
	public function displayListOfSignedFiles($digitalSignatureRequest, $urlSource)
	{
		global $langs;
		return $this->formFile->showdocuments(
			'digitalsignaturemanager',
			$digitalSignatureRequest->getRelativePathForSignedFilesToModuleDirectory(),
			$digitalSignatureRequest->getAbsoluteDirectoryOfSignedFiles(),
			$urlSource,
			0,
			0,
			null,
			1,
			0,
			0,
			28,
			0,
			'',
			$langs->trans('DigitalSignatureRequestListOfSignedFiles'),
			'',
			$langs->defaultlang,
			null,
			$digitalSignatureRequest,
			0);
	}
}
