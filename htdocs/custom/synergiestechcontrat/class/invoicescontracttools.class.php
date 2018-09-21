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
     * @var     string      Error message
     */
	var $error = '';
    /**
     * @var     array       List of error message
     */
    var $errors = array();

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
     *  Get watching period
     *
     * @param   Contrat     $contract       Contract object
     * @param   int         $current_date   Current date
     *
     * @return  array|int                   Watching period: array('begin' => Carbon, 'end' => Carbon), -1: Errors
     */
    function getWatchingPeriod(&$contract, $current_date) {
        global $langs;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Set parameters
        $invoice_type = $contract->array_options['invoicetype'];
        $invoice_dates = $contract->array_options['invoicedates'];
        $current_date = Carbon::createFromTimestamp($current_date);

        switch ($invoice_type) {
            // Invoice to go
            case 1:
                switch ($invoice_dates) {
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
                        $errors[] = $langs->trans('STCErrorBadContractInvoiceDates', $invoice_dates);
                        return -1;
                }
            // Invoice due
            case 2:
                switch ($invoice_dates) {
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
                        $errors[] = $langs->trans('STCErrorBadContractInvoiceDates', $invoice_dates);
                        return -1;
                }
            default:
                $errors[] = $langs->trans('STCErrorBadContractInvoiceType', $invoice_type);
                return -1;
        }
    }

    /**
     *  Get bill period
     *
     * @param   Contrat     $contract           Contract object
     * @param   int         $watching_period    Watching period
     *
     * @return  array|int                       Bill period: array('begin' => Carbon, 'end' => Carbon), null: no period found, -1: Errors
     */
    function getBillPeriod(&$contract, $watching_period) {
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

        // Set parameters
        $start_date = $contract->array_options['startdate'];
        if (!is_numeric($start_date) || $start_date < 0) {
            $errors[] = $langs->trans('STCErrorBadContractStartDate', $start_date);
            return -1;
        }

        $bill_begin = null;
        $bill_end = null;
        $start_date = Carbon::createFromTimestamp($start_date);
        $watching_period_begin = $watching_period['begin'];
        $watching_period_end = $watching_period['end'];

        // Get begin date of the bill
        if ($start_date < $watching_period_begin) {
            $bill_begin = $watching_period_begin;
        } elseif ($watching_period_begin <= $start_date && $start_date <= $watching_period_end) {
            $bill_begin = $start_date;
        } else {
            return null;
        }

        // Get end date of the contract
        $contract_end = null;
        $termination_date = $contract->array_options['realdate'];
        if (is_numeric($termination_date) && $termination_date > 0) {
            $contract_end = Carbon::createFromTimestamp($termination_date);
        } else {
            if (!empty($contract->array_options['tacitagreement'])) {
                $contract_end = Carbon::now()->addMillennia();
            } else {
                $contract_end = $start_date->addMonths($contract->array_options['duration']);
            }
        }

        // Get end date of the bill
        if ($contract_end < $watching_period_begin) {
            return null;
        } elseif ($watching_period_begin <= $contract_end && $contract_end <= $watching_period_end) {
            $bill_end = $contract_end;
        } else {
            $bill_end = $watching_period_end;
        }

        return array('begin' => $bill_begin, 'end' => $bill_end);
    }

    /**
     *  Get date of first absolute revaluation
     *
     * @param   Contrat     $contract           Contract object
     *
     * @return  Carbon|int                      Date of first absolute revaluation, -1: Errors
     */
    function getDateFirstAbsoluteRevaluation(&$contract) {
        global $langs;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Set parameters
        $start_date = $contract->array_options['startdate'];
        if (!is_numeric($start_date) || $start_date < 0) {
            $errors[] = $langs->trans('STCErrorBadContractStartDate', $start_date);
            return -1;
        }
        $start_date = Carbon::createFromTimestamp($start_date);

        $revaluation_date = $contract->array_options['revalorisationperiod'];
        switch ($revaluation_date) {
            // Anniversary date
            case 1:
                $revaluation_date = $start_date;
                break;
            // 1st of January
            case 2:
                $revaluation_date = Carbon::create(0, 1, 1);
                break;
            // 1st of April
            case 3:
                $revaluation_date = Carbon::create(0, 4, 1);
                break;
            // 1st of July
            case 4:
                $revaluation_date = Carbon::create(0, 7, 1);
                break;
            // 1st of October
            case 5:
                $revaluation_date = Carbon::create(0, 10, 1);
                break;
            default:
                $errors[] = $langs->trans('STCErrorBadContractRevaluationDate', $revaluation_date);
                return -1;
        }

        $dateFirstAbsoluteRevaluation = null;
        $sd = $start_date->copy()->year(0);
        $rd = $revaluation_date->copy()->year(0);
        if ($sd >= $rd) {
            return Carbon::create($sd->year + 1, $rd->month, $rd->day);
        } else {
            return Carbon::create($sd->year, $rd->month, $rd->day);
        }
    }


}
