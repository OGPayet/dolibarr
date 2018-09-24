<?php
/* Copyright (C) 2018       Open-DSI         <support@open-dsi.fr>
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
 * 	\file 		htdocs/synergiestechcontrat/class/invoicescontracttools.class.php
 *	\ingroup    synergiestechcontrat
 *	\brief      File of class to manage generating of invoices from a contract
 */

dol_include_once('/synergiestechcontrat/includes/Carbon/Carbon.php');
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/**
 *	Class to manage generating of invoices from a contract
 */
class InvoicesContractTools
{
    /**
     * @var     DoliDB      Database handler
     */
    public $db;

    /**
     * @var     array       List of error message
     */
    public $errors = array();

    /**
     * Constants of the extra fields code
     */
    const EF_BILLING_TYPE                       = 'invoicetype';
    const EF_FREQUENCY_BILLING                  = 'invoicedates';
    const EF_TERMINATION_DATE                   = 'realdate';
    const EF_TACIT_RENEWAL                      = 'tacitagreement';
    const EF_CONTRACT_DURATION_MONTHS           = 'duration';
    const EF_CONTRACT_AMOUNT                    = 'initialvalue';
    const EF_REVALUATION_INDEX                  = 'reindexmethod';
    const EF_ENABLE_TO_REVALUATION_DATE         = 'revalorisationactivationdate';
    const EF_UNAUTHORIZED_DEFLATION             = 'prohibitdecrease';
    const EF_CURRENT_VALUE_INSTALLATION         = 'equipmentvalue';
    const EF_EFFECTIVE_DATE                     = 'startdate';
    const EF_REVALUATION_DATE                   = 'revalorisationperiod';
    const EF_LAST_REVALUATION_INDEX_USED        = 'oldindicemonth';
    const EF_MONTH_FOR_NEW_REVALUATION_INDEX    = 'newindicemonth';

	/**
	 *  Constructor
	 *
	 * @param   DoliDB  $db     Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

    /**
     *  Clear pool of errors
     */
    public function clearErrors() {
        $this->errors = array();
    }

    /**
     *  Get watching period
     *
     * @param   Contrat     $contract       Contract object
     * @param   int         $current_date   Current date
     *
     * @return  array|int                   Watching period: array('begin' => Carbon, 'end' => Carbon), -1: Errors
     */
    public function getWatchingPeriod(&$contract, $current_date) {
        global $langs;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Get parameters
        $billing_type = $contract->array_options[self::EF_BILLING_TYPE];
        $frequency_of_billing = $contract->array_options[self::EF_FREQUENCY_BILLING];
        $current_date = Carbon::createFromTimestamp($current_date);

        // Get watching period
        switch ($billing_type) {
            // Billing to go
            case 1:
                switch ($frequency_of_billing) {
                    // Monthly
                    case 1:
                        $next_month = $current_date->addMonth();
                        return array('begin' => $next_month->startOfMonth(), 'end' => $next_month->endOfMonth());
                    // Quarterly
                    case 2:
                        $next_quarter = $current_date->addMonths(3);
                        return array('begin' => $next_quarter->startOfQuarter(), 'end' => $next_quarter->endOfQuarter());
                    // Semi
                    case 3:
                        $next_semi = $current_date->addMonths(7 - ($current_date->month % 6));
                        return array('begin' => $next_semi->startOfMonth(), 'end' => $next_semi->addMonths(6)->endOfMonth());
                    // Annual
                    case 4:
                        $next_year = $current_date->addYear();
                        return array('begin' => $next_year->startOfYear(), 'end' => $next_year->endOfYear());
                    default:
                        $errors[] = $langs->trans('STCErrorBadContractFrequencyBilling', $frequency_of_billing);
                        return -1;
                }
            // Billing due
            case 2:
                switch ($frequency_of_billing) {
                    // Monthly
                    case 1:
                        $next_month = $current_date->subMonth();
                        return array('begin' => $next_month->startOfMonth(), 'end' => $next_month->endOfMonth());
                    // Quarterly
                    case 2:
                        $next_quarter = $current_date->subMonths(3);
                        return array('begin' => $next_quarter->startOfQuarter(), 'end' => $next_quarter->endOfQuarter());
                    // Semi
                    case 3:
                        $next_semi = $current_date->subMonths(($current_date->month % 6) + 1);
                        return array('begin' => $next_semi->startOfMonth(), 'end' => $next_semi->addMonths(6)->endOfMonth());
                    // Annual
                    case 4:
                        $next_year = $current_date->subYear();
                        return array('begin' => $next_year->startOfYear(), 'end' => $next_year->endOfYear());
                    default:
                        $errors[] = $langs->trans('STCErrorBadContractFrequencyBilling', $frequency_of_billing);
                        return -1;
                }
            default:
                $errors[] = $langs->trans('STCErrorBadContractBillingType', $billing_type);
                return -1;
        }
    }

