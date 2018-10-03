<?php
/* Copyright (C) 2018 		Netlogic			<info@netlogic.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    doliesign/lib/doliesign.lib.php
 * \ingroup doliesign
 * \brief   Library files with common functions for DoliEsign
 */


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

dol_include_once('/doliesign/class/doliesign.class.php');
dol_include_once('/doliesign/class/config.class.php');
dol_include_once('/contact/class/contact.class.php');

/**
 * Prepare array of tabs for DoliEsign
 *
 * @param	DoliEsign	$object		DoliEsign
 * @return 	array					Array of tabs
 */
function doliesignPrepareHead($object)
{
	global $db, $langs, $conf;

	$langs->load("doliesign@doliesign");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/doliesign/doliesign_card.php", 1).'?id='.$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->doliesign->dir_output . "/doliesign/" . dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir,'files',0,'','(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'doliesign@doliesign');

	return $head;
}

/**
 * Prepare admin pages header
 *
 * @return array
 */
function doliesignAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("doliesign@doliesign");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/doliesign/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;
	$head[$h][0] = dol_buildpath("/doliesign/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@doliesign:/doliesign/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@doliesign:/doliesign/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'doliesign');

	return $head;
}

function doliEsignFixMobile($mobile, $countryCode)
{
	if (! preg_match('/^\+(?:[0-9] ?){6,14}[0-9]$/',$mobile)) {
		// remove (0) and spaces
		$mobile = preg_replace(['/\(0\)/','/\s/'],['',''],$mobile);
		if (! preg_match('/^\+(?:[0-9] ?){6,14}[0-9]$/',$mobile)) {
			// try to add +33 if country is France and numbers starts with zero
			if ($countryCode == 'FR' && preg_match('/^0.*/',$mobile)) {
				$mobile = preg_replace('/^0/','+33',$mobile);
			} elseif ($countryCode == 'BE' && preg_match('/^0.*/',$mobile)) {
				$mobile = preg_replace('/^0/','+32',$mobile);
			} elseif ($countryCode == 'LU' && preg_match('/^0.*/',$mobile)) {
				$mobile = preg_replace('/^0/','+352',$mobile);
			}
		}
		if (! preg_match('/^\+(?:[0-9] ?){6,14}[0-9]$/',$mobile)) {
			$mobile = '';
		}
	}
	return $mobile;
}



/**
 * Return array of possible common substitutions. This includes several families like: 'system', 'mycompany', 'object', 'objectamount', 'date', 'user'
 *
 * @param	Translate	$outputlangs	Output language
 * @param   int         $onlykey        1=Do not calculate some heavy values of keys (performance enhancement when we need only the keys), 2=Values are trunc and html sanitized (to use for help tooltip)
 * @param   array       $exclude        Array of family keys we want to exclude. For example array('system', 'mycompany', 'object', 'objectamount', 'date', 'user', ...)
 * @param   Object      $object         Object for keys on object
 * @return	array						Array of substitutions
 * @see setSubstitFromObject
 */
