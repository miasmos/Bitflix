<?PHP
	function getSingleReturn($db,$query) {
		$result=$db->query($query);	//the last entry
		if ($result->num_rows > 0) {
			$rows=array();
			while($row = $result->fetch_row()) {
				$rows[]=$row;
			}
			$result->close();
			return $rows[0][0];
		}
		else {
			return "DNE";
		}
	}
	
	function getRow($db,$query) {
		$result=$db->query($query);	//the last entry
		if ($result->num_rows>0) {
			$rows=array();
			while($row = $result->fetch_row()) {
				$rows[]=$row;
			}
			$result->close();
			return $rows[0];
		}
		else {
			return 0;
		}
	}
	
	function getRows($db,$query) {
		if (!is_null($query)) {
			$result=$db->query($query);	//the last entry
			if ($result->num_rows>0) {
				$rows=array();
				while($row = $result->fetch_row()) {
					$rows[]=$row;
				}
				$result->close();
				return $rows;
			}
			else {
				return 0;
			}
		}
		else {
			return 0;
		}
	}
	
	function sqliReturn($stmt) {
		$meta = $stmt->result_metadata();
	
		while ($field = $meta->fetch_field()) {
		  $parameters[] = &$row[$field->name];
		}
		
		call_user_func_array(array($stmt, 'bind_result'), $parameters);
		
		while ($stmt->fetch()) {
		  foreach($row as $key => $val) {
			$x[$key] = $val;
		  }
		  $results[] = $x;
		}
		return $results;
	}
?>