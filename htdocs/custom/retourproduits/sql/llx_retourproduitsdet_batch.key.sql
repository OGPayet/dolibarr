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

DELETE FROM llx_retourproduitsdet_batch where fk_retourproduitsdet NOT IN (SELECT rowid from llx_retourproduitsdet);

ALTER TABLE llx_retourproduitsdet_batch ADD INDEX idx_fk_retourproduitsdet (fk_retourproduitsdet);
ALTER TABLE llx_retourproduitsdet_batch ADD CONSTRAINT fk_retourproduitsdet_batch_fk_retourproduitsdet FOREIGN KEY (fk_retourproduitsdet) REFERENCES llx_retourproduitsdet(rowid);
