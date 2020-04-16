<?php
/*  Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
 * Copyright (C) 2018      Alexis LAURIER             <alexis@alexislaurier.fr>
 * Copyright (C) 2018      Synergies-Tech             <infra@synergies-france.fr>
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

if (!class_exists('ComposerAutoloaderInit7e289d877b5289c34886bc66da322d02', false)) {
    dol_include_once('/extendedintervention/vendor/autoload.php');
}
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
     * @var array       List of planning times
     */
    public static $planning_times;

    /**
     * Constants of the extra fields code
     */
    const EF_EFFECTIVE_DATE                     = 'startdate';
    const EF_CONTRACT_DURATION_MONTHS           = 'duration';
    const EF_COUNT_PERIOD_SIZE                  = 'ei_count_period_size';
    const EF_TERMINATION_DATE                   = 'realdate';
    const EF_TACIT_RENEWAL                      = 'tacitagreement';

    /**
     *  Constructor
     *
     * @param   DoliDB      $db     Database handler
     */
    public function __construct($db)
    {
        global $langs;

        $langs->load('extendedintervention@extendedintervention');

        $this->db = $db;

        self::$planning_times = array(
            1 => $langs->trans('January'),
            2 => $langs->trans('February'),
            3 => $langs->trans('March'),
            4 => $langs->trans('April'),
            5 => $langs->trans('May'),
            6 => $langs->trans('June'),
            7 => $langs->trans('July'),
            8 => $langs->trans('August'),
            9 => $langs->trans('September'),
            10 => $langs->trans('October'),
            11 => $langs->trans('November'),
            12 => $langs->trans('December'),
        );
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
     *  Get list of quota info by type intervention defined into a contract
     *
     * @param   int             $contract_id      Contract ID
     * @return  int|array                         <0 if KO, List of count by type of intervention if OK
     */
    public function getInfoInterventionOfContract($contract_id)
    {
        $result = array();

        $sql = "SELECT fk_c_intervention_type, `count`, planning_times FROM ".MAIN_DB_PREFIX."extendedintervention_contract_type_info".
            " WHERE fk_contrat = " . $contract_id;

        $resql = $this->db->query($sql);
        if ($resql) {
            while ($obj = $this->db->fetch_object($resql)) {
                $result[$obj->fk_c_intervention_type] = array(
                    'count' => isset($obj->count) ? $obj->count : 0,
                    'planning_times' => !empty($obj->planning_times) ? json_decode($obj->planning_times, true) : array()
                );
            }
        } else {
            $this->errors[] = $this->db->lasterror();
            return -1;
        }

        return $result;
    }

    /**
     *  Set list of quota info by type of intervention into a contract
     *
     * @param   int         $contract_id        Contract ID
     * @param   array       $info               List of quota info by type of intervention
     * @return  int                             <0 if KO, >0 if OK
     */
    public function setInfoInterventionOfContract($contract_id, $info)
    {
        foreach ($info as $id => $values) {
            if (isset($values['count']) || isset($values['planning_times'])) {
                $count = $values['count'] > 0 ? $values['count'] : 'NULL';
                $planning_times = !empty($values['planning_times']) ? json_encode($values['planning_times']) : 'NULL';

                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "extendedintervention_contract_type_info (fk_contrat, fk_c_intervention_type" .
                    (isset($values['count']) ? ", `count`" : "") . (isset($values['planning_times']) ? ", planning_times" : "") . ")" .
                    " VALUES (" . $contract_id . ", " . $id . (isset($values['count']) ? ", " . $count : "") .
                    (isset($values['planning_times']) ? ", '" . $this->db->escape($planning_times) . "'" : "") . ")" .
                    " ON DUPLICATE KEY UPDATE ". (isset($values['count']) ? "`count` = " . $count : "") .
                    (isset($values['count']) && isset($values['planning_times']) ? ", " : "") .
                    (isset($values['planning_times']) ? "planning_times = '" . $this->db->escape($planning_times) . "'" : "");

                $resql = $this->db->query($sql);
                if (!$resql) {
                    $this->errors[] = $this->db->lasterror();
                    return -1;
                }
            }
        }

        return 1;
    }

    /**
     *  Del all count of each type of intervention into a contract
     *
     * @param   int         $contract_id        Contract ID
     * @return  void
     */
    public function delAllCountInterventionOfContract($contract_id)
    {
        $this->db->query("DELETE FROM " . MAIN_DB_PREFIX . "extendedintervention_contract_type_info WHERE fk_contrat = " . $contract_id);
    }

    /**
     *  Get list of count (free, in progress, forced) by type intervention for a company and a contract
     *
     * @param   Contrat         $contract       Contract handler
     * @return  int|array                       <0 if KO, List of count (free, in progress, forced) by type intervention for a company and a contract if OK
     */
    public function getCountInterventionInfoOfCompany(&$contract)
    {
        global $conf;

        $periods = $this->getPeriodOfContract($contract);
        $info = $this->getInfoInterventionOfContract($contract->id);
        $result = array('periods' => $periods, 'types' => array());

        $idx = 1;
        foreach ($periods as $period) {
            foreach ($info as $fk_c_intervention_type => $values) {
                $result['types'][$fk_c_intervention_type][$idx] = array('current' => 0, 'max' => $values['count'], 'free' => 0, 'forced' => 0);
            }

            // Get forced intervention
            $sql = "SELECT fief.ei_type AS fk_c_intervention_type, COUNT(*) AS nb_current, SUM(IF(ac.id IS NULL, 0, 1)) AS nb_forced".
                " FROM " . MAIN_DB_PREFIX . "fichinter AS fi" .
                " LEFT JOIN " . MAIN_DB_PREFIX . "fichinter_extrafields AS fief ON fief.fk_object = fi.rowid" .
                " LEFT JOIN " . MAIN_DB_PREFIX . "actioncomm AS ac ON ac.elementtype = 'fichinter' AND ac.fk_element = fi.rowid AND ac.code = 'AC_EI_FCI'" .
                " WHERE fi.entity IN (" . getEntity('intervention') . ")";
            if ($conf->companyrelationships->enabled) {
                $contract->fetch_optionals();
                $sql .= " AND fief.companyrelationships_fk_soc_benefactor = " . $contract->array_options['options_companyrelationships_fk_soc_benefactor'];
            }
            $sql .= " AND fi.fk_soc = " . $contract->socid .
                " AND fi.fk_contrat = " . $contract->id .
                " AND '" . $this->db->idate($period['begin']) . "' <= fi.dateo" .
                " AND fi.dateo <= '" . $this->db->idate($period['end']) . "'".
                " GROUP BY fief.ei_type";

            $resql = $this->db->query($sql);
            if ($resql) {
                while ($obj = $this->db->fetch_object($resql)) {
                    $contract_counts = isset($info[$obj->fk_c_intervention_type]['count']) ? $info[$obj->fk_c_intervention_type]['count'] : 0;

                    $result['types'][$obj->fk_c_intervention_type][$idx]['current'] = $obj->nb_current;
                    $result['types'][$obj->fk_c_intervention_type][$idx]['free'] = max(0, $obj->nb_current - $contract_counts - $obj->nb_forced);
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
        global $conf;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        $periods = array();
        $effective_date_t = $contract->array_options['options_' . self::EF_EFFECTIVE_DATE];
        $duration = $contract->array_options['options_' . self::EF_CONTRACT_DURATION_MONTHS];
        $period_size = $contract->array_options['options_' . self::EF_COUNT_PERIOD_SIZE];
        $termination_date_t = $contract->array_options['options_' . self::EF_TERMINATION_DATE];
        $tacit_renewal = $contract->array_options['options_' . self::EF_TACIT_RENEWAL];

        $effective_date = null;
        if (!empty($effective_date_t)) {
            $effective_date = Carbon::createFromFormat('Y-m-d', $effective_date_t);
        }

        $termination_date = null;
        if (!empty($termination_date_t)) {
            $termination_date = Carbon::createFromFormat('Y-m-d', $termination_date_t);
        }

        $contract_closed_date = null;
        if (is_array($contract->lines)) {
            foreach ($contract->lines as $line) {
                if (!empty($line->date_cloture) && (!isset($contract_closed_date) || $line->date_cloture < $contract_closed_date->timestamp)) $contract_closed_date = Carbon::createFromTimestamp($line->date_cloture);
                elseif (!empty($line->date_fin_validite) && (!isset($contract_closed_date) || $line->date_fin_validite < $contract_closed_date->timestamp)) $contract_closed_date = Carbon::createFromTimestamp($line->date_fin_validite);
            }
        }

        if (isset($effective_date) && $duration > 0 && $period_size > 0) {
            $now = Carbon::now();
            $begin_date = $effective_date;
            $end_date = null;
            $last_end_date = null;
            if (empty($tacit_renewal)) $end_date = $begin_date->copy()->addMonths($duration)->subDay();
            if (isset($termination_date) && (!isset($end_date) || $termination_date < $end_date)) $end_date = $termination_date;
            if (isset($contract_closed_date) && (!isset($end_date) || $contract_closed_date < $end_date)) $end_date = $contract_closed_date;
            $begin_date->setTime(0,0);
            if (isset($end_date)) {
                $end_date->setTime(0,0);
                $last_end_date = $end_date->copy()->subDay();
            }

            if ($begin_date <= $now) {
                $idx = 1;
                $nb_period = 0;
                $stop_period = false;
                do {
                    $b_date = $begin_date->timestamp;
                    $e_date = $begin_date->addMonths($period_size)->copy()->subDay()->timestamp;
                    $last_period = isset($end_date) && (($b_date <= $end_date->timestamp && $end_date->timestamp <= $e_date) || $e_date == $last_end_date->timestamp);
                    $in_period = $b_date <= $now->timestamp && $now->timestamp <= ($last_period ? $end_date->timestamp : $e_date);
                    $periods[$idx] = array('begin' => $b_date, 'end' => $last_period ? $end_date->timestamp : $e_date, 'in_period' => $in_period, 'last_period' => $last_period);
                    $idx++;
                    $nb_period++;
                    if ($in_period) $stop_period = true;
                } while ((!$stop_period || $nb_period < $conf->global->EXTENDEDINTERVENTION_QUOTA_SHOW_X_PERIOD) && !$last_period);
            }
        }

        if (count($periods) > $conf->global->EXTENDEDINTERVENTION_QUOTA_SHOW_X_PERIOD) {
            $periods = array_slice($periods, -$conf->global->EXTENDEDINTERVENTION_QUOTA_SHOW_X_PERIOD, null, true);
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

        if (is_array($contract_list) && count($contract_list) > 0 && !empty($conf->global->EXTENDEDINTERVENTION_QUOTA_ACTIVATE)) {
            $langs->load('extendedintervention@extendedintervention');
            $nb_contract = count($contract_list);

            // Get block of table
            $block = array();
            foreach ($contract_list as $contract) {
                $table = $this->showTableCountInterventionOfContract($contract, $fk_c_intervention_type);

                if (!empty($table)) {
                    if ($nb_contract > 0) { //modif par Alexis Laurier - on affiche toujours le titre de chaque sous tableau - on y indique également la formule (la valeur de l'extrafield donc)
						$extrafields_contract = new ExtraFields($this->db);
						$extralabels_contract = $extrafields_contract->fetch_name_optionals_label($contract->element);

						$contract->fetch_optionals();

						$title = $contract->ref . " - " . $extrafields_contract->showOutputField('formule', $contract->array_options['options_formule']);

                        $table = load_fiche_titre($langs->trans("ExtendedInterventionQuotaForContract", $title), '', '') . $table;
                    }
                    $block[] = $table;
                }
            }

            if (count($block) > 0) {
                $out .= '<div id="ei_count_intervention_block"><br>';
                $out .= load_fiche_titre('<span class="ei_count_intervention_block_title_label" style="font-weight: bolder !important; font-size: medium !important;">' . $langs->trans("ExtendedInterventionQuotaBlockTitle") . '&nbsp;<img class="ei_count_intervention_block_title_icon" src=""></img></span>', '', '', 0, 'ei_count_intervention_block_title');
                $out .= '<div id="ei_count_intervention_block_content"' . (empty($conf->global->EXTENDEDINTERVENTION_QUOTA_SHOW_BLOCK) ? ' style="display: none;"' : '') . '>';
                $out .= implode('', $block);
                $out .= '</div></div>';

                $show_label = json_encode($langs->trans('Show'));
                $hide_label = json_encode($langs->trans('Hide'));
                $arrow_up = json_encode(img_picto('', 'sort_asc', '', false, 1));
                $arrow_down = json_encode(img_picto('', 'sort_desc', '', false, 1));
                $out .= <<<SCRIPT
<script type="text/javascript" language="javascript">
    $(document).ready(function () {
        var ei_count_block_title = $("#ei_count_intervention_block_title");
        var ei_count_block_title_div = $('#ei_count_intervention_block_title div.titre');
        var ei_count_block_title_icon = $(".ei_count_intervention_block_title_icon");
        var ei_count_block_content = $("#ei_count_intervention_block_content");

        ei_count_block_title_div.css('cursor', 'pointer');
        ei_update_title_icon();
        ei_count_block_title.on('click', function() {
            ei_count_block_content.toggle();
            ei_update_title_icon();
        });

        function ei_update_title_icon() {
            if (ei_count_block_content.is(':visible')) {
                ei_count_block_title_div.attr('title', $hide_label)
                ei_count_block_title_icon.attr('src', $arrow_down);
            } else {
                ei_count_block_title_div.attr('title', $show_label)
                ei_count_block_title_icon.attr('src', $arrow_up);
            }
        }
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
     * @param   int         $fk_c_intervention_type     Show only this intervention type (if defined)
     * @return  string                                  HTML table list of count by type of intervention for a contract of a company
     */
    public function showTableCountInterventionOfContract(&$contract, $fk_c_intervention_type=0)
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
        $company_counts = $this->getCountInterventionInfoOfCompany($contract);

        if (count($inter_type_dictionary->lines) > 0 && count($company_counts['periods']) > 0) {
            $out .= '<table class="border" width="100%">';
            $out .= '<tr><td class="titlefield" align="right">' . $langs->trans('ExtendedInterventionPeriod') . ' : </td>';
            foreach ($company_counts['periods'] as $period) {
                $out .= '<td align="center"' . (!empty($period['last_period']) || !empty($period['in_period']) ? ' style="background-color: ' . (!empty($period['last_period']) ? 'indianred' : 'lightblue') . ';"' : '') . '>' .
                    $langs->trans('DateFromTo', dol_print_date($period['begin'], 'day'), dol_print_date($period['end'], 'day')) .
                    (!empty($period['last_period']) || !empty($period['in_period']) ? '&nbsp;' . $this->form->textwithpicto('',
                            (!empty($period['in_period']) ? $langs->trans('ExtendedInterventionCurrentPeriod') : '') .
                            (!empty($period['last_period']) && !empty($period['in_period']) ? '<br>' : '') .
                            (!empty($period['last_period']) ? $langs->trans('ExtendedInterventionEndOfTheContract') : ''), 1, 'help') : '') .
                    '</td>';
            }
            $out .= '</tr>';

            foreach ($inter_type_dictionary->lines as $line) {
				//We try to find the max number of intervention of each type done per period
                $max=0;
				if(!empty($company_counts['types'][$line->id]))
				foreach ($company_counts['types'][$line->id] as $period_idx => $period)
				{
					$max = ($period['current'] > $max ? $period['current'] : $max);
				}
				$first=true; //we output only one time <tr>
				if(!empty($company_counts['types'][$line->id]))
                foreach ($company_counts['types'][$line->id] as $period_idx => $period) {
                    // Set label - we display the line only if the quota due is >0 or if some interventions have been done ($max >0)

					if($period['max'] > 0 || $max > 0)
					{
					if($first) $out .= '<tr><td class="titlefield">' . $line->fields['label'] . '</td>';
					$first=false;
                    $label = $period['current'] . ' / ' . $period['max'];
                    if ($period['current'] < $period['max']) $label = '<span style="color: green;">' . $label . '</span>';
                    elseif ($period['current'] > $period['max'] + $period['free']) $label = '<span style="color: red;">' . $label . '</span>';

                    // Set more info
                    $more_info = '';
                    $toprint = array();
                    if (!empty($period['free'])) $toprint[] = $langs->trans('ExtendedInterventionFree') . ' : ' . $period['free'];
                    if (!empty($period['forced'])) $toprint[] = $langs->trans('ExtendedInterventionForced') . ' : ' . $period['forced'];
                    if (!empty($toprint)) $more_info = '&nbsp;' . $this->form->textwithpicto('', implode('<br>', $toprint), 1, 'warning');

                    $out .= '<td align="center"' . (!empty($company_counts['periods'][$period_idx]['in_period']) ? ' style="background-color: lightblue;"' : '') . '>' . $label . $more_info . '</td>';
					}
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
     * @param   int         $fk_c_intervention_type     Show only this intervention type (if defined)
     * @return  boolean
     */
    public function isCreatedOutOfQuota(&$contract, $fk_c_intervention_type)
    {
        global $conf;
        $now = dol_now();

        if (!($fk_c_intervention_type > 0)) return false;

        $periods = $this->getPeriodOfContract($contract);
        $info = $this->getInfoInterventionOfContract($contract->id);
        $contract_counts = isset($info[$fk_c_intervention_type]['count']) ? $info[$fk_c_intervention_type]['count'] : 0;

        foreach ($periods as $period) {
            if ($now < $period['begin'] || $period['end'] < $now) continue;

            // Get forced intervention
            $sql = "SELECT COUNT(*) AS nb_current" .
                " FROM " . MAIN_DB_PREFIX . "fichinter AS fi" .
                " LEFT JOIN " . MAIN_DB_PREFIX . "fichinter_extrafields AS fief ON fief.fk_object = fi.rowid" .
                " WHERE fi.entity IN (" . getEntity('intervention') . ")";
            if ($conf->companyrelationships->enabled) {
                $contract->fetch_optionals();
                $sql .= " AND fief.companyrelationships_fk_soc_benefactor = " . $contract->array_options['options_companyrelationships_fk_soc_benefactor'];
            }
            $sql .= " AND fi.fk_soc = " . $contract->socid .
                " AND fi.fk_contrat = " . $contract->id .
                " AND fief.ei_type = " . $fk_c_intervention_type .
                " AND '" . $this->db->idate($period['begin']) . "' <= fi.dateo" .
                " AND fi.dateo <= '" . $this->db->idate($period['end']) . "'" .
                " GROUP BY fief.ei_type";

            $resql = $this->db->query($sql);
            if ($resql) {
                if ($obj = $this->db->fetch_object($resql)) {
                    return $contract_counts <= $obj->nb_current;
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

    /**
     *  Generate planning request of a contract
     *
     * @param   Contrat     $object             Contract handler
     * @param   User        $user               User that modifies
     * @param   array       $billing_period     Billing period
     * @return  int                             <0 if KO, >0 if OK
     */
    function generatePlanningRequest($object, $user, $billing_period)
    {
        global $conf, $langs;

        $periods = $this->getPeriodOfContract($object);

        // Billing period
        $billing_period_begin = $billing_period['begin']->timestamp;
        $billing_period_end = $billing_period['end']->timestamp;

        $generate = false;
        $reset_date = 0;
        foreach ($periods as $period) {
            if ($billing_period_begin <= $period['begin'] && $period['begin'] <= $billing_period_end) {
                $reset_date = $period['begin'];
                $generate = true;
                break;
            } elseif ($billing_period_end < $period['begin']) {
                break;
            }
        }

        // Generate the planned intervention
        //---------------------------------------------------
        if ($generate) {
            dol_include_once('/requestmanager/class/requestmanager.class.php');
            $request_types_planned = !empty($conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) ? explode(',', $conf->global->REQUESTMANAGER_PLANNING_REQUEST_TYPE) : array();

            dol_include_once('/advancedictionaries/class/dictionary.class.php');
            $inter_type_dictionary = Dictionary::getDictionary($this->db, 'extendedintervention', 'extendedinterventiontype');
            $inter_type_dictionary->fetch_lines(1, array('count' => 1), array('label' => 'ASC'));

            $info = $this->getInfoInterventionOfContract($object->id);
            $date_infos = dol_getdate($reset_date, true);

            foreach ($info as $type_intervention => $values) {
                foreach ($request_types_planned as $request_type) {
                    $planned_times = $values['planning_times'][$request_type];

                    if (!empty($planned_times)) {
                        foreach ($planned_times as $month) {
                            $request = new RequestManager($this->db);

                            $request->fk_type = $request_type;
                            $request->label = $langs->trans('ExtendedInterventionPlanningRequestTitle', $inter_type_dictionary->lines[$type_intervention]->fields['label']);
                            $request->socid_origin = $object->socid;
                            $request->socid = $object->socid;
                            $request->socid_benefactor = $object->array_options['options_companyrelationships_fk_soc_benefactor'];
                            $request->socid_watcher = $object->array_options['options_companyrelationships_fk_soc_watcher'];

                            $year = $date_infos['year'];
                            $request->date_operation = dol_get_first_day($year, $month);
                            if ($request->date_operation < $reset_date) {
                                $year++;
                                $request->date_operation = dol_get_first_day($year, $month);
                            }
                            $request->date_deadline = dol_get_last_day($year, $month);

                            $request->array_options['options_ei_type'] = $type_intervention;
                            $request->origin = $object->element;
                            $request->origin_id = $object->id;
                            $request->linkedObjectsIds[$request->origin] = $request->origin_id;

                            $id = $request->create($user);
                            if ($id < 0) {
                                setEventMessages($object->error, $object->errors, 'errors');
                                return -1;
                            }
                        }
                    }
                }
            }
        }

        return 1;
    }
}
