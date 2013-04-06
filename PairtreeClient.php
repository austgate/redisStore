<?php
/**
 * @file
 * Functions to use Redis as an underlying store for the Pairtree
 * instead of the filesystem
 * 
 * PHP version 5.3.10
 * 
 * @category Storage
 * @package  RedisPairFS
 * @author   Iain Emsley <iain_emsley@austgate.co.uk>
 * @license  MIT BSD
 * @link     http://github.com/austgate/redisFS
 */

require __DIR__.'/PairtreePath.php';
require __DIR__.'/PairtreeException.php';

require 'Predis/Autoloader.php';

/**
 * A client implementation of using Redis instead of the file system. 
 * Standard Redis style suggests using ':' as a separator but this is 
 * reserved in Pairtree so using the directory separator 
 * in this instance. Also maintains conformity with the standard. 
 * A conversion between the two may be necessary in future and is something
 * to be discussed. 
 * 
 * @category Storage
 * @package  RedisPairFS
 * @author   Iain Emsley <iain_emsley@austgate.co.uk>
 * @license  MIT BSD
 * @link     http://github.com/austgate/redisFS
 */
class PairtreeClient
{
    // The base name used for the Redis set
    private $_dir;

    // The uri base used in the keys
    private $_uriBase;

    // The length of the shorty used to split the name into the path
    private $_shortyLength;

    // The hash library used.
    private $_hashlib;

