<?php
/* Copyright (C) 2011-2017 Regis Houssin  <regis.houssin@inodbox.com>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *  \file       multicompany/admin/parameters.php
 *  \ingroup    multicompany
 *  \brief      Page d'administration/configuration du module Multi-Company
 */

$res=@include("../../main.inc.php");						// For root directory
if (! $res && file_exists($_SERVER['DOCUMENT_ROOT']."/main.inc.php"))
	$res=@include($_SERVER['DOCUMENT_ROOT']."/main.inc.php"); // Use on dev env only
if (! $res) $res=@include("../../../main.inc.php");			// For "custom" directory

require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';
if (! class_exists('ActionsMulticompany')) {
	require '../class/actions_multicompany.class.php';
}

$langs->load("admin");
$langs->load('multicompany@multicompany');

// Security check
if (empty($user->admin) || !empty($user->entity)) accessforbidden();


$action=GETPOST('action');

$object = New ActionsMulticompany($db);


/*
 * Action
 */
if (preg_match('/set_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_set_const($db, $code, 1, 'chaine', 0, '', 0) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

if (preg_match('/del_(.*)/',$action,$reg))
{
    $code=$reg[1];
    if (dolibarr_del_const($db, $code, 0) > 0)
    {
        Header("Location: ".$_SERVER["PHP_SELF"]);
        exit;
    }
    else
    {
        dol_print_error($db);
    }
}

/*
 * View
 */

llxHeader('',$langs->trans("MultiCompanySetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("MultiCompanySetup"),$linkback,'multicompany@multicompany');

print '<br>';

$head = multicompany_prepare_head();
dol_fiche_head($head, 'options', $langs->trans("ModuleSetup"));

$form=new Form($db);
$var=true;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

/*
 * Formulaire parametres divers
 */

// Login page combobox activation
$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("HideLoginCombobox").'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
print ajax_constantonoff('MULTICOMPANY_HIDE_LOGIN_COMBOBOX', '', 0);
print '</td></tr>';

/* Mode de gestion des droits :
 * Mode Off : mode Off : pyramidale. Les droits et les groupes sont gérés dans chaque entité : les utilisateurs appartiennent au groupe de l'entity pour obtenir leurs droits
 * Mode On : mode On : transversale : Les groupes ne peuvent appartenir qu'a l'entity = 0 et c'est l'utilisateur qui appartient à tel ou tel entity
 */

$text = img_picto('', 'info','class="linkobject"');
$htmltext = $langs->trans("GroupModeTransversalInfoFull");

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->trans("GroupModeTransversal").' '.$form->textwithtooltip('',$htmltext,2,1,$text).'</td>';
print '<td align="center" width="20">&nbsp;</td>';

print '<td align="center" width="100">';
$input = array(
		'alert' => array(
				'set' => array(
						'info' => true,
						'yesButton' => $langs->trans('Ok'),
						'title' => $langs->transnoentities('GroupModeTransversalTitle'),
						'content' => img_warning().' '.$langs->trans('GroupModeTransversalInfo')
				)
		)
);
print ajax_constantonoff('MULTICOMPANY_TRANSVERSE_MODE', $input, 0);
print '</td></tr>';

// Enable global sharings
if (! empty($conf->societe->enabled) || ! empty($conf->product->enabled) || ! empty($conf->service->enabled) || ! empty($conf->categorie->enabled))
{
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$langs->trans("EnableGlobalSharings").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	$input = array(
			'alert' => array(
					'set' => array(
							'info' => true,
							'yesButton' => $langs->trans('Ok'),
							'title' => $langs->transnoentities('GlobalSharings'),
							'content' => img_warning().' '.$langs->trans('GlobalSharingsInfo')
					)
			),
			'showhide' => array(
					'#sharetitle',
					'#shareproduct',
					'#sharethirdparty',
					'#sharecategory',
					'#sharebank',
					'#shareexpensereport',
					'#shareproject',
					'#sharemember'
			),
			'hide' => array(
					'#sharetitle',
					'#shareinvoice',
					'#shareinvoicenumber',
					'#shareproduct',
					'#shareproductprice',
					'#sharestock',
					'#sharethirdparty',
					'#shareagenda',
					'#sharecategory',
					'#sharebank',
					'#shareexpensereport',
					'#shareproject',
					'#sharemember'
			),
			'del' => array(
					'MULTICOMPANY_INVOICE_SHARING_ENABLED',
					'MULTICOMPANY_INVOICENUMBER_SHARING_ENABLED',
					'MULTICOMPANY_PRODUCT_SHARING_ENABLED',
					'MULTICOMPANY_PRODUCTPRICE_SHARING_ENABLED',
					'MULTICOMPANY_STOCK_SHARING_ENABLED',
					'MULTICOMPANY_THIRDPARTY_SHARING_ENABLED',
					'MULTICOMPANY_AGENDA_SHARING_ENABLED',
					'MULTICOMPANY_CATEGORY_SHARING_ENABLED',
					'MULTICOMPANY_BANKACCOUNT_SHARING_ENABLED',
					'MULTICOMPANY_EXPENSEREPORT_SHARING_ENABLED',
					'MULTICOMPANY_PROJECT_SHARING_ENABLED',
					'MULTICOMPANY_MEMBER_SHARING_ENABLED'
			)
	);
	print ajax_constantonoff('MULTICOMPANY_SHARINGS_ENABLED', $input, 0);
	print '</td></tr>';
}

$text = img_picto('', 'info','class="linkobject"');
$htmltext = $langs->trans("GlobalSharingsInfo");

print '<tr class="liste_titre" id="sharetitle" '.(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? 'style="display:none;"' : '').'>';
print '<td>'.$langs->trans("ActivatingShares").' '.$form->textwithtooltip('',$htmltext,2,1,$text).'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

// Share thirparties and contacts
if (! empty($conf->societe->enabled))
{
	$var=!$var;
	print '<tr id="sharethirdparty" '.$bc[$var].(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td>'.$langs->trans("ShareThirdpartiesAndContacts").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	$input = array(
			'showhide' => array(
					'#shareinvoice',
					'#shareinvoicenumber',
					'#shareagenda'
			),
			'del' => array(
					'MULTICOMPANY_INVOICE_SHARING_ENABLED',
					'MULTICOMPANY_INVOICENUMBER_SHARING_ENABLED',
					'MULTICOMPANY_AGENDA_SHARING_ENABLED'
			)
	);
	print ajax_constantonoff('MULTICOMPANY_THIRDPARTY_SHARING_ENABLED', $input, 0);
	print '</td></tr>';
}

// Share invoices and invoices number
if (! empty($conf->facture->enabled) && ! empty($conf->societe->enabled))
{
	/*if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) && !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED))
		$var=!$var;
	$display=(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) || empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) ? ' style="display:none;"' : '');
	print '<tr id="shareinvoice" '.$bc[$var].$display.'>';
	print '<td>'.$langs->trans("ShareInvoices").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_INVOICE_SHARING_ENABLED', '', 0);
	print '</td></tr>';*/

	//if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) && !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED))
		$var=!$var;
	$display=(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) || empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) ? ' style="display:none;"' : '');
	print '<tr id="shareinvoicenumber" '.$bc[$var].$display.'>';
	print '<td>'.$langs->trans("ShareInvoicesNumber").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_INVOICENUMBER_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}

// Share agendas
if (! empty($conf->agenda->enabled) && ! empty($conf->societe->enabled))
{
	if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) && !empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED))
		$var=!$var;
	$display=(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) || empty($conf->global->MULTICOMPANY_THIRDPARTY_SHARING_ENABLED) ? ' style="display:none;"' : '');
	print '<tr id="shareagenda" '.$bc[$var].$display.'>';
	print '<td>'.$langs->trans("ShareAgenda").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_AGENDA_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}

