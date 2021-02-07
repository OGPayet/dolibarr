<?php
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) { $res = @include("../../../main.inc.php"); } // From "custom" directory

// Libraries
require_once DOL_DOCUMENT_ROOT . "/core/lib/admin.lib.php";
require_once '../lib/parcautomobile.lib.php';
dol_include_once('/parcautomobile/class/parcautomobile.class.php');

$parcautomobile  = new parcautomobile($db);
// Translations
$langs->load("parcautomobile@parcautomobile");
$langs->load("propal");
$langs->load("admin");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');

if ($action == 'setinterventionemail') {
    $name = GETPOST ( 'name', 'text' );
    $value = GETPOST ( 'value', 'int' );

    $error = 0;
    if ($value){
        $res = dolibarr_set_const($db, $name, 1, 'chaine', 0, '', 0);
    }else{
        $res = dolibarr_set_const($db, $name, 0, 'chaine', 0, '', 0);
    }
    if (! $res > 0) $error ++;


    if (! $error) {
        activateModule('modCron');
        setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    } else {
        setEventMessage($langs->trans("Error"), 'errors');
    }
}
elseif ($action == 'setinterventionemail_beforeafter') {
    $name = GETPOST ( 'name', 'text' ); $value = GETPOST ( 'value', 'int' );

    $error = 0;
    if ($value){
        $res = dolibarr_set_const($db, $name, 1, 'chaine', 0, '', 0);
    }else{
        $res = dolibarr_set_const($db, $name, 0, 'chaine', 0, '', 0);
    }
    if (! $res > 0) $error ++;

    if (! $error) {
        // activateModule('modCron');
        setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    } else {
        setEventMessage($langs->trans("Error"), 'errors');
    }
}
elseif ($action == 'parcautomobile_interventionmails') {
    $name = GETPOST ( 'name', 'text' );
    $nbrd = GETPOST ( 'PARCAUTOMOBILE_INTERVENTION_EMAIL_DAYS_BEFORE', 'int' );
    $msgcont = GETPOST ( 'PARCAUTOMOBILE_INTERVENTION_EMAIL_CONTENT_MSG', 'int' );

    $error = 0;
    if ($nbrd && $nbrd > 0){
        $res = dolibarr_set_const($db, $name, $nbrd, 'chaine', 0, '', 0);
        
        // $sql = "UPDATE " . MAIN_DB_PREFIX. "cronjob SET frequency = '".$nbrd."', unitfrequency = 86400 WHERE module_name = 'parcautomobile' AND classesname = 'parcautomobile/class/interventions_parc.class.php' AND objectname = 'interventions_parc' AND methodename = 'checkInterventionsMails'";
        // $resql = $db->query($sql);
        // if($resql) $res = dolibarr_set_const($db, $name, $nbrd, 'chaine', 0, '', 0);

    }

    // if(!empty($msgcont)){
        
    // }
    
    if (! $res > 0) $error ++;


    if (! $error) {
        setEventMessage($langs->trans("SetupSaved"), 'mesgs');
    } else {
        setEventMessage($langs->trans("Error"), 'errors');
    }
}

if(!empty($action)){
    header('Location: ./parcautomobile_setup.php');
    exit;
}



/*
 * View
 */
$page_name = "parcautomobileSetup";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// // Configuration header
$head = parcautomobileAdminPrepareHead();
dol_fiche_head(
    $head,
    'setting',
    $langs->trans("Configuration"),
    0,
    "parcautomobile@parcautomobile"
);


// Setup page goes here
$form=new Form($db);
$var=false;


$MenuMembers=$conf->global->ECV_GENERATE_CV_FOR_ADHERENTS;

