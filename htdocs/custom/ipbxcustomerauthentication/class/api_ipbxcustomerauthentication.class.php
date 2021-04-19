<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2021 Alexis LAURIER <contact@alexislaurier.fr>
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

use Luracast\Restler\RestException;

dol_include_once("/ipbxcustomerauthentication/class/extendedSociete.class.php");
dol_include_once("/ipbxcustomerauthentication/class/extendedContact.class.php");
dol_include_once('/ipbxcustomerauthentication/core/dictionaries/ipbxcustomertype.dictionary.php');

/**
 * \file    ipbxcustomerauthentication/class/api_ipbxcustomerauthentication.class.php
 * \ingroup ipbxcustomerauthentication
 * \brief   File for API management of ipbxcustomerauthentication.
 */

/**
 * API class for ipbxcustomerauthentication ipbxcustomerauthentication
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class IpbxCustomerAuthenticationApi extends DolibarrApi
{
    /**
     * @var ExtendedSociete static instance to work with thirdparties
     */
    public $thirdparty;

    /**
     * @var ExtendedContact static instance to work with contact
     */
    public $contact;

    /**
     * @var array[] static cache of customer type dictionary
     */
    static public $cache_customer_dictionary_type;

    /**
     * @var int Number of digit to check on a phone number
     */
    const NUMBER_OF_PHONE_NUMBER_DIGITS = 9;

    /**
     * @var int Number of digit to check on a phone number
     */
    const NUMBER_OF_CUSTOMER_CODE_DIGITS = 4;

    /**
     * Constructor
     *
     * @url     GET /
     *
     */
    public function __construct()
    {
        global $db;
        $this->db = $db;
        $this->thirdparty = new ExtendedSociete($this->db);
        $this->contact = new ExtendedContact($this->db);
        global $hookmanager;
        $hookmanager->initHooks(array('globalapi', 'ipbxcustomerauthenticationapi'));
    }

    /**
     * Get properties of a ipbxcustomerauthentication object
     *
     * Return an array with ipbxcustomerauthentication information
     *
     * @param   string     $phoneNumber incoming phone number
     * @param   string     $customerCode incoming given customer code from ipbx
     * @return  int
     *
     * @url GET getCustomerId
     *
     */
    public function getCustomerId($phoneNumber = null, $customerCode = null)
    {
        if (!DolibarrApiAccess::$user->rights->ipbxcustomerauthentication->ipbxcustomerauthentication->getCustomerId) {
            throw new RestException(401);
        }

        if (strlen($phoneNumber) < self::NUMBER_OF_PHONE_NUMBER_DIGITS && strlen($customerCode) < self::NUMBER_OF_CUSTOMER_CODE_DIGITS) {
            throw new RestException(400, 'Bad Request, wrong value provided for phoneNumber or customerCode');
        }
        $thirdpartyIds = array();
        if (strlen($phoneNumber) >= self::NUMBER_OF_PHONE_NUMBER_DIGITS) {
            $truncatedPhoneNumber = substr($phoneNumber, -self::NUMBER_OF_PHONE_NUMBER_DIGITS);
            $thirdpartyIds = array_unique(array_merge(
                $thirdpartyIds,
                $this->getThirdpartyIdsByPhoneNumber($phoneNumber, $truncatedPhoneNumber),
                $this->getThirdpartyIdsByLinkedContactPhoneNumber($phoneNumber, $truncatedPhoneNumber)
            ));
        }

        if (strlen($customerCode) >= self::NUMBER_OF_CUSTOMER_CODE_DIGITS) {
            $thirdpartyIds = array_merge(
                $thirdpartyIds,
                $this->getThirdpartyIdsByCustomerCode($customerCode)
            );
        }

        if (!empty($thirdpartyIds)) {
            return array_pop(array_reverse($thirdpartyIds));
        } else {
            throw new RestException(404, 'No Customer found with this phone number');
        }
    }

    /**
     * Get properties of a ipbxcustomerauthentication object
     *
     * Return an array with ipbxcustomerauthentication information
     *
     * @param   int     $customerId ID of the customer (third party)
     * @return  string
     *
     * @url GET getCustomerType
     */
    public function getCustomerType($customerId)
    {
        global $hookmanager;
        if (!DolibarrApiAccess::$user->rights->ipbxcustomerauthentication->ipbxcustomerauthentication->getCustomerType) {
            throw new RestException(401);
        }

        if ($customerId <= 0) {
            throw new RestException(400, 'Bad request, parameter customerId in incorrect range');
        }
        if ($this->thirdparty->fetch($customerId) <= 0) {
            throw new RestException(404, 'No thirdparty found with Id ' . $customerId);
        }
        //We try to find a third party tag matching a dictionary entry
        $thirdPartyCategoryIds = $this->thirdparty->getLinkedCategoryIds(IpbxCustomerTypeDictionary::CATEGORY_TYPES);
        $dictionaryOfCustomerType = $this->getCustomerTypeDictionary();
        $numberOfMatchForCustomerType = array();
        foreach ($dictionaryOfCustomerType as $rowid => $customerType) {
            $numberOfCommonTag = count(array_intersect($customerType->thirdparty_tag, $thirdPartyCategoryIds));
            if ($numberOfCommonTag > 0) {
                $numberOfMatchForCustomerType = array_pad($numberOfMatchForCustomerType, count($numberOfMatchForCustomerType) + $numberOfCommonTag, $rowid);
            }
        }
        //We call a hook to check if some external module may modify effective market type probability array
        $parameters = array(
            'customerTypeDictionary' => &$dictionaryOfCustomerType,
            'numberOfMatchForCustomerType' => &$numberOfMatchForCustomerType,
            'customerId' => $customerId
        );
        $reshook = $hookmanager->executeHooks('buildCustomerTypeProbability', $parameters, $this->thirdparty);
        if ($reshook == 0) {
            $numberOfMatchForCustomerType = array_merge($numberOfMatchForCustomerType, $hookmanager->resArray);
        } elseif ($reshook > 0) {
            $numberOfMatchForCustomerType = $hookmanager->resArray;
        }

        $frequencyOfEachCustomerType = array_count_values($numberOfMatchForCustomerType);

        $customerTypeId = null;
        if (!empty($frequencyOfEachCustomerType)) {
            $customerTypeId = array_search(
                max($frequencyOfEachCustomerType),
                $frequencyOfEachCustomerType
            );
        }
        if ($customerTypeId) {
            $finalCustomerTypeDictionaryEntry = $dictionaryOfCustomerType[$customerTypeId];
        }
        if (is_object($finalCustomerTypeDictionaryEntry)) {
            return $finalCustomerTypeDictionaryEntry->ipbxvalue;
        } else {
            throw new RestException(404, 'No Customer type for this customer');
        }
    }

    /**
     * Function to deeply compare phone number with different syntax
     * @param int $phoneA first phone number to compare
     * @param int $phoneB seconde phone number to compare
     * @return bool
     */
    public static function isEqualPhoneNumber($phoneA, $phoneB)
    {
        if ($phoneA == $phoneB) {
            return true;
        }

        $phoneA = self::cleanPhoneNumber($phoneA);
        $phoneB = self::cleanPhoneNumber($phoneB);
        // remove "0", "+" from the beginning of the numbers
        if ($phoneA[0] == '0' || $phoneB[0] == '0' ||
            $phoneA[0] == '+' || $phoneB[0] == '+'
        ) {
            return self::isEqualPhoneNumber(ltrim($phoneA, '0+'), ltrim($phoneB, '0+'));
        }

        // change numbers if second is longer
        if (strlen($phoneA) < strlen($phoneB)) {
            return self::isEqualPhoneNumber($phoneB, $phoneA);
        }

        if (strlen($phoneB) < self::NUMBER_OF_PHONE_NUMBER_DIGITS) {
            return false;
        }

        // is second number a first number ending
        $position = strrpos($phoneA, $phoneB);
        if ($position !== false && ($position + strlen($phoneB) === strlen($phoneA))) {
            return true;
        }

        return false;
    }

    /**
     * Function to clean phone number from unwanted characters
     * @param string $phoneNumber phone number to clean
     * @return string
     */
    private static function cleanPhoneNumber($phoneNumber)
    {
		return preg_replace('/[^0-9]/i', '', $phoneNumber);
    }

    /**
     * Function to get matching thirdparty Id according to a phone number
     * @param int $phoneNumber real phone number
     * @param int $truncatedPhoneNumber truncated phone number to reduce number of check done thanks to a more precise sql request
     * @return int[]
     */
    private function getThirdpartyIdsByPhoneNumber($phoneNumber, $truncatedPhoneNumber)
    {
        $sqlFilter = array();
        $phoneFieldOnSociete = array('phone', 'fax');
        $sqlFilterForPhone = array();
        foreach ($phoneFieldOnSociete as $field) {
        	$sqlFilterForPhone[] = 'REGEXP_REPLACE(' . $field . ',"[^0-9]+", "")' . ' LIKE "%' . $truncatedPhoneNumber .'"';
        }
        $sqlFilter[] = implode(' OR ', $sqlFilterForPhone);
        $sqlFilter[] = 'client = 1 OR fournisseur = 1';
        $finalSqlFilter = '(' . implode(' ) AND (', $sqlFilter) . ')';
        $possibleThirdparty = $this->thirdparty->fetchAll('DESC', 'rowid', 0, 0, array('customsql' => $finalSqlFilter));
        $thirdpartyMatchingPhoneNumber = array();
        foreach ($possibleThirdparty as $thirdparty) {
            foreach ($phoneFieldOnSociete as $phoneField) {
                if (self::isEqualPhoneNumber($phoneNumber, $thirdparty->$phoneField)) {
                    $thirdpartyMatchingPhoneNumber[] = $thirdparty->id;
                }
            }
        }
        return array_unique(array_filter($thirdpartyMatchingPhoneNumber));
    }

    /**
     * Function to get matching thirdparty Id according to a phone number
     * @param int $phoneNumber real phone number
     * @param int $truncatedPhoneNumber truncated phone number to reduce number of check done thanks to a more precise sql request
     * @return int[]
     */
    private function getThirdpartyIdsByLinkedContactPhoneNumber($phoneNumber, $truncatedPhoneNumber)
    {
        $sqlFilter = array();
        $phoneFieldOnContact = array('phone', 'phone_perso', 'phone_mobile', 'fax');
        $sqlFilterForPhone = array();
        foreach ($phoneFieldOnContact as $field) {
        	$sqlFilterForPhone[] = 'REGEXP_REPLACE(' . $field . ',"[^0-9]+", "")' . ' LIKE "%' . $truncatedPhoneNumber .'"';
        }
        $sqlFilter[] = implode(' OR ', $sqlFilterForPhone);
        $sqlFilter[] = 'statut = 1';
        $finalSqlFilter = '(' . implode(' ) AND (', $sqlFilter) . ')';
        $possibleContacts = $this->contact->fetchAll('DESC', 'rowid', 0, 0, array('customsql' => $finalSqlFilter));
        $thirdpartyMatchingPhoneNumber = array();
        foreach ($possibleContacts as $contact) {
            foreach ($phoneFieldOnContact as $phoneField) {
                if (self::isEqualPhoneNumber($phoneNumber, $contact->$phoneField)) {
                    $thirdpartyMatchingPhoneNumber[] = $contact->fk_soc;
                }
            }
        }
        return array_unique(array_filter($thirdpartyMatchingPhoneNumber));
    }

    /**
     * Function to get thirdparty according to a given customer code with only digits
     * @param int $customerCodeWithOnlyDigits Customer code with only digit (came from the ipbx)
     * @return int[]
     */
    private function getThirdpartyIdsByCustomerCode($customerCodeWithOnlyDigits)
    {
        $possibleThirdparty = $this->thirdparty->fetchAll('DESC', 'rowid', 0, 0, array('customsql' => 'code_client LIKE "%' . $customerCodeWithOnlyDigits . '"'));
        $thirdpartyMatchingClientCode = array();
        foreach ($possibleThirdparty as $thirdparty) {
            $thirdpartyMatchingClientCode[] = $thirdparty->id;
        }
        return array_unique(array_filter($thirdpartyMatchingClientCode));
    }


    /**
     * Function to load customer type dictionary
     */
    private function loadCustomerTypeDictionary()
    {
        $crudeData = Dictionary::getJSONDictionary($this->db, 'ipbxcustomerauthentication', 'ipbxcustomertype');
        $dataPerRowId = array();
        foreach ($crudeData as &$data) {
            if (is_array($data)) {
                $data = (object) $data;
                $data->thirdparty_tag = explode(',', $data->thirdparty_tag);
                $dataPerRowId[$data->rowid] = $data;
                $dataPerIpbxValue[$data->ipbxvalue] = $data;
            }
        }
        self::$cache_customer_dictionary_type = $dataPerRowId;
        return $crudeData;
    }
    /**
     * Function to get customer type dictionary
     * @return object[]
     */
    private function getCustomerTypeDictionary()
    {
        if (!self::$cache_customer_dictionary_type) {
            $this->loadCustomerTypeDictionary();
        }
        return self::$cache_customer_dictionary_type;
    }
}
