-- ===================================================================
-- Copyright (C) 2012-2016 Charlie Benke <charlie@patas-monkey.com>
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
-- ===================================================================

create table llx_equipementevt_predef
(
  fk_product				integer,			-- produit associé à l'équipement
  fk_equipementevt_type		integer NOT NULL,	-- type d'évènement sur l'équipement
  description				text,				-- description de la ligne d'evènement
  import_key 				VARCHAR( 14 ) NULL DEFAULT NULL
)ENGINE=innodb;
