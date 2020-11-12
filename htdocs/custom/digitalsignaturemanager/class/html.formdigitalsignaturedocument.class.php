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
		print '<input type="hidden" name="max_file_size" value="' . 1024 * 1024 * 1024 . '">'; //Value must be given in B
		print '<input class="flat minwidth400" type="file" name="addDigitalSignatureDocument" accept=".pdf">';
		print '</td>';
		$colspan++;

		// Show add button
		print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="' . $numberOfActionColumns .'">';
		print '<input type="submit" class="button" value="'. $langs->trans('Add') .'">';
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
			print '<td class="linecoldelete" align="center">';
			print '<a href="' . $_SERVER["PHP_SELF"] . '?id='.$digitalSignatureRequestId . '&amp;action=' . self::DELETE_ACTION_NAME . '&amp;' . self::DOCUMENT_POST_ID_FIELD_NAME . '=' . $document->id . '">';
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
	 * @param DigitalSignatureDocument $digitalSignatureDocument Given digital signature comment to be previewed
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

	/**
	 * Function to manage delete on page which called showDocument methods
	 * @param string $action current action name on card
	 * @param DoliDB	$db DoliDb instance
	 * @param User $user User doing actions
	 * @return void
	 */
	public function manageDeleteAction($action, $db, $user)
	{
		if($action == self::DELETE_ACTION_NAME) {
			$idToDelete = GETPOST(self::DOCUMENT_POST_ID_FIELD_NAME);
			$object = new DigitalSignatureDocument($db);
			if($object->fetch($idToDelete) > 0 && $object->delete($user) > 0) {
				global $langs;
				setEventMessages($langs->trans('DigitalSignatureManagerFileSuccessfullyDeleted', $object->getDocumentName()), array());
			}
			if(!empty($object->errors) || !empty($object->error)) {
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
	public function manageAddAction($action, $digitalSignatureRequest, $user)
	{
		if($action == self::ADD_ACTION_NAME) {
			global $langs;
			$result = dol_add_file_process($digitalSignatureRequest->getUploadDirOfFilesToSign(), 0, 1, 'addDigitalSignatureDocument');
			if($result < 0) {
				setEventMessages($langs->trans('DigitalSignatureManagerErrorWhileSavingFile'), array(), 'errors');
			}
			else {
				//We have to get filename of the uploaded file
				$TFile = $_FILES['addDigitalSignatureDocument'];
				if (!is_array($TFile['name']))
				{
					foreach ($TFile as $key => &$val)
					{
						$val = array($val);
					}
				}
				$filename = $TFile['name'][0];
				//Now we  are able to find its ecm instance
				$ecmFile = DigitalSignatureDocument::getEcmInstanceOfFile($this->db, $digitalSignatureRequest->getRelativePathForFilesToSign(), $filename);
				//With ecm instance we can get ecm file id
				if($ecmFile) {
					$newDigitalSignatureDocument = new DigitalSignatureDocument($this->db);
					$newDigitalSignatureDocument->fk_digitalsignaturerequest = $digitalSignatureRequest->id;
					$newDigitalSignatureDocument->fk_ecm = $ecmFile->id;
					$newDigitalSignatureDocument->position = $newDigitalSignatureDocument::getLastPositionOfDocument($digitalSignatureRequest->documents);
					//We may add here property elements from the form
					$result = $newDigitalSignatureDocument->create($user);
					if($result<0) {
						setEventMessages($langs->trans('DigitalSignatureManagerErrorWhileAddingFileToSignatureRequest'), $newDigitalSignatureDocument->errors, 'errors');
					}
					else {
						setEventMessages($langs->trans('DigitalSignatureManagerFileSuccessfullyAddedToRequest'), array());
					}
				}
				else {
					setEventMessages($langs->trans('DigitalSignatureManagerFileCantFindIntoEcmDatabase'), array(), 'errors');
				}
			}
		}
	}
}
