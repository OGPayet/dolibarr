<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2018 SuperAdmin
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

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';


/**
 * \file    infoextranet/class/api_infoextranet.class.php
 * \ingroup infoextranet
 * \brief   File for API management of myobject.
 */

/**
 * API class for infoextranet myobject
 *
 * @smart-auto-routing false
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class InfoExtranetApi extends DolibarrApi
{
    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'name'
    );

    /**
     * Constructor
     *
     * @url     GET /
     *
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
    }

    /**
     * Get extrafields of a given thirdparty
     *
     * Return an array with societe->array_options informations
     *
     * @param 	int 	$id ID of thirdparty
     * @return 	array|mixed data without useless information
	 *
     * @url	GET thirdparties/{id}
     * @throws 	RestException
     */
    function getThirdparty($id)
    {
		if(! DolibarrApiAccess::$user->rights->infoextranet->read) {
			throw new RestException(401);
		}

		$societe = new Societe($this->db);
        $result = $societe->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Thirdparty not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('societe',$societe->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		return $this->_cleanObjectDatas($societe->array_options);
    }

    /**
     * Update extrafields of a given thirparty
     *
     * @param int   $id             Id of thirdparty to update
     * @param array $request_data   Datas
     * @return int
     *
     * @url	PUT thirdparties/{id}
     */
    function putThirdparty($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->write) {
            throw new RestException(401);
        }

        $societe = new Societe($this->db);
        $result = $societe->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Thirdparty not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('societe',$societe->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            $societe->array_options[$field] = $value;
        }

        if($societe->update($id, DolibarrApiAccess::$user))
            return $this->getThirdparty($id);

        return false;
    }

    /**
     * Reset extrafields of a given thirdparty
     *
     * @param   int     $id         Id of thirdparty to reset
     * @return  array
     *
     * @url	DELETE thirdparties/{id}
     */
    function deleteThirdparty($id)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->delete) {
			throw new RestException(401);
		}

        $societe = new Societe($this->db);
        $result = $societe->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Thirdparty not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('societe',$societe->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach ($societe->array_options as $key => $value)
            $societe->array_options[$key] = null;

        if ($societe->update($id, DolibarrApiAccess::$user))
            return array(
                'success' => array(
                    'code' => 200,
                    'message' => 'Thirparty extrafields reseted'
                )
            );
        else
            throw new RestException(500);
    }

    /**
     * Get extrafields of a given invoice
     *
     * Return an array with facture->array_options informations
     *
     * @param 	int 	$id ID of invoice
     * @return 	array|mixed data without useless information
     *
     * @url	GET invoices/{id}
     * @throws 	RestException
     */
    function getInvoice($id)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->read) {
            throw new RestException(401);
        }

        $invoice = new Facture($this->db);
        $result = $invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($invoice->array_options);
    }

    /**
     * Update extrafields of a given invoice
     *
     * @param int   $id             Id of invoice to update
     * @param array $request_data   Datas
     * @return int
     *
     * @url	PUT invoices/{id}
     */
    function putInvoice($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->write) {
            throw new RestException(401);
        }

        $invoice = new Facture($this->db);
        $result = $invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            $invoice->array_options[$field] = $value;
        }

        if($invoice->update($id, DolibarrApiAccess::$user))
            return $this->getInvoice($id);

        return false;
    }

    /**
     * Reset extrafields of a given invoice
     *
     * @param   int     $id         Id of invoice to reset
     * @return  array
     *
     * @url	DELETE invoices/{id}
     */
    function deleteInvoice($id)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->delete) {
            throw new RestException(401);
        }

        $invoice = new Facture($this->db);
        $result = $invoice->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Invoice not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('facture',$invoice->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach ($invoice->array_options as $key => $value)
            $invoice->array_options[$key] = null;

        if ($invoice->update($id, DolibarrApiAccess::$user))
            return array(
                'success' => array(
                    'code' => 200,
                    'message' => 'Invoice extrafields reseted'
                )
            );
        else
            throw new RestException(500);
    }

    /**
     * Get extrafields of a given contract
     *
     * Return an array with contrat->array_options informations
     *
     * @param 	int 	$id ID of contract
     * @return 	array|mixed data without useless information
     *
     * @url	GET contracts/{id}
     * @throws 	RestException
     */
    function getContract($id)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->read) {
            throw new RestException(401);
        }

        $contract = new Contrat($this->db);
        $result = $contract->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contract not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat', $contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($contract->array_options);
    }

    /**
     * Update extrafields of a given contract
     *
     * @param int   $id             Id of contract to update
     * @param array $request_data   Datas
     * @return int
     *
     * @url	PUT contracts/{id}
     */
    function putContract($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->write) {
            throw new RestException(401);
        }

        $contract = new Contrat($this->db);
        $result = $contract->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contract not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat',$contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            $contract->array_options[$field] = $value;
        }

        if($contract->update($id, DolibarrApiAccess::$user))
            return $this->getContract($id);

        return false;
    }

    /**
     * Reset extrafields of a given contract
     *
     * @param   int     $id         Id of contract to reset
     * @return  array
     *
     * @url	DELETE contracts/{id}
     */
    function deleteContract($id)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->delete) {
            throw new RestException(401);
        }

        $contract = new Contrat($this->db);
        $result = $contract->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contract not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat',$contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach ($contract->array_options as $key => $value)
            $contract->array_options[$key] = null;

        if ($contract->update($id, DolibarrApiAccess::$user))
            return array(
                'success' => array(
                    'code' => 200,
                    'message' => 'Contract extrafields reseted'
                )
            );
        else
            throw new RestException(500);
    }

    /**
     * Get extrafields of all lines of a contract
     *
     * Return an array with contratligne->array_options informations
     *
     * @param 	int 	$id ID of contract
     * @return 	array|mixed data without useless information
     *
     * @url	GET contracts/{id}/lines/
     * @throws 	RestException
     */
    function getContractLines($id)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->read) {
            throw new RestException(401);
        }

        $contract = new Contrat($this->db);
        $result = $contract->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contract not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat', $contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if ( ! $contract->lines)
            throw new RestException(404, 'No lines for contract');

        $res = array();
        foreach($contract->lines as $key => $line)
        {
            $block = $line->array_options;
            $res[] = array_merge(array("id" => $line->id), $block);
        }

        return $this->_cleanObjectDatas($res);
    }

    /**
     * Get extrafields of a given line of a contract
     *
     * Return an array with contratligne->array_options informations
     *
     * @param 	int 	$id ID of contract
     * @param 	int 	$lineid ID of contract line
     * @return 	array|mixed data without useless information
     *
     * @url	GET contracts/{id}/lines/{lineid}
     * @throws 	RestException
     */
    function getContractLine($id, $lineid)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->read) {
            throw new RestException(401);
        }

        $contract = new Contrat($this->db);
        $result = $contract->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contract not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat', $contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if ( ! $contract->lines)
            throw new RestException(404, 'No lines for contract');

        $res = array();
        foreach($contract->lines as $key => $line)
        {
            if ($lineid == $line->id)
                $res[] = $line->array_options;
        }

        if (count($res) > 0)
            return $this->_cleanObjectDatas($res);
        else
            throw new RestException(404, 'Line not found');
    }

    /**
     * Update extrafields of a given line of a contract
     *
     * Return an array with contratligne->array_options informations
     *
     * @param 	int 	$id ID of contract
     * @param 	int 	$lineid ID of contract line
     * @param   array   $request_data   Datas
     * @return 	array|mixed data without useless information
     *
     * @url	PUT contracts/{id}/lines/{lineid}
     * @throws 	RestException
     */
    function putContractLine($id, $lineid, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->write) {
            throw new RestException(401);
        }

        $contract = new Contrat($this->db);
        $result = $contract->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contract not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat', $contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if ( ! $contract->lines)
            throw new RestException(404, 'No lines for contract');

        // Check if line id exist
        $exist = false;
        foreach($contract->lines as $key => $line)
            if ($lineid == $line->id)
            {
                $old_line = $line;
                $exist = true;
            }

        if (!$exist)
            throw new RestException(404, 'Line not found');

        $array_options = $old_line->array_options;
        foreach($request_data as $field => $value) {
            $array_options[$field] = $value;
        }

        $updateRes = $contract->updateline(
            $lineid,
            $old_line->desc,
            $old_line->subprice,
            $old_line->qty,
            $old_line->remise_percent,
            $old_line->date_ouveture_prevue,
            $old_line->date_fin_validite,
            $old_line->tva_tx,
            $old_line->localtax1_tx,
            $old_line->localtax2_tx,
            $old_line->date_ouverture,
            $old_line->date_cloture,
            'HT',
            $old_line->info_bits,
            $old_line->fk_fourn_price,
            $old_line->pa_ht,
            $array_options,
            $old_line->fk_unit
        );

        if ($updateRes > 0)
            return $this->getContractLine($id, $lineid);

        return false;
    }

    /**
     * Reset extrafields of a given line of a contract
     *
     * @param 	int 	$id ID of contract
     * @param 	int 	$lineid ID of contract line
     * @return 	array
     *
     * @url	DELETE contracts/{id}/lines/{lineid}
     * @throws 	RestException
     */
    function deleteContractLine($id, $lineid)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->delete) {
            throw new RestException(401);
        }

        $contract = new Contrat($this->db);
        $result = $contract->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contract not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contrat', $contract->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if ( ! $contract->lines)
            throw new RestException(404, 'No lines for contract');

        // Check if line id exist
        $exist = false;
        foreach($contract->lines as $key => $line)
            if ($lineid == $line->id)
            {
                $old_line = $line;
                $exist = true;
            }

        if (!$exist)
            throw new RestException(404, 'Line not found');

        foreach ($old_line->array_options as $key => $value)
            $old_line->array_options[$key] = null;

        $updateRes = $contract->updateline(
            $lineid,
            $old_line->desc,
            $old_line->subprice,
            $old_line->qty,
            $old_line->remise_percent,
            $old_line->date_ouveture_prevue,
            $old_line->date_fin_validite,
            $old_line->tva_tx,
            $old_line->localtax1_tx,
            $old_line->localtax2_tx,
            $old_line->date_ouverture,
            $old_line->date_cloture,
            'HT',
            $old_line->info_bits,
            $old_line->fk_fourn_price,
            $old_line->pa_ht,
            $old_line->array_options,
            $old_line->fk_unit
        );

        if ($updateRes > 0)
            return array(
                'success' => array(
                    'code' => 200,
                    'message' => 'Contract Line extrafields reseted'
                )
            );
        else
            throw new RestException(500);
    }

    /**
     * Get extrafields of a given contact
     *
     * Return an array with contact->array_options informations
     *
     * @param 	int 	$id ID of contact
     * @return 	array|mixed data without useless information
     *
     * @url	GET contacts/{id}
     * @throws 	RestException
     */
    function getContact($id)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->read) {
            throw new RestException(401);
        }

        $contact = new Contact($this->db);
        $result = $contact->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contact not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contact', $contact->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        return $this->_cleanObjectDatas($contact->array_options);
    }

    /**
     * Update extrafields of a given contact
     *
     * @param int   $id             Id of contact to update
     * @param array $request_data   Datas
     * @return int
     *
     * @url	PUT contacts/{id}
     */
    function putContact($id, $request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->write) {
            throw new RestException(401);
        }

        $contact = new Contact($this->db);
        $result = $contact->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contact not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contact',$contact->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach($request_data as $field => $value) {
            $contact->array_options[$field] = $value;
        }

        if($contact->update($id, DolibarrApiAccess::$user))
            return $this->getContract($id);

        return false;
    }

    /**
     * Reset extrafields of a given contact
     *
     * @param   int     $id         Id of contact to reset
     * @return  array
     *
     * @url	DELETE contacts/{id}
     */
    function deleteContact($id)
    {
        if(! DolibarrApiAccess::$user->rights->infoextranet->delete) {
            throw new RestException(401);
        }

        $contact = new Contact($this->db);
        $result = $contact->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Contact not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('contact',$contact->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        foreach ($contact->array_options as $key => $value)
            $contact->array_options[$key] = null;

        if ($contact->update($id, DolibarrApiAccess::$user))
            return array(
                'success' => array(
                    'code' => 200,
                    'message' => 'Contact extrafields reseted'
                )
            );
        else
            throw new RestException(500);
    }

    /**
     * Create myobject object
     *
     * @param array $request_data   Request datas
     * @return int  ID of myobject
     *
     * @url	POST myobjects/
     */
    /*function post($request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->myobject->create) {
			throw new RestException(401);
		}
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->myobject->$field = $value;
        }
        if( ! $this->myobject->create(DolibarrApiAccess::$user)) {
            throw new RestException(500);
        }
        return $this->myobject->id;
    }*/

    /**
     * Validate fields before create or update object
     *
     * @param array $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
    function _validate($data)
    {
        $myobject = array();
        foreach (MyObjectApi::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $myobject[$field] = $data[$field];
        }
        return $myobject;
    }
}