print '<div class="setepparcauto">';
print '<form id="col4-form" method="post" action="parcautomobile_setup.php">';
print '<input type="hidden" name="action" value="parcautomobile_interventionmails">';
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';
print '<tr>';
    print '<td style="width:;">';

    $txt = $langs->trans("GenerateCVFor");
    $helpcontent = $langs->trans("parcautomobilecheckInterventionsMails");
    print $form->textwithpicto($langs->trans("parcautomobilecheckInterventionsMails"), $helpcontent, 1, 'help', '', 0, 2, 'watermarktooltip');

    $name1 = 'PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL';
    print '</td>';
    if (!empty($conf->global->PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL)) {
        print '<td class=""><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setinterventionemail&name='.$name1.'&value=0">';
        print img_picto($langs->trans("Activated"), 'switch_on');
        print '</a></td>';
    } else {
        print '<td class=""><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setinterventionemail&name='.$name1.'&value=1">';
        print img_picto($langs->trans("Disabled"), 'switch_off');
        print '</a></td>';
    }

print '</tr>';

print '</table>';

print '</form>';
if (!empty($conf->global->PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL)) {
    print '<br>';

    print '<form id="col5-form" method="post" action="parcautomobile_setup.php">';
    print '<input type="hidden" name="action" value="parcautomobile_interventionmails">';
    print '<input type="hidden" name="name" value="PARCAUTOMOBILE_INTERVENTION_EMAIL_DAYS_BEFORE">';
    print '<br>';
    print '<table class="noborder" width="100%">';

    $nbd = 7;
    if(!empty($conf->global->PARCAUTOMOBILE_INTERVENTION_EMAIL_DAYS_BEFORE)) 
        $nbd = $conf->global->PARCAUTOMOBILE_INTERVENTION_EMAIL_DAYS_BEFORE;

    print '<tr>';
        print '<td style="width:;" colspan="2">';
        print $langs->trans("Send_Mail_Before")."&nbsp;&nbsp;";

        print '<input type="number" min="1" step="1" name="PARCAUTOMOBILE_INTERVENTION_EMAIL_DAYS_BEFORE" value="'.$nbd.'" style="width:50px;"> '.$langs->trans("Days")." ".$langs->trans("Before_Validity_Date");

        print '<input type="submit" class="butAction" value="'.$langs->trans("Validate").'">';
        print '</td>';
       

    print '</tr>';

    // PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL_EVRYDAY_BEFORE
    print '<tr>';
        print '<td style="width: 230px; white-space: nowrap;">';

        $tittxt = $langs->trans("SendMailEvryDayBefore");
        $substitutionarray['__NBRDAYS__'] = '<b>'.$nbd.'</b>';
        complete_substitutions_array($substitutionarray, $langs);
        $txttosh = make_substitutions($tittxt, $substitutionarray);

        print $txttosh;

        $name1 = 'PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL_EVRYDAY_BEFORE';
        print '</td>';
        if (!empty($conf->global->PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL_EVRYDAY_BEFORE)) {
            print '<td class=""><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setinterventionemail_beforeafter&name='.$name1.'&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on');
            print '</a></td>';
        } else {
            print '<td class=""><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setinterventionemail_beforeafter&name='.$name1.'&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }

    print '</tr>';

    // PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL_EVRYDAY_AFTER
    print '<tr>';
        print '<td style="width: 230px; white-space: nowrap;">';

        print $langs->trans("SendMailEvryDayAfter");

        $name1 = 'PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL_EVRYDAY_AFTER';
        print '</td>';
        if (!empty($conf->global->PARCAUTOMOBILE_INTERVENTION_SEND_EMAIL_EVRYDAY_AFTER)) {
            print '<td class=""><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setinterventionemail_beforeafter&name='.$name1.'&value=0">';
            print img_picto($langs->trans("Activated"), 'switch_on');
            print '</a></td>';
        } else {
            print '<td class=""><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setinterventionemail_beforeafter&name='.$name1.'&value=1">';
            print img_picto($langs->trans("Disabled"), 'switch_off');
            print '</a></td>';
        }

    print '</tr>';


    // $msgemail = $langs->trans("Content_Of_The_Email");
    // if(!empty($conf->global->PARCAUTOMOBILE_INTERVENTION_EMAIL_CONTENT_MSG)) 
    //     $msgemail = $conf->global->PARCAUTOMOBILE_INTERVENTION_EMAIL_CONTENT_MSG;

    // print '<tr>';
    //     print '<td style="width:;">';
    //     print '<div class="inline-block">'.$langs->trans("Content_Of_The_Email")."</div><br><br>";

    //     print '<textarea name="PARCAUTOMOBILE_INTERVENTION_EMAIL_CONTENT_MSG" rows="4" cols="80" style="width:100%;" class="flat">'.$msgemail.'</textarea>';
    //     print '</td>';
       

    // print '</tr>';


    print '</table>';
    // print '<br><div style="text-align:left;"><input type="submit" class="butAction" value="'.$langs->trans("Validate").'"></div>';
    print '</form>';

    print '<br>';

  
    $cronid = '';

    $sql = "SELECT * FROM " . MAIN_DB_PREFIX. "cronjob WHERE module_name = 'parcautomobile' AND classesname = 'parcautomobile/class/interventions_parc.class.php' AND objectname = 'interventions_parc' AND methodename = 'checkInterventionsMails'";
    $resql = $db->query($sql);

    if ($resql) {
        $numrows = $db->num_rows($resql);
        if ($numrows){
            $obj = $db->fetch_object($resql);
            $cronid = $obj->rowid;
        }
    }

    if($cronid){
        print '<div>';
        print '<h4>'.$langs->trans("StepToFollowParcInterventionEmail").' : </h4>';
        print '</div>';

        print '<ol class="parclist">';
            // print '<li>'.$langs->trans("ActivateTravauxPlanifModule").'</li>';
            print '<li>';
            print $langs->trans("Gotothe");
            print ' <a target="_blank" href="'.dol_buildpath('/cron/admin/cron.php',2).'">'.$langs->trans("ConfigurationPageTravPlan").'</a> ';
            print $langs->trans("GenerateKeySecurity");
            print '</li>';
            print '<li>'.$langs->trans("VerifyThatEmailSentInYourPlateform").'</li>';
            // print '<li>';
            if(empty($conf->global->CRON_KEY)){
                print '<div class="pserrorcls">';
                print '<br><i>'.$langs->trans("PSSecurityKeyNotGenerated") .' (1.)</i>';
                print '</div>';
                // print '<br>';
            }
            print '<li>';
            
            // print $langs->trans("CopyTheLinkBelowToTheFile").' "<b>'.dol_buildpath('/conf/',1).'conf.php</b>" '.$langs->trans("WithTheLink").' :<br>';
            print $langs->trans("CopyTheLinkBelowToTheFile").' "<b>'.dol_buildpath('/conf/conf.php',1).'</b>" :<br>';

            $thlink = '';
            $thlink .= "\$dolibarr_main_url_parcautomobile_cronjob='";


            global $dolibarr_main_url_root;
            $urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
            $urlwithroot = $urlwithouturlroot.DOL_URL_ROOT;
            $url = $urlwithroot.'/public/cron/cron_run_jobs.php?'.(empty($conf->global->CRON_KEY) ? '' : 'securitykey='.$conf->global->CRON_KEY.'&').'userlogin='.$user->login.'&id='.$cronid;
            $thlink .= $url."';<br>\n";

            print '<div></div>';
            print '<div class="divlink">'.$thlink.'</div>';
            print '<div></div>';

            print '<a href="'.dol_buildpath('/parcautomobile/img/conf.png',2).'" target="_blank"><img src="'.dol_buildpath('/parcautomobile/img/conf.png',2).'" alt="" class="" style="height:320px;margin: 10px 0 0;"></a>';
            global $dolibarr_main_url_parcautomobile_cronjob;
            if(empty($dolibarr_main_url_parcautomobile_cronjob)){
                print '<div class="pserrorcls">';
                print '<br><i>'.$langs->trans("StepThreeNotYet") .'</i>';
                print '</div>';
            }
            print '</li>';

            print '<li>';
            print $langs->trans("TheLinkTheFileToMakeInYourServerToBeExecute").' :';

            $thlink = dol_buildpath('/parcautomobile/cronjob.php');
            print '<b> '.$thlink.'</b>';

            print ' '.$langs->trans("Everydays").'.';
            print '</li>';
        print '</ol>';
    }else{
        print '<div class="pserrorcls">';
        print '<br><i>'.$langs->trans("DesacAndActivModule").'</i>';
        print '</div>';
    }



    
    
}

print '</div>';

llxFooter();

$db->close();