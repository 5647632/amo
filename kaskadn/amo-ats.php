<?php 
//	http://127.0.0.1/amon/kaskadn/cron5min.php
//	http://127.0.0.1/amon/kaskadn/amo-ats.php
//	http://127.0.0.1/amon/kaskadn/amo-ats.php?phone=89854348321

//	https://94.228.113.92/kaskadn/amo-ats.php
//	https://94.228.113.92/kaskadn/amo-ats.php?phone=89854348321&start=20211016T000000Z&end=20211017T000000Z&phone=89854348321
//	https://94.228.113.92/kaskadn/cron5min.php

	set_time_limit(1200);
  	ignore_user_abort(true);
	global $outheaders, $RetCode,$amo;
	require_once('constants.php');
	require_once('curl.php');
	global $access_token;
	
	if (file_exists(getpath().'stop.txt')) unlink(getpath().'stop.txt');
	if (file_exists(getpath().'debug.log')) unlink(getpath().'debug.log');

	send_header('UTF-8');

	if (file_exists('time.txt')) 
	{ 
		decho('time='.(time()-filectime('time.txt')));
		if ( (time()-filectime('time.txt')) < 300 ) die();
		@unlink(getpath().'time.txt');
	};
	$err = file_put_contents(getpath().'time.txt','123');
	decho('err='.$err);
	
	$cfg 		= readIniFile(getpath().'config.txt');

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
	print_arr('$cfg',$cfg);
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
				print_arr('data=',$data);
				
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
					decho(getpath().'refresh_token.txt');
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
	//==============================================================================================
	//==============================================================================================
	//==============================================================================================
	// читаем атс
	$saved = $_REQUEST;
	$_REQUEST['action'] = 'history';
	ob_start();
	$p = include('rest.php');
	$p = ob_get_contents();
	ob_end_clean();
	$_REQUEST = $saved;

	$arr 	= explode("\x0D\x0A",$p);
	foreach($arr as $i=>$a) if (strlen($a)<80) unset($arr[$i]);
	
	$nums	= [];
	decho('======================================================================================');
	foreach($arr as $___a)
	{
		if (file_exists('stop.txt')) exit;
		$_a = explode(",",$___a);
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

	$contacts = [];
	if (file_exists('contacts.txt')) 
	{ 
		$contacts	= file_get_contents('contacts.txt');
		$contacts	= json_decode($contacts,true);
		$keys		= array_keys($contacts);

		$_contacts 	= getContacts(1);
//print_arr('$_contacts',$_contacts); die();
		$flag 		= false;	 
		decho('count($contacts) 0='.count($contacts));
		foreach($_contacts as $id=>$contact) if (!in_array($id,$keys)) { $flag =true; $keys[]=$id; $contacts[$id]=$contact; };
		decho('count($contacts) 1='.count($contacts).' $flag='.$flag);
		if ($flag) file_put_contents('contacts.txt',json_encode($contacts));
	} else {	
		$contacts 	= getContacts(100);
		$contacts	= json_encode($contacts);
decho('strlen($contacts)='.strlen($contacts));
		file_put_contents('contacts.txt',$contacts);
	}	
	global $contacts;
//print_arr('$contacts',$contacts); die();

	@unlink('debug.log');

decho('count($arr)='.count($arr));

	$exists = '';
	if (file_exists(getpath().'done.txt'))
	{	
		$exists = file_get_contents(getpath().'done.txt');
	};	
		
	$maxia = count($arr);
	$number = 0;
	$global_arr = $arr;
	foreach($global_arr as $ia=>$___a):
		$str = $___a;
		if (file_exists('stop.txt')) exit;
		
		$pos = strpos($exists,$___a);
		if ($pos>0) die('Nothing!');
		
		$_a = explode(",",$___a);
		if (!isset($_a[8]) || strlen($_a[8])<4 ) continue;
		if (!isset($_a[2])) continue;
		if (isset($_REQUEST['begnum']) && $ia<$_REQUEST['begnum']) continue;
		
		$a  = $_a[2];
		$a = str_replace('+7','8',$a);
		$a = str_replace('(','',$a);
		$a = str_replace(')','',$a);
		$a = str_replace('-','',$a);
		$a = str_replace(' ','',$a);
		$a = '8'.substr($a,1);
		
		$phone = $a;
		$href = $_a[8];

		if (isset($_REQUEST['phone']) && $phone != $_REQUEST['phone']) continue;
		
decho('$ia='.$ia.' $maxia='.$maxia.' $phone='.$phone.':'.strlen($_a[8]).':'.$_a[8]);
		if (file_exists('events.txt')) 
		{ 
			$events	= file_get_contents('events.txt');
			$events	= json_decode($events,true);
			$_events= getEvents(50);
			
		} else {	
			$events = getEvents();
			$events	= json_encode($events);
			file_put_contents('events.txt',$events);
		}	

		global $events;
		
		if (file_exists('leads.txt')) 
		{ 
			$leads	= file_get_contents('leads.txt');
		} else {	
			$leads = getLeads(10);
			$leads	= json_encode($leads);
			file_put_contents('leads.txt',$leads);
		}	
		$leads	= json_decode($leads,true);
		global $events,$contact;

		$contact	= getFilterInArray($phone);
		if (!is_array($contact))
		{
			decho('!contact='.$contact.' $phone='.$phone);
			addtolog($phone,'absent.txt');
//			die();
			addtolog($str,'done.txt');
			file_put_contents(getpath().'time.txt','123');
			continue;
		};
//		print_arr('contact',$contact);
		$contact_id	= $contact['id']*1;

		decho('contact_id='.$contact_id.' +++');
		addtolog('contact_id='.$contact_id.' +++');
		$name = $contact['name'];
		$account_id = $contact['account_id']*1;

		$page = 0;
		$flag = false;
		$baseurl = "https://kaskadn.amocrm.ru/api/v4/contacts/$contact_id/notes";
		while(1)
		{	
			if (file_exists('stop.txt')) exit;
			$page++;
			$url 	= $baseurl."?order[updated_at]=desc";
			if ($page>1) $url.="&page=$page";
			if ($page>50) break;
decho($url);
			$p = get_page_curl( $url);
			if (strpos($p,'{')!==false)
			{	
				$decode = decode($p);
//				print_arr('$decode',$decode);
			
				foreach($decode['_embedded']['notes'] as $np=>$note)
				{
					if (isset($note['params']['link'])) 
					{	
//						decho($note['params']['link']);
						if ($note['params']['link'] == $href)
						{
							decho('Найдена in contacts '.$contact_id.'!');
							$flag = true;
							break;
						}	
					}	
				}
				if ($flag || count($decode)<50) break;
			};
		};	
		if ($flag) 
		{	
			addtolog($str,'done.txt');
			file_put_contents(getpath().'time.txt','123');
			continue;
		};	
		$query = [
			'_embedded' =>[
				'entity' =>[
					'_links' =>[
						'href' => 'https://kaskadn.amocrm.ru/api/v4/contacts/'.$contact_id
					]
				]
			]
		];	
		$query = [
			'updated_at' =>[
			]
		];	
//29965713
//https://records.megapbx.ru/record/kaskad-nedvizhimost.megapbx.ru/2021-10-16/d142d435-270d-4e37-a1ef-f9dcb676c7a5/kashin_d_out_2021_10_16-14_28_52_79854348321_vtey.mp3

		$url = $baseamo."/api/v4/events";
		$url.= '?order[updated_at]=desc';
		$url.= '&filter[entity]=contact&filter[entity_id][]='.$contact_id;
//		$url.= '&query='.http_build_query($query);
//		$url.= '&query='.http_build_query($query);

		decho('$url='.$url);
		$p = get_page_curl( $url);
		$decode = decode($p);
//		print_arr('$decode',$decode);
		$id = $id_created = 0;
		foreach($decode['_embedded']['events'] as $event)
		{
			decho($event['type']);
			switch($event['type'])
			{
				case 'outgoing_call'	: 	if ($id_created==0) $id_created = $event['created_by']; break;
				case 'entity_linked'	:	if ($id==0) $id = $event['value_after'][0]['link']['entity']['id']; break;
			}	
		}
		decho('---$id='.$id.' id_created='.$id_created);
		
		if($id_created==0) 
		{
			addtolog($str,'done.txt');
			file_put_contents(getpath().'time.txt','123');
			continue;
		}	

		if (isset($_REQUEST['id'])) $id = $_REQUEST['id'];
		$baseurl = $url = "https://kaskadn.amocrm.ru/api/v4/leads/$id/notes";
		$flag = false;
		$page = 0;
		while(1)
		{	
			if (file_exists('stop.txt')) exit;
			$page++;
			$url 	= $baseurl."?order[updated_at]=desc";
			if ($page>1) $url.="&page=$page";
			if ($page>50) break;
			
decho($url);
			$p = get_page_curl( $url);
			$decode = decode($p);
//			print_arr('$decode',$decode);
			if (count($decode)==0) break;
			foreach($decode['_embedded']['notes'] as $np=>$note)
			{
//				if ($note['updated_at']<(time()-86400*2)) break 2;
				if (isset($note['params']['link'])) 
				{	
					decho($note['params']['link']);
					if ($note['params']['link'] == $href)
					{
						decho('Найдена in leads!');
						$flag = true;
						break;
					}	
				}	
			};
			if ( $flag or count($decode)<50 ) break;
		}
		if ($flag) 
		{	
			addtolog($str,'done.txt');
			file_put_contents(getpath().'time.txt','123');
			continue;
		}	
decho('$href='.$href); 
$ab = explode('/',$href); $uid = $ab[6];
print_arr('$ab=',$ab);
decho('$uid='.$uid); 
decho('Не найдена!'); 
print_r($_REQUEST);

decho('$id='.$id); 
		if ( isset($id) )  //&& ($id == '29965713' ) )
		{

			$url = "https://kaskadn.amocrm.ru/api/v4/leads/$id/notes";
decho($url);
			$arr 	= explode('-',$ab[7])[0];
			$arr 	= explode('_',$arr);
//print_arr('$arr=',$arr);
			$date	= $arr[count($arr)-3].'-'.$arr[count($arr)-2].'-'.$arr[count($arr)-1].'T';
decho('$date(0)='.$date);
			
			$arr1 	=explode('-',$ab[7])[1];
print_arr('$arr=',$arr1);
			$arr1 	=explode('_',$arr1);
print_arr('$arr(1)=',$arr);
			$date .=$arr1[0].$arr1[1].$arr1[2];
decho('$date='.$date);
			$date  = mktime($arr1[0],$arr1[1],$arr1[2],$arr[count($arr)-2],$arr[count($arr)-1],$arr[count($arr)-3]);
decho('$date='.$date);
			$data = 
			[
				[
					"created_by"	=> $id_created,
//                    "updated_by"	=> $account_id,
					"updated_at"	=> $date,
					"note_type"		=> "call_in",
					"params"		=> [ 
						"uniq"		=> $uid,
						"source"	=> "onlinePBX",
						"duration"	=> 60,
						"phone"		=> $phone,
						"link"		=> $href
					],
				]
			];
decho('++++++++++++++++++++++++++');
			
			if ($_a[1] == 'out') $data[0]["note_type"]	= "call_out";
			print_arr('$data',$data);
			$data = json_encode($data);
			decho('$data='.$data);

			$p = get_page_curl( $url, 1 , $data);
			$decode = decode($p);
			print_arr('$decode',$decode);
		};	
			
		addtolog($id.' '.$contact_id.' '.$href,'new.log');
		if ( isset($_REQUEST['phone']) ) die(); 
		$number++;
	endforeach;
	die('Koнец!');
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
	function getLeads($maxpage=10,$params = [])	// список сделок
	{
		
		global $baseamo;
		$page = 0;
		$leads =[];
		WHILE (1)
		{
		
			if (file_exists('stop.txt')) exit;
			$page++;
			if ($page>$maxpage) break;
			$url = $baseamo.'/api/v4/leads?order[updated_at]=desc';
			$url.= '&limit=100';
			if ($page>1) $url.= '&page='.$page;
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
	function getEvents($maxcount=100,$query='')
	{
		
		global $baseamo;
		
		$baseurl = $baseamo.'/api/v4/events';
		$baseurl.= '?order[updated_at]=desc';
		decho('$baseurl='.$baseurl);
		$events = [];
		for($i=1; $i<=$maxcount;$i++)
		{
			if (file_exists('stop.txt')) exit;
			$url = $baseurl;
			if ($i>1)$url = $baseurl.'&page='.$i;
			$url.= '&limit=100';
			if (strlen($query)>3) $url.='&query='.$i;
			
			decho('$url='.$url);
			$p = get_page_curl( $url);
			if ($p == '') break;
			$decode = decode($p);
			$_decode = $decode['_embedded']['events']; $decode = [];
			$flag = false;
			foreach($_decode as $n=>$event)
			{
				$flag = $event['updated_at']<(time()-86400*2);
				if ($flag) break;
				$decode[$event['id']] = $event;
				unset($_decode[$n]);
				if (strpos($event['created_at'],'-')>0) break;
				$decode[$event['id']]['created_at'] = Date("d-m-Y H:s",$decode[$event['id']]['created_at']);
			};
			$events = array_merge($decode,$events);
			if ($flag) break;
		}	
		return $events;
	};
	//----------------------------------------------------------------------------
	function getFilterInArray($phone='89778722509')
	{
		
		global $baseamo,$contacts;
		
		if (isset($_REQUEST['phone'])) $phone = $_REQUEST['phone'];
//print_arr('$contacts(in filter)',$contacts); die();
		foreach($contacts as $i=>$element)
		{
//print_arr('$element',$element); //die();
			if (file_exists('stop.txt')) exit;

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
					if (strpos($element['custom_fields_values'][$n]['values'][0]['value'],$phone)!==false) 
					{	
						decho('---'.$element['custom_fields_values'][$n]['values'][0]['value'].':'.$element['id'].':'.$phone);
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
	function getFilter($dir='contacts',$phone='89778722509')
	{
		
		global $baseamo,$contacts;
		
		if (isset($_REQUEST['phone'])) $phone = $_REQUEST['phone'];
		
	// поиск по телефону
		$page = 0;
		while(1){
		$page++;	
		$url = $baseamo."/api/v4/$dir?order[updated_at]=desc";
		$url.= '&limit=100';
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
			if (file_exists('stop.txt')) exit;
			$page++;
			if ($page>$maxpage) break;
			$url = $baseamo.'/api/v4/contacts?';
			if ($maxpage>0) $url.= '&page='.$page;
			$url.='&order[updated_at]=desc';
			$url.= '&limit=100';

			decho('$url='.$url);
			$p = get_page_curl( $url);
			$decode = decode($p);
//			print_arr('',decode($p));
			if (isset($decode['_embedded']['contacts']))
			{
				$_contacts 	= array_merge($decode['_embedded']['contacts'],$contacts);
				$contacts	= [];
				foreach($_contacts as $nc=>$_contact) $contacts[$_contact['id']] = $_contact;
				decho('count($contacts)='.count($contacts));
				if(count($decode['_embedded']['contacts'])<50) break;
			};	
		};
//		print_arr('$contacts',$contacts);
		return $contacts;
		
	};
	//----------------------------------------------------------------------------
	function decodeLeads($leads = [])
	{
		foreach($leads as $n=>$lead)
		{
			if (strpos($leads[$n]['created_at'],'-')) break;
			$leads[$n]['created_at'] 	= Date("d-m-Y H:s",$leads[$n]['created_at']);
			$leads[$n]['updated_at'] 	= Date("d-m-Y H:s",$leads[$n]['updated_at']);
			if (isset($leads[$n]['closed_at']))
			$leads[$n]['closed_at'] 	= Date("d-m-Y H:s",$leads[$n]['closed_at']);
		};
		return $leads;
	};
	//----------------------------------------------------------------------------
	function getContactsInLeads($contact,$leads = [])
	{
/*
9376443 	- новое обращение
20015766	- принят KЦ
9381054		- назначен
12136246	- принят в работу ОП
9376446		- потребности выявлены
9381342		- показ назначен
9376449		- показ совершён
9381051		- забронирован
11749182	- бронь закончена
9925776		- ПОДПИСАНИЕ НАЗНАЧЕНО 

*/		
		$exists = ['9376443','20015766','9381054','12136246','9376446','9381342','9376449','9381051','11749182','9925776'];
		foreach($leads as $n=>$lead)		
		{

			
//print_arr('lead',$lead); if ($n>50) die();
		};
		return $contact;
	};
	//----------------------------------------------------------------------------
	function getContactsInEvent($href)
	{
		global	$events,$contact;

		$_events = [];
		foreach($events as $event)
		{
			if (isset($event['_embedded']['entity']['_links']) && strpos($event['_embedded']['entity']['_links']['self']['href'],'/'.$contact['id'])!==false)
				$_events[] = $event;
		};	
		if (count($_events)>0)
		{
			foreach($_events as $event)
			{
				if (isset($event['value_after'][0]['note']))			
				decho($event['value_after'][0]['note']['id'].'---');
				print_arr('$event',$event);
				
			};
		};
		return $_events;
	};	
	