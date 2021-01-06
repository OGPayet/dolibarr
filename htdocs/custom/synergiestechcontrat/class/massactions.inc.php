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

// facture model
if (!$error && $massaction == 'facturerec') {
    $db->begin();

    $objecttmp = new $objectclass($db);
    $nbok      = 0;
    foreach ($toselect as $toselectid) {
        $result = $objecttmp->fetch($toselectid);
        if ($result > 0) {
            //print'<pre>';var_dump($objecttmp->array_options);die();
            $initialvalue = $objecttmp->array_options['options_initialvalue'];
            $fixedamount  = $objecttmp->array_options['options_fixedamount'];
            $nbFactAn     = array(0, 12, 4, 2, 1); // mensuel, trimestre ....
            $invoicedates = $nbFactAn[$objecttmp->array_options['options_invoicedates']]; //periode
//            if (in_array($objecttmp->element, array('societe','member'))) $result = $objecttmp->delete($objecttmp->id, $user, 1);
//            else $result = $objecttmp->delete($user);
            $result       = 1;
            // load facturerecS linked
            $objecttmp->fetchObjectLinked();
            if (isset($objecttmp->linkedObjects["facturerec"])) { // delete facture rec
                // most recent facturerec
                foreach ($objecttmp->linkedObjects["facturerec"] as $obj) {
                    $obj->delete($user);
                }
            }
            // create Facture Rec
            //  first facture brouillon
            $facture                    = new Facture($db);
            $cl                         = new Client($db);
            $cl->fetch($objecttmp->socid);
            $facture->socid             = $facture->fk_soc            = $objecttmp->socid;
            $facture->fetch_thirdparty();
            $facture->date              = dol_now();
            $facture->mode_reglement_id = $cl->mode_reglement_id;
            $facture->cond_reglement_id = $cl->cond_reglement_id;
            $facture->cond_reglement    = $cl->cond_reglement;
            $facture->fk_account        = $cl->fk_account;
            $facture->create($user);
//            echo '<pre>';
//            var_dump($cl, $facture);
//            die();
            $lines                      = $objecttmp->lines;
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
                    } else {
//                        setEventMessages($discount->error, $discount->errors, 'errors');
//                        $error ++;
//                        break;
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
                    $resultat     = $facture->addline($desc, $initialvalue / $invoicedates, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, $lines[$i]->fk_product, $lines[$i]->remise_percent,
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
            // secondly facturerec
            $facturerec                            = new FactureRec($db);
            $facturerec->titre                     = $db->escape($facture->thirdparty->nom." ($facture->id)");
            $facturerec->linked_objects            = array();
            $facturerec->linked_objects['contrat'] = $toselectid;
            $facturerec->create($user, $facture->id);
            $facture->delete($user);


            //$db->commit();
            //die();
            if ($result <= 0) {
                setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
                $error++;
                break;
            } else $nbok++;
        }
        else {
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
    //var_dump($listofobjectthirdparties);exit;
}

// facture model
if (!$error && $massaction == 'facture') {
    $db->begin();

    $objecttmp = new $objectclass($db);
    $nbok      = 0;
    foreach ($toselect as $toselectid) {
        $result = $objecttmp->fetch($toselectid);
        if ($result > 0) {
            //print'<pre>';var_dump($objecttmp->array_options);die();
            $initialvalue = $objecttmp->array_options['options_initialvalue'];
            $fixedamount  = $objecttmp->array_options['options_fixedamount'];
            //var_dump($fixedamount);die();
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
            $tacitagreement         = $objecttmp->array_options['options_tacitagreement'];
            $invoicetype            = $objecttmp->array_options['options_invoicetype'];
            $firstdaysofperiods     = array(
                1 => strtotime(date('Y-m-01 00:00:00')), //month
                2 => firstDayOf(3), // trimestre
                3 => firstDayOf(6), // semestre
                4 => strtotime(date('Y-01-01 00:00:00')) //year
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
            $firstdayofnextperiod   = $firstdaysofnextperiods[$periodsetter];
            $lastdayofnextperiod    = $lastdaysofnextperiods[$periodsetter];
            //var_dump($firstdayofperiod);die();
            $now                    = time();
            $daysinperiods          = array(
                1 => date("t"),
                2 => ceil(365 / 4),
                3 => ceil(365 / 2),
                4 => 365
            );
            $daysinperiod           = $daysinperiods[$objecttmp->array_options['options_invoicedates']];
            $lastdayofperiod        = $firstdayofperiod + $oneday * $daysinperiod - 1; // 1 seconde avant le d?but de la p?riode suivante
            if ($invoicetype == '1') { //on facture la p?riode suivante
                $firstdayofperiod = $firstdayofnextperiod;
                $lastdayofperiod  = $lastdayofnextperiod;
                $now              += $oneday * $daysinperiod;
            }
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
            $revalorisationactivationdate = strtotime($objecttmp->array_options['options_revalorisationactivationdate']);
            if ($revalorisationactivationdate == false) $revalorisationactivationdate = 0;

//            if (in_array($objecttmp->element, array('societe','member'))) $result = $objecttmp->delete($objecttmp->id, $user, 1);
//            else $result = $objecttmp->delete($user);
            $result = 1;
            // load facturerecS linked
            $objecttmp->fetchObjectLinked();
            if (isset($objecttmp->linkedObjects["facturerec"])) {
                // most recent facturerec
                foreach ($objecttmp->linkedObjects["facturerec"] as $idref => $obj) {
                    $desc = '';
                    if ($startdate < $now && $now < $enddate || $tacitagreement && $now > $enddate) { // le contrat est en cour
                        $fac          = new Facture($db);
                        $fac->fac_rec = $obj->id;
                        $fac->socid   = $fac->fk_soc  = $obj->socid;
                        $fac->date    = $firstdayofnextperiod;
                        $fac->create($user, 1);
                        //var_dump($fac);die();
                        $ratio        = 1; // periode partielle
                        $majoration   = 0; // majoration indice
                        $desc         .= ' '.dol_print_date($firstdayofperiod, 'day').' => '.dol_print_date($lastdayofperiod, 'day');

                        if ($firstdayofperiod < $startdate) { // prorata de d?but
                            $ratio *= 1 - ($startdate - $firstdayofperiod) / $oneday / $daysinperiod;
                        } // end prorata d?but

                        if ($lastdayofperiod > $enddate && $tacitagreement != '1') { // prorata de fin
                            $ratio *= 1 - ($startdate - $lastdayofperiod) / $oneday / $daysinperiod;
                        } // end prorata fin

                        if ($lastdayofperiod > $revalorisationactivationdate && $lastdayofperiod > $revalorisationdate) { // on doit revaloriser
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
                            if ($prohibitdecrease == '1' && $indice1->indice < $indice0->indice) { // pas de d�pression
                                $indice0->indice = 1;
                                $indice1->indice = 1;
                            }
                            //P1 = P0 x (S1 / S0)
                            $majoration += $montantrevalorisable * $indice1->indice / $indice0->indice - $montantrevalorisable;
//                            var_dump($majoration,$montantrevalorisable, $indice1 , $indice0 , $montantrevalorisable);
//                            die($ratio.' '.$nbdaysafter.' '.$montantrevalorisable.' '.$majoration);
                        } // end revalorisation
                        if (!$error) { // on met ? jour la facture
                            foreach ($fac->lines as &$line) {
                                //print '<pre>';var_dump($fac->tva_tx);die();
                                $fac->updateline(
                                    $line->id, $line->desc.$desc, $line->multicurrency_subprice * $ratio + $majoration, $line->qty, $line->remise_percent, $line->date_start, $line->date_end,
                                    $line->tva_tx
                                );
                            }
                        }
                    }
                }
            }
            //$db->commit();
            //die();
            if ($result <= 0) {
                setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
                $error++;
                break;
            } else $nbok++;
        }
        else {
            setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
            $error++;
            break;
        }
    }

    if (!$error) {
        if ($nbok > 1) setEventMessages($langs->trans("FacturesGeneres", $nbok), null, 'mesgs');
        else setEventMessages($langs->trans("FactureGenere", $nbok), null, 'mesgs');
        $db->commit();
        //header('Location: '.dol_buildpath('/compta/facture/list.php?leftmenu=customers_bills_draft&search_status=0', 2));
    }
    else {
        $db->rollback();
    }
    //var_dump($listofobjectthirdparties);exit;
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
function firstDayOf($period = 3, $next = 0)
{
    $moisActuel = date("n", time() + 2592000 * $next * $period);
    $mois       = ceil($moisActuel / $period) * $period - $period + 1;
    return strtotime(date("Y-$mois-01 00:00:00"));
}

function getIndice($source = 'Syntec', $year = null, $month = null, $id = null)
{
    global $db;
    if ((string) (int) $source == $source) { //la source est un num�ro chez ST
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
    //var_dump($sql);
    $result = $db->query($sql);
    if ($result) {
        if ($db->num_rows($result) === 1) {
            $obj = $db->fetch_object($result);
            return $obj;
        } elseif ($id === null) {
            $sql    = "SELECT indice, year_indice, month_indice FROM `$table` WHERE indice !=0 AND active= 1  AND `year_indice` = ".(1 * $year)." ORDER BY `year_indice` DESC, `month_indice` DESC LIMIT 1";
            //var_dump($sql);
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
