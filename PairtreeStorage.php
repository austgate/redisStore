<?php
/**
 * 
 * @file
 * PHP Implementation of Pairtree. 
 * 
 * @author Iain Emsley <iain_emsley@austgate.co.uk>
 * @category Redis storage
 * @license MIT BSD
 * @link http://www.austgate.co.uk
 * @package 0.1
 * 
 * 
 * PHP Version 5.3.10
 */
require __DIR__.'/PairtreeClient.php';

/**
 * Factory class to be called to call the storage client
 *
 */
class PairtreeStorageFactory
{

    /**
	 * Factory to begin the creation of Pairtree system. 
	 * Calls the Pairtree Client
	 * 
	 * Called with PairtreeStorageFactory::pairtreeStorage
	 * 
	 * @param string $dir
	 *    
	 * @param string $uriBase
	 * @param number $shorty
	 * @param string $hashlib
	 */
    public function pairtreeStorage($dir, $uriBase, $shorty = 2, $hashlib = null) 
    {
        $this->pc = new PairtreeClient();
        if (class_exists($this->pc($dir, $uriBase, $shorty = 2, $hashlib = null))) {
            return $this->pc($dir, $uriBase, $shorty = 2, $hashlib = null);
	    }
	    else {
            print "Pairtree Storage Factory does not exist";
        }
    }
}