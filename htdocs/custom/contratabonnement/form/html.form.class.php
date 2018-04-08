<?php
/* Copyright (c) 2014 Maxime MANGIN	<maxime@tuxserv.fr>
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
 *	\file       htdocs/contratabonnement/form/html.form.class.php
 *	\brief      File of class with all html predefined components
 */


/**
 *	\class      Form
 *	\brief      Classe permettant la generation de composants html
 */
class FormAbonnement extends Form
{
	var $db;
	var $error;

	// Cache arrays
	var $cache_types_paiements=array();
	var $cache_conditions_paiements=array();

	var $tva_taux_value;
	var $tva_taux_libelle;


	/**
	 *	\brief     Constructor
	 *	\param     DB      Database handler
	 */
	function FormAbonnement($DB)
	{

		$this->db = $DB;
		return 1;
	}



    function dateUsVersDateFr($date){
        if (empty($date)) {return null;}
        return substr($date,8,2).'/'.substr($date,5,2).'/'.substr($date,0,4);
    }

    /**
	 *  \brief    Return list of services in contract
	 *  \param    	id_contrat
	 *  \param		rowiddefault : Select a row
	 */
	function select_services_contrat($id_contrat)
	{
		global $langs;
		$sql = "SELECT ";
		$sql.= " p.label,";
		$sql.= " cd.rowid, cd.description, cd.date_ouverture, cd.date_ouverture_prevue, ";
		$sql.= " p.ref";
		$sql.= " FROM ".MAIN_DB_PREFIX."contratdet as cd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p";
		$sql.= " ON p.rowid = cd.fk_product";
		$sql.= " WHERE cd.fk_contrat=".$id_contrat." AND cd.statut <> 5";
		$sql.= " ORDER BY cd.rowid";
		$result=$this->db->query($sql);

		$num = $this->db->num_rows($result);
		if($num){
			print '<select name="cbContratDet" >';
				print '<option value="-1">'.$langs->trans("ProductsServicesFound").'</option>';
				for($i=0;$i<$num;$i++){
					$resultat = $this->db->fetch_object($result);
					// Sanitize tooltip
					$resultat->description=str_replace("\\","\\\\",$resultat->description);
					$resultat->description=str_replace("\r","",$resultat->description);
					$resultat->description=str_replace("<br>\n","<br>",$resultat->description);
					$resultat->description=str_replace("\n","",$resultat->description);
					$resultat->description=str_replace('"',"&quot;",$resultat->description);

                    $datedebut = "";
                    if ($resultat->date_ouverture) {$datedebut = substr($resultat->date_ouverture, 0, 10);}
                    else {$datedebut = substr($resultat->date_ouverture_prevue, 0, 10);}

					if($resultat->ref!='' && $resultat->ref!=null){
						if($resultat->description) {
							print '<option data-startdate="'.dateUsVersDateFr($datedebut).'" value='.$resultat->rowid.' style="position: absolute;z-index: 3000;opacity: 0.85;" title="'.$resultat->description.'" >'.$resultat->ref.' - '.$resultat->label.'</option>';
                        }
                        else {
							print '<option data-startdate="'.dateUsVersDateFr($datedebut).'" value='.$resultat->rowid.'>'.$resultat->ref.' - '.$resultat->label.'</option>';
                        }
					}
                    else {
						print '<option data-startdate="'.dateUsVersDateFr($datedebut).'"  value='.$resultat->rowid.'>'.dol_trunc($resultat->description,40).'...</option>';
                    }
				}

			print '</select>';

		}
		else{
			print $langs->trans("noServiceInContract");
		}

	}

