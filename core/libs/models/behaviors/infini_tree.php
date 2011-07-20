<?php

App::import('Behavior', 'Tree');

class InfiniTreeBehavior extends TreeBehavior {
	public $name = 'ScopedTree';

	public function setup($Model, $config = array()) {
		
		$defaults = array(
			'scopeField' => false
		);
		
		$config = array_merge(array('scopeField' => false), $config);
		
		return parent::setup($Model, $config);
	}

	public function afterSave($Model, $created) {
		if(!$this->__setScope($Model, $Model->data[$Model->alias])) {
			return false;
		}
		
		$return = parent::afterSave($Model, $created);
		return $return;
	}
	
	public function beforeDelete($Model) {
		return parent::beforeDelete($Model);
	}
	
	public function beforeSave($Model) {
		if(!$Model->id || $Model->id && array_key_exists($this->settings[$Model->alias]['parent'], $Model->data)) {
			if(!$this->__setScope($Model, $Model->data[$Model->alias], true)) {
				return false;
			}
		}

		return parent::beforeSave($Model);
	}

	public function childcount($Model, $id = null, $direct = false) {
		if($this->scoped($Model)) {
			if(empty($id)) {
				return false;
			}
			
			$id = $this->__setScopeFromId($Model, $id);
		}
		
		return parent::childcount($Model, $id, $direct);
	}
	
	public function children($Model, $id = null, $direct = false, $fields = null, $order = null, $limit = null, $page = 1, $recursive = null) {
		if($this->scoped($Model)) {
			if(empty($id)) {
				return false;
			}
			
			$id = $this->__setScopeFromId($Model, $id);
		}
		
		return parent::children($Model, $id, $direct, $fields, $order, $limit, $page, $recursive);
	}
	
	public function generatetreelist($Model, $conditions = null, $keyPath = null, $valuePath = null, $spacer = '_', $recursive = null) {
		$this->__setScope($Model, $conditions);
		return parent::generatetreelist($Model, $conditions, $keyPath, $valuePath, $spacer, $recursive);
	}

	public function getparentnode($Model, $id = null, $fields = null, $recursive = null) {
		if($this->scoped($Model)) {
			if(empty($id)) {
				return false;
			}
			
			$id = $this->__setScopeFromId($Model, $id);
		}
		
		return parent::getparentnode($Model, $id, $fields, $recursive);
	}
	
	public function getpath($Model, $id = null, $fields = null, $recursive = null) {
		if($this->scoped($Model)) {
			if(empty($id)) {
				return false;
			}
			
			$id = $this->__setScopeFromId($Model, $id);
		}
		
		return parent::getpath($Model, $id, $fields, $recursive);
	}
	
	public function movedown($Model, $id = null, $number = 1) {
		if($this->scoped($Model)) {
			if(empty($id)) {
				return false;
			}
			
			$id = $this->__setScopeFromId($Model, $id);
		}
		
		return parent::movedown($Model, $id, $number);
	}
	
	public function moveup($Model, $id = null, $number = 1) {
		if($this->scoped($Model)) {
			if(empty($id)) {
				return false;
			}
			
			$id = $this->__setScopeFromId($Model, $id);
		}
		
		return parent::moveup($Model, $id, $number);
	}

	public function recover($Model, $mode = 'parent', $missingParentAction = null) {
		return parent::recover($Model, $mode, $missingParentAction);
	}
	
	public function reorder($Model, $options = array()) {
		return parent::reorder($Model, $options);
	}

	public function removefromtree($Model, $id = null, $delete = false) {
		if($this->scoped($Model)) {
			if(empty($id)) {
				return false;
			}
			
			$id = $this->__setScopeFromId($Model, $id);
		}
		
		return parent::removefromtree($Model, $id, $delete);
	}

	public function verify($Model, $scope = null) {
		$this->__setScope($Model, $scope);
		
		return parent::verify($Model);
	}
	
