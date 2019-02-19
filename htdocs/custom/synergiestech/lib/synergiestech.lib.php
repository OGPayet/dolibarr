<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2017      Open-DSI             <support@open-dsi.fr>
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
 *	\file       htdocs/synergiestech/lib/synergiestech.lib.php
 *	\brief      Ensemble de fonctions pour le module synergiestech
 * 	\ingroup	synergiestech
 */

/**
 * Prepare array with list of tabs
 *
 * @return  array				Array of tabs to show
 */
function synergiestech_prepare_head()
{
    global $langs, $conf, $user;
    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/synergiestech/admin/setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = dol_buildpath("/synergiestech/admin/dictionaries.php", 1);
    $head[$h][1] = $langs->trans("Dictionary");
    $head[$h][2] = 'dictionaries';
    $h++;

    $head[$h][0] = dol_buildpath("/synergiestech/admin/about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    $head[$h][0] = dol_buildpath("/synergiestech/admin/changelog.php", 1);
    $head[$h][1] = $langs->trans("OpenDsiChangeLog");
    $head[$h][2] = 'changelog';
    $h++;

    complete_head_from_modules($conf,$langs,null,$head,$h,'synergiestech_admin');

    return $head;
}

/**
 * Check if has shipping equipment to serialize
 *
 * @param   DoliDB  $db             Database handler
 * @param   int     $shipping_id    Id of the shipping
 * @return  bool
 */
function synergiestech_has_shipping_equipment_to_serialize($db, $shipping_id)
{
    $sql = "SELECT IF(IFNULL(ts.nb, 0) != IFNULL(s.nb, 0), 1, 0) AS result
            FROM (
              SELECT SUM(ed.qty) as nb, cd.fk_product FROM " . MAIN_DB_PREFIX . "expeditiondet AS ed
              LEFT JOIN " . MAIN_DB_PREFIX . "commandedet AS cd ON cd.rowid = ed.fk_origin_line
              LEFT JOIN " . MAIN_DB_PREFIX . "product_extrafields AS pe ON pe.fk_object = cd.fk_product
              WHERE ed.fk_expedition = " . $shipping_id ."
              AND ed.qty > 0
              AND pe.synergiestech_to_serialize = 1
              GROUP BY cd.fk_product
            ) AS ts
            LEFT JOIN (
              SELECT count(ee.rowid) as nb, e.fk_product FROM " . MAIN_DB_PREFIX . "equipementevt AS ee
              LEFT JOIN " . MAIN_DB_PREFIX . "equipement AS e ON e.rowid = ee.fk_equipement
              WHERE ee.fk_expedition = " . $shipping_id . "
              GROUP BY e.fk_product
           ) AS s ON ts.fk_product = s.fk_product";

    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            if($obj->result == 1)
                return true;
        }
    }

    return false;
}

/**
 * Check if has shipping equipment to serialize
 *
 * @param   DoliDB  $db             Database handler
 * @param   int     $shipping_id    Id of the shipping
 * @return  bool
 */
function synergiestech_has_shipping_equipment_to_validate($db, $shipping_id)
{
    $sql = "SELECT IF(count(ee.rowid) > 0, 1, 0) as result FROM " . MAIN_DB_PREFIX . "equipementevt AS ee
            LEFT JOIN " . MAIN_DB_PREFIX . "equipement AS e ON e.rowid = ee.fk_equipement
            WHERE ee.fk_expedition = " . $shipping_id . "
            AND e.fk_entrepot IS NOT NULL";

    $resql = $db->query($sql);
    if ($resql) {
        if ($obj = $db->fetch_object($resql)) {
            return $obj->result == 1;
        }
    }

    return false;
}

/**
 * Check if has supplier order equipment to serialize
 *
 * @param   DoliDB  $db                     Database handler
 * @param   int     $supplier_order_id      Id of the supplier order
 * @return  bool
 */
function synergiestech_has_dispatching_equipment_to_serialize($db, $supplier_order_id) {
    $sql = "SELECT IF(IFNULL(ts.nb, 0) != IFNULL(s.nb, 0), 1, 0) AS result
            FROM (
              SELECT SUM(cfd.qty) as nb, cfd.rowid as fk_commande_fournisseur_dispatch FROM " . MAIN_DB_PREFIX . "commande_fournisseur_dispatch as cfd
              LEFT JOIN " . MAIN_DB_PREFIX . "product_extrafields AS pe ON pe.fk_object = cfd.fk_product
              WHERE cfd.fk_commande = " . $supplier_order_id ."
              AND cfd.qty > 0
              AND pe.synergiestech_to_serialize = 1
              GROUP BY cfd.rowid
            ) AS ts
            LEFT JOIN (
              SELECT SUM(e.quantity) as nb, e.fk_commande_fournisseur_dispatch FROM " . MAIN_DB_PREFIX . "equipement AS e
              WHERE e.fk_commande_fourn = " . $supplier_order_id . "
              GROUP BY e.fk_commande_fournisseur_dispatch
            ) AS s ON ts.fk_commande_fournisseur_dispatch = s.fk_commande_fournisseur_dispatch";

    $resql = $db->query($sql);
    if ($resql) {
        if ($obj = $db->fetch_object($resql)) {
            return $obj->result == 1;
        }
    }

    return false;
}


