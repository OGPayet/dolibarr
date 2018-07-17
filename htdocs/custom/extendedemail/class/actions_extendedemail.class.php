<?php
/* Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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

dol_include_once('/extendedemail/lib/extendedemail.lib.php');

/**
 *  \file       htdocs/extendedemail/class/actions_extendedemail.class.php
 *  \ingroup    extendedemail
 *  \brief      File for hooks
 */

class ActionsExtendedEmail
{
    /**
     * Overloading the getFormMail function : replacing the parent's function with the one below
     *
     * @param   array() $parameters Hook metadatas (context, etc...)
     * @param   CommonObject &$object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
     * @param   string &$action Current action (if set). Generally create or edit or null
     * @param   HookManager $hookmanager Hook manager propagated to allow calling another hook
     * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
     */
    function getFormMail($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $db, $langs, $form, $user;

        $langs->load('extendedemail@extendedemail');

        // Modify interface of recipient list
        //------------------------------------------------------------
        $users_email = [];
        if ($conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTO ||
            $conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTOCC ||
            $conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTOCCC
        ) {
            $users_email = extendedemail_get_users_email();
        }
        $contacts_thirdparty_parent_email = [];
        if ($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTO ||
            $conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCC ||
            $conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCCC
        ) {
            $soc_id = 0;
            if (in_array('propalcard', explode(':', $parameters['context']))) {
                require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
                $obj = new Propal($db);
                $obj->fetch($object->param['id']);
                $soc_id = $obj->socid;
            } elseif (in_array('ordercard', explode(':', $parameters['context']))) {
                require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
                $obj = new Commande($db);
                $obj->fetch($object->param['orderid']);
                $soc_id = $obj->socid;
            } elseif (in_array('invoicecard', explode(':', $parameters['context']))) {
                require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
                $obj = new Facture($db);
                $obj->fetch($object->param['facid']);
                $soc_id = $obj->socid;
            } elseif (in_array('contractcard', explode(':', $parameters['context']))) {
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                $obj = new Contrat($db);
                $obj->fetch($object->param['contractid']);
                $soc_id = $obj->socid;
            } elseif (in_array('expeditioncard', explode(':', $parameters['context']))) {
                require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
                $obj = new Expedition($db);
                $obj->fetch($object->param['shippingid']);
                $soc_id = $obj->socid;
            } elseif (in_array('interventioncard', explode(':', $parameters['context']))) {
                require_once DOL_DOCUMENT_ROOT . '/fichinter/class/fichinter.class.php';
                $obj = new Fichinter($db);
                $obj->fetch($object->param['fichinter_id']);
                $soc_id = $obj->socid;
            } elseif (in_array('ordersuppliercard', explode(':', $parameters['context']))) {
                require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
                $obj = new CommandeFournisseur($db);
                $obj->fetch($object->param['orderid']);
                $soc_id = $obj->socid;
            } elseif (in_array('invoicesuppliercard', explode(':', $parameters['context']))) {
                require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
                $obj = new FactureFournisseur($db);
                $obj->fetch($object->param['facid']);
                $soc_id = $obj->socid;
            } elseif (in_array('thirdpartycard', explode(':', $parameters['context']))) {
                $soc_id = $object->param['socid'];
            } elseif (in_array('supplier_proposalcard', explode(':', $parameters['context']))) {
                require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
                $obj = new SupplierProposal($db);
                $obj->fetch($object->param['id']);
                $soc_id = $obj->socid;
            } elseif (in_array('usercard', explode(':', $parameters['context']))) {
                require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';
                $obj = new User($db);
                $obj->fetch($object->param['socid']);
                $soc_id = $obj->socid;
            }

            $contacts_thirdparty_parent_email = extendedemail_get_contacts_thirdparty_parent_email($soc_id);
        }
        $hide_no_email = !empty($conf->global->EXTENDEDEMAIL_HIDE_NO_EMAIL) ? 'true' : 'false';
        $max_options = $conf->global->EXTENDEDEMAIL_MAX_LINE_HIDE_LIST;

        print '<script type="text/javascript" language="javascript">';
        print '$(document).ready(function () {';
        // From
        if (!empty($object->withfrom)) {
            $senders = extendedemail_get_senders_email($object);
            if (count($senders) > 0) {
                $frommail = $object->frommail;
                if (empty($frommail)) {
                    foreach ($senders as $sender) {
                        if (!$sender['disabled']) {
                            $frommail = $sender['email'];
                            break;
                        }
                    }
                }
                $fromreadonly = !empty($object->withfromreadonly) ? 'true' : 'false';
                print 'extendedemail_select_from(' . json_encode($senders) . ', ' . json_encode([$frommail]) . ', \'' . $object->fromtype . '\', ' . $hide_no_email . ', ' . $fromreadonly . ', ' . $max_options . ');';
            }
        }
        // To
        if (!empty($object->withto) || is_array($object->withto)) {
            $sendto_label = str_replace('"', '\\"', $form->textwithpicto($langs->trans("MailTo"), $langs->trans("YouCanUseCommaSeparatorForSeveralRecipients")));
            $users_email_to_sendto = ($conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTO ? $users_email : array());
            if ($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTO) $users_email_to_sendto = array_merge($users_email_to_sendto, $contacts_thirdparty_parent_email);
            $users_email_to_sendto = !empty($users_email_to_sendto) ? json_encode($users_email_to_sendto) : '[]';
            $toreadonly = !empty($object->withtoreadonly) ? 'true' : 'false';
            print 'extendedemail_select_email("sendto", "receiver", "' . $sendto_label . '", ' . $users_email_to_sendto . ', ' . $hide_no_email . ', ' . $toreadonly . ', ' . $max_options . ');';
        }
        // CC
        if (!empty($object->withtocc) || is_array($object->withtocc)) {
            $sendtocc_label = str_replace('"', '\\"', $form->textwithpicto($langs->trans("MailCC"), $langs->trans("YouCanUseCommaSeparatorForSeveralRecipients")));
            $users_email_to_sendtocc = ($conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTOCC ? $users_email : array());
            if ($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCC) $users_email_to_sendtocc = array_merge($users_email_to_sendtocc, $contacts_thirdparty_parent_email);
            $users_email_to_sendtocc = !empty($users_email_to_sendtocc) ? json_encode($users_email_to_sendtocc) : '[]';
            $toccreadonly = !empty($object->withtoccreadonly) ? 'true' : 'false';
            print 'extendedemail_select_email("sendtocc", "receivercc", "' . $sendtocc_label . '", ' . $users_email_to_sendtocc . ', ' . $hide_no_email . ', ' . $toccreadonly . ', ' . $max_options . ');';
        }
        // CCC
        if (!empty($object->withtoccc) || is_array($object->withtoccc)) {
            $sendtoccc_label = str_replace('"', '\\"', $form->textwithpicto($langs->trans("MailCCC"), $langs->trans("YouCanUseCommaSeparatorForSeveralRecipients")));
            $users_email_to_sendtoccc = ($conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTOCCC ? $users_email : array());
            if ($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCCC) $users_email_to_sendtoccc = array_merge($users_email_to_sendtoccc, $contacts_thirdparty_parent_email);
            $users_email_to_sendtoccc = !empty($users_email_to_sendtoccc) ? json_encode($users_email_to_sendtoccc) : '[]';
            $tocccreadonly = !empty($object->withtocccreadonly) ? 'true' : 'false';
            print 'extendedemail_select_email("sendtoccc", "receiverccc", "' . $sendtoccc_label . '", ' . $users_email_to_sendtoccc . ', ' . $hide_no_email . ', ' . $tocccreadonly . ', ' . $max_options . ');';
        }
        print '});';
        print '</script>';

        return 0;
    }
}