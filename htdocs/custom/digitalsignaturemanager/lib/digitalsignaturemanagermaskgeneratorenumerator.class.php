<?php
/* Copyright (C) 2020 Alexis LAURIER
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        lib/digitalsignaturemanagermaskgeneratorenumerator.class.php
 * \ingroup     digitalsignaturemanager
 * \brief       Class aims at fetching list of document generator available for each module part
 */
class DigitalSignatureManagerMaskGeneratorEnumerator
{
	/**
	 * @var array arrayOfModulePart and Module Class Name
	 */
	const SETTINGS_OF_CORE_MODULE = array(
		'company' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/societe/modules_societe.class.php',
			'className' => 'ModeleThirdPartyDoc'
		),
		'propal' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/propale/modules_propale.php',
			'className' => 'ModelePDFPropales'
		),
		'supplier_proposal' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/supplier_proposal/modules_supplier_proposal.php',
			'className' => 'ModelePDFSupplierProposal'
		),
		'commande' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/commande/modules_commande.php',
			'className' => 'ModelePDFCommandes'
		),
		'expedition' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/expedition/modules_expedition.php',
			'className' => 'ModelePDFExpedition'
		),
		'reception' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/reception/modules_reception.php',
			'className' => 'ModelePdfReception'
		),
		'livraison' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/livraison/modules_livraison.php',
			'className' => 'ModelePDFDeliveryOrder'
		),
		'ficheinter' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/fichinter/modules_fichinter.php',
			'className' => 'ModelePDFFicheinter'
		),
		'facture' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/facture/modules_facture.php',
			'className' => 'ModelePDFFactures'
		),
		'contract' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/contract/modules_contract.php',
			'className' => 'ModelePDFContract'
		),
		'project' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/project/modules_project.php',
			'className' => 'ModelePDFProjects'
		),
		'project_task' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/project/task/modules_task.php',
			'className' => 'ModelePDFTask'
		),
		'product' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/product/modules_product.class.php',
			'className' => 'ModelePDFProduct'
		),
		'product_batch' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/product_batch/modules_product_batch.class.php',
			'className' => 'ModelePDFProductBatch'
		),
		'stock' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/stock/modules_stock.php',
			'className' => 'ModelePDFStock'
		),
		'movement' => array(
			'include' =>  DOL_DOCUMENT_ROOT . '/core/modules/stock/modules_movement.php',
			'className' => 'ModelePDFMovement'
		),
		'export' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/export/modules_export.php',
			'className' => 'ModeleExports'
		),
		'commande_fournisseur' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/supplier_order/modules_commandefournisseur.php',
			'className' => 'ModelePDFSuppliersOrders'
		),
		'supplier_order' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/supplier_order/modules_commandefournisseur.php',
			'className' => 'ModelePDFSuppliersOrders'
		),
		'facture_fournisseur' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/supplier_invoice/modules_facturefournisseur.php',
			'className' => 'ModelePDFSuppliersInvoices'
		),
		'supplier_invoice' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/supplier_invoice/modules_facturefournisseur.php',
			'className' => 'ModelePDFSuppliersInvoices'
		),
		'supplier_payment' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/supplier_payment/modules_supplier_payment.php',
			'className' => 'ModelePDFSuppliersPayments'
		),
		'remisecheque' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/cheque/modules_chequereceipts.php',
			'className' => 'ModeleChequeReceipts'
		),
		'donation' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/dons/modules_don.php',
			'className' => 'ModeleDon'
		),
		'member' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/member/modules_cards.php',
			'className' => 'ModelePDFCards'
		),
		'agenda' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/action/modules_action.php',
			'className' => 'ModeleAction'
		),
		'actions' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/action/modules_action.php',
			'className' => 'ModeleAction'
		),
		'expensereport' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/expensereport/modules_expensereport.php',
			'className' => 'ModeleExpenseReport'
		),
		'user' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/user/modules_user.class.php',
			'className' => 'ModelePDFUser'
		),
		'usergroup' => array(
			'include' => DOL_DOCUMENT_ROOT . '/core/modules/usergroup/modules_usergroup.class.php',
			'className' => 'ModelePDFUserGroup'
		)
	);

	/**
	 * Function to get list of available pdf for core module part
	 * @param DoliDB $db Instance of database to use
	 * @param string $modulePart Name of the custom module part with 'nameofmodule:nameofsubmodule' syntax if needed
	 * @return array
	 */
	public static function getAvailableModelsForCoreModulePart($db, $modulePart) {
		$result = array();
		$settings = self::SETTINGS_OF_CORE_MODULE[$modulePart];
		if(!empty($settings)) {
			include_once $settings['include'];
			$classname = $settings['className'];
			$result = $classname::liste_modeles($db);
		}
		//For odt template - we have to remove absolute path to file and only saved relative directory to DOL_DOCUMENT_ROOT
		$finalResult = array();
		foreach($result as $keyWithFullPath=>$displayName) {
			$finalKey = $keyWithFullPath;
			$splittedKey = explode(':', $finalKey);
			$keyOnWhichGetProperName = $splittedKey[1] ?? $splittedKey[0];
			$pathInfo = pathinfo($keyOnWhichGetProperName);
			$finalKey = $pathInfo['filename'];
			$finalResult[$finalKey] = $displayName;
		}

		return $finalResult;
	}


	/**
	 * Function to get list of available pdf model generator for a custom module part
	 * @param DoliDB $db Instance of database to use
	 * @param string $modulePart Name of the custom module part with 'nameofmodule:nameofsubmodule' syntax if needed
	 * @return array
	 */
	public static function getAvailableModelsForCustomModulePart($db, $modulePart)
	{
		$result = array();
		$subModulePart = $modulePart;
		// modulepart = 'nameofmodule' or 'nameofmodule:nameofsubmodule'
		$tmp = explode(':', $modulePart);
		if (!empty($tmp[1])) {
			$modulePart = $tmp[0];
			$subModulePart = $tmp[1];
		}

		// For normalized standard modules
		$file = dol_buildpath('/core/modules/' . $modulePart . '/modules_' . $subModulePart . '.php', 0);
		if (file_exists($file)) {
			$res = include_once $file;
		}
		// For normalized external modules.
		else {
			$file = dol_buildpath('/' . $modulePart . '/core/modules/' . $modulePart . '/modules_' . $subModulePart . '.php', 0);
			$res = include_once $file;
		}
		$class = 'ModelePDF' . ucfirst($subModulePart);

		if (class_exists($class)) {
			$result = call_user_func($class . '::liste_modeles', $db);
		} else {
			dol_print_error($db, "Bad value for modulepart '" . $modulePart . "' in showdocuments");
		}
		return $result;
	}
}
