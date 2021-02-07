<?php
/* Copyright (C) 2021 SuperAdmin <infra@synergies-france.fr>
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
 * \file    core/triggers/interface_99_modPropalApprovement_PropalApprovementTriggers.class.php
 * \ingroup propalapprovement
 * \brief   Example trigger.
 *
 * Put detailed description here.
 *
 * \remarks You can create other triggers by copying this one.
 * - File name should be either:
 *      - interface_99_modPropalApprovement_MyTrigger.class.php
 *      - interface_99_all_MyTrigger.class.php
 * - The file must stay in core/triggers
 * - The class name must be InterfaceMytrigger
 * - The constructor method must be named InterfaceMytrigger
 * - The name property name must be MyTrigger
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
 *  Class of triggers for PropalApprovement module
 */
class InterfacePropalApprovementTriggers extends DolibarrTriggers
{
    /**
     * Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i', '', get_class($this));
        $this->family = "demo";
        $this->description = "PropalApprovement triggers.";
        // 'development', 'experimental', 'dolibarr' or version
        $this->version = 'development';
        $this->picto = 'propalapprovement@propalapprovement';
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
     * @param string        $action     Event action code
     * @param CommonObject  $object     Object
     * @param User          $user       Object user
     * @param Translate     $langs      Object langs
     * @param Conf          $conf       Object conf
     * @return int                      <0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
        if (empty($conf->propalapprovement->enabled)) {
            return 0; // If module is not enabled, we do nothing
        }

        // Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action

        // You can isolate code for each action in a separate method: this method should be named like the trigger in camelCase.
        // For example : COMPANY_CREATE => public function companyCreate($action, $object, User $user, Translate $langs, Conf $conf)
        $methodName = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($action)))));
        $callback = array($this, $methodName);
        if (is_callable($callback)) {
            dol_syslog(
                "Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id
            );

            return call_user_func($callback, $action, $object, $user, $langs, $conf);
        };

        if ($object->element == 'propal') {
            $arrayOfTriggerCodeConfigurationAndEventContent = array(
                'PROPAL_AWAITING' => array(
                    'activated' => $conf->global->PROPALAPPROVEMENT_EVENTONAPPROVEMENTREQUEST,
                    'title' => $langs->trans('PropalApprovementEventCreateTitle', $object->ref),
                    'description' => $langs->trans('PropalApprovementEventCreateDescription', $object->ref)
                )
            );
        }

        if (!empty($arrayOfTriggerCodeConfigurationAndEventContent[$action]['activated'])) {
            $titleAndDescription = $arrayOfTriggerCodeConfigurationAndEventContent[$action];

            // Insertion action
            $now = dol_now();
            dol_include_once('/comm/action/class/actioncomm.class.php');
            $actioncomm = new ActionComm($this->db);
            $actioncomm->type_code   = "AC_OTH_AUTO";       // Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
            $actioncomm->code        = 'AC_' . $action;
            $actioncomm->label       = $titleAndDescription['title'];
            $actioncomm->note        = $titleAndDescription['description'] . "<br>" . $langs->trans('PropalApprovementEventAuthor', $user->login);
            //$actioncomm->fk_project  = $object->getLinkedProjectId();
            $actioncomm->datep       = $now;
            $actioncomm->datef       = $now;
            $actioncomm->percentage  = -1;   // Not applicable
            $actioncomm->socid       = $object->fk_soc;
            //$actioncomm->contactid   = $object->fk_people_type == 'contact' ? $object->fk_people_object : null;
            $actioncomm->authorid    = $user->id;   // User saving action
            $actioncomm->userownerid = $user->id;   // Owner of action
            $actioncomm->fk_element  = $object->id;
            $actioncomm->elementtype = $object->element;
            $ret = $actioncomm->create($user);       // User creating action
            return $ret;
        }
        return 0;
    }
}