     /**
	 *  \brief    Return list of services in command
	 *  \param    	idcommande
	 *  \param		rowiddefault : Select a row
	 */
	function select_services_fournisseurcommande($id_commande)
	{
		global $langs;
		$sql = "SELECT ";
		$sql.= " p.label,";
		$sql.= " cd.rowid, cd.description, cd.date_start, ";
		$sql.= " p.ref";
		$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseurdet as cd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p";
		$sql.= " ON p.rowid = cd.fk_product";
		$sql.= " WHERE cd.fk_commande=".$id_commande;
		$sql.= " ORDER BY cd.rowid";
		$result=$this->db->query($sql);

		$num = $this->db->num_rows($result);
		if($num){
			print '<select name="cbContratDet" >';
				print '<option value="-1">'.$langs->trans("ProductsServicesFound").'</option>';
				for($i=0;$i<$num;$i++){
					$resultat = $this->db->fetch_object($result);
					// Sanitize tooltip
					$resultat->description=str_replace("\\","\\\\",$resultat->description);
					$resultat->description=str_replace("\r","",$resultat->description);
					$resultat->description=str_replace("<br>\n","<br>",$resultat->description);
					$resultat->description=str_replace("\n","",$resultat->description);
					$resultat->description=str_replace('"',"&quot;",$resultat->description);

                    $datedebut = "";
                    if ($resultat->date_start) {$datedebut = substr($resultat->date_start, 0, 10);}

					if($resultat->ref!='' && $resultat->ref!=null){
						if($resultat->description) {
							print '<option data-startdate="'.dateUsVersDateFr($datedebut).'" value='.$resultat->rowid.' style="position: absolute;z-index: 3000;opacity: 0.85;" title="'.$resultat->description.'" >'.$resultat->ref.' - '.$resultat->label.'</option>';
                        }
                        else {
							print '<option data-startdate="'.dateUsVersDateFr($datedebut).'" value='.$resultat->rowid.'>'.$resultat->ref.' - '.$resultat->label.'</option>';
                        }
					}
                    else {
						print '<option data-startdate="'.dateUsVersDateFr($datedebut).'"  value='.$resultat->rowid.'>'.dol_trunc($resultat->description,40).'...</option>';
                    }
				}

			print '</select>';

		}
		else{
			print $langs->trans("noServiceInContract");
		}

	}


	/**
	 *  \brief    Return list of frequency
	 */
	function select_frequence_repetition($rowiddefault=0) {
		global $langs;
		$sql = "SELECT ";
		$sql.= " f.rowid, f.nomfrequencerepetition, f.coeffrepetition";
		$sql.= " FROM ".MAIN_DB_PREFIX."frequence_repetition as f";
		$sql.= " ORDER BY f.coeffrepetition";
		$result=$this->db->query($sql);

		$num = $this->db->num_rows($result);
		if($num){
			print '<select name="cbFrequence">';
			for($i=0;$i<$num;$i++){
				$resultat = $this->db->fetch_object($result);
				if($rowiddefault == $resultat->rowid) {print '<option selected="true" value='.$resultat->rowid.'>'.$langs->trans($resultat->nomfrequencerepetition).'</option>';}
				else {print '<option value='.$resultat->rowid.'>'.$langs->trans($resultat->nomfrequencerepetition).'</option>';}
			}
			print '</select>';
		}else{print 'Aucune répétition disponible';}
	}

	/**
	 * \ brief Return duration of service
	 */
	function select_duration_contrat() {
		global $langs;
		print '<select name="cbDurationContrat">';
		print '<option value="720">'.$langs->trans("Hour").'</option>';
		print '<option value="30">'.$langs->trans("Day").'</option>';
		print '<option value="4.285714286">'.$langs->trans("Week").'</option>';
		print '<option value="1" selected="true">'.$langs->trans("Month").'</option>';
		print '<option value="0.333333333">'.$langs->trans("Trimester").'</option>';
		print '<option value="0.166666666">'.$langs->trans("HalfYear").'</option>';
		print '<option value="0.083333333">'.$langs->trans("Year").'</option>';
		print '</select>';
	}

	/**
	 *  \brief   show periode facturation
	 */
	function select_periode_facturation($rowiddefault = 0) {
		global $langs;
		print '<select name="cbPeriode">';
		if($rowiddefault==0){
			print '<option selected="true" value="0">'.$langs->trans("BegPeriod").'</option>';
			print '<option value="1">'.$langs->trans("EndPeriod").'</option>';
		}
		else{
			print '<option value="0">'.$langs->trans("BegPeriod").'</option>';
			print '<option selected="true" value="1">'.$langs->trans("EndPeriod").'</option>';
		}
		print '</select>';
	}



} //Fin de classe

?>
