<?php
/**
 * Opauth core
 *
 */
class Opauth{
/**
 * User configuraable settings
 * 
 * - Do not refer to this anywhere in logic, except in __construct() of Opauth
 * - TODO: Documentation on config
 */
	public $config;	
	
/**
 * Environment variables
 */
	public $env;
	
/** 
 * Defined strategies
 * - array key	: URL-friendly name, preferably all lowercase
 * - name		: Class and file name. If unset, Opauth automatically capitalize first letter as name
 * 
 * eg. array( 'facebook', 'flickr' );
 * eg. array( 'foobar' => array( 'name' => 'FooBar') );
 * 
 */
	public $strategies;
	
	public function __construct($config = array()){
		
	/**
	 * Configurable settings
	 */
		$this->config = array_merge(array(
			'host' => 'http://'.$_SERVER['HTTP_HOST'],
			'path' => '/',
			'debug' => false
		), $config);
	
	/**
	 * Environment variables, including config
	 * Used mainly as accessors
	 */
		$this->env = array_merge(array(
			'uri' => $_SERVER['REQUEST_URI'],
			'LIB' => dirname(__FILE__).'/',
			'STRATEGY' => dirname(__FILE__).'/Strategy/',
			'complete_path' => str_replace("//", "/", $this->config['host'].$this->config['path'])
		), $this->config);
		
		$this->_loadStrategies();
		$this->_parseUri();
		
		/* Run */
		if (!empty($this->env['strategy'])){
			if (array_key_exists($this->env['strategy'], $this->strategies)){
				$strategy = $this->strategies[$this->env['strategy']];
				require $this->env['LIB'].'OpauthStrategy.php';
				require $this->env['STRATEGY'].$strategy['name'].'/'.$strategy['name'].'.php';
				
				$this->Strategy = new $strategy['name']($this, $strategy);
				$this->Strategy->callAction($this->env['action']);
			}
			else{
				trigger_error('Unsupported or undefined Opauth strategy - '.$this->env['strategy'], E_USER_ERROR);
			}
		}
	}
	
/**
 * Parses Request URI
 */
	protected function _parseUri(){
		$this->env['request'] = substr($this->env['uri'], strlen($this->env['path']) - 1);
		
		if (preg_match_all('/\/([A-Za-z0-9-_]+)/', $this->env['request'], $matches)){
			foreach ($matches[1] as $match){
				$this->env['params'][] = $match;
			}
		}
		
		if (!empty($this->env['params'][0])) $this->env['strategy'] = $this->env['params'][0];
		if (!empty($this->env['params'][1])) $this->env['action'] = $this->env['params'][1];
	}
	
/**
 * Load strategies from user-input $config
 */	
	protected function _loadStrategies(){
		if (isset($this->env['strategies']) && is_array($this->env['strategies']) && count($this->env['strategies']) > 0){
			foreach ($this->env['strategies'] as $key => $strategy){
				if (!is_array($strategy)){
					$key = $strategy;
					$strategy = array();
				}
				
				if (empty($strategy['name'])) $strategy['name'] = strtoupper(substr($key, 0, 1)).substr($key, 1);
				$this->strategies[$key] = $strategy;
			}
		}
		else{
			trigger_error('No Opauth strategies defined', E_USER_ERROR);
		}
	}
	
/**
 * Prints out variable with <pre> tags
 * - If debug is false, no printing
 */	
	public function debug($var){
		if ($this->env['debug'] !== false){
			echo "<pre>";
			print_r($var);
			echo "</pre>";
		}
	}
}