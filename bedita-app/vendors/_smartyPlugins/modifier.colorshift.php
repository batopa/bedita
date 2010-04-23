<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     colorshift
 * Version:  1.0
 * Author:   xho - Christiano Presutti
 * Purpose:  shift Hue, Saturation or Brightness in hex colors (saturation modification is to be refined)
 * Input:    hex color (with or without hash on beginning), parameter to change = "H"ue or "S"aturation or "B"rightness, amount (percent from -100 to 100)
 * -------------------------------------------------------------
 */

function smarty_modifier_colorshift($string, $amount=10, $param="B")
{
	/*
	 * verify and cleanup params
	 */
	
    if (empty($string))
    {
    	return; // missing argument hex color
	}

	// hash
	$stringWithHash = false;
	if (substr($string, 0, 1) == "#")
	{
		$string = substr ($string, 1, 6);
		$stringWithHash = true;
	}

	// lenght
	if ( (strlen($string) != 6) && (strlen($string) != 3) ) {
		return; // argument hex color looks wrong
	}
	
	// param
	$param = strtoupper($param);
	if (strlen($param) != 1 && $param != "H" && $param != "S" && $param != "B")
		$param = "B"; // defaults to Brightness

	// amount
	if (is_nan($amount)) $amount = 10; // defaults to 50%
	if ($param != "H")
	{
		if ($amount > 100) $amount = 100;
		if ($amount < -100) $amount = -100;
	}

	// changed for function changeBrightness below
/*
	if ($param == "B")
	{
		if ($amount < 0) $amount = 0;
	}
*/
	// 3 chars hex -> 6 chars hex (RGB -> RRGGBB)	
	if (strlen($string) == 3)
	{
		$string = substr($string, 0, 1) + substr($string, 0, 1) + substr($string, 1, 1) + substr($string, 1, 1) + substr($string, 2, 1) + substr($string, 2, 1);
	}

	// transform
	switch ($param) {
		case "S":
			// hex -> HSV (is array)
			$HSV = hex2hsv ($string);
			$HSV[1] = $HSV[1] + ($HSV[1] * $amount / 100);
			return (empty($stringWithHash)? "" : "#") . hsv2hex($HSV[0], $HSV[1], $HSV[2]);

		case "H":
			// hex -> HSV (is array)
			$HSV = hex2hsv ($string);
			$HSV[0] = $HSV[0] + ($HSV[0] += $amount); //to be finished because maybe with polar coordinates
			return (empty($stringWithHash)? "" : "#") . hsv2hex($HSV[0], $HSV[1], $HSV[2]);

		case "B":
		default:
			return (empty($stringWithHash)? "" : "#") . changeBrightness($string, $amount);
	}

}





/**
* converts a hexidecimal color to HSV Color Model
* @param $hex hexidecimal color
* @returns array( hue, saturation, value )
* 0 < hue < 360 (circle)
* 0 < sat < 100
* 0 < val < 100
*/
function hex2hsv ($_hex)
{
	$rgb = hexdec(str_replace("#","",$_hex));
	list ($_red,$_green,$_blue) = array(($rgb >> 16) & 0xFF, ($rgb >> 8) & 0xFF, $rgb & 0xFF);
	
	// get highest and lowest colors
	$max = max($_red,$_green,$_blue);
	$min = min($_red,$_green,$_blue);
	
	$v = $max;          // 'value' is always the highest value from RGB
	$delta = $max-$min; // get midway between highest and lowest, this is our delta!
	if ($max == 0)  return Array('0','0','0'); // this is black, if the biggest value is 0
	
	$s = 100*($delta/$max);  // 'saturation' is our delta divided by max 
	
	// see which color is most dominant
	if ($_red == $max)          $h = @round(( $_green - $_blue ) / $delta);     // between yellow & magenta
	elseif ($_green == $max)    $h = @round(2 + ( $_blue - $_red ) / $delta);   // between cyan & yellow
	else                        $h = @round(4 + ( $_red - $_green ) / $delta);  // between magenta & cyan
	
	$h*=60; // move into primary/secondary color group
	
	// we can't be having negatives.. if it's in negatives, add 360 (full circle)
	$h = ($h < 0) ? $h+=360 : $h;
	return Array(ceil($h),ceil($s),ceil(100*($v/255)));
}





