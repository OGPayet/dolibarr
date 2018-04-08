CREATE TABLE IF NOT EXISTS llx_contratabonnement (
rowid INT NOT NULL AUTO_INCREMENT ,
fk_contratdet INT NULL ,
fk_commandefournisseurdet INT NULL ,
fk_frequencerepetition INT NOT NULL ,
periodepaiement SMALLINT( 1 ) NOT NULL DEFAULT '0',
remise DOUBLE NOT NULL DEFAULT  '0',
`statut` INT NOT NULL DEFAULT  '1' COMMENT  'actif ou non',
`autoupdate` INT NOT NULL DEFAULT  '0' COMMENT  'met a jour les tarifs auto ou non',
PRIMARY KEY ( rowid )
) ENGINE = InnoDB ;

ALTER TABLE  `llx_contratabonnement` ADD  `statut` INT NOT NULL DEFAULT  '1' COMMENT  'actif ou non';

ALTER TABLE  `llx_contratabonnement` ADD  `remise` DOUBLE NOT NULL DEFAULT  '0';

ALTER TABLE  `llx_contratabonnement` ADD  `fk_commandefournisseurdet` INT NULL AFTER  `fk_contratdet`;