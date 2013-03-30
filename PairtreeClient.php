<?php
/**
 * @file
 * Functions to use Redis as an underlying store for the Pairtree
 * instead of the filesystem
 * 
 */

require __DIR__.'/PairtreePath.php';

require 'Predis/Autoloader.php';

/**
 * A client implementation of using Redis instead of the file system. 
 * Standard Redis style suggests using ':' as a separator but this is 
 * reserved to some extent in Pairtree so using the directory separator 
 * in this instance. Also maintains conformity with the standard. 
 * 
 * A conversion between the two may be necessary in future and is something
 * to be discussed. 
 *
 */
class PairtreeClient {
	//The PairtreePath
	protected $pp;
	
	//The base name used for the Redis set
	protected $dir;
	
	//The uri base used in the keys
	protected $uriBase;
	
	//The length of the shorty used to split the name into the path
	protected $shortyLength;
	
	//the hash library used 
	protected $hashlib;
	
	//redis client
	protected $redis;
	
	/**
	 * Constructor called by the storage factory
	 * 
	 * @param string $dir
	 *   Dir is the name of the base set which will be used to store the keys
	 * @param string $uriBase
	 *   uriBase is the Uri that we are using as a base for the key
	 * @param number $shorty
	 *   Used to divide the directory. Unused in the Redis version
	 * @param string $hashlib
	 *   String for the type of hash library being used.
	 */
	public function PairtreeClient($dir='data', $uriBase='http://', $shorty=2, $hashlib=null) 
    {
    	try 
    	{
    	    // Set up the Redis client.
            Predis\Autoloader::register();
            $this->redis = new Predis\Client();

            // Set up the values for the PairtreePath
		    $this->pp = new PairtreePath();
		    $this->dir = $dir;
		    $this->uriBase = $uriBase;
		    $this->shorty_length = $shorty;
		    $this->hashlib = $hashlib;
		
		    self::init_store($this->dir, $this->uriBase, $this->shorty_length);
    	} catch (Exception $e) {
    		print "Exception $e";
    	}
    }
	
	/**
	 * Function to create a base directory if it doesn't exist, 
	 * or return the existing parts if it does. With Redis, rather than writing
	 * files, the base directory is a hashset against which the files are assigned
	 * as keys. 
	 * 
	 * 
	 * @param string $dir
	 *   This string is the directory name which is used for the keys.
	 *   Default is data
	 * @param string $uriBase
	 *   The uriBase is the protocol used (could be http, https, ftp, rdf etc.)
	 * 
	 */
	private function init_store ($dir='data',$uriBase= 'http://') {
		//  Implementation of setting up the base directory
		if ($this->redis->hexists($dir, 'pairtree_prefix')) {
			// Get the uribase from the store and set it
			$this->uriBase = $this->redis->hget($dir, 'pairtree_prefix');
		} else {
			//The set does not exist. Create the roots as keys to the key.
			
			// Create the prefix.
			$this->redis->hset($dir, 'pairtree_prefix', $uriBase);
			// Create the root directory.
			$this->redis->hset($dir, 'pairtree_root', '');
			// create the Pairtree Version
			$conformance = "This directory conforms to Pairtree Version 0.1.\n
			Updated spec: http://www.cdlib.org/inside/diglib/pairtree/pairtreespec.html";
			$this->redis->hset($dir, 'pairtree_version0_1', $conformance);
		}
	}
	
	/**
	 * Function to encode the id in the Pairtree FS 0.1 style and replace unwanted characters
	 * Then 
	 * @param string  $id
	 */
	public function id_encode($id) {
		return $this->pp->id_encode($id);
	}

	/**
	 * Function to decode the id from Pairtree style back to its original form
	 * @param string $id
	 * @return decided id
	 */
	public function id_decode($id) {
		return $this->pp->id_decode($id);
	}
	
	/**
	 * Function to list the keys for a defined set
	 * 
	 * @param string $path 
	 *   The directory of the path
	 * @return Array
	 *    An array of the Keys
	 */
	public function listIds($dir = null) {
		$listdirs = array();
		$objects = array();
		// FS can use . as a root
		// Can something analogous exist in Redis?
		if (!$dir) {
			$dir = $this->dir;
		}
		$objects = $this->redis->smembers($dir. ':keys');
		return $objects;
	}
	
