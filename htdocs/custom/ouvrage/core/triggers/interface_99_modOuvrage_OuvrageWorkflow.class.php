<?php

/**
 *      \file       /ouvrage/core/triggers/interface_99_modOuvrage_OuvrageWorkflow.class.php
 *      \ingroup    ouvrage
 *      \brief      Trigger file for create ouvrage data
 */


/**
 *      \class      InterfaceMilestoneWorkflow
 *      \brief      Classe des fonctions triggers des actions personnalisees du milestone
 */

class InterfaceOuvrageWorkflow
{
    private $db;

    /**
     *   Constructor
     *
     *   @param      DoliDB		$db		Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "ouvrage";
        $this->description = "Triggers of this module allows to create ouvrage data";
        $this->version = '1.0.0';            // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'ouvrage@ouvrage';
    }


    /**
     * Trigger name
     *
     * 	@return		string	Name of trigger file
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Trigger description
     *
     * 	@return		string	Description of trigger file
     */
    public function getDesc()
    {
        return $this->description;
    }

    /**
     * Trigger version
     *
     * 	@return		string	Version of trigger file
     */
    public function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'development') return $langs->trans("Development");
        elseif ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

     /**
     * Function called when a Dolibarrr business event is done.
     * All functions "run_trigger" are triggered if file
     * is inside directory core/triggers
     *
     * 	@param		string		$action		Event action code
     * 	@param		Object		$object		Object
     * 	@param		User		$user		Object user
     * 	@param		Translate	$langs		Object langs
     * 	@param		conf		$conf		Object conf
     * 	@return		int						<0 if KO, 0 if no triggered ran, >0 if OK
     */
    public function run_trigger($action, $object, $user, $langs, $conf)
    {
                        //commande

        // Mettre ici le code a executer en reaction de l'action
        // Les donnees de l'action sont stockees dans $object

        $update_actions = array('LINEPROPAL_UPDATE','LINEORDER_UPDATE','LINEBILL_UPDATE');
        $delete_actions = array('LINEPROPAL_DELETE','LINEORDER_DELETE','LINEBILL_DELETE');

        // Update Line
        if (in_array($action, $update_actions) && !empty($object->fk_parent_line)) {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ". __FILE__ .". id=".$object->rowid);
        }



        // Delete Line
        if (in_array($action, $delete_actions)) {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ". __FILE__ .". id=".$object->rowid);

            if (!empty($object->fk_parent_line)) {
                if ($action == 'LINEPROPAL_DELETE') {
                    require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
                    $ouvrage = new PropaleLigne($this->db);
                    $element = new Propal($this->db);
                    $fetch_line_function = 'getLinesArray';
                }
                elseif ($action == 'LINEORDER_DELETE') {
                    require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
                    $ouvrage = new OrderLine($this->db);
                    $element = new Commande($this->db);
                    $fetch_line_function = 'fetch_lines';
                }
                elseif ($action == 'LINEBILL_DELETE') {
                    require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
                    $ouvrage = new FactureLigne($this->db);
                    $element = new Facture($this->db);
                    $fetch_line_function = 'fetch_lines';
                }

                $ouvrage->fetch($object->fk_parent_line);

                $ouvrage->total_ht = 0;
                $ouvrage->multicurrency_total_ht = 0;
                $ouvrage->pa_ht = 0;
                $ouvrage->subprice = 0;

                $element->fetch(GETPOST('id'));

                $element->{$fetch_line_function}();
                foreach($element->lines as $line) {
                    if ($line->fk_parent_line == $object->fk_parent_line && $line->rowid != $object->rowid) {
                        $ouvrage->multicurrency_total_ht += $line->multicurrency_total_ht;
                        $ouvrage->pa_ht += $line->qty * $line->pa_ht;
                        $ouvrage->subprice += $line->qty * $line->subprice;
                    }
                }

                $ouvrage->total_ht = $ouvrage->multicurrency_total_ht;
                $ouvrage->price = round($ouvrage->multicurrency_total_ht / $ouvrage->qty, 2);
                $perc = round(100 - ($ouvrage->price / $ouvrage->subprice * 100), 2);
                $ouvrage->remise_percent = $perc < 100 ? $perc : 0 ;
                $ouvrage->multicurrency_total_ttc = $ouvrage->total_ht * (100 + $ouvrage->tva_tx)/100;
                $ouvrage->multicurrency_total_tva = $ouvrage->multicurrency_total_ttc - $object->multicurrency_total_ht;


                $ouvrage->total_tva = $ouvrage->multicurrency_total_tva;
                $ouvrage->total_ttc = $ouvrage->multicurrency_total_ttc;

                $ret = $ouvrage->update_total();
                // On modifie

                $ret = $ouvrage->update($user);
                /*var_dump($object->fk_parent_line);
                var_dump($ouvrage);exit;*/

                return;
            }
        }

        // DELETE OUVRAGE
        if ($action == 'OUVRAGE_DELETE') {
            switch ($object->element) {
                case 'commandedet' :
                    require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
                    $element = new Commande($this->db);
                    $fk_element = 'fk_commande';
                    $fetch_line_function = 'fetch_lines';
                    break;
                case 'facturedet' :
                    require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
                    $element = new Facture($this->db);
                    $fk_element = 'fk_facture';
                    $fetch_line_function = 'fetch_lines';
                    break;
                case 'propaldet' :
                    require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
                    $element = new Propal($this->db);
                    $fk_element = 'fk_propal';
                    $fetch_line_function = 'getLinesArray';
                    break;
            }

            $sql = "DELETE FROM ".MAIN_DB_PREFIX.$object->element;
            $sql.= " WHERE `fk_parent_line` = " . $object->rowid;
            $this->db->begin();
            $resql=$this->db->query($sql);
            $this->db->commit();

            return $resql;
        }
        // UPDATE OUVRAGE
        if ($action == 'OUVRAGE_UPDATE') {
            $new_qty = (int)GETPOST('qty');


            $rapport_qty = $object->qty / $new_qty;


            $object->tva_tx = GETPOST('tva_tx');
            $object->label = GETPOST('label');
            $object->qty = $new_qty;
            $object->remise_percent = GETPOST('remise_percent');
            $object->desc = GETPOST('product_desc');

            if (!empty(GETPOST('buying_price'))) {
                $object->pa_ht = GETPOST('buying_price');
            }

            if (!empty(GETPOST('price_ht'))) {
                $object->subprice = GETPOST('price_ht');
            }

            $object->total_ht = $object->qty * $object->subprice;

            $object->update_total();

            $object->update($user);

            if ($rapport_qty != 1 || true) {
                switch ($object->element) {
                    case 'commandedet' :
                        require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
                        $element = new Commande($this->db);
                        $ouvrage = new OrderLine($this->db);
                        $fk_element = 'fk_commande';
                        $fetch_line_function = 'fetch_lines';
                        break;
                    case 'facturedet' :
                        require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
                        $element = new Facture($this->db);
                        $ouvrage = new FactureLigne($this->db);
                        $fk_element = 'fk_facture';
                        $fetch_line_function = 'fetch_lines';
                        break;
                    case 'propaldet' :
                        require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
                        $element = new Propal($this->db);
                        $ouvrage = new PropaleLigne($this->db);
                        $fk_element = 'fk_propal';
                        $fetch_line_function = 'getLinesArray';
                        break;
                }

                $idouvrage = $object->product_type == 9 ? $object->rowid : $object->fk_parent_line;

                $ouvrage->fetch($idouvrage);

                $ouvrage->total_ht = 0;
                $ouvrage->multicurrency_total_ht = 0;
                $ouvrage->pa_ht = 0;
                $ouvrage->subprice = 0;
                //$ouvrage->qty = ceil($ouvrage->qty / $rapport_qty);

                $element->fetch($object->{$fk_element});
                $element->total_ht = 0;
                $element->total_tva = 0;
                $element->total_ttc = 0;

                $element->{$fetch_line_function}();
                foreach($element->lines as $line) {
                    if ($line->fk_parent_line == $idouvrage) {

                        $line->rowid = (!empty($line->rowid)?$line->rowid:$line->id);



                        if ($object->product_type == 9) {
                            $line->qty = ceil($line->qty / $rapport_qty);
                            $line->tva_tx = GETPOST('tva_tx');
                            $line->remise_percent = GETPOST('remise_percent');
                        }

                        if ($line->remise_percent != $ouvrage->remise_percent) {
                            $ouvrage->remise_percent = 0;
                        }

                        $line->total_ht = $line->qty * $line->subprice;
                        $ouvrage->subprice += $line->qty * $line->subprice;

                        $ouvrage->pa_ht += $line->qty * $line->pa_ht;

                        $ouvrage->multicurrency_total_ht += $line->total_ht - ($line->total_ht * $line->remise_percent / 100);
                        $line->update_total();
                        $ret = $line->update($user);
                        if ($ret < 0) $error++;



                        switch ($object->element) {
                            case 'commandedet' :
                                $element->updateline($line->rowid, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, 0.0, 0.0, 'HT', $line->info_bits, $line->date_start, $line->date_end, $line->type, $idouvrage, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, 0, 0, null, 0, 1);
                                break;
                            case 'facturedet' :
                                $element->updateline($line->rowid, $line->desc, $line->subprice, $line->qty, $line->remise_percent, $line->date_start, $line->date_end, $line->tva_tx, 0.0, 0.0, 'HT', $line->info_bits, $line->type, $idouvrage, 0,$line->fk_fournprice, $line->pa_ht, $line->label, $line->special_code, 0, 100, null, 0, 1);
                                break;
                            case 'propaldet' :
                                $element->updateline($line->rowid, $line->subprice, $line->qty, $line->remise_percent, $line->tva_tx, 0.0, 0.0, $line->desc, 'HT', $line->info_bits, $line->special_code, $idouvrage, 0, $line->fk_fournprice, $line->pa_ht, $line->label, $line->type, $line->date_start, $line->date_end, $line->array_options, $line->fk_unit, 0, 1);
                                break;
                        }
                    }
                }

                $ouvrage->total_ht = $ouvrage->multicurrency_total_ht;
                $ouvrage->price = round($ouvrage->multicurrency_total_ht / $ouvrage->qty, 2);
                $ouvrage->subprice = round($ouvrage->subprice / $ouvrage->qty, 2);

                if ($object->product_type != 9) {
                    $perc = round(100 - (($ouvrage->total_ht / ($ouvrage->subprice * $ouvrage->qty)) * 100), 2);
                    $ouvrage->remise_percent = $perc < 100 ? $perc : 0 ;
                }

                $ouvrage->multicurrency_total_ttc = $ouvrage->total_ht * (100 + $ouvrage->tva_tx)/100;
                $ouvrage->multicurrency_total_tva = $ouvrage->multicurrency_total_ttc - $object->multicurrency_total_ht;


                $ouvrage->total_tva = $ouvrage->multicurrency_total_tva;
                $ouvrage->total_ttc = $ouvrage->multicurrency_total_ttc;

                $ret = $ouvrage->update_total();
                // On modifie

                $ret = $ouvrage->update($user);
            }

            if ($object->element == 'facturedet') {
                Header ('Location: '.$_SERVER["PHP_SELF"].'?facid='.$object->{$fk_element});
            }
            Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->{$fk_element});

            return 1;
        }

        // Update Line
        if (in_array($action, $update_actions)) {
            if (!empty($object->fk_parent_line)) {
                if ($action == 'LINEPROPAL_UPDATE') {
                    require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
                    $ouvrage = new PropaleLigne($this->db);
                    $element = new Propal($this->db);
                    $fk_element = 'fk_propal';
                    $fetch_line_function = 'getLinesArray';
                }
                elseif ($action == 'LINEORDER_UPDATE') {
                    require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
                    $ouvrage = new OrderLine($this->db);
                    $element = new Commande($this->db);
                    $fk_element = 'fk_commande';
                    $fetch_line_function = 'fetch_lines';
                }
                elseif ($action == 'LINEBILL_UPDATE') {
                    require_once(DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php');
                    $ouvrage = new FactureLigne($this->db);
                    $element = new Facture($this->db);
                    $fk_element = 'fk_facture';
                    $fetch_line_function = 'fetch_lines';
                }

                $ouvrage->fetch($object->fk_parent_line);

                $ouvrage->total_ht = 0;
                $ouvrage->multicurrency_total_ht = 0;
                $ouvrage->pa_ht = 0;
                $ouvrage->subprice = 0;

                $element->fetch(GETPOST('id'));

                $element->{$fetch_line_function}();
                foreach($element->lines as $line) {
                    if ($line->fk_parent_line == $object->fk_parent_line) {
                        $ouvrage->multicurrency_total_ht += $line->multicurrency_total_ht;
                        $ouvrage->pa_ht += $line->qty * $line->pa_ht;
                        $ouvrage->subprice += $line->qty * $line->subprice;
                    }
                }


                $ouvrage->price = round($ouvrage->multicurrency_total_ht / $ouvrage->qty, 2);
                $perc = round(100 - ($ouvrage->price / $ouvrage->subprice * 100), 2);
                $ouvrage->multicurrency_subprice = $ouvrage->subprice;
                $ouvrage->remise_percent = $perc < 100 ? $perc : 0 ;
                $ouvrage->multicurrency_total_ttc = $ouvrage->multicurrency_total_ht * (100 + $ouvrage->tva_tx)/100;
                $ouvrage->multicurrency_total_tva = $ouvrage->total_ttc - $object->multicurrency_total_ht;


                $ouvrage->total_ht = $ouvrage->multicurrency_total_ht;
                $ouvrage->total_tva = $ouvrage->multicurrency_total_tva;
                $ouvrage->total_ttc = $ouvrage->multicurrency_total_ttc;

                $ret = $ouvrage->update_total();

                // On modifie

                $ret = $ouvrage->update($user);



                return $ret;
            }

        }


	return 0;
    }

}
?>
