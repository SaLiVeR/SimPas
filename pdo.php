<?php

/*-----------------------------------

* pdo.php - SimPas database file
* (c) Macsch15 - web@macsch15.pl

-----------------------------------*/

class SimPasDB{
	protected $db_connection;
	protected $query = '';

	public $num_query = 0;
	public $debug;

	public function __construct(){
		$data = array_merge(require_once __DIR__ . DIRECTORY_SEPARATOR . 'db_conf.php');

		try{
			$this -> db_connection = new PDO('mysql:host=' . $data['db_server'] . ';port=' . $data['db_port'] . ';dbname=' . $data['db_name'], $data['db_username'], $data['db_password']);
		}catch(PDOException $error){
			throw new Exception($error -> getMessage());
		}
	}

	public function buildSelect(array $data){
		$query = '';

		if(isset($data['select']) && isset($data['from'])){
			$query .= 'SELECT ' . $data['select'] . ' FROM ' . $data['from'];
		}

		if(isset($data['where'])){
			$query .= ' WHERE ' . $data['where'];
		}

		if(isset($data['order'])){
			$query .= ' ORDER BY ' . $data['order'];
		}

		if(isset($data['limit'])){
			$query .= ' LIMIT ' . $data['limit'];
		}

		if($query !== ''){
			$this -> num_query++;

			$startTimer = microtime(true);
			$this -> query = $this -> db_connection -> prepare($query);
			$this -> query -> execute();
			$this -> debug[][(string)round(microtime(true) - $startTimer, 4)] = $query;
		}

		return $this -> query;
	}

	public function fetch(){
		return $this -> query -> fetchAll();
	}
	
	public function buildInsert($table, array $data){
		$this -> num_query++;
		
		$insert = 'INSERT INTO ' . $table;
		
		foreach($data as $column => $value){
			$columns[] = $column;
			$values[] = '"' . $value . '"';
		}
		
		$insert .= ' (' . implode($columns, ',') . ') ';
		$insert .= 'VALUES (' . implode($values, ',') . '); ';
		
		$startTimer = microtime(true);
		$this -> db_connection -> exec($insert);
		$this -> debug[][(string)round(microtime(true) - $startTimer, 4)] = $insert;
	}
	
	public function countRows(array $data, $bool = false){
		$count = $this -> buildSelect($data);
		
		if($bool){
			if($count -> rowCount() == 0){
				return false;
			}elseif($count -> rowCount() > 0){
				return true;
			}
		}
		
		return $count -> rowCount();
	}
	
	public function execQuery($query){
		return $this -> db_connection -> exec($query);
	}	
	
	public function getNumQuery(){
		return $this -> num_query;
	}
	
	public function getDebug(){
		return $this -> debug;
	}	
}