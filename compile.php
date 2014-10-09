<?php

function recur_compile($file,$first ){
	if(!file_exists("src/".$file)){
		die("file not found: src/".$file);
	}
	$data = file_get_contents("src/".$file);
	echo "joining ".$file."<br/>\n";
	preg_match_all("/return\\s*\\(\\s*(include|require|require_once)\\s*\\(([^)]+)\\s*\\)\\s*\\);/", $data, $results,PREG_SET_ORDER);
	foreach($results as $result){
		$file2 = str_replace(array("'",'"'),"",trim($result[2]));
		$result22 = recur_compile($file2,2);

		 $result1 = str_replace(array("<"."?php"),"",$result22);

		 $result2 = "\n#--------start file ".$file2." \n".$result1;
		 $result3 = $result2. "\n#--------end file ".$file2." \n";
		$data = str_replace($result[0], $result3 , $data);
	}

	return $data;
}

$data = recur_compile("model.php",false);
$outputfile = 'casualmodel_singlefile.php';
file_put_contents($outputfile, $data);
echo "file written: $outputfile";
