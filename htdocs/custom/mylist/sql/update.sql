-- ===================================================================
-- Copyright (C) 2013-2017	Charlie Benke	<charlie@patas-monkey.com>
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

ALTER TABLE  `llx_mylist` 		ADD  `categories`  varchar(10) NULL DEFAULT NULL AFTER  `export`;
ALTER TABLE  `llx_mylist` 		ADD  `datatable`  INT DEFAULT 0 AFTER  `export`;

ALTER TABLE  `llx_mylist` 		DROP `code`;
ALTER TABLE  `llx_mylist` 		ADD  `forceall`  INT DEFAULT 0 AFTER  `querydo`;
ALTER TABLE  `llx_mylistdet` 	ADD  `widthpdf`  INT DEFAULT NULL AFTER  `width`;
ALTER TABLE  `llx_mylist` 		DROP INDEX uk_mylist_code;
ALTER TABLE  `llx_mylist` 		ADD  `export`  INT DEFAULT 0 AFTER  `querydo`;
ALTER TABLE  `llx_mylist` 		ADD  `model_pdf`  varchar(255) DEFAULT '' AFTER  `export`;
ALTER TABLE  `llx_mylist` 		ADD  `rowid` INT NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST;
ALTER TABLE  `llx_mylist` 		ADD  `description` TEXT NULL DEFAULT NULL AFTER  `rowid`;