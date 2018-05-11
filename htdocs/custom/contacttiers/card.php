<?php
if (false === (@include '../../main.inc.php')) {  // From htdocs directory
	require '../../../main.inc.php'; // From "custom" directory
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (! empty($conf->adherent->enabled)) require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

$langs->load("companies");
$langs->load("commercial");
$langs->load("bills");
$langs->load("banks");
$langs->load("users");
$id_contact = GETPOST('id','int');
if (! empty($conf->categorie->enabled)) $langs->load("categories");
if (! empty($conf->incoterm->enabled)) $langs->load("incoterm");
if (! empty($conf->notification->enabled)) $langs->load("mails");

$mesg=''; $error=0; $errors=array();

$action		= (GETPOST('action','aZ09') ? GETPOST('action','aZ09') : 'view');
$cancel     = GETPOST('cancel');
$backtopage = GETPOST('backtopage','alpha');
$confirm	= GETPOST('confirm');
$socid		= GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
if (empty($socid) && $action == 'view') $action='create';

$object = new Societe($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('thirdpartycard','globalcard'));

if ($object->fetch($socid)<=0 && $action == 'view')
{
	$langs->load("errors");
	print($langs->trans('ErrorRecordNotFound'));
	exit;
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($socid);
$canvas = $object->canvas?$object->canvas:GETPOST("canvas");
$objcanvas=null;
if (! empty($canvas))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db, $action);
    $objcanvas->getCanvas('thirdparty', 'card', $canvas);
}

// Security check
$result = restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid', $objcanvas);



/*
 * Actions
 */

