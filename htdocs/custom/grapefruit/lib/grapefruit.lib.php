<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
 *	\file		lib/grapefruit.lib.php
 *	\ingroup	grapefruit
 *	\brief		This file is an example module library
 *				Put some comments here
 */

function grapefruitAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("grapefruit@grapefruit");

    $h = 0;
    $head = array();

    $head[$h][0] = dol_buildpath("/grapefruit/admin/grapefruit_setup.php", 1);
    $head[$h][1] = $langs->trans("Parameters");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = dol_buildpath("/grapefruit/admin/grapefruit_about.php", 1);
    $head[$h][1] = $langs->trans("About");
    $head[$h][2] = 'about';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //	'entity:+tabname:Title:@grapefruit:/grapefruit/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //	'entity:-tabname:Title:@grapefruit:/grapefruit/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'grapefruit');

    return $head;
}

function addPuHtRemise($nbcolumn, &$object) {
    global $langs, $conf;

    if($conf->subtotal->enabled && ! class_exists('TSubtotal')) dol_include_once('/subtotal/class/subtotal.class.php');

    ?>
        <script type="text/javascript">
            $(document).ready(function(){
                $('#tablelines tr:not([rel=subtotal]) td:nth-child(<?php echo $nbcolumn; ?>)').each(function() {
                    let parent_tr_class = $(this).parent('tr').attr('class');

                    let classname = 'pu_ht_remise';
                    if(parent_tr_class.indexOf('liste_titre_create') !== -1) classname += ' nobottom';

                    let td = '<td align="right" class="'+classname+'" width=80></td>';
                    $(this).after(td);
                });

                $('tr[rel=subtotal]').each(function() {
                    let first_td = $(this).children().first();
                    first_td.attr('colspan', parseInt(first_td.attr('colspan')) + 1);
                });

                $('#trlinefordates td:first').after('<td align="right" class="pu_ht_remise" width=80></td>'); // Add empty column in objectline_create
                    // Ajout des libellé de colonne
                $('#tablelines .liste_titre > td.pu_ht_remise').first().html('<?php echo $langs->trans('DiscountUHT'); ?>');
                    // Ajout des prix devisé sur les lignes

                <?php
                if(!empty($object->lines)) {
				foreach($object->lines as $line){
						if($line->rowid) $line->id = $line->rowid;
                        if($conf->subtotal->enabled && TSubtotal::isModSubtotalLine($line)) continue;

						echo "$('#row-".$line->id." td.pu_ht_remise').html('".price($line->subprice*(1-$line->remise_percent/100),0,'',1,$conf->global->MAIN_MAX_DECIMALS_TOT,$conf->global->MAIN_MAX_DECIMALS_TOT)."');";
                        if($line->error != '') echo "alert('".$line->error."');";
                    }
                }
                ?>
            });
        </script>
    <?php
}

function grapefruitGetTasksForProject($name='fk_task', $socid=-1, $showempty=1, $projectid=0)
{
	global $db;

	dol_include_once('/core/class/html.form.class.php');

	$form = new Form($db);
	$TTask = getTaskByProjectId($projectid);

	$return=array();
	$return['value'] = $form->selectarray('fk_task', $TTask, '', 1, 0, 0, '', 0, 0, 0, '', 'minwidth100 maxwidth300', 1);

	echo json_encode($return);
}

function getTaskByProjectId($projectid=0)
{
	global $db,$conf;

	$TRes = array();

	if (empty($projectid)) return $TRes;

	$sql = 'SELECT t.rowid, t.ref as tref, t.label as tlabel, p.ref, p.title, p.fk_soc, p.fk_statut, p.public,';
	$sql.= ' s.nom as name';
	$sql.= ' FROM '.MAIN_DB_PREFIX .'projet as p';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'societe as s ON s.rowid = p.fk_soc';
	$sql.= ', '.MAIN_DB_PREFIX.'projet_task as t';
	$sql.= " WHERE p.entity = ".$conf->entity;
	$sql.= " AND t.fk_projet = p.rowid";
	$sql.= " AND p.rowid = ".$projectid;
	//if ($socid == 0) $sql.= " AND (p.fk_soc=0 OR p.fk_soc IS NULL)";
	//if ($socid > 0)  $sql.= " AND (p.fk_soc=".$socid." OR p.fk_soc IS NULL)";
	$sql.= " ORDER BY p.ref, t.ref ASC";

	$resql=$db->query($sql);
	if ($resql)
	{
		while ($line = $db->fetch_object($resql))
		{
			$TRes[$line->rowid] = $line->tref.' '.$line->tlabel;
		}
	}

	return $TRes;
}
