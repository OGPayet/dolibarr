<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
 *  Copyright (C) 2020      Alexis LAURIER       <contact@alexislaurier.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modInterventionSurvey_InterventionSurveyTriggers.class.php
 * \ingroup interventionsurvey
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modInterventionSurvey_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT . '/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for InterventionSurvey module
 */
class InterfaceSendByMailInterventionSurveyTriggers extends DolibarrTriggers
{
    /**
     * @var DoliDB Database handler
     */
    protected $db;

    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "crm";
        $this->description = "Send by mail InterventionSurvey triggers.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = '1.0';
        $this->picto = 'interventionsurvey@interventionsurvey';
    }

    /**
     * Trigger name
     *
     * @return string Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * @return string Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }


    /**
     * Function called when a Dolibarrr business event is done.
     * All functions "runTrigger" are triggered if file
     * is inside directory core/triggers
     *
     * @param string 		$action 	Event action code
     * @param CommonObject 	$object 	Object
     * @param User 			$user 		Object user
     * @param Translate 	$langs 		Object langs
     * @param Conf 			$conf 		Object conf
     * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (empty($conf->interventionsurvey->enabled)) return 0;     // If module is not enabled, we do nothing

        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action

        switch ($action) {
            case 'FICHINTER_FIELDS_CHECKED':
                dol_syslog("Trigger for action '$action' launched. id=".$object->id);
                
                if ($conf->global->INTERVENTIONSURVEY_SEND_FICHINTER_BY_MAIL) {
                    require_once DOL_DOCUMENT_ROOT . '/custom/interventionsurvey/lib/interventionsurvey_interventionmail.lib.php';

                    $interventionMail = new InterventionMail($this->db, $object, $user);
                    
                    $sendfrom = $conf->global->INTERVENTIONSURVEY_DEFAULT_EMAIL_ADDRESS_SENDER;
                    $interventionMail->sendInterventionByMail($sendfrom);
                }

                return 0;
                break;
            default:
                dol_syslog("Trigger '" . $this->name . "' for action '$action' launched by " . __FILE__ . ". id=" . $object->id);
                break;
        }

        return 0;
    }
}

?>