    /**
     *  Get billing period
     *
     * @param   Contrat     $contract           Contract object
     * @param   array       $watching_period    Watching period
     *
     * @return  array|int                       Billing period: array('begin' => Carbon, 'end' => Carbon), null: no period found, -1: Errors
     */
    public function getBillingPeriod(&$contract, $watching_period) {
        global $langs;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        if (!is_array($watching_period) ||
            !isset($watching_period['begin']) || !is_numeric($watching_period['begin']) || $watching_period['begin'] < 0 ||
            !isset($watching_period['end']) || !is_numeric($watching_period['end']) || $watching_period['end'] < 0) {
            $errors[] = $langs->trans('STCErrorBadWatchingPeriod', json_encode($watching_period));
            return -1;
        }

        // Get effective date
        $effective_date = $this->getEffectiveDate($contract);
        if (is_numeric($effective_date) && $effective_date < 0) {
            return -1;
        }

        $start_date = Carbon::createFromTimestamp($effective_date);
        $watching_period_begin = $watching_period['begin'];
        $watching_period_end = $watching_period['end'];

        // Get begin date of the billing
        if ($effective_date < $watching_period_begin) {
            $billing_begin = $watching_period_begin;
        } elseif ($watching_period_begin <= $effective_date && $effective_date <= $watching_period_end) {
            $billing_begin = $effective_date;
        } else {
            return null;
        }

        // Get end date of the contract
        $contract_end = null;
        $termination_date = $contract->array_options[self::EF_TERMINATION_DATE];
        if (is_numeric($termination_date) && $termination_date > 0) {
            $contract_end = Carbon::createFromTimestamp($termination_date);
        } else {
            if (!empty($contract->array_options[self::EF_TACIT_RENEWAL])) {
                $contract_end = Carbon::now()->addMillennia();
            } else {
                $contract_end = $start_date->addMonths($contract->array_options[self::EF_CONTRACT_DURATION_MONTHS]);
            }
        }

        // Get end date of the bill
        if ($contract_end < $watching_period_begin) {
            return null;
        } elseif ($watching_period_begin <= $contract_end && $contract_end <= $watching_period_end) {
            $billing_end = $contract_end;
        } else {
            $billing_end = $watching_period_end;
        }

        return array('begin' => $billing_begin, 'end' => $billing_end);
    }

    /**
     *  Get first absolute revaluation date
     *
     * @param   Contrat     $contract           Contract object
     *
     * @return  Carbon|int                      First absolute revaluation date, -1: Errors
     */
    public function getFirstAbsoluteRevaluationDate(&$contract) {
        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Get effective date
        $effective_date = $this->getEffectiveDate($contract);
        if (is_numeric($effective_date) && $effective_date < 0) {
            return -1;
        }

        // Get revaluation date
        $revaluation_date = $this->getRevaluationDate($contract);
        if (is_numeric($revaluation_date) && $revaluation_date < 0) {
            return -1;
        }

        // Get date of first absolute revaluation
        $year_offset = $effective_date->copy()->year(0) >= $revaluation_date ? 1 : 0;
        return Carbon::create($effective_date->year + $year_offset, $revaluation_date->month, $revaluation_date->day);
    }

