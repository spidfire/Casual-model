<?php

$name = strtolower($name);
if(isset($this->links[$name])){
	$type = strtolower($this->links[$name][0]);
	$from = $this->links[$name][1];
	$to = $this->links[$name][2];
	$class = $this->links[$name][3];
	if($type == 'tomany'){
		// select many from the other table
		$where = new WhereClause("and");
		$fromValue = $this->getValue($from);
		$where->add($to." = %d",$fromValue);
		$tmp = new $class();

		$result = DB::query("Select * from ".$tmp->getTableName()." where ".$where->text());
		$ret = array();
		foreach($result as $res){
			$ret[] = new  $class($res);
		}
		return $ret;
	}elseif($type == 'toone'){
		// select one from the other table
		$where = new WhereClause("and");
		$fromValue = $this->getValue($from);
		$where->add($to." = %d",$fromValue);
		$this->links[$name]['item'] = new  $class($where);
		return $this->links[$name]['item'];
	}else{
		throw new Exception("Foulthy link type is set", 1);

	}


}else{
	throw new Exception("Link is not found", 1);

}