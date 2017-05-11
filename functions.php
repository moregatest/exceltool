<?php 

function preg_ls($path = ".", $rec = false, $pat = "/.*/") {	
	// it's going to be used repeatedly, ensure we compile it for speed.
	//$pat=preg_replace("|(/.*/[^S]*)|s", "\\1S", $pat);
	//Remove trailing slashes from path
	while (substr($path, -1, 1) == "/") $path = substr($path, 0, -1);
	//also, make sure that $path is a directory and repair any screwups
	if (!is_dir($path)) $path = dirname($path);
	//assert either truth or falsehoold of $rec, allow no scalars to mean truth
	if ($rec !== true) $rec = false;
	//get a directory handle
	//$firephp->log($path,"¤ÀªR¸ô®|");
	if (!is_dir($path)) {
		return false;
	}
	$d = @dir($path);
	//initialise the output array
	$ret = Array();
	//loop, reading until there's no more to read
	while (false !== ($e = $d->read())) {
		//Ignore parent- and self-links
		if (($e == ".") || ($e == "..")) continue;
		//If we're working recursively and it's a directory, grab and merge
		if ($rec && is_dir($path . "/" . $e)) {
			$ret = array_merge($ret, preg_ls($path . "/" . $e, $rec, $pat));
			continue;
		}
		//If it don't match, exclude it
		if (!preg_match($pat, $e)) continue;
		$ret[] = $path . "/" . $e;
	}
	//finally, return the array
	return $ret;
}
function ProPathInfo($filepath){
    if(preg_match("#\\\#", $filepath)){ //3個斜線代表1個斜線，應該是因為要跳脫2次
        $delimiter = '\\'; //2個斜線代表1個斜線
    }
    else{
        $delimiter = '/';
    }
    $path_parts = array();   
    $path_parts['basename'] = ltrim(substr($filepath, strrpos($filepath, $delimiter)),$delimiter);   
    $path_parts['extension'] = substr(strrchr($filepath, '.'), 1);
    $path_parts['filename'] = ltrim(substr($path_parts ['basename'], 0, strrpos($path_parts ['basename'], '.')),$delimiter);
    $pos = strrpos($filepath, $path_parts['filename']);
    $path_parts['dirname'] = substr($filepath, 0, $pos);
    return $path_parts;   
}  
?>