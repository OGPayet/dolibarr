<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 * 	\file			htdocs/core/actions_massactions.inc.php
 *  \brief			Code for actions done with massaction button (send by email, merge pdf, delete, ...)
 */
// $massaction must be defined
// $objectclass and $$objectlabel must be defined
// $parameters, $object, $action must be defined for the hook.
// $uploaddir may be defined (example to $conf->projet->dir_output."/";)
// $toselect may be defined
// Protection
if (empty($objectclass) || empty($uploaddir)) {
    dol_print_error(null, 'include of actions_massactions.inc.php is done but var $massaction or $objectclass or $uploaddir was not defined');
    exit;
}


// Mass actions. Controls on number of lines checked.
$maxformassaction = (empty($conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS) ? 1000 : $conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS);
if (!empty($massaction) && count($toselect) < 1) {
    $error++;
    setEventMessages($langs->trans("NoRecordSelected"), null, "warnings");
}
if (!$error && count($toselect) > $maxformassaction) {
    setEventMessages($langs->trans('TooManyRecordForMassAction', $maxformassaction), null, 'errors');
    $error++;
}


// Facture classique
if (!$error && $massaction == 'facture') {
    $db->begin();

    $objecttmp = new $objectclass($db);
    $nbok      = 0;
	$nbalready = 0;
    foreach ($toselect as $toselectid) {
        $result = $objecttmp->fetch($toselectid);
        if ($result > 0) {
            $initialvalue = $objecttmp->array_options['options_initialvalue'];
            $fixedamount  = $objecttmp->array_options['options_fixedamount'];
            if ($fixedamount === null || $fixedamount === '') {
                $fixedamount = 0;
            }
            $oneday                 = 60 * 60 * 24;
            $nbFactAn               = array(0, 12, 4, 2, 1); // mensuel, trimestre ....
            $invoicedates           = $nbFactAn[$objecttmp->array_options['options_invoicedates']]; //periode
            // dates
            $startdate              = strtotime($objecttmp->array_options['options_startdate']);
            $duration               = $objecttmp->array_options['options_duration'];
            $enddate                = strtotime($objecttmp->array_options['options_startdate']." +$duration month");
			//Si le contrat est résilié la date de fin de contrat devient la date de résiliation
			if(!empty($objecttmp->array_options['options_realdate']) && strtotime($objecttmp->array_options['options_realdate']) < $enddate) {
				$enddate = strtotime($objecttmp->array_options['options_realdate']);
			}
            $tacitagreement         = $objecttmp->array_options['options_tacitagreement'];
			//Si le contrat est résilié la reconduction tacite passe à 0
			if(!empty($objecttmp->array_options['options_realdate'])) {
				$tacitagreement = 0;
			}
            $invoicetype            = $objecttmp->array_options['options_invoicetype'];

            // load facturerecS linked
            $objecttmp->fetchObjectLinked();

			//Check if this period is not already invoiced
			$already_invoice = false;
			$year_already_invoice = date("Y",$startdate); //Cette variable permettra de stocker la dernière année facturée pour le renouvellement
			if (isset($objecttmp->linkedObjects["facture"])) { // delete facture rec
                //Suppression des factures modèles
                foreach ($objecttmp->linkedObjects["facture"] as $obj) {
					if(($firstdayofperiod <= strtotime($obj->array_options['options_datedeb'])) ||
					($firstdayofperiod > strtotime($obj->array_options['options_datedeb']) && $firstdayofperiod <= strtotime($obj->array_options['options_datefin'])) ||
					($lastdayofperiod <= strtotime($obj->array_options['options_datefin']))) {
						$already_invoice = true;
					}
					$year_already_invoice = max($year_already_invoice, date("Y",strtotime($obj->array_options['options_datefin'])));
                }
            }
            $firstdaysofperiods     = array(
                1 => strtotime(date('Y-m-01 00:00:00')), //month
                2 => firstDayOf(3), // trimestre
                3 => firstDayOf(6), // semestre
                4 => strtotime(date('Y-01-01 00:00:00')) //year
            );
            $lastdaysofperiods = array(
                1 => firstDayOf(1, 1) - $oneday, //month
                2 => firstDayOf(3, 1) - $oneday, // trimestre
                3 => firstDayOf(6, 1) - $oneday, // semestre
                4 => firstDayOf(12, 1) - $oneday //year
            );
            $firstdaysofnextperiods = array(
                1 => firstDayOf(1, 1), //month
                2 => firstDayOf(3, 1), // trimestre
                3 => firstDayOf(6, 1), // semestre
                4 => firstDayOf(12, 1) //year
            );
            $lastdaysofnextperiods  = array(
                1 => firstDayOf(1, 2) - $oneday, //month
                2 => firstDayOf(3, 2) - $oneday, // trimestre
                3 => firstDayOf(6, 2) - $oneday, // semestre
                4 => firstDayOf(12, 2) - $oneday //year
            );
            $periodsetter           = $objecttmp->array_options['options_invoicedates'];
            $firstdayofperiod       = $firstdaysofperiods[$periodsetter];
            $lastdayofperiod        = $lastdaysofperiods[$periodsetter];
            $firstdayofnextperiod   = $firstdaysofnextperiods[$periodsetter];
            $lastdayofnextperiod    = $lastdaysofnextperiods[$periodsetter];
            $now = strtotime("now");

            if ($invoicetype == '1') { //on facture la p?riode suivante si le contrat est à échoir
                $firstdayofperiod = $firstdayofnextperiod;
                $lastdayofperiod  = $lastdayofnextperiod;
				$daysinperiod     = intval($lastdayofperiod - $firstdayofperiod) / $oneday;
				$now = strtotime("+$daysinperiod days",$now);
			} else {
                $now = strtotime("now");
			}
            $daysinperiod                 = ($lastdayofperiod - $firstdayofperiod) / $oneday;
            //Préparation de la revalorisation
            $prohibitdecrease             = $objecttmp->array_options['options_prohibitdecrease'];
            $dates_ravalo                 = array(
                1 => get_next_birthday($objecttmp->array_options['options_startdate']),
                2 => strtotime(date('Y-01-01 00:00:00')),
                3 => strtotime(date('Y-04-01 00:00:00')),
                4 => strtotime(date('Y-07-01 00:00:00')),
                5 => strtotime(date('Y-10-01 00:00:00'))
            );
            $revalorisationperiod         = $objecttmp->array_options['options_revalorisationperiod'];
            $reindexmethod                = $objecttmp->array_options['options_reindexmethod'];
            $oldindicemonth               = $objecttmp->array_options['options_oldindicemonth'];
            $newindicemonth               = $objecttmp->array_options['options_newindicemonth'];
            $revalorisationdate           = $dates_ravalo[$revalorisationperiod];
            $revalorisationactivationdate = strtotime($objecttmp->array_options['options_revalorisationactivationdate']);
            if ($revalorisationactivationdate == false) $revalorisationactivationdate = 0;

            $result = 1;

			if($already_invoice == false) { //Si le contrat n'a jamais été facturé
				if (isset($objecttmp->linkedObjects["facturerec"])) {
					// most recent facturerec
					foreach ($objecttmp->linkedObjects["facturerec"] as $idref => $obj) {
						$desc = '';
						if (($startdate < $now && $now < $enddate) || ($tacitagreement && $now > $enddate)) { // le contrat est en cour
							$fac          = new Facture($db);
							$fac->fac_rec = $obj->id;
							$fac->socid   = $fac->fk_soc  = $obj->socid;
							$fac->fetch_thirdparty();
							$fac->date    = dol_now();
							$fac->remise_percent = $fac->thirdparty->remise_percent;
							$fac->array_options['options_datedeb'] = $firstdayofperiod;
							$fac->array_options['options_datefin'] = $lastdayofperiod;
							$fac->create($user, 1);

							$ratio        = 1; // periode partielle
							$majoration   = 0; // majoration indice
							$desc         .= ' '.dol_print_date($firstdayofperiod, 'day').' => '.dol_print_date($lastdayofperiod, 'day');

							if ($firstdayofperiod < $startdate) { // prorata de d?but
								$ratio *= 1 - ($startdate - $firstdayofperiod) / $oneday / $daysinperiod;
							} // end prorata d?but

							if ($lastdayofperiod > $enddate && $tacitagreement != '1') { // prorata de fin
								$ratio *= 1 - ($startdate - $lastdayofperiod) / $oneday / $daysinperiod;
							} // end prorata fin

							if (!$error) { // on met ? jour la facture
								foreach ($fac->lines as &$line) {
									$fac->updateline(
										$line->id, $desc, $line->multicurrency_subprice * $ratio + $majoration, $line->qty, $line->remise_percent, $line->date_start, $line->date_end,
										$line->tva_tx
									);

								}

								if(!empty($conf->global->AUTOMATIC_VALID_INVOICE_CONTRACT)) {
									$fac->validate($user);
								}

								//Renouvellement contrat
								if($year_already_invoice < date('Y',$lastdayofperiod)) {
									if(strstr($objecttmp->ref, '/')){
										$last_number = substr($objecttmp->ref, -1);
										$last_number++;
										$objecttmp->ref = substr_replace($objecttmp->ref ,$last_number,-1);
									} else {
										$objecttmp->ref = $objecttmp->ref."/1";
									}
									$objecttmp->update();

									//Ajout de l'evenement
									require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
									require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';

									$actioncomm = new ActionComm($db);

									$actioncomm->type_id=40;
									$actioncomm->type_code='AC_OTH_AUTO';
									$actioncomm->label       = utf8_encode("Contrat ".$objecttmp->ref." renouvellé");
									$actioncomm->note       = utf8_encode("Contrat ".$objecttmp->ref." renouvellé");
									$actioncomm->fk_project  = 0;
									$actioncomm->datep       = $now;
									$actioncomm->datef       = $now;
									$actioncomm->fulldayevent = 0;
									$actioncomm->durationp   = 0;
									$actioncomm->punctual    = 1;
									$actioncomm->percentage  = -1;   // Not applicable
									$actioncomm->transparency= 0; // Not applicable
									$actioncomm->authorid    = $user->id;   // User saving action
									$actioncomm->userownerid    = $user->id;   // User saving action
									$actioncomm->elementtype = 'contrat';
									$actioncomm->fk_element = $objecttmp->id;
									$actioncomm->fk_soc = $objecttmp->fk_soc;

									$ret = $actioncomm->create($user);
								}

								//Reconduction contrat
								if($tacitagreement && $now > $enddate) {
									require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
									require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';

									$actioncomm = new ActionComm($db);

									$actioncomm->type_id=40;
									$actioncomm->type_code='AC_OTH_AUTO';
									$actioncomm->label       = utf8_encode("Contrat ".$objecttmp->ref." reconduit");
									$actioncomm->note        = utf8_encode("Contrat ".$objecttmp->ref." reconduit");
									$actioncomm->fk_project  = 0;
									$actioncomm->datep       = $now;
									$actioncomm->datef       = $now;
									$actioncomm->fulldayevent = 0;
									$actioncomm->durationp   = 0;
									$actioncomm->punctual    = 1;
									$actioncomm->percentage  = -1;   // Not applicable
									$actioncomm->transparency= 0; // Not applicable
									$actioncomm->authorid    = $user->id;   // User saving action
									$actioncomm->userownerid    = $user->id;   // User saving action
									$actioncomm->elementtype = 'contrat';
									$actioncomm->fk_element = $objecttmp->id;
									$actioncomm->fk_soc = $objecttmp->fk_soc;

									$ret = $actioncomm->create($user);
								}
							}
						}
					}
				}
				if ($result <= 0) {
					setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
					$error++;
					break;
				} else $nbok++;
			} else {
				$nbalready++;
			}
        } else {
            setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
            $error++;
            break;
        }
    }

    if (!$error) {
        if ($nbok > 1) setEventMessages($langs->trans("FacturesGeneres", $nbok), null, 'mesgs');
        else if ($nbok > 0) setEventMessages($langs->trans("FactureGenere", $nbok), null, 'mesgs');
        $db->commit();
    } else {
        $db->rollback();
    }
	if($nbalready > 1) {
		setEventMessages($nbalready." factures déjà facturées", null, 'warnings');
	} else if($nbalready > 0) {
		setEventMessages("Facture déjà facturée", null, 'warnings');
	}
}

