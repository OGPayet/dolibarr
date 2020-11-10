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
     * @var array
     */
	public static $errors = array();

	/**
	 * @var string Delete Document Action Name
	 */
	const DELETE_ACTION_NAME = 'deleteDocument';

	/**
	 * @var string Edit Document Action Name
	 */
	const EDIT_ACTION_NAME = 'editDocument';

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
	const DOCUMENT_POST_ID_FIELD_NAME = 'documentId';

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
	}

	/**
     *  Display form to add a new document into card lines
     *
     *  @param	DigitalSignatureRequest	$object			Object
	 *  @param  DigitalSignatureDocument $document Document being edited
	 *  @param int $numberOfActionColumns number of column used by actions
     *	@return	int						<0 if KO, >=0 if OK
     */
	public function showDocumentEditForm($object, $document, $numberOfActionColumns)
	{
		global $hookmanager, $action;
		$parameters = array();
		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

		if(!is_object($document)) {
			$line = new DigitalSignatureDocument($object->db);
			$line->digitalSignatureRequest = $object;
		}
		$colspan = 0; //used for extrafields
		global $conf, $langs;
		//We display row
		print '<tr ';
		if($document->id) {
			print 'id="row-' . $document->id .'" ';
		}
		print ' class="nodrag nodrop nohoverpair liste_titre_create oddeven">';
		//We display number column
		if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
			$colspan++;
		}
		// We show upload file form
		print '<td>';
		print '<input class="flat minwidth400" type="file" name="newDocumentToSign" accept=".pdf">';
		print '</td>';
		$colspan++;

		// Show add button
		print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="' . $numberOfActionColumns .'">';
		print '<input type="submit" class="button" value="'. $langs->trans('Add') .'" name="addDocument" id="addDocument">';
		print '</td>';
		$colspan++;

		//We end row
		print '</tr>';
	}


	/**
     *  Display form to add a new document into card lines
     *
	 *  @param  DigitalSignatureDocument $document Document being edited
	 *  @param bool $userCanAskToEditLine display edit button
	 *  @param bool $userCanAskToDeleteLine display delete button
	 *  @param bool $userCanMoveLine display move button
     *  @param  string  $morecss        More css on table
     *  @param	string	$moreparambacktopage	More param for the backtopage
     *	@return	int						<0 if KO, >=0 if OK
     */
	public function showDocument($document, $userCanAskToEditLine, $userCanAskToDeleteLine, $userCanMoveLine)
	{
		$colspan = 0; //used for extrafields
		$digitalSignatureRequestId = $document->digitalSignatureRequest->id;
		$numberOfDocuments = count($document->digitalSignatureRequest->documents);
		global $conf;
		//We display row
		print '<tr id="row-' . $document->id . '" class="oddeven drag drop">';
		//We display number column
		if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
			print '<td class="linecolnum" align="center"></td>';
			$colspan++;
		}
		// We show uploaded file
		print '<td>';
		print $this->getDocumentLinkAndPreview($document);

		//print '<input class="flat minwidth400" type="file" name="newDocumentToSign" accept=".pdf">';
		print '</td>';
		$colspan++;

		// Show edit button
		if($userCanAskToEditLine) {
			print '<td class="linecoledit" align="center">';
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id='.$digitalSignatureRequestId . '&amp;action="' . self::EDIT_ACTION_NAME . '"&amp;' . self::DOCUMENT_POST_ID_FIELD_NAME . '=' . $document->id . '">';
			print img_edit();
			print '</td>';
			$colspan++;
		}

		if($userCanAskToDeleteLine) {
			print '<td class="linecoledit" align="center">';
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id='.$digitalSignatureRequestId . '&amp;action="' . self::DELETE_ACTION_NAME . '"&amp;' . self::DOCUMENT_POST_ID_FIELD_NAME . '=' . $document->id . '">';
			print img_delete();
			print '</td>';
			$colspan++;
		}

		if($userCanMoveLine) {
			$this->formDigitalSignatureManager->showMoveActionButtonsForLine($digitalSignatureRequestId, $document->id, $document->position, $numberOfDocuments, self::MOVE_UP_ACTION_NAME, self::MOVE_DOWN_ACTION_NAME, self::DOCUMENT_POST_ID_FIELD_NAME);
			$colspan++;
		}
		//We end row
		print '</tr>';
	}

	/**
	 * Function to display document file by its filename with link to download it and ability to preview it
	 * @param DigitalSignatureDocument $digitalSignatureDocument
	 * @return string
	 */
	public function getDocumentLinkAndPreview($digitalSignatureDocument)
	{
		global $conf, $langs;
		//We prepare data to use elements from form file, as done by dolibarr core
		$documentUrl = DOL_URL_ROOT.'/document.php';
		$modulePart = 'digitalsignaturemanager';
		$relativePath = $digitalSignatureDocument->getLinkedFileRelativePath();
		$fileName = $digitalSignatureDocument->getDocumentName();
		$entityOfThisDocument = $digitalSignatureDocument->getEntity() ? $digitalSignatureDocument->getEntity() : $conf->entity;
		$entityParam = '&entity=' . $entityOfThisDocument;
		$arrayWithFileInformation = array('name'=>$fileName);


		$out = '<a class="documentdownload paddingright" href="' . $documentUrl . '?modulepart=' . $modulePart . '&amp;file=' . urlencode($relativePath) . $entityParam;

		$mime = dol_mimetype($relativePath, '', 0);
		if (preg_match('/text/', $mime)) {
			$out .= ' target="_blank"';
		}
		$out .= '>';
		$out .= img_mime($fileName, $langs->trans("File").': '.$fileName);
		$out .= dol_trunc($fileName, 150);
		$out .= '</a>'."\n";
		$out .= $this->formFile->showPreview($arrayWithFileInformation, $modulePart, $relativePath, 0, $entityParam);
		$out .= '</td>';
		return $out;
	}
}
