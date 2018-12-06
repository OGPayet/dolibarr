<?php
/* Copyright (C) 2014-2017		Charlene BENKE	<charlie@patas-monkey.com>
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
 *	\file	   htdocs/customlink/class/customlink.class.php
 *	\ingroup	tools
 *	\brief	  File of class to customlink moduls
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class to manage members type
 */
class Customlink extends CommonObject
{
	public $table_element = '';

	var $rowid;
	var $fk_source;
	var $ref_source;
	var $type_source;
	var $typename_source;
	var $fk_target;
	var $fk_soc_source;
	var $fk_soc_target;
	var $ref_target;
	var $type_target;
	var $typename_target;

	/**
	 *	Constructor
	 *
	 *	@param 		DoliDB		$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}

	function getUrlofLink($objecttype, $objectkey, $showtiers=0)
	{
		global $langs;
		$langs->load($objecttype);
		$object = $this->getobjectclass($objecttype);
		if (is_object($object)) {
			$ret = $object->fetch($objectkey);
			if ($ret < 0)
				return $ret;
		} else
			return "";

		if ($showtiers==0) {
			// on verifie que l'élément possède les méthodes avant de les appeler
			$ret=($object->element?$langs->trans($object->element):"");
			if (method_exists($object, 'getNomUrl'))
				$ret.=" ".$object->getNomUrl(1);
			if (method_exists($object, 'getLibStatut'))
				$ret.=" ".$object->getLibStatut(4);
			return $ret;
		}

		if ($object->element == 'factory') {
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			$soc = new Product($this->db);
			$soc->fetch($object->fk_product);
		} else {
			require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			$soc = new Societe($this->db);
			$soc->fetch($object->socid);
		}

		return $object->getNomUrl(1)." (".$soc->getNomUrl(1).") ".$object->getLibStatut(4);
	}


	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param	  int			$withpicto	  Add picto into link
	 *	@param	  int			$option		 Where point the link (0=> main card, 1,2 => shipment)
	 *	@param	  int			$max		  	Max length to show
	 *	@param	  int			$short			Use short labels
	 *	@return	 string		  			String with URL
	 */
	function getNomUrl($withpicto=0, $option=0, $max=0, $short=0)
	{
		global $langs;
		$result='';
		$url = dol_buildpath('/customlink/fiche.php?id='.$this->rowid, 1);
		if ($short) return $url;

		$linkstart = '<a href="'.$url.'">';
		$linkend='</a>';

		$picto='customlink@customlink';
		$label=$langs->trans("ShowCustomlink");

		if ($withpicto) $result.=($linkstart.img_object($label, $picto).$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$linkstart.$this->rowid.$linkend;
		return $result;
	}

	function getNomUrlTag($withpicto=0, $option=0, $max=0, $short=0)
	{
		global $langs;
		$result='';
		$url = dol_buildpath('/customlink/fichetag.php?id='.$this->rowid, 1);
		if ($short) return $url;

		$linkstart = '<a href="'.$url.'">';
		$linkend='</a>';

		$picto='customlink@customlink';
		$label=$langs->trans("ShowCustomlink");

		if ($withpicto) $result.=($linkstart.img_object($label, $picto).$linkend);
		if ($withpicto && $withpicto != 2) $result.=' ';
		$result.=$linkstart.$this->rowid.$linkend;
		return $result;
	}

	// récupère la class d'un objet à partir de son type
	function getobjectclass($objecttype)
	{
		global $langs;

		// pour le moment on travail en mode dur, on le fera ensuite sur la base
		$module = $element = $subelement = $objecttype;
		if ($objecttype != 'order_supplier'
			&& $objecttype != 'invoice_supplier'
			&& preg_match('/^([^_]+)_([^_]+)/i', $objecttype, $regs)) {
			$module = $element = $regs[1];
			$subelement = $regs[2];
		}
		$classpath = $element.'/class';

		// To work with non standard path
		if ($objecttype == 'facture') {
			$classpath = 'compta/facture/class';
		} elseif ($objecttype == 'propal') {
			$classpath = 'comm/propal/class';
		} elseif ($objecttype == 'shipping') {
			$classpath = 'expedition/class'; $subelement = 'expedition'; $module = 'expedition_bon';
		} elseif ($objecttype == 'delivery') {
			$classpath = 'livraison/class';
			$subelement = 'livraison'; $module = 'livraison_bon';
		} elseif ($objecttype == 'invoice_supplier' || $objecttype == 'order_supplier') {
			$classpath = 'fourn/class'; $module = 'fournisseur';
		} elseif ($objecttype == 'order_supplier') {
			$classpath = 'fourn/class';
		} elseif ($objecttype == 'fichinter') {
			$classpath = 'fichinter/class';
			$subelement = 'fichinter'; $module = 'ficheinter';
		} elseif ($objecttype == 'chargesociales') {
			$classpath = 'compta/sociales/class';
			$subelement = 'chargesociales'; $module = 'chargesociales';
		} elseif ($objecttype == 'project') {
			$classpath = 'projet/class';
			$subelement = 'project'; $module = 'projet';
		} elseif ($objecttype == 'subscription') {
			$classpath = 'adherents/class';
			$subelement = 'subscription'; $module = 'adherent';
		} elseif ($objecttype == 'task') {
			$classpath = 'projet/class';
			$subelement = 'task'; $module = 'projet';
		} elseif ($objecttype == 'contratabonnement') {
			// TODO ajout temporaire - MAXIME MANGIN
			$classpath = 'contrat/class';
			$subelement = 'contrat'; $module = 'contratabonnement';
		}

		$classfile = strtolower($subelement); $classname = ucfirst($subelement);
		if ($objecttype == 'invoice_supplier') {
			$classfile = 'fournisseur.facture'; $classname = 'FactureFournisseur';
		} else if ($objecttype == 'order_supplier') {
			$classfile = 'fournisseur.commande'; $classname = 'CommandeFournisseur';
		}

		dol_include_once('/'.$classpath.'/'.$classfile.'.class.php');
		$langs->load($objecttype);
		if (class_exists($classname)) {
			$object = new $classname($this->db);
			return $object;
		} else
			return null;
	}

	// retourne l'id d'un élément à partir de son type et sa référence
	function get_idlink($type, $ref)
	{
		// on récupère la classe à partir du type
		$classlink = $this->getobjectclass($type);
		// et on récupère l'id de la ref associé

		if ($classlink->element !='') {

			if ($classlink->element == "ticketsup")
				$ret = $classlink->fetch('', '', $ref);
			elseif ($classlink->element == "subscription") {
				$ret = $ref;
				$classlink->rowid = $ref;
			} else
				$ret = $classlink->fetch('', $ref);

			if ($ret >= 0)
				if ($classlink->rowid)
					return $classlink->rowid;
				else
					return $classlink->id;
			else
				return $ret;
		}
		else
			return 0;
	}

	// retourne l'id de la société à partir d'une référence
	function get_idsoc($refsoc)
	{
		// on récupère la classe à partir du type
		$companystatic=new Societe($this->db);
		$ret = $companystatic->fetch('', $refsoc);   //nom société
		if ($ret >= 0)
			return $companystatic->id;
		$ret = $companystatic->fetch('', '', $refsoc);   // ref externe
		if ($ret >= 0)
			return $companystatic->id;
		$ret = $companystatic->fetch('', '', '', $refsoc);   // ref interne
		if ($ret >= 0)
			return $companystatic->id;
		else
			return 0;
	}

	// retourne l'id de la société associé à la facture de départ (toujours fournisseur)
	function get_idsoc_supplierbill($fk_facture)
	{
		require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
		$facturestatic=new FactureFournisseur($this->db);

		$ret = $facturestatic->fetch($fk_facture);
		if ($ret >= 0)
			return $facturestatic->socid;
		else
			return 0;
	}

	// retourne l'id de la société associé à la facture cible (fournisseur ou client)
	function get_idsoc_bill($fk_facture)
	{
		// on récupère la classe selon le type
		if ($this->type_target == "facture") {
			require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
			$facturestatic=new Facture($this->db);
		} elseif ($this->type_target == "invoice_supplier") {
			require_once(DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.facture.class.php");
			$facturestatic=new FactureFournisseur($this->db);
		} else	// pas de client pour factory
			return 0;

		$ret = $facturestatic->fetch($fk_facture);
		if ($ret >= 0)
			return $facturestatic->socid;
		else
			return 0;
	}


	/**
	 *  Fonction qui permet de creer la liaison
	 *
	 *  @param	  User		$user		User making creation
	 *  @return	 						>0 if OK, < 0 if KO
	 */
	function create($user)
	{
		global $langs;

		$this->db->begin();
		// normalement c'est renseigné mais au cas ou...
		if (!$this->fk_source)
			$this->fk_source = $this->get_idlink($this->type_source, $this->ref_source);
		if (!$this->fk_target )
			$this->fk_target = $this->get_idlink($this->type_target, $this->ref_target);

		if ($this->fk_source > 0 && $this->fk_target > 0) {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
			$sql.= "fk_source, sourcetype, fk_target, targettype";
			$sql.= ") VALUES (";
			$sql.= " ".$this->fk_source.", '".$this->type_source."'";
			$sql.= ", ".$this->fk_target.", '".$this->type_target."'";
			$sql.= ")";

			dol_syslog(get_class($this)."::create sql=".$sql);
			$result = $this->db->query($sql);
			if ($result) {
				$this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."element_element");
				$this->db->commit();
				return $this->rowid ;
			} else {
				$this->error=$this->db->error().' sql='.$sql;
				$this->db->rollback();
				return -1;
			}
		} else {
			// pb sur une des ref qui n'existe pas
			$this->error=$langs->trans("BadRefValue");
			$this->db->rollback();
			return 0;
		}
	}

	/**
	 *  Fonction qui permet de creer la liaison
	 *
	 *  @param	  User		$user		User making creation
	 *  @return	 						>0 if OK, < 0 if KO
	 */
	function createtag($user)
	{
		$this->db->begin();
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_tag (";
		$sql.= "entity, tag, fk_element, element) VALUES (";
		$sql.= " 1, '".$this->tag."', ".$this->fk_source;
		$sql.= ", '".$this->type_source."'";
		$sql.= ")";

		dol_syslog(get_class($this)."::create sql=".$sql);
		$result = $this->db->query($sql);
		if ($result) {
			$this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."element_tag");
			$this->db->commit();
			return $this->rowid ;
		} else {
			$this->error=$this->db->error().' sql='.$sql;
			$this->db->rollback();
			return -1;
		}
	}

	function addventil($subprice, $tva_tx, $qty, $label, $datev)
	{
		//global $conf, $langs;

		$qty=price2num($qty);

		$subprice=price2num($subprice);
		if ($tva_tx)
			$tva_tx = price2num($tva_tx);
		else
			$tva_tx = 0;
		$label=trim($label);

		switch ($this->type_target) {
			case "facture" :
				$fk_facture_typelink = "0";
				break;
			case "invoice_supplier" :
				$fk_facture_typelink = "1";
				break;
			case "factory" :
				$fk_facture_typelink = "2";
				break;
		}

		// on récupère la société de l'émetteur et du destinataire
		$total_ht= $subprice * $qty;

		if ($tva_tx == 0)
			$total_tva= 0;
		else
			$total_tva= $total_ht * ($tva_tx / 100);
		$total_ttc=$total_ht + $total_tva;

		$this->db->begin();
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_fourn_ventil (";
		$sql.= " entity, fk_facture_fourn, fk_facture_link, fk_facture_typelink,";
		$sql.= " fk_socid_fourn, fk_socid_link,";
		$sql.= " datev, subprice, tva_tx, qty, label, total_ht, total_tva, total_ttc";
		$sql.= " ) VALUES (";
		$sql.= " 1, ".$this->fk_source.", ".$this->fk_target.", ".$fk_facture_typelink;
		$sql.= ", ".$this->get_idsoc_supplierbill($this->fk_source).", ".$this->get_idsoc_bill($this->fk_target);
		$sql.= ", ".($datev? $this->db->idate($datev):"null");
		$sql.= ", " .$subprice.", ".$tva_tx.", ".$qty.", '".$label."'";
		$sql.= ", ".$total_ht.", ".$total_tva.", ".$total_ttc;
		$sql.= ")";
//print $sql;
		dol_syslog(get_class($this)."::addventil sql=".$sql);
		$result = $this->db->query($sql);
		if ($result) {
			$this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX."facture_fourn_ventil");
			$this->db->commit();
			return $this->rowid ;
		} else {
			$this->error=$this->db->error().' sql='.$sql;
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Fonction qui permet de recuperer l'élément
	 *
	 *  @param 		int		$rowid		Id of the element type to load
	 *  @return		int					<0 if KO, >0 if OK
	 */
	function fetch($rowid=0)
	{
//		global $user;

		$sql = "SELECT rowid, fk_source, fk_target, sourcetype, targettype";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_element as el";
		$sql .= " WHERE el.rowid = ".$rowid;

		dol_syslog(get_class($this)."::fetch sql=".$sql);

		$resql=$this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->rowid		= $obj->rowid;
				$this->fk_source	= $obj->fk_source;
				$this->type_source	= $obj->sourcetype;
				$this->fk_target	= $obj->fk_target;
				$this->type_target	= $obj->targettype;

				return 1;
			}
		} else {
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}

	function delete($user)
	{
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_element";
		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog("CustomLink::delete sql=".$sql);
		if ( $this->db->query($sql) ) {
			$this->db->commit();
			return 1;
		} else {
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -2;
		}
	}

	function deleteTag($user)
	{
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_tag";
		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog("CustomLink::deleteTag sql=".$sql);
		if ( $this->db->query($sql) ) {
			$this->db->commit();
			return 1;
		} else {
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -2;
		}
	}

	function deleteVentilation($user)
	{
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."facture_fourn_ventil";
		$sql.= " WHERE rowid = ".$this->rowid;

		dol_syslog("CustomLink::deleteVentilation sql=".$sql);
		if ( $this->db->query($sql) ) {
			$this->db->commit();
			return 1;
		} else {
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -2;
		}
	}
}