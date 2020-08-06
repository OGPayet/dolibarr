<?php
/* Change Tiers
 * Copyright (C) 2018       Inovea-conseil.com     <info@inovea-conseil.com>
 */

/**
 * \file    class/actions_changetiers.class.php
 * \ingroup changetiers
 * \brief   ActionsChangetiers
 *
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';

/**
 * Class ActionsAproximite
 */
class Actionschangetiers
{
    /**
     * @var DoliDB Database handler
     */
    private $db;

    /**
     *  Constructor
     *
     * @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    function doActions($parameters, &$object, &$action, $hookmanager)
    {

        global $langs, $user, $conf;
        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';

        $jsscript = '';

        $actions_exclude = array('create', 'modif');

        if ($action == 'changetiers' && GETPOST('socid')) {

            if ($object->element == 'invoice_supplier') {
                $object->fk_soc = GETPOST('socid');
            } else {
                $object->socid = GETPOST('socid');
            }
            if (method_exists($object, 'update')) {
                if ((int)DOL_VERSION >= '7') {
                    $object->update($user);
                } else {
                    $object->update();
                }
            } else {
                $sql = "UPDATE " . MAIN_DB_PREFIX.$object->table_element . " SET fk_soc = " . GETPOST('socid') . " WHERE rowid = " . $object->id;
                $resql=$this->db->query($sql);
                if ($resql)
                {
                    $this->db->commit();
                } else {
                    $this->db->rollback();
                    dol_print_error($this->db);
                    return 0;
                }
            }
            $object->delete_linked_contact('external');

            $object->generateDocument('', $langs);
        }

        return 0;
    }

    function printCommonFooter($parameters, &$object, &$action, $hookmanager) {

        global $langs, $user, $conf;
        require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
        require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
        require_once DOL_DOCUMENT_ROOT . '/comm/propal/class/propal.class.php';
        require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
        require_once DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
        require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.facture.class.php';
        require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
        require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';

        $idparam = 'id';
        $idextparam = 'socid';
        $paramcompany = 'client>0';

        switch ($parameters['currentcontext']) {
            case 'ordercard' :
                $object = new Commande($this->db);
                $object->fetch(GETPOST('id'));
                break;
            case 'propalcard' :
                $object = new Propal($this->db);
                $object->fetch(GETPOST('id'));
                break;
            case 'invoicecard' :
                $object = new Facture($this->db);
                $object->fetch(GETPOST('facid'));
                $idparam = 'facid';
                break;
            case 'supplier_proposalcard' :
                $object = new SupplierProposal($this->db);
                $object->fetch(GETPOST('id'));
                $paramcompany = 'fournisseur=1';
                break;
            case 'ordersuppliercard' :
                $object = new CommandeFournisseur($this->db);
                $object->fetch(GETPOST('id'));
                $paramcompany = 'fournisseur=1';
                break;
            case 'invoicesuppliercard' :
                $object = new FactureFournisseur($this->db);
                $object->fetch(GETPOST('facid'));
                $idparam = 'facid';
                $paramcompany = 'fournisseur=1';
                break;
            case 'expeditioncard' :
                $object = new Expedition($this->db);
                $object->fetch(GETPOST('id'));
                $idparam = 'id';
                break;
        }

        $jsscript = '';

        $actions_exclude = array('create', 'modif');

        $element_authorized = array('propal', 'commande', 'facture', 'supplier_proposal', 'order_supplier', 'invoice_supplier', 'expedition');


        if (1 ||($action == '' || !in_array($action, $actions_exclude)) && (in_array($object->element, $element_authorized) && $conf->global->{strtoupper($object->element).'_CHANGE_THIRDPARTY'})) {

            $jsscript .= '<script>';

            $form = new Form($this->db);

            // Select changement tiers
            $formtiers = '<form method="post" action="'.$_SERVER['PHP_SELF'] . '?'.$idparam.'=' . GETPOST($idparam).'">' . PHP_EOL;
            $formtiers .=  '<input type="hidden" name="action" value="changetiers">' . PHP_EOL;
            $formtiers .=  '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">' . PHP_EOL;
            $formtiers .=  $form->select_company($object->{$idextparam}, 'socid', $paramcompany) . PHP_EOL;
            $formtiers .=  '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">' . PHP_EOL;
            $formtiers .=  '<input type="submit" name="cancel" class="button valignmiddle" id="changetierscancelbtn" value="'.$langs->trans("Cancel").'">' . PHP_EOL;
            $formtiers .=  '</form>' . PHP_EOL;

            $matches = null;
            $returnValue = preg_match_all('#<script(.*?)>(.*?)</script>#is', $formtiers, $matches);

            $jsscript .= 'var urlConf = "' . $_SERVER['PHP_SELF'] . '";' . PHP_EOL;

            $jsscript .= 'var changeTiers = true;' . PHP_EOL;
            if(DOL_VERSION>7){
                $jsscript .= 'var pictoChangeTiers = "<span class=\'fa fa-pencil marginleftonly valignmiddle pictoedit\' style=\'color: #444;\' alt=\'Modifier\' title=\'Modifier\'></span>";' . PHP_EOL;
            }else{
                $jsscript .= 'var pictoChangeTiers = "<img class=\'valigntextbottom\' src=\''.DOL_URL_ROOT.'/theme/eldy/img/edit.png\'>";' . PHP_EOL;
            }
            $jsscript .=   "var formTiers = `" . PHP_EOL
                    .preg_replace('#<script(.*?)>(.*?)</script>#is', '', $formtiers).PHP_EOL
                    . " ` ;" . PHP_EOL;



            if (isset($matches[2]) && isset($matches[2][0])) {
                $jsscript .=   "var scriptTiers = `" . PHP_EOL
                        .$matches[2][0].PHP_EOL
                        . " ` ;" . PHP_EOL;
            }

            $jsscript .= '</script>';

        }

        echo $jsscript;
        return 0;
    }
}
