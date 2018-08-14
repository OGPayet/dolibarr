<?php
/* Copyright (C) 2018      Open-DSI              <support@open-dsi.fr>
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
 *  \file		htdocs/requestmanager/tpl/createfastzone.tpl.php
 *  \ingroup	requestmanager
 *  \brief		Template to show
 */

// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
if (! $res && file_exists("../../main.inc.php")) $res=@include '../../main.inc.php';					// to work if your module directory is into dolibarr root htdocs directory
if (! $res && file_exists("../../../main.inc.php")) $res=@include '../../../main.inc.php';			// to work if your module directory is into a subdir of root htdocs directory
if (! $res && file_exists("../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../dolibarr/htdocs/main.inc.php';     // Used on dev env only
if (! $res && file_exists("../../../../dolibarr/htdocs/main.inc.php")) $res=@include '../../../../dolibarr/htdocs/main.inc.php';   // Used on dev env only
if (! $res) die("Include of main fails");
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/user/class/usergroup.class.php';

if (!empty($conf->categorie->enabled)) {
    require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
    dol_include_once('/requestmanager/class/categorierequestmanager.class.php');
}

dol_include_once('/requestmanager/class/requestmanager.class.php');
dol_include_once('/requestmanager/class/html.formrequestmanager.class.php');


// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf) || !isset($_POST['zone']))
{
	print "Error, template page can't be called as URL";
	exit();
}

?>

<!-- BEGIN PHP TEMPLATE -->
<?php
$langs->load('companies');
$langs->load('requestmanager@requestmanager');

$zone  = intval(GETPOST('zone' , 'int'));
?>
<?php

//
// Zone 1
//
if ($zone === 1) {
    $selectedActionJs     = GETPOST('action_js')?GETPOST('action_js'):'';
    $selectedActionCommId = GETPOST('actioncomm_id', 'int')?intval(GETPOST('actioncomm_id', 'int')):-1;
    $selectedCategories   = GETPOST('categories', 'array')?GETPOST('categories', 'array'):array();
    $selectedContactId    = GETPOST('contactid', 'int')?intval(GETPOST('contactid', 'int')):-1;
    $selectedDescription  = GETPOST('description', 'alpha')?GETPOST('description', 'alpha'):'';
    $selectedEquipementId = GETPOST('equipement_id', 'int')?intval(GETPOST('equipement_id', 'int')):-1;
    $selectedLabel        = GETPOST('label', 'alpha')?GETPOST('label', 'alpha'):'';
    $selectedSocId        = GETPOST('socid', 'int')?intval(GETPOST('socid', 'int')):-1;
    $selectedFkSource     = GETPOST('source', 'int')?intval(GETPOST('source', 'int')):-1;
    $selectedFkType       = GETPOST('type', 'int')?intval(GETPOST('type', 'int')):-1;
    $selectedFkUrgency    = GETPOST('urgency', 'int')?intval(GETPOST('urgency', 'int')):-1;

    // default filters
    $filterSocId = '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1';

    // load thirdparty of event
    if ($selectedActionCommId>0 && $selectedActionJs=='change_actioncomm_id') {
        $actionComm = new ActionComm($db);
        $actionComm->fetch($selectedActionCommId);
        $actionComm->fetch_thirdparty();
        $actionCommThirdParty = $actionComm->thirdparty;
        $selectedSocId = $actionCommThirdParty->id;
    }

    $form = new Form($db);
    $formrequestmanager = new FormRequestManager($db);
    $usergroup_static = new UserGroup($db);

    print '<table class="border" width="100%">';
    print '<tr>';
    // Source
    print '<td>' . $langs->trans('RequestManagerSource') . '</td>';
    print '<td>' . $formrequestmanager->select_source($selectedFkSource, 'source', 1, 0, array(), 0, 0, 'minwidth100') . '</td>';
    // Urgency
    print '<td>' . $langs->trans('RequestManagerUrgency') . '</td>';
    print '<td>' . $formrequestmanager->select_urgency($selectedFkUrgency, 'urgency', 1, 0, array(), 0, 0, 'minwidth100') . '</td>';
    // Type
    print '<td class="fieldrequired">' . $langs->trans('RequestManagerType') . '</td>';
    $groupslist = $usergroup_static->listGroupsForUser($user->id);
    print '<td>';
    print $formrequestmanager->select_type(array_keys($groupslist), $selectedFkType, 'type', 1, 0, null, 0, 0, 'minwidth100');
    print '</td>';
    print '</tr>';
    print '</table>';

    print '<table class="border" width="100%">';
    print '<tr>';
    // ActionComm
    print '<td class="fieldrequired" width="200px">' . $langs->trans('RequestManagerCreateFastActionCommLabel') . '</td>';
    print '<td>';
    print $formrequestmanager->select_actioncomm('', array('AC_TEL'), $selectedActionCommId, 'actioncomm_id', 1, 0, null, 0);
    print '</td>';
    print '</tr>';
    print '</table>';

    print '<table class="border" width="100%">';
    print '<tr>';
    // Company
    print '<td class="fieldrequired">' . $langs->trans('ThirdParty') . '</td>';
    print '<td>';
    print $form->select_company($selectedSocId, 'socid', $filterSocId, 1, 0, 0, null, 0, 'maxwidth300');
    if (!empty($conf->societe->enabled) &&  $user->rights->societe->creer) {
        $backToPage = dol_buildpath('/requestmanager/createfast.php', 1) . '?action=createfast';
        print ' <a id="new_thridparty" href="' . DOL_URL_ROOT . '/societe/card.php?action=create&client=3&fournisseur=0&backtopage=' . urlencode($backToPage) . '">' . $langs->trans("AddThirdParty") . '</a>';
    }
    print '</td>';
    // Contact
    print '<td>' . $langs->trans('Contact') . '</td>';
    print '<td>';
    $form->select_contacts($selectedSocId, $selectedContactId, 'contactid', 1);
    if ($selectedSocId > 0 && $user->rights->societe->contact->creer) {
        $backToPage = dol_buildpath('/requestmanager/createfast.php', 1) . '?action=createfast';
        $btnCreateContactLabel = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));
        $btnCreateContact = '<a class="addnewrecord" href="' . DOL_URL_ROOT . '/contact/card.php?socid=' . $selectedSocId . '&amp;action=create&amp;backtopage=' . urlencode($backToPage) . '">' . $btnCreateContactLabel;
        if (empty($conf->dol_optimize_smallscreen)) $btnCreateContact .= ' ' . img_picto($btnCreateContactLabel, 'filenew');
        $btnCreateContact .= '</a>' . "\n";
        print '&nbsp;&nbsp;' . $btnCreateContact;
    }
    print '</td>';
    print '</tr>';

    print '<tr>';
    // Equipement
    if ($conf->equipement->enabled) {
        print '<td>' . $langs->trans("Equipement") . '</td>';
        print '<td>';
        print $formrequestmanager->select_equipement($selectedSocId, $selectedEquipementId, 'equipement_id', 1, 0, null, 0);
        print '</td>';
    }
    // Categories
    if ($conf->categorie->enabled) {
        print '<td>' . $langs->trans("Categories") . '</td>';
        print '<td>';
        $cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 1);
        print $form->multiselectarray('categories', $cate_arbo, $selectedCategories, '', 0, '', 0, '100%');
        print '</td>';
    }
    print '</tr>';
    print '</table>';

    print '<table class="border" width="100%">';
    // Label
    print '<tr>';
    print '<td class="fieldrequired">' . $langs->trans("RequestManagerLabel") . '</td>';
    print '<td>';
    print '<input class="quatrevingtpercent" type="text" id="label" name="label" value="' . dol_escape_htmltag($selectedLabel) . '">';
    print '</td>';
    print '</tr>';

    // Description
    print '<tr>';
    print '<td class="tdtop fieldrequired">' . $langs->trans('RequestManagerDescription') . '</td>';
    print '<td valign="top">';
    $doleditor = new DolEditor('description', $selectedDescription, '', 200, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
    print $doleditor->Create(1);
    print '</td>';
    print '</tr>';
    print '</table>';
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function(){
            var requestManagerLoader = new RequestManagerLoader(1, 'create_fast_zone', '<?php echo $_SERVER["PHP_SELF"]; ?>', {});

            jQuery('#actioncomm_id').change(function(){
                requestManagerLoader.loadZone(1, 'change_actioncomm_id');
            });

            jQuery('#categories').change(function(){
                requestManagerLoader.loadZone(1, 'change_categories');
            });

            jQuery('#equipement_id').change(function(){
                requestManagerLoader.loadZone(1, 'change_equipement_id');
            });

            jQuery('#socid').change(function(){
                requestManagerLoader.loadZone(1, 'change_socid');
            });
        });
    </script>
    <?php
}
?>
<?php

