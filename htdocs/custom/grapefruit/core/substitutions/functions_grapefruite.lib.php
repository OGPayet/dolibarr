<?php

/**
 * Function de substitution de clé custom
 * Actuellement disponible en standard @see tooltip dans la configuration du module facture:
 *     - __MYCOMPANY_NAME__
 *     - __MYCOMPANY_EMAIL__
 *     - __MYCOMPANY_PROFID1__
 *     - __MYCOMPANY_PROFID2__
 *     - __MYCOMPANY_PROFID3__
 *     - __MYCOMPANY_PROFID4__
 *     - __MYCOMPANY_PROFID5__
 *     - __MYCOMPANY_PROFID6__
 *     - __MYCOMPANY_CAPITAL__
 *     - __MYCOMPANY_COUNTRY_ID__
 *     - __TOTAL_TTC__
 *     - __TOTAL_HT__
 *     - __TOTAL_VAT__
 *     - __AMOUNT__
 *     - __AMOUNT_WO_TAX__
 *     - __AMOUNT_VAT__
 *     - __DAY__
 *     - __MONTH__
 *     - __YEAR__
 *     - __PREVIOUS_DAY__
 *     - __PREVIOUS_MONTH__
 *     - __PREVIOUS_YEAR__
 *     - __NEXT_DAY__
 *     - __NEXT_MONTH__
 *     - __NEXT_YEAR__
 *     - __USER_ID__
 *     - __USER_LOGIN__
 *     - __USER_LASTNAME__
 *     - __USER_FIRSTNAME__
 *     - __USER_FULLNAME__
 *     - __USER_SUPERVISOR_ID__
 *     - __FROM_NAME__
 *     - __FROM_EMAIL__
 *     - __EXTRA_mode_transport__
 *     - %EXTRA_mode_transport%
 *     - __EXTRA_reason__
 *     - %EXTRA_reason%
 *     - __EXTRA_grapefruitReminderBill__
 *     - %EXTRA_grapefruitReminderBill%
 *
 * @param array $substitutionarray
 * @param type $outputlangs
 * @param type $object
 * @param type $parameters
 */
function grapefruite_completesubstitutionarray(&$substitutionarray,$outputlangs,$object,$parameters)
{
	global $conf;

	if (!empty($object))
	{
		// Petit bonus
		foreach ($object as $attr => &$value)
		{
			if (!is_object($value) && !is_array($value))
			{
				$substitutionarray['__OBJECT_'.strtoupper($attr).'__'] = $value;
			}
		}

		$total_remise_dispo = 0;
		$total_avoir_dispo = 0;
		$total_facture_impayee = 0;
		$total_facture_impayee_only_pos = 0;

		if (method_exists($object, 'fetch_thirdparty')) $object->fetch_thirdparty();
		$societe = !empty($object->client) ? $object->client : $object->thirdparty;

		if (!empty($societe))
		{
			if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {	// Never use this
				$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
				$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
			} else {
				$filterabsolutediscount = "fk_facture_source IS NULL OR (fk_facture_source IS NOT NULL AND (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%'))";
				$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
			}

			$total_remise_dispo = $societe->getAvailableDiscounts('', $filterabsolutediscount);
			$total_avoir_dispo = $societe->getAvailableDiscounts('', $filtercreditnote);

			$tmp = $societe->getOutstandingBills();
			$total_facture_impayee = $tmp['opened']; // si négatif alors c'est un client à qui on doit de l'argent
			if ($total_facture_impayee > 0) $total_facture_impayee_only_pos = $total_facture_impayee;
//			$outstandingTotal=$tmp['total_ht'];
//			$outstandingTotalIncTax=$tmp['total_ttc'];
		}

		if (!empty($conf->global->GRAPEFRUIT_SUBSTITUTION_USE_CURRENCY_SYMBOL))
		{
			$substitutionarray['__CLIENT_TOTAL_REMISE_DISPO__'] = price($total_remise_dispo, 0, $outputlangs, 1, -1, -1, $conf->currency);
			$substitutionarray['__CLIENT_TOTAL_AVOIR_DISPO__'] = price($total_avoir_dispo, 0, $outputlangs, 1, -1, -1, $conf->currency);
			$substitutionarray['__CLIENT_TOTAL_FACTURE_IMPAYEE__'] = price($total_facture_impayee, 0, $outputlangs, 1, -1, -1, $conf->currency);
			$substitutionarray['__CLIENT_TOTAL_FACTURE_IMPAYEE_ONLY_POS__'] = price($total_facture_impayee_only_pos, 0, $outputlangs, 1, -1, -1, $conf->currency);
		}
		else
		{
			$substitutionarray['__CLIENT_TOTAL_REMISE_DISPO__'] = price($total_remise_dispo).' '.$outputlangs->transnoentities("Currency" . $conf->currency);
			$substitutionarray['__CLIENT_TOTAL_AVOIR_DISPO__'] = price($total_avoir_dispo).' '.$outputlangs->transnoentities("Currency" . $conf->currency);
			$substitutionarray['__CLIENT_TOTAL_FACTURE_IMPAYEE__'] = price($total_facture_impayee).' '.$outputlangs->transnoentities("Currency" . $conf->currency);
			$substitutionarray['__CLIENT_TOTAL_FACTURE_IMPAYEE_ONLY_POS__'] = price($total_facture_impayee_only_pos).' '.$outputlangs->transnoentities("Currency" . $conf->currency);
		}
	}
}