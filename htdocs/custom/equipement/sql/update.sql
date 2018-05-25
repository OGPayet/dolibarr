ALTER TABLE `llx_c_equipement_etat` CHANGE `coder` `coder` VARCHAR(16) NULL DEFAULT NULL;
ALTER TABLE `llx_c_equipementevt_type` CHANGE `coder` `coder` VARCHAR(16) NULL DEFAULT NULL;

ALTER TABLE `llx_equipement` 			ADD `fk_user_modif` 		INT(11) NULL 	AFTER `fk_user_valid`;
ALTER TABLE  `llx_equipement`			ADD	`fk_commande_fourn`		integer		NULL		DEFAULT  NULL;

ALTER TABLE  `llx_equipementconsumption`	ADD	`qty`		double(24,8)	NULL	DEFAULT  1;

ALTER TABLE  `llx_equipement`			ADD	`dated`				datetime	NULL	DEFAULT  NULL;
ALTER TABLE  `llx_c_equipementevt_type`	ADD	`color`		varchar(8) NULL DEFAULT NULL;

ALTER TABLE  `llx_equipement`			ADD	`fk_product_batch`		integer		NULL		DEFAULT  NULL;

insert into llx_c_equipementevt_type ( code, libelle, active) values ('MOVE', 'StockMovement',  1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('RECEPT', 'Recept',  1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('LOOSED', 'NotRecept',  1);
insert into llx_c_equipementevt_type ( code, libelle, active) values ('BROKEN', 'MoveBroken',  1);

ALTER TABLE  `llx_equipementevt`	ADD  `fk_operation`	Integer 		NULL 		DEFAULT NULL;
ALTER TABLE  `llx_equipementevt`	ADD  `fk_project`	Integer 		NULL 		DEFAULT NULL;
ALTER TABLE  `llx_equipement`		ADD  `import_key` 	VARCHAR( 14 ) 	NULL 		DEFAULT NULL;
ALTER TABLE  `llx_equipementevt`	ADD  `import_key`	VARCHAR( 14 ) 	NULL 		DEFAULT NULL;
ALTER TABLE  `llx_equipement`		ADD  `price`		double(24,8)	NULL		DEFAULT  '0';
ALTER TABLE  `llx_equipement`		ADD  `pmp`			double(24,8)	NULL		DEFAULT  '0';
ALTER TABLE  `llx_equipement`		ADD  `unitweight`	double(24,8)	NULL		DEFAULT  '0';		-- NEW : poid de l'équipement
ALTER TABLE  `llx_equipement`		ADD  `quantity`		INT 			NOT NULL	DEFAULT  '1';
