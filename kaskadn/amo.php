<?php 
//	http://127.0.0.1/amon/kaskadn/amo.php?filer=1&phone=89037992401
//	http://127.0.0.1/amon/kaskadn/amo.php?action=getcontact&phone=89266049613
	set_time_limit(0);
  	ignore_user_abort(true);
	global $outheaders, $RetCode,$amo;
	require_once('constants.php');
	require_once('curl.php');
	global $access_token;
	
	if (file_exists(getpath().'stop.txt')) unlink(getpath().'stop.txt');
	if (file_exists(getpath().'debug.log')) unlink(getpath().'debug.log');

	send_header('UTF-8');
	
	$cfg 		= readIniFile(getpath().'config.txt');
decho('$cfg='.print_r($cfg,1)); 
	$baseamo	= $cfg['amo_base'];

	if (file_exists(getpath().'time.txt'))
	{
		$time = file_get_contents(getpath().'time.txt');
	};
	
	if (file_exists(getpath().'refresh_token.txt'))
	{
		$refresh_token = file_get_contents(getpath().'refresh_token.txt');
	};
	if (file_exists(getpath().'access_token.txt'))
	{
		$access_token = file_get_contents(getpath().'access_token.txt');
	};
	if (isset($access_token))
	decho('$access_token='.$access_token);
	if (isset($refresh_token))
	decho('$refresh_token='.$refresh_token);
	$first = !isset($refresh_token);
	decho('$first='.$first);
	$num = 0;

	while(1)
	{
		
		$outheaders = [];
		$outheaders[]	= "Content-Type: application/json";
		$url = $baseamo.'/oauth2/access_token';
		if (!isset($access_token))
		{
			decho('!isset($access_token) first='.$first);
			// авторизация первичная
			if ($first)
			{
				$cfg 			= readIniFile(getpath().'config.txt');
				$client_secret	= $cfg['client_secret'];
				$client_id		= $cfg['client_id'];
				$redirect_uri	= $cfg['redirect_uri'];
				$code			= $cfg['code'];
				$grant_type		= 'authorization_code';
				
				$data= [
					'client_secret'	=>	$client_secret,
					'client_id'		=>	$client_id,
					'code'			=>	$code,
					'redirect_uri'	=>	$redirect_uri,
					'grant_type'	=>	'authorization_code',
				];
				
				$data = json_encode($data); 
				decho('$url='.$url);
				$p = decode(get_page_curl( $url, 1, $data));
				print_arr('$p',$p);
				if ($p['status']==400) 
				{
					if (file_exists(getpath().'refresh_token.txt'))	unlink(getpath().'refresh_token.txt');
					if (file_exists(getpath().'access_token.txt'))	unlink(getpath().'access_token.txt');
					die('status:400');
				};	
				decho('access_token='.$p['access_token']);
				decho('refresh_token='.$p['refresh_token']);

				if (isset($p['refresh_token'])) 
				{
					decho($p['refresh_token']);
					file_put_contents(getpath().'refresh_token.txt',$p['refresh_token']);
					$access_token = $p['access_token'];
					file_put_contents(getpath().'access_token.txt',$access_token);
				} else {
					if (file_exists(getpath().'refresh_token.txt'))	unlink(getpath().'refresh_token.txt');
					if (file_exists(getpath().'access_token.txt'))	unlink(getpath().'access_token.txt');
				};
			} else if (isset($refresh_token))
			{

				$cfg 			= readIniFile(getpath().'config.txt');
				$client_secret	= $cfg['client_secret'];
				$client_id		= $cfg['client_id'];
				$redirect_uri	= $cfg['redirect_uri'];
//				$refresh_token	= $cfg['refresh_token'];
//				$code			= $cfg['code'];
				
				// авторизация
				$data= [
					'client_secret'	=>	$client_secret,
					'client_id'		=>	$client_id,
					'redirect_uri'	=>	$redirect_uri,
					'refresh_token' => 	$refresh_token,
					'grant_type'	=>	'refresh_token'
				];
				$data = json_encode($data); 
				decho('$url='.$url);
//				decho(json_encode($data)); 
				$p = decode(get_page_curl( $url, 1, $data));
				print_arr('$p',$p);
				if (isset($p['access_token']))
				{
					$access_token = $p['access_token'];
					file_put_contents(getpath().'access_token.txt',$access_token);
				}
				if (isset($p['refresh_token']))
				{
					$refresh_token = $p['refresh_token'];
					file_put_contents(getpath().'refresh_token.txt',$refresh_token);
				}
			};	
		};

		$first = false;
	
		$outheaders[] = 'Authorization: Bearer ' . $access_token;

		$url = $baseamo.'/api/v2/account';
		$p   = '';
		$p	 = get_page_curl($url);
		
		$url = $baseamo.'/api/v4/leads/pipelines';
		$p	 = get_page_curl($url);
		$p 	 = json_decode($p,1);
	
		if (isset($p['status']) and $p['status'] == 401)
		{
			$num++;
			if ($num>2) die('<br>Unauthorized!');
		} else break;
	};
	decho('Анализ.');
	unlink(getpath().'access_token.txt');

	// читаем атс
	$saved = $_REQUEST;
	$_REQUEST['action'] = 'history';
	ob_start();
	$p = include('rest.php');
	$p = ob_get_contents();
	ob_end_clean();
	$_REQUEST = $saved;
