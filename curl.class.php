<?php
class CurlTool {
    public static $userAgents = array(
        'FireFox3' => 'Mozilla/5.0 (Windows; U; Windows NT 5.1; pl; rv:1.9) Gecko/2008052906 Firefox/3.0',
        'GoogleBot' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'IE7' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)',
        'Netscape' => 'Mozilla/4.8 [en] (Windows NT 6.0; U)',
        'Opera' => 'Opera/9.25 (Windows NT 6.0; U; en)'
        );
    public static $options = array(
        CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)',
        CURLOPT_AUTOREFERER => true,
        CURLOPT_COOKIEFILE => '',
        CURLOPT_FOLLOWLOCATION => true
        );
    public static $header = array();
           
    private static $proxyServers = array();
    private static $proxyCount = 0;
    private static $currentProxyIndex = 0;
    
       
    public static function addProxyServer($url) {
        self::$proxyServers[] = $url;
        self::$proxyCount;   
    }
	public static function checkUrl($url) {
        if (($curl = curl_init($url)) == false) {
            throw new Exception("curl_init error for url $url.");
        }		
		curl_setopt_array($curl,self::$options);
		curl_setopt($curl, CURLOPT_VERBOSE, false);
		curl_setopt($curl, CURLOPT_HEADER, true);    // we want headers
		curl_setopt($curl, CURLOPT_NOBODY, true);    // we don't need body		
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec($curl);
		$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);		
		curl_close($curl);
		if($httpcode == '200'){
			return true;			
		}else{
			return false;
		}
	}
    public static function fetchContent($url, $verbose = false) {
        if (($curl = curl_init($url)) == false) {
            throw new Exception("curl_init error for url $url.");
        }
       
        if (self::$proxyCount > 0) {
            $proxy = self::$proxyServers[self::$currentProxyIndex++ % self::$proxyCount];
            curl_setopt($curl, CURLOPT_PROXY, $proxy);
            if ($verbose === true) {
                echo "Reading $url [Proxy: $proxy] ... ";
            }
        } else if ($verbose === true) {
            echo "Reading $url ... ";   
        }
       
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt_array($curl, self::$options);
               
        $content = curl_exec($curl);
        if ($content === false) {
            throw new Exception("curl_exec error for url $url.");
        }
       	self::$header = curl_getinfo($curl);
        curl_close($curl);
        if ($verbose === true) {
            echo "Done.\n";
        }        
        return $content;
    }
   
    public static function downloadFile($url, $fileName, $verbose = false) {
        if (($curl = curl_init($url)) == false) {
            throw new Exception("curl_init error for url $url.");
        }
       
        if (self::$proxyCount > 0) {
            $proxy = self::$proxyServers[self::$currentProxyIndex++ % self::$proxyCount];
            curl_setopt($curl, CURLOPT_PROXY, $proxy);
            if ($verbose === true) {
                echo "Downloading $url [Proxy: $proxy] ... ";
            }
        } else if ($verbose === true) {
            echo "Downloading $url ... ";   
        }
       
        curl_setopt_array($curl, self::$options);
       
        if (substr($fileName, -1) == '/') {
            $targetDir = $fileName;
            $fileName = tempnam(sys_get_temp_dir(), 'c_');
        }
        if (($fp = fopen($fileName, "wb")) === false) {
            throw new Exception("fopen error for filename $fileName");
        }
        curl_setopt($curl, CURLOPT_FILE, $fp);
       
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, true);
        if (curl_exec($curl) === false) {
            fclose($fp);
            unlink($fileName);
            throw new Exception("curl_exec error for url $url.");
        } elseif (isset($targetDir)) {
            $eurl = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
            preg_match('#^.*/(.+)$#', $eurl, $match);
            fclose($fp);
            rename($fileName, "$targetDir{$match[1]}");
            $fileName = "$targetDir{$match[1]}";
        } else {
            fclose($fp);
        }
       
        curl_close($curl);
        if ($verbose === true) {
            echo "Done.\n";
        }
        return $fileName;
    }
       
    // activates POST and set post data
    public static function addPostData($data) {
        self::$options[CURLOPT_POST] = true;
        self::$options[CURLOPT_POSTFIELDS] = $data;
    }
}

 
?>