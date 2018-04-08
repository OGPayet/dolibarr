CREATE TABLE IF NOT EXISTS llx_date_repetition (
rowid INT NOT NULL AUTO_INCREMENT ,
fk_frequence_repetition INT NOT NULL,
moidebut SMALLINT(2) NOT NULL ,
moifin SMALLINT(2) NOT NULL ,
nbmois SMALLINT(2) NOT NULL ,
PRIMARY KEY ( rowid )
) ENGINE = InnoDB ;