/** 
* converts a HSV color to hexidecimal 
* @param $_hue HSV Hue
* @param $_sat HSV saturation
* @param $_val HSV value
* @returns hexidecimal color (without #)
*/
function hsv2hex ($_hue, $_sat, $_val) 
{
	// safegaurds against invalid values
	if ($_hue < 0 ) $_hue+=359; elseif ($_hue > 359) $_hue-=359;
	if ($_val > 100) $_val=100; elseif ($_val < 0) $_val = 0; 
	if ($_sat > 100) $_sat=100; elseif ($_sat < 0) $_sat=0;
	if ($_sat == 0) {
		$_val = floor($_val*2.55);
		return str_pad(dechex($_val),2,'0',STR_PAD_LEFT).str_pad(dechex($_val),2,'0',STR_PAD_LEFT).str_pad(dechex($_val),2,'0',STR_PAD_LEFT);
	} // this is grey
	
	$_hue/=60;              // move hue into 1-6 (primary & secondary colors)
	$_sat/=100; $_val/=100; // divide by 100 so we are dealing with proper 0.0 - 0.1 
	$factor = $_hue-floor($_hue); // get fractional part of the hue
	
	// math to get into the 255 range of things from the _sat and _val
	$color1 = ceil($_val * (1-$_sat)*255);
	$color2 = ceil($_val * (1-($_sat * $factor))*255);
	$color3 = ceil($_val * (1-($_sat * (1-$factor)))*255);
	$_val = ceil($_val*255);
	
	// return rgb based on which primary/secondary color group we are in
	switch (floor($_hue))
	{
		case 0: $red = $_val; $green = $color3; $blue = $color1; break;
		case 1: $red = $color2; $green = $_val; $blue = $color1; break;
		case 2: $red = $color1; $green = $_val; $blue = $color3; break;
		case 3: $red = $color1; $green = $color2; $blue = $_val; break;
		case 4: $red = $color3; $green = $color1; $blue = $_val; break;
		case 5: $red = $_val; $green = $color1; $blue = $color2; break;
	}
	return str_pad(dechex($red),2,'0',STR_PAD_LEFT).str_pad(dechex($green),2,'0',STR_PAD_LEFT).str_pad(dechex($blue),2,'0',STR_PAD_LEFT);
}


// change brightness, adjust is percent
function changeBrightness ( $hex, $adjust )
{
	$red   = hexdec( $hex[0] . $hex[1] );
	$green = hexdec( $hex[2] . $hex[3] );
	$blue  = hexdec( $hex[4] . $hex[5] );

	$red	= $red + ($red * $adjust / 100);
	$green	= $green + ($green * $adjust / 100);
	$blue	= $blue + ($blue * $adjust / 100);

	if ( $red < 0 ) $red = 0;
	if ( $green < 0 ) $green = 0;
	if ( $blue < 0 ) $blue = 0;
	if ( $red > 255 ) $red = 255;
	if ( $green > 255 ) $green = 255;
	if ( $blue > 255 ) $blue = 255;
    
	return str_pad( dechex( $red ), 2, '0', 0 )
		. str_pad( dechex( $green ), 2, '0', 0 )
		. str_pad( dechex( $blue ), 2, '0', 0 );
}




// change image absolute brightness, adjust is absolute desired brightness from 0 to 100
function absoluteBrightness ( $hex, $adjust )
{
	$red   = hexdec( $hex[0] . $hex[1] );
	$green = hexdec( $hex[2] . $hex[3] );
	$blue  = hexdec( $hex[4] . $hex[5] );
	
	$cb = $red + $green + $blue;
	
	$adjust = (765 * $adjust) / 100; // amount should be between 0 and 765
	
	if ( $cb > $adjust ) {
		$db = ( $cb - $adjust ) % 255;
		
		$red -= $db; $green -= $db; $blue -= $db;
		if ( $red < 0 ) $red = 0;
		if ( $green < 0 ) $green = 0;
		if ( $blue < 0 ) $blue = 0;
	} else {
		$db = ( $adjust - $cb ) % 255;
		
		$red += $db; $green += $db; $blue += $db;
		if ( $red > 255 ) $red = 255;
		if ( $green > 255 ) $green = 255;
		if ( $blue > 255 ) $blue = 255;
	}
	
	return str_pad( dechex( $red ), 2, '0', 0 )
			. str_pad( dechex( $green ), 2, '0', 0 )
			. str_pad( dechex( $blue ), 2, '0', 0 );
}
?>