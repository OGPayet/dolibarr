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
-- ===========================================================================

ALTER TABLE llx_requestmanager ADD fk_soc_origin	    integer NOT NULL; -- id of the thirdparty origin
ALTER TABLE llx_requestmanager ADD fk_soc_benefactor	integer NOT NULL; -- id of the thirdparty benefactor

ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_soc_origin (fk_soc_origin);
ALTER TABLE llx_requestmanager ADD INDEX idx_requestmanager_fk_soc_benefactor (fk_soc_benefactor);

ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_soc_origin         FOREIGN KEY (fk_soc_origin) REFERENCES llx_societe (rowid);
ALTER TABLE llx_requestmanager ADD CONSTRAINT fk_requestmanager_fk_soc_benefactor     FOREIGN KEY (fk_soc_benefactor) REFERENCES llx_societe (rowid);

UPDATE llx_requestmanager SET fk_soc_origin = fk_soc WHERE fk_soc_origin = 0;
UPDATE llx_requestmanager SET fk_soc_benefactor = fk_soc WHERE fk_soc_benefactor = 0;
