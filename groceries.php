<?php
	function selectBills($db)
	{
		$result = $db->query('SELECT * FROM groceries ORDER BY date');
		while($row = $result->fetch_assoc())
		{
			$bill = new stdClass();
			$bill->date = $row['date'];
			$bill->location = $row['location'];
			$bill->amount = $row['amount'];
			$bill->id = $row['id'];

			$groceries[] = $bill;
		}
		$result->free();
		echo json_encode($groceries);
	}
	function selectWeeks($db)
	{
		$sql = 
			"SELECT
				YEARWEEK(date, 3) as week,
				STR_TO_DATE(CONCAT(YEARWEEK(date,3), ' Monday'), '%x%v %W') as weekStart,
				COUNT(id) as count,
				SUM(amount) as amount
			FROM groceries
			GROUP BY week
			ORDER BY week";
		$result = $db->query($sql);
		$weeks = [];
		while($row = $result->fetch_assoc())
		{
			$week = new stdClass();
			$week->weekStart = $row['weekStart'];
			$week->count = $row['count'];
			$week->amount = $row['amount'];

			$weeks[] = $week;
		}
		$result->free();
		echo json_encode($weeks);
	}
	function insert($db, $bill)
	{
		var_dump($bill);
		$stmt = $db->prepare('INSERT INTO groceries (date, location, amount) VALUES (?, ?, ?);');
		$stmt->bind_param('ssd', $bill->date, $bill->location, $bill->amount);
		$stmt->execute();
		echo $stmt->insert_id;
	}
	function update($db, $bill)
	{
		$stmt = $db->prepare('UPDATE groceries SET date = ?, location = ?, amount = ? WHERE id = ? LIMIT 1;');
		$stmt->bind_param('ssdi', $bill->date, $bill->location, $bill->amount, $bill->id);
		$stmt->execute();
		echo $stmt->affected_rows;
	}
	function delete($db, $id)
	{
		$stmt = $db->prepare('DELETE FROM groceries WHERE id = ? LIMIT 1;');
		$stmt->bind_param('i', $id);
		$stmt->execute();
		echo $stmt->affected_rows;
	}

	try
	{
		//mysqli_report(MYSQLI_REPORT_STRICT);
		$host = 'localhost';
		$dbname = 'bills';
		$user = 'root';
		$password = 'root';
		$db = new mysqli($host, $user, $password, $dbname);

		switch($_SERVER['REQUEST_METHOD'])
		{
			case 'GET':
				if(isset($_GET['weeks']))
				{
					selectWeeks($db);
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