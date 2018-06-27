-- Copyright (C) ---Put here your own copyright and developer email---
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see http://www.gnu.org/licenses/.


-- BEGIN MODULEBUILDER INDEXES
ALTER TABLE llx_doliesign ADD INDEX idx_doliesign_rowid (rowid);
ALTER TABLE llx_doliesign ADD INDEX idx_doliesign_ref (ref);
ALTER TABLE llx_doliesign ADD INDEX idx_doliesign_entity (entity);
ALTER TABLE llx_doliesign ADD INDEX idx_doliesign_fk_soc (fk_soc);
ALTER TABLE llx_doliesign ADD INDEX idx_doliesign_status (status);
ALTER TABLE llx_doliesign ADD INDEX idx_doliesign_fk_object (fk_object);
-- END MODULEBUILDER INDEXES

--ALTER TABLE llx_doliesign ADD UNIQUE INDEX uk_doliesign_fieldxyz(fieldx, fieldy);

--ALTER TABLE llx_doliesign ADD CONSTRAINT llx_doliesign_field_id FOREIGN KEY (fk_field) REFERENCES llx_myotherobject(rowid);
