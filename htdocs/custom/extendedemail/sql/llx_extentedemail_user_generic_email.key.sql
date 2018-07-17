-- ========================================================================
-- Copyright (C) 2017 		Open-DSI      <support@open-dsi.fr>
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
-- ========================================================================

ALTER TABLE llx_extentedemail_user_generic_email ADD UNIQUE INDEX uk_extentedemail_generic_email(fk_user, fk_generic_email, entity);

ALTER TABLE llx_extentedemail_user_generic_email ADD CONSTRAINT fk_extentedemail_user_generic_email_fk_user 	        FOREIGN KEY (fk_user)           REFERENCES llx_user (rowid);
ALTER TABLE llx_extentedemail_user_generic_email ADD CONSTRAINT fk_extentedemail_user_generic_email_fk_generic_email 	FOREIGN KEY (fk_generic_email)  REFERENCES llx_c_extentedemail_user_generic_email (rowid);
