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
dol_include_once('/sepamandatmanager/class/sepamandatcompanybankaccountlink.class.php');

/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 */
class FormSepaMandateDigitalSignatureRequest
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
	public $errors = array();

	/**
	 * @var string confirm set to sign status action name
	 */
	const ACCOUNT_ID_FIELD_NAME = 'addFromBankAccountId';

	/**
	 * @var string set to sign status action name
	 */
	const MANDATE_TYPE_FIELD_NAME = 'mandatType';

	/**
	 * @var string confirm set to sign status action name
	 */
	const FREE_IBAN_FIELD_NAME = 'freeIban';

	/**
	 * @var string set to sign status action name
	 */
	const FREE_BIC_FIELD_NAME = 'freeBic';

	/**
	 * @var string set to sign status action name
	 */
	const DIGITAL_SIGNATURE_REQUEST_REQUEST_SIGN_ACTION_NAME = 'requestSign';

	/**
	 * @var string set to sign status action name
	 */
	const DIGITAL_SIGNATURE_REQUEST_SET_SIGNERS_ACTION_NAME = 'setSigners';

	/**
	 * @var string set to sign status action name
	 */
	const DIGITAL_SIGNATURE_REQUEST_CONFIRM_SET_SIGNERS_ACTION_NAME = 'confirmSetSigners';

	/**
	 * @var string id of the temporary ecm file create with sepa mandate information
	 */
	const SEPA_MANDATE_TEMPORARY_ECM_ID = 'newIban';

	/**
	 * @var FormSepaMandate
	 */
	public $formSepaMandate;

	/**
	 * Constructor
	 *
	 * @param   DoliDB $db Database handler
	 */
	public function __construct(DoliDb $db)
	{
		$this->db = $db;
		dol_include_once('/sepamandatmanager/class/html.formsepamandate.class.php');
		$this->formSepaMandate = new FormSepaMandate($db);
		require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
		$this->form = new Form($db);
	}

	/**
	 * Function to get questions to be added on a formconfirm to create a new sepa mandate
	 * @param int $companyId Id of the company on which we ask to create a sepa mandate
	 * @return array
	 */
	public function getFormConfirmQuestionsToCreateSepaMandate($companyId)
	{
		global $langs;
		$questions = array();
		$companyAccounts = SepaMandatCompanyBankAccountLink::getIbanBicUniqueNumberForACompany($this->db, $companyId);
		if (!empty($companyAccounts)) {
			$questions[self::ACCOUNT_ID_FIELD_NAME] = array(
				'type' => 'other',
				'label' => $langs->trans('SepaMandateAddFromIban'),
				'name' => self::ACCOUNT_ID_FIELD_NAME,
				'value' => $this->formSepaMandate->getSelectComponent(self::ACCOUNT_ID_FIELD_NAME, $this->getSelectedBankAccountId(), $companyAccounts)
			);
		}
		$questions[self::FREE_IBAN_FIELD_NAME] = array(
			'type' => 'other',
			'label' => $langs->trans('SepaMandateIbanNumber'),
			'value' => '<input type="text" class="minwidth300" id="freeIbanValue" name="freeIbanValue" value="' . ($this->getFreeIban() ?? '') . '" />',
			'name' => self::FREE_IBAN_FIELD_NAME,
			'css' => 'minwidth300'
		);
		$questions[self::FREE_BIC_FIELD_NAME] = array(
			'type' => 'text',
			'label' => $langs->trans('SepaMandateBicNumber'),
			'name' => self::FREE_BIC_FIELD_NAME,
			'css' => 'minwidth300'
		);
		$questions[self::MANDATE_TYPE_FIELD_NAME] = array(
			'type' => 'other',
			'label' => $langs->trans('SepaMandateType'),
			'name' => self::MANDATE_TYPE_FIELD_NAME,
			'value' => $this->formSepaMandate->getMandatTypeSelect($this->getMandateType(), self::MANDATE_TYPE_FIELD_NAME)
		);
		return $questions;
	}
	/**
	 * Function to get selected account id from post
	 * @return int
	 */
	public function getSelectedBankAccountId()
	{
		$postContent = (int) GETPOST(self::ACCOUNT_ID_FIELD_NAME, 'integer');
		return $postContent > 0 ? $postContent : -1;
	}

	/**
	 * Function to get free iban set from post
	 * @return string|null
	 */
	public function getFreeIban()
	{
		$postContent = GETPOST(self::FREE_IBAN_FIELD_NAME);
		return empty($postContent) ? null : $postContent;
	}

	/**
	 * Function to get free bic set from post
	 * @return string|null
	 */
	public function getFreeBic()
	{
		$postContent = GETPOST(self::FREE_BIC_FIELD_NAME);
		return empty($postContent) ? null : $postContent;
	}

	/**
	 * Function to get free mandate type set from post
	 * @return int
	 */
	public function getMandateType()
	{
		$postContent = (int) GETPOST(self::MANDATE_TYPE_FIELD_NAME, 'integer');
		return $postContent > 0 ? $postContent : -1;
	}

	/**
	 * Function to keep data from previous field and send them into an hidden way for a new form
	 * @return array
	 */
	public function getHiddenQuestionToKeepSepaPostContent()
	{
		$questions = array();
		$bankAccountId = $this->getSelectedBankAccountId();
		$freeIban = $this->getFreeIban();
		$freeBic = $this->getFreeBic();
		$sepaMandateType = $this->getMandateType();
		if ($bankAccountId > 0) {
			$questions[self::ACCOUNT_ID_FIELD_NAME] = array('type' => 'hidden', 'name' => self::ACCOUNT_ID_FIELD_NAME, 'value' => $bankAccountId);
			$questions[self::MANDATE_TYPE_FIELD_NAME] = array('type' => 'hidden', 'name' => self::MANDATE_TYPE_FIELD_NAME, 'value' => $sepaMandateType);
		} elseif (!empty($freeIban) || !empty($freeBic)) {
			$questions[self::FREE_IBAN_FIELD_NAME] = array('type' => 'hidden', 'name' => self::FREE_IBAN_FIELD_NAME, 'value' => $freeBic);
			$questions[self::FREE_BIC_FIELD_NAME] = array('type' => 'hidden', 'name' => self::FREE_BIC_FIELD_NAME, 'value' => $freeBic);
			$questions[self::MANDATE_TYPE_FIELD_NAME] = array('type' => 'hidden', 'name' => self::MANDATE_TYPE_FIELD_NAME, 'value' => $sepaMandateType);
		}
		return $questions;
	}

	/**
	 * Function to create a fake sepa mandate ecm file with post information
	 * @return ExtendedEcm[]
	 */
	public function getFakeSepaMandateEcmDocuments()
	{
		global $langs, $conf;
		$fakeEcmFileToAdd = new ExtendedEcm($this->db); //It is a fake document as we will create sepa mandate only before create digital signature request
		$fakeEcmFileToAdd->filename = $langs->trans("SepaMandatLinkedObjectName");
		$fakeEcmFileToAdd->mask = !empty($conf->global->SEPAMANDATMANAGER_SEPAMANDAT_ADDON_PDF) ? $conf->global->SEPAMANDATMANAGER_SEPAMANDAT_ADDON_PDF : 'mercure';
		$fakeEcmFileToAdd->elementtype = SepaMandat::$staticElement;
		return array(self::SEPA_MANDATE_TEMPORARY_ECM_ID => $fakeEcmFileToAdd);
	}

	/**
	 * Function to know if there is some sepa mandate information into post data
	 * @return bool
	 */
	public function isThereSepaMandateInformationIntoPost()
	{
		return $this->getSelectedBankAccountId() > 0 || !empty($this->getFreeBic()) || !empty($this->getFreeIban());
	}

	/**
	 * Function to get ecm instance of the sepa mandate files to sign
	 * @param User $user user requesting action
	 * @param int $companyId Company on which create sepa mandate
	 * @return ExtendedEcm[]|null
	 */
	public function manageCreateEcmSepaMandateToSign($user, $companyId)
	{
		$errors = array();
		$bankAccountId = $this->getSelectedBankAccountId();
		$freeIban = $this->getFreeIban();
		$freeBic = $this->getFreeBic();
		$mandatType = $this->getMandateType();
		if ($bankAccountId > 0) {
			$bankAccount = new CompanyBankAccount($this->db);
			$bankAccount->fetch($bankAccountId);
			$errors += $bankAccount->errors;
			$iban = $bankAccount->iban;
			$bic = $bankAccount->bic;
		} elseif (!empty($freeIban) || !empty($freeBic)) {
			$iban = $freeIban;
			$bic = $freeBic;
		}
		$sepaMandat = new SepaMandat($this->db);
		$sepaMandat->fk_soc = $companyId;
		$sepaMandat->iban = $iban;
		$sepaMandat->bic = $bic;
		$sepaMandat->type = $mandatType;
		if (empty($errors) && $sepaMandat->create($user) > 0 && $sepaMandat->setToSign($user)) {
			$extendedEcm = new ExtendedEcm($this->db);
			$extendedEcm->fetch($sepaMandat->fk_generated_ecm);
		}
		$errors += $sepaMandat->errors;
		$this->errors += $errors;
		if (empty($errors)) {
			return array(self::SEPA_MANDATE_TEMPORARY_ECM_ID => $extendedEcm);
		} else {
			return null;
		}
	}

	/**
	 * Function to check validity of sepa mandate information that are into post data
	 * @return string[] $array of errors
	 */
	public function checkSepaMandateInformationIntoPost()
	{
		global $langs;
		if ($this->getSelectedBankAccountId() > 0 && !empty(SepaMandatCompanyBankAccountLink::checkAccountInformationFromId($this->db, $this->getSelectedBankAccountId()))) {
			$errors[] = $langs->trans('SepaMandateInvalidMandatInformationIntoBankAccount');
		} elseif ($this->getSelectedBankAccountId() <= 0 && (!empty($this->getFreeIban()) || !empty($this->getFreeBic())) && !empty(SepaMandatCompanyBankAccountLink::checkAccountInformation($this->db, $this->getFreeIban(), $this->getFreeBic()))) {
			$errors[] = $langs->trans('SepaMandateInvalidFreeMandatInformation');
		}
		return $errors;
	}
}