	public function treeSave($Model, $data = array(), $options = array()) {
		if(empty($data)) {
			return false;
		}
		
		if($this->scoped($Model)) {
			
			if(empty($options['scope'])) {
				return false;
			}
			
			$this->__setScope($Model, $options['scope']);
		}
		
		if(!$data){
			return false;
		}

		//$Model->transaction();
		
		if($this->__doTreeSave($Model, $data, array('scope' => $options['scope'], 'parent' => null, 'depth' => 0))) {
			//$Model->transaction(true);
			return true;
		}
		
		//$Model->transaction(false);
		return false;
	}
	
	private function __doTreeSave($Model, $data, $options = array()) {		
		$return = false;
		
		//Special case in the first run
		if($options['depth'] > 0) {
			$Model->create();
			if(!$Model->save(array_diff_key(array_merge(array($this->settings[$Model->alias]['scopeField']  => $options['scope'], $this->settings[$Model->alias]['parent'] => $options['parent']), $data), array($Model->alias => $Model->alias)))) {
				debug($Model->validationErrors);
				debug('fail');
				return false;
			}
			
			$options['parent'] = $Model->getInsertID();
			
			$return = true;
		}
		
		if(array_key_exists($Model->alias, $data)) {
			$options['depth']++;
			
			foreach($data[$Model->alias] as $childData) {
				if(!$this->__doTreeSave($Model, $childData, $options)) {
					return false;
				};
			}
			
			$return = true;
		}
		
		return $return;
	}
	
	private function __setScopeFromId($Model, $id) {
		if($this->scoped($Model)) {
			if(empty($id)) {
				return false;
			}
			
			if(!is_array($id)) {
				//Fetch scope from row id given
				$scope = $this->__getScopeFromId($Model, $id);
				
				if(!$scope) {
					return false;
				}
			} else {
				$scope = $id['scope'];
				$id = null;
			}
			
			$this->__setScope($Model, $scope);
		}
		
		return $id;
	}
	
	private function __setScope($Model, $data, $beforeSave = false) {
		//See if autoscoping is enabled
		if(!$this->scoped($Model)) {
			return true;
		}
		
		$scope = null;
		
		//Is the scope given as an id?
		if(!is_array($data)) {
			$scope = $data;
			
		//Is the scopeField given in the data array?
		} elseif(array_key_exists($this->settings[$Model->alias]['scopeField'], $data)) {
			$scope = $data[$this->settings[$Model->alias]['scopeField']];
			
		//Is the scopeField given in the conditions for a find?
		} elseif(array_key_exists($Model->alias . '.' . $this->settings[$Model->alias]['scopeField'], $data)) {
			$scope = $data[$Model->alias . '.' . $this->settings[$Model->alias]['scopeField']];
			
		//Is the parent_id given in the data array?
		} elseif(array_key_exists($this->settings[$Model->alias]['parent'], $data) && !empty($data[$this->settings[$Model->alias]['parent']])) {
			$scope = $this->__getScopeFromId($Model, $data[$this->settings[$Model->alias]['parent']]);
		} else {
			
		}
		
		if($scope) {
			$this->settings[$Model->alias]['scope'] = $Model->alias . '.' . $this->settings[$Model->alias]['scopeField'] . " = '" . $scope . "'";
			
			if($beforeSave) {
				$Model->data[$Model->alias][$this->settings[$Model->alias]['scopeField']] = $scope;
			}
			return true;
		}
		
		return false;
	}
	
	private function __getScopeFromId($Model, $id) {
		$data = $Model->find('first', array(
			'fields' => $this->settings[$Model->alias]['scopeField'],
			'conditions' => array(
				$Model->alias . '.' . $Model->primaryKey => $id
			),
			'contain' => false
		));
		
		return $data[$Model->alias][$this->settings[$Model->alias]['scopeField']];
	}
	
	public function scoped($Model) {
		return !empty($this->settings[$Model->alias]['scopeField']);
	}
}