/**
 *	Generic function that return javascript to add to a page to transform a common input field into an autocomplete field by calling an Ajax page (ex: /societe/ajaxcompanies.php).
 *  The HTML field must be an input text with id=search_$htmlname.
 *  This use the jQuery "autocomplete" function.
 *
 *  @param	string	$selected           Preselecte value
 *	@param	string	$htmlname           HTML name of input field
 *	@param	string	$url                Url for request: /path/page.php. Must return a json array ('key'=>id, 'value'=>String shown into input field once selected, 'label'=>String shown into combo list)
 *  @param	string	$urloption			More parameters on URL request
 *  @param	int		$minLength			Minimum number of chars to trigger that Ajax search
 *  @param	int		$autoselect			Automatic selection if just one value
 *  @param	array	$ajaxoptions		Multiple options array
 *                                          Ex: array('update'=>array('field1','field2'...)) will reset field1 and field2 once select done
 *                                          Ex: array('disabled'=> )
 *                                          Ex: array('show'=> )
 *                                          Ex: array('update_textarea'=> )
 *                                          Ex: array('option_disabled'=> id to disable and warning to show if we select a disabled value (this is possible when using autocomplete ajax)
 *	@return string              		Script
 */
function synergiestech_ajax_autocompleter($selected, $htmlname, $url, $urloption='', $minLength=2, $autoselect=0, $ajaxoptions=array())
{
    if (empty($minLength)) $minLength=1;

    $dataforrenderITem='ui-autocomplete';
    $dataforitem='ui-autocomplete-item';
    // Allow two constant to use other values for backward compatibility
    if (defined('JS_QUERY_AUTOCOMPLETE_RENDERITEM')) $dataforrenderITem=constant('JS_QUERY_AUTOCOMPLETE_RENDERITEM');
    if (defined('JS_QUERY_AUTOCOMPLETE_ITEM'))       $dataforitem=constant('JS_QUERY_AUTOCOMPLETE_ITEM');

    // Input search_htmlname is original field
    // Input htmlname is a second input field used when using ajax autocomplete.
	$script = '<input type="hidden" name="'.$htmlname.'" id="'.$htmlname.'" value="'.$selected.'" />';

	$script.= '<!-- Javascript code for autocomplete of field '.$htmlname.' -->'."\n";
	$script.= '<script type="text/javascript">'."\n";
	$script.= '$(document).ready(function() {
					var autoselect = '.$autoselect.';
					var options = '.json_encode($ajaxoptions).';

					/* Remove product id before select another product use keyup instead of change to avoid loosing the product id. This is needed only for select of predefined product */
					/* TODO Check if we can remove this */
					$("input#search_'.$htmlname.'").keydown(function() {
						$("#'.$htmlname.'").val("");
					});

					/* I disable this. A call to trigger is already done later into the select action of the autocomplete code
						$("input#search_'.$htmlname.'").change(function() {
					    console.log("Call the change trigger on input '.$htmlname.' because of a change on search_'.$htmlname.' was triggered");
						$("#'.$htmlname.'").trigger("change");
					});*/

					// Check options for secondary actions when keyup
					$("input#search_'.$htmlname.'").keyup(function() {
						    if ($(this).val().length == 0)
						    {
	                            $("#search_'.$htmlname.'").val("");
	                            $("#'.$htmlname.'").val("").trigger("change");
	                            if (options.option_disabled) {
								$("#" + options.option_disabled).removeAttr("disabled");
							}
							if (options.disabled) {
								$.each(options.disabled, function(key, value) {
									$("#" + value).removeAttr("disabled");
									});
							}
							if (options.update) {
								$.each(options.update, function(key, value) {
									$("#" + key).val("").trigger("change");
									});
								}
								if (options.show) {
								$.each(options.show, function(key, value) {
									$("#" + value).hide().trigger("hide");
									});
								}
								if (options.update_textarea) {
								$.each(options.update_textarea, function(key, value) {
									if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && CKEDITOR.instances[key] != "undefined") {
										CKEDITOR.instances[key].setData("");
									} else {
										$("#" + key).html("");
										}
								});
							}
						    }
                    });
				$("input#search_'.$htmlname.'").autocomplete({
					source: function( request, response ) {
						$.get("'.$url.($urloption?'?'.$urloption:'').'", { '.$htmlname.': request.term }, function(data){
								if (data != null)
								{
									response($.map( data, function(item) {
										if (autoselect == 1 && data.length == 1) {
											$("#search_'.$htmlname.'").val(item.value);
											$("#'.$htmlname.'").val(item.key).trigger("change");
										}
										var label = item.label.toString();
										var update = {};
										if (options.update) {
											$.each(options.update, function(key, value) {
												update[key] = item[value];
											});
										}
										var textarea = {};
										if (options.update_textarea) {
											$.each(options.update_textarea, function(key, value) {
												textarea[key] = item[value];
											});
										}
										return { label: label, value: item.value, id: item.key, update: update, textarea: textarea, disabled: item.disabled, opt_disabled: item.opt_disabled }
									}));
								}
								else console.error("Error: Ajax url '.$url.($urloption?'?'.$urloption:'').' has returned an empty page. Should be an empty json array.");
							}, "json");
						},
						dataType: "json",
					minLength: '.$minLength.',
					select: function( event, ui ) {		// Function ran once new value has been selected into javascript combo
						console.log("Call change on input '.$htmlname.' because of select definition of autocomplete select call on input#search_'.$htmlname.'");
					    console.log("Selected id = "+ui.item.id+" - If this value is null, it means you select a record with key that is null so selection is not effective");
						$("#'.$htmlname.'").val(ui.item.id).trigger("change");	// Select new value
						// Disable an element
						if (options.option_disabled) {
							console.log("Make action option_disabled on #"+options.option_disabled+" with disabled="+ui.item.disabled)
							if (ui.item.disabled) {
									$("#" + options.option_disabled).prop("disabled", true);
								if (options.error) {
									$.jnotify(options.error, "error", true);		// Output with jnotify the error message
								}
								if (options.warning) {
									$.jnotify(options.warning, "warning", false);		// Output with jnotify the warning message
								}
								} else {
								$("#" + options.option_disabled).removeAttr("disabled");
							}
						}
						if (options.disabled) {
							console.log("Make action disabled on each "+options.option_disabled)
							$.each(options.disabled, function(key, value) {
									$("#" + value).prop("disabled", true);
							});
						}
						if (options.show) {
							console.log("Make action show on each "+options.show)
							$.each(options.show, function(key, value) {
								$("#" + value).show().trigger("show");
							});
						}
						// Update an input
						if (ui.item.update) {
							console.log("Make action update on each ui.item.update")
							// loop on each "update" fields
							$.each(ui.item.update, function(key, value) {
								$("#" + key).val(value).trigger("change");
							});
						}
						if (ui.item.textarea) {
							console.log("Make action textarea on each ui.item.textarea")
							$.each(ui.item.textarea, function(key, value) {
								if (typeof CKEDITOR == "object" && typeof CKEDITOR.instances != "undefined" && CKEDITOR.instances[key] != "undefined") {
									CKEDITOR.instances[key].setData(value);
									CKEDITOR.instances[key].focus();
								} else {
									$("#" + key).html(value);
									$("#" + key).focus();
									}
							});
						}
						console.log("ajax_autocompleter new value selected, we trigger change on original component so field #search_'.$htmlname.'");

						$("#search_'.$htmlname.'").trigger("change");	// We have changed value of the combo select, we must be sure to trigger all js hook binded on this event. This is required to trigger other javascript change method binded on original field by other code.
					}
					,delay: 500
					}).data("'.$dataforrenderITem.'")._renderItem = function( ul, item ) {
					console.log(item.opt_disabled);
						return $("<li" + (item.opt_disabled ? \' class="ui-state-disabled"\' : "" ) + ">")
						.data( "'.$dataforitem.'", item ) // jQuery UI > 1.10.0
						.append( \'<a><span class="tag">\' + item.label + "</span></a>" )
						.appendTo(ul);
					};

				});';
	$script.= '</script>';

	return $script;
}


