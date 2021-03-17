<?php
/** Copyright (C) 2021  Alexis Laurier <contact@alexislaurier.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file        class/test.class.php
 * \ingroup     invoicebetterstatus
 * \brief       This file is a CRUD class file for Test (Create/Read/Update/Delete)
 */

class InvoiceBetterStatusTool {
	public static $invoiceStatusLabel = array(
		0=>'InvoiceBetterStatusDraft',
		1=>'InvoiceBetterStatusDraft',
		2=>'Paid',
		3=>'Abandonned');

		public function test() {
					// phpcs:enable
		global $langs;
		$langs->load('bills');

		if ($type == -1) $type = $this->type;

		$statusType = 'status0';
		$prefix = 'Short';
		if (!$paye) {
			if ($status == 0) {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusDraft');
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusDraft');
			} elseif (($status == 3 || $status == 2) && $alreadypaid <= 0) {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusClosedUnpaid');
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusClosedUnpaid');
				$statusType = 'status5';
			} elseif (($status == 3 || $status == 2) && $alreadypaid > 0) {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusClosedPaidPartially');
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusClosedPaidPartially');
				$statusType = 'status9';
			} elseif ($alreadypaid == 0) {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusNotPaid');
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusNotPaid');
				$statusType = 'status1';
			} else {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusStarted');
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusStarted');
				$statusType = 'status3';
			}
		} else {
			$statusType = 'status6';

			if ($type == self::TYPE_CREDIT_NOTE) {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusPaidBackOrConverted'); // credit note
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusPaidBackOrConverted'); // credit note
			} elseif ($type == self::TYPE_DEPOSIT) {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusConverted'); // deposit invoice
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusConverted'); // deposit invoice
			} else {
				$labelStatus = $langs->transnoentitiesnoconv('BillStatusPaid');
				$labelStatusShort = $langs->transnoentitiesnoconv('Bill'.$prefix.'StatusPaid');
			}
		}

		return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode);
		}
}
