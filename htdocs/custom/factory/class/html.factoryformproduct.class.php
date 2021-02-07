<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013-2017	Charlie BENKE			<charlie@patas-monkey.com>
 * Copyright (C) 2017       Open-DSI                <support@open-dsi.fr>
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
 * 	\file       htdocs/factory/class/html.factoryformproduct.class.php
 * 	\ingroup    factory
 * 	\brief      Fichier de classe FactoryFormProduct
 */
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';


/**
 * Class FactoryFormProduct
 */
class FactoryFormProduct extends FormProduct
{
    /**
	 *  Return list of warehouses
	 *
	 *  @param	int		$selected       Id of preselected warehouse ('' for no value, 'ifone'=select value if one value otherwise no value)
	 *  @param  string	$htmlname       Name of html select html
	 *  @param  string	$filterstatus   warehouse status filter, following comma separated filter options can be used
     *									'warehouseopen' = select products from open warehouses,
	 *									'warehouseclosed' = select products from closed warehouses,
	 *									'warehouseinternal' = select products from warehouses for internal correct/transfer only
	 *  @param  int		$empty			1=Can be empty, 0 if not
	 * 	@param	int		$disabled		1=Select is disabled
	 * 	@param	int		$fk_product		Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 *  @param	string	$empty_label	Empty label if needed (only if $empty=1)
	 *  @param	int		$showstock		1=Show stock count
	 *  @param	int		$forcecombo		1=Force combo iso ajax select2
	 *  @param	array	$events			Events to add to select2
	 *  @param  string  $morecss        Add more css classes to HTML select
	 *  @param	array	$exclude		Warehouses ids to exclude
	 *  @param  int     $showfullpath   1=Show full path of name (parent ref into label), 0=Show only ref of current warehouse
     *  @param  int     $onlyStock      [=FALSE] to show all or TRUE to filter only warehouses whose have stock
	 * 	@return	string					HTML select
	 */
	function selectWarehouses($selected='', $htmlname='idwarehouse', $filterstatus='', $empty=0, $disabled=0, $fk_product=0, $empty_label='', $showstock=0, $forcecombo=0, $events=array(), $morecss='minwidth200', $exclude='', $showfullpath=1, $onlyStock = FALSE)
	{
	    global $conf,$langs,$user;

		dol_syslog(get_class($this)."::selectWarehouses $selected, $htmlname, $filterstatus, $empty, $disabled, $fk_product, $empty_label, $showstock, $forcecombo, $morecss",LOG_DEBUG);

		$out='';
		if (empty($conf->global->ENTREPOT_EXTRA_STATUS)) $filterstatus = '';
        $this->cache_warehouses = array();
		$this->loadWarehouses($fk_product, '', $filterstatus, true, $exclude, $onlyStock);
		$nbofwarehouses=count($this->cache_warehouses);

		if ($conf->use_javascript_ajax && ! $forcecombo)
		{
			include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
			$comboenhancement = ajax_combobox($htmlname, $events,0,0,'off');
			$out.= $comboenhancement;
			$nodatarole=($comboenhancement?' data-role="none"':'');
		}

		$out.='<select style="width:100%" class="flat'.($morecss?' '.$morecss:'').'"'.($disabled?' disabled':'').' id="'.$htmlname.'" name="'.($htmlname.($disabled?'_disabled':'')).'"'.$nodatarole.'>';
		if ($empty) $out.='<option value="-1">'.($empty_label?$empty_label:'&nbsp;').'</option>';
		foreach($this->cache_warehouses as $id => $arraytypes)
		{
			$out.='<option value="'.$id.'"';
			if ($selected == $id || ($selected == 'ifone' && $nbofwarehouses == 1)) $out.=' selected';
			$out.='>';
			if ($showfullpath) $out.=$arraytypes['full_label'];
			else $out.=$arraytypes['label'];
			if (($fk_product || ($showstock > 0)) && ($arraytypes['stock'] != 0 || ($showstock > 0))) $out.=' ('.$langs->trans("Stock").':'.$arraytypes['stock'].')';
            if(isset($arraytypes['entrepot_fav'])&& $arraytypes['entrepot_fav'] == 1)
                $out .=' (*)';
			$out.='</option>';
		}
		$out.='</select>';
		if ($disabled) $out.='<input type="hidden" name="'.$htmlname.'" value="'.(($selected>0)?$selected:'').'">';

		return $out;
	}


    /**
	 * Load in cache array list of warehouses
	 * If fk_product is not 0, we do not use cache
	 *
	 * @param	int		$fk_product		    Add quantity of stock in label for product with id fk_product. Nothing if 0.
	 * @param	string	$batch			    Add quantity of batch stock in label for product with batch name batch, batch name precedes batch_id. Nothing if ''.
	 * @param	string	$status		      	warehouse status filter, following comma separated filter options can be used
     *										'warehouseopen' = select products from open warehouses,
	 *										'warehouseclosed' = select products from closed warehouses,
	 *										'warehouseinternal' = select products from warehouses for internal correct/transfer only
	 * @param	boolean	$sumStock		    sum total stock of a warehouse, default true
	 * @param	array	$exclude		    warehouses ids to exclude
     * @param  int     $onlyStock          [=FALSE] to show all or TRUE to filter only warehouses whose have stock
	 * @return  int  		    		    Nb of loaded lines, 0 if already loaded, <0 if KO
	 */
	function loadWarehouses($fk_product=0, $batch = '', $status='', $sumStock = true, $exclude='', $onlyStock = false)
	{
		global $conf, $langs;

		if (empty($fk_product) && count($this->cache_warehouses)) return 0;    // Cache already loaded and we do not want a list with information specific to a product

		if (is_array($exclude))	$excludeGroups = implode("','",$exclude);

		$warehouseStatus = array();

		if (preg_match('/warehouseclosed/', $status))
		{
			$warehouseStatus[] = Entrepot::STATUS_CLOSED;
		}
		if (preg_match('/warehouseopen/', $status))
		{
			$warehouseStatus[] = Entrepot::STATUS_OPEN_ALL;
		}
		if (preg_match('/warehouseinternal/', $status))
		{
			$warehouseStatus[] = Entrepot::STATUS_OPEN_INTERNAL;
		}

		$sql = "SELECT e.rowid, e.ref, e.description, e.fk_parent";

        $sqlStockField = '';
		if (!empty($fk_product))
		{
			if (!empty($batch))
			{
				$sqlStockField = "pb.qty";
			}
			else
			{
                $sqlStockField = "ps.reel";
			}
            // ADD entrepot favoris
            $sql .= ", if((SELECT COUNT(*) FROM llx_element_element ee WHERE ee.fk_source = $fk_product	AND ee.sourcetype = 'product' AND ee.fk_target = e.rowid AND ee.targettype = 'stock') > 0,	1,	0) as entrepot_fav";
		}
		else if ($sumStock)
		{
            $sqlStockField = "sum(ps.reel)";
		}
        if ($sqlStockField) $sql.= ", " . $sqlStockField . " as stock";

		$sql.= " FROM ".MAIN_DB_PREFIX."entrepot as e";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps on ps.fk_entrepot = e.rowid";
		if (!empty($fk_product))
		{
			$sql.= " AND ps.fk_product = '".$fk_product."'";
			if (!empty($batch))
            {
                $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_batch as pb on pb.fk_product_stock = ps.rowid AND pb.batch = '".$batch."'";
            }
        }
		$sql.= " WHERE e.entity IN (".getEntity('stock').")";
		if (count($warehouseStatus))
		{
			$sql.= " AND e.statut IN (".$this->db->escape(implode(',',$warehouseStatus)).")";
		}
		else
		{
			$sql.= " AND e.statut = 1";
		}

		if(!empty($exclude)) $sql.= ' AND e.rowid NOT IN('.$this->db->escape(implode(',', $exclude)).')';

		// add stock condition
		if ($onlyStock && $sqlStockField) $sql .= " AND " . $sqlStockField . " > 0";

		if ($sumStock && empty($fk_product)) $sql.= " GROUP BY e.rowid, e.ref, e.description, e.fk_parent";
		$sql.= " ORDER BY ";
		if (!empty($fk_product)) {
            $sql .= " entrepot_fav DESC,";
        }
        if (!empty($fk_product)) {
            if (!empty($batch)) {
                $sql .= " pb.qty DESC, ";
            } else {
                $sql .= " ps.reel DESC, ";
            }
        }
        $sql .= " e.ref ASC";

		dol_syslog(get_class($this).'::loadWarehouses '.$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				if ($sumStock) $obj->stock = price2num($obj->stock,5);
				$this->cache_warehouses[$obj->rowid]['id'] =$obj->rowid;
				$this->cache_warehouses[$obj->rowid]['label']=$obj->ref;
				$this->cache_warehouses[$obj->rowid]['parent_id']=$obj->fk_parent;
				$this->cache_warehouses[$obj->rowid]['description'] = $obj->description;
				$this->cache_warehouses[$obj->rowid]['stock'] = $obj->stock;
				$this->cache_warehouses[$obj->rowid]['entrepot_fav'] = $obj->entrepot_fav;
				$i++;
			}

			// Full label init
			foreach($this->cache_warehouses as $obj_rowid=>$tab) {
				$this->cache_warehouses[$obj_rowid]['full_label'] = $this->get_parent_path($tab);
			}

			return $num;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


    /**
	 * Return full path to current warehouse in $tab (recursive function)
	 *
	 * @param	array	$tab			warehouse data in $this->cache_warehouses line
	 * @param	String	$final_label	full label with all parents, separated by ' >> ' (completed on each call)
	 * @return	String					full label with all parents, separated by ' >> '
	 */
	private function get_parent_path($tab, $final_label='') {

		if(empty($final_label)) $final_label = $tab['label'];

		if(empty($tab['parent_id'])) return $final_label;
		else {
			if(!empty($this->cache_warehouses[$tab['parent_id']])) {
				$final_label = $this->cache_warehouses[$tab['parent_id']]['label'].' >> '.$final_label;
				return $this->get_parent_path($this->cache_warehouses[$tab['parent_id']], $final_label);
			}
		}

		return $final_label;
	}


    /**
     * Return list of labels (translated) of education
     *
     * @param	string	$htmlname	Name of html select field ('myid' or '.myclass')
     * @param	array	$events		Event options. Example: array(array('action'=>'getContacts', 'url'=>dol_buildpath('/core/ajax/contacts.php',1), 'htmlname'=>'contactid', 'done_action'=>array('disabled' => array('add-customer-contact'))))
     *                                  'url':          string,  Url of the Ajax script
     *                                  'action':       string,  Action name for the Ajax script
     *                                  'params':       array(), Others parameters send for Ajax script (exclude name: id, action, htmlname), if value = '{{selector}}' get value of the 'selector' input
     *                                  'htmlname':     string,  Id of the select updated with new options from Ajax script
     *                                  'done_action':  array(), List of action done when new options get successfully
     *                                      'empty_select': array(), List of html ID of select to empty
     *                                      'disabled'    : array(), List of html ID to disable if no options
     * @return  string
     */
    function add_select_events($htmlname, $events)
    {
        global $conf;

        $out = '';
        if (!empty($conf->use_javascript_ajax)) {
            $out .= '<script type="text/javascript">
            $(document).ready(function () {
                jQuery("#'.$htmlname.'").change(function () {
                    var obj = '.json_encode($events).';
                    $.each(obj, function(key,values) {
                        if (values.action.length) {
                            runJsCodeForEvent'.$htmlname.'(values);
                        }
                    });
                });

                function runJsCodeForEvent'.$htmlname.'(obj) {
                    console.log("Run runJsCodeForEvent'.$htmlname.'");
                    var id = $("#'.$htmlname.'").val();
                    var action = obj.action;
                    var url = obj.url;
                    var htmlname = obj.htmlname;
                    var datas = {
                        action: action,
                        id: id,
                        htmlname: htmlname,
                    };
                    var selector_regex = new RegExp("^\\{\\{(.*)\\}\\}$", "i");
                    $.each(obj.params, function(key, value) {
                        var match = null;
                        if ($.type(value) === "string") match = value.match(selector_regex);
                        if (match) {
                            datas[key] = $(match[1]).val();
                        } else {
                            datas[key] = value;
                        }
                    });
                    var input = $("select#" + htmlname);
                    var inputautocomplete = $("#inputautocomplete"+htmlname);
                    $.getJSON(url, datas,
                        function(response) {
                            input.html(response.value);
                            if (response.num) {
                                var selecthtml_dom = $.parseHTML(response.value);
                                inputautocomplete.val(selecthtml_dom.innerHTML);
                            } else {
                                inputautocomplete.val("");
                            }

                            var num = response.num;
                            $.each(obj.done_action, function(key, action) {
                                switch (key) {
                                    case "empty_select":
                                        $.each(action, function(id) {
                                            $("select#" + id).html("");
                                        });
                                        break;
                                    case "disabled":
                                        $.each(action, function(id) {
                                            if (num > 0) {
                                                $("#" + id).removeAttr("disabled");
                                            } else {
                                                $("#" + id).attr("disabled", "disabled");
                                            }
                                        });
                                        break;
                                }
                            });

                            input.change();	/* Trigger event change */

                            if (response.num < 0) {
                                console.error(response.error);
                            }
                        }
                    );
                }
            });
            </script>';
        }

        return $out;
    }
}
