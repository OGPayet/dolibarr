<?php
/* Copyright (C) 2014 Maxime MANGIN <maxime@tuxserv.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       contratabonnement/admin/contratabonnement_conf_masse_envoi.php
 *  \ingroup    contratabonnement
 *  \brief      Page d'administration/configuration du module contrat d'abonnement
 */


// Envoi en masse des factures
	print '</table><br/>';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '  <td style="width:100px">'.$langs->trans("SubscriptionMassInvoicing").'</td>';
	print '  <td style="width:400px"></td>';
	print '  <td style="width:80px">&nbsp;</td></tr>';

    if (file_exists("../facturation_masse_envoi.php")) {
        print "<form method=\"post\" action=\"contratabonnement_conf.php\">";
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print "<input type=\"hidden\" name=\"action\" value=\"objectMailMass\">";
        print "<tr ".$bc[$var].">";
        print '<td>'.$langs->trans("Object").'</td>';
        print '<td><input style="width:400px" type="text" name="value" class="flat" value="'.$conf->global->SUBSCRIPTION_MASS_MAIL_OBJECT.'"><br/>';
        print '<i>'.$langs->trans("variablesForMassObjectMailSubscription").'</i>';
        print '</td>';
        print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
        print '</tr>';
        print '</form>';

        print "<form method=\"post\" action=\"contratabonnement_conf.php\">";
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print "<input type=\"hidden\" name=\"action\" value=\"contentMailMass\">";
        print "<tr ".$bc[$var].">";
        print '<td>'.$langs->trans("Message").'</td>';
        print '<td ><textarea style="width:400px" rows="10" name="value" type="text" class="flat" >'.$conf->global->SUBSCRIPTION_MASS_MAIL_CONTENT.'</textarea><br />';
        print '<i>'.$langs->trans("variablesForMassMailSubscription").'</i>';
        print '</td>';
        print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
        print '</tr>';
        print '</form>';
    }
    else {
        print '<td><b>Module "envoi en masse" non installé</b></td>';
    }
?>
