<?php
$nameLower= strtolower($name);
if(isset($this->fields[$nameLower])){
	//some modifications per type
	if($is_init == true){
		$this->fields[$nameLower]['value'] = $value;
		$this->fields[$nameLower]['originalValue'] = $value;
	}elseif($this->fields[$nameLower]['value'] != $value) {
		$type = $this->fields[$nameLower]['type'];
		if($type == 'date' and ctype_digit($value)){ // Is a timestamp
			$this->fields[$nameLower]['value'] = date("Y-m-d",$value);
			$this->fields[$nameLower]['is_changed'] = true;
		}elseif($type == 'datetime' and ctype_digit($value)){ // Is a timestamp
			$this->fields[$nameLower]['value'] = date("Y-m-d H:i:s",$value);
			$this->fields[$nameLower]['is_changed'] = true;
		}elseif($type == 'json'){ // Ohh no A wild json apears
			if(is_array($value)){
				$value = json_encode($value);
			}
			$this->fields[$nameLower]['value'] =$value;
			$this->fields[$nameLower]['is_changed'] = true;
		}elseif($type == 'unix'){ // unix
			$value = trim($value);
			if(!preg_match("/^\d+$/i", $value)){
				$value = strtotime($value);
				if($value == false || $value == -1){
					$value = time();
				}
			}
			$this->fields[$nameLower]['value'] =$value;
			$this->fields[$nameLower]['is_changed'] = true;
		}else{
			$value = empty($value) ? null : $value;
			$this->fields[$nameLower]['value'] = $value;
			$this->fields[$nameLower]['is_changed'] = true;
		}
	}else{
		// still the same
	}


}elseif(substr($nameLower, 0,4) == "link"){
	$linkname = substr($nameLower,4);
	return $this->setLink($linkname,$value);

}elseif($nameLower == $this->pkField){
	// ignore pk

}else{
	$this->error[] = "Set Field does not exist: ".$nameLower;
}