/**
 *	Generate PDF Ticket Report
 *
 * @param   DoliDB          $db                 Database handler
 * @param   int				$contrat_id         ID of contract
 * @param   int				$date_begin         Begin period date
 * @param   int				$date_end           End period date
 * @return  int                                 <0 if not ok, >0 if ok
 */
function synergiestech_ticket_generate_report($db, $contrat_id, $date_begin, $date_end)
{
    global $conf, $langs;

    $langs->load('synergiestech@synergiestech');

    require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
    $contrat = new Contrat($db);
    $res = $contrat->fetch($contrat_id);
    if ($res < 0) {
        setEventMessages($langs->trans('SynergiesTechTicketGenerateReportErrorGetContract', $contrat->errorsToString()), null, 'errors');

        return -1;
    }

    /**
     * Liste des services du contrat
     */
    $contrat->fetch_thirdparty();
    $contrat->fetch_lines();
    $title1 = $langs->trans('SynergiesTechTicketGenerateReportServiceTitle', $contrat->ref, $contrat->thirdparty->getFullName($langs));
    $content1 = '<bookmark title="'.$title1.'" level="0" ></bookmark>
    <h1>'.$title1.'</h1>
    <table style="width: 100%; margin-top:120px">
        <thead>
            <tr>
                <th style="height:50px; width:15% ">'.$langs->trans('SynergiesTechTicketGenerateReportServiceRef').'</th>
                <th style="height:50px; width:65% ">'.$langs->trans('SynergiesTechTicketGenerateReportServiceDescription').'</th>
                <th style="height:50px; width:10% ">'.$langs->trans('SynergiesTechTicketGenerateReportDateBegin').'</th>
                <th style="height:50px; width:10% ">'.$langs->trans('SynergiesTechTicketGenerateReportDateEnd').'</th>
            </tr>
        </thead>
        <tbody>';
    if (count($contrat->lines) > 0){
        foreach ($contrat->lines as $line) {
            $content1 .= '<tr>
                <td style="height:50px; width:15%;" class="border">' . $line->ref . '</td>
                <td style="height:50px; width:65%;" class="border">' . strip_tags($line->description, '<br />') . '</td>
                <td style="height:50px; width:10%;" class="border center">' . (!empty($line->date_ouverture_prevue)?dol_print_date($line->date_ouverture_prevue, 'day'):'') . '</td>
                <td style="height:50px; width:10%;" class="border center">' . (!empty($line->date_fin_validite)?dol_print_date($line->date_fin_validite, 'day'):'') . '</td>
            </tr>';
        }
    }else{
        $content1 .= '<tr>
                <td style="height:50px; width:100%;" colspan="4" class="border center">'.$langs->trans('SynergiesTechAdvancedTicketGenerateNoService').'</td>
            </tr>';
    }
    $content1 .= '</tbody></table>';
    /**
     * Fin de la boucle des services du contrat
     */

    dol_include_once('/advancedticket/class/advancedticket.class.php');
    $advancedticket_static = new advancedticket($db);

    /**
     * Liste des tickets en cours
     */
    $sql = "SELECT adt.rowid, adt.ref, adt.subject, adt.message, adt.duree, adt.datec, adt.fk_statut
    FROM ".MAIN_DB_PREFIX."advancedticket AS adt
    LEFT JOIN ".MAIN_DB_PREFIX."advancedticket_extrafields AS adte ON adt.rowid = adte.fk_object
    LEFT JOIN ".MAIN_DB_PREFIX."element_element AS ee ON ee.sourcetype = 'contrat' AND ee.targettype = 'advancedticket' AND ee.fk_target = adt.rowid
    WHERE adt.fk_statut < 8
    AND ee.fk_source = $contrat_id
    GROUP BY adt.rowid
    ORDER BY adt.ref";
    $resql = $db->query($sql);
    $title2 = $langs->trans('SynergiesTechTicketGenerateReportTicketInProgressTitle');
    $content2 = '<bookmark title="'.$title2.'" level="0" ></bookmark>
    <table style="width: 100%;">
        <thead>
            <tr>
                <th class="mini" style="height:50px;width:9%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketRef').'</th>
                <th class="mini" style="height:50px;width:15%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketTitle').'</th>
                <th class="mini" style="height:50px;width:53%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketDescription').'</th>
                <th class="mini" style="height:50px;width:8%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketDuration').'</th>
                <th class="mini" style="height:50px;width:8%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketDateCreated').'</th>
                <th class="mini" style="height:50px;width:7%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketStatus').'</th>
            </tr>
        </thead>
        <tbody>';
    if ($resql && $db->num_rows($resql) > 0) {
        while ($obj = $db->fetch_object($resql)) {
            if ($advancedticket_static->fetch($obj->rowid) > 0) {
                $content2 .= '<tr>
                    <td style="height:50px;width:9%;" class="border mini">' . $obj->ref . '</td>
                    <td style="height:50px;width:15%;" class="border mini">' . strip_tags($obj->subject) . '</td>
                    <td style="height:50px;width:53%;" class="border mini">' . strip_tags($obj->message, '<br />') . '</td>
                    <td style="height:50px;width:8%;" class="border mini center">' . $obj->duree . '</td>
                    <td style="height:50px;width:8%;" class="border mini center">' . (!empty($obj->datec)?dol_print_date($obj->datec, 'day'):'') . '</td>
                    <td style="height:50px;width:7%;" class="border mini center">' . $advancedticket_static->LibStatut($obj->fk_statut) . '</td>
                </tr>';
                // Get public messages
                $sql2 = "SELECT ts.rowid, ts.ref, ts.subject, ts.message, ts.duree, ts.datec, ts.fk_statut
                FROM ".MAIN_DB_PREFIX."ticketsup AS ts
                LEFT JOIN ".MAIN_DB_PREFIX."ticketsup_extrafields AS tse ON ts.rowid = tse.fk_object
                LEFT JOIN ".MAIN_DB_PREFIX."element_element AS ee ON ee.sourcetype = 'contrat' AND ee.targettype = 'ticketsup' AND ee.fk_target = ts.rowid
                LEFT JOIN ".MAIN_DB_PREFIX."element_element AS eefi ON eefi.sourcetype = 'ticketsup' AND eefi.targettype = 'fichinter' AND eefi.fk_source = ts.rowid
                LEFT JOIN ".MAIN_DB_PREFIX."fichinter AS fi ON eefi.fk_target = fi.rowid
                LEFT JOIN ".MAIN_DB_PREFIX."fichinterdet AS fid ON fi.rowid = fid.fk_fichinter
                WHERE ts.fk_statut<8
                AND ee.fk_source = $contrat_id
                GROUP BY ts.rowid
                ORDER BY ts.ref";
                $resql2 = $db->query($sql2);
                $content2 .= '<table style="width: 100%;">
                    <thead>
                        <tr>
                            <th class="mini" style="height:50px;width:9%;">&nbsp;</th>
                            <th class="mini" style="height:50px;width:15%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketMessageUser').'</th>
                            <th class="mini" style="height:50px;width:60%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketMessageDate').'</th>
                            <th class="mini" style="height:50px;width:8%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketMessageText').'</th>
                            <th class="mini" style="height:50px;width:8%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketMessageDuration').'</th>
                        </tr>
                    </thead>
                    <tbody>';
                if ($resql2 && $db->num_rows($resql2) > 0) {
                    while ($obj2 = $db->fetch_object($resql2)) {
                        $content2 .= '<tr>
                            <td style="height:50px;width:9%;" class="border mini">&nbsp;</td>
                            <td style="height:50px;width:15%;" class="border mini">' . strip_tags($obj2->user) . '</td>
                            <td style="height:50px;width:60%;" class="border mini">' . (!empty($obj2->datec)?dol_print_date($obj2->datec, 'day'):'') . '</td>
                            <td style="height:50px;width:8%;" class="border mini center">' . strip_tags($obj2->message, '<br />') . '</td>
                            <td style="height:50px;width:8%;" class="border mini center">' . $obj2->duree . '</td>
                        </tr>';
                    }
                } else {
                    $content2 .= '<tr>
                        <td style="height:50px;width:100%;" colspan="7" class="border center">'.$langs->trans('SynergiesTechAdvancedTicketGenerateNoTicketMessage').'</td>
                    </tr>';
                }
                $content2 .= '</tbody></table>';
            } else {
                $content2 .= '<tr>
                        <td style="height:50px;width:100%;" colspan="4" class="border center">'.$langs->trans('SynergiesTechTicketGenerateReportErrorGetTicket', $obj->ref).'</td>
                    </tr>';
            }
        }
    } else {
        $content2 .= '<tr>
            <td style="height:50px;width:100%;" colspan="7" class="border center">'.$langs->trans('SynergiesTechAdvancedTicketGenerateNoTicketInProgress').'</td>
        </tr>';
    }
    $content2 .= '</tbody></table>';
    /**
     * Fin de liste des tickets en cours
     */

    /**
     * Tickets cloturés pour la période $periode_begin au $periode_end
     */
    $sql = "SELECT ts.ref, ts.subject, ts.message, ts.datec, ts.fk_statut, SUM(CEIL(fid.duree/(60*$ticket_time))) AS ts_consomme, tse.tck_decompt
    FROM ".MAIN_DB_PREFIX."ticketsup AS ts
    LEFT JOIN ".MAIN_DB_PREFIX."ticketsup_extrafields AS tse ON ts.rowid = tse.fk_object
    LEFT JOIN ".MAIN_DB_PREFIX."element_element AS ee ON ee.sourcetype = 'contrat' AND ee.targettype = 'ticketsup' AND ee.fk_target = ts.rowid
    LEFT JOIN ".MAIN_DB_PREFIX."element_element AS eefi ON eefi.sourcetype = 'ticketsup' AND eefi.targettype = 'fichinter' AND eefi.fk_source = ts.rowid
    LEFT JOIN ".MAIN_DB_PREFIX."fichinter AS fi ON eefi.fk_target = fi.rowid
    LEFT JOIN ".MAIN_DB_PREFIX."fichinterdet AS fid ON fi.rowid = fid.fk_fichinter
    WHERE ts.fk_statut=8
    AND ee.fk_source = $contrat_id
    AND ts.date_close >= $periode_begin AND ts.date_close <= $periode_end
    GROUP BY ts.rowid
    ORDER BY ts.ref";
    $resql = $db->query($sql);
    $title3 = $langs->trans('SynergiesTechTicketGenerateReportTicketClosedTitle', dol_print_date($date_begin, 'day'), dol_print_date($date_end, 'day'));
    $content3 = '<bookmark title="'.$title3.'" level="0" ></bookmark>
    <table style="width: 100%;"><thead>
            <tr>
                <th class="mini" style="height:50px;width:9%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketRef').'</th>
                <th class="mini" style="height:50px;width:15%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketTitle').'</th>
                <th class="mini" style="height:50px;width:45%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketDescription').'</th>
                <th class="mini" style="height:50px;width:8%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketDuration').'</th>
                <th class="mini" style="height:50px;width:8%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketDateCreated').'</th>
                <th class="mini" style="height:50px;width:7%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketStatus').'</th>
                <th class="mini" style="height:50px;width:7%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketDateClosed').'</th>
                <th class="mini" style="height:50px;width:7%;">'.$langs->trans('SynergiesTechTicketGenerateReportTicketUserClosed').'</th>
            </tr>
            </thead><tbody>';
    if ($resql && $db->num_rows($resql) > 0) {
        while ($obj = $db->fetch_object($resql)) {
            $content3 .= '<tr>
                <td style="height:50px;width:9%;" class="border mini">' . $obj->ref . '</td>
                <td style="height:50px;width:15%;" class="border mini">' . strip_tags($obj->subject) . '</td>
                <td style="height:50px;width:45%;" class="border mini">' . strip_tags($obj->message, '<br />') . '</td>
                <td style="height:50px;width:8%;" class="border mini center">' . (!empty($obj->datec)?dol_print_date($obj->datec, 'day'):'') . '</td>
                <td style="height:50px;width:7%;" class="border mini center">' . $ticketsup->LibStatut($obj->fk_statut) . '</td>
                <td style="height:50px;width:8%;" class="border mini center">' . round($obj->ts_consomme) . '</td>
                <td style="height:50px;width:8%;" class="border mini center">' . ($obj->tck_decompt == 1 ? 'Oui' : 'Non') . '</td>
            </tr>';
        }
    } else {
        $content3 .= '<tr>
                <td style="height:50px;" colspan="7" class="border center">'.$langs->trans('SynergiesTechAdvancedTicketGenerateNoTicketClosed').'</td>
            </tr>';
    }
    $content3 .= '</tbody></table>';
    /**
     * Fin des tickets cloturés pour la période $periode_begin au $periode_end
     */

    /**
     * Création du pdf avec les contenus générés
     */
    $dir = $conf->contrat->dir_output . "/" . dol_sanitizeFileName($contrat->ref);
    if (dol_mkdir($dir) < 0) {
        setEventMessages($langs->trans('OpenDsiGenerateTSResultsErrorCreatePath', $dir), null, 'errors');

        return -1;
    }

    dol_include_once('/opendsi/class/pdf.class.php');
    $pdf = new create_pdf();
    $pdf->add_content($content1, $title1);
    $pdf->add_content($content2, $title2);
    $pdf->add_content($content3, $title3);
    $pdf->output($dir . "/tickets_reports_" . dol_print_date($date_begin, 'dayrfc') . "_".dol_print_date($date_end, 'dayrfc'));

    return 1;
}

