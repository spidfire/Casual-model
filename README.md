# CasualModel
This database model is created to help php developers to create easy and fast a easy to use database abstraction layer for your projects.


hosted at: [https://bitbucket.org/spidfire/mysql-database-abstraction/overview](https://bitbucket.org/spidfire/mysql-database-abstraction/overview)


## Requirements
* MeekroDB http://www.meekro.com/docs.php (only the MeekroDB.php)
* and the compiled version of CasualModel
* (optional the generate.php for code generation)

## Usage

First include MeekroDB.php and CasualModel.php

	include "MeekroDB.php";
	include "casualmodel.php";

And create a connection to the database for MeekroDB

	DB::$user = 'root';
	DB::$password = '';
	DB::$dbName = 'test';

creating a class is easy to do by using generate.php you only have to set the Database settings to your own database and load the page and chose the table you want to use.

The other way is to do it manually.
The bare minimum example is:

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

The variable `$table` is the name of table in MySQL this model is mapped to. 

The variable `$pkField` is the name of the field in this table where a record can uniquely identify itself with.

In `$fields` you put all the fields you want to be able to access.
With first the name of this field and after this the settings of this field like the type.

There is not yet a lot of functionality coupled to these types. The only one currently is if you use a date or datetime field and send a unix_timestamp to it will automatically convert it.

##Basic usage
The first step is to use the getters and setters.
You can just use it like:

	$user = new UserCasualModel();
This will create a new Object which you can manipulate.

First we will set some value to the model:

	$user->name = "Daenerys";
Now we can echo it using the same value

	echo $user->name;
>Daenerys

This value still resides in your local namespace so now we will store it in the database using

	$insertid = $user->store();
(you can also use the pk function like `$user->pk()` to get the value of the primary key or you can do `$user->id` if you happen to know the name of the pk)

Now we are going to get the record from the database and updating the name and store it again

	$user2 = new UserCasualModel($insertid);
	echo $user2->name;
	$user2->name = "Khaleesi";
	$user2->store();
	$lastid = $user2->pk();
	echo $user->name; 
>Daenerys
>
>Khaleesi

For debug you can just echo the object like:

	echo $user2;
This will return:

	Table: user
	PK: id
	Fields: 
	-name(varchar(32)): Khaleesi (has been changed)
	-surname(varchar(32)): IS EMPTY
	-birthday(datetime): 0000-00-00 00:00:00
	-joindate(int(11)): 0
	has_errors: 0

## Links
You can also use connections between tables to easy use the relations between them:

links are just like the fields defined in the class of an CasualModel like

	var $links = array(
		'birthplace' => array("toone","living","id","BirthplacesModel"),
		'cart' => array("tomany","id","user","ShoppingcartModel"),
	);
There are 2 types "toone" and "tomany".
It depends on the table which you need to use.

Like in this example there is one place a user is born and there are multiple Items in a basket of an customer.

### To One
You can use the toone relation just like the get and set only instead of a generic value of an integer or string you will be handeling an object like:

	$bi = new BirthplacesModel();
    $bi->name = "King's landing";
    $bi->country = "Westeros";

	$user = new UserCasualModel($lastid);
	$user->linkbirthplace = $bi;
	$user->store();

You can chain these actions to make it simple to get the info you need like:

	echo $user->linkbirthplace->name;
>King's landing


To get the value that was linked.
###To many
by using a tomany relation you get to deal with an array of values:

 	$items = array();       
 	$i = new ShoppingcartModel();
    $i->products = 22;
    $i->amount = 1;
    $items[] = $i;
    $i = new ShoppingcartModel();
    $i->products = 23;
    $i->amount = 1;
    $items[] = $i;
	$nt = new CustomerModel();
    $nt->store();
    $nt->linkcart = $items;

Now the linkcart contains two items.

	foreach( $nt->linkcart as $item){
		echo $item->products;
	}

## Some things who need better documentation:
### pre\_insert, pre\_update
This is a override able function in the CasualModel class

These functions get executed right before the store() function get called. you can use them for checking/editing values. and if you return a false you will prevent database insertion.

### post\_insert,post\_update
This is a override able function in the CasualModel class
No real power only for things like logging a database insertion or alteration ?

### delete()
Will delete the current record from the database (no recursion)

### getValue setValue getLink setLink

	$user->name == $user->getValue('name');
	$user->name = 2; == $user->setValue('name',2);
	$user->linkcart == $user->getLink('cart')
	$user->linkcart=$a; == $user->setLink('cart',$a);

#import()
Will let you import values. useful for importing $_POST arrays.
if you set the second var to true you will not get an error when there are unknown fields in the dataset.


##Licence

This software is distributed under the  [GNU GPL](http://opensource.org/licenses/gpl-2.0.php "GNU GPL")

But if you got changes or improvements always feel free to make a pull request.
