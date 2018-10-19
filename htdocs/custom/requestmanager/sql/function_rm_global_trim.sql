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
-- ============================================================================

-- purge de caractères indésirables
-- exemple : RM_GLOBAL_TRIM('+30 06 06 14 15', '0123456789') => '3006061415'
CREATE FUNCTION RM_GLOBAL_TRIM(input_text VARCHAR (8000), accepted_chars VARCHAR(256))
 RETURNS VARCHAR (8000)
 BEGIN
   DECLARE res VARCHAR(8000);

   IF (input_text IS NOT NULL AND LENGTH(input_text) > 0) THEN
     BEGIN
       DECLARE idx INT DEFAULT 1;
       DECLARE len INT DEFAULT LENGTH(input_text);
       DECLARE c CHAR DEFAULT '';

       -- initialisation
       SET res = '';

       -- lecture caractère par caractère
       WHILE (idx <= len) DO
         SET c = SUBSTRING(input_text, idx, 1);
         IF LOCATE(c, accepted_chars) > 0 THEN SET res = CONCAT(res, c); END IF;

         SET idx = idx + 1;
       END WHILE;
     END;
   ELSE
     SET res = NULL;
   END IF;

   RETURN res;
 END;
