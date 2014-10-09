<?php
$return = null;
$nameLower= strtolower($name);


if(isset($this->fields[$nameLower])){		
	return $this->fields[$nameLower]['value'];
	

}elseif($nameLower == $this->pkField){
	return $this->used_search;
	

}elseif(substr($nameLower,0,4) == "link" && isset($this->links[substr($nameLower,4)])){
	return $this->getLink(substr($nameLower,4));
	

}else{
	throw new Exception("Get Field does not exist: ".$nameLower, 1);
	
}