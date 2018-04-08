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
 *  \file       htdocs/contratabonnement/admin/contratabonnement_conf.php
 *  \ingroup    contratabonnement
 *  \brief      Page d'administration/configuration du module contrat d'abonnement
 */


//Facturation sur les périodes civiles


        print "<form method=\"post\" action=\"contratabonnement_conf.php\">";
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print "<input type=\"hidden\" name=\"action\" value=\"facturationCivile\">";
        print "<tr ".$bc[$var].">";
        print '<td>'.$langs->trans("CivilBilling").'</td>';
        if (file_exists("../fiche_noncivile.php")) {
            print '<td align="right">'.$html->selectyesno('value',$conf->global->SUBSCRIPTION_USE_CIVIL_BILLING,1).'</td>';
            print '<td align="right"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
        }
        else {
            print '<td colspan="2"><b>Module "périodes non civiles" non installé</b></td>';
        }
        print '</tr>';
        print '</form>';


?>
