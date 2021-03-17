<?php
/** Copyright (C) 2021  Alexis Laurier <contact@alexislaurier.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/test.class.php
 * \ingroup     invoicebetterstatus
 * \brief       This file is a CRUD class file for Test (Create/Read/Update/Delete)
 */

class InvoiceBetterStatusTool
{
    /**
     * Status
     */
    const STATUS_DRAFT = 100;
    const STATUS_WAITING_PAYMENT = 100;
    const STATUS_WAITING_PAYMENT_PARTIAL_PAID = 101;
    const STATUS_LATE_PAYMENT = 102;
    const STATUS_CONTENTIOUS_PAYMENT = 103;
    const STATUS_ABANDONED_PAYMENT = 104;
    const STATUS_PARTIAL_ABANDONED_PAYMENT = 105;
    const STATUS_PAID_OR_CONVERTED = 106;
    const STATUS_CONVERTED = 107;
    const STATUS_PAID = 108;
    const STATUS_UNKNOWN = -1;

    /**
     * Status (long) translation key label
     */
    public static $statusLabel = array(
        self::STATUS_UNKNOWN => 'InvoiceBetterStatusUnknown',
        self::STATUS_DRAFT => 'InvoiceBetterStatusDraft',
        self::STATUS_WAITING_PAYMENT => 'InvoiceBetterStatusWaitingPayment',
        self::STATUS_WAITING_PAYMENT_PARTIAL_PAID => 'InvoiceBetterStatusWaintingPaymentStartToBePaid',
        self::STATUS_LATE_PAYMENT => 'InvoiceBetterStatusLatePayment',
        self::STATUS_CONTENTIOUS_PAYMENT => 'InvoiceBetterStatusContentiousPayment',
        self::STATUS_ABANDONED_PAYMENT => 'InvoiceBetterStatusAbandonedPayment',
        self::STATUS_PARTIAL_ABANDONED_PAYMENT => 'InvoiceBetterStatusPartialAbandonedPayment',
        self::STATUS_PAID_OR_CONVERTED=>'InvoiceBetterStatusPaidOrConverted',
        self::STATUS_CONVERTED => 'InvoiceBetterStatusConverted',
        self::STATUS_PAID => 'InvoiceBetterStatusPaid'
    );

    /**
     * Status short translation key label
     */
    public static $statusLabelShort = array(
        self::STATUS_UNKNOWN => 'InvoiceBetterStatusUnknownShort',
        self::STATUS_DRAFT => 'InvoiceBetterStatusDraftShort',
        self::STATUS_WAITING_PAYMENT => 'InvoiceBetterStatusWaitingPaymentShort',
        self::STATUS_WAITING_PAYMENT_PARTIAL_PAID => 'InvoiceBetterStatusWaintingPaymentStartToBePaidShort',
        self::STATUS_LATE_PAYMENT => 'InvoiceBetterStatusLatePaymentShort',
        self::STATUS_CONTENTIOUS_PAYMENT => 'InvoiceBetterStatusContentiousPaymentShort',
        self::STATUS_ABANDONED_PAYMENT => 'InvoiceBetterStatusAbandonedPaymentShort',
        self::STATUS_PARTIAL_ABANDONED_PAYMENT => 'InvoiceBetterStatusPartialAbandonedPaymentShort',
        self::STATUS_PAID_OR_CONVERTED=>'InvoiceBetterStatusPaidOrConvertedShort',
        self::STATUS_CONVERTED => 'InvoiceBetterStatusConvertedShort',
        self::STATUS_PAID => 'InvoiceBetterStatusPaidShort'
    );

    /**
     * Status type picto
     */
    public static $statusPicto = array(
        self::STATUS_UNKNOWN => 'status0',
        self::STATUS_DRAFT => 'status0',
        self::STATUS_WAITING_PAYMENT => 'status1',
        self::STATUS_WAITING_PAYMENT_PARTIAL_PAID => 'status1',
        self::STATUS_LATE_PAYMENT => 'status7',
        self::STATUS_CONTENTIOUS_PAYMENT => 'status8',
        self::STATUS_ABANDONED_PAYMENT => 'status5',
        self::STATUS_PARTIAL_ABANDONED_PAYMENT => 'status9',
        self::STATUS_PAID_OR_CONVERTED=>'status6',
        self::STATUS_CONVERTED => 'status6',
        self::STATUS_PAID => 'status6'
    );
    /**
     * Function to get current advanced status value
     * @param Facture $invoice
     * @return int
     */
    public static function getCurrentStatus($invoice)
    {
        if (!$invoice->paye) {
            if ($invoice->status == 0) {
                $result = self::STATUS_DRAFT;
            } elseif (($invoice->status == 3 || $invoice->status == 2) && $invoice->alreadypaid <= 0) {
                $result = self::STATUS_ABANDONED_PAYMENT;
            } elseif (($invoice->status == 3 || $invoice->status == 2) && $invoice->alreadypaid > 0) {
                $result = self::STATUS_PARTIAL_ABANDONED_PAYMENT;
            } else {
                if ($invoice->date_lim_reglement <= dol_now()) {
                    if ($invoice->alreadypaid == 0) {
                        $result = self::STATUS_WAITING_PAYMENT;
                    } else {
                        $result = self::STATUS_WAITING_PAYMENT_PARTIAL_PAID;
                    }
                } else {
                    if ($invoice->array_options['options_classify_as_contentious']) {
                        $result = self::STATUS_CONTENTIOUS_PAYMENT;
                    } else {
                        $result = self::STATUS_LATE_PAYMENT;
                    }
                }
            }
        } else {
            if ($invoice->type == $invoice::TYPE_CREDIT_NOTE) {
                $result = self::STATUS_PAID_OR_CONVERTED;
            } elseif ($invoice->type == $invoice::TYPE_DEPOSIT) {
                $result = self::STATUS_CONVERTED;
            } else {
                $result = self::STATUS_PAID;
            }
        }
        return $result;
    }
}
