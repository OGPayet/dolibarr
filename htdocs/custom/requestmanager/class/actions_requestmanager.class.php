<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/requestmanager/class/actions_requestmanager.class.php
 * \ingroup requestmanager
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsRequestManager
 */
class ActionsRequestManager
{
    /**
     * @var DoliDB Database handler.
     */
    public $db;
    /**
     * @var string Error
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
     * @param        DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Overloading the addSearchEntry function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function addSearchEntry($parameters, &$object, &$action, $hookmanager)
    {
        global $langs;
        $langs->load('requestmanager@requestmanager');

        $search_boxvalue = $parameters['search_boxvalue'];
        //$arrayresult = $parameters['arrayresult'];

        $arrayresult['searchintorequestmanager']=array(
            'position'=>16,
            'shortcut'=>'R',
            'img'=>'object_requestmanager@requestmanager',
            'label'=>$langs->trans("RequestManagerSearchIntoRequests", $search_boxvalue),
            'text'=>img_picto('','object_requestmanager@requestmanager').' '.$langs->trans("RequestManagerSearchIntoRequests", $search_boxvalue),
            'url'=>dol_buildpath('/requestmanager/list.php', 1).($search_boxvalue?'?sall='.urlencode($search_boxvalue):'')
        );

        $this->results = $arrayresult;

        return 0; // or return 1 to replace standard code
    }

    /**
	 * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
	 *
	 * @param   array() $parameters Hook metadatas (context, etc...)
	 * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param   string &$action Current action (if set). Generally create or edit or null
	 * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $user, $langs;

        $contexts = explode(':', $parameters['context']);

        if (in_array('thirdpartycard', $contexts) ||
            in_array('propalcard', $contexts) ||
            in_array('ordercard', $contexts) ||
            in_array('invoicecard', $contexts) ||
            in_array('interventioncard', $contexts) ||
            in_array('contractcard', $contexts)
        ) {
            $langs->load('requestmanager@requestmanager');

            $socid = $object->element == 'societe' ? $object->id : $object->socid;
            $origin = $object->element != 'societe' ? '&origin=' . urlencode($object->element) . '&originid=' . $object->id : '';

            if ($user->rights->requestmanager->creer)
                print '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/requestmanager/card.php', 2) . '?action=create&socid=' . $socid . $origin . '">' . $langs->trans("RequestManagerAddRequest") . '</a></div>';
            else
                print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . $langs->trans("NotEnoughPermissions") . '">' . $langs->trans("RequestManagerAddRequest") . '</a></div>';
        }

        return 0;
    }

    /**
     * Overloading the updateSession function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function updateSession($parameters, &$object, &$action, $hookmanager)
    {
        return 0;
    }
}
