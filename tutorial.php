<?php
include "MeekroDB.php";
include "src/model.php";
DB::$user = 'root';
DB::$password = '';
DB::$dbName = 'test';

class UserCasualModel extends CasualModel{
		var $table = 'user';
		var $pkField = 'id';
		var $fields = array(
			'name' => array('type' => 'varchar(32)'),
			'surname' => array('type' => 'varchar(32)'),
			'birthday' => array('type' => 'datetime'),
			'joindate' => array('type' => 'int(11)')
		);
	}

	$user = new UserCasualModel();
	$user->name = "Daenerys";
	echo $user->name;
	$insertid = $user->store();

	$user2 = new UserCasualModel($insertid);
	echo $user2->name."\n";
	$user2->name = "Khaleesi";
	$user2->store();
	$lastid = $user2->pk();
	echo $user2->name."\n";

	echo $user2;