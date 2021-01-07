<?php
/* Copyright (C) 2015      Sergio Sanchis         <ssanchis@2byte.es>
 * Copyright (C) 2015      Ferran Marcet          <fmarcet@2byte.es>
 * Copyright (C) 2015      Serge Azout - MS Mobile  <contact@msmobile.fr>
 * 
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
 * Original file modified on Sep29th 2015 in order to be compatible with OwnTheme module.
 * Tables moved inside forms to respect HTML coding standards.
 */

$archiv=GETPOST('viewarchived');
$deleteds=GETPOST('viewdeleteds');
$outbox=GETPOST('viewoutbox');
$action=GETPOST('action');
$toAction = GETPOST('toAction');
if(!empty($toAction))
	$_SESSION['toAction'] = $toAction;
$id=GETPOST('id');
$viewstatut=GETPOST('viewstatut');
if(!isset($_POST[viewstatut])){
	$viewstatut = -1;
}

	print '<script language="javascript" type="text/javascript">
        jQuery(document).ready(function()
        {
            jQuery("#checkall").click(function()
            {
                jQuery(".checkformail").attr(\'checked\', true);
            });
            jQuery("#checknone").click(function()
            {
                jQuery(".checkformail").attr(\'checked\', false);
            });
        });
        </script>
        ';
	print'<script type="text/javascript">
	$(document).on("ready",function(){
		var imgbg = $("#status").find("option:selected").attr("image");
  		 $("#status").css("background-image","url("+imgbg+")");

	});
	jQuery(document).ready(function()
        {
	';
		//para que en status salga en el filtro solo la imagen
	print '
			$("#status").change(function(){	
				var imgbg = $(this).find("option:selected").attr("image");
  				 $(this).css("background-image","url("+imgbg+")");
			});';

	//ajax message	
	print '$(".ajax_message").click(function(){
			';
	//delete unread class with delay and change status image	
		print'
				$(this).delay(600).queue(function() {
					$(this).removeClass("unread");
					
					if($(this).find( "td[data-campo=\'status\'] img" ).attr(\'src\')==\''.html_entity_decode(img_picto('','statut8','','',1)).'\'){
					$(this).find( "td[data-campo=\'status\']" ).html(\''.html_entity_decode(LibStatut(0)).'\')
				}	
				 });

    			var message_id = $(this).data("id");
				$.ajax("'.dol_buildpath('/webmail/message_ajax.php',1).'", {
				   	"type": "get",   // usualmente post o get
					"data": {id: message_id},
				   	"async": true,
				   	"success": function(result) {
				     if ($("#message").length==0){
				     
								$("#inbox").append("<div id=\'message\'></div>");
				     			$("#message").html(result); 
				     
						} else {		
					        
	                		$("#message").html(result); 
				     	}; 		
				   	},
				   	"error": function(result) {
				     console.error("Este callback maneja los errores", result);
				   	}
				});
			});
		
		});


		</script>';
if ($socid)
	{
		$title = $langs->trans('ListOfMails') /*. ' - '.$soc->nom*/;
	}
	else
	{
		$title = $langs->trans('Inbox');
		if($outbox) $title = $langs->trans('OutBox');
	}
	
	if($archiv) $title = $langs->trans('Archiveds');
	if($deleteds) $title = $langs->trans('Deleteds');
	
		$param='';
	if ($archiv)		  $param.='&viewarchived=1';	
	if ($deleteds)		  $param.='&viewdeleteds=1';	
	if ($outbox)		  $param.='&viewoutbox=1';	
	if ($viewstatut) 	  $param.='&viewstatut='.$viewstatut;
	if ($sremitente)      $param.='&sremitente='.$sremitente;
	if ($sdestinatario)   $param.='&sdestinatario='.$sdestinatario;
	if ($sasunto)         $param.='&sasunto='.$sasunto;
	if($id)  			  $param.='&id='.$id;
	if($outbox)  		  $param.='&viewoutbox='.$outbox;
	if($deleteds)  		  $param.='&viewdeleteds='.$deleteds;
	if($archiv)  		  $param.='&viewarchived='.$archiv;
	
	$num = $db->num_rows($resql);
	print_barre_liste($title, $page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num);
	$i = 0;
	
	// Lignes des champs de filtre	
	print '<div id="inbox">';
	print '<div id="list_messages">';

	print '<form method="POST" action="'.$_SERVER["REQUEST_URI"].'">';
	print '<table class="noborder" width="100%">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';
	print '<input type="hidden" name="action" value="search">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="viewarchived" value="'.$archiv.'">';
	print '<input type="hidden" name="viewdeleteds" value="'.$deleteds.'">';
	print '<input type="hidden" name="viewoutbox" value="'.$outbox.'">';
	
 	print '<thead>';	
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('MailSender').' ',$_SERVER["PHP_SELF"],'from','id=',$param,'data-campo="sender"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('MailReceiver').' ',$_SERVER["PHP_SELF"],'to','',$param,'data-campo="to"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('MailTopic').' ',$_SERVER["PHP_SELF"],'subject','',$param,'data-campo="topic"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('MailDate').' ',$_SERVER["PHP_SELF"],'datetime','',$param, 'data-campo="date"',$sortfield,$sortorder);
	print_liste_field_titre(img_picto($langs->trans('Attachments'),'addfile.png').' ',$_SERVER["PHP_SELF"],'files','',$param,'data-campo="attachments"',$sortfield,$sortorder);
	print_liste_field_titre(img_picto($langs->trans('MailStatus'),'statut7'),$_SERVER["PHP_SELF"],'state_new','',$param,'data-campo="status"',$sortfield,$sortorder);
	
	// To delete
	print '<th data-campo="delete">'.$langs->trans("Select")."<br>";
	if ($conf->use_javascript_ajax) print '<a href="#" id="checkall">'.$langs->trans("All").'</a> / <a href="#" id="checknone">'.$langs->trans("None").'</a>';
	print '</th>';
	
	print '</tr>';
	print '<tr class="liste_titre">';
	
	print '<td class="liste_titre" data-campo="sender">';
	print '<input class="flat" size="10" type="text" name="sremitente" value="'.$sremitente.'">';
	print '</td>';
	
	print '<td class="liste_titre" data-campo="to">';
	print '<input class="flat" size="10" type="text" name="sdestinatario" value="'.$sdestinatario.'">';
	print '</td>';
	
	print '<td class="liste_titre" data-campo="topic">';
	print '<input class="flat" type="text" size="25" name="sasunto" value="'.$sasunto.'">';
	print '</td>';
	
	print '<td class="liste_titre" data-campo="date"></td>';//date
	print '<td class="liste_titre attachments" data-campo="attachments"></td>';//attachments
	print '<td class="liste_titre" data-campo="status">';
	select_mail_statut_jquery($viewstatut);
	print '</td>';
	
	print '<td align="right" class="liste_titre" data-campo="delete">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'"  value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	if(!empty($sremitente) || !empty($sdestinatario) || $viewstatut!=-1 || !empty($sasunto)){
		print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	}
	print '</td>';

	print '</tr>';

	print'</thead>';
	print '</table>';
	print '</form>';


	//delete variables $_SERVER["REQUEST_URI"]
	$url_action = $_SERVER['PHP_SELF'].'?'.$param;
	
	print '<form method="POST" action="'.$url_action.'">';
	print '<table class="noborder" width="100%">';
	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="viewarchived" value="'.$archiv.'">';
	print '<input type="hidden" name="viewdeleteds" value="'.$deleteds.'">';
	print '<input type="hidden" name="viewoutbox" value="'.$outbox.'">';
	print'<tbody>';
	
	$bc=array(0=>'impair',1=>'pair');
	
	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);
		$objp->envia = html_entity_decode($objp->envia);
		$objp->destinatario = html_entity_decode($objp->destinatario);
		$objp->asunto = html_entity_decode($objp->asunto);
		$objp->datec = htmlentities($objp->datec);
		$objp->files = htmlentities($objp->files);
		$objp->leido = htmlentities($objp->leido);
				
		print '<tr data-id="'.$objp->rowid.'"';
		print 'class="';
		if($objp->leido==1){
			print 'unread';
		}
		print ' ajax_message">';		
		
		//link all tr
		$mailstatic->id = $objp->rowid;
		$mailstatic->subject=$objp->asunto;
		
		//Remitente
		$typemail = search_sender($objp->envia);
		
		
		if(is_array($typemail))
		{	
			
			switch ($typemail['type']) 
			{
    			case "Third":
    				print '<td class="nobordernopadding nowrap" data-campo="sender" title="'.$typemail['name'].'">';
    				
    				$thirdpartystatic->id=$typemail['id'];
       				$thirdpartystatic->name=$typemail['name'];
      		  		print '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$typemail['id'].'">';
    				print img_picto($langs->trans("ShowCompany"),'object_company.png').'</a> ';
    				print $typemail['name'];
    			break;
    				
    			case "Contact":
    				print '<td class="nobordernopadding nowrap" data-campo="sender" title="'.$typemail['firstname']." ".$typemail['lastname'].'">';
    				
    				if (version_compare(DOL_VERSION, '3.7.0') >= 0){
    					print '<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$typemail['id'].'" title="'.$typemail['firstname']." ".$typemail['lastname'].'">';
    				} else {
    					print '<a href="'.dol_buildpath('/contact/fiche.php',1).'?email='.$mail.'&lastname='.$name.'&action=create">';
    				}
    				print img_picto($langs->trans("ShowContact"),'object_contact.png').'</a> ';
					$contactstatic->id=$typemail['id'];
					print $typemail['firstname']." ".$typemail['lastname'];
    			break;
    				
    			case "User":		
    						
					print '<td class="nobordernopadding nowrap" data-campo="sender" title="'.$typemail['firstname']." ".$typemail['lastname'].'">';
					if (version_compare(DOL_VERSION, '3.7.0') >= 0){
						print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$typemail['id'].'">';
					}
					else{
						print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$typemail['id'].'">';
					}
    				print img_picto($langs->trans("ShowUser"),'object_user.png').'</a> ';
    				print $typemail['firstname']." ".$typemail['lastname'];
    				break;
				break;
				
    			default:
    				print "";
			}
		}
		else
		{
			$sender = strtr($objp->envia,array('&lt;'=>'<','&gt;'=>'>'));
			$initpos =strpos($objp->envia, "<");
			$endpos=strpos($objp->envia, ">");
			$endname=$initpos;
			$lenght= $endpos-$initpos-1;
			
			if($endname == false){
				$endname = strlen ($objp->envia);
				$lenght = $endname;
				$initpos = -1;
			}
			$mail = substr ( $objp->envia , $initpos+1, $lenght);
			$name = substr ($objp->envia , 0, $endname);
					
			print '<td class="nobordernopadding nowrap" data-campo="sender" title="'.$name.'">';
			
			if (version_compare(DOL_VERSION, '3.7.0') >= 0){
				print '<a href="'.dol_buildpath('/contact/card.php',1).'?email='.$mail.'&lastname='.$name.'&action=create">';
			} else {
				print '<a href="'.dol_buildpath('/contact/fiche.php',1).'?email='.$mail.'&lastname='.$name.'&action=create">';
			}
			print img_picto($langs->trans("CreateContact"),'vcard.png').'</a> ';
			print $name;
			
		}
		print '</td>';

		//destinatario
		$destinatario = str_replace(';', ',', $objp->destinatario);
		print '<td data-campo="to" title="'.$destinatario.'">'.$destinatario.'</td>';
		
		// Asunto	
		print '<td data-campo="topic" title="'.$objp->asunto.'">'.img_object('',email).' '.$objp->asunto.'</td>';

		// Date mail
		print '<td data-campo="date">';
		$timenow = dol_print_date(dol_now(),'day');
		$timemail = dol_print_date($db->jdate($objp->datec),'day');
		
		if($timenow == $timemail){
			
			print dol_print_date($db->jdate($objp->datec),'hour')."</td>\n";
			
		} else {
			
			print dol_print_date($db->jdate($objp->datec),'day')."</td>\n";
		}
		
		

		//Files
		print '<td data-campo="attachments">';
		if ($objp->files)
		{	
			print $objp->files;
		} else {
			print'&nbsp;';
		}
		print '</td>';

		// Estado
		
		$status=$objp->leido;
		
		if ($objp->state_spam)
			$status=3;
		elseif($objp->state_reply)
			$status=2;
		
		print '<td class="nowrap" data-campo="status">'.LibStatut($status).'</td>';
		
		print '<td data-campo="delete">';
		print '<input id="'.$i.'" class="flat checkformail" type="checkbox" name="toAction[]" value="'.$objp->rowid.'">';
	
		print '</td>' ;
		print '</tr>';

		
		$i++;
	}

	print '</tbody>';
	print '</table>';

	/*
 	* Boutons Actions
 	*/
	print '<div class="tabsAction">';
	if(!$deleteds){
		if(!$archiv && !$outbox){
			print '<button type="submit" class="button butAction" name="action" value="setunread">'.$langs->trans("SetasUnreads").'</button>';
			print '<button type="submit" class="button butAction" name="action" value="Archive">'.$langs->trans("Archive").'</button>';
		}
		if(!$outbox)print '<button type="submit" class="button butActionDelete" name="action" value="SetasSpam">'.$langs->trans("SetasSpam").'</button>';
		if ($user->rights->webmail->delete)
		{
			print '<button type="submit" class="button butActionDelete" name="action" value="deleteSelected">'.$langs->trans("DeleteSelected").'</button>';
		}
	} else {
		print '<button type="submit" class="button butActionDelete" name="action" value="restoreSelected">'.$langs->trans("RestoreSelected").'</button>';
		print '<button type="submit" class="button butActionDelete" name="action" value="deleteSelectedPermanent">'.$langs->trans("DeleteSelected").'</button>';
		print '<button type="submit" class="button butActionDelete" name="action" value="empty_Trash">'.$langs->trans("EmptyTrash").'</button>';
	}
	print '</div>';

	print '</form>';



	print '</div>';
	
	print '</div>';

	$db->free($resql);
	
