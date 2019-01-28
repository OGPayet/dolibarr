<?php
/* Copyright (C) 2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014 Juanjo Menent	      <jmenent@2byte.es>
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
 *  \file       fourn/class/fournisseur.commande.dispatch.class.php
 *  \ingroup    fournisseur stock
 *  \brief      This file is an example for a CRUD class file (Create/Read/Update/Delete)
 *				Initialy built by build_class_from_table on 2015-02-24 10:38
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.dispatch.class.php';
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
//require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");


/**
 *	Class to manage table commandefournisseurdispatch
 */
class CommandeFournisseurDispatchATM extends CommandeFournisseurDispatch
{
	/**
	 * Load object in memory from the database
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int    $limit     offset limit
	 * @param int    $offset    offset limit
	 * @param array  $filter    filter array
	 * @param string $filtermode filter mode (AND or OR)
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder='', $sortfield='', $limit=0, $offset=0, array $filter = array(), $filtermode='AND')
	{
		dol_syslog(__METHOD__, LOG_DEBUG);

		$sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.fk_commande,";
		$sql.= " t.fk_product,";
		$sql.= " t.fk_commandefourndet,";
		$sql.= " t.qty,";
		$sql.= " t.fk_entrepot,";
		$sql.= " t.fk_user,";
		$sql.= " t.datec,";
		$sql.= " t.comment,";
		$sql.= " t.status,";
		$sql.= " t.tms,";
		$sql.= " t.batch,";
		$sql.= " t.eatby,";
		$sql.= " t.sellby";

        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";

		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key=='t.comment') {
					$sqlwhere [] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				} elseif ($key=='t.datec' || $key=='t.tms' || $key=='t.eatby' || $key=='t.sellby' || $key=='t.batch') {
					$sqlwhere [] = $key . ' = \'' . $this->db->escape($value) . '\'';
				} else {
					$sqlwhere [] = $key . ' = ' . $this->db->escape($value);
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' WHERE ' . implode(' '.$filtermode.' ', $sqlwhere);
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield,$sortorder);
		}
		if (!empty($limit)) {
			$sql .=  ' ' . $this->db->plimit($limit + 1, $offset);
		}
		$this->lines = array();

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$line = new self($this->db);

				$line->id    = $obj->rowid;

				$line->fk_commande = $obj->fk_commande;
				$line->fk_product = $obj->fk_product;
				$line->fk_commandefourndet = $obj->fk_commandefourndet;
				$line->qty = $obj->qty;
				$line->fk_entrepot = $obj->fk_entrepot;
				$line->fk_user = $obj->fk_user;
				$line->datec = $this->db->jdate($obj->datec);
				$line->comment = $obj->comment;
				$line->status = $obj->status;
				$line->tms = $this->db->jdate($obj->tms);
				$line->batch = $obj->batch;
				$line->eatby = $this->db->jdate($obj->eatby);
				$line->sellby = $this->db->jdate($obj->sellby);

				$this->lines[$line->id] = $line;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . implode(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}

}