function doliEsignGetCommonSubstitutionArray($outputlangs, $onlykey=0, $exclude=null, $object=null)
{
	global $db, $conf, $mysoc, $user;

	$substitutionarray=array();

	if (empty($exclude) || ! in_array('system', $exclude))
	{
		$substitutionarray['__(AnyTranslationKey)__']=$outputlangs->trans('TranslationOfKey');
		$substitutionarray['__[AnyConstantKey]__']=$outputlangs->trans('ValueOfConstant');
		$substitutionarray['__DOL_MAIN_URL_ROOT__']=DOL_MAIN_URL_ROOT;
	}
	if (empty($exclude) || ! in_array('mycompany', $exclude))
	{
		$substitutionarray=array_merge($substitutionarray, array(
			'__MYCOMPANY_NAME__'    => $mysoc->name,
			'__MYCOMPANY_EMAIL__'   => $mysoc->email,
			'__MYCOMPANY_PROFID1__' => $mysoc->idprof1,
			'__MYCOMPANY_PROFID2__' => $mysoc->idprof2,
			'__MYCOMPANY_PROFID3__' => $mysoc->idprof3,
			'__MYCOMPANY_PROFID4__' => $mysoc->idprof4,
			'__MYCOMPANY_PROFID5__' => $mysoc->idprof5,
			'__MYCOMPANY_PROFID6__' => $mysoc->idprof6,
			'__MYCOMPANY_CAPITAL__' => $mysoc->capital,
			'__MYCOMPANY_FULLADDRESS__' => $mysoc->getFullAddress(1, ', '),
			'__MYCOMPANY_ADDRESS__' => $mysoc->address,
			'__MYCOMPANY_ZIP__'     => $mysoc->zip,
			'__MYCOMPANY_TOWN__'    => $mysoc->town,
			'__MYCOMPANY_COUNTRY__'    => $mysoc->country,
			'__MYCOMPANY_COUNTRY_ID__' => $mysoc->country_id
		));
	}
	if (($onlykey || is_object($object)) && (empty($exclude) || ! in_array('object', $exclude)))
	{
		if ($onlykey)
		{
			$substitutionarray['__ID__'] = '__ID__';
			$substitutionarray['__REF__'] = '__REF__';
			$substitutionarray['__REFCLIENT__'] = '__REFCLIENT__';
			$substitutionarray['__REFSUPPLIER__'] = '__REFSUPPLIER__';
			$substitutionarray['__EXTRAFIELD_XXX__'] = '__EXTRAFIELD_XXX__';

			$substitutionarray['__THIRDPARTY_ID__'] = '__THIRDPARTY_ID__';
			$substitutionarray['__THIRDPARTY_NAME__'] = '__THIRDPARTY_NAME__';

			if (is_object($object) && $object->element == 'shipping')
			{
				$substitutionarray['__MEMBER_CIVILITY__'] = '__MEMBER_CIVILITY__';
				$substitutionarray['__MEMBER_FIRSTNAME__'] = '__MEMBER_FIRSTNAME__';
				$substitutionarray['__MEMBER_LASTNAME__'] = '__MEMBER_LASTNAME__';
			}
			$substitutionarray['__PROJECT_ID__'] = '__PROJECT_ID__';
			$substitutionarray['__PROJECT_REF__'] = '__PROJECT_REF__';
			$substitutionarray['__PROJECT_NAME__'] = '__PROJECT_NAME__';

			$substitutionarray['__CONTRACT_HIGHEST_PLANNED_START_DATE__'] = 'Highest date planned for a service start';
			$substitutionarray['__CONTRACT_HIGHEST_PLANNED_START_DATETIME__'] = 'Highest date and hour planned for service start';
			$substitutionarray['__CONTRACT_LOWEST_EXPIRATION_DATE__'] = 'Lowest data for planned expiration of service';
			$substitutionarray['__CONTRACT_LOWEST_EXPIRATION_DATETIME__'] = 'Lowest date and hour for planned expiration of service';

			$substitutionarray['__ONLINE_PAYMENT_URL__'] = 'LinkToPayOnlineIfApplicable';
			$substitutionarray['__SECUREKEYPAYMENT__'] = 'Security key (if key is not unique per record)';
			$substitutionarray['__SECUREKEYPAYMENT_MEMBER__'] = 'Security key for payment on a member subscription (one key per member)';
			$substitutionarray['__SECUREKEYPAYMENT_ORDER__'] = 'Security key for payment on an order';
			$substitutionarray['__SECUREKEYPAYMENT_INVOICE__'] = 'Security key for payment on an invoice';
			$substitutionarray['__SECUREKEYPAYMENT_CONTRACTLINE__'] = 'Security key for payment on a a service';

			if (is_object($object) && $object->element == 'shipping')
			{
				$substitutionarray['__SHIPPINGTRACKNUM__']='Shipping tacking number';
				$substitutionarray['__SHIPPINGTRACKNUMURL__']='Shipping tracking url';
			}
		}
		else
		{
			$substitutionarray['__ID__'] = $object->id;
			$substitutionarray['__REF__'] = $object->ref;
			$substitutionarray['__REFCLIENT__'] = (isset($object->ref_client) ? $object->ref_client : (isset($object->ref_customer) ? $object->ref_customer : ''));
			$substitutionarray['__REFSUPPLIER__'] = (isset($object->ref_supplier) ? $object->ref_supplier : '');

			// TODO USe this ?
			$msgishtml = 0;

			$birthday = dol_print_date($object->birth,'day');

			if (method_exists($object, 'getCivilityLabel')) $substitutionarray['__MEMBER_CIVILITY__'] = $object->getCivilityLabel();
			$substitutionarray['__MEMBER_FIRSTNAME__']=$msgishtml?dol_htmlentitiesbr($object->firstname):$object->firstname;
			$substitutionarray['__MEMBER_LASTNAME__']=$msgishtml?dol_htmlentitiesbr($object->lastname):$object->lastname;
			if (method_exists($object, 'getFullName')) $substitutionarray['__MEMBER_FULLNAME__']=$msgishtml?dol_htmlentitiesbr($object->getFullName($outputlangs)):$object->getFullName($outputlangs);
			$substitutionarray['__MEMBER_COMPANY__']=$msgishtml?dol_htmlentitiesbr($object->societe):$object->societe;
			$substitutionarray['__MEMBER_ADDRESS__']=$msgishtml?dol_htmlentitiesbr($object->address):$object->address;
			$substitutionarray['__MEMBER_ZIP__']=$msgishtml?dol_htmlentitiesbr($object->zip):$object->zip;
			$substitutionarray['__MEMBER_TOWN__']=$msgishtml?dol_htmlentitiesbr($object->town):$object->town;
			$substitutionarray['__MEMBER_COUNTRY__']=$msgishtml?dol_htmlentitiesbr($object->country):$object->country;
			$substitutionarray['__MEMBER_EMAIL__']=$msgishtml?dol_htmlentitiesbr($object->email):$object->email;
			$substitutionarray['__MEMBER_BIRTH__']=$msgishtml?dol_htmlentitiesbr($birthday):$birthday;
			$substitutionarray['__MEMBER_PHOTO__']=$msgishtml?dol_htmlentitiesbr($object->photo):$object->photo;
			$substitutionarray['__MEMBER_LOGIN__']=$msgishtml?dol_htmlentitiesbr($object->login):$object->login;
			$substitutionarray['__MEMBER_PASSWORD__']=$msgishtml?dol_htmlentitiesbr($object->pass):$object->pass;
			$substitutionarray['__MEMBER_PHONE__']=$msgishtml?dol_htmlentitiesbr($object->phone):$object->phone;
			$substitutionarray['__MEMBER_PHONEPRO__']=$msgishtml?dol_htmlentitiesbr($object->phone_perso):$object->phone_perso;
			$substitutionarray['__MEMBER_PHONEMOBILE__']=$msgishtml?dol_htmlentitiesbr($object->phone_mobile):$object->phone_mobile;

			if (is_object($object) && $object->element == 'societe')
			{
				$substitutionarray['__THIRDPARTY_ID__'] = (is_object($object)?$object->id:'');
				$substitutionarray['__THIRDPARTY_NAME__'] = (is_object($object)?$object->name:'');
			}
			elseif (is_object($object->thirdparty) && $object->thirdparty->id > 0)
			{
				$substitutionarray['__THIRDPARTY_ID__'] = (is_object($object->thirdparty)?$object->thirdparty->id:'');
				$substitutionarray['__THIRDPARTY_NAME__'] = (is_object($object->thirdparty)?$object->thirdparty->name:'');
			}

			if (is_object($object->projet) && $object->projet->id > 0)
			{
				$substitutionarray['__PROJECT_ID__'] = (is_object($object->projet)?$object->projet->id:'');
				$substitutionarray['__PROJECT_REF__'] = (is_object($object->projet)?$object->projet->ref:'');
				$substitutionarray['__PROJECT_NAME__'] = (is_object($object->projet)?$object->projet->title:'');
			}

			if (is_object($object) && $object->element == 'shipping')
			{
				$substitutionarray['__SHIPPINGTRACKNUM__']=$object->tracking_number;
				$substitutionarray['__SHIPPINGTRACKNUMURL__']=$object->tracking_url;
			}

			if (is_object($object) && $object->element == 'contrat' && is_array($object->lines))
			{
				$dateplannedstart='';
				$datenextexpiration='';
				foreach($object->lines as $line)
				{
					if ($line->date_ouverture_prevue > $dateplannedstart) $dateplannedstart = $line->date_ouverture_prevue;
					if ($line->statut == 4 && $line->date_fin_prevue && (! $datenextexpiration || $line->date_fin_prevue < $datenextexpiration)) $datenextexpiration = $line->date_fin_prevue;
				}
				$substitutionarray['__CONTRACT_HIGHEST_PLANNED_START_DATE__'] = dol_print_date($dateplannedstart, 'dayrfc');
				$substitutionarray['__CONTRACT_HIGHEST_PLANNED_START_DATETIME__'] = dol_print_date($dateplannedstart, 'standard');
				$substitutionarray['__CONTRACT_LOWEST_EXPIRATION_DATE__'] = dol_print_date($datenextexpiration, 'dayrfc');
				$substitutionarray['__CONTRACT_LOWEST_EXPIRATION_DATETIME__'] = dol_print_date($datenextexpiration, 'standard');
			}

			// Create dynamic tags for __EXTRAFIELD_FIELD__
			if ($object->table_element && $object->id > 0)
			{
				$extrafieldstmp = new ExtraFields($db);
				$extralabels = $extrafieldstmp->fetch_name_optionals_label($object->table_element, true);
				$object->fetch_optionals($object->id, $extralabels);
				foreach ($extrafieldstmp->attribute_label as $key => $label) {
					$substitutionarray['__EXTRAFIELD_' . strtoupper($key) . '__'] = $object->array_options['options_' . $key];
				}
			}

			$substitutionarray['__ONLINE_PAYMENT_URL__'] = 'TODO';
		}
	}
	if (empty($exclude) || ! in_array('objectamount', $exclude))
	{
		$substitutionarray['__DATE_YMD__']        = is_object($object)?(isset($object->date) ? dol_print_date($object->date, 'day', 0, $outputlangs) : '') : '';
		$substitutionarray['__DATE_DUE_YMD__']    = is_object($object)?(isset($object->date_lim_reglement)? dol_print_date($object->date_lim_reglement, 'day', 0, $outputlangs) : '') : '';
		$substitutionarray['__AMOUNT__']          = is_object($object)?$object->total_ttc:'';
		$substitutionarray['__AMOUNT_EXCL_TAX__'] = is_object($object)?$object->total_ht:'';
		$substitutionarray['__AMOUNT_VAT__']      = is_object($object)?($object->total_vat?$object->total_vat:$object->total_tva):'';
		if ($onlykey != 2 || $mysoc->useLocalTax(1)) $substitutionarray['__AMOUNT_TAX2__']     = is_object($object)?($object->total_localtax1?$object->total_localtax1:$object->total_localtax1):'';
		if ($onlykey != 2 || $mysoc->useLocalTax(2)) $substitutionarray['__AMOUNT_TAX3__']     = is_object($object)?($object->total_localtax2?$object->total_localtax2:$object->total_localtax2):'';

		/* TODO Add key for multicurrency
	$substitutionarray['__AMOUNT_FORMATED__']          = is_object($object)?price($object->total_ttc, 0, $outputlangs, 0, 0, -1, $conf->currency_code):'';
		$substitutionarray['__AMOUNT_EXCL_TAX_FORMATED__'] = is_object($object)?price($object->total_ht, 0, $outputlangs, 0, 0, -1, $conf->currency_code):'';
        $substitutionarray['__AMOUNT_VAT_FORMATED__']      = is_object($object)?($object->total_vat?price($object->total_vat, 0, $outputlangs, 0, 0, -1, $conf->currency_code):price($object->total_tva, 0, $outputlangs, 0, 0, -1, $conf->currency_code)):'';
		*/
		// For backward compatibility
		if ($onlykey != 2)
		{
			$substitutionarray['__TOTAL_TTC__']    = is_object($object)?$object->total_ttc:'';
			$substitutionarray['__TOTAL_HT__']     = is_object($object)?$object->total_ht:'';
			$substitutionarray['__TOTAL_VAT__']    = is_object($object)?($object->total_vat?$object->total_vat:$object->total_tva):'';
		}
	}

	if (empty($exclude) || ! in_array('date', $exclude))
	{
		include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		$tmp=dol_getdate(dol_now(), true);
		$tmp2=dol_get_prev_day($tmp['mday'], $tmp['mon'], $tmp['year']);
		$tmp3=dol_get_prev_month($tmp['mday'], $tmp['mon'], $tmp['year']);
		$tmp4=dol_get_next_day($tmp['mday'], $tmp['mon'], $tmp['year']);
		$tmp5=dol_get_next_month($tmp['mday'], $tmp['mon'], $tmp['year']);

		$substitutionarray=array_merge($substitutionarray, array(
			'__DAY__' => (string) $tmp['mday'],
			'__MONTH__' => (string) $tmp['mon'],
			'__YEAR__' => (string) $tmp['year'],
			'__PREVIOUS_DAY__' => (string) $tmp2['day'],
			'__PREVIOUS_MONTH__' => (string) $tmp3['month'],
			'__PREVIOUS_YEAR__' => (string) ($tmp['year'] - 1),
			'__NEXT_DAY__' => (string) $tmp4['day'],
			'__NEXT_MONTH__' => (string) $tmp5['month'],
			'__NEXT_YEAR__' => (string) ($tmp['year'] + 1),
		));
	}

	if (empty($exclude) || ! in_array('user', $exclude))
	{
		// Add SIGNATURE into substitutionarray first, so, when we will make the substitution,
		// this will also replace var found into content of signature
		$signature = $user->signature;
		$substitutionarray=array_merge($substitutionarray, array(
			'__USER_SIGNATURE__' => (string) (($signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN)) ? ($onlykey == 2 ? dol_trunc(dol_string_nohtmltag($signature), 30) : $signature) : '')
		)
			);
		// For backward compatibility
		if ($onlykey != 2)
		{
			$substitutionarray['__SIGNATURE__'] = (string) (($signature && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN)) ? ($onlykey == 2 ? dol_trunc(dol_string_nohtmltag($signature), 30) : $signature) : '');
		}

		$substitutionarray=array_merge($substitutionarray, array(
			'__USER_ID__' => (string) $user->id,
			'__USER_LOGIN__' => (string) $user->login,
			'__USER_LASTNAME__' => (string) $user->lastname,
			'__USER_FIRSTNAME__' => (string) $user->firstname,
			'__USER_FULLNAME__' => (string) $user->getFullName($outputlangs),
			'__USER_SUPERVISOR_ID__' => (string) $user->fk_user
			)
		);
	}
	if (! empty($conf->multicompany->enabled))
	{
		$substitutionarray=array_merge($substitutionarray, array('__ENTITY_ID__' => $conf->entity));
	}

	return $substitutionarray;
}

