<?php
	include 'include/db.php';
	function selectBills($db, $days)
	{
		if ($days > 0) 
		{
			$result = $db->query("SELECT date, name, amount, id FROM bill WHERE type = 'Transport' AND name = 'Gas' AND date > NOW() - INTERVAL $days DAY ORDER BY date");
		} 
		else 
		{
			$result = $db->query("SELECT date, name, amount, id FROM bill WHERE type = 'Transport' AND name = 'Gas' ORDER BY date");
		}
		$gas = [];
		while($row = $result->fetch_assoc())
		{
			$bill = new stdClass();
			$bill->date = $row['date'];
			$bill->amount = (float)$row['amount'];
			$bill->id = (int)$row['id'];

			$gas[] = $bill;
		}
		$result->free();
		echo json_encode($gas);
	}
	function selectWeeks($db)
	{
		$sql = 
			"SELECT
				YEARWEEK(date, 3) as yearweek,
				STR_TO_DATE(CONCAT(YEARWEEK(date, 3), ' Monday'), '%x%v %W') as weekStart,
				COUNT(id) as count,
				SUM(amount) as amount
			FROM bill
			WHERE type = 'Transport'
				AND name = 'Gas'
			GROUP BY yearweek
			ORDER BY yearweek";
		$result = $db->query($sql);
		$weeks = [];
		while($row = $result->fetch_assoc())
		{
			$week = new stdClass();
			$week->weekStart = $row['weekStart'];
			$week->count = (int)$row['count'];
			$week->amount = (float)$row['amount'];

			$weeks[] = $week;
		}
		$result->free();
		echo json_encode($weeks);
	}
	function selectMonths($db)
	{
		$sql = 
			"SELECT
				YEAR(date) as year,
				MONTH(date) as month,
				STR_TO_DATE(CONCAT(YEAR(date), ' ', MONTHNAME(date), ' 1'), '%Y %M %e') as monthStart,
				COUNT(id) as count,
				SUM(amount) as amount
			FROM bill
			WHERE type = 'Transport'
				AND name = 'Gas'
			GROUP BY year, month
			ORDER BY year, month";
		$result = $db->query($sql);
		$months = [];
		while($row = $result->fetch_assoc())
		{
			$month = new stdClass();
			$month->month = $row['monthStart'];
			$month->count = (int)$row['count'];
			$month->amount = (float)$row['amount'];

			$months[] = $month;
		}
		$result->free();
		echo json_encode($months);
	}
	function insertBill($db, $bill)
	{
		var_dump($bill);
		$stmt = $db->prepare("INSERT INTO bill (date, type, name, amount) VALUES (?, 'Transport', 'Gas', ?);");
		$stmt->bind_param('sd', $bill->date, $bill->amount);
		$stmt->execute();
		echo $stmt->insert_id;
	}
	function insertGas($db, $bill)
	{
		var_dump($bill);
		$stmt = $db->prepare("INSERT INTO gas (date, price, volume, odometer) VALUES (?, ?, ?, ?);");
		$stmt->bind_param('sddi', $bill->date, $bill->amount, $bill->volume, $bill->odometer);
		$stmt->execute();
		echo $stmt->insert_id;
	}
	function update($db, $bill)
	{
		$stmt = $db->prepare('UPDATE bill SET date = ?, amount = ? WHERE id = ? LIMIT 1;');
		$stmt->bind_param('sdi', $bill->date, $bill->amount, $bill->id);
		$stmt->execute();
		echo $stmt->affected_rows;
	}
	function delete($db, $id)
	{
		$stmt = $db->prepare('DELETE FROM bill WHERE id = ? LIMIT 1;');
		$stmt->bind_param('i', $id);
		$stmt->execute();
		echo $stmt->affected_rows;
	}

	try
	{
		switch($_SERVER['REQUEST_METHOD'])
		{
			case 'GET':
				if(isset($_GET['weeks']) || isset($_GET['week']))
				{
					selectWeeks($db);
				}
				else if(isset($_GET['months']) || isset($_GET['month']))
				{
					selectMonths($db);
				}
				else if(isset($_GET['days']))
				{
					selectBills($db, $_GET['days']);
				}
				else
				{
					selectBills($db, null);
				}
				break;
			case 'POST':
				$bill = json_decode(file_get_contents("php://input"));
				insertBill($db, $bill);
				insertGas($db, $bill);
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