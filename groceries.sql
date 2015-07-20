DROP TABLE IF EXISTS groceries;
CREATE TABLE groceries
(
	date		DATE,
	location	VARCHAR(100),
	amount		DECIMAL(6,2),

	id INT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY (id)
);