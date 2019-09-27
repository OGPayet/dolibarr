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

if (!class_exists('ComposerAutoloaderInit7e289d877b5289c34886bc66da322d02', false)) {
    dol_include_once('/synergiestechcontrat/vendor/autoload.php');
}
use Carbon\Carbon;

/**
 *	Class to manage generating of invoices from a contract
 */
class InvoicesContractTools
{
    /**
     * @var DoliDB  Database handler
     */
    public $db;
    /**
     * @var array   List of error message
     */
    public $errors = array();

    /**
     * @var Form   Form object
     */
    public $form = null;
    /**
     * @var ExtraFields   Extra fields object
     */
    public $extraFields = null;
    /**
     * @var array         List of old month index key/label (EF_LAST_REVALUATION_INDEX_USED)
     */
    public $cache_old_month_index = null;
    /**
     * @var array         List of account
     */
    public $cache_account = null;
    /**
     * @var array         List of extra fields
     */
    public $cache_extrafields = null;

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
    const EF_CONTRACT_DURATION                  = 'duration';
    // Specific Synergies-Tech - Begin
    const EF_CONTRACT_FORMULA                   = 'formule';
    // Specific Synergies-Tech - End

    /**
     * @var string      CSV separator to use
     */
    public $csv_separator = ';';
    /**
     * @var string      CSV enclosure
     */
    public $csv_enclosure = '"';
    /**
     * @var string      CSV escape
     */
    public $csv_escape = '\\';
    /**
     * @var resource    Report file instance
     */
    public $report_file = null;
    /**
     * @var array       Current report line
     */
    public $current_report_line = array();
    /**
     * @var boolean     Has current report line been modified
     */
    public $current_report_line_modified = false;
    /**
     * Constants report line header
     */
    const RLH_PARAM_BEGIN_WATCHING_DATE = 0;
    const RLH_PARAM_END_WATCHING_DATE = 1;
    const RLH_PARAM_PAYMENT_CONDITION_ID = 2;
    const RLH_PARAM_PAYMENT_DEADLINE_DATE = 3;
    const RLH_PARAM_REF_CUSTOMER = 4;
    const RLH_PARAM_USE_CUSTOMER_DISCOUNTS = 5;
    const RLH_PARAM_TEST_MODE = 6;
    const RLH_PARAM_DISABLE_REVALUATION = 7;
    const RLH_CONTRACT_ID = 8;
    const RLH_CONTRACT_REF = 9;
    const RLH_WATCHING_DATE = 10;
    const RLH_WATCHING_PERIOD_BEGIN = 11;
    const RLH_WATCHING_PERIOD_END = 12;
    const RLH_BILLING_PERIOD_BEGIN = 13;
    const RLH_BILLING_PERIOD_END = 14;
    const RLH_WATCHING_PERIOD_LENGHT = 15;
    const RLH_CONTRACT_REVALUATION_INDEX = 16;
    const RLH_CONTRACT_REVALUATION_DATE = 17;
    const RLH_POTENTIAL_REVALUATION_DATE = 18;
    const RLH_FIRST_ABSOLUTE_REVALUATION_DATE = 19;
    const RLH_CONTRACT_ENABLE_TO_REVALUATION_DATE = 20;
    const RLH_FIRST_BILLING_PERIOD_BEGIN = 21;
    const RLH_FIRST_BILLING_PERIOD_END = 22;
    const RLH_FIRST_BILLING_PERIOD_LENGHT = 23;
    const RLH_SECOND_BILLING_PERIOD_BEGIN = 24;
    const RLH_SECOND_BILLING_PERIOD_END = 25;
    const RLH_SECOND_BILLING_PERIOD_LENGHT = 26;
    const RLH_CONTRACT_LAST_REVALUATION_INDEX_VALUE_USED = 27;
    const RLH_REVALUATION_INDEX_INFO_INDEX = 28;
    const RLH_REVALUATION_INDEX_INFO_INDEX_VALUE = 29;
    const RLH_REVALUATION_INDEX_INFO_MONTH = 30;
    const RLH_REVALUATION_INDEX_INFO_YEAR = 31;
    const RLH_CONTRACT_UNAUTHORIZED_DEFLATION = 32;
    const RLH_CONTRACT_AMOUNT = 33;
    const RLH_CONTRACT_NEW_AMOUNT = 34;
    const RLH_CONTRACT_LAST_REVALUATION_INDEX_USED = 35;
    const RLH_CONTRACT_NEW_LAST_REVALUATION_INDEX_USED = 36;
    const RLH_CONTRACT_CURRENT_VALUE_INSTALLATION = 37;
    const RLH_CONTRACT_NEW_CURRENT_VALUE_INSTALLATION = 38;
    const RLH_FIRST_BILLING_PERIOD_AMOUNT = 39;
    const RLH_SECOND_BILLING_PERIOD_AMOUNT = 40;
    const RLH_BILLING_PERIOD_LENGHT = 41;
    const RLH_BILLING_PERIOD_AMOUNT = 42;
    const RLH_INVOICE_ID = 43;
    const RLH_INVOICE_REF = 44;
    const RLH_INVOICE_SOC_ID = 45;
    const RLH_INVOICE_SOC_NAME = 46;
    const RLH_INVOICE_REF_CUSTOMER = 47;
    const RLH_INVOICE_MODEL_PDF = 48;
    const RLH_INVOICE_COND_REGLEMENT_ID = 49;
    const RLH_INVOICE_MODE_REGLEMENT_ID = 50;
    const RLH_INVOICE_ACCOUNT_ID = 51;
    const RLH_INVOICE_ACCOUNT_NAME = 52;
    const RLH_INVOICE_AMOUNT_HT = 53;
    const RLH_INVOICE_AMOUNT_VAT = 54;
    const RLH_INVOICE_AMOUNT_TTC = 55;
    const RLH_INVOICE_REMISE_PERCENT = 56;
    const RLH_INVOICE_REMISE_ABSOLUE = 57;
    const RLH_INVOICE_INCOTERMS_ID = 58;
    const RLH_INVOICE_LOCATION_INCOTERMS = 59;
    const RLH_INVOICE_MULTICURRENCY_CODE = 60;
    const RLH_INVOICE_BILLING_PERIOD_BEGIN = 61;
    const RLH_INVOICE_BILLING_PERIOD_END = 62;
    const RLH_INVOICE_VALIDATED = 63;
    const RLH_ERRORS = 100;


	/**
	 *  Constructor
	 *
	 * @param   DoliDB  $db     Database handler
	 */
	function __construct($db)
    {
        global $conf, $langs;

		ini_set('max_execution_time', 1800);

        $langs->load('synergiestechcontrat@synergiestechcontrat');
        $this->db = $db;
        $this->csv_separator = !empty($conf->global->SYNERGIESTECHCONTRAT_CSV_SEPARATOR_TO_USE) ? $conf->global->SYNERGIESTECHCONTRAT_CSV_SEPARATOR_TO_USE : $this->csv_separator;
        $this->csv_enclosure = !empty($conf->global->SYNERGIESTECHCONTRAT_CSV_ENCLOSURE) ? $conf->global->SYNERGIESTECHCONTRAT_CSV_ENCLOSURE : $this->csv_enclosure;
        $this->csv_escape = !empty($conf->global->SYNERGIESTECHCONTRAT_CSV_ESCAPE) ? $conf->global->SYNERGIESTECHCONTRAT_CSV_ESCAPE : $this->csv_escape;

        require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
        $this->extrafields = new ExtraFields($this->db);
        $extralabels = $this->extrafields->fetch_name_optionals_label('contrat');

        $this->cache_old_month_index = array();
        $sql = "SELECT rowid, label FROM ".MAIN_DB_PREFIX."view_c_indice";
        $resql = $this->db->query($sql);
        if ($resql) {
            while($obj = $this->db->fetch_object($resql)) {
                $this->cache_old_month_index[$obj->rowid] = $obj->label;
            }
        }

        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
        $this->form = new Form($this->db);
        $this->cache_account = array();
        $this->cache_extrafields = array();
    }

    /**
     *  Set database handler
     *
     * @param   DoliDB  $db     Database handler
     */
    public function setDataBase($db) {
        $this->db = $db;
    }

    /**
     *  Clear pool of errors
     */
    public function clearErrors() {
        $this->errors = array();
    }

    /**
     *  Get contract extra fields label
     *
     * @param   string      $code       Code of the extra fields
     * @param   string      $value      Value
     * @return  string                  Label of the value
     */
    public function getContractExtraFieldsLabel($code, $value) {
        global $langs;

        $label = $this->extrafields->attributes['contrat']['param'][$code]['options'][$value];

        return isset($label) ? $label : $langs->trans('STCValueNotFound');
    }

    /**
     *  Get contract old month index label (EF_LAST_REVALUATION_INDEX_USED)
     *
     * @param   string      $value      Value
     * @return  string                  Label of the value
     */
    public function getContractOldMonthIndexLabel($value) {
        global $langs;

        $label = $this->cache_old_month_index[$value];

        return isset($label) ? $label : $langs->trans('STCValueNotFound');
    }

    /**
     *  Get watching period
     *
     * @param   Contrat     $contract       Contract object
     * @param   int         $watching_date  Watching date
     *
     * @return  array                       Watching period: array('begin' => Carbon, 'end' => Carbon), null: Errors
     */
    public function getWatchingPeriod(&$contract, $watching_date) {
        global $langs;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Get parameters
        $billing_type = $contract->array_options['options_' . self::EF_BILLING_TYPE];
        $frequency_of_billing = $contract->array_options['options_' . self::EF_FREQUENCY_BILLING];
        $watching_date = Carbon::createFromTimestamp($watching_date);

        // TODO 'endOf' serait la base du 'bug' de la longueur des périodes facturées, 1 jour ajouté
        // Get watching period
        switch (intval($billing_type)) {
            // Billing to go
            case 1:
                switch (intval($frequency_of_billing)) {
                    // Monthly
                    case 1:
                        $next_month = $watching_date->copy()->addMonth();
                        return array('begin' => $next_month->copy()->startOfMonth(), 'end' => $next_month->copy()->endOfMonth());
                    // Quarterly
                    case 2:
                        $next_quarter = $watching_date->copy()->addMonths(3);
                        return array('begin' => $next_quarter->copy()->startOfQuarter(), 'end' => $next_quarter->copy()->endOfQuarter());
                    // Semi
                    case 3:
                        $next_semi = $watching_date->copy()->addMonths(7 - ($watching_date->month % 6));
                        return array('begin' => $next_semi->copy()->startOfMonth(), 'end' => $next_semi->copy()->startOfMonth()->addMonths(6)->subDay());
                    // Annual
                    case 4:
                        $next_year = $watching_date->copy()->addYear();
                        return array('begin' => $next_year->copy()->startOfYear(), 'end' => $next_year->copy()->endOfYear());
                    default:
                        $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractFrequencyBilling'), $frequency_of_billing);
                        return null;
                }
            // Billing due
            case 2:
                switch (intval($frequency_of_billing)) {
                    // Monthly
                    case 1:
                        $next_month = $watching_date->copy()->subMonth();
                        return array('begin' => $next_month->copy()->startOfMonth(), 'end' => $next_month->copy()->endOfMonth());
                    // Quarterly
                    case 2:
                        $next_quarter = $watching_date->copy()->subMonths(3);
                        return array('begin' => $next_quarter->copy()->startOfQuarter(), 'end' => $next_quarter->copy()->endOfQuarter());
                    // Semi
                    case 3:
                        $next_semi = $watching_date->copy()->subMonths(($watching_date->month % 6) + 1);
                        return array('begin' => $next_semi->copy()->startOfMonth(), 'end' => $next_semi->copy()->startOfMonth()->addMonths(6)->subDay());
                    // Annual
                    case 4:
                        $next_year = $watching_date->copy()->subYear();
                        return array('begin' => $next_year->copy()->startOfYear(), 'end' => $next_year->copy()->endOfYear());
                    default:
                        $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractFrequencyBilling'), $frequency_of_billing);
                        return null;
                }
            default:
                $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractBillingType'), $billing_type);
                return null;
        }
    }

