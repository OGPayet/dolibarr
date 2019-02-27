<?php
/* Copyright (C) 2018      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/requestmanager/lib/requestmanager.lib.php
 * 	\ingroup	requestmanager
 *	\brief      Functions for the module Request Manager
 */

/**
 * Prepare array with list of tabs for admin
 *
 * @return  array				Array of tabs to show
 */
function requestmanager_admin_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/requestmanager/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/requestmanager/admin/dictionaries.php", 1);
    $head[$h][1] = $langs->trans("Dictionary");
    $head[$h][2] = 'dictionaries';
    $h++;

    $head[$h][0] = dol_buildpath("/requestmanager/admin/requestmanager_extrafields.php", 1);
    $head[$h][1] = $langs->trans("RequestManagerExtraFields");
    $head[$h][2] = 'requestmanager_attributes';
    $h++;

    $head[$h][0] = dol_buildpath("/requestmanager/admin/requestmanager_message_extrafields.php", 1);
    $head[$h][1] = $langs->trans("RequestManagerMessageExtraFields");
    $head[$h][2] = 'requestmanager_message_attributes';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'requestmanager_admin');

    $head[$h][0] = dol_buildpath("/requestmanager/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/requestmanager/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'requestmanager_admin', 'remove');

    return $head;
}

/**
 * Return array of tabs to used on pages for request manager cards.
 *
 * @param 	RequestManager	    $object		Object request manager shown
 * @return 	array				            Array of tabs
 */
function requestmanager_prepare_head(RequestManager $object)
{
    global $db, $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath('/requestmanager/card.php', 1) . '?id=' . $object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'requestmanager');

    if ($user->societe_id == 0) {
        // Attached files
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
        require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
        $upload_dir = $conf->requestmanager->multidir_output[$object->entity] . "/" . $object->ref;
        $nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
        $nbLinks = Link::count($db, $object->element, $object->id);

        if ($user->rights->requestmanager->read_file) {
            $head[$h][0] = dol_buildpath('/requestmanager/document.php', 1) . '?id=' . $object->id;
            $head[$h][1] = $langs->trans("Documents");
            if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= ' <span class="badge">' . ($nbFiles + $nbLinks) . '</span>';
            $head[$h][2] = 'document';
            $h++;
        }
    }

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'requestmanager', 'remove');

    return $head;
}

/**
 *	Get an array with properties of all element of the linked object of the event of a thridparty
 *
 * @param 	int 	$socid 	 	Id of the thridparty
 * @return 	array 	 	 	 	array('element'=>array('label'=>'label of the element', 'picto' => 'picto of the element'))
 */
function requestmanager_get_all_element_of_events($socid)
{
    global $langs, $db;
    $elements = array();

    // Get all element type of the event linked to the thridparty
    $sql = "SELECT DISTINCT elementtype FROM " . MAIN_DB_PREFIX . "actioncomm WHERE fk_soc = " . $socid;
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $key = empty($obj->elementtype) ? 'societe' : $obj->elementtype;
            $elements[$key] = '';
        }
    }

    // Load infos
    $elements_infos = requestmanager_get_elements_infos();
    foreach ($elements as $key => $element) {
        $label = '';
        $picto = '';
        if (isset($elements_infos[$key])) {
            $langs->loadLangs($elements_infos[$key]['langs']);
            $label = $langs->trans($elements_infos[$key]['label']);
            $picto = $elements_infos[$key]['picto'];
        }
        $elements[$key] = array('label'=>$label, 'picto' => $picto);
    }

    return $elements;
}

/**
 *	Get an array with properties of all element object
 *
 * @return 	array 	 	 	array('element'=>array('label'=>'label of the element', 'langs'=>'language file of the element', 'picto' => 'picto of the element')
 */
