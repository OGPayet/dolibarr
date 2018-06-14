<?php


/**
	@brief Brief construct html table section, type of link vs qualityreport
	@note
		Require $formfile declared
*/


function DrawLink( $object
									, $key
									, $element
									, $classname
									, $tablename
									, $datefieldname
									, $langtoload
									, $title
									)
	{

		global $langs, $conf, $user, $formfile;



		print load_fiche_titre($langs->trans($title), $addform, '');

		print "\n".'<!-- Table for tablename = '.$tablename.' -->'."\n";
		print '<table class="noborder" width="100%">';

		print '<tr class="liste_titre">';
		// Remove link
		print '<td style="width: 24px"></td>';
		// Ref
		print '<td'.(($tablename != 'actioncomm' /*&& $tablename != 'taskin'*/) ? ' style="width: 200px"':'').'>'.$langs->trans("Ref").'</td>';
		// Date
		print '<td'.(($tablename != 'actioncomm' /*&& $tablename != 'taskin'*/) ? ' style="width: 200px"':'').' align="center">';
		if (! in_array($tablename, array('taskin'))) print $langs->trans("Date");
		print '</td>';
		// Thirdparty or user
		print '<td>';
		if (in_array($tablename, array('taskin')) && $key == 'taskin') print '';		// if $key == 'taskin', we don't want details per user
		elseif (in_array($tablename, array('expensereport_det','don','taskin'))) print $langs->trans("User");
		else print $langs->trans("ThirdParty");
		print '</td>';
		// Amount HT
		//if (empty($value['disableamount']) && ! in_array($tablename, array('taskin'))) print '<td align="right" width="120">'.$langs->trans("AmountHT").'</td>';
		//elseif (empty($value['disableamount']) && in_array($tablename, array('taskin'))) print '<td align="right" width="120">'.$langs->trans("Amount").'</td>';
		if (empty($value['disableamount'])) print '<td align="right" width="120">'.$langs->trans("AmountHT").'</td>';
		else print '<td width="120"></td>';
		// Amount TTC
		//if (empty($value['disableamount']) && ! in_array($tablename, array('taskin'))) print '<td align="right" width="120">'.$langs->trans("AmountTTC").'</td>';
		if (empty($value['disableamount'])) print '<td align="right" width="120">'.$langs->trans("AmountTTC").'</td>';
		else print '<td width="120"></td>';
		// Status
		if (in_array($tablename, array('taskin'))) print '<td align="right" width="200">'.$langs->trans("ProgressDeclared").'</td>';
		else print '<td align="right" width="200">'.$langs->trans("Status").'</td>';
		print '</tr>';

		$object->id = $element->id;
		$elementarray = $object->get_element_list('qualityreport', $key, $datefieldname, $dates, $datee);
		if (is_array($elementarray) && count($elementarray)>0)
		{
			$var=true;
			$total_ht = 0;
			$total_ttc = 0;

			$total_ht_by_third = 0;
			$total_ttc_by_third = 0;

			$saved_third_id = 0;
			$breakline = '';

// 			if (canApplySubtotalOn($tablename))
// 			{
// 			   // Sort
// 			   $elementarray = sortElementsByClientName($elementarray);
// 			}

			$num=count($elementarray);
			for ($i = 0; $i < $num; $i++)
			{
				$tmp=explode('_',$elementarray[$i]);
				$idofelement=$tmp[0];
				$idofelementuser=$tmp[1];

				$object->fetch($idofelement);


// 				if ($idofelementuser) $elementuser->fetch($idofelementuser);

// 				if ($tablename != 'expensereport_det')
// 				{
// 					$element->fetch_thirdparty();
// 				}
// 				else
// 				{
// 					$expensereport=new ExpenseReport($db);
// 					$expensereport->fetch($element->fk_expensereport);
// 				}

				//print 'xxx'.$tablename;
// 				print $classname;

				if ($breakline && $saved_third_id != $object->thirdparty->id)
				{
					print $breakline;
					$var = true;

					$saved_third_id = $object->thirdparty->id;
					$breakline = '';

					$total_ht_by_third=0;
					$total_ttc_by_third=0;
				}
				$saved_third_id = $object->thirdparty->id;

				$qualifiedfortotal=true;
				if ($key == 'invoice')
				{
					if (! empty($object->close_code) && $object->close_code == 'replaced') $qualifiedfortotal=false;	// Replacement invoice, do not include into total
				}

				$var=!$var;
				print "<tr ".$bc[$var].">";
				// Remove link
				print '<td style="width: 24px">';
				if ($tablename != 'taskin')
				{
					print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $qualityreportid . '&action=unlink&tablename=' . $tablename . '&elementselect=' . $element->id . '">' . img_picto($langs->trans('Unlink'), 'editdelete') . '</a>';
				}
				print "</td>\n";
				// Ref
				print '<td align="left">';

				if ($tablename == 'expensereport_det')
				{
					print $expensereport->getNomUrl(1);
				}
				else
				{
					if ($element instanceof Taskin)
					{
						print $element->getNomUrl(1,'withqualityreport','time');
						print ' - '.dol_trunc($element->label, 48);
					}
					else

					print $object->getNomUrl(1);

					$element_doc = $object->element;
					$filename=dol_sanitizeFileName($object->ref);
					$filedir=$conf->{$element_doc}->dir_output . '/' . dol_sanitizeFileName($object->ref);

// 					if($element_doc === 'order_supplier') {
// 						$element_doc='commande_fournisseur';
// 						$filedir = $conf->fournisseur->commande->dir_output.'/'.dol_sanitizeFileName($element->ref);
// 					}
// 					else if($element_doc === 'invoice_supplier') {
// 						$element_doc='facture_fournisseur';
// 						$filename = get_exdir($element->id,2,0,0,$this,'product').dol_sanitizeFileName($element->ref);
// 						$filedir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($element->id,2,0,0,null,'invoice_supplier').dol_sanitizeFileName($element->ref);
// 					}
//
// 					print $formfile->getDocumentsLink($element_doc, $filename, $filedir);
//
// 					// Show supplier ref
// 					if (! empty($element->ref_supplier)) print ' - '.$element->ref_supplier;
// 					// Show customer ref
// 					if (! empty($element->ref_customer)) print ' - '.$element->ref_customer;
				}

				print "</td>\n";

				// Date
				$date='';
				if ($tablename == 'expensereport_det') $date = $object->date;      // No draft status on lines
				elseif (! empty($object->status) || ! empty($object->statut) || ! empty($object->fk_status))
				{
				    if ($tablename=='don') $date = $object->datedon;
				    if ($tablename == 'commande_fournisseur' || $tablename == 'supplier_order')
				{
				    $date=($object->date_commande?$object->date_commande:$object->date_valid);
				}
				elseif ($tablename == 'supplier_proposal') $date=$object->date_validation; // There is no other date for this
				elseif ($tablename == 'fichinter') $date=$object->datev; // There is no other date for this
				elseif ($tablename == 'taskin') $date='';	// We show no date. Showing date of beginning of task make user think it is date of time consumed
				else
				{
					$date=$object->date;                              // invoice, ...
					if (empty($date)) $date=$object->date_contrat;
					if (empty($date)) $date=$object->datev;
				}
				}
				print '<td align="center">';
				if ($tablename == 'actioncomm')
				{
				    print dol_print_date($object->datep,'dayhour');
				    if ($object->datef && $object->datef > $object->datep) print " - ".dol_print_date($object->datef,'dayhour');
				}
				else print dol_print_date($date,'day');
				print '</td>';

				// Third party or user
                print '<td align="left">';
                if (is_object($object->thirdparty))
									print $object->thirdparty->getNomUrl(1,'',48);
//                 else if ($tablename == 'expensereport_det')
//                 {
//                 	$tmpuser=new User($db);
//                 	$tmpuser->fetch($expensereport->fk_user_author);
//                 	print $tmpuser->getNomUrl(1,'',48);
//                 }
// 				else if ($tablename == 'don')
//                 {
//                 	if ($object->fk_user_author > 0)
//                 	{
// 	                	$tmpuser2=new User($db);
// 	                	$tmpuser2->fetch($object->fk_user_author);
// 	                	print $tmpuser2->getNomUrl(1,'',48);
//                 	}
//                 }
//                 else if ($tablename == 'taskin' && $key == 'taskin_time')	// if $key == 'taskin', we don't want details per user
//                 {
//                 	print $objectuser->getNomUrl(1);
//                 }
				print '</td>';

                // Amount without tax
				$warning='';
				if (empty($value['disableamount']))
				{
				    $total_ht_by_line=null;
				    $othermessage='';
					if ($tablename == 'don') $total_ht_by_line=$object->amount;
					elseif ($tablename == 'taskin')
					{
					    if (! empty($conf->salaries->enabled))
					    {
					        // TODO Permission to read daily rate
					    $tmp = $object->getSumOfAmount($objectuser, $dates, $datee);	// $object is a task. $objectuser may be empty
						$total_ht_by_line = price2num($tmp['amount'],'MT');
						if ($tmp['nblinesnull'] > 0)
						{
							$langs->load("errors");
							$warning=$langs->trans("WarningSomeLinesWithNullHourlyRate", $conf->currency);
						}
					    }
					    else
					    {
					        $othermessage=$form->textwithpicto($langs->trans("NotAvailable"), $langs->trans("ModuleSalaryToDefineHourlyRateMustBeEnabled"));
					    }
					}
					else
					{
						$total_ht_by_line=$object->total_ht;
					}
					print '<td align="right">';
					if ($othermessage) print $othermessage;
					if (isset($total_ht_by_line))
					{
					   if (! $qualifiedfortotal) print '<strike>';
					   print price($total_ht_by_line);
					   if (! $qualifiedfortotal) print '</strike>';
					}
					if ($warning) print ' '.img_warning($warning);
					print '</td>';
				}
				else print '<td></td>';

                // Amount inc tax
				if (empty($value['disableamount']))
				{
				    $total_ttc_by_line=null;
					if ($tablename == 'don') $total_ttc_by_line=$object->amount;
					elseif ($tablename == 'taskin')
					{
					    if (! empty($conf->salaries->enabled))
					    {
					        // TODO Permission to read daily rate
						$defaultvat = get_default_tva($mysoc, $mysoc);
						$total_ttc_by_line = price2num($total_ht_by_line * (1 + ($defaultvat / 100)),'MT');
					    }
					    else
					    {
					        $othermessage=$form->textwithpicto($langs->trans("NotAvailable"), $langs->trans("ModuleSalaryToDefineHourlyRateMustBeEnabled"));
					    }
					}
					else
					{
						$total_ttc_by_line=$object->total_ttc;
					}
					print '<td align="right">';
					if ($othermessage) print $othermessage;
					if (isset($total_ttc_by_line))
					{
					   if (! $qualifiedfortotal) print '<strike>';
					   print price($total_ttc_by_line);
					   if (! $qualifiedfortotal) print '</strike>';
					}
					if ($warning) print ' '.img_warning($warning);
					print '</td>';
				}
				else print '<td></td>';

				// Status
				print '<td align="right">';
// 				if ($tablename == 'expensereport_det')
// 				{
// 					print $expensereport->getLibStatut(5);
// 				}
// 				else if ($element instanceof CommonInvoice)
// 				{
// 					//This applies for Facture and FactureFournisseur
// 					print $element->getLibStatut(5, $element->getSommePaiement());
// 				}
// 				else if ($element instanceof Task)
// 				{
// 					if ($element->progress != '')
// 					{
// 						print $element->progress.' %';
// 					}
// 				}
// 				else
// 				{
					print $object->getLibStatut(5);
// 				}
				print '</td>';

				print '</tr>';

// 				if ($qualifiedfortotal)
// 				{
// 					$total_ht = $total_ht + $total_ht_by_line;
// 					$total_ttc = $total_ttc + $total_ttc_by_line;
//
// 					$total_ht_by_third += $total_ht_by_line;
// 					$total_ttc_by_third += $total_ttc_by_line;
// 				}
//
// 				if (canApplySubtotalOn($tablename))
// 				{
// 					$breakline='<tr class="liste_total liste_sub_total">';
// 					$breakline.='<td colspan="2">';
// 					$breakline.='</td>';
// 					$breakline.='<td>';
// 					$breakline.='</td>';
// 					$breakline.='<td class="right">';
// 					$breakline.=$langs->trans('SubTotal').' : ';
// 					if (is_object($element->thirdparty)) $breakline.=$element->thirdparty->getNomUrl(0,'',48);
// 					$breakline.='</td>';
// 					$breakline.='<td align="right">'.price($total_ht_by_third).'</td>';
// 					$breakline.='<td align="right">'.price($total_ttc_by_third).'</td>';
// 					$breakline.='<td></td>';
// 					$breakline.='</tr>';
// 				}

				//var_dump($element->thirdparty->name.' - '.$saved_third_id.' - '.$element->thirdparty->id);
			}

			if ($breakline) print $breakline;

			// Total
			print '<tr class="liste_total"><td colspan="4">'.$langs->trans("Number").': '.$i.'</td>';
			//if (empty($value['disableamount']) && ! in_array($tablename, array('taskin'))) print '<td align="right" width="100">'.$langs->trans("TotalHT").' : '.price($total_ht).'</td>';
			//elseif (empty($value['disableamount']) && in_array($tablename, array('taskin'))) print '<td align="right" width="100">'.$langs->trans("Total").' : '.price($total_ht).'</td>';
			print '<td align="right">';
			if (empty($value['disableamount']))
			{
			    if (! empty($conf->salaries->enabled)) print ''.$langs->trans("TotalHT").' : '.price($total_ht);
			}
			print '</td>';
			//if (empty($value['disableamount']) && ! in_array($tablename, array('taskin'))) print '<td align="right" width="100">'.$langs->trans("TotalTTC").' : '.price($total_ttc).'</td>';
			//elseif (empty($value['disableamount']) && in_array($tablename, array('taskin'))) print '<td align="right" width="100"></td>';
			print '<td align="right">';
			if (empty($value['disableamount']))
			{

			    if (! empty($conf->salaries->enabled)) print $langs->trans("TotalTTC").' : '.price($total_ttc);
			}
			print '</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		else // error
		{
			print $elementarray;
		}
		print "</table>";
		print "<br>\n";
}