    /**
     *  Get amount of the invoice of the contract for the given bill period
     *  Ps: Also update the 'contract amount', 'last revaluation index value used' and the 'current value of the installation'
     *      of the contract if has a deflation
     *      And create a event with all information of this deflation
     *
     * @param   Contrat     $contract               Contract object
     * @param   array       $watching_period        Watching period
     * @param   array       $billing_period         Billing period
     * @param   int         $test_mode              Mode test (don't update contract)
     * @param   int         $disable_revaluation    Disabled revaluation (option only taken into account in test mode)
     *
     * @return  double|int                          Amount of the invoice of the contract for the given billing period, -1: Errors
     */
    public function getAmountInvoice(&$contract, $watching_period, $billing_period, $test_mode=0, $disable_revaluation=0) {
        global $langs, $user;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Get contract amount
        $contract_amount = $contract->array_options[self::EF_CONTRACT_AMOUNT];
        if (!is_numeric($contract_amount) || $contract_amount < 0) {
            $errors[] = $langs->trans('STCErrorBadContractAmount', $contract_amount);
            return -1;
        }

        // Get number of bill period in a year
        $frequency_of_billing = $contract->array_options[self::EF_FREQUENCY_BILLING];
        switch ($frequency_of_billing) {
            // Monthly
            case 1: $number_billing_period_in_year = 12; break;
            // Quarterly
            case 2: $number_billing_period_in_year = 4; break;
            // Semi
            case 3: $number_billing_period_in_year = 2; break;
            // Annual
            case 4: $number_billing_period_in_year = 1; break;
            default:
                $errors[] = $langs->trans('STCErrorBadContractFrequencyBilling', $frequency_of_billing);
                return -1;
        }

        // Get watching and bill periods
        $watching_period_begin = $watching_period['begin'];
        $watching_period_end = $watching_period['end'];
        $billing_period_begin = $billing_period['begin'];
        $billing_period_end = $billing_period['end'];

        // Get watching period length
        $watching_period_lenght = $watching_period_begin->diffInDays($watching_period_end);

        // Get revaluation index
        $revaluation_index = $contract->array_options[self::EF_REVALUATION_INDEX];
        if ($revaluation_index != 1 && $revaluation_index != 2 && $revaluation_index != 3) {
            $errors[] = $langs->trans('STCErrorBadContractRevaluationIndex', $revaluation_index);
            return -1;
        }

        $billing_period_amount = null;

        // If has a revaluation
        if ($revaluation_index == 2 || $revaluation_index == 3) {
            // Get revaluation date
            $revaluation_date = $this->getRevaluationDate($contract);
            if (is_numeric($revaluation_date) && $revaluation_date < 0) {
                return -1;
            }

            // Get potential revaluation date
            $potential_revaluation_date = null;
            if ($billing_period_begin <= $revaluation_date->year($billing_period_begin->year) && $revaluation_date->year($billing_period_begin->year) <= $billing_period_end) {
                $potential_revaluation_date = $revaluation_date->year($billing_period_begin->year);
            } elseif ($billing_period_begin <= $revaluation_date->year($billing_period_end->year) && $revaluation_date->year($billing_period_end->year) <= $billing_period_end) {
                $potential_revaluation_date = $revaluation_date->year($billing_period_end->year);
            }

            // Get date first absolute revaluation
            $first_absolute_revaluation_date = $this->getFirstAbsoluteRevaluationDate($contract);
            if (is_numeric($first_absolute_revaluation_date) && $first_absolute_revaluation_date < 0) {
                return -1;
            }

            // Get the date when enable to revaluation
            $enable_to_revaluation_date = $contract->array_options[self::EF_ENABLE_TO_REVALUATION_DATE];
            if (!is_numeric($enable_to_revaluation_date) || $enable_to_revaluation_date < 0) {
                $errors[] = $langs->trans('STCErrorBadContractStartDate', $enable_to_revaluation_date);
                return -1;
            }
            $enable_to_revaluation_date = Carbon::createFromTimestamp($enable_to_revaluation_date);

            // Calculate revaluation
            if (isset($potential_revaluation_date) &&
                $potential_revaluation_date >= $first_absolute_revaluation_date &&
                $potential_revaluation_date >= $enable_to_revaluation_date &&
                (empty($test_mode) || empty($disable_revaluation))
            ) {
                // Get split billing periods
                $first_billing_period_begin = $billing_period_begin;
                $first_billing_period_end = $potential_revaluation_date;
                $second_billing_period_begin = $potential_revaluation_date;
                $second_billing_period_end = $billing_period_end;

                // Get length periods
                $first_billing_period_lenght = $first_billing_period_begin->diffInDays($first_billing_period_end);
                $second_billing_period_lenght = $second_billing_period_begin->diffInDays($second_billing_period_end);

                // Calculate amount first billing period
                // = ($first_billing_period_lenght / $watching_period_lenght) * $contract_amount / $number_billing_period_in_year
                $first_billing_period_amount = ($first_billing_period_lenght * $contract_amount) / ($watching_period_lenght * $number_billing_period_in_year);

                // Get last revaluation index value used
                $last_revaluation_index_value_used = $this->getLastRevaluationIndexValueUsed($contract);
                if ($last_revaluation_index_value_used < 0) {
                    return -1;
                }

                // Get revaluation index info
                $revaluation_index_info = $this->getRevaluationIndexInfo($contract, $potential_revaluation_date);
                if (!is_array($revaluation_index_info)) {
                    return -1;
                }

                // Get unauthorized deflation
                $unauthorized_deflation = $contract->array_options[self::EF_UNAUTHORIZED_DEFLATION];

                // Calculate the deflation factor
                if (!($revaluation_index_info['index'] < $last_revaluation_index_value_used && !empty($unauthorized_deflation))) {
                    // Update 'contract amount' of the contract
                    $contract_amount = $contract_amount * $revaluation_index_info['index'] / $last_revaluation_index_value_used;
                    $contract->array_options[self::EF_CONTRACT_AMOUNT] = $contract_amount;
                    // Update 'last revaluation index value used' of the contract
                    $this->setLastRevaluationIndexUsed($contract, $revaluation_index_info['month'], $revaluation_index_info['year']);
                    // Update 'current value of the installation' of the contract
                    $contract->array_options[self::EF_CURRENT_VALUE_INSTALLATION] = $contract->array_options[self::EF_CURRENT_VALUE_INSTALLATION] * $revaluation_index_info['index'] / $last_revaluation_index_value_used;

                    if (!empty($test_mode)) $this->db->begin();

                    // Update contract
                    $result = $contract->update($user);
                    if ($result < 0) {
                        $this->errors = array_merge($this->errors, $this->_getObjectErrors($contract));
                        if (!empty($test_mode)) $this->db->rollback();
                        return -1;
                    }

                    // Create event with all information of this deflation
                    $label = $langs->trans('STCContractDeflationEventLabel', $contract->ref);
                    // Todo build the message
                    $message = $langs->trans('STCContractDeflationEventDescription', $contract->ref);
                    $result = $this->_addEvent($contract, 'AC_STC_DEFLATION', $label, $message);
                    if ($result < 0) {
                        if (!empty($test_mode)) $this->db->rollback();
                        return -1;
                    }

                    if (!empty($test_mode)) $this->db->rollback();
                }

                // Calculate amount second billing period
                // = ($contract_amount / $number_billing_period_in_year) * ($second_billing_period_lenght / $watching_period_lenght)
                $second_billing_period_amount = ($second_billing_period_lenght * $contract_amount) / ($watching_period_lenght * $number_billing_period_in_year);

                // Calculate the billing period amount
                $billing_period_amount = $first_billing_period_amount + $second_billing_period_amount;
            }
        }

        // Calculate the billing period amount if not already calculated
        if (!isset($billing_period_amount)) {
            $billing_period_lenght = $billing_period_begin->diffInDays($billing_period_end);
            // = ($billing_period_lenght / $watching_period_lenght) * $contract_amount / $number_billing_period_in_year
            $billing_period_amount = ($billing_period_lenght * $contract_amount) / ($watching_period_lenght * $number_billing_period_in_year);
        }

        if ($billing_period_amount < 0) {
            $errors[] = $langs->trans('STCErrorBadContractAmountCalculated', $billing_period_amount);
            return -1;
        }

        return $billing_period_amount;
    }

