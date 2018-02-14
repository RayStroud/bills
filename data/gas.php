<?php
	include 'include/db.php';
	function selectGases($db, $days)
	{
		if ($days > 0) 
		{
			$result = $db->query("SELECT date, amount, volume, price, odometer, notes, bill_id, id FROM gas WHERE date > NOW() - INTERVAL $days DAY ORDER BY date");
		} 
		else 
		{
			$result = $db->query("SELECT date, amount, volume, price, odometer, notes, bill_id, id FROM gas ORDER BY date");
		}
		$gases = [];
		while($row = $result->fetch_assoc())
		{
			$gas = new stdClass();
			$gas->date = $row['date'];
			$gas->amount = (float)$row['amount'];
			$gas->volume = empty($row['volume']) ? null : (float)$row['volume'];
			$gas->price = empty($row['price']) ? null : (float)$row['price'];
			$gas->odometer = empty($row['odometer']) ? null : (float)$row['odometer'];
			$gas->notes = $row['notes'];
			$gas->bill_id = (int)$row['bill_id'];
			$gas->id = (int)$row['id'];

			$gases[] = $gas;
		}
		$result->free();
		echo json_encode($gases);
	}
	function selectMonths($db)
	{
		$sql = 
			"SELECT
				YEAR(date) as year,
				MONTH(date) as month,
				STR_TO_DATE(CONCAT(YEAR(date), ' ', MONTHNAME(date), ' 1'), '%Y %M %e') as monthString,
				COUNT(id) as count,
				SUM(amount) as amount
			FROM gas
			GROUP BY year, month
			ORDER BY year, month";
		$result = $db->query($sql);
		$months = [];
		while($row = $result->fetch_assoc())
		{
			$month = new stdClass();
			$month->month = $row['monthString'];
			$month->count = (int)$row['count'];
			$month->amount = (float)$row['amount'];

			$months[] = $month;
		}
		$result->free();
		echo json_encode($months);
	}
	function insertGas($db, $gas)
	{
		$price = $gas->amount/$gas->volume;
		$stmt = $db->prepare('SET @date = ?, @amount = ?, @volume = ?, @price = ?, @odometer = ?, @notes = ?');
		$stmt->bind_param('sdddis', $gas->date, $gas->amount, $gas->volume, $price, $gas->odometer, $gas->notes);
		$stmt->execute();
		$db->query("INSERT INTO bill (date, type, name, amount) VALUES (@date, 'Transport', 'Gas', @amount);");
		$db->query("INSERT INTO gas (date, amount, volume, price, odometer, notes, bill_id) VALUES (@date, @amount, @volume, @price, @odometer, @notes, LAST_INSERT_ID() );");
		echo $db->insert_id;
	}
	function updateGas($db, $gas)
	{
		$price = !empty($gas->amount) || !empty($gas->volume) ? NULL : $gas->amount/$gas->volume;
		$stmt = $db->prepare('SET @date = ?, @amount = ?, @volume = ?, @price = ?, @odometer = ?, @notes = ?, @bill_id = ?');
		$stmt->bind_param('sdddiis', $gas->date, $gas->amount, $gas->volume, $price, $gas->odometer, $gas->notes, $gas->id, $gas->bill_id);
		$stmt->execute();
		$db->query('UPDATE bill SET date = @date, amount = @amount WHERE id = @bill_id LIMIT 1; 
			UPDATE gas SET date = @date, amount = @amount, volume = @volume, price = @price, odometer = @odometer, notes = @notes WHERE id = @id LIMIT 1;');
		echo $stmt->affected_rows;
	}
	function deleteGas($db, $gas)
	{
		$stmt = $db->prepare('SET @id = ?, @bill_id = ?');
		$stmt->bind_param('ii', $gas->id, $gas->bill_id);
		$stmt->execute();
		$db->query('DELETE FROM bill WHERE id = @bill_id LIMIT 1; 
			DELETE FROM gas WHERE id = @id LIMIT 1;');
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
				else if(isset($_GET['days']))
				{
					selectGases($db, $_GET['days']);
				}
				else
				{
					selectGases($db, null);
				}
				break;
			case 'POST':
				$gas = json_decode(file_get_contents("php://input"));
				insertGas($db, $gas);
				break;
			case 'PUT':
				$gas = json_decode(file_get_contents("php://input"));
				updateGas($db, $gas);
				break;
			case 'DELETE':
				$gas = json_decode(file_get_contents("php://input"));
				deleteGas($db, $gas);
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