<?php
/* Copyright (C) ---Put here your own copyright and developer email---
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
 * \file    htdocs/modulebuilder/template/class/actions_mymodule.class.php
 * \ingroup mymodule
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsPropalapprovement
 */
class ActionsPropalapprovement
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     * @var string[] cache data for translated content
     */
    public static $cache_propal_status_label = array();

    /**
     * @var string Error code (or message)
     */
    public $error = '';

    /**
     * @var array Errors
     */
    public $errors = array();


    /**
     * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
     */
    public $results = array();

    /**
     * @var string String displayed by executeHook() immediately after return
     */
    public $resprints;


    /**
     * Constructor
     *
     *  @param      DoliDB      $db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the doActions function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        $this->addStatusLabelToPropalHookObject($object);
        return 0;
    }

    /**
     * Overloading the printFieldListTitle function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printFieldListTitle($parameters, &$object, &$action, $hookmanager)
    {
        $this->addStatusLabelToPropalHookObject($object);
        return 0;
    }

    /**
     * Overloading the printFieldListValue function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function printFieldListValue($parameters, &$object, &$action, $hookmanager)
    {
        $this->addStatusLabelToPropalHookObject($object);
        return 0;
    }

    /**
     * Overloading the showLinkedObjectBlock function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function showLinkedObjectBlock($parameters, &$object, &$action, $hookmanager)
    {
        $this->addStatusLabelToPayload($object);
        foreach ($object->linkedObjects['propal'] as &$payload) {
            $this->addStatusLabelToPayload($payload);
        }
        return 0;
    }

    /**
     * Function to add missing data on Propal Instance
     * @param CommonObject &$object
     * @return 0
     */
    private function addStatusLabelToPropalHookObject(&$object)
    {
        global $objectstatic;
        $objectToCheck = array(&$object, $objectstatic);
        foreach ($objectToCheck as &$payload) {
            $this->addStatusLabelToPayload($payload);
        }
        return 0;
    }

    /**
     * Function to add missing data on Propal Instance
     * @param CommonObject &$payload
     * @return 0
     */
    private function addStatusLabelToPayload(&$payload)
    {
        if ($payload && $payload->element == 'propal') {
            $this->loadCachedData();
            if (empty($payload->labelStatus)
            || empty($payload->labelStatusShort)
            || empty($payload->labelStatus[5])
            || empty($payload->labelStatusShort[5])
            ) {
                $payload->labelStatus = self::$cache_propal_status_label['long'];
                $payload->labelStatusShort = self::$cache_propal_status_label['short'];
            }
            $payload->statusType[5] = 'status7';
        }
    }

    /**
     * Function to load translated key cache
     */
    private function loadCachedData()
    {
        if (empty(self::$cache_propal_status_label)) {
            global $langs;
            $langs->load("propal");
            $langs->load("propalapprovement@propalapprovement");
            $result = array();
            $result["long"][0] = $langs->transnoentitiesnoconv("PropalStatusDraft");
            $result["long"][1] = $langs->transnoentitiesnoconv("PropalStatusValidated");
            $result["long"][2] = $langs->transnoentitiesnoconv("PropalStatusSigned");
            $result["long"][3] = $langs->transnoentitiesnoconv("PropalStatusNotSigned");
            $result["long"][4] = $langs->transnoentitiesnoconv("PropalStatusBilled");
            $result["short"][0] = $langs->transnoentitiesnoconv("PropalStatusDraftShort");
            $result["short"][1] = $langs->transnoentitiesnoconv("PropalStatusValidatedShort");
            $result["short"][2] = $langs->transnoentitiesnoconv("PropalStatusSignedShort");
            $result["short"][3] = $langs->transnoentitiesnoconv("PropalStatusNotSignedShort");
            $result["short"][4] = $langs->transnoentitiesnoconv("PropalStatusBilledShort");
            $result["long"][5] = $langs->transnoentitiesnoconv("PropalApprovementWaitingStatus");
            $result["short"][5] = $langs->transnoentitiesnoconv("PropalApprovementWaitingStatusShort");
            self::$cache_propal_status_label = $result;
        }
    }
}
