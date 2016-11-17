<?php
	include 'include/db.php';

	function selectBills($db)
	{
		$result = $db->query("	
			SELECT date, type, name, amount, 0 AS isIncome, id FROM bill
			UNION
			SELECT date, type, name, amount, 1 AS isIncome, id FROM income");
		$bills = [];
		while($row = $result->fetch_assoc())
		{
			$bill = new stdClass();
			$bill->date 		= 			$row['date'];
			$bill->type 		= 			$row['type'];
			$bill->name 		= 			$row['name'];
			$bill->amount 		= (float)	$row['amount'];
			$bill->isIncome 	= (int)		$row['isIncome'];
			$bill->id 			= (int)		$row['id'];

			$bills[] = $bill;
		}
		$result->free();
		echo json_encode($bills);
	}
	function selectTypes($db)
	{
		$result = $db->query("	
			SELECT DISTINCT type, 0 AS isIncome FROM bill
			UNION
			SELECT DISTINCT type, 1 AS isIncome FROM income
			ORDER BY type");
		$types = [];
		while($row = $result->fetch_assoc())
		{
			$type = new stdClass();
			$type->type 		= 			$row['type'];
			$type->isIncome 	= (int) 	$row['isIncome'];
			$types[] = $type;
		}
		$result->free();
		echo json_encode($types);
	}
	function selectMonths($db)
	{
		$sql = 
			"SELECT year, month, monthname, SUM(total) AS 'total', SUM(billTotal) AS 'billTotal', SUM(incomeTotal) AS 'incomeTotal', GROUP_CONCAT(bills ORDER BY type SEPARATOR ';') AS 'bills'
			FROM (
				SELECT 
					YEAR(date) AS year,
					LPAD(MONTH(date),2,'0') AS month,
					LEFT(MONTHNAME(date),3) AS monthname,
					type,
					-1.0 * SUM(amount) AS 'total',
					0 AS 'incomeTotal',
					SUM(amount) AS 'billTotal',
					CONCAT_WS(',', type, COUNT(id), SUM(amount), 0) AS bills
				FROM bill
				GROUP BY year, month, type
				UNION
				SELECT 
					YEAR(date) AS year,
					LPAD(MONTH(date),2,'0') AS month,
					LEFT(MONTHNAME(date),3) AS monthname,
					type,
					SUM(amount) AS 'total',
					SUM(amount) AS 'incomeTotal',
					0 AS 'billTotal',
					CONCAT_WS(',', type, COUNT(id), SUM(amount), 1) AS bills
				FROM income
				GROUP BY year, month, type
				ORDER BY year, month, type
			) AS t1
			GROUP BY year, month
			ORDER BY year, month;";
		$result = $db->query($sql);
		$months = [];
		while($row = $result->fetch_assoc())
		{
			$month = new stdClass();
			$month->year 		= (int)		$row['year'];
			$month->month 		= (int)		$row['month'];
			$month->monthname 	= 			$row['monthname'];
			$month->total 		= (float)	$row['total'];
			$month->billTotal 	= (float)	$row['billTotal'];
			$month->incomeTotal = (float)	$row['incomeTotal'];

			//convert concatenated types into objects
			$bills = [];
			$typeSummaries = explode(";", $row['bills']);
			foreach ($typeSummaries as $value)
			{
				$typeArray = explode(",", $value);
				$type = new stdClass();
				$type->count 		= (int)		$typeArray[1];
				$type->amount 		= (float)	$typeArray[2];
				$type->isIncome 	= (int)		$typeArray[3];
				$bills[$typeArray[0]] = $type;
			}
			$month->bills = $bills;

			$months[] = $month;
		}
		$result->free();
		echo json_encode($months);
	}
	function insert($db, $bill)
	{
		$tableName = $bill->isIncome ? 'income' : 'bill';
		$stmt = $db->prepare("INSERT INTO $tableName (date, type, name, amount) VALUES (?, ?, ?, ?);");
		$stmt->bind_param('sssd', $bill->date, $bill->type, $bill->name, $bill->amount);
		$stmt->execute();
		echo $stmt->insert_id;
	}
	function update($db, $bill)
	{
		$tableName = $bill->isIncome ? 'income' : 'bill';
		$stmt = $db->prepare("UPDATE $tableName SET date = ?, type = ?, name = ?, amount = ? WHERE id = ? LIMIT 1;");
		$stmt->bind_param('sssdi', $bill->date, $bill->type, $bill->name, $bill->amount, $bill->id);
		$stmt->execute();
		echo $stmt->affected_rows;
	}
	function delete($db, $id)
	{
		$tableName = $bill->isIncome ? 'income' : 'bill';
		$stmt = $db->prepare("DELETE FROM $tableName WHERE id = ? LIMIT 1;");
		$stmt->bind_param('i', $id);
		$stmt->execute();
		echo $stmt->affected_rows;
	}

	try
	{	
		switch($_SERVER['REQUEST_METHOD'])
		{
			case 'GET':
				if(isset($_GET['months']) || isset($_GET['month']))
				{
					selectMonths($db);
				}
				else if(isset($_GET['types']) || isset($_GET['type']))
				{
					selectTypes($db);
				}
				else
				{
					selectBills($db);
				}
				break;
			case 'POST':
				$bill = json_decode(file_get_contents("php://input"));
				insert($db, $bill);
				break;
			case 'PUT':
				$bill = json_decode(file_get_contents("php://input"));
				update($db, $bill);
				break;
			case 'DELETE':
				$bill = json_decode(file_get_contents("php://input"));
				delete($db, $bill->id);
				break;
		}
		$db->close();
	}
	catch (Exception $e)
	{
		http_response_code(500);
		die();
	}
?>