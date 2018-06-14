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
    //$db->begin();

    $objecttmp = new $objectclass($db);
    $nbok      = 0;
    foreach ($toselect as $toselectid) {
        $result = $objecttmp->fetch($toselectid);
        if ($result > 0) {
            //print'<pre>';var_dump($objecttmp->array_options);die();
            $nbFactAn     = array(0, 12, 4, 2, 1); // mensuel, trimestre ....
            $initialvalue = $objecttmp->array_options['options_initialvalue'];
            $fixedamount  = $objecttmp->array_options['options_fixedamount'];
            $invoicedates = $nbFactAn[$objecttmp->array_options['options_invoicedates']]; //periode
            $initialvalue = $objecttmp->array_options['options_initialvalue'];
            $initialvalue = $objecttmp->array_options['options_initialvalue'];
            $initialvalue = $objecttmp->array_options['options_initialvalue'];
            $initialvalue = $objecttmp->array_options['options_initialvalue'];
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
            $facture         = new Facture($db);
            $facture->socid  = $facture->fk_soc = $objecttmp->socid;
            $facture->fetch_thirdparty();
            $facture->date   = dol_now();
            $facture->create($user);
            $lines           = $objecttmp->lines;
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

                    $resultat = $facture->addline($desc, $lines[$i]->subprice / $invoicedates, $lines[$i]->qty, $tva_tx, $localtax1_tx, $localtax2_tx, $lines[$i]->fk_product,
                        $lines[$i]->remise_percent, $date_start, $date_end, 0, $lines[$i]->info_bits, $lines[$i]->fk_remise_except, 'HT', 0, $product_type, $lines[$i]->rang, $lines[$i]->special_code,
                        $facture->origin, $lines[$i]->rowid, $fk_parent_line, $lines[$i]->fk_fournprice, $lines[$i]->pa_ht / $invoicedates, $label, $array_options, $lines[$i]->situation_percent,
                        $lines[$i]->fk_prev_id, $lines[$i]->fk_unit);

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
            $facturerec->titre                     = $facture->thirdparty->nom." ($facture->id)";
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
        //$db->commit();
    }
    else {
        //$db->rollback();
    }
    //var_dump($listofobjectthirdparties);exit;
}

// facture model
if (!$error && $massaction == 'facture') {
    //$db->begin();

    $objecttmp = new $objectclass($db);
    $nbok      = 0;
    foreach ($toselect as $toselectid) {
        $result = $objecttmp->fetch($toselectid);
        if ($result > 0) {
            //print'<pre>';var_dump($objecttmp->array_options);die();
            $nbFactAn        = array(0, 12, 4, 2, 1); // mensuel, trimestre ....
            $initialvalue    = $objecttmp->array_options['options_initialvalue'];
            $fixedamount     = $objecttmp->array_options['options_fixedamount'];
            $invoicedates    = $nbFactAn[$objecttmp->array_options['options_invoicedates']]; //periode
            // dates
            $startdate       = strtotime($objecttmp->array_options['options_startdate']);
            $duration        = $objecttmp->array_options['options_duration'];
            $enddate         = strtotime($objecttmp->array_options['options_startdate']." +$duration month");
            $tacitagreement  = $objecttmp->array_options['options_tacitagreement'];
            $invoicetype     = $objecttmp->array_options['options_invoicetype'];
            $firstdayofmonth = strtotime(date('Y-m-01 00:00:00'));
            $now             = time();
            $daysinmonth     = date("t");
            $tstamptoday     = 60 * 60 * 24;
            //
            $initialvalue    = $objecttmp->array_options['options_initialvalue'];
            $initialvalue    = $objecttmp->array_options['options_initialvalue'];
//            if (in_array($objecttmp->element, array('societe','member'))) $result = $objecttmp->delete($objecttmp->id, $user, 1);
//            else $result = $objecttmp->delete($user);
            $result          = 1;
            // load facturerecS linked
            $objecttmp->fetchObjectLinked();
            if (isset($objecttmp->linkedObjects["facturerec"])) {
                // most recent facturerec

                foreach ($objecttmp->linkedObjects["facturerec"] as $idref => $obj) {
                    if ($startdate < $now && $now < $enddate) { // le contrat est en cour
                        $fac          = new Facture($db);
                        $fac->fac_rec = $obj->id;
                        $fac->socid   = $fac->fk_soc  = $obj->socid;
                        $fac->date    = dol_now();
                        $fac->create($user);
                        if ($firstdayofmonth < $startdate) { // prorata de début
                            $prorata = ($startdate - $firstdayofmonth) / $tstamptoday / $daysinmonth;
//                            print '<pre>';var_dump($fac->lines);die();
//                            foreach ($fac->lines as $key => $value) {
//
//                            }
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
        if ($nbok > 1) setEventMessages($langs->trans("ModelesGeneres", $nbok), null, 'mesgs');
        else setEventMessages($langs->trans("ModeleGenere", $nbok), null, 'mesgs');
        //$db->commit();
    }
    else {
        //$db->rollback();
    }
    //var_dump($listofobjectthirdparties);exit;
}

$parameters['toselect']  = $toselect;
$parameters['uploaddir'] = $uploaddir;

$reshook = $hookmanager->executeHooks('doMassActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
