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

/**
 *	\file       htdocs/extendedemail/lib/functions_extendedemail.lib.php
 *	\brief      Ensemble de fonctions de substitutions pour le module Extended Email
 * 	\ingroup	extendedemail
 */

function extendedemail_completesubstitutionarray(&$substitutionarray, $langs, $object, $parameters) {
    global $conf, $db, $langs, $user;

    if ($object->element == 'user' && $parameters['needforkey'] == 'SUBSTITUTION_EXTENDEDEMAILUSERGENERICEMAILTABLABEL') {
        $nbgenericemails = 0;
        $sql = "SELECT fk_generic_email as nb FROM ".MAIN_DB_PREFIX."extentedemail_user_generic_email WHERE fk_user = {$object->id}";
        if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity) {
            $sql .= " AND entity IS NOT NULL";
        } else {
            $sql .= " AND entity IN (0," . $conf->entity . ")";
        }
        $sql .= " GROUP BY fk_generic_email";

        $resql = $db->query($sql);
        if ($resql) {
            $nbgenericemails = $db->num_rows($resql);
        } else {
            dol_print_error($db);
        }

        $substitutionarray['EXTENDEDEMAILUSERGENERICEMAILTABLABEL'] = $langs->trans("ExtendedEmailGenericEmailTab") . ($nbgenericemails > 0 ? ' <span class="badge">' . ($nbgenericemails) . '</span>' : '');
    }
}