/**
 *     Show a confirmation HTML form or AJAX popup.
 *     Easiest way to use this is with useajax=1.
 *     If you use useajax='xxx', you must also add jquery code to trigger opening of box (with correct parameters)
 *     just after calling this method. For example:
 *       print '<script type="text/javascript">'."\n";
 *       print 'jQuery(document).ready(function() {'."\n";
 *       print 'jQuery(".xxxlink").click(function(e) { jQuery("#aparamid").val(jQuery(this).attr("rel")); jQuery("#dialog-confirm-xxx").dialog("open"); return false; });'."\n";
 *       print '});'."\n";
 *       print '</script>'."\n";
 *     @param   object		$form
 *     @param  	string		$page        	   	Url of page to call if confirmation is OK. Can contains paramaters (param 'action' and 'confirm' will be reformated)
 *     @param	string		$title       	   	Title
 *     @param	string		$question    	   	Question
 *     @param 	string		$action      	   	Action
 *	   @param  	array		$formquestion	   	An array with complementary inputs to add into forms: array(array('label'=> ,'type'=> , ))
*												type can be 'hidden', 'text', 'password', 'checkbox', 'radio', 'date', ...
* 	   @param  	string		$selectedchoice  	"" or "no" or "yes"
* 	   @param  	int			$useajax		   	0=No, 1=Yes, 2=Yes but submit page with &confirm=no if choice is No, 'xxx'=Yes and preoutput confirm box with div id=dialog-confirm-xxx
*     @param  	int			$height          	Force height of box
*     @param	int			$width				Force width of box ('999' or '90%'). Ignored and forced to 90% on smartphones.
*     @param	int			$disableformtag		1=Disable form tag. Can be used if we are already inside a <form> section.
*     @return 	string      	    			HTML ajax code if a confirm ajax popup is required, Pure HTML code if it's an html form
*/
function doliesignFormconfirm($form, $page, $title, $question, $action, $formquestion='', $selectedchoice='', $useajax=0, $height=200, $width=500, $disableformtag=0)
{
	global $langs,$conf;
	global $useglobalvars;

	$more='';
	$formconfirm='';
	$inputok=array();
	$inputko=array();

	// Clean parameters
	$newselectedchoice=empty($selectedchoice)?"no":$selectedchoice;
	if ($conf->browser->layout == 'phone') $width='95%';

	if (is_array($formquestion) && ! empty($formquestion))
	{
		// First add hidden fields and value
		foreach ($formquestion as $key => $input)
		{
			if (is_array($input) && ! empty($input))
			{
				if ($input['type'] == 'hidden')
				{
					$more.='<input type="hidden" id="'.$input['name'].'" name="'.$input['name'].'" value="'.dol_escape_htmltag($input['value']).'">'."\n";
				}
			}
		}

		// Now add questions
		$more.='<table class="paddingtopbottomonly" width="100%">'."\n";
		$more.='<tr><td colspan="3">'.(! empty($formquestion['text'])?$formquestion['text']:'').'</td></tr>'."\n";
		foreach ($formquestion as $key => $input)
		{
			if (is_array($input) && ! empty($input))
			{
				$size=(! empty($input['size'])?' size="'.$input['size'].'"':'');
				$moreattr=(! empty($input['moreattr'])?' '.$input['moreattr']:'');
				$morecss=(! empty($input['morecss'])?' '.$input['morecss']:'');

				if ($input['type'] == 'text')
				{
					$more.='<tr><td>'.$input['label'].'</td><td colspan="2" align="left"><input type="text" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'"'.$moreattr.' /></td></tr>'."\n";
				}
				else if ($input['type'] == 'password')
				{
					$more.='<tr><td>'.$input['label'].'</td><td colspan="2" align="left"><input type="password" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'"'.$size.' value="'.$input['value'].'"'.$moreattr.' /></td></tr>'."\n";
				}
				else if ($input['type'] == 'select')
				{
					$more.='<tr><td>';
					if (! empty($input['label'])) $more.=$input['label'].'</td><td valign="top" colspan="2" align="left">';
					$more.=$form->selectarray($input['name'],$input['values'],$input['default'],1,0,0,$moreattr,0,0,0,'',$morecss);
					$more.='</td></tr>'."\n";
				}
				else if ($input['type'] == 'checkbox')
				{
					$more.='<tr>';
					$more.='<td>'.$input['label'].' </td><td align="left">';
					$more.='<input type="checkbox" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'"'.$moreattr;
					if (! is_bool($input['value']) && $input['value'] != 'false') $more.=' checked';
					if (is_bool($input['value']) && $input['value']) $more.=' checked';
					if (isset($input['disabled'])) $more.=' disabled';
					$more.=' /></td>';
					$more.='<td align="left">&nbsp;</td>';
					$more.='</tr>'."\n";
				}
				else if ($input['type'] == 'radio')
				{
					$i=0;
					foreach($input['values'] as $selkey => $selval)
					{
						$more.='<tr>';
						if ($i==0) $more.='<td class="tdtop">'.$input['label'].'</td>';
						else $more.='<td>&nbsp;</td>';
						$more.='<td width="20"><input type="radio" class="flat'.$morecss.'" id="'.$input['name'].'" name="'.$input['name'].'" value="'.$selkey.'"'.$moreattr;
						if ($input['disabled']) $more.=' disabled';
						$more.=' /></td>';
						$more.='<td align="left">';
						$more.=$selval;
						$more.='</td></tr>'."\n";
						$i++;
					}
				}
				else if ($input['type'] == 'date')
				{
					$more.='<tr><td>'.$input['label'].'</td>';
					$more.='<td colspan="2" align="left">';
					$more.=$form->select_date($input['value'],$input['name'],0,0,0,'',1,0,1);
					$more.='</td></tr>'."\n";
					$formquestion[] = array('name'=>$input['name'].'day');
					$formquestion[] = array('name'=>$input['name'].'month');
					$formquestion[] = array('name'=>$input['name'].'year');
					$formquestion[] = array('name'=>$input['name'].'hour');
					$formquestion[] = array('name'=>$input['name'].'min');
				}
				else if ($input['type'] == 'other')
				{
					$more.='<tr><td>';
					if (! empty($input['label'])) $more.=$input['label'].'</td><td colspan="2" align="left">';
					$more.=$input['value'];
					$more.='</td></tr>'."\n";
				}

				else if ($input['type'] == 'onecolumn')
				{
					$more.='<tr><td colspan="3" align="left">';
					$more.=$input['value'];
					$more.='</td></tr>'."\n";
				}
			}
		}
		$more.='</table>'."\n";
	}

	// JQUI method dialog is broken with jmobile, we use standard HTML.
	// Note: When using dol_use_jmobile or no js, you must also check code for button use a GET url with action=xxx and check that you also output the confirm code when action=xxx
	// See page product/card.php for example
	if (! empty($conf->dol_use_jmobile)) $useajax=0;
	if (empty($conf->use_javascript_ajax)) $useajax=0;

	if ($useajax)
	{
		$autoOpen=true;
		$dialogconfirm='dialog-confirm';
		$button='';
		if (! is_numeric($useajax))
		{
			$button=$useajax;
			$useajax=1;
			$autoOpen=false;
			$dialogconfirm.='-'.$button;
		}
		$pageyes=$page.(preg_match('/\?/',$page)?'&':'?').'action='.$action.'&confirm=yes';
		$pageno=($useajax == 2 ? $page.(preg_match('/\?/',$page)?'&':'?').'confirm=no':'');
		// Add input fields into list of fields to read during submit (inputok and inputko)
		if (is_array($formquestion))
		{
			foreach ($formquestion as $key => $input)
			{
				//print "xx ".$key." rr ".is_array($input)."<br>\n";
				if (is_array($input) && isset($input['name'])) array_push($inputok,$input['name']);
				if (isset($input['inputko']) && $input['inputko'] == 1) array_push($inputko,$input['name']);
			}
		}
		// Show JQuery confirm box. Note that global var $useglobalvars is used inside this template
		$formconfirm.= '<div id="'.$dialogconfirm.'" title="'.dol_escape_htmltag($title).'" style="display: none;">';
		if (! empty($more)) {
			$formconfirm.= '<div class="confirmquestions">'.$more.'</div>';
		}
		$formconfirm.= ($question ? '<div class="confirmmessage">'.img_help('','').' '.$question . '</div>': '');
		$formconfirm.= '</div>'."\n";

		$formconfirm.= "\n<!-- begin ajax form_confirm page=".$page." -->\n";
		$formconfirm.= '<script type="text/javascript">'."\n";
		$formconfirm.= 'jQuery(document).ready(function() {
		$(function() {
			$( "#'.$dialogconfirm.'" ).dialog(
			{
				autoOpen: '.($autoOpen ? "true" : "false").',';
				if ($newselectedchoice == 'no')
				{
					$formconfirm.='
					open: function() {
						$(this).parent().find("button.ui-button:eq(2)").focus();
					},';
				}
				$formconfirm.='
				resizable: false,
				height: "'.$height.'",
				width: "'.$width.'",
				modal: true,
				closeOnEscape: false,
				buttons: {
					"'.dol_escape_js($langs->transnoentities("Yes")).'": function() {
						var options="";
						var inputok = '.json_encode($inputok).';
						var pageyes = "'.dol_escape_js(! empty($pageyes)?$pageyes:'').'";
						if (inputok.length>0) {
							$.each(inputok, function(i, inputname) {
								var more = "";
								if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
								if ($("#" + inputname).attr("type") == "radio") { more = ":checked"; }
								var inputvalue = $("#" + inputname + more).val();
								if (typeof inputvalue == "undefined") { inputvalue=""; }
								options += "&" + inputname + "=" + inputvalue;
							});
						}
						var urljump = pageyes + (pageyes.indexOf("?") < 0 ? "?" : "") + options;
						//alert(urljump);
						if (pageyes.length > 0) { location.href = urljump; }
						$(this).dialog("close");
					},
					"'.dol_escape_js($langs->transnoentities("No")).'": function() {
						var options = "";
						var inputko = '.json_encode($inputko).';
						var pageno="'.dol_escape_js(! empty($pageno)?$pageno:'').'";
						if (inputko.length>0) {
							$.each(inputko, function(i, inputname) {
								var more = "";
								if ($("#" + inputname).attr("type") == "checkbox") { more = ":checked"; }
								var inputvalue = $("#" + inputname + more).val();
								if (typeof inputvalue == "undefined") { inputvalue=""; }
								options += "&" + inputname + "=" + inputvalue;
							});
						}
						var urljump=pageno + (pageno.indexOf("?") < 0 ? "?" : "") + options;
						//alert(urljump);
						if (pageno.length > 0) { location.href = urljump; }
						$(this).dialog("close");
					}
				}
			}
			);

			var button = "'.$button.'";
			if (button.length > 0) {
				$( "#" + button ).click(function() {
					$("#'.$dialogconfirm.'").dialog("open");
				});
			}
		});
		});
		</script>';
		$formconfirm.= "<!-- end ajax form_confirm -->\n";
	}
	else
	{
		$formconfirm.= "\n<!-- begin form_confirm page=".$page." -->\n";

		if (empty($disableformtag)) $formconfirm.= '<form method="POST" action="'.$page.'" class="notoptoleftroright">'."\n";

		$formconfirm.= '<input type="hidden" name="action" value="'.$action.'">'."\n";
		if (empty($disableformtag)) $formconfirm.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">'."\n";

		$formconfirm.= '<table width="100%" class="valid">'."\n";

		// Line title
		$formconfirm.= '<tr class="validtitre"><td class="validtitre" colspan="3">'.img_picto('','recent').' '.$title.'</td></tr>'."\n";

		// Line form fields
		if ($more)
		{
			$formconfirm.='<tr class="valid"><td class="valid" colspan="3">'."\n";
			$formconfirm.=$more;
			$formconfirm.='</td></tr>'."\n";
		}

		// Line with question
		$formconfirm.= '<tr class="valid">';
		$formconfirm.= '<td class="valid">'.$question.'</td>';
		$formconfirm.= '<td class="valid">';
		$formconfirm.= $form->selectyesno("confirm",$newselectedchoice);
		$formconfirm.= '</td>';
		$formconfirm.= '<td class="valid" align="center"><input class="button valignmiddle" type="submit" value="'.$langs->trans("Validate").'"></td>';
		$formconfirm.= '</tr>'."\n";

		$formconfirm.= '</table>'."\n";

		if (empty($disableformtag)) $formconfirm.= "</form>\n";
		$formconfirm.= '<br>';

		$formconfirm.= "<!-- end form_confirm -->\n";
	}

	return $formconfirm;
}
