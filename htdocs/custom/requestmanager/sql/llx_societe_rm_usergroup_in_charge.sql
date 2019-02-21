-- ============================================================================
-- Copyright (C) 2019	 Open-DSI 	 <support@open-dsi.fr>
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
-- ============================================================================

create table llx_societe_rm_usergroup_in_charge
(
  rowid               integer AUTO_INCREMENT PRIMARY KEY,

  fk_soc              integer NOT NULL,   -- id of the thirdparty
  fk_usergroup        integer NOT NULL,	  -- id of the group of users
  fk_c_request_type   integer NOT NULL	  -- id of the request type into the dictionary
)ENGINE=innodb;
