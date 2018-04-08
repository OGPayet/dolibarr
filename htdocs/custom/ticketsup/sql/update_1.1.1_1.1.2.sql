ALTER TABLE llx_ticketsup_msg ADD private INT NOT NULL DEFAULT 0 AFTER message , ADD INDEX (private) ;
