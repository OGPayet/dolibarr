<?php
/* Copyright (C) 2005-2017	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2017	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2015		Bahfir Abbes			<bafbes@gmail.com>
 * Copyright (C) 2018		Open-Dsi			    <support@open-dsi.fr>
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
 *	\file       htdocs/companyrelationships/core/triggers/iinterface_99_modCompanyRelationships_CompanyRelationshipsMassAction.class.php
 *  \ingroup    agenda
 *  \brief      Trigger file for agenda module
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggered functions for mass actions
 */
class InterfaceCompanyRelationshipsMassAction extends DolibarrTriggers
{
	public $family = 'companyrelationships';
	public $description = "Triggers of this module RelationsTiersContacts to manage mass actions.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'technic';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * Following properties may be set before calling trigger. The may be completed by this trigger to be used for writing the event into database:
	 *      $object->actiontypecode (translation action code: AC_OTH, ...)
	 *      $object->actionmsg (note, long text)
	 *      $object->actionmsg2 (label, short text)
	 *      $object->sendtoid (id of contact or array of ids)
	 *      $object->socid (id of thirdparty)
	 *      $object->fk_project
	 *      $object->fk_element
	 *      $object->elementtype
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (empty($conf->companyrelationships->enabled)) return 0;     // Module not active, we do nothing

        // invoice create
        if ($action == 'BILL_CREATE') {
            dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);

            dol_include_once('/companyrelationships/class/companyrelationships.class.php');

            $langs->load('companyrelatioships@companyrelatioships');

            // for mass action from order list
            if ($object->socid > 0 && in_array($object->origin, array('commande', 'contrat')) && $object->origin_id > 0) {
                // Parse element/subelement (ex: project_task)
                $element = $subelement = $object->origin;
                if (preg_match('/^([^_]+)_([^_]+)/i', $object->origin, $regs)) {
                    $element = $regs [1];
                    $subelement = $regs [2];
                }

                // For compatibility
                if ($element == 'order') {
                    $element = $subelement = 'commande';
                }
                if ($element == 'propal') {
                    $element = 'comm/propal';
                    $subelement = 'propal';
                }
                if ($element == 'contract') {
                    $element = $subelement = 'contrat';
                }
                if ($element == 'inter') {
                    $element = $subelement = 'ficheinter';
                }
                if ($element == 'shipping') {
                    $element = $subelement = 'expedition';
                }

                dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');
                $classname = ucfirst($subelement);
                $srcobject = new $classname($this->db);
                $result = $srcobject->fetch($object->origin_id);
                $srcobject->fetch_optionals();

                // fetch extrafields of this invoice
                $object->fetch_optionals();

                // verify if it's an order
                if (is_object($srcobject)) {
                    $insert_extrafields = FALSE;
                    $companyRelationships = new CompanyRelationships($this->db);

                    // benefactor : /!\ can provide from create card : check if we haven't got extrafields yet
                    if (!($object->array_options['options_companyrelationships_fk_soc_benefactor'] > 0)) {
                        // set benefactor company
                        if ($srcobject->array_options['options_companyrelationships_fk_soc_benefactor'] > 0) {
                            $object->array_options['options_companyrelationships_fk_soc_benefactor'] = $srcobject->array_options['options_companyrelationships_fk_soc_benefactor'];
                        } else {
                            $object->array_options['options_companyrelationships_fk_soc_benefactor'] = $object->socid;
                        }

                        $relation_type = CompanyRelationships::RELATION_TYPE_BENEFACTOR;
                        $relation_type_name = $companyRelationships->getRelationTypeName($relation_type);
                        $publicSpaceAvailability = $companyRelationships->getPublicSpaceAvailabilityThirdparty($object->socid, $relation_type, $object->array_options['options_companyrelationships_fk_soc_benefactor'], $object->element);

                        if (!is_array($publicSpaceAvailability)) {
                            $object->error = $companyRelationships->error;
                            $object->errors = $companyRelationships->errors;
                            dol_syslog(__METHOD__ . " Error : " . $object->errorsToString(), LOG_ERR);
                            return -1;
                        }

                        // modify extrafields for this invoice
                        $object->array_options['options_companyrelationships_availability_principal'] = $publicSpaceAvailability['principal'];
                        $object->array_options['options_companyrelationships_availability_benefactor'] = $publicSpaceAvailability[$relation_type_name];
                        $insert_extrafields = TRUE;
                    }

                    // watcher
                    if (!($object->array_options['options_companyrelationships_fk_soc_watcher'] > 0)) {
                        // set watcher company
                        if ($srcobject->array_options['options_companyrelationships_fk_soc_watcher'] > 0) {
                            $object->array_options['options_companyrelationships_fk_soc_watcher'] = $srcobject->array_options['options_companyrelationships_fk_soc_watcher'];

                            $relation_type = CompanyRelationships::RELATION_TYPE_WATCHER;
                            $relation_type_name = $companyRelationships->getRelationTypeName($relation_type);
                            $publicSpaceAvailability = $companyRelationships->getPublicSpaceAvailabilityThirdparty($object->socid, $relation_type, $object->array_options['options_companyrelationships_fk_soc_watcher'], $object->element);

                            if (!is_array($publicSpaceAvailability)) {
                                $object->error = $companyRelationships->error;
                                $object->errors = $companyRelationships->errors;
                                dol_syslog(__METHOD__ . " Error : " . $object->errorsToString(), LOG_ERR);
                                return -1;
                            }

                            // modify extrafields for this invoice
                            $object->array_options['options_companyrelationships_availability_watcher'] = $publicSpaceAvailability[$relation_type_name];
                            $insert_extrafields = TRUE;
                        }
                    }

                    if ($insert_extrafields === TRUE) {
                        $ret = $object->insertExtrafields();
                        if ($ret < 0) {
                            dol_syslog(__METHOD__ . " Error : " . $object->errorsToString(), LOG_ERR);
                            return -1;
                        }
                    }
                }
            }

            return 1;
        }

        return 0;
    }
}
