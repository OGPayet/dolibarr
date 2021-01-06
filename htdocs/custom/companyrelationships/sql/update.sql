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
--
-- ===========================================================================

ALTER TABLE `llx_companyrelationships` DROP FOREIGN KEY `fk_companyrelationships_fk_soc_benefactor`;
ALTER TABLE `llx_companyrelationships` CHANGE `fk_soc_benefactor` `fk_soc_benefactor` INT DEFAULT 0 NOT NULL;

ALTER TABLE `llx_companyrelationships` ADD `fk_soc_watcher` INT DEFAULT 0 NOT NULL;
ALTER TABLE `llx_companyrelationships_availability` ADD `watcher_availability` INT DEFAULT 0 NOT NULL;