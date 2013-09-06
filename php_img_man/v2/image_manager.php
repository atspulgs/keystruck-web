<?php
/* -------------------------------------------------------------------------------------------------------------
* Author:       Atspulgs
* Version:      0.3 Alpha
* Webpage:      
* -------------->>
* Changelog:
* - 18/06/2013	- 0.1 A	- Initial creation of the file.
* 						- Object can be declared.
*						- Can get the resource from the object.
* 						- When declaring checks if the file is legit otherwise throws a custom Exception.
* - 24/08/2013 	- 0.2 A	- Did a small bug fix. Still No real life tests done.
*						- Added a basic logger option (may still be improved)
* 						- Added some getters.
* 						- Added a function for saving the resource to a file.
* - 25/08/2013	- 0.3 A - Added resize functions.
*						- Added an option to reuse the original.
* 						- Can obtain the original as well ass modified versions of the image.
* 						- Modified log messages.
*						- Added basic border function.
* - 27/08/2013	- 0.4 A - Added extension check and loading.
* 						- Border function can now do a gradiant border using 2 colors.
* -------------->>
* Todo:
* - Gives the option to add a string to the image (http://php.net/manual/en/function.imagestring.php).
* - Optional object construction using image string or resource.
* - Cleanup.
* - Image export to string (http://stackoverflow.com/questions/8502610/how-to-create-a-base64encoded-string-from-image-resource) 
* 		with optional base64 encode (http://php.net/manual/en/function.base64-encode.php).
* - Rotation with options to zoom in or crop the image. (need to test avilable rotation functions)
* - Add drop shadow function. (http://www.codewalkers.com/c/a/Miscellaneous/Adding-Drop-Shadows-with-PHP/6/)
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
* - Resize functions added. This includes, resize forcing the new size, resize on either width or height keeping original
* 		aspect ratio, resize both height and width keeping original aspect ratio with the help of crop and center options.
* - Resize to fit a rectangle, keeps the aspect ratio and checks whcih side should be used for resizing based on input width and height.
* - Can add a simple color border of chosen width around the image. (still impure look, will try to improve)
* - Checks if extensions are loaded.
* - Attempts to load the extensions it needs.
* - gradiant option for border added.(still needs some refining.)
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
* -> 1009 - Final image creation failed (imagecopyresampled/imagecopymerge failed).
* -> 1010 - Variable was expected to be gretaer than 0.
* -> 1011 - Imagecolorallocate failed.
* -> 1012 - Failed to load a library.
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
	private $srcWidth;
	private $srcHeight;
	private $srcAspect;
	private $filetype;
	private $extension;
	private $imgtype;
	private $trgt;
	private $width;
	private $height;
	private $aspect;

	//For internal use mostly
	private $log;
	private $logger;

	public function __construct($strsrc, $log = false){
		$this->checkExctensions();
		if($log) {
			$this->logger = new ImgManagerLogger();
			$this->log = $log;
		}
		$this->src = $this->creategd($strsrc);
		$this->trgt = $this->src;
		$this->srcWidth = $this->width = imagesx($this->src);
		$this->srcHeight = $this->height = imagesy($this->src);
		$this->srcAspect = $this->aspect = $this->srcWidth / $this->srcHeight;
		if(empty($this->src)) throw new ImgManagerException("Failed at creating gd resource!", 1008);
		else if($this->log) $this->logger->logit("Original File: ".$strsrc.", File Type: ".$this->filetype, "Image Loaded: ");
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

	private function checkExctensions() {
		$suffix = PHP_SHLIB_SUFFIX;
		$prefix = ($suffix === 'dll') ? 'php_' : '';
		if(!extension_loaded("gd"))
    		if(dl($prefix.'gd.'.$suffix))
    			throw new ImgManagerException("GD library was not loaded and the attempt to load it failed!", 1012);
		if(!extension_loaded("fileinfo"))
    		if(dl($prefix.'fileinfo.'.$suffix))
    			throw new ImgManagerException("FileInfo library was not loaded and the attempt to load it failed!", 1012);
		if(!extension_loaded("exif"))
    		if(dl($prefix .'exif.'.$suffix))
    			throw new ImgManagerException("Exif library was not loaded and the attempt to load it failed!", 1012);
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

	//Resize function with options to keep the aspect and center the image if aspect is to be kept. Centring can be done in H || V .
	private function resize_crop_center($width, $height, $crop=false, $centredH=false, $centredV=false){
		if($width <= 0 && $height > 0)
			$width = round($height * $this->aspect);
		elseif($height <=0)
			$height = round($width / $this->aspect);
		$aspect = $width / $height;
		$target = imagecreatetruecolor($width, $height);
		$cWidth = $width;
		$cHeight = $height;

		if($crop) {
			$cWidth = $this->aspect >= $aspect? $this->width / ($this->height / $height) : $width;
			$cHeight = $this->aspect >= $aspect? $height : $this->height / ($this->width / $width);
		}

		$dst_x = $centredH? (0 - (($cWidth - $width) / 2)) : 0;
		$dst_y = $centredV? (0 - (($cHeight - $height) / 2)) : 0;

		if(!imagecopyresampled($target, $this->trgt, $dst_x, $dst_y, 0, 0, round($cWidth), round($cHeight),$this->width, $this->height))
			throw new ImgManagerException("imagecopyresampled was unsuccessful!", 1009);
		$this->trgt = $target;
		$this->width = imagesx($target);
		$this->height = imagesy($target);
		$this->aspect = $this->width/$this->height;
	}

	//Ill be using javadoc style later

	public function getOriginal() { return $this->src; }
	//return the image resource
	public function getResource() { return $this->trgt; }
	//return the file type of the assumed image
	public function getFileType() { return $this->filetype; }
	//return the extension of the file
	public function getExt() { return $this->extension; }
	//return the image type as a number *see the imagetype table
	public function getImgType() { return $this->imgtype; }
	//basic resize function. Simply squizes or stretches the image.
	public function resize($width, $height) {
		$this->resize_crop_center($width, $height);
		if($this->log) $this->logger->logit("Image was resized to (".$width.",".$height.").", "Image Resize: ");
		return $this;
	}
	//resizes the image, crops if needed and centers if cropped.
	public function rcc($width, $height) {
		$this->resize_crop_center($width, $height, true, true, true);
		if($this->log) $this->logger->logit("Image was resized to (".$width.",".$height.") cropped to keep the aspect and centred.", "Image Resize: ");
		return $this;
	}
	//resize on width
	public function ronx($width) {
		if($width <=0) throw new ImgManagerException("Width can not be smaller than or equal to 0!", 1010);
		$this->resize_crop_center($width, 0);
		if($this->log) $this->logger->logit("Image was resized on width(".$width.") keeping aspect ratio.", "Image Resize: ");
		return $this;
	}
	//resize on height
	public function rony($height) {
		if($height <=0) throw new ImgManagerException("Height can not be smaller than or equal to 0!", 1010);
		$this->resize_crop_center(0, $height);
		if($this->log) $this->logger->logit("Image was resized on height(".$height.") keeping aspect ratio.", "Image Resize: ");
		return $this;
	}
	//resizes in a way so the image keeps it aspect and also fits within a rectangle specified(does not crop)
	public function fitin($width, $height) {
		if($this->aspect >= $width / $height)
			$this->ronx($width);
		else $this->rony($height);
		return $this;
	}
	//Adds a basic border around the image.
	public function border($color, $size = 1, $color2 = false) {
		if($color === false) throw new ImgManagerException("Failed to set a color!", 1011);
		if($size == 0) return $this;

		$width = $this->width + ($size*2);
		$height = $this->height + ($size*2);
		$target = imagecreatetruecolor($width, $height);
		$colors = [ImgManager::hextorgb($color)];
		$color_int_values = array();
		if($color2 && $size > 1){
			$colors[$size-1] = ImgManager::hextorgb($color2);
			if($size > 2){
				$diff_red = ($colors[0]['red'] - $colors[$size-1]['red'])/($size-1);
				$diff_green = ($colors[0]['green'] - $colors[$size-1]['green'])/($size-1);
				$diff_blue = ($colors[0]['blue'] - $colors[$size-1]['blue'])/($size-1);
				for($i=1;$i<$size-1;$i++)
					$colors[$i] = ['red' => intval(round($colors[0]['red'] - ($diff_red*$i))),
						'green' => intval(round($colors[0]['green'] - ($diff_green*$i))),
						'blue' => intval(round($colors[0]['blue'] - ($diff_blue*$i))),
						intval(round($colors[0]['red'] - ($diff_red*$i))),
						intval(round($colors[0]['green'] - ($diff_green*$i))),
						intval(round($colors[0]['blue'] - ($diff_blue*$i)))];
			}
		}
		ksort($colors);
		foreach($colors as $c)
			$color_int_values[] = imagecolorallocate($target, $c['red'], $c['green'], $c['blue']);

		for($i=0,$j=0;$i <$size;$i+=1,$j++)
			imagefilledrectangle($target, $i, $i, $width-$i-1, $height-$i-1, $color_int_values[$j]);
		if(!imagecopymerge($target, $this->trgt, $size, $size, 0, 0,$this->width, $this->height, 100))
			throw new ImgManagerException("imagecopymerge was unsuccessful!", 1009);
		$this->trgt = $target;
		$this->width = $width;
		$this->height = $height;
		$this->aspect = $width/$height;
		if($this->log) $this->logger->logit("Border of color ".$color." was successfully added to the image.", "Image Color: ");
		return $this;
	}
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
			case IMAGETYPE_GIF: imagegif($this->trgt, $filename.($ftype = ".gif")); break;
			case IMAGETYPE_JPEG: imagejpeg($this->trgt, $filename.($ftype = ".jpg")); break;
			case IMAGETYPE_PNG: imagepng($this->trgt, $filename.($ftype = ".png")); break;
			default: imagejpeg($this->trgt, $filename.($ftype = ".jpg"));
		}
		if($this->log) $this->logger->logit("Image was saved successfully @ ".$filename.$ftype, "Image Saved: ");
		if($rtrn) return $filename.$ftype; else return $this;
	}

	//Resets the target image into original image. This may be useful if you want to do more than one thing with the same image.
	public function reset() {
		$this->trgt = $this->src;
		$this->height = $this->srcHeight;
		$this->width = $this->srcWidth;
		$this->aspect = $this->srcAspect;
		if($this->log) $this->logger->logit("Modified image was reset to original.", "Image Reset: ");
		return $this;
	}
	//Hex to RGB. A small extra. returns an array 0 = R, 1 = G, 2 = B OR red, green, blue
	public static function hextorgb($hex) {
		if($hex[0] == '#' && strlen($hex) == 7)
			return ['red' => hexdec($hex[1].$hex[2]), 'green' => hexdec($hex[3].$hex[4]), 'blue' => hexdec($hex[5].$hex[6]),
				hexdec($hex[1].$hex[2]), hexdec($hex[3].$hex[4]), hexdec($hex[5].$hex[6])];
		return false;
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