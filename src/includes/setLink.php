<?php
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