    /**
     *  Get billing period
     *
     * @param   Contrat     $contract           Contract object
     * @param   array       $watching_period    Watching period
     *
     * @return  array                           Billing period: array('begin' => Carbon, 'end' => Carbon), empty: no period found, null: Errors
     */
    public function getBillingPeriod(&$contract, $watching_period) {
        global $langs;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        if (!is_array($watching_period) ||
            !isset($watching_period['begin']) || $watching_period['begin'] <= 0 ||
            !isset($watching_period['end']) || $watching_period['end'] <= 0) {
            $this->errors[] = $langs->trans('STCErrorBadWatchingPeriod', json_encode($watching_period));
            return null;
        }

        // Get effective date
        $effective_date = $this->getEffectiveDate($contract);
        if (is_numeric($effective_date) && $effective_date < 0) {
            return null;
        }

        $start_date = $effective_date->copy();
        $watching_period_begin = $watching_period['begin']->copy();
        $watching_period_end = $watching_period['end']->copy();

        // Get begin date of the billing
        if ($effective_date < $watching_period_begin) {
            $billing_begin = $watching_period_begin->copy();
        } elseif ($watching_period_begin <= $effective_date && $effective_date <= $watching_period_end) {
            $billing_begin = $effective_date->copy();
        } else {
            return array();
        }

        // Get end date of the contract
        $contract_end = null;
        $termination_date = $contract->array_options['options_' . self::EF_TERMINATION_DATE];
        if (!empty($termination_date)) {
            $contract_end = Carbon::createFromFormat('Y-m-d', $termination_date);
            if (!isset($contract_end)) {
                $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCTerminationDate'), $termination_date);
                return null;
            }
        } else {
            if (!empty($contract->array_options['options_' . self::EF_TACIT_RENEWAL])) {
                $contract_end = Carbon::now()->addCenturies(10);
            } else {
                $contract_end = $start_date->copy()->addMonths($contract->array_options['options_' . self::EF_CONTRACT_DURATION_MONTHS]);
            }
        }

        // Get end date of the bill
        if ($contract_end < $watching_period_begin) {
            return array();
        } elseif ($watching_period_begin <= $contract_end && $contract_end <= $watching_period_end) {
            $billing_end = $contract_end->copy();
        } else {
            $billing_end = $watching_period_end->copy();
        }

        return array('begin' => $billing_begin, 'end' => $billing_end);
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
     * @param   int         $test_mode              Mode test (don't write in database)
     * @param   int         $disable_revaluation    Disabled revaluation (option only taken into account in test mode)
     *
     * @return  double                              Amount of the invoice of the contract for the given billing period, null: Errors
     */
    public function getInvoiceAmount(&$contract, $watching_period, $billing_period, $test_mode=0, $disable_revaluation=0) {
        global $conf, $langs, $user;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Get contract amount
        $contract_amount = $contract->array_options['options_' . self::EF_CONTRACT_AMOUNT];
        if (!is_numeric($contract_amount) || $contract_amount < 0) {
            $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractAmount'), $contract_amount);
            return null;
        }

        // Get number of bill period in a year
        $frequency_of_billing = $contract->array_options['options_' . self::EF_FREQUENCY_BILLING];
        switch (intval($frequency_of_billing)) {
            // Monthly
            case 1: $number_billing_period_in_year = 12; break;
            // Quarterly
            case 2: $number_billing_period_in_year = 4; break;
            // Semi
            case 3: $number_billing_period_in_year = 2; break;
            // Annual
            case 4: $number_billing_period_in_year = 1; break;
            default:
                $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractFrequencyBilling'), $frequency_of_billing);
                return null;
        }

        // Get watching and bill periods
        $watching_period_begin = $watching_period['begin']->copy();
        $watching_period_end = $watching_period['end']->copy();
        $billing_period_begin = $billing_period['begin']->copy();
        $billing_period_end = $billing_period['end']->copy();

        // Get watching period length
        $watching_period_lenght = $watching_period_begin->diffInDays($watching_period_end) + 1;

        // Set info into the report CSV
        $this->setCurrentReportLineValue(self::RLH_WATCHING_PERIOD_LENGHT, $watching_period_lenght);

        // Get revaluation index
        $revaluation_index = $contract->array_options['options_' . self::EF_REVALUATION_INDEX];
        if ($revaluation_index != 1 && $revaluation_index != 2 && $revaluation_index != 3) {
            $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractRevaluationIndex'), $revaluation_index);
            return null;
        }

        // Set contract info into the report CSV
        $this->setCurrentReportLineValue(self::RLH_CONTRACT_REVALUATION_INDEX, $this->getContractExtraFieldsLabel(self::EF_REVALUATION_INDEX, $revaluation_index));

        $billing_period_amount = null;

        // If has a revaluation
        if ($revaluation_index == 2 || $revaluation_index == 3) {
            // Get revaluation date
            $revaluation_date = $this->getRevaluationDate($contract);
            if (is_numeric($revaluation_date) && $revaluation_date < 0) {
                return null;
            }

            // Set contract info into the report CSV
            $this->setCurrentReportLineValue(self::RLH_CONTRACT_REVALUATION_DATE, $revaluation_date->toDateString());

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
                return null;
            }

            // Set info into the report CSV
            $this->setCurrentReportLineValue(self::RLH_FIRST_ABSOLUTE_REVALUATION_DATE, $first_absolute_revaluation_date->toDateString());

            // Get the date when enable to revaluation
            $enable_to_revaluation_date = null;
            $enable_to_revaluation_date_t = $contract->array_options['options_' . self::EF_ENABLE_TO_REVALUATION_DATE];
            if (!empty($enable_to_revaluation_date_t)) {
                $enable_to_revaluation_date = Carbon::createFromFormat('Y-m-d', $enable_to_revaluation_date_t);
                if (!isset($enable_to_revaluation_date)) {
                    $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractStartRevaluationDate'), $enable_to_revaluation_date_t);
                    return null;
                }

                // Set contract info into the report CSV
                $this->setCurrentReportLineValue(self::RLH_CONTRACT_ENABLE_TO_REVALUATION_DATE, $enable_to_revaluation_date->toDateString());
            }

            // Calculate revaluation
            if (isset($potential_revaluation_date) &&
                $potential_revaluation_date >= $first_absolute_revaluation_date &&
                (!isset($enable_to_revaluation_date) || $potential_revaluation_date >= $enable_to_revaluation_date) &&
                (empty($test_mode) || empty($disable_revaluation))
            ) {
                // Set info into the report CSV
                $this->setCurrentReportLineValue(self::RLH_POTENTIAL_REVALUATION_DATE, $potential_revaluation_date->toDateString());

                // Get split billing periods
                $first_billing_period_begin = $billing_period_begin->copy();
                $first_billing_period_end = $potential_revaluation_date->copy();
                $second_billing_period_begin = $potential_revaluation_date->copy();
                $second_billing_period_end = $billing_period_end->copy();

                // Get length periods
                $first_billing_period_lenght = $first_billing_period_begin->diffInDays($first_billing_period_end) + ($first_billing_period_begin->year != $first_billing_period_end->year && $first_billing_period_begin->month != $first_billing_period_end->month && $first_billing_period_begin->day != $first_billing_period_end->day ? 1 : 0);
                $second_billing_period_lenght = $second_billing_period_begin->diffInDays($second_billing_period_end) + 1;

                // Set info into the report CSV
                $this->setCurrentReportLineValue(self::RLH_FIRST_BILLING_PERIOD_BEGIN, $first_billing_period_begin->toDateString());
                $this->setCurrentReportLineValue(self::RLH_FIRST_BILLING_PERIOD_END, $first_billing_period_end->toDateString());
                $this->setCurrentReportLineValue(self::RLH_SECOND_BILLING_PERIOD_BEGIN, $second_billing_period_begin->toDateString());
                $this->setCurrentReportLineValue(self::RLH_SECOND_BILLING_PERIOD_END, $second_billing_period_end->toDateString());
                $this->setCurrentReportLineValue(self::RLH_FIRST_BILLING_PERIOD_LENGHT, $first_billing_period_lenght);
                $this->setCurrentReportLineValue(self::RLH_SECOND_BILLING_PERIOD_LENGHT, $second_billing_period_lenght);

                // Calculate amount first billing period
                // = ($first_billing_period_lenght / $watching_period_lenght) * $contract_amount / $number_billing_period_in_year
                $first_billing_period_amount = ($first_billing_period_lenght * $contract_amount) / ($watching_period_lenght * $number_billing_period_in_year);

                // Set info into the report CSV
                $this->setCurrentReportLineValue(self::RLH_FIRST_BILLING_PERIOD_AMOUNT, $first_billing_period_amount);

                // Get last revaluation index value used
                $last_revaluation_index_value_used = $this->getLastRevaluationIndexValueUsed($contract);
                if ($last_revaluation_index_value_used < 0) {
                    return null;
                }

                // Set contract info into the report CSV
                $this->setCurrentReportLineValue(self::RLH_CONTRACT_LAST_REVALUATION_INDEX_VALUE_USED, $last_revaluation_index_value_used);

                // Get revaluation index info
                $revaluation_index_info = $this->getRevaluationIndexInfo($contract, $potential_revaluation_date);
                if (!is_array($revaluation_index_info)) {
                    return null;
                }

                // Set info into the report CSV
                $this->setCurrentReportLineValue(self::RLH_REVALUATION_INDEX_INFO_INDEX, $revaluation_index_info['index']);
                $this->setCurrentReportLineValue(self::RLH_REVALUATION_INDEX_INFO_INDEX_VALUE, $revaluation_index_info['index_value']);
                $this->setCurrentReportLineValue(self::RLH_REVALUATION_INDEX_INFO_MONTH, $revaluation_index_info['month']);
                $this->setCurrentReportLineValue(self::RLH_REVALUATION_INDEX_INFO_YEAR, $revaluation_index_info['year']);

                // Get unauthorized deflation
                $unauthorized_deflation = $contract->array_options['options_' . self::EF_UNAUTHORIZED_DEFLATION];

                // Set contract info into the report CSV
                $this->setCurrentReportLineValue(self::RLH_CONTRACT_UNAUTHORIZED_DEFLATION, yn($unauthorized_deflation));

                // Calculate the revaluation
                if (!($revaluation_index_info['index_value'] < $last_revaluation_index_value_used && !empty($unauthorized_deflation))) {
                    $old_contract_amount = $contract->array_options['options_' . self::EF_CONTRACT_AMOUNT];
                    $old_last_revaluation_index_used = $contract->array_options['options_' . self::EF_LAST_REVALUATION_INDEX_USED];
                    $old_current_value_installation = $contract->array_options['options_' . self::EF_CURRENT_VALUE_INSTALLATION];

                    // Update 'last revaluation index value used' of the contract
                    if ($this->setLastRevaluationIndexUsed($contract, $revaluation_index_info['month'], $revaluation_index_info['year']) < 0)
                        return null;
                    // Update 'contract amount' of the contract
                    $contract_amount = $contract_amount * $revaluation_index_info['index_value'] / $last_revaluation_index_value_used;
                    $contract->array_options['options_' . self::EF_CONTRACT_AMOUNT] = $contract_amount;
                    // Update 'current value of the installation' of the contract
                    $contract->array_options['options_' . self::EF_CURRENT_VALUE_INSTALLATION] = $contract->array_options['options_' . self::EF_CURRENT_VALUE_INSTALLATION] * $revaluation_index_info['index_value'] / $last_revaluation_index_value_used;

                    $error = 0;

                    if (!empty($test_mode)) $this->db->begin();

                    // Update contract
                    $result = $contract->update($user);
                    if ($result < 0) {
                        $this->errors = array_merge($this->errors, $this->getObjectErrors($contract));
                        $error++;
                    }

                    // Create event with all information of this revaluation
                    if (!$error) {
                        $label = $langs->trans('STCContractRevaluationEventLabel', $contract->ref);
                        $message = $langs->trans('STCContractRevaluationData') . ' :<br>';
                        $message.= $langs->trans('STCWatchingPeriod') . ' : ' . $watching_period_begin->toDateString() . ' ' . $langs->trans('to') . ' ' . $watching_period_end->toDateString() . ' (' . $watching_period_lenght . ' ' . $langs->trans('days') . ')<br>';
                        $message.= $langs->trans('STCBillingPeriod') . ' : ' . $billing_period_begin->toDateString() . ' ' . $langs->trans('to') . ' ' . $billing_period_end->toDateString() . '<br>';
                        $message.= $langs->trans('STCRevaluationIndex') . ' : ' . $this->getContractExtraFieldsLabel(self::EF_REVALUATION_INDEX, $revaluation_index) . '<br>';
                        $message.= $langs->trans('STCRevaluationDate') . ' : ' . $revaluation_date->toDateString() . '<br>';
                        $message.= $langs->trans('STCPotentialRevaluationDate') . ' : ' . $potential_revaluation_date->toDateString() . '<br>';
                        $message.= $langs->trans('STCFirstAbsoluteRevaluationDate') . ' : ' . $first_absolute_revaluation_date->toDateString() . '<br>';
                        $message.= $langs->trans('STCEnableToRevaluationDate') . ' : ' . (isset($enable_to_revaluation_date) ? $enable_to_revaluation_date->toDateString() : '') . '<br>';
                        $message.= $langs->trans('STCFirstBillingPeriod') . ' : ' . $first_billing_period_begin->toDateString() . ' ' . $langs->trans('to') . ' ' . $first_billing_period_end->toDateString() . ' (' . $first_billing_period_lenght . ' ' . $langs->trans('days') . ')<br>';
                        $message.= $langs->trans('STCSecondBillingPeriod') . ' : ' . $second_billing_period_begin->toDateString() . ' ' . $langs->trans('to') . ' ' . $second_billing_period_end->toDateString() . ' (' . $second_billing_period_lenght . ' ' . $langs->trans('days') . ')<br>';
                        $message.= $langs->trans('STCLastRevaluationIndexValueUsed') . ' : ' . $last_revaluation_index_value_used . '<br>';
                        $message.= $langs->trans('STCRevaluationIndexInfo') . ' : ' . $revaluation_index_info['index'] . ', ' . $langs->trans('STCValue') . ': ' . $revaluation_index_info['index_value'] . ', ' . $langs->trans('STCMonth') . ': ' .$revaluation_index_info['month'] . ', ' . $langs->trans('STCYear') . ': ' .$revaluation_index_info['year'] . '<br>';
                        $message.= $langs->trans('STCUnauthorizedDeflation') . ' : ' . yn($unauthorized_deflation) . '<br>';
                        $message.= '<br>' . $langs->trans('STCContractRevaluationResult') . ' :<br>';
                        $message.= $langs->trans('STCContractOldContractAmount') . ' : ' . price($old_contract_amount) . '<br>';
                        $message.= $langs->trans('STCContractNewContractAmount') . ' : ' . price($contract->array_options['options_' . self::EF_CONTRACT_AMOUNT]) . '<br>';
                        $message.= $langs->trans('STCContractOldLastRevaluationIndexUsed') . ' : ' . $this->getContractOldMonthIndexLabel($old_last_revaluation_index_used) . '<br>';
                        $message.= $langs->trans('STCContractNewLastRevaluationIndexUsed') . ' : ' . $this->getContractOldMonthIndexLabel($contract->array_options['options_' . self::EF_LAST_REVALUATION_INDEX_USED]) . '<br>';
                        $message.= $langs->trans('STCContractOldCurrentValueInstallation') . ' : ' . price($old_current_value_installation) . '<br>';
                        $message.= $langs->trans('STCContractNewCurrentValueInstallation') . ' : ' . price($contract->array_options['options_' . self::EF_CURRENT_VALUE_INSTALLATION]) . '<br>';
                        $message.= '<br>' . $langs->trans('Author') . ' : ' . $user->login;

                        $result = $this->addEvent($contract, 'AC_STC_REVAL', $label, $message);
                        if ($result < 0) {
                            $error++;
                        }
                    }

                    // Set revaluation contract info into the report CSV
                    $this->setCurrentReportLineValue(self::RLH_CONTRACT_NEW_AMOUNT, $contract->array_options['options_' . self::EF_CONTRACT_AMOUNT]);
                    $this->setCurrentReportLineValue(self::RLH_CONTRACT_NEW_LAST_REVALUATION_INDEX_USED, $this->getContractOldMonthIndexLabel($contract->array_options['options_' . self::EF_LAST_REVALUATION_INDEX_USED]));
                    $this->setCurrentReportLineValue(self::RLH_CONTRACT_NEW_CURRENT_VALUE_INSTALLATION, $contract->array_options['options_' . self::EF_CURRENT_VALUE_INSTALLATION]);

                    if (!empty($test_mode)) $this->db->rollback();
                    if ($error) return null;
                }

                // Calculate amount second billing period
                // = ($contract_amount / $number_billing_period_in_year) * ($second_billing_period_lenght / $watching_period_lenght)
                $second_billing_period_amount = ($second_billing_period_lenght * $contract_amount) / ($watching_period_lenght * $number_billing_period_in_year);

                // Calculate the billing period amount
                $billing_period_amount = $first_billing_period_amount + $second_billing_period_amount;

                // Set info into the report CSV
                $this->setCurrentReportLineValue(self::RLH_SECOND_BILLING_PERIOD_AMOUNT, $second_billing_period_amount);
            }
        }

        // Calculate the billing period amount if not already calculated
        if (!isset($billing_period_amount)) {
            $billing_period_lenght = $billing_period_begin->diffInDays($billing_period_end) + 1;

            // Set info into the report CSV
            $this->setCurrentReportLineValue(self::RLH_BILLING_PERIOD_LENGHT, $billing_period_lenght);

            // = ($billing_period_lenght / $watching_period_lenght) * $contract_amount / $number_billing_period_in_year
            $billing_period_amount = ($billing_period_lenght * $contract_amount) / ($watching_period_lenght * $number_billing_period_in_year);
        }

        $rounding = !empty($conf->global->SYNERGIESTECHCONTRAT_MAX_DECIMALS_UNIT) ? $conf->global->SYNERGIESTECHCONTRAT_MAX_DECIMALS_UNIT : 'MU';
        $billing_period_amount = price2num($billing_period_amount, $rounding);

        // Set info into the report CSV
        $this->setCurrentReportLineValue(self::RLH_BILLING_PERIOD_AMOUNT, $billing_period_amount);

        if ($billing_period_amount < 0) {
            $this->errors[] = $langs->trans('STCErrorBadContractAmountCalculated', $billing_period_amount);
            return null;
        }

        return $billing_period_amount;
    }

    /**
     *  Set absolute discount of a invoice
     *
     * @param   Facture     $invoice        Invoice object
     * @param   int         $test_mode      Mode test (don't write in database)
     *
     * @return  int                         1: OK, -1: Errors
     */
    public function setAbsoluteDiscountAndCreditNote(&$invoice, $test_mode=0)
    {
        global $conf, $langs;

        $remise_absolue = 0;

        if (($invoice->statut == Facture::STATUS_DRAFT && $invoice->type != Facture::TYPE_CREDIT_NOTE && $invoice->type != Facture::TYPE_DEPOSIT) ||
            ($invoice->statut == Facture::STATUS_VALIDATED && $invoice->type != Facture::TYPE_CREDIT_NOTE)
        ) {
            // On recherche les remises
            $sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc, re.description, re.fk_facture_source";
            $sql .= " FROM " . MAIN_DB_PREFIX . "societe_remise_except as re";
            $sql .= " WHERE re.fk_soc = " . $invoice->socid;
            $sql .= " AND re.entity = " . $conf->entity;
            if (empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {    // Never use this
//                if ($invoice->statut == Facture::STATUS_DRAFT) { // absolute discount
//                    $sql .= " AND re.fk_facture_source IS NULL OR (re.fk_facture_source IS NOT NULL AND (re.description LIKE '(DEPOSIT)%' AND re.description NOT LIKE '(EXCESS RECEIVED)%'))";
//                } else { // credit note
//                    $sql .= " AND re.fk_facture_source IS NOT NULL AND (re.description NOT LIKE '(DEPOSIT)%' OR re.description LIKE '(EXCESS RECEIVED)%'))";
//                }
                $sql .= " AND re.fk_facture IS NULL AND re.fk_facture_line IS NULL";
            }
            $sql .= " ORDER BY re.description ASC";

            $resql = $this->db->query($sql);
            if (!$resql) {
                $this->errors[] = $langs->trans('STCErrorSQLGetAbsoluteDiscountAndCreditNote', $this->db->lasterror());
                dol_syslog(__METHOD__ . ': Error: SQL: "' . $sql . '", Message: "' . $this->db->lasterror() . '"', LOG_ERR);
                return -1;
            }

            if ($invoice->statut == Facture::STATUS_VALIDATED) { // credit note
                require_once DOL_DOCUMENT_ROOT . '/core/class/discount.class.php';
                $discount_static = new DiscountAbsolute($this->db);
            }

            if (!empty($test_mode)) $this->db->begin();

            $total_ttc = $invoice->statut == Facture::STATUS_DRAFT ? $invoice->total_ttc : $invoice->getRemainToPay(0);
            while ($obj = $this->db->fetch_object($resql)) {
                if ($invoice->statut == Facture::STATUS_DRAFT) { // absolute discount
                    if ($obj->amount_ttc <= $total_ttc) {
                        $result = $invoice->insert_discount($obj->rowid);
                        if ($result < 0) {
                            $this->errors = array_merge($this->errors, $this->getObjectErrors($invoice));
                            return -1;
                        }
                        $total_ttc -= $obj->amount_ttc;
                        $remise_absolue += $obj->amount_ht;
                    }
                } else { // credit note
                    if ($obj->amount_ttc <= $total_ttc) {
                        $discount_static->id = $obj->rowid;
                        $result = $discount_static->link_to_invoice(0, $invoice->id);
                        if ($result < 0) {
                            $this->errors = array_merge($this->errors, $this->getObjectErrors($invoice));
                            return -1;
                        }
                        $total_ttc -= $obj->amount_ttc;
                        $remise_absolue += $obj->amount_ht;
                    }
                }
            }

            if (!empty($test_mode)) $this->db->rollback();
        }

        return price2num($remise_absolue, 'MU');
    }

    /**
     *  Create invoice for the contract
     *
     * @param   Contrat     $contract                   Contract object
     * @param   array       $billing_period             Billing period
     * @param   double      $amount                     Amount of the invoice
     * @param   int         $payment_condition          Payment condition
     * @param   int         $payment_deadline_date      Payment deadline date
     * @param   string      $ref_customer               Ref customer
     * @param   int         $use_customer_discounts     Use customer discount
     * @param   int         $test_mode                  Mode test (don't write in database)
     *
     * @return  int                                     1: OK, -1: Errors
     */
    public function createInvoice(&$contract, $billing_period, $amount, $payment_condition=0, $payment_deadline_date=0, $ref_customer='', $use_customer_discounts=0, $test_mode=0)
    {
        global $conf, $langs, $user;

        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        $invoice = new Facture($this->db);

        $error = 0;
        $now = dol_now();
        $billing_period_begin = $billing_period['begin']->timestamp;
        $billing_period_end = $billing_period['end']->timestamp;

        // Set values
        $invoice->socid = $contract->socid;
        $invoice->fetch_thirdparty();
        $invoice->type = Facture::TYPE_STANDARD;
        $invoice->date = $now;
        $invoice->date_pointoftax = '';
        $invoice->note_public = '';
        $invoice->note_private = '';
        $invoice->ref_client = !empty($ref_customer) ? $ref_customer : '';
        $invoice->ref_int = '';
        $invoice->modelpdf = $conf->global->FACTURE_ADDON_PDF;
        $invoice->fk_project = '';
        $invoice->cond_reglement_id = $payment_condition > 0 ? $payment_condition : $invoice->thirdparty->cond_reglement_id;
        $invoice->mode_reglement_id = $invoice->thirdparty->mode_reglement_id;
        $invoice->fk_account = $invoice->thirdparty->fk_account;
        $invoice->remise_absolue = '';
        $invoice->remise_percent = $use_customer_discounts ? $invoice->thirdparty->remise_percent : '';
        $invoice->fk_incoterms = $invoice->thirdparty->fk_incoterms;
        $invoice->location_incoterms = $invoice->thirdparty->location_incoterms;
        $invoice->multicurrency_code = $invoice->thirdparty->multicurrency_code;
        $invoice->multicurrency_tx = '';
        $invoice->origin = $contract->element;
        $invoice->origin_id = $contract->id;
        $invoice->linkedObjectsIds[$contract->element] = $contract->id;

        if (empty($invoice->cond_reglement_id)) {
            $cond_payment_keys = array_keys($this->form->cache_conditions_paiements);
            $invoice->cond_reglement_id = $cond_payment_keys[0];
        }

        $invoice->array_options['options_datedeb'] = $billing_period_begin;
        $invoice->array_options['options_datefin'] = $billing_period_end;

        // Set general invoice info into the report CSV
        $this->setGeneralInvoiceInfoInCurrentReportLine($invoice);

        if (!empty($test_mode)) $this->db->begin();

        if (!$error) {
            $invoice_id = $invoice->create($user, 0, $payment_deadline_date);
            if ($invoice_id < 0) {
                $this->errors = array_merge($this->errors, $this->getObjectErrors($invoice));
                $error++;
            }
        }

        // Insert lines of the contract
        if (!$error) {
            $contract->fetch_lines();
            // Specific Synergies-Tech - Begin
            if (empty($contract->array_options)) {
                $contract->fetch_optionals();
            }
            require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
            $extrafields = new ExtraFields($this->db);
            $extrafields->fetch_name_optionals_label($contract->element);
            // Specific Synergies-Tech - End

            // Set invoice info into the report CSV
            $this->setCurrentReportLineValue(self::RLH_INVOICE_ID, $invoice->id);

            $fk_parent_line = 0;
            $i = 0;
            foreach ($contract->lines as $line) {
                $label = (!empty($line->label) ? $line->label : (!empty($line->libelle) ? $line->libelle : ''));
                $desc = (!empty($line->desc) ? $line->desc : '');

                // Specific Synergies-Tech - Begin
                $desc = dol_concatdesc($desc, $langs->trans('STCContractLineExtraDesc') . ' : ' . $extrafields->showOutputField(self::EF_CONTRACT_FORMULA, $contract->array_options['options_' . self::EF_CONTRACT_FORMULA]));
                // Specific Synergies-Tech - End

                // Positive line
                $product_type = ($line->product_type ? $line->product_type : 0);

                // Reset fk_parent_line for no child products and special product
                if (($line->product_type != 9 && empty($line->fk_parent_line)) || $line->product_type == 9) {
                    $fk_parent_line = 0;
                }

                // Extrafields
                if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) {
                    $line->fetch_optionals($line->id);
                    $array_options = $line->array_options;
                }

                $tva_tx = $line->tva_tx;
                if (!empty($line->vat_src_code) && !preg_match('/\(/', $tva_tx)) $tva_tx .= ' (' . $line->vat_src_code . ')';

                // View third's localtaxes for NOW and do not use value from origin.
                $localtax1_tx = get_localtax($tva_tx, 1, $invoice->thirdparty);
                $localtax2_tx = get_localtax($tva_tx, 2, $invoice->thirdparty);
                $qty = 1;
                $result = $invoice->addline($desc, $i ? 0 : $amount, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $line->fk_product, $invoice->remise_percent,
                    $billing_period_begin, $billing_period_end, 0, $line->info_bits, $line->fk_remise_except, 'HT', 0, $product_type, $line->rang, $line->special_code, $contract->element,
                    $line->id, $fk_parent_line, $line->fk_fournprice, $i ? 0 : $amount, $label, $array_options, $line->situation_percent, $line->fk_prev_id,
                    $line->fk_unit);

                if ($result < 0) {
                    $this->errors = array_merge($this->errors, $this->getObjectErrors($invoice));
                    $error++;
                    break;
                }
                $i++;
            }
        }

        $remise_absolue = 0;
        if (!$error && $use_customer_discounts) {
            $result = $this->setAbsoluteDiscountAndCreditNote($invoice, $test_mode);
            if ($result < 0) {
                $error++;
            } else {
                $remise_absolue += $result;
            }
        }

        // Auto validate
        $validated = 0;
        if (!$error && !empty($conf->global->AUTOMATIC_VALID_INVOICE_CONTRACT)) {
            $result = $invoice->validate($user);
            if ($result < 0) {
                $this->errors = array_merge($this->errors, $this->getObjectErrors($invoice));
                $error++;
            } else {
                $validated = 1;

//                if ($use_customer_discounts) {
//                    $result = $this->setAbsoluteDiscountAndCreditNote($invoice, $test_mode);
//                    if ($result < 0) {
//                        $error++;
//                    } else {
//                        $remise_absolue += $result;
//                    }
//                }
            }
        }

        // Update general invoice info into the report CSV
        if (!$error) {
            $invoice->fetch($invoice_id);
            $this->setGeneralInvoiceInfoInCurrentReportLine($invoice);

            // Set invoice info into the report CSV
            $this->setCurrentReportLineValue(self::RLH_INVOICE_REF, $invoice->ref);
            $this->setCurrentReportLineValue(self::RLH_INVOICE_VALIDATED, yn($validated));
            $this->setCurrentReportLineValue(self::RLH_INVOICE_REMISE_ABSOLUE, $remise_absolue);
        }

        if (!$error) {
            $contract->context['ec_create_invoice'] = array(
                'invoice' => &$invoice,
                'billing_period' => &$billing_period,
                'amount' => &$amount,
                'payment_condition' => &$payment_condition,
                'payment_deadline_date' => &$payment_deadline_date,
                'ref_customer' => &$ref_customer,
                'use_customer_discounts' => &$use_customer_discounts,
                'test_mode' => &$test_mode
            );
            $result = $contract->call_trigger('CONTRACT_EC_CREATE_INVOICE', $user);
            if ($result < 0) {
                $this->errors[] = $contract->errorsToString();
                $error++;
            }
            unset($contract->context['ec_create_invoice']);
        }

        if (!empty($test_mode)) $this->db->rollback();
        if ($error) return -1;

        return 1;
    }

    /**
     *  Renewal contract
     *
     * @param   Contrat     $contract               Contract object
     * @param   array       $billing_period         Billing period
     * @param   int         $test_mode              Mode test (don't write in database)
     *
     * @return  int                                 >0: OK, -1: Errors
     */
    public function renewalContract(&$contract, $billing_period, $test_mode=0) {
        global $conf, $langs, $user;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        // Get effective date
        $effective_date = $this->getEffectiveDate($contract);
        if (is_numeric($effective_date) && $effective_date < 0) {
            return -1;
        }

        // Get watching and bill periods
        $billing_period_begin = $billing_period['begin']->copy();
        $billing_period_end = $billing_period['end']->copy();

        $error = 0;
        if (!empty($test_mode)) $this->db->begin();

        // Create event with all information of this contract renewal (Renouvellement)
        if (!$error) {
            // Check if the contract is renewed into the billing period
            $birthday_date = $effective_date->copy();
            $renewed = false;
            $nbPeriod = 0;
            do {
                $nbPeriod++;
                $birthday_date->addYear();
                if ($billing_period_begin <= $birthday_date && $birthday_date <= $billing_period_end) {
                    $renewed = true;
                    break;
                }
            } while ($birthday_date < $billing_period_end);

            if ($renewed) {
                $label = $langs->trans('STCContractRenewalEventLabel', $contract->ref);
                $message = $langs->trans('STCContractRenewalEventDescription', $contract->ref, $birthday_date->toDateString(), $nbPeriod, $effective_date->toDateString()) . '<br>';
                $message .= '<br>' . $langs->trans('STCContractExtraFields') . ' :<br>';
                $message .= $this->getAllExtraFieldsToString($contract);
                $message .= '<br>' . $langs->trans('Author') . ' : ' . $user->login;

                $result = $this->addEvent($contract, 'AC_STC_CRENE', $label, $message);
                if ($result < 0) {
                    $error++;
                }

                if (!$error) {
                    $result = $contract->call_trigger('CONTRACT_EC_CRENE', $user);
                    if ($result < 0) {
                        $this->errors[] = $contract->errorsToString();
                        $error++;
                    }
                }
            }
        }

        // Create event with all information of the renewal of the contract (Reconduction)
        if (!$error) {
            // Get contract duration in number of months
            $contract_duration = $contract->array_options['options_' . self::EF_CONTRACT_DURATION];

            // Check if the contract is renewed into the billing period
            $renewed_date = $effective_date->copy();
            $renewed = false;
            $nbPeriod = 0;
            do {
                $nbPeriod++;
                $renewed_date->addMonths($contract_duration);
                if ($billing_period_begin <= $renewed_date && $renewed_date <= $billing_period_end) {
                    $renewed = true;
                    break;
                }
            } while ($renewed_date < $billing_period_end);

            if ($renewed) {
                // Get contract duration in number of months
                $tacit_renewal = $contract->array_options['options_' . self::EF_TACIT_RENEWAL];

                if ($tacit_renewal) {
                    $label = $langs->trans('STCRenewalOfTheContractEventLabel', $contract->ref);
                    $message = $langs->trans('STCRenewalOfTheContractEventDescription', $contract->ref, $renewed_date->addMonths($contract_duration)->toDateString()) . '<br>';
                    $message .= '<br>' . $langs->trans('STCContractExtraFields'). ' :<br>';
                    $message .= $this->getAllExtraFieldsToString($contract);
                    $message .= '<br>' . $langs->trans('Author') . ' : ' . $user->login;

                    // Update ref contract
                    $old_ref = $contract->ref;
                    if ($pos = strrpos($contract->ref, '_')) {
                        $contract->ref = substr($contract->ref, 0, $pos) . '_' . $nbPeriod;
                    } else {
                        $contract->ref = $contract->ref . "_1";
                    }
                    if ($contract->update() > 0) {
                        // Rename of contract directory to  not lose the linked files
                        $old_ref = dol_sanitizeFileName($old_ref);
                        $new_ref = dol_sanitizeFileName($contract->ref);
                        if ($old_ref != $new_ref) {
                            $dirsource = $conf->contrat->dir_output . '/' . $old_ref;
                            $dirdest = $conf->contrat->dir_output . '/' . $new_ref;
                            if (file_exists($dirsource)) {
                                @rename($dirsource, $dirdest);
                            }
                        }
                    } else {
                        $this->errors = array_merge($this->errors, $this->getObjectErrors($contract));
                        $error++;
                    }
                } else {
                    $label = $langs->trans('STCRenewalOfTheContractEndedEventLabel', $contract->ref);
                    $message = $langs->trans('STCRenewalOfTheContractEventEndedDescription', $contract->ref, $renewed_date->toDateString()) . '<br>';
                    $message .= '<br>' . $langs->trans('Author') . ' : ' . $user->login;
                }

                $result = $this->addEvent($contract, 'AC_STC_RENEC', $label, $message);
                if ($result < 0) {
                    $error++;
                }

                if (!$error) {
                    $result = $contract->call_trigger('CONTRACT_EC_RENEC', $user);
                    if ($result < 0) {
                        $this->errors[] = $contract->errorsToString();
                        $error++;
                    }
                }
            }
        }

        if (!empty($test_mode)) $this->db->rollback();

        if ($error)
            return -1;
        else
            return 1;
    }

    /**
     *  Generate invoice for the contract at the given watching date
     *
     * @param   Contrat     $contract                           Contract object
     * @param   int         $watching_date                      Watching date
     * @param   int         $payment_condition                  Payment condition
     * @param   int         $payment_deadline_date              Payment deadline date
     * @param   string      $ref_customer                       Ref customer
     * @param   int         $use_customer_discounts             Use customer discount
     * @param   int         $test_mode                          Mode test (don't write in database)
     * @param   int         $disable_revaluation                Disabled revaluation (option only taken into account in test mode)
     *
     * @return  int                                     1: OK, 0: None, -1: Errors
     */
    public function generateInvoiceForTheContract(&$contract, $watching_date, $payment_condition=0, $payment_deadline_date=0, $ref_customer='', $use_customer_discounts=0, $test_mode=0, $disable_revaluation=0) {
        global $langs;

        $error = 0;
        $pass = 0;
        $this->clearErrors();

        // Set parameters info into the report CSV
        $this->setCurrentReportLineValue(self::RLH_PARAM_PAYMENT_CONDITION_ID, $payment_condition);
        $this->setCurrentReportLineValue(self::RLH_PARAM_PAYMENT_DEADLINE_DATE, dol_print_date($payment_deadline_date, 'day'));
        $this->setCurrentReportLineValue(self::RLH_PARAM_REF_CUSTOMER, $ref_customer);
        $this->setCurrentReportLineValue(self::RLH_PARAM_USE_CUSTOMER_DISCOUNTS, yn($use_customer_discounts));
        $this->setCurrentReportLineValue(self::RLH_PARAM_TEST_MODE, yn($test_mode));
        $this->setCurrentReportLineValue(self::RLH_PARAM_DISABLE_REVALUATION, yn($disable_revaluation));
        $this->setCurrentReportLineValue(self::RLH_WATCHING_DATE, dol_print_date($watching_date, 'day'));

        // Set general contract info into the report CSV
        $this->setGeneralContractInfoInCurrentReportLine($contract);

        // Get watching period
        $watching_period = $this->getWatchingPeriod($contract, $watching_date);
        if (!isset($watching_period)) {
            $error++;
        }

        // Get billing period
        if (!$error) {
            // Set watching period into the report CSV
            $this->setCurrentReportLineValue(self::RLH_WATCHING_PERIOD_BEGIN, $watching_period['begin']->toDateString());
            $this->setCurrentReportLineValue(self::RLH_WATCHING_PERIOD_END, $watching_period['end']->toDateString());

            $billing_period = $this->getBillingPeriod($contract, $watching_period);
            if (!isset($billing_period)) {
                $error++;
            } elseif (count($billing_period) == 0) {
                $this->setCurrentReportLineValue(self::RLH_ERRORS, $langs->trans('STCErrorNoBillingPeriod'));
                $pass++;
            }
        }

        // Check if invoice already exist on the billing period
        if (!$error && !$pass) {
            // Set billing period into the report CSV
            $this->setCurrentReportLineValue(self::RLH_BILLING_PERIOD_BEGIN, $billing_period['begin']->toDateString());
            $this->setCurrentReportLineValue(self::RLH_BILLING_PERIOD_END, $billing_period['end']->toDateString());

            $invoices = $this->getInvoicesOnThePeriod($contract, $billing_period);
            if (!isset($invoices)) {
                $error++;
            } elseif (count($invoices) > 0) {
                $this->errors[] = $langs->trans('STCErrorHaveAlreadyInvoicesOnTheBillingPeriod');
                foreach ($invoices as $invoice) {
                    $this->errors[] = $langs->trans('STCErrorInvoiceOnBillingPeriodInfo',
                        $invoice->ref,
                        dol_print_date($invoice->array_options['options_datedeb'], 'day'),
                        dol_print_date($invoice->array_options['options_datefin'], 'day'),
                        price($invoice->total_ht) . 'HT');
                }
                $error++;
            }
        }

        // Renewal contract
        if (!$error && !$pass) {
            $result = $this->renewalContract($contract, $billing_period, $test_mode);
            if ($result < 0) {
                $error++;
            }
        }

        // Get amount of the invoice of the contract for the given bill period
        if (!$error && !$pass) {
            $invoice_amount = $this->getInvoiceAmount($contract, $watching_period, $billing_period, $test_mode, $disable_revaluation);
            if (!isset($invoice_amount)) {
                $error++;
            } elseif ($invoice_amount == 0) {
                $this->setCurrentReportLineValue(self::RLH_ERRORS, $langs->trans('STCErrorNoAmountForThisInvoice'));
                $pass++;
            }
        }

        // Create the invoice
        if (!$error && !$pass) {
            $result = $this->createInvoice($contract, $billing_period, $invoice_amount, $payment_condition, $payment_deadline_date, $ref_customer, $use_customer_discounts, $test_mode);
            if ($result < 0) {
                $error++;
            }
        }

        if ($error) {
            $this->setCurrentReportLineValue(self::RLH_ERRORS, implode("\n", $this->errors));
        }

        // Add current report line into the file
        $this->addCurrentReportLine();

        if ($error) {
            return -1;
        } elseif ($pass) {
            return 0;
        }

        return 1;
    }

    /**
     *  Generate invoices for the contract at the given watching period
     *
     * @param   Contrat     $contract                   Contract object
     * @param   int         $watching_period_begin      Watching period begin
     * @param   int         $watching_period_end        Watching period end
     * @param   int         $payment_condition          Payment condition
     * @param   int         $payment_deadline_date      Payment deadline date
     * @param   string      $ref_customer               Ref customer
     * @param   int         $use_customer_discounts     Use customer discount
     * @param   int         $test_mode                  Mode test (don't write in database)
     * @param   int         $disable_revaluation        Disabled revaluation (option only taken into account in test mode)
     *
     * @return  int                                     1: OK, 0: None, -1: Errors
     */
    public function generateInvoicesForTheContractInPeriod(&$contract, $watching_period_begin, $watching_period_end, $payment_condition=0, $payment_deadline_date=0, $ref_customer='', $use_customer_discounts=0, $test_mode=0, $disable_revaluation=0) {
        global $langs;

        if (empty($contract->array_options)) {
            $contract->fetch_optionals();
        }

        $watching_period_begin = Carbon::createFromTimestamp($watching_period_begin);
        $watching_period_end = Carbon::createFromTimestamp($watching_period_end);

        // Set parameters info into the report CSV
        $this->setCurrentReportLineValue(self::RLH_PARAM_BEGIN_WATCHING_DATE, $watching_period_begin->toDateString());
        $this->setCurrentReportLineValue(self::RLH_PARAM_END_WATCHING_DATE, $watching_period_end->toDateString());
        $this->setCurrentReportLineValue(self::RLH_PARAM_PAYMENT_CONDITION_ID, $payment_condition);
        $this->setCurrentReportLineValue(self::RLH_PARAM_PAYMENT_DEADLINE_DATE, dol_print_date($payment_deadline_date, 'day'));
        $this->setCurrentReportLineValue(self::RLH_PARAM_REF_CUSTOMER, $ref_customer);
        $this->setCurrentReportLineValue(self::RLH_PARAM_USE_CUSTOMER_DISCOUNTS, yn($use_customer_discounts));
        $this->setCurrentReportLineValue(self::RLH_PARAM_TEST_MODE, yn($test_mode));
        $this->setCurrentReportLineValue(self::RLH_PARAM_DISABLE_REVALUATION, yn($disable_revaluation));

        // Set general contract info into the report CSV
        $this->setGeneralContractInfoInCurrentReportLine($contract);

        // Get number of bill period in a year
        $frequency_of_billing = $contract->array_options['options_' . self::EF_FREQUENCY_BILLING];
        switch (intval($frequency_of_billing)) {
            // Monthly
            case 1: $number_billing_period_in_year = 1; break;
            // Quarterly
            case 2: $number_billing_period_in_year = 3; break;
            // Semi
            case 3: $number_billing_period_in_year = 6; break;
            // Annual
            case 4: $number_billing_period_in_year = 12; break;
            default:
                $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractFrequencyBilling'), $frequency_of_billing);
                return -1;
        }

        $nbGenerated = 0;
        $last = false;
        $last_watching_date = $watching_period_begin->copy();
        $watching_date = $watching_period_begin->copy();
        do {
            if ($watching_date > $watching_period_end) {
                // Get first watching period
                $first_watching_period = $this->getWatchingPeriod($contract, $last_watching_date->timestamp);
                if (!isset($first_watching_period)) {
                    return -1;
                }
                // Get second watching period
                $second_watching_period = $this->getWatchingPeriod($contract, $watching_period_end->timestamp);
                if (!isset($second_watching_period)) {
                    return -1;
                }
                if ($first_watching_period['begin'] == $second_watching_period['begin'] && $first_watching_period['end'] == $second_watching_period['end']) break;

                $watching_date = $watching_period_end->copy();
                $last = true;
            }

            // Set parameters info into the report CSV
            $this->setCurrentReportLineValue(self::RLH_PARAM_BEGIN_WATCHING_DATE, $watching_period_begin->toDateString());
            $this->setCurrentReportLineValue(self::RLH_PARAM_END_WATCHING_DATE, $watching_period_end->toDateString());

            $result = $this->generateInvoiceForTheContract($contract, $watching_date->timestamp, $payment_condition, $payment_deadline_date, $ref_customer, $use_customer_discounts, $test_mode, $disable_revaluation);
            if ($result < 0) {
                return -1;
            } elseif ($result > 0) {
                $nbGenerated++;
            }

            $last_watching_date = $watching_date->copy();
            $watching_date->addMonths($number_billing_period_in_year);
        } while ($watching_date > $watching_period_end && !$last);

        return $nbGenerated;
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
        $effective_date_t = $contract->array_options['options_' . self::EF_EFFECTIVE_DATE];
        $effective_date = null;
        if (!empty($effective_date_t)) {
            $effective_date = Carbon::createFromFormat('Y-m-d', $effective_date_t);
        }
        if (!isset($effective_date)) {
            $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractEffectiveDate'), $effective_date_t);
            return -1;
        }

        return $effective_date;
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
        $revaluation_date = $contract->array_options['options_' . self::EF_REVALUATION_DATE];
        switch (intval($revaluation_date)) {
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
                $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractRevaluationDate'), $revaluation_date);
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
        $last_revaluation_index_used = $contract->array_options['options_' . self::EF_LAST_REVALUATION_INDEX_USED];
        if (empty($last_revaluation_index_used)) {
            $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractLastRevaluationIndexUsed'), $last_revaluation_index_used);
            return -1;
        }

        // Get last revaluation index value used
        $sql = "SELECT indice FROM " . MAIN_DB_PREFIX . "view_c_indice" .
            " WHERE rowid = '" . $this->db->escape($last_revaluation_index_used) . "'";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = $langs->trans('STCErrorSQLGetLastRevaluationIndexValueUsed', $this->db->lasterror());
            dol_syslog(__METHOD__ . ': Error: SQL: "' . $sql . '", Message: "' . $this->db->lasterror() .'"', LOG_ERR);
            return -1;
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        if ($obj) {
            if ($obj->indice <= 0) {
                $this->errors[] = $langs->trans('STCErrorBadLastRevaluationIndexValueUsed', $obj->indice);
                return -1;
            }

            return $obj->indice;
        } else {
            $this->errors[] = $langs->trans('STCErrorLastRevaluationIndexValueUsedNotFound');
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
        $revaluation_index = $contract->array_options['options_' . self::EF_REVALUATION_INDEX];
        switch (intval($revaluation_index)) {
            // Syntec
            case 2: $filter = 'Syntec'; break;
            // Insee
            case 3: $filter = 'Insee'; break;
            default:
                $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractRevaluationIndex'), $revaluation_index);
                return -1;
        }

        // Search last revaluation index value used into the table
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "view_c_indice" .
            " WHERE month_indice = '" . $this->db->escape($month) . "'" .
            " AND year_indice = '" . $this->db->escape($year) . "'" .
            " AND filter = '" . $this->db->escape($filter) . "'";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = $langs->trans('STCErrorSQLSetLastRevaluationIndexUsed', $this->db->lasterror());
            dol_syslog(__METHOD__ . ': Error: SQL: "' . $sql . '", Message: "' . $this->db->lasterror() .'"', LOG_ERR);
            return -1;
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        if ($obj) {
            $contract->array_options['options_' . self::EF_LAST_REVALUATION_INDEX_USED] = $obj->rowid;
            return 1;
        } else {
            $this->errors[] = $langs->trans('STCErrorLastRevaluationIndexUsedNotFound', $filter, $month, $year);
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
        $month_for_new_revaluation_index = $contract->array_options['options_' . self::EF_MONTH_FOR_NEW_REVALUATION_INDEX];
        if (!is_numeric($month_for_new_revaluation_index) ||
            (!empty($month_for_new_revaluation_index) && ($month_for_new_revaluation_index < 1 || 12 < $month_for_new_revaluation_index))) {
            $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractMonthForNewRevaluationIndex'), $month_for_new_revaluation_index);
            return -1;
        }

        // Get revaluation index
        $revaluation_index = $contract->array_options['options_' . self::EF_REVALUATION_INDEX];
        switch (intval($revaluation_index)) {
            // Syntec
            case 2: $suffix_table = 'syntec'; break;
            // Insee
            case 3: $suffix_table = 'insee'; break;
            default:
                $this->errors[] = $langs->trans('STCErrorBadFieldValue', $langs->transnoentitiesnoconv('STCContractRevaluationIndex'), $revaluation_index);
                return -1;
        }

        $limit_up = ($potential_revaluation_date->year - ($month_for_new_revaluation_index > $potential_revaluation_date->month ? 1 : 0)). "-" . $month_for_new_revaluation_index;
        $limit_down = ($potential_revaluation_date->year - 1) . "-" . $potential_revaluation_date->month;

        // Todo to check for 'month used for get the new revaluation index' and 'potential revaluation date' used in the sql request
        $sql = "SELECT indice, year_indice, month_indice FROM " . MAIN_DB_PREFIX . "c_indice_".$suffix_table .
            " WHERE active = 1" .
            " AND STR_TO_DATE(CONCAT(year_indice, '-', month_indice), '%Y-%m') <= STR_TO_DATE('" . $this->db->escape($limit_up) . "', '%Y-%m')" .
            " AND STR_TO_DATE(CONCAT(year_indice, '-', month_indice), '%Y-%m') >= STR_TO_DATE('" . $this->db->escape($limit_down) . "', '%Y-%m')" .
            " ORDER BY year_indice DESC, month_indice DESC" .
            " LIMIT 1";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = $langs->trans('STCErrorSQLGetRevaluationIndexInfo', $this->db->lasterror());
            dol_syslog(__METHOD__ . ': Error: SQL: "' . $sql . '", Message: "' . $this->db->lasterror() .'"', LOG_ERR);
            return -1;
        }

        $obj = $this->db->fetch_object($resql);
        $this->db->free($resql);

        if ($obj) {
            if ($obj->indice <= 0) {
                $this->errors[] = $langs->trans('STCErrorBadRevaluationIndexInfo', ucfirst($suffix_table), $obj->month_indice, $obj->year_indice, $obj->indice);
                return -1;
            }

            return array('index' => ucfirst($suffix_table), 'index_value' => $obj->indice, 'month' => $obj->month_indice, 'year' => $obj->year_indice);
        } else {
            $this->errors[] = $langs->trans('STCErrorRevaluationIndexInfoNotFound', ucfirst($suffix_table), $limit_down, $limit_up);
            return -1;
        }
    }

    /**
     *  Get all invoices on the given period
     *
     * @param   Contrat     $contract   Contract object
     * @param   array       $period     Period
     * @param   string      $mode       Return invoice object if equal 'object' or invoice ID if equal 'id'
     *
     * @return  array                   List of invoice on the given period, null: Errors
     */
    public function getInvoicesOnThePeriod(&$contract, $period, $mode='object')
    {
        global $langs;

        $period_begin = $period['begin']->timestamp;
        $period_end = $period['begin']->timestamp;

        $sql = "SELECT f.rowid FROM " . MAIN_DB_PREFIX . "facture AS f" .
            " INNER JOIN " . MAIN_DB_PREFIX . "element_element AS ee ON ee.fk_source = " . $contract->id . " AND ee.sourcetype = 'contrat' AND ee.fk_target = f.rowid AND ee.targettype = 'facture'" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "facture_extrafields AS fef ON fef.fk_object = f.rowid" .
            " WHERE f.entity IN (" . getEntity('facture') . ")" .
            " AND (" .
            "   (fef.datedeb <= '" . $this->db->idate($period_begin) . "' AND '" . $this->db->idate($period_begin) . "' < fef.datefin)" .
            "   OR (fef.datedeb < '" . $this->db->idate($period_end) . "' AND '" . $this->db->idate($period_end) . "' <= fef.datefin)" .
            " )";

        $resql = $this->db->query($sql);
        if (!$resql) {
            $this->errors[] = $langs->trans('STCErrorSQLGetInvoicesOnThePeriod', $this->db->lasterror());
            dol_syslog(__METHOD__ . ': Error: SQL: "' . $sql . '", Message: "' . $this->db->lasterror() .'"', LOG_ERR);
            return null;
        }

        $invoices = array();
        if ($mode == 'object') {
            require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
            while ($obj = $this->db->fetch_object($resql)) {
                $invoice = new Facture($this->db);
                $invoice->fetch($obj->rowid);
                $invoices[$obj->rowid] = $invoice;
            }
        } elseif ($mode == 'id') {
            while ($obj = $this->db->fetch_object($resql)) {
                $invoices[$obj->rowid] = $obj->rowid;
            }
        }

        $this->db->free($resql);

        return $invoices;
    }

    /************************************************************************************
     * Report file functions
     ************************************************************************************/

    /**
     *  Create report file
     *
     * @param   string          $dir                        Directory path of the contracts report file
     * @param   int             $watching_period_begin      Watching period begin
     * @param   int             $watching_period_end        Watching period end
     * @return  bool
     */
    public function createReportFiles($dir, $watching_period_begin, $watching_period_end)
    {
        global $langs;

        if ($this->report_file) {
            $this->closeReportFile();
        }

        if (!file_exists($dir)) {
            dol_mkdir($dir);
        }

        if (file_exists($dir)) {
            $now = dol_now();
            $to = $watching_period_begin != $watching_period_end ? ' ' . $langs->transnoentitiesnoconv('to') . ' ' . dol_print_date($watching_period_end, 'dayrfc') : '';
            $fileName = dol_sanitizeFileName($langs->transnoentitiesnoconv('STCReportFileName', dol_print_date($watching_period_begin, 'dayrfc'), $to, dol_print_date($now, 'standard')). '.csv');
            $file_path = $dir . '/' . $fileName;
            $this->report_file = @fopen($file_path, "w");
            if ($this->report_file) {
                // Add headers
                $this->current_report_line_modified = true;
                $this->current_report_line = array(
                    self::RLH_PARAM_BEGIN_WATCHING_DATE => $langs->transnoentitiesnoconv('STC_RLH_PARAM_BEGIN_WATCHING_DATE'),
                    self::RLH_PARAM_END_WATCHING_DATE => $langs->transnoentitiesnoconv('STC_RLH_PARAM_END_WATCHING_DATE'),
                    self::RLH_PARAM_PAYMENT_CONDITION_ID => $langs->transnoentitiesnoconv('STC_RLH_PARAM_PAYMENT_CONDITION_ID'),
                    self::RLH_PARAM_PAYMENT_DEADLINE_DATE => $langs->transnoentitiesnoconv('STC_RLH_PARAM_PAYMENT_DEADLINE_DATE'),
                    self::RLH_PARAM_REF_CUSTOMER => $langs->transnoentitiesnoconv('STC_RLH_PARAM_REF_CUSTOMER'),
                    self::RLH_PARAM_USE_CUSTOMER_DISCOUNTS => $langs->transnoentitiesnoconv('STC_RLH_PARAM_USE_CUSTOMER_DISCOUNTS'),
                    self::RLH_PARAM_TEST_MODE => $langs->transnoentitiesnoconv('STC_RLH_PARAM_TEST_MODE'),
                    self::RLH_PARAM_DISABLE_REVALUATION => $langs->transnoentitiesnoconv('STC_RLH_PARAM_DISABLE_REVALUATION'),
                    self::RLH_CONTRACT_ID => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_ID'),
                    self::RLH_CONTRACT_REF => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_REF'),
                    self::RLH_WATCHING_DATE => $langs->transnoentitiesnoconv('STC_RLH_WATCHING_DATE'),
                    self::RLH_WATCHING_PERIOD_BEGIN => $langs->transnoentitiesnoconv('STC_RLH_WATCHING_PERIOD_BEGIN'),
                    self::RLH_WATCHING_PERIOD_END => $langs->transnoentitiesnoconv('STC_RLH_WATCHING_PERIOD_END'),
                    self::RLH_BILLING_PERIOD_BEGIN => $langs->transnoentitiesnoconv('STC_RLH_BILLING_PERIOD_BEGIN'),
                    self::RLH_BILLING_PERIOD_END => $langs->transnoentitiesnoconv('STC_RLH_BILLING_PERIOD_END'),
                    self::RLH_WATCHING_PERIOD_LENGHT => $langs->transnoentitiesnoconv('STC_RLH_WATCHING_PERIOD_LENGHT'),
                    self::RLH_CONTRACT_REVALUATION_INDEX => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_REVALUATION_INDEX'),
                    self::RLH_CONTRACT_REVALUATION_DATE => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_REVALUATION_DATE'),
                    self::RLH_POTENTIAL_REVALUATION_DATE => $langs->transnoentitiesnoconv('STC_RLH_POTENTIAL_REVALUATION_DATE'),
                    self::RLH_FIRST_ABSOLUTE_REVALUATION_DATE => $langs->transnoentitiesnoconv('STC_RLH_FIRST_ABSOLUTE_REVALUATION_DATE'),
                    self::RLH_CONTRACT_ENABLE_TO_REVALUATION_DATE => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_ENABLE_TO_REVALUATION_DATE'),
                    self::RLH_FIRST_BILLING_PERIOD_BEGIN => $langs->transnoentitiesnoconv('STC_RLH_FIRST_BILLING_PERIOD_BEGIN'),
                    self::RLH_FIRST_BILLING_PERIOD_END => $langs->transnoentitiesnoconv('STC_RLH_FIRST_BILLING_PERIOD_END'),
                    self::RLH_FIRST_BILLING_PERIOD_LENGHT => $langs->transnoentitiesnoconv('STC_RLH_FIRST_BILLING_PERIOD_LENGHT'),
                    self::RLH_SECOND_BILLING_PERIOD_BEGIN => $langs->transnoentitiesnoconv('STC_RLH_SECOND_BILLING_PERIOD_BEGIN'),
                    self::RLH_SECOND_BILLING_PERIOD_END => $langs->transnoentitiesnoconv('STC_RLH_SECOND_BILLING_PERIOD_END'),
                    self::RLH_SECOND_BILLING_PERIOD_LENGHT => $langs->transnoentitiesnoconv('STC_RLH_SECOND_BILLING_PERIOD_LENGHT'),
                    self::RLH_CONTRACT_LAST_REVALUATION_INDEX_VALUE_USED => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_LAST_REVALUATION_INDEX_VALUE_USED'),
                    self::RLH_REVALUATION_INDEX_INFO_INDEX => $langs->transnoentitiesnoconv('STC_RLH_REVALUATION_INDEX_INFO_INDEX'),
                    self::RLH_REVALUATION_INDEX_INFO_INDEX_VALUE => $langs->transnoentitiesnoconv('STC_RLH_REVALUATION_INDEX_INFO_INDEX_VALUE'),
                    self::RLH_REVALUATION_INDEX_INFO_MONTH => $langs->transnoentitiesnoconv('STC_RLH_REVALUATION_INDEX_INFO_MONTH'),
                    self::RLH_REVALUATION_INDEX_INFO_YEAR => $langs->transnoentitiesnoconv('STC_RLH_REVALUATION_INDEX_INFO_YEAR'),
                    self::RLH_CONTRACT_UNAUTHORIZED_DEFLATION => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_UNAUTHORIZED_DEFLATION'),
                    self::RLH_CONTRACT_AMOUNT => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_AMOUNT'),
                    self::RLH_CONTRACT_NEW_AMOUNT => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_NEW_AMOUNT'),
                    self::RLH_CONTRACT_LAST_REVALUATION_INDEX_USED => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_LAST_REVALUATION_INDEX_USED'),
                    self::RLH_CONTRACT_NEW_LAST_REVALUATION_INDEX_USED => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_NEW_LAST_REVALUATION_INDEX_USED'),
                    self::RLH_CONTRACT_CURRENT_VALUE_INSTALLATION => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_CURRENT_VALUE_INSTALLATION'),
                    self::RLH_CONTRACT_NEW_CURRENT_VALUE_INSTALLATION => $langs->transnoentitiesnoconv('STC_RLH_CONTRACT_NEW_CURRENT_VALUE_INSTALLATION'),
                    self::RLH_FIRST_BILLING_PERIOD_AMOUNT => $langs->transnoentitiesnoconv('STC_RLH_FIRST_BILLING_PERIOD_AMOUNT'),
                    self::RLH_SECOND_BILLING_PERIOD_AMOUNT => $langs->transnoentitiesnoconv('STC_RLH_SECOND_BILLING_PERIOD_AMOUNT'),
                    self::RLH_BILLING_PERIOD_LENGHT => $langs->transnoentitiesnoconv('STC_RLH_BILLING_PERIOD_LENGHT'),
                    self::RLH_BILLING_PERIOD_AMOUNT => $langs->transnoentitiesnoconv('STC_RLH_BILLING_PERIOD_AMOUNT'),
                    self::RLH_INVOICE_ID => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_ID'),
                    self::RLH_INVOICE_REF => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_REF'),
                    self::RLH_INVOICE_SOC_ID => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_SOC_ID'),
                    self::RLH_INVOICE_SOC_NAME => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_SOC_NAME'),
                    self::RLH_INVOICE_REF_CUSTOMER => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_REF_CUSTOMER'),
                    self::RLH_INVOICE_MODEL_PDF => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_MODEL_PDF'),
                    self::RLH_INVOICE_COND_REGLEMENT_ID => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_COND_REGLEMENT_ID'),
                    self::RLH_INVOICE_MODE_REGLEMENT_ID => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_MODE_REGLEMENT_ID'),
                    self::RLH_INVOICE_ACCOUNT_ID => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_ACCOUNT_ID'),
                    self::RLH_INVOICE_ACCOUNT_NAME => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_ACCOUNT_NAME'),
                    self::RLH_INVOICE_AMOUNT_HT => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_AMOUNT_HT'),
                    self::RLH_INVOICE_AMOUNT_VAT => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_AMOUNT_VAT'),
                    self::RLH_INVOICE_AMOUNT_TTC => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_AMOUNT_TTC'),
                    self::RLH_INVOICE_REMISE_ABSOLUE => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_REMISE_ABSOLUE'),
                    self::RLH_INVOICE_REMISE_PERCENT => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_REMISE_PERCENT'),
                    self::RLH_INVOICE_INCOTERMS_ID => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_INCOTERMS_ID'),
                    self::RLH_INVOICE_LOCATION_INCOTERMS => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_LOCATION_INCOTERMS'),
                    self::RLH_INVOICE_MULTICURRENCY_CODE => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_MULTICURRENCY_CODE'),
                    self::RLH_INVOICE_BILLING_PERIOD_BEGIN => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_BILLING_PERIOD_BEGIN'),
                    self::RLH_INVOICE_BILLING_PERIOD_END => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_BILLING_PERIOD_END'),
                    self::RLH_INVOICE_VALIDATED => $langs->transnoentitiesnoconv('STC_RLH_INVOICE_VALIDATED'),
                    self::RLH_ERRORS => $langs->transnoentitiesnoconv('Errors'),
                );
                $this->addCurrentReportLine();
                return true;
            }

            $this->errors[] = $langs->transnoentities('STCErrorCanNotCreateFile', $file_path);
            return false;
        } else {
            $this->errors[] = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
            return false;
        }
    }

    /**
     *  Set general contract info in current report line
     *
     * @param   Contrat     $contract       Contract object
     */
    public function setGeneralContractInfoInCurrentReportLine(&$contract) {
        if ($this->report_file) {
            if (empty($contract->array_options)) {
                $contract->fetch_optionals();
            }

            $this->current_report_line[self::RLH_CONTRACT_ID] = $contract->id;
            $this->current_report_line[self::RLH_CONTRACT_REF] = $contract->ref;
            $this->current_report_line[self::RLH_CONTRACT_AMOUNT] = $contract->array_options['options_' . self::EF_CONTRACT_AMOUNT];
            $this->current_report_line[self::RLH_CONTRACT_LAST_REVALUATION_INDEX_USED] = $this->getContractOldMonthIndexLabel($contract->array_options['options_' . self::EF_LAST_REVALUATION_INDEX_USED]);
            $this->current_report_line[self::RLH_CONTRACT_CURRENT_VALUE_INSTALLATION] = $contract->array_options['options_' . self::EF_CURRENT_VALUE_INSTALLATION];
            $this->current_report_line_modified = true;
        }
    }

    /**
     *  Set general invoice info in current report line
     *
     * @param   Facture     $invoice        Invoice object
     */
    public function setGeneralInvoiceInfoInCurrentReportLine(&$invoice) {
        global $langs;

        if ($this->report_file) {
            if (empty($invoice->array_options)) {
                $invoice->fetch_optionals();
            }

            $invoice->fetch_thirdparty();
            $this->form->load_cache_conditions_paiements();
            $this->form->load_cache_types_paiements();
            $cond_reglement = dol_html_entity_decode($this->form->cache_conditions_paiements[$invoice->cond_reglement_id]['label'], ENT_QUOTES);
            $mode_reglement = $this->form->cache_types_paiements[$invoice->mode_reglement_id]['label'];

            if ($invoice->fk_account > 0 && !isset($this->cache_account[$invoice->fk_account])) {
                require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
                $bankstatic = new Account($this->db);
                $bankstatic->fetch($invoice->fk_account);
                $this->cache_account[$invoice->fk_account] = $bankstatic;
            }

            $this->current_report_line[self::RLH_INVOICE_SOC_ID] = $invoice->socid;
            $this->current_report_line[self::RLH_INVOICE_SOC_NAME] = $invoice->thirdparty->getFullName($langs);
            $this->current_report_line[self::RLH_INVOICE_REF_CUSTOMER] = $invoice->ref_client;
            $this->current_report_line[self::RLH_INVOICE_MODEL_PDF] = $invoice->modelpdf;
            $this->current_report_line[self::RLH_INVOICE_COND_REGLEMENT_ID] = isset($cond_reglement) ? $cond_reglement : '';
            $this->current_report_line[self::RLH_INVOICE_MODE_REGLEMENT_ID] = isset($mode_reglement) ? $mode_reglement : '';
            $this->current_report_line[self::RLH_INVOICE_ACCOUNT_ID] = $invoice->fk_account;
            $this->current_report_line[self::RLH_INVOICE_ACCOUNT_NAME] = ($invoice->fk_account > 0 ? $this->cache_account[$invoice->fk_account]->getFullName($langs) : '');
            $this->current_report_line[self::RLH_INVOICE_AMOUNT_HT] = price($invoice->total_ht);
            $this->current_report_line[self::RLH_INVOICE_AMOUNT_VAT] = price($invoice->total_tva);
            $this->current_report_line[self::RLH_INVOICE_AMOUNT_TTC] = price($invoice->total_ttc);
            $this->current_report_line[self::RLH_INVOICE_REMISE_PERCENT] = $invoice->remise_percent;
            $this->current_report_line[self::RLH_INVOICE_INCOTERMS_ID] = $invoice->fk_incoterms;
            $this->current_report_line[self::RLH_INVOICE_LOCATION_INCOTERMS] = $invoice->location_incoterms;
            $this->current_report_line[self::RLH_INVOICE_MULTICURRENCY_CODE] = $invoice->multicurrency_code;
            $this->current_report_line[self::RLH_INVOICE_BILLING_PERIOD_BEGIN] = dol_print_date($invoice->array_options['options_datedeb'], 'dayrfc');
            $this->current_report_line[self::RLH_INVOICE_BILLING_PERIOD_END] = dol_print_date($invoice->array_options['options_datefin'], 'dayrfc');
            $this->current_report_line_modified = true;
        }
    }

    /**
     *  Clear current report line value
     */
    public function clearCurrentReportLineValue()
    {
        $this->current_report_line = array(
            self::RLH_PARAM_BEGIN_WATCHING_DATE => '',
            self::RLH_PARAM_END_WATCHING_DATE => '',
            self::RLH_PARAM_PAYMENT_CONDITION_ID => '',
            self::RLH_PARAM_PAYMENT_DEADLINE_DATE => '',
            self::RLH_PARAM_REF_CUSTOMER => '',
            self::RLH_PARAM_USE_CUSTOMER_DISCOUNTS => '',
            self::RLH_PARAM_TEST_MODE => '',
            self::RLH_PARAM_DISABLE_REVALUATION => '',
            self::RLH_CONTRACT_ID => '',
            self::RLH_CONTRACT_REF => '',
            self::RLH_WATCHING_DATE => '',
            self::RLH_WATCHING_PERIOD_BEGIN => '',
            self::RLH_WATCHING_PERIOD_END => '',
            self::RLH_BILLING_PERIOD_BEGIN => '',
            self::RLH_BILLING_PERIOD_END => '',
            self::RLH_WATCHING_PERIOD_LENGHT => '',
            self::RLH_CONTRACT_REVALUATION_INDEX => '',
            self::RLH_CONTRACT_REVALUATION_DATE => '',
            self::RLH_POTENTIAL_REVALUATION_DATE => '',
            self::RLH_FIRST_ABSOLUTE_REVALUATION_DATE => '',
            self::RLH_CONTRACT_ENABLE_TO_REVALUATION_DATE => '',
            self::RLH_FIRST_BILLING_PERIOD_BEGIN => '',
            self::RLH_FIRST_BILLING_PERIOD_END => '',
            self::RLH_FIRST_BILLING_PERIOD_LENGHT => '',
            self::RLH_SECOND_BILLING_PERIOD_BEGIN => '',
            self::RLH_SECOND_BILLING_PERIOD_END => '',
            self::RLH_SECOND_BILLING_PERIOD_LENGHT => '',
            self::RLH_CONTRACT_LAST_REVALUATION_INDEX_VALUE_USED => '',
            self::RLH_REVALUATION_INDEX_INFO_INDEX => '',
            self::RLH_REVALUATION_INDEX_INFO_INDEX_VALUE => '',
            self::RLH_REVALUATION_INDEX_INFO_MONTH => '',
            self::RLH_REVALUATION_INDEX_INFO_YEAR => '',
            self::RLH_CONTRACT_UNAUTHORIZED_DEFLATION => '',
            self::RLH_CONTRACT_AMOUNT => '',
            self::RLH_CONTRACT_NEW_AMOUNT => '',
            self::RLH_CONTRACT_LAST_REVALUATION_INDEX_USED => '',
            self::RLH_CONTRACT_NEW_LAST_REVALUATION_INDEX_USED => '',
            self::RLH_CONTRACT_CURRENT_VALUE_INSTALLATION => '',
            self::RLH_CONTRACT_NEW_CURRENT_VALUE_INSTALLATION => '',
            self::RLH_FIRST_BILLING_PERIOD_AMOUNT => '',
            self::RLH_SECOND_BILLING_PERIOD_AMOUNT => '',
            self::RLH_BILLING_PERIOD_LENGHT => '',
            self::RLH_BILLING_PERIOD_AMOUNT => '',
            self::RLH_INVOICE_ID => '',
            self::RLH_INVOICE_REF => '',
            self::RLH_INVOICE_SOC_ID => '',
            self::RLH_INVOICE_SOC_NAME => '',
            self::RLH_INVOICE_REF_CUSTOMER => '',
            self::RLH_INVOICE_MODEL_PDF => '',
            self::RLH_INVOICE_COND_REGLEMENT_ID => '',
            self::RLH_INVOICE_MODE_REGLEMENT_ID => '',
            self::RLH_INVOICE_ACCOUNT_ID => '',
            self::RLH_INVOICE_ACCOUNT_NAME => '',
            self::RLH_INVOICE_AMOUNT_HT => '',
            self::RLH_INVOICE_AMOUNT_VAT => '',
            self::RLH_INVOICE_AMOUNT_TTC => '',
            self::RLH_INVOICE_REMISE_ABSOLUE => '',
            self::RLH_INVOICE_REMISE_PERCENT => '',
            self::RLH_INVOICE_INCOTERMS_ID => '',
            self::RLH_INVOICE_LOCATION_INCOTERMS => '',
            self::RLH_INVOICE_MULTICURRENCY_CODE => '',
            self::RLH_INVOICE_BILLING_PERIOD_BEGIN => '',
            self::RLH_INVOICE_BILLING_PERIOD_END => '',
            self::RLH_INVOICE_VALIDATED => '',
            self::RLH_ERRORS => '',
        );
        $this->current_report_line_modified = false;
    }

    /**
     *  Set current report line value
     *
     * @param int       $index      Index of the column
     * @param string    $value      Value of the column
     */
    public function setCurrentReportLineValue($index, $value) {
        if ($this->report_file) {
            if (is_string($value)) $value = dol_html_entity_decode($value, ENT_QUOTES);
            $this->current_report_line[$index] = $value;
            $this->current_report_line_modified = true;
        }
    }

    /**
     *  Add current report line in the file
     */
    public function addCurrentReportLine() {
        if ($this->report_file && $this->current_report_line_modified) {
            ksort($this->current_report_line);
            fputcsv($this->report_file, $this->current_report_line, $this->csv_separator, $this->csv_enclosure, $this->csv_escape);
            $this->clearCurrentReportLineValue();
        }
    }

    /**
     *  Close report file
     */
    public function closeReportFile() {
        if ($this->report_file) {
            fclose($this->report_file);
            $this->report_file = null;
        }
    }

    /************************************************************************************
     * Tools functions
     ************************************************************************************/

    /**
     *  Add a new event related to the given object
     *
     * @param   object    $object       Object related to the event
     * @param   string    $type_code    Code of the event
     * @param   string    $label        Label of the event
     * @param   string    $message      Message of the event
     * @return  int                     >0: rowid of the new created event, <0: Errors
     */
    public function addEvent(&$object, $type_code, $label, $message = '')
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
            $this->errors = array_merge($this->errors, $this->getObjectErrors($actioncomm));
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
    public function getObjectErrors(&$object) {
	    $errors = is_array($object->errors) ? $object->errors : array();
	    $errors = array_merge($errors, (!empty($object->error) ? array($object->error) : array()));

	    return $errors;
    }

    /**
     *  Is contract closed ?
     *
     * @param   int         $contract_id    Contract ID
     * @return  boolean
     */
    public function isContractClosed($contract_id) {
        $sql = "SELECT cd.rowid FROM " . MAIN_DB_PREFIX . "contratdet as cd WHERE cd.statut != 5 AND cd.fk_contrat = " . $contract_id;

        $resql = $this->db->query($sql);
        if ($resql) {
            return $this->db->num_rows($resql) == 0;
        }

	    return false;
    }

    /**
     *  Get all extra fields label/value to a string
     *
     * @param  object   $object     Object
     * @param  string   $rl         Return line
     * @return string               List of extra fields label/value of the object
     */
    public function getAllExtraFieldsToString(&$object, $rl = '<br>') {
        global $langs;

        if (!isset($this->cache_extrafields[$object->table_element])) {
            $this->cache_extrafields[$object->table_element] = new ExtraFields($this->db);
            $extralabels = $this->cache_extrafields[$object->table_element]->fetch_name_optionals_label($object->table_element);
        }

        if (empty($object->array_options)) {
            $object->fetch_optionals();
        }

        $out = '';

        $extrafields = $this->cache_extrafields[$object->table_element];
        if (!empty($extrafields->attributes[$object->table_element]['label'])) {
            foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $label) {
                $value = $object->array_options["options_" . $key];
                if ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' && !empty($extrafields->attributes[$object->table_element]['ishidden'][$key])) {
                    $out .= $langs->trans($label) . ' : ' . $extrafields->showOutputField($key, $value) . $rl;
                }
            }
        }

        return $out;
    }

    /**
	 *  Activate all contracts.
	 *  A result may also be provided into this->output.
	 *
	 *  @return	int						0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK)
	 */
    public function activateContracts()
    {
        global $conf, $langs, $db, $user;

        $langs->load('synergiestechcontrat@synergiestechcontrat');
        $now = dol_now();
        $nbok = 0;
        $all_error = 0;
        $ref_contracts = array();
        $this->output = '';

        $sql = "SELECT c.rowid FROM " . MAIN_DB_PREFIX . "contrat as c" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "contratdet as cd ON c.rowid = cd.fk_contrat" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "contrat_extrafields as cef ON c.rowid = cef.fk_object" .
            " WHERE cef.startdate <= '" . $db->idate($now) . "'" .
            " AND (cef.realdate > '" . $db->idate($now) . "' OR cef.realdate IS NULL OR cef.realdate = '')" .
            " AND cd.statut = 0" . " AND c.statut > 0 " .
            " GROUP BY c.rowid";

        dol_syslog(__METHOD__);
        $resql = $db->query($sql);
        if ($resql) {
            require_once (DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

            while ($obj = $db->fetch_object($resql)) {
                $error = 0;
                $db->begin();

                $contract = new Contrat($db);
                $result = $contract->fetch($obj->rowid);
                if ($result > 0) {
                    $contract->fetch_thirdparty();

                    if ($this->activateContract($user, $contract) < 0) {
                        $error++;
                        setEventMessages($langs->trans("Contract") . ' : ' . $contract->ref, $this->errors, 'errors');
                        dol_syslog(__METHOD__ . ' ' . $langs->trans("Contract") . ' : ' . $contract->ref . ' Errors: ' . implode('; ', $this->errors), LOG_ERR);
                        $this->output .= $langs->trans("Contract") . ' : ' . $contract->ref . ' Errors: ' . implode('; ', $this->errors) . "\n";
                    }

                    $label = $langs->trans('STCContractActivateEventLabel', $contract->ref);
                    $message = $langs->trans('Author') . ' : ' . $user->login;

                    $result = $this->addEvent($contract, 'AC_OTH_AUTO', $label, $message);
                    if ($result < 0) {
                        $error++;
                        setEventMessages($langs->trans("Contract") . ' : ' . $contract->ref, $this->errors, 'errors');
                        dol_syslog(__METHOD__ . ' ' . $langs->trans("Contract") . ' : ' . $contract->ref . ' Errors: ' . implode('; ', $this->errors), LOG_ERR);
                        $this->output .= $langs->trans("Contract") . ' : ' . $contract->ref . ' Errors: ' . implode('; ', $this->errors) . "\n";
                    } else {
                        $ref_contracts[] = '- ' . $contract->ref;
                        $nbok++;
                    }
                } elseif ($result == 0) {
                    $error++;
                    setEventMessage($langs->trans("ErrorRecordNotFound") . ' : ID:' . $obj->rowid, 'errors');
                    dol_syslog(__METHOD__ . ' ' . $langs->trans("ErrorRecordNotFound") . ' : ID:' . $obj->rowid, LOG_ERR);
                    $this->output .= $langs->trans("ErrorRecordNotFound") . ' : ID:' . $obj->rowid . "\n";
                } else {
                    $error++;
                    setEventMessages($contract->error, $contract->errors, 'errors');
                    dol_syslog(__METHOD__ . ' Errors: ' . $contract->error . '; ' . implode('; ', $contract->errors), LOG_ERR);
                    $this->output .= ' Errors: ' . $contract->error . '; ' . implode('; ', $contract->errors) . "\n";
                }

                if ($error) {
                    $all_error += $error;
                    $db->rollback();
                } else {
                    $db->commit();
                }
            }
        }

        if ($nbok > 0) {
            setEventMessages($langs->trans('STCContractActivated', $nbok), $ref_contracts, 'warnings');
            dol_syslog(__METHOD__ . ' ' . $langs->trans('STCContractActivated', $nbok) . ' ' . implode(' ', $ref_contracts));
            $this->output .= $langs->trans('STCContractActivated', $nbok) . ' ' . implode(' ', $ref_contracts) . "\n";
        } else {
            setEventMessage($langs->trans('STCNoContractActivated'), 'warnings');
            dol_syslog(__METHOD__ . ' ' . $langs->trans('STCNoContractActivated'));
            $this->output .= $langs->trans('STCNoContractActivated') . "\n";
        }

        return $all_error ? $all_error : 0;
    }

    /**
	 *  Terminate all contracts.
	 *  A result may also be provided into this->output.
	 *
	 *  @return	int						0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK)
	 */
    public function terminateContracts()
    {
        global $conf, $langs, $db, $user;

        $langs->load('synergiestechcontrat@synergiestechcontrat');
        $now = dol_now();
        $nbok = 0;
        $all_error = 0;
        $ref_contracts = array();
        $this->output = '';

        $sql = "SELECT c.rowid FROM " . MAIN_DB_PREFIX . "contrat as c" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "contratdet as cd ON c.rowid = cd.fk_contrat" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "contrat_extrafields as cef ON c.rowid = cef.fk_object" .
            " WHERE cef.realdate <= '" . $db->idate($now) . "'" .
            " AND cd.statut != 5" .
            " GROUP BY c.rowid";

        dol_syslog(__METHOD__);
        $resql = $db->query($sql);
        if ($resql) {
            require_once (DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");

            while ($obj = $db->fetch_object($resql)) {
                $error = 0;
                $db->begin();

                $contract = new Contrat($db);
                $result = $contract->fetch($obj->rowid);
                if ($result > 0) {
                    $contract->fetch_thirdparty();

                    $contract->cloture($user);

                    $label = $langs->trans('STCContractTerminateEventLabel', $contract->ref);
                    $message = $langs->trans('Author') . ' : ' . $user->login;

                    $result = $this->addEvent($contract, 'AC_STC_TERMI', $label, $message);
                    if ($result < 0) {
                        $error++;
                        setEventMessages($langs->trans("Contract") . ' : ' . $contract->ref, $this->errors, 'errors');
                        dol_syslog(__METHOD__ . ' ' . $langs->trans("Contract") . ' : ' . $contract->ref . ' Errors: ' . implode('; ', $this->errors), LOG_ERR);
                        $this->output .= $langs->trans("Contract") . ' : ' . $contract->ref . ' Errors: ' . implode('; ', $this->errors) . "\n";
                    } else {
                        $ref_contracts[] = '- ' . $contract->ref;
                        $nbok++;
                    }
                } elseif ($result == 0) {
                    $error++;
                    setEventMessage($langs->trans("ErrorRecordNotFound") . ' : ID:' . $obj->rowid, 'errors');
                    dol_syslog(__METHOD__ . ' ' . $langs->trans("ErrorRecordNotFound") . ' : ID:' . $obj->rowid, LOG_ERR);
                    $this->output .= $langs->trans("ErrorRecordNotFound") . ' : ID:' . $obj->rowid . "\n";
                } else {
                    $error++;
                    setEventMessages($contract->error, $contract->errors, 'errors');
                    dol_syslog(__METHOD__ . ' Errors: ' . $contract->error . '; ' . implode('; ', $contract->errors), LOG_ERR);
                    $this->output .= ' Errors: ' . $contract->error . '; ' . implode('; ', $contract->errors) . "\n";
                }

                if ($error) {
                    $all_error += $error;
                    $db->rollback();
                } else {
                    $db->commit();
                }
            }
        }

        if ($nbok > 0) {
            setEventMessages($langs->trans('STCContractTerminated', $nbok), $ref_contracts, 'warnings');
            dol_syslog(__METHOD__ . ' ' . $langs->trans('STCContractTerminated', $nbok) . ' ' . implode(' ', $ref_contracts));
            $this->output .= $langs->trans('STCContractTerminated', $nbok) . ' ' . implode(' ', $ref_contracts) . "\n";
        } else {
            setEventMessage($langs->trans('STCNoContractTerminated'), 'warnings');
            dol_syslog(__METHOD__ . ' ' . $langs->trans('STCNoContractTerminated'));
            $this->output .= $langs->trans('STCNoContractTerminated') . "\n";
        }

        return $all_error ? $all_error : 0;
    }

    /**
	 *  Activate all lines of a contract
	 *
     *  @param	User		$user       Object User making action
     *  @param	Contrat		$contract   Object of a contract
	 *	@return	void
	 */
	function activateContract($user, &$contract)
    {
        $this->db->begin();

        // Load lines
        $contract->fetch_lines();

        $ok = true;
        foreach ($contract->lines as $contratline) {
            // Active line not already active
            if ($contratline->statut != 4) {
                $contratline->date_ouverture = dol_now();
                $contratline->fk_user_ouverture = $user->id;
                $contratline->statut = '4';
                $result = $contratline->update($user);
                if ($result < 0) {
                    $this->errors = array_merge($this->errors, $contratline->errors);
                    $ok = false;
                    break;
                }
            }
        }

        if ($contract->statut == 0) {
            $result = $contract->validate($user);
            if ($result < 0) {
                $this->errors[] = $contract->error;
               $ok = false;
            }
        }

        if ($ok) {
            $this->db->commit();
            return 1;
        } else {
            $this->db->rollback();
            return -1;
        }
    }
}
