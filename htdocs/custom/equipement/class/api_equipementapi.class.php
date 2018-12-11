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
        $equipment->fetch_product();
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

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (DolibarrApiAccess::$user->socid > 0 && !DolibarrApiAccess::$user->rights->societe->client->voir) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "equipement as t";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "equipement_extrafields as tef ON tef.fk_object = t.rowid";
        if ($search_sale > 0) {
            $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale
        }
        $sql .= ' WHERE t.entity IN (' . getEntity('equipement') . ')';
        if ($search_sale > 0) {
            $sql .= " AND ((t.fk_soc_client = sc.fk_soc AND sc.fk_user = " . $search_sale. ")";     // Join for the needed table to filter by sale | OR t.fk_soc_fourn = sc.fk_soc
            $sql .= " OR t.fk_soc_client = " . DolibarrApiAccess::$user->socid . ")";               // Insert sale filter
        }
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
                    $equipment->fetch_product();
                    $equipment->fetchObjectLinked();
                    $obj_ret[] = $this->_cleanObjectDatas($equipment);
                }
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve equipment list", ['details' => [self::$db->lasterror()]]);
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
            throw new RestException(500, "Error while creating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while updating the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while validate the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while set to draft the equipment", [ 'details' => $this->_getErrors($equipment) ]);
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
            throw new RestException(500, "Error while deleting the equipment", [ 'details' => $this->_getErrors($equipment) ]);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Equipment deleted'
            )
        );
    }

    /**
     *  Get lines (events) of a equipment
     *
     * @url	GET {id}/events
     *
     * @param   int     $id             ID of the equipment
     *
     * @return  array                   List of equipment line
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  404     RestException   Equipment not found
     * @throws  500     RestException   Error when retrieve equipment
     * @throws  500     RestException   Error when retrieve the equipment lines (events)
     */
	function getLines($id)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        if ($equipment->fetch_lines() < 0) {
            throw new RestException(500, "Error when retrieve the equipment lines (events)", [ 'details' => $this->_getErrors($equipment) ]);
        }

        $result = array();
        foreach ($equipment->lines as $line) {
            array_push($result, $this->_cleanLineObjectDatas($line));
        }

        return $result;
    }

    /**
     *  Add a line (event) to given equipment
     *
     * @url	POST {id}/events
     *
     * @param   int     $id             ID of the equipment
     * @param   array   $request_data   Equipment line (event) data
     *
     * @return  int                     ID of the equipment line (event) created
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  404     RestException   Equipment not found
     * @throws  500     RestException   Error when retrieve equipment
     * @throws  500     RestException   Error while creating the equipment line (event)
     */
	function postLine($id, $request_data = null)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        $request_data = (object)$request_data;

        $createRes = $equipment->addline(
            $equipment->id,
            $request_data->fk_equipementevt_type,
            $request_data->desc,
            $request_data->dateo,
            $request_data->datee,
            $request_data->fulldayevent,
            $request_data->fk_contrat,
            $request_data->fk_fichinter,
            $request_data->fk_expedition,
            $request_data->fk_project,
            $request_data->fk_user_author,
            $request_data->total_ht,
            $request_data->array_options,
            $request_data->fk_expeditiondet,
            $request_data->fk_retourproduits
        );

        if ($createRes > 0) {
            return $createRes;
        } else {
            throw new RestException(500, "Error while creating the equipment line (event)", [ 'details' => $this->_getErrors($equipment) ]);
        }
    }

    /**
     *  Update a given a equipment line (event)
     *
     * @url	POST {id}/events/{line_id}
     *
     * @param   int     $id             ID of the equipment
     * @param   int     $line_id        ID of line to update
     * @param   array   $request_data   Equipment line (event) data
     *
     * @return  object                  Equipment data with line updated
     *
     * @throws  401     RestException   Insufficient rights
     * @throws  404     RestException   Equipment not found
     * @throws  404     RestException   Equipment line (event) not found
     * @throws  500     RestException   Error when retrieve equipment
     * @throws  500     RestException   Error when retrieve equipment line (event)
     * @throws  500     RestException   Error while updating the equipment line (event)
     */
	function putLine($id, $line_id, $request_data = null)
	{
		if(! DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
		}

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        $equipmentline = new Equipementevt(self::$db);
        $result = $equipmentline->fetch($line_id);
        if ($result == 0 || ($result > 0 && $equipmentline->fk_equipement != $equipment->id)) {
            throw new RestException(404, "Equipment line (event) not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve equipment line (event)", [ 'details' => $this->_getErrors($equipmentline) ]);
        }

        $request_data = (object)$request_data;

        $equipmentline->fk_equipementevt_type   = isset($request_data->fk_equipementevt_type) ? $request_data->fk_equipementevt_type : $equipmentline->fk_equipementevt_type;
        $equipmentline->desc                    = isset($request_data->desc) ? $request_data->desc : $equipmentline->desc;
        $equipmentline->dateo                   = isset($request_data->dateo) ? $request_data->dateo : $equipmentline->dateo;
        $equipmentline->datee                   = isset($request_data->datee) ? $request_data->datee : $equipmentline->datee;
        $equipmentline->fulldayevent            = isset($request_data->fulldayevent) ? $request_data->fulldayevent : $equipmentline->fulldayevent;
        $equipmentline->fk_contrat              = isset($request_data->fk_contrat) ? $request_data->fk_contrat : $equipmentline->fk_contrat;
        $equipmentline->fk_fichinter            = isset($request_data->fk_fichinter) ? $request_data->fk_fichinter : $equipmentline->fk_fichinter;
        $equipmentline->fk_expedition           = isset($request_data->fk_expedition) ? $request_data->fk_expedition : $equipmentline->fk_expedition;
        $equipmentline->fk_project              = isset($request_data->fk_project) ? $request_data->fk_project : $equipmentline->fk_project;
        $equipmentline->fk_user_author          = isset($request_data->fk_user_author) ? $request_data->fk_user_author : $equipmentline->fk_user_author;
        $equipmentline->total_ht                = isset($request_data->total_ht) ? $request_data->total_ht : $equipmentline->total_ht;
        $equipmentline->array_options           = isset($request_data->array_options) ? $request_data->array_options : $equipmentline->array_options;
        $equipmentline->fk_expeditiondet        = isset($request_data->fk_expeditiondet) ? $request_data->fk_expeditiondet : $equipmentline->fk_expeditiondet;

		$updateRes = $equipmentline->update();

        if ($updateRes > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while updating the equipment line (event)", [ 'details' => $this->_getErrors($equipmentline) ]);
        }
	}

    /**
     *  Delete a given equipment line (event)
     *
     * @url	DELETE {id}/lines/{line_id}
     *
     * @param   int     $id                 Id of equipment to delete
     * @param   int     $line_id            Id of line (event) to delete
     *
     * @return  object                      Equipment data with line (event) deleted
     *
     * @throws  401     RestException       Insufficient rights
     * @throws  404     RestException       Equipment not found
     * @throws  404     RestException       Equipment line (event) not found
     * @throws  500     RestException       Error when retrieve equipment
     * @throws  500     RestException       Error when retrieve equipment line (event)
     * @throws  500     RestException       Error while deleting the equipment line (event)
     */
	function deleteLine($id, $line_id)
    {
        if (!DolibarrApiAccess::$user->rights->equipement->creer) {
            throw new RestException(401, "Insufficient rights");
        }

        // Get equipment object
        $equipment = $this->_getEquipmentObject($id);

        $equipmentline = new Equipementevt(self::$db);
        $result = $equipmentline->fetch($line_id);
        if ($result == 0 || ($result > 0 && $equipmentline->fk_equipement != $equipment->id)) {
            throw new RestException(404, "Equipment line (event) not found");
        } elseif ($result < 0) {
            throw new RestException(500, "Error when retrieve equipment line (event)", [ 'details' => $this->_getErrors($equipmentline) ]);
        }

        if ($equipmentline->deleteline($line_id) > 0) {
            return $this->get($id);
        } else {
            throw new RestException(500, "Error while deleting the equipment line (event)", [ 'details' => $this->_getErrors($requestmanager) ]);
        }
    }

    /**
     *  Get the list of event type equipments into the dictionary
     *
     * @url	GET dictionary_event_types
     *
     * @return  array                           Array of event type equipments
     *
     * @throws  401         RestException       Insufficient rights
     * @throws  500         RestException       Error when retrieve event type equipments list
     */
    function indexEventType()
    {
        global $langs;

        $langs->load('equipement@equipement');
        $obj_ret = array();

        if (!DolibarrApiAccess::$user->rights->equipement->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $sql = "SELECT t.rowid, t.code, t.libelle, t.coder, t.active, t.entity";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_equipementevt_type as t";
        $sql .= " ORDER BY t.rowid";

        $resql = self::$db->query($sql);
        if ($resql) {
            while ($obj = self::$db->fetch_object($resql)) {
                $obj_ret[] = array(
                    'rowid' => $obj->rowid,
                    'code' => $obj->code,
                    'libelle' => $langs->transnoentitiesnoconv($obj->libelle),
                    'coder' => $obj->coder,
                    'active' => $obj->active,
                    'entity' => $obj->entity
                );
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve event type equipments list", ['details' => [self::$db->lasterror()]]);
        }

        return $obj_ret;
    }

    /**
     *  Get the list of status equipments into the dictionary
     *
     * @url	GET dictionary_status
     *
     * @return  array                           Array of status equipments
     *
     * @throws  401         RestException       Insufficient rights
     * @throws  500         RestException       Error when retrieve status equipments list
     */
    function indexStatus()
    {
        global $langs;

        $langs->load('equipement@equipement');
        $obj_ret = array();

        if (!DolibarrApiAccess::$user->rights->equipement->lire) {
            throw new RestException(401, "Insufficient rights");
        }

        $sql = "SELECT t.rowid, t.code, t.libelle, t.coder, t.active, t.entity";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_equipement_etat as t";
        $sql .= " ORDER BY t.rowid";

        $resql = self::$db->query($sql);
        if ($resql) {
            while ($obj = self::$db->fetch_object($resql)) {
                $obj_ret[] = array(
                    'rowid' => $obj->rowid,
                    'code' => $obj->code,
                    'libelle' => $langs->transnoentitiesnoconv($obj->libelle),
                    'coder' => $obj->coder,
                    'active' => $obj->active,
                    'entity' => $obj->entity
                );
            }

            self::$db->free($resql);
        } else {
            throw new RestException(500, "Error when retrieve status equipments list", ['details' => [self::$db->lasterror()]]);
        }

        return $obj_ret;
    }

    /**
     *  Get equipment object with authorization
     *
     * @param   int             $equipment_id       Id of the equipment
     *
     * @return  Equipement
     *
     * @throws  403             RestException       Access unauthorized
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
            throw new RestException(500, "Error when retrieve equipment", [ 'details' => $this->_getErrors($equipment) ]);
        }

        if(!DolibarrApi::_checkAccessToResource('equipement', $equipment->id, 'equipement', '', 'fk_soc_client')) {
            throw new RestException(403, "Access unauthorized");
        }

//        if(!DolibarrApi::_checkAccessToResource('equipement', $equipment->id, 'equipement', '', 'fk_soc_fourn')) {
//            throw new RestException(403, "Access unauthorized");
//        }

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

        // If object has lines, remove $db property
        if (isset($object->lines) && is_array($object->lines) && count($object->lines) > 0)  {
            $nboflines = count($object->lines);
		for ($i=0; $i < $nboflines; $i++)
            {
                $this->_cleanLineObjectDatas($object->lines[$i]);
            }
        }

        if (! empty($object->product) && is_object($object->product))
        {
		parent::_cleanObjectDatas($object->product);
            unset($object->product->regeximgext);
        }

        return $object;
    }

    /**
     *  Clean sensible line object data
     *
     * @param   object          $object         Object to clean
     *
     * @return  object|array                    Array of cleaned object properties
     */
    function _cleanLineObjectDatas($object)
    {
        unset($object->db);
        unset($object->error);
        unset($object->element);
        unset($object->table_element);
        unset($object->rowid);
        unset($object->errors);
        unset($object->table_element_line);
        unset($object->linkedObjects);
        unset($object->oldcopy);
        unset($object->context);
        unset($object->canvas);
        unset($object->project);
        unset($object->projet);
        unset($object->contact);
        unset($object->contact_id);
        unset($object->thirdparty);
        unset($object->user);
        unset($object->origin);
        unset($object->origin_id);
        unset($object->ref_previous);
        unset($object->ref_next);
        unset($object->ref_ext);
        unset($object->country);
        unset($object->country_id);
        unset($object->country_code);
        unset($object->barcode_type);
        unset($object->barcode_type_code);
        unset($object->barcode_type_label);
        unset($object->barcode_type_coder);
        unset($object->mode_reglement_id);
        unset($object->cond_reglement_id);
        unset($object->cond_reglement);
        unset($object->fk_delivery_address);
        unset($object->shipping_method_id);
        unset($object->modelpdf);
        unset($object->fk_account);
        unset($object->note_public);
        unset($object->note_private);
        unset($object->note);
        unset($object->total_tva);
        unset($object->total_localtax1);
        unset($object->total_localtax2);
        unset($object->total_ttc);
        unset($object->fk_incoterms);
        unset($object->libelle_incoterms);
        unset($object->location_incoterms);
        unset($object->name);
        unset($object->lastname);
        unset($object->firstname);
        unset($object->civility_id);
        unset($object->ref_fichinter);
        unset($object->ref_contrat);
        unset($object->ref_expedition);
        unset($object->import_key);
        unset($object->linkedObjectsIds);
        unset($object->ref);
        unset($object->statut);
        unset($object->lines);

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