	/**
	 * Function to check if a path is a file
	 * @param string $id
	 * @param boolean
	 */
	public function isfile ($id, $filepath=null) {
		$isfile = false;
		$filepath = join(DIRECTORY_SEPARATOR, $this->pp->get_id_from_dirpath($id)).DIRECTORY_SEPARATOR.$stream;
		if (is_file($filepath)) {
			$isfile = true;
		}
		return $isfile;	
	}
	
	/**
	 * Function to check if a path is a key in the set. 
	 * @param string $id
	 * @param boolean
	 */
	public function isdirectory($id, $filepath=null) {
		$isdir = false;
		if (self::exists($id, $filepath)) {
			$isdir = TRUE;
		}
		return $isdir;
	}
	
	/**
	 * Function to create streams in the path
	 * @param string $id the main path
	 * @param string $path an optional subfolder
	 * @param string $streamName name of the file to be written to
	 * @param string $bytestream 
	 * @param int $buffer_size
	 * 
	 * @return boolean
	 */
	public function putStream($id, $path, $streamName, $bytestream, $buffer_size=8192) 
	{
		$stored = FALSE;
		$dirpath = null;
		if ($path) {
			if (self::exists($path) !== TRUE) {
				$dirpath = $id . DIRECTORY_SEPARATOR . $path;
				print "extra directory $dirpath";
			} 
		} else {
		    if (self::exists($id) !== TRUE) {
			    $dirpath = $id;
		    } 
		}
		if ($path) { print "path $dirpath"; }
		// Put the key in the set based on the dir name and 'keys'
		$this->redis->sadd($this->dir.':keys', (string) $dirpath);
        // Set the hash set with the streamName as the key.
        // If Redis confirms storage, confirm to the calling code
		if ($this->redis->hset( $dirpath, $streamName, $bytestream)) {
			// If set, then store the time.
			$this->redis->hset($dirpath, 'time', time());
			$stored = TRUE;
		}
		//@todo look at filesystem hashing for replication.
		return $stored;
	}
	
	/**
	 * Function to get the whole key
	 * @param string $id
	 *   String of the key name.
	 * @param Array
	 *   A hash of the key.
	 */
	public function getKey ($id) 
	{
		$key = array();
		$key = $this->redis->hgetall($id);
		if (!$key) {
			throw new Exception("getKey for $key failed");
		}

		return $key;
	}
	
	/**
	 * Function to return a filehandle object which is appendable
	 * 
	 * @param string $id the path
	 * @param string $path an optional subfolder
	 * @param string $streamName the filename to opened
	 * @return the filehandle object
	 */
	public function getAppendableStream($id, $path, $streamName) 
	{
		
	}
	
	/**
	 * Function to read the given filestream
	 * 
	 * @param string $id  
	 *   The given filepath id
	 * @param string $path 
	 *   The optional directory
	 * @param string $streamName 
	 *   The key to be opened in the 
	 * @param boolean $streamable if the filecontents are streamable or not @todo to implement this 
	 *  if streamble is true, then return the filehandle and close 
	 *  @return file object
	 */
	public function getStream($id, $path, $streamName, $streamable=false) 
	{
		$dirpath=null;
		if ($path) {
			if (self::exists($path, $id) !== TRUE) {
				throw new Exception('Stream does not exist');
			} else {
				$dirpath = $id.DIRECTORY_SEPARATOR.$path;
			}
		} else {
			if (self::exists($id) !== TRUE) {
				throw new Exception ('Directory did not exist');
			} else {
				$dirpath = $id;
			}
		}
		//  Do we need to test if it is a member of the set?
		$fileobj = $this->redis->hget($dirpath, $streamName);
        if (!$fileobj) {
        	throw new Exception("getStream Error: $streamName could not be retrieved from $dirpath");
        }
		return $fileobj; 
	}
	
