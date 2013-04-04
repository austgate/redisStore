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
		$this->assertEquals($stored, 'te/st');
		
		$newkey = $this->pc->getKey($id);
		$this->assertNotEmpty($newkey);
	}
	
	public function testputStreamPath() {
		$id = 'test';
		$path = 'pairtree';
		$streamName = 'teststream';
		$bytestream = 'test file';
		$stored = $this->pc->putStream($id, $path, $streamName, $bytestream);
		$this->assertEquals('te/st/pa/ir/tr/ee', $stored);
	}
	
	public function testgetStream() {
		$id = 'te/st';
		$streamName = 'teststream';		
		$teststream = $this->pc->getStream($id, $streamName);
		$this->assertEquals('test file',$teststream);
		$this->assertNotEmpty($teststream);
	}
	
	public function testgetStreamPath() {
		$id = 'te/st/pa/ir/tr/ee';
		$streamName = 'teststream';
		$teststream = $this->pc->getStream($id, $streamName);
		$this->assertEquals($teststream, 'test file');
		$this->assertNotEmpty($teststream);
	}
	
	// Change the value of the key
	public function testPutStreamChangeValue() {
		$id = 'test';
		$path = '';
		$streamName = 'teststream';
		$bytestream = 'change the file contents again';
		$stored = $this->pc->putStream($id, $path, $streamName, $bytestream);
		$this->assertEquals($stored, 'te/st');
	
		$newkey = $this->pc->getKey($id);
		$this->assertNotEmpty($newkey);
	}
	
	public function testGetStreamChangeValue() {
		$id = 'te/st';
		$streamName = 'teststream';
		$teststream = $this->pc->getStream($id, $streamName);
		$this->assertEquals('change the file contents again',$teststream);
		$this->assertNotEmpty($teststream);
	}
	
	/* lists_ids */
	// Testing default base set.
	public function testlistIdsDefaultSet () {
		$nid = $this->pc->listIds();
		$this->assertContains('te/st', $nid);
	}
	
	// Testing the 
	public function testlistIdsListall () {
		$nid = $this->pc->listIds('', TRUE);
		$this->assertContains('te/st', $nid);
	}
	
	/* Delete the key */
	public function testdelStream() {
		$id = 'te/st';
		$streamName = 'teststream';
		$teststream = $this->pc->delStream($id, $streamName);
		$this->assertNotEquals($teststream, 'test file');
		
		$newkey = $this->pc->getKey($id);
		$this->assertNotContains('teststream', $newkey);
	}
	
	public function testdelStreamPath() {
		$id = 'te/st/pa/ir/tr/ee';
		$streamName = 'teststream';
		$teststream = $this->pc->delStream($id, $streamName);
		$this->assertNotEquals($teststream, 'test file');
		$this->assertEmpty($teststream);
	}
	
	// Delete the directory
	public function testdelDirectory() {
		$dir = '';
		$this->pc->delDirectory($dir);
	}
	
	// Exception test.
	public function testdelDirectoryNotExist() {
		$dir = 'notexist';
		try {
		    $this->pc->delDirectory($dir);
		} catch (Exception $e) {
			return;
		}
		//$this->setExpectedException('Exception');
		$this->fail("delDirectory error: notexist could not be deleted");
	}
}