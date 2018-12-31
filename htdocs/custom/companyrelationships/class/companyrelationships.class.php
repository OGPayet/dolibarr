<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2018       Open-DSI            <support@open-dsi.fr>
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
 * 	\file 		htdocs/companyrelationships/class/companyrelationships.class.php
 *	\ingroup    companyrelationships
 *	\brief      File of class to manage company relationships
 */


/**
 *	Class to manage company relationships
 */
class CompanyRelationships
{
    /**
     * Realtion types
     */
    const RELATION_TYPE_PRINCIPAL  = 0;
    const RELATION_TYPE_BENEFACTOR = 1;
    const RELATION_TYPE_WATCHER    = 2;

    /**
     * Database handler
     * @var DoliDB
     */
    public $db;

    /**
     * Error message
     * @var string
     */
    public $error;
    /**
     * List of error message
     * @var array
     */
    public $errors;

    /**
     * Id
     * @var int
     */
    public $id;

    /**
     * Id thirdparty
     * @var int
     */
    public $fk_soc;

    /**
     * Id benefactor
     * @var
     */
    public $fk_soc_benefactor;

    /**
     * Id watcher
     * @var int
     */
    public $fk_soc_relation;

    /**
     * Relation type
     * @var int
     */
    public $relation_type;


    /**
     * Element list of public space availability
     * @var array ('propal', 'commande', 'facture', 'shipping', 'fichinter', 'contrat')
     * expedition=shipping in class
     */
    public static $psa_element_list = array('propal', 'commande', 'facture', 'shipping', 'fichinter', 'contrat');
    //public static $psa_element_list = array('propal', 'commande', 'facture', 'shipping', 'fichinter', 'contrat', 'order_supplier');


    /**
     * Constructor
     *
     * @param   DoliDb $db Database handler
     */
    public function __construct(DoliDB $db)
    {
        $this->db = $db;
    }


    /**
     * Create companies relationship into database
     * @deprecated use $this->createRelationshipThirdparty($socid, self::RELATION_TYPE_BENEFACTOR, $socid_benefactor) instead
     *
     * @param   int     $socid                  Main company ID
     * @param   int     $socid_benefactor       Benefactor company ID
     * @return  int     <0 if KO, Id of created companies relationship if OK, 0 if already created
     *
     * @throws  Exception
     */
    public function createRelationship($socid, $socid_benefactor)
    {
        return $this->createRelationshipThirdparty($socid, self::RELATION_TYPE_BENEFACTOR, $socid_benefactor);
    }

    /**
     * Create thirdparty relationship into database
     *
     * @param   int         $socid              Id of principal company
     * @param   int         $relation_type      Relation type (ex : self::RELATION_TYPE_BENEFACTOR, self::RELATION_TYPE_WATCHER)
     * @param   int         $socid_relation     Id of company in relation
     * @return  int         <0 if KO, Id of created companies relationship if OK, 0 if already created
     *
     * @throws  Exception
     */
    public function createRelationshipThirdparty($socid, $relation_type, $socid_relation)
    {
        global $langs, $user;

        $error = 0;
        $this->errors = array();

        $langs->load('errors');

        dol_syslog(__METHOD__ . " socid=" . $socid . " relation_type=" . $relation_type . " socid_relation=" . $socid_relation, LOG_DEBUG);

        // Check parameters
        if (!($socid>0)) {
            $this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyRelationshipsMainCompany"));
            $this->errors[] = $this->error;
            $error++;
        }
        if (!($relation_type>=0)) {
            $this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyRelationshipsRelationType"));
            $this->errors[] = $this->error;
            $error++;
        }
        if (!($socid_relation>0)) {
            $this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyRelationshipsRelationCompany"));
            $this->errors[] = $this->error;
            $error++;
        }
        if ($error) {
            return -1;
        }

        dol_include_once('/companyrelationships/class/companyrelationshipsavailability.class.php');

        // get thirdparty keyname for this relation type
        $thirdparty_key_name = $this->_getRelationThirdpartyKeyName($relation_type);

        // Insert relationship
        $sql  = "INSERT INTO " . MAIN_DB_PREFIX . "companyrelationships (";
        $sql .= "";
        $sql .= "fk_soc";
        $sql .= ", " . $thirdparty_key_name;
        $sql .= ", relation_type";
        $sql .= ") VALUES (";
        $sql .= $socid;
        $sql .= ", " . $socid_relation;
        $sql .= ", " . $relation_type;
        $sql .= ")";

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
        } else {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "companyrelationships");

