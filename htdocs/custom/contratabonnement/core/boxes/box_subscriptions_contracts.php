<?php
/* Copyright (C) 2015 MAXIME MANGIN  <maxime@tuxserv.fr>
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
 * 		\file       htdocs/contratabonnement/core/boxes/box_subscriptions_contracts.php
 * 		\ingroup    contracts
 * 		\brief      Module de generation de l'affichage de la box contrat d'abonnement
 */

include_once(DOL_DOCUMENT_ROOT."/core/boxes/modules_boxes.php");


class box_subscriptions_contracts extends ModeleBoxes {

    var $boxcode = "contratabonnement";
    var $boximg = "object_contract";
    var $boxlabel;
    var $depends = array("contrat");

    var $db;
    var $param;

    var $info_box_head = array();
    var $info_box_contents = array();


    /**
     *      \brief      Constructeur de la classe
     */
    function box_subscriptions_contracts()
    {
	global $langs;

	$langs->load("contracts");
	$langs->load("contratabonnement@contratabonnement");

	$this->boxlabel=$langs->trans("BoxSubscription");
    }

    /**
     *      \brief      Charge les donnees en memoire pour affichage ulterieur
     *      \param      $max        Nombre maximum d'enregistrements a charger
     */
    function loadBox($max=5)
    {
	global $user, $langs, $db, $conf;

	$this->max=$max;

	include_once(DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
	$contractstatic=new Contrat($db);

	$this->info_box_head = array('text' => $langs->trans("BoxSubscription",$max));

	if ($user->rights->contrat->lire)
	{

			$nbJoursAvant = $conf->global->SUBSCRIPTION_BOX_DAYS_BEFORE;
			$nbJourApres = $conf->global->SUBSCRIPTION_BOX_DAYS_AFTER;
			if ($nbJoursAvant=='' || $nbJoursAvant==null || $nbJoursAvant<=0) {
				$nbJoursAvant = 15;
            }
			if ($nbJourApres=='' || $nbJourApres==null || $nbJourApres<=0) {
				$nbJourApres=15;
            }

			$now=dol_now();

			$sql = "SELECT s.nom, s.rowid as socid,";
		$sql.= " c.rowid, c.ref, c.statut as fk_statut,";
		$sql.= " cat.montantperiode, ";
		$sql.= " CASE ca.periodepaiement  WHEN 0 THEN cat.datedebutperiode WHEN 1 THEN cat.datefinperiode END as datefacturation";
		$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."societe s ON c.fk_soc=s.rowid";
		if (!$user->rights->societe->client->voir && !$user->societe_id) {$sql.= " INNER JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";} // Gestion des droits
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratdet as cd ON cd.fk_contrat=c.rowid";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratabonnement as ca ON ca.fk_contratdet=cd.rowid";
		$sql.= " INNER JOIN ".MAIN_DB_PREFIX."contratabonnement_term as cat ON cat.fk_contratabonnement=ca.rowid";
		$sql.= " WHERE c.entity = ".$conf->entity;
			if (!$user->rights->societe->client->voir && !$user->societe_id) {$sql.= " AND sc.fk_user = " .$user->id;} // Gestion des droits
		$sql.= " AND cat.facture=0 AND ca.statut = 1 AND c.statut > 0 AND (";
		$sql.= " (ca.periodepaiement=0 AND (datediff(cat.datedebutperiode, NOW()) BETWEEN -".$nbJourApres." AND ".$nbJoursAvant.")) OR";
		$sql.= " (ca.periodepaiement=1 AND (datediff(cat.datefinperiode, NOW()) BETWEEN -".$nbJourApres." AND ".$nbJoursAvant.")))";
		$sql.= " ORDER BY datefacturation";
		$sql.= $db->plimit($max, 0);

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$objp = $db->fetch_object($resql);

					$datefacturation=$db->jdate($objp->datefacturation);

				$contractstatic->statut=$objp->fk_statut;
				$contractstatic->id=$objp->rowid;
                    $contractstatic->fetch_lines();

				$this->info_box_contents[$i][0] = array('td' => 'align="left" width="16"',
				'logo' => $this->boximg,
				'url' => dol_buildpath('/contratabonnement/fiche.php', 1).'?id='.$objp->rowid);

				$this->info_box_contents[$i][1] = array('td' => 'align="left"',
				'text' => ($objp->ref?$objp->ref:$objp->rowid),	// Some contracts have no ref
				'url' => dol_buildpath('/contratabonnement/fiche.php', 1).'?id='.$objp->rowid);

				$this->info_box_contents[$i][2] = array('td' => 'align="left" width="16"',
				'logo' => 'company',
				'url' => DOL_URL_ROOT."/societe/card.php?socid=".$objp->socid);

				$this->info_box_contents[$i][3] = array('td' => 'align="left"',
				'text' => dol_trunc($objp->nom,40),
				'url' => DOL_URL_ROOT."/societe/card.php?socid=".$objp->socid);

				$this->info_box_contents[$i][4] = array('td' => 'align="right"',
				'text' => dol_print_date($datefacturation,'day'));

					$this->info_box_contents[$i][5] = array('td' => 'align="right"',
				'text' => $objp->montantperiode.' HT');

				$this->info_box_contents[$i][6] = array('td' => 'align="right" nowrap="nowrap"',
				'text' => $contractstatic->getLibStatut(6),
				'asis'=>1);

				$nbJoursRetard = round(($now - strtotime($objp->datefacturation))/(60*60*24)-1);
				if($nbJoursRetard<=$nbJoursAvant && $nbJoursRetard>0 && strtotime($objp->datefacturation) < $now){ //Affichage du warning
						$this->info_box_contents[$i][7] = array('td' => 'align="left" width="16"',
						'text' => img_picto($nbJoursRetard.' '.$langs->trans("dayLate"),"warning"));
					}
					else{
						$this->info_box_contents[$i][7] = array('td' => 'align="left" width="16"',
						'text' => '');
					}
				$i++;
			}

			if ($num == 0) {$this->info_box_contents[$i][0] = array('td' => 'align="center"','text'=> '<img src="'.DOL_URL_ROOT.'/theme/eldy/img/weather/weather-clear.png" height="30" />');}
				else if (file_exists(dol_buildpath('/contratabonnement/facturation_masse.php'))) { // Facturation en masse
					$this->info_box_contents[$i][0] = array('td' => 'align="right" colspan="8"',
						'text' => $langs->trans("SubscriptionMassInvoicing"),
						'url' => dol_buildpath('/contratabonnement/facturation_masse.php', 1));
				}
		}
		else {dol_print_error($db);}
	}
	else {
		$this->info_box_contents[0][0] = array('td' => 'align="left"',
		'text' => $langs->trans("ReadPermissionNotAllowed"));
	}
    }

    function showBox($head = NULL, $contents = NULL, $nooutput = 0) {
        parent::showBox($this->info_box_head, $this->info_box_contents);
    }
}

?>
