<?php defined('SYSPATH') OR die('No direct access allowed.');

/**31.12.2025 Модель для отображения состояние контроллеров.
*/
class Model_Dev extends Model
{
	
	/**9.01.2025
	*/
	public function date_stat()//получение даты и времени выбора статистики
	{
		$sql='select min (std.time_insert), max (std.time_insert) from st_data std';
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		//echo Debug::vars('12',$sql, $query ); exit;
		$res=array();
		foreach ($query as $key=>$value)
		{
			$res['min'] = Arr::get($value, 'MIN', 'not');
			$res['max'] = Arr::get($value, 'MAX', 'not');
		}
		return $res;
		
	}
	
	public function getDevData()
	{
		
		$sql='select d.id_dev, d.id_reader, d.name as doorName, d.netaddr, d."ACTIVE", d2.name as devName,  s.id_server, s.name as serverName, std.facts, std.time_insert from device d
			join device d2 on d2.id_ctrl=d.id_ctrl and d2.id_reader is null
			join server s on d2.id_server=s.id_server
			left join st_data std on d.id_dev=std.id_dev
			where d.id_reader is not null';
		$query = DB::query(Database::SELECT, iconv('UTF-8', 'CP1251',$sql))
					->execute(Database::instance('fb'))
					->as_array();
	
		return $query;
	}
	
public function getDataList()
{
    $getCardidxStat = $this->getCardidxStat();
    $result = array();
    
    // Получаем информацию о планах
    $floorplanData = $this->getFloorplanDevices();
    $floorplanDevices = Arr::get($floorplanData, 'devices', array());
    $floorplanStatus = Arr::get($floorplanData, 'status', 'error');
    $floorplanMessage = Arr::get($floorplanData, 'message', '');
    
    foreach ($this->getDevDataDetail() as $key => $value) {
        $deviceInfo = new DeviceInfo(Arr::get($value, 'ID_DEV'), Arr::get($value, 'facts2'));
        $deviceInfo->isBlocked = false;
        $deviceInfo->isAlarm = false;
        
        $deviceInfo->id = Arr::get($value, 'ID_DEV');
        
        if (Arr::get($value, 'ID_READER') == 0) {
            if (Arr::get($deviceInfo->inputPortState, 2) == 0) $deviceInfo->isBlocked = true;
            if (Arr::get($deviceInfo->inputPortState, 3) == 0) $deviceInfo->isAlarm = true;
            $deviceInfo->keyCount_reader = Arr::get($deviceInfo->keyCount, 0);
            $deviceInfo->doorMode = $deviceInfo->doorMode_0;
        }
        
        if (Arr::get($value, 'ID_READER') == 1) {
            if (Arr::get($deviceInfo->inputPortState, 6) == 0) $deviceInfo->isBlocked = true;
            if (Arr::get($deviceInfo->inputPortState, 7) == 0) $deviceInfo->isAlarm = true;
            $deviceInfo->keyCount_reader = Arr::get($deviceInfo->keyCount, 1);
            $deviceInfo->doorMode = $deviceInfo->doorMode_1;
        }
        
        $deviceInfo->id = Arr::get($value, 'ID_DEV');
        $deviceInfo->ip = Arr::get($value, 'NETADDR');
        $deviceInfo->id_dev = $deviceInfo->id;
        $deviceInfo->name = Arr::get($value, 'NAME');
        $deviceInfo->parentid = Arr::get($value, 'PARENTID');
        $deviceInfo->parentname = Arr::get($value, 'PARENTNAME');
        $deviceInfo->servername = Arr::get($value, 'SERVERNAME');
        $deviceInfo->active = Arr::get($value, 'ACTIVE');
        $deviceInfo->devtypename = Arr::get($value, 'DEVTYPENAME');
        $deviceInfo->id_reader = Arr::get($value, 'ID_READER');
        $deviceInfo->doorname = Arr::get($value, 'DOORNAME');
        $deviceInfo->countDataBase = Arr::get($getCardidxStat, $deviceInfo->id);
        
        // Флаг наличия на плане
        $deviceInfo->hasFloorplan = in_array($deviceInfo->id, $floorplanDevices);
        
        // Статус модуля floorplan
        $deviceInfo->floorplanStatus = $floorplanStatus;
        $deviceInfo->floorplanMessage = $floorplanMessage;
        
        $result[] = $deviceInfo;
    }
    
    return $result;
}

