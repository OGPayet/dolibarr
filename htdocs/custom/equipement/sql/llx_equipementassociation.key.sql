-- ===================================================================
-- Copyright (C) 2013 charles.fr@benke.fr <charles.fr@benke.fr>
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


ALTER TABLE llx_equipementassociation ADD INDEX idx_equipementassociation_fk_equipement_pere (fk_equipement_pere);
ALTER TABLE llx_equipementassociation ADD INDEX idx_equipementassociation_fk_equipement_fils (fk_equipement_fils);
ALTER TABLE llx_equipementassociation ADD CONSTRAINT fk_equipementassociation_fk_equipement_pere FOREIGN KEY (fk_equipement_pere) REFERENCES llx_equipement(rowid);
ALTER TABLE llx_equipementassociation ADD CONSTRAINT fk_equipementassociation_fk_equipement_fils FOREIGN KEY (fk_equipement_fils) REFERENCES llx_equipement(rowid);