/**
 * Print confirm form
 *
 * @param   string  $formconfirm    Confirm form
 *
 * @return  void
 */
function synergiestech_print_confirmform($formconfirm)
{
    $html = [];
    $scripts = [];
    $cursor_pos = 0;
    while ($begin_script_pos = strpos($formconfirm, '<script', $cursor_pos)) {
        $html[] = substr($formconfirm, $cursor_pos, $begin_script_pos - $cursor_pos);

        $end_script_pos = strpos($formconfirm, '</script>', $begin_script_pos);
        $cursor_pos = $end_script_pos + 9;
        $scripts[] = substr($formconfirm, $begin_script_pos, $cursor_pos - $begin_script_pos);
    }
    $html[] = substr($formconfirm, $cursor_pos);

    $confirm = str_replace(["'", "\n"], ["\\'", ""], implode('', $html));

    print '<script type="text/javascript" language="javascript">'."\n";
    print '$(document).ready(function () {'."\n";
    print '$("#id-right").append(\''.$confirm.'\');'."\n";
    print '});';
    print '</script>'."\n";
    print implode('', $scripts);
}

/**
 * Return list of the product who can be returned for this thirdparty
 *
 * @param   DoliDB      $db         Database handler
 * @param   int         $socid      Thirdparty ID
 *
 * @return  array                   array('Product id' => array('product'=>'Product label', 'product_id'=>'Product id', 'qty_sent'=>'Product quantity', 'equipments' => array('Equipment id' => 'Equipment label', ...)), ...)
 */
