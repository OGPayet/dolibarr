<?php
/* Copyright (C) 2010-2011  Regis Houssin <regis.houssin@capnetworks.com>
 * Copyright (C) 2013       Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2014       Marcos García <marcosgdf@gmail.com>
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
 */

/**
 *  \file       htdocs/requestmanager/tpl/linkedobjectblock.tpl.php
 *  \ingroup    requestmanager
 *  \brief      Template to show objects linked to request manager
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf)) {
    print "Error, template page can't be called as URL";
    exit;
}

?>

<!-- BEGIN PHP TEMPLATE -->

<?php
if ($objecttype == 'equipement') {
    global $user;

    $langs = $GLOBALS['langs'];
    $db = $GLOBALS['db'];
    $linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

    $langs->load("equipement@equipement");

    require_once DOL_DOCUMENT_ROOT . "/product/class/product.class.php";
    $productlink = new Product($db);

    dol_include_once('/synergiestech/class/html.formsynergiestech.class.php');
    $formsynergiestech = new FormSynergiesTech($db);

    $total = 0;
    $ilink = 0;
    $var = true;
    foreach ($linkedObjectBlock as $key => $objectlink) {
        $ilink++;

        $productlink->fetch($objectlink->fk_product);
        $objectlink->fetch_optionals();

        $trclass = ($var ? 'pair' : 'impair');
        if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
            $trclass .= ' liste_sub_total';
        }
        ?>
      <tr class="<?php echo $trclass; ?>">
        <td><?php echo $langs->trans("Equipement"); ?></td>
        <td>
            <?php
            echo $objectlink->getNomUrl(1) . ' ' . $formsynergiestech->picto_equipment_has_contract($objectlink->id) . (!empty($objectlink->array_options['options_machineclient']) ? ' (M)' : '');
            ?>
        </td>
        <td><?php echo $productlink->label; ?></td>
        <td align="center"></td>
        <td align="center"><?php if (!empty($objectlink->array_options['options_machineclient'])&&!empty($objectlink->array_options['options_idtelem'])) {
            echo $objectlink->array_options['options_idtelem'];
} else {
    echo $productlink->getNomUrl(1);
} ?></td>
        <td align="right"><?php echo $objectlink->getLibStatut(3); ?></td>
        <td align="right"><a href="<?php echo $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=dellink&dellinkid=' . $key; ?>"><?php echo img_delete($langs->transnoentitiesnoconv("RemoveLink")); ?></a>
        </td>
      </tr>
        <?php
    }
} elseif ($objecttype == 'contrat') {
    global $user;
    global $noMoreLinkedObjectBlockAfter;

    $langs = $GLOBALS['langs'];
    $db = $GLOBALS['db'];
    $linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

    $langs->load("contracts");

    require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
    $extrafieldsobject = new ExtraFields($db);
    $extralabelsobject = $extrafieldsobject->fetch_name_optionals_label('contrat');

    $total = 0;
    $ilink = 0;
    $var = true;
    foreach ($linkedObjectBlock as $key => $objectlink) {
        $ilink++;
        $objectlink->fetch_optionals();

        $trclass = ($var ? 'pair' : 'impair');
        if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
            $trclass .= ' liste_sub_total';
        }
        ?>
      <tr class="<?php echo $trclass; ?>">
        <td><?php echo $langs->trans("Contract"); ?></td>
        <td><?php echo $objectlink->getNomUrl(1); ?></td>
        <td><?php echo $extrafieldsobject->showOutputField('formule', $objectlink->array_options['options_formule']); ?></td>
        <td align="center"><?php echo $extrafieldsobject->showOutputField('startdate', $objectlink->array_options['options_startdate']); ?></td>
        <td align="right"></td>
        <td align="right"><?php echo $objectlink->getLibStatut(7); ?></td>
        <td align="right"><a href="<?php echo $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=dellink&dellinkid=' . $key; ?>"><?php echo img_delete($langs->transnoentitiesnoconv("RemoveLink")); ?></a>
        </td>
      </tr>
        <?php
    }
} elseif ($objecttype == 'fichinter') {
    global $user;
    global $noMoreLinkedObjectBlockAfter;

    $langs = $GLOBALS['langs'];
    $linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

    $langs->load("interventions");

    $ilink = 0;
    $var = true;
    foreach ($linkedObjectBlock as $key => $objectlink) {
        $ilink++;

        $trclass = ($var ? 'pair' : 'impair');
        if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
            $trclass .= ' liste_sub_total';
        }
        ?>
      <tr class="<?php echo $trclass; ?>">
        <td><?php echo $langs->trans("Intervention"); ?></td>
        <td><?php echo $objectlink->getNomUrl(1); ?></td>
        <td><?php echo $objectlink->description; ?></td>
        <td align="center"><?php echo dol_print_date($objectlink->datev, 'day'); ?></td>
        <td></td>
        <td align="right"><?php echo $objectlink->getLibStatut(3); ?></td>
        <td align="right"><a
              href="<?php echo $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=dellink&dellinkid=' . $key; ?>"><?php echo img_delete($langs->transnoentitiesnoconv("RemoveLink")); ?></a>
        </td>
      </tr>
        <?php
    }
} elseif ($objecttype == 'propal') {
    global $user, $conf;

    $langs = $GLOBALS['langs'];
    $linkedObjectBlock = $GLOBALS['linkedObjectBlock'];

    // Load translation files required by the page
    $langs->load("propal");

    $linkedObjectBlock = dol_sort_array($linkedObjectBlock, 'date', 'desc', 0, 0, 1);

    $total = 0;
    $ilink = 0;
    foreach ($linkedObjectBlock as $key => $objectlink) {
        $ilink++;

        $trclass = 'oddeven';
        if ($ilink == count($linkedObjectBlock) && empty($noMoreLinkedObjectBlockAfter) && count($linkedObjectBlock) <= 1) {
            $trclass .= ' liste_sub_total';
        }
        print '<tr class="'.$trclass.'"  data-element="'.$objectlink->element.'"  data-id="'.$objectlink->id.'" >';
        print '<td class="linkedcol-element" >'.$langs->trans("Proposal");
        if (!empty($showImportButton) && $conf->global->MAIN_ENABLE_IMPORT_LINKED_OBJECT_LINES) {
            $url = DOL_URL_ROOT.'/comm/propal/card.php?id='.$objectlink->id;
            print '<a class="objectlinked_importbtn" href="'.$url.'&amp;action=selectlines"  data-element="'.$objectlink->element.'"  data-id="'.$objectlink->id.'"  > <i class="fa fa-indent"></i> </a>';
        }
        print '</td>';
        print '<td class="linkedcol-name nowraponall" >'.$objectlink->getNomUrl(1).'</td>';
        print '<td class="linkedcol-ref" >'.$objectlink->ref_client.'</td>';
        print '<td class="linkedcol-date center">'.dol_print_date($objectlink->date, 'day').'</td>';
		print '<td class="linkedcol-amount right">';
		 //-------------------------------------------------------------------------------
        // Modification - Open-DSI - Begin
        if ($user->rights->propale->lire && (!$conf->synergiestech->enabled || $user->rights->synergiestech->amount->customerpropal)) {
		// Modification - Open-DSI - End
        //-------------------------------------------------------------------------------
			$total = $total + $objectlink->total_ht;
            echo price($objectlink->total_ht);
        }
        print '</td>';
        print '<td class="linkedcol-statut right">'.$objectlink->getLibStatut(3).'</td>';
        print '<td class="linkedcol-action right"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=dellink&dellinkid='.$key.'">'.img_picto($langs->transnoentitiesnoconv("RemoveLink"), 'unlink').'</a></td>';
        print "</tr>\n";
    }
    if (count($linkedObjectBlock) > 1) {
        print '<tr class="liste_total '.(empty($noMoreLinkedObjectBlockAfter) ? 'liste_sub_total' : '').'">';
        print '<td>'.$langs->trans("Total").'</td>';
        print '<td></td>';
        print '<td class="center"></td>';
        print '<td class="center"></td>';
        print '<td class="right">'.price($total).'</td>';
        print '<td class="right"></td>';
        print '<td class="right"></td>';
        print "</tr>\n";
    }
} else {
    return 0;
}
?>

<!-- END PHP TEMPLATE -->
