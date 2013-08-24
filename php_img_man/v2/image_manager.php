<?php
/* -------------------------------------------------------------------------------------------------------------
* Author:       Atspulgs
* Version:      0.2 Alpha
* Webpage:      
* -------------->>
* Changelog:
* - 18/06/2013	- 0.1 A	- Initial creation of the file.
* 						- Object can be declared.
*						- Can get the resource from the object.
* 						- When declaring checks if the file is legit otherwise throws a custom Exception.
* - 24/08/2013 	- 0.2 A	- Did a small bug fix. Still No real life tests done.
*						- Added a basic logger option (may still be improved)
* 						- Added some getters
* 						- Added a function for saving the resource to a file.
* -------------->>
* Todo:
* - Create image resizer keeping aspect ratio using either width or height
* - Create resize function that will keep the aspect ratio and crop image while centring it.
* - Create a function that will put a border of the chosen color around the image. Border size could have some variation.
* - Add config file
* - Configurable logger types
* - Make logger more Object Oriented.
* - Add extension checks - extension_loaded
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
* - Output information about the image(file).
* - Save the image in the specified filetype.(Original file type = 0, gif = 1, jpg = 2, png = 3) Based on IMAGETYPE variables.
*		If a filename contains a supported image type extension, will use that overriding type alltogether.
* - Optional logger available, Formats with tab size of 7 (works well with notepad)
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
* -------------->>
* Image Type Id list
* 1 - gif
* 2 - jpg/jpeg
* 3 - png
* ---------------------------------------------------------------------------------------------------------------*/
final class ImgManager
{
	/* @const */
	private static $allowedMimes = ["image/gif","image/jpeg","image/png"];
	private static $supportedExtensions = ["gif", "jpeg", "jpg", "png"];

	//Properties
	private $src;
	private $filetype;
	private $extension;
	private $imgtype;

	//For internal use mostly
	private $log;
	private $logger;

	public function __construct($strsrc, $log = false){
		if($log) {
			$this->logger = new ImgManagerLogger();
			$this->log = $log;
		}
		$this->src = $this->creategd($strsrc);
		if(empty($this->src)) throw new ImgManagerException("Failed at creating gd resource!", 1008);
		else if($this->log) $this->logger->logit("Image was created successfully from the file.\r\n\t\t\t\t\t\tOriginal File: ".$strsrc."\r\n\t\t\t\t\t\tFile Type: ".$this->filetype, "File Loaded: ");
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
		$tempType = explode('.', $file);
		$this->extension = end($tempType);
		if(empty($this->extension)) throw new ImgManagerException("File ".$file." has no extension!", 1004);
		if(!$this->extMatch($this->filetype, $this->extension)) throw new ImgManagerException("Filetype ".$this->filetype." does not match the extension ".$this->extension."!", 1005);
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

	//Ill be using javadoc style later

	//return the image resource
	public function getResource() { return $this->src; }
	//return the file type of the assumed image
	public function getFileType() { return $this->filetype; }
	//return the extension of the file
	public function getExt() { return $this->extension; }
	//return the image type as a number *see the imagetype table
	public function getImgType() { return $this->imgtype; }
	//saves the image to the specified location and returns the location *location is returned so you can use the function straignt in the img tag(can be overwritten)
	//default img type is jpg, but can be overwritten by a different imagetype (Imagetype variables cna be used as well as numbers)
	public function saveImg($filename, $type = IMAGETYPE_JPEG, $rtrn = true){
		$ftype = ".unsupported";
		$tempfn = explode('.', $filename);
		$tempType = end($tempfn);
		if(in_array(strtolower($tempType), self::$supportedExtensions)) {
			$filename = "";
			for($i = 0; $i < count($tempfn)-1; $i++){
				$filename .= $tempfn[$i];
			}
			switch($tempType){
				case "gif": 	$type = IMAGETYPE_GIF; break;
				case "jpg":
				case "jpeg": 	$type = IMAGETYPE_JPEG; break;
				case "png": 	$type = IMAGETYPE_PNG; break;
				default: $type = IMAGETYPE_JPEG; // Should NEVER reach this
			}
		} else if($type == 0)
			$type = $this->imgtype;
		switch($type){
			case IMAGETYPE_GIF: imagegif($this->src, $filename.($ftype = ".gif")); break;
			case IMAGETYPE_JPEG: imagejpeg($this->src, $filename.($ftype = ".jpg")); break;
			case IMAGETYPE_PNG: imagepng($this->src, $filename.($ftype = ".png")); break;
			default: imagejpeg($this->src, $filename.($ftype = ".jpg"));
		}
		if($this->log) $this->logger->logit("Image was saved successfully @ ".$filename.$ftype, "Image Saved: ");
		if($rtrn) return $filename.$ftype; else return true;
	}
}

class ImgManagerLogger
{
	private $fres;

	public function __construct(){
		if(!file_exists("ImgManager")) mkdir('ImgManager');
		$this->fres = fopen("ImgManager/log.txt", 'a');
	}

	public function logit($message, $type = "Regular Action: ") {
		fwrite($this->fres, date("d/m/Y H:i:s")." - ".$type."\t".$message."\r\n");
	}

	public function __destruct() {
		fclose($this->fres);
	}
}

class ImgManagerException extends Exception
{
	public function __construct($message, $code = 0, Exception $previous = null){
		parent::__construct($message,$code,$previous);
	}
}
?>