function synergiestech_get_return_products_list($db, $socid)
{
    global $langs;

    $lines = array();

    require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

    $lineText = $langs->transnoentitiesnoconv('Line');
    $qtyText = $langs->transnoentitiesnoconv('Qty');

    // Get nb sent for each product
    $sql = "SELECT cd.rowid, cd.rang, c.rowid as order_id, c.ref as order_ref, p.rowid as product_id, p.ref, p.label, SUM(exp.qty) as qty_sent";
    $sql .= " FROM " . MAIN_DB_PREFIX . "commande as c, " . MAIN_DB_PREFIX . "product as p, ";
    $sql .= MAIN_DB_PREFIX . "commandedet as cd, " . MAIN_DB_PREFIX . "expeditiondet as exp";
    $sql .= " WHERE c.rowid = cd.fk_commande";
    $sql .= " AND p.rowid = cd.fk_product";
    $sql .= " AND cd.rowid = exp.fk_origin_line";
    $sql .= " AND c.fk_soc = " . $socid;
    $sql .= " AND c.fk_statut > " . Commande::STATUS_DRAFT;
    $sql .= " GROUP BY cd.rowid";
    $sql .= " ORDER BY c.rowid, cd.rang, cd.rowid";
    $resql = $db->query($sql);
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $lines[$obj->product_id]['product'] = $obj->ref . ' - ' . $obj->label;
            $lines[$obj->product_id]['orders'][$obj->rowid] = array(
                'order_id' => $obj->order_id,
                'label' => $obj->order_ref . ' - ' . $lineText . ': ' . $obj->rang . ' (' . $qtyText . ': ' . $obj->qty_sent . ')',
                'qty_sent' => $obj->qty_sent,
                'equipments' => array(),
            );
        }
    }

    // Get list of available serial numbers for each product
    if (!empty($lines)) {
        $sql = "SELECT DISTINCT cd.rowid, cd.fk_product as product_id, c.rowid as order_id, e.rowid as equipment_id, e.ref, e.fk_product";
        $sql .= " FROM " . MAIN_DB_PREFIX . "commande as c";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commandedet as cd ON cd.fk_commande = c.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "expeditiondet as ed ON ed.fk_origin_line = cd.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "equipementevt AS ee ON ee.fk_expedition = ed.fk_expedition";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "equipement AS e ON e.rowid = ee.fk_equipement";
        $sql .= " WHERE c.fk_soc = " . $socid;
        $sql .= " AND c.fk_statut > " . Commande::STATUS_DRAFT;
        $sql .= " AND e.fk_soc_client = c.fk_soc";
        $sql .= " AND e.fk_entrepot IS NULL";
        $resql = $db->query($sql);
        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                if (isset($lines[$obj->product_id]['orders'][$obj->rowid])) {
                    $lines[$obj->product_id]['orders'][$obj->rowid]['equipments'][$obj->equipment_id] = $obj->ref;
                }
            }
        }
    }

    return $lines;
}

