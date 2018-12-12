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
dol_include_once('/requestmanager/lib/requestmanagertimeslots.lib.php');


// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf) || !isset($_POST['zone']))
{
	print "Error, template page can't be called as URL";
	exit();
}


// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('requestmanagerfastcard','globalcard'));

// Load other template instead of this one
foreach($conf->modules_parts['tpl'] as $reldir) {
    $res = @include dol_buildpath($reldir . '/rm_createfastzone.tpl.php');
    if ($res) {
        exit();
    }
}

?>

<!-- BEGIN PHP TEMPLATE -->
<?php
$langs->load('companies');
$langs->load('agenda');
$langs->load('requestmanager@requestmanager');

$zone  = intval(GETPOST('zone' , 'int'));
?>
<?php

//
// Zone 1
//
if ($zone === 1) {
    $selectedActionJs               = GETPOST('action_js')?GETPOST('action_js'):'';
    $selectedActionCommId           = GETPOST('actioncomm_id', 'int')?intval(GETPOST('actioncomm_id', 'int')):-1;
    $selectedCategories             = GETPOST('categories', 'array') ? GETPOST('categories', 'array') : (GETPOST('categories', 'alpha') ? explode(',', GETPOST('categories', 'alpha')) : array());
    $selectedContacts               = GETPOST('contact_ids', 'array') ? GETPOST('contact_ids', 'array') : (GETPOST('contact_ids', 'alpha') ? explode(',', GETPOST('contact_ids', 'alpha')) : array());
    $selectedDescription            = GETPOST('description')?GETPOST('description'):'';
    $selectedEquipementId           = GETPOST('equipement_id', 'int')?intval(GETPOST('equipement_id', 'int')):-1;
    $selectedLabel                  = GETPOST('label', 'alpha')?GETPOST('label', 'alpha'):'';
    $selectedSocIdOrigin            = GETPOST('socid_origin', 'int')?intval(GETPOST('socid_origin', 'int')):-1;
    $selectedSocId                  = GETPOST('socid', 'int')?intval(GETPOST('socid', 'int')):-1;
    $selectedSocIdBenefactor        = GETPOST('socid_benefactor', 'int')?intval(GETPOST('socid_benefactor', 'int')):-1;
    $selectedFkSource               = GETPOST('source', 'int')?intval(GETPOST('source', 'int')):-1;
    $selectedFkType                 = GETPOST('type', 'int')?intval(GETPOST('type', 'int')):-1;
    $selectedFkUrgency              = GETPOST('urgency', 'int')?intval(GETPOST('urgency', 'int')):-1;
    $selectedRequesterNotification  = GETPOST('notify_requester_by_email', 'int') > 0 ? 1 : 0;

    // default filters
    $filterSocId = '(s.client = 1 OR s.client = 2 OR s.client = 3) AND status=1';

    // load thirdparty of event
    if ($selectedActionCommId>0 && $selectedActionJs=='change_actioncomm_id') {
        $actionComm = new ActionComm($db);
        $actionComm->fetch($selectedActionCommId);
        $selectedSocIdOrigin = $actionComm->socid;
    }

    $form = new Form($db);
    $formrequestmanager = new FormRequestManager($db);
    $usergroup_static = new UserGroup($db);

    if (!empty($conf->companyrelationships->enabled)) {
        dol_include_once('/companyrelationships/class/companyrelationships.class.php');
        $companyrelationships = new CompanyRelationships($db);

        // Set default values
        $force_set = $selectedActionJs=='change_socid_origin';
        if ($selectedSocIdOrigin > 0) {
            $originRelationshipType = $companyrelationships->getRelationshipType($selectedSocIdOrigin);
            if ($originRelationshipType == 0) { // Benefactor company
                $selectedSocIdBenefactor = $selectedSocIdBenefactor < 0 || $force_set ? $selectedSocIdOrigin : $selectedSocIdBenefactor;
            } elseif ($originRelationshipType > 0) { // Principal company or both
                $selectedSocId = $selectedSocId < 0 || $force_set ? $selectedSocIdOrigin : $selectedSocId;
            } else { // None
                $selectedSocId = $selectedSocId < 0 || $force_set ? $selectedSocIdOrigin : $selectedSocId;
                $selectedSocIdBenefactor = $selectedSocIdBenefactor < 0 || $force_set ? $selectedSocId : $selectedSocIdBenefactor;
            }
        }
        if ($selectedSocId > 0) {
            $benefactor_companies_ids = $companyrelationships->getRelationships($selectedSocId, 1);
            $benefactor_companies_ids = is_array($benefactor_companies_ids) ? array_values($benefactor_companies_ids) : array();
            $selectedSocIdBenefactor = $selectedSocIdBenefactor < 0 || $force_set ? (count($benefactor_companies_ids) > 0 ? $benefactor_companies_ids[0] : $selectedSocId) : $selectedSocIdBenefactor;
        }
        if ($selectedSocIdBenefactor > 0) {
            $principal_companies_ids = $companyrelationships->getRelationships($selectedSocIdBenefactor, 0);
            $principal_companies_ids = is_array($principal_companies_ids) ? array_values($principal_companies_ids) : array();
            $selectedSocId = $selectedSocId < 0 || $force_set ? (count($principal_companies_ids) > 0 ? $principal_companies_ids[0] : $selectedSocIdBenefactor) : $selectedSocId;
        }

        // Get principal companies
        $principal_companies_ids = $companyrelationships->getRelationships($selectedSocIdBenefactor, 0);

        // Get benefactor companies
        $benefactor_companies_ids = $companyrelationships->getRelationships($selectedSocId, 1);
    }

    print '<table class="border" width="100%">';
    print '<tr>';
    // Source
    print '<td>' . $langs->trans('RequestManagerSource') . '</td>';
    print '<td>' . $formrequestmanager->select_source($selectedFkSource, 'source', 1, 0, array(), 0, 0, 'minwidth300') . '</td>';
    // Urgency
    print '<td>' . $langs->trans('RequestManagerUrgency') . '</td>';
    print '<td>' . $formrequestmanager->select_urgency($selectedFkUrgency, 'urgency', 1, 0, array(), 0, 0, 'minwidth300') . '</td>';
    // Type
    print '<td class="fieldrequired">' . $langs->trans('RequestManagerType') . '</td>';
    $groupslist = $usergroup_static->listGroupsForUser($user->id);
    print '<td>';
    print $formrequestmanager->select_type(array_keys($groupslist), $selectedFkType, 'type', 1, 0, null, 0, 0, 'minwidth300');
    print '</td>';
    print '</tr>';
    print '</table>';

    print '<table class="border" width="100%">';
    print '<tr>';
    // ActionComm
    print '<td width="200px">' . $langs->trans('RequestManagerCreateFastActionCommLabel') . '</td>';
    print '<td>';
    print $formrequestmanager->select_actioncomm('', array('AC_TEL'), $selectedActionCommId, 'actioncomm_id', 1, 0, null, 0, 'minwidth300');
    print '</td>';
    if (!empty($conf->global->REQUESTMANAGER_TIMESLOTS_ACTIVATE) && $selectedSocId > 0) {
        dol_include_once('/requestmanager/lib/requestmanagertimeslots.lib.php');
        print '<td colspan="2" width="50%" align="center">';
        $res = requestmanagertimeslots_is_in_time_slot($selectedSocId, dol_now());
        if (is_array($res)) {
            print '<span style="font-weight: bolder !important; font-size: 16px !important; color: green !important;">' . $langs->trans('RequestManagerTimeSlotsIntoPeriod', sprintf("%02d:%02d", $res['begin']['hour'], $res['begin']['minute']), sprintf("%02d:%02d", $res['end']['hour'], $res['end']['minute'])) . '</span>';
        } else {
            if (!$res) {
                print '<span style="font-weight: bolder !important; font-size: 16px !important; color: red !important;">' . $langs->trans('RequestManagerTimeSlotsOutOfPeriod') . '</span>';
                $outOfTimes = requestmanagertimeslots_get_out_of_time_infos($selectedSocId);
                if (is_array($outOfTimes) && count($outOfTimes) > 0) {
                    $toprint = array();
                    foreach ($outOfTimes as $infos) {
                        $toprint[] = '&nbsp;-&nbsp;' . $infos['year'] . (isset($infos['month']) ? '-' . $infos['month'] : '') . ' : ' . $infos['count'];
                    }
                    print '&nbsp;' . $form->textwithpicto('', $langs->trans('RequestManagerCreatedOutOfTime') . ' :<br>' . implode('<br>', $toprint), 1, 'warning');
                }
            }
        }
        print '</td>';
    }
    print '</tr>';
    print '</table>';

    print '<table class="border" width="100%">';
    print '<tr>';
    // ThirdParty Origin
    print '<td class="fieldrequired">' . $langs->trans('RequestManagerThirdPartyOrigin') . '</td><td>';
    print $form->select_company($selectedSocIdOrigin, 'socid_origin', $filterSocId, 'SelectThirdParty', 0, 0, array(), 0, 'minwidth300');
    if (!empty($conf->societe->enabled) && $user->rights->societe->creer) {
        $backToPage = dol_buildpath('/requestmanager/createfast.php', 1) . '?action=createfast' . ($selectedFkType ? '&type=' . $selectedFkType : '') . '&socid_origin=##SOCID##';
        if (!empty($conf->companyrelationships->enabled)) {
            $backToPage .= ($selectedSocId ? '&socid=' . $selectedSocId : '') . ($selectedSocIdBenefactor ? '&socid_benefactor=' . $selectedSocIdBenefactor : '');
        }
        print ' <a id="new_thridparty" href="' . DOL_URL_ROOT . '/societe/card.php?action=create&client=3&fournisseur=0&backtopage=' . urlencode($backToPage) . '">' . $langs->trans("AddThirdParty") . '</a>';
    }
    if (empty($conf->companyrelationships->enabled)) {
        print '
        <script type="text/javascript">
            $(document).ready(function(){
              $("#socid_origin").on("change", function() {
                  var value = $(this).val();
                  $("#socid").val(value);
                  $("#socid_benefactor").val(value);
              });
            });
        </script>';
        print '<input type="hidden" id="socid" name="socid" value="' . $selectedSocId . '">';
        print '<input type="hidden" id="socid_benefactor" name="socid_benefactor" value="' . $selectedSocIdBenefactor . '">';
    }
    print '</td>';
    // Requester Contacts
	print '<td>' . $langs->trans('RequestManagerRequesterContacts') . '</td><td>';
    print $formrequestmanager->multiselect_contacts($selectedSocIdOrigin, $selectedContacts, 'contact_ids', '', '', 0, 'minwidth300');
    if ($selectedSocIdOrigin > 0 && $user->rights->societe->contact->creer) {
        $backToPage = dol_buildpath('/requestmanager/createfast.php', 1) . '?action=createfast' . ($selectedFkType ? '&type=' . $selectedFkType : '') . ($selectedSocIdOrigin ? '&socid_origin=' . $selectedSocIdOrigin : '') . ($selectedSocId ? '&socid=' . $selectedSocId : '') . ($selectedSocIdBenefactor ? '&socid_benefactor=' . $selectedSocIdBenefactor : '');
        $btnCreateContactLabel = (!empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("AddContact") : $langs->trans("AddContactAddress"));
        $btnCreateContact = '<a class="addnewrecord" href="' . DOL_URL_ROOT . '/contact/card.php?socid=' . $selectedSocIdOrigin . '&amp;action=create&amp;backtopage=' . urlencode($backToPage) . '">' . $btnCreateContactLabel;
        if (empty($conf->dol_optimize_smallscreen)) $btnCreateContact .= ' ' . img_picto($btnCreateContactLabel, 'filenew');
        $btnCreateContact .= '</a>' . "\n";
        print '&nbsp;&nbsp;' . $btnCreateContact;
    }
    print '&nbsp;&nbsp;<input type="checkbox" id="notify_requester_by_email" name="notify_requester_by_email"' . ($selectedRequesterNotification ? ' checked="checked"' : '') . ' value="1"><label for="notify_requester_by_email">&nbsp;' . $langs->trans('Notifications') . '</label>';
    print '</td>';
    print '</tr>';

    if (!empty($conf->companyrelationships->enabled)) {
        print '<tr>';
        // ThirdParty Principal
        print '<td>' . $langs->trans('RequestManagerThirdPartyPrincipal') . '</td><td>';
        print $form->select_company($selectedSocId, 'socid', $filterSocId, 'SelectThirdParty', 0, 0, array(), 0, 'minwidth300');
        if (!empty($conf->societe->enabled) && $user->rights->societe->creer) {
            $backToPage = dol_buildpath('/requestmanager/createfast.php', 1) . '?action=createfast' . ($selectedFkType ? '&type=' . $selectedFkType : '') . ($selectedSocIdOrigin ? '&socid_origin=' . $selectedSocIdOrigin : '') . ($selectedSocIdBenefactor ? '&socid_benefactor=' . $selectedSocIdBenefactor : '') . '&socid=##SOCID##';
            print ' <a id="new_thridparty" href="' . DOL_URL_ROOT . '/societe/card.php?action=create&client=3&fournisseur=0&backtopage=' . urlencode($backToPage) . '">' . $langs->trans("AddThirdParty") . '</a>';
        }
        print '</td>';
        // ThirdParty Benefactor
        print '<td>' . $langs->trans('RequestManagerThirdPartyBenefactor') . '</td><td>';
        print $form->select_company($selectedSocIdBenefactor, 'socid_benefactor', $filterSocId, 'SelectThirdParty', 0, 0, array(), 0, 'minwidth300');
        print '
        <script type="text/javascript">
            $(document).ready(function(){
              move_top_select_options("socid", ' . json_encode($principal_companies_ids) . ');
              move_top_select_options("socid_benefactor", ' . json_encode($benefactor_companies_ids) . ');
            });
        </script>';
        print '</td>';
        print '</tr>';
    }

    print '<tr>';
    // Equipement
    if ($conf->equipement->enabled) {
        print '<td>' . $langs->trans("Equipement") . '</td>';
        print '<td>';
        print $formrequestmanager->select_benefactor_equipement($selectedSocId, $selectedSocIdBenefactor, $selectedEquipementId, 'equipement_id', 1, 0, null, 0, 'minwidth300');
        print '</td>';
    }
    // Categories
    if ($conf->categorie->enabled) {
        print '<td>' . $langs->trans("RequestManagerTags") . '</td>';
        print '<td>';
        print $formrequestmanager->multiselect_categories($selectedCategories, 'categories',  '', 0, '', 0, '100%');
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

    // btn create
    print '<div align="right">';
    print '<input type="submit" class="button" name="btn_create" value="' . $langs->trans('RequestManagerCreateFastBtnCreateLabel') . '"/>';
    print '</div>';

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

          jQuery('#socid_origin').change(function(){
              requestManagerLoader.loadZone(1, 'change_socid_origin');
          });

          jQuery('#socid').change(function(){
              requestManagerLoader.loadZone(1, 'change_socid');
          });

          jQuery('#socid_benefactor').change(function(){
              requestManagerLoader.loadZone(1, 'change_socid_benefactor');
          });
        });
    </script>
    <?php

    // Wrapper to show tooltips (html or onclick popup)
    if (! empty($conf->use_javascript_ajax) && empty($conf->dol_no_mouse_hover))
    {
        print "\n<!-- JS CODE TO ENABLE tipTip on all object with class classfortooltip -->\n";
        print '<script type="text/javascript">
            jQuery(document).ready(function () {
              jQuery(".classfortooltip").tipTip({maxWidth: "'.dol_size(($conf->browser->layout == 'phone' ? 400 : 700),'width').'px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
              jQuery(".classfortooltiponclicktext").dialog({ width: 500, autoOpen: false });
              jQuery(".classfortooltiponclick").click(function () {
                console.log("We click on tooltip for element with dolid="+$(this).attr(\'dolid\'));
                if ($(this).attr(\'dolid\'))
                {
                  obj=$("#idfortooltiponclick_"+$(this).attr(\'dolid\'));
                  obj.dialog("open");
                }
              });
            });
          </script>' . "\n";
    }
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

    $requestManagerStatic = new RequestManager($db);
    $requestManagerList = $requestManagerStatic->loadAllByFkSoc($selectedSocId, array(RequestManager::STATUS_TYPE_INITIAL, RequestManager::STATUS_TYPE_IN_PROGRESS), $selectedCategories, $selectedEquipementId);

    if (count($requestManagerList) > 0) {
        print '<br />';
        print load_fiche_titre($langs->trans('ListOfActions'), '', '');
        print '<div class="div-table-responsive-no-min">';
        print '<table class="noborder allwidth">';
        print '<tr class="liste_titre">';
        print '<td align="left">' . $langs->trans("RequestManagerType") . '</td>';
        print '<td align="left">' . $langs->trans("Ref") . '</td>';
        print '<td align="left">' . $langs->trans("RequestManagerLabel") . '</td>';
        print '<td align="left">' . $langs->trans("DateCreation") . '</td>';
        print '<td align="left"></td>';
        print '</tr>';

        foreach ($requestManagerList as $requestManager) {
            print '<tr class="liste">';
            print '<td align="left">' . $requestManager->getLibType() . '</td>';
            print '<td align="left"><a href="' . dol_buildpath('/requestmanager/card.php', 1) . '?id=' . $requestManager->id . '" target="_blank">' . $requestManager->ref . '</a></td>';
            print '<td align="left">' . $requestManager->label . '</td>';
            print '<td align="left">' . dol_print_date($requestManager->date_creation, 'dayhour') . '</td>';
            print '<td align="center">';
            print '<input type="radio" name="associate_list[]" value="' . $requestManager->id . '" />';
            print '</td>';
            print '</tr>';
        }
        print '</table>';

        // btn associate
        print '<div align="right">';
        print '<input type="submit" class="button" name="btn_associate" value="' . $langs->trans('RequestManagerCreateFastBtnAssociateLabel') . '"/>';
        print '</div>';
        print '</div>';

        // Wrapper to show tooltips (html or onclick popup)
        if (! empty($conf->use_javascript_ajax) && empty($conf->dol_no_mouse_hover))
        {
            print "\n<!-- JS CODE TO ENABLE tipTip on all object with class classfortooltip -->\n";
            print '<script type="text/javascript">
                jQuery(document).ready(function () {
                  jQuery(".classfortooltip").tipTip({maxWidth: "'.dol_size(($conf->browser->layout == 'phone' ? 400 : 700),'width').'px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
                  jQuery(".classfortooltiponclicktext").dialog({ width: 500, autoOpen: false });
                  jQuery(".classfortooltiponclick").click(function () {
                    console.log("We click on tooltip for element with dolid="+$(this).attr(\'dolid\'));
                    if ($(this).attr(\'dolid\'))
                    {
                      obj=$("#idfortooltiponclick_"+$(this).attr(\'dolid\'));
                      obj.dialog("open");
                    }
                  });
                });
              </script>' . "\n";
        }

    }
}

?>
<?php

//
// Zone 3
//
if ($zone === 3) {
    $langs->load('contracts');

    $selectedCategories       = GETPOST('categories', 'array')?GETPOST('categories', 'array'):array();
    $selectedEquipementId     = GETPOST('equipement_id', 'int')?intval(GETPOST('equipement_id', 'int')):-1;
    $selectedSocId            = GETPOST('socid', 'int')?intval(GETPOST('socid', 'int')):-1;
    $selectedSocIdBenefactor  = GETPOST('socid_benefactor', 'int')?intval(GETPOST('socid_benefactor', 'int')):-1;

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
        print '<td>' . $langs->trans("Ref") . '</td>';
        print '<td align="right">' . $langs->trans("Status") . '</td>';
        print '</tr>';
        print '</tr>';
        if (count($contractList) > 0) {
            foreach ($contractList as $contract) {
                print '<tr class="liste">';
                print '<td align="left"><a href="' . DOL_URL_ROOT.  '/contrat/card.php?id=' . $contract->id . '" target="_blank">' . $contract->ref . '</a></td>';
                print '<td align="right">'.$contract->getLibStatut(7).'</td>';
                print '<tr>';
            }
        }
        print '</table>';
        print '</div>';

        // Equipement list of this thirdparty
        if ($conf->equipement->enabled) {
            $langs->load('equipement@equipement');

            $equipementList = $requestManager->loadAllBenefactorEquipments($selectedSocId, $selectedSocIdBenefactor);
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

        // Wrapper to show tooltips (html or onclick popup)
        if (! empty($conf->use_javascript_ajax) && empty($conf->dol_no_mouse_hover))
        {
            print "\n<!-- JS CODE TO ENABLE tipTip on all object with class classfortooltip -->\n";
            print '<script type="text/javascript">
                jQuery(document).ready(function () {
                  jQuery(".classfortooltip").tipTip({maxWidth: "'.dol_size(($conf->browser->layout == 'phone' ? 400 : 700),'width').'px", edgeOffset: 10, delay: 50, fadeIn: 50, fadeOut: 50});
                  jQuery(".classfortooltiponclicktext").dialog({ width: 500, autoOpen: false });
                  jQuery(".classfortooltiponclick").click(function () {
                    console.log("We click on tooltip for element with dolid="+$(this).attr(\'dolid\'));
                    if ($(this).attr(\'dolid\'))
                    {
                      obj=$("#idfortooltiponclick_"+$(this).attr(\'dolid\'));
                      obj.dialog("open");
                    }
                  });
                });
              </script>' . "\n";
        }

    }
}

?>
<!-- END PHP TEMPLATE -->
