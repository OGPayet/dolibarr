<?php
/* Copyright (C) 2018   Open-DSI    <support@open-dsi.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/synergiestechcontrat/core/actions_massactions.inc.php
 *  \brief			Code for actions done with massaction button (send by email, merge pdf, delete, ...)
 */


// $confirm must be defined
// $massaction must be defined
// $objectclass and $$objectlabel must be defined
// $parameters, $object, $action must be defined for the hook.

// $diroutputmassaction may be defined (example to $conf->projet->dir_output."/";)
// $toselect may be defined
// $invoices_draft_list must be defined

include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

if ($reshook == 0) {
    // Validate invoices
    if (!$error && $massaction == 'validate_invoices' && $user->rights->facture->creer) {
        if (!empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_BILL)) {
            $langs->load("errors");
            setEventMessages($langs->trans('ErrorMassValidationNotAllowedWhenStockIncreaseOnAction'), null, 'errors');
            $error++;
        }

        if (!$error) {
            require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';

            $nbok = 0;
            foreach ($toselect as $toselectid) {
                if (isset($invoices_draft_list[$toselectid]['invoices'])) {
                    foreach ($invoices_draft_list[$toselectid]['invoices'] as $invoice_infos) {
                        $db->begin();

                        $invoice = new Facture($db);
                        $result = $invoice->fetch($invoice_infos['id']);
                        if ($result > 0) {
                            $result = $invoice->validate($user);
                            if ($result > 0) {
                                $nbok++;
                            }
                        }

                        if ($result < 0) {
                            $db->rollback();
                            setEventMessages($invoice->error, $invoice->errors, 'errors');
                            $error++;
                        } else {
                            $db->commit();
                        }
                    }
                }
            }

            if ($nbok > 0)
                setEventMessage($langs->trans('STCInvoicesContractValidated', $nbok));

                // Get all invoices draft linked to contracts
                $invoices_draft_list = $formsynergiestechcontract->getInvoicesContractsInfo();
                if (!is_array($invoices_draft_list)) {
                    setEventMessage('getInvoicesContractsInfo: ' . $db->lasterror(), 'errors');
                }
            else
                setEventMessage($langs->trans('STCNoInvoicesContractValidated'), 'warnings');
        }
    }
    // Generate invoices for the contracts
    elseif (!$error && $massaction == 'confirm_generate_invoices' && $confirm == 'yes' && $user->rights->facture->creer) {
        $begin_watching_date = dol_mktime(0, 0, 0, GETPOST('begin_watching_datemonth', 'int'), GETPOST('begin_watching_dateday', 'int'), GETPOST('begin_watching_dateyear', 'int'));
        $end_watching_date = dol_mktime(0, 0, 0, GETPOST('end_watching_datemonth', 'int'), GETPOST('end_watching_dateday', 'int'), GETPOST('end_watching_dateyear', 'int'));
        $payment_condition_id = GETPOST('payment_condition_id', 'int');
        $payment_deadline_date = dol_mktime(0, 0, 0, GETPOST('payment_deadline_datemonth', 'int'), GETPOST('payment_deadline_dateday', 'int'), GETPOST('payment_deadline_dateyear', 'int'));
        $ref_customer = GETPOST('ref_customer', 'alpha');
        $use_customer_discounts = GETPOST('use_customer_discounts') ? 1 : 0;
        $test_mode = GETPOST('test_mode') ? 1 : 0;
        $disable_revaluation = GETPOST('disable_revaluation') ? 1 : 0;

        if (empty($begin_watching_date)) {
            setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv('STCGenerateInvoicesContractBeginWatchingDate')), 'errors');
            $error++;
        }

        if (empty($end_watching_date)) $end_watching_date = $begin_watching_date;

        if (!$error) {
            if ($test_mode) {
                $db->begin();
                setEventMessage($langs->trans('STCGenerateInvoicesContractTestMode'), 'errors');
            }

            dol_include_once('/synergiestechcontrat/class/invoicescontracttools.class.php');
            $invoicescontracttools = new InvoicesContractTools($db);
            $nbok = 0;

            // Create report CSV
            if ($invoicescontracttools->createReportFiles($diroutputmassaction, $begin_watching_date, $end_watching_date)) {
                foreach ($toselect as $toselectid) {
                    $error = 0;
                    if (!$test_mode) {
                        $db->begin();
                        $invoicescontracttools->setDataBase($db);
                    }

                    // Set contract info into the report CSV
                    $invoicescontracttools->setCurrentReportLineValue(InvoicesContractTools::RLH_CONTRACT_ID, $toselectid);

                    $objecttmp = new $objectclass($db);
                    $result = $objecttmp->fetch($toselectid);
                    if ($result > 0) {
                        // Generate invoices for the period
                        $result = $invoicescontracttools->generateInvoicesForTheContractInPeriod($objecttmp, $begin_watching_date, $end_watching_date, $payment_condition_id, $payment_deadline_date, $ref_customer, $use_customer_discounts, $test_mode, $disable_revaluation);
                        if ($result < 0) {
                            setEventMessages($langs->trans('Contract') . ': ' . $objecttmp->ref, $invoicescontracttools->errors, 'errors');
                            $error++;
                        } else {
                            $nbok += $result;
                        }
                    } else {
                        $invoicescontracttools->setCurrentReportLineValue(InvoicesContractTools::RLH_ERRORS, $objecttmp->errorsToString());
                        setEventMessages($langs->trans('Contract') . ': ID:' . $toselectid . "\n" . $objecttmp->error, $objecttmp->errors, 'errors');
                        $error++;
                    }

                    if (!$test_mode) {
                        if ($error) {
                            $db->rollback();
                        } else {
                            $db->commit();
                        }
                    }

                    // Add current report line into the file
                    $invoicescontracttools->addCurrentReportLine();
                }

                // Save report CSV
                $invoicescontracttools->closeReportFile();
            } else {
                setEventMessages(null, $invoicescontracttools->errors, 'errors');
                $error++;
            }

            if ($test_mode) {
                $db->rollback();
            }

            if ($nbok > 0) {
                setEventMessage($langs->trans('STCInvoicesContractGenerated', $nbok));

                // Get all invoices draft linked to contracts
                $invoices_draft_list = $formsynergiestechcontract->getInvoicesContractsInfo();
                if (!is_array($invoices_draft_list)) {
                    setEventMessage('getInvoicesContractsInfo: ' . $db->lasterror(), 'errors');
                }
            } else
                setEventMessage($langs->trans('STCNoInvoicesContractGenerated'), 'warnings');
        } else {
            $massaction = 'generate_invoices';
        }
    }
}
