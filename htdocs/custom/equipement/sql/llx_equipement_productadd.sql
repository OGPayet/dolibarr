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


CREATE TABLE IF NOT EXISTS `llx_equipement_productadd` (
  `rowid` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `fk_equipement` int NOT NULL DEFAULT '0',
  `fk_product` int NOT NULL DEFAULT '0',
  `qty` int NOT NULL DEFAULT '0'
) ENGINE=InnoDB;