		/**
		 * Получение списка устройств, которые есть на плане
		 */
		private function getFloorplanDevices()
		{
			try {
				// 1. Проверяем, установлен ли модуль floorplan
				if (!$this->isFloorplanModuleAvailable()) {
					Log::instance()->add(Log::DEBUG, 'Модуль floorplan не загружен');
					return array(
						'status' => 'disabled',
						'message' => 'Модуль "Планы этажей" отключен',
						'devices' => array()
					);
				}
				
				// 2. Проверяем существование таблицы FLOORPLAN_POINT
				$sql = "SELECT 1 FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = 'FLOORPLAN_POINT'";
				$tableExists = DB::query(Database::SELECT, $sql)
					->execute(Database::instance('fb'))
					->count();
				
				if ($tableExists == 0) {
					return array(
						'status' => 'no_table',
						'message' => 'Таблица планов этажей не найдена',
						'devices' => array()
					);
				}
				
				// 3. Получаем список устройств
				$sql = "SELECT id_dev FROM FLOORPLAN_POINT";
				$query = DB::query(Database::SELECT, $sql)
					->execute(Database::instance('fb'))
					->as_array();
				
				$result = array();
				foreach ($query as $row) {
					$result[] = Arr::get($row, 'ID_DEV');
				}
				
				return array(
					'status' => 'ok',
					'message' => 'OK',
					'devices' => $result
				);
				
			} catch (Exception $e) {
				Log::instance()->add(Log::ERROR, 'Ошибка получения списка устройств на плане: ' . $e->getMessage());
				return array(
					'status' => 'error',
					'message' => 'Ошибка: ' . $e->getMessage(),
					'devices' => array()
				);
			}
		}

		/**
		 * Проверка доступности модуля floorplan
		 * 
		 * @return bool
		 */
		private function isFloorplanModuleAvailable()
		{
			// Проверяем, загружен ли модуль в Kohana
			$modules = Kohana::modules();
			if (!isset($modules['floorplan'])) {
				return false;
			}
			
			// Проверяем, существует ли папка модуля
			if (!is_dir(MODPATH . 'floorplan')) {
				return false;
			}
			
			// Проверяем, существует ли контроллер
			if (!class_exists('Controller_Floorplan')) {
				return false;
			}
			
			return true;
		}	
	
	public function getCardidxStat()
	{
		$result=array();
		$sql='select cdx.id_dev, count(*) from cardidx cdx
		group by cdx.id_dev';
		$query = DB::query(Database::SELECT, iconv('UTF-8', 'CP1251',$sql))
					->execute(Database::instance('fb'))
					->as_array();
					
					//echo Debug::vars('85', array_column($query, null, 'ID_DEV'));exit;
		foreach($query as $key=>$value)
		{
			
			$result[Arr::get($value, 'ID_DEV')]=Arr::get($value, 'COUNT');
		}
		//echo Debug::vars('94', $result);exit;
		return $result;
		
	}
	/**3.01.2026 сборка данных для вывода на экран построчно
	*/
	
