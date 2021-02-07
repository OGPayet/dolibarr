<?php
/* Copyright (C) 2021 Alexis LAURIER <contact@alexislaurier.fr>
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
     * @var string Action name to ask for approvement
     */
    const ASK_FOR_APPROVE_ACTION_NAME = 'askForApprove';

    /**
     * @var string Confirm Action name to ask for approvement
     */
    const CONFIRM_ASK_FOR_APPROVE_ACTION_NAME = 'confirm_askForApprove';

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
     * Overloading the formConfirm function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    public function formConfirm($parameters, &$object, &$action, $hookmanager)
    {
        $contexts = explode(':', $parameters['context']);
        if (in_array('propalcard', $contexts) && $action == self::ASK_FOR_APPROVE_ACTION_NAME) {
            global $langs;
            $form = new Form($this->db);
            print $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('PropalApprovementAwaitingProp'), $langs->trans('PropalApprovementConfirmAwaitingProp'), self::CONFIRM_ASK_FOR_APPROVE_ACTION_NAME, '', 0, 1);
            return 1;
        }
    }

    /**
     * Overloading the addMoreActionsButtons function : replacing the parent's function with the one below
     *
     * @param   array           $parameters     Hook metadatas (context, etc...)
     * @param   CommonObject    $object         The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string          $action         Current action (if set). Generally create or edit or null
     * @param   HookManager     $hookmanager    Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */

    public function addMoreActionsButtons($parameters = array(), &$object, &$action = '', $hookmanager)
    {
        global $conf, $user, $langs;
        $contexts = explode(':', $parameters['context']);
        if (in_array('propalcard', $contexts)) {
            if ($object->statut == 0 && !empty($user->rights->propalapprovement->approve->automatically) &&
            ($object->statut == Propal::STATUS_DRAFT && $object->total_ttc >= 0 && count($object->lines) > 0)
                    || ($object->statut == Propal::STATUS_DRAFT && !empty($conf->global->PROPAL_ENABLE_NEGATIVE) && count($object->lines) > 0)) {
                print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=' . self::ASK_FOR_APPROVE_ACTION_NAME . '">' . $langs->trans('PropalApprovementAwaitButton') . '</a></div>';
                return 1;
            } elseif ($object->statut == 5) {
                if (!empty($user->rights->propal->creer)) {
                    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=modif">'.$langs->trans('Modify').'</a>';
                } else {
                    print '<a class="butActionRefused"classfortooltip" href="#">'.$langs->trans('Modify').'</a>';
                }
                if (!empty($user->rights->propalapprovement->approve->automatically)) {
                    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validate">'.$langs->trans('Validate').'</a>';
                } else {
                    print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans('Validate').'</a>';
                }
            }
        }
        return 0;
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
        global $user, $conf;
        $this->addStatusLabelToPropalHookObject($object);
        $contexts = explode(':', $parameters['context']);
        if (in_array('propalcard', $contexts)) {
            $confirm = GETPOST('confirm');
            if ($object->statut == 0
                && $action == self::CONFIRM_ASK_FOR_APPROVE_ACTION_NAME
                && $confirm == 'yes'
                &&
                ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->propal->creer))
                   || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->propal->propal_advance->validate)))
            ) {
                global $langs;
                if ($this->setPropalInAwaitingApprovement($object, $user) >= 0) {
                    setEventMessages($langs->trans("PropalApprovementSuccessfullyAsked"), array());
                } else {
                    setEventMessages($langs->trans("PropalApprovementErrorWhileAskingForApprovement"), $object->errors, 'errors');
                }
            } elseif ($object->statut == 5) {
                if (($action == 'modif' && empty($user->rights->propal->creer)) || ($action == 'confirm_validate' && empty($user->rights->propalapprovement->approve->automatically))) {
                    return -1;
                } elseif ($action == 'confirm_validate') {
                    $object->db->begin();
                    $result = $object->setDraft($user, true);
                    if ($result > 0) {
                        $result = $object->valid($user);
                    }
                    if ($result >= 0) {
                        $object->db->commit();
                        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                            $outputlangs = $langs;
                            $newlang = '';
                            if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
                                $newlang = GETPOST('lang_id', 'aZ09');
                            }
                            if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
                                $newlang = $object->thirdparty->default_lang;
                            }
                            if (!empty($newlang)) {
                                $outputlangs = new Translate("", $conf);
                                $outputlangs->setDefaultLang($newlang);
                            }
                            $model = $object->model_pdf;
                            $ret = $object->fetch($id); // Reload to get new records
                            if ($ret > 0) {
                                $object->fetch_thirdparty();
                            }

                            $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
                        }
                    } else {
                        $langs->load("errors");
                        if (count($object->errors) > 0) {
                            setEventMessages($object->error, $object->errors, 'errors');
                        } else {
                            setEventMessages($langs->trans($object->error), null, 'errors');
                        }
                        $object->db->rollback();
                    }
                    return 1;
                }
            }
        }
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

    /**
     * Function to set a propal instance into awaiting approvement status
     * @param Propal $object
     * @param User $user
     * @param Boolean $notrigger
     * @return  int < 0 on error, 0 if nothing done, 1 if success
     */

    private function setPropalInAwaitingApprovement($object, $user, $notrigger = false)
    {
        global $conf,$langs;

        $error=0;

        // Protection
        if ($object->statut == 5) {
            dol_syslog(get_class($object)."::await action abandonned: already awaited", LOG_WARNING);
            return 0;
        }

        if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->propal->creer))
        || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->propal->propal_advance->validate)))) {
            $object->error='ErrorPermissionDenied';
            dol_syslog(get_class($object)."::valid ".$object->error, LOG_ERR);
            return -1;
        }

        $object->db->begin();

        $sql = "UPDATE ".MAIN_DB_PREFIX."propal";
        $sql.= " SET fk_statut = 5";
        $sql.= " WHERE rowid = ".$object->id." AND fk_statut = " . $object::STATUS_DRAFT;

        dol_syslog(get_class($object)."::await", LOG_DEBUG);
        $resql=$object->db->query($sql);
        if (! $resql) {
            dol_print_error($object->db);
            $error++;
        }

        // Trigger calls
        if (! $error && ! $notrigger) {
            // Call trigger
             $result=$object->call_trigger('PROPAL_AWAITING', $user);
            if ($result < 0) {
                $error++;
            }
            // End call triggers
        }

        if (! $error) {
            $object->brouillon=0;
            $object->statut = 5;

            $object->db->commit();
            return 1;
        } else {
            $object->db->rollback();
            return -1;
        }
    }
}
