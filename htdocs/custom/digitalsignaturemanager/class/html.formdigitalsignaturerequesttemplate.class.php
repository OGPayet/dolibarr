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

	public static function getListOfFilesThatCouldBeSign($relativePathToDirectoryFromDolDataRoot) {

	}
}
