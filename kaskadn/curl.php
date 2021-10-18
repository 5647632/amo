<?php 
	global $outheaders,$RetCode;
	//--------------------------------------------------------------------------
	function get_page_curl( $url, $post=0, $data=null, $patch='' )
	{
		global $outheaders,$RetCode;
		addtolog('$url='.$url);
		addtolog('$data='.$data);
		addtolog('$outheaders='.print_r($outheaders,1));
		
		$cmd = 'POST';
		if ($post==0 && $data==null)$cmd = 'GET';
		if (strlen($patch)==5) $cmd = 'PATCH';
//		addtolog('cmd='.$cmd);
		$process = curl_init($url);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
		curl_setopt($process, CURLOPT_URL, $url);
		curl_setopt($process, CURLOPT_HTTPHEADER, $outheaders);
		$h = false; if (strlen($patch)==5) $h = 1;
		curl_setopt($process, CURLOPT_HEADER, $h);
		if($post)
		{
			curl_setopt($process, CURLOPT_CUSTOMREQUEST, $cmd);
			curl_setopt($process, CURLOPT_POSTFIELDS, $data);
		};
		curl_setopt($process, CURLOPT_SSL_VERIFYPEER, 1);
		curl_setopt($process, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		$return = @curl_exec($process);
		$curl_ch_info = curl_getinfo($process);
//		addtolog('$curl_ch_info='.print_r($curl_ch_info,1));
		curl_close($process);
//		addtolog('$return='.$return);
		return $return;
	}