// Facture modèle
if (!$error && $massaction == 'facturerec') {
    $db->begin();

    $objecttmp = new $objectclass($db);
    $nbok      = 0;
    foreach ($toselect as $toselectid) {
        $result = $objecttmp->fetch($toselectid);
        if ($result > 0) {
            $initialvalue = $objecttmp->array_options['options_initialvalue'];
            $fixedamount  = $objecttmp->array_options['options_fixedamount'];
            if ($fixedamount === null || $fixedamount === '') {
                $fixedamount = 0;
            }
            $oneday       = 60 * 60 * 24;
            $nbFactAn     = array(0, 12, 4, 2, 1); // mensuel, trimestre ....
            $invoicedates = $nbFactAn[$objecttmp->array_options['options_invoicedates']]; //periode

            $result       = 1;
            //Chargement des factures modèles
            $objecttmp->fetchObjectLinked();
            if (isset($objecttmp->linkedObjects["facturerec"])) { // delete facture rec
                //Suppression des factures modèles
                foreach ($objecttmp->linkedObjects["facturerec"] as $obj) {
                    $obj->delete($user);
                }
            }
			//Check if this period is not already invoiced
			$already_invoice = false;
			if (isset($objecttmp->linkedObjects["facture"])) { // delete facture rec
				//Suppression des factures modèles
				foreach ($objecttmp->linkedObjects["facture"] as $obj) {
					if(($firstdayofperiod <= strtotime($obj->array_options['options_datedeb'])) ||
					($firstdayofperiod > strtotime($obj->array_options['options_datedeb']) && $firstdayofperiod <= strtotime($obj->array_options['options_datefin'])) ||
					($lastdayofperiod <= strtotime($obj->array_options['options_datefin']))) {
						$already_invoice = true;
					}
				}
			}

            // dates
            $startdate              = strtotime($objecttmp->array_options['options_startdate']);
            $duration               = $objecttmp->array_options['options_duration'];
            $enddate                = strtotime($objecttmp->array_options['options_startdate']." +$duration month");
			//Si le contrat est résilié la date de fin de contrat devient la date de résiliation
			if(!empty($objecttmp->array_options['options_realdate']) && strtotime($objecttmp->array_options['options_realdate']) < $enddate) {
				$enddate = strtotime($objecttmp->array_options['options_realdate']);
			}
            $tacitagreement         = $objecttmp->array_options['options_tacitagreement'];
			//Si le contrat est résilié la reconduction tacite passe à 0
			if(!empty($objecttmp->array_options['options_realdate'])) {
				$tacitagreement = 0;
			}
			$invoicetype            = $objecttmp->array_options['options_invoicetype'];
			$firstdaysofperiods     = array(
				1 => strtotime(date('Y-m-01 00:00:00')), //month
				2 => firstDayOf(3), // trimestre
				3 => firstDayOf(6), // semestre
				4 => strtotime(date('Y-01-01 00:00:00')) //year
			);
			$lastdaysofperiods = array(
				1 => firstDayOf(1, 1) - $oneday, //month
				2 => firstDayOf(3, 1) - $oneday, // trimestre
				3 => firstDayOf(6, 1) - $oneday, // semestre
				4 => firstDayOf(12, 1) - $oneday //year
			);
			$firstdaysofnextperiods = array(
				1 => firstDayOf(1, 1), //month
				2 => firstDayOf(3, 1), // trimestre
				3 => firstDayOf(6, 1), // semestre
				4 => firstDayOf(12, 1) //year
			);
			$lastdaysofnextperiods  = array(
				1 => firstDayOf(1, 2) - $oneday, //month
				2 => firstDayOf(3, 2) - $oneday, // trimestre
				3 => firstDayOf(6, 2) - $oneday, // semestre
				4 => firstDayOf(12, 2) - $oneday //year
			);
            $periodsetter           = $objecttmp->array_options['options_invoicedates'];
            $firstdayofperiod       = $firstdaysofperiods[$periodsetter];
            $lastdayofperiod        = $lastdaysofperiods[$periodsetter];
            $firstdayofnextperiod   = $firstdaysofnextperiods[$periodsetter];
            $lastdayofnextperiod    = $lastdaysofnextperiods[$periodsetter];
            $now = strtotime("now");

            if ($invoicetype == '1') { //on facture la p?riode suivante si le contrat est à échoir
                $firstdayofperiod = $firstdayofnextperiod;
                $lastdayofperiod  = $lastdayofnextperiod;
				$daysinperiod     = intval($lastdayofperiod - $firstdayofperiod) / $oneday;
				$now = strtotime("+$daysinperiod days",$now);
			} else {
                $now = strtotime("now");
			}
            $daysinperiod                 = ($lastdayofperiod - $firstdayofperiod) / $oneday;
            //revalorisation
            $prohibitdecrease             = $objecttmp->array_options['options_prohibitdecrease'];
            $dates_ravalo                 = array(
                1 => get_next_birthday($objecttmp->array_options['options_startdate']),
                2 => strtotime(date('Y-01-01 00:00:00')),
                3 => strtotime(date('Y-04-01 00:00:00')),
                4 => strtotime(date('Y-07-01 00:00:00')),
                5 => strtotime(date('Y-10-01 00:00:00'))
            );
            $revalorisationperiod         = $objecttmp->array_options['options_revalorisationperiod'];
            $reindexmethod                = $objecttmp->array_options['options_reindexmethod'];
            $oldindicemonth               = $objecttmp->array_options['options_oldindicemonth'];
            $newindicemonth               = $objecttmp->array_options['options_newindicemonth'];
            $revalorisationdate           = $dates_ravalo[$revalorisationperiod];
			if(!empty($revalorisationdate)) { //calcul pour la première revalorisation
				if(date("m-d",$startdate) == date("m-d",$revalorisationdate)) {
					$firstrevalorisationdate = strtotime("+1 years",strtotime(date("Y",$startdate)."-".date("m-d",$revalorisationdate)));
				} else if(date("m-d",$startdate) > date("m-d",$revalorisationdate)) {
					$firstrevalorisationdate = strtotime("+1 years",strtotime(date("Y",$startdate)."-".date("m-d",$revalorisationdate)));
				} else {
					$firstrevalorisationdate = strtotime(date("Y",$startdate)."-".date("m-d",$revalorisationdate));
				}
			}
            $revalorisationactivationdate = strtotime($objecttmp->array_options['options_revalorisationactivationdate']);
            if ($revalorisationactivationdate == false) $revalorisationactivationdate = 0;

            $result = 1;

			if ($startdate < $now && $now < $enddate || $tacitagreement && $now > $enddate) { // le contrat est en cour

				//Création de la facture brouillon permettant de faire la facture modèle
				$facture                    = new Facture($db);
				$cl                         = new Client($db);
				$cl->fetch($objecttmp->socid);
				$facture->socid             = $facture->fk_soc            = $objecttmp->socid;
				$facture->fetch_thirdparty();
				$facture->remise_percent =  $facture->thirdparty->remise_percent;
				$facture->date              = dol_now();
				$facture->mode_reglement_id = $cl->mode_reglement_id;
				$facture->cond_reglement_id = $cl->cond_reglement_id;
				$facture->cond_reglement    = $cl->cond_reglement;
				$facture->fk_account        = $cl->fk_account;
				$facture->array_options['options_datedeb'] = $firstdayofperiod;
				$facture->array_options['options_datefin'] = $lastdayofperiod;

				$facture->create($user,1);

				$ratio        = 1; // periode partielle
				$majoration   = 0; // majoration indice
				$descupdate         .= ' '.dol_print_date($firstdayofperiod, 'day').' => '.dol_print_date($lastdayofperiod, 'day');

				if ($firstdayofperiod < $startdate) { // prorata de d?but
					$ratio *= 1 - ($startdate - $firstdayofperiod) / $oneday / $daysinperiod;
				} // end prorata d?but

				if ($lastdayofperiod > $enddate && $tacitagreement != '1') { // prorata de fin
					$ratio *= 1 - ($startdate - $lastdayofperiod) / $oneday / $daysinperiod;
				} // end prorata fin

				$lines = $objecttmp->lines;
				if (empty($lines) && method_exists($objecttmp, 'fetch_lines')) {
					$srcobject->fetch_lines();
					$lines = $objecttmp->lines;
				}

				$fk_parent_line = 0;
				$num            = count($lines);
				for ($i = 0; $i < $num; $i++) {
					// Don't add lines with qty 0 when coming from a shipment including all order lines
					if ($srcobject->element == 'shipping' && $conf->global->SHIPMENT_GETS_ALL_ORDER_PRODUCTS && $lines[$i]->qty == 0) continue;

					$label                        = (!empty($lines[$i]->label) ? $lines[$i]->label : '');
					$desc                         = (!empty($lines[$i]->desc) ? $lines[$i]->desc : $lines[$i]->libelle);
					if ($facture->situation_counter == 1) $lines[$i]->situation_percent = 0;

					if ($lines[$i]->subprice < 0) {
						// Negative line, we create a discount line
						$discount              = new DiscountAbsolute($db);
						$discount->fk_soc      = $facture->socid;
						$discount->amount_ht   = abs($lines[$i]->total_ht / $invoicedates);
						$discount->amount_tva  = abs($lines[$i]->total_tva);
						$discount->amount_ttc  = abs($lines[$i]->total_ttc / $invoicedates);
						$discount->tva_tx      = $lines[$i]->tva_tx;
						$discount->fk_user     = $user->id;
						$discount->description = $desc;
						$discountid            = $discount->create($user);
						if ($discountid > 0) {
							$result = $facture->insert_discount($discountid); // This include link_to_invoice
						}
					} else {
						// Positive line
						$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

						// Reset fk_parent_line for no child products and special product
						if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
							$fk_parent_line = 0;
						}

						// Extrafields
						if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) {
							$lines[$i]->fetch_optionals($lines[$i]->rowid);
							$array_options = $lines[$i]->array_options;
						}

						$tva_tx = $lines[$i]->tva_tx;
						if (!empty($lines[$i]->vat_src_code) && !preg_match('/\(/', $tva_tx)) $tva_tx .= ' ('.$lines[$i]->vat_src_code.')';

						// View third's localtaxes for NOW and do not use value from origin.
						// TODO Is this really what we want ? Yes if source if template invoice but what if proposal or order ?
						$localtax1_tx = get_localtax($tva_tx, 1, $facture->thirdparty);
						$localtax2_tx = get_localtax($tva_tx, 2, $facture->thirdparty);
						$qty          = 1;
						$resultat     = $facture->addline($desc, $initialvalue / $invoicedates, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $lines[$i]->fk_product, $cl->remise_percent,
							$date_start, $date_end, 0, $lines[$i]->info_bits, $lines[$i]->fk_remise_except, 'HT', 0, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $facture->origin,
							$lines[$i]->rowid, $fk_parent_line, $lines[$i]->fk_fournprice, $initialvalue / $invoicedates, $label, $array_options, $lines[$i]->situation_percent, $lines[$i]->fk_prev_id,
							$lines[$i]->fk_unit);

						if ($resultat > 0) {
							$lineid = $resultat;
						} else {
							$lineid = 0;
							$error ++;
							break;
						}

						// Defined the new fk_parent_line
						if ($resultat > 0 && $lines[$i]->product_type == 9) {
							$fk_parent_line = $resultat;
						}
					}
				}
				$now = strtotime("now");
				if ($lastdayofperiod > $revalorisationactivationdate && $lastdayofperiod > $revalorisationdate && !($firstdayofperiod <= $startdate && $startdate <= $lastdayofperiod) && $reindexmethod>1 && $now >= $firstrevalorisationdate) { // on doit revaloriser
					//$nbjouravant          = max($revalorisationactivationdate - $firstdayofperiod, 0) / $oneday;
					if ($revalorisationactivationdate == 0) {
						$revalorisationactivationdate = $firstdayofperiod;
					}
					$nbdaysafter          = max($lastdayofperiod - $revalorisationactivationdate, 0) / $oneday;
					//P1 = P0 x (S1 / S0)
					$montantrevalorisable = $nbdaysafter / $daysinperiod * ($initialvalue - $fixedamount) / $invoicedates; //P0
					$indice0              = getIndice($reindexmethod, null, null, $oldindicemonth); //S0
					$indice1              = getIndice($reindexmethod, null, $newindicemonth);  //S1
					if ($indice0 === false) {
						setEventMessages($langs->trans("NoIndice0", $objecttmp->getNomUrl(1)), 0, 'errors');
						$error++; // die('indice0 OR indice1 not provided');
					}
					if ($indice1 === false) {
						setEventMessages($langs->trans("NoIndice1", $objecttmp->getNomUrl(1))." $reindexmethod ".$newindicemonth.'/'.date('Y'), 0, 'errors');
						$error++;  //die('indice0 OR indice1 not provided');
					}
					if ($indice0->year_indice >= $indice1->year_indice || $indice0->year_indice >= $indice1->year_indice && $indice0->month_indice >= $indice1->month_indice) { // pas de taux dans le futur
						$indice0->indice = 1;
						$indice1->indice = 1;
					} else {
						// setEventMessages($objecttmp->getNomUrl(1)." Indice0: $indice0->indice $indice0->month_indice/$indice0->year_indice , Indice1: $indice1->indice $indice1->month_indice/$indice1->year_indice",
							// null, 'mesgs');
					}
					if ($prohibitdecrease == '1' && $indice1->indice < $indice0->indice) { // pas de dépression
						$indice0->indice = 1;
						$indice1->indice = 1;
					}
					//P1 = P0 x (S1 / S0)
					$majoration += $montantrevalorisable * $indice1->indice / $indice0->indice - $montantrevalorisable;
//                            var_dump($majoration, $montantrevalorisable, $indice1, $indice0, $montantrevalorisable);
//                            die($ratio.' '.$nbdaysafter.' '.$montantrevalorisable.' '.$majoration);
				} // end revalorisation
				$facture->fetch_lines();
				foreach ($facture->lines as &$line) {
					$facture->updateline(
						$line->id, $descupdate, $line->multicurrency_subprice * $ratio + $majoration, $line->qty, $line->remise_percent, $line->date_start, $line->date_end,
						$line->tva_tx
					);
				}

				// Création de la nouvelle facture modèle
				$facturerec                            = new FactureRec($db);
				$facturerec->titre                     = $db->escape($facture->thirdparty->nom." ($facture->id)");
				$facturerec->linked_objects            = array();
				$facturerec->linked_objects['contrat'] = $toselectid;
				$facturerec->create($user, $facture->id);
				$facture->delete($user);

				if ($result <= 0) {
					setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
					$error++;
					break;
				} else $nbok++;
			}
		} else {
            setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
            $error++;
            break;
        }
    }
    if (!$error) {
        if ($nbok > 1) setEventMessages($langs->trans("ModelesGeneres", $nbok), null, 'mesgs');
        else setEventMessages($langs->trans("ModeleGenere", $nbok), null, 'mesgs');
        $db->commit();
    }
    else {
        $db->rollback();
    }
}