/*	
	$filename	= '13102021_22_28.txt';
	$p		= file_get_contents($filename);
*/	
	$arr 	= explode("\x0D\x0A",$p);
	foreach($arr as $i=>$a) if (strlen($a)<80) unset($arr[$i]);
	
//	print_arr('',$arr);
//die();
	$nums	= [];
	decho('======================================================================================');
	foreach($arr as $a)
	{
		$_a = explode(",",$a);
		if (!isset($_a[2])) continue;
		$a  = $_a[2];
		$a = str_replace('+7','8',$a);
		$a = str_replace('(','',$a);
		$a = str_replace(')','',$a);
		$a = str_replace('-','',$a);
		$a = str_replace(' ','',$a);
		$a = '8'.substr($a,1);
		$nums[] = $a;
	};
//	print_arr('$nums',$nums);


	// собираем все контакты за 3 месяца
	$contacts = [];
	if (file_exists('contacts.txt')) 
	{ 
		$contacts	= file_get_contents('contacts.txt');
		$contacts	= json_decode($contacts,true);
	} else {	
		$contacts 	= getContacts(40);
		$contacts	= json_encode($contacts);
		file_put_contents('contacts.txt',$contacts);
	}	
	global $contacts;


	@unlink('debug.log');
/*
	foreach($nums as $num)
	{
		$contact = getFilterInArray($num);
		if ($contact==false) 
			addtolog($num);
		else {
			addtolog($num.' _embedded:'.$contact['_embedded']['leads'][0]['id']);
		}	
	}	
*/	
	if (file_exists('events.txt')) 
	{ 
		$events	= file_get_contents('events.txt');
		$events	= json_decode($events,true);
	} else {	
		$events = getEvents();
		$events	= json_encode($events);
		file_put_contents('events.txt',$events);
	}	
	global $events;
	
	if (isset($_REQUEST['action'])):
	$action = strtolower($_REQUEST['action']);
	decho('$action='.$action);
	switch($action)
	{
		case 'getcontact':
			if ($_REQUEST['id'])
			{
				$url = 'https://kaskadn.amocrm.ru/api/v4/contacts/'.$_REQUEST['id'];
				decho('$url='.$url);
				$p = get_page_curl( $url);
				$decode = decode($p);
				print_arr('contact',$decode);
			};
			
			$phone 		= $_REQUEST['phone'];
			decho('$phone='.$phone);
			$contact	= getFilterInArray($phone);
			
			print_arr('$contact[по телефону]',$contact);
			$id = $contact['id'];
			decho('$id='.$id);

			$url = 'https://kaskadn.amocrm.ru/api/v4/contacts/'.$id.'/notes?note_type=common';
decho($url);
			$p = get_page_curl( $url);
			$decode = decode($p);
			print_arr('$decode',$decode);
			$url = 'https://kaskadn.amocrm.ru/api/v4/contacts/'.$id.'/notes?note_type=call_in';
decho($url);
			$p = get_page_curl( $url);
			$decode = decode($p);
			print_arr('$decode',$decode);
			$url = 'https://kaskadn.amocrm.ru/api/v4/contacts/'.$id.'/notes?note_type=call_out';
decho($url);
			$p = get_page_curl( $url);
			$decode = decode($p);
			print_arr('$decode',$decode);
			$url = 'https://kaskadn.amocrm.ru/api/v4/contacts/'.$id.'/notes?note_type=service_message';
decho($url);
			$p = get_page_curl( $url);
			$decode = decode($p);
			print_arr('$decode',$decode);
			
			$url = 'https://kaskadn.amocrm.ru/api/v4/leads/29961757/notes';
decho($url);
			$p = get_page_curl( $url);
			$decode = decode($p);
			print_arr('$decode',$decode);
unlink('debug.log');
			$url = 'https://kaskadn.amocrm.ru/api/v4/leads/29961757/notes';
decho($url);
			$data = [
				[
					"note_type"	=> "call_in",
					"params"	=> [ 
						"uniq"		=> "3779c489-45f0-4f60-8ffb-5909f239c926",
						"source"	=> "onlinePBX",
						"duration"	=> 38,
						"phone"		=> "79166343876",
//						"link"		=> "https://records.megapbx.ru/record/kaskad-nedvizhimost.megapbx.ru/2021-10-15/4aa32fe4-9ee0-4611-9e82-10099b9affaf/polguj_a_out_2021_10_15-10_33_10_79154028383_zvpd.mp3"
						"link"		=> "https://records.megapbx.ru/record/kaskad-nedvizhimost.megapbx.ru/2021-10-15/3779c489-45f0-4f60-8ffb-5909f239c926/kashin_d_out_2021_10_15-10_54_05_79166343876_fh4y.mp3",
					],
				]
			];
/*			
			$data = [
				[
					"note_type"	=> "attachment",
					"params"	=> [ 
						"source"		=> "https://records.megapbx.ru/record/kaskad-nedvizhimost.megapbx.ru/2021-10-15/4aa32fe4-9ee0-4611-9e82-10099b9affaf/polguj_a_out_2021_10_15-10_33_10_79154028383_zvpd.mp3",
						"attachment"	=> "https://records.megapbx.ru/record/kaskad-nedvizhimost.megapbx.ru/2021-10-15/4aa32fe4-9ee0-4611-9e82-10099b9affaf/polguj_a_out_2021_10_15-10_33_10_79154028383_zvpd.mp3",
					],
				]
			];
*/			
			$data = json_encode($data);
			$p = get_page_curl( $url, 1 , $data);
			$decode = decode($p);
			print_arr('$decode',$decode);

			break;
	};
	endif;

	die('<br>Конец.');
	//------------------------------------------------------------
	function decode($p)
	{
		$pos= strpos( $p, "{");
		if ($pos===false) return '';
		$p	= json_decode( substr( $p, $pos), true);
		if (!isset($p['_embedded'])	) return $p;

		if (isset($p['_embedded']['companies'])		)
		foreach($p['_embedded']['companies'] as $n=>$e)
		{
			if (strpos($p['_embedded']['companies'][$n]['created_at'],'-')!==false) break; 
			$p['_embedded']['companies'][$n]['created_at'] = Date("d-m-Y H:s",$p['_embedded']['companies'][$n]['created_at']);
			$p['_embedded']['companies'][$n]['updated_at'] = Date("d-m-Y H:s",$p['_embedded']['companies'][$n]['updated_at']);
		};

		if (isset($p['_embedded']['contacts'])		)
		foreach($p['_embedded']['contacts'] as $n=>$e)
		{
			if (strpos($p['_embedded']['contacts'][$n]['created_at'],'-')!==false) break; 
			$p['_embedded']['contacts'][$n]['created_at'] = Date("d-m-Y H:s",$p['_embedded']['contacts'][$n]['created_at']);
			$p['_embedded']['contacts'][$n]['updated_at'] = Date("d-m-Y H:s",$p['_embedded']['contacts'][$n]['updated_at']);
		};
		
		if (isset($p['_embedded']['notes'])		)
		foreach($p['_embedded']['notes'] as $n=>$e)
		{
			if (!isset($p['_embedded']['notes'][$n]['created_at'])) continue;
			if (strpos($p['_embedded']['notes'][$n]['created_at'],'-')!==false) break; 
			$p['_embedded']['notes'][$n]['created_at'] = Date("d-m-Y H:s",$p['_embedded']['notes'][$n]['created_at']);
			$p['_embedded']['notes'][$n]['updated_at'] = Date("d-m-Y H:s",$p['_embedded']['notes'][$n]['updated_at']);
		};

		if (isset($p['_embedded']['events'])		)
		foreach($p['_embedded']['events'] as $n=>$e)
		{
			if (strpos($p['_embedded']['events'][$n]['created_at'],'-')!==false) break; 
			$p['_embedded']['events'][$n]['created_at'] = Date("d-m-Y H:s",$p['_embedded']['events'][$n]['created_at']);
		};
		
		return $p;
	}
	//----------------------------------------------------------------------------
	function getLeads($params = [])	// список сделок
	{
		
		global $baseamo;
		$url = $baseamo.'/api/v4/leads';
		$url = $baseamo.'/api/v4/leads/pipeline/3982795';
		$maxpage = 0;
		if (isset($params['page'])) $maxpage = $params['page']*1;
//		$url.= '?order[updated_at]=desc';
		
//		$url.= '/pipeline/?filter[cf][689337]=59000638581989&useFilter=y';
//		$url = '/pipeline/?filter[cf][689337]=&useFilter=y';

		if (isset($params['date_create']))
			$url.= '?filter%5Bdate_create%5D%5Bto%5D='.time()-86400;
		if (isset($params['responsible_user_id']))
			$url.= '?filter%5Bresponsible_user_id%5D%5Bto%5D='.$params['responsible_user_id'];
		
		if (isset($_REQUEST['action']) && $_REQUEST['action']=='leads' )
		{
			$url.= '?id='.$_REQUEST['id'];//7295533
		}
		if (isset($_REQUEST['query']))
		{
			$url.= '?query='.$_REQUEST['query'];
		}
		$page = 0;
		$leads =[];
decho('$url='.$url);
decho('$maxpage='.$maxpage.' $url='.$url);
		WHILE (1)
		{
		
			$page++;
			if ($page>$maxpage) break;
			$url = $baseamo.'/api/v4/leads';
			decho('---$maxpage>0='.($maxpage>0));
			if ($maxpage>0) $url.= '?page='.$page;
			decho('$url='.$url);
			$p = get_page_curl( $url);
			$decode = decode($p);
//			print_arr('',decode($p));
			if (isset($decode['_embedded']['leads']))
			{
				$leads = array_merge($decode['_embedded']['leads'],$leads);
				decho('count($leads)='.count($leads));
				if(count($leads)<50) break;
			};	
		};
//		print_arr('$leads',$leads);
		return $leads;
		
	};
	//----------------------------------------------------------------------------
	function getEvents($maxcount=200)
	{
		
		global $baseamo;
		
		$baseurl = $baseamo.'/api/v4/events';
		$baseurl.= '?order[update_at]=desc';
//		$baseurl.= '&filter[type]=incoming_call';
//		$url.= '&filter[type]=outgoing_call';
		decho('$baseurl='.$baseurl);
		$events = [];
		for($i=1; $i<=$maxcount;$i++)
		{
			$url = $baseurl;
			if ($i>1)
			$url = $baseurl.'&page='.$i;
			decho('$url='.$url);
			$p = get_page_curl( $url);
			if ($p == '') break;
			$decode = decode($p);
			$decode = $decode['_embedded']['events'];
			foreach($decode as $n=>$event)
			{
//				decho(Date("d-m-Y H:s",$decode[$n]['created_at']));
				$decode[$n]['created_at'] = Date("d-m-Y H:s",$decode[$n]['created_at']);
			}
			$events = array_merge($decode,$events);
		}	
		return $events;
	};
	//----------------------------------------------------------------------------
	function getFilterInArray($phone='89778722509')
	{
		
		global $baseamo,$contacts;
		
		if (isset($_REQUEST['phone'])) $phone = $_REQUEST['phone'];
//print_arr('$contacts',$contacts); die();
		foreach($contacts as $i=>$element)
		{
//print_arr('$element',$element); //die();
			
//print_arr('$element',$element); //die();
			$flag = false;
			if (isset($element['custom_fields_values']) && count($element['custom_fields_values'])>0)
			foreach($element['custom_fields_values'] as $n=>$field)
			{
				if ($field['field_name']=='Телефон')
				{	
					$element['custom_fields_values'][$n]['values'][0] = str_replace('+7','8',$element['custom_fields_values'][$n]['values'][0]);
					$element['custom_fields_values'][$n]['values'][0] = str_replace('(','',$element['custom_fields_values'][$n]['values'][0]);
					$element['custom_fields_values'][$n]['values'][0] = str_replace(')','',$element['custom_fields_values'][$n]['values'][0]);
					$element['custom_fields_values'][$n]['values'][0] = str_replace('-','',$element['custom_fields_values'][$n]['values'][0]);
					$element['custom_fields_values'][$n]['values'][0] = str_replace(' ','',$element['custom_fields_values'][$n]['values'][0]);
//					decho($element['custom_fields_values'][$n]['values'][0]['value'].':'.$element['id']);
					if (strpos($element['custom_fields_values'][$n]['values'][0]['value'],$phone)!==false) 
					{	
						return $element;
					}	
					$flag = true;
					break;
				};
			};	
		}
		return false;
	};	
	//----------------------------------------------------------------------------
	function getFilter($id,$dir='contacts',$phone='89778722509')
	{
		
		global $baseamo,$contacts;
		
		if (isset($_REQUEST['phone'])) $phone = $_REQUEST['phone'];
		
	// поиск по телефону
		$page = 0;
		while(1){
		$page++;	
		$url = $baseamo."/api/v4/$dir?order[updated_at]=desc";
		if ($page>1) $url.='&page='.$page;
		if ($page>100) die('$page>100');
		
		decho('$url='.$url);
		$p = get_page_curl( $url);
		$decode = decode($p);
		if (isset($decode['_embedded']) && count($decode['_embedded'][$dir])>0) 
		foreach($decode['_embedded'][$dir] as $i=>$element)
		{
			if (isset($_REQUEST['filter'])):
			$flag = false;
			if (isset($element['custom_fields_values']) && count($element['custom_fields_values'])>0)
			foreach($element['custom_fields_values'] as $n=>$field)
			{
				if ($field['field_name']=='Телефон')
				{	
//					decho($element['custom_fields_values'][$n]['values'][0]['value']);
					if ($element['custom_fields_values'][$n]['values'][0]['value']==$phone) 
					{	
						print_arr('',$decode['_embedded'][$dir][$i]);
						die('!!!');
					}	
					$flag = true;
					break;
				};
			};	
			if ($flag) 
			{
				unset($decode['_embedded'][$dir][$i]);
				continue;
			};
			endif;
			$decode['_embedded'][$dir][$i]['created_at'] = Date("d-m-Y H:s",$decode['_embedded'][$dir][$i]['created_at']);
			$decode['_embedded'][$dir][$i]['updated_at'] = Date("d-m-Y H:s",$decode['_embedded'][$dir][$i]['updated_at']);
		}
		}
		print_arr('',$decode);
//		$decode = decode($p);
//		print_arr('',$decode);
		
die();		



		$url = $baseamo."/api/v4/$dir/37290652";
//		$url = $baseamo."/api/v4/$dir?order[updated_at]=desc";
		decho('$url='.$url);
		$p = get_page_curl( $url);
		$decode = decode($p);
		if (isset($decode['_embedded']) && count($decode['_embedded'][$dir])>0) 
		foreach($decode['_embedded'][$dir] as $i=>$element)
		{
			if (isset($_REQUEST['filter'])):
			$flag = false;
			foreach($element['custom_fields_values'] as $n=>$field)
			{
				if ($field['field_name']!=='Телефон')
				{	
					$flag = true;
					break;
				};
			};	
			if ($flag) 
			{
				unset($decode['_embedded'][$dir][$i]);
				continue;
			};
			endif;
			$decode['_embedded'][$dir][$i]['created_at'] = Date("d-m-Y H:s",$decode['_embedded'][$dir][$i]['created_at']);
			$decode['_embedded'][$dir][$i]['updated_at'] = Date("d-m-Y H:s",$decode['_embedded'][$dir][$i]['updated_at']);
		}
		print_arr('',$decode);
//		$decode = decode($p);
//		print_arr('',$decode);
		
die();		
		return $contacts;
		
	};
	//----------------------------------------------------------------------------
	function getContacts($maxpage=10,$params = [])	// список контактов
	{
		
		global $baseamo;
		$page = 0;
decho('$url='.$url);
decho('$maxpage='.$maxpage.' $url='.$url);
		$contacts = [];
		WHILE (1)
		{
			$page++;
			if ($page>$maxpage) break;
			$url = $baseamo.'/api/v4/contacts?';
			if ($maxpage>0) $url.= '&page='.$page;
			$url.='&order[updated_at]=desc';

			decho('$url='.$url);
			$p = get_page_curl( $url);
			$decode = decode($p);
//			print_arr('',decode($p));
			if (isset($decode['_embedded']['contacts']))
			{
				$contacts = array_merge($decode['_embedded']['contacts'],$contacts);
				decho('count($contacts)='.count($contacts));
				if(count($decode['_embedded']['contacts'])<50) break;
			};	
		};
//		print_arr('$contacts',$contacts);
		return $contacts;
		
	};
// https://www.amocrm.ru/developers/content/crm_platform/contacts-api
/*
POST /api/v4/{entity_type}/notes
	function addNote()
	{
	//----------------------------------------------------------------------------
	function getContacts($maxpage=10,$params = [])	// список контактов
	{
		global $baseamo;
		$url = $baseamo.'/api/v4/contacts/notes';

		$p = get_page_curl( $url);
		
		
	}
*/	