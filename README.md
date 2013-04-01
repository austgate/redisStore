redisFS
=======

An implementation of the Pairtree FS (https://confluence.ucop.edu/display/Curation/PairTree) 
using Redis as the underlying store rather than a File System.  

It uses the Pairtree FS spec to implement an interoperable way of reading the keys. 

Usage
=====

Basic example.

This example calls the storage factory, then puts and gets the stream. 

Initialising the class creates the pairtree hash and pairtree:keys set. 

The key 'anothertestkey' is then created and the string 'factory testing' is set in the 'testvalue2' key
$pc = PairtreeStorageFactory::pairtreeStorage('pairtree', 'ftp://');
$pc->putStream('anothertestkey', '', 'testvalue2', 'factory testing');
print $pc->getStream('anothertestkey', '', 'testvalue2');