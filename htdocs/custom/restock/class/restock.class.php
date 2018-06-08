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
	var $PrixAchatHT;
	var $PrixVenteHT;
	var $PrixVenteCmdeHT;		// pour les commandes clients
	var $ComposedProduct;
	var $OnBuyProduct;
	var $StockQty=0;
	var $nbBillDraft=0;
	var $nbBillValidate=0;
	var $nbBillpartial=0;
	var $nbBillpaye=0;
	var $nbCmdeDraft=0;
	var $nbCmdeValidate=0;
	var $nbCmdepartial=0;
	var $nbCmdeClient=0;
	var $MntCmdeClient=0;
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

		$sql='select * from '.MAIN_DB_PREFIX.'element_contact';
		$sql.=" where element_id=".$cmdeClientid;


		dol_syslog(get_class($this)."::add_contact_delivery_client sql=".$sql);
		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$i=0;
			$num = $this->db->num_rows($resql);
			while ($i < $num) {
				$objp = $this->db->fetch_object($resql);
				$fk_socpeople=$objp->fk_socpeople;
				$type_contact=$objp->fk_c_type_contact;
				if($type_contact == 91) {
					$type_contact_supplier = 140;
				} else if($type_contact == 100) {
					$type_contact_supplier = 142;
				} else if($type_contact == 101) {
					$type_contact_supplier = 143;
				} else if($type_contact == 102) {
					$type_contact_supplier = 145;
				}
				// on ajoute le contact de livraison client  la commande fournisseur
				$sql= "Insert into ".MAIN_DB_PREFIX."element_contact";
				$sql.= " ( statut, fk_c_type_contact, element_id, fk_socpeople)";
				$sql.= " values (4, '".$type_contact_supplier."', ".$cmdeFournId.", ".$fk_socpeople.")";
				dol_syslog(get_class($this)."::add_contact_delivery_client insert sql=".$sql);
				$resqlinsert = $this->db->query($sql);
				$i++;
			}

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
				$tblRestock[$i]->PrixVenteHT=	$objp->price;
				$tblRestock[$i]->PrixAchatHT=	$objp->minsellprice;
				$tblRestock[$i]->OnBuyProduct=	$objp->tobuy;
				$tblRestock[$i]->fk_product_type=	$objp->fk_product_type;
				$tblRestock[$i]->StockQty= 		$objp->stock;
				$tblRestock[$i]->StockQtyAlert=	$objp->seuil_stock_alerte;
				// on calcul ici le prix de vente unitaire réel
				if ($tblRestock[$i]->nbCmdeClient > 0)
					$tblRestock[$i]->PrixVenteCmdeHT = $tblRestock[$i]->MntCmdeClient/$tblRestock[$i]->nbCmdeClient;
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

	/** OpenDSI **/
	function selectInputMethodRestock($selected='',$htmlname='source_id',$addempty=0)
	{
		global $langs;

        $listofmethods=array();

		$sql = "SELECT rowid, code, libelle as label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_input_method";
		$sql.= " WHERE active = 1";

		dol_syslog(get_class($this)."::selectInputMethod", LOG_DEBUG);
		$resql=$this->db->query($sql);

		if (!$resql) {
			dol_print_error($this->db);
			return -1;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$listofmethods[$obj->rowid] = $langs->trans($obj->code) != $obj->code ? $langs->trans($obj->code) : $obj->label;
		}


		return $listofmethods;
	}


    /**
     *     Show a confirmation HTML form or AJAX popup.
     *     Easiest way to use this is with useajax=1.
     *     If you use useajax='xxx', you must also add jquery code to trigger opening of box (with correct parameters)
     *     just after calling this method. For example:
     *       print '<script type="text/javascript">'."\n";
     *       print 'jQuery(document).ready(function() {'."\n";
     *       print 'jQuery(".xxxlink").click(function(e) { jQuery("#aparamid").val(jQuery(this).attr("rel")); jQuery("#dialog-confirm-xxx").dialog("open"); return false; });'."\n";
     *       print '});'."\n";
     *       print '</script>'."\n";
     *
     *     @param  	string		$page        	   	Url of page to call if confirmation is OK
     *     @param	string		$title       	   	Title
     *     @param	string		$question    	   	Question
     *     @param 	string		$action      	   	Action
     *	   @param  	array		$formquestion	   	An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
     * 	   @param  	string		$selectedchoice  	"" or "no" or "yes"
     * 	   @param  	int			$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
     *     @param  	int			$height          	Force height of box
     *     @param	int			$width				Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
     *     @return 	string      	    			HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
     */
    function formconfirmRestock($page, $title, $question, $action, $formquestions='', $selectedchoice="", $useajax=0, $height=200, $width=500)
    {
        global $langs,$conf;
        global $useglobalvars;

        $more='';
        $formconfirm='';
        $inputok=array();
        $inputko=array();
		$form=new Form($this->db);

        // Clean parameters
        $newselectedchoice=empty($selectedchoice)?"no":$selectedchoice;
        if ($conf->browser->layout == 'phone') $width='95%';

        if (is_array($formquestions) && ! empty($formquestions))
        {
		// First add hidden fields and value
			foreach($formquestions as $formquestion) {
				foreach ($formquestion as $key => $input)
				{
					if (is_array($input) && ! empty($input))
					{
						if ($input['type'] == 'hidden')
						{
							$more.='<input type="hidden" id="'.$input['name'].'" name="'.$input['name'].'" value="'.dol_escape_htmltag($input['value']).'">'."\n";
						}
					}
				}
			}

		// Now add questions
            $more.='<table class="paddingtopbottomonly" width="100%">'."\n";
			foreach($formquestions as $formquestion) {
				$more.='<tr><td colspan="3">'.(! empty($formquestion['text'])?$formquestion['text']:'').'</td></tr>'."\n";
				foreach ($formquestion as $key => $input)
				{
					if (is_array($input) && ! empty($input))
					{
						$size=(! empty($input['size'])?' size="'.$input['size'].'"':'');

						if ($input['type'] == 'text')
						{
							$more.='<tr><td>'.$input['label'].'</td><td colspan="2" align="left"><input type="text" class="flat" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'" /></td></tr>'."\n";
						}
						else if ($input['type'] == 'password')
						{
							$more.='<tr><td>'.$input['label'].'</td><td colspan="2" align="left"><input type="password" class="flat" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'" /></td></tr>'."\n";
						}
						else if ($input['type'] == 'select')
						{
							$more.='<tr><td>';
							if (! empty($input['label'])) $more.=$input['label'].'</td><td valign="top" colspan="2" align="left">';
							$more.=$form->selectarray($input['name'],$input['values'],$input['default'],1);
							$more.='</td></tr>'."\n";
						}
						else if ($input['type'] == 'checkbox')
						{
							$more.='<tr>';
							$more.='<td>'.$input['label'].' </td><td align="left">';
							$more.='<input type="checkbox" class="flat" id="'.$input['name'].'" name="'.$input['name'].'"';
							if (! is_bool($input['value']) && $input['value'] != 'false') $more.=' checked';
							if (is_bool($input['value']) && $input['value']) $more.=' checked';
							if (isset($input['disabled'])) $more.=' disabled';
							$more.=' /></td>';
							$more.='<td align="left">&nbsp;</td>';
							$more.='</tr>'."\n";
						}
						else if ($input['type'] == 'radio')
						{
							$i=0;
							foreach($input['values'] as $selkey => $selval)
							{
								$more.='<tr>';
								if ($i==0) $more.='<td class="tdtop">'.$input['label'].'</td>';
								else $more.='<td>&nbsp;</td>';
								$more.='<td width="20"><input type="radio" class="flat" id="'.$input['name'].'" name="'.$input['name'].'" value="'.$selkey.'"';
								if ($input['disabled']) $more.=' disabled';
								$more.=' /></td>';
								$more.='<td align="left">';
								$more.=$selval;
								$more.='</td></tr>'."\n";
								$i++;
							}
						}
						else if ($input['type'] == 'date')
						{
							$more.='<tr><td>'.$input['label'].'</td>';
							$more.='<td colspan="2" align="left">';
							$more.=$form->select_date($input['value'],$input['name'],1,1,0,'',1,1,1);
							$more.='</td></tr>'."\n";
							$formquestion[] = array('name'=>$input['name'].'day');
							$formquestion[] = array('name'=>$input['name'].'month');
							$formquestion[] = array('name'=>$input['name'].'year');
							$formquestion[] = array('name'=>$input['name'].'hour');
							$formquestion[] = array('name'=>$input['name'].'min');
						}
						else if ($input['type'] == 'other')
						{
							$more.='<tr><td>';
							if (! empty($input['label'])) $more.=$input['label'].'</td><td colspan="2" align="left">';
							$more.=$input['value'];
							$more.='</td></tr>'."\n";
						}
						else if ($input['type'] == 'dispatch') {
							$more.='<table class="noborder" width="100%">';
							$order_fourn_list = explode(',',$input['value']);
							foreach($order_fourn_list as $order_fourn) {
								$object = new CommandeFournisseur($this->db);
								$object->fetch($order_fourn);

								$entrepot = new Entrepot($this->db);
								$listwarehouses = $entrepot->list_array(1);

								$formproduct = new FormProduct($this->db);

								// Set $products_dispatched with qty dispatched for each product id
								$products_dispatched = array();
								$sql = "SELECT l.rowid, cfd.fk_product, sum(cfd.qty) as qty";
								$sql .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch as cfd";
								$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_fournisseurdet as l on l.rowid = cfd.fk_commandefourndet";
								$sql .= " WHERE cfd.fk_commande = " . $object->id;
								$sql .= " GROUP BY l.rowid, cfd.fk_product";

								$resql = $this->db->query($sql);
								if ($resql) {
									$num = $this->db->num_rows($resql);
									$i = 0;

									if ($num) {
										while ( $i < $num ) {
											$objd = $this->db->fetch_object($resql);
											$products_dispatched[$objd->rowid] = price2num($objd->qty, 5);
											$i++;
										}
									}
									$this->db->free($resql);
								}

								$sql = "SELECT l.rowid, l.fk_product, l.subprice, l.remise_percent, SUM(l.qty) as qty,";
								$sql .= " p.ref, p.label, p.tobatch";
								$sql .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet as l";
								$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON l.fk_product=p.rowid";
								$sql .= " WHERE l.fk_commande = " . $object->id;
								if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
									$sql .= " AND l.product_type = 0";
								$sql .= " GROUP BY p.ref, p.label, p.tobatch, l.rowid, l.fk_product, l.subprice, l.remise_percent"; // Calculation of amount dispatched is done per fk_product so we must group by fk_product
								$sql .= " ORDER BY p.ref, p.label";

								$resql = $this->db->query($sql);
								if ($resql) {
									$num = $this->db->num_rows($resql);
									$i = 0;

									if ($num) {
										$more.= '<tr class="liste_titre">';

										$more.= '<td>' . $langs->trans("Description") . '</td>';
										$more.= '<td></td>';
										$more.= '<td></td>';
										$more.= '<td></td>';
										$more.= '<td align="right">' . $langs->trans("QtyOrdered") . '</td>';
										$more.= '<td align="right">' . $langs->trans("QtyDispatchedShort") . '</td>';
										$more.= '<td align="right">' . $langs->trans("QtyToDispatchShort") . '</td>';
										$more.= '<td width="32"></td>';
										$more.= '<td align="right">' . $langs->trans("Warehouse") . '</td>';
										$more.= "</tr>\n";
									}

									$nbfreeproduct = 0;		// Nb of lins of free products/services
									$nbproduct = 0;			// Nb of predefined product lines to dispatch (already done or not) if SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED is off (default)
															// or nb of line that remain to dispatch if SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED is on.

									$var = false;
									while ( $i < $num ) {
										$objp = $this->db->fetch_object($resql);

										// On n'affiche pas les produits libres
										if (! $objp->fk_product > 0) {
											$nbfreeproduct++;
										} else {
											$remaintodispatch = price2num($objp->qty - (( float ) $products_dispatched[$objp->rowid]), 5); // Calculation of dispatched
											if ($remaintodispatch < 0)
												$remaintodispatch = 0;

											if ($remaintodispatch || empty($conf->global->SUPPLIER_ORDER_DISABLE_STOCK_DISPATCH_WHEN_TOTAL_REACHED)) {
												$nbproduct++;

												// To show detail cref and description value, we must make calculation by cref
												// $more.= ($objp->cref?' ('.$objp->cref.')':'');
												// if ($objp->description) $more.= '<br>'.nl2br($objp->description);
												$suffix = '_'.$order_fourn.'_' . $i;

												$more.= "\n";
												$more.= '<!-- Line to dispatch ' . $suffix . ' -->' . "\n";
												$more.= '<tr class="oddeven">';

												$linktoprod = '<a href="' . DOL_URL_ROOT . '/product/fournisseurs.php?id=' . $objp->fk_product . '">' . img_object($langs->trans("ShowProduct"), 'product') . ' ' . $objp->ref . '</a>';
												$linktoprod .= ' - ' . $objp->label . "\n";

												if (! empty($conf->productbatch->enabled)) {
													if ($objp->tobatch) {
														$more.= '<td colspan="4">';
														$more.= $linktoprod;
														$more.= "</td>";
													} else {
														$more.= '<td>';
														$more.= $linktoprod;
														$more.= "</td>";
														$more.= '<td colspan="3">';
														$more.= $langs->trans("ProductDoesNotUseBatchSerial");
														$more.= '</td>';
													}
												} else {
													$more.= '<td colspan="4">';
													$more.= $linktoprod;
													$more.= "</td>";
												}

												// Define unit price for PMP calculation
												$up_ht_disc = $objp->subprice;
												if (! empty($objp->remise_percent) && empty($conf->global->STOCK_EXCLUDE_DISCOUNT_FOR_PMP))
													$up_ht_disc = price2num($up_ht_disc * (100 - $objp->remise_percent) / 100, 'MU');

												// Qty ordered
												$more.= '<td align="right">' . $objp->qty . '</td>';

												// Already dispatched
												$more.= '<td align="right">' . $products_dispatched[$objp->rowid] . '</td>';
												$type = 'dispatch';
												$more.= '<td align="right">';
												$more.= '</td>';     // Qty to dispatch
												$more.= '<td>';
												//$more.= img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'onClick="addDispatchLine(' . $i . ',\'' . $type . '\')"');
												$more.= '</td>';      // Dispatch column
												$more.= '<td></td>'; // Warehouse column
												$more.= '</tr>';

												$more.= '<tr class="oddeven" name="' . $type . $suffix . '">';
												$more.= '<td colspan="6">';
												$more.= '<input name="fk_commandefourndet' . $suffix . '" type="hidden" value="' . $objp->rowid . '">';
												$more.= '<input name="product' . $suffix . '" type="hidden" value="' . $objp->fk_product . '">';

												$more.= '<!-- This is a up (may include discount or not depending on STOCK_EXCLUDE_DISCOUNT_FOR_PMP. will be used for PMP calculation) -->';
												if (! empty($conf->global->SUPPLIER_ORDER_EDIT_BUYINGPRICE_DURING_RECEIPT)) // Not tested !
												{
													$more.= $langs->trans("BuyingPrice").': <input class="maxwidth75" name="pu' . $suffix . '" type="text" value="' . price2num($up_ht_disc, 'MU') . '">';
												}
												else
												{
													$more.= '<input class="maxwidth75" name="pu' . $suffix . '" type="hidden" value="' . price2num($up_ht_disc, 'MU') . '">';
												}

												// hidden fields for js function
												$more.= '<input id="qty_ordered' . $suffix . '" type="hidden" value="' . $objp->qty . '">';
												$more.= '<input id="qty_dispatched' . $suffix . '" type="hidden" value="' . ( float ) $products_dispatched[$objp->rowid] . '">';
												$more.= '</td>';

												// Qty to dispatch
												$more.= '<td align="right">';
												$more.= '<input id="qty' . $suffix . '" name="qty' . $suffix . '" type="text" size="8" value="' . (GETPOST('qty' . $suffix) != '' ? GETPOST('qty' . $suffix) : $remaintodispatch) . '">';
												$more.= '</td>';

												$more.= '<td>';

												$type = 'dispatch';
												$more.= img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLine(' . $i . ',\'' . $type . '\')"');

												$more.= '</td>';

												// Warehouse
												$more.= '<td align="right">';
												if (count($listwarehouses) > 1) {
													$more.= $formproduct->selectWarehouses(GETPOST("entrepot" . $suffix), "entrepot" . $suffix, '', 1, 0, $objp->fk_product, '', 1);
												} elseif (count($listwarehouses) == 1) {
													$more.= $formproduct->selectWarehouses(GETPOST("entrepot" . $suffix), "entrepot" . $suffix, '', 0, 0, $objp->fk_product, '', 1);
												} else {
													$langs->load("errors");
													$more.= $langs->trans("ErrorNoWarehouseDefined");
												}
												$more.= "</td>\n";

												$more.= "</tr>\n";
											}
										}
										$i++;
									}
									$this->db->free($resql);
								} else {
									dol_print_error($this->db);
								}
							}
							$more.='</table>';
						}
					}
				}
			}
            $more.='</table>'."\n";
        }

		// JQUI method dialog is broken with jmobile, we use standard HTML.
		// Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
		// See page product/card.php for example
        if (! empty($conf->dol_use_jmobile)) $useajax=0;
		if (empty($conf->use_javascript_ajax)) $useajax=0;

        if ($useajax)
        {
            $autoOpen=true;
            $dialogconfirm='dialog-confirm';
            $button='';
            if (! is_numeric($useajax))
            {
                $button=$useajax;
                $useajax=1;
                $autoOpen=false;
                $dialogconfirm.='-'.$button;
            }
            $pageyes=$page.(preg_match('/\?/',$page)?'&':'?').'action='.$action.'&confirm=yes';
            $pageno=($useajax == 2 ? $page.(preg_match('/\?/',$page)?'&':'?').'confirm=no':'');
            // Add input fields into list of fields to read during submit (inputok and inputko)
            if (is_array($formquestions))
            {
				foreach($formquestions as $formquestion) {
					foreach ($formquestion as $key => $input)
					{
						//print "xx ".$key." rr ".is_array($input)."<br>\n";
						if (is_array($input) && isset($input['name'])) array_push($inputok,$input['name']);
						if (isset($input['inputko']) && $input['inputko'] == 1) array_push($inputko,$input['name']);
					}
				}
            }
			// Show JQuery confirm box. Note that global var $useglobalvars is used inside this template
            $formconfirm.= '<div id="'.$dialogconfirm.'" title="'.dol_escape_htmltag($title).'" style="display: none;">';
            if (! empty($more)) {
		$formconfirm.= '<div class="confirmquestions">'.$more.'</div>';
            }
            $formconfirm.= ($question ? '<div class="confirmmessage">'.img_help('','').' '.$question . '</div>': '');
            $formconfirm.= '</div>'."\n";

            $formconfirm.= "\n<!-- begin ajax form_confirm page=".$page." -->\n";
            $formconfirm.= '<script type="text/javascript">'."\n";
            $formconfirm.= 'jQuery(document).ready(function() {
            $(function() {
		$( "#'.$dialogconfirm.'" ).dialog(
		{
                    autoOpen: '.($autoOpen ? "true" : "false").',';
			if ($newselectedchoice == 'no')
			{
						$formconfirm.='
						open: function() {
					$(this).parent().find("button.ui-button:eq(2)").focus();
						},';
			}
				$formconfirm.='
                    resizable: false,
                    height: "'.$height.'",
                    width: "'.$width.'",
                    modal: true,
                    closeOnEscape: false,
                    buttons: {
                        "'.dol_escape_js($langs->transnoentities("Yes")).'": function() {
				var options="";
				var inputok = '.json_encode($inputok).';
				var pageyes = "'.dol_escape_js(! empty($pageyes)?$pageyes:'').'";
				if (inputok.length>0) {
					$.each(inputok, function(i, inputname) {
						var more = "";
						if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
					    if ($("#" + inputname).attr("type") == "radio") { more = ":checked"; }
						var inputvalue = $("#" + inputname + more).val();
						if (typeof inputvalue == "undefined") { inputvalue=""; }
						options += "&" + inputname + "=" + inputvalue;
					});
				}
				var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
				//alert(urljump);
					if (pageyes.length > 0) { location.href = urljump; }
                            $(this).dialog("close");
                        },
                        "'.dol_escape_js($langs->transnoentities("No")).'": function() {
				var options = "";
				var inputko = '.json_encode($inputko).';
				var pageno="'.dol_escape_js(! empty($pageno)?$pageno:'').'";
				if (inputko.length>0) {
					$.each(inputko, function(i, inputname) {
						var more = "";
						if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
						var inputvalue = $("#" + inputname + more).val();
						if (typeof inputvalue == "undefined") { inputvalue=""; }
						options += "&" + inputname + "=" + inputvalue;
					});
				}
				var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
				//alert(urljump);
					if (pageno.length > 0) { location.href = urljump; }
                            $(this).dialog("close");
                        }
                    }
                }
                );

		var button = "'.$button.'";
		if (button.length > 0) {
			$( "#" + button ).click(function() {
				$("#'.$dialogconfirm.'").dialog("open");
				});
                }
            });
            });
            </script>';
            $formconfirm.= "<!-- end ajax form_confirm -->\n";
        }
        else
        {
		$formconfirm.= "\n<!-- begin form_confirm page=".$page." -->\n";

            $formconfirm.= '<form method="POST" action="'.$page.'" class="notoptoleftroright">'."\n";
            $formconfirm.= '<input type="hidden" name="action" value="'.$action.'">'."\n";
            $formconfirm.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";

            $formconfirm.= '<table width="100%" class="valid">'."\n";

            // Line title
            $formconfirm.= '<tr class="validtitre"><td class="validtitre" colspan="3">'.img_picto('','recent').' '.$title.'</td></tr>'."\n";

            // Line form fields
            if ($more)
            {
                $formconfirm.='<tr class="valid"><td class="valid" colspan="3">'."\n";
                $formconfirm.=$more;
                $formconfirm.='</td></tr>'."\n";
            }

            // Line with question
            $formconfirm.= '<tr class="valid">';
            $formconfirm.= '<td class="valid">'.$question.'</td>';
            $formconfirm.= '<td class="valid">';
            $formconfirm.= $form->selectyesno("confirm",$newselectedchoice);
            $formconfirm.= '</td>';
            $formconfirm.= '<td class="valid" align="center"><input class="button valignmiddle" type="submit" value="'.$langs->trans("Validate").'"></td>';
            $formconfirm.= '</tr>'."\n";

            $formconfirm.= '</table>'."\n";

            $formconfirm.= "</form>\n";
            $formconfirm.= '<br>';

            $formconfirm.= "<!-- end form_confirm -->\n";
        }

        return $formconfirm;
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
	var $PrixVenteHT;
	var $PrixAchatHT;
	var $OnBuyProduct;
	var $fk_product_type;
	var $StockQty;
	var $StockQtyAlert;
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
				$tblRestock[$i]->PrixVenteHT=	$objp->price;
				$tblRestock[$i]->PrixAchatHT=	$objp->minsellprice;
				$tblRestock[$i]->OnBuyProduct=	$objp->tobuy;
				$tblRestock[$i]->fk_product_type=	$objp->fk_product_type;
				$tblRestock[$i]->StockQty= 		$objp->stock;
				$tblRestock[$i]->StockQtyAlert=	$objp->seuil_stock_alerte;
				// on calcul ici le prix de vente unitaire réel
//				if ($tblRestock[$i]->nbCmdeClient > 0)
//					$tblRestock[$i]->PrixVenteCmdeHT = $tblRestock[$i]->MntCmdeClient/$tblRestock[$i]->nbCmdeClient;
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
				$this->PrixVenteHT=	$objp->price;
				$this->PrixAchatHT=	$objp->minsellprice;
				$this->OnBuyProduct=	$objp->tobuy;
				$this->fk_product_type=	$objp->fk_product_type;
				$this->StockQty= 		$objp->stock;
				$this->StockQtyAlert=	$objp->seuil_stock_alerte;
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