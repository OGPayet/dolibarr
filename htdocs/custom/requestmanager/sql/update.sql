-- ============================================================================
-- Copyright (C) 2018	 Open-DSI 	 <support@open-dsi.fr>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program. If not, see <http://www.gnu.org/licenses/>.
-- ===========================================================================

ALTER TABLE `llx_requestmanager` ADD `total_ht`					        double(24,8) DEFAULT 0 COMMENT 'montant total ht apres remise globale';
ALTER TABLE `llx_requestmanager` ADD `tva`						          double(24,8) DEFAULT 0 COMMENT 'montant total tva apres remise globale';
ALTER TABLE `llx_requestmanager` ADD `localtax1`				        double(24,8) DEFAULT 0 COMMENT 'amount total localtax1';
ALTER TABLE `llx_requestmanager` ADD `localtax2`				        double(24,8) DEFAULT 0 COMMENT 'amount total localtax2';
ALTER TABLE `llx_requestmanager` ADD `total_ttc`			        	double(24,8) DEFAULT 0 COMMENT 'montant total ttc apres remise globale';
ALTER TABLE `llx_requestmanager` ADD `fk_multicurrency` 		    integer;
ALTER TABLE `llx_requestmanager` ADD `multicurrency_code` 		  varchar(255);
ALTER TABLE `llx_requestmanager` ADD `multicurrency_tx` 		    double(24,8) DEFAULT 1;
ALTER TABLE `llx_requestmanager` ADD `multicurrency_total_ht`	  double(24,8) DEFAULT 0;
ALTER TABLE `llx_requestmanager` ADD `multicurrency_total_tva`	double(24,8) DEFAULT 0;
ALTER TABLE `llx_requestmanager` ADD `multicurrency_total_ttc`	double(24,8) DEFAULT 0;
