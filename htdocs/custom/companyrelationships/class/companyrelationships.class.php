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
     * Element list of public space availability
     * @var array ('propal', 'commande', 'facture', 'expedition', 'ficheinter')
     * expedition=shipping in class
     */
    public static $psa_element_list = array('propal', 'commande', 'facture', 'expedition', 'fichinter');


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
     *
     * @param   int     $socid                  Main company ID
     * @param   int     $socid_benefactor       Benefactor company ID
     * @return  int     <0 if KO, Id of created companies relationship if OK, 0 if already created
     * @throws  Exception
     */
    public function createRelationship($socid, $socid_benefactor)
    {
        global $user;

        $error = 0;
        $this->errors = array();

        dol_syslog(__METHOD__ . " socid=" . $socid . " socid_benefactor=" . $socid_benefactor, LOG_DEBUG);

        dol_include_once('/companyrelationships/class/companyrelationshipsavailability.class.php');

        // Insert relationship
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "companyrelationships (fk_soc, fk_soc_benefactor)";
        $sql .= " VALUES (" . $socid . ", " . $socid_benefactor . ")";

        $this->db->begin();

        $resql = $this->db->query($sql);
        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "companyrelationships");

            if ($this->id > 0) {
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
                        $companyRelationshipsAvailability->principal_availability = intval($psaAvailabilityArray['principal_availability']);
                        $companyRelationshipsAvailability->benefactor_availability = intval($psaAvailabilityArray['benefactor_availability']);
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
        } else if ($this->db->lasterrno() != 'DB_ERROR_RECORD_ALREADY_EXISTS') {
            $error++;
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $this->db->lasterror(), LOG_ERR);
        } else {
            $this->db->rollback();
            return 0;
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
     *
     * @param   int     $rowid                          ID of the relationship
     * @param   int     $socid                          Main company ID
     * @param   int     $socid_benefactor               Benefactor company ID
     * @param   array   $publicSpaceAvailabilityArray   Array of public space availability (rowid in public space availability dictionary)
     * @param   int     $mode
     * @return  int     <0 if KO, >0 if OK
     * @throws  Exception
     */
    public function updateRelationship($rowid, $socid, $socid_benefactor, $publicSpaceAvailabilityArray=array(), $mode=0)
    {
        global $user;

        $error = 0;
        $this->errors = array();

        dol_syslog(__METHOD__ . " rowid=" . $rowid . " socid=" . $socid . " socid_benefactor=" . $socid_benefactor, LOG_DEBUG);

        dol_include_once('/companyrelationships/class/companyrelationshipsavailability.class.php');

        // Update relationship
        $sql = "UPDATE " . MAIN_DB_PREFIX . "companyrelationships" .
            " SET fk_soc = " . $socid . ", fk_soc_benefactor = " . $socid_benefactor .
            " WHERE rowid = " . $rowid;

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
                $availabilityFieldName = $mode ? 'benefactor_availability' : 'principal_availability';

                foreach($publicSpaceAvailabilityArray as $psaId => $psaAvailability) {
                    $companyRelationshipsAvailability = CompanyRelationshipsAvailability::loadByUniqueKey($this->db, $rowid, $psaId);
                    if ($companyRelationshipsAvailability < 0) {
                        $error++;
                        $this->error  = $companyRelationshipsAvailability->error;
                        $this->errors = $companyRelationshipsAvailability->errors;
                        break;
                    }

                    if (intval($psaAvailability) > 0) {
                        $companyRelationshipsAvailability->{$availabilityFieldName} = 1;
                    } else {
                        $companyRelationshipsAvailability->{$availabilityFieldName} = 0;
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

        $filter = '';
        if ($mode == 0) {
            $filter = 'fk_soc_benefactor=' . $socid;
        } elseif ($mode == 1) {
            $filter = 'fk_soc=' . $socid;
        } elseif ($mode == 2) {
            $filter = 'fk_soc=' . $socid . ' OR fk_soc_benefactor=' . $socid;
        }

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
        $sql = "SELECT COUNT(DISTINCT fk_soc) as nb_benefactor, COUNT(DISTINCT fk_soc_benefactor) as nb_principal FROM " . MAIN_DB_PREFIX . "companyrelationships WHERE fk_soc=" . $socid . " OR fk_soc_benefactor=" . $socid;

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

        dol_syslog(__METHOD__, LOG_DEBUG);

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
     *
     * @param   int         $socid                  Id of principal company
     * @param   innt        $socid_benefactor       Id of benefactor company
     * @param   string      $element                [=''] for all elements, or element name (ex : propal, commande, etc)
     * @return  array|int   <0 if KO, array of public space availability if OK
     * @throws  Exception
     */
    public function getAllPublicSpaceAvailability($socid, $socid_benefactor, $element='')
    {
        global $conf;

        dol_syslog(__METHOD__ . " socid=" . $socid . " socid_benefactor=" . $socid_benefactor . " element=" . $element, LOG_DEBUG);

        $sql  = "SELECT";
        $sql .= " crpsa.rowid, crpsa.element, crpsa.label, crpsa.principal_availability, crpsa.benefactor_availability";
        $sql .= ", cra.rowid as cra_id, cra.fk_companyrelationships, cra.principal_availability as cra_principal_availability, cra.benefactor_availability as cra_benefactor_availability";
        $sql .= ", cr.rowid as cr_id, cr.fk_soc as cr_fk_fk_soc, cr.fk_soc_benefactor as cr_fk_soc_benefactor";
        $sql .= " FROM " . MAIN_DB_PREFIX . "c_companyrelationships_publicspaceavailability as crpsa";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "companyrelationships as cr ON cr.fk_soc = " . $socid . " AND cr.fk_soc_benefactor = " . $socid_benefactor;
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
                    $publicSpaceAvailability['principal']  = intval($obj->cra_principal_availability);
                    $publicSpaceAvailability['benefactor'] = intval($obj->cra_benefactor_availability);
                } else {
                    $publicSpaceAvailability['principal']  = intval($obj->principal_availability);
                    $publicSpaceAvailability['benefactor'] = intval($obj->benefactor_availability);
                }

                $publicSpaceAvailabilityList[] = $publicSpaceAvailability;
            }

            return $publicSpaceAvailabilityList;
        }
    }


    /**
     * Get one element in public space availability for a company relationship
     *
     * @param   int         $socid                  Id of principal company
     * @param   innt        $socid_benefactor       Id of benefactor company
     * @param   string      $element                Element name (ex : propal, commande, etc)
     * @return  array|int   <0 if KO, array of public space availability if OK
     * @throws  Exception
     */
    public function getPublicSpaceAvailability($socid, $socid_benefactor, $element)
    {
        dol_syslog(__METHOD__ . " socid=" . $socid . " socid_benefactor=" . $socid_benefactor . " element=" . $element, LOG_DEBUG);

        $publicSpaceAvailabilityList = $this->getAllPublicSpaceAvailability($socid, $socid_benefactor, $element);

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
}