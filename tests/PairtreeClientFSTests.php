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
	
	/* lists_ids */
	public function testlist_ids () {
		$id = '/home/iain/data/ab';
		$fh = mkdir($id, 0700);
		$nid = $this->pc->list_ids($id);
		$this->assertContains('ab', $nid);
		rmdir($id);
	}
	
	/* list_parts*/
	public function testlist_parts() {
		$id = "/home/iain/data/cd";
		mkdir($id);
		$nid = $this->pc->list_parts($id);
		$this->assertContains('cd', $nid);
		rmdir($id);
	}
	/* is_file */
	public function testis_file() {
		$id = "/home/iain/data/test.txt";
		$fh = fopen($id, 'w+');
		$this->assertTrue($this->pc->isfile('test.txt'));
		unlink($id);
	}
	 /* is_directory */
	public function testis_directory() {
		$id = '/home/iain/data/pd';
		mkdir($id);
		$this->assertTrue($this->pc->isdirectory($id));
		rmdir($id);
	}
}