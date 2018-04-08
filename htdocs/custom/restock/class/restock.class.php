<?php
/* Copyright (C) 2013-2017	Charlie BENKE		<charlie@patas-monkey.com>
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
 *	\file	   htdocs/restock/class/restock.class.php
 *	\ingroup	categorie
 *	\brief	  File of class to restock
 */

/**
 *	Class to manage Restock
 */
class Restock
{
	var $id;
	var $ref_product;
	var $libproduct;
	var $prixAchatHT;			// upercase
	var $prixVenteHT;			// upercase
	var $prixVenteCmdeHT;		// pour les commandes clients
	var $composedProduct;		// upercase
	var $onBuyProduct;			// upercase
	var $stockQty=0;			// upercase
	var $nbBillDraft=0;
	var $nbBillValidate=0;
	var $nbBillpartial=0;
	var $nbBillpaye=0;
	var $nbCmdeDraft=0;
	var $nbCmdeValidate=0;
	var $nbCmdepartial=0;
	var $nbCmdeClient=0;
	var $MntCmdeClient=0;		// upercase
	var $nbPropDraft=0;
	var $nbPropValidate=0;
	var $nbPropSigned=0;
	var $nbCmdFourn=0;

	// nouveau champs pour les commandes liées
	var $fk_commandedet;
	var $fk_product;
	var $fk_commande;
	var $date_commande;
	var $qty;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db	 Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	function get_array_product_cmde($tblRestock, $search_categ, $search_fourn, $statut, $onlyfactory=0, $year='', $month='')
	{
		global $conf;
		// on récupère les products des commandes
		$sql = 'SELECT DISTINCT cod.fk_product, sum(cod.qty) as nbCmde';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as co ON co.rowid = cod.fk_commande";
		if (! empty($search_fourn))
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON cod.fk_product = pfp.fk_product";
		// We'll need this table joined to the select in order to filter by categ
		if (! empty($search_categ))
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON cod.fk_product = cp.fk_product";
		$sql.= " WHERE co.entity = ".$conf->entity;
		$sql.= " AND cod.fk_product > 0";
		switch($conf->global->RESTOCK_PRODUCT_TYPE_SELECT) {
			case  1 :	// seulement product
				$sql.= " AND cod.product_type =0";
				break;
			case 2 :	// seulement service
				$sql.= " AND cod.product_type =1";
				break;
		}

		$sql.= " AND co.fk_statut =".$statut;
		if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
		if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
		if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
		$sql.= " GROUP BY cod.fk_product";
		dol_syslog(get_class($this)."::get_array_product_cmde sql=".$sql);
//		print $sql."//".$conf->global->RESTOCK_PRODUCT_TYPE_SELECT."<br>";
		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num) {
				// on met le compte du nombre de ligne car le tableau peu augmenter durant la boucle
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);
				// on regarde si on trouve déjà le produit dans le tableau
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblRestock[$j]->id == $objp->fk_product)
						$lineofproduct=$j;

				// si le produit est déja dans le tableau des produits
				if ($lineofproduct >= 0) {
					// on met à jours les données pour la partie commande
					if ($statut==0)
						$tblRestock[$lineofproduct]->nbCmdeDraft+= $objp->nbCmde;
					elseif ($statut==1)
						$tblRestock[$lineofproduct]->nbCmdeValidate+= $objp->nbCmde;
					else
						$tblRestock[$lineofproduct]->nbCmdepartial+= $objp->nbCmde;
				} else {
					// sinon on ajoute une ligne dans le tableau
					$tblRestock[$numlines] = new Restock($db);
					$tblRestock[$numlines]->id= $objp->fk_product;
					if ($statut==0)
						$tblRestock[$numlines]->nbCmdeDraft = $objp->nbCmde;
					elseif ($statut==1)
						$tblRestock[$numlines]->nbCmdeValidate = $objp->nbCmde;
					else
						$tblRestock[$numlines]->nbCmdepartial = $objp->nbCmde;
				}
				$i++;
			}
		}
		return $tblRestock;
	}

	function get_array_product_bill($tblRestock, $search_categ, $search_fourn, $statut, $onlyfactory=0, $year='', $month='')
	{
		global $conf;
		// on récupère les products des commandes
		$sql = 'SELECT DISTINCT fad.fk_product, sum(fad.qty) as nbBill';
		$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as fad";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as fa ON fa.rowid = fad.fk_facture";
		if (! empty($search_fourn))
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON fad.fk_product = pfp.fk_product";
		// We'll need this table joined to the select in order to filter by categ
		if (! empty($search_categ))
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON fad.fk_product = cp.fk_product";
		$sql.= " WHERE fa.entity = ".$conf->entity;
		$sql.= " AND fad.fk_product >0";
		if ($statut !=4)
			$sql.= " AND fa.fk_statut =".$statut;
		else
			$sql.= " AND fa.paye = 1 ";

		switch($conf->global->RESTOCK_PRODUCT_TYPE_SELECT) {
			case  1 :	// seulement product
				$sql.= " AND fad.product_type =0";
				break;
			case 2 :	// seulement service
				$sql.= " AND fad.product_type =1";
				break;
		}

		if ($month > 0) {
			if ($year > 0 ) {
				$sql.= " AND fa.datef BETWEEN '".$this->db->idate(dol_get_first_day($year, $month, false))."'";
				$sql.= " AND '".$this->db->idate(dol_get_last_day($year, $month, false))."'";
			} else
				$sql.= " AND date_format(fa.datef, '%m') = '".$month."'";
		} elseif ($year > 0) {
			$sql.= " AND fa.datef BETWEEN '".$db->idate(dol_get_first_day($year, 1, false))."'";
			$sql.= " AND '".$db->idate(dol_get_last_day($year, 12, false))."'";
		}


		if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
		if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
		if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
		$sql.= " GROUP BY fad.fk_product";
		dol_syslog(get_class($this)."::get_array_product_bill sql=".$sql);


		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num) {
				// on met le compte du nombre de ligne car le tableau peu augmenter durant la boucle
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);
				// on regarde si on trouve déjà le produit dans le tableau
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblRestock[$j]->id == $objp->fk_product)
						$lineofproduct=$j;
				// si le produit est déja dans le tableau des produits
				if ($lineofproduct >= 0) {
					// on met à jours les données
					if ($statut==0)
						$tblRestock[$lineofproduct]->nbBillDraft+= $objp->nbBill;
					elseif ($statut==1)
						$tblRestock[$lineofproduct]->nbBillValidate+= $objp->nbBill;
					elseif ($statut==3)
						$tblRestock[$lineofproduct]->nbBillpartial+= $objp->nbBill;
					else
						$tblRestock[$lineofproduct]->nbBillpaye+= $objp->nbBill;
				} else {
					// sinon on ajoute une ligne dans le tableau
					$tblRestock[$numlines] = new Restock($db);
					$tblRestock[$numlines]->id= $objp->fk_product;
					if ($statut==0)
						$tblRestock[$numlines]->nbBillDraft = $objp->nbBill;
					elseif ($statut==1)
						$tblRestock[$numlines]->nbBillValidate = $objp->nbBill;
					elseif ($statut==3)
						$tblRestock[$numlines]->nbBillpartial = $objp->nbBill;
					else
						$tblRestock[$numlines]->nbBillpaye = $objp->nbBill;
				}
				$i++;
			}
		}

		return $tblRestock;
	}

	function get_array_product_cmde_client($tblRestock, $rowid)
	{
		global $conf;

		// on récupère les products des commandes
		$sql = 'SELECT DISTINCT cod.fk_product, sum(cod.qty) as nbCmde, sum(total_ht) as MntCmde, count(*) as nblgn';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		$sql.= " WHERE cod.fk_commande=".$rowid;
		$sql.= " AND cod.fk_product > 0";
		switch($conf->global->RESTOCK_PRODUCT_TYPE_SELECT) {
			case  1 :	// seulement product
				$sql.= " AND cod.product_type =0";
				break;
			case 2 :	// seulement service
				$sql.= " AND cod.product_type =1";
				break;
		}

		$sql.= " GROUP BY cod.fk_product";
		dol_syslog(get_class($this)."::get_array_product_cmde_client sql=".$sql);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num) {
				// on met le compte du nombre de ligne car le tableau peu augmenter durant la boucle
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);

				// si il n'y a qu'une ligne associé au produit on mémorise la ligne de détail pour faire le lien
				if ($objp->nblgn == 1) {
					$sql = 'SELECT DISTINCT cod.rowid';
					$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
					$sql.= " WHERE cod.fk_commande=".$rowid;
					$sql.= " AND cod.fk_product=".$objp->fk_product;
					dol_syslog(get_class($this)."::get_array_product_cmde_client sql=".$sql);
//					print $sql;
					$resqldet = $this->db->query($sql);
					if ($resqldet) {
						$objcdet = $this->db->fetch_object($resqldet);
						$fk_commandedet = $objcdet->rowid;
					}
				} else
					$fk_commandedet = 0;

				// on regarde si on trouve déjà le produit dans le tableau
				for ($j = 0 ; $j < $numlines ; $j++)
					if ($tblRestock[$j]->id == $objp->fk_product)
						$lineofproduct=$j;

				// si le produit est déja dans le tableau des produits
				if ($lineofproduct >= 0) {
					$tblRestock[$lineofproduct]->nbCmdeClient+= $objp->nbCmde;
					$tblRestock[$lineofproduct]->MntCmdeClient+= $objp->MntCmde;
				} else {
					// sinon on ajoute une ligne dans le tableau
					$tblRestock[$numlines] = new Restock($db);
					$tblRestock[$numlines]->id= $objp->fk_product;
					$tblRestock[$numlines]->nbCmdeClient = $objp->nbCmde;
					$tblRestock[$numlines]->MntCmdeClient = $objp->MntCmde;
					$tblRestock[$numlines]->fk_commandedet = $fk_commandedet;
				}
				$i++;
			}
		}
		return $tblRestock;
	}

	function only_one_line_order_product_det($commandeid, $productid)
	{
		$fk_commandedet = 0;

		$sql = 'SELECT count(*) as nblgn';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		$sql.= " WHERE cod.fk_commande=".$commandeid;
		$sql.= " AND cod.fk_product=".$productid;
		$resqldet = $this->db->query($sql);
		if ($resqldet) {
			$objc = $this->db->fetch_object($resqldet);
			$nblgn=$objc->nblgn;
		}
		// si il n'y a qu'une ligne associé au produit on mémorise la ligne de détail pour faire le lien
		if ($nblgn == 1) {
			$sql = 'SELECT DISTINCT cod.rowid';
			$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
			$sql.= " WHERE cod.fk_commande=".$commandeid;
			$sql.= " AND cod.fk_product=".$productid;
			dol_syslog(get_class($this)."::get_array_product_cmde_client sql=".$sql);

			$resqldet = $this->db->query($sql);
			if ($resqldet) {
				$objcdet = $this->db->fetch_object($resqldet);
				$fk_commandedet = $objcdet->rowid;
			}
		}
		return $fk_commandedet;
	}

	// mise à jour du prix de vente fournisseur à partir du prix de vente du produit sur la commande
	function update_product_price_cmde_client($rowid, $idproduct)
	{
		global $conf;

		$sql = 'SELECT DISTINCT price';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		$sql.= " where cod.fk_commande=".$rowid;
		$sql.= " and cod.fk_product=".$idproduct;


		dol_syslog(get_class($this)."::update_product_price_cmde_client sql=".$sql);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$objp = $this->db->fetch_object($resql);
			$productprice=$objp->price;
			// on pondère
			$coef=$conf->global->RESTOCK_COEF_ORDER_CLIENT_FOURN/100;
			$productprice=$productprice * $coef;
			// on met à jour le prix fournisseur
			$sql= "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price ";
			$sql.= " SET price=".$productprice;
			$sql.= " , unitprice=".$productprice;
			$sql.= " where fk_product=".$idproduct;
			$resqlupdate = $this->db->query($sql);
			return 1;
		}
		return 0;
	}

	// mise à jour du prix de vente fournisseur à partir du prix de vente du produit sur la commande
	function add_contact_delivery_client($cmdeClientid, $cmdeFournId)
	{
		global $conf;

		$sql='select fk_socpeople from '.MAIN_DB_PREFIX.'element_contact';
		$sql.=" where fk_c_type_contact=102 and element_id=".$cmdeClientid;

		dol_syslog(get_class($this)."::add_contact_delivery_client sql=".$sql);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$objp = $this->db->fetch_object($resql);
			$fk_socpeople=$objp->fk_socpeople;
			// on ajoute le contact de livraison client à la commande fournisseur
			$sql= "Insert into ".MAIN_DB_PREFIX."element_contact";
			$sql.= " ( statut, fk_c_type_contact, element_id, fk_socpeople)";
			$sql.= " values (4, 145, ".$cmdeFournId.", ".$fk_socpeople.")";
			dol_syslog(get_class($this)."::add_contact_delivery_client insert sql=".$sql);
			$resqlinsert = $this->db->query($sql);
			return 1;
		}
		return 0;
	}

	function get_array_product_prop($tblRestock, $search_categ, $search_fourn, $statut, $onlyfactory=0, $year='', $month='')
	{
		global $conf;

		// on récupère les products des propales
		$sql = 'SELECT DISTINCT prd.fk_product, sum(prd.qty) as nbProp';
		$sql.= " FROM ".MAIN_DB_PREFIX."propaldet as prd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."propal as pr ON pr.rowid = prd.fk_propal";
		// We'll need this table joined to the select in order to filter by categ
		if (! empty($search_fourn))
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON prd.fk_product = pfp.fk_product";
		if (! empty($search_categ))
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON prd.fk_product = cp.fk_product";
		$sql.= " WHERE pr.entity = ".$conf->entity;
		$sql.= " AND prd.fk_product >0";
		switch($conf->global->RESTOCK_PRODUCT_TYPE_SELECT) {
			case  1 :	// seulement product
				$sql.= " AND prd.product_type =0";
				break;
			case 2 :	// seulement service
				$sql.= " AND prd.product_type =1";
				break;
		}

		$sql.= " AND pr.fk_statut =".$statut;
		if ($search_fourn > 0)   $sql.= " AND pfp.fk_soc = ".$search_fourn;
		if ($search_categ > 0)   $sql.= " AND cp.fk_categorie = ".$search_categ;
		if ($search_categ == -2) $sql.= " AND cp.fk_categorie IS NULL";
		$sql.= " GROUP BY prd.fk_product";
		dol_syslog(get_class($this)."::get_array_product_prop sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num) {
				$numlines=count($tblRestock);
				$lineofproduct = -1;
				$objp = $this->db->fetch_object($resql);
				// on regarde si on trouve déjà le produit dans le tableau
				for ($j = 0 ; $j < $numlines ; $j++) {
					if ($tblRestock[$j]->id == $objp->fk_product) {
						$lineofproduct=$j;
						//exit for;
					}
				}
				// si le produit est déja dans le tableau des produits
				if ($lineofproduct >= 0) {
					// on met à jours les données pour la partie commande
					if ($statut==0)
						$tblRestock[$lineofproduct]->nbPropDraft+= $objp->nbProp;
					elseif ($statut==1)
						$tblRestock[$lineofproduct]->nbPropValidate+= $objp->nbProp;
					else
						$tblRestock[$lineofproduct]->nbPropSigned+= $objp->nbProp;
				} else {
					// sinon on ajoute un nouveau produit dans le tableau
					$tblRestock[$numlines] = new Restock($db);
					$tblRestock[$numlines]->id= $objp->fk_product;
					if ($statut==0)
						$tblRestock[$numlines]->nbPropDraft = $objp->nbProp;
					elseif ($statut==1)
						$tblRestock[$numlines]->nbPropValidate = $objp->nbProp;
					else
						$tblRestock[$numlines]->nbPropSigned = $objp->nbProp;
				}
				$i++;
			}
		}
		return $tblRestock;
	}

	function enrichir_product($tblRestock)
	{
		global $conf;

		$numlines=count($tblRestock);
		for ($i = 0 ; $i < $numlines ; $i++) {
			// on récupère les infos des produits
			$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type, MIN(pfp.unitprice) as minsellprice';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON (p.rowid = pfp.fk_product";
			$sql.= " AND pfp.entity = ".$conf->entity.")";

			$sql.= " WHERE p.rowid=".$tblRestock[$i]->id;
			$sql.= ' GROUP by p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type';


			dol_syslog(get_class($this)."::enrichir_product sql=".$sql);
			$resql = $this->db->query($sql);
			if ($resql) {
				$objp = $this->db->fetch_object($resql);

				$tblRestock[$i]->ref_product=	$objp->ref;
				$tblRestock[$i]->libproduct=	$objp->label;
				$tblRestock[$i]->prixVenteHT=	$objp->price;
				$tblRestock[$i]->prixAchatHT=	$objp->minsellprice;
				$tblRestock[$i]->onBuyProduct=	$objp->tobuy;
				$tblRestock[$i]->fk_product_type=	$objp->fk_product_type;
				$tblRestock[$i]->stockQty= 		$objp->stock;
				$tblRestock[$i]->stockQtyAlert=	$objp->seuil_stock_alerte;
				// on calcul ici le prix de vente unitaire réel
				if ($tblRestock[$i]->nbCmdeClient > 0)
					$tblRestock[$i]->prixVenteCmdeHT = $tblRestock[$i]->MntCmdeClient/$tblRestock[$i]->nbCmdeClient;
			}

			// on regarde si il n'y pas de commande fournisseur en cours
			$sql = 'SELECT DISTINCT sum(cofd.qty) as nbCmdFourn';
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cofd";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur as cof ON cof.rowid = cofd.fk_commande";
			$sql.= " WHERE cof.entity = ".$conf->entity;
			$sql.= " AND cof.fk_statut = 3";
			$sql.= " and cofd.fk_product=".$tblRestock[$i]->id;
			dol_syslog(get_class($this)."::enrichir_product::cmde_fourn sql=".$sql);
			//print $sql;
			$resql = $this->db->query($sql);
			if ($resql) {
				$objp = $this->db->fetch_object($resql);
				$tblRestock[$i]->nbCmdFourn= $objp->nbCmdFourn;
			}
		}
		return $tblRestock;
	}

	// recherche récursive des composants avec filtrage sur les catégories
	function getcomponent($fk_parent, $qty, $search_categ=0, $search_fourn=0)
	{
		global $conf;

		$NotInOF=true;
		$components=array();
		$nbcomponent=0;
		// on regarde si factory est installé
		if ($conf->global->MAIN_MODULE_FACTORY) {

			$recursivitedeep=$conf->global->RESTOCK_RECURSIVITY_DEEP;

			// si on est pas dans trop loin dans la récursivité
			if ($recursivitedeep != "" && $maxlevel > $recursivitedeep) {
				print $langs->trans("RecursivityLimitReached", $fk_parent)." <br>";
				return $components;
			}

			$sql = 'SELECT fk_product_children as fk_product_fils, qty from '.MAIN_DB_PREFIX.'product_factory';
			$sql.= ' WHERE fk_product_father  = '.$fk_parent;

			$res = $this->db->query($sql);
			if ($res) {
				$num = $this->db->num_rows($res);
				if ($num > 0) {
					// si le produit à des composants
					$NotInOF=false;
					$i=0;
					while ($i < $num) {
						$objp = $this->db->fetch_object($res);

						$tblcomponent=$this->getcomponent(
										$objp->fk_product_fils, $objp->qty,
										$search_categ, $search_fourn,
										$maxlevel++
						);

						foreach ($tblcomponent as $lgncomponent) {
							$lineofproduct =-1;
							// on regarde si on trouve déjà le produit dans le tableau
							for ($j = 0 ; $j < $nbcomponent ; $j++)
								if ($components[$j][0] == $lgncomponent[0])
									$lineofproduct=$j;

							if ($lineofproduct >= 0) // on multiplie par la quantité du composant
								$components[$lineofproduct][1]+= $lgncomponent[1]*$qty;
							else {
								// on ajoute le composant trouvé au tableau des composants
								$components[$nbcomponent][0]=$lgncomponent[0];
								$components[$nbcomponent][1]=$lgncomponent[1]*$qty;
								$nbcomponent++;
							}
						}
						$i++;
					}
				}
			}
		}

		// dans les autres cas (produits virtuels ou de base
		if ($conf->global->PRODUIT_SOUSPRODUITS) {

			$recursivitedeep=$conf->global->RESTOCK_RECURSIVITY_DEEP;

			// si on est pas dans trop loin dans la récursivité
			if ($recursivitedeep != "" && $maxlevel > $recursivitedeep) {
				print $langs->trans("RecursivityLimitReached", $fk_parent)." <br>";
				return $components;
			}

			// On regarde dans les produits virtuels
			$sql = 'SELECT fk_product_fils, qty ';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'product_association';
			$sql.= ' WHERE fk_product_pere  = '.$fk_parent;

			$res = $this->db->query($sql);
			if ($res) {
				$num = $this->db->num_rows($res);
				if ($num > 0) {
					// si le produit à des composants
					$i=0;
					while ($i < $num) {
						$objp = $this->db->fetch_object($res);
						// on regarde récursivement si les composants ont eux-même des composants
						$tblcomponent=$this->getcomponent(
										$objp->fk_product_fils, $objp->qty,
										$search_categ, $search_fourn,
										$maxlevel++
						);

						foreach ($tblcomponent as $lgncomponent) {
							$lineofproduct =-1;
							// on regarde si on trouve déjà le produit dans le tableau
							for ($j = 0 ; $j < $nbcomponent ; $j++)
								if ($components[$j][0] == $lgncomponent[0])
									$lineofproduct=$j;

							if ($lineofproduct >= 0) {
								// on multiplie par la quantité du composant
								$components[$lineofproduct][1]+= $lgncomponent[1]*$qty;
							} else {
								// on ajoute le composant trouvé au tableau des composants
								$components[$nbcomponent][0]=$lgncomponent[0];
								$components[$nbcomponent][1]=$lgncomponent[1]*$qty;
								$nbcomponent++;
							}
						}
						$i++;
					}
				}
			}
		}

		// pas d'enfant, c'est un produit de base, il est sont propre composant unique
		if ($NotInOF) {
			$components[0][0]=$fk_parent;
			$components[0][1]=$qty;
		}

		return $components;
	}
}