function requestmanager_get_elements_infos()
{
    global $hookmanager;

    // TODO to completed
    $elements = array(
        'accounting_category' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'accounting_journal' => array(
            'label' => 'AccountingJournal',
            'langs' => array('accountancy'),
            'picto' => 'object_billr',
        ),
        'accountingbookkeeping' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'action' => array(
            'label' => 'Action',
            'langs' => array(),
            'picto' => 'object_action',
        ),
        'adherent_type' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'advtargetemailing' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'bank' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'bank_account' => array(
            'label' => 'Account',
            'langs' => array('banks'),
            'picto' => 'object_account',
        ),
        'bookmark' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'category' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'cchargesociales' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'chargesociales' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'chequereceipt' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'commande' => array(
            'label' => 'Order',
            'langs' => array('orders'),
            'picto' => 'object_order',
        ),
        'order' => array(
            'label' => 'Order',
            'langs' => array('orders'),
            'picto' => 'object_order',
        ),
        'commandefournisseurdispatch' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'contact' => array(
            'label' => 'Contact',
            'langs' => array('compagnies'),
            'picto' => 'object_contact',
        ),
        'contrat' => array(
            'label' => 'Contract',
            'langs' => array('contracts'),
            'picto' => 'object_contract',
        ),
        'contract' => array(
            'label' => 'Contract',
            'langs' => array('contracts'),
            'picto' => 'object_contract',
        ),
        'cpaiement' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'cronjob' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'ctyperesource' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'delivery' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'deplacement' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'dolresource' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'don' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'ecm_directories' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'ecmfiles' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'equipement' => array(
            'label' => 'Equipement',
            'langs' => array('equipement@equipement'),
            'picto' => 'object_equipement@equipement',
        ),
        'establishment' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'events' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'expeditionlignebatch' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'expensereport' => array(
            'label' => 'ExpenseReport',
            'langs' => array('trips'),
            'picto' => 'object_trip',
        ),
        'facture' => array(
            'label' => 'Invoice',
            'langs' => array('bills'),
            'picto' => 'object_bill',
        ),
        'invoice' => array(
            'label' => 'Invoice',
            'langs' => array('bills'),
            'picto' => 'object_bill',
        ),
        'facturerec' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'fichinter' => array(
            'label' => 'Intervention',
            'langs' => array('interventions'),
            'picto' => 'object_intervention',
        ),
        'fiscalyear' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'holiday' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'inventory' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'invoice_supplier' => array(
            'label' => 'SupplierInvoice',
            'langs' => array('bills'),
            'picto' => 'object_bill',
        ),
        'link' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'loan' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'loan_schedule' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'mailing' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'member' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'multicurrency' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'multicurrency_rate' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'opensurvey_sondage' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'order_supplier' => array(
            'label' => 'SupplierOrder',
            'langs' => array('orders'),
            'picto' => 'object_order',
        ),
        'paiementcharge' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'payment' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'payment_donation' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'payment_expensereport' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'payment_loan' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'payment_supplier' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'product' => array(
            'label' => 'ProductOrService',
            'langs' => array('products'),
            'picto' => 'object_product',
        ),
        'productbatch' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'productlot' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'ProductStockEntrepot' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'project' => array(
            'label' => 'Project',
            'langs' => array('projects'),
            'picto' => 'object_project',
        ),
        'project_task' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'propal' => array(
            'label' => 'Proposal',
            'langs' => array('propal'),
            'picto' => 'object_propal',
        ),
        'propal_merge_pdf_product' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'shipping' => array(
            'label' => 'Shipment',
            'langs' => array('sendings'),
            'picto' => 'object_sending',
        ),
        'societe' => array(
            'label' => 'ThirdParty',
            'langs' => array('companies'),
            'picto' => 'object_company',
        ),
        'stock' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'stockmouvement' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'subscription' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'supplier_proposal' => array(
            'label' => 'SupplierProposal',
            'langs' => array('supplier_proposal'),
            'picto' => 'object_supplier_proposal',
        ),
        'user' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'usergroup' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'website' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'websitepage' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'widthdraw' => array(
            'label' => '',
            'langs' => array(),
            'picto' => '',
        ),
        'requestmanager' => array(
            'label' => 'RequestManagerRequest',
            'langs' => array('requestmanager@requestmanager'),
            'picto' => 'object_requestmanager@requestmanager',
        ),
    );

    // Add custom object
    $hookmanager->initHooks(array('requestmanagerdao'));
    $parameters = array();
    $reshook = $hookmanager->executeHooks('getElementsInfos', $parameters); // Note that $action and $object may have been
    if ($reshook) $elements = array_merge($elements, $hookmanager->resArray);

    return $elements;
}

/**
 * Return the duration information array('days', 'hours', 'minutes', 'seconds')
 *
 * @param	int	    $timestamp		Duration in second
 * @param	int	    $day			Get days
 * @param   int     $hour_minute    Get hours / minutes
 * @param   int     $second         Get seconds
 *
 * @return	array                  array informations
 */
function requestmanager_get_duration($timestamp, $day = 1, $hour_minute = 1, $second = 0)
{
    $days = $hours = $minutes = $seconds = 0;

    if (!empty($timestamp)) {
        if ($day) {
            $days = floor($timestamp / 86400);
            $timestamp -= $days * 86400;
        }

        if ($hour_minute) {
            $hours = floor($timestamp / 3600);
            $timestamp -= $hours * 3600;

            $minutes = floor($timestamp / 60);
            $timestamp -= $minutes * 60;
        }

        if ($second) {
            $seconds = $timestamp;
        }
    }

    return array('days' => $days, 'hours' => $hours, 'minutes' => $minutes, 'seconds' => $seconds);
}

/**
 * Return a formatted duration (x days x hours x minutes x seconds)
 *
 * @param	int	    $timestamp		Duration in second
 * @param	int	    $day			Show days
 * @param   int     $hour_minute    Show hours / minutes
 * @param   int     $second         Show seconds
 *
 * @return	string                  Formated duration
 */
function requestmanager_print_duration($timestamp, $day = 1, $hour_minute = 1, $second = 0)
{
    global $langs;

    $duration_infos = requestmanager_get_duration($timestamp, $day, $hour_minute, $second);

    $text = '';
    if ($duration_infos['days'] > 0) $text .= $duration_infos['days'] . ' ' . $langs->trans('Days');
    if ($duration_infos['hours'] > 0) $text .= ' ' . $duration_infos['hours'] . ' ' . $langs->trans('Hours');
    if ($duration_infos['minutes'] > 0) $text .= ' ' . $duration_infos['minutes'] . ' ' . $langs->trans('Minutes');
    if ($duration_infos['seconds'] > 0) $text .= ' ' . $duration_infos['seconds'] . ' ' . $langs->trans('Seconds');

    return trim($text);
}
