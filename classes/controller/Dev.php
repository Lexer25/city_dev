<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Dev extends Controller_Template {
   public $template = 'template';
   //Широки шаблон
   //для использьвания необходимо указать 
   //$this->template = View::factory($this->template_width);
   public $template_width = 'template';
   
  	
	public function before()
	{
			
			parent::before();
			$session = Session::instance();
		
	}
	
	
	
	
	
	
	public function action_load() //таблица загрузки контроллеров
	{
        $_SESSION['menu_active']='load';
	
		//$this->template = View::factory($this->template_width);
		$this->template->full_width = true;
				
		if(array_key_exists('browser',$_POST)) $_SESSION['brows']=Arr::get($_POST, 'browser');
		
		
		$dataList=Model::Factory('dev')->getDataList(); // 
		$date_stat=Model::Factory('dev')->date_stat();//получение даты и времени выбора статистики
		$dataListView = View::factory('load_table_new2', array(
			'list' => $dataList,
			'date_stat' => $date_stat,
			
					));
		
        $this->template->content = $dataListView;
        //echo View::factory('profiler/stats');
	}
	
	
	public function action_load_order()
	{
		
		$_SESSION['menu_active']='load_order';
		
		if(!empty($_POST['stop_load'])) Model::Factory('Stat')->stop_load($_POST['stop_load']);
		if(Arr::get($_POST, 'reload', 0)) Model::Factory('Stat')->repeat_load(Arr::get($_POST, 'reload'));
		if(Arr::get($_POST, 'del_queue', 0)) Model::Factory('Stat')->del_queue(Arr::get($_POST, 'reload'));
	
		$errArrForDevice=$this->getErrArrForDevice();//список ошибок при записи
	//echo Debug::vars('205', $errArrForDevice); exit;	
		$b=array();
		$c=array();
		
		$b=Model::Factory('Stat')->load_order(); // вывод очереди карт на загрузку
		//$c=Model::Factory('Stat')->load_order_overcount(); // вывод очереди карт на загрузку с превышенным количеством попыток
		//echo Debug::vars('221', $b); exit;
		$c=array();
		$content = View::factory('order_table', array(
			'list' => $b,
			'overcount'=>$c,
			'errArrForDevice'=>$errArrForDevice,
		));
        $this->template->content = $content;
		
		
	}
	
	//подготовка списка ошибок для каждого устройтва
	
	public function  getErrArrForDevice()
	{
		$sql='select distinct cdx.id_dev, cdx.load_result from cardidx cdx
			where cdx.load_result containing \'err\'';
			
		$sql2='select distinct
			cdx.id_dev,
			case
				when (cdx.load_result containing \'Device return error, code is 1\') then (SELECT \'is_1\' FROM RDB$DATABASE)
				when (cdx.load_result containing \'UDP recv() error\') then (SELECT \'udp_err\' FROM RDB$DATABASE)
				when (cdx.load_result containing \'not found\') then (SELECT \'not_found\' FROM RDB$DATABASE)
				else  cdx.load_result
			end as load_result
			from cardidx cdx
            where cdx.load_result containing \'err\'';
			
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'));
		$mess=array(
			'is_1'=>'234_mess',
			'udp_err'=>'235_mess_mess',
			'not_found'=>'235_mess_mess'
		);
		$result=array();	
			foreach($query as $key=>$value)
			{
				
				$result[Arr::get($value, 'ID_DEV')][]=Arr::get($value, 'LOAD_RESULT');

			}	
		return $result;
	}
	
	
	
	public function action_device_control()// обработка кнопок рыботы с контролерами
	{
		$_SESSION['menu_active']='device_control';
		
		//echo Debug::vars('144', $_POST); exit;
		$res='';
		if(array_key_exists('checkStateDoor',$_POST)){ // опрос состояния контроллеров
				
				//echo Debug::vars('177 опрос указанных контроллеров', $_POST);exit;
				if(is_null(Arr::get($_POST, 'id_dev'))) $this->redirect('errorpage?err='.__('no device id for check door state'));
				

					
					$sql='select distinct d2.id_dev from device d
							join device d2 on d2.id_ctrl=d.id_ctrl  and d2.id_reader is null
							where d.id_dev in ('.implode(",", Arr::get($_POST, 'id_dev')).')';
					
					$query = DB::query(Database::SELECT, $sql)
							->execute(Database::instance('fb'))
							->as_array();
				
				foreach ($query as $key=>$value){
					Model::factory('Device')->getStatForOneController(Arr::get($value, 'ID_DEV'));//надо указать id контроллера
					Log::instance()->add(Log::DEBUG, '183 сбор информации для контроллера id_dev='.Arr::get($value, 'ID_DEV'));
				}
				$res='183 сбор информации для контроллеров'.implode(",", Arr::get($_POST, 'id_dev'));
		}

		if(array_key_exists('all',$_POST)) 
			{
				$id_dev=Model::Factory('Device')->getdeviceList();
			} else {
			
				$id_dev=Arr::get($_POST, 'id_dev'); 
			}

		
		
		if (Arr::get($_POST, 'synctime'))
		{
				if(is_null(Arr::get($_POST, 'id_dev'))) $this->redirect('errorpage?err='.__('no device id for synctime'));
				Log::instance()->add(Log::NOTICE, 'Synctime for device :user', array(
					'user' => implode(",",$id_dev),
				));
				
				
				$res=$res.Model::Factory('Device')->synctime($id_dev);
				
		}
		
		if (Arr::get($_POST, 'checkStatus'))// запись состояния контроллера в БД: версия контроллера, контроль линии связи, кол-во карт в указаанной канале (только в одном!!!), кол-во карт двери по базе данных.
		{
				
				//echo Debug::vars('173', $_POST, $id_dev); //exit;
				$sql='select distinct d2.id_dev, d2.id_devtype, d2.netaddr from device d
					join device d2 on d2.id_ctrl=d.id_ctrl and d2.id_reader is null
					where d.id_dev in ('.implode(",", $id_dev).')';
					
				$query = DB::query(Database::SELECT, $sql)
			->execute(Database::instance('fb'))
			->as_array();	
				//	echo Debug::vars('270', $query);exit;
				foreach($query as $key)
				{
						
						switch(Arr::get($key, 'ID_DEVTYPE')){
							case 1: //контроллеры типа Артонит
							case 2: //контроллеры типа Артонит
							$ip_address=Arr::get($key, 'NETADDR');
								if($ip_address){//если указана строка подключения (а пока под этим подразумевается IP адрес), то иду выбирать данные для этого контроллера.
								$tt1=microtime(true);
										$deviceHard = new artonitHTTP($ip_address);
										$deviceHard->getDeviceInfo();// заполняю данные экземпляра из полученных ответов
										$deviceHard->disconnect();
										
									} else {
										//echo Debug::vars('214 Неправильно указан IP адрес устройства id_dev='.$key);exit;		
										Log::instance()->add(Log::DEBUG, '214 Неправильно указан IP адрес устройства id_dev='.$key);										
								}
								//echo Debug::vars('336', $deviceHard); exit;
								$deviceState=array(
										'id'=>$key,
										'ip'=>$deviceHard->ip_address,
										'mac'=>$deviceHard->mac_address,
										'Onl'=>$deviceHard->isOnline,
										'isWp'=>$deviceHard->isWp,
										'isTst'=>$deviceHard->isTest,
										'dMode_0'=>Arr::get($deviceHard->doorMode, 0),
										'dMode_1'=>Arr::get($deviceHard->doorMode, 1),
										'InputPortState'=>$deviceHard->portStateInput,
										'sver'=>$deviceHard->softVersion,
										'kc'=>$deviceHard->keyCount,
										'scud'=>$deviceHard->scud,
										'te'=>number_format((microtime(true)-$tt1), 3, '.',''), //time execute
										'timef'=>time(), //время получения статистики
										);
								
								$id_param=113;//название параметра - данные в виде json. Этот же параметр должен быть заявлен в БД в таблице ST_PARAM
								$id_agent=1;
								$order=445;//$ser;
								
								if(Model::factory('device')->stat_insert($order, Arr::get($key, 'ID_DEV'), $id_agent, $id_param, json_encode($deviceState)) == 0) {
									
										Log::instance()->add(Log::DEBUG, 'Данные для id_dev='.Arr::get($key, 'ID_DEV').' ip='.$ip_address.'записаны успешно');
									
								} else {
									
									Log::instance()->add(Log::DEBUG, Log::instance()->add(Log::DEBUG, 'ERR Данные для id_dev='.$key.' ip='.$ip_address.'записаны c ошибкой.'.Debug::vars($deviceState)));
								};
						
							
							break;
							default:
							
								throw new Exception('Function checkStatus for devtype id_dev='.Arr::get($key, 'ID_DEV').' connection '.$ip_address.'Not implemented, please install iconv');
							
							break;
							
						}
				
					
				}
			
			
			
				$res= 'insertStatusIdDev_arr for id_dev '. implode(",",$id_dev);
				//Model::Factory('Stat')->fixKeyOnDBCountForDoors($id_dev);//вставка количества карт для точки прохода по базе данных
				Model::Factory('Stat')->fixKeyOnCardidx($id_dev);//вставка количества карт для точки прохода по базе данных
		}
		
		if (Arr::get($_POST, 'checkStatusOnLine'))// проверка статуса он-лайн. Делается вычитка количества карт по базе данных и из контроллера и заносится в базу данных.
		{
				//echo Debug::vars('178', 'checkStatus'); exit;
				$res=Model::Factory('Device')->checkStatusOnLine($id_dev);
				$b=Model::Factory('Stat')->load_table($id_dev, $res);
				
		}
		
		if (Arr::get($_POST, 'load_card'))// загрузить карты в контроллер 
		{

				if(is_null(Arr::get($_POST, 'id_dev'))) $this->redirect('errorpage?err='.__('no device id for load'));
				$res=Model::Factory('Device')->load_card_arr($id_dev);
		}
		
		
		if (Arr::get($_POST, 'cardidx_refresh'))// загрузить карты в контроллер 
		{

				if(is_null(Arr::get($_POST, 'id_dev'))) $this->redirect('errorpage?err='.__('cardidx_refresh'));
				$res=Model::Factory('Device')->cardidx_refresh($id_dev);
		}
		
		
		if (Arr::get($_POST, 'clear_device'))
		{
				if(is_null(Arr::get($_POST, 'id_dev'))) $this->redirect('errorpage?err='.__('no device id for clear'));
				$res=Model::Factory('Device')->clear_device_arr($id_dev);
		}
		
		if (Arr::get($_POST, 'control_door'))//выполнение команд для точек прохода. Сама команда содержится в Arr::get($_POST, 'control_door') (открыть, закрыть и т.п.)
		{
				//echo Debug::vars('257', $_POST, Arr::get($_POST, 'control_door'));exit;
				if(is_null(Arr::get($_POST, 'id_dev'))) $this->redirect('errorpage?err='.__('no device id for clear'));// если нет перечня точек прохода, то выходим...
				$res=Model::Factory('Device')->unlock_door_arr($id_dev, Arr::get($_POST, 'control_door'));// реализация команды управления точкой прохода
				sleep(2);//пауза, чтобы контроллер успел поменять свое состояние
				foreach($id_dev as $key=>$value)// тут получаю список точек прохода (не контроллеров!!!)
				{

					 $id_dev_hard=Arr::get(Model::Factory('Device')->get_device_info($key), 'device_id');//16.07.2024 доработка для опроса нового состояния точек прохода
					 //Log::instance()->add(Log::DEBUG, '245 надо записать состояние точек прохода для контроллера '.$id_dev_hard);
					
					 Model::Factory('Device')->getStatForOneController($id_dev_hard);// чтение нового состояния контроллера (закрыт, открыт и т.п.)
					
				} 
		}
		
		if (Arr::get($_POST, 'settz'))
		{
				if(is_null(Arr::get($_POST, 'id_dev'))) $this->redirect('errorpage?err='.__('no device id for settz'));
				$res=Model::Factory('Device')->settz_arr($id_dev);
		}
		
		
		/*28.08.2024 Сверка карт на основе нового алгоритма
		* делается вычитка карт из контроллера в массив,
		*делается вычитка карт из БД в массив,
		* делается сверка массивов.
		* результат записывается в базу данных, в таблицу cardindev
		*/
		if (Arr::get($_POST, 'readkey'))//вычитать данные из контроллеров, сравнить с базой данных, найти "лишние" карты и выставить их в очередь на удалдение.
		{
			$errKeyFormat=0;	
			$errKeyFormat=Model::factory('dbskud')->checkRfidKeyFormat();
	//echo Debug::vars('421', $errKeyFormat);exit;
			if(count($errKeyFormat)>0) throw new Exception ('Ошибка в номерах идентификаторов RFID. Проверьте RFID '.implode(",", $errKeyFormat));
				$post=Validation::factory($_POST);
				$post->rule('id_dev', 'not_empty')
					->rule('readkey', 'digit');
					
					$t1=microtime(true);
			if($post->check())
			{
				$t1=microtime(true);
					
			
			//получаю ip адреса контроллеров, тип контроллеров для точек прохода, с которыми надо работать.
					$sql='select distinct d.id_dev, d2.id_devtype, d2.netaddr, d2.name, d.id_reader from device d
					join device d2 on d2.id_ctrl=d.id_ctrl and d2.id_reader is null
					where d.id_dev in ('.implode(",", $id_dev).')';
					
			$query = DB::query(Database::SELECT, $sql)
				->execute(Database::instance('fb'))
				->as_array();	
				
				//echo Debug::vars('420', $query);exit;
				//для каждой точки прохода (а не контроллера!) организую цикл вычитки номеров ключей 
				$result='Старт процесса сверки для контроллеров '. implode(",", $id_dev);
				$res=array();//массив, в который будут записыватьяс карты, вычитанные из контроллера.
				Log::instance()->add(Log::NOTICE, '475 '.$result);
								
				foreach($query as $key)
				{
					
						switch(Arr::get($key, 'ID_DEVTYPE')){
							case 1: //контроллеры типа Артонит
							case 2: //контроллеры типа Артонит
							$ip_address=Arr::get($key, 'NETADDR');
								
								//созданю экземпляр класса работы через ТС2
								$dev=new phpArtonitTS2();
			
							
								$dev->dev_name=Arr::get($key, 'NAME');

		
								$dev->connect();
								
							//echo Debug::vars('463', $dev);exit;
						
							if($dev->connection) {
								
							//		$t1=microtime(true);
								//получаю данные о версии контроллера и режиме работы.
								
									$deviceHard = new artonitHTTP($ip_address);
									$deviceHard->getSoftVersion();
									$deviceHard->getScudMode();
									$deviceHard->disconnect();
								//echo Debug::vars('503', $deviceHard->scud, Arr::get($key, 'ID_READER'), (!($deviceHard->scud == 'd1') AND (Arr::get($key, 'ID_READER') ==1))); exit;	
								if(!(($deviceHard->scud == 'd1') AND (Arr::get($key, 'ID_READER') ==1))){ // если режим 1 дверь, и канала 1, то сверку не проводить!!!
									
									//echo Debug::vars('507', $deviceHard->scud, Arr::get($key, 'ID_READER')); exit;	
									
									$strFrom='Key=';
									$strTo='"", Access';
									$startShift=6;
								//вычитываю карты из контроллера в цикле
								for($i=0; $i<4000; $i++){

									//$res[]=trim($dev->sendcommand('readkey door=0, cell='.$i));
									$command='readkey door='.Arr::get($key, 'ID_READER').', cell='.$i;
									//echo Debug::vars('447', $command);//exit;
									$strdata=trim($dev->sendcommand($command));
									// string(66) "t45 OK Answer="OK Cell=0, Key=""0022AE0D"", Access=Yes, TZ=0x0001""
									
									//Log::instance()->add(Log::NOTICE, '451 Комнада '.$command.'Ответ'.$strdata);
					
									
									$var1=explode(",", $strdata);
									
									$card_=explode("=", trim($var1[1]));
									$access_=explode("=", trim($var1[2]));
									$tz_=explode("=", trim($var1[3]));

									switch(Kohana::$config->load('artonitcity_config')->baseFormatRfid){
										case 0:
											$keyRfidLenght=8;
										break;
										case 1:
											$keyRfidLenght=10;
										break;
											
									}
									
									$card=str_pad(trim(Arr::get($card_,1), '"'), $keyRfidLenght, 0, STR_PAD_LEFT);// формирую номер карты как строка. Количество символов зависит от формата базы данных, добавлена нулями слева
								
									$access=trim(Arr::get($access_,1));
									$tz=trim(Arr::get($tz_, 1));
								
									if((preg_match('/^00000000/', $card) ==0) and ($access == 'Yes') and ($tz<>'TZ=0x0000') )$res[$card]=$card;
								
								}
								$dev->close();
								
								Log::instance()->add(Log::NOTICE, '482 из контроллера id_dev='.Arr::get($key, 'ID_DEV').' выбрал '.count($res).' карты за время '.(microtime(true) - $t1));
									// сохраняю массив в файл (для последующего анализа)
								$file_name="compare_readkey_from_door_id_door=".Arr::get($key, 'ID_DEV');
								$this->saveFile($file_name,$res);
							
								
								
								//echo Debug::vars('519', $res, count($res), $dev->connection);exit;
								// массив карт может быть пустой (и при этом контроллер на связи. Например, его заменили. В этом случае надо продолжить работу как обычно...
								//но если массив пустой, т.к. нет связи, то работу по сверке необходимо прекратить. 
								//т.о., продолжать сверку надо только при наличии связи с контроллером: пустой массив не является признаком отсутсвия связи.
													
								// теперь готовлю список карт из таблицы crdidx для выбранной точки прохода
										$sql='select cd.id_card from cardidx cd where cd.id_dev='.Arr::get($key, 'ID_DEV');
											
										$query_db = DB::query(Database::SELECT, $sql)
									->execute(Database::instance('fb'))
									->as_array();	
									$card_db=array();
									foreach($query_db as $key3=>$value3)
									{
																	
										$card_db[]=strtoupper(str_pad(trim(Arr::get($value3, 'ID_CARD')), $keyRfidLenght, 0, STR_PAD_LEFT));
									}
						//удаляю повторяющиеся значения				
									$card_db=array_unique($card_db);
									
						// сохраняю массив в файл (для последующего анализа)
									$file_name="compare_readkey_from_db_id_door=".Arr::get($key, 'ID_DEV');
									$this->saveFile($file_name,$card_db);

									
									Log::instance()->add(Log::NOTICE, '497 выбрал из базы данных '.count($card_db).' карты id_dev='.Arr::get($key, 'ID_DEV'));			

						// ищу карты для удаления из контроллера СКУД	
										$cardForDeleteArray=array_diff($res, $card_db);
										
						//Организую запись в очередь на удаление карт
										if(count($cardForDeleteArray)>0){
											
											
						// сохраняю массив в файл (для последующего анализа)
												$this->saveFile('compare_cardForDeleteArray_for_id_dev_'.Arr::get($key, 'ID_DEV'),$cardForDeleteArray);
												Log::instance()->add(Log::NOTICE, 'Карт для удаления для точки прохода id_dev='.Arr::get($key, 'ID_DEV').' найдено '.count($cardForDeleteArray).' штук.');
											
											foreach($cardForDeleteArray as $key4=>$value4)
											{
												
												Model::factory('Device')-> delKeyFromIdDev($value4, Arr::get($key, 'ID_DEV'), 508);// постановка карты в очередь на удаление
											}
											
										} else {
											Log::instance()->add(Log::NOTICE, 'Карт для удаления для точки прохода id_dev='.Arr::get($key, 'ID_DEV').' не найдено.');
										}
										
						//Ищу карты для записи в контроллер			
										$cardForWriteArray=array_diff($card_db ,$res);
										
						//Организую запись в очередь на запись карт
										if(count($cardForWriteArray)>0){
											
											foreach($cardForWriteArray as $key4=>$value4)
											{
																	// сохраняю массив в файл (для последующего анализа)
												$this->saveFile('compare_cardForWriteArray_for_id_dev_'.Arr::get($key, 'ID_DEV'),$cardForWriteArray);
												
												
												Model::factory('Device')->writeKeyToDevice($value4, Arr::get($key, 'ID_DEV'));// постановка карты в очередь на запись 2024
											}
											Log::instance()->add(Log::NOTICE, 'Карт для записи в точку прохода id_dev='.Arr::get($key, 'ID_DEV').' найдено '.count($cardForWriteArray).' штук.');
											
										} else {
											Log::instance()->add(Log::NOTICE, 'Карт для записи в точку прохода id_dev='.Arr::get($key, 'ID_DEV').' не найдено .');
											
										}
										
								$result=$result.'<br> работу по сверке карт с точкой прохода '.Arr::get($key, 'ID_DEV').' завершил. Поставлено в очередь на удаление '.count($cardForDeleteArray).' карт, на запись '.count($cardForWriteArray).' карт. Время выполнения '.(microtime(true) - $t1);			
								
								
								}else {
									
									$result='Для точки прохода id_dev='.Arr::get($key, 'ID_DEV').' "'. iconv('windows-1251','UTF-8',Arr::get($key, 'NAME')).'" сверка не производится, т.к. контроллера имеет настройку Одна дверь на два считывателя, и канал 1 повторяет содержимое канала 1.';
									Log::instance()->add(Log::NOTICE, $result);
									
								}
								
								} else { // если нет подключения
								
									$result=$result.'<br>Подключение точке прохода id_dev= '.Arr::get($key, 'ID_DEV').' контроллер '.iconv('windows-1251','UTF-8', Arr::get($key, 'NAME')).' произошло неудачно. Причина: '.$dev->errDesc;				
				
								
								
								}
								
							break;
							default:
							
								throw new Exception('Function checkStatus for devtype id_dev='.Arr::get($key, 'ID_DEV').' connection '.$ip_address.'Not implemented, please install iconv');
							
							break;
							
						}
			
								
				}
			
			
			} else {
				
				$res=$result.'<br>Валидация списка точек прохода для сверки прошла неудачно.';	
			}
				
			$res=$result.'<br> сверка карт для точек прохода '.implode(",", $id_dev).' заверешн.';	
		}
		
		
	
		
		
		if (Arr::get($_POST, 'checkkey'))//8.07.2020 вычитать данные из контроллера по списку из БД, найти карты, которых нет в контроллере, и выставить их на запись в контроллеры
		{
				echo Debug::vars('205', $_POST ); exit;
				//$res=Model::Factory('Check')->checkKey($id_dev, NULL);
				$res=Model::Factory('Device')->readkey_arr(Arr::get($post, 'id_dev'));
		}
		
		$resultMessages=array();
		//echo Debug::vars('629',  Session::instance()->get('res') );exit;
		$resultMessages=Session::instance()->get('res');
		$resultMessages[]=$res;
		Session::instance()->set('res',$resultMessages);
		
		$content = View::factory('result', array(
			'content' => $res,
		));
		
        $this->template->content = $content;
	}
	
}
