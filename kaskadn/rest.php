<?php
//	http://127.0.0.1/amon/kaskadn/rest.php?action=history
//	http://127.0.0.1/amon/kaskadn/rest.php?action=history?view=1
			//YYYYmmddTHHMMSSZ, где
//	https://94.228.113.92/kaskadn/rest.php?action=history&start=20211016T000000Z&end=20211017T000000Z

	require_once 'constants.php'; 

	$path = $_SERVER['DOCUMENT_ROOT'].'/';
	if (strpos($_SERVER['SERVER_NAME'],'127.0.0.1')!==false) $path .='wordpress/';

	global $outheaders,$location,$baseurl,$token;
	send_header('utf-8');

	$cfg 		= readIniFile(getpath().'config.txt');
	if (isset($_REQUEST['view'])) print_arr('$cfg',$cfg);

	$token 	= $cfg['token'];

	$baseurl = 'https://kaskad-nedvizhimost.megapbx.ru/sys/crm_api.wcgp';

	if (file_exists('stop.txt')) unlink('stop.txt');

	if (isset($_REQUEST['action']) )
	{
		$action = strtolower($_REQUEST['action']);

		switch ($action)
		{
			case 'history':	history('yesterday');	break;
		};
	};

	//------------------------------------------------------------------------------
	function view($filename,$p=0)
	{
		$out	= '<table><thead></thead><tbody>';
		
		$arr = explode("\x0D\x0A",$p);
print_arr('',$arr);
		foreach($arr as $a)
		{
			$a = explode(",",$a);
			$out.='<tr>';
			for($i=0;$i<count($a);$i++)
				$out.='<td>'.$a[$i].'</td>';
			$out.='</tr>';
		}
		$out	.= '</tbody></table>';
		echo $out;
	};
	//------------------------------------------------------------------------------
	function history($period='today')
	{
		global $outheaders,$location,$baseurl,$token;
		$url = $baseurl."?cmd=history&token=$token";
		if (isset($_REQUEST['start']))
		{
			//YYYYmmddTHHMMSSZ, где
			$start  = $_REQUEST['start'];
			$end  	= $_REQUEST['end'];
			$url.= "&start=$start&end=$end";
		} else 
			$url.= "&$period";
		addtolog('history $url='.$url);
		if (isset($_REQUEST['view']))
		decho($url);
		$p		= file_get_contents($url);
		$time	= time();
		$filename	= Date("dmY_H_i",$time).'.txt';
		$path 	= getpath().'data';
		if (!file_exists($path)) mkdir($path);
		$filename 	= $path.'/'.$filename;

		if ($period!='today')
		{
			$url = $baseurl."?cmd=history&token=$token&period=yesterday";
			addtolog('history $url='.$url);
			$p1			= file_get_contents($url);
			$time	= time();
			$filename	= Date("dmY_H_i",$time).'.txt';
			$path 	= getpath().'data';
			if (!file_exists($path)) mkdir($path);
			$filename 	= $path.'/'.$filename;
			$p = $p.$p1;
		}	
		file_put_contents($filename,$p);

		if (isset($_REQUEST['view']))
			view($filename,$p);
		else
			echo $p;
		
		return	$p;
	};
	