// Facture modèle
if (!$error && $massaction == 'factureanterieur') {
    $db->begin();

    $objecttmp = new $objectclass($db);
    $nbok      = 0;
    foreach ($toselect as $toselectid) {
        $result = $objecttmp->fetch($toselectid);
        if ($result > 0) {
			if($objecttmp->array_options['options_oldinvoice'] > 0) {
				$initialvalue = $objecttmp->array_options['options_initialvalue'];
				$fixedamount  = $objecttmp->array_options['options_fixedamount'];
				if ($fixedamount === null || $fixedamount === '') {
					$fixedamount = 0;
				}
				$oneday       = 60 * 60 * 24;
				$nbFactAn     = array(0, 12, 4, 2, 1); // mensuel, trimestre ....
				$invoicedates = $nbFactAn[$objecttmp->array_options['options_invoicedates']]; //periode

				$result       = 1;
				//Chargement des factures modèles
				$objecttmp->fetchObjectLinked();

				// dates
				$startdate              = strtotime($objecttmp->array_options['options_startdate']);
				$duration               = $objecttmp->array_options['options_duration'];
				$enddate                = strtotime($objecttmp->array_options['options_startdate']." +$duration month");
				//Si le contrat est résilié la date de fin de contrat devient la date de résiliation
				if(!empty($objecttmp->array_options['options_realdate']) && strtotime($objecttmp->array_options['options_realdate']) < $enddate) {
					$enddate = strtotime($objecttmp->array_options['options_realdate']);
				}
				$tacitagreement         = $objecttmp->array_options['options_tacitagreement'];
				//Si le contrat est résilié la reconduction tacite passe à 0
				if(!empty($objecttmp->array_options['options_realdate'])) {
					$tacitagreement = 0;
				}
				$invoicetype            = $objecttmp->array_options['options_invoicetype'];
				$anterieurdate = $startdate;
				while($anterieurdate < time()) {
					$now = $anterieurdate;
					$anterieurmonth = date("m", $anterieurdate);
					$anterieuryear = date("Y", $anterieurdate);

					$anterieurdate = strtotime(date("$anterieuryear-$anterieurmonth-01 00:00:00"));

					$firstdaysofperiods     = array(
						1 => firstDayOf(1, 0, $anterieurdate), //month
						2 => firstDayOf(3, 0, $anterieurdate), // trimestre
						3 => firstDayOf(6, 0, $anterieurdate), // semestre
						4 => firstDayOf(12, 0, $anterieurdate) //year
					);
					$lastdaysofperiods = array(
						1 => firstDayOf(1, 1, $anterieurdate) - $oneday, //month
						2 => firstDayOf(3, 1, $anterieurdate) - $oneday, // trimestre
						3 => firstDayOf(6, 1, $anterieurdate) - $oneday, // semestre
						4 => firstDayOf(12, 1, $anterieurdate) - $oneday //year
					);
					$firstdaysofnextperiods = array(
						1 => firstDayOf(1, 1, $anterieurdate), //month
						2 => firstDayOf(3, 1, $anterieurdate), // trimestre
						3 => firstDayOf(6, 1, $anterieurdate), // semestre
						4 => firstDayOf(12, 1, $anterieurdate) //year
					);
					$lastdaysofnextperiods  = array(
						1 => firstDayOf(1, 2, $anterieurdate) - $oneday, //month
						2 => firstDayOf(3, 2, $anterieurdate) - $oneday, // trimestre
						3 => firstDayOf(6, 2, $anterieurdate) - $oneday, // semestre
						4 => firstDayOf(12, 2, $anterieurdate) - $oneday //year
					);

					$periodsetter           = $objecttmp->array_options['options_invoicedates'];
					$firstdayofperiod       = $firstdaysofperiods[$periodsetter];
					$lastdayofperiod        = $lastdaysofperiods[$periodsetter];
					$firstdayofnextperiod   = $firstdaysofnextperiods[$periodsetter];
					$lastdayofnextperiod    = $lastdaysofnextperiods[$periodsetter];

					if ($invoicetype == '1') { //on facture la p?riode suivante
						$firstdayofperiod = $firstdayofnextperiod;
						$lastdayofperiod  = $lastdayofnextperiod;
						$daysinperiod     = intval($lastdayofperiod - $firstdayofperiod) / $oneday;
						$now = strtotime("+$daysinperiod days",$now);
					}
					$daysinperiod                 = ($lastdayofperiod - $firstdayofperiod) / $oneday;
					//revalorisation
					$prohibitdecrease             = $objecttmp->array_options['options_prohibitdecrease'];
					$dates_ravalo                 = array(
						1 => get_next_birthday($objecttmp->array_options['options_startdate']),
						2 => strtotime(date("$anterieuryear-01-01 00:00:00")),
						3 => strtotime(date("$anterieuryear-04-01 00:00:00")),
						4 => strtotime(date("$anterieuryear-07-01 00:00:00")),
						5 => strtotime(date("$anterieuryear-10-01 00:00:00"))
					);
					$revalorisationperiod         = $objecttmp->array_options['options_revalorisationperiod'];
					$reindexmethod                = $objecttmp->array_options['options_reindexmethod'];
					$oldindicemonth               = $objecttmp->array_options['options_oldindicemonth'];
					$newindicemonth               = $objecttmp->array_options['options_newindicemonth'];
					$revalorisationdate           = $dates_ravalo[$revalorisationperiod];
					if(!empty($revalorisationdate)) { //calcul pour la première revalorisation
						if(date("m-d",$startdate) == date("m-d",$revalorisationdate)) {
							$firstrevalorisationdate = strtotime("+1 years",strtotime(date("Y",$startdate)."-".date("m-d",$revalorisationdate)));
						} else if(date("m-d",$startdate) > date("m-d",$revalorisationdate)) {
							$firstrevalorisationdate = strtotime("+1 years",strtotime(date("Y",$startdate)."-".date("m-d",$revalorisationdate)));
						} else {
							$firstrevalorisationdate = strtotime(date("Y",$startdate)."-".date("m-d",$revalorisationdate));
						}
					}
					$revalorisationactivationdate = strtotime($objecttmp->array_options['options_revalorisationactivationdate']);
					if ($revalorisationactivationdate == false) $revalorisationactivationdate = 0;

					$result = 1;
					// load facturerecS linked
					$objecttmp->fetchObjectLinked();

					//Check if this period is not already invoiced
					$already_invoice = false;
					if (isset($objecttmp->linkedObjects["facture"])) { // delete facture rec
						//Suppression des factures modèles
						foreach ($objecttmp->linkedObjects["facture"] as $obj) {
							if(($firstdayofperiod <= strtotime($obj->array_options['options_datedeb'])) ||
							($firstdayofperiod > strtotime($obj->array_options['options_datedeb']) && $firstdayofperiod <= strtotime($obj->array_options['options_datefin'])) ||
							($lastdayofperiod <= strtotime($obj->array_options['options_datefin']))) {
								$already_invoice = true;
							}
						}
					}
					if($already_invoice == false) {
						if ($startdate < $now && $now < $enddate || $tacitagreement && $now > $enddate) { // le contrat est en cour
							//Création de la facture brouillon permettant de faire la facture modèle
							$facture                    = new Facture($db);
							$cl                         = new Client($db);
							$cl->fetch($objecttmp->socid);
							$facture->socid             = $facture->fk_soc            = $objecttmp->socid;
							$facture->fetch_thirdparty();
							$facture->remise_percent =  $facture->thirdparty->remise_percent;
							$facture->date              = dol_now();
							$facture->mode_reglement_id = $cl->mode_reglement_id;
							$facture->cond_reglement_id = $cl->cond_reglement_id;
							$facture->cond_reglement    = $cl->cond_reglement;
							$facture->fk_account        = $cl->fk_account;
							$facture->array_options['options_datedeb'] = $firstdayofperiod;
							$facture->array_options['options_datefin'] = $lastdayofperiod;
							$facture->linked_objects = array();
							$facture->linked_objects['contrat'] = $toselectid;

							$facture->create($user,1);

							$ratio        = 1; // periode partielle
							$majoration   = 0; // majoration indice
							$descupdate   = ' '.dol_print_date($firstdayofperiod, 'day').' => '.dol_print_date($lastdayofperiod, 'day');

							if ($firstdayofperiod < $startdate) { // prorata de d?but
								$ratio *= 1 - ($startdate - $firstdayofperiod) / $oneday / $daysinperiod;
							} // end prorata d?but

							if ($lastdayofperiod > $enddate && $tacitagreement != '1') { // prorata de fin
								$ratio *= 1 - ($startdate - $lastdayofperiod) / $oneday / $daysinperiod;
							} // end prorata fin

							$lines = $objecttmp->lines;
							if (empty($lines) && method_exists($objecttmp, 'fetch_lines')) {
								$srcobject->fetch_lines();
								$lines = $objecttmp->lines;
							}

							$fk_parent_line = 0;
							$num            = count($lines);
							for ($i = 0; $i < $num; $i++) {
								// Don't add lines with qty 0 when coming from a shipment including all order lines
								if ($srcobject->element == 'shipping' && $conf->global->SHIPMENT_GETS_ALL_ORDER_PRODUCTS && $lines[$i]->qty == 0) continue;

								$label                        = (!empty($lines[$i]->label) ? $lines[$i]->label : '');
								$desc                         = (!empty($lines[$i]->desc) ? $lines[$i]->desc : $lines[$i]->libelle);
								if ($facture->situation_counter == 1) $lines[$i]->situation_percent = 0;

								if ($lines[$i]->subprice < 0) {
									// Negative line, we create a discount line
									$discount              = new DiscountAbsolute($db);
									$discount->fk_soc      = $facture->socid;
									$discount->amount_ht   = abs($lines[$i]->total_ht / $invoicedates);
									$discount->amount_tva  = abs($lines[$i]->total_tva);
									$discount->amount_ttc  = abs($lines[$i]->total_ttc / $invoicedates);
									$discount->tva_tx      = $lines[$i]->tva_tx;
									$discount->fk_user     = $user->id;
									$discount->description = $desc;
									$discountid            = $discount->create($user);
									if ($discountid > 0) {
										$result = $facture->insert_discount($discountid); // This include link_to_invoice
									}
								} else {
									// Positive line
									$product_type = ($lines[$i]->product_type ? $lines[$i]->product_type : 0);

									// Reset fk_parent_line for no child products and special product
									if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
										$fk_parent_line = 0;
									}

									// Extrafields
									if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) {
										$lines[$i]->fetch_optionals($lines[$i]->rowid);
										$array_options = $lines[$i]->array_options;
									}

									$tva_tx = $lines[$i]->tva_tx;
									if (!empty($lines[$i]->vat_src_code) && !preg_match('/\(/', $tva_tx)) $tva_tx .= ' ('.$lines[$i]->vat_src_code.')';

									// View third's localtaxes for NOW and do not use value from origin.
									// TODO Is this really what we want ? Yes if source if template invoice but what if proposal or order ?
									$localtax1_tx = get_localtax($tva_tx, 1, $facture->thirdparty);
									$localtax2_tx = get_localtax($tva_tx, 2, $facture->thirdparty);
									$qty          = 1;
									$resultat     = $facture->addline($desc, $initialvalue / $invoicedates, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $lines[$i]->fk_product, $cl->remise_percent,
										$date_start, $date_end, 0, $lines[$i]->info_bits, $lines[$i]->fk_remise_except, 'HT', 0, $product_type, $lines[$i]->rang, $lines[$i]->special_code, $facture->origin,
										$lines[$i]->rowid, $fk_parent_line, $lines[$i]->fk_fournprice, $initialvalue / $invoicedates, $label, $array_options, $lines[$i]->situation_percent, $lines[$i]->fk_prev_id,
										$lines[$i]->fk_unit);

									if ($resultat > 0) {
										$lineid = $resultat;
									} else {
										$lineid = 0;
										$error ++;
										break;
									}

									// Defined the new fk_parent_line
									if ($resultat > 0 && $lines[$i]->product_type == 9) {
										$fk_parent_line = $resultat;
									}
								}
							}
							$now = strtotime("now");
							if ($lastdayofperiod > $revalorisationactivationdate && $lastdayofperiod > $revalorisationdate && !($firstdayofperiod <= $startdate && $startdate <= $lastdayofperiod) && $reindexmethod>1 && $now >= $firstrevalorisationdate) { // on doit revaloriser
								//$nbjouravant          = max($revalorisationactivationdate - $firstdayofperiod, 0) / $oneday;
								if ($revalorisationactivationdate == 0) {
									$revalorisationactivationdate = $firstdayofperiod;
								}
								$nbdaysafter          = max($lastdayofperiod - $revalorisationactivationdate, 0) / $oneday;
								//P1 = P0 x (S1 / S0)
								$montantrevalorisable = $nbdaysafter / $daysinperiod * ($initialvalue - $fixedamount) / $invoicedates; //P0
								$indice0              = getIndice($reindexmethod, null, null, $oldindicemonth); //S0
								$indice1              = getIndice($reindexmethod, null, $newindicemonth);  //S1
								if ($indice0 === false) {
									setEventMessages($langs->trans("NoIndice0", $objecttmp->getNomUrl(1)), 0, 'errors');
									$error++; // die('indice0 OR indice1 not provided');
								}
								if ($indice1 === false) {
									setEventMessages($langs->trans("NoIndice1", $objecttmp->getNomUrl(1))." $reindexmethod ".$newindicemonth.'/'.date('Y'), 0, 'errors');
									$error++;  //die('indice0 OR indice1 not provided');
								}
								if ($indice0->year_indice >= $indice1->year_indice || $indice0->year_indice >= $indice1->year_indice && $indice0->month_indice >= $indice1->month_indice) { // pas de taux dans le futur
									$indice0->indice = 1;
									$indice1->indice = 1;
								} else {
									setEventMessages($objecttmp->getNomUrl(1)." Indice0: $indice0->indice $indice0->month_indice/$indice0->year_indice , Indice1: $indice1->indice $indice1->month_indice/$indice1->year_indice",
										null, 'mesgs');
								}
								if ($prohibitdecrease == '1' && $indice1->indice < $indice0->indice) { // pas de dépression
									$indice0->indice = 1;
									$indice1->indice = 1;
								}
								//P1 = P0 x (S1 / S0)
								$majoration += $montantrevalorisable * $indice1->indice / $indice0->indice - $montantrevalorisable;
							} // end revalorisation
							$facture->fetch_lines();
							foreach ($facture->lines as &$line) {
								$facture->updateline(
									$line->id, $descupdate, $line->multicurrency_subprice * $ratio + $majoration, $line->qty, $line->remise_percent, $line->date_start, $line->date_end,
									$line->tva_tx
								);

							}
							if(!empty($conf->global->AUTOMATIC_VALID_INVOICE_CONTRACT)) {
								$facture->validate($user);
							}

							if ($result <= 0) {
							setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
							$error++;
							break;
							} else $nbok++;
						}
					}

					$anterieurdate = strtotime('+1 month',$anterieurdate);
				}
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
    }

    if (!$error) {
        if ($nbok > 1) setEventMessages($langs->trans("FacturesGeneres", $nbok), null, 'mesgs');
        else setEventMessages($langs->trans("FactureGenere", $nbok), null, 'mesgs');
        $db->commit();
    }
    else {
        $db->rollback();
    }
}

