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

        // Get selected emails
        $selected_users_email_to_sendto = [];
        $selected_users_email_to_sendtocc = [];
        $selected_users_email_to_sendtoccc = [];

        // Load the object of the card
        //------------------------------------------------------------
        $card_object = null;
        if ($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTO ||
            $conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCC ||
            $conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCCC ||
            $conf->global->EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTO ||
            $conf->global->EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCC ||
            $conf->global->EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCCC
        ) {
            $contexts = explode(':', $parameters['context']);
            $id = $object->param['id'];
            if (empty($id)) $id = $object->param['orderid'];
            if (empty($id)) $id = $object->param['facid'];
            if (empty($id)) $id = $object->param['contractid'];
            if (empty($id)) $id = $object->param['shippingid'];
            if (empty($id)) $id = $object->param['fichinter_id'];
            if (empty($id)) $id = $object->param['socid'];

            if ($id > 0) {
                if (in_array('propalcard', $contexts)) {
                    $card_object = new Propal($db);
                } elseif (in_array('ordercard', $contexts)) {
                    $card_object = new Commande($db);
                } elseif (in_array('invoicecard', $contexts)) {
                    $card_object = new Facture($db);
                } elseif (in_array('contractcard', $contexts)) {
                    $card_object = new Contrat($db);
                } elseif (in_array('expeditioncard', $contexts)) {
                    $card_object = new Expedition($db);
                } elseif (in_array('interventioncard', $contexts)) {
                    $card_object = new Fichinter($db);
                } elseif (in_array('ordersuppliercard', $contexts)) {
                    $card_object = new CommandeFournisseur($db);
                } elseif (in_array('invoicesuppliercard', $contexts)) {
                    $card_object = new FactureFournisseur($db);
                } elseif (in_array('thirdpartycard', $contexts)) {
                    $card_object = new Societe($db);
                } elseif (in_array('supplier_proposalcard', $contexts)) {
                    $card_object = new SupplierProposal($db);
                } elseif (in_array('usercard', $contexts)) {
                    $card_object = new User($db);
                }
                if (isset($card_object)) {
                    $card_object->fetch($id);
                }
            }
        }

        // Modify interface of recipient list
        //------------------------------------------------------------
        // Get email of the internal user
        $users_email = [];
        if ($conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTO ||
            $conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTOCC ||
            $conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTOCCC
        ) {
            $users_email = extendedemail_get_users_email();
        }
        // Get email of the contacts of the company linked with this card
        $contacts_thirdparty_parent_email = [];
        if (isset($card_object) &&
            ($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTO ||
            $conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCC ||
                $conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCCC)
        ) {
            $contacts_thirdparty_parent_email = extendedemail_get_contacts_thirdparty_parent_email($card_object->socid);
            $contacts_thirdparty_parent_email = array_values($contacts_thirdparty_parent_email);
            }
        // Get emails of the contacts of the card
        $object_contacts_email = [];
        if (isset($card_object) &&
            ($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTO ||
                $conf->global->EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCC ||
                $conf->global->EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCCC)
        ) {
            $tmpobject = $card_object;
            if ($card_object->element == 'shipping') {
                if (!empty($card_object->origin)) {
                    $card_object->fetch_origin();
                    if ($card_object->{$card_object->origin}->id > 0 && !empty($conf->{$card_object->origin}->enabled)) {
                        $tmpobject = $card_object->{$card_object->origin};
                    }
                }
            }
            $object_contacts = $tmpobject->liste_contact(-1, 'external');
            if (is_array($object_contacts) && count($object_contacts) > 0) {
                $hide_no_email = !empty($conf->global->EXTENDEDEMAIL_HIDE_NO_EMAIL);
                require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
                $contact_static = new Contact($db);

                $shipping_code = [];
                if ($conf->global->EXTENDEDEMAIL_SHIPPING_CONTACT_EMAIL_BY_DEFAULT &&
                    !empty($conf->global->EXTENDEDEMAIL_SHIPPING_CONTACT_CODES)
                ) {
                    $shipping_code = explode(',', $conf->global->EXTENDEDEMAIL_SHIPPING_CONTACT_CODES);
        }

                $idx = 1000;
                foreach ($object_contacts as $contact) {
                    if ($hide_no_email && empty($contact['email']))
                        continue;

                    $contact_static->civility_id = $contact['civility'];
                    $contact_static->lastname = $contact['lastname'];
                    $contact_static->firstname = $contact['firstname'];

                    $tmp = array('email' => $contact['email'], 'name' => $contact_static->getFullName($langs));
                    if (empty($contact['email'])) {
                        $tmp['email'] = $idx++;
                        $tmp['disabled'] = true;
                    } elseif (in_array($contact['code'], $shipping_code)) {
                        $selected_users_email_to_sendto[$contact['email']] = $contact['email'];
                    }

                    $object_contacts_email[$tmp['email']] = $tmp;
                }
                $object_contacts_email = array_values($object_contacts_email);
                $selected_users_email_to_sendto = array_values($selected_users_email_to_sendto);
            }
        }

        // General options
        $hide_no_email = !empty($conf->global->EXTENDEDEMAIL_HIDE_NO_EMAIL) ? 'true' : 'false';
        $max_options = $conf->global->EXTENDEDEMAIL_MAX_LINE_HIDE_LIST;

        // Force select emails already choiced
        if (!empty($_POST['sendto']) || !empty($_POST['sendtocc']) || !empty($_POST['sendtoccc'])) {
            $sendto = GETPOST('sendto', 'alpha');
            if (empty($sendto)) $selected_users_email_to_sendto = [];
            $sendtocc = GETPOST('sendto', 'alpha');
            if (empty($sendtocc)) $selected_users_email_to_sendtocc = [];
            $sendtoccc = GETPOST('sendto', 'alpha');
            if (empty($sendtoccc)) $selected_users_email_to_sendtoccc = [];
        }

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
            if ($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTO) $users_email_to_sendto = array_merge($users_email_to_sendto, $object_contacts_email);
            $users_email_to_sendto = !empty($users_email_to_sendto) ? json_encode($users_email_to_sendto) : '[]';
            $selected_users_email_to_sendto = !empty($selected_users_email_to_sendto) ? json_encode($selected_users_email_to_sendto) : '[]';
            $toreadonly = !empty($object->withtoreadonly) ? 'true' : 'false';
            print 'extendedemail_select_email("sendto", "receiver", "' . $sendto_label . '", ' . $selected_users_email_to_sendto . ', ' . $users_email_to_sendto . ', ' . $hide_no_email . ', ' . $toreadonly . ', ' . $max_options . ');';
        }
        // CC
        if (!empty($object->withtocc) || is_array($object->withtocc)) {
            $sendtocc_label = str_replace('"', '\\"', $form->textwithpicto($langs->trans("MailCC"), $langs->trans("YouCanUseCommaSeparatorForSeveralRecipients")));
            $users_email_to_sendtocc = ($conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTOCC ? $users_email : array());
            if ($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCC) $users_email_to_sendtocc = array_merge($users_email_to_sendtocc, $contacts_thirdparty_parent_email);
            if ($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCC) $users_email_to_sendtocc = array_merge($users_email_to_sendtocc, $object_contacts_email);
            $users_email_to_sendtocc = !empty($users_email_to_sendtocc) ? json_encode($users_email_to_sendtocc) : '[]';
            $selected_users_email_to_sendtocc = !empty($selected_users_email_to_sendtocc) ? json_encode($selected_users_email_to_sendtocc) : '[]';
            $toccreadonly = !empty($object->withtoccreadonly) ? 'true' : 'false';
            print 'extendedemail_select_email("sendtocc", "receivercc", "' . $sendtocc_label . '", ' . $selected_users_email_to_sendtocc . ', ' . $users_email_to_sendtocc . ', ' . $hide_no_email . ', ' . $toccreadonly . ', ' . $max_options . ');';
        }
        // CCC
        if (!empty($object->withtoccc) || is_array($object->withtoccc)) {
            $sendtoccc_label = str_replace('"', '\\"', $form->textwithpicto($langs->trans("MailCCC"), $langs->trans("YouCanUseCommaSeparatorForSeveralRecipients")));
            $users_email_to_sendtoccc = ($conf->global->EXTENDEDEMAIL_ADD_USER_TO_SENDTOCCC ? $users_email : array());
            if ($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_THIRDPARTY_PARENT_TO_SENDTOCCC) $users_email_to_sendtoccc = array_merge($users_email_to_sendtoccc, $contacts_thirdparty_parent_email);
            if ($conf->global->EXTENDEDEMAIL_ADD_CONTACTS_OF_OBJECT_TO_SENDTOCCC) $users_email_to_sendtoccc = array_merge($users_email_to_sendtoccc, $object_contacts_email);
            $users_email_to_sendtoccc = !empty($users_email_to_sendtoccc) ? json_encode($users_email_to_sendtoccc) : '[]';
            $selected_users_email_to_sendtoccc = !empty($selected_users_email_to_sendtoccc) ? json_encode($selected_users_email_to_sendtoccc) : '[]';
            $tocccreadonly = !empty($object->withtocccreadonly) ? 'true' : 'false';
            print 'extendedemail_select_email("sendtoccc", "receiverccc", "' . $sendtoccc_label . '", ' . $selected_users_email_to_sendtoccc . ', ' . $users_email_to_sendtoccc . ', ' . $hide_no_email . ', ' . $tocccreadonly . ', ' . $max_options . ');';
        }
        print '});';
        print '</script>';

        return 0;
    }
}