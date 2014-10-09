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
	final function __construct($search=null){return ( include("includes/construct.php") );}
	public function store($throwOnError=CASUAL_MODEL_THROW_EXCEPTIONS,$forceinsert=CASUAL_MODEL_FORCE_INSERT){return ( include("includes/store.php") );}
	public function getValue($name){return ( include("includes/getFunction.php") );	}
	public function setValue($name,$value,$is_init=false){return ( include("includes/setFunction.php") );}
	public function __get($name){return $this->getValue($name);	}
	public function __set($name,$value){return $this->setValue($name,$value);	}
	public function __isset($name){return ( include("includes/isset.php") );}
	public function is_valid(){return count($this->error) == 0;	}
	public function __toString(){ return ( include("includes/tostring.php") );}
	public function getLink($name){ return ( include("includes/getLink.php") );}
	public function setLink($name,$value){return ( include("includes/setLink.php") );}
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