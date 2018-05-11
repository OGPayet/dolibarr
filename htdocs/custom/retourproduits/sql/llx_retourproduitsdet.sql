-- ===================================================================
-- Copyright (C) 2012-2014 Charles-Fr Benke <charles.fr@benke.fr>
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

CREATE TABLE llx_retourproduitsdet (
  rowid INTEGER AUTO_INCREMENT PRIMARY KEY,
  fk_retourproduits int(11) NOT NULL,
  fk_origin_line int(11) DEFAULT NULL,
  fk_product int(11) DEFAULT NULL,
  fk_equipement int(11) DEFAULT NULL,
  fk_entrepot_dest int(11) DEFAULT NULL,
  qty double DEFAULT NULL,
  rang int(11) DEFAULT '0'
) ENGINE=InnoDB;