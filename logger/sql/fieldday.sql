/* tables */
DROP TABLE IF EXISTS `fdlog`;
DROP TABLE IF EXISTS `fdnote`;
DROP TABLE IF EXISTS `fdcallbook`;
DROP TABLE IF EXISTS `fdband`;
DROP TABLE IF EXISTS `fdradio`;

CREATE TABLE fdlog (
	lid		BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	freq	BIGINT UNSIGNED NOT NULL,
	band    CHAR(5) NOT NULL DEFAULT 'none',
	mode	ENUM('CW', 'AM', 'FM', 'USB', 'LSB', 'DIG') NOT NULL,
	csign	VARCHAR(32) NOT NULL,
	tx		TINYINT UNSIGNED NOT NULL,
	class	CHAR(2)	NOT NULL,
	sec		CHAR(3) NOT NULL,
	handle	VARCHAR(64) NOT NULL,
	notes	VARCHAR(2048),
	logged	TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY pk_fdlog(lid)
) ENGINE=InnoDB;

CREATE TABLE fdnote (
	nid		BIGINT UNSIGNED	NOT NULL AUTO_INCREMENT,
	notes	VARCHAR(2048) NOT NULL,
	handle	VARCHAR(64) NOT NULL,
	logged	TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY pk_fdnote(nid)
) ENGINE=InnoDB;

CREATE TABLE fdcallbook (
	cbid	BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	csign	VARCHAR(32) NOT NULL,
	name	VARCHAR(1024),
	city	VARCHAR(1024),
	state	VARCHAR(1024),
	PRIMARY KEY pk_fdcallbook(cbid),
	INDEX idx_fdcallbook_csign(csign)
) ENGINE=InnoDB;

/* only field day bands and bands we have the equipment to work.
   I do have that spectra, probably not gonna try it but you never know.
   and maybe we could do a wifi qso?  I have no idea. 
   but since band selection is done by trigger there's no reason
   not to include it */
CREATE TABLE fdband (
	low		BIGINT UNSIGNED NOT NULL,
	high	BIGINT UNSIGNED	NOT NULL,
	label   CHAR(5) NOT NULL,
) ENGINE=InnoDB;
INSERT INTO fdband VALUES
	(1800000,    2000000,    "160m"),
	(3500000,    4000000,    "80m"),
	(7000000,    7300000,    "40m"),
	(14000000,   14350000,   "20m"),
	(21000000,   21450000,   "15m"),
	(28000000,   29700000,   "10m"),
	(50000000,   54000000,   "6m"),
	(144000000,  148000000,  "2m"),
	(222000000,  224000000,  "1.25m"),
	(420000000,  450000000,  "70cm"),
	(902000000,  928000000,  "33cm"),
	(2390000000, 2450000000, "13cm");


CREATE TABLE fdradio (
	name	VARCHAR(32),
	freq	BIGINT UNSIGNED NOT NULL,
	mode	ENUM('CW', 'AM', 'FM', 'USB', 'LSB', 'DIG') NOT NULL,
	logged	TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY pk_fdradio(name)
) ENGINE=MEMORY;

/* triggers */
DROP TRIGGER IF EXISTS `fdlog_normalize`;

CREATE TRIGGER fdlog_normalize
BEFORE INSERT
ON fdlog FOR EACH ROW
SET 
NEW.csign = UPPER(NEW.csign),
NEW.class = UPPER(NEW.class),
NEW.sec = UPPER(NEW.sec),
NEW.band = IFNULL((SELECT label FROM fdband WHERE NEW.freq BETWEEN low AND high LIMIT 1), 'none');

/* views */
DROP VIEW IF EXISTS `fdlogdisplay`;

CREATE VIEW fdlogdisplay AS SELECT
	freq, band, mode, csign, CONCAT(tx, class, '-', sec) AS exch, handle, logged, notes
FROM fdlog
UNION SELECT
	NULL, NULL, NULL, NULL, NULL, handle, logged, notes
FROM fdnote
ORDER BY logged DESC;


