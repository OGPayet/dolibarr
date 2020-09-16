<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2018      Alexis LAURIER             <alexis@alexislaurier.fr>
 * Copyright (C) 2018      Synergies-Tech             <infra@synergies-france.fr>
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

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT . '/main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

dol_include_once('/companyrelationships/class/companyrelationships.class.php');
if (!empty($conf->equipement->enabled)) {
    dol_include_once('/equipement/class/equipement.class.php');
}


/**
 * API class for Company Relationships
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class CompanyRelationshipsApi extends DolibarrApi {
    /**
     * Proposals
     * @var array   $FIELDS_PROPOSAL     Mandatory fields, checked when create and update object
     */
    static $FIELDS_PROPOSAL = array(
        'socid',
        'array_options' => array('options_companyrelationships_fk_soc_benefactor')
    );

    /**
     * Orders
     * @var array   $FIELDS_ORDER     Mandatory fields, checked when create and update object
     */
    static $FIELDS_ORDER = array(
        'socid',
        'array_options' => array('options_companyrelationships_fk_soc_benefactor')
    );

    /**
     * Invoices
     * @var array   $FIELDS_INVOICE     Mandatory fields, checked when create and update object
     */
    static $FIELDS_INVOICE = array(
        'socid',
        'array_options' => array('options_companyrelationships_fk_soc_benefactor')
    );

    /**
     * Interventions
     * @var array   $FIELDS_INTERVENTION     Mandatory fields, checked when create and update object
     */
    static $FIELDS_INTERVENTION = array(
        'socid',
        'fk_project',
        'description',
        'array_options' => array('options_companyrelationships_fk_soc_benefactor')
    );

    /**
     * Interventions lines
     * @var array   $FIELDSLINE_INTERVENTION     Mandatory fields, checked when create and update object
     */
    static $FIELDSLINE_INTERVENTION = array(
        // Specific for Synergies-Tech: Description not mandatory
        //'desc',
        'datei',
        'duration'
    );

    /**
     * @var array   $FIELDS_SHIPMENT     Mandatory fields, checked when create and update object
     */
    static $FIELDS_SHIPMENT = array(
        'socid',
        'origin_id',
        'origin_type',
        'array_options' => array('options_companyrelationships_fk_soc_benefactor')
    );

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS_CONTRACT = array(
        'socid',
        'date_contrat',
        'commercial_signature_id',
        'commercial_suivi_id',
        'array_options' => array('options_companyrelationships_fk_soc_benefactor')
    );
    /**
	 * @var array   $DOCUMENT_FIELDS     Mandatory fields, checked when create and update object
	 */
	static $DOCUMENT_FIELDS = array(
		'modulepart'
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
    static protected $WHITELIST_OF_PROPERTIES = array(
        'action' => array(
            "id" => '', "ref" => '', "type_id" => '', "type_code" => '', "type" => '', "type_color" => '', "code" => '',
            "label" => '', "datec" => '', "datem" => '', "authorid" => '', "usermodid" => '', "datep" => '', "datef" => '',
            "durationp" => '', "fulldayevent" => '', "punctual" => '', "percentage" => '', "location" => '', "transparency" => '',
            "priority" => '', "userassigned" => array(''=>array('id'=>'')), "userownerid" => '', "userdoneid" => '', "socid" => '', "contactid" => '',
            "contact" => '', "array_options" => '', "fk_project" => '', "ref_ext" => '', "note" => '', "type_picto" => '',
            "user_mod" => '', "user_done" => '', "user_owner" => '', "thirdparty" => '', "entity" => '', "fk_element" => '', "elementtype" => '',
        ),
        'user' => array(
            "id" => '', "employee" => '', "gender" => '', "email" => '', "skype" => '', "job" => '', "signature" => '',
            "address" => '', "zip" => '', "town" => '', "state_id" => '', "state_code" => '', "state" => '', "office_phone" => '',
            "office_fax" => '', "user_mobile" => '', "entity" => '', "datec" => '', "datem" => '', "socid" => '', "contactid" => '',
            "fk_member" => '', "fk_user" => '', "datelastlogin" => '', "datepreviouslogin" => '', "statut" => '', "photo" => '',
            "lang" => '', "users" => '', "parentof" => '', "thm" => '', "tjm" => '', "salary" => '', "salaryextra" => '',
            "weeklyhours" => '', "color" => '', "dateemployment" => '', "array_options" => '', "ref" => '', "ref_ext" => '',
            "country_id" => '', "country_code" => '', "lastname" => '', "firstname" => '', "thirdparty" => '', "linkedObjectsIds" => '',
        ),
        'usergroup' => array(
            "id" => '', "ref" => '', "name" => '', "array_options" => '', "entity" => '', "datec" => '', "datem" => '',
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
            "logo" => '', "array_options" => '', "id" => '', "linkedObjectsIds" => '','address' => '',"name" => '',
        ),
        'product' => array(
            "label" => '', "entity" => '', "description" => '', "type" => '', "price" => '', "price_ttc" => '', "price_min" => '',
            "price_min_ttc" => '', "price_base_type" => '', "multiprices" => '', "multiprices_ttc" => '', "multiprices_base_type" => '',
            "multiprices_min" => '', "multiprices_min_ttc" => '', "multiprices_tva_tx" => '', "multiprices_recuperableonly" => '',
            "price_by_qty" => '', "prices_by_qty" => '', "prices_by_qty_id" => '', "prices_by_qty_list" => '', "default_vat_code" => '',
            "tva_tx" => '', "tva_npr" => '', "localtax1_tx" => '', "localtax2_tx" => '', "localtax1_type" => '', "localtax2_type" => '',
            "stock_reel" => '', "cost_price" => '', "pmp" => '', "seuil_stock_alerte" => '', "desiredstock" => '', "duration_value" => '',
            "duration_unit" => '', "status" => '', "status_buy" => '', "finished" => '', "status_batch" => '', "customcode" => '',
            "url" => '', "weight" => '', "weight_units" => '', "length" => '', "length_units" => '', "surface" => '', "surface_units" => '',
            "volume" => '', "volume_units" => '', "accountancy_code_buy" => '', "accountancy_code_sell" => '', "barcode" => '',
            "multilangs" => '', "date_creation" => '', "date_modification" => '', "fk_price_expression" => '', "fk_unit" => '',
            "price_autogen" => '', "id" => '', "array_options" => '', "linkedObjectsIds" => '', "ref" => '', "ref_ext" => '',
            "barcode_type" => '', "barcode_type_code" => '', "recuperableonly" => '', "duration" => '', "width" => '', "width_units" => '',
            "height" => '', "height_units" => '',
        ),
        'contrat' => array(
            "id" => '', "entity" => '', "ref" => '', "ref_customer" => '', "ref_supplier" => '', "ref_ext" => '', "statut" => '',
            "mise_en_service" => '', "date_contrat" => '', "date_creation" => '', "fin_validite" => '', "date_modification" => '',
            "date_validation" => '', "user_author_id" => '', "commercial_signature_id" => '', "commercial_suivi_id" => '',
            "note_public" => '', "fk_project" => '', "socid" => '', "array_options" => '', "thirdparty" => '',
            "cr_thirdparty_benefactor" => '', "lines" => '', "linkedObjectsIds" => '',
        ),
        'contratdet' => array(
            "id" => '', "ref" => '', "tms" => '', "fk_contrat" => '', "fk_product" => '', "statut" => '', "product_ref" => '',
            "product_label" => '', "product_description" => '', "product_type" => '', "label" => '', "description" => '',
            "date_commande" => '', "date_ouverture_prevue" => '', "date_ouverture" => '', "date_fin_validite" => '',
            "date_cloture" => '', "tva_tx" => '', "vat_src_code" => '', "localtax1_tx" => '', "localtax2_tx" => '',
            "localtax1_type" => '', "localtax2_type" => '', "qty" => '', "remise_percent" => '', "remise" => '',
            "fk_remise_except" => '', "subprice" => '', "price_ht" => '', "total_ht" => '', "total_tva" => '', "total_localtax1" => '',
            "total_localtax2" => '', "total_ttc" => '', "info_bits" => '', "fk_user_author" => '', "fk_user_ouverture" => '',
            "fk_user_cloture" => '', "commentaire" => '', "fk_fournprice" => '', "pa_ht" => '', "fk_unit" => '', "array_options" => '',
        ),
        'shipping' => array(
            "id" => '', "entity" => '', "ref" => '', "socid" => '', "ref_customer" => '', "ref_ext" => '', "statut" => '',
            "user_author_id" => '', "date_creation" => '', "date_shipping" => '', "date_delivery" => '', "shipping_method_id" => '',
            "tracking_number" => '', "origin" => '', "origin_id" => '', "billed" => '', "trueWeight" => '', "weight_units" => '',
            "trueWidth" => '', "width_units" => '', "trueHeight" => '', "height_units" => '', "trueDepth" => '', "depth_units" => '',
            "note_public" => '', "trueSize" => '', "size_units" => '', "fk_incoterms" => '', "location_incoterms" => '',
            "libelle_incoterms" => '', "brouillon" => '', "tracking_url" => '', "thirdparty" => '', "array_options" => '',
            "cr_thirdparty_benefactor" => '', "lines" => '', "linkedObjectsIds" => '',
        ),
        'expeditiondet' => array(
            "id" => '', "entrepot_id" => '', "qty_shipped" => '', "details_entrepot" => '', "fk_origin" => '', "fk_origin_line" => '',
            "product_type" => '', "fk_product" => '', "fk_product_type" => '', "product_ref" => '',
            "product_label" => '', "product_tobatch" => '', "label" => '', "description" => '', "qty_asked" => '',
            "weight" => '', "weight_units" => '', "length" => '', "length_units" => '', "surface" => '', "surface_units" => '',
            "volume" => '', "volume_units" => '', "pa_ht" => '', "desc" => '', "qty" => '',/* "total_ht" => '', "total_localtax1" => '',*/
            /*"total_localtax2" => '', "total_ttc" => '', "total_tva" => '', "vat_src_code" => '', "tva_tx" => '',*/ "localtax1_tx" => '',
            "localtax2_tx" => '', "info_bits" => '', "price" => '', "subprice" => '', /*"remise_percent" => '',*/ "fk_multicurrency" => '',
            "multicurrency_code" => '', "multicurrency_subprice" => '', "multicurrency_total_ht" => '', "multicurrency_total_tva" => '',
            "multicurrency_total_ttc" => '', "detail_batch" => '', "array_options" => '',
        ),
        'fichinter' => array(
            "id" => '', "entity" => '', "ref" => '', "description" => '', "socid" => '', "statut" => '', "duration" => '', "datec" => '',
            "datee" => '', "dateo" => '', "datet" => '', "datev" => '', "datem" => '', "fk_project" => '', "note_public" => '',
            "trueWidth" => '', "width_units" => '', "trueHeight" => '', "height_units" => '', "trueDepth" => '', "depth_units" => '',
            "fk_contrat" => '', "user_creation" => '', "brouillon" => '', "thirdparty" => '', "array_options" => '',
            "cr_thirdparty_benefactor" => '', "lines" => '', "linkedObjectsIds" => '',
        ),
        'fichinterdet' => array(
           "id" => '', "desc" => '', "duration" => '', "qty" => '', "date" => '', "datei" => '',
            "rang" => '', "product_type" => '', "array_options" => '',
        ),
        'facture' => array(
            "id" => '', "entity" => '', "ref" => '', "ref_client" => '', "ref_ext" => '', "type" => '', "date" => '', "date_pointoftax" => '',
            "date_creation" => '', "date_validation" => '', "datem" => '', "remise_percent" => '', "remise_absolue" => '',
            "total_ht" => '', "total_tva" => '', "total_localtax1" => '', "total_localtax2" => '', "total_ttc" => '',
            "revenuestamp" => '', "paye" => '', "close_code" => '', "close_note" => '', "socid" => '', "statut" => '',
            "date_lim_reglement" => '', "mode_reglement_id" => '', "mode_reglement_code" => '', "mode_reglement" => '',
            "cond_reglement_id" => '', "cond_reglement_code" => '', "cond_reglement_doc" => '', "fk_account" => '',
            "fk_project" => '', "fk_facture_source" => '', "note_public" => '', "user_author" => '', "user_valid" => '',
            "situation_cycle_ref" => '', "situation_counter" => '', "situation_final" => '', "fk_incoterms" => '',
            "location_incoterms" => '', "libelle_incoterms" => '', "fk_multicurrency" => '', "multicurrency_code" => '',
            "multicurrency_tx" => '', "multicurrency_total_ht" => '', "multicurrency_total_tva" => '',
            "multicurrency_total_ttc" => '', "tab_previous_situation_invoice" => '', "tab_next_situation_invoice" => '',
            "brouillon" => '', "thirdparty" => '', "array_options" => '', "cr_thirdparty_benefactor" => '', "lines" => '', "linkedObjectsIds" => '',
        ),
        'facturedet' => array(
            "id" => '', "desc" => '', "product_type" => '', "product_ref" => '', "product_label" => '', "product_desc" => '',
            "fk_product_type" => '', "qty" => '', "subprice" => '', "vat_src_code" => '', "tva_tx" => '', "localtax1_tx" => '',
            "localtax2_tx" => '', "localtax1_type" => '', "localtax2_type" => '', "remise_percent" => '', "fk_remise_except" => '',
            "fk_product" => '', "date_start" => '', "date_end" => '', "info_bits" => '', "total_ht" => '', "total_tva" => '',
            "total_localtax1" => '', "total_localtax2" => '', "total_ttc" => '', "code_ventilation" => '', "fk_fournprice" => '',
            "pa_ht" => '', "marge_tx" => '', "marque_tx" => '', "rang" => '', "special_code" => '', "fk_parent_line" => '',
            "situation_percent" => '', "fk_prev_id" => '', "fk_unit" => '', "fk_multicurrency" => '', "multicurrency_code" => '',
            "multicurrency_subprice" => '', "multicurrency_total_ht" => '', "multicurrency_total_tva" => '',
            "multicurrency_total_ttc" => '', "array_options" => '',
        ),
        'commande' => array(
            "id" => '', "entity" => '', "ref" => '', "ref_customer" => '', "ref_ext" => '', "socid" => '', "statut" => '', "user_author_id" => '',
            "user_valid" => '', "total_ht" => '', "total_tva" => '', "total_localtax1" => '', "total_localtax2" => '',
            "total_ttc" => '', "date" => '', "remise" => '', "remise_percent" => '', "remise_absolue" => '',
            "source" => '', "billed" => '', "note_public" => '', "fk_project" => '', "mode_reglement_id" => '',
            "mode_reglement_code" => '', "mode_reglement" => '', "cond_reglement_id" => '', "cond_reglement_code" => '',
            "cond_reglement_doc" => '', "fk_account" => '', "availability_id" => '', "availability_code" => '',
            "availability" => '', "demand_reason_id" => '', "demand_reason_code" => '', "date_livraison" => '',
            "shipping_method_id" => '', "warehouse_id" => '', "fk_incoterms" => '', "location_incoterms" => '',
            "libelle_incoterms" => '', "fk_multicurrency" => '', "multicurrency_code" => '', "multicurrency_tx" => '',
            "multicurrency_total_ht" => '', "multicurrency_total_tva" => '', "multicurrency_total_ttc" => '',
            "brouillon" => '', "thirdparty" => '', "array_options" => '', "cr_thirdparty_benefactor" => '', "lines" => '', "linkedObjectsIds" => '',
        ),
        'commandedet' => array(
            "id" => '', "label" => '', "desc" => '', "product_type" => '', "qty" => '', "vat_src_code" => '',
            "tva_tx" => '', "localtax1_tx" => '', "localtax2_tx" => '', "localtax1_type" => '', "localtax2_type" => '',
            "total_ht" => '', "total_ttc" => '', "total_tva" => '', "total_localtax1" => '', "total_localtax2" => '',
            "subprice" => '', "fk_remise_except" => '', "remise_percent" => '', "fk_product" => '', "fk_fournprice" => '',
            "pa_ht" => '', "marge_tx" => '', "marque_tx" => '', "rang" => '', "info_bits" => '', "special_code" => '',
            "fk_parent_line" => '', "product_ref" => '', "product_label" => '', "product_desc" => '', "product_tobatch" => '',
            "fk_product_type" => '', "fk_unit" => '', "weight" => '', "weight_units" => '', "volume" => '', "volume_units" => '',
            "date_start" => '', "date_end" => '', "fk_multicurrency" => '', "multicurrency_code" => '', "multicurrency_subprice" => '',
            "multicurrency_total_ht" => '', "multicurrency_total_tva" => '', "multicurrency_total_ttc" => '', "array_options" => '',
        ),
        'propal' => array(
            "id" => '', "entity" => '', "ref" => '', "ref_client" => '', "remise" => '', "remise_percent" => '', "remise_absolue" => '',
            "total_ht" => '', "total_tva" => '', "total_localtax1" => '', "total_localtax2" => '', "total_ttc" => '',
            "socid" => '', "fk_project" => '', "note_public" => '', "statut" => '', "statut_libelle" => '', "date_creation" => '',
            "date_validation" => '', "date" => '', "fin_validite" => '', "date_livraison" => '', "shipping_method_id" => '',
            "availability_id" => '', "availability_code" => '', "availability" => '', "demand_reason_id" => '',
            "demand_reason_code" => '', "demand_reason" => '', "fk_address" => '', "mode_reglement_id" => '',
            "mode_reglement_code" => '', "mode_reglement" => '', "fk_account" => '', "cond_reglement_id" => '',
            "cond_reglement_code" => '', "cond_reglement_doc" => '', "user_author_id" => '', "user_valid_id" => '',
            "user_close_id" => '', "fk_incoterms" => '', "location_incoterms" => '', "libelle_incoterms" => '',
            "fk_multicurrency" => '', "multicurrency_code" => '', "multicurrency_tx" => '', "multicurrency_total_ht" => '',
            "multicurrency_total_tva" => '', "multicurrency_total_ttc" => '',
            "brouillon" => '', "thirdparty" => '', "array_options" => '', "cr_thirdparty_benefactor" => '', "lines" => '', "linkedObjectsIds" => ''

        ),
        'propaldet' => array(
            "id" => '', "fk_parent_line" => '', "product_type" => '', "label" => '', "desc" => '', "qty" => '',
            "vat_src_code" => '', "tva_tx" => '', "localtax1_tx" => '', "localtax2_tx" => '', "localtax1_type" => '',
            "localtax2_type" => '', "subprice" => '', "fk_remise_except" => '', "remise_percent" => '', "info_bits" => '',
            "total_ht" => '', "total_tva" => '', "total_localtax1" => '', "total_localtax2" => '', "total_ttc" => '',
            "fk_fournprice" => '', "pa_ht" => '', "marge_tx" => '', "marque_tx" => '', "special_code" => '',
            "rang" => '', "fk_product" => '', "product_ref" => '', "product_label" => '', "product_desc" => '',
            "fk_unit" => '', "date_start" => '', "date_end" => '', "fk_multicurrency" => '', "multicurrency_code" => '',
            "multicurrency_subprice" => '', "multicurrency_total_ht" => '', "multicurrency_total_tva" => '',
            "multicurrency_total_ttc" => '', "array_options" => '',
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

    /**
     * @var Propal $propal {@type Propal}
     */
    public $propal;

    /**
     * @var Commande $commande {@type Commande}
     */
    public $commande;

    /**
     * @var Facture $invoice {@type Facture}
     */
    public $invoice;

    /**
     * @var fichinter $fichinter {@type fichinter}
     */
    public $fichinter;

    /**
     * @var Expedition $shipment {@type Expedition}
     */
    public $shipment;

    /**
     * @var Contrat $contract {@type Contrat}
     */
    public $contract;

    /**
     * @var Equipement $equipement {@type Equipement}
     */
    public $equipement;


    /**
     * @var array $benefactor_ids List of benefactor ids (for thirdpaty of API user)
     */
    public $benefactor_ids = array();


    /**
     * Constructor
     */
    function __construct()
    {
        global $db, $conf, $user;

        $user = DolibarrApiAccess::$user;
        $this->db = $db;

        // proposals
        $this->propal = new Propal($this->db);

        // orders
        $this->commande = new Commande($this->db);

        // invoices
        $this->invoice = new Facture($this->db);

        // interventions
        $this->fichinter = new Fichinter($this->db);

        // shipments
        $this->shipment = new Expedition($this->db);

        // contracts
        $this->contract = new Contrat($this->db);

        // equipments
        if (!empty($conf->equipement->enabled)) {
            $this->equipement = new Equipement($this->db);

            // get API user
            $user = DolibarrApiAccess::$user;
            $userSocId = $user->societe_id;

            // If external user: Check permission for external users
            if ($userSocId > 0) {
                // get benefactors ids
                $companyrelationships = new CompanyRelationships($db);
                $benefactor_ids = $companyrelationships->getRelationshipsThirdparty($userSocId, CompanyRelationships::RELATION_TYPE_BENEFACTOR, 1);
                if (is_array($benefactor_ids)) {
                    $this->benefactor_ids = $benefactor_ids;
                }

                // get watchers ids
                $companyrelationships = new CompanyRelationships($db);
                $watcher_ids = $companyrelationships->getRelationshipsThirdparty($userSocId, CompanyRelationships::RELATION_TYPE_WATCHER, 0);
                if (is_array($watcher_ids)) {
                    $this->watcher_ids = $watcher_ids;
                }
            }
        }

        if (!DolibarrApiAccess::$user->rights->companyrelationships->update_md->element) {
            self::$BLACKLIST_OF_PROPERTIES = array(
                'contrat' => array(
                    'array_options' => array(
                        'options_companyrelationships_availability_principal' => '',
                        'options_companyrelationships_availability_benefactor' => '',
                    ),
                ),
                'shipping' => array(
                    'array_options' => array(
                        'options_companyrelationships_availability_principal' => '',
                        'options_companyrelationships_availability_benefactor' => '',
                    ),
                ),
                'fichinter' => array(
                    'array_options' => array(
                        'options_companyrelationships_availability_principal' => '',
                        'options_companyrelationships_availability_benefactor' => '',
                    ),
                ),
                'facture' => array(
                    'array_options' => array(
                        'options_companyrelationships_availability_principal' => '',
                        'options_companyrelationships_availability_benefactor' => '',
                    ),
                ),
                'commande' => array(
                    'array_options' => array(
                        'options_companyrelationships_availability_principal' => '',
                        'options_companyrelationships_availability_benefactor' => '',
                    ),
                ),
                'propal' => array(
                    'array_options' => array(
                        'options_companyrelationships_availability_principal' => '',
                        'options_companyrelationships_availability_benefactor' => '',
                    ),
                ),
            );
        }
    }


    //
    // Common
    //

    /**
     * Check perms for user with public space availability
     *
     * @param   Object      $object         Object (propal, commande, invoice, fichinter)
     * @return  bool        FALSE to deny user access, TRUE to authorize
     * @throws  Exception
     */
    private function _checkUserPublicSpaceAvailabilityPermOnObject($object)
    {
        global $conf;

        $hasPerm = FALSE;

        // get API user
        $user = DolibarrApiAccess::$user;
        $userSocId = $user->societe_id;

        // If external user: Check permission for external users
        if ($userSocId > 0) {
            // search customers of this external user
            $search_sale = 0;
            if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

            $sql  = "SELECT t.rowid";
            $sql .= " FROM " . MAIN_DB_PREFIX . $object->table_element . " as t";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . $object->table_element . "_extrafields as ef ON ef.fk_object = t.rowid";

            if ($search_sale > 0) $sql .=  " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scp ON scp.fk_soc = t.fk_soc AND scp.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale
            if ($search_sale > 0) $sql .=  " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scb ON scb.fk_soc = ef.companyrelationships_fk_soc_benefactor AND scb.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale
            if ($search_sale > 0) $sql .=  " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scw ON scw.fk_soc = ef.companyrelationships_fk_soc_watcher AND scw.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale

            // search principal company
            $sqlPrincipal  = "(";
            $sqlPrincipal .= "(t.fk_soc = " . $userSocId;
            if ($search_sale > 0) {
                $sqlPrincipal .= " OR scp.fk_user = " . $search_sale;
            }
            $sqlPrincipal .= ")";
            $sqlPrincipal .= " AND ef.companyrelationships_availability_principal = 1";
            $sqlPrincipal .= ")";

            // search benefactor company
            $sqlBenefactor  = "(";
            $sqlBenefactor .= "(ef.companyrelationships_fk_soc_benefactor = " . $userSocId;
            if ($search_sale > 0) {
                $sqlBenefactor .= " OR scb.fk_user = " . $search_sale;
            }
            $sqlBenefactor .= ")";
            $sqlBenefactor .= " AND ef.companyrelationships_availability_benefactor = 1";
            $sqlBenefactor .= ")";

            // search watcher company
            $sqlWatcher = "(";
            $sqlWatcher .= "(ef.companyrelationships_fk_soc_watcher = " . $userSocId;
            if ($search_sale > 0) {
                $sqlWatcher .= " OR scw.fk_user = " . $search_sale;
            }
            $sqlWatcher .= ")";
            $sqlWatcher .= " AND ef.companyrelationships_availability_watcher = 1";
            $sqlWatcher .= ")";

            $sql .= " WHERE t.rowid = " . $object->id;
            $sql .= " AND t.entity IN (" . getEntity($object->table_element) . ")";
            $sql .= " AND (". $sqlPrincipal . " OR " . $sqlBenefactor . " OR " . $sqlWatcher . ")";

            $resql = $this->db->query($sql);
            if ($resql) {
                $nbResult = $this->db->num_rows($resql);
                if ($nbResult > 0) {
                    $hasPerm = TRUE;
                }
            }
        }
        // If internal user: Check permission for internal users that are restricted on their objects
        else if (! empty($conf->societe->enabled) && ($user->rights->societe->lire && ! $user->rights->societe->client->voir) && $object->id > 0) {
            $hasPerm = TRUE;

            $sql  = "SELECT COUNT(sc.fk_soc) as nb";
            $sql .= " FROM " . MAIN_DB_PREFIX . $object->table_element . " as dbt";
            $sql .= ", " . MAIN_DB_PREFIX . "societe as s";
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
            $sql .= " WHERE dbt.rowid = " . $object->id;
            $sql .= " AND sc.fk_soc = dbt.fk_soc";
            $sql .= " AND dbt.fk_soc = s.rowid";
            $sql .= " AND dbt.entity IN (" . getEntity($object->table_element, 1) . ")";
            $sql .= " AND sc.fk_user = " . $user->id;

            $resql = $this->db->query($sql);
            if ($resql) {
                $obj = $this->db->fetch_object($resql);
                if (! $obj || $obj->nb < count(explode(',', $object->id))) $hasPerm = FALSE;
            } else {
                $hasPerm = FALSE;
            }
        }
        // If multicompany and internal users with all permissions, check user is in correct entity
        else if (! empty($conf->multicompany->enabled) && $object->id > 0) {
            $hasPerm = TRUE;

            $sql = "SELECT COUNT(dbt.fk_soc) as nb";
            $sql.= " FROM " . MAIN_DB_PREFIX . $object->table_element . " as dbt";
            $sql.= " WHERE dbt.rowid = " . $object->id;
            $sql.= " AND dbt.entity IN (". getEntity($object->table_element, 1) . ")";

            $resql = $this->db->query($sql);
            if ($resql) {
                $obj = $this->db->fetch_object($resql);
                if (! $obj || $obj->nb < count(explode(',', $object->id))) $hasPerm = FALSE;
            } else {
                $hasPerm = FALSE;
            }
        } else {
            $hasPerm = TRUE;
        }

        return $hasPerm;
    }

    /**
     * Prepare SQL request for element list (propal, commande, invoice, fichinter, contract) for external user
     *
     * @param       int     $userSocId      Id of user company (external user)
     * @param       int     $search_sale    Id of commercial user
     * @return      string  SQL request
     */
    private static function _sqlElementListForExternalUser($userSocId, $search_sale=0)
    {
        $sql = '';

        if ($search_sale > 0) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scp ON scp.fk_soc = t.fk_soc AND scp.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale
        if ($search_sale > 0) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scb ON scb.fk_soc = ef.companyrelationships_fk_soc_benefactor AND scb.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale
        if ($search_sale > 0) $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scw ON scw.fk_soc = ef.companyrelationships_fk_soc_watcher AND scw.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale

        // search principal company
        $sqlPrincipal  = "(";
        $sqlPrincipal .= "(t.fk_soc = " . $userSocId;
        if ($search_sale > 0) {
            $sqlPrincipal .= " OR scp.fk_user = " . $search_sale;
        }
        $sqlPrincipal .= ")";
        $sqlPrincipal .= " AND ef.companyrelationships_availability_principal = 1";
        $sqlPrincipal .= ")";

        // search benefactor company
        $sqlBenefactor  = "(";
        $sqlBenefactor .= "(ef.companyrelationships_fk_soc_benefactor = " . $userSocId;
        if ($search_sale > 0) {
            $sqlBenefactor .= " OR scb.fk_user = " . $search_sale;
        }
        $sqlBenefactor .= ")";
        $sqlBenefactor .= " AND ef.companyrelationships_availability_benefactor = 1";
        $sqlBenefactor .= ")";

        // search watcher company
        $sqlWatcher  = "(";
        $sqlWatcher .= "(ef.companyrelationships_fk_soc_watcher = " . $userSocId;
        if ($search_sale > 0) {
            $sqlWatcher .= " OR scw.fk_user = " . $search_sale;
        }
        $sqlWatcher .= ")";
        $sqlWatcher .= " AND ef.companyrelationships_availability_watcher = 1";
        $sqlWatcher .= ")";

        $sql .= " WHERE (". $sqlPrincipal . " OR " . $sqlBenefactor . " OR " . $sqlWatcher . ")";

        return $sql;
    }


    //
    // API Proposals
    //

    /**
     * Get properties of a commercial proposal object
     *
     * Return an array with commercial proposal informations
     *
     * @url	GET proposals/{id}
     *
     * @param   int             $id         ID of commercial proposal
     * @return  array|mixed     Data without useless information
     *
     * @throws  401     RestException   Insufficient rights
     */
    function getProposal($id)
    {
        if(! DolibarrApiAccess::$user->rights->propal->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->propal->fetch($id);
        if( ! $result ) {
            throw new RestException(200);
        }

        /*
        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        */

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->propal->fetchObjectLinked();
        $this->propal->fetch_thirdparty();
        $this->_fetch_benefactor($this->propal);
        return $this->_cleanObjectData($this->propal);
    }

    /**
     * List commercial proposals
     *
     * Get a list of commercial proposals
     *
     * @url	GET proposals
     *
     * @param   string      $sortfield	            Sort field
     * @param   string	    $sortorder	            Sort order
     * @param   int		    $limit		            Limit for list
     * @param   int         $page		            Page number
     * @param   string      $thirdparty_ids	        Thirdparty ids to filter commercial proposals. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param   string      $sqlfilters             Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
     * @return  array       Array of order objects
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  503     RestException   Error when retrieve proposal list
     */
    function indexProposal($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->propal->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // get API user
        $userSocId = DolibarrApiAccess::$user->societe_id;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."propal as t";

        // external
        if ($userSocId > 0) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "propal_extrafields as ef ON ef.fk_object = t.rowid";
            $sql .= self::_sqlElementListForExternalUser($userSocId, $search_sale);
            $sql .= " AND t.entity IN (" . getEntity('propal') . ")";
        }
        // internal
        else {
            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

            $sql.= ' WHERE (t.entity IN ('.getEntity('propal').')';
            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
            if ($search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";		// Join for the needed table to filter by sale
            // Insert sale filter
            if ($search_sale > 0)
            {
                $sql .= " AND sc.fk_user = ".$search_sale;
            }
            $sql.= ")";

            // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
            $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;
            if ($socids) {
                $sql.= ' OR (t.entity IN ('.getEntity('societe').')';
                $sql.= " AND t.fk_soc IN (".$socids."))";
            }
            $sql.= " GROUP BY rowid";
        }

        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        dol_syslog("API Rest request");
        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i = 0;
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $proposal_static = new Propal($db);
                if($proposal_static->fetch($obj->rowid)) {
                    $proposal_static->fetchObjectLinked();
                    $proposal_static->fetch_thirdparty();
                    $this->_fetch_benefactor($proposal_static);
                    $obj_ret[] = $this->_cleanObjectData($proposal_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve propal list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            return [];
        }
        return $obj_ret;
    }

    /**
     * Create commercial proposal object
     *
     * @url	POST proposals
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of proposal
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error when creating proposal
     */
    function postProposal($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->propal->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        // Check mandatory fields
        $result = $this->_validateProposal($request_data);

        foreach($request_data as $field => $value) {
            $this->propal->$field = $value;
        }
        /*if (isset($request_data["lines"])) {
          $lines = array();
          foreach ($request_data["lines"] as $line) {
            array_push($lines, (object) $line);
          }
          $this->propal->lines = $lines;
        }*/

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if ($this->propal->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating propal", array_merge(array($this->propal->error), $this->propal->errors));
        }

        return $this->propal->id;
    }

    /**
     * Get lines of a commercial proposal
     *
     * @url	GET proposals/{id}/lines
     *
     * @param   int $id             Id of commercial proposal
     * @return  int|array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function getLinesProposal($id)
    {
        if(! DolibarrApiAccess::$user->rights->propal->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->propal->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->propal->getLinesArray();
        $result = array();
        foreach ($this->propal->lines as $line) {
            array_push($result,$this->_cleanObjectData($line));
        }
        return $result;
    }

    /**
     * Add a line to given commercial proposal
     *
     * @url	POST proposals/{id}/lines
     *
     * @param   int     $id             Id of commercial proposal to update
     * @param   array   $request_data   Commercial proposal line data
     * @return  int|array
     *
     * @throws  400     RestException   Error while creating proposal line
     * @throws  401     RestException   Insufficient rights
     */
    function postLineProposal($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->propal->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->propal->fetch($id);
        if (! $result) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $request_data = (object) $request_data;

        $updateRes = $this->propal->addline(
            $request_data->desc,
            $request_data->subprice,
            $request_data->qty,
            $request_data->tva_tx,
            $request_data->localtax1_tx,
            $request_data->localtax2_tx,
            $request_data->fk_product,
            $request_data->remise_percent,
            'HT',
            0,
            $request_data->info_bits,
            $request_data->product_type,
            $request_data->rang,
            $request_data->special_code,
            $request_data->fk_parent_line,
            $request_data->fk_fournprice,
            $request_data->pa_ht,
            $request_data->label,
            $request_data->date_start,
            $request_data->date_end,
            $request_data->array_options,
            $request_data->fk_unit,
            $request_data->origin,
            $request_data->origin_id,
            $request_data->multicurrency_subprice,
            $request_data->fk_remise_except
        );

        if ($updateRes > 0) {
            return $updateRes;
        }
        else {
            throw new RestException(400, $this->propal->error);
        }
    }

    /**
     * Update a line of given commercial proposal
     *
     * @url	PUT proposals/{id}/lines/{lineid}
     *
     * @param   int     $id             Id of commercial proposal to update
     * @param   int     $lineid         Id of line to update
     * @param   array   $request_data   Commercial proposal line data
     * @return  bool|array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function putLineProposal($id, $lineid, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->propal->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->propal->fetch($id);
        if($result <= 0) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $request_data = (object) $request_data;

        $propalline = new PropaleLigne($this->db);
        $result = $propalline->fetch($lineid);
        if ($result <= 0) {
            return [];
        }

        $updateRes = $this->propal->updateline(
            $lineid,
            isset($request_data->subprice)?$request_data->subprice:$propalline->subprice,
            isset($request_data->qty)?$request_data->qty:$propalline->qty,
            isset($request_data->remise_percent)?$request_data->remise_percent:$propalline->remise_percent,
            isset($request_data->tva_tx)?$request_data->tva_tx:$propalline->tva_tx,
            isset($request_data->localtax1_tx)?$request_data->localtax1_tx:$propalline->localtax1_tx,
            isset($request_data->localtax2_tx)?$request_data->localtax2_tx:$propalline->localtax2_tx,
            isset($request_data->desc)?$request_data->desc:$propalline->desc,
            'HT',
            isset($request_data->info_bits)?$request_data->info_bits:$propalline->info_bits,
            isset($request_data->special_code)?$request_data->special_code:$propalline->special_code,
            isset($request_data->fk_parent_line)?$request_data->fk_parent_line:$propalline->fk_parent_line,
            0,
            isset($request_data->fk_fournprice)?$request_data->fk_fournprice:$propalline->fk_fournprice,
            isset($request_data->pa_ht)?$request_data->pa_ht:$propalline->pa_ht,
            isset($request_data->label)?$request_data->label:$propalline->label,
            isset($request_data->product_type)?$request_data->product_type:$propalline->product_type,
            isset($request_data->date_start)?$request_data->date_start:$propalline->date_start,
            isset($request_data->date_end)?$request_data->date_end:$propalline->date_end,
            isset($request_data->array_options)?$request_data->array_options:$propalline->array_options,
            isset($request_data->fk_unit)?$request_data->fk_unit:$propalline->fk_unit,
            isset($request_data->multicurrency_subprice)?$request_data->multicurrency_subprice:$propalline->subprice
        );

        if ($updateRes > 0) {
            $result = $this->getPropal($id);
            unset($result->line);
            return $this->_cleanObjectData($result);
        }
        return false;
    }

    /**
     * Delete a line of given commercial proposal
     *
     * @url	DELETE proposals/{id}/lines/{lineid}
     *
     * @param   int   $id       Id of commercial proposal to update
     * @param   int   $lineid   Id of line to delete
     * @return  int
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  405     RestException   Error while deleting the proposal line
     */
    function deleteLineProposal($id, $lineid)
    {
        if(! DolibarrApiAccess::$user->rights->propal->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->propal->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        // TODO Check the lineid $lineid is a line of ojbect

        $updateRes = $this->propal->deleteline($lineid);
        if ($updateRes > 0) {
            return $this->getProposal($id);
        }
        else
        {
            throw new RestException(405, $this->propal->error);
        }
    }

    /**
     * Update commercial proposal general fields (won't touch lines of commercial proposal)
     *
     * @url	PUT proposals/{id}
     *
     * @param   int     $id             Id of commercial proposal to update
     * @param   array   $request_data   Datas
     * @return  int|array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while updating the proposal line
     */
    function putProposal($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->propal->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->propal->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->propal->$field = $value;
        }

        // update end of validity date
        if (empty($this->propal->fin_validite) && !empty($this->propal->duree_validite) && !empty($this->propal->date_creation))
        {
            $this->propal->fin_validite = $this->propal->date_creation + ($this->propal->duree_validite * 24 * 3600);
        }
        if (!empty($this->propal->fin_validite))
        {
            if($this->propal->set_echeance(DolibarrApiAccess::$user, $this->propal->fin_validite)<0)
            {
                throw new RestException(500, $this->propal->error);
            }
        }

        if ($this->propal->update(DolibarrApiAccess::$user) > 0)
        {
            return $this->getProposal($id);
        }
        else
        {
            throw new RestException(500, $this->propal->error);
        }
    }

    /**
     * Delete commercial proposal
     *
     * @url	DELETE proposals/{id}
     *
     * @param   int     $id         Commercial proposal ID
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while deleting the proposal
     */
    function deleteProposal($id)
    {
        if(! DolibarrApiAccess::$user->rights->propal->supprimer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->propal->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if( ! $this->propal->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when delete Commercial Proposal : '.$this->propal->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Commercial Proposal deleted'
            )
        );

    }

    /**
     * Set a proposal to draft
     *
     * @url	POST proposals/{id}/settodraft
     *
     * @param   int     $id             Order ID
     * @return  array
     *
     * @throws  304     RestException   Nothing done
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while setting draft to proposal
     */
    function settodraftProposal($id)
    {
        if(! DolibarrApiAccess::$user->rights->propal->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->propal->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->propal->set_draft(DolibarrApiAccess::$user);
        if ($result == 0) {
            throw new RestException(304, 'Nothing done. May be object is already draft');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error : '.$this->propal->error);
        }

        $result = $this->propal->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->propal->fetchObjectLinked();

        return $this->_cleanObjectData($this->propal);
    }

    /**
     * Validate a commercial proposal
     *
     * If you get a bad value for param notrigger check that ou provide this in body
     * {
     * "notrigger": 0
     * }
     *
     * @url	POST proposals/{id}/validate
     *
     * @param   int     $id             Commercial proposal ID
     * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  array
     *
     * @throws  304     RestException   Nothing done
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while validating proposal
     */
    function validateProposal($id, $notrigger=0)
    {
        if(! DolibarrApiAccess::$user->rights->propal->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->propal->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->propal->valid(DolibarrApiAccess::$user, $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when validating Commercial Proposal: '.$this->propal->error);
        }

        $result = $this->propal->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->propal->fetchObjectLinked();

        return $this->_cleanObjectData($this->propal);
    }

    /**
     * Close (Accept or refuse) a quote / commercial proposal
     *
     * @url	POST proposals/{id}/close
     *
     * @param   int     $id             Commercial proposal ID
     * @param   int	    $status			Must be 2 (accepted) or 3 (refused)				{@min 2}{@max 3}
     * @param   string  $note_private   Add this mention at end of private note
     * @param   int     $notrigger      Disabled triggers
     * @return  array
     *
     * @throws  304     RestException   Nothing done
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while closing proposal
     */
    function closeProposal($id, $status, $note_private='', $notrigger=0)
    {
        if(! DolibarrApiAccess::$user->rights->propal->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->propal->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->propal->cloture(DolibarrApiAccess::$user, $status, $note_private, $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already closed');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when closing Commercial Proposal: '.$this->propal->error);
        }

        $result = $this->propal->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->propal->fetchObjectLinked();

        return $this->_cleanObjectData($this->propal);
    }

    /**
     * Set a commercial proposal billed. Could be also called setbilled
     *
     * @url	POST proposals/{id}/setinvoiced
     *
     * @param   int     $id             Commercial proposal ID
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while setting billed proposal
     */
    function setinvoicedProposal($id)
    {
        if(! DolibarrApiAccess::$user->rights->propal->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->propal->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->propal->classifyBilled(DolibarrApiAccess::$user );
        if ($result < 0) {
            throw new RestException(500, 'Error : '.$this->propal->error);
        }

        $result = $this->propal->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->propal->fetchObjectLinked();

        return $this->_cleanObjectData($this->propal);
    }

    /**
     * Validate fields before create or update object
     *
     * @param   array           $data   Array with data to verify
     * @return  array
     *
     * @throws  400     RestException   Field missing
     */
    function _validateProposal($data)
    {
        $propal = array();

        foreach (self::$FIELDS_PROPOSAL as $key => $field) {
            if (is_array($field)) {
                foreach($field as $fieldValue) {
                    if (!isset($data[$key][$fieldValue])) {
                        throw new RestException(400, "$fieldValue field missing");
                    }
                }
                $propal[$key] = $data[$key];
            } else {
                if (!isset($data[$field]))
                    throw new RestException(400, "$field field missing");
                $propal[$field] = $data[$field];
            }
        }

        return $propal;
    }


    //
    // API Orders
    //

    /**
     * Get properties of an order object
     *
     * Return an array with order informations
     *
     * @url	GET orders/{id}
     *
     * @param   int             $id         ID of order
     * @return  array|mixed     Data without useless information
     *
     * @throws  401     RestException   Insufficient rights
     */
    function getOrder($id)
    {
        if(! DolibarrApiAccess::$user->rights->commande->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->commande->fetchObjectLinked();
        $this->commande->fetch_thirdparty();
        $this->_fetch_benefactor($this->commande);
        return $this->_cleanObjectData($this->commande);
    }

    /**
     * List orders
     *
     * Get a list of orders
     *
     * @url	GET orders
     *
     * @param   string          $sortfield	        Sort field
     * @param   string          $sortorder	        Sort order
     * @param   int             $limit		        Limit for list
     * @param   int             $page		        Page number
     * @param   string          $thirdparty_ids	    Thirdparty ids to filter orders of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param   string          $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array                               Array of order objects
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  503     RestException   Error when retrieve order list
     */
    function indexOrder($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->commande->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // get API user
        $userSocId = DolibarrApiAccess::$user->societe_id;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as t";

        // external
        if ($userSocId > 0) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_extrafields as ef ON ef.fk_object = t.rowid";
            $sql .= self::_sqlElementListForExternalUser($userSocId, $search_sale);
            $sql .= " AND t.entity IN (" . getEntity('commande') . ")";
        }
        // internal
        else {
            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

            $sql.= ' WHERE (t.entity IN ('.getEntity('commande').')';
            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
            if ($search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";		// Join for the needed table to filter by sale
            // Insert sale filter
            if ($search_sale > 0)
            {
                $sql .= " AND sc.fk_user = ".$search_sale;
            }
            $sql.= ")";

            // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
            $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;
            if ($socids) {
                $sql.= ' OR (t.entity IN ('.getEntity('commande').')';
                $sql.= " AND t.fk_soc IN (".$socids."))";
            }
            $sql.= " GROUP BY rowid";
        }

        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        dol_syslog("API Rest request");
        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i=0;
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $commande_static = new Commande($db);
                if($commande_static->fetch($obj->rowid)) {
                    $commande_static->fetchObjectLinked();
                    $commande_static->fetch_thirdparty();
                    $this->_fetch_benefactor($commande_static);
                    $obj_ret[] = $this->_cleanObjectData($commande_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve commande list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            return [];
        }
        return $obj_ret;
    }

    /**
     * Create order object
     *
     * @url	POST orders
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of order
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error when creating order
     */
    function postOrder($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        // Check mandatory fields
        $result = $this->_validateOrder($request_data);

        foreach($request_data as $field => $value) {
            $this->commande->$field = $value;
        }
        /*if (isset($request_data["lines"])) {
          $lines = array();
          foreach ($request_data["lines"] as $line) {
            array_push($lines, (object) $line);
          }
          $this->commande->lines = $lines;
        }*/

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if ($this->commande->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating order", array_merge(array($this->commande->error), $this->commande->errors));
        }

        return $this->commande->id;
    }

    /**
     * Get lines of an order
     *
     * @url	GET orders/{id}/lines
     *
     * @param   int   $id             Id of order
     * @return  int|array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function getLinesOrder($id)
    {
        if(! DolibarrApiAccess::$user->rights->commande->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->commande->getLinesArray();
        $result = array();
        foreach ($this->commande->lines as $line) {
            array_push($result,$this->_cleanObjectData($line));
        }
        return $result;
    }

    /**
     * Add a line to given order
     *
     * @url	POST orders/{id}/lines
     *
     * @param   int     $id             Id of order to update
     * @param   array   $request_data   OrderLine data
     * @return  int|array
     *
     * @throws  400     RestException   Error while creating order line
     * @throws  401     RestException   Insufficient rights
     */
    function postLineOrder($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $request_data = (object) $request_data;
        $updateRes = $this->commande->addline(
            $request_data->desc,
            $request_data->subprice,
            $request_data->qty,
            $request_data->tva_tx,
            $request_data->localtax1_tx,
            $request_data->localtax2_tx,
            $request_data->fk_product,
            $request_data->remise_percent,
            $request_data->info_bits,
            $request_data->fk_remise_except,
            'HT',
            0,
            $request_data->date_start,
            $request_data->date_end,
            $request_data->product_type,
            $request_data->rang,
            $request_data->special_code,
            $request_data->fk_parent_line,
            $request_data->fk_fournprice,
            $request_data->pa_ht,
            $request_data->label,
            $request_data->array_options,
            $request_data->fk_unit,
            $request_data->origin,
            $request_data->origin_id,
            $request_data->multicurrency_subprice
        );

        if ($updateRes > 0) {
            return $updateRes;

        }
        else {
            throw new RestException(400, $this->commande->error);
        }
    }

    /**
     * Update a line to given order
     *
     * @url	PUT orders/{id}/lines/{lineid}
     *
     * @param   int   $id             Id of order to update
     * @param   int   $lineid         Id of line to update
     * @param   array $request_data   OrderLine data
     * @return  bool|array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function putLineOrder($id, $lineid, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $request_data = (object) $request_data;
        $updateRes = $this->commande->updateline(
            $lineid,
            $request_data->desc,
            $request_data->subprice,
            $request_data->qty,
            $request_data->remise_percent,
            $request_data->tva_tx,
            $request_data->localtax1_tx,
            $request_data->localtax2_tx,
            'HT',
            $request_data->info_bits,
            $request_data->date_start,
            $request_data->date_end,
            $request_data->product_type,
            $request_data->fk_parent_line,
            0,
            $request_data->fk_fournprice,
            $request_data->pa_ht,
            $request_data->label,
            $request_data->special_code,
            $request_data->array_options,
            $request_data->fk_unit,
            $request_data->multicurrency_subprice
        );

        if ($updateRes > 0) {
            $result = $this->getOrder($id);
            unset($result->line);
            return $this->_cleanObjectData($result);
        }
        return false;
    }

    /**
     * Delete a line to given order
     *
     * @url	DELETE orders/{id}/lines/{lineid}
     *
     * @param   int   $id             Id of order to update
     * @param   int   $lineid         Id of line to delete
     * @return  int|array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  405     RestException   Error while deleting the order line
     */
    function deleteLineOrder($id, $lineid)
    {
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        // TODO Check the lineid $lineid is a line of ojbect

        $updateRes = $this->commande->deleteline(DolibarrApiAccess::$user,$lineid);
        if ($updateRes > 0) {
            return $this->getOrder($id);
        }
        else
        {
            throw new RestException(405, $this->commande->error);
        }
    }

    /**
     * Update order general fields (won't touch lines of order)
     *
     * @url	PUT orders/{id}
     *
     * @param   int     $id             Id of order to update
     * @param   array   $request_data   Datas
     * @return  int|array
     *
     * @throws  400     RestException   Field missing
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while updating the order
     */
    function putOrder($id, $request_data = null)
    {
        if (! DolibarrApiAccess::$user->rights->commande->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->commande->fetch($id);
        if (! $result) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->commande->$field = $value;
        }

        // Update availability
        if (!empty($this->commande->availability_id)) {
            if ($this->commande->availability($this->commande->availability_id) < 0)
                throw new RestException(400, 'Error while updating availability');
        }

        if ($this->commande->update(DolibarrApiAccess::$user) > 0)
        {
            return $this->getOrder($id);
        }
        else
        {
            throw new RestException(500, $this->commande->error);
        }
    }

    /**
     * Delete order
     *
     * @url	DELETE orders/{id}
     *
     * @param   int     $id         Order ID
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while deleting the order
     */
    function deleteOrder($id)
    {
        if(! DolibarrApiAccess::$user->rights->commande->supprimer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if( ! $this->commande->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when delete order : '.$this->commande->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Order deleted'
            )
        );
    }

    /**
     * Validate an order
     *
     * If you get a bad value for param notrigger check, provide this in body
     * {
     *   "idwarehouse": 0,
     *   "notrigger": 0
     * }
     *
     * @url POST orders/{id}/validate
     *
     * @param   int     $id             Order ID
     * @param   int     $idwarehouse    Warehouse ID
     * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  array
     *
     * @throws  304     RestException   Nothing done
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while validating order
     */
    function validateOrder($id, $idwarehouse=0, $notrigger=0)
    {
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->commande->valid(DolibarrApiAccess::$user, $idwarehouse, $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when validating Order: '.$this->commande->error);
        }
        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->commande->fetchObjectLinked();

        return $this->_cleanObjectData($this->commande);
    }

    /**
     * Tag the order as validated (opened)
     *
     * Function used when order is reopend after being closed.
     *
     * @url POST orders/{id}/reopen
     *
     * @param  int   $id       Id of the order
     * @return int|array
     *
     * @throws  304     RestException   Nothing done
     * @throws  400     RestException   Field missing
     * @throws  401     RestException   Insufficient rights
     * @throws  405     RestException   Error while opening again the order
     */
    function reopenOrder($id)
    {
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        if(empty($id)) {
            throw new RestException(400, 'Order ID is mandatory');
        }
        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->commande->set_reopen(DolibarrApiAccess::$user);
        if( $result < 0) {
            throw new RestException(405, $this->commande->error);
        }else if( $result == 0) {
            throw new RestException(304);
        }

        return $result;
    }

    /**
     * Classify the order as invoiced. Could be also called setbilled
     *
     * @url POST orders/{id}/setinvoiced
     *
     * @param   int   $id           Id of the order
     * @return  array
     *
     * @throws  400     RestException   Field missing
     * @throws  401     RestException   Insufficient rights
     */
    function setinvoicedOrder($id)
    {
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        if(empty($id)) {
            throw new RestException(400, 'Order ID is mandatory');
        }
        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->commande->classifyBilled(DolibarrApiAccess::$user);
        if( $result < 0) {
            throw new RestException(400, $this->commande->error);
        }

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->commande->fetchObjectLinked();

        return $this->_cleanObjectData($this->commande);
    }

    /**
     * Close an order (Classify it as "Delivered")
     *
     * @url POST orders/{id}/close
     *
     * @param   int     $id             Order ID
     * @param   int     $notrigger      Disabled triggers
     * @return  array
     *
     * @throws  304     RestException   Nothing done
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while closing order
     */
    function closeOrder($id, $notrigger=0)
    {
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->commande->cloture(DolibarrApiAccess::$user, $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already closed');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when closing Order: '.$this->commande->error);
        }

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->commande->fetchObjectLinked();

        return $this->_cleanObjectData($this->commande);
    }

    /**
     * Set an order to draft
     *
     * @url POST orders/{id}/settodraft
     *
     * @param   int     $id             Order ID
     * @param   int 	$idwarehouse    Warehouse ID to use for stock change (Used only if option STOCK_CALCULATE_ON_VALIDATE_ORDER is on)
     * @return  array
     *
     * @throws  304     RestException   Nothing done
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while closing order
     */
    function settodraftOrder($id, $idwarehouse=-1)
    {
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->commande->set_draft(DolibarrApiAccess::$user, $idwarehouse);
        if ($result == 0) {
            throw new RestException(304, 'Nothing done. May be object is already closed');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when closing Order: '.$this->commande->error);
        }

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->commande->fetchObjectLinked();

        return $this->_cleanObjectData($this->commande);
    }

    /**
     * Create an order using an existing proposal.
     *
     * @url POST orders/createfromproposal/{proposalid}
     *
     * @param   int   $proposalid       Id of the proposal
     * @return  array
     *
     * @throws  400     RestException   Field missing
     * @throws  401     RestException   Insufficient rights
     * @throws  405     RestException   Error while creating order from proposal
     */
    function createOrderFromProposal($proposalid)
    {
        require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

        if(! DolibarrApiAccess::$user->rights->propal->lire) {
            throw new RestException(401, "Insufficient rights");
        }
        if(! DolibarrApiAccess::$user->rights->commande->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        if(empty($proposalid)) {
            throw new RestException(400, 'Proposal ID is mandatory');
        }

        $propal = new Propal($this->db);
        $result = $propal->fetch($proposalid);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->commande->createFromProposal($propal, DolibarrApiAccess::$user);
        if( $result < 0) {
            throw new RestException(405, $this->commande->error);
        }
        $this->commande->fetchObjectLinked();

        return $this->_cleanObjectData($this->commande);
    }

    /**
     * Validate fields before create or update object
     *
     * @param   array           $data   Array with data to verify
     * @return  array
     *
     * @throws  400     RestException   Field missing
     */
    function _validateOrder($data)
    {
        $commande = array();

        foreach (self::$FIELDS_ORDER as $key => $field) {
            if (is_array($field)) {
                foreach($field as $fieldValue) {
                    if (!isset($data[$key][$fieldValue])) {
                        throw new RestException(400, "$fieldValue field missing");
                    }
                }
                $commande[$key] = $data[$key];
            } else {
                if (!isset($data[$field]))
                    throw new RestException(400, "$field field missing");
                $commande[$field] = $data[$field];
            }
        }

        return $commande;
    }


    //
    // API Invoices
    //

    /**
     * Get properties of a invoice object
     *
     * Return an array with invoice informations
     *
     * @url GET invoices/{id}
     *
     * @param 	int 	        $id     ID of invoice
     * @return 	array|mixed     Data without useless information
     *
     * @throws  401     RestException   Insufficient rights
     */
    function getInvoice($id)
    {
        if(! DolibarrApiAccess::$user->rights->facture->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->invoice->fetchObjectLinked();
        $this->invoice->fetch_thirdparty();
        $this->_fetch_benefactor($this->invoice);
        return $this->_cleanObjectData($this->invoice);
    }

    /**
     * List invoices
     *
     * Get a list of invoices
     *
     * @url GET invoices
     *
     * @param   string	    $sortfield	      Sort field
     * @param   string	    $sortorder	      Sort order
     * @param   int		    $limit		      Limit for list
     * @param   int		    $page		      Page number
     * @param   string   	$thirdparty_ids	  Thirdparty ids to filter orders of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param   string	    $status		      Filter by invoice status : draft | unpaid | paid | cancelled
     * @param   string      $sqlfilters       Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array       Array of invoice objects
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  503     RestException   Error when retrieve invoice list
     */
    function indexInvoice($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 0, $page = 0, $thirdparty_ids='', $status='', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->facture->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // get API user
        $userSocId = DolibarrApiAccess::$user->societe_id;

        // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
        $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as t";

        // external
        if ($userSocId > 0) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture_extrafields as ef ON ef.fk_object = t.rowid";
            $sql .= self::_sqlElementListForExternalUser($userSocId, $search_sale);
            $sql .= " AND t.entity IN (" . getEntity('facture') . ")";
        }
        // internal
        else {
            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

            $sql .= ' WHERE (t.entity IN (' . getEntity('facture') . ')';
            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql .= " AND t.fk_soc = sc.fk_soc";
            if ($search_sale > 0) $sql .= " AND t.fk_soc = sc.fk_soc";        // Join for the needed table to filter by sale

            // Filter by status
            if ($status == 'draft') $sql .= " AND t.fk_statut IN (0)";
            if ($status == 'unpaid') $sql .= " AND t.fk_statut IN (1)";
            if ($status == 'paid') $sql .= " AND t.fk_statut IN (2)";
            if ($status == 'cancelled') $sql .= " AND t.fk_statut IN (3)";
            // Insert sale filter
            if ($search_sale > 0) {
                $sql .= " AND sc.fk_user = " . $search_sale;
            }
            $sql .= ")";

            // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
            $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;
            if ($socids) {
                $sql .= ' OR (t.entity IN (' . getEntity('facture') . ')';
                // Filter by status
                if ($status == 'draft') $sql .= " AND t.fk_statut IN (0)";
                if ($status == 'unpaid') $sql .= " AND t.fk_statut IN (1)";
                if ($status == 'paid') $sql .= " AND t.fk_statut IN (2)";
                if ($status == 'cancelled') $sql .= " AND t.fk_statut IN (3)";
                $sql .= " AND t.fk_soc IN (" . $socids . "))";
            }
            $sql .= " GROUP BY rowid";
        }

        // Add sql filters
        if ($sqlfilters) {
            if (!DolibarrApi::_checkFilters($sqlfilters)) {
                throw new RestException(503, 'Error when validating parameter sqlfilters ' . $sqlfilters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) . ")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        $result = $db->query($sql);
        if ($result)
        {
            $i=0;
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $invoice_static = new Facture($db);
                if($invoice_static->fetch($obj->rowid)) {
                    $invoice_static->fetchObjectLinked();
                    $invoice_static->fetch_thirdparty();
                    $this->_fetch_benefactor($invoice_static);
                    $obj_ret[] = $this->_cleanObjectData($invoice_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve invoice list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            return [];
        }
        return $obj_ret;
    }

    /**
     * Create invoice object
     *
     * @url POST invoices
     *
     * @param array $request_data   Request datas
     * @return int                  ID of invoice
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error when creating invoice
     */
    function postInvoice($request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        // Check mandatory fields
        $result = $this->_validateInvoice($request_data);

        foreach($request_data as $field => $value) {
            $this->invoice->$field = $value;
        }
        if(! array_keys($request_data,'date')) {
            $this->invoice->date = dol_now();
        }
        /* We keep lines as an array
         if (isset($request_data["lines"])) {
            $lines = array();
            foreach ($request_data["lines"] as $line) {
                array_push($lines, (object) $line);
            }
            $this->invoice->lines = $lines;
        }*/

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if ($this->invoice->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating invoice", array_merge(array($this->invoice->error), $this->invoice->errors));
        }
        return $this->invoice->id;
    }

    /**
     * Get lines of an invoice
     *
     * @url	GET invoices/{id}/lines
     *
     * @param   int   $id             Id of invoice
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function getLinesInvoice($id)
    {
        if(! DolibarrApiAccess::$user->rights->facture->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->invoice->getLinesArray();
        $result = array();
        foreach ($this->invoice->lines as $line) {
            array_push($result,$this->_cleanObjectData($line));
        }
        return $result;
    }

    /**
     * Add a line to a given invoice
     *
     * @url	POST invoices/{id}/lines
     *
     * @param   int   $id             Id of invoice to update
     * @param   array $request_data   InvoiceLine data
     * @return  bool|array
     *
     * @throws  400     RestException   Error while creating invoice line
     * @throws  401     RestException   Insufficient rights
     */
    function postLineInvoice($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $request_data = (object) $request_data;
        $updateRes = $this->invoice->addline(
            $request_data->desc,
            $request_data->subprice,
            $request_data->qty,
            $request_data->tva_tx,
            $request_data->localtax1_tx,
            $request_data->localtax2_tx,
            $request_data->fk_product,
            $request_data->remise_percent,
            $request_data->date_start,
            $request_data->date_end,
            0,
            $request_data->info_bits,
            $request_data->fk_remise_except,
            'HT',
            0,
            $request_data->product_type,
            $request_data->rang,
            $request_data->special_code,
            $request_data->origin,
            $request_data->origin_id,
            0,
            $request_data->fk_fournprice,
            $request_data->pa_ht,
            $request_data->label,
            $request_data->array_options,
            $request_data->situation_percent,
            $request_data->prev_id,
            $request_data->fk_unit,
            $request_data->multicurrency_subprice
        );

        if ($updateRes > 0) {
            return $updateRes;

        }
        return false;
    }

    /**
     * Update a line to a given invoice
     *
     * @url	PUT invoices/{id}/lines/{lineid}
     *
     * @param   int     $id             Id of invoice to update
     * @param   int     $lineid         Id of line to update
     * @param   array   $request_data   InvoiceLine data
     * @return  bool|array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function putLineInvoice($id, $lineid, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $request_data = (object) $request_data;
        $updateRes = $this->invoice->updateline(
            $lineid,
            $request_data->desc,
            $request_data->subprice,
            $request_data->qty,
            $request_data->remise_percent,
            $request_data->date_start,
            $request_data->date_end,
            $request_data->tva_tx,
            $request_data->localtax1_tx,
            $request_data->localtax2_tx,
            'HT',
            $request_data->info_bits,
            $request_data->product_type,
            $request_data->fk_parent_line,
            0,
            $request_data->fk_fournprice,
            $request_data->pa_ht,
            $request_data->label,
            $request_data->special_code,
            $request_data->array_options,
            $request_data->situation_percent,
            $request_data->fk_unit,
            $request_data->multicurrency_subprice
        );

        if ($updateRes > 0) {
            $result = $this->getInvoice($id);
            unset($result->line);
            return $this->_cleanObjectData($result);
        }
        return false;
    }

    /**
     * Delete a line to a given invoice
     *
     * @url	DELETE invoices/{id}/lines/{lineid}
     *
     * @param   int   $id             Id of invoice to update
     * @param   int   $lineid         Id of line to delete
     * @return  bool|array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function deleteLineInvoice($id, $lineid)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $updateRes = $this->invoice->deleteline($lineid);
        if ($updateRes > 0) {
            return $this->getInvoice($id);
        }
        return false;
    }

    /**
     * Update invoice
     *
     * @url PUT invoices/{id}
     *
     * @param   int   $id             Id of invoice to update
     * @param   array $request_data   Datas
     * @return  bool|array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function putInvoice($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->invoice->$field = $value;
        }

        if($this->invoice->update($id, DolibarrApiAccess::$user))
            return $this->getInvoice($id);

        return false;
    }

    /**
     * Delete invoice
     *
     * @url DELETE invoices/{id}
     *
     * @param   int     $id   Invoice ID
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while deleting the invoice
     */
    function deleteInvoice($id)
    {
        if(! DolibarrApiAccess::$user->rights->facture->supprimer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if( $this->invoice->delete($id) < 0)
        {
            throw new RestException(500);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Invoice deleted'
            )
        );
    }

    /**
     * Validate an invoice
     *
     * @url POST invoices/{id}/validate
     *
     * @param   int     $id             Invoice ID
     * @param   int     $idwarehouse    Warehouse ID
     * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  array
     * FIXME An error 403 is returned if the request has an empty body.
     * Error message: "Forbidden: Content type `text/plain` is not supported."
     * Workaround: send this in the body
     * {
     *   "idwarehouse": 0,
     *   "notrigger": 0
     * }
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while validating invoice
     */
    function validateInvoice($id, $idwarehouse=0, $notrigger=0)
    {
        if(! DolibarrApiAccess::$user->rights->facture->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->invoice->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->invoice->validate(DolibarrApiAccess::$user, '', $idwarehouse, $notrigger);
        if ($result == 0) {
            throw new RestException(500, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when validating Invoice: '.$this->invoice->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Invoice validated (Ref='.$this->invoice->ref.')'
            )
        );
    }

    /**
     * Validate fields before create or update object
     *
     * @param   array|null    $data       Datas to validate
     * @return  array
     *
     * @throws  400     RestException   Field missing
     */
    function _validateInvoice($data)
    {
        $invoice = array();

        foreach (self::$FIELDS_INVOICE as $key => $field) {
            if (is_array($field)) {
                foreach($field as $fieldValue) {
                    if (!isset($data[$key][$fieldValue])) {
                        throw new RestException(400, "$fieldValue field missing");
                    }
                }
                $invoice[$key] = $data[$key];
            } else {
                if (!isset($data[$field]))
                    throw new RestException(400, "$field field missing");
                $invoice[$field] = $data[$field];
            }
        }

        return $invoice;
    }


    //
    // API Interventions
    //

    /**
     * Get properties of an intervention object
     *
     * Return an array with intervention informations
     *
     * @url GET interventions/{id}
     *
     * @param       int             $id         ID of Expense Report
     * @return 	    array|mixed                 Data without useless information
     *
     * @throws  401     RestException   Insufficient rights
     */
    function getIntervention($id)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->fichinter->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->fichinter->fetchObjectLinked();
        $this->fichinter->fetch_thirdparty();
        foreach ($this->fichinter->lines as $k => $line) {
            $this->fichinter->lines[$k]->fetch_optionals();
        }
        $this->_fetch_benefactor($this->fichinter);
        return $this->_cleanObjectData($this->fichinter);
    }

    /**
     * List of interventions
     *
     * Return a list of interventions
     *
     * @url GET interventions
     *
     * @param   string          $sortfield          Sort field
     * @param   string          $sortorder          Sort order
     * @param   int             $limit		        Limit for list
     * @param   int             $page		        Page number
     * @param   string          $thirdparty_ids     Thirdparty ids to filter orders of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param   string          $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array           Array of order objects
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  503     RestException   Error when retrieve intervention list
     */
    function indexIntervention($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // get API user
        $userSocId = DolibarrApiAccess::$user->societe_id;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."fichinter as t";

        // external
        if ($userSocId > 0) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "fichinter_extrafields as ef ON ef.fk_object = t.rowid";
            $sql .= self::_sqlElementListForExternalUser($userSocId, $search_sale);
            $sql .= " AND t.entity IN (" . getEntity('fichinter') . ")";
			// Add sql filters
        if ($sqlfilters) {
            if (!DolibarrApi::_checkFilters($sqlfilters)) {
                throw new RestException(503, 'Error when validating parameter sqlfilters ' . $sqlfilters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) . ")";
        }
        }
        // internal
        else {
            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

            $sql .= ' WHERE (t.entity IN (' . getEntity('intervention') . ')';

			        // Add sql filters
        if ($sqlfilters) {
            if (!DolibarrApi::_checkFilters($sqlfilters)) {
                throw new RestException(503, 'Error when validating parameter sqlfilters ' . $sqlfilters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) . ")";
        }

            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql .= " AND t.fk_soc = sc.fk_soc";
            if ($search_sale > 0) $sql .= " AND t.fk_soc = sc.fk_soc";        // Join for the needed table to filter by sale
            // Insert sale filter
            if ($search_sale > 0) {
                $sql .= " AND sc.fk_user = " . $search_sale;
            }
            $sql .= ")";

            // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
            $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;
            if ($socids) {
                $sql .= ' OR (t.entity IN (' . getEntity('intervention') . ')';
                $sql .= " AND t.fk_soc IN (" . $socids . "))";
            }
            $sql .= " GROUP BY rowid";
        }



        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        dol_syslog("API Rest request");
        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i = 0;
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $fichinter_static = new Fichinter($db);
                if($fichinter_static->fetch($obj->rowid)) {
                    $fichinter_static->fetchObjectLinked();
                    $fichinter_static->fetch_thirdparty();
                    foreach ($fichinter_static->lines as $k => $line) {
                        $fichinter_static->lines[$k]->fetch_optionals();
                    }
                    $this->_fetch_benefactor($fichinter_static);
                    $obj_ret[] = $this->_cleanObjectData($fichinter_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve fichinter list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            return $obj_ret;
        }

        return $obj_ret;
    }

    /**
     * Create intervention object
     *
     * @url POST interventions
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of intervention
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error when creating intervention
     */
    function postIntervention($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        // Check mandatory fields
        $result = $this->_validateIntervention($request_data);
        foreach($request_data as $field => $value) {
            $this->fichinter->$field = $value;
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if ($this->fichinter->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating fichinter", array_merge(array($this->fichinter->error), $this->fichinter->errors));
        }

        return $this->fichinter->id;
    }

    /**
     * Get lines of an intervention
     *
     * @url	GET interventions/{id}/lines
     *
     * @param   int   $id             Id of intervention
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function getLinesIntervention($id)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->fichinter->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->fichinter->getLinesArray();
        $result = array();
        foreach ($this->fichinter->lines as $line) {
            $line->fetch_optionals();
            array_push($result,$this->_cleanObjectData($line));
        }
        return $result;
    }

    /**
     * Update intervention general fields (won't touch lines of intervention)
     *
     * @url PUT interventions/{id}
     *
     * @param   int   $id             Id of intervention to update
     * @param   array $request_data   Datas
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while updating the intervention
     */
    function putIntervention($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->fichinter->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->fichinter->$field = $value;
        }

        if ($this->fichinter->update(DolibarrApiAccess::$user) > 0)	{
            return $this->getIntervention($id);
        } else {
            throw new RestException(500, $this->fichinter->error);
        }
    }

    /**
     * Add a line to given intervention
     *
     * @url POST interventions/{id}/lines
     *
     * @param 	int   	$id             Id of intervention to update
     * @param   array   $request_data   Request data
     * @return  int|array
     *
     * @throws  400     RestException   Error while creating intervention line
     * @throws  401     RestException   Insufficient rights
     */
    function postLineIntervention($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Check mandatory fields
        $result = $this->_validateLineIntervention($request_data);

        $result = $this->fichinter->fetch($id);
        if (! $result) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if ($this->fichinter->statut == 0 ||  $this->fichinter->statut == 1) {
            $request_data = (object) $request_data;

            // insert line
            $line = new FichinterLigne($this->db);
            $line->fk_fichinter = $id;
            $line->desc         = $request_data->desc;
            $line->datei        = $request_data->datei;
            $line->duration     = $request_data->duration;

            if (is_array($request_data->array_options) && count($request_data->array_options)>0) {
                $line->array_options = $request_data->array_options;
            }

            $updateRes = $line->insert(DolibarrApiAccess::$user);

            if ($updateRes >= 0) {
                return $line->rowid;
            } else {
                throw new RestException(400, $this->fichinter->error);
            }
        } else {
            throw new RestException(500, 'Error when adding Intervention line: Bad status='.$this->fichinter->statut);
        }
    }

    /**
     * Update a line of given intervention
     *
     * @url	PUT interventions/{id}/lines/{lineid}
     *
     * @param   int   $id             Id of intervention to update
     * @param   int   $lineid         Id of line to update
     * @param   array $request_data   Intervention line data
     * @return  array
     *
     * @throws  400     RestException   Error while updating intervention line
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while updating the intervention line
     */
    function putLineIntervention($id, $lineid, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->fichinter->fetch($id);
        if($result <= 0) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $request_data = (object) $request_data;

        $ficheinterline = new FichinterLigne($this->db);
        $result = $ficheinterline->fetch($lineid);
        if ($result <= 0) {
            return [];
        }

        if ($ficheinterline->id != $lineid) {
            throw new RestException(500, 'Error when updating Intervention line: line id='.$lineid.' not found');
        }

        if ($this->fichinter->statut == 0 || $this->fichinter->statut == 1) {
            if (isset($request_data->desc))             $ficheinterline->desc = $request_data->desc;
            if (isset($request_data->datei))            $ficheinterline->datei = $request_data->datei;
            if (isset($request_data->duration))         $ficheinterline->duration = $request_data->duration;
            if (isset($request_data->array_options) && is_array($request_data->array_options) && count($request_data->array_options)) {
                $ficheinterline->array_options = $request_data->array_options;
            }
            $updateRes = $ficheinterline->update(DolibarrApiAccess::$user);

            if ($updateRes < 0) {
                throw new RestException(400, $this->fichinter->error);
            } else {
                $result = $this->getIntervention($id);
                unset($result->line);
                return $this->_cleanObjectData($result);
            }
        } else {
            throw new RestException(500, 'Error when updating Intervention line: Bad status='.$this->fichinter->statut);
        }
    }

    /**
     * Delete a line of given intervention
     *
     * @url	DELETE interventions/{id}/lines/{lineid}
     *
     * @param   int   $id             Id of intervention to update
     * @param   int   $lineid         Id of line to delete
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  405     RestException   Error while deleting the intervention line
     */
    function deleteLineIntervention($id, $lineid)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->fichinter->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        // TODO Check the lineid $lineid is a line of ojbect

        //$updateRes = $this->fichinter->deleteline($lineid, DolibarrApiAccess::$user);
        if ($this->fichinter->statut <= 1)
        {
            $this->db->begin();

            $line=new FichinterLigne($this->db);

            if($line->fetch($lineid) < 0){
                //Line has already been deleted
                $updateRes = 1;
                $this->db->commit();
            }
            else if ($line->deleteline(DolibarrApiAccess::$user) < 0)
            {
                $this->db->rollback();
                $updateRes = -1;
                $this->fichinter->error = $this->db->lasterror();
            }
            else
            {
                $this->db->commit();
                $updateRes = 1;
            }
        }
        else
        {
            $this->fichinter->error='ErrorDeleteLineNotAllowedByObjectStatus';
            $updateRes = -2;
        }

        if ($updateRes > 0) {
            return $this->getIntervention($id);
        } else {
            throw new RestException(403, $this->fichinter->error);
        }
    }

    /**
     * Delete intervention
     *
     * @url DELETE interventions/{id}
     *
     * @param   int     $id         Intervention ID
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while deleting the intervention
     */
    function deleteIntervention($id)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->supprimer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->fichinter->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if( ! $this->fichinter->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when delete intervention : '.$this->fichinter->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Intervention deleted'
            )
        );

    }

    /**
     * Validate an intervention
     *
     * If you get a bad value for param notrigger check, provide this in body
     * {
     *   "notrigger": 0
     * }
     *
     * @url POST interventions/{id}/validate
     *
     * @param   int $id             Intervention ID
     * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  array
     *
     * @throws  304     RestException   Nothing done
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while validating intervention
     */
    function validateIntervention($id, $notrigger=0)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->fichinter->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->fichinter->setValid(DolibarrApiAccess::$user, $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when validating Intervention: '.$this->commande->error);
        }

        $this->fichinter->fetchObjectLinked();

        return $this->_cleanObjectData($this->fichinter);
    }

    /**
     * Close an intervention
     *
     * @url POST interventions/{id}/close
     *
     * @param   int 	$id             Intervention ID
     * @return  array
     *
     * @throws  304     RestException   Nothing done
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while closing intervention
     */
    function closeIntervention($id)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer)
        {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->fichinter->fetch($id);
        if (! $result) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->fichinter->setStatut(3);

        if ($result == 0) {
            //throw new RestException(304, 'Error nothing done. May be object is already closed');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when closing Intervention: '.$this->fichinter->errorsToString());
        }

        $this->fichinter->fetchObjectLinked();

        return $this->_cleanObjectData($this->fichinter);
    }

    /**
     * Tag the intervention as validated (opened)
     *
     * Function used when intervention is reopened after being closed.
     *
     * @url POST interventions/{id}/reopen
     *
     * @param  int   $id       Id of the intervention
     * @return int|array
     *
     * @throws  304     RestException   Nothing done
     * @throws  400     RestException   Field missing
     * @throws  401     RestException   Insufficient rights
     * @throws  405     RestException   Error while opening again the intervention
     */
    function reopenIntervention($id)
    {
        global $conf, $db, $langs;

        // module not active
        if (empty($conf->synergiestech->enabled)) {
           throw new RestException(500, 'Error when re-opening Intervention : Module SynergiesTech disabled');
        }

        if(! DolibarrApiAccess::$user->rights->synergiestech->fichinter->reopen) {
            throw new RestException(401, "Insufficient rights");
        }
        if(empty($id)) {
            throw new RestException(400, 'Intervention ID is mandatory');
        }
        $result = $this->fichinter->fetch($id);
        if( ! $result) {
            return [];
        }

        //if ($this->fichinter->statut != 2 /* invoiced */ && $this->fichinter->statut != 3 /* done */) {
        //    throw new RestException(500, 'Error when re-opening Intervention : Bad status='.$this->fichinter->statut);
        //}

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        dol_include_once('/synergiestech/lib/synergiestech.lib.php');
        $langs->load('synergiestech@synergiestech');

        $msg_error = '';
        $result = synergiestech_reopen_intervention($db, $this->fichinter, DolibarrApiAccess::$user, $msg_error);

        if ($result <= 0) {
            throw new RestException(405, $this->fichinter->error);
        } elseif($result == 0) {
            throw new RestException(304);
        }

        return $result;
    }

    /**
     * Validate fields before create or update object
     *
     * @param   array $data   Data to validate
     * @return  array
     *
     * @throws  400     RestException       Field missing
     */
    function _validateIntervention($data)
    {
        $fichinter = array();

        foreach (self::$FIELDS_INTERVENTION as $key => $field) {
            if (is_array($field)) {
                foreach($field as $fieldValue) {
                    if (!isset($data[$key][$fieldValue])) {
                        throw new RestException(400, "$fieldValue field missing");
                    }
                }
                $fichinter[$key] = $data[$key];
            } else {
                if (!isset($data[$field]))
                    throw new RestException(400, "$field field missing");
                $fichinter[$field] = $data[$field];
            }
        }

        return $fichinter;
    }

    /**
     * Validate fields before create or update object
     *
     * @param   array $data   Data to validate
     * @return  array
     *
     * @throws  400     RestException       Field missing
     */
    function _validateLineIntervention($data)
    {
        $fichinter = array();

        foreach (self::$FIELDSLINE_INTERVENTION as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $fichinter[$field] = $data[$field];
        }

        return $fichinter;
    }


    //
    // API Shipments
    //

    /**
     * Get properties of a shipment object
     *
     * Return an array with shipment informations
     *
     * @url	GET shipments/{id}
     *
     * @param   int             $id         ID of shipment
     * @return  array|mixed     Data without useless information
     *
     * @throws  401     RestException   Insufficient rights
     */
    function getShipment($id)
    {
        if(! DolibarrApiAccess::$user->rights->expedition->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->shipment->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->shipment);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->shipment->fetchObjectLinked();
        $this->shipment->fetch_thirdparty();
        $this->_fetch_benefactor($this->shipment);
        return $this->_cleanObjectData($this->shipment);
    }

    /**
     * List shipments
     *
     * Get a list of shipments
     *
     * @url	GET shipments
     *
     * @param   string      $sortfield          Sort field
     * @param   string      $sortorder          Sort order
     * @param   int         $limit              Limit for list
     * @param   int         $page               Page number
     * @param   string      $thirdparty_ids     Thirdparty ids to filter shipments of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param   string      $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array       Array of shipment objects
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  503     RestException   Error when retrieve shipment list
     */
    function indexShipment($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->expedition->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // get API user
        $userSocId = DolibarrApiAccess::$user->societe_id;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."expedition as t";

        // external
        if ($userSocId > 0) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "expedition_extrafields as ef ON ef.fk_object = t.rowid";
            $sql .= self::_sqlElementListForExternalUser($userSocId, $search_sale);
            $sql .= " AND t.entity IN (" . getEntity('expedition') . ")";
        }
        // internal
        else {
            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

            $sql .= ' WHERE (t.entity IN (' . getEntity('expedition') . ')';
            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql .= " AND t.fk_soc = sc.fk_soc";
            if ($search_sale > 0) $sql .= " AND t.fk_soc = sc.fk_soc";        // Join for the needed table to filter by sale
            // Insert sale filter
            if ($search_sale > 0) {
                $sql .= " AND sc.fk_user = " . $search_sale;
            }
            $sql .= ")";

            // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
            $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;
            if ($socids) {
                $sql .= ' OR (t.entity IN (' . getEntity('expedition') . ')';
                $sql .= " AND t.fk_soc IN (" . $socids . "))";
            }
            $sql .= " GROUP BY rowid";
        }

        // Add sql filters
        if ($sqlfilters) {
            if (!DolibarrApi::_checkFilters($sqlfilters)) {
                throw new RestException(503, 'Error when validating parameter sqlfilters ' . $sqlfilters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) . ")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        dol_syslog("API Rest request");
        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i=0;
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $shipment_static = new Expedition($db);
                if($shipment_static->fetch($obj->rowid)) {
                    $shipment_static->fetchObjectLinked();
                    $shipment_static->fetch_thirdparty();
                    $this->_fetch_benefactor($shipment_static);
                    $obj_ret[] = $this->_cleanObjectData($shipment_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve shipment list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            return [];
        }
        return $obj_ret;
    }

    /**
     * Create shipment object
     *
     * @url	POST shipments
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of shipment
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error when creating shipment
     */
    function postShipment($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->expedition->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        // Check mandatory fields
        $result = $this->_validateShipment($request_data);

        foreach($request_data as $field => $value) {
            $this->shipment->$field = $value;
        }
        /*if (isset($request_data["lines"])) {
          $lines = array();
          foreach ($request_data["lines"] as $line) {
            array_push($lines, (object) $line);
          }
          $this->shipment->lines = $lines;
        }*/

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->shipment);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if ($this->shipment->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating shipment", array_merge(array($this->shipment->error), $this->shipment->errors));
        }

        return $this->shipment->id;
    }


    /**
     * Delete a line to given shipment
     *
     * @url	DELETE shipments/{id}/lines/{lineid}
     *
     * @param   int     $id             Id of shipment to update
     * @param   int     $lineid         Id of line to delete
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  405     RestException   Error while deleting the shipment line
     */
    function deleteLineShipment($id, $lineid)
    {
        if(! DolibarrApiAccess::$user->rights->expedition->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->shipment->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->shipment);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        // TODO Check the lineid $lineid is a line of ojbect
        $updateRes = $this->shipment->deleteline(DolibarrApiAccess::$user, $lineid);
        if ($updateRes > 0) {
            return $this->getShipment($id);
        }
        else
        {
            throw new RestException(405, $this->shipment->error);
        }
    }

    /**
     * Update shipment general fields (won't touch lines of shipment)
     *
     * @url	PUT shipments/{id}
     *
     * @param   int     $id             Id of shipment to update
     * @param   array   $request_data   Datas
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while updating the shipment
     */
    function putShipment($id, $request_data = null)
    {
        if (! DolibarrApiAccess::$user->rights->expedition->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->shipment->fetch($id);
        if (! $result) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->shipment);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->shipment->$field = $value;
        }

        if ($this->shipment->update(DolibarrApiAccess::$user) > 0)
        {
            return $this->getShipment($id);
        }
        else
        {
            throw new RestException(500, $this->shipment->error);
        }
    }

    /**
     * Delete shipment
     *
     * @url	DELETE shipments/{id}
     *
     * @param   int     $id         Shipment ID
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while deleting the shipment
     */
    function deleteShipment($id)
    {
        if(! DolibarrApiAccess::$user->rights->shipment->supprimer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->shipment->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->shipment);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if( ! $this->shipment->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when deleting shipment : '.$this->shipment->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Shipment deleted'
            )
        );
    }

    /**
     * Validate a shipment
     *
     * This may record stock movements if module stock is enabled and option to
     * decrease stock on shipment is on.
     *
     * @url POST shipments/{id}/validate
     *
     * @param   int     $id             Shipment ID
     * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  array
     *
     * FIXME An error 403 is returned if the request has an empty body.
     * Error message: "Forbidden: Content type `text/plain` is not supported."
     * Workaround: send this in the body
     * {
     *   "notrigger": 0
     * }
     *
     * @throws  304     RestException   Nothing done
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while validating shipment
     */
    function validateShipment($id, $notrigger=0)
    {
        if(! DolibarrApiAccess::$user->rights->expedition->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->shipment->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->shipment);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->shipment->valid(DolibarrApiAccess::$user, $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when validating Shipment: '.$this->shipment->error);
        }
        $result = $this->shipment->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->shipment);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->shipment->fetchObjectLinked();
        return $this->_cleanObjectData($this->shipment);
    }

    /**
     * Validate fields before create or update object
     *
     * @param   array       $data   Array with data to verify
     * @return  array
     *
     * @throws  400     RestException   Field missing
     */
    function _validateShipment($data)
    {
        $shipment = array();

        foreach (self::$FIELDS_SHIPMENT as $key => $field) {
            if (is_array($field)) {
                foreach($field as $fieldValue) {
                    if (!isset($data[$key][$fieldValue])) {
                        throw new RestException(400, "$fieldValue field missing");
                    }
                }
                $shipment[$key] = $data[$key];
            } else {
                if (!isset($data[$field]))
                    throw new RestException(400, "$field field missing");
                $shipment[$field] = $data[$field];
            }
        }

        return $shipment;
    }


    //
    // API Contracts
    //

    /**
     * Get properties of a contrat object
     *
     * Return an array with contrat informations
     *
     * @url GET contracts/{id}
     *
     * @param   int             $id         ID of shipment
     * @return  array|mixed     Data without useless information
     *
     * @throws  401     RestException   Insufficient rights
     */
    function getContract($id)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->contract->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->contract);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->contract->fetchObjectLinked();
        $this->contract->fetch_thirdparty();
        $this->_fetch_benefactor($this->contract);
        return $this->_cleanObjectData($this->contract);
    }

    /**
     * List contracts
     *
     * Get a list of contracts
     *
     * @url GET contracts
     *
     * @param   string          $sortfield	        Sort field
     * @param   string          $sortorder	        Sort order
     * @param   int             $limit		        Limit for list
     * @param   int             $page		        Page number
     * @param   string          $thirdparty_ids     Thirdparty ids to filter contracts of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param   string          $sqlfilters         Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array                               Array of contract objects
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  503     RestException   Error when retrieve contract list
     */
    function indexContract($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->contrat->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // get API user
        $userSocId = DolibarrApiAccess::$user->societe_id;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."contrat as t";

        // external
        if ($userSocId > 0) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "contrat_extrafields as ef ON ef.fk_object = t.rowid";
            $sql .= self::_sqlElementListForExternalUser($userSocId, $search_sale);
            $sql .= " AND t.entity IN (" . getEntity('contrat') . ")";
        }
        // internal
        else {
            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

            $sql.= ' WHERE (t.entity IN ('.getEntity('contrat').')';
            if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
            if ($search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";		// Join for the needed table to filter by sale
            // Insert sale filter
            if ($search_sale > 0)
            {
                $sql .= " AND sc.fk_user = ".$search_sale;
            }
            $sql.= ")";


            // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
            $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;
            if ($socids) {
                $sql.= ' OR (t.entity IN ('.getEntity('contrat').')';
                $sql.= " AND t.fk_soc IN (".$socids."))";
            }
            $sql.= " GROUP BY rowid";
        }

        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        dol_syslog("API Rest request");
        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i=0;
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $contrat_static = new Contrat($db);
                if($contrat_static->fetch($obj->rowid)) {
                    $contrat_static->fetchObjectLinked();
                    $contrat_static->fetch_thirdparty();
                    $this->_fetch_benefactor($contrat_static);
                    $obj_ret[] = $this->_cleanObjectData($contrat_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve contrat list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            return [];
        }
        return $obj_ret;
    }

    /**
     * Create contract object
     *
     * @url POST contracts
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of contrat
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error when creating contract
     */
    function postContract($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        // Check mandatory fields
        $result = $this->_validateContract($request_data);

        foreach($request_data as $field => $value) {
            $this->contract->$field = $value;
        }
        /*if (isset($request_data["lines"])) {
          $lines = array();
          foreach ($request_data["lines"] as $line) {
            array_push($lines, (object) $line);
          }
          $this->contract->lines = $lines;
        }*/

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->contract);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if ($this->contract->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating contract", array_merge(array($this->contract->error), $this->contract->errors));
        }

        return $this->contract->id;
    }

    /**
     * Get lines of a contract
     *
     * @url	GET contracts/{id}/lines
     *
     * @param   int   $id             Id of contract
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function getLinesContract($id)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->contract->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->contract);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->contract->getLinesArray();
        $result = array();
        foreach ($this->contract->lines as $line) {
            array_push($result,$this->_cleanObjectData($line));
        }
        return $result;
    }

    /**
     * Add a line to given contract
     *
     * @url	POST contracts/{id}/lines
     *
     * @param   int   $id             Id of contrat to update
     * @param   array $request_data   Contractline data
     * @return  bool|array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function postLineContract($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->contract->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->contract);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }
        $request_data = (object) $request_data;
        $updateRes = $this->contract->addline(
            $request_data->desc,
            $request_data->subprice,
            $request_data->qty,
            $request_data->tva_tx,
            $request_data->localtax1_tx,
            $request_data->localtax2_tx,
            $request_data->fk_product,
            $request_data->remise_percent,
            $request_data->date_start,			// date_start = date planned start, date ouverture = date_start_real
            $request_data->date_end,			// date_end = date planned end, date_cloture = date_end_real
            $request_data->HT,
            $request_data->subprice_excl_tax,
            $request_data->info_bits,
            $request_data->fk_fournprice,
            $request_data->pa_ht,
            $request_data->array_options,
            $request_data->fk_unit,
            $request_data->rang
        );

        if ($updateRes > 0) {
            return $updateRes;

        }
        return false;
    }

    /**
     * Update a line to given contract
     *
     * @url	PUT contracts/{id}/lines/{lineid}
     *
     * @param   int   $id             Id of contrat to update
     * @param   int   $lineid         Id of line to update
     * @param   array $request_data   Contractline data
     * @return  bool|array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function putLineContract($id, $lineid, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->contract->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->contract);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $request_data = (object) $request_data;

        $updateRes = $this->contract->updateline(
            $lineid,
            $request_data->desc,
            $request_data->subprice,
            $request_data->qty,
            $request_data->remise_percent,
            $request_data->date_ouveture_prevue,
            $request_data->date_fin_validite,
            $request_data->tva_tx,
            $request_data->localtax1_tx,
            $request_data->localtax2_tx,
            $request_data->date_ouverture,
            $request_data->date_cloture,
            'HT',
            $request_data->info_bits,
            $request_data->fk_fourn_price,
            $request_data->pa_ht,
            $request_data->array_options,
            $request_data->fk_unit
        );

        if ($updateRes > 0) {
            $result = $this->getContract($id);
            unset($result->line);
            return $this->_cleanObjectData($result);
        }

        return false;
    }

    /**
     * Activate a service line of a given contract
     *
     * @url	PUT contracts/{id}/lines/{lineid}/activate
     *
     * @param   int   	    $id             Id of contract to activate
     * @param   int   	    $lineid         Id of line to activate
     * @param   string      $datestart		{@from body}  Date start        {@type timestamp}
     * @param   string      $dateend		{@from body}  Date end          {@type timestamp}
     * @param   string      $comment  		{@from body}  Comment
     * @return  bool|array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function activateLineContract($id, $lineid, $datestart, $dateend = null, $comment = null)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->contract->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->contract);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $updateRes = $this->contract->active_line(DolibarrApiAccess::$user, $lineid, $datestart, $dateend, $comment);

        if ($updateRes > 0) {
            $result = $this->getContract($id);
            unset($result->line);
            return $this->_cleanObjectData($result);
        }

        return false;
    }

    /**
     * Unactivate a service line of a given contract
     *
     * @url	PUT contracts/{id}/lines/{lineid}/unactivate
     *
     * @param   int   	$id             Id of contract to activate
     * @param   int   	$lineid         Id of line to activate
     * @param   string  $datestart		{@from body}  Date start        {@type timestamp}
     * @param   string  $comment  		{@from body}  Comment
     * @return  bool|array
     *
     * @throws  401     RestException   Insufficient rights
     */
    function unactivateLineContract($id, $lineid, $datestart, $comment = null)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->contract->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->contract);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $updateRes = $this->contract->close_line(DolibarrApiAccess::$user, $lineid, $datestart, $comment);

        if ($updateRes > 0) {
            $result = $this->getContract($id);
            unset($result->line);
            return $this->_cleanObjectData($result);
        }

        return false;
    }

    /**
     * Delete a line to given contract
     *
     * @url	DELETE contracts/{id}/lines/{lineid}
     *
     * @param   int   $id             Id of contract to update
     * @param   int   $lineid         Id of line to delete
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  405     RestException   Error while deleting the contract line
     */
    function deleteLineContract($id, $lineid)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->contract->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->contract);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        // TODO Check the lineid $lineid is a line of ojbect

        $updateRes = $this->contract->deleteline($lineid, DolibarrApiAccess::$user);
        if ($updateRes > 0) {
            return $this->getContract($id);
        }
        else
        {
            throw new RestException(405, $this->contract->error);
        }
    }

    /**
     * Update contract general fields (won't touch lines of contract)
     *
     * @url	PUT contracts/{id}
     *
     * @param   int     $id             Id of contrat to update
     * @param   array   $request_data   Datas
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while updating the contract
     */
    function putContract($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->contract->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->contract);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }
        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->contract->$field = $value;
        }

        if ($this->contract->update(DolibarrApiAccess::$user) > 0)
        {
            return $this->getContract($id);
        }
        else
        {
            throw new RestException(500, $this->contract->error);
        }
    }

    /**
     * Delete contract
     *
     * @url DELETE contracts/{id}
     *
     * @param   int     $id         Contract ID
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while deleting the contract
     */
    function deleteContract($id)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->supprimer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->contract->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->contract);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        if( ! $this->contract->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when delete contract : '.$this->contract->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Contract deleted'
            )
        );
    }

    /**
     * Validate an contract
     *
     * @url POST contracts/{id}/validate
     *
     * @param   int $id             Contract ID
     * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  array
     *
     * FIXME An error 403 is returned if the request has an empty body.
     * Error message: "Forbidden: Content type `text/plain` is not supported."
     * Workaround: send this in the body
     * {
     *   "notrigger": 0
     * }
     *
     * @throws  304     RestException   Nothing done
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while validating contract
     */
    function validateContract($id, $notrigger=0)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->contract->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->contract);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->contract->validate(DolibarrApiAccess::$user, '', $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when validating Contract: '.$this->contract->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Contract validated (Ref='.$this->contract->ref.')'
            )
        );
    }

    /**
     * Close all services of a contract
     *
     * @url POST contracts/{id}/close
     *
     * @param   int $id             Contract ID
     * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
     * @return  array
     *
     * FIXME An error 403 is returned if the request has an empty body.
     * Error message: "Forbidden: Content type `text/plain` is not supported."
     * Workaround: send this in the body
     * {
     *   "notrigger": 0
     * }
     *
     * @throws  304     RestException   Nothing done
     * @throws  401     RestException   Insufficient rights
     * @throws  500     RestException   Error while closing contract
     */
    function closeContract($id, $notrigger=0)
    {
        if(! DolibarrApiAccess::$user->rights->contrat->creer) {
            throw new RestException(401, "Insufficient rights");
        }
        $result = $this->contract->fetch($id);
        if( ! $result ) {
            return [];
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->contract);
        if (! $hasPerm) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->contract->closeAll(DolibarrApiAccess::$user, $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already close');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when closing Contract: '.$this->contract->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Contract closed (Ref='.$this->contract->ref.'). All services were closed.'
            )
        );
    }

    /**
     * Validate fields before create or update object
     *
     * @param   array           $data   Array with data to verify
     * @return  array
     * @throws  400     RestException   Field missing
     */
    function _validateContract($data)
    {
        $contrat = array();

        foreach (self::$FIELDS_CONTRACT as $key => $field) {
            if (is_array($field)) {
                foreach($field as $fieldValue) {
                    if (!isset($data[$key][$fieldValue])) {
                        throw new RestException(400, "$fieldValue field missing");
                    }
                }
                $contrat[$key] = $data[$key];
            } else {
                if (!isset($data[$field]))
                    throw new RestException(400, "$field field missing");
                $contrat[$field] = $data[$field];
            }
        }

        return $contrat;
    }

    /**
     *  Fetch all benefactor of the object
     *
     * @param   object          $object         Object to fetch
     *
     * @return  void
     **/
    function _fetch_benefactor(&$object)
    {
        $object->cr_thirdparty_benefactor = null;

        if (!isset($object->array_options['options_companyrelationships_fk_soc_benefactor'])) {
            $object->fetch_optionals();
        }

        if ($object->array_options['options_companyrelationships_fk_soc_benefactor'] > 0) {
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            $societe = new Societe($this->db);
            if ($societe->fetch($object->array_options['options_companyrelationships_fk_soc_benefactor']) > 0) {
                $object->cr_thirdparty_benefactor = $societe;
            }
        }
    }

    //
    // API Equipement
    //

    /**
     * List equipements
     *
     * Get a list of equipements
     *
     * @url	GET equipements
     *
     * @param   string      $sortfield	            Sort field
     * @param   string	    $sortorder	            Sort order
     * @param   int		    $limit		            Limit for list
     * @param   int         $page		            Page number
     * @param   string      $thirdparty_ids	        Thirdparty ids to filter commercial proposals. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param   string      $sqlfilters             Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
     * @return  array       Array of order objects
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  503     RestException   Error when retrieve equipement list
     */
    function indexEquipement($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        if(! DolibarrApiAccess::$user->rights->equipement->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // get API user
        $userSocId = DolibarrApiAccess::$user->societe_id;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql  = "SELECT t.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "equipement as t";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "equipement_extrafields as tef ON tef.fk_object = t.rowid";

        // external
        if ($userSocId > 0) {
            // list of benefactor ids of this user
            $sqlInBenefactorIds = implode(',', $this->benefactor_ids);

            // list of watcher ids of this user
            $sqlInWatcherIds = implode(',', $this->watcher_ids);

            if ($search_sale > 0) $sql .=  " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scfourn ON scfourn.fk_soc = t.fk_soc_fourn AND scfourn.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale
            if ($search_sale > 0) $sql .=  " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scclient ON scclient.fk_soc = t.fk_soc_client AND scclient.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale

            $sql .= " WHERE t.entity IN (" . getEntity('equipement') . ")";
            $sql .= " AND (";
            // equipment thirdparty of API user
            $sql .= "t.fk_soc_fourn = " . $userSocId . " OR t.fk_soc_client = " . $userSocId;

            // equipment seller
            if ($search_sale > 0) {
                $sql .= " OR scfourn.fk_user = " . $search_sale . " OR scclient.fk_user = " . $search_sale;
            }

            // equipment benefactor of API user
            if (!empty($sqlInBenefactorIds)) {
                $sql .= " OR t.fk_soc_fourn IN (" . $sqlInBenefactorIds . ") OR t.fk_soc_client IN (" . $sqlInBenefactorIds . ")";
            }

            // equipment watcher of API user
            if (!empty($sqlInWatcherIds)) {
                $sql .= " OR t.fk_soc_fourn IN (" . $sqlInWatcherIds . ") OR t.fk_soc_client IN (" . $sqlInWatcherIds . ")";
            }

            $sql .= ")";
        }
        // internal
        else {
            if ($search_sale > 0) $sql .=  " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scfourn ON scfourn.fk_soc = t.fk_soc_fourn AND scfourn.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale
            if ($search_sale > 0) $sql .=  " LEFT JOIN " . MAIN_DB_PREFIX . "societe_commerciaux as scclient ON scclient.fk_soc = t.fk_soc_client AND scclient.fk_user = " . $search_sale; // We need this table joined to the select in order to filter by sale

            $sql .= " WHERE (t.entity IN (" . getEntity('equipement') . ")";
            // equipment seller
            if ($search_sale > 0) {
                $sql .= " AND (scfourn.fk_user = " . $search_sale . " OR scclient.fk_user = " . $search_sale . ")";
            }
            $sql .= ")";

            // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
            $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;
            if ($socids) {
                $sql .= " OR (";
                $sql .= "t.entity IN (" . getEntity('societe') . ")";
                $sql .= " AND (t.fk_soc_fourn IN (" . $socids . ") OR t.fk_soc_client IN (" . $socids . "))";
                $sql .= ")";
            }

            $sql .= " GROUP BY rowid";
        }

        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
            $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        dol_syslog("API Rest request");
        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            $i = 0;
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $equipement_static = new Equipement($db);
                if($equipement_static->fetch($obj->rowid)) {
                    $equipement_static->fetchObjectLinked();
					$equipement_static->fetch_optionals();
                    $equipement_static->fetch_product();
                    $equipement_static->fetch_thirdparty();
                    $obj_ret[] = $this->_cleanObjectData($equipement_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve equipement list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            return [];
        }
        return $obj_ret;
    }

    //
    // API thirdparties
    //

    /**
     * List my thirdparties
     *
     * Get a list of thirdparties (where this user is a commercial affect at the company and his company if external user, with benefactors and principals company of these companies)
     *
     * @url	GET mythirdparties
     *
     * @param   string  $sortfield      Sort field
     * @param   string  $sortorder      Sort order
     * @param   int     $limit          Limit for list
     * @param   int     $page           Page number
     * @param   int     $mode           Set to 1 to show only customers
     *                                  Set to 2 to show only prospects
     *                                  Set to 3 to show only those are not customer neither prospect
     * @param   string  $sqlfilters     Other criteria to filter answers separated by a comma. Syntax example "(t.nom:like:'TheCompany%') and (t.date_creation:<:'20160101')"
     *
     * @return  array                   Array of order objects
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  503     RestException   Error when retrieve my thirdparties where i'm a commercial
     * @throws  503     RestException   Error when retrieve all ids of sub principals and benefactors of these companies
     * * @throws  503     RestException   Error when retrieve all ids of sub watchers of these companies
     */
    function indexMyThirdparties($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $mode=0, $sqlfilters = '')
    {
        global $db;

        $obj_ret = array();

        if (!DolibarrApiAccess::$user->rights->societe->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $company_ids = array();
        $company_details = array();

        if (DolibarrApiAccess::$user->societe_id > 0) {
            $company_ids[DolibarrApiAccess::$user->societe_id] = DolibarrApiAccess::$user->societe_id;
        }

        // Get all ids of my companies where i'm a commercial
        $sql = "SELECT t.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "societe AS t";
        $sql .= " , " . MAIN_DB_PREFIX . "societe_commerciaux AS sc";
        $sql .= " WHERE t.entity IN (" . getEntity('societe') . ')';
        $sql .= " AND t.rowid = sc.fk_soc AND sc.fk_user = " . DolibarrApiAccess::$user->id;
        $sql .= " GROUP BY t.rowid";
        $result = $db->query($sql);
        if ($result) {
            while ($obj = $db->fetch_object($result)) {
                $company_ids[$obj->rowid] = $obj->rowid;
            }
        } else {
            throw new RestException(503, 'Error when retrieve my thirdparties where i\'m a commercial : ' . $db->lasterror());
        }

        if (count($company_ids) == 0) {
            return [];
        }

        // Get all ids of sub principals and benefactors of these companies
        $sql = "SELECT cr.fk_soc AS principal_id, cr.fk_soc_benefactor AS benefactor_id";
        $sql .= " FROM " . MAIN_DB_PREFIX . "companyrelationships AS cr";
        $sql .= " WHERE (cr.fk_soc IN (" . implode(',', $company_ids) . ")";
        $sql .= " OR cr.fk_soc_benefactor IN (" . implode(',', $company_ids) . "))";
        $sql .= " AND cr.relation_type = " . CompanyRelationships::RELATION_TYPE_BENEFACTOR;
        $result = $db->query($sql);
        if ($result) {
            while ($obj = $db->fetch_object($result)) {
                $company_ids[$obj->principal_id] = $obj->principal_id;
                $company_ids[$obj->benefactor_id] = $obj->benefactor_id;
                $company_details[$obj->benefactor_id]['principal_ids'][$obj->principal_id] = $obj->principal_id;
                $company_details[$obj->principal_id]['benefactor_ids'][$obj->benefactor_id] = $obj->benefactor_id;
            }
        } else {
            throw new RestException(503, 'Error when retrieve all ids of sub principals and benefactors of these companies : ' . $db->lasterror());
        }

        // Get all ids of sub watchers of these companies
        $sql = "SELECT cr.fk_soc, cr.fk_soc_relation";
        $sql .= " FROM " . MAIN_DB_PREFIX . "companyrelationships AS cr";
        $sql .= " WHERE (cr.fk_soc IN (" . implode(',', $company_ids) . ")";
        $sql .= " OR cr.fk_soc_relation IN (" . implode(',', $company_ids) . "))";
        $sql .= " AND cr.relation_type = " . CompanyRelationships::RELATION_TYPE_WATCHER;
        $result = $db->query($sql);
        if ($result) {
            while ($obj = $db->fetch_object($result)) {
                $company_ids[$obj->fk_soc] = $obj->fk_soc;
                $company_ids[$obj->fk_soc_relation] = $obj->fk_soc_relation;
                $company_details[$obj->fk_soc]['watcher_ids'][$obj->fk_soc_relation] = $obj->fk_soc_relation;
            }
        } else {
            throw new RestException(503, 'Error when retrieve all ids of sub watchers of these companies : ' . $db->lasterror());
        }

        $sql = "SELECT t.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "societe AS t";
        $sql .= " , " . MAIN_DB_PREFIX . "c_stcomm AS st";
        $sql .= " WHERE t.fk_stcomm = st.id";
        if ($mode == 1) $sql .= " AND t.client IN (1, 3)";
        if ($mode == 2) $sql .= " AND t.client IN (2, 3)";
        if ($mode == 3) $sql .= " AND t.client IN (0)";
        $sql .= ' AND t.entity IN (' . getEntity('societe') . ')';
        $sql .= ' AND t.rowid IN (' . implode(',', $company_ids) . ')';
        // Add sql filters
        if ($sqlfilters) {
            if (!DolibarrApi::_checkFilters($sqlfilters)) {
                throw new RestException(503, 'Error when validating parameter sqlfilters ' . $sqlfilters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters) . ")";
        }
        $sql .= " GROUP BY t.rowid";

        $sql .= $db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $db->plimit($limit + 1, $offset);
        }

        $i = 0;
        $result = $db->query($sql);
        if ($result) {
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min) {
                $obj = $db->fetch_object($result);
                $soc_static = new Societe($db);
                if ($soc_static->fetch($obj->rowid)) {
                    $soc_static->fetchObjectLinked();
                    $soc_static->thirdparty_principal_ids = !empty($company_details[$obj->rowid]['principal_ids']) ? array_values($company_details[$obj->rowid]['principal_ids']) : array();
                    $soc_static->thirdparty_benefactor_ids = !empty($company_details[$obj->rowid]['benefactor_ids']) ? array_values($company_details[$obj->rowid]['benefactor_ids']) : array();
                    $soc_static->thirdparty_watcher_ids = !empty($company_details[$obj->rowid]['watcher_ids']) ? array_values($company_details[$obj->rowid]['watcher_ids']) : array();
                    $obj_ret[] = $this->_cleanObjectData($soc_static);
                }
                $i++;
            }
        } else {
            throw new RestException(503, 'Error when retrieve thirdparties : ' . $db->lasterror());
        }
        if (!count($obj_ret)) {
            return [];
        }
        return $obj_ret;
    }

    //
    // API documents
    //

    /**
	 * Download a document.
	 *
	 * Note that, this API is similar to using the wrapper link "documents.php" to download a file (used for
	 * internal HTML links of documents into application), but with no need to have a session cookie (the token is used instead).
	 *
	 * @param   string  $module_part    Name of module or area concerned by file download ('facture', 'agenda', ...)
	 * @param   string  $original_file  Relative path with filename, relative to modulepart (for example: IN201701-999/IN201701-999.pdf)
	 * @return  array                   List of documents
	 *
	 * @throws 400
	 * @throws 401
	 * @throws 404
     * @throws 200
     * @throws 500
	 *
     * @url	GET documents/download
	 */
	public function indexDocuments($module_part, $original_file='')
    {
        global $conf, $langs, $db;

        if (empty($module_part)) {
            throw new RestException(400, 'bad value for parameter modulepart');
        }
        if (empty($original_file)) {
            throw new RestException(400, 'bad value for parameter original_file');
        }

        //--- Finds and returns the document
        $entity = $conf->entity;
        $dirname = dirname($original_file);
        if (strstr($dirname, '/')) {
            $dirname = dirname($dirname);
        }
        $refname = basename($dirname . "/");

        //--------------------------------------------------------------
        // Open-DSI - Modification - Begin
        //
        //Modif par Alexis LAURIER - we try to merge elementtype return by agenda module to elementtype accepted by document

        switch ($module_part) {
            case 'shipping':
                $module_part = 'expedition';
                break;
				case 'intervention':
				$module_part = 'ficheinter';
				break;

            default:
        }

        $output_dir = "";
        $check_access = dol_check_secure_access_document($module_part, $original_file, $entity, DolibarrApiAccess::$user, $refname, 'read', true);

        $companyrelationships_modulepart_check = array_flip(array(
            'propal', 'proposal', 'commande', 'order', 'shipment', 'expedition', 'facture', 'invoice',
            'fichinter', 'ficheinter', 'intervention', 'interventions', 'contract', 'contrat'
        ));


        if (isset($companyrelationships_modulepart_check[$module_part])) {
            $object = null;
            if ($module_part == 'propal' || $module_part == 'proposal') {
                require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
                $object = new Propal($this->db);
                $result = $object->fetch('', $refname);
                if (!$result) {
                    return [];
                }
            } elseif ($module_part == 'commande' || $module_part == 'order') {
                require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
                $object = new Commande($this->db);
                $result = $object->fetch('', $refname);
                if (!$result) {
                    return [];
                }
            } elseif ($module_part == 'shipment' || $module_part == 'expedition') {
                require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
                $ref = basename(str_replace('sending/', '', $dirname . "/"));
                $object = new Expedition($this->db);
                $result = $object->fetch('', $ref);
                if (!$result) {
                    return [];
                }
            } elseif ($module_part == 'facture' || $module_part == 'invoice') {
                require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
                $object = new Facture($this->db);
                $result = $object->fetch('', $refname);

                if (!$result) {
                    return [];
                }
            } elseif ($module_part == 'fichinter' || $module_part == 'ficheinter' || $module_part == 'intervention' || $module_part == 'interventions') {
                require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
                $object = new Fichinter($this->db);
                $result = $object->fetch('', $refname);
                if (!$result) {
                    return [];
                }
            } elseif ($module_part == 'contract' || $module_part == 'contrat') {
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                $object = new Contrat($this->db);
                $result = $object->fetch('', $refname);
                if (!$result) {
                    return [];
                }
            }

            if (isset($object)) {
                $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($object);
                if (!$hasPerm) {
                    throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
                }
            }
            $accessallowed = true;
        } else {
            $accessallowed = $check_access['accessallowed'];
            $sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
            $sqlprotectagainstexternalsapi = $check_access['sqlprotectagainstexternalsapi'];

            if (DolibarrApiAccess::$user->societe_id > 0) {
                if ($sqlprotectagainstexternalsapi) {
                    $resql = $db->query($sqlprotectagainstexternalsapi);
                    if ($resql) {
                        if ($db->num_rows($resql) == 0) throw new RestException(401);
                    } else {
                        throw new RestException(401);
                    }
                } else {
                    throw new RestException(401);
                }
            }


            if ($module_part == 'agenda' && $conf->eventconfidentiality->enabled) // Wrapping pour les actions
            {
                require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                $object = new ActionComm($this->db);
                $result = $object->fetch('', $refname);
                $entity = $object->entity;
                if ($result <= 0) {
                    throw new RestException(500, "Error while retrieve the event object.", ['details' => $this->_getErrors($object)]);
                }

                dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
                $eventconfidentiality = new EventConfidentiality($this->db);

                // Get mode for the user and event
                $mode = $eventconfidentiality->getModeForUserAndEvent(DolibarrApiAccess::$user, $object->id);
                if ($mode < 0) {
                    throw new RestException(500, "Error while retrieve the mode of confidentiality for the event.", ['details' => $this->_getErrors($eventconfidentiality)]);
                }

                if ($mode != EventConfidentiality::MODE_VISIBLE) {
                    throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
                }
            } elseif ($module_part == 'societe' || $module_part == 'thirdparty' || $module_part == 'thirdparties') // Wrapping pour les societes
            {
                require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

                if (!DolibarrApiAccess::$user->rights->societe->lire || !DolibarrApiAccess::$user->rights->societe->read_file) {
                    throw new RestException(401);
                }
                $object = new Societe($this->db);
                $result = $object->fetch($refname, '');
                if (!$result) {
                    return [];
                }

                //--------------------------------------------------------------
                // Open-DSI - Modification - Begin
                //
                $output_dir = $conf->societe->multidir_output[$object->entity] . "/";
                $entity = $object->entity;
                //
                // Open-DSI - Modification - End
                //--------------------------------------------------------------
            }
        }
        $check_access = dol_check_secure_access_document($module_part, $original_file, $entity, DolibarrApiAccess::$user, $refname, 'read', true);

        $original_file = $check_access['original_file'];
        //
        // Open-DSI - Modification - End
        //--------------------------------------------------------------
        if (preg_match('/\.\./', $original_file) || preg_match('/[<>|]/', $original_file)) {
            throw new RestException(401);
        }
        if (!$accessallowed) {
            throw new RestException(401);
        }

        $filename = basename($original_file);
        $original_file_osencoded = dol_osencode($original_file);    // New file name encoded in OS encoding charset

        if (!file_exists($original_file_osencoded)) {
            return [];
        }

        $file_content = file_get_contents($original_file_osencoded);

        return array('filename' => $filename, 'content' => base64_encode($file_content), 'encoding' => 'MIME base64 (base64_encode php function, http://php.net/manual/en/function.base64-encode.php)', 'Content-Type' => mime_content_type($original_file_osencoded));
    }

	/**
	 * Return the list of documents of a dedicated element (from its ID or Ref)
	 *
	 * @param   string 	$modulepart		Name of module or area concerned ('thirdparty', 'member', 'proposal', 'order', 'invoice', 'shipment', 'project', 'agenda',  ...)
	 * @param	int		$id				ID of element
	 * @param	string	$ref			Ref of element
	 * @param	string	$sortfield		Sort criteria ('','fullname','relativename','name','date','size')
	 * @param	string	$sortorder		Sort order ('asc' or 'desc')
	 * @param	bool	$showlogo		Show logo (true or false) for thirdparty, contact, user, member only (default false)
	 * @param	bool	$active			Show active logo only (true or false) for thirdparty, contact, user, member only and if $showlogo is true (default true)
	 * @return	array					Array of documents with path
	 *
	 * @throws 200
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 500
	 *
     * @url	GET documents
	 */
	function getDocumentsListByElement($modulepart, $id=0, $ref='', $sortfield='', $sortorder='', $showlogo=false, $active=true)
    {
        global $conf;

        $active = true; //Forçage pour Synergies-Tech à retirer en cas de PR

        $tableandshare = '';
        //--------------------------------------------------------------
        // Open-DSI - Modification - Begin
        //
        $feature2 = '';
        $dbt_keyfield = 'fk_soc';
        $dbt_select = 'rowid';
        $companyrelationships_modulepart_check = array_flip(array(
            'propal', 'proposal', 'commande', 'order', 'shipment', 'expedition', 'facture', 'invoice',
            'fichinter', 'ficheinter', 'intervention', 'interventions', 'contract', 'contrat'
        ));
        //
        // Open-DSI - Modification - End
        //--------------------------------------------------------------

        if (empty($modulepart)) {
            throw new RestException(400, 'bad value for parameter modulepart');
        }

        if (empty($id) && empty($ref)) {
            throw new RestException(400, 'bad value for parameter id or ref');
        }

        $id = (empty($id) ? 0 : $id);

        if ($modulepart == 'societe' || $modulepart == 'thirdparty' || $modulepart == 'thirdparties') // Wrapping pour les societes
        {
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

            if (!DolibarrApiAccess::$user->rights->societe->lire || !DolibarrApiAccess::$user->rights->societe->read_file) {
                throw new RestException(401);
            }

            $object = new Societe($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }
            if ($showlogo == false) {
                $upload_dir = $conf->societe->multidir_output[$object->entity] . "/" . $object->id;
            } else if ($showlogo == true) {
                $upload_dir = $conf->societe->multidir_output[$object->entity] . "/" . $object->id;
                $upload_dir_logo = $conf->societe->multidir_output[$object->entity] . "/" . $object->id . "/logos/";
                $logo_name = $object->logo;
            }
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            $output_dir = $conf->societe->multidir_output[$object->entity] . "/";
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
            $tableandshare = 'societe';
            $modulepart = 'societe';
        } else if ($modulepart == 'contact') // Wrapping pour les contacts
        {
            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

            if (!DolibarrApiAccess::$user->rights->societe->contact->lire) {
                throw new RestException(401);
            }

            $object = new Contact($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }
            $object->entity = 1;
            if ($showlogo == false) {
                $upload_dir = $conf->societe->multidir_output[$object->entity] . "/contact/" . $object->id;
            } else if ($showlogo == true) {
                $upload_dir = $conf->societe->multidir_output[$object->entity] . "/contact/" . $object->id;
                $upload_dir_logo = $conf->societe->multidir_output[$object->entity] . "/contact/" . $object->id . "/photos/";
                $logo_name = $object->photo;
            }
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            $output_dir = $conf->societe->multidir_output[$object->entity] . "/contact/";
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
            $tableandshare = 'socpeople';
        } else if ($modulepart == 'adherent' || $modulepart == 'member') {
            require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';

            if (!DolibarrApiAccess::$user->rights->adherent->lire) {
                throw new RestException(401);
            }

            $object = new Adherent($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }

            if ($showlogo == false) {
                $upload_dir = $conf->adherent->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'member');
            } else if ($showlogo == true) {
                $upload_dir = $conf->adherent->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'member');
                $upload_dir_logo = $conf->adherent->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'member') . 'photos/';
                $logo_name = $object->photo;
            }
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            $output_dir = $conf->adherent->dir_output . "/";
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
        } else if ($modulepart == 'propal' || $modulepart == 'proposal') {
            require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';

            if (!DolibarrApiAccess::$user->rights->propal->lire) {
                throw new RestException(401);
            }

            $object = new Propal($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }

            $upload_dir = $conf->propal->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'propal');
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            $output_dir = $conf->propal->dir_output . "/";
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
            $tableandshare = 'propal';
        } else if ($modulepart == 'commande' || $modulepart == 'order') {
            require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

            if (!DolibarrApiAccess::$user->rights->commande->lire) {
                throw new RestException(401);
            }

            $object = new Commande($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }

            $upload_dir = $conf->commande->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'commande');
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            $output_dir = $conf->commande->dir_output . "/";
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
            $tableandshare = 'commande';
        } else if ($modulepart == 'shipment' || $modulepart == 'expedition' || $modulepart == 'shipping') {
            require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';

            if (!DolibarrApiAccess::$user->rights->expedition->lire) {
                throw new RestException(401);
            }

            $object = new Expedition($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }

            $upload_dir = $conf->expedition->dir_output . "/sending/" . get_exdir(0, 0, 0, 1, $object, 'shipment');
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            $output_dir = $conf->expedition->dir_output . "/";
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
            $tableandshare = 'expedition';
        } else if ($modulepart == 'facture' || $modulepart == 'invoice') {
            require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

            if (!DolibarrApiAccess::$user->rights->facture->lire) {
                throw new RestException(401);
            }

            $object = new Facture($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }

            $upload_dir = $conf->facture->dir_output . "/" . get_exdir(0, 0, 0, 1, $object, 'invoice');
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            $output_dir = $conf->facture->dir_output . "/";
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
            $tableandshare = 'facture';
        } else if ($modulepart == 'product' || $modulepart == 'produit') // Wrapping pour les produits
        {
            require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

            if (!DolibarrApiAccess::$user->rights->produit->lire) {
                throw new RestException(401);
            }

            $object = new Product($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }

            $upload_dir = $conf->product->multidir_output[$object->entity] . "/" . get_exdir(0, 0, 0, 0, $object, 'product') . dol_sanitizeFileName($object->ref);
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            $output_dir = $conf->product->multidir_output[$object->entity] . "/";
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
            $tableandshare = 'product';
        } else if ($modulepart == 'service') // Wrapping pour les services
        {
            require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

            if (!DolibarrApiAccess::$user->rights->service->lire) {
                throw new RestException(401);
            }

            $object = new Product($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }

            $upload_dir = $conf->service->multidir_output[$object->entity] . "/" . get_exdir(0, 0, 0, 0, $object, 'product') . dol_sanitizeFileName($object->ref);
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            $output_dir = $conf->service->multidir_output[$object->entity] . "/";
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
            $tableandshare = 'product';
        } else if ($modulepart == 'user') // Wrapping for users
        {
            require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

            if (!DolibarrApiAccess::$user->rights->user->user->lire && DolibarrApiAccess::$user->id != $id) {
                throw new RestException(401);
            }

            $object = new User($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }

            $entitytouseforuserdir = $object->entity;
            if (empty($entitytouseforuserdir)) $entitytouseforuserdir = 1;

            if ($showlogo == false) {
                $upload_dir = $conf->user->multidir_output[$entitytouseforuserdir] . "/" . $object->id;
            } else if ($showlogo == true) {
                $upload_dir = $conf->user->multidir_output[$entitytouseforuserdir] . "/" . $object->id;
                $upload_dir_logo = $conf->user->multidir_output[$entitytouseforuserdir] . "/" . get_exdir($id, 2, 0, 0, $object, 'user');
                $logo_name = $object->photo;
            }
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            $output_dir = $conf->user->multidir_output[$entitytouseforuserdir] . "/";
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
            $tableandshare = 'user';
        } else if ($modulepart == 'fichinter' || $modulepart == 'ficheinter' || $modulepart == 'intervention' || $modulepart == 'interventions') // Wrapping for interventions
        {
            require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';

            if (!DolibarrApiAccess::$user->rights->ficheinter->lire) {
                throw new RestException(401);
            }

            $object = new Fichinter($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }

            $upload_dir = $conf->ficheinter->dir_output . '/' . $object->ref;
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            $output_dir = $conf->ficheinter->dir_output . "/";
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
            $tableandshare = 'fichinter';
        } else if ($modulepart == 'contract' || $modulepart == 'contrat') // Wrapping pour les contrats
        {
            require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';

            if (!DolibarrApiAccess::$user->rights->contrat->lire) {
                throw new RestException(401);
            }

            $object = new Contrat($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }

            $upload_dir = $conf->contrat->dir_output . '/' . $object->ref;
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            $output_dir = $conf->contrat->dir_output . "/";
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
            $tableandshare = 'contrat';
        }
        //--------------------------------------------------------------
        // Open-DSI - Modification - Begin
        //
        else if ($modulepart == 'agenda') // Wrapping pour les actions
        {
            require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

            $object = new ActionComm($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }

            if ($conf->eventconfidentiality->enabled) {
                dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
                $eventconfidentiality = new EventConfidentiality($this->db);

                // Get mode for the user and event
                $mode = $eventconfidentiality->getModeForUserAndEvent(DolibarrApiAccess::$user, $object->id);
                if ($mode < 0) {
                    throw new RestException(500, "Error while retrieve the mode of confidentiality for the event: ", ['details' => $this->_getErrors($eventconfidentiality)]);
                }

                if ($mode != EventConfidentiality::MODE_VISIBLE) {
                    throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
                }
            }

            if (!DolibarrApiAccess::$user->rights->agenda->allactions->read &&
                !(($object->authorid == DolibarrApiAccess::$user->id || $object->userownerid == DolibarrApiAccess::$user->id) && DolibarrApiAccess::$user->rights->agenda->myactions->read)
            ) {
                throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
            }

            $tableandshare = 'actioncomm&societe';
            $feature2 = 'myactions|allactions';
            $dbt_select = 'id';

            $upload_dir = $conf->agenda->dir_output . '/' . dol_sanitizeFileName($object->ref);
            $output_dir = $conf->agenda->dir_output . "/";
        }
        else if ($modulepart == 'equipement') // Wrapping pour les equipements
        {
            dol_include_once('/equipement/class/equipement.class.php');

            $object = new Equipement($this->db);
            $result = $object->fetch($id, $ref);
            if (!$result) {
                return [];
            }

            if (!DolibarrApiAccess::$user->rights->equipement->lire) {
                throw new RestException(401);
            }

            $upload_dir = $conf->equipement->dir_output . '/' . dol_sanitizeFileName($object->id);
            $output_dir = $conf->equipement->dir_output . "/";
            $tableandshare = 'equipement';
            $dbt_keyfield = 'fk_soc_client';
        }
        //
        // Open-DSI - Modification - End
        //--------------------------------------------------------------
        else {
            throw new RestException(500, 'Modulepart ' . $modulepart . ' not implemented yet.');
        }
        //--------------------------------------------------------------
        // Open-DSI - Modification - Begin
        //
        if (isset($companyrelationships_modulepart_check[$modulepart])) {
            $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($object);
            if (!$hasPerm) {
                throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
            }
        } elseif (!DolibarrApi::_checkAccessToResource($modulepart, $object->id, $tableandshare, $feature2, $dbt_keyfield, $dbt_select)) {
            throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
        }
        //
        // Open-DSI - Modification - End
        //--------------------------------------------------------------

        $filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
        if (!empty($upload_dir_logo)) {
            $filearraylogo = dol_dir_list($upload_dir_logo, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);

            //Add identifier for logo only
            $tmp_array = array();
            foreach ($filearraylogo as $logo) {
                $logo['type'] = 'logo';
                $tmp_array[] = $logo;
            }
            $filearraylogo = $tmp_array;

            if ($active == true) {
                $tmp_array = "";
                foreach ($filearraylogo as $logo) {
                    if ($logo['relativename'] == $logo_name) {
                        $tmp_array = $logo;
                    }
                }
                $filearraylogo = $tmp_array;
            }
            $filearray[] = $filearraylogo;
        }

        if (empty($filearray)) {
            return [];
        }

        //--------------------------------------------------------------
        // Open-DSI - Modification - Begin
        //
        foreach ($filearray as $k => $v) {
            $filearray[$k]['original_file'] = str_replace($output_dir, '', $v['fullname']);
			unset($filearray[$k]['path']);
			unset($filearray[$k]['fullname']);
        }
        //
        // Open-DSI - Modification - End
        //--------------------------------------------------------------

        return $filearray;
    }

    /**
	 * Upload a file.
	 *
	 * Test sample 1: { "filename": "mynewfile.txt", "modulepart": "facture", "ref": "FA1701-001", "subdir": "", "filecontent": "content text", "fileencoding": "", "overwriteifexists": "0" }.
     * Test sample 2: { "filename": "mynewfile.txt", "modulepart": "medias", "ref": "", "subdir": "image/mywebsite", "filecontent": "Y29udGVudCB0ZXh0Cg==", "fileencoding": "base64", "overwriteifexists": "0" }.
     * Test sample 2: { "filename": "mynewfile.txt", "modulepart": "agenda", "ref": "7215", "subdir": "", "filecontent": "Y29udGVudCB0ZXh0Cg==", "fileencoding": "base64", "overwriteifexists": "0" }.
	 *
	 * @param   string  $filename           Name of file to create ('FA1705-0123.txt')
	 * @param   string  $modulepart         Name of module or area concerned by file upload ('facture', 'project', 'project_task', 'agenda', ...)
	 * @param   string  $ref                Reference of object (This will define subdir automatically and store submited file into it)
	 * @param   string  $subdir       		Subdirectory (Only if ref not provided)
	 * @param   string  $filecontent        File content (string with file content. An empty file will be created if this parameter is not provided)
	 * @param   string  $fileencoding       File encoding (''=no encoding, 'base64'=Base 64) {@example '' or 'base64'}
	 * @param   int 	$overwriteifexists  Overwrite file if exists (1 by default)
     * @param	bool	$islogo		        The file is a logo (true or false) for thirdparty, contact, user, member only (default false)
     * @param   int     $id                 Id of object (This will define subdir automatically and store submited file into it) (dafault 0 not used)
     * @return	string					    File path of the uploaded file
	 *
	 * @throws 200
	 * @throws 400
	 * @throws 401
	 * @throws 404
	 * @throws 500
	 *
	 * @url POST documents/upload
	 */
	public function postDocuments($filename, $modulepart, $ref='', $subdir='', $filecontent='', $fileencoding='', $overwriteifexists=0, $islogo=false, $id=0)
    {
        global $db, $conf;

        if (empty(DolibarrApiAccess::$user->entity)) {
            DolibarrApiAccess::$user->entity = $conf->entity;
        }

        if (!DolibarrApiAccess::$user->rights->companyrelationships->api_documents->upload) {
            throw new RestException(401, "Insufficient rights");
        }

        if (DolibarrApiAccess::$user->societe_id > 0) {
            ///Disabled function for external user to avoid functionnal conflict with companyrelationshipsapi - Alexis LAURIER
            ///BEGIN
            throw new RestException(403);
            ///END
        }

        if (empty($modulepart)) {
            throw new RestException(400, 'Modulepart not provided.');
        }

        $newfilecontent = '';
        if (empty($fileencoding)) $newfilecontent = $filecontent;
        if ($fileencoding == 'base64') $newfilecontent = base64_decode($filecontent);

        $original_file = dol_sanitizeFileName($filename);

        // Define $uploadir
        $object = null;
        $entity = DolibarrApiAccess::$user->entity;
        if (empty($entity)) {
            throw new RestException(400, 'Wrong user entity=' . $entity);
        }
        if ($id>0 || $ref) {
            $tmpreldir = '';

            if ($modulepart == 'facture' || $modulepart == 'invoice') {
                $modulepart = 'facture';

                require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
                $object = new Facture($this->db);
            } elseif ($modulepart == 'project') {
                require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
                $object = new Project($this->db);
            } elseif ($modulepart == 'task' || $modulepart == 'project_task') {
                $modulepart = 'project_task';

                require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';
                $object = new Task($this->db);

                $task_result = $object->fetch('', $ref);

                // Fetching the tasks project is required because its out_dir might be a sub-directory of the project
                if ($task_result > 0) {
                    $project_result = $object->fetch_projet();

                    if ($project_result >= 0) {
                        $tmpreldir = dol_sanitizeFileName($object->project->ref) . '/';
                    }
                } else {
                    throw new RestException(500, 'Error while fetching Task ' . $ref);
                }
            }
            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            elseif ($modulepart == 'societe' || $modulepart == 'thirdparty' || $modulepart == 'thirdparties') // Wrapping pour les societes
            {
                require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                $object = new Societe($this->db);
            } else if ($modulepart == 'contact') // Wrapping pour les contacts
            {
                require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
                $object = new Contact($this->db);
            } else if ($modulepart == 'adherent' || $modulepart == 'member') {
                require_once DOL_DOCUMENT_ROOT . '/adherents/class/adherent.class.php';
                $object = new Adherent($this->db);
            } else if ($modulepart == 'propal' || $modulepart == 'proposal') {
                require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
                $object = new Propal($this->db);
            } else if ($modulepart == 'commande' || $modulepart == 'order') {
                require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
                $object = new Commande($this->db);
            } else if ($modulepart == 'shipment' || $modulepart == 'expedition' || $modulepart == 'shipping') {
                require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
                $object = new Expedition($this->db);
            } else if ($modulepart == 'product' || $modulepart == 'produit') // Wrapping pour les produits
            {
                require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
                $object = new Product($this->db);
            } else if ($modulepart == 'service') // Wrapping pour les services
            {
                require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
                $object = new Product($this->db);
            } else if ($modulepart == 'user') // Wrapping for users
            {
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $object = new User($this->db);
            } else if ($modulepart == 'fichinter' || $modulepart == 'ficheinter' || $modulepart == 'intervention' || $modulepart == 'interventions') // Wrapping for interventions
            {
                $modulepart = 'fichinter';
                require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
                $object = new Fichinter($this->db);
            } else if ($modulepart == 'contract' || $modulepart == 'contrat') // Wrapping pour les contrats
            {
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                $object = new Contrat($this->db);
            } else if ($modulepart == 'agenda') // Wrapping pour les actions
            {
                require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                $object = new ActionComm($this->db);
            } else if ($modulepart == 'equipement') // Wrapping pour les equipements
            {
                dol_include_once('/equipement/class/equipement.class.php');
                $object = new Equipement($this->db);
            }
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------
            // TODO Implement additional moduleparts
            else {
                throw new RestException(500, 'Modulepart ' . $modulepart . ' not implemented yet.');
            }

            if (is_object($object)) {
                if ($id > 0) {
                    $result = $object->fetch($id);
                } else {
                    $result = $object->fetch('', $ref);
                }

                if ($result == 0) {
                    throw new RestException(404, 'Object not found.');
                } elseif ($result < 0) {
                    throw new RestException(500, 'Error while fetching object.');
                }
            }

            if (!($object->id > 0)) {
                return [];
            }

            $relativefile = $tmpreldir . dol_sanitizeFileName($object->ref);

            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            if ($islogo) {
                if ($modulepart == 'societe' || $modulepart == 'thirdparty' || $modulepart == 'thirdparties') {
                    $relativefile = $relativefile . "/logos/";
                } else if ($modulepart == 'contact') {
                    $relativefile = $relativefile . "/photos/";
                } else if ($modulepart == 'adherent' || $modulepart == 'member') {
                    $relativefile = $relativefile . "/" . get_exdir(0, 0, 0, 1, $object, 'member') . 'photos/';
                } else if ($modulepart == 'user') {
                    $relativefile = $tmpreldir . get_exdir($object->id, 2, 0, 0, $object, 'user');
                }
            }
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------

            $tmp = dol_check_secure_access_document($modulepart, $relativefile, $entity, DolibarrApiAccess::$user, $ref, 'write');
            $upload_dir = $tmp['original_file'];    // No dirname here, tmp['original_file'] is already the dir because dol_check_secure_access_document was called with param original_file that is only the dir

            if (empty($upload_dir) || $upload_dir == '/') {
                throw new RestException(500, 'This value of modulepart does not support yet usage of ref. Check modulepart parameter or try to use subdir parameter instead of ref.');
            }
        } else {
            if ($modulepart == 'invoice') $modulepart = 'facture';

            $relativefile = $subdir;

            //--------------------------------------------------------------
            // Open-DSI - Modification - Begin
            //
            if ($islogo) {
                if ($modulepart == 'societe' || $modulepart == 'thirdparty' || $modulepart == 'thirdparties') {
                    $relativefile = $relativefile . "/logos/";
                } else if ($modulepart == 'contact') {
                    $relativefile = $relativefile . "/photos/";
                } else if ($modulepart == 'adherent' || $modulepart == 'member') {
                    $relativefile = $relativefile . "/" . get_exdir(0, 0, 0, 1, $object, 'member') . 'photos/';
                } else if ($modulepart == 'user') {
                    $relativefile = $relativefile . "/" . get_exdir($object->id, 2, 0, 0, $object, 'user');
                }
            }
            //
            // Open-DSI - Modification - End
            //--------------------------------------------------------------

            $tmp = dol_check_secure_access_document($modulepart, $relativefile, $entity, DolibarrApiAccess::$user, '', 'write');
            $upload_dir = $tmp['original_file'];    // No dirname here, tmp['original_file'] is already the dir because dol_check_secure_access_document was called with param original_file that is only the dir

            if (empty($upload_dir) || $upload_dir == '/') {
                throw new RestException(500, 'This value of modulepart does not support yet usage of ref. Check modulepart parameter or try to use subdir parameter instead of ref.');
            }
        }
        // $original_file here is still value of filename without any dir.

        $companyrelationships_modulepart_check = array_flip(array(
            'propal', 'proposal', 'commande', 'order', 'shipment', 'expedition', 'facture', 'invoice',
            'fichinter', 'ficheinter', 'intervention', 'interventions', 'contract', 'contrat'
        ));

        if (isset($companyrelationships_modulepart_check[$modulepart])) {
            $object_tmp = null;
            if ($modulepart == 'propal' || $modulepart == 'proposal') {
                require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
                $object_tmp = new Propal($this->db);
                $object_tmp->fetch($id, $ref);
                if (!($object_tmp->id > 0)) {
                    return [];
                }
            } elseif ($modulepart == 'commande' || $modulepart == 'order') {
                require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
                $object_tmp = new Commande($this->db);
                $object_tmp->fetch($id, $ref);
                if (!($object_tmp->id > 0)) {
                    return [];
                }
            } elseif ($modulepart == 'shipment' || $modulepart == 'expedition') {
                require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
                $object_tmp = new Expedition($this->db);
                $object_tmp->fetch($id, $ref);
                if (!($object_tmp->id > 0)) {
                    return [];
                }
            } elseif ($modulepart == 'facture' || $modulepart == 'invoice') {
                require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
                $object_tmp = new Facture($this->db);
                $object_tmp->fetch($id, $ref);
                if (!($object_tmp->id > 0)) {
                    return [];
                }
            } elseif ($modulepart == 'fichinter' || $modulepart == 'ficheinter' || $modulepart == 'intervention' || $modulepart == 'interventions') {
                require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
                $object_tmp = new Fichinter($this->db);
                $object_tmp->fetch($id, $ref);
                if (!($object_tmp->id > 0)) {
                    return [];
                }
            } elseif ($modulepart == 'contract' || $modulepart == 'contrat') {
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                $object_tmp = new Contrat($this->db);
                $object_tmp->fetch($id, $ref);
                if (!($object_tmp->id > 0)) {
                    return [];
                }
            }

            if (isset($object_tmp)) {
                $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($object_tmp);
                if (!$hasPerm) {
                    throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
                }
            }
        }

        $upload_dir = dol_sanitizePathName($upload_dir);

        $destfile = $upload_dir . '/' . $original_file;
        $destfiletmp = DOL_DATA_ROOT . '/admin/temp/' . $original_file;
        dol_delete_file($destfiletmp);
        //var_dump($original_file);exit;

        //--------------------------------------------------------------
        // Open-DSI - Modification - Begin
        //
        $dir = dirname($destfile);
        if (!dol_is_dir($dir)) {
            dol_mkdir($dir);
        }
        //
        // Open-DSI - Modification - End
        //--------------------------------------------------------------

        if (!dol_is_dir(dirname($destfile))) {
            throw new RestException(401, 'Directory not exists : ' . dirname($destfile));
        }

        if (!$overwriteifexists && dol_is_file($destfile)) {
            throw new RestException(500, "File with name '" . $original_file . "' already exists.");
        }

        $fhandle = @fopen($destfiletmp, 'w');
        if ($fhandle) {
            $nbofbyteswrote = fwrite($fhandle, $newfilecontent);
            fclose($fhandle);
            @chmod($destfiletmp, octdec($conf->global->MAIN_UMASK));
        } else {
            throw new RestException(500, "Failed to open file '" . $destfiletmp . "' for write");
        }

        $result = dol_move($destfiletmp, $destfile, 0, $overwriteifexists, 1);
        if (!$result) {
            throw new RestException(500, "Failed to move file into '" . $destfile . "'");
        }


        if($object && method_exists($object,"addThumbs")){
            $object->addThumbs($destfile);
        }

        //--------------------------------------------------------------
        // Open-DSI - Modification - Begin
        //
        if ($islogo && ($modulepart == 'societe' || $modulepart == 'thirdparty' || $modulepart == 'thirdparties' ||
                $modulepart == 'contact' || $modulepart == 'adherent' || $modulepart == 'member' || $modulepart == 'user')) {
            // Create thumbs
            if ($modulepart == 'societe' || $modulepart == 'thirdparty' || $modulepart == 'thirdparties') {
                $object->logo = dol_basename($destfile);
                $object->update($object->id, DolibarrApiAccess::$user);
            } else if ($modulepart == 'contact') {
                $object->photo = dol_basename($destfile);
                $object->update($object->id, DolibarrApiAccess::$user);
            } else if ($modulepart == 'adherent' || $modulepart == 'member') {
                $object->photo = dol_basename($destfile);
                $object->update(DolibarrApiAccess::$user);
            } else if ($modulepart == 'user') {
                $object->photo = dol_basename($destfile);
                $object->update(DolibarrApiAccess::$user);
            }
        }
        //
        // Open-DSI - Modification - End
        //--------------------------------------------------------------

        return dol_basename($destfile);
    }

    /**
	 * Delete a document.
	 *
	 * Note that, this API is similar to using the wrapper link "documents.php" to download a file (used for
	 * internal HTML links of documents into application), but with no need to have a session cookie (the token is used instead).
	 *
	 * @param   string  $module_part    Name of module or area concerned by file download ('facture', 'agenda', ...)
	 * @param   string  $original_file  Relative path with filename, relative to modulepart (for example: IN201701-999/IN201701-999.pdf)
     * @param	bool	$islogo		        The file is a logo (true or false) for thirdparty, contact, user, member only (default false)
	 * @return  array                   File deleted
	 *
	 * @throws 400
	 * @throws 401
	 * @throws 404
     * @throws 200
     * @throws 500
	 *
     * @url	DELETE documents/delete
	 */
	public function deleteDocuments($module_part, $original_file='', $islogo=false)
    {
        global $conf, $langs, $db;

        if (!DolibarrApiAccess::$user->rights->companyrelationships->api_documents->delete) {
            throw new RestException(401, "Insufficient rights");
        }

        if (empty($module_part)) {
            throw new RestException(400, 'bad value for parameter modulepart');
        }
        if (empty($original_file)) {
            throw new RestException(400, 'bad value for parameter original_file');
        }

        //--- Finds and returns the document
        $entity = $conf->entity;
        $dirname = dirname($original_file);
        if (strstr($dirname, '/')) {
            $dirname = dirname($dirname);
        }
        $refname = basename($dirname . "/");

        //--------------------------------------------------------------
        // Open-DSI - Modification - Begin
        //
        //Modif par Alexis LAURIER - we try to merge elementtype return by agenda module to elementtype accepted by document

switch ($module_part) {
            case 'shipping':
                $module_part = 'expedition';
                break;
				case 'intervention':
				$module_part = 'ficheinter';
				break;

            default:
        }

        $output_dir = "";
        $check_access = dol_check_secure_access_document($module_part, $original_file, $entity, DolibarrApiAccess::$user, $refname, 'delete', true);

        $companyrelationships_modulepart_check = array_flip(array(
            'propal', 'proposal', 'commande', 'order', 'shipment', 'expedition', 'facture', 'invoice',
            'fichinter', 'ficheinter', 'intervention', 'interventions', 'contract', 'contrat'
        ));

        if (isset($companyrelationships_modulepart_check[$module_part])) {
            $object = null;
            if ($module_part == 'propal' || $module_part == 'proposal') {
                require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
                $object = new Propal($this->db);
                $result = $object->fetch('', $refname);
                if (!$result) {
                    return [];
                }
            } elseif ($module_part == 'commande' || $module_part == 'order') {
                require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
                $object = new Commande($this->db);
                $result = $object->fetch('', $refname);
                if (!$result) {
                    return [];
                }
            } elseif ($module_part == 'shipment' || $module_part == 'expedition') {
                require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
                $ref = basename(str_replace('sending/', '', $dirname . "/"));
                $object = new Expedition($this->db);
                $result = $object->fetch('', $ref);
                if (!$result) {
                    return [];
                }
            } elseif ($module_part == 'facture' || $module_part == 'invoice') {
                require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
                $object = new Facture($this->db);
                $result = $object->fetch('', $refname);

                if (!$result) {
                    return [];
                }
            } elseif ($module_part == 'fichinter' || $module_part == 'ficheinter' || $module_part == 'intervention' || $module_part == 'interventions') {
                require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
                $object = new Fichinter($this->db);
                $result = $object->fetch('', $refname);
                if (!$result) {
                    return [];
                }
            } elseif ($module_part == 'contract' || $module_part == 'contrat') {
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                $object = new Contrat($this->db);
                $result = $object->fetch('', $refname);
                if (!$result) {
                    return [];
                }
            }

            if (isset($object)) {
                $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($object);
                if (!$hasPerm) {
                    throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
                }
            }
            $accessallowed = true;
        } else {
            $accessallowed = $check_access['accessallowed'];
            $sqlprotectagainstexternals = $check_access['sqlprotectagainstexternals'];
            $sqlprotectagainstexternalsapi = $check_access['sqlprotectagainstexternalsapi'];

            if (DolibarrApiAccess::$user->societe_id > 0) {
                if ($sqlprotectagainstexternalsapi) {
                    $resql = $db->query($sqlprotectagainstexternalsapi);
                    if ($resql) {
                        if ($db->num_rows($resql) == 0) throw new RestException(401);
                    } else {
                        throw new RestException(401);
                    }
                } else {
                    throw new RestException(401);
                }
            }

            if ($module_part == 'agenda' && $conf->eventconfidentiality->enabled) // Wrapping pour les actions
            {
                require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                $object = new ActionComm($this->db);
                $result = $object->fetch('', $refname);
                $entity = $object->entity;
                if ($result <= 0) {
                    throw new RestException(500, "Error while retrieve the event object.", ['details' => $this->_getErrors($object)]);
                }

                dol_include_once('/eventconfidentiality/class/eventconfidentiality.class.php');
                $eventconfidentiality = new EventConfidentiality($this->db);

                // Get mode for the user and event
                $mode = $eventconfidentiality->getModeForUserAndEvent(DolibarrApiAccess::$user, $object->id);
                if ($mode < 0) {
                    throw new RestException(500, "Error while retrieve the mode of confidentiality for the event.", ['details' => $this->_getErrors($eventconfidentiality)]);
                }

                if ($mode != EventConfidentiality::MODE_VISIBLE) {
                    throw new RestException(401, 'Access not allowed for login ' . DolibarrApiAccess::$user->login);
                }
            } elseif ($module_part == 'societe' || $module_part == 'thirdparty' || $module_part == 'thirdparties') // Wrapping pour les societes
            {
                require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

                if (!DolibarrApiAccess::$user->rights->societe->lire || !DolibarrApiAccess::$user->rights->societe->read_file) {
                    throw new RestException(401);
                }
                $object = new Societe($this->db);
                $result = $object->fetch($refname, '');
                if (!$result) {
                    return [];
                }

                //--------------------------------------------------------------
                // Open-DSI - Modification - Begin
                //
                $output_dir = $conf->societe->multidir_output[$object->entity] . "/";
                $entity = $object->entity;
                //
                // Open-DSI - Modification - End
                //--------------------------------------------------------------
            }
        }
        $check_access = dol_check_secure_access_document($module_part, $original_file, $entity, DolibarrApiAccess::$user, $refname, 'delete', true);

        $original_file = $check_access['original_file'];
        //
        // Open-DSI - Modification - End
        //--------------------------------------------------------------
        if (preg_match('/\.\./', $original_file) || preg_match('/[<>|]/', $original_file)) {
            throw new RestException(401);
        }
        if (!$accessallowed) {
            throw new RestException(401);
        }

        $filename = basename($original_file);
        $original_file_osencoded = dol_osencode($original_file);    // New file name encoded in OS encoding charset

        if (!file_exists($original_file_osencoded)) {
            return [];
        }

        $ret = dol_delete_file($original_file_osencoded, 0, 0, 0, $object);
        if (!$ret) {
            $langs->load('errors');
            throw new RestException(401, $langs->transnoentitiesnoconv("ErrorFailToDeleteFile", $original_file_osencoded));
        }

        //--------------------------------------------------------------
        // Open-DSI - Modification - Begin
        //
        if ($islogo && ($module_part == 'societe' || $module_part == 'thirdparty' || $module_part == 'thirdparties' ||
                $module_part == 'contact' || $module_part == 'adherent' || $module_part == 'member' || $module_part == 'user')) {
            // Delete thumbs if exist
            $dir = dirname($original_file_osencoded) . '/thumbs';
            if (dol_is_dir($dir)) {
                dol_delete_dir_recursive($dir);
            }
            if ($module_part == 'societe' || $module_part == 'thirdparty' || $module_part == 'thirdparties') {
                $object->logo = '';
                $object->update($object->id, DolibarrApiAccess::$user);
            } else if ($module_part == 'contact') {
                $object->photo = '';
                $object->update($object->id, DolibarrApiAccess::$user);
            } else if ($module_part == 'adherent' || $module_part == 'member') {
                $object->photo = '';
                $object->update(DolibarrApiAccess::$user);
            } else if ($module_part == 'user') {
                $object->photo = '';
                $object->update(DolibarrApiAccess::$user);
            }
        }
        //
        // Open-DSI - Modification - End
        //--------------------------------------------------------------

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'File deleted'
            )
        );
    }

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
	function _cleanObjectData(&$object, $whitelist_of_properties=array(), $blacklist_of_properties=array())
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
	function _cleanSubObjectData(&$object, $whitelist_of_properties=array(), $blacklist_of_properties=array())
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
	function _getBlackWhitelistOfProperties($object, &$whitelist_of_properties, &$blacklist_of_properties, $linked_object=false)
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
                $hookmanager->initHooks(array('companyrelationshipsapi', 'globalapi'));
                $parameters = array('whitelist_of_properties' => &$whitelist_of_properties, 'whitelist_of_properties_linked_object' => &$whitelist_of_properties_linked_object,
                    'blacklist_of_properties' => &$blacklist_of_properties, 'blacklist_of_properties_linked_object' => &$blacklist_of_properties_linked_object);
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
}
