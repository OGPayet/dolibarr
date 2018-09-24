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

// $uploaddir may be defined (example to $conf->projet->dir_output."/";)
// $toselect may be defined

include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

if ($reshook == 0) {
    // Delete all the invoices test generated
    if (!$error && $massaction == 'delete_invoices_test' && $user->rights->facture->creer) {

    } // Generate invoices for the contracts
    elseif (!$error && $massaction == 'confirm_generate_invoices' && $confirm == 'yes' && $user->rights->facture->creer) {

    }
}