    /**
     * Constructor called by the storage factory
     * 
     * @param string $dir     
     *  Dir is the name of the base set which will be used to store the keys
     * @param string $uriBase 
     *  uriBase is the Uri that we are using as a base for the key, i.e. http://www.twitter.com
     * @param number $shorty
     *  Used to divide the directory. Unused in the Redis version
     * @param string $hashlib
     *  String for the type of hash library being used.
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
            $this->_dir = $dir;
            $this->_uriBase = $uriBase;
            $this->_shortyLength = $shorty;
            $this->_hashlib = $hashlib;
        
            self::_initStore($this->_dir, $this->_uriBase, $this->_shortyLength);
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
     * @param string $dir
     *   This string is the directory name which is used for the keys.
     *   Default is data
     * @param string $uriBase
     *   The uriBase is the protocol used (could be http, https, ftp, rdf etc.)
     * 
     */
    private function _initStore ($dir='data',$uriBase= 'http://') 
    {
        //  Implementation of setting up the base directory
        if ($this->redis->hexists($dir, 'pairtree_prefix')) {
            // Get the uribase from the store and set it
            $this->_uriBase = $this->redis->hget($dir, 'pairtree_prefix');
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
     * 
     * @param string  $id
     * Id to be encoded
     * 
     * @return array
     *  
     */
    public function idEncode($id) 
    {
        return $this->pp->idEncode($id);
    }

    /**
     * Function to decode the id from Pairtree style back to its original form
     * @param string $id
     *  String of an encoded id
     * @return string 
     *  The decoded id
     */
    public function idDecode($id) 
    {
        return $this->pp->idDecode($id);
    }
    
    /**
     * Function to list the keys for a defined set
     * 
     * @param string $dir      
     *   The directory of the path. If this is not set, then list the default directory.
     *   
     * @return Array
     *    An array of the Keys with times
     */
    public function listIds($dir = null) 
    {
        $listdirs = array();
        $objects = $newobjects = array();

        if (!$dir) {
            $dir = $this->_dir;
        }
        $objects = $this->redis->smembers($dir. ':keys');

        foreach ($objects as $object) {
            // Load the key object.
            $key = self::getKey($object);
            $newobjects[$object] = $key['time'];
        }
        $objects = $newobjects;
        return $objects;
    }
    
    /**
     * Function to check if a path is a key in the set. 
     * 
     * @param string $id 
     * Id of the key in the set. 
     * 
     * @return boolean
     */
    public function isdirectory($id)
    {
        $isdir = false;
        if (self::_exists($id, $filepath)) {
            $isdir = true;
        }
        return $isdir;
    }
    
    /**
     * Function to create streams in the stores. 
     * 
     * It takes the identifier and then returns the stored path. 
     * If foobar+ is given, then fo/ob/ar/+ is returned and the 
     * stream is stored against this key.
     * 
     * @param string $id
     *  The main path
     * @param string $path
     *  An optional subfolder
     * @param string $streamName
     *  Name of the value to be store the data in the hash
     * @param string $bytestream 
     *  Data stream to be stored
     * @param number $bufferSize
     *  Set the buffer size
     * 
     * @return string
     *   returns the directory path of the string
     */
    public function putStream($id, $path, $streamName, $bytestream, $bufferSize=8192) 
    {
        $dirpath = null;
        if ($path) {
            $dirpath = $id . $path;
        } else {
            $dirpath = $id;
        }
        $dirpath = $this->pp->id_to_dirpath($dirpath, '', $this->_shortyLength);
        // Put the key in the set based on the dir name and 'keys'
        $this->redis->sadd($this->_dir.':keys', $dirpath);
        // Set the hash set with the streamName as the key.
        $this->redis->hset($dirpath, $streamName, $bytestream);
        // Then set update time
        $this->redis->hset($dirpath, "time", time());
        $size = filesize($bytestream);
        // set the size of the stream
        $this->redis->hset($dirpath, "size", time());

        return $dirpath;
    }

    /**
     * Function to get the whole key
     * 
     * @param string $id
     *   String of the key name.
     */
    public function getKey ($id) 
    {
        $key = array();
        $key = $this->redis->hgetall($id);
        if (!$key) {
            throw new PairtreeException("getKey for $key failed");
        }

        return $key;
    }
    
    /**
     * Function to return a filehandle object which is appendable
     * 
     * @param string $id 
     *  The path.
     * @param string $path 
     *  An optional subfolder.
     * @param string $streamName 
     *  The filename to opened.
     * @return object
     *   the filehandle object
     */
    public function getAppendableStream($id, $path, $streamName) 
    {    
    }
    
    /**
     * Function to read the given filestream
     * 
     * @param string  $id         
     *  The given filepath id.
     * @param string  $streamName 
     *  The key to be opened.
     * @param boolean $streamable 
     *  If the filecontents are streamable or not.
     *  
     * @return file object
     */
    public function getStream($id, $streamName, $streamable=false) 
    {
        $dirpath=null;

        if (self::_exists($id) !== true) {
            throw new PairtreeException('Directory did not exist');
        } else {
            $dirpath = $id;
        }
        $fileobj = $this->redis->hget($dirpath, $streamName);
        if (!$fileobj) {
            $message = "getStream Error: $streamName not retrieved from $dirpath";
            throw new PairtreeException($message);
        }
        return $fileobj; 
    }
    
    /**
     * Removes the stream from the store
     * 
     * @param string $id 
     *  The key that is to be deleted.
     * @param string $streamName
     *  The name of the stream for the key.
     * 
     * @throws Exception
     */
    public function delStream($id, $streamName) 
    {
        $filepath = $id;

        if (self::_exists($filepath) !== true) {
            throw new PairtreeException($filepath .' does not exist');
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
            $dir = $this->_dir . ':keys';
        }
        $keys = self::listIds($dir);
        // Loop through each key and delete them.
        foreach ($keys as $key) {
            $this->redis->del($key);
        }
        // Delete the set.
        if (!$this->redis->del($dir)) {
            throw new PairtreeException("delDirectory error: $dir could not be deleted");
        }
    }

    /**
     * Function to check if the PairTree path exists or not
     * 
     * @param String $id 
     *    The id of the key to be checked
     *    
     * @return boolean
     *    Whether the id exists at all
     */
    private function _exists($id) 
    {
        $dirpath = $id;
        $exists = false;

        if ($this->redis->sismember($this->_dir . ':keys', $dirpath)) {
            $exists = true;
        }
        return $exists;
    }

    /**
     * Function to create a 14 digit length random id
     * 
     * @return string
     *   14 character string of random numbers. 
     */
    private function _getNewId() 
    {
        return substr(rand(0, 999999999999999), 0, 14);
    }
}