//
// Zone 2
//
if ($zone === 2) {
    $selectedCategories   = GETPOST('categories', 'array')?GETPOST('categories', 'array'):array();
    $selectedEquipementId = GETPOST('equipement_id', 'int')?intval(GETPOST('equipement_id', 'int')):-1;
    $selectedSocId        = GETPOST('socid', 'int')?intval(GETPOST('socid', 'int')):-1;

    $requestManager = new RequestManager($db);
    $objectList = $requestManager->loadAllByFkSoc($selectedSocId, array(RequestManager::STATUS_TYPE_INITIAL, RequestManager::STATUS_TYPE_IN_PROGRESS), $selectedCategories, $selectedEquipementId);

    print '<br />';
    print '<table class="nobordernopadding" width="100%">';
    print '<tr class="liste_titre">';
    print '<td align="left">' . $langs->trans("RequestManagerType") . '</td>';
    print '<td align="left">' . $langs->trans("Ref") . '</td>';
    print '<td align="left">' . $langs->trans("RequestManagerLabel") . '</td>';
    print '<td align="left">' . $langs->trans("DateCreation") . '</td>';
    print '</tr>';

    foreach ($objectList as $object) {
        print '<tr class="liste">';
        print '<td align="left">' . $object->getLibType() . '</td>';
        print '<td align="left"><a href="' . dol_buildpath('/requestmanager/card.php', 1) . '?id=' . $object->id . '" target="_blank">'. $object->ref . '</a></td>';
        print '<td align="left">' . $object->label  . '</td>';
        print '<td align="left">' . dol_print_date($object->date_creation, 'dayhour') . '</td>';
        print '</tr>';
    }

    print '<tr>';
    print '<td align="right" colspan="4">';
    print '<input type="submit" class="button" value="' . $langs->trans('RequestManagerCreateFastAction') . '"/>';
    print '</td>';
    print '<tr>';

    print '</table>';
}

