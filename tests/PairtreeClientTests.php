<?php

include '../PairtreeClient.php';

class TestPairtreeClient extends PHPUnit_Framework_TestCase {
	
	public function setUp() {
		$this->pc = new PairtreeClient();
	}
	public function tearDown() {
		unset($this->pc);
	}
	
	/* id_encode */
	public function testid_encodenoextra() {
		$id = 'abcd';
		$nid = $this->pc->id_encode($id);
		$this->assertEquals('abcd', $nid);
	}
	public function testid_encodecolon() {
		$id = 'abc:';
		$nid = $this->pc->id_encode($id);
		$this->assertEquals('abc+', $nid);
	}
	public function testid_encodeequals() {
		$id = 'abc.';
		$nid = $this->pc->id_encode($id);
		$this->assertEquals('abc,', $nid);
	}
	public function testid_encodeslash() {
		$id = 'abc/';
		$nid = $this->pc->id_encode($id);
		$this->assertEquals('abc=', $nid);
	}
	
	/* id_decode */
	public function testid_decodenoextra() {
		$id = 'abcd';
		$nid = $this->pc->id_decode($id);
		$this->assertEquals('abcd', $nid);
	}
	public function testid_decodecolon() {
		$id = 'abc+';
		$nid = $this->pc->id_decode($id);
		$this->assertEquals('abc:', $nid);
	}
	public function testid_decodeequals() {
		$id = 'abc,';
		$nid = $this->pc->id_decode($id);
		$this->assertEquals('abc.', $nid);
	}
	public function testid_decodeslash() {
		$id = 'abc=';
		$nid = $this->pc->id_decode($id);
		$this->assertEquals('abc/', $nid);
	}
	
	/* Set the key and sets */
	public function testputStream() {
		$id = 'test';
		$path = '';
		$streamName = 'teststream';
		$bytestream = 'test file';
    	$stored = $this->pc->putStream($id, $path, $streamName, $bytestream);
		$this->assertTrue($stored);
		
		$newkey = $this->pc->getKey($id);
		$this->assertNotEmpty($newkey);
		$this->assertContains('teststream', $newkey);
	}
	
	/*public function testputStreamPath() {
		$id = 'test';
		$path = 'pairtree';
		$streamName = 'teststream';
		$bytestream = 'test file';
		$stored = $this->pc->putStream($id, $path, $streamName, $bytestream);
		$this->assertEquals($stored, TRUE);
	}*/
	
	public function testgetStream() {
		$id = 'test';
		$path = '';
		$streamName = 'teststream';		
		$teststream = $this->pc->getStream($id, $path, $streamName);
		$this->assertEquals('test file',$teststream);
		$this->assertNotEmpty($teststream);
	}
	
	/*public function testgetStreamPath() {
		$id = 'test';
		$path = 'pairtree';
		$streamName = 'teststream';
		$teststream = $this->pc->getStream($id, $path, $streamName);
		$this->assertEquals($teststream, 'test file');
		$this->assertNotEmpty($teststream);
	}*/
	
	/* lists_ids */
	// Testing default base set.
	public function testlistIdsDefaultSet () {
		$nid = $this->pc->listIds();
		$this->assertContains('test', $nid);
	}
	
	// Testing default base set.
	/*public function testlistIdsNonDefaultSet () {
		$dir = 'exist';
		$nid = $this->pc->listIds($dir);
		$this->assertContains('test', $nid);
	}*/
	
	/* Delete the key */
	public function testdelStream() {
		$id = 'test';
		$path = '';
		$streamName = 'teststream';
		$teststream = $this->pc->delStream($id, $path, $streamName);
		$this->assertNotEquals($teststream, 'test file');
		
		$newkey = $this->pc->getKey($id);
		$this->assertNotContains('teststream', $newkey);
	}
	
	/*public function testdelStreamPath() {
		$id = 'test';
		$path = 'pairtree';
		$streamName = 'teststream';
		$teststream = $this->pc->delStream($id, $path, $streamName);
		$this->assertEquals($teststream, 'test file');
		$this->assertNotEmpty($teststream);
	}*/
	
	public function testdelDirectory() {
		$dir = '';
		$this->pc->delDirectory($dir);
	}
	
	/*public function testdelDirectoryNotExist() {
		$dir = 'notexist';
		$this->pc->delDirectory($dir);
		$this->assert("delDirectory error: notexist could not be deleted");
	}*/
}