class RestockCmde
{
	// nouveau champs pour les commandes liées
	var $fk_commandedet;
	var $fk_product;
	var $fk_commande;
	var $date_commande;
	var $qty;
	var $ref_product;
	var $libproduct;
	var $prixVenteHT;
	var $prixAchatHT;
	var $onBuyProduct;
	var $fk_product_type;
	var $stockQty;
	var $stockQtyAlert;
	var $nbCmdFourn;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db	 Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	function get_array_product_cmdedet($tblRestock, $search_categ, $search_fourn, $morefilter="", $year="", $month="")
	{
		global $conf;

		// on récupère les products des commandes
		$sql = 'SELECT co.rowid as fk_commande, co.date_commande, cod.rowid as fk_commandedet,';
		$sql.= ' cod.fk_product, cod.qty as nbCmde';
		$sql.= " FROM ".MAIN_DB_PREFIX."commande as co ";
		$sql.= " , ".MAIN_DB_PREFIX."commandedet as cod";
		if (! empty($search_fourn))
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON cod.fk_product = pfp.fk_product";
		// We'll need this table joined to the select in order to filter by categ
		if (! empty($search_categ))
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX."categorie_product as cp ON cod.fk_product = cp.fk_product";
		$sql.= " WHERE co.entity = ".$conf->entity;
		$sql.= " AND cod.fk_product >0";
		switch($conf->global->RESTOCK_PRODUCT_TYPE_SELECT) {
			case  1 :	// seulement product
				$sql.= " AND cod.product_type =0";
				break;
			case 2 :	// seulement service
				$sql.= " AND cod.product_type =1";
				break;
		}

		$sql.= " AND co.rowid = cod.fk_commande";
		$sql.= " AND (cod.fk_commandefourndet is null OR cod.fk_commandefourndet =0)";

		if ($morefilter)			$sql.= " AND ".$morefilter;

		if ($month > 0) {
			if ($year > 0 ) {
				$sql.= " AND co.date_commande BETWEEN '".$this->db->idate(dol_get_first_day($year, $month, false))."'";
				$sql.= " AND '".$this->db->idate(dol_get_last_day($year, $month, false))."'";
			} else
				$sql.= " AND date_format(co.date_commande, '%m') = '".$month."'";
		} elseif ($year > 0) {
			$sql.= " AND co.date_commande BETWEEN '".$db->idate(dol_get_first_day($year, 1, false))."'";
			$sql.= " AND '".$db->idate(dol_get_last_day($year, 12, false))."'";
		}


		if ($search_fourn > 0)   	$sql.= " AND pfp.fk_soc = ".$search_fourn;
		if ($search_categ > 0)   	$sql.= " AND cp.fk_categorie = ".$search_categ;
		if ($search_categ == -2) 	$sql.= " AND cp.fk_categorie IS NULL";

		dol_syslog(get_class($this)."::get_array_product_cmdedet sql=".$sql);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0;
			$num = $this->db->num_rows($resql);

			while ($i < $num) {
				$objp = $this->db->fetch_object($resql);

				// sinon on ajoute une ligne dans le tableau
				$tblRestock[$i] = new RestockCmde($db);
				$tblRestock[$i]->fk_product= $objp->fk_product;
				$tblRestock[$i]->fk_commandedet= $objp->fk_commandedet;
				$tblRestock[$i]->fk_commande= $objp->fk_commande;
				$tblRestock[$i]->date_commande= $objp->date_commande;
				$tblRestock[$i]->qty = $objp->nbCmde;

				$i++;
			}
		}
		return $tblRestock;
	}

	function enrichir_product($tblRestock)
	{
		global $conf;

		$numlines=count($tblRestock);
		for ($i = 0 ; $i < $numlines ; $i++) {
			// on récupère les infos des produits
			$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type, pfp.remise_percent, MIN(pfp.unitprice) as minsellprice';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON (p.rowid = pfp.fk_product";
			$sql.= " AND pfp.entity = ".$conf->entity.")";
			$sql.= " WHERE p.rowid=".$tblRestock[$i]->fk_product;
			if (! empty($conf->product->enabled) && ! empty($conf->service->enabled)) {
				if ($conf->global->RESTOCK_PRODUCT_TYPE_SELECT == 1)
					$sql.= " AND p.fk_product_type = 0";	// product
				elseif ($conf->global->RESTOCK_PRODUCT_TYPE_SELECT == 2)
					$sql.= " AND p.fk_product_type = 1";  // service
			}

			$sql.= ' GROUP by p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type';


			dol_syslog(get_class($this)."::enrichir_product sql=".$sql);
			$resql = $this->db->query($sql);
			if ($resql) {
				$objp = $this->db->fetch_object($resql);

				$tblRestock[$i]->ref_product=	$objp->ref;
				$tblRestock[$i]->libproduct=	$objp->label;
				$tblRestock[$i]->prixVenteHT=	$objp->price;
				$tblRestock[$i]->prixAchatHT=	$objp->minsellprice;
				$tblRestock[$i]->onBuyProduct=	$objp->tobuy;
				$tblRestock[$i]->fk_product_type=	$objp->fk_product_type;
				$tblRestock[$i]->stockQty= 		$objp->stock;
				$tblRestock[$i]->stockQtyAlert=	$objp->seuil_stock_alerte;
				// on calcul ici le prix de vente unitaire réel
//				if ($tblRestock[$i]->nbCmdeClient > 0)
//					$tblRestock[$i]->prixVenteCmdeHT = $tblRestock[$i]->MntCmdeClient/$tblRestock[$i]->nbCmdeClient;
			}

			// on regarde si il n'y pas de commande fournisseur en cours
			$sql = 'SELECT DISTINCT sum(cofd.qty) as nbCmdFourn';
			$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cofd";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur as cof ON cof.rowid = cofd.fk_commande";
			$sql.= " WHERE cof.entity = ".$conf->entity;
			$sql.= " AND cof.fk_statut = 3";
			$sql.= " and cofd.fk_product=".$tblRestock[$i]->id;
			dol_syslog(get_class($this)."::enrichir_product::cmde_fourn sql=".$sql);
			//print $sql;
			$resql = $this->db->query($sql);
			if ($resql) {
				$objp = $this->db->fetch_object($resql);
				$tblRestock[$i]->nbCmdFourn= $objp->nbCmdFourn;
			}
		}
		return $tblRestock;
	}

	function fetchdet($fk_commandedet, $qtysel)
	{
		$sql = 'SELECT co.rowid as fk_commande, co.date_commande, cod.rowid as fk_commandedet, cod.fk_product';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cod";
		$sql.= " ,".MAIN_DB_PREFIX."commande as co ";
		$sql.= " WHERE cod.rowid = ".$fk_commandedet;
		$sql.= " and co.rowid = cod.fk_commande";
		dol_syslog(get_class($this)."::fetchdet sql=".$sql);
//		print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$objp = $this->db->fetch_object($resql);

			$this->fk_product= $objp->fk_product;
			$this->fk_commandedet= $objp->fk_commandedet;
			$this->fk_commande= $objp->fk_commande;
			$this->date_commande= $objp->date_commande;
			$this->qty = $qtysel;

			// on récupère les infos des produits
			$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type, MIN(pfp.unitprice) as minsellprice';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'product as p';
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp ON (p.rowid = pfp.fk_product";
			$sql.= " AND pfp.entity = ".$conf->entity.")";
			$sql.= " WHERE p.rowid=".$objp->fk_product;
			$sql.= ' GROUP by p.rowid, p.ref, p.label, p.price, p.stock, p.tobuy,';
			$sql.= ' p.seuil_stock_alerte, p.fk_product_type';

			dol_syslog(get_class($this)."::fetchdet_enrichir_product sql=".$sql);
			$resql = $this->db->query($sql);
			if ($resql) {
				$objp = $this->db->fetch_object($resql);

				$this->ref_product=	$objp->ref;
				$this->libproduct=	$objp->label;
				$this->prixVenteHT=	$objp->price;
				$this->prixAchatHT=	$objp->minsellprice;
				$this->onBuyProduct=	$objp->tobuy;
				$this->fk_product_type=	$objp->fk_product_type;
				$this->stockQty= 		$objp->stock;
				$this->stockQtyAlert=	$objp->seuil_stock_alerte;
			}
		}
	}

	function deletelink($fk_commandefourn, $user)
	{
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';

		// on se positionne sur la commande fournisseur
		$cfdelete = new CommandeFournisseur($this->db);
		$cfdelete->fetch($fk_commandefourn);

		// on boucle sur les lignes la commande fournisseur
		foreach ($cfdelete->lines as $cfdetline) {
			// on remet à zéro la ligne de la commande client associé
			$sql = "UPDATE ".MAIN_DB_PREFIX."commandedet";
			$sql.= " SET fk_commandefourndet=0";
			$sql.= " WHERE fk_commandefourndet=".$cfdetline->id;
			$this->db->query($sql);
		}

		// on lance enfin la suppression de la commande fournisseur
		$cfdelete->delete($user);

		return 1;
	}
}