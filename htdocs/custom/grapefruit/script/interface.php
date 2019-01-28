<?php
	require '../config.php';

	dol_include_once('/grapefruit/lib/grapefruit.lib.php');

	$langs->load("agenda");
	$langs->load("other");

	$get=GETPOST('get');
	$set=GETPOST('set');




	switch ($get) {
		case 'fullcalandar_tasks':
			$projectid = GETPOST('projectid');
			return grapefruitGetTasksForProject('fk_task', -1, 0, $projectid);

			break;

		default:
			break;
	}

	switch ($set) {
		case 'defaultTVA':

			_updateTVA(GETPOST('element'), GETPOST('element_id'), GETPOST('default_tva'));

			break;

		case 'defaultProgress':

			_updateProgress(GETPOST('element_id'), GETPOST('default_progress'));

			break;
		default:
			break;
	}



function _updateTVA($element, $element_id, $default_tva)
{
	global $langs,$conf,$db;

	if (!empty($conf->global->GRAPEFRUIT_DEFAULT_TVA_ON_DOCUMENT_CLIENT_ENABLED) && $default_tva != '' && in_array($element, array('propal', 'commande', 'facture')))
	{
		dol_include_once('/comm/propal/class/propal.class.php');
		dol_include_once('/commande/class/commande.class.php');
		dol_include_once('/compta/facture/class/facture.class.php');
		if (!empty($conf->subtotal->enabled)) dol_include_once('/subtotal/class/subtotal.class.php');

		$langs->load('grapefruit@grapefruit');

		$classname = ucfirst($element);

		$object = new $classname($db);
		$object->fetch($element_id);

		if ($object->statut != $classname::STATUS_DRAFT) return 0;

		$object->db->begin();
		$res = 1;
		foreach ($object->lines as &$l)
		{
			if (!empty($conf->subtotal->enabled) && (TSubtotal::isTitle($l) || TSubtotal::isSubtotal($l)) ) continue;

			switch ($object->element) {
				case 'propal':
					//$rowid, $pu, $qty, $remise_percent, $txtva, $txlocaltax1=0.0, $txlocaltax2=0.0, $desc='', $price_base_type='HT', $info_bits=0, $special_code=0, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=0, $pa_ht=0, $label='', $type=0, $date_start='', $date_end='', $array_options=0, $fk_unit=null
					$res = $object->updateline($l->id, $l->subprice, $l->qty, $l->remise_percent, $default_tva, $l->localtax1_tx, $l->localtax1_tx, $l->desc, 'HT', $l->info_bits, $l->special_code, $l->fk_parent_line, 0, $l->fk_fournprice, $l->pa_ht, '', $l->product_type, $l->date_start, $l->date_end, $l->array_options, $l->fk_unit);
					break;
				case 'commande':
					//$rowid, $desc, $pu, $qty, $remise_percent, $txtva, $txlocaltax1=0.0,$txlocaltax2=0.0, $price_base_type='HT', $info_bits=0, $date_start='', $date_end='', $type=0, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=null, $pa_ht=0, $label='', $special_code=0, $array_options=0, $fk_unit=null
					$res = $object->updateline($l->id, $l->desc, $l->subprice, $l->qty, $l->remise_percent, $default_tva, $l->localtax1_tx, $l->localtax2_tx, 'HT', $l->info_bits, $l->date_start, $l->date_end, $l->product_type, $l->fk_parent_line, 0, $l->fk_fournprice, $l->pa_ht, '', $l->special_code, $l->array_options, $l->fk_unit);
					break;
				case 'facture':
					//$rowid, $desc, $pu, $qty, $remise_percent, $date_start, $date_end, $txtva, $txlocaltax1=0, $txlocaltax2=0, $price_base_type='HT', $info_bits=0, $type= self::TYPE_STANDARD, $fk_parent_line=0, $skip_update_total=0, $fk_fournprice=null, $pa_ht=0, $label='', $special_code=0, $array_options=0, $situation_percent=0, $fk_unit = null
					$res = $object->updateline($l->id, $l->desc, $l->subprice, $l->qty, $l->remise_percent, $l->date_start, $l->date_end, $default_tva, $l->localtax1_tx, $l->localtax2_tx, 'HT', $l->info_bits, $l->product_type, $l->fk_parent_line, 0, $l->fk_fournprice, $l->pa_ht, '', $l->special_code, $l->array_options, $l->situation_percent, $l->fk_unit);
					break;
			}

			if ($res <= 0) break;
		}

		if ($res <= 0)
		{
			$object->db->rollback();
			setEventMessage($langs->trans('grapefruit_error_tva_updateline'), 'errors');
			return -2;
		}
		else
		{
			$object->db->commit();
			setEventMessage($langs->trans('grapefruit_success_tva_updateline'));
			return 1;
		}

	}
}

function _updateProgress($element_id, $default_progress)
{
	global $langs,$conf,$db;

	if (!empty($conf->global->GRAPEFRUIT_SITUATION_INVOICE_DEFAULT_PROGRESS) && is_numeric($default_progress))
	{
		dol_include_once('/compta/facture/class/facture.class.php');
		if (!empty($conf->subtotal->enabled)) dol_include_once('/subtotal/class/subtotal.class.php');

		$object = new Facture($db);
		$object->fetch($element_id);

		if ($object->statut != Facture::STATUS_DRAFT) return 0;

		$res=1;
		$nb_progress_updated = $nb_progress_not_updated = 0;
		foreach ($object->lines as &$l)
		{
			if (!empty($conf->subtotal->enabled) && (TSubtotal::isTitle($l) || TSubtotal::isSubtotal($l)) ) continue;

			$prev_percent = $l->get_prev_progress($object->id);
			if ($default_progress >= $prev_percent)
			{
				$res = $object->updateline($l->id, $l->desc, $l->subprice, $l->qty, $l->remise_percent, $l->date_start, $l->date_end, $l->tva_tx, $l->localtax1_tx, $l->localtax2_tx, 'HT', $l->info_bits, $l->product_type, $l->fk_parent_line, 0, $l->fk_fournprice, $l->pa_ht, '', $l->special_code, $l->array_options, $default_progress, $l->fk_unit);
				if ($res > 0) $nb_progress_updated++;
				else $nb_progress_not_updated++;
			}
			else $nb_progress_not_updated++;
		}

		if ($nb_progress_updated > 0) setEventMessage($langs->trans('grapefruit_nb_progress_updated', $nb_progress_updated));
		if ($nb_progress_not_updated > 0) setEventMessage($langs->trans('grapefruit_nb_progress_not_updated', $nb_progress_not_updated), 'warnings');
	}
}