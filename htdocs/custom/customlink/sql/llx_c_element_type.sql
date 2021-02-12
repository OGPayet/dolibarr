-- ===================================================================
-- Copyright (C) 2014 Charles-Fr Benke <charles.fr@benke.fr>
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

create table llx_c_element_type
(
  rowid				integer AUTO_INCREMENT PRIMARY KEY,
  type				varchar (32),	-- type de l'�l�ment tel qu'utilis� dans la table element_element
  label				text,			-- nom de l'�l�ment 
  classpath			text,
  subelement		text,
  module			text,
  translatefile		text,
  classfile			text,
  className			text,
  incore			integer		-- le module est pr�sent dans le core
)ENGINE=innodb;