?>
<?php

//
// Zone 3
//
if ($zone === 3) {
    $langs->load('contracts');

    $selectedCategories   = GETPOST('categories', 'array')?GETPOST('categories', 'array'):array();
    $selectedEquipementId = GETPOST('equipement_id', 'int')?intval(GETPOST('equipement_id', 'int')):-1;
    $selectedSocId        = GETPOST('socid', 'int')?intval(GETPOST('socid', 'int')):-1;

    $requestManager = new RequestManager($db);

    if ($selectedSocId <= 0) {
        print '';
    } else {
        // Contract list of this thirdparty
        $contractList = array();
        $requestManager->loadAllContract($selectedSocId, FALSE, $contractList);
        print '<br />';
        print load_fiche_titre($langs->trans('Contracts'), '', '');
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder allwidth">';
        print '<tr class="liste_titre">';
        print '<td>' . $langs->trans("RequestManagerCreateFastContractFormula") . '</td>';
        print '<td>' . $langs->trans("Ref") . '</td>';
        print '<td></td>';
        print '</tr>';
        print '</tr>';
        if (count($contractList) > 0) {
            $contractStatic = new Contrat($db);
            $contractExtraFields = new ExtraFields($db);
            $contractExtraLabels = $contractExtraFields->fetch_name_optionals_label($contractStatic->table_element);
            foreach ($contractList as $contract) {
                $formuleId    = $contract->array_options['options_formule'];
                $formuleLabel = $contractExtraFields->attribute_param['formule']['options'][$formuleId];
                print '<tr class="liste">';
                print '<td align="left">' . $formuleLabel . '</td>';
                print '<td align="left"><a href="' . DOL_URL_ROOT.  '/contrat/card.php?id=' . $contract->id . '" target="_blank">' . $contract->ref . '</a></td>';
                print '<tr>';
            }
        }
        print '</table>';
        print '</div>';

        // Equipement list of this thirdparty
        if ($conf->equipement->enabled) {
            $langs->load('equipement@equipement');

            $equipementList = $requestManager->loadAllEquipementByFkSoc($selectedSocId);
            print '<br />';
            print load_fiche_titre($langs->trans('Equipements'), '', '');
            print '<div class="div-table-responsive-no-min">';
            print '<table class="noborder allwidth">';
            print '<tr class="liste_titre">';
            print '<td>' . $langs->trans("Product") . '</td>';
            print '<td>' . $langs->trans("Ref") . '</td>';
            print '<td></td>';
            print '</tr>';
            print '</tr>';
            if (count($equipementList) > 0) {
                foreach($equipementList as $equipement) {
                    $productStatic = new Product($db);
                    $productStatic->fetch($equipement->fk_product);
                    print '<tr class="liste">';
                    print '<td align="left"><a href="' . DOL_URL_ROOT . '/product/card.php?id=' . $equipement->fk_product . '" target="_blank">' . $productStatic->label . '</a></td>';
                    print '<td align="left"><a href="' . dol_buildpath('/equipement/card.php', 1) . '?id=' . $equipement->id . '" target="_blank">' . $equipement->ref . '</a></td>';
                    print '<td align="left"></td>';
                    print '<tr>';
                }
            }
            print '</table>';
            print '</div>';
        }

        // Last 5 events of this thirdparty
        $lastEventList = $requestManager->loadAllLastEventByFkSoc($selectedSocId, 5);
        print '<br />';
        print load_fiche_titre($langs->trans('RequestManagerLastEvents'), '', '');
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder allwidth">';
        print '<tr class="liste_titre">';
        print '<td>' . $langs->trans("Ref") . '</td>';
        print '<td>' . $langs->trans("Label") . '</td>';
        print '<td>' . $langs->trans("Description") . '</td>';
        print '<td></td>';
        print '</tr>';
        if (count($lastEventList) > 0) {
            foreach ($lastEventList as $actionComm) {
                print '</tr>';
                print '<tr class="liste">';
                print '<td align="left"><a href="' . DOL_URL_ROOT . '/comm/action/card.php?id=' . $actionComm->id . '" target="_blank">' . $actionComm->id . '</a></td>';
                print '<td align="left">' . $actionComm->label . '</td>';
                print '<td align="left">' . $actionComm->note . '</td>';
                print '<tr>';
            }
        }
        print '</table>';
        print '</div>';
    }
}

?>
<!-- END PHP TEMPLATE -->