	/**
	 * Removes the stream from the store
	 * 
	 * @param string $id
	 *   The key that is to be deleted
	 * @param string $streamName
	 *    The name of the stream for the key
	 * @param string $path - optional
	 *    Optional override of the base set, otherwise the default set is checked
	 * @param boolean
	 *    Set this to true to remove the key
	 * 
	 * @throws Exception
	 */
	public function delStream($id, $path=null, $streamName) {
		//$filepath = join(DIRECTORY_SEPARATOR, $this->pp->get_id_from_dirpath($id));
		$filepath = $id;
		
		if ($path) {
			$filepath = $path . DIRECTORY_SEPARATOR . $filepath;
		} else {
			$filepath = $id;
		}
		
		if (self::exists($filepath) !== TRUE) {
			throw new Exception($filepath .' does not exist');
		}
		// Delete the value from the key.
		$this->redis->hdel($filepath, $streamName);
	}
	
	/**
	 * Function to delete a directory. 
	 * 
	 * @param string $dir
	 *   The directory to be deleted. If empty, we delete the base directory.
	 */
	public function delDirectory ($dir) 
	{
		if (!$dir) {
			$dir = $this->dir . ':keys';
		}
		$keys = self::listIds($dir);
		print_r($keys);
		// Loop through each key and delete them.
		foreach ($keys as $key) {
			$this->redis->del($key);
		}
		
		// Delete the set
		if (!$this->redis->del($dir)) {
			throw new Exception("delDirectory error: $dir could not be deleted");
		}
	}
	
	/**
	 * Function to delete the key from the set
	 * 
	 * @param string $id
	 * @param string $path
	 * @throws Exception
	 */
	public function del_path ($id,$path) {
		if (self::exists($id) !== TRUE) {
			throw new Exception($id .' cannot be deleted from the FS');
		}
		
		if (self::isfile($id, $filepath)) {
			unlink($id);
		} else {
			$it = new RecursiveDirectoryIterator($id);
			$files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
			foreach($files as $file){
				if ($file->isDir()){
					rmdir($file->getRealPath());
				} else {
					unlink($file->getRealPath());
				}
			}
		}
	}
	
	/**
	 * Function to delete the object from the FS
	 * @param string $id
	 */
	public function delete_object($id) {
		if (!exists($id)) {
			throw new Exception ($id.' does not exist');
		}
	    $it = new RecursiveDirectoryIterator( 'data');
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file){
          if ($file->isDir()){
            rmdir($file->getRealPath());
          } else {
            unlink($file->getRealPath());
          }
        }
	}
	
	/**
	 * Function to check if the PairTree path exists or not
	 * 
	 * @param string $id
	 * @param string $path
	 */
	private function exists($id, $path=null) {
		//$dirpath = $this->pp->id_to_dirpath($id);
		$dirpath = $id;
		$exists = FALSE;
		if (!$path) {
			$path =$this->dir;
		}

		if ($this->redis->sismember($path . ':keys', $dirpath)) {
			$exists = TRUE;
		}
		return $exists;
	}
	
	/**
	 * Function to create a 14 digit length random id
	 */
	private function get_new_id () {
		return substr(rand(0,999999999999999), 0, 14);
	}
	
	/**
	 * Function to get the Pairtree object
	 * 
	 * @param unknown_type $object
	 * @param unknown_type $createifnotexist
	 * @throws Exception
	 */
	public function get_object($object, $createifnotexist=false) {
		if (!file_exists($object) && $createifnotexist === true) {
			throw new Exception('There is no object of that name');
		}
	
		if (!file_exists($this->dir.DIRECTORY_SEPARATOR.$object) && $createifnotexist === false) {
			self::create_object($this->dir.DIRECTORY_SEPARATOR.$object);
		}
		return file_get_contents($object);
	}
	
	/**
	 * Function to create a Pairtree object in storage
	 * @param string $object
	 * @throws Exception
	 */
	public function create_object ($object) {
		if (file_exists($object)) {
			throw new Exception ('Object already exists');
		}
		$fh = fopen($this->dir.DIRECTORY_SEPARATOR.$object, 'w');
		fwrite($object, filesize($fh));
		fclose($fh);
	}
}