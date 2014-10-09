<?php


abstract class CasualModel{
	var $table = null;
	//name, default, value, originalValue, is_changed, type
	var $fields = array();
	var $pkField = null;
	var $error = array();
    var $fieldNameToRegExFilter = array();
	private $used_search = null;

	// linkname => array("{type}","{from}","{to}","{class}")
	// linkname => array("tomany","id","id","users")
	// linkname => array("toone","id","id","flabber")
	var $links = array();

    var $mySQLFieldTypes = array
    (
        // NUMERIC
        'INT' => array('type' => 'int', 'charLimit' => 11),
        'TINYINT' => array('type' => 'int', 'charLimit' => 4),
        'SMALLINT' => array('type' => 'int', 'charLimit' => 5),
        'MEDIUMINT' => array('type' => 'int', 'charLimit' => 9),
        'BIGINT' => array('type' => 'int', 'charLimit' => 20),
        'FLOAT' => array('type' => 'float', 'charLimit' => 24),
        'DOUBLE' => array('type' => 'double', 'charLimit' => 53),
        'DECIMAL' => array('type' => 'double', 'charLimit' => 30),

        // BOOLEAN
        'BOOLEAN' => array('type' => 'boolean', 'charLimit' => 5, 'max'=> 1, 'min'=> 0),

        // DATES
        'DATE' => array('type' => 'date', 'charLimit' => 10),
        'DATETIME' => array('type' => 'datetime', 'charLimit' => 19),
        'TIME' => array('type' => 'date', 'charLimit' => 8),
        'TIMESTAMP' => array('type' => 'date', 'charLimit' => 11),
        'YEAR' => array('type' => 'int', 'charLimit' => 4),

        // STRING
        'CHAR' => array('type' => 'char', 'charLimit' => 1),
        'VARCHAR' => array('type' => 'string', 'charLimit' => 255),
        'TEXT' => array('type' => 'string', 'charLimit' => 65535),
        'BLOB' => array('type' => 'string', 'charLimit' => 65535),
        'TINYBLOB' => array('type' => 'string', 'charLimit' => 255),
        'TINYTEXT' => array('type' => 'string', 'charLimit' => 255),
        'MEDIUMBLOB' => array('type' => 'string', 'charLimit' => 16777215),
        'MEDIUMTEXT' => array('type' => 'string', 'charLimit' => 16777215),
        'LONGBLOB' => array('type' => 'string', 'charLimit' => 4294967295),
        'LONGTEXT' => array('type' => 'string', 'charLimit' => 4294967295),

        // ENUM
        'ENUM' => array('type' => 'enum')
    );

	// if search == null there is a new object
	final function __construct($search=null){
#--------start file includes/construct.php 

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
#--------end file includes/construct.php 
}
	public function store($throwOnError=CASUAL_MODEL_THROW_EXCEPTIONS,$forceinsert=CASUAL_MODEL_FORCE_INSERT){
#--------start file includes/store.php 

$updatedFields = array();

if($this->used_search == null || $forceinsert == true){
	//insert
	if($this->pre_insert() !== false){
		$updatedFields = $this->getUpdatedFields($forceinsert);
		if(count($updatedFields) == 0){

			$this->error[] = "No fields have been edited, please make changes or press 'Cancel'";
		}else{
			try{
				DB::insert($this->table,$updatedFields);
				$this->used_search = DB::insertId();
                $this->id = $this->used_search;
			}catch(Exception $e){
				if($throwOnError == true){
					throw new Exception("DB error: ".$e->getMessage(). " on ".$e->getFile().":".$e->getLine(), 1);
				}
				$this->error[] = $e->getMessage(). " on ".$e->getFile().":".$e->getLine();
			}
		}
		$this->post_insert();
		return $this->used_search;
	}else{
		$this->error[] = "the update has been canceled from the pre_insert";
	}
}else{
	if($this->pre_update() !== false){
		$updatedFields = $this->getUpdatedFields($forceinsert);

		//update
		if(count($updatedFields) == 0){

			$this->error[] = "No fields have been edited, please make changes or press 'Cancel'";
		}else{
			try{
				DB::update($this->table,$updatedFields,$this->pkField." = %d",$this->used_search);
			}catch(Exception $e){
				if($throwOnError == true){
					throw new Exception("DB error: ".$e->getMessage(). " on ".$e->getFile().":".$e->getLine(), 1);
				}
				$this->error[] = $e->getMessage(). " on ".$e->getFile().":".$e->getLine();
			}
		}
		$this->post_update();
		return $this->used_search;
	}else{
		$this->error[] = "the update has been canceled from the pre_update";
	}

}


#--------end file includes/store.php 
}
	public function getValue($name){
#--------start file includes/getFunction.php 

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
#--------end file includes/getFunction.php 
	}
	public function setValue($name,$value,$is_init=false){
#--------start file includes/setFunction.php 

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
#--------end file includes/setFunction.php 
}
	public function __get($name){return $this->getValue($name);	}
	public function __set($name,$value){return $this->setValue($name,$value);	}
	public function __isset($name){
#--------start file includes/isset.php 


$nameLower= strtolower($name);


if(isset($this->fields[$nameLower])){		
	return true;
}elseif($nameLower == $this->pkField){
	return true;
}else{
	return false;	
}
#--------end file includes/isset.php 
}
	public function is_valid(){return count($this->error) == 0;	}
	public function __toString(){ 
#--------start file includes/tostring.php 


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


#--------end file includes/tostring.php 
}
	public function getLink($name){ 
#--------start file includes/getLink.php 


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
#--------end file includes/getLink.php 
}
	public function setLink($name,$value){
#--------start file includes/setLink.php 

	if(isset($this->links[$name])){
		$type = strtolower($this->links[$name][0]);
		$from = $this->links[$name][1];
		$to = $this->links[$name][2];
		$class = $this->links[$name][3];
		if($type == 'tomany'){
			if(is_array($value)){
				$items = $this->getLink($name);
				foreach($items as $i){
					$i->delete();
				}
				$left = $this->getValue($from);
				foreach($value as $i){
					$i->setValue($to,$left);
					$i->store(true,true);
				}
			}else{
				throw new Exception("Was expecting an array", 1);
			}
		}elseif($type == 'toone'){
			//toone is right to left
			// select one from the other table
			if($value instanceof $class){
				$value->store();
				$right = $value->getValue($to);
				$this->setValue($from,$right);
			}else{
				throw new Exception("You need to put an instance of the right class here!", 1);
			}
		}else{
			throw new Exception("Foulthy link type is set", 1);

		}
	}else{
		$this->error[] = "Unknown link found: ".$nameLower;
	}


#--------end file includes/setLink.php 
}
	public function delete(){ DB::delete($this->getTableName(),$this->pkField."=%d",$this->used_search);}
	public function pk(){ return $this->used_search;}

	//events
	public function pre_insert(){}
	public function post_insert(){}
	public function pre_update(){}
	public function post_update(){}

	function formatTableName($table) {
	    $table = str_replace('`', '', $table);
	    if (strpos($table, '.')) {
	      list($table_db, $table_table) = explode('.', $table, 2);
	      $table = "`$table_db`.`$table_table`";
	    } else {
	      $table = "`$table`";
	    }

	    return $table;
	  }

	function printShort(){
		$return = array();
		foreach($this->fields as $name => $type){
			$value = $type['value'] == null ? "null" : htmlspecialchars($type['value']);
			if($type['type'] == "text"){
				$return[] = substr(htmlspecialchars($value),0,20)."....";
			}else{
				$return[]  = $value;
			}
		}
		return implode(" - ", $return);

	}

	final function getAllFields(){
		$items = array();
		$items[] =  $this->formatTableName($this->pkField);
		foreach($this->fields as $field){
			$items[] = $this->formatTableName($field['name']);
		}
		return implode(",", $items);
	}


	public static function getAllQuery( $where =null,$limit=null){
		$objectDetails = new static();
		$whereStatement = $where != null ? DB::sqleval("WHERE %l",$where)->text : "";
		$table = $objectDetails->getTableName();
		$d = DB::sqleval("Select %l FROM %b  %l",$objectDetails->getAllFields(),$table,$whereStatement);
		$sql = $d->text;
		if($limit !== null){
			$sql .= " Limit ".$limit;
		}
		return $sql;
	}


	public static function getAll( $where =null,$limit=null){
		$sql = self::getAllQuery($where,$limit);
		return self::queryToClasses($sql);
	}
	static function queryToClasses($query){
		$result = DB::query($query);
		$ret = array();
		foreach($result as $res){
			$ret[] = new static($res);
		}
		return $ret;

	}

	function getKeyValue(){
		$items = array();
		foreach($this->fields as $field){
			$items[$field['name']] = $field['value'];
		}
		return $items;
	}
	function getTableName(){
		return $this->formatTableName($this->table);
	}

	function getUpdatedFields($forceall=false){
		$updatedFields = array();
		foreach($this->fields as $name => $field){
			if($field['is_changed'] == true || $forceall){
				$updatedFields[$field['name']] = $field['value'];
			}
		}
		return $updatedFields;
	}
	function import($data,$ignore_unkown_fields=false){
		foreach($data as $name => $value){
			$lower = strtolower($name);
			if($lower == strtolower($this->pkField)){
				$this->used_search = $value;
			}elseif(isset($this->fields[$lower])){
				$this->setValue($lower,$value);
			}elseif($ignore_unkown_fields == false){
				throw new Exception("Unknown field found in the (array)insert: ".$lower."! table: '".$this->table."'", 1);
			}
		}
	}

	function customExport($array){
		//if you exend this you can controll what will get exported!
		return $array;
	}

	// If you set the whitelist you only will show the given fields
	function export($whitelist=null){
		$fields = array();
		if($this->used_search != null){
			if($whitelist === null || in_array($this->pkField, $whitelist)){
				$fields[$this->pkField] = $this->used_search;
			}

		}
		foreach($this->fields as $name => $field){
			if($whitelist === null || in_array($field['name'], $whitelist)){
				if($field['type'] == 'json' && is_string($field['value'])){
					if(empty($field['value'])){
						$fields[$field['name']] = array();
					}else{
						$fields[$field['name']] = json_decode($field['value'],true);
						
					}
				}else{
					$fields[$field['name']] = $field['value'];
				}
			}
		}


		return $this->customExport($fields);
	}


	// Error
	public static function exportAll($where =null, $limit=null, $whitelist=null){
		$sql = self::getAllQuery($where, $limit);
		$result = DB::query($sql);
		return self::parseForExport($result, $whitelist);
	}
	// If you set the whitelist you only will show the given fields
	public static function exportToArray(array $array, $whitelist=null){
		$export = array();
		foreach ($array as $key => $value) {
			$export[] = $value->export($whitelist);
		}
		return $export;
	}

	public static function parseForExport(array $array,$whitelist=null){
		$ret = array();
			$temp = new static();
		foreach($array as $res){
			$temp->import($res,true);
			$ret[] = $temp->export($whitelist);
		}
		return $ret;
	}

	function copy(){
		$class = get_class($this);
		$copy = new  $class();
		$copy->import($this->getKeyValue());
		return $copy;
	}

    /**
     * Verifies the update base and saves errors to $this->errors
     *
     * @return bool
     */
    function verifyBase()
    {
        $errorFields = array();

        foreach($this->fields AS $fieldName => $mysqlProperties)
        {
            $resultArray = $this->verifyField($fieldName, $mysqlProperties['type']);

            if(!is_null($resultArray) && !$resultArray['result']){
                $errorFields[$fieldName] = $resultArray['errors'];
            }
        }

        $this->error = array_merge($this->error, $errorFields);
        return empty($errorFields);
    }

    /**
     * @param $fieldName
     * @param $mysqlProperties
     * @return array
     */
    function verifyField($fieldName, $mysqlProperties)
    {
        $errorField = array();
        $charLengthOk = false;
        $fieldIntegrityOk = false;

        $fieldProperties = trim(strtoupper(str_replace(array('(', ')'), ' ', $mysqlProperties)));
        $propertyArray = explode(' ', $fieldProperties);
        $defaultProperties = $this->mySQLFieldTypes[$propertyArray[0]];
        $fieldValue = $this->$fieldName;

        if(is_null($fieldValue)){
            return NULL;
        }

        // Check for needed RegEx
        if(isset($this->fieldNameToRegExFilter[$fieldName]))
            $regExFilter = $this->fieldNameToRegExFilter[$fieldName];

        // Set the char size limit
        if(count($propertyArray) > 1)
            $fieldSizeLimit = $propertyArray[1];
        else
            $fieldSizeLimit = $defaultProperties['charLimit'];

        // Check the entity size
        $tmpCharArray = str_split($fieldName);
        $charLengthOk = (strlen($this->$fieldName) < $fieldSizeLimit || $fieldSizeLimit == 1) ||
            ($defaultProperties['type'] != 'int' && $this->$fieldName <= $fieldSizeLimit) ||
            ($defaultProperties['type'] == 'int' && strlen($charLengthOk) == $fieldSizeLimit && $tmpCharArray[0]== '-');

        // Check default var filtering
        if(!isset($regExFilter)){
            switch($defaultProperties['type'])
            {
                case 'int':
                    $fieldIntegrityOk = ctype_digit($fieldValue) || is_int($fieldValue);
                    break;
                case 'date':
                    if(strtotime($fieldValue))
                        $fieldIntegrityOk = preg_match('^(17|18|19|20|21)\d\d([- /.])(0[1-9]|1[012])\2(0[1-9]|[12][0-9]|3[01])$', $fieldValue);
                    else
                        $fieldIntegrityOk = false;
                    break;
                case 'datetime':
                    if(strtotime($fieldValue))
                        $fieldIntegrityOk = preg_match('^(?ni:(?=\d)((?\'year\'((1[6-9])|([2-9]\d))\d\d)(?\'sep\'[/.-])(?\'month\'0?[1-9]|1[012])\2(?\'day\'((?<!(\2((0?[2469])|11)\2))31)|(?<!\2(0?2)\2)(29|30)|((?<=((1[6-9]|[2-9]\d)(0[48]|[2468][048]|[13579][26])|(16|[2468][048]|[3579][26])00)\2\3\2)29)|((0?[1-9])|(1\d)|(2[0-8])))(?:(?=\x20\d)\x20|$))?((?<time>((0?[1-9]|1[012])(:[0-5]\d){0,2}(\x20[AP]M))|([01]\d|2[0-3])(:[0-5]\d){1,2}))?)$', $fieldValue);
                    else
                        $fieldIntegrityOk = false;
                    break;
                case 'double':
                    $fieldIntegrityOk = ((double) $fieldValue) || $fieldValue == 0;
                    break;
                case 'float':
                    $fieldIntegrityOk = ((float) $fieldValue) || $fieldValue == 0;
                    break;
                case 'string':
                    $fieldIntegrityOk = ((string) $fieldValue) || is_string($fieldValue);
                    break;
                case 'char':
                    $fieldIntegrityOk = (count($fieldValue) == $fieldSizeLimit || (is_int($fieldValue) && (chr($fieldValue) || chr($fieldValue) === 0 )));
                    break;
            }
        }
        else
            $fieldIntegrityOk = preg_match($regExFilter,$fieldValue);

        if(!$charLengthOk) $errorField[] = ErrorMessageModel::verifyCodeToMessage('OVERSIZE');
        if(!$fieldIntegrityOk) $errorField[] = ErrorMessageModel::verifyCodeToMessage('CORRUPT');

        return array('result' => (sizeOf($errorField) < 1), 'errors' => $errorField);
    }
}