	public function getDevDataDetail()
	{
	//массив точек прохода
	$result=array();
	
			$sql='SELECT 
			d.id_dev,
			d.id_devtype,
			dt.name AS devTypename,
			d.id_reader,
			d.name,
			d2.netaddr,
			d."ACTIVE" * d2."ACTIVE" AS "ACTIVE",  -- òîëüêî åñëè INTEGER
			d2.id_dev AS parentId,
			d2.name AS parentName,
			s.id_server,
			s.name AS serverName,
			std.facts AS dbCount
		FROM device d
		JOIN device d2 ON d2.id_ctrl = d.id_ctrl AND d2.id_reader IS NULL
		JOIN devtype dt ON d2.id_devtype = dt.id_devtype
		LEFT JOIN server s ON d2.id_server = s.id_server
		LEFT JOIN st_data std ON d.id_dev = std.id_dev AND std.id_param = 8
		WHERE d.id_reader IS NOT NULL
		ORDER BY d.id_dev';
	
	$query = DB::query(Database::SELECT, $sql)
					->execute(Database::instance('fb'))
					->as_array();
					
	$result=array_column($query, null, 'ID_DEV');

	//массив данных для контроллеров 
	$sql='select std.id_dev, std.facts, std.time_insert from st_data std
	join device d on d.id_dev=std.id_dev
	and d.id_reader is null
	and std.id_param in (113)';
	$queryDev = DB::query(Database::SELECT, iconv('UTF-8', 'CP1251',$sql))
				->execute(Database::instance('fb'))
				->as_array();
						
	$temp=array_column($queryDev, null, 'ID_DEV');
	//echo Debug::vars('50', $temp);exit;
	
	foreach($result as $key=>$value)
	{
		
		
		$result[$key]['facts2']=Arr::get(Arr::get($temp, Arr::get($value, 'PARENTID')), 'FACTS');
		
	}
	
	
	//сведение данных в один массив

	return $result;
	
	}
	
	
	// В модели Model_Dev или Model_Floorplan
	public function checkDeviceOnFloorplan($id_dev)
	{
		// Проверяем, существует ли таблица floorplan
		try {
			$sql = "SELECT 1 FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = 'FLOORPLAN_DEVICES'";
			$tableExists = DB::query(Database::SELECT, $sql)
				->execute(Database::instance('fb'))
				->count();
			
			if ($tableExists == 0) {
				return false; // Таблица не существует
			}
			
			// Проверяем, есть ли устройство на плане
			$sql = "SELECT COUNT(*) as cnt FROM floorplan_devices WHERE id_dev = :id_dev";
			$result = DB::query(Database::SELECT, $sql)
				->param(':id_dev', $id_dev)
				->execute(Database::instance('fb'))
				->as_array();
			
			return Arr::get($result[0], 'CNT', 0) > 0;
			
		} catch (Exception $e) {
			Log::instance()->add(Log::ERROR, 'Ошибка проверки floorplan_devices: ' . $e->getMessage());
			return false;
		}
	}
	
	
	public function load_order()// вывод очереди карт на загрузку
	{
		$sql='select d.id_dev,
       min(d.name)  as name,
       min(d2.name) as device,
       min(s.name)  as server,
       sum(case when cd.operation = 1 then 1 else 0 end) as COUNT_WRITE,
       sum(case when cd.operation = 2 then 1 else 0 end) as COUNT_DELETE
		from cardindev cd
		  join device d  on d.id_dev = cd.id_dev
		  join device d2 on d2.id_ctrl = d.id_ctrl and d2.id_reader is null
		  join server s  on d2.id_server = s.id_server
		where d."ACTIVE" > 0 and d2."ACTIVE" > 0
		  and d2.id_devtype in (1,2,6)
		group by d.id_dev';
 
 
		$query = DB::query(Database::SELECT, $sql)
		->execute(Database::instance('fb'))
		->as_array();
		
		$res=array();
		foreach ($query as $key=>$value)
		{
			$res[$value['ID_DEV']]['ID_DEV']=Arr::get($value, 'ID_DEV');
			$res[$value['ID_DEV']]['NAME']=iconv('windows-1251','UTF-8',Arr::get($value, 'NAME'));
			$res[$value['ID_DEV']]['DEVICE']=iconv('windows-1251','UTF-8',Arr::get($value, 'DEVICE'));
			$res[$value['ID_DEV']]['SERVER']=iconv('windows-1251','UTF-8',Arr::get($value, 'SERVER'));
			$res[$value['ID_DEV']]['COUNT_WRITE']=Arr::get($value, 'COUNT_WRITE');
			$res[$value['ID_DEV']]['COUNT_DELETE']=Arr::get($value, 'COUNT_DELETE');
		}
		
		return $res;
	}
	
	
	/**
	 * Словарь: фрагмент технического текста cardidx.load_result => понятное пользователю описание.
	 * Подбор идёт по подстроке без учёта регистра, берётся первое совпадение.
	 * При появлении новых формулировок в БД - добавлять сюда.
	 */
	protected static $loadResultMessages = array(
		// формулировки, которые встречаются в cardidx.load_result сейчас
		'authorisation error' => 'Ошибка авторизации на транспортном сервере',
		'device offline'      => 'Контроллер не в сети',
		'not found'           => 'Устройство не найдено на транспортном сервере',
		'socket error'        => 'Нет связи с транспортным сервером',
		// исторические формулировки (встречались в других сборках/базах)
		'code is 1'           => 'Не хватает памяти контроллера',
		'recv()'              => 'Нет связи с контроллером',
	);
	
	/**
	 * Описание ошибки по умолчанию, если формулировка не распознана.
	 */
	const LOAD_RESULT_UNKNOWN = 'Ошибка загрузки карт';
	
	/**
	 * Перевод технического текста результата загрузки карты (cardidx.load_result)
	 * в понятное пользователю описание.
	 *
	 * @param string $loadResult значение cardidx.load_result
	 * @return string описание для пользователя
	 */
	public function humanizeLoadResult($loadResult)
	{
		$loadResult = (string) $loadResult;
		
		foreach (self::$loadResultMessages as $needle => $message)
		{
			if (stripos($loadResult, $needle) !== false) return $message;
		}
		
		return self::LOAD_RESULT_UNKNOWN;
	}
	
	/**
	 * Группировка технических текстов результата загрузки по понятному описанию.
	 * Разные технические формулировки с одним смыслом (например, от разных транспортных
	 * серверов TRANS2, TRANS3, ... ) сворачиваются в одну строку со счётчиком повторов.
	 *
	 * @param array $loadResults список значений cardidx.load_result
	 * @return array array(описание => array('count' => число повторов, 'raw' => array(технические тексты)))
	 */
	public function humanizeLoadResultList(array $loadResults)
	{
		$result = array();
		
		foreach ($loadResults as $loadResult)
		{
			$message = $this->humanizeLoadResult($loadResult);
			
			if ( ! isset($result[$message]))
			{
				$result[$message] = array('count' => 0, 'raw' => array());
			}
			
			$result[$message]['count']++;
			$result[$message]['raw'][] = (string) $loadResult;
		}
		
		return $result;
	}
	
}
	

