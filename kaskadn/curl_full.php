<?php 
	global $outheaders,$RetCode;
	//--------------------------------------------------------------------------
	function get_page_curl( $url, $post=0, $data=null, $header = 0, $user='', $password='' )
	{
	
		global $outheaders,$RetCode,$location;;
		addtolog('$url='.$url);
		addtolog('$outheaders='.print_r($outheaders,1));
		addtolog('$post='.$post.' $header='.$header);
		if (!isset($cookies_arr)) $cookies_arr = array();
		$arr 	= explode('/',$url);
		$location = '';
		$process = curl_init($url);
		if( ($post==1) )
		{
			curl_setopt($process, CURLOPT_POSTFIELDS, $data);
			curl_setopt($process, CURLOPT_POST, 1);
		}
		curl_setopt($process, CURLOPT_URL, $url);
		curl_setopt($process, CURLOPT_HEADER, $header);
		curl_setopt($process, CURLOPT_HTTPHEADER, $outheaders);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		// игнорируем сертификаты при работе с SSL
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($process, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($process, CURLOPT_CONNECTTIMEOUT, 10);
		
		$return = curl_exec($process);
//		addtolog('Передача...');
		$curl_ch_info = curl_getinfo($process);
//		addtolog('$curl_ch_info='.print_r($curl_ch_info,1));
		
//		addtolog('$curl_ch_info[url]='.$curl_ch_info['url']);
//		addtolog('$curl_ch_info[content_type]='.$curl_ch_info['content_type']);
//		addtolog('$curl_ch_info[http_code]='.$curl_ch_info['http_code']);
		$RetCode = $curl_ch_info['http_code'];

		$pos  = strpos($return,"<!DOCTYPE");
		if ($pos===false) $pos  = strpos($return,"<!doctype");
		if ($pos===false) $pos  = strpos($return,"<html");
		if ($pos>0) $pos  = strpos($return,"\n\r");
		if ($pos===false) { 
			if (strpos($return,'JFIF')==6 ) { $headers = ''; $pos = 0; } else
			if (strpos($return,'GIF'))  { $headers = ''; $pos = strpos($return,'GIF'); } else
			{ $headers = $return; $pos = strlen($return); };
		} else  
			if ($pos>0) { $headers = substr($return, 0   , $pos-1);} else
			{ $headers = ''; $pos = 0; };

		if (strpos($return,'?PNG')>0)  $pos = strpos($return,'?PNG');
		if($count = preg_match_all("~Set-Cookie:\s*([^=]+)=([^\s;]+)~si", $headers, $matches))
		{
			$new_cookies_arr = array();
			for ($i=0; $i<$count; $i++){
				$cookies_arrmatches[1][$i] = $matches[2][$i];
				$new_cookies_arr[$matches[1][$i]] = $matches[2][$i];
			};
			$cookies_arr = array_merge( $cookies_arr, $new_cookies_arr);
		};
		$pos = strpos($headers, "location:");
		if($pos>0) {
			$pos = strpos($headers, " ", $pos+1);
			$location = substr($headers, $pos+1, strlen($headers)-$pos-1);
			$pos = strpos($location, "\r");
			if ($pos>0) $location = substr($location, 0, $pos);
		};
		if (strlen($curl_ch_info['redirect_url'])>5) $location = $curl_ch_info['redirect_url'];
		
		curl_close($process);

		$pos = strpos($return, "<?xml ");
		if ($pos > 1) {
			$return = substr($return, $pos, strlen($return) - $pos);
		};
		if ($header)
		{
			$pos	= strpos( $return, "\x0D\x0A\x0D\x0A")+4;
			$return	= substr( $return, $pos );
		}
//		addtolog('$return='.$return);
		return $return;
	}
	//--------------------------------------------------------------------------
	function set_cookies($names=array())
	{
		global $proxy,$need_proxy,$all_useragents,$numproxy, $cookies,$headers;
		global $cookies_arr;
		$cookies = '';
		if (isset($cookies_arr)){
			foreach($cookies_arr as $key =>$value) {
				if ((count($names)==0) || in_array($key,$names)) {
					if (strpos($value,'deleted')===false) 
					if ($key!='0')	$cookies .= $key.'='.$value.';';
				};
			}
		}
		$s = '';
		foreach ($names as $key => $value){
			if ($key!='0') $s.= '/'.$key.':'.$value;
		};
		$s = '';
		if (count($cookies_arr)>0)
		foreach ($cookies_arr as $key => $value){
			if ($key!='0') $s.= '/'.$key.':'.$value;
		};
		$pos = strlen($cookies);
		$cookies = substr($cookies,0,$pos-1);
		return $cookies;
	}
	//--------------------------------------------------------------------------
	function set_outheaders() 
	{
		$h['Accept']			= "Accept: */*";
		$h['Accept-Language']	= "Accept-Language: en-US,en;q=0.5";
		$h['Accept-Encoding']	= "Accept-Encoding: deflate, gzip, br";
		$h['Connection']		= "Connection:keep-alive";
		
//		$h['Cache-control'] 	= 'Cache-control:max-age=0';

		$h['pragma']			= 'pragma: no-cache';
//		$h['sec-fetch-dest']	= 'sec-fetch-dest: document';
//		$h['sec-fetch-mode']	= 'sec-fetch-mode: navigate';
//		$h['sec-fetch-site']	= 'sec-fetch-site: none';
		$h['User-Agent']		= 'User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:48.0) Gecko/20100101 Firefox/48.0';
		$h['upgrade-insecure-requests'] = 'upgrade-insecure-requests: 1';
		return $h;
	}
