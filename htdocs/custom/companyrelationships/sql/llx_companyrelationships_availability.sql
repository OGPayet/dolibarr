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

create table llx_companyrelationships_availability
(
  rowid                                               integer AUTO_INCREMENT PRIMARY KEY,
  fk_companyrelationships                             integer NOT NULL,		                      -- id of company relationships
  fk_c_companyrelationships_availability              integer NOT NULL,                         -- id of line in dictionary of company relationships public space availability
  principal_availability                              integer DEFAULT 0 NOT NULL,               -- public space availability for contractor
  benefactor_availability                             integer DEFAULT 0 NOT NULL,               -- public space availability for benefactor
  watcher_availability                                integer DEFAULT 0 NOT NULL                -- public space availability for watcher
)ENGINE=innodb;
