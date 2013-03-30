<?php
/**
 *  Test functions 
 */
include '../PairtreeStorage.php';

class TestPairTreeStorage extends PHPUnit_Framework_TestCase {
	
	public function setUp () {
		$this->p = new PairtreeStorageFactory();
	}
	
	public function tearDown() {
		unset($this->p);
		//remove the directory
	    /*$it = new RecursiveDirectoryIterator('data');
        $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file){
          if ($file->isDir()){
            rmdir($file->getRealPath());
          } else {
            unlink($file->getRealPath());
          }
        }*/	
	}
	
	public function test_get_store () {
		$this->p->get_store();
	}
	
	public function test_id2path() {
		$id = 'abcd';
		$newid = $this->p->id2path($id);
		$this->assertEquals('abcd', $newid);
	}
	
	public function test_path2id() {
		$id = 'ab/cd';
		$newid = $this->p->path2id($id);
		$this->assertEquals('abcd', $newid);
	}
	
}