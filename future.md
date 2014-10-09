<?php

# future


// single item

$item = new UserModel(22); // OR UserModel::get(22);
$item->name = "test";

// couple to location table user(city=1) => cities(id=1,name=LA)
$item->city; // == (id=1,name=LA)

$item->city->id = 1; // pk is readonly
$item->city->name = 'SF'; // update back through the structure


$item->city = 5; // changes the ref
$item->city = new CityModel(); // changes the ref


// many to many talbes user(id=1)  user_to_product(user=1,product=2)  product(id=2,name="Iron throne")

$item->owns; // is ittarable list of products  [{id:2,name:"Iron throne"}]


$p = new ProductModel();
$p->name = "Valerian Sword";
$item->owns->add($p);

->count();
->get($index);
->shift();
->map();
->reduce();
->pop();
->push();



Select * from users
left join item_to_users as i2u ON i2u.user = users.id 
left join items as i ON i.i2u = i.id
Where users.name = 'Tinco'



for( users){
	for(itemstousers)
		if(i2u.user = users.id )
		for(items){
			if(i.i2u = i.id){

			}
		}
	}

}

Users::all()->where("Tinco", "!=", "name")->join("i2u")->join("items")

$where = new WhereClause('and');
$where->add("name = %s","Tinco")
UserCasualModel::all($where)->i2u->all()->items->export()

class user extends eloquent{
	$table = "users";
}