$parameters['toselect']  = $toselect;
$parameters['uploaddir'] = $uploaddir;

$reshook = $hookmanager->executeHooks('doMassActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

function get_next_birthday($birthday)
{
    $date = new DateTime($birthday);
    $date->modify('+'.date('Y') - $date->format('Y').' years');
    if ($date < new DateTime()) {
        $date->modify('+1 year');
    }

    return $date->format('Y-m-d');
}

// return timestamp
function firstDayOf($period = 3, $next = 0, $time=null)
{
	if($time == null) {
		$time = time();
	}
	$addnext = $next * $period;
    $currentmonth = date("m", $time);
    $moisActuel = date("n", strtotime("+$addnext month",$time));
    $mois       = ceil($moisActuel / $period) * $period - $period + 1;
	$year = date("Y", $time);

   if($next == 1) {
        if(($period == 1 && $currentmonth == 12) || ($period == 3 && $currentmonth >= 10 && $currentmonth <= 12) || ($period == 6 && $currentmonth >= 07 && $currentmonth <= 12) || ($period == 12)) {
           $year = date("Y", strtotime('+1 years',$time));
        }
    }
    if($next == 2 || $next == 3) {
        if(($period == 1 && $currentmonth == 11 && $currentmonth == 12) || ($period == 3 && $currentmonth >= 07 && $currentmonth <= 12) || ($period == 6)) {
           $year = date("Y", strtotime('+1 years',$time));
        }
        if($period == 12) {
           $year = date("Y", strtotime('+2 years',$time));
        }
    }
    return strtotime(date("$year-$mois-01 00:00:00"));
}

function getIndice($source = 'Syntec', $year = null, $month = null, $id = null)
{
    global $db;
    if ((string) (int) $source == $source) { //la source est un numéro chez ST
        $assoc  = array(2 => 'Syntec', 3 => 'Insee');
        $source = $assoc[$source];
    }
    $table = MAIN_DB_PREFIX.'c_indice_'.strtolower($source);
    if ($id !== null) {
        $where = " `rowid` = ".(1 * $id);
    } else {
        if ($month == null && $year == null) {
            $month = date('n');
            $year  = date('Y');
            $where = " `year_indice` = ".(1 * $year)." AND `month_indice` = ".(1 * $month);
        } elseif ($year == null && $month != null) {
            $where = " `month_indice` = ".(1 * $month);
        } else {
            $where = " `year_indice` = ".(1 * $year)." AND `month_indice` = ".(1 * $month);
        }
    }
    $sql    = "SELECT indice, year_indice, month_indice FROM `$table` WHERE (".$where.") AND indice !=0 AND active= 1 ORDER BY `year_indice` DESC, `month_indice` DESC LIMIT 1";
    $result = $db->query($sql);
    if ($result) {
        if ($db->num_rows($result) === 1) {
            $obj = $db->fetch_object($result);
            return $obj;
        } elseif ($id === null) {
            $sql    = "SELECT indice, year_indice, month_indice FROM `$table` WHERE indice !=0 AND active= 1  AND `year_indice` = ".(1 * $year)." ORDER BY `year_indice` DESC, `month_indice` DESC LIMIT 1";
            $result = $db->query($sql);
            if ($result) {
                if ($db->num_rows($result) === 1) {
                    $obj = $db->fetch_object($result);
                    return $obj;
                }
            }
        }
    } else {
        die('EROR '.$sql);
    }
    return false;
}
