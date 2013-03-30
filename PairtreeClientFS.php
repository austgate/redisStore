<?php
/**
 * Client to create and manage a Pairtree on the filesystem
 * 
 * Takes some ideas from Typo3:
 * http://doxygen.frozenkiwi.com/typo3/html/dc/d0d/FileByteStream_8php_source.html
 * 
 * @author Iain Emsley iain_emsley@austgate.co.uk
 * @version  0.1
 * 
 */

include __DIR__.'/PairtreePath.php';

class PairtreeClient {
	
	public function __construct($dir='data', $uri_base='http://', $shorty=2, $hashlib=null) {
		$this->pp = new PairtreePath();
		$this->dir = $dir;
		$this->uribase = $uri_base;
		$this->shorty_length = $shorty;
		$this->hashlib = $hashlib;
		self::init_store($this->dir, $this->uribase, $this->shorty_length);
	}
	
	/**
	 * Function to create a base directory if it doesn't exist, or return the existing parts if it does
	 * @param string $dir
	 * @param string $uribase
	 */
	private function init_store ($dir='data',$uribase= 'http://') {
		if (is_dir($dir)) {
			$this->dir = $dir;
			$fh = fopen($dir.DIRECTORY_SEPARATOR."pairtree_prefix", 'r');
			$this->uribase = fread($fh, filesize($dir.DIRECTORY_SEPARATOR."pairtree_prefix"));
			fclose($fh);
	
		} else {
			mkdir($dir);
			chdir($dir);
			$prefix = fopen("pairtree_prefix", 'x+') or die("can't open file");
			fwrite($prefix, "http://");
			fclose($prefix);
			$prefix = fopen("pairtree_root", 'x+') or die("can't open file");
			fclose($prefix);
			$prefix = fopen("pairtree_version0_1", 'x+') or die("can't open file");
			if (fwrite($prefix, "This directory conforms to Pairtree Version 0.1.\n
					Updated spec: http://www.cdlib.org/inside/diglib/pairtree/pairtreespec.html")===false) {
					print 'cannot write to filename pairtree_versin0_1';
			}
			fclose($prefix);
		}
	
		return array($dir, $uribase);
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
	 * Function to list the valid shorty paths for a subdirectory
	 * @param string $path the directory of the path
	 * @return array of valid directory paths
	 */
	public function list_ids($path = null) {
		$listdirs = array();
		$objects = array();
		if (!$path) {
			$path = '.';
		}
		
		$p = opendir($path);
		if ($p != '.' && $p != '..' && is_dir($p)) {
			$listdirs[]= $p.DIRECTORY_SEPARATOR.$p;
		}
		
		foreach ($listdirs as $dir) {
			if (len($dir) > $this->shorty_length) {
				$objects[]=$dir;
			}
		}
		return $objects;
	}
	
	/**
	 * Function to list the parts of a path but not the path contents
	 * 
	 * If 
	 * 
	 * @param string $path
	 */
	public function list_parts($id, $path=null) {
		$listdir = array();
		if ($path) {
			$dirpath = join(DIRECTORY_SEPARATOR, $this->pp->id_to_dirlist($id, $path));
		} else {
		    $dirpath = join(DIRECTORY_SEPARATOR, $this->pp->id_to_dirlist($id));
		}
		
		/*if (!is_dir($dirpath) || !is_file($dirpath)) {
			throw new Exception($path.' could not be found');
		}*/
		
		foreach(str_split($dirpath) as $dir) {
			if (strlen($dir) > $this->shorty_length) {
				$listdir[] = $dir;
			}
		}
		
		return $listdir;
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
	 * Function to check if a path is a directory
	 * @param string $id
	 * @param boolean
	 */
	public function isdirectory($id, $filepath=null) {
		$isdir = false;
		$filepath = join(DIRECTORY_SEPARATOR, $this->pp->get_id_from_dirpath($id)).DIRECTORY_SEPARATOR.$stream;
		if (is_dir($filepath)) {
			$isdir = true;
		}
		return $isdir;
	}
	
	/**
	 * Function to create streams in the path
	 * @param string $id the main path
	 * @param string $path an optional subfolder
	 * @param string $stream_name name of the file to be written to
	 * @param string $bytestream 
	 * @param int $buffer_size
	 */
	public function put_stream($id, $path, $stream_name, $bytestream, $buffer_size=8192) {
		$dirpath=null;
		if ($path) {
			if (!self::exists($path, $id)) {
				mkdir($id.DIRECTORY_SEPARATOR.$path);
				$dirpath = $id.DIRECTORY_SEPARATOR.$path;
			} else {
				$dirpath = $id.DIRECTORY_SEPARATOR.$path;
			}
		} else {
		    if (!self::exists($id)) {
			    mkdir($id);
			    $dirpath = $id;
		    } else {
		    	$dirpath = $id;
		    }
		}
		
		$fh = fopen($dirpath.DIRECTORY_SEPARATOR.$stream_name, 'x+');
		if (!$fh) {
			throw new Exception($path.DIRECTORY_SEPARATOR.$stream_name.' could not be created');
		}
		if (!$buffer_size) {
			$buffer_size = 1024 * 8;
		}
		fwrite($fh, $buffer_size);
		
		fclose($fh);
		//@todo look at filesystem hashing
	}
	
	/**
	 * Function to return a filehandle object which is appendable
	 * @param string $id the path
	 * @param string $path an optional subfolder
	 * @param string $stream_name the filename to opened
	 * @return the filehandle object
	 */
	public function get_appendable_stream($id, $path, $stream_name) {
		
	}
	
	/**
	 * Function to read the given filestream
	 * @param string $id  the given filepath id
	 * @param string $path the optional directory
	 * @param string $stream_name the fileame to be opened
	 * @param boolean $streamable if the filecontents are streamable or not @todo to implement this 
	 *  if streamble is true, then return the filehandle and close 
	 *  @return file object
	 */
	public function get_stream($id, $path, $stream_name, $streamable=false) {
		$dirpath=null;
		if ($path) {
			if (!self::exists($path, $id)) {
				throw new Exception(' stream does not exist');
			} else {
				$dirpath = $id.DIRECTORY_SEPARATOR.$path;
			}
		} else {
			if (!self::exists($id)) {
				throw new Exception (' Directory did not exist');
			} else {
				$dirpath = $id;
			}
		}
		
		$fileobj=null;
		$fh = fopen($dirpath.DIRECTORY_SEPARATOR.$stream_name, 'r');
		$fileobj = fread($fh, filesize($dirpath.DIRECTORY_SEPARATOR.$stream_name));
		fclose($fh);
		
		return $fileobj; 
	}
	
	/**
	 * Removes the stream from the filepath. 
	 * @param string $id
	 * @param string $stream_name
	 * @param string $path - optional
	 * @throws Exception
	 */
	public function del_stream($id, $stream_name, $path=null) {
		$filepath = join(DIRECTORY_SEPARATOR, $this->pp->get_id_dirpath($id)).DIRECTORY_SEPARATOR.$stream;
		if ($path) {
			$filepath = $path.DIRECTORY_SEPARATOR.$filepath;
		}
		
		if (!exists($filepath)) {
			throw new Exception($filepath .' does not exist');
		}
		
		if (is_dir($filepath)) {
			rmdir($filepath);
		} else {
			unlink($filepath);
		}
	}
	
	public function del_path ($id,$path, $recursive=false) {
		if (!exists($id)) {
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
	 * Function to check if the ppath exists or not
	 * @param string $id
	 * @param string $path
	 */
	private function exists($id, $path=null) {
		$dirpath = $this->pp->id_to_dirpath($id);
		if ($path) {
			$dirpath = $path.DIRECTORY_SEPARATOR.$dirpath; 
		}
		return $dirpath;
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
	 * @param unknown_type $object
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