/**
 *  Get available stocks with details in tooltip for the product
 *
 * @param   Translate	$langs              Translate handler
 * @param   Conf		$conf               Conf handler
 * @param   DoliDb		$db                 Database handler
 * @param   Form		$form               Form handler
 * @param   int         $product_id         Product ID
 * @return  string                          Return available stocks with details in tooltip for the product
 */
function synergiestech_get_principal_stocks_tooltip_for_product($langs, $conf, $db, $form, $product_id) {
    global $cache_synergiestech_principal_stocks;

    $out = '';

    if ($product_id > 0) {
        if (!isset($form)) {
            require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
            $form = new Form($db);
        }

        $langs->load('synergiestech@synergiestech');
        $principal_warehouse_id = $conf->global->SYNERGIESTECH_PRINCIPAL_WAREHOUSE;
        $sumStock = 0;

        if ($principal_warehouse_id > 0) {
            $sql = "SELECT e.rowid, e.label, e.fk_parent, IFNULL(ps.reel, 0) as stock";
            $sql .= " FROM " . MAIN_DB_PREFIX . "entrepot as e";
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_stock as ps on ps.fk_entrepot = e.rowid AND ps.fk_product = '" . $product_id . "'";
            $sql .= " WHERE e.entity IN (" . getEntity('stock') . ")";
            $sql .= " AND e.statut IN (" . Entrepot::STATUS_OPEN_ALL . "," . Entrepot::STATUS_OPEN_INTERNAL . ")";
            $sql .= " AND e.statut IN (" . Entrepot::STATUS_OPEN_ALL . "," . Entrepot::STATUS_OPEN_INTERNAL . ")";

            $resql = $db->query($sql);
            if (!$resql) {
                dol_print_error($db);
                return -1;
            }

            while ($obj = $db->fetch_object($resql)) {
                $cache_synergiestech_principal_stocks[$product_id][$obj->rowid]['id'] = $obj->rowid;
                $cache_synergiestech_principal_stocks[$product_id][$obj->rowid]['label'] = $obj->label;
                $cache_synergiestech_principal_stocks[$product_id][$obj->rowid]['parent_id'] = $obj->fk_parent;
                $cache_synergiestech_principal_stocks[$product_id][$obj->rowid]['stock'] = $obj->stock;
            }

            // Full path and label init
            foreach ($cache_synergiestech_principal_stocks[$product_id] as $obj_rowid => $tab) {
                $final_label = synergiestech_get_warehouse_parent_path($product_id, $tab);
                $cache_synergiestech_principal_stocks[$product_id][$obj_rowid]['full_path'] = $final_label['full_path'];
                $cache_synergiestech_principal_stocks[$product_id][$obj_rowid]['full_label'] = $final_label['full_label'];
            }

            // Sort
            uasort($cache_synergiestech_principal_stocks[$product_id], function ($a, $b) {
                // Compare stocks
                if ($a['stock'] > $b['stock'])
                    return -1;
                else if ($a['stock'] < $b['stock'])
                    return 1;

                // Compare full label
                return strcmp($a['full_label'], $b['full_label']);
            });

            $infos_list = array();
            $count = 0;
            $nb_warehouse_showed = !empty($conf->global->SYNERGIESTECH_PRINCIPAL_WAREHOUSE_NB_SHOWED) ? $conf->global->SYNERGIESTECH_PRINCIPAL_WAREHOUSE_NB_SHOWED : 10;
            foreach ($cache_synergiestech_principal_stocks[$product_id] as $warehouse_id => $warehouse) {
                if ($warehouse['stock'] > 0 &&
                    (preg_match("/^{$principal_warehouse_id}$/", $warehouse['full_path']) ||
                    preg_match("/^{$principal_warehouse_id}_/", $warehouse['full_path']) ||
                    preg_match("/_{$principal_warehouse_id}$/", $warehouse['full_path']) ||
                    preg_match("/_{$principal_warehouse_id}_/", $warehouse['full_path']))
                ) {
                    $infos_list[] = $langs->trans('SynergiesTechPrincipalWarehouseStocks', $warehouse['stock'], $warehouse['full_label']);
                    $sumStock += $warehouse['stock'];
                    $count++;
                }

                if ($nb_warehouse_showed <= $count) break;
            }

            if (count($infos_list)) {
                $infos = implode('<br>', $infos_list);
            } else {
                $infos = $langs->trans('SynergiesTechNoStockIntoPrincipalWarehouse');
            }
        } else {
            $infos = $langs->trans('SynergiesTechErrorPrincipalWarehouseNotDefined');
        }

        $out = '&nbsp;' . $form->textwithpicto('', $infos, 1, $sumStock > 0 ? 'status_green.png@synergiestech' : 'status_red.png@synergiestech');
    }

    return $out;
}

