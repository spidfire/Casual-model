<?php
//table not set
if($this->table == null){
	throw new Exception("No table has been defined", 1);
}
//fields not set
if(!is_array($this->fields) || count($this->fields) == 0){
	throw new Exception("No proper fields are set", 1);
}
//pk not set
if($this->pkField == null){
	throw new Exception("No proper primairyKey has been defined", 1);
}
$newFields = array();
foreach($this->fields as $name => $type){
	$empty['name'] = $name;
	$empty['default'] = null;
	$empty['value'] = null;
	$empty['originalValue'] = null;
	$empty['is_changed'] = null;
	$empty['type'] = null;
	$newFields[strtolower($name)] = array_merge($empty,$type);
}

$this->fields = $newFields;
if($search == null){
	//insert new record

}elseif(is_array($search)){
	foreach($search as $name => $value){
		$lower = strtolower($name);
		if(isset($this->fields[$lower])){
			$this->setValue($name,$value,true);
		}elseif(strtolower($this->pkField) == $lower){
			$this->used_search = $value;
		}else{
			//ignore field
		}

	}
	if($this->used_search == null){
		throw new Exception("Primairy Key has not been found in the resultset ".__CLASS__, 1);

	}

}elseif(ctype_digit((string)$search)){
	$this->used_search = $search;
	$row = DB::queryFirstRow("select * from `".$this->table."` where `".$this->pkField."` = %d",$search);
	if($row != false){
		foreach($row as $name => $value){
			$this->setValue($name,$value,true);
		}
	}else{
		throw new Exception("Selected record(".$search.") is not found in '".$this->table."'! table: '".$this->table."'", 1);

	}

}elseif($search instanceof WhereClause){
	$row = DB::query("select * from `".$this->table."` where ".$search->text());
	if(count($row) == 1){
		foreach($row[0] as $name => $value){
			$lower = strtolower($name);
			if(isset($this->fields[$lower])){
				$this->setValue($name,$value,true);
			}elseif(strtolower($this->pkField) == $lower){
				$this->used_search = $value;
			}else{
				throw new Exception("Unknown field found in the (where) insert: ".$lower."! table: '".$this->table."'", 1);

			}
		}
	}elseif(count($row) > 1){
		throw new Exception("The used select statement returned to many rows!  table: '".$this->table."'", 1);

	}else{
		throw new Exception("Selected where record(".$search.") is not found in '".$this->table."'! table: '".$this->table."'", 1);

	}

}else{
	throw new Exception("Unknown action to open ".gettype($search), 1);
}