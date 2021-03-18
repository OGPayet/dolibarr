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
    const STATUS_WAITING_PAYMENT = 101;
    const STATUS_WAITING_PAYMENT_PARTIAL_PAID = 102;
    const STATUS_LATE_PAYMENT = 103;
    const STATUS_CONTENTIOUS_PAYMENT = 104;
    const STATUS_ABANDONED_PAYMENT = 105;
    const STATUS_PARTIAL_ABANDONED_PAYMENT = 106;
    const STATUS_PAID_OR_CONVERTED = 107;
    const STATUS_CONVERTED = 108;
    const STATUS_PAID = 109;
    const STATUS_UNKNOWN = -1;

    /**
     * Status (long) translation key label
     */
    public static $sqlSearchInvoiceList = array(
        self::STATUS_DRAFT => 'f.fk_statut = 0',
        self::STATUS_WAITING_PAYMENT => 'InvoiceBetterStatusWaitingPayment',
        self::STATUS_WAITING_PAYMENT_PARTIAL_PAID => 'InvoiceBetterStatusWaintingPaymentStartToBePaid',
        self::STATUS_LATE_PAYMENT => 'InvoiceBetterStatusLatePayment',
        self::STATUS_CONTENTIOUS_PAYMENT => 'InvoiceBetterStatusContentiousPayment',
        self::STATUS_ABANDONED_PAYMENT => 'InvoiceBetterStatusAbandonedPayment',
        self::STATUS_PARTIAL_ABANDONED_PAYMENT => 'InvoiceBetterStatusPartialAbandonedPayment',
        self::STATUS_PAID_OR_CONVERTED=> 'f.paye = 1 AND f.type != ' . Facture::TYPE_CREDIT_NOTE,
        self::STATUS_CONVERTED => 'f.paye = 1 AND f.type != ' . Facture::TYPE_DEPOSIT,
        self::STATUS_PAID => 'f.paye = 1 AND f.type != ' . Facture::TYPE_CREDIT_NOTE . ' AND f.type != ' . Facture::TYPE_DEPOSIT
    );

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
        $invoiceStatut = $invoice->status ? $invoice->status : $invoice->statut;
        if (!$invoice->paye) {
            if ($invoiceStatut == 0) {
                $result = self::STATUS_DRAFT;
            } elseif (($invoiceStatut == 3 || $invoiceStatut == 2) && $invoice->alreadypaid <= 0) {
                $result = self::STATUS_ABANDONED_PAYMENT;
            } elseif (($invoiceStatut == 3 || $invoiceStatut == 2) && $invoice->alreadypaid > 0) {
                $result = self::STATUS_PARTIAL_ABANDONED_PAYMENT;
            } else {
                $test = $invoice->date_lim_reglement;
                $now = dol_now();
                if ($invoice->date_lim_reglement > dol_now()) {
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

    /**
     * Function to get invoice lib status
     * @param Facture $invoice
     * @return int
     */
    public static function getLibStatus($invoice, $mode = 0)
    {
        $status = self::getCurrentStatus($invoice);
        return dolGetStatus(self::$statusLabel[$status], self::$statusLabelShort[$status], '', self::$statusPicto[$status], $mode);
    }

    /**
     *  Return clicable link of object (with eventually picto)
     *
     *  @param  Facture $invoice                    Invoice on which we are working
     *  @param  int     $withpicto                  Add picto into link
     *  @param  string  $option                     Where point the link
     *  @param  int     $max                        Maxlength of ref
     *  @param  int     $short                      1=Return just URL
     *  @param  string  $moretitle                  Add more text to title tooltip
     *  @param  int     $notooltip                  1=Disable tooltip
     *  @param  int     $addlinktonotes             1=Add link to notes
     *  @param  int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *  @param  string  $target                     Target of link ('', '_self', '_blank', '_parent', '_backoffice', ...)
     *  @return string                              String with URL
     */
    public static function getNomUrl($invoice, $withpicto = 0, $option = '', $max = 0, $short = 0, $moretitle = '', $notooltip = 0, $addlinktonotes = 0, $save_lastsearch_value = -1, $target = '')
    {
        global $langs, $conf, $user, $mysoc;

        if (!empty($conf->dol_no_mouse_hover)) {
            $notooltip = 1; // Force disable tooltips
        }

        $result = '';

        if ($option == 'withdraw') {
            $url = DOL_URL_ROOT.'/compta/facture/prelevement.php?facid='.$invoice->id;
        } else {
            $url = DOL_URL_ROOT.'/compta/facture/card.php?facid='.$invoice->id;
        }

        if (!$user->rights->facture->lire) {
            $option = 'nolink';
        }

        if ($option !== 'nolink') {
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
                $add_save_lastsearch_values = 1;
            }
            if ($add_save_lastsearch_values) {
                $url .= '&save_lastsearch_values=1';
            }
        }

        if ($short) {
            return $url;
        }

        $picto = $invoice->picto;
        if ($invoice->type == $invoice::TYPE_REPLACEMENT) {
            $picto .= 'r'; // Replacement invoice
        }
        if ($invoice->type == $invoice::TYPE_CREDIT_NOTE) {
            $picto .= 'a'; // Credit note
        }
        if ($invoice->type == $invoice::TYPE_DEPOSIT) {
            $picto .= 'd'; // Deposit invoice
        }
        $label = '';

        if ($user->rights->facture->lire) {
            $label = img_picto('', $picto).' <u class="paddingrightonly">'.$langs->trans("Invoice").'</u>';
            if ($invoice->type == $invoice::TYPE_REPLACEMENT) {
                $label = img_picto('', $picto).' <u class="paddingrightonly">'.$langs->transnoentitiesnoconv("ReplacementInvoice").'</u>';
            }
            if ($invoice->type == $invoice::TYPE_CREDIT_NOTE) {
                $label = img_picto('', $picto).' <u class="paddingrightonly">'.$langs->transnoentitiesnoconv("CreditNote").'</u>';
            }
            if ($invoice->type == $invoice::TYPE_DEPOSIT) {
                $label = img_picto('', $picto).' <u class="paddingrightonly">'.$langs->transnoentitiesnoconv("Deposit").'</u>';
            }
            if ($invoice->type == $invoice::TYPE_SITUATION) {
                $label = img_picto('', $picto).' <u class="paddingrightonly">'.$langs->transnoentitiesnoconv("InvoiceSituation").'</u>';
            }
            if (isset($invoice->statut) && isset($invoice->alreadypaid) && isset($invoice->date_lim_reglement)) {
                $label .= ' ' . self::getLibStatus($invoice, 5);
            }
            if (!empty($invoice->ref)) {
                $label .= '<br><b>'.$langs->trans('Ref').':</b> '.$invoice->ref;
            }
            if (!empty($invoice->ref_client)) {
                $label .= '<br><b>'.$langs->trans('RefCustomer').':</b> '.$invoice->ref_client;
            }
            if (!empty($invoice->date)) {
                $label .= '<br><b>'.$langs->trans('Date').':</b> '.dol_print_date($invoice->date, 'day');
            }
            if (!empty($invoice->total_ht)) {
                $label .= '<br><b>'.$langs->trans('AmountHT').':</b> '.price($invoice->total_ht, 0, $langs, 0, -1, -1, $conf->currency);
            }
            if (!empty($invoice->total_tva)) {
                $label .= '<br><b>'.$langs->trans('AmountVAT').':</b> '.price($invoice->total_tva, 0, $langs, 0, -1, -1, $conf->currency);
            }
            if (!empty($invoice->total_localtax1) && $invoice->total_localtax1 != 0) {        // We keep test != 0 because $this->total_localtax1 can be '0.00000000'
                $label .= '<br><b>'.$langs->transcountry('AmountLT1', $mysoc->country_code).':</b> '.price($invoice->total_localtax1, 0, $langs, 0, -1, -1, $conf->currency);
            }
            if (!empty($invoice->total_localtax2) && $invoice->total_localtax2 != 0) {
                $label .= '<br><b>'.$langs->transcountry('AmountLT2', $mysoc->country_code).':</b> '.price($invoice->total_localtax2, 0, $langs, 0, -1, -1, $conf->currency);
            }
            if (!empty($invoice->total_ttc)) {
                $label .= '<br><b>'.$langs->trans('AmountTTC').':</b> '.price($invoice->total_ttc, 0, $langs, 0, -1, -1, $conf->currency);
            }
            if ($moretitle) {
                $label .= ' - '.$moretitle;
            }
        }

        $linkclose = ($target ? ' target="'.$target.'"' : '');
        if (empty($notooltip) && $user->rights->facture->lire) {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
                $label = $langs->trans("Invoice");
                $linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose .= ' class="classfortooltip"';
        }

        $linkstart = '<a href="'.$url.'"';
        $linkstart .= $linkclose.'>';
        $linkend = '</a>';

        if ($option == 'nolink') {
            $linkstart = '';
            $linkend = '';
        }

        $result .= $linkstart;
        if ($withpicto) {
            $result .= img_object(($notooltip ? '' : $label), $picto, ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
        }
        if ($withpicto != 2) {
            $result .= ($max ?dol_trunc($invoice->ref, $max) : $invoice->ref);
        }
        $result .= $linkend;

        if ($addlinktonotes) {
            $txttoshow = ($user->socid > 0 ? $invoice->note_public : $invoice->note_private);
            if ($txttoshow) {
                //$notetoshow = $langs->trans("ViewPrivateNote").':<br>'.dol_string_nohtmltag($txttoshow, 1);
                $notetoshow = $langs->trans("ViewPrivateNote").':<br>'.$txttoshow;
                $result .= ' <span class="note inline-block">';
                $result .= '<a href="'.DOL_URL_ROOT.'/compta/facture/note.php?id='.$invoice->id.'" class="classfortooltip" title="'.dol_escape_htmltag($notetoshow, 1, 1).'">';
                $result .= img_picto('', 'note');
                $result .= '</a>';
                //$result.=img_picto($langs->trans("ViewNote"),'object_generic');
                //$result.='</a>';
                $result .= '</span>';
            }
        }
        return $result;
    }

    /**
     * Function to get translated possible status
     * @param Facture $invoice
     * @return int
     */
    public static function getStatusArrayTranslatedForSearch($langs)
    {
        $result = array();
        foreach (self::$statusLabelShort as $value => $translateKey) {
            $result[$value] = $translateKey;
            if (method_exists($langs, "trans")) {
                $result[$value] = $langs->trans($translateKey);
            }
        }
        return $result;
    }
}
