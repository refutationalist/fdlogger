/* tables */
DROP TABLE IF EXISTS `fdlog`;
DROP TABLE IF EXISTS `fdnote`;
DROP TABLE IF EXISTS `fdcallbook`;
DROP TABLE IF EXISTS `fdband`;
DROP TABLE IF EXISTS `fdradio`;
DROP TABLE IF EXISTS `fdzone`;
DROP TABLE IF EXISTS `fdmode`;
DROP TABLE IF EXISTS `fdclass`;


CREATE TABLE fdnote (
	nid		BIGINT UNSIGNED	NOT NULL AUTO_INCREMENT,
	notes	VARCHAR(2048) NOT NULL,
	handle	VARCHAR(64) NOT NULL,
	logged	TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY pk_fdnote(nid),
	INDEX idx_fdnote(logged)
) ENGINE=InnoDB;

/* populated via import scripts in sql/ */
CREATE TABLE fdcallbook (
	cbid	BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	csign	VARCHAR(32) NOT NULL,
	name	VARCHAR(1024),
	city	VARCHAR(1024),
	state	VARCHAR(1024),
	PRIMARY KEY pk_fdcallbook(cbid),
	INDEX idx_fdcallbook_csign(csign)
) ENGINE=InnoDB;


/* populated via import scripts in sql/ */
CREATE TABLE fdzone (
	code	CHAR(3) NOT NULL,
	name	VARCHAR(128) NOT NULL,
	area	CHAR(1) NOT NULL,
	PRIMARY KEY pk_fdzones(code)
) ENGINE=InnoDB;

CREATE TABLE fdclass (
	code	CHAR(3) NOT NULL,
	name	VARCHAR(256) NOT NULL,
	PRIMARY KEY pk_fdclass(code)
) ENGINE=InnoDB;

/* I am not using ARRL descriptions because they are
 * not adequately descriptive
 */
INSERT INTO fdclass VALUES
	('A',  'Group Portable'),
	('AB', 'Group Portable (Battery)'),
	('B',  '1 or 2 Person Portable'),
	('BB', '1 or 2 Person Portable (Battery)'),
	('C',  'Mobile'),
	('D',  'Home Station'),
	('E',  'Home Station, Emergency Power'),
	('F',  'Emergency Operations Center');

/* only field day bands and bands we have the equipment to work.
   I do have that spectra, probably not gonna try it but you never know.
   and maybe we could do a wifi qso?  I have no idea. 
   but since band selection is done by trigger there's no reason
   not to include it */
CREATE TABLE fdband (
	code    CHAR(5) NOT NULL,
	low		BIGINT UNSIGNED NOT NULL,
	high	BIGINT UNSIGNED	NOT NULL,
	PRIMARY KEY pk_fdband(code)
) ENGINE=InnoDB;
INSERT INTO fdband VALUES
	("160m",  1800000,    2000000),
	("80m",   3500000,    4000000),
	("40m",   7000000,    7300000),
	("20m",   14000000,   14350000),
	("15m",   21000000,   21450000),
	("10m",   28000000,   29700000),
	("6m",    50000000,   54000000),
	("2m",    144000000,  148000000),
	("1.25m", 222000000,  224000000),
	("70cm",  420000000,  450000000),
	("33cm",  902000000,  928000000),
	("13cm",  2390000000, 2450000000),
	("none",  0,          0);


CREATE TABLE fdmode (
	code	CHAR(5)	NOT NULL,
	cab		CHAR(3) NOT NULL,
	ord		TINYINT UNSIGNED NOT NULL,
	PRIMARY KEY pk_fdmode(code)
) ENGINE=InnoDB;
INSERT INTO fdmode VALUES
	("CW",    "CW", 0),
	("AM",    "PH", 1),
	("USB",   "PH", 2),
	("LSB",   "PH", 3),
	("FM",    "PH", 4),
	("DIG",   "DG", 5),
	("PH",    "PH", 6),
	("FT8",   "DG", 7),
	("JT8",   "DG", 8),
	("FT4",   "DG", 9),
	("JT65",  "DG", 10),
	("PSK31", "DG", 11),
	("RTTY",  "DG", 12),
	("SSTV",  "DG", 13);

