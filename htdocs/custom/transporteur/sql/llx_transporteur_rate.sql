-- ========================================================================
-- Copyright (C) 2012-2017	charlie Benke  <charlie@patas-monkey.com>
--
-- This program is free software; you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation; either version 2 of the License, or
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
-- ========================================================================

create table llx_transporteur_rate
(
  rowid			integer			AUTO_INCREMENT PRIMARY KEY,
  label			varchar(50)		NOT NULL,
  color			varchar(16)		NOT NULL,
  fk_soc		integer 		DEFAULT 0 NOT NULL,		-- fournisseur du transport
  fk_pays		integer 		DEFAULT 0 NOT NULL,		-- pays associé au transport
  entity		integer 		DEFAULT 1 NOT NULL,
  weightmin		DOUBLE(24, 8) 	NULL DEFAULT 0,
  weightmax		DOUBLE(24, 8) 	NULL DEFAULT 0,
  weightunit	tinyint			DEFAULT NULL,
  subprice		DOUBLE(24, 8) 	NULL DEFAULT 0,
  active		integer  		DEFAULT 1 NOT NULL

)ENGINE=innodb;