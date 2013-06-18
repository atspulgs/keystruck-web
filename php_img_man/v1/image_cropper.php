<?php
/* -------------------------------------------------------------------------------------------------------------
* Author:       Atspulgs
* Version:      1.3
* Webpage:      http://keystruck.wordpress.com/2013/06/18/php-image-cropper-function-for-thumbnails/
* Changelog:
* - v 1.3 - Refined the checks done on the variables and cleaned up the code a bit.
* - v 1.2 - Added the option to add one of the side sizes or neither (Advised to use 0 for the side you want to move with aspect)
* - v 1.1 - Removed the check for size, the code works for any size.
*         - Anoter way of resizing the image, currently commented out. In case of misbehaviour can be used.
* - v 1.0 - Initial Release
*
* Todo:
* - Transfer this (global function) to be a static function when its beneficial. (currently static call is 5.1% slower (php: 5.4.9))
* Todo for 2.0:
* - Make this into an Object for Image manipulation.
* - Add exceptions
* - Add logging capabilitie, togglable if possible.
*
* This is a function that will accept an image resource and crop it down to specifics.
* $original         - Image resource, already extracted from file
* $target_width     - Width of the resource that will be returned
* $target_height    - Height of the resource that will be returned
* $centred          - Will the result be a centred version of the original
*                           default: true
* return            - The cropped image resource or FALSE in case of an error
* ---------------------------------------------------------------------------------------------------------------*/

/* Some math and logic behind the aspect ratios - just in case
** If width is greater than height then width/height will result in a number greater than 1.0
** If height is greater than width then width/height will result in a number smaller than 1.0
** ----------------------------------------------------------------------------------------------------------
** If width is greater than height then height/width will result in a number smaller than 1.0
** If height is greater than width then height/width will result in a number greater than 1.0
** ----------------------------------------------------------------------------------------------------------
** if(width > height)(horizontal aspect ratio) -> width/height > 1.0           - Used in this example
** if(width < height)(vertical aspect ratio)   -> width/height < 1.0           - Used in this example
** if(height < width)(horizontal aspect ratio) -> height/width < 1.0
** if(height > width)(vertical aspect ratio)   -> height/width > 1.0
** Depending on which perspective we use, width or height, we either get more vertical or horizontal ratios.
** In this function we use width perspective so we get greater values when having a more horizontal ratio.
** In case we would use a height perspective, we would get smaller values for horizontal ratios.
** ----------------------------------------------------------------------------------------------------------
** ($original_aspect >= $target_aspect) -> height = The source is wider than the target
** ($original_aspect < $target_aspect)  -> width  = The target is wider than the source
** This check finds out if sources and targets aspect ratio is more horizontal or vertical. In this case,
** if the sources aspect ratio is more vertical than targets,
** ----------------------------------------------------------------------------------------------------------
** $original_width / ( $original_height/$target_height ) == original width / aspect difference
**      aspect difference = $original_height/$target_height
** If we resized the original to the targets height then we need to resize originals width to match the
** aspect ratio. The width in this case would be greater than the targets width and that reminder would be
** the part we cut off. The same is true for the opposite case.
** ------------------------------------------------------------------------------------------------------- */
 
function cropImage($original, $target_width = 0, $target_height = 0, $centred = true)
{
    //Makes sure that the $original is a gd type resource
    if(gettype($original) !== "resource") return false;         //will throw an exception
    elseif(get_resource_type($original) !== "gd") return false; // throw an exception in future versions
 
    //Preparing the variables
    $original_width     = imagesx($original);                   //Gets the original resource Width
    $original_height    = imagesy($original);                   //Gets the original resource Height
    $original_aspect    = $original_width / $original_height;   //Gets the original resource aspcet ratio in form of a float
 
    if($target_width <= 0 && $target_height <= 0) {           //If no resize options added, use the original sizes
        $target_width = $original_width;
        $target_height = $original_height;
    } elseif($target_width <= 0)                                 //If no width provided or provided less than 0,
        $target_width = round($target_height * $original_aspect);   //use original width keeping aspect
    elseif($target_height <= 0)                              //If no eight provided or provided less than 0,
        $target_height = round($target_width / $original_aspect);   //use original height keeping aspect
 
    $target_aspect      = $target_width / $target_height;       //Gets the target resource aspect ratio in form of a float
 
    // Detrmining which side will be cut off
    if($original_aspect >= $target_aspect){
        $result_height  = $target_height;
        //$result_width     = round($target_height * $original_aspect);
        $result_width   = $original_width / ( $original_height/$target_height );
    } else {
        $result_width   = $target_width;
        //$result_height    = round($target_width / $original_aspect);
        $result_height  = $original_height / ( $original_width/$target_width );
    }
 
    // Create an empty black canvas to put the cropped image in
    $target = imagecreatetruecolor($target_width,$target_height);
 
    $ox = $oy = $tx = $ty = 0;      //Originals and Targets x and y coordinate for top-left corner
 
    // Checks if the image should be centred. Centers if it should be.
     if($centred){
            $tx     = 0 - (( $result_width - $target_width ) / 2);
            $ty     = 0 - (( $result_height - $target_height ) / 2);
     }
 
    //Image resizing and cropping
    if(!imagecopyresampled($target, $original, $tx, $ty, $ox, $oy, $result_width, $result_height, $original_width, $original_height ))
        return false; //Will throw an exception in future
 
    return $target;
}
?>