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
