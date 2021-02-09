<?php
/* Copyright (C) 2014-2018	Charlie Benke		<charlie@patas-monkey.com>
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
 * 	\file	   	htdocs/transporteur/class/actions_transporteur.class.php
 * 	\ingroup	transporteur
 * 	\brief	  	Fichier de la classe des actions/hooks de transporteur
 */

dol_include_once('/transporteur/class/transporteur.class.php');

class ActionsTransporteur // extends CommonObject
{

	// ajout du bouton pour les frais de transport
	function addMoreActionsButtons($parameters, $object, $action)
	{
		global $langs, $conf, $db;
		$lines = empty($object->lines) ? array() : $object->lines;

		// si on a des lignes de saisies et que l'on est � l'�tat brouillon
		if (count($lines) > 0 && (int) $object->statut == 0) {

			$langs->load("transporteur@transporteur");
			$objectelement = array("propal", "commande", "facture");
			$arrayaction= array("", "addline", "modif", "updateline", "updateligne", "addtransporteur");

			if (in_array($object->element, $objectelement) && in_array($action, $arrayaction)) {
				$tranporteurStatic =  New Transporteur($db);
				$totalweight=$tranporteurStatic->getWeight($object->lines);
				// si on a des choses � transporter...
				if ($totalweight > 0) {

					// on affiche le poid et le montant
					print '<div class="inline-block divButAction">';
					print '<table class="noborder" width="100%">';
					print '<tr class="liste_titre">';
					print '<td align=center width=100px >'.$langs->trans("TotalWeight").'</td>';
					print '<td align=center>'.$langs->trans("EstimedPrice").'</td></tr>';
					print '<tr><td align=center>'.price($totalweight)." Kg".'</td>';

					$totalprice=$tranporteurStatic->getPrice($totalweight, $object);
					// si on a trouv� un prix
					if ($totalprice > 0) {
						$transportfranco=$conf->global->TRANSPORTEUR_FRANCO;
						// on ne prend pas en compte le prix du transport pour le Franco
						$transportfranco+=$totalprice;
						//var_dump($object->total_ht);
						if ($conf->global->TRANSPORTEUR_FRANCO_TTC == 0)
							$restToFranco=$transportfranco - $object->total_ht;
						else
							$restToFranco=$transportfranco - $object->total_ttc;

						if ($restToFranco > 0)
							print '<td align="right" title="'.$langs->trans("RestBeforeFranco", price($restToFranco)).'">';
						else
							print '<td align="right" bgcolor="orange">';

						print price($totalprice).'</td></tr>';
					} else {
						// sinon on affiche une erreur
						print '<td align="center" bgcolor="#FF9393" width=200px>';
						if ($totalprice == -2)
							print $langs->trans("NoTransportPriceFind");
						else
							print $langs->trans("NoTransportPriceDefined");
						print '</td></tr>';
					}
					print '<tr><td colspan=2 align=center>';
					print '<a class="butAction" style="background:#E87400;" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=addtransporteur"';
					print '>'.$langs->trans("AddTransporteur");

					print '</a></td></tr>';
					print '</table>';

					print '</div><br>';
				}
			}
		}

		return 0;
	}

	// Ajout de la ligne de transport
	function doActions($parameters, $object, $action)
	{
		global $db; //, $langs;

		if ($action == "addtransporteur") {
			$tranporteurStatic =  New Transporteur($db);
			$tranporteurStatic->add_transporteur($object);
		}
		return 0;
	}
}
