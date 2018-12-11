<?php
/* Copyright (C) 2015 	   Jean Heimburger      <jean@tiaris.info>
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
*
*/
dol_include_once("/shippinglabels/lib/shippinglabels.lib.php");
class ActionsShippinglabels
{
	var $db;
	public $error;
	public $results = array();
	public $resprints;
	public $errors = array();
	public $shipping_method;

	function __construct($db) {
		$this->db = $db;
		$this->error = 0;
		$this->errors = array ();
	}

	function getService($shipping_method)
	{
		global $db, $user;
		dol_syslog(__METHOD__.":: DEPREC use lib ", LOG_ERR);
		switch($shipping_method->code)
		{
			case "GLS":
				dol_include_once("/shippinglabels/class/gls.class.php");
				$service=new L_gls($db, $user);
				break;
			case "GLSPR":
				dol_include_once("/shippinglabels/class/gls.class.php");
				$service=new L_gls($db, $user);
				$service->contrat = 'PR';
				break;
			case "DPD":
				dol_include_once("/shippinglabels/class/dpd.class.php");
				$service=new L_dpd($db, $user);
				break;
			case "UPS":
				dol_include_once("/shippinglabels/class/ups.class.php");
				$service=new L_ups($db, $user);
				break;
			case "COLSUI":
				dol_include_once("/shippinglabels/class/colissimo.class.php");
				$service=new L_soco($db, $user);
				break;
			case "COLDOM":
				dol_include_once("/shippinglabels/class/colissimo.class.php");
				$service=new L_soco($db, $user);
				$service->contrat = 'OM';
				break;
			case "COLINT":
				dol_include_once("/shippinglabels/class/colissimo.class.php");
				$service=new L_soco($db, $user);
				$service->contrat = 'INT';
				break;
			case "CHRONO":
				dol_include_once("/shippinglabels/class/chronopost.class.php");
				$service=new L_chronopost($db, $user);
				break;
			case "TNT":
				dol_include_once("/shippinglabels/class/tnt.class.php");
				$service=new L_tnt($db, $user);
				break;
			case "MR":
				dol_include_once("/shippinglabels/class/mondialRelay.class.php");
				$service=new L_MondialRelay($db, $user);
				break;
			default:
				$service=0;
		}
		return $service;
	}

	function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $db, $user, $conf, $langs;

		$current_context=explode(':',$parameters['context']);
		dol_syslog(__FUNCTION__." context ".print_r($current_context,true)." action ".$action, LOG_DEBUG);
		if (in_array('expeditioncard',$current_context))
		{
			if (is_object($object) && ($object->id > 0) && in_array($action, array('getshiplabel')))
			{
				$langs->load("shippinglabels@shippinglabels");

				$shipping_method=delivery_methods($object->shipping_method_id);

				$page='';
				if (version_compare(DOL_VERSION, '3.7.0') >= 0)
				{
					$page='card';
				}
				else
				{
					$page='fiche';
				}
				dol_syslog(__FUNCTION__." est appelée ".$action, LOG_DEBUG);
				// appel fonction création

				$service = $this->getService($shipping_method);

                if (!empty($conf->companyrelationships->enabled)) {
                    // modify thirdparty with benefactor
                    $savObjectSocId = $object->socid;

                    if ($object->array_options['options_companyrelationships_fk_soc_benefactor'] > 0) {
                        $object->socid = $object->array_options['options_companyrelationships_fk_soc_benefactor'];
                    }
                }

				if($service->ws_call($object)<0)
				{
                    if (!empty($conf->companyrelationships->enabled)) {
                        // reset saved thirdparty
                        $object->socid = $savObjectSocId;
                    }

					dol_syslog(__FUNCTION__." erreur ".$service->error, LOG_DEBUG);
					setEventMessages('',$service->errors,'errors');
				}
				else
				{
                    $urlAction = '';
                    if (!empty($conf->companyrelationships->enabled)) {
                        // reset saved thirdparty
                        $object->socid = $savObjectSocId;
                        $urlAction = '&action=builddoc';
                    }

					$object->tracking_number=$service->trackingnumber;
					$object->update($user);
					header("Location: ".$page.".php?id=".$object->id.$urlAction);
					exit();
				};
			}
		}
		return 0;
	}


	function formObjectOptions($parameters, $object, $action)
	{
		global $conf,$langs, $user;

		$current_context=explode(':',$parameters['context']);
		if (in_array('expeditioncard',$current_context) && $action != 'create' && $object->statut == 1)
		{
			$shipping_method=delivery_methods($object->shipping_method_id);
			$form = new Form($db);
			$langs->load("shippinglabels@shippinglabels");

			$service = $this->getService($shipping_method);
			if ($service)
			{
				$service->get_label($object);
				$dir = $conf->expedition->dir_output . "/sending/" .$object->ref ;

				$page='';
				if (version_compare(DOL_VERSION, '3.7.0') >= 0)
				{
					$page='card';
				}
				else
				{
					$page='fiche';
				}

				// form
				print '<tr><td>'.$langs->trans("getLabelShipping").'</td>';
				print '<td>';

				if($object->shipping_method_id == null)
				{
					print $langs->trans("ShippingMethodNotFilled");

					return 0;
				}
				else
				{
				//	print "'".$service->labelDir.$service->labelFile."'";
					if(file_exists($service->labelDir.$service->labelFile)==false)
					{
						$html =  '<form method="POST" action="'.$page.'.php?id='.$object->id.'">';
						$html .=  '<input type="submit" name="label"/>';
						$html .=  '<input type="hidden" name="action" value="getshiplabel" />';
						$html .=  '</form>';
						print $html;
					}
					else
					{
						print '<form method="POST" action="'.$page.'.php?id='.$object->id.'">';
						print $langs->trans("labelAlreadyExist");
						print '   ';
						print '<input type="submit" name="label" value="';
						print $langs->trans("UpdateLabel");
						print '"/>';
						print '<input type="hidden" name="action" value="getshiplabel" />';

						print '</form>';
					}
				}
				print '</td></tr>';
			}
		}
		$a=UpdateUrlTrackingStatus($object);
		return 0;
	}
}
?>