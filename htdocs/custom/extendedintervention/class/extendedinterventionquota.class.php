<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *	\file       extendedintervention/core/class/extendedinterventionquota.class.php
 *  \ingroup    extendedintervention
 *	\brief      File of class to manage quota of the intervention
 */

dol_include_once('/extendedintervention/vendor/autoload.php');
use Carbon\Carbon;

/**
 *	Class to manage quota of the intervention
 */
class ExtendedInterventionQuota
{
    /**
     * @var DoliDB      Database handler.
     */
    public $db;
    /**
     * @var string      Error
     */
    public $error = '';
    /**
     * @var array       Errors
     */
    public $errors = array();

    /**
     * @var Form        Form handler
     */
    public $form = null;

    /**
     * Constants of the extra fields code
     */
    const EF_EFFECTIVE_DATE                     = 'startdate';
    const EF_CONTRACT_DURATION_MONTHS           = 'duration';
    const EF_COUNT_PERIOD_SIZE                  = 'ei_count_period_size';

    /**
     *  Constructor
     *
     * @param   DoliDB      $db     Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Load form instance
     */
    public function load_form()
    {
        if (!isset($this->form)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            $this->form = new Form($this->db);
        }
    }

    /**
     *  Get list of count by type intervention defined into a contract
     *
     * @param   int             $contract_id      Contract ID
     * @return  int|array                         <0 if KO, List of count by type of intervention if OK
     */
    public function getCountInterventionOfContract($contract_id)
    {
        $result = array();

        $sql = "SELECT fk_c_intervention_type, count FROM ".MAIN_DB_PREFIX."extendedintervention_contract_count_type".
            " WHERE fk_contrat = " . $contract_id;

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $result[$obj->fk_c_intervention_type] = isset($obj->count) ? $obj->count : 0;
            }
        } else {
            $this->errors[] = $this->db->lasterror();
            return -1;
        }

        return $result;
    }

    /**
     *  Set list of count by type of intervention into a contract
     *
     * @param   int         $contract_id        Contract ID
     * @param   array       $counts             List of count by type of intervention
     * @return  int                             <0 if KO, >0 if OK
     */
    public function setCountInterventionOfContract($contract_id, $counts)
    {
        foreach ($counts as $id => $count) {
            $value = $count > 0 ? $count : 'NULL';

            $sql = "INSERT INTO " . MAIN_DB_PREFIX . "extendedintervention_contract_count_type (fk_contrat, fk_c_intervention_type, count)" .
                " VALUES (" . $contract_id . ", " . $id . ", " . $value . ")" .
                " ON DUPLICATE KEY UPDATE count = " . $value;

            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->errors[] = $this->db->lasterror();
                return -1;
            }
        }

        return 1;
    }

    /**
     *  Get list of count (free, in progress, forced) by type intervention for a company and a contract
     *
     * @param   Contrat         $contract       Contract handler
     * @param   array           $soc_ids        List of company ID
     * @return  int|array                       <0 if KO, List of count (free, in progress, forced) by type intervention for a company and a contract if OK
     */
    public function getCountInterventionInfoOfCompany(&$contract, $soc_ids)
    {
        $periods = $this->getPeriodOfContract($contract);
        $contract_counts = $this->getCountInterventionOfContract($contract->id);
        $result = array('periods' => $periods, 'types' => array());

        $idx = 1;
        foreach ($periods as $period) {
            foreach ($contract_counts as $fk_c_intervention_type => $max) {
                $result['types'][$fk_c_intervention_type][$idx] = array('current' => 0, 'max' => $max, 'free' => 0, 'forced' => 0);
            }

            // Get forced intervention
            $sql = "SELECT fief.ei_type AS fk_c_intervention_type, COUNT(*) AS nb_current, SUM(IF(ac.id IS NULL, 0, 1)) AS nb_forced".
                " FROM " . MAIN_DB_PREFIX . "fichinter AS fi" .
                " LEFT JOIN " . MAIN_DB_PREFIX . "fichinter_extrafields AS fief ON fief.fk_object = fi.rowid" .
                " LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm AS ac ON ac.elementtype = 'fichinter' AND ac.fk_element = fi.rowid AND ac.code = 'AC_EI_FCI'" .
                " WHERE fi.entity IN (" . getEntity('intervention') . ")" .
                " AND fi.fk_soc IN (" . implode(',', $soc_ids) . ")" .
                " AND '" . $this->db->idate($period['begin']) . "' <= fi.datec" .
                " AND fi.datec <= '" . $this->db->idate($period['end']) . "'".
                " GROUP BY fief.ei_type";

            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $result['types'][$obj->fk_c_intervention_type][$idx]['current'] = $obj->nb_current;
                    $result['types'][$obj->fk_c_intervention_type][$idx]['free'] = max(0, $obj->nb_current - $contract_counts[$obj->fk_c_intervention_type] - $obj->nb_forced);
                    $result['types'][$obj->fk_c_intervention_type][$idx]['forced'] = $obj->nb_forced;
                }
            } else {
                $this->errors[] = $this->db->lasterror();
                return -1;
            }

            $idx++;
        }

        return $result;
    }

    /**
     *  Get period list for a contract
     *
     * @param   Contrat         $contract       Contract handler
     * @return  array                           <0 if KO, Period list if OK
     */
    public function getPeriodOfContract(&$contract)
    {
        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        $periods = array();
        $effective_date_t = $contract->array_options['options_' . self::EF_EFFECTIVE_DATE];
        $duration = $contract->array_options['options_' . self::EF_CONTRACT_DURATION_MONTHS];
        $period_size = $contract->array_options['options_' . self::EF_COUNT_PERIOD_SIZE];

        $effective_date = null;
        if (!empty($effective_date_t)) {
            $effective_date = Carbon::createFromFormat('Y-m-d', $effective_date_t);
        }

        if (isset($effective_date) && $duration > 0 && $period_size > 0) {
            $begin_date = $effective_date;
            $end_date = $begin_date->copy()->addMonths($duration)->subDay();

            $idx = 1;
            while ($begin_date < $end_date) {
                $periods[$idx] = array('begin' => $begin_date->timestamp, 'end' => $begin_date->addMonths($period_size)->copy()->subDay()->timestamp);
                $idx++;
            }
            $periods[$idx-1]['end'] = $end_date->timestamp;
        }

        return $periods;
    }

    /**
     *  Show HTML bloc list of count by type of intervention for the contracts
     *
     * @param   Contrat[]   $contract_list              List of contract handler
     * @param   int         $fk_c_intervention_type     Show only this intervention type (if defined)
     * @return  string                                  HTML bloc list of count by type of intervention for the contracts
     */
    public function showBlockCountInterventionOfContract($contract_list, $fk_c_intervention_type=0)
    {
        global $conf, $langs;
        $out = '';

        if (is_array($contract_list) && count($contract_list) > 0) {
            $langs->load('extendedintervention@extendedintervention');
            $nb_contract = count($contract_list);

            // Get block of table
            $block = array();
            foreach ($contract_list as $contract) {
                $table = $this->showTableCountInterventionOfContract($contract, array($contract->socid), $fk_c_intervention_type);

                if (!empty($table)) {
                    if ($nb_contract > 1) {
                        $table = load_fiche_titre($langs->trans("ExtendedInterventionQuotaForContract", $contract->ref), '', '') . $table;
                    }
                    $block[] = $table;
                }
            }

            if (count($block) > 0) {
                $out .= '<div id="ei_count_intervention_block"><br>';
                $out .= load_fiche_titre($langs->trans("ExtendedInterventionQuotaBlockTitle"), '', '', 0, 'ei_count_intervention_block_title');
                $out .= '<div id="ei_count_intervention_block_content"' . (empty($conf->global->EXTENDEDINTERVENTION_QUOTA_SHOW_BLOCK) ? ' style="display: none;"' : '') . '>';
                $out .= implode('', $block);
                $out .= '</div></div>';
                $out .= <<<SCRIPT
<script type="text/javascript" language="javascript">
    $(document).ready(function () {
        var ei_count_block_title = $("#ei_count_intervention_block_title");
        var ei_count_block_content = $("#ei_count_intervention_block_content");

        ei_count_block_title.on('click', function() {
            ei_count_block_content.toggle();
        });
    });
</script>
SCRIPT;
            }
        }

        return $out;
    }

    /**
     *  Show HTML table list of count by type of intervention for a contract of a company
     *
     * @param   Contrat     $contract                   Contract handler
     * @param   array       $soc_ids                    List of company ID
     * @param   int         $fk_c_intervention_type     Show only this intervention type (if defined)
     * @return  string                                  HTML table list of count by type of intervention for a contract of a company
     */
    public function showTableCountInterventionOfContract(&$contract, $soc_ids, $fk_c_intervention_type=0)
    {
        global $conf, $langs;

        $this->load_form();
        $langs->load('extendedintervention@extendedintervention');
        $out = '';

        if (empty($conf->global->EXTENDEDINTERVENTION_QUOTA_SHOW_ONLY_TYPE_OF_INTERVENTION)) $fk_c_intervention_type = 0;

        // Get intervention types counted
        $filter = array('count' => 1);
        if ($fk_c_intervention_type > 0) $filter['rowid'] = array($fk_c_intervention_type);
        dol_include_once('/advancedictionaries/class/dictionary.class.php');
        $inter_type_dictionary = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventiontype');
        $inter_type_dictionary->fetch_lines(1, $filter, array('label' => 'ASC'));

        // Get count of current stats for each intervention type for a company and a contract
        $company_counts = $this->getCountInterventionInfoOfCompany($contract, $soc_ids);

        if (count($inter_type_dictionary->lines) > 0 && count($company_counts['periods']) > 0) {
            $out .= '<table class="border" width="100%">';
            $out .= '<tr><td class="titlefield" align="right">' . $langs->trans('ExtendedInterventionPeriod') . ' : </td>';
            $idx = 1;
            foreach ($company_counts['periods'] as $period) {
                $out .= '<td align="center">' . $idx++ . '&nbsp;' . $this->form->textwithpicto('', $langs->trans('DateFromTo', dol_print_date($period['begin'], 'day'), dol_print_date($period['end'], 'day')), 1, 'help') . '</td>';
            }
            $out .= '</tr>';

            foreach ($inter_type_dictionary->lines as $line) {
                $out .= '<tr><td class="titlefield">' . $line->fields['label'] . '</td>';
                foreach ($company_counts['types'][$line->id] as $period) {
                    // Set label
                    $label = $period['current'] . ' / ' . $period['max'];
                    if ($period['current'] == $period['max'] || $period['current'] == $period['max'] + $period['free']) $label = '<span style="color: green;">' . $label . '</span>';
                    elseif ($period['current'] > $period['max']) $label = '<span style="color: red;">' . $label . '</span>';

                    // Set more info
                    $more_info = '';
                    $toprint = array();
                    if (!empty($period['free'])) $toprint[] = $langs->trans('ExtendedInterventionFree') . ' : ' . $period['free'];
                    if (!empty($period['forced'])) $toprint[] = $langs->trans('ExtendedInterventionForced') . ' : ' . $period['forced'];
                    if (!empty($toprint)) $more_info = '&nbsp;' . $this->form->textwithpicto('', implode('<br>', $toprint), 1, 'warning');

                    $out .= '<td align="center">' . $label . $more_info . '</td>';
                }
                $out .= '</tr>';
            }

            $out .= '</table>';
        }

        return $out;
    }

    /**
     *  Is the intervention created out of quota
     *
     * @param   Contrat     $contract                   Contract handler
     * @param   array       $soc_ids                    List of company ID
     * @param   int         $fk_c_intervention_type     Show only this intervention type (if defined)
     * @return  boolean
     */
    public function isCreatedOutOfQuota(&$contract, $soc_ids, $fk_c_intervention_type)
    {
        $now = dol_now();

        if (!($fk_c_intervention_type > 0)) return false;

        $periods = $this->getPeriodOfContract($contract);
        $contract_counts = $this->getCountInterventionOfContract($contract->id);

        foreach ($periods as $period) {
            if ($now < $period['begin'] || $period['end'] < $now) continue;

            // Get forced intervention
            $sql = "SELECT COUNT(*) AS nb_current".
                " FROM " . MAIN_DB_PREFIX . "fichinter AS fi" .
                " LEFT JOIN " . MAIN_DB_PREFIX . "fichinter_extrafields AS fief ON fief.fk_object = fi.rowid" .
                " WHERE fi.entity IN (" . getEntity('intervention') . ")" .
                " AND fi.fk_soc IN (" . implode(',', $soc_ids) . ")" .
                " AND fief.ei_type = " . $fk_c_intervention_type .
                " AND '" . $this->db->idate($period['begin']) . "' <= fi.datec" .
                " AND fi.datec <= '" . $this->db->idate($period['end']) . "'".
                " GROUP BY fief.ei_type";

            $resql = $this->db->query($sql);
            if ($resql) {
                if ($obj = $this->db->fetch_object($resql)) {
                    return $contract_counts[$fk_c_intervention_type] <= $obj->nb_current;
                }
            }
        }

        return false;
    }

    /**
     *  Add action : Forced created out of quota
     *
     * @param   Fichinter   $object     Intervention handler
     * @param   User        $user       User that modifies
     * @param   string      $reason     Reason of the forcing
     * @return  int                     <0 if KO, >0 if OK
     */
    function addActionForcedCreatedOutOfQuota($object, $user, $reason)
    {
        global $langs;

        $langs->load('extendedintervention@extendedintervention');
        $title = $langs->trans('ExtendedInterventionForcedCreatedOutOfQuotaActionLabel');
        $msg = $langs->trans('ExtendedInterventionForcedCreatedOutOfQuotaActionReason') . ' :<br>'.$reason;
        $msg .= '<br><br>' . $langs->transnoentities("Author") . ': ' . $user->login;

        $now = dol_now();
        // Insertion action
        require_once(DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php');
        $actioncomm = new ActionComm($this->db);
        $actioncomm->type_code = 'AC_EI_FCI';
        $actioncomm->label = $title;
        $actioncomm->note = $msg;
        $actioncomm->datep = $now;
        $actioncomm->datef = $now;
        $actioncomm->durationp = 0;
        $actioncomm->punctual = 1;
        $actioncomm->percentage = -1; // Not applicable
        $actioncomm->contactid = 0;
        $actioncomm->socid = $object->socid;
        $actioncomm->author = $user; // User saving action
        // $actioncomm->usertodo = $user; // User affected to action
        $actioncomm->userdone = $user; // User doing action
        $actioncomm->fk_element = $object->id;
        $actioncomm->elementtype = $object->element;
        $actioncomm->userownerid = $user->id;

        $result = $actioncomm->create($user); // User qui saisit l'action
        if ($result < 0) {
            $this->error = $actioncomm->error;
            $this->errors = $actioncomm->errors;
        }

        return $result;
    }
}
