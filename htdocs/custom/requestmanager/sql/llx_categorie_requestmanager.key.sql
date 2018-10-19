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
-- ===========================================================================

ALTER TABLE llx_categorie_requestmanager ADD PRIMARY KEY pk_categorie_requestmanager (fk_categorie, fk_requestmanager);
ALTER TABLE llx_categorie_requestmanager ADD INDEX idx_categorie_requestmanager_fk_categorie (fk_categorie);
ALTER TABLE llx_categorie_requestmanager ADD INDEX idx_categorie_requestmanager_fk_requestmanager (fk_requestmanager);

ALTER TABLE llx_categorie_requestmanager ADD CONSTRAINT fk_categorie_requestmanager_categorie_rowid FOREIGN KEY (fk_categorie) REFERENCES llx_categorie (rowid);
ALTER TABLE llx_categorie_requestmanager ADD CONSTRAINT fk_categorie_requestmanager_requestmanager_rowid FOREIGN KEY (fk_requestmanager) REFERENCES llx_requestmanager (rowid);
