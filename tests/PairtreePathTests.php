<?php
/**
 * @abstract Tests for the Pairtree Path functions
 * 
 * Initial tests. Needs some edge cases for more robust testss
 */

include '../PairtreePath.php';

class TestPairtreePath extends PHPUnit_Framework_TestCase {
	
	protected $p;
	
	public function setUp() {
		$this->p = new PairtreePath();
		$this->decode_ppath = 'fo/ob/ar/+/';
		$this->encode_ppath = 'foobar+';
	}
	
	public function tearDown() {
		unset($this->p);
		unset($this->decode_ppath);
		unset($this->encode_ppath);
	}
	
	/*  Decoding tests  */
	
	public function testget_id_decode() {
		$newid = $this->p->get_path_from_dirpath($this->decode_ppath);
		$this->assertEquals('foobar+', $newid);
	}
	
	public function testget_id_from_path() {
		$newid = $this->p->get_id_from_dirpath($this->decode_ppath);
		$this->assertEquals('foobar:', $newid);
	}
	
	public function testget_path_from_path() {
		$newid = $this->p->get_path_from_dirpath($this->decode_ppath);
		$this->assertEquals('foobar+', $newid);
	}
	
	/*  Encoding tests  */
	
	public function testget_id_encode() {
		$newid = $this->p->id_encode($this->encode_ppath);
		$this->assertEquals('foobar^66', $newid);
	}
	
	public function testid_to_dirlist() {
		$newid = $this->p->id_to_dirlist($this->encode_ppath);
		$this->assertContains('fo', $newid);
	}
	
	public function testid_to_dirpath() {
		$newid = $this->p->id_to_dirpath($this->encode_ppath);
		$this->assertEquals('fo/ob/ar/+', $newid);
	}

}