DROP TABLE IF EXISTS bill;
CREATE TABLE bill
(
	date			DATE NOT NULL,
	type			VARCHAR(100),
	name			VARCHAR(100),
	amount			DECIMAL(7,2),

	id INT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY (id)
);

DROP TABLE IF EXISTS income;
CREATE TABLE income
(
	date			DATE NOT NULL,
	type			VARCHAR(100),
	name			VARCHAR(100),
	amount			DECIMAL(7,2),

	id INT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY (id)
);

DROP TABLE IF EXISTS bill;
CREATE TABLE bill
(
	date			DATE NOT NULL,
	type			VARCHAR(100),
	name			VARCHAR(100),
	amount			DECIMAL(7,2),
	isIncome		BIT DEFAULT 0,
	rangeStart		DATE,
	rangeEnd		DATE,

	rangeDays		INT,
	amountPerDay 	DECIMAL(9,4),

	id INT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY (id)
);

DROP TABLE IF EXISTS gas;
CREATE TABLE gas
(
	date			DATE NOT NULL,
	price			DECIMAL(7,2),
	volume			DECIMAL(7,3),
	odometer		INT,

	id INT NOT NULL AUTO_INCREMENT,
	PRIMARY KEY (id)
);

-- grocery bill should default to a one week range


DROP PROCEDURE IF EXISTS saveBill;
	DELIMITER //
	CREATE PROCEDURE saveBill (
		# p_id: # to update, NULL to insert with id return, 0 to insert without return
		p_id			INT,
		p_date			DATE,
		p_type			VARCHAR(100),
		p_name			VARCHAR(100),
		p_amount		DECIMAL(7,2),
		p_isIncome		BIT,
		p_rangeStart	DATE,
		p_rangeEnd		DATE,
	)
	BEGIN
		DECLARE v_rangeStart		DATE;
		DECLARE v_rangeEnd			DATE;
		DECLARE v_rangeDays			INT;
		DECLARE v_amountPerDay		DECIMAL(9,4);

		-- check if there is a range set
		IF (p_rangeStart IS NULL)
			THEN SET v_rangeStart := p_date;
			ELSE SET v_rangeStart := p_rangeStart;
		END IF;
		IF (p_rangeEnd IS NULL)
			THEN SET v_rangeEnd := p_date;
			ELSE SET v_rangeEnd := p_rangeEnd;
		END IF;

		-- calc duration
		SET v_rangeDays := TIMEDIFF(v_rangeEnd, v_rangeStart) + 1;

		-- calc amount per day
		SET v_amountPerDay := p_amount / v_rangeDays;

		-- insert or update row
		IF (p_id IS NULL)
			THEN INSERT INTO bill (date, type, name, amount, isIncome, rangeStart, rangeEnd, rangeDays, amountPerDay) 
					VALUES (p_date, p_type, p_name, p_amount, p_isIncome, v_rangeStart, v_rangeEnd, v_rangeDays, v_amountPerDay);
				SELECT LAST_INSERT_ID() as id;
			ELSEIF (p_id = 0)
				THEN INSERT INTO bill (date, type, name, amount, isIncome, rangeStart, rangeEnd, rangeDays, amountPerDay) 
						VALUES (p_date, p_type, p_name, p_amount, p_isIncome, v_rangeStart, v_rangeEnd, v_rangeDays, v_amountPerDay);
			ELSE UPDATE bill SET
				date = p_date, 
				type = p_type, 
				name = p_name, 
				amount = p_amount, 
				isIncome = p_isIncome, 
				rangeStart = v_rangeStart, 
				rangeEnd = v_rangeEnd, 
				rangeDays = v_rangeDays, 
				amountPerDay = v_amountPerDay
				WHERE id = p_id
					LIMIT 1;
		END IF;
	END //
	DELIMITER ;


DROP PROCEDURE IF EXISTS getMonthlyBills;
	DELIMITER //
	CREATE PROCEDURE getMonthlyBills (p_dateFrom DATE, p_dateTo DATE)
	BEGIN
		DECLARE v_dateFrom		DATE;
		DECLARE v_dateTo		DATE;

		IF (p_dateFrom IS NULL)
			THEN SET v_dateFrom := '1000-01-01';
			ELSE SET v_dateFrom := p_dateFrom;
		END IF;

		IF (p_dateTo IS NULL)
			THEN SET v_dateTo := '9999-12-31';
			ELSE SET v_dateTo := p_dateTo;
		END IF;

		SELECT 
			YEAR(date) as year,
			MONTH(date) as month,
			MONTHNAME(date) as monthname,

			type,
			COUNT(id) AS count,
			amount,
			isIncome
		FROM bill
		WHERE date BETWEEN v_dateFrom AND v_dateTo
		GROUP BY year, month, type
		ORDER BY year, month, type;
	END //
	DELIMITER ;


---
SELECT 
	YEAR(date) as year,
	MONTH(date) as month,
	MONTHNAME(date) as monthname,
	COUNT(id) AS bills,

	SUM(amount) AS amount
FROM bill
WHERE type = 'Income'
GROUP BY YEAR(date), MONTH(date);
---


DATEDIFF('2016-06-30','2016-06-01')



SET @yearweek = YEARWEEK('2016-05-30',3);
SET @rangeStart = '2016-06-01';
SET @rangeEnd = '2016-06-30';

SELECT @yearweek, 
	@rangeStart, 
	@rangeEnd, 
	@yearweek BETWEEN YEARWEEK(@rangeStart,3) AND YEARWEEK(@rangeEnd,3) AS `isBetween`







-- table of all bills within the week
SET v_yearweek := YEARWEEK(p_weekStart)
SELECT *
FROM bill
WHERE yearweek BETWEEN YEARWEEK(rangeStart,3) AND YEARWEEK(rangeEnd,3) 

# ╔═══════════════════════════════╗
# ║ have to use some sort of join ║
# ╚═══════════════════════════════╝

-- outer query groups information by yearweek


	-- inner query calculates the total amount of bills for each yearweek