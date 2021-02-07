<?php
/* Copyright (c) 2002-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien Di Cintio     <sdicintio@ressource-toi.org>
 * Copyright (C) 2004       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2017  Regis Houssin           <regis.houssin@capnetworks.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2006       Marc Barilley/Ocebo     <marc@ocebo.com>
 * Copyright (C) 2007       Franky Van Liedekerke   <franky.van.liedekerker@telenet.be>
 * Copyright (C) 2007       Patrick Raguin          <patrick.raguin@gmail.com>
 * Copyright (C) 2010       Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2010-2014  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2011       Herve Prot              <herve.prot@symeos.com>
 * Copyright (C) 2012-2016  Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2012       Cedric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014       Alexandre Spangaro      <aspangaro.dolibarr@gmail.com>
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
 *	\file       synergiestech/core/class/html.formsynergiestechcontract.class.php
 *  \ingroup    core
 *	\brief      File of class with all html predefined components - form modified
 */


/**
 *	Class to manage generation of HTML components
 *	Only common components must be here.
 *
 *  TODO Merge all function load_cache_* and loadCache* (except load_cache_vatrates) into one generic function loadCacheTable
 */
class FormSynergiesTechContract
{
    var $db;
    var $error;
    var $num;

    // Cache arrays
    var $cache_payment_conditions = array();

    /**
     * Constructor
     *
     * @param   DoliDB    $db     Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Load into cache list of payment terms
     *
     * @return  int             Nb of lines loaded, <0 if KO
     */
    function load_cache_payment_conditions()
    {
        global $langs;

        $num = count($this->cache_payment_conditions);
        if ($num > 0) return 0;    // Cache already loaded

        dol_syslog(__METHOD__, LOG_DEBUG);

        $sql = "SELECT rowid, code, libelle as label";
        $sql .= " FROM " . MAIN_DB_PREFIX . 'c_payment_term';
        $sql .= " WHERE active > 0";
        $sql .= " ORDER BY sortorder";

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num) {
                $obj = $this->db->fetch_object($resql);

                // Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
                $label = ($langs->trans("PaymentConditionShort" . $obj->code) != ("PaymentConditionShort" . $obj->code) ? $langs->trans("PaymentConditionShort" . $obj->code) : ($obj->label != '-' ? $obj->label : ''));
                $this->cache_payment_conditions[$obj->rowid]['code'] = $obj->code;
                $this->cache_payment_conditions[$obj->rowid]['label'] = $label;
                $i++;
            }

            //$this->cache_conditions_paiements=dol_sort_array($this->cache_conditions_paiements, 'label', 'asc', 0, 0, 1);		// We use the field sortorder of table

            return $num;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *  Return list of payment modes.
     *  Constant MAIN_DEFAULT_PAYMENT_TERM_ID can used to set default value but scope is all application, probably not what you want.
     *  See instead to force the default value by the caller.
     *
     * @param   int     $selected       Id of payment term to preselect by default
     * @param   string  $htmlname       Nom de la zone select
     * @param   int     $filtertype     Not used
     * @param   int     $addempty       Add an empty entry
     * @return  string
     */
    function select_payment_condition($selected=0, $htmlname='condid', $filtertype=-1, $addempty=0)
    {
        global $langs, $user, $conf;

        dol_syslog(__METHOD__ . " selected=" . $selected . ", htmlname=" . $htmlname, LOG_DEBUG);

        $this->load_cache_payment_conditions();

        // Set default value if not already set by caller
        if (empty($selected) && !empty($conf->global->MAIN_DEFAULT_PAYMENT_TERM_ID)) $selected = $conf->global->MAIN_DEFAULT_PAYMENT_TERM_ID;

        $out = '';
        $out .= '<select class="flat" name="' . $htmlname . '">';
        if ($addempty) $out .= '<option value="0">&nbsp;</option>';
        foreach ($this->cache_payment_conditions as $id => $arrayconditions) {
            if ($selected == $id) {
                $out .= '<option value="' . $id . '" selected>';
            } else {
                $out .= '<option value="' . $id . '">';
            }
            $out .= $arrayconditions['label'];
            $out .= '</option>';
        }
        $out .= '</select>';
        if ($user->admin) $out .= info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);

        return $out;
    }

    /**
     *  Get all invoices draft linked to contracts
     *
     * @return array|int            List of contracts with all linked draft invoices info
     */
    function getInvoicesContractsInfo() {
        global $db;

        $invoices_draft_list = array();
        $sql = "SELECT ee.fk_source AS contract_id, f.rowid, f.ref AS ref, f.ref_client, f.type, f.note_private, f.note_public, f.total AS total_ht, f.tva AS total_vat, f.total_ttc FROM " . MAIN_DB_PREFIX . "facture AS f" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "element_element AS ee ON ee.sourcetype = 'contrat' AND ee.fk_target = f.rowid AND ee.targettype = 'facture'" .
            " WHERE f.fk_statut = 0";
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                if (!isset($invoices_draft_list[$obj->contract_id])) {
                    $invoices_draft_list[$obj->contract_id] = array(
                        'invoices' => array(),
                        'total_ht' => 0,
                        'total_vat' => 0,
                        'total_ttc' => 0,
                    );
                }
                $invoices_draft_list[$obj->contract_id]['invoices'][$obj->rowid] = array(
                    'id' => $obj->rowid,
                    'ref' => $obj->ref,
                    'type' => $obj->type,
                    'ref_client' => $obj->ref_client,
                    'note_private' => $obj->note_private,
                    'note_public' => $obj->note_public,
                    'total_ht' => $obj->total_ht,
                    'total_vat' => $obj->total_vat,
                    'total_ttc' => $obj->total_ttc,
                );
                $invoices_draft_list[$obj->contract_id]['total_ht'] += $obj->total_ht;
                $invoices_draft_list[$obj->contract_id]['total_vat'] += $obj->total_vat;
                $invoices_draft_list[$obj->contract_id]['total_ttc'] += $obj->total_ttc;
            }
        } else {
            return -1;
        }

        return $invoices_draft_list;
    }

    /**
     *  Has contract to terminate
     *
     * @return boolean
     */
    function hasContractsToTerminate() {
        global $db;

        $now = dol_now();
        $sql = "SELECT c.rowid FROM " . MAIN_DB_PREFIX . "contrat as c" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "contratdet as cd ON c.rowid = cd.fk_contrat" .
            " LEFT JOIN " . MAIN_DB_PREFIX . "contrat_extrafields as cef ON c.rowid = cef.fk_object" .
            " WHERE cef.realdate <= '" . $db->idate($now) . "'" .
            " AND cd.statut != 5" .
            " GROUP BY c.rowid";

        $resql = $db->query($sql);
        if ($resql) {
            return $db->num_rows($resql) > 0 ? 1 : 0;
        } else {
            return -1;
        }
    }
}

