-- ===================================================================
-- Copyright (C) 2015	Charlie Benke	<charlie@patas-monkey.com>
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

create table llx_myfield
(
	rowid		integer AUTO_INCREMENT PRIMARY KEY, -- clé principale
	label		varchar(50) NOT NULL,				-- Libellé du champ à cacher ou son identifiant LANG
	context		varchar(255) NULL  DEFAULT NULL,	-- context au sens hook du champs à enlever. une virgule de séparation si plusieurs
	author		varchar(50) NULL DEFAULT NULL,		-- auteur du champs
	active		integer NULL DEFAULT NULL,			-- visible = 0, caché = 1, invisible = 2
	typefield	integer NULL DEFAULT NULL,			-- champs classique = 0 ou null, onglet = 1, menu = 2
	compulsory	integer NULL DEFAULT NULL,			-- fct natif = 0, obligatoire = 1
	sizefield	integer NULL DEFAULT NULL, 			-- largeur de la zone de saisie
	movefield	integer NULL DEFAULT NULL, 			-- déplacement du champ
	formatfield	varchar(255) NULL DEFAULT NULL,
	color		varchar(6) NULL DEFAULT NULL,		-- couleur de fond (si visible ou caché
	replacement	varchar(255) NULL  DEFAULT NULL,	-- Text qui en remplace un autre
	initvalue	text NULL DEFAULT NULL				-- valeur par défaut
)ENGINE=innodb;