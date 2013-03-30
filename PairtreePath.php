<?php

/**
 * The PairtreePath class is the one that called by Storage and Client
 * 
 
 * @todo Implement log4php or other logging system
 */

/**
 * These are the main functions that are used to create, get and set paths
 * 
 */
class PairtreePath {
	
	//regexes appear to be standard
	public $encode_regex = '(?u)["\*\+,<=>\?\\\^\|]|[^!-~]';
	public $decode_regex = '(?u)\^(..)';
	
	/**
	 * Function to encode a string into 2 character hex string
	 * @param string $id
	 * @return returns a hexadecimal character
	 */
	public function char2hex($id) {
		return chr(hexdec($id));
	}
	
	/**
	 * Function to convert a 2 character hex character to decimal
	 * @param string $id
	 * @return returns a decimal string converted from hexadecimal
	 */
	public function hex2char($id) {
		return '^'.sprintf("%02x",ord($id));
	}
	/**
	 * Function clean the path and remove anything that may cause the underlying filesystem issues
	 * 
	 * The string is initally encoded in utf-8
	 * 
	 * Secondly using the Erik Hetzner's regex (taken from the Python version) to have a second pass
	 * at anything that might cause issues. 
	 * 
	 * Then the path is rejoined returned
	 * 
	 * The /+= characters must be replaced as per the spec
	 * @param string $id
	 */
	public function id_encode($id) {
		//first pass: utf8 encode the string
		$encode = utf8_encode($id);
		$newid = preg_replace('/["\*\+,<=>\?\\\^\|]|[^!-~]/u', self::hex2char($encode), $encode);
		
		//second pass: make anything that gives the filesystem issues into hexadecimal
		$second_pass_m = array(':'=>'+', '.'=>',', '/'=>'=');
		
		$arr = str_split($newid);
		$second_pass = array();
		foreach ($arr as $chr) {
		    if (array_key_exists($chr, $second_pass_m)) {
		    	$second_pass[] = $second_pass_m[$chr];
		    } else {
		    	$second_pass[] = $chr;
		    }
		}
		
		$enc_id = join("", $second_pass);
		return join('', preg_split('/(.{2})/us', $enc_id, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE));
	}
	
	/**
	 * Function to decode the the ppath file back from the hex and returns a string
	 * @param string $id
	 */
	public function id_decode($id) {
		$second_pass_m = array('+'=>':', ','=>'.', '='=>'/');
		
		$arr = str_split($id);
		$second_pass = array();
		foreach ($arr as $chr) {
			if (array_key_exists($chr, $second_pass_m)) {
				$second_pass[] = $second_pass_m[$chr];
			} else {
				$second_pass[] = $chr;
			}
		}
		$dec_id = join("", $second_pass);
		$ppath = preg_replace('/\^(..)/', self::char2hex($dec_id), $dec_id);
		return $ppath;
	}
	
	/**
	 * Function returns the exploded path of the directory
	 * pairtree_root/fo/ob/ar/+/  --> 'foobar:'
	 * @param string $dirpath
	 * @return Path for the Pairtree object
	 */
	public function get_id_from_dirpath ($dirpath, $pairtree=null) {
		return self::id_decode(self::get_path_from_dirpath($dirpath));
	}
	
	/**
	 * Function to determine the ppath from the directory path
	 * If we get ab/cd, then function should return ab cd
	 * @param string $dirpath
	 * @param string $pairtree
	 * @return the path
	 */
	public function get_path_from_dirpath ($dirpath, $pairtree=null) {
		if ($pairtree) {
			$dirpath = str_replace($pairtree, '', $dirpath);
		}
		return join("", explode(DIRECTORY_SEPARATOR, $dirpath));
	}
	
	/**
	 * Function to return the id as the directory identity
	 * "foobar://ark.1" --> "fo/ob/ar/+=/ar/k,/1"
	 * @param string $id
	 * @param string $pairtree
	 * @param int $shorty
	 */
	public function id_to_dirpath ($id, $pairtree=null, $shorty=2) {
		return join('/', self::id_to_dirlist($id));
	}

	/**
	 * Function to return the id as the directory list
	 * 
	 * foobar://ark.1" --> ["fo","ob","ar","+=","ar","k,","1"]
	 * @param string $id
	 * @param string $pairtree
	 * @param int $shorty
	 */
	public function id_to_dirlist ($id, $pairtree=null, $shorty=2) {		
		return preg_split('/(.{'.$shorty.'})/us', $id, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
	}
	
	
}