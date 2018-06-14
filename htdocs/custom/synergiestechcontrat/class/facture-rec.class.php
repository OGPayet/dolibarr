<?php

/* Copyright (C) 2016		 Oscss-Shop       <support@oscss-shop.fr>
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
 * or see http://www.gnu.org/
 */



// class OSEContact extends Contact {


namespace CORE;
dol_include_once('/compta/facture/class/facture-rec.class.php');


namespace CORE\FRAMEWORK;
use \CORE\FRAMEWORK\FactureRec as FactureRec ;



class FactureRec
	extends \FactureRec{

    public $OSE_loaded_version = 4;


	function create($user, $fk_id, $fk_source = 'facture')
	{
		global $conf;

		$error=0;
		$now=dol_now();

		// Clean parameters
		$this->titre=trim($this->titre);
		$this->usenewprice=empty($this->usenewprice)?0:$this->usenewprice;

		// No frequency defined then no next date to execution
		if (empty($this->frequency))
		{
			$this->frequency=0;
			$this->date_when=NULL;
		}


		$this->frequency=abs($this->frequency);
		$this->nb_gen_done=0;
		$this->nb_gen_max=empty($this->nb_gen_max)?0:$this->nb_gen_max;
		$this->auto_validate=empty($this->auto_validate)?0:$this->auto_validate;

		$this->db->begin();

		switch($fk_source) {
			case 'facture':
				$objsrc=new Facture($this->db);
				$result=$objsrc->fetch($fk_id);
			break;
			case 'contrat':
				$objsrc=new Contrat($this->db);
				$result=$objsrc->fetch($fk_id);
			break;
		}
		// Charge facture modele

		if ($result > 0)
		{
			// On positionne en mode brouillon la facture
			$this->brouillon = 1;

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."facture_rec (";
			$sql.= "titre";
			$sql.= ", fk_soc";
			$sql.= ", entity";
			$sql.= ", datec";
			$sql.= ", amount";
			$sql.= ", remise";
			$sql.= ", note_private";
			$sql.= ", note_public";
			$sql.= ", fk_user_author";
			$sql.= ", fk_projet";
			$sql.= ", fk_account";
			$sql.= ", fk_cond_reglement";
			$sql.= ", fk_mode_reglement";
			$sql.= ", usenewprice";
			$sql.= ", frequency";
			$sql.= ", unit_frequency";
			$sql.= ", date_when";
			$sql.= ", date_last_gen";
			$sql.= ", nb_gen_done";
			$sql.= ", nb_gen_max";
			$sql.= ", auto_validate";
			$sql.= ") VALUES (";
			$sql.= "'".$this->titre."'";
			$sql.= ", ".$facsrc->socid;
			$sql.= ", ".$conf->entity;
			$sql.= ", '".$this->db->idate($now)."'";
			$sql.= ", ".(!empty($facsrc->amount)?$facsrc->amount:'0');
			$sql.= ", ".(!empty($facsrc->remise)?$this->remise:'0');
			$sql.= ", ".(!empty($this->note_private)?("'".$this->db->escape($this->note_private)."'"):"NULL");
			$sql.= ", ".(!empty($this->note_public)?("'".$this->db->escape($this->note_public)."'"):"NULL");
			$sql.= ", '".$user->id."'";
			$sql.= ", ".(! empty($facsrc->fk_project)?"'".$facsrc->fk_project."'":"null");
			$sql.= ", ".(! empty($facsrc->fk_account)?"'".$facsrc->fk_account."'":"null");
			$sql.= ", '".$facsrc->cond_reglement_id."'";
			$sql.= ", '".$facsrc->mode_reglement_id."'";
			$sql.= ", ".$this->usenewprice;
			$sql.= ", ".$this->frequency;
			$sql.= ", '".$this->db->escape($this->unit_frequency)."'";
			$sql.= ", ".(!empty($this->date_when)?"'".$this->db->idate($this->date_when)."'":'NULL');
			$sql.= ", ".(!empty($this->date_last_gen)?"'".$this->db->idate($this->date_last_gen)."'":'NULL');
			$sql.= ", ".$this->nb_gen_done;
			$sql.= ", ".$this->nb_gen_max;
			$sql.= ", ".$this->auto_validate;
			$sql.= ")";

			if ($this->db->query($sql))
			{
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."facture_rec");

				// Add lines
				$num=count($facsrc->lines);
				for ($i = 0; $i < $num; $i++)
				{
					$tva_tx = $facsrc->lines[$i]->tva_tx;
					if (! empty($facsrc->lines[$i]->vat_src_code) && ! preg_match('/\(/', $tva_tx)) $tva_tx .= ' ('.$facsrc->lines[$i]->vat_src_code.')';

					$result_insert = $this->addline(
                        $facsrc->lines[$i]->desc,
                        $facsrc->lines[$i]->subprice,
                        $facsrc->lines[$i]->qty,
						$tva_tx,
                        $facsrc->lines[$i]->localtax1_tx,
                        $facsrc->lines[$i]->localtax2_tx,
                        $facsrc->lines[$i]->fk_product,
                        $facsrc->lines[$i]->remise_percent,
                        'HT',
                        0,
                        '',
                        0,
                        $facsrc->lines[$i]->product_type,
                        $facsrc->lines[$i]->rang,
                        $facsrc->lines[$i]->special_code,
			$facsrc->lines[$i]->label,
	                    $facsrc->lines[$i]->fk_unit
                    );

					if ($result_insert < 0)
					{
						$error++;
					}
				}

				if (! empty($this->linkedObjectsIds) && empty($this->linked_objects))	// To use new linkedObjectsIds instead of old linked_objects
				{
					$this->linked_objects = $this->linkedObjectsIds;	// TODO Replace linked_objects with linkedObjectsIds
				}

				// Add object linked
				if (! $error && $this->id && is_array($this->linked_objects) && ! empty($this->linked_objects))
				{
					foreach($this->linked_objects as $origin => $tmp_origin_id)
					{
					    if (is_array($tmp_origin_id))       // New behaviour, if linked_object can have several links per type, so is something like array('contract'=>array(id1, id2, ...))
					    {
					        foreach($tmp_origin_id as $origin_id)
					        {
					            $ret = $this->add_object_linked($origin, $origin_id);
					            if (! $ret)
					            {
					                $this->error=$this->db->lasterror();
					                $error++;
					            }
					        }
					    }
					    else                                // Old behaviour, if linked_object has only one link per type, so is something like array('contract'=>id1))
					    {
					        $origin_id = $tmp_origin_id;
						$ret = $this->add_object_linked($origin, $origin_id);
						if (! $ret)
						{
							$this->error=$this->db->lasterror();
							$error++;
						}
					    }
					}
				}

				if ($error)
				{
					$this->db->rollback();
				}
				else
				{
					$this->db->commit();
					return $this->id;
				}
			}
			else
			{
			    $this->error=$this->db->lasterror();
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}
}
