<?php
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