$parameters=array('id'=>$socid, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if ($cancel)
    {
        $action='';
        if (! empty($backtopage))
        {
            header("Location: ".$backtopage);
            exit;
        }
    }

    // Add new or update third party
    if ((! GETPOST('getcustomercode') && ! GETPOST('getsuppliercode'))
    && ($action == 'add' || $action == 'update') && $user->rights->societe->creer)
    {
        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

        if (! GETPOST('name'))
        {
            setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ThirdPartyName")), null, 'errors');
            $error++;
            $action='create';
        }
        if (GETPOST('client') < 0)
        {
            setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProspectCustomer")), null, 'errors');
            $error++;
            $action='create';
        }
        if (GETPOST('fournisseur') < 0)
        {
            setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Supplier")), null, 'errors');
            $error++;
            $action='create';
        }


        if ($action == 'update')
        {
		$ret=$object->fetch($socid);
			$object->oldcopy = clone $object;
        }
		else $object->canvas=$canvas;

        $object->name              = GETPOST('name', 'alpha');
        $object->name_alias            = GETPOST('name_alias');
        $object->address               = GETPOST('address');
        $object->zip                   = GETPOST('zipcode', 'alpha');
        $object->town                  = GETPOST('town', 'alpha');
        $object->country_id            = GETPOST('country_id', 'int');
        $object->state_id              = GETPOST('state_id', 'int');
        $object->skype                 = GETPOST('skype', 'alpha');
        $object->phone                 = GETPOST('phone', 'alpha');
        $object->fax                   = GETPOST('fax','alpha');
        $object->email                 = GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
        $object->url                   = GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
        $object->idprof1               = GETPOST('idprof1', 'alpha');
        $object->idprof2               = GETPOST('idprof2', 'alpha');
        $object->idprof3               = GETPOST('idprof3', 'alpha');
        $object->idprof4               = GETPOST('idprof4', 'alpha');
        $object->idprof5               = GETPOST('idprof5', 'alpha');
        $object->idprof6               = GETPOST('idprof6', 'alpha');
        $object->prefix_comm           = GETPOST('prefix_comm', 'alpha');
        $object->code_client           = GETPOST('code_client', 'alpha');
        $object->code_fournisseur      = GETPOST('code_fournisseur', 'alpha');
        $object->capital               = GETPOST('capital', 'alpha');
        $object->barcode               = GETPOST('barcode', 'alpha');

        $object->tva_intra             = GETPOST('tva_intra', 'alpha');
        $object->tva_assuj             = GETPOST('assujtva_value', 'alpha');
        $object->status                = GETPOST('status', 'alpha');

        // Local Taxes
        $object->localtax1_assuj       = GETPOST('localtax1assuj_value', 'alpha');
        $object->localtax2_assuj       = GETPOST('localtax2assuj_value', 'alpha');

        $object->localtax1_value	   = GETPOST('lt1', 'alpha');
        $object->localtax2_value	   = GETPOST('lt2', 'alpha');

        $object->forme_juridique_code  = GETPOST('forme_juridique_code', 'int');
        $object->effectif_id           = GETPOST('effectif_id', 'int');
        $object->typent_id             = GETPOST('typent_id');

        $object->client                = GETPOST('client', 'int');
        $object->fournisseur           = GETPOST('fournisseur', 'int');

        $object->commercial_id         = GETPOST('commercial_id', 'int');
        $object->default_lang          = GETPOST('default_lang');

        // Webservices url/key
        $object->webservices_url       = GETPOST('webservices_url', 'custom', 0, FILTER_SANITIZE_URL);
        $object->webservices_key       = GETPOST('webservices_key', 'san_alpha');

		// Incoterms
		if (!empty($conf->incoterm->enabled))
		{
			$object->fk_incoterms 		   = GETPOST('incoterm_id', 'int');
			$object->location_incoterms    = GETPOST('location_incoterms', 'alpha');
		}

		// Multicurrency
		if (!empty($conf->multicurrency->enabled))
		{
			$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
		}

        // Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		if ($ret < 0)
		{
			 $error++;
			 $action = ($action=='add'?'create':'edit');
		}

        if (GETPOST('deletephoto')) $object->logo = '';
        else if (! empty($_FILES['photo']['name'])) $object->logo = dol_sanitizeFileName($_FILES['photo']['name']);

        // Check parameters
        if (! GETPOST("cancel"))
        {
            if (! empty($object->email) && ! isValidEMail($object->email))
            {
                $langs->load("errors");
                $error++; $errors[] = $langs->trans("ErrorBadEMail",$object->email);
                $action = ($action=='add'?'create':'edit');
            }
            if (! empty($object->url) && ! isValidUrl($object->url))
            {
                $langs->load("errors");
                $error++; $errors[] = $langs->trans("ErrorBadUrl",$object->url);
                $action = ($action=='add'?'create':'edit');
            }
            if ($object->fournisseur && ! $conf->fournisseur->enabled)
            {
                $langs->load("errors");
                $error++; $errors[] = $langs->trans("ErrorSupplierModuleNotEnabled");
                $action = ($action=='add'?'create':'edit');
            }
            if (! empty($object->webservices_url)) {
                //Check if has transport, without any the soap client will give error
                if (strpos($object->webservices_url, "http") === false)
                {
                    $object->webservices_url = "http://".$object->webservices_url;
                }
                if (! isValidUrl($object->webservices_url)) {
                    $langs->load("errors");
                    $error++; $errors[] = $langs->trans("ErrorBadUrl",$object->webservices_url);
                    $action = ($action=='add'?'create':'edit');
                }
            }

            // We set country_id, country_code and country for the selected country
            $object->country_id=GETPOST('country_id')!=''?GETPOST('country_id'):$mysoc->country_id;
            if ($object->country_id)
            {
		$tmparray=getCountry($object->country_id,'all');
		$object->country_code=$tmparray['code'];
		$object->country=$tmparray['label'];
            }

            // Check for duplicate or mandatory prof id
            // Only for companies
	        if (!($object->particulier || $private))
		{
			for ($i = 1; $i <= 6; $i++)
			{
			    $slabel="idprof".$i;
				$_POST[$slabel]=trim($_POST[$slabel]);
			    $vallabel=$_POST[$slabel];
				if ($vallabel && $object->id_prof_verifiable($i))
					{
						if($object->id_prof_exists($i,$vallabel,$object->id))
						{
							$langs->load("errors");
					$error++; $errors[] = $langs->transcountry('ProfId'.$i, $object->country_code)." ".$langs->trans("ErrorProdIdAlreadyExist", $vallabel);
					$action = (($action=='add'||$action=='create')?'create':'edit');
						}
					}

			// Check for mandatory prof id (but only if country is than than ours)
					if ($mysoc->country_id > 0 && $object->country_id == $mysoc->country_id)
			{
					$idprof_mandatory ='SOCIETE_IDPROF'.($i).'_MANDATORY';
					if (! $vallabel && ! empty($conf->global->$idprof_mandatory))
					{
						$langs->load("errors");
						$error++;
						$errors[] = $langs->trans("ErrorProdIdIsMandatory", $langs->transcountry('ProfId'.$i, $object->country_code));
						$action = (($action=='add'||$action=='create')?'create':'edit');
					}
			}
			}
		}
        }

        if (! $error)
        {
            if ($action == 'add')
            {
                $db->begin();

                if (empty($object->client))      $object->code_client='';
                if (empty($object->fournisseur)) $object->code_fournisseur='';

                $result = $object->create($user);

				if ($result >= 0)
                {
                    if ($object->particulier)
                    {
                        dol_syslog("This thirdparty is a personal people",LOG_DEBUG);
                        $result=$object->create_individual($user);
                        if (! $result >= 0)
                        {
                            $error=$object->error; $errors=$object->errors;
                        }
                    }


					// Customer categories association
					$custcats = GETPOST( 'custcats', 'array' );
					$object->setCategories($custcats, 'customer');

					// Supplier categories association
					$suppcats = GETPOST('suppcats', 'array');
					$object->setCategories($suppcats, 'supplier');

                    // Logo/Photo save
                    $dir     = $conf->societe->multidir_output[$conf->entity]."/".$object->id."/logos/";
                    $file_OK = is_uploaded_file($_FILES['photo']['tmp_name']);
                    if ($file_OK)
                    {
                        if (image_format_supported($_FILES['photo']['name']))
                        {
                            dol_mkdir($dir);

                            if (@is_dir($dir))
                            {
                                $newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
                                $result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

                                if (! $result > 0)
                                {
                                    $errors[] = "ErrorFailedToSaveFile";
                                }
                                else
                                {
                                    // Create thumbs
                                    $object->addThumbs($newfile);
                                }
                            }
                        }
                    }
                    else
	              {
						switch($_FILES['photo']['error'])
						{
						    case 1: //uploaded file exceeds the upload_max_filesize directive in php.ini
						    case 2: //uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the html form
						      $errors[] = "ErrorFileSizeTooLarge";
						      break;
						case 3: //uploaded file was only partially uploaded
						      $errors[] = "ErrorFilePartiallyUploaded";
						      break;
						}
	                }
                    // Gestion du logo de la société
                }
                else
				{
				    if ($db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') // TODO Sometime errors on duplicate on profid and not on code, so we must manage this case
					{
						$duplicate_code_error = true;
						$object->code_fournisseur = null;
						$object->code_client = null;
					}

                    $error=$object->error; $errors=$object->errors;
                }

                if ($result >= 0)
                {
                    $db->commit();

			if (! empty($backtopage))
			{
			    if (preg_match('/\?/', $backtopage)) $backtopage.='&socid='.$object->id;
			    header("Location: ".$backtopage);
			exit;
			}
			else
			{
						$id_contact = GETPOST('id','int');
						require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
						$contact=new Contact($db);
						$contact->fetch($id_contact);
						$contact->socid = $object->id;	// fk_soc
						$contact->update($id_contact, $user);

			$url=DOL_URL_ROOT."/societe/card.php?socid=".$object->id;
			if (($object->client == 1 || $object->client == 3) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) $url=DOL_URL_ROOT."/comm/card.php?socid=".$object->id;
			else if ($object->fournisseur == 1) $url=DOL_URL_ROOT."/fourn/card.php?socid=".$object->id;

				header("Location: ".$url);
			exit;
			}
                }
                else
                {
                    $db->rollback();
                    $action='create';
                }
            }
        }
    }
}



/*
 *  View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formadmin = new FormAdmin($db);
$formcompany = new FormCompany($db);

if ($socid > 0 && empty($object->id))
{
    $result=$object->fetch($socid);
	if ($result <= 0) dol_print_error('',$object->error);
}

$title=$langs->trans("ThirdParty");
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->name." - ".$langs->trans('Card');
$help_url='EN:Module_Third_Parties|FR:Module_Tiers|ES:Empresas';
llxHeader('',$title,$help_url);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
    // -----------------------------------------
    // When used with CANVAS
    // -----------------------------------------
	$objcanvas->assign_values($action, $object->id, $object->ref);	// Set value for templates
    $objcanvas->display_canvas($action);							// Show template
}
else
{
    // -----------------------------------------
    // When used in standard mode
    // -----------------------------------------
    if ($action == 'create')
    {
        /*
         *  Creation
         */
		$private=GETPOST("private","int");
		if (! empty($conf->global->THIRDPARTY_DEFAULT_CREATE_CONTACT) && ! isset($_GET['private']) && ! isset($_POST['private'])) $private=1;
	if (empty($private)) $private=0;

        // Load object modCodeTiers
        $module=(! empty($conf->global->SOCIETE_CODECLIENT_ADDON)?$conf->global->SOCIETE_CODECLIENT_ADDON:'mod_codeclient_leopard');
        if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
        {
            $module = substr($module, 0, dol_strlen($module)-4);
        }
        $dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);
        foreach ($dirsociete as $dirroot)
        {
            $res=dol_include_once($dirroot.$module.'.php');
            if ($res) break;
        }
        $modCodeClient = new $module;
        // Load object modCodeFournisseur
        $module=(! empty($conf->global->SOCIETE_CODECLIENT_ADDON)?$conf->global->SOCIETE_CODECLIENT_ADDON:'mod_codeclient_leopard');
        if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
        {
            $module = substr($module, 0, dol_strlen($module)-4);
        }
        $dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);
        foreach ($dirsociete as $dirroot)
        {
            $res=dol_include_once($dirroot.$module.'.php');
            if ($res) break;
        }
        $modCodeFournisseur = new $module;

        // Define if customer/prospect or supplier status is set or not
        if (GETPOST("type")!='f')
        {
            $object->client=-1;
            if (! empty($conf->global->THIRDPARTY_CUSTOMERPROSPECT_BY_DEFAULT))  { $object->client=3; }
        }
        if (GETPOST("type")=='c')  { $object->client=3; }   // Prospect / Customer
        if (GETPOST("type")=='p')  { $object->client=2; }
        if (! empty($conf->fournisseur->enabled) && (GETPOST("type")=='f' || (GETPOST("type")=='' && ! empty($conf->global->THIRDPARTY_SUPPLIER_BY_DEFAULT))))  { $object->fournisseur=1; }

        $object->name				= GETPOST('name', 'alpha');
        $object->firstname			= GETPOST('firstname', 'alpha');
        $object->particulier		= $private;
        $object->prefix_comm		= GETPOST('prefix_comm');
        $object->client				= GETPOST('client')?GETPOST('client'):$object->client;

        if(empty($duplicate_code_error)) {
	        $object->code_client		= GETPOST('code_client', 'alpha');
	        $object->fournisseur		= GETPOST('fournisseur')?GETPOST('fournisseur'):$object->fournisseur;
        }
		else {
			setEventMessages($langs->trans('NewCustomerSupplierCodeProposed'),'', 'warnings');
		}

        $object->code_fournisseur	= GETPOST('code_fournisseur', 'alpha');
        $object->address			= GETPOST('address', 'alpha');
        $object->zip				= GETPOST('zipcode', 'alpha');
        $object->town				= GETPOST('town', 'alpha');
        $object->state_id			= GETPOST('state_id', 'int');
        $object->skype				= GETPOST('skype', 'alpha');
        $object->phone				= GETPOST('phone', 'alpha');
        $object->fax				= GETPOST('fax', 'alpha');
        $object->email				= GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
        $object->url				= GETPOST('url', 'custom', 0, FILTER_SANITIZE_URL);
        $object->capital			= GETPOST('capital', 'alpha');
        $object->barcode			= GETPOST('barcode', 'alpha');
        $object->idprof1			= GETPOST('idprof1', 'alpha');
        $object->idprof2			= GETPOST('idprof2', 'alpha');
        $object->idprof3			= GETPOST('idprof3', 'alpha');
        $object->idprof4			= GETPOST('idprof4', 'alpha');
        $object->idprof5			= GETPOST('idprof5', 'alpha');
        $object->idprof6			= GETPOST('idprof6', 'alpha');
        $object->typent_id			= GETPOST('typent_id', 'int');
        $object->effectif_id		= GETPOST('effectif_id', 'int');
        $object->civility_id		= GETPOST('civility_id', 'int');

        $object->tva_assuj			= GETPOST('assujtva_value', 'int');
        $object->status				= GETPOST('status', 'int');

        //Local Taxes
        $object->localtax1_assuj	= GETPOST('localtax1assuj_value', 'int');
        $object->localtax2_assuj	= GETPOST('localtax2assuj_value', 'int');

        $object->localtax1_value	=GETPOST('lt1', 'int');
        $object->localtax2_value	=GETPOST('lt2', 'int');

        $object->tva_intra			= GETPOST('tva_intra', 'alpha');

        $object->commercial_id		= GETPOST('commercial_id', 'int');
        $object->default_lang		= GETPOST('default_lang');

        $object->logo = (isset($_FILES['photo'])?dol_sanitizeFileName($_FILES['photo']['name']):'');

        // Gestion du logo de la société
        $dir     = $conf->societe->multidir_output[$conf->entity]."/".$object->id."/logos";
        $file_OK = (isset($_FILES['photo'])?is_uploaded_file($_FILES['photo']['tmp_name']):false);
        if ($file_OK)
        {
            if (image_format_supported($_FILES['photo']['name']))
            {
                dol_mkdir($dir);

                if (@is_dir($dir))
                {
                    $newfile=$dir.'/'.dol_sanitizeFileName($_FILES['photo']['name']);
                    $result = dol_move_uploaded_file($_FILES['photo']['tmp_name'], $newfile, 1);

                    if (! $result > 0)
                    {
                        $errors[] = "ErrorFailedToSaveFile";
                    }
                    else
                    {
                        // Create thumbs
                        $object->addThumbs($newfile);
                    }
                }
            }
        }

        // We set country_id, country_code and country for the selected country
        $object->country_id=GETPOST('country_id')?GETPOST('country_id'):$mysoc->country_id;
        if ($object->country_id)
        {
            $tmparray=getCountry($object->country_id,'all');
            $object->country_code=$tmparray['code'];
            $object->country=$tmparray['label'];
        }
        $object->forme_juridique_code=GETPOST('forme_juridique_code');
        /* Show create form */

        $linkback="";
        print load_fiche_titre($langs->trans("NewThirdParty"),$linkback,'title_companies.png');

        if (! empty($conf->use_javascript_ajax))
        {
            print "\n".'<script type="text/javascript">';
            print '$(document).ready(function () {
						id_te_private=8;
                        id_ef15=1;
                        is_private='.$private.';
						if (is_private) {
							$(".individualline").show();
						} else {
							$(".individualline").hide();
						}
                        $("#selectcountry_id").change(function() {
				document.formsoc.action.value="create";
				document.formsoc.submit();
                        });
                     });';
            print '</script>'."\n";
        }

        dol_htmloutput_mesg(is_numeric($error)?'':$error, $errors, 'error');

        print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formsoc">';

        print '<input type="hidden" name="action" value="add">';
        print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="private" value='.$object->particulier.'>';
        print '<input type="hidden" name="id" value='.$id_contact.'>';
        print '<input type="hidden" name="type" value='.GETPOST("type").'>';
        print '<input type="hidden" name="LastName" value="'.$langs->trans('ThirdPartyName').' / '.$langs->trans('LastName').'">';
        print '<input type="hidden" name="ThirdPartyName" value="'.$langs->trans('ThirdPartyName').'">';
        if ($modCodeClient->code_auto || $modCodeFournisseur->code_auto) print '<input type="hidden" name="code_auto" value="1">';

        dol_fiche_head(null, 'card', '', 0, '');

        print '<table class="border" width="100%">';

        // Name, firstname
	    print '<tr><td class="titlefieldcreate">';
        if ($object->particulier || $private)
        {
	        print '<span id="TypeName" class="fieldrequired">'.$langs->trans('ThirdPartyName').' / '.$langs->trans('LastName','name').'</span>';
        }
        else
		{
			print '<span id="TypeName" class="fieldrequired">'.fieldLabel('ThirdPartyName','name').'</span>';
        }
	    print '</td><td'.(empty($conf->global->SOCIETE_USEPREFIX)?' colspan="3"':'').'>';
	    print '<input type="text" class="minwidth300" maxlength="128" name="name" id="name" value="'.$object->name.'" autofocus="autofocus"></td>';
	    if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
	    {
		    print '<td>'.$langs->trans('Prefix').'</td><td><input type="text" size="5" maxlength="5" name="prefix_comm" value="'.$object->prefix_comm.'"></td>';
	    }
	    print '</tr>';

        // If javascript on, we show option individual
        if ($conf->use_javascript_ajax)
        {
            print '<tr class="individualline"><td>'.fieldLabel('FirstName','firstname').'</td>';
	        print '<td colspan="3"><input type="text" class="minwidth300" maxlength="128" name="firstname" id="firstname" value="'.$object->firstname.'"></td>';
            print '</tr>';
            // Title
            print '<tr class="individualline"><td>'.fieldLabel('UserTitle','civility_id').'</td><td colspan="3" class="maxwidthonsmartphone">';
            print $formcompany->select_civility($object->civility_id, 'civility_id', 'maxwidth100').'</td>';
            print '</tr>';
        }

        // Alias names (commercial, trademark or alias names)
        print '<tr id="name_alias"><td><label for="name_alias_input">'.$langs->trans('AliasNames').'</label></td>';
	    print '<td colspan="3"><input type="text" class="minwidth300" name="name_alias" id="name_alias_input" value="'.$object->name_alias.'"></td></tr>';

        // Prospect/Customer
        print '<tr><td class="titlefieldcreate">'.fieldLabel('ProspectCustomer','customerprospect',1).'</td>';
	    print '<td class="maxwidthonsmartphone">';
	    $selected=isset($_POST['client'])?GETPOST('client'):$object->client;
        print '<select class="flat" name="client" id="customerprospect">';
        if (GETPOST("type") == '') print '<option value="-1">&nbsp;</option>';
        if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) print '<option value="2"'.($selected==2?' selected':'').'>'.$langs->trans('Prospect').'</option>';
        if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS) && empty($conf->global->SOCIETE_DISABLE_CUSTOMERS) && empty($conf->global->SOCIETE_DISABLE_PROSPECTSCUSTOMERS)) print '<option value="3"'.($selected==3?' selected':'').'>'.$langs->trans('ProspectCustomer').'</option>';
        if (empty($conf->global->SOCIETE_DISABLE_CUSTOMERS)) print '<option value="1"'.($selected==1?' selected':'').'>'.$langs->trans('Customer').'</option>';
        print '<option value="0"'.((string) $selected == '0'?' selected':'').'>'.$langs->trans('NorProspectNorCustomer').'</option>';
        print '</select></td>';

        print '<td>'.fieldLabel('CustomerCode','customer_code').'</td><td>';
        print '<table class="nobordernopadding"><tr><td>';
		$tmpcode=$object->code_client;
        if (empty($tmpcode) && ! empty($modCodeClient->code_auto)) $tmpcode=$modCodeClient->getNextValue($object,0);
        print '<input type="text" name="code_client" id="customer_code" class="maxwidthonsmartphone" value="'.dol_escape_htmltag($tmpcode).'" maxlength="15">';
        print '</td><td>';
        $s=$modCodeClient->getToolTip($langs,$object,0);
        print $form->textwithpicto('',$s,1);
        print '</td></tr></table>';
        print '</td></tr>';

        if (! empty($conf->fournisseur->enabled) && ! empty($user->rights->fournisseur->lire))
        {
            // Supplier
            print '<tr>';
            print '<td>'.fieldLabel('Supplier','fournisseur',1).'</td><td>';
            $default = -1;
            if (! empty($conf->global->THIRDPARTY_SUPPLIER_BY_DEFAULT)) $default=1;
            print $form->selectyesno("fournisseur", (isset($_POST['fournisseur'])?GETPOST('fournisseur'):(GETPOST("type") == '' ? $default : $object->fournisseur)), 1, 0, (GETPOST("type") == '' ? 1 : 0));
            print '</td>';
            print '<td>'.fieldLabel('SupplierCode','supplier_code').'</td><td>';
            print '<table class="nobordernopadding"><tr><td>';
            $tmpcode=$object->code_fournisseur;
            if (empty($tmpcode) && ! empty($modCodeFournisseur->code_auto)) $tmpcode=$modCodeFournisseur->getNextValue($object,1);
            print '<input type="text" name="code_fournisseur" id="supplier_code" class="maxwidthonsmartphone" value="'.dol_escape_htmltag($tmpcode).'" maxlength="15">';
            print '</td><td>';
            $s=$modCodeFournisseur->getToolTip($langs,$object,1);
            print $form->textwithpicto('',$s,1);
            print '</td></tr></table>';
            print '</td></tr>';
        }

        // Status
        print '<tr><td>'.fieldLabel('Status','status').'</td><td colspan="3">';
        print $form->selectarray('status', array('0'=>$langs->trans('ActivityCeased'),'1'=>$langs->trans('InActivity')),1);
        print '</td></tr>';

        // Barcode
        if (! empty($conf->barcode->enabled))
        {
            print '<tr><td>'.fieldLabel('Gencod','barcode').'</td>';
	        print '<td colspan="3"><input type="text" name="barcode" id="barcode" value="'.$object->barcode.'">';
            print '</td></tr>';
        }

        // Address
        print '<tr><td class="tdtop">'.fieldLabel('Address','address').'</td>';
	    print '<td colspan="3"><textarea name="address" id="address" class="quatrevingtpercent" rows="'._ROWS_2.'" wrap="soft">';
        print $object->address;
        print '</textarea></td></tr>';

        // Zip / Town
        print '<tr><td>'.fieldLabel('Zip','zipcode').'</td><td>';
        print $formcompany->select_ziptown($object->zip,'zipcode',array('town','selectcountry_id','state_id'), 0, 0, '', 'maxwidth100 quatrevingtpercent');
        print '</td><td>'.fieldLabel('Town','town').'</td><td>';
        print $formcompany->select_ziptown($object->town,'town',array('zipcode','selectcountry_id','state_id'), 0, 0, '', 'maxwidth100 quatrevingtpercent');
        print '</td></tr>';

        // Country
        print '<tr><td>'.fieldLabel('Country','selectcountry_id').'</td><td colspan="3" class="maxwidthonsmartphone">';
        print $form->select_country((GETPOST('country_id')!=''?GETPOST('country_id'):$object->country_id));
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
        print '</td></tr>';

        // State
        if (empty($conf->global->SOCIETE_DISABLE_STATE))
        {
            print '<tr><td>'.fieldLabel('State','state_id').'</td><td colspan="3" class="maxwidthonsmartphone">';
            if ($object->country_id) print $formcompany->select_state($object->state_id,$object->country_code);
            else print $countrynotdefined;
            print '</td></tr>';
        }

        // Email web
        print '<tr><td>'.fieldLabel('EMail','email').(! empty($conf->global->SOCIETE_MAIL_REQUIRED)?'*':'').'</td>';
	    print '<td colspan="3"><input type="text" name="email" id="email" value="'.$object->email.'"></td></tr>';
        print '<tr><td>'.fieldLabel('Web','url').'</td>';
	    print '<td colspan="3"><input type="text" name="url" id="url" value="'.$object->url.'"></td></tr>';

        // Skype
        if (! empty($conf->skype->enabled))
        {
            print '<tr><td>'.fieldLabel('Skype','skype').'</td>';
	        print '<td colspan="3"><input type="text" name="skype" id="skype" value="'.$object->skype.'"></td></tr>';
        }

        // Phone / Fax
        print '<tr><td>'.fieldLabel('Phone','phone').'</td>';
	    print '<td><input type="text" name="phone" id="phone" class="maxwidth100onsmartphone quatrevingtpercent" value="'.$object->phone.'"></td>';
        print '<td>'.fieldLabel('Fax','fax').'</td>';
	    print '<td><input type="text" name="fax" id="fax" class="maxwidth100onsmartphone quatrevingtpercent" value="'.$object->fax.'"></td></tr>';

        // Prof ids
        $i=1; $j=0;
        while ($i <= 6)
        {
            $idprof=$langs->transcountry('ProfId'.$i,$object->country_code);
            if ($idprof!='-')
            {
	            $key='idprof'.$i;

                if (($j % 2) == 0) print '<tr>';

                $idprof_mandatory ='SOCIETE_IDPROF'.($i).'_MANDATORY';
                print '<td>'.fieldLabel($idprof,$key, (empty($conf->global->$idprof_mandatory)?0:1)).'</td><td>';

                print $formcompany->get_input_id_prof($i, $key, $object->$key, $object->country_code);
                print '</td>';
                if (($j % 2) == 1) print '</tr>';
                $j++;
            }
            $i++;
        }
        if ($j % 2 == 1) print '<td colspan="2"></td></tr>';

        // Vat is used
        print '<tr><td>'.fieldLabel('VATIsUsed','assujtva_value').'</td>';
        print '<td>';
        print $form->selectyesno('assujtva_value',(isset($conf->global->THIRDPARTY_DEFAULT_USEVAT)?$conf->global->THIRDPARTY_DEFAULT_USEVAT:1),1);     // Assujeti par defaut en creation
        print '</td>';
        print '<td class="nowrap">'.fieldLabel('VATIntra','intra_vat').'</td>';
        print '<td class="nowrap">';
        $s = '<input type="text" class="flat maxwidthonsmartphone" name="tva_intra" id="intra_vat" maxlength="20" value="'.$object->tva_intra.'">';

        if (empty($conf->global->MAIN_DISABLEVATCHECK))
        {
            $s.=' ';

            if (! empty($conf->use_javascript_ajax))
            {
                print "\n";
                print '<script language="JavaScript" type="text/javascript">';
                print "function CheckVAT(a) {\n";
                print "newpopup('".DOL_URL_ROOT."/societe/checkvat/checkVatPopup.php?vatNumber='+a,'".dol_escape_js($langs->trans("VATIntraCheckableOnEUSite"))."',500,300);\n";
                print "}\n";
                print '</script>';
                print "\n";
                $s.='<a href="#" class="hideonsmartphone" onclick="javascript: CheckVAT(document.formsoc.tva_intra.value);">'.$langs->trans("VATIntraCheck").'</a>';
                $s = $form->textwithpicto($s,$langs->trans("VATIntraCheckDesc",$langs->trans("VATIntraCheck")),1);
            }
            else
            {
                $s.='<a href="'.$langs->transcountry("VATIntraCheckURL",$object->country_id).'" target="_blank">'.img_picto($langs->trans("VATIntraCheckableOnEUSite"),'help').'</a>';
            }
        }
        print $s;
        print '</td>';
        print '</tr>';

        // Local Taxes
        //TODO: Place into a function to control showing by country or study better option
        if($mysoc->localtax1_assuj=="1" && $mysoc->localtax2_assuj=="1")
        {
            print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed",$mysoc->country_code).'</td><td>';
            print $form->selectyesno('localtax1assuj_value',(isset($conf->global->THIRDPARTY_DEFAULT_USELOCALTAX1)?$conf->global->THIRDPARTY_DEFAULT_USELOCALTAX1:0),1);
            print '</td><td>'.$langs->transcountry("LocalTax2IsUsed",$mysoc->country_code).'</td><td>';
            print $form->selectyesno('localtax2assuj_value',(isset($conf->global->THIRDPARTY_DEFAULT_USELOCALTAX2)?$conf->global->THIRDPARTY_DEFAULT_USELOCALTAX2:0),1);
            print '</td></tr>';

        }
        elseif($mysoc->localtax1_assuj=="1")
        {
            print '<tr><td>'.$langs->transcountry("LocalTax1IsUsed",$mysoc->country_code).'</td><td colspan="3">';
            print $form->selectyesno('localtax1assuj_value',(isset($conf->global->THIRDPARTY_DEFAULT_USELOCALTAX1)?$conf->global->THIRDPARTY_DEFAULT_USELOCALTAX1:0),1);
            print '</td></tr>';
        }
        elseif($mysoc->localtax2_assuj=="1")
        {
            print '<tr><td>'.$langs->transcountry("LocalTax2IsUsed",$mysoc->country_code).'</td><td colspan="3">';
            print $form->selectyesno('localtax2assuj_value',(isset($conf->global->THIRDPARTY_DEFAULT_USELOCALTAX2)?$conf->global->THIRDPARTY_DEFAULT_USELOCALTAX2:0),1);
            print '</td></tr>';
        }

        // Type - Size
        print '<tr><td>'.fieldLabel('ThirdPartyType','typent_id').'</td><td class="maxwidthonsmartphone">'."\n";
        $sortparam=(empty($conf->global->SOCIETE_SORT_ON_TYPEENT)?'ASC':$conf->global->SOCIETE_SORT_ON_TYPEENT); // NONE means we keep sort of original array, so we sort on position. ASC, means next function will sort on label.
        print $form->selectarray("typent_id", $formcompany->typent_array(0), $object->typent_id, 0, 0, 0, '', 0, 0, 0, $sortparam);
        if ($user->admin) print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
        print '</td>';
        print '<td>'.fieldLabel('Staff','effectif_id').'</td><td class="maxwidthonsmartphone">';
        print $form->selectarray("effectif_id", $formcompany->effectif_array(0), $object->effectif_id);
        if ($user->admin) print ' '.info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
        print '</td></tr>';

        // Legal Form
        print '<tr><td>'.fieldLabel('JuridicalStatus','forme_juridique_code').'</td>';
        print '<td colspan="3" class="maxwidthonsmartphone">';
        if ($object->country_id)
        {
            print $formcompany->select_juridicalstatus($object->forme_juridique_code, $object->country_code, '', 'forme_juridique_code');
        }
        else
        {
            print $countrynotdefined;
        }
        print '</td></tr>';

        // Capital
        print '<tr><td>'.fieldLabel('Capital','capital').'</td>';
	    print '<td colspan="3"><input type="text" name="capital" id="capital" size="10" value="'.$object->capital.'"> ';
        print '<span class="hideonsmartphone">'.$langs->trans("Currency".$conf->currency).'</span></td></tr>';

        if (! empty($conf->global->MAIN_MULTILANGS))
        {
            print '<tr><td>'.fieldLabel('DefaultLang','default_lang').'</td><td colspan="3" class="maxwidthonsmartphone">'."\n";
            print $formadmin->select_language(($object->default_lang?$object->default_lang:$conf->global->MAIN_LANG_DEFAULT),'default_lang',0,0,1,0,0,'maxwidth200onsmartphone');
            print '</td>';
            print '</tr>';
        }

        if ($user->rights->societe->client->voir)
        {
            // Assign a Name
            print '<tr>';
            print '<td>'.fieldLabel('AllocateCommercial','commercial_id').'</td>';
            print '<td colspan="3" class="maxwidthonsmartphone">';
            print $form->select_dolusers((! empty($object->commercial_id)?$object->commercial_id:$user->id),'commercial_id',1); // Add current user by default
            print '</td></tr>';
        }

		// Incoterms
		if (!empty($conf->incoterm->enabled))
		{
			print '<tr>';
			print '<td>'.fieldLabel('IncotermLabel','incoterm_id').'</td>';
	        print '<td colspan="3" class="maxwidthonsmartphone">';
	        print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms)?$object->location_incoterms:''));
			print '</td></tr>';
		}

		// Categories
		if (! empty($conf->categorie->enabled)  && ! empty($user->rights->categorie->lire))
		{
			$langs->load('categories');

			// Customer
			if ($object->prospect || $object->client) {
				print '<tr><td class="toptd">' . fieldLabel('CustomersCategoriesShort', 'custcats') . '</td><td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_CUSTOMER, null, 'parent', null, null, 1);
				print $form->multiselectarray('custcats', $cate_arbo, GETPOST('custcats', 'array'), null, null, null,
					null, "90%");
				print "</td></tr>";
			}

			// Supplier
			if ($object->fournisseur) {
				print '<tr><td class="toptd">' . fieldLabel('SuppliersCategoriesShort', 'suppcats') . '</td><td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_SUPPLIER, null, 'parent', null, null, 1);
				print $form->multiselectarray('suppcats', $cate_arbo, GETPOST('suppcats', 'array'), null, null, null,
					null, "90%");
				print "</td></tr>";
			}
		}

		// Multicurrency
		if (! empty($conf->multicurrency->enabled))
		{
			print '<tr>';
			print '<td>'.fieldLabel('Currency','multicurrency_code').'</td>';
	        print '<td colspan="3" class="maxwidthonsmartphone">';
	        print $form->selectMultiCurrency(($object->multicurrency_code ? $object->multicurrency_code : $conf->currency), 'multicurrency_code', 1);
			print '</td></tr>';
		}

        // Other attributes
        $parameters=array('colspan' => ' colspan="3"', 'colspanvalue' => '3');
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
        print $hookmanager->resPrint;
        if (empty($reshook) && ! empty($extrafields->attribute_label))
        {
		print $object->showOptionals($extrafields,'edit');
        }

        // Ajout du logo
        print '<tr class="hideonsmartphone">';
        print '<td>'.fieldLabel('Logo','photoinput').'</td>';
        print '<td colspan="3">';
        print '<input class="flat" type="file" name="photo" id="photoinput" />';
        print '</td>';
        print '</tr>';

        print '</table>'."\n";

        dol_fiche_end();

        print '<div class="center">';
        print '<input type="submit" class="button" name="create" value="'.$langs->trans('AddThirdParty').'">';
        if (! empty($backtopage))
        {
            print ' &nbsp; &nbsp; ';
            print '<input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
        }
        else
        {
            print ' &nbsp; &nbsp; ';
            print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
        }
        print '</div>'."\n";

        print '</form>'."\n";
    }
}


// End of page
llxFooter();
$db->close();
