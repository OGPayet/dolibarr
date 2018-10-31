<?php
/* Copyright (C) 2018	Open-DSI	        <support@open-dsi.fr>
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

dol_include_once('/equipement/class/equipement.class.php');

/**
 * API class for Equipement
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class EquipementApi extends DolibarrApi {
    /**
     * @var DoliDb      $db         Database object
     */
    static protected $db;
    /**
     * @var array       $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'fk_product',
    );

    /**
     *  Constructor
     */
    function __construct()
    {
        global $db;
        self::$db = $db;
    }

    /**
     *  Get the equipment
     *
     * @param   int             $id                 ID of the equipment
     *
     * @return  object|array                        Equipment data without useless information
     *
     * @throws  401             RestException       Insufficient rights
     * @throws  404             RestException       Equipment not found
     * @throws  500             RestException       Error when retrieve equipment
     */
    function get($id)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        $equipment->fetch_optionals();
        $equipment->fetchObjectLinked();
        return $this->_cleanObjectDatas($equipment);
    }

    /**
     *  Get the list of equipments
     *
     * @param   string	    $sort_field         Sort field
     * @param   string	    $sort_order         Sort order
     * @param   int		    $limit		        Limit for list
     * @param   int		    $page		        Page number
     * @param   string      $sql_filters        Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.datec:<:'20160101')"
     *
     * @return  array                           Array of equipment objects
     *
     * @throws  400         RestException       Error when validating parameter 'sqlfilters'
     * @throws  401         RestException       Insufficient rights
     * @throws  500         RestException       Error when retrieve equipment list
     */
    function index($sort_field="t.rowid", $sort_order='ASC', $limit=100, $page=0, $sql_filters='')
    {
        $obj_ret = array();

        if (!DolibarrApiAccess::$user->rights->equipement->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $sql = "SELECT t.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "equipement as t";
        $sql .= ' WHERE t.entity IN (' . getEntity('equipement') . ')';
        // Add sql filters
        if ($sql_filters) {
            if (!DolibarrApi::_checkFilters($sql_filters)) {
                throw new RestException(400, 'Error when validating parameter \'sql_filters\': ' . $sql_filters);
            }
            $regexstring = '\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql .= " AND (" . preg_replace_callback('/' . $regexstring . '/', 'DolibarrApi::_forge_criteria_callback', $sql_filters) . ")";
        }
        $sql .= " GROUP BY t.rowid";

        // Set Order and Limit
        $sql .= self::$db->order($sort_field, $sort_order);
        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= self::$db->plimit($limit, $offset);
        }

        $resql = self::$db->query($sql);
        if ($resql) {
            while ($obj = self::$db->fetch_object($resql)) {
                $equipment = new Equipement(self::$db);
                if ($equipment->fetch($obj->rowid) > 0) {
                    $equipment->fetch_optionals();
                    $equipment->fetchObjectLinked();
                    $obj_ret[] = $this->_cleanObjectDatas($equipment);
                }
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve equipment list", self::$db->lasterror());
        }

        return $obj_ret;
    }

    /**
     *  Create a equipment
     *
     * @param   array   $equipment_data     Equipment data
     *
     * @return  int                         ID of the equipment created
     *
     * @throws  400     RestException       Field missing
     * @throws  401     RestException       Insufficient rights
     * @throws  500     RestException       Error while creating the equipment
     */
    function post($equipment_data = null)
    {
        global $user;

        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Check mandatory fields
        $this->_validate($equipment_data);

        $equipment = new Equipement(self::$db);
        foreach ($equipment_data as $field => $value) {
            $equipment->$field = $value;
        }

        $save_user = $user;
        $user = DolibarrApiAccess::$user;
        if ($equipment->create() < 0) {
            $user = $save_user;
            throw new RestException(500, "Error while creating the equipment", $this->_getErrors($equipment));
        }
        $user = $save_user;

        return $equipment->id;
    }

    /**
     *  Update the reference of a equipment
     *
     * @url	PUT {id}/update_reference
     *
     * @param   int         $id             ID of the equipment
     * @param   string      $ref            Reference
     *
     * @return  object                      Equipment data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  404         RestException   Equipment not found
     * @throws  500         RestException   Error when retrieve equipment
     * @throws  500         RestException   Error while updating the equipment
     */
    function updateReference($id, $ref)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_numref(DolibarrApiAccess::$user, $ref) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the version number of a equipment
     *
     * @url	PUT {id}/update_version_number
     *
     * @param   int         $id                 ID of the equipment
     * @param   string      $numversion         Version number
     *
     * @return  object                          Equipment data updated
     *
     * @throws  401         RestException       Insufficient rights
     * @throws  404         RestException       Equipment not found
     * @throws  500         RestException       Error when retrieve equipment
     * @throws  500         RestException       Error while updating the equipment
     */
    function updateVersionNumber($id, $numversion)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_numversion(DolibarrApiAccess::$user, $numversion) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the quantity of a equipment
     *
     * @url	PUT {id}/update_quantity
     *
     * @param   int         $id             ID of the equipment
     * @param   double      $quantity       Quantity
     *
     * @return  object                      Equipment data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  404         RestException   Equipment not found
     * @throws  500         RestException   Error when retrieve equipment
     * @throws  500         RestException   Error while updating the equipment
     */
    function updateQuantity($id, $quantity)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_quantity(DolibarrApiAccess::$user, $quantity) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the unit weight of a equipment
     *
     * @url	PUT {id}/update_unit_weight
     *
     * @param   int         $id             ID of the equipment
     * @param   double      $unitweight     Unit weight
     *
     * @return  object                      Equipment data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  404         RestException   Equipment not found
     * @throws  500         RestException   Error when retrieve equipment
     * @throws  500         RestException   Error while updating the equipment
     */
    function updateUnitWeight($id, $unitweight)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_unitweight(DolibarrApiAccess::$user, $unitweight) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the accounting asset number of a equipment
     *
     * @url	PUT {id}/update_accounting_asset_number
     *
     * @param   int         $id                         ID of the equipment
     * @param   string      $numimmocompta              Accounting asset number
     *
     * @return  object                                  Equipment data updated
     *
     * @throws  401         RestException               Insufficient rights
     * @throws  404         RestException               Equipment not found
     * @throws  500         RestException               Error when retrieve equipment
     * @throws  500         RestException               Error while updating the equipment
     */
    function updateAccountingAssetNumber($id, $numimmocompta)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_numimmocompta(DolibarrApiAccess::$user, $numimmocompta) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the warehouse of a equipment
     *
     * @url	PUT {id}/update_warehouse
     *
     * @param   int         $id                 ID of the equipment
     * @param   int         $fk_entrepot        Warehouse ID
     * @param   boolean     $isentrepotmove     Makes stock movements
     *
     * @return  object                          Equipment data updated
     *
     * @throws  401         RestException       Insufficient rights
     * @throws  404         RestException       Equipment not found
     * @throws  500         RestException       Error when retrieve equipment
     * @throws  500         RestException       Error while updating the equipment
     */
    function updateWarehouse($id, $fk_entrepot, $isentrepotmove=false)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_entrepot(DolibarrApiAccess::$user, $fk_entrepot, $isentrepotmove ? 1 : 0) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the description of a equipment
     *
     * @url	PUT {id}/update_description
     *
     * @param   int         $id             ID of the equipment
     * @param   string      $description    Description
     *
     * @return  object                      Equipment data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  404         RestException   Equipment not found
     * @throws  500         RestException   Error when retrieve equipment
     * @throws  500         RestException   Error while updating the equipment
     */
    function updateDescription($id, $description)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_description(DolibarrApiAccess::$user, $description) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the public note of a equipment
     *
     * @url	PUT {id}/update_public_note
     *
     * @param   int         $id             ID of the equipment
     * @param   string      $note_public    Public note
     *
     * @return  object                      Equipment data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  404         RestException   Equipment not found
     * @throws  500         RestException   Error when retrieve equipment
     * @throws  500         RestException   Error while updating the equipment
     */
    function updatePublicNote($id, $note_public)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->update_note($note_public, '_public') > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update private note of a equipment
     *
     * @url	PUT {id}/update_private_note
     *
     * @param   int         $id             ID of the equipment
     * @param   string      $note_private   Private note
     *
     * @return  object                      Equipment data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  404         RestException   Equipment not found
     * @throws  500         RestException   Error when retrieve equipment
     * @throws  500         RestException   Error while updating the equipment
     */
    function updatePrivateNote($id, $note_private)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->update_note($note_private, '_private') > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the condition of a equipment
     *
     * @url	PUT {id}/update_condition
     *
     * @param   int         $id                     ID of the equipment
     * @param   int         $fk_etatequipement      Condition ID
     *
     * @return  object                              Equipment data updated
     *
     * @throws  401         RestException           Insufficient rights
     * @throws  404         RestException           Equipment not found
     * @throws  500         RestException           Error when retrieve equipment
     * @throws  500         RestException           Error while updating the equipment
     */
    function updateCondition($id, $fk_etatequipement)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_etatEquipement(DolibarrApiAccess::$user, $fk_etatequipement) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the customer company of a equipment
     *
     * @url	PUT {id}/update_customer_company
     *
     * @param   int         $id                 ID of the equipment
     * @param   int         $fk_soc_client      Customer company ID
     *
     * @return  object                          Equipment data updated
     *
     * @throws  401         RestException       Insufficient rights
     * @throws  404         RestException       Equipment not found
     * @throws  500         RestException       Error when retrieve equipment
     * @throws  500         RestException       Error while updating the equipment
     */
    function updateCustomerCompany($id, $fk_soc_client)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_client(DolibarrApiAccess::$user, $fk_soc_client > 0 ? $fk_soc_client : 0) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the customer invoice of a equipment
     *
     * @url	PUT {id}/update_customer_invoice
     *
     * @param   int         $id                 ID of the equipment
     * @param   int         $fk_fact_client     Customer invoice ID
     *
     * @return  object                          Equipment data updated
     *
     * @throws  401         RestException       Insufficient rights
     * @throws  404         RestException       Equipment not found
     * @throws  500         RestException       Error when retrieve equipment
     * @throws  500         RestException       Error while updating the equipment
     */
    function updateCustomerInvoice($id, $fk_fact_client)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_fact_client(DolibarrApiAccess::$user, $fk_fact_client) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the supplier invoice of a equipment
     *
     * @url	PUT {id}/update_supplier_invoice
     *
     * @param   int         $id                 ID of the equipment
     * @param   int         $fk_fact_fourn      Supplier invoice ID
     *
     * @return  object                          Equipment data updated
     *
     * @throws  401         RestException       Insufficient rights
     * @throws  404         RestException       Equipment not found
     * @throws  500         RestException       Error when retrieve equipment
     * @throws  500         RestException       Error while updating the equipment
     */
    function updateSupplierInvoice($id, $fk_fact_fourn)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_fact_fourn(DolibarrApiAccess::$user, $fk_fact_fourn) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the supplier order of a equipment
     *
     * @url	PUT {id}/update_supplier_order
     *
     * @param   int         $id                     ID of the equipment
     * @param   int         $fk_commande_fourn      Supplier order ID
     *
     * @return  object                              Equipment data updated
     *
     * @throws  401         RestException           Insufficient rights
     * @throws  404         RestException           Equipment not found
     * @throws  500         RestException           Error when retrieve equipment
     * @throws  500         RestException           Error while updating the equipment
     */
    function updateSupplierOrder($id, $fk_commande_fourn)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_commande_fourn(DolibarrApiAccess::$user, $fk_commande_fourn) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the end date of a equipment
     *
     * @url	PUT {id}/update_end_date
     *
     * @param   int         $id             ID of the equipment
     * @param   int         $datee          End date (timestamp)
     *
     * @return  object                      Equipment data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  404         RestException   Equipment not found
     * @throws  500         RestException   Error when retrieve equipment
     * @throws  500         RestException   Error while updating the equipment
     */
    function updateEndDate($id, $datee)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_datee(DolibarrApiAccess::$user, $datee) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the begin date of a equipment
     *
     * @url	PUT {id}/update_begin_date
     *
     * @param   int         $id             ID of the equipment
     * @param   int         $dateo          Begin date (timestamp)
     *
     * @return  object                      Equipment data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  404         RestException   Equipment not found
     * @throws  500         RestException   Error when retrieve equipment
     * @throws  500         RestException   Error while updating the equipment
     */
    function updateBeginDate($id, $dateo)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_dateo(DolibarrApiAccess::$user, $dateo) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the DLUO date of a equipment
     *
     * @url	PUT {id}/update_dluo_date
     *
     * @param   int         $id             ID of the equipment
     * @param   int         $dated          DLUO date (timestamp)
     *
     * @return  object                      Equipment data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  404         RestException   Equipment not found
     * @throws  500         RestException   Error when retrieve equipment
     * @throws  500         RestException   Error while updating the equipment
     */
    function updateDLUODate($id, $dated)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->set_dated(DolibarrApiAccess::$user, $dated) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Update the extra fields of a equipment
     *
     * @url	PUT {id}/update_extra_fields
     *
     * @param   int         $id             ID of the equipment
     * @param   array       $array_options  Extra fields (key without 'options_' => value, ...)
     *
     * @return  object                      Equipment data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  404         RestException   Equipment not found
     * @throws  500         RestException   Error when retrieve equipment
     * @throws  500         RestException   Error while updating the equipment
     */
    function updateExtraFields($id, $array_options)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        foreach ($array_options as $key => $value) {
            $equipment->array_options["options_".$key] = $value;
        }

        if ($equipment->insertExtraFields() > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Valid a equipment
     *
     * @url	PUT {id}/validate
     *
     * @param   int         $id             ID of the equipment
     *
     * @return  object                      Equipment data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  404         RestException   Equipment not found
     * @throws  500         RestException   Error when retrieve equipment
     * @throws  500         RestException   Error while updating the equipment
     */
    function validate($id)
    {
        global $conf;

        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->setValid(DolibarrApiAccess::$user, $conf->equipement->outputdir) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while validate the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Set to draft a equipment
     *
     * @url	PUT {id}/set_to_draft
     *
     * @param   int         $id             ID of the equipment
     *
     * @return  object                      Equipment data updated
     *
     * @throws  401         RestException   Insufficient rights
     * @throws  404         RestException   Equipment not found
     * @throws  500         RestException   Error when retrieve equipment
     * @throws  500         RestException   Error while updating the equipment
     */
    function setToDraft($id)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->setDraft(DolibarrApiAccess::$user) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while set to draft the equipment", $this->_getErrors($equipment));
        }
    }

    /**
     *  Delete equipment
     *
     * @param   int     $id             ID of the equipment
     *
     * @return  array
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  404     RestException   Equipment not found
     * @throws  500     RestException   Error when retrieve equipment
     * @throws  500     RestException   Error while deleting the equipment
     */
    function delete($id)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->supprimer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->delete(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error while deleting the equipment", $this->_getErrors($equipment));
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Equipment deleted'
            )
        );
    }

    /**
     *  Get equipment object with authorization
     *
     * @param   int             $equipment_id       Id of the equipment
     *
     * @return  Equipement
     *
     * @throws  404             RestException       Equipment not found
     * @throws  500             RestException       Error when retrieve equipment
     */
    function _getEquipmentObject($equipment_id)
    {
        $equipment = new Equipement(self::$db);
        $result = $equipment->fetch($equipment_id);
        if ($result == 0) {
            throw new RestException(404, "Equipment not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve equipment", $this->_getErrors($equipment));
        }

        return $equipment;
    }

    /**
     *  Validate fields before create or update object
     *
     * @param   array   $data               Array with data to verify
     *
     * @return  void
     *
     * @throws  400     RestException       Field missing
     */
    function _validate($data)
    {
        foreach (self::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "Field missing: $field");
        }
    }

    /**
     *  Clean sensible object data
     *
     * @param   object          $object         Object to clean
     *
     * @return  object|array                    Array of cleaned object properties
     */
    function _cleanObjectDatas($object)
    {
        $object = parent::_cleanObjectDatas($object);

        unset($object->lines);
        unset($object->statuts_image);
        unset($object->fk_contact);
        unset($object->client);
        unset($object->country_code);
        unset($object->barcode);
        unset($object->barcode_type);
        unset($object->barcode_type_code);
        unset($object->barcode_type_label);
        unset($object->barcode_type_coder);
        unset($object->canvas);
        unset($object->contact);
        unset($object->contact_id);
        unset($object->thirdparty);
        unset($object->user);
        unset($object->origin);
        unset($object->origin_id);
        unset($object->ref_ext);
        unset($object->country);
        unset($object->country_id);
        unset($object->country_code);
        unset($object->mode_reglement_id);
        unset($object->cond_reglement_id);
        unset($object->cond_reglement);
        unset($object->fk_delivery_address);
        unset($object->shipping_method_id);
        unset($object->fk_account);
        unset($object->total_ht);
        unset($object->total_tva);
        unset($object->total_localtax1);
        unset($object->total_localtax2);
        unset($object->total_ttc);
        unset($object->fk_incoterms);
        unset($object->libelle_incoterms);
        unset($object->location_incoterms);
        unset($object->note);
        unset($object->name);
        unset($object->lastname);
        unset($object->firstname);
        unset($object->fulldayevent);
        unset($object->fk_project);
        unset($object->civility_id);
        unset($object->rowid);

        return $object;
    }

    /**
     * Get all errors
     *
     * @param  object   $object     Object
     * @return array                Array of errors
     */
	function _getErrors(&$object) {
	    $errors = is_array($object->errors) ? $object->errors : array();
	    $errors = array_merge($errors, (!empty($object->error) ? array($object->error) : array()));

	    return $errors;
    }
}
