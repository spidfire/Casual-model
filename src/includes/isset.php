<?php

$nameLower= strtolower($name);


if(isset($this->fields[$nameLower])){		
	return true;
}elseif($nameLower == $this->pkField){
	return true;
}else{
	return false;	
}