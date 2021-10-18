<?php
	@error_reporting ( E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE );
	@ini_set ( 'error_reporting', E_ALL ^ E_WARNING ^ E_DEPRECATED ^ E_NOTICE );

	@ini_set ( 'display_errors', true );
	@ini_set ( 'html_errors', false );
	$path = $_SERVER['DOCUMENT_ROOT'].'/kaskadn/';
	require_once($path.'constants.php');
	addtolog('cron05min','cron.log');
	ob_start();
	include('/var/www/html/kaskadn/amo-ats.php');
	$p = ob_get_contents();
	ob_end_clean();
	addtolog('$p='.$p,'cron.log');
	

	function addtolog($str, $name = 'debug.log') 
	{
		if (file_exists($name)) { 
			if (filesize($name)>(50*1024*1024)) { unlink($name);  $file = fopen ($name,"w+"); } else
			$file = fopen ($name,"a+"); 
		} else { 
			$file = fopen ($name,"w+");
		};
		@fputs($file, Date('H:i:s').' ');
		@fputs($file, $str);
		fputs($file, "\r\n");
		fclose ( $file );
	}
