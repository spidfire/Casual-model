<?php

$text = "<b>Table</b>: ".$this->table."<br/>\n";
$text .= "<b>PK</b>: ".$this->pkField."<br/>\n";
$text .= "<b>Fields</b></b>: <br/>\n";
foreach($this->fields as $name => $type){
	$value = $type['value'] == null ? "IS EMPTY" : "<u>".$type['value']."</u>";
	$upd = $type['is_changed'] == true ? " (has been changed)" : "";
	$text .= "-<b>". $type['name']."</b>(". $type['type']."): ";
	if($type['type'] == "text"){
		$text .= "&nbsp;&nbsp;".$upd."<br/>\n".substr(htmlspecialchars($value),0,100)."....<br/>\n";
	}else{
		$text .= "".$value."".$upd."<br/>\n";
	}
}

$text .= "<b>has_errors</b>: ".count($this->error)."<br/>\n";
foreach ($this->error as $key => $value) {
	$text .= "-$key: ".$value."<br/>\n";
}

return $text;