/**
 * Return full path and full label to current warehouse in $tab (recursive function)
 *
 * @param   int     $product_id     Product ID
 * @param	array	$tab			Warehouse data in $cache_synergiestech_principal_stocks line
 * @param	array	$final_label	Full path with all parents, separated by '_' and full label with all parents, separated by ' >> ' (completed on each call)
 * @return	array					Full path with all parents, separated by '_' and full label with all parents, separated by ' >> '
 */
function synergiestech_get_warehouse_parent_path($product_id, $tab, $final_label=null) {
    global $cache_synergiestech_principal_stocks;

	if(empty($final_label)) $final_label = array('full_path'=>$tab['id'], 'full_label'=>$tab['label']);

	if(empty($tab['parent_id'])) return $final_label;
	else {
		if(!empty($cache_synergiestech_principal_stocks[$product_id][$tab['parent_id']])) {
            $final_label['full_path'] = $cache_synergiestech_principal_stocks[$product_id][$tab['parent_id']]['id'].'_'.$final_label['full_path'];
            $final_label['full_label'] = $cache_synergiestech_principal_stocks[$product_id][$tab['parent_id']]['label'].' >> '.$final_label['full_label'];
			return synergiestech_get_warehouse_parent_path($product_id, $cache_synergiestech_principal_stocks[$product_id][$tab['parent_id']], $final_label);
		}
	}

	return $final_label;
}

/**
 *  Get all contracts of a company (and a optional matched benefactor)
 *
 * @param   int             $socId              Id of company
 * @param   int             $socBenefactorId    Id of matched company benefactor
 * @param   string          $msg_error          Output error message
 *
 * @return  int|Contrat[]                       <0 if KO, List of contract if OK
 */
function synergiestech_fetch_contract($socId, $socBenefactorId=0, &$msg_error=null)
{
    global $db, $conf;

    $result = array();

    if (!empty($conf->contrat->enabled) && $socId > 0) {
        $sql = "SELECT DISTINCT c.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "contrat as c";
        if (!empty($conf->companyrelationships->enabled) && $socBenefactorId > 0) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "contrat_extrafields as cf ON c.rowid = cf.fk_object";
        }
        $sql .= " WHERE c.entity IN (" . getEntity('contrat') . ")";
        $sql .= " AND c.fk_soc = " . $socId;
        if (!empty($conf->companyrelationships->enabled) && $socBenefactorId > 0) {
            $sql .= " AND cf.companyrelationships_fk_soc_benefactor = " . $socBenefactorId;
        }

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql) {
            if ($db->num_rows($resql) > 0) {
                require_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
                while ($obj = $db->fetch_object($resql)) {
                    $contrat = new Contrat($db);
                    $contrat->fetch($obj->rowid);
                    $result[$obj->rowid] = $contrat;
                }
            }
        } else {
            $msg_error = $db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $msg_error, LOG_DEBUG);
        }
    }

    return $result;
}

/**
 *  Get all request of a company benefactor
 *
 * @param   int     $socBenefactorId    Id of company benefactor
 * @param   array   $statusTypes        Filter by a list of status type
 * @param   array   $categories         Filter by a list of categories
 * @param   array   $equipments         Filter by a list of equipments
 * @param   string  $msg_error          Output error message
 *
 * @return  int|array                   <0 if KO, List of request if OK
 */
function synergiestech_fetch_request_of_benefactor($socBenefactorId, $statusTypes=array(), $categories=array(), $equipments=array(), &$msg_error) {
    global $db;

    $result = array();

    if ($socBenefactorId > 0) {
        $sql = "SELECT DISTINCT rm.rowid";
        $sql .= " FROM " . MAIN_DB_PREFIX . "requestmanager as rm";
        if (!empty($statusTypes)) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_requestmanager_status as crms ON crms.rowid = rm.fk_status";
        }
        if (!empty($categories)) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "categorie_requestmanager as crm ON crm.fk_requestmanager = rm.rowid";
        }
        if (!empty($equipments)) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "element_element as ee ON ee.fk_target = rm.rowid AND ee.targettype = 'requestmanager' AND ee.sourcetype = 'equipement'";
        }
        $sql .= " WHERE rm.entity IN (" . getEntity('requestmanager') . ")";
        $sql .= " AND rm.fk_soc_benefactor = " . $socBenefactorId;
        if (!empty($statusTypes)) {
            $sql .= " AND crms.type IN (" . implode(',', $statusTypes) . ")";
        }
        if (!empty($categories)) {
            $sql .= " AND crm.fk_categorie IN (" . implode(',', $categories) . ")";
        }
        if (!empty($equipments)) {
            $sql .= " AND ee.fk_source IN (" . implode(',', $equipments) . ")";
        }

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql) {
            if ($db->num_rows($resql) > 0) {
                dol_include_once('/requestmanager/class/requestmanager.class.php');
                while ($obj = $db->fetch_object($resql)) {
                    $request = new RequestManager($db);
                    $request->fetch($obj->rowid);
                    $result[] = $request;
                }
            }
        } else {
            $msg_error = $db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $msg_error, LOG_DEBUG);
        }
    }

    return $result;
}

/**
 *  Get all event of a company benefactor
 *
 * @param   int     $socBenefactorId    Id of company benefactor
 * @param   int     $limit              Limit to load, 0 to load nothing, -1 to load all
 * @param   string  $join               Join SQL
 * @param   string  $filter             Filter SQL
 * @param   string  $msg_error          Output error message
 *
 * @return  int|array                   <0 if KO, List of request if OK
 */
function synergiestech_fetch_event_of_benefactor($socBenefactorId, $limit=5, $join='', $filter='', &$msg_error) {
    global $db;

    $result = array();
    if ($socBenefactorId > 0) {
        $sql = "SELECT DISTINCT ac.id";
        $sql .= " FROM " . MAIN_DB_PREFIX . "actioncomm ac";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "requestmanager as rm ON ac.elementtype = 'requestmanager' AND rm.rowid = ac.fk_element";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "propal_extrafields as pf ON ac.elementtype = 'propal' AND pf.fk_object = ac.fk_element";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "commande_extrafields as cmf ON (ac.elementtype = 'order' OR ac.elementtype = 'commande') AND cmf.fk_object = ac.fk_element";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "facture_extrafields as ff ON (ac.elementtype = 'invoice' OR ac.elementtype = 'facture') AND ff.fk_object = ac.fk_element";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "expedition_extrafields as ef ON ac.elementtype = 'shipping' AND ef.fk_object = ac.fk_element"; // Todo a completer si il y a d'autres correspondances a l'avenir
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "fichinter_extrafields as fif ON ac.elementtype = 'fichinter' AND fif.fk_object = ac.fk_element";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "contrat_extrafields as cf ON (ac.elementtype = 'contract' OR ac.elementtype = 'contrat') AND cf.fk_object = ac.fk_element";
        if (!empty($join)) $sql .= $join;
        $sql .= " WHERE ac.entity IN (" . getEntity('agenda') . ")";
        $sql .= " AND (ac.fk_soc= " . $socBenefactorId .
            " OR rm.fk_soc_benefactor = " . $socBenefactorId .
            " OR pf.companyrelationships_fk_soc_benefactor = " . $socBenefactorId .
            " OR cmf.companyrelationships_fk_soc_benefactor = " . $socBenefactorId .
            " OR ff.companyrelationships_fk_soc_benefactor = " . $socBenefactorId .
            " OR ef.companyrelationships_fk_soc_benefactor = " . $socBenefactorId .
            " OR fif.companyrelationships_fk_soc_benefactor = " . $socBenefactorId .
            " OR cf.companyrelationships_fk_soc_benefactor = " . $socBenefactorId . ")";
        if (!empty($filter)) $sql .= $filter;
        $sql .= " ORDER BY ac.datep DESC";
        if ($limit >= 0) {
            $sql .= " LIMIT " . $limit;
        }

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql) {
            if ($db->num_rows($resql) > 0) {
                require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
                while ($obj = $db->fetch_object($resql)) {
                    $actioncomm = new ActionComm($db);
                    $actioncomm->fetch($obj->id);
                    $result[] = $actioncomm;
                }
            }
        } else {
            $msg_error = $db->lasterror();
            dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $msg_error, LOG_DEBUG);
        }
    }

    return $result;
}

/**
 *  Reopen intervention
 *
 * @param   DoliDB  $db             Database handler
 * @param   int     $object         Intervention object
 * @param   User    $user           Object user that close
 * @param   string  $msg_error      Output error message
 * @param   int     $notrigger      1=Does not execute triggers, 0= execute triggers
 *
 * @return  int                     <0 if KO, >0 if OK
 */
function synergiestech_reopen_intervention($db, $object, $user, &$msg_error, $notrigger=0)
{
    global $conf;
    $save_status = $object->statut;

    $object->statut = 1;
    $error = 0;

    $sql = "UPDATE " . MAIN_DB_PREFIX . "fichinter";
    $sql .= " SET fk_statut = " . $object->statut;
    $sql .= " WHERE rowid = " . $object->id;
    $sql .= " AND entity = " . $conf->entity;

    $db->begin();

    dol_syslog(__METHOD__, LOG_DEBUG);
    $resql = $db->query($sql);
    if (!$resql) {
        $error++;
        $msg_error = $db->lasterror();
        dol_syslog(__METHOD__ . " SQL: " . $sql . "; Error: " . $msg_error, LOG_DEBUG);
    }

    if (!$error) {
        if (!$notrigger) {
            // Call trigger
            $result = $object->call_trigger('FICHINTER_REOPEN', $user);
            if ($result < 0) {
                $error++;
                $msg_error = $object->errorsToString();
            }
            // End call triggers
        }
    }

    // Commit or rollback
    if ($error) {
        $object->statut = $save_status;
        $db->rollback();
        return -1 * $error;
    } else {
        $db->commit();
        return 1;
    }
}