/*
 fdradio.mode is not a foreign key as we want to be able to set 
 mode "UNK", which unlocks the mode drop down in the client, allowing
 the user to set the mode.
*/
CREATE TABLE fdradio (
	name	VARCHAR(16),
	freq	BIGINT UNSIGNED NOT NULL,
	mode	CHAR(5) NOT NULL,
	logged	TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY pk_fdradio(name)
) ENGINE=MEMORY;


CREATE TABLE fdlog (
	lid		BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	freq	BIGINT UNSIGNED NOT NULL,
	band    CHAR(5) NOT NULL DEFAULT 'none',
	mode	CHAR(5) NOT NULL,
	csign	VARCHAR(32) NOT NULL,
	tx		TINYINT UNSIGNED NOT NULL,
	class	CHAR(2)	NOT NULL,
	zone	CHAR(3) NOT NULL,
	handle	VARCHAR(64) NOT NULL,
	notes	VARCHAR(2048),
	logged	TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY pk_fdlog(lid),
	INDEX idx_fdlog(logged),
	CONSTRAINT fk_fdlog_fdmode
		FOREIGN KEY (mode) REFERENCES fdmode(code)
		ON UPDATE RESTRICT
		ON DELETE RESTRICT,
	CONSTRAINT fk_fdlog_fdclass
		FOREIGN KEY (class) REFERENCES fdclass(code)
		ON UPDATE RESTRICT
		ON DELETE RESTRICT,
	CONSTRAINT fk_fdlog_fdband
		FOREIGN KEY (band) REFERENCES fdband(code)
		ON UPDATE RESTRICT
		ON DELETE RESTRICT,
	CONSTRAINT fk_fdlog_fdzone
		FOREIGN KEY (zone) REFERENCES fdzone(code)
		ON UPDATE RESTRICT
		ON DELETE RESTRICT
) ENGINE=InnoDB;

/* triggers */
DROP TRIGGER IF EXISTS `fdlog_normalize`;

CREATE TRIGGER fdlog_normalize
BEFORE INSERT
ON fdlog FOR EACH ROW
SET 
NEW.csign = UPPER(NEW.csign),
NEW.class = UPPER(NEW.class),
NEW.zone  = UPPER(NEW.zone),
NEW.band  = IFNULL((SELECT code FROM fdband WHERE NEW.freq BETWEEN low AND high LIMIT 1), 'none');

/* views */
DROP VIEW IF EXISTS `fdlogdisplay`;
DROP VIEW IF EXISTS `fdbyband`;
DROP VIEW IF EXISTS `fdbymode`;
DROP VIEW IF EXISTS `fdbycabmode`;
DROP VIEW IF EXISTS `fdbyzone`;
DROP VIEW IF EXISTS `fdbyclass`;

CREATE VIEW fdlogdisplay AS SELECT
	'log' AS kind,
	lid AS id,
	freq,
	band,
	mode,
	csign,
	CONCAT(tx, class, '-', zone) AS exch,
	handle,
	logged,
	notes
FROM fdlog
UNION SELECT
	'note', nid, NULL, NULL, NULL, NULL, NULL, handle, logged, notes
FROM fdnote
ORDER BY logged DESC;

CREATE VIEW fdbyband AS SELECT
COUNT(lid) AS qso, band
FROM fdlog
GROUP BY band
ORDER BY qso DESC;

CREATE VIEW fdbymode AS SELECT
COUNT(lid) AS qso,
mode
FROM fdlog
GROUP BY mode
ORDER BY qso DESC;

CREATE VIEW fdbycabmode AS SELECT
COUNT(A.lid) AS qso,
COUNT(A.lid) * IF(B.cab = 'PH', 1, 2) AS points,
B.cab AS cabmode
FROM fdmode B
LEFT OUTER JOIN fdlog A ON A.mode = B.code
GROUP BY B.cab
ORDER BY qso DESC;

CREATE VIEW fdbyzone AS SELECT
COUNT(lid) AS qso,
B.code, B.name
FROM fdzone B
LEFT OUTER JOIN fdlog A ON A.zone = B.code
GROUP BY B.code
ORDER BY qso DESC;


CREATE VIEW fdbyclass AS SELECT
COUNT(lid) AS qso,
B.code, B.name
FROM fdclass B
LEFT OUTER JOIN fdlog A ON A.class = B.code
GROUP BY B.code
ORDER BY qso DESC;

