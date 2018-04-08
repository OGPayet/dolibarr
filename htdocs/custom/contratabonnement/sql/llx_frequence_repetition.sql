DROP TABLE IF EXISTS llx_frequence_repetition;

CREATE TABLE IF NOT EXISTS llx_frequence_repetition (
rowid INT NOT NULL,
coeffrepetition INT NOT NULL,
nomfrequencerepetition VARCHAR( 100 ) NOT NULL ,
PRIMARY KEY ( rowid )
) ENGINE = InnoDB ;