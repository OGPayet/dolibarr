CREATE TABLE IF NOT EXISTS llx_contratabonnement_massmail (
rowid INT NOT NULL AUTO_INCREMENT ,
fk_societe INT NOT NULL ,
objectMail VARCHAR(255) NOT NULL ,
messageMail TEXT NOT NULL,
PRIMARY KEY ( rowid )
) ENGINE = InnoDB ;