    /**
     *  Get effective date
     *
     * @param   Contrat     $contract           Contract object
     *
     * @return  Carbon|int                      Effective date, -1: Errors
     */
    public function getEffectiveDate(&$contract) {
        global $langs;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Get effective date
        $effective_date = $contract->array_options[self::EF_EFFECTIVE_DATE];
        if (!is_numeric($effective_date) || $effective_date < 0) {
            $errors[] = $langs->trans('STCErrorBadContractEffectiveDate', $effective_date);
            return -1;
        }

        return Carbon::createFromTimestamp($effective_date);
    }

    /**
     *  Get revaluation date
     *
     * @param   Contrat     $contract           Contract object
     *
     * @return  Carbon|int                      Revaluation date, -1: Errors
     */
    public function getRevaluationDate(&$contract) {
        global $langs;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Get effective date
        $effective_date = $this->getEffectiveDate($contract);
        if (is_numeric($effective_date) && $effective_date < 0) {
            return -1;
        }

        // Get revaluation date
        $revaluation_date = $contract->array_options[self::EF_REVALUATION_DATE];
        switch ($revaluation_date) {
            // Anniversary date
            case 1: return Carbon::create(0, $effective_date->month, $effective_date->day); break;
            // 1st of January
            case 2: return Carbon::create(0, 1, 1);
            // 1st of April
            case 3: return Carbon::create(0, 4, 1);
            // 1st of July
            case 4: return Carbon::create(0, 7, 1);
            // 1st of October
            case 5: return Carbon::create(0, 10, 1);
            default:
                $errors[] = $langs->trans('STCErrorBadContractRevaluationDate', $revaluation_date);
                return -1;
        }
    }

    /**
     *  Get last revaluation index value used
     *
     * @param   Contrat     $contract           Contract object
     *
     * @return  int                             Last revaluation index value used, -1: Errors
     */
    public function getLastRevaluationIndexValueUsed(&$contract) {
        global $langs;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Get last revaluation index used
        $last_revaluation_index_used = $contract->array_options[self::EF_LAST_REVALUATION_INDEX_USED];
        if (empty($last_revaluation_index_used)) {
            $errors[] = $langs->trans('STCErrorLastRevaluationIndexValueUsedEmpty');
            return -1;
        }

        // Get last revaluation index value used
        $sql = "SELECT indice FROM " . MAIN_DB_PREFIX . "view_c_indice" .
            " WHERE rowid = '" . $this->db->escape($last_revaluation_index_used) . "'";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $errors[] = $langs->trans('STCErrorSQLGetLastRevaluationIndexValueUsed', $this->db->lasterror());
            return -1;
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        if ($obj) {
            if ($obj->indice <= 0) {
                $errors[] = $langs->trans('STCErrorBadLastRevaluationIndexValueUsed', $this->db->lasterror());
                return -1;
            }

            return $obj->indice;
        } else {
            $errors[] = $langs->trans('STCErrorLastRevaluationIndexValueUsedNotFound', $this->db->lasterror());
            return -1;
        }
    }

    /**
     *  Set last revaluation index value used
     *
     * @param   Contrat     $contract       Contract object
     * @param   int         $month          Month of the revaluation index
     * @param   int         $year           Year of the revaluation index
     *
     * @return  int                         1: OK, -1: Errors
     */
    public function setLastRevaluationIndexUsed(&$contract, $month, $year) {
        global $langs;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Get revaluation index
        $revaluation_index = $contract->array_options[self::EF_REVALUATION_INDEX];
        switch ($revaluation_index) {
            // Syntec
            case 2: $filter = 'Syntec'; break;
            // Insee
            case 3: $filter = 'Insee'; break;
            default:
                $errors[] = $langs->trans('STCErrorBadContractRevaluationIndex', $revaluation_index);
                return -1;
        }

        // Search last revaluation index value used into the table
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "view_c_indice" .
            " WHERE month_indice = '" . $this->db->escape($month) . "'" .
            " AND year_indice = '" . $this->db->escape($year) . "'" .
            " AND filter = '" . $this->db->escape($filter) . "'";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $errors[] = $langs->trans('STCErrorSQLSetLastRevaluationIndexUsed', $this->db->lasterror());
            return -1;
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        if ($obj) {
            return $obj->rowid;
        } else {
            $errors[] = $langs->trans('STCErrorLastRevaluationIndexUsedNotFound', $filter, $month, $year);
            return -1;
        }
    }

    /**
     *  Get revaluation index info
     *
     * @param   Contrat     $contract                       Contract object
     * @param   Carbon      $potential_revaluation_date     Potential revaluation date
     *
     * @return  array|int                                   Revaluation index info array('index' => int, 'month' => int, 'year' => int), -1: Errors
     */
    public function getRevaluationIndexInfo(&$contract, $potential_revaluation_date) {
        global $langs;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Get month used for get the new revaluation index value
        $month_for_new_revaluation_index = $contract->array_options[self::EF_MONTH_FOR_NEW_REVALUATION_INDEX];
        if ($month_for_new_revaluation_index != floor($month_for_new_revaluation_index) ||
            1 < $month_for_new_revaluation_index || $month_for_new_revaluation_index < 12) {
            $errors[] = $langs->trans('STCErrorBadContractMonthForNewRevaluationIndex', $month_for_new_revaluation_index);
            return -1;
        }

        // Get revaluation index
        $revaluation_index = $contract->array_options[self::EF_REVALUATION_INDEX];
        switch ($revaluation_index) {
            // Syntec
            case 2: $suffix_table = 'syntec'; break;
            // Insee
            case 3: $suffix_table = 'insee'; break;
            default:
                $errors[] = $langs->trans('STCErrorBadContractRevaluationIndex', $revaluation_index);
                return -1;
        }

        // Todo to check for 'month used for get the new revaluation index' and 'potential revaluation date' used in the sql request
        $sql = "SELECT indice, year_indice, month_indice FROM " . MAIN_DB_PREFIX . "c_indice_".$suffix_table .
            " WHERE active = 1" .
            " AND CONCAT(year_indice, '-', month_indice) <= '" . $this->db->escape($potential_revaluation_date->year . "-" . $month_for_new_revaluation_index) . "'" .
            " AND CONCAT(year_indice, '-', month_indice) >= '" . $this->db->escape(($potential_revaluation_date->year - 1) . "-" . $potential_revaluation_date->month) . "'" .
            " ORDER BY year_indice DESC, month_indice DESC" .
            " LIMIT 1";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $errors[] = $langs->trans('STCErrorSQLGetRevaluationIndexInfo', $this->db->lasterror());
            return -1;
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        if ($obj) {
            if ($obj->indice <= 0) {
                $errors[] = $langs->trans('STCErrorBadRevaluationIndexInfo', $this->db->lasterror());
                return -1;
            }

            return array('index' => $obj->indice, 'month' => $obj->month_indice, 'year' => $obj->year_indice);
        } else {
            $errors[] = $langs->trans('STCErrorRevaluationIndexInfoNotFound', $suffix_table, $month_for_new_revaluation_index, $potential_revaluation_date->month, $potential_revaluation_date->year);
            return -1;
        }
    }

    /**
     *  Add a new event related to the given object
     *
     * @param   object    $object       Object related to the event
     * @param   string    $type_code    Code of the event
     * @param   string    $label        Label of the event
     * @param   string    $message      Message of the event
     * @return  int                     >0: rowid of the new created event, <0: Errors
     */
    private function _addEvent(&$object, $type_code, $label, $message = '')
    {
        global $user;

        require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';

        $now = dol_now();
        $actioncomm = new ActionComm($this->db);

        $actioncomm->type_code = $type_code;
        $actioncomm->label = $label;
        $actioncomm->note = !empty($message) ? $message : $label;
        $actioncomm->fk_project = 0;
        $actioncomm->datep = $now;
        $actioncomm->datef = $now;
        $actioncomm->fulldayevent = 0;
        $actioncomm->durationp = 0;
        $actioncomm->punctual = 1;
        $actioncomm->percentage = -1;           // Not applicable
        $actioncomm->transparency = 0;          // Not applicable
        $actioncomm->authorid = $user->id;      // User saving action
        $actioncomm->userownerid = $user->id;   // User saving action
        $actioncomm->elementtype = $object->element;
        $actioncomm->fk_element = $object->id;
        $actioncomm->fk_soc = $object->fk_soc > 0 ? $object->fk_soc : ($object->element == 'societe' ? $object->id : 0);

        $result = $actioncomm->create($user);
        if ($result < 0) {
            $this->errors = array_merge($this->errors, $this->_getObjectErrors($actioncomm));
            return -1;
        }

        return $result;
    }

    /**
     *  Get all errors of the object
     *
     * @param  object   $object     Object
     * @return array                Array of errors
     */
	private function _getObjectErrors(&$object) {
	    $errors = is_array($object->errors) ? $object->errors : array();
	    $errors = array_merge($errors, (!empty($object->error) ? array($object->error) : array()));

	    return $errors;
    }
}
