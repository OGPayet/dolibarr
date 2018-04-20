<?php
/* Copyright (C) 2014	Maxime MANGIN	<maxime@tuxserv.fr>
 * Copyright (C) 2012	Regis Houssin	<regis@dolibarr.fr>
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
 *  \file       /retourproduits/admin/retourproduits_conf.php
 *  \ingroup    contract
 *  \brief      Page d'administration/configuration du module contrat d'abonnement
 */

$res=@include("../../main.inc.php");					// For root directory
if (! $res) $res=@include("../../../main.inc.php");		// For "custom" directory

require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("admin");
$langs->load("products");
$langs->load("retourproduits@retourproduits");

// Security check
if (!$user->admin) {accessforbidden();}


/*
 * Affiche page
 */

llxHeader('',$langs->trans("ProductSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("RetourProduitsSetUp"),$linkback,'setup');

$html = new Form($db);
$var = true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print "  <td>".$langs->trans("ParametersOfSubscribBox")."</td>\n";
print "  <td align=\"right\" width=\"60\">".$langs->trans("Value")."</td>\n";
print "  <td width=\"80\">&nbsp;</td></tr>\n";

/*
 * Formulaire parametres divers
 */


$var=!$var;


$db->close();

llxFooter('$Date: 2012/07/10 15:00:00');
?>
