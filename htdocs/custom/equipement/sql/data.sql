-- Copyright (C) 2012 	   Charles-Fr Benke       <charles.fr@benke.fr>
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
--

--
-- Ne pas placer de commentaire en fin de ligne, ce fichier est pars� lors
-- de l'install et tous les sigles '--' sont supprim�s.
--

--
-- equipement state
--

insert into llx_c_equipement_etat ( code, libelle, active) values ('NEW', 'BrandNew',  1);
insert into llx_c_equipement_etat ( code, libelle, active) values ('DEMO', 'Demo',  1);
insert into llx_c_equipement_etat ( code, libelle, active) values ('SETUP', 'InSetup',  1);
insert into llx_c_equipement_etat ( code, libelle, active) values ('LEASE', 'Lease',   1);
insert into llx_c_equipement_etat ( code, libelle, active) values ('REPAIR', 'InRepair',  1);
insert into llx_c_equipement_etat ( code, libelle, active) values ('MAINTAIN', 'ToMaintain',  1);
insert into llx_c_equipement_etat ( code, libelle, active) values ('USE', 'InUse',  1);
insert into llx_c_equipement_etat ( code, libelle, active) values ('BROKEN', 'Broken',  1);
insert into llx_c_equipement_etat ( code, libelle, active) values ('SALE', 'ForSale',  1);
insert into llx_c_equipement_etat ( code, libelle, active) values ('HS', 'OutofOrder',  1);
insert into llx_c_equipement_etat ( code, libelle, active) values ('LOST', 'Lost',  1);

--
-- Type equipement event
--

insert into llx_c_equipementevt_type ( code, libelle, active) values ('INSTALL', 'Install',  1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('UNINSTALL', 'Uninstall',   1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('REPAIR', 'Repair',  1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('MAINTAIN', 'Maintain',  1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('TRASH', 'Trash',  1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('LEASE', 'Lease',  1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('STOCK', 'Stock',  1);
-- Nouveau type d'�venement pour les mouvement
insert into llx_c_equipementevt_type ( code, libelle, active) values ('MOVE', 'StockMovement',  1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('RECEPT', 'Recept',  1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('LOOSED', 'NotRecept',  1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('BROKEN', 'MoveBroken',  1);
-- Ajout 2018-06-19 - Open-Dsi
insert into llx_c_equipementevt_type ( code, libelle, active) values ('COMPO', 'Composition',  1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('REASSIGN', 'Réaffectation',  1);
