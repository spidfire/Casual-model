<?php

include "MeekroDB.php";
DB::$user = 'root';
DB::$password = '';
DB::$dbName = 'test';

if(!isset($_GET['table'])){
$tables = DB::queryFirstColumn("SHOW Tables");
foreach($tables as $t){ echo "<a href='?table=".$t."'>".$t."</a><br/>";}
die();
}else{
	$tables = DB::query("SHOW fields from `%l` ",$_GET['table']);
	$table = $_GET['table'];
	echo "<pre>";
	$classname = ucfirst(preg_replace("/[^\w]+/", "_", $table));
	echo "&lt;?php\n\n// This class has been generated using the generate.php\n";
	echo "&lt;?php\n\n// Look at https://bitbucket.org/spidfire/mysql-database-abstraction/overview form more information\n";
	echo "class ".$classname."CasualModel extends CasualModel{\n";
	echo "\t"."var \$table = '".$table."';\n";
	$rows = array();
	$pkField = "";
	foreach ($tables as $key => $t) {
		if($t['Key'] == "PRI"){
			$pkField = $t['Field'];
		}else{
			$rows[] = "\n\t\t'".$t['Field']."' => array('type' => '".$t['Type']."')";
		}
	}
	echo "\t"."var \$pkField = '".$pkField."';\n";
	echo "\t"."var \$links = array(\n";
		echo "\t"."\t".'//\'page\' => array("toone","pageid","id","pageModel"),'."\n";
		echo "\t"."\t".'//\'page\' => array("tomany","pageid","id","pageModel"),'."\n";
	echo "\t".");\n";
	echo "\t"."var \$fields = array(";

	echo implode(",", $rows);
	echo "\n\t".");\n";
	echo "\t"."function pre_update(){}\n";
	echo "\t"."function pre_insert(){}\n";
	echo "\t"."function post_insert(){}\n";
	echo "\t"."function post_update(){}\n";
	echo "}\n";
}