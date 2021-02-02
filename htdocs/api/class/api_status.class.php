<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
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

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

use Luracast\Restler\RestException;

/**
 * API that gives the status of the Dolibarr instance.
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Status extends DolibarrApi
{
    /**
     * @var Societe $company {@type Societe}
     */
    public $company;

    // Open DSI -- add hook getBlackWhitelistOfProperties to clean datas in object -- Begin
    /**
     * Array of whitelist of properties keys to overwrite the white list of each element object used in this API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static protected $WHITELIST_OF_PROPERTIES = array(
        'contact' => array(
            "id" => '', "ref" => '', "ref_ext" => '', "civility_id" => '', "civility_code" => '', "lastname" => '', "firstname" => '',
            "address" => '', "zip" => '', "town" => '', "state_id" => '', "state_code" => '', "state" => '', "country_id" => '',
            "country_code" => '', "country" => '', "socid" => '', "socname" => '', "poste" => '', "statut" => '', "phone_pro" => '',
            "fax" => '', "phone_perso" => '', "phone_mobile" => '', "email" => '', "jabberid" => '', "skype" => '',
            "photo" => '', "priv" => '', "mail" => '', "birthday" => '', "note_public" => '', "gender" => '', "user_id" => '',
            "default_lang" => '',
        ),
        'user' => array(
            "id" => '', "employee" => '', "gender" => '', "email" => '', "skype" => '', "job" => '', "signature" => '',
            "address" => '', "zip" => '', "town" => '', "state_id" => '', "state_code" => '', "state" => '', "office_phone" => '',
            "office_fax" => '', "user_mobile" => '', "entity" => '', "datec" => '', "datem" => '', "socid" => '', "contactid" => '',
            "fk_member" => '', "fk_user" => '', "datelastlogin" => '', "datepreviouslogin" => '', "statut" => '', "photo" => '',
            "lang" => '', "users" => '', "parentof" => '', "thm" => '', "tjm" => '', "salary" => '', "salaryextra" => '',
            "weeklyhours" => '', "color" => '', "dateemployment" => '', "array_options" => '', "ref" => '', "ref_ext" => '',
            "country_id" => '', "country_code" => '', "lastname" => '', "firstname" => '', "thirdparty" => '', "rights" => '', "login" => ''
        ),
        'societe' => array(
            "entity" => '', "nom" => '', "name_alias" => '', "particulier" => '', "zip" => '', "town" => '', "status" => '',
            "state_id" => '', "state_code" => '', "state" => '', "departement_code" => '', "departement" => '', "pays" => '',
            "phone" => '', "fax" => '', "email" => '', "skype" => '', "url" => '', "barcode" => '', "idprof1" => '', "idprof2" => '',
            "idprof3" => '', "idprof4" => '', "idprof5" => '', "idprof6" => '', "prefix_comm" => '', "tva_assuj" => '', "tva_intra" => '',
            "localtax1_assuj" => '', "localtax1_value" => '', "localtax2_assuj" => '', "localtax2_value" => '', "capital" => '',
            "typent_id" => '', "typent_code" => '', "effectif" => '', "effectif_id" => '', "forme_juridique_code" => '', "forme_juridique" => '',
            "remise_percent" => '', "mode_reglement_supplier_id" => '', "cond_reglement_supplier_id" => '', "fk_prospectlevel" => '',
            "date_modification" => '', "date_creation" => '', "client" => '', "prospect" => '', "fournisseur" => '', "code_client" => '',
            "code_fournisseur" => '', "code_compta" => '', "code_compta_fournisseur" => '', "stcomm_id" => '', "statut_commercial" => '',
            "price_level" => '', "outstanding_limit" => '', "parent" => '', "default_lang" => '', "ref" => '', "ref_ext" => '',
            "logo" => '', "array_options" => '', "id" => '', "linkedObjectsIds" => '',
        ),
    );

    /**
     * Array of whitelist of properties keys to overwrite the white list of each element object used in this API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get whitelist of his object element
     *      if property is a object and this properties_name value is a array then get whitelist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get whitelist set in the array
     */
    static protected $WHITELIST_OF_PROPERTIES_LINKED_OBJECT = array();

    /**
     * Array of blacklist of properties keys to overwrite the blacklist of each element object used in this API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $BLACKLIST_OF_PROPERTIES = array();

    /**
     * Array of blacklist of properties keys to overwrite the blacklist of each element object when is a linked object used in this API
     * @var  array
     *      array('element_type' => array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...), ...)
     *      if property is a object and this properties_name value is not a array then get blacklist of his object element
     *      if property is a object and this properties_name value is a array then get blacklist set in the array
     *      if property is a array and this properties_name value is not a array then get all values
     *      if property is a array and this properties_name value is a array then get blacklist set in the array
     */
    static protected $BLACKLIST_OF_PROPERTIES_LINKED_OBJECT = array();

    /**
     * @var array   $BLACKWHITELIST_OF_PROPERTIES_LOADED      List of element type who is loaded
     */
    static protected $BLACKWHITELIST_OF_PROPERTIES_LOADED = array();
    // Open DSI -- add hook getBlackWhitelistOfProperties to clean datas in object -- End

    /**
     * Constructor
     */
    function __construct()
    {
        global $db, $conf;
        $this->db = $db;

        require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        $this->company = new Societe($this->db);

        require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
        require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

        $this->contact = new Contact($this->db);
    }


    /**
     * Get status (Dolibarr version)
     *
     *  @param boolean		       $getcontact		        Get details of contact (true or false)
     *  @param boolean		       $getthirdparty		       Get details of thirdparty (true or false)
     *
     *  @throws 	RestException
     */
	function index($getcontact = false, $getthirdparty = false)
    {
        global $conf;
        // Open DSI -- add hook getBlackWhitelistOfProperties to clean datas in object -- Begin
        //$info_user = $this->_cleanObjectDatas(DolibarrApiAccess::$user);
		$info_user = clone DolibarrApiAccess::$user;
        $info_user = $this->_cleanObjectData($info_user);
        // Open DSI -- add hook getBlackWhitelistOfProperties to clean datas in object -- End
        $return = array(
            'success' => array(
                'code' => 200,
                'dolibarr_version' => DOL_VERSION
            ),
            'user' => $info_user
        );

        if ($getthirdparty == true) {
            $socid = $info_user->socid;

            if (!DolibarrApiAccess::$user->rights->societe->lire) {
                throw new RestException(401, 'No permission to read thirdparty');
            }

            $result = $this->company->fetch($socid);
            if (!$result) {
                throw new RestException(404, 'Thirdparty not found');
            }

            if (!DolibarrApi::_checkAccessToResource('societe', $this->company->id)) {
                throw new RestException(401, 'Access Thirdparty not allowed for login ' . DolibarrApiAccess::$user->login);
            }

            // Open DSI -- add hook getBlackWhitelistOfProperties to clean datas in object -- Begin
            //$return['thirdparty'] = $this->_cleanObjectDatas($this->company);
            $return['thirdparty'] = $this->_cleanObjectData($this->company);
            // Open DSI -- add hook getBlackWhitelistOfProperties to clean datas in object -- End
        }

        if ($getcontact == true) {
            $contactid = $info_user->contact_id;

            if (!DolibarrApiAccess::$user->rights->societe->contact->lire) {
                throw new RestException(401, 'No permission to read contacts');
            }

            $result = $this->contact->fetch($contactid);
            if (!$result) {
                throw new RestException(404, 'Contact not found');
            }

            // Open DSI -- add hook getBlackWhitelistOfProperties to clean datas in object -- Begin
            //$return['contact'] = $this->_cleanObjectDatas($this->contact);
            $return['contact'] = $this->_cleanObjectData($this->contact);
            // Open DSI -- add hook getBlackWhitelistOfProperties to clean datas in object -- End
        }

        //Function to get Welcome Message
        if (DolibarrApiAccess::$user->rights->synergiestech->api->welcomeMessage) {
                $conf->global->MAIN_MOTD = preg_replace('/<br(\s[\sa-zA-Z_="]*)?\/?>/i', '<br>', $conf->global->MAIN_MOTD);
                    $texttoshow = $conf->global->MAIN_MOTD ?? "";
                    $texttoshow = dol_html_entity_decode($texttoshow,ENT_QUOTES);
                    $texttoshow = dol_htmlentitiesbr_decode($texttoshow);
                    $return["welcomeMessage"] = $texttoshow;
        }
        return $return;
    }


    // Open DSI -- add hook getBlackWhitelistOfProperties to clean datas in object -- Begin
    /*******************************************************************************************************************
     * Tools functions
     ******************************************************************************************************************/

    /**
     *  Clean sensible object data
     *
     * @param   object|array    $object                     Object to clean
     * @param   array           $whitelist_of_properties    Whitelist of properties
     * @param   array           $blacklist_of_properties    Blacklist of properties
     *
     * @return  object|array                                Array of cleaned object properties
     *
     * @throws  500             RestException               Error while retrieve the custom whitelist of properties for the object type
     */
    function _cleanObjectData(&$object, $whitelist_of_properties = array(), $blacklist_of_properties = array())
    {
        if (!empty($object->element)) {
            $this->_getBlackWhitelistOfProperties($object, $whitelist_of_properties, $blacklist_of_properties);
        }

        if (!is_array($whitelist_of_properties)) $whitelist_of_properties = array();
        $has_whitelist = count($whitelist_of_properties) > 0 && !isset($whitelist_of_properties['']);
        if (!is_array($blacklist_of_properties)) $blacklist_of_properties = array();
        $has_blacklist = count($blacklist_of_properties) > 0 && !isset($blacklist_of_properties['']);
        foreach ($object as $k => $v) {
            if (($has_whitelist && !isset($whitelist_of_properties[$k])) || ($has_blacklist && isset($blacklist_of_properties[$k]) && !is_array($blacklist_of_properties[$k]))) {
                if (is_array($object))
                    unset($object[$k]);
                else
                    unset($object->$k);
            } else {
                if (is_object($v) || is_array($v)) {
                    if (is_array($object))
                        $this->_cleanSubObjectData($object[$k], $whitelist_of_properties[$k], $blacklist_of_properties[$k]);
                    else
                        $this->_cleanSubObjectData($object->$k, $whitelist_of_properties[$k], $blacklist_of_properties[$k]);
                }
            }
        }

        return $object;
    }

    /**
     *  Clean sensible linked object data
     *
     * @param   object|array    $object                     Object to clean
     * @param   array           $whitelist_of_properties    Whitelist of properties
     * @param   array           $blacklist_of_properties    Blacklist of properties
     *
     * @return  object|array                                Array of cleaned object properties
     *
     * @throws  500             RestException               Error while retrieve the custom whitelist of properties for the object type
     */
    function _cleanSubObjectData(&$object, $whitelist_of_properties = array(), $blacklist_of_properties = array())
    {
        if (!empty($object->element)) {
            $this->_getBlackWhitelistOfProperties($object, $whitelist_of_properties, $blacklist_of_properties, true);
        }

        if (!is_array($whitelist_of_properties)) $whitelist_of_properties = array();
        $has_whitelist = count($whitelist_of_properties) > 0 && !isset($whitelist_of_properties['']);
        if (!is_array($blacklist_of_properties)) $blacklist_of_properties = array();
        $has_blacklist = count($blacklist_of_properties) > 0 && !isset($blacklist_of_properties['']);
        foreach ($object as $k => $v) {
            if (($has_whitelist && !isset($whitelist_of_properties[$k])) || ($has_blacklist && isset($blacklist_of_properties[$k]) && !is_array($blacklist_of_properties[$k]))) {
                if (is_array($object))
                    unset($object[$k]);
                else
                    unset($object->$k);
            } else {
                if (is_object($v) || is_array($v)) {
                    if (is_array($object))
                        $this->_cleanSubObjectData($object[$k], $whitelist_of_properties[$k], $blacklist_of_properties[$k]);
                    else
                        $this->_cleanSubObjectData($object->$k, $whitelist_of_properties[$k], $blacklist_of_properties[$k]);
                }
            }
        }

        return $object;
    }

    /**
     *  Get a array of whitelist of properties keys for this object or linked object
     *
     * @param   object      $object                     Object to clean
     * @param   boolean     $linked_object              This object is a linked object
     * @param   array       $whitelist_of_properties    Array of whitelist of properties keys for this object
     *                                                      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *                                                      if property is a object and this properties_name value is equal '' then get whitelist of his object element
     *                                                      if property is a object and this properties_name value is a array then get whitelist set in the array
     *                                                      if property is a array and this properties_name value is equal '' then get all values
     *                                                      if property is a array and this properties_name value is a array then get whitelist set in the array
     * @param   array       $blacklist_of_properties    Array of blacklist of properties keys for this object
     *                                                      array('properties_name'=> '' or array('properties_name'=> '' or array(...), ...)
     *                                                      if property is a object and this properties_name value is equal '' then get blacklist of his object element
     *                                                      if property is a object and this properties_name value is a array then get blacklist set in the array
     *                                                      if property is a array and this properties_name value is equal '' then get all values
     *                                                      if property is a array and this properties_name value is a array then get blacklist set in the array
     *
     * @return void
     *
     * @throws  500         RestException       Error while retrieve the custom whitelist of properties for the object type
     */
    function _getBlackWhitelistOfProperties($object, &$whitelist_of_properties, &$blacklist_of_properties, $linked_object = false)
    {
        global $hookmanager;

        $whitelist_of_properties = array();
        $whitelist_of_properties_linked_object = array();
        $blacklist_of_properties = array();
        $blacklist_of_properties_linked_object = array();

        if (!empty($object->element)) {
            // Load white list for clean sensitive properties of the objects
            if (!isset(self::$BLACKWHITELIST_OF_PROPERTIES_LOADED[$object->element])) {
                $object_class = get_class($object);

                // Whitelist
                if (!empty(self::$WHITELIST_OF_PROPERTIES[$object->element]))
                    $whitelist_of_properties = self::$WHITELIST_OF_PROPERTIES[$object->element];
                elseif (!empty($object_class::$API_WHITELIST_OF_PROPERTIES))
                    $whitelist_of_properties = $object_class::$API_WHITELIST_OF_PROPERTIES;

                if (!empty(self::$WHITELIST_OF_PROPERTIES_LINKED_OBJECT[$object->element]))
                    $whitelist_of_properties_linked_object = self::$WHITELIST_OF_PROPERTIES_LINKED_OBJECT[$object->element];
                elseif (!empty($object_class::$API_WHITELIST_OF_PROPERTIES_LINKED_OBJECT))
                    $whitelist_of_properties_linked_object = $object_class::$API_WHITELIST_OF_PROPERTIES_LINKED_OBJECT;

                // Blacklist
                if (!empty(self::$BLACKLIST_OF_PROPERTIES[$object->element]))
                    $blacklist_of_properties = self::$BLACKLIST_OF_PROPERTIES[$object->element];
                elseif (!empty($object_class::$API_BLACKLIST_OF_PROPERTIES))
                    $blacklist_of_properties = $object_class::$API_BLACKLIST_OF_PROPERTIES;

                if (!empty(self::$BLACKLIST_OF_PROPERTIES_LINKED_OBJECT[$object->element]))
                    $blacklist_of_properties_linked_object = self::$BLACKLIST_OF_PROPERTIES_LINKED_OBJECT[$object->element];
                elseif (!empty($object_class::$API_BLACKLIST_OF_PROPERTIES_LINKED_OBJECT))
                    $blacklist_of_properties_linked_object = $object_class::$API_BLACKLIST_OF_PROPERTIES_LINKED_OBJECT;

                // Modification by hook
                $hookmanager->initHooks(array('globalapi'));
                $parameters = array(
                    'whitelist_of_properties' => &$whitelist_of_properties, 'whitelist_of_properties_linked_object' => &$whitelist_of_properties_linked_object,
                    'blacklist_of_properties' => &$blacklist_of_properties, 'blacklist_of_properties_linked_object' => &$blacklist_of_properties_linked_object
                );
                $reshook = $hookmanager->executeHooks('getBlackWhitelistOfProperties', $parameters, $object); // Note that $action and $object may have been
                if ($reshook < 0) {
                    throw new RestException(500, "Error while retrieve the custom blacklist and whitelist of properties for the object type: " . $object->element, ['details' => $this->_getErrors($hookmanager)]);
                }

                if (empty($whitelist_of_properties_linked_object)) $whitelist_of_properties_linked_object = $whitelist_of_properties;
                if (empty($blacklist_of_properties_linked_object)) $blacklist_of_properties_linked_object = $blacklist_of_properties;

                self::$WHITELIST_OF_PROPERTIES[$object->element] = $whitelist_of_properties;
                self::$WHITELIST_OF_PROPERTIES_LINKED_OBJECT[$object->element] = $whitelist_of_properties_linked_object;
                self::$BLACKLIST_OF_PROPERTIES[$object->element] = $blacklist_of_properties;
                self::$BLACKLIST_OF_PROPERTIES_LINKED_OBJECT[$object->element] = $blacklist_of_properties_linked_object;

                self::$BLACKWHITELIST_OF_PROPERTIES_LOADED[$object->element] = true;
            }
            // Get white list
            elseif (isset(self::$WHITELIST_OF_PROPERTIES[$object->element])) {
                $whitelist_of_properties = self::$WHITELIST_OF_PROPERTIES[$object->element];
                $whitelist_of_properties_linked_object = self::$WHITELIST_OF_PROPERTIES_LINKED_OBJECT[$object->element];
                if (empty($whitelist_of_properties_linked_object)) $whitelist_of_properties_linked_object = $whitelist_of_properties;

                $blacklist_of_properties = self::$BLACKLIST_OF_PROPERTIES[$object->element];
                $blacklist_of_properties_linked_object = self::$BLACKLIST_OF_PROPERTIES_LINKED_OBJECT[$object->element];
                if (empty($blacklist_of_properties_linked_object)) $blacklist_of_properties_linked_object = $blacklist_of_properties;
            }
        }

        $whitelist_of_properties = $linked_object ? $whitelist_of_properties_linked_object : $whitelist_of_properties;
        $blacklist_of_properties = $linked_object ? $blacklist_of_properties_linked_object : $blacklist_of_properties;
    }

    /**
     * Get all errors
     *
     * @param  object   $object     Object
     *
     * @return array                Array of errors
     */
    function _getErrors(&$object)
    {
        $errors = is_array($object->errors) ? $object->errors : array();
        $errors = array_merge($errors, (!empty($object->error) ? array($object->error) : array()));

        function convert($item)
        {
            return dol_htmlentitiesbr_decode($item);
        }

        $errors = array_map('convert', $errors);

        return $errors;
    }
    // Open DSI -- add hook getBlackWhitelistOfProperties to clean datas in object -- End
}
