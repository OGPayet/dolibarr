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
-- Table of journals for accountancy
-- ============================================================================

create table llx_extendedintervention_contract_count_type
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,

  fk_contrat                integer NOT NULL,		            -- id of the contract
  fk_c_intervention_type    integer NOT NULL,		            -- id of the dictionary extended intervention type
  count                     integer NULL                    -- count of maximum intervention defined for the contract
)ENGINE=innodb;
