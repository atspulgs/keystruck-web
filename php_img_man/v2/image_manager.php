<?php
/* -------------------------------------------------------------------------------------------------------------
* Author:       Atspulgs
* Version:      0.1
* Webpage:      
* -------------->>
* Changelog:
* - 18/06/2013	- 0.1 	- Initial creation of the file.
* 						- Object can be declared.
*						- Can get the resource from the object.
* 						- When declaring checks if the file is legit otherwise throws a custom Exception.
* -------------->>
* Todo:
* - Create image resizer keeping aspect ratio using either width or height
* - Create resize function that will keep the aspect ratio and crop image while centring it.
* - Create a function that will put a border of the chosen color around the image. Border size could have some variation.
* - Togglable progress output to file. Log file. A way to keep track of images that were processed and how.
* - Changable desired image format when saving. If not specified use extension type given by the user.
* 		 If user extension not supported or not existing, use original file extension.
* -------------->>
* Features:
* - Checks if the file exists.
* - Checks if the file is a file.
* - Checks if the file is supported using fifo class and file mime types.
* - Checks if files extension matches its mimetype.
* - Makes sure that the extension exists.
* - Checks the filetype using the file header.
* - Creates the image resource.
* - Can return the image resource.
* -------------->>
* Exception Codes:
* -> 1001 - File couldnt be found, assumed as non existant.
* -> 1002 - The given link didnt point to a File, assumed its a directory.
* -> 1003 - Unsupported file type. This object cant handle the provided file type.
* -> 1004 - File with no extension. 1002 should have caught this already, but just in case.
* -> 1005 - Filetype taken from mime does not match the given extension.
* -> 1006 - Filetype extension didnt match after reading file header.
* -> 1007 - imagetype didnt match any of the supported filetypes.
* -> 1008 - One of the imagecreatefrom* functions failed.
* ---------------------------------------------------------------------------------------------------------------*/
final class ImgManager
{
	/* @const */
	private static $allowedMimes = ["image/gif","image/jpeg","image/png"];

	private $src;
	private $filetype;
	private $extension;
	private $imgtype;

	public function __construct($strsrc){
		$this->src = $this->creategd($strsrc);
		if(empty($this->src)) throw new ImgManagerException("Failed at creating gd resource!", 1008);
	}

	private function creategd($file){
		//Checks if the file exists
		if(!file_exists($file)) throw new ImgManagerException("File ".$file." could not be found!", 1001);
		//Checks id the file is a file
		if(!is_file($file)) throw new ImgManagerException($file." is not an acceptable file!", 1002);
		//Checks the file type and compares if the extension matches the filetype
		$finfo = new finfo();
		$this->filetype = $finfo->file($file, FILEINFO_MIME_TYPE);
		if(!in_array($this->filetype, self::$allowedMimes)) throw new ImgManagerException("Filetype ".$this->filetype." is not supported!", 1003);
		$this->extension = end(explode('.', $file));
		if(empty($this->extension)) throw new ImgManagerException("File ".$file." has no extension!", 1004);
		if(!extMatch($this->filetype, $this->extension)) throw new ImgManagerException("Filetype ".$this->filetype." does not match the extension ".$this->extension."!", 1005);
		if(exif_imagetype($file) !== $this->imgtype) throw new ImgManagerException("exif_imagetype - Filetype did not match the extension provided!", 1006);

		switch($this->imgtype){
			case IMAGETYPE_GIF: return imagecreatefromgif($file);
			case IMAGETYPE_JPEG: return imagecreatefromjpeg($file);
			case IMAGETYPE_PNG: return imagecreatefrompng($file);
			default: throw new ImgManagerException("Imagetype didnt match the possible source creation methods!", 1007);
		}
	}

	private function extMatch($mimetype, $extension){
		switch($extension){
			case "gif": 	$this->imgtype = IMAGETYPE_GIF; return ($mimetype == self::$allowedMimes[0])? true : false;
			case "jpg":
			case "jpeg": 	$this->imgtype = IMAGETYPE_JPEG; return ($mimetype == self::$allowedMimes[1])? true : false;
			case "png": 	$this->imgtype = IMAGETYPE_PNG; return ($mimetype == self::$allowedMimes[2])? true  : false;
			default: return false;
		}
	}

	public function getResource() { return $this->$src; }
}

class ImgManagerException extends Exception
{
	public function __construct($message, $code = 0, Exception $previous = null){
		parent::__construct($message,$code,$previous);
	}
}
?>