// Share products/services
if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
{
	if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		$var=!$var;
	print '<tr id="shareproduct" '.$bc[$var].(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td>'.$langs->trans("ShareProductsAndServices").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	$input = array(
			'showhide' => array(
					'#shareproductprice',
					'#sharestock'
			),
			'del' => array(
					'MULTICOMPANY_PRODUCTPRICE_SHARING_ENABLED',
					'MULTICOMPANY_STOCK_SHARING_ENABLED'
			)
	);
	print ajax_constantonoff('MULTICOMPANY_PRODUCT_SHARING_ENABLED', $input, 0);
	print '</td></tr>';

	if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		$var=!$var;
	print '<tr id="shareproductprice" '.$bc[$var].(empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td>'.$langs->trans("ShareProductsAndServicesPrices").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_PRODUCTPRICE_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}

// Share stocks
if (! empty($conf->stock->enabled) && (! empty($conf->product->enabled) || ! empty($conf->service->enabled)))
{
	if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		$var=!$var;
	$display=(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) || empty($conf->global->MULTICOMPANY_PRODUCT_SHARING_ENABLED) ? ' style="display:none;"' : '');
	print '<tr id="sharestock" '.$bc[$var].$display.'>';
	print '<td>'.$langs->trans("ShareStock").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_STOCK_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}

// Share categories
if (! empty($conf->categorie->enabled))
{
	if (!empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED))
		$var=!$var;
	print '<tr id="sharecategory" '.$bc[$var].(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td>'.$langs->trans("ShareCategories").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_CATEGORY_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}

// Share banks
if (! empty($conf->banque->enabled))
{
	$var=!$var;
	print '<tr id="sharebank" '.$bc[$var].(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td>'.$langs->trans("ShareBank").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_BANKACCOUNT_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}

// Share expenses reports
if (! empty($conf->expensereport->enabled))
{
	$var=!$var;
	print '<tr id="shareexpensereport" '.$bc[$var].(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td>'.$langs->trans("ShareExpenseReport").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_EXPENSEREPORT_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}

//share projects
if (! empty($conf->projet->enabled))
{
        $var=!$var;
        print '<tr id="shareproject" '.$bc[$var].(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
        print '<td>'.$langs->trans("ShareProject").'</td>';
        print '<td align="center" width="20">&nbsp;</td>';

        print '<td align="center" width="100">';
        print ajax_constantonoff('MULTICOMPANY_PROJECT_SHARING_ENABLED', '', 0);
        print '</td></tr>';
}

// Share members
if (! empty($conf->adherent->enabled))
{
	$var=!$var;
	print '<tr id="sharemember" '.$bc[$var].(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td>'.$langs->trans("ShareMembers").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_MEMBER_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}

// Share equipements
if (! empty($conf->equipement->enabled))
{
	$var=!$var;
	print '<tr id="sharemember" '.$bc[$var].(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td>'.$langs->trans("SharedEquipements").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_EQUIPEMENT_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}

// Share contract
if (! empty($conf->contrat->enabled))
{
	$var=!$var;
	print '<tr id="sharemember" '.$bc[$var].(empty($conf->global->MULTICOMPANY_SHARINGS_ENABLED) ? ' style="display:none;"' : '').'>';
	print '<td>'.$langs->trans("SharedContrat").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';

	print '<td align="center" width="100">';
	print ajax_constantonoff('MULTICOMPANY_CONTRACT_SHARING_ENABLED', '', 0);
	print '</td></tr>';
}

// Dictionnaries
if (1 == 2 && ! empty($object->sharingdicts)) // TODO wait Dolibarr 7.0 ?
{
	$text = img_picto('', 'info','class="linkobject"');
	$htmltext = $langs->trans("DictsSharingsInfo");

	print '<tr class="liste_titre" id="dictsharetitle">';
	print '<td>'.$langs->trans("ActivatingDictsShares").' '.$form->textwithtooltip('',$htmltext,2,1,$text).'</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
	print '</tr>';

	foreach ($object->sharingdicts as $dict)
	{
		$var=!$var;
		print '<tr id="share'.$dict.'" '.$bc[$var].'>';
		print '<td>'.$langs->trans("Share".ucfirst($dict)).'</td>';
		print '<td align="center" width="20">&nbsp;</td>';

		print '<td align="center" width="100">';
		print ajax_constantonoff('MULTICOMPANY_'.strtoupper($dict).'_SHARING_DISABLED', '', 0);
		print '</td></tr>';
	}
}

print '</table>';

// Card end
dol_fiche_end();
// Footer
llxFooter();
// Close database handler
$db->close();
