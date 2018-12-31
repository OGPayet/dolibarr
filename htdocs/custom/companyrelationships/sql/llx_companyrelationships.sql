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

create table llx_companyrelationships
(
  rowid                     integer AUTO_INCREMENT PRIMARY KEY,
  fk_soc                    integer NOT NULL,		            -- id of the thirdparty
  fk_soc_benefactor         integer DEFAULT 0 NOT NULL,		  -- id of the thirdparty benefactor
  fk_soc_relation           integer DEFAULT 0 NOT NULL,		  -- id of the thirdparty in relation
  relation_type             integer DEFAULT 1 NOT NULL		  -- relation type default benefactor
)ENGINE=innodb;
