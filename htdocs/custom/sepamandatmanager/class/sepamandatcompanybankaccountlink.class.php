<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2020-2021 Alexis LAURIER <contact@alexislaurier.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/sepamandat.class.php
 * \ingroup     sepamandatmanager
 * \brief       This file is a CRUD class file for Sepamandat (Create/Read/Update/Delete)
 */

require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
dol_include_once('/sepamandatmanager/class/sepamandat.class.php');
dol_include_once('/societe/class/companybankaccount.class.php');

/**
 * Class to manage links between sepa manda and company bank account
 */
class SepaMandatCompanyBankAccountLink
{

	/**
	 * @var DoliDb		Database handler (result of a new DoliDB)
	 */
	public $db;

	/**
	 * @var SepaMandat  Instance of the dolibarr object to sign
	 */
	public $object;

	/**
	 * @var string[] array of errors
	 */
	public $errors = array();

	/**
	 * @var string[] array to convert mandat type to companybankaccount frstrecur type
	*/
	private static $mandatSepaTypeToFrstrecurValue = array();

	/**
	 * Constructor
	 *
	 * @param   SepaMandat $object from dolibarr to be handle
	 */
	public function __construct(&$object)
	{
		$this->db = $object->db;
		$this->object = $object;
		self::$mandatSepaTypeToFrstrecurValue[SepaMandat::TYPE_PUNCTUAL] = "FRST";
		self::$mandatSepaTypeToFrstrecurValue[SepaMandat::TYPE_RECURRENT] = "RECUR";
	}

	/**
	 * Function to create linked company bank account of a sepa mandate
	 * @param User $user user requesting action
	 * @return CompanyBankAccount|null
	 */
	public function createLinkedCompanyBankAccount($user)
	{
		global $langs;
		$this->db->begin();
		$errors = array();
		$companyAccount = new CompanyBankAccount($this->db);
		$companyAccount->iban = $this->object->iban;
		$companyAccount->socid = $this->object->fk_soc;
		$companyAccount->bic = $this->object->bic;
		$companyAccount->date_rum = $this->object->date_rum;
		$companyAccount->date_signature = $this->object->date_rum;
		$companyAccount->label = $this->object->getNomUrl(1, null, 1);
		$companyAccount->bank = $langs->trans("SepaMandateCompanyAccountName", $this->object->ref);
		$companyAccount->rum = $this->object->rum;
		$companyAccount->frstrecur = self::$mandatSepaTypeToFrstrecurValue[$this->object->type];
		if ($companyAccount->create($user) > 0 && $companyAccount->update($user) > 0) {
			$this->object->fk_companybankaccount = $companyAccount->id;
			$this->object->update($user, true);
		} else {
			$errors[] = $langs->trans('SepaMandatErrorWhenUpdatingCompanyBankAccount');
		}
		$errors = array_merge($errors, $companyAccount->errors, $this->object->errors);
		if (empty($errors)) {
			$this->db->commit();
			return $companyAccount;
		} else {
			$this->db->rollback();
			return null;
		}
	}

	/**
	 * Function to get all sepa mandates for this company
	 * @param int $socId
	 * @return SepaMandate[] | int
	 */
	public function getSignedSepaMandatesOfThisCompany($socId)
	{
		return $this->object->fetchAll("DESC", "rowid", null, null, array('fk_soc' => $socId, 'status' => $this->object::STATUS_SIGNED));
	}

	/**
	 * Function to delete linked company bank account
	 * @param User $user user requesting action
	 * @return boolean true if all linked account successfully deleted or nothing to delete
	 */
	public function deleteLinkedCompanyBankAccount($user)
	{
		$result = false;
		$companyAccount = new CompanyBankAccount($this->db);
		if ($this->object->fk_companybankaccount > 0 && $companyAccount->fetch($this->object->fk_companybankaccount) > 0 && $companyAccount->id > 0) {
			if ($companyAccount->delete($user) > 0) {
				$this->object->fk_companybankaccount = null;
				if ($this->object->update($user) > 0) {
					//We try to find a sepa mandat for this company if we delete default rib for this company
					if (!$companyAccount->default_rib) {
						$result = true;
					} else {
						//mandates for this company
						$otherMandates = $this->getSignedSepaMandatesOfThisCompany($companyAccount->socid);
						if (is_array($otherMandates)) {
							if (empty($otherMandates)) {
								$result = true;
							} else {
								$firstElement = array_shift($otherMandates);
								$result = $companyAccount->setAsDefault($firstElement->fk_companybankaccount) > 0;
							}
						}
					}
				}
			}
		} else {
			$result = true; //nothing to delete
		}
		return $result;
	}

	/**
	 * Function to get Sepa Mandate linked to a company bank account
	 * @param DoliDB $db Database instance to use
	 * @param int $companyBankAccountId researched company bank account
	 * @return SepaMandat|int
	 */
	public static function getSepaMandatesLinkToACompanyAccount($db, $companyBankAccountId)
	{
		$element = new SepaMandat($db);
		return $element->fetchAll("DESC", "rowid", null, null, array('fk_companybankaccount' => $companyBankAccountId));
	}

	/**
	 * Function to know if a company account is managed by a sepa mandate
	 * @param DoliDB $db Database instance to use
	 * @param int $companyBankAccountId researched company bank account
	 * @return bool
	 */
	public static function isAMandateLinkedToThisCompanyAccountId($db, $companyBankAccountId)
	{
		$sepaMandates = self::getSepaMandatesLinkToACompanyAccount($db, $companyBankAccountId);
		return is_array($sepaMandates) && count($sepaMandates) > 0 ? true : false;
	}

	/**
	 * Function to get a list of company bank account of a company
	 * @param DoliDB $db database instance to use
	 * @param int $companyId Company id to check
	 * @return CompanyBankAccount[]|null
	 */
	public static function getCompanyBankAccounts($db, $companyId)
	{
		$company = new Societe($db);
		$company->id = $companyId;
		$listOfRib = $company->get_all_rib();
		if (!is_array($listOfRib) || !empty($company->errors)) {
			return null;
		} else {
			return $listOfRib;
		}
	}

	/**
	 * Function to get list of unique iban - bic value for a company id
	 * @param DoliDB $db database instance to use
	 * @param int $companyId Company id to check
	 * @return CompanyBankAccount[]|null
	 */
	public static function getIbanBicUniqueNumberForACompany($db, $companyId)
	{
		$ibanCrudeList = array();
		$listOfAccount = self::getCompanyBankAccounts($db, $companyId);
		if ($listOfAccount) {
			foreach ($listOfAccount as $account) {
				if (empty(self::checkAccountInformation($db, $account->iban, $account->bic))) {
					$ibanCrudeList[$account->id] = "IBAN : " . $account->iban . " - BIC : " . $account->bic;
				}
			}
		} else {
			return null;
		}
		return array_unique($ibanCrudeList);
	}

	/**
	 * Function to know if account information are valid
	 * @param DoliDB $db database instance to use
	 * @param string $iban iban value to check
	 * @param string $bic bic value to check
	 * @return string[] array of errors
	 */
	public static function checkAccountInformation($db, $iban, $bic)
	{
		$staticElement = new SepaMandat($db);
		$staticElement->iban = $iban;
		$staticElement->bic = $bic;
		return array_merge($staticElement->checkIbanValue(), $staticElement->checkBicValue());
	}

	/**
	 * Function to know if account information are valid into company bank account
	 * @param DoliDB $db database instance to use
	 * @param int $id id of the bank account to check
	 * @return string[] array of errors
	 */
	public static function checkAccountInformationFromId($db, $id)
	{
		$staticElement = new CompanyBankAccount($db);
		$staticElement->fetch($id);
		return self::checkAccountInformation($db, $staticElement->iban, $staticElement->bic);
	}
}
