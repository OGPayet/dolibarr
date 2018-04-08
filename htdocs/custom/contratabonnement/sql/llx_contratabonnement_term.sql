CREATE TABLE IF NOT EXISTS llx_contratabonnement_term (
rowid INT NOT NULL AUTO_INCREMENT ,
fk_contratabonnement INT NOT NULL ,
datedebutperiode date NOT NULL ,
datefinperiode date NOT NULL ,
montantperiode double,
facture BOOL NOT NULL DEFAULT '0',
PRIMARY KEY ( rowid )
) ENGINE = InnoDB ;
