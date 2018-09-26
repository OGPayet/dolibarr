<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
        'description',
        'date',
        'duree'
    );

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
     * Constructor
     */
    function __construct()
    {
        global $db, $conf;
        $this->db = $db;

        // proposals
        $this->propal = new Propal($this->db);

        // orders
        $this->commande = new Commande($this->db);

        // invoices
        $this->invoice = new Facture($this->db);

        // interventions
        $this->fichinter = new Fichinter($this->db);
    }


    //
    // Common
    //

    /**
     * Check perms for user with public space availability
     *
     * @param   Object      $object         Object (propal, commande, invoice, ficheinter)
     * @return  bool        FALSE to deny user access, TRUE to authorize
     * @throws  Exception
     */
    private function _checkUserPublicSpaceAvailabilityPermOnObject($object)
    {
        // get user thirdparty (for external user only)
        $userSocId = DolibarrApiAccess::$user->societe_id;

        // external user
        if ($userSocId > 0) {
            $hasPerm = FALSE;

            // company principal
            $object->fetch_thirdparty();
            $companyPrincipalId = $object->thirdparty->id;
            $companyPrincipalAvailability = intval($object->array_options['options_companyrelationships_availability_principal']);

            // company benefactor
            $companyBenefactorId = $object->array_options['options_companyrelationships_fk_soc_benefactor'];
            $companyBenefacorAvailability = intval($object->array_options['options_companyrelationships_availability_benefactor']);

            // user company is principal
            if ($userSocId==$companyPrincipalId && $companyPrincipalAvailability==1) {
                $hasPerm = TRUE;
            }
            // user company is benefactor
            else if ($userSocId==$companyBenefactorId && $companyBenefacorAvailability==1) {
                $hasPerm = TRUE;
            }
        }
        // internal user
        else {
            $hasPerm = TRUE;
        }

        return $hasPerm;
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->propal->fetchObjectLinked();
        return $this->_cleanProposalObjectDatas($this->propal);
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

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."propal as t";

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
                    $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($proposal_static);
                    if ($hasPerm) {
                        $obj_ret[] = $this->_cleanProposalObjectDatas($proposal_static);
                    }
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
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
     * @return  int
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->propal->getLinesArray();
        $result = array();
        foreach ($this->propal->lines as $line) {
            array_push($result,$this->_cleanProposalObjectDatas($line));
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
     * @return  int
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

        if (! DolibarrApi::_checkAccessToResource('propal',$this->propal->id))
        {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
     * @return  array
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
            return $this->_cleanProposalObjectDatas($result);
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
     * @return  int
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->propal->fetchObjectLinked();

        return $this->_cleanProposalObjectDatas($this->propal);
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->propal->fetchObjectLinked();

        return $this->_cleanProposalObjectDatas($this->propal);
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->propal->fetchObjectLinked();

        return $this->_cleanProposalObjectDatas($this->propal);
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('propal',$this->propal->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->propal->fetchObjectLinked();

        return $this->_cleanProposalObjectDatas($this->propal);
    }

    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return  array   Array of cleaned object properties
     */
    function _cleanProposalObjectDatas($object)
    {
        $object = parent::_cleanObjectDatas($object);

        unset($object->note);
        unset($object->name);
        unset($object->lastname);
        unset($object->firstname);
        unset($object->civility_id);
        unset($object->address);

        if (! DolibarrApiAccess::$user->rights->companyrelationships->update_md->element) {
            unset($object->array_options['options_companyrelationships_availability_principal']);
            unset($object->array_options['options_companyrelationships_availability_benefactor']);
        }

        return $object;
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->commande->fetchObjectLinked();
        return $this->_cleanOrderObjectDatas($this->commande);
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

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."commande as t";

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
                    $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($commande_static);
                    if ($hasPerm) {
                        $obj_ret[] = $this->_cleanOrderObjectDatas($commande_static);
                    }
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
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
     * @return  int
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->commande->getLinesArray();
        $result = array();
        foreach ($this->commande->lines as $line) {
            array_push($result,$this->_cleanOrderObjectDatas($line));
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
     * @return  int
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
     * @return  array
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
            return $this->_cleanOrderObjectDatas($result);
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
     * @return  int
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
     * @return  int
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

        if (! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->commande->fetchObjectLinked();

        return $this->_cleanOrderObjectDatas($this->commande);
    }

    /**
     * Tag the order as validated (opened)
     *
     * Function used when order is reopend after being closed.
     *
     * @url POST orders/{id}/reopen
     *
     * @param  int   $id       Id of the order
     * @return int
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
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
     * @return  int
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
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->commande->classifyBilled(DolibarrApiAccess::$user);
        if( $result < 0) {
            throw new RestException(400, $this->commande->error);
        }

        $result = $this->commande->fetch($id);
        if( ! $result ) {
            return [];
        }

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->commande->fetchObjectLinked();

        return $this->_cleanOrderObjectDatas($this->commande);
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->commande->fetchObjectLinked();

        return $this->_cleanOrderObjectDatas($this->commande);
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->commande);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->commande->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->commande->fetchObjectLinked();

        return $this->_cleanOrderObjectDatas($this->commande);
    }

    /**
     * Create an order using an existing proposal.
     *
     * @url POST orders/createfromproposal/{proposalid}
     *
     * @param   int   $proposalid       Id of the proposal
     * @return  int
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

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($propal);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->commande->createFromProposal($propal, DolibarrApiAccess::$user);
        if( $result < 0) {
            throw new RestException(405, $this->commande->error);
        }
        $this->commande->fetchObjectLinked();

        return $this->_cleanOrderObjectDatas($this->commande);
    }

    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return  array   Array of cleaned object properties
     */
    function _cleanOrderObjectDatas($object)
    {
        $object = parent::_cleanObjectDatas($object);

        unset($object->note);
        unset($object->address);
        unset($object->barcode_type);
        unset($object->barcode_type_code);
        unset($object->barcode_type_label);
        unset($object->barcode_type_coder);

        if (! DolibarrApiAccess::$user->rights->companyrelationships->update_md->element) {
            unset($object->array_options['options_companyrelationships_availability_principal']);
            unset($object->array_options['options_companyrelationships_availability_benefactor']);
        }

        return $object;
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

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->invoice->fetchObjectLinked();
        return $this->_cleanInvoiceObjectDatas($this->invoice);
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

        // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
        $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."facture as t";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql.= ' WHERE (t.entity IN ('.getEntity('facture').')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";		// Join for the needed table to filter by sale

        // Filter by status
        if ($status == 'draft')     $sql.= " AND t.fk_statut IN (0)";
        if ($status == 'unpaid')    $sql.= " AND t.fk_statut IN (1)";
        if ($status == 'paid')      $sql.= " AND t.fk_statut IN (2)";
        if ($status == 'cancelled') $sql.= " AND t.fk_statut IN (3)";
        // Insert sale filter
        if ($search_sale > 0)
        {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        $sql.= ")";

        // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
        $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;
        if ($socids) {
            $sql.= ' OR (t.entity IN ('.getEntity('facture').')';
            // Filter by status
            if ($status == 'draft')     $sql.= " AND t.fk_statut IN (0)";
            if ($status == 'unpaid')    $sql.= " AND t.fk_statut IN (1)";
            if ($status == 'paid')      $sql.= " AND t.fk_statut IN (2)";
            if ($status == 'cancelled') $sql.= " AND t.fk_statut IN (3)";
            $sql.= " AND t.fk_soc IN (".$socids."))";
        }
        $sql.= " GROUP BY rowid";

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
                    $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($invoice_static);
                    if ($hasPerm) {
                        $obj_ret[] = $this->_cleanInvoiceObjectDatas($invoice_static);
                    }
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
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
     * @return  int
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

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->invoice->getLinesArray();
        $result = array();
        foreach ($this->invoice->lines as $line) {
            array_push($result,$this->_cleanInvoiceObjectDatas($line));
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
     * @return  int
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

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
     * @return  array
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

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
            return $this->_cleanInvoiceObjectDatas($result);
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
     * @return  int
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

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
     * @return  int
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

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('facture',$this->invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->invoice);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return  array   Array of cleaned object properties
     */
    function _cleanInvoiceObjectDatas($object)
    {
        $object = parent::_cleanObjectDatas($object);

        unset($object->address);

        if (! DolibarrApiAccess::$user->rights->companyrelationships->update_md->element) {
            unset($object->array_options['options_companyrelationships_availability_principal']);
            unset($object->array_options['options_companyrelationships_availability_benefactor']);
        }

        return $object;
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

        if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->fichinter->fetchObjectLinked();
        return $this->_cleanInterventionObjectDatas($this->fichinter);
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

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql.= " FROM ".MAIN_DB_PREFIX."fichinter as t";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql.= ' WHERE (t.entity IN ('.getEntity('intervention').')';
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
            $sql.= ' OR (t.entity IN ('.getEntity('intervention').')';
            $sql.= " AND t.fk_soc IN (".$socids."))";
        }
        $sql.= " GROUP BY rowid";

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
                $fichinter_static = new Fichinter($db);
                if($fichinter_static->fetch($obj->rowid)) {
                    $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($fichinter_static);
                    if ($hasPerm) {
                        $obj_ret[] = $this->_cleanInterventionObjectDatas($fichinter_static);
                    }
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve fichinter list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            return [];
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
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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
     * @return  int
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

        if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $this->fichinter->getLinesArray();
        $result = array();
        foreach ($this->fichinter->lines as $line) {
            array_push($result,$this->_cleanInterventionObjectDatas($line));
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
     * @return  int
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

        if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->fichinter->$field = $value;
        }

        if ($this->fichinter->update(DolibarrApiAccess::$user) > 0)	{
            return $this->get($id);
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
     * @return  int
     *
     * @throws  400     RestException   Error while creating intervention line
     * @throws  401     RestException   Insufficient rights
     */
    function postLineIntervention($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        $result = $this->fichinter->fetch($id);
        if (! $result) {
            return [];
        }

        // Check mandatory fields
        $result = $this->_validateLineIntervention($request_data);

        foreach($request_data as $field => $value) {
            $this->fichinter->$field = $value;
        }

        if( ! $result ) {
            return [];
        }

        if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $updateRes = $this->fichinter->addLine(
            DolibarrApiAccess::$user,
            $id,
            $this->fichinter->desc,
            $this->fichinter->datei,
            $this->fichinter->duration
        );

        if ($updateRes > 0) {
            return $updateRes;
        }
        else {
            throw new RestException(400, $this->fichinter->error);
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

        if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $request_data = (object) $request_data;

        $ficheinterline = new FichinterLigne($this->db);
        $result = $ficheinterline->fetch($lineid);
        if ($result <= 0) {
            return [];
        }

        $updateRes = $this->fichinter->updateline(
            $lineid,
            $id,
            isset($request_data->desc)?$request_data->desc:$ficheinterline->desc,
            isset($request_data->datei)?$request_data->datei:$ficheinterline->datei,
            isset($request_data->duration)?$request_data->duration:$ficheinterline->duration
        );

        if ($updateRes > 0) {
            $result = $this->getIntervention($id);
            unset($result->line);
            return $this->_cleanInterventionObjectDatas($result);
        } else {
            throw new RestException(400, $this->fichinter->error);
        }
    }

    /**
     * Delete a line of given intervention
     *
     * @url	DELETE interventions/{id}/lines/{lineid}
     *
     * @param   int   $id             Id of intervention to update
     * @param   int   $lineid         Id of line to delete
     * @return  int
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

        if( ! DolibarrApi::_checkAccessToResource('ficheinter',$this->ficheinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        // TODO Check the lineid $lineid is a line of ojbect

        $updateRes = $this->ficheinter->deleteline($lineid);
        if ($updateRes > 0) {
            return $this->getIntervention($id);
        } else {
            throw new RestException(405, $this->propal->error);
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

        if( ! DolibarrApi::_checkAccessToResource('commande',$this->fichinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
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

        if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->fichinter->setValid(DolibarrApiAccess::$user, $notrigger);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when validating Intervention: '.$this->commande->error);
        }

        $this->fichinter->fetchObjectLinked();

        return $this->_cleanInterventionObjectDatas($this->fichinter);
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

        if (! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $hasPerm = $this->_checkUserPublicSpaceAvailabilityPermOnObject($this->fichinter);
        if (! $hasPerm) {
            throw new RestException(401, 'Access public space availability not allowed for login ' . DolibarrApiAccess::$user->login);
        }

        $result = $this->fichinter->setStatut(3);

        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already closed');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when closing Intervention: '.$this->fichinter->error);
        }

        $this->fichinter->fetchObjectLinked();

        return $this->_cleanInterventionObjectDatas($this->fichinter);
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
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return  array   Array of cleaned object properties
     */
    function _cleanInterventionObjectDatas($object)
    {
        $object = parent::_cleanObjectDatas($object);

        unset($object->statuts_short);
        unset($object->statuts_logo);
        unset($object->statuts);

        if (! DolibarrApiAccess::$user->rights->companyrelationships->update_md->element) {
            unset($object->array_options['options_companyrelationships_availability_principal']);
            unset($object->array_options['options_companyrelationships_availability_benefactor']);
        }

        return $object;
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
}