            if ($this->id > 0) {
                $relation_psa_key_name = $this->_getRelationPublicSpaceAvailabilityKeyName($relation_type);
                $relation_cra_key_name = $this->_getRelationAvailabilityKeyName($relation_type);

                $publicSpaceAvailabilityDefaultList = $this->getAllPublicSpaceAvailabilityByDefault();
                if (!is_array($publicSpaceAvailabilityDefaultList)) {
                    $error++;
                }

                if (!$error) {
                    foreach ($publicSpaceAvailabilityDefaultList as $psaId => $psaAvailabilityArray) {
                        // create public space availability for this relationship
                        $companyRelationshipsAvailability = new CompanyRelationshipsAvailability($this->db);
                        $companyRelationshipsAvailability->fk_companyrelationships = $this->id;
                        $companyRelationshipsAvailability->fk_c_companyrelationships_availability = $psaId;
                        $companyRelationshipsAvailability->principal_availability   = intval($psaAvailabilityArray['principal_availability']);
                        $companyRelationshipsAvailability->{$relation_cra_key_name} = intval($psaAvailabilityArray[$relation_psa_key_name]);
                        $result = $companyRelationshipsAvailability->create($user);

                        if ($result < 0) {
                            $error++;
                            $this->error  = $companyRelationshipsAvailability->error;
                            $this->errors = $companyRelationshipsAvailability->errors;
                            break;
                        }
                    }
                }
            }
        }

        // Commit or rollback
        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);
            return $this->id;
        }
    }


    /**
     * Update companies relationship into database
     * @deprecated use $this->createRelationshipThirdparty($socid, self::RELATION_TYPE_BENEFACTOR, $socid_benefactor) instead
     *
     * @param   int     $rowid                          ID of the relationship
     * @param   int     $socid                          Main company ID
     * @param   int     $socid_benefactor               Benefactor company ID
     * @param   array   $publicSpaceAvailabilityArray   Array of public space availability (rowid in public space availability dictionary)
     * @param   int     $mode
     * @return  int     <0 if KO, >0 if OK
     *
     * @throws  Exception
     */
    public function updateRelationship($rowid, $socid, $socid_benefactor, $publicSpaceAvailabilityArray=array(), $mode=0)
    {
        $this->id = $rowid;
        $relation_direction = ($mode ? 1 : -1);
        return $this->updateRelationshipThirdparty($socid, self::RELATION_TYPE_BENEFACTOR, $socid_benefactor, $publicSpaceAvailabilityArray, $relation_direction);
    }

    /**
     * Update thirdparty relationship into database
     *
     * @param   int     $socid                          Id of principal company
     * @param   int     $relation_type                  Relation type
     * @param   int     $socid_relation                 Id of company in relation
     * @param   array   $publicSpaceAvailabilityArray   Array of public space availability (rowid in public space availability dictionary)
     * @param   int     $relation_direction             [=1] Relation direction, -1 to inverse relation
     * @return  int     <0 if KO, >0 if OK
     *
     * @throws  Exception
     */
    public function updateRelationshipThirdparty($socid, $relation_type, $socid_relation, $publicSpaceAvailabilityArray=array(), $relation_direction=1)
    {
        global $langs, $user;

        $error = 0;
        $this->errors = array();

        $langs->load('errors');

        dol_syslog(__METHOD__ . " socid=" . $socid . " relation_type=" . $relation_type . " socid_relation=" . $socid_relation, LOG_DEBUG);

        // get thirdparty key name for this relation type
        $thirdparty_key_name = $this->_getRelationThirdpartyKeyName($relation_type);

        // Check parameters
        if (!($this->id > 0)) {
            $this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("TechnicalID"));
            $this->errors[] = $this->error;
            $error++;
        }
        if (!($socid>0)) {
            $this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyRelationshipsMainCompany"));
            $this->errors[] = $this->error;
            $error++;
        }
        if (!($relation_type>=0)) {
            $this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyRelationshipsRelationType"));
            $this->errors[] = $this->error;
            $error++;
        }
        if (!($socid_relation>0)) {
            $this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyRelationshipsRelationCompany"));
            $this->errors[] = $this->error;
            $error++;
        }
        if ($error) {
            return -1;
        }

        dol_include_once('/companyrelationships/class/companyrelationshipsavailability.class.php');

        // Update relationship
        $sql  = "UPDATE " . MAIN_DB_PREFIX . "companyrelationships";
        $sql .= " SET fk_soc = " . $socid;
        $sql .= ", " . $thirdparty_key_name . " = " . $socid_relation;
        $sql .= ", relation_type = " . $relation_type;
        $sql .= " WHERE rowid = " . $this->id;

        $this->db->begin();

        $resql = $this->db->query($sql);
        if (!$resql) {
            $error++;
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
        }

        if (!$error && $user->rights->companyrelationships->update_md->relationship) {
            // update public space availability for this company relationships
            if (count($publicSpaceAvailabilityArray) > 0) {
                $relation_cra_key_name = $this->_getRelationAvailabilityKeyName($relation_type, $relation_direction);

                foreach($publicSpaceAvailabilityArray as $psaId => $psaAvailability) {
                    $companyRelationshipsAvailability = CompanyRelationshipsAvailability::loadByUniqueKey($this->db, $this->id, $psaId);
                    if ($companyRelationshipsAvailability < 0) {
                        $error++;
                        $this->error  = $companyRelationshipsAvailability->error;
                        $this->errors = $companyRelationshipsAvailability->errors;
                        break;
                    }

                    if (intval($psaAvailability) > 0) {
                        $companyRelationshipsAvailability->{$relation_cra_key_name} = 1;
                    } else {
                        $companyRelationshipsAvailability->{$relation_cra_key_name} = 0;
                    }

                    // save public space availability for company relationships
                    if ($companyRelationshipsAvailability->id > 0) {
                        $result = $companyRelationshipsAvailability->update($user);
                    } else {
                        $result = $companyRelationshipsAvailability->create($user);
                    }

                    if ($result < 0) {
                        $error++;
                        $this->error  = $companyRelationshipsAvailability->error;
                        $this->errors = $companyRelationshipsAvailability->errors;
                        break;
                    }
                }
            }
        }

        // Commit or rollback
        if ($error) {
            $this->db->rollback();
            return -1;
        } else {
            $this->db->commit();
            dol_syslog(__METHOD__ . " success", LOG_DEBUG);
            return 1;
        }
    }

    /**
     *  Delete companies relationships into database
     *  if rowid defined or socid + socid_benefactor defined => delete line
     *  if socid defined                                     => delete all relation for main company
     *  if socid_benefactor defined                          => delete all relation for benefactor company
     *
     * @param   int $rowid                  ID of the relationship
     * @param   int $socid                  Main company ID
     * @param   int $socid_benefactor       Benefactor company ID
     * @return  int                         <0 if KO, >0 if OK
     *
     * @throws  Exception
     */
    public function deleteRelationships($rowid=0, $socid=0, $socid_benefactor=0)
    {
        global $user;

        $this->errors = array();

        dol_syslog(__METHOD__ . " rowid=" . $rowid . " socid=" . $socid . " socid_benefactor=" . $socid_benefactor, LOG_DEBUG);

        if ($rowid > 0) {
            dol_include_once('/companyrelationships/class/companyrelationshipsavailability.class.php');

            // delete public space availability for this company relationships
            $companyRelationshipsAvailability = new CompanyRelationshipsAvailability($this->db);
            $ret = $companyRelationshipsAvailability->deleteAllByFkCompanyRelationships($rowid, $user);
            if ($ret < 0) {
                $this->errors = $companyRelationshipsAvailability->errors;
                return -1;
            }
        }

        // Delete relationship
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "companyrelationships WHERE";
        if ($rowid > 0) {
            $sql .= " rowid = " . $rowid;
        } elseif ($socid > 0 && $socid_benefactor > 0) {
            $sql .= " fk_soc = " . $socid . " AND fk_soc_benefactor = " . $socid_benefactor;
        } elseif ($socid > 0) {
            $sql .= " fk_soc = " . $socid;
        } elseif ($socid_benefactor > 0) {
            $sql .= " fk_soc_benefactor = " . $socid_benefactor;
        }

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);

            return -1;
        }

        return 1;
    }

    /**
     * Get thirdparty key name from relation type
     *
     * @param   int     $relation_type      Relation type (ex : self::RELATION_TYPE_BENEFACTOR, self::RELATION_TYPE_WATCHER)
     * @return  string
     */
    private function _getRelationThirdpartyKeyName($relation_type)
    {
        $key_name = '';

        switch ($relation_type) {
            case self::RELATION_TYPE_PRINCIPAL :
                $key_name = 'fk_soc';
                break;

            case self::RELATION_TYPE_BENEFACTOR :
                $key_name = 'fk_soc_benefactor';
                break;
            case self::RELATION_TYPE_WATCHER :
                $key_name = 'fk_soc_relation';
                break;
            default : break;
        }

        return $key_name;
    }

    /**
     * Get key name for public space availability in relation
     *
     * @param   int     $relation_type          Relation type (ex : self::RELATION_TYPE_BENEFACTOR, self::RELATION_TYPE_WATCHER)
     * @param   int     $relation_direction     [=1] Relation direction, -1 to inverse relation
     * @return  string
     */
    private function _getRelationPublicSpaceAvailabilityKeyName($relation_type, $relation_direction=1)
    {
        $key_name = '';

        switch($relation_type) {
            case self::RELATION_TYPE_BENEFACTOR :
                $key_name = ($relation_direction==1 ? 'benefactor_availability' : 'principal_availability');
                break;
            case self::RELATION_TYPE_WATCHER :
                $key_name = ($relation_direction==1 ? 'watcher_availability' : 'principal_availability');
                break;
            default : break;
        }

        return $key_name;
    }

    /**
     * Get key name for company relationships availability in relation
     *
     * @param   int     $relation_type          Relation type (ex : self::RELATION_TYPE_BENEFACTOR, self::RELATION_TYPE_WATCHER)
     * @param   int     $relation_direction     [=1] Relation direction, -1 to inverse relation
     * @return  string
     */
    private function _getRelationAvailabilityKeyName($relation_type, $relation_direction=1)
    {
        $key_name = '';

        switch($relation_type) {
            case self::RELATION_TYPE_BENEFACTOR :
                $key_name = ($relation_direction==1 ? 'benefactor_availability' : 'principal_availability');
                break;
            case self::RELATION_TYPE_WATCHER :
                $key_name = ($relation_direction==1 ? 'relation_availability' : 'principal_availability');
                break;
            default : break;
        }

        return $key_name;
    }

    /**
     * Get relation type name from relation type
     *
     * @param   int     $relation_type      Relation type (ex : self::RELATION_TYPE_BENEFACTOR, self::RELATION_TYPE_WATCHER)
     * @return  string
     */
    public function getRelationTypeName($relation_type)
    {
        $relation_type_name = '';

        switch ($relation_type) {
            case self::RELATION_TYPE_BENEFACTOR :
                $relation_type_name = 'benefactor';
                break;
            case self::RELATION_TYPE_WATCHER :
                $relation_type_name = 'watcher';
                break;
            default : break;
        }

        return $relation_type_name;
    }

    /**
     * Fetch thirdparty relationship (only first one)
     *
     * @param   int         $socid              Id of principal company
     * @param   int         $relation_type      Relation type (ex : self::RELATION_TYPE_BENEFACTOR, self::RELATION_TYPE_WATCHER)
     * @return  int         <0 if KO, >0 if OK
     *
     * @throws  Exception
     */
    public function fetchRelationshipThirdparty($socid, $relation_type)
    {
        // find thirdparty in relation
        $sql  = "SELECT";
        $sql .= " rowid";
        $sql .= ", fk_soc";
        $sql .= ", fk_soc_benefactor";
        $sql .= ", fk_soc_relation";
        $sql .= ", relation_type";
        $sql .= " FROM " .  MAIN_DB_PREFIX . "companyrelationships";
        $sql .= " WHERE fk_soc = " . $socid;
        $sql .= " AND relation_type = " . $relation_type;
        $sql .= " ORDER BY rowid";
        $sql .= " LIMIT 1";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        } else {
            if ($this->db->num_rows($resql) > 0) {
                $obj = $this->db->fetch_object($resql);
                $this->id                = $obj->rowid;
                $this->fk_soc            = $obj->fk_soc;
                $this->fk_soc_benefactor = $obj->fk_soc_benefactor;
                $this->fk_soc_relation    = $obj->fk_soc_relation;
                $this->relation_type     = $obj->relation_type;
            }
            return 1;
        }
    }

    /**
     * Get thirdparty in relation (only first one)
     *
     * @param   int             $socid              Id of principal company
     * @param   int             $relation_type      Relation type (ex : self::RELATION_TYPE_BENEFACTOR, self::RELATION_TYPE_WATCHER)
     * @return  int|Societe     <0 if KO, NULL or object Societe of watcher
     *
     * @throws  Exception
     */
    public function getRelationshipThirdparty($socid, $relation_type)
    {
        dol_syslog(__METHOD__ . " socid=" . $socid . " relation_type=" . $relation_type, LOG_DEBUG);

        if (!($socid > 0) || !($relation_type >= 0))
            return -1;

        $societe = NULL;

        // get thirdparty key name for this relation type
        $thirdparty_key_name = $this->_getRelationThirdpartyKeyName($relation_type);

        if (!empty($thirdparty_key_name)) {
            // fetch thirdparty in relation
            $this->fetchRelationshipThirdparty($socid, $relation_type);
            $societeId = $this->{$thirdparty_key_name};
            if ($societeId > 0) {
                require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
                $societe = new Societe($this->db);
                $societe->fetch($societeId);
            }
        }

        return $societe;
    }

    /**
     * Save thirdparty in relation (only first one)
     *
     * @param   int     $socid                          Id of principal company
     * @param   int     $relation_type                  Relation type (ex : self::RELATION_TYPE_BENEFACTOR, self::RELATION_TYPE_WATCHER)
     * @param   int     $socid_relation                 Id of company in relation
     * @param   array   $publicSpaceAvailabilityArray   Array of public space availability (rowid in public space availability dictionary)
     * @return  int     <0 if KO, >0 if OK (Id of saved company relationship), 0 if already created
     *
     * @throws  Exception
     */
    public function saveRelationshipThirdparty($socid, $relation_type, $socid_relation, $publicSpaceAvailabilityArray=array())
    {
        dol_syslog(__METHOD__ . " socid=" . $socid . " relation_type=" . $relation_type . " socid_relation=" . $socid_relation, LOG_DEBUG);

        // fetch thirdparty in relation
        $this->fetchRelationshipThirdparty($socid, $relation_type);

        if ($this->id > 0) {
            if ($socid_relation > 0) {
                // update
                $result = $this->updateRelationshipThirdparty($socid, $relation_type, $socid_relation, $publicSpaceAvailabilityArray);
            } else {
                // delete
                $result = $this->deleteRelationships($this->id);
            }
        } else {
            if ($socid_relation > 0) {
                // create
                $result = $this->createRelationshipThirdparty($socid, $relation_type, $socid_relation);
            }
        }

        return $result;
    }


    /**
     *  Get all companies relationships
     *
     * @param   int $socid                  Company ID
     * @param   int $mode                   0: list of principal companies, 1: list of beneficial companies, 2: list of principal and beneficial companies
     * @param   int $fetch_object           Fetch company object
     * @return  int|array                   List of company ID or object (main or/and benefactor), <0 if KO
     */
    public function getRelationships($socid=0, $mode=0, $fetch_object=0)
    {
        $this->errors = array();

        dol_syslog(__METHOD__ . " socid=" . $socid . " mode=" . $mode . " fetch_object=" . $fetch_object, LOG_DEBUG);

        if (!($socid > 0) || !($mode >= 0))
            return array();

        $filter = "relation_type=" . self::RELATION_TYPE_BENEFACTOR . " AND (";
        if ($mode == 0) {
            $filter .= 'fk_soc_benefactor=' . $socid;
        } elseif ($mode == 1) {
            $filter .= 'fk_soc=' . $socid;
        } elseif ($mode == 2) {
            $filter .= 'fk_soc=' . $socid . ' OR fk_soc_benefactor=' . $socid;
        }
        $filter .= ")";

        // Get relationships
        $sql = "SELECT fk_soc, fk_soc_benefactor FROM " . MAIN_DB_PREFIX . "companyrelationships WHERE " . $filter;

        $resql = $this->db->query($sql);
        if ($resql) {
            $compagnies = array();

            require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
            while ($obj = $this->db->fetch_object($resql)) {
                $id = $obj->fk_soc == $socid ? $obj->fk_soc_benefactor : $obj->fk_soc;
                if (!isset($compagnies[$id])) {
                    if ($fetch_object) {
                        $company = new Societe($this->db);
                        $company->fetch($id);
                        $compagnies[$id] = $company;
                    } else {
                        $compagnies[$id] = $id;
                    }
                }
            }

            return $compagnies;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);

            return -1;
        }
    }

    /**
     *  Get types of companies relationships for the company
     *  0: has beneficial companies, 1: has principal companies, 2: has principal and beneficial companies
     *
     * @param   int     $socid          Company ID
     * @return  int                     -1: no relationships, 0: has beneficial companies, 1: has principal companies, 2: has principal and beneficial companies, -2: if KO
     */
    public function getRelationshipType($socid=0)
    {
        $this->errors = array();

        dol_syslog(__METHOD__ . " socid=" . $socid, LOG_DEBUG);

        if (!($socid > 0))
            return -1;

        // Get count relationships
        $sql  = "SELECT COUNT(DISTINCT fk_soc) as nb_benefactor, COUNT(DISTINCT fk_soc_benefactor) as nb_principal";
        $sql .= " FROM " . MAIN_DB_PREFIX . "companyrelationships";
        $sql .= " WHERE relation_type=" . self::RELATION_TYPE_BENEFACTOR;
        $sql .= " AND (fk_soc=" . $socid . " OR fk_soc_benefactor=" . $socid . ")";

        $resql = $this->db->query($sql);
        if ($resql) {
            $type = -1;

            if ($obj = $this->db->fetch_object($resql)) {
                if ($obj->nb_benefactor > 0 && $obj->nb_principal > 0) $type = 2;
                elseif ($obj->nb_benefactor > 0) $type = 0;
                elseif ($obj->nb_principal > 0) $type = 1;
            }

            return $type;
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);

            return -2;
        }
    }


    /**
     * Get all fields of each element in dictionary public space availability
     *
     * @param   string      $fieldName      [=''] All fields or field name in dictionary (ex : element)
     * @return  array|int   <0 if KO, array of all fields of each element if OK
     * @throws  Exception
     */
    public function getAllPublicSpaceAvailabilityByDefault($fieldName='')
    {
        global $langs;

        dol_syslog(__METHOD__ . " fieldName=" . $fieldName, LOG_DEBUG);

        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $dictionary = Dictionary::getDictionary($this->db, 'companyrelationships', 'companyrelationshipspublicspaceavailability');

        // Get lines
        $lines = $dictionary->fetch_lines(1, array(), array('rowid' => 'ASC'), 0, 0, false, true);

        if ($lines < 0) {
            $this->errors[] = $dictionary->errorsToString();
            dol_syslog(__METHOD__ . " Error : No public space availability in dictionary", LOG_ERR);
            return -1;
        } else {
            $publicSpaceAvailabilityList = array();

            if (count($lines) <= 0) {
                $this->errors[] = $langs->trans("CompanyRelationshipsErrorPublicSpaceAvailabilityDictionaryNoLines");
                dol_syslog(__METHOD__ . " Error : No lines in dictionary company relationships public space availability", LOG_ERR);
            } else {
                foreach ($lines as $line) {
                    if (!empty($fieldName)) {
                        $publicSpaceAvailabilityList[$line->id] = $line->fields[$fieldName];
                    } else {
                        $publicSpaceAvailabilityList[$line->id] = $line->fields;
                    }

                }
            }

            return $publicSpaceAvailabilityList;
        }
    }


    /**
     * Get all elements in public space availability for a company relationship
     * @deprecated use $this->getAllPublicSpaceAvailabilityThirdparty($socid, self::RELATION_TYPE_BENEFACTOR, $socid_benefactor, $element) instead
     *
     * @param   int         $socid                  Id of principal company
     * @param   int         $socid_benefactor       Id of benefactor company
     * @param   string      $element                [=''] for all elements, or element name (ex : propal, commande, etc)
     * @return  array|int   <0 if KO, array of public space availability if OK
     *
     * @throws  Exception
     */
    public function getAllPublicSpaceAvailability($socid, $socid_benefactor, $element='')
    {
        return $this->getAllPublicSpaceAvailabilityThirdparty($socid, self::RELATION_TYPE_BENEFACTOR, $socid_benefactor, $element);
    }


    /**
     * Get all elements in public space availability for a company relationship
     *
     * @param   int         $socid                  Id of principal company
     * @param   int         $relation_type          Relation type
     * @param   int         $socid_relation         Id of company in relation
     * @param   string      $element                [=''] for all elements, or element name (ex : propal, commande, etc)
     * @return  array|int   <0 if KO, array of public space availability if OK
     *
     * @throws  Exception
     */
    public function getAllPublicSpaceAvailabilityThirdparty($socid, $relation_type, $socid_relation, $element='')
    {
        global $conf;

        dol_syslog(__METHOD__ . " socid=" . $socid . " relation_type=" . $relation_type . " socid_relation=" . $socid_relation . " element=" . $element, LOG_DEBUG);

        $relation_type_name = $this->getRelationTypeName($relation_type);

        // get thirdparty key name for this relation type
        $thirdparty_key_name = $this->_getRelationThirdpartyKeyName($relation_type);

        $relation_psa_key_name = $this->_getRelationPublicSpaceAvailabilityKeyName($relation_type);
        $relation_cra_key_name = $this->_getRelationAvailabilityKeyName($relation_type);

        $sql  = "SELECT";
        $sql .= " crpsa.rowid, crpsa.element, crpsa.label, crpsa.principal_availability as psa_principal_availability, crpsa." . $relation_psa_key_name . " as psa_relation_availability";
        $sql .= ", cra.rowid as cra_id, cra.fk_companyrelationships, cra.principal_availability as cra_principal_availability, cra." . $relation_cra_key_name . " as cra_relation_availability";
        $sql .= ", cr.rowid as cr_id";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_companyrelationships_publicspaceavailability as crpsa";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "companyrelationships as cr ON cr.fk_soc = " . $socid . " AND cr." . $thirdparty_key_name . " = " . $socid_relation . " AND cr.relation_type = " . $relation_type;
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "companyrelationships_availability as cra ON cra.fk_companyrelationships = cr.rowid AND cra.fk_c_companyrelationships_availability = crpsa.rowid";
        $sql .= " WHERE crpsa.entity = " . $conf->entity;
        $sql .= " AND crpsa.active = 1";
        if (!empty($element)) {
            $sql .= " AND crpsa.element = '" . $this->db->escape($element) . "'";
        }
        $sql .= " ORDER BY crpsa.rowid ASC";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
            return -1;
        } else {
            $publicSpaceAvailabilityList = array();
            $publicSpaceAvailability = array();

            while ($obj = $this->db->fetch_object($resql)) {
                $publicSpaceAvailability['rowid']   = $obj->rowid;
                $publicSpaceAvailability['element'] = $obj->element;
                $publicSpaceAvailability['label']   = $obj->label;

                if ($obj->cra_id > 0) {
                    $publicSpaceAvailability['principal']         = intval($obj->cra_principal_availability);
                    $publicSpaceAvailability[$relation_type_name] = intval($obj->cra_relation_availability);
                } else {
                    $publicSpaceAvailability['principal']         = intval($obj->psa_principal_availability);
                    $publicSpaceAvailability[$relation_type_name] = intval($obj->psa_relation_availability);
                }

                $publicSpaceAvailabilityList[] = $publicSpaceAvailability;
            }

            return $publicSpaceAvailabilityList;
        }
    }


    /**
     * Get one element in public space availability for a company relationship
     * @deprecated use $this->getPublicSpaceAvailabilityThirdparty($socid, self::RELATION_TYPE_BENEFACTOR, $socid_benefactor, $element) instead
     *
     * @param   int         $socid                  Id of principal company
     * @param   int         $socid_benefactor       Id of benefactor company
     * @param   string      $element                Element name (ex : propal, commande, etc)
     * @return  array|int   <0 if KO, array of public space availability if OK
     * @throws  Exception
     */
    public function getPublicSpaceAvailability($socid, $socid_benefactor, $element)
    {
        return $this->getPublicSpaceAvailabilityThirdparty($socid, self::RELATION_TYPE_BENEFACTOR, $socid_benefactor, $element);
    }

    /**
     * Get one element in public space availability for a company relationship
     *
     * @param   int         $socid                  Id of principal company
     * @param   int         $relation_type          Relation type
     * @param   int         $socid_relation         Id of company in relation
     * @param   string      $element                [=''] for all elements, or element name (ex : propal, commande, etc)
     * @return  array|int   <0 if KO, array of public space availability if OK
     *
     * @throws  Exception
     */
    public function getPublicSpaceAvailabilityThirdparty($socid, $relation_type, $socid_relation, $element)
    {
        dol_syslog(__METHOD__ . " socid=" . $socid . " relation_type=" . $relation_type . " socid_relation=" . $socid_relation . " element=" . $element, LOG_DEBUG);

        $publicSpaceAvailabilityList = $this->getAllPublicSpaceAvailabilityThirdparty($socid, $relation_type, $socid_relation, $element);

        if (!is_array($publicSpaceAvailabilityList)) {
            return -1;
        } else {
            $publicSpaceAvailability = array();

            if (count($publicSpaceAvailabilityList) > 0) {
                $publicSpaceAvailability = $publicSpaceAvailabilityList[0];
            }

            return $publicSpaceAvailability;
        }
    }

    /**
     * Get the name of form for an element with his associated action
     *
     * @param   string      $element        Element name (ex : propal, commande, etc)
     * @param   string      $formAction     [=''] Form action (ex : create)
     * @return string
     */
    public function getFormNameForElementAndAction($element, $formAction='')
    {
        $formName = '';

        if ($element == 'propal') {
            if ($formAction == 'create') {
                $formName = 'addprop';
            }
        } else if ($element == 'commande') {
            if ($formAction == 'create') {
                $formName = 'crea_commande';
            }
        } else if ($element == 'facture') {
            if ($formAction == 'create') {
                $formName = 'add';
            }
        // not possible for this moment to create without origin
        /*
        } else if ($element == 'shipping') {
            if ($formAction == 'create') {
                $formName = ':first';
            }
        */
        } else if ($element == 'fichinter') {
            if ($formAction == 'create') {
                $formName = 'fichinter';
            }
        } else if ($element == 'contrat') {
            if ($formAction == 'create') {
                $formName = 'form_contract';
            }
        }

        return $formName;
    }


    /**
     * Return a link on thirdparty (with picto)
     *
     * @param   Societe     $societe                    Societe object
     * @param	int         $withpicto                  Add picto into link (0=No picto, 1=Include picto with link, 2=Picto only)
     * @param	string      $option                     Target of link ('', 'customer', 'prospect', 'supplier', 'project')
     * @param	int	        $maxlen                     Max length of name
     * @param	int  	    $notooltip                  1=Disable tooltip
     * @param   int         $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     * @return	string
     */
    function getNomUrlForSociete($societe, $withpicto=0, $option='', $maxlen=0, $notooltip=0, $save_lastsearch_value=-1)
    {
        global $conf, $langs, $hookmanager;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $name=$societe->name?$societe->name:$societe->nom;

        if (! empty($conf->global->SOCIETE_ADD_REF_IN_LIST) && (!empty($withpicto)))
        {
            if (($societe->client) && (! empty ( $societe->code_client ))
                && ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1
                    || $conf->global->SOCIETE_ADD_REF_IN_LIST == 2
                )
            )
                $code = $societe->code_client . ' - ';

            if (($societe->fournisseur) && (! empty ( $societe->code_fournisseur ))
                && ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1
                    || $conf->global->SOCIETE_ADD_REF_IN_LIST == 3
                )
            )
                $code .= $societe->code_fournisseur . ' - ';

            if ($conf->global->SOCIETE_ADD_REF_IN_LIST == 1)
                $name =$code.' '.$name;
            else
                $name =$code;
        }

        if (!empty($societe->name_alias)) $name .= ' ('.$societe->name_alias.')';

        $result=''; $label='';
        $linkstart=''; $linkend='';

        if (! empty($societe->logo) && class_exists('Form'))
        {
            $label.= '<div class="photointooltip">';
            $label.= Form::showphoto('societe', $societe, 80, 0, 0, 'photowithmargin', 'mini');
            $label.= '</div><div style="clear: both;"></div>';
        }

        $label.= '<div class="centpercent">';

        if ($option == 'customer' || $option == 'compta' || $option == 'category' || $option == 'category_supplier')
        {
            $label.= '<u>' . $langs->trans("ShowCustomer") . '</u>';
            $linkstart = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$societe->id;
        }
        else if ($option == 'prospect' && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
        {
            $label.= '<u>' . $langs->trans("ShowProspect") . '</u>';
            $linkstart = '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$societe->id;
        }
        else if ($option == 'supplier')
        {
            $label.= '<u>' . $langs->trans("ShowSupplier") . '</u>';
            $linkstart = '<a href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$societe->id;
        }
        else if ($option == 'agenda')
        {
            $label.= '<u>' . $langs->trans("ShowAgenda") . '</u>';
            $linkstart = '<a href="'.DOL_URL_ROOT.'/societe/agenda.php?socid='.$societe->id;
        }
        else if ($option == 'project')
        {
            $label.= '<u>' . $langs->trans("ShowProject") . '</u>';
            $linkstart = '<a href="'.DOL_URL_ROOT.'/societe/project.php?socid='.$societe->id;
        }
        else if ($option == 'margin')
        {
            $label.= '<u>' . $langs->trans("ShowMargin") . '</u>';
            $linkstart = '<a href="'.DOL_URL_ROOT.'/margin/tabs/thirdpartyMargins.php?socid='.$societe->id.'&type=1';
        }
        // specific link for this module
        else if ($option == 'companyrelationships')
        {
            $label.= '<u>' . $langs->trans("ShowCompany") . '</u>';
            $linkstart = '<a href="' . dol_buildpath('/companyrelationships/companyrelationships.php?socid=' . $societe->id, 1);
        }

        // By default
        if (empty($linkstart))
        {
            $label.= '<u>' . $langs->trans("ShowCompany") . '</u>';
            $linkstart = '<a href="'.DOL_URL_ROOT.'/societe/card.php?socid='.$societe->id;
        }

        if (! empty($societe->name))
        {
            $label.= '<br><b>' . $langs->trans('Name') . ':</b> '. $societe->name;
            if (! empty($societe->name_alias)) $label.=' ('.$societe->name_alias.')';
        }
        if (! empty($societe->code_client) && $societe->client)
            $label.= '<br><b>' . $langs->trans('CustomerCode') . ':</b> '. $societe->code_client;
        if (! empty($societe->code_fournisseur) && $societe->fournisseur)
            $label.= '<br><b>' . $langs->trans('SupplierCode') . ':</b> '. $societe->code_fournisseur;
        if (! empty($conf->accounting->enabled) && $societe->client)
            $label.= '<br><b>' . $langs->trans('CustomerAccountancyCode') . ':</b> '. $societe->code_compta;
        if (! empty($conf->accounting->enabled) && $societe->fournisseur)
            $label.= '<br><b>' . $langs->trans('SupplierAccountancyCode') . ':</b> '. $societe->code_compta_fournisseur;

        $label.= '</div>';

        // Add type of canvas
        $linkstart.=(!empty($societe->canvas)?'&canvas='.$societe->canvas:'');
        // Add param to save lastsearch_values or not
        $add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
        if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
        if ($add_save_lastsearch_values) $linkstart.='&save_lastsearch_values=1';
        $linkstart.='"';

        $linkclose='';
        if (empty($notooltip))
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowCompany");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip"';

            if (! is_object($hookmanager))
            {
                include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
                $hookmanager=new HookManager($societe->db);
            }
            $hookmanager->initHooks(array('cr_societedao'));
            $parameters=array('id'=>$societe->id);
            $reshook=$hookmanager->executeHooks('cr_getnomurltooltip',$parameters,$societe,$action);    // Note that $action and $societe may have been modified by some hooks
            if ($reshook > 0) $linkclose = $hookmanager->resPrint;
        }
        $linkstart.=$linkclose.'>';
        $linkend='</a>';

        global $user;
        if (! $user->rights->societe->client->voir && $user->societe_id > 0 && $societe->id != $user->societe_id)
        {
            $linkstart='';
            $linkend='';
        }

        if ($withpicto) $result.=($linkstart.img_object(($notooltip?'':$label), 'company', ($notooltip?'':'class="classfortooltip valigntextbottom"'), 0, 0, $notooltip?0:1).$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$linkstart.($maxlen?dol_trunc($name,$maxlen):$name).$linkend;

        return $result;
    }
}