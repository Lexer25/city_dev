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
		$getCardidxStat=$this->getCardidxStat();
		foreach ($this->getDevDataDetail() as $key => $value)//для каждой точки прохода набираю данные
		{

			$deviceInfo=new DeviceInfo(Arr::get($value, 'ID_DEV'), Arr::get($value, 'facts2'));//набор данных из статистики
			//echo Debug::vars('49', $value);
			//echo Debug::vars('50', $deviceInfo);exit;
			$deviceInfo->isBlocked = false;
			$deviceInfo->isAlarm = false;
				
				
			$deviceInfo->id = Arr::get($value, 'ID_DEV');
			if(Arr::get($value, 'ID_READER') ==0){
				
				if(Arr::get($deviceInfo->inputPortState, 2) == 0) $deviceInfo->isBlocked = true;
				if(Arr::get($deviceInfo->inputPortState, 3) ==0 ) $deviceInfo->isAlarm = true;
				$deviceInfo->keyCount_reader=Arr::get($deviceInfo->keyCount, 0);
				$deviceInfo->doorMode=$deviceInfo->doorMode_0;
				
			}
			
			//построение данных для канала 1
			if(Arr::get($value, 'ID_READER') ==1){
				
				if(Arr::get($deviceInfo->inputPortState, 6) == 0) $deviceInfo->isBlocked = true;
				if(Arr::get($deviceInfo->inputPortState, 7) == 0) $deviceInfo->isAlarm = true;
				$deviceInfo->keyCount_reader=Arr::get($deviceInfo->keyCount, 1);
				$deviceInfo->doorMode=$deviceInfo->doorMode_1;
				
			}
		//	echo Debug::vars('51', $value);exit;
		 $deviceInfo->id = Arr::get($value, 'ID_DEV');
		 $deviceInfo->ip = Arr::get($value, 'NETADDR');
		 $deviceInfo->id_dev = $deviceInfo->id;
		 $deviceInfo->name = Arr::get($value, 'NAME');
         $deviceInfo->parentid = Arr::get($value, 'PARENTID');
         $deviceInfo->parentname = Arr::get($value, 'PARENTNAME');
         $deviceInfo->parentid = Arr::get($value, 'PARENTID');
        
		$deviceInfo->servername = Arr::get($value, 'SERVERNAME');
		$deviceInfo->active = Arr::get($value, 'ACTIVE');
		$deviceInfo->devtypename = Arr::get($value, 'DEVTYPENAME');
		$deviceInfo->id_reader = Arr::get($value, 'ID_READER');
		$deviceInfo->doorname = Arr::get($value, 'DOORNAME');
		
		$deviceInfo->countDataBase = Arr::get($getCardidxStat, $deviceInfo->id);
        
		//echo Debug::vars('68', $deviceInfo);exit;	
			$result[]=$deviceInfo;
		}
		
		
		return $result;
	}
	
	
	public function getCardidxStat()
	{
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
	$sql='select d.id_dev, d.id_devtype, dt.name as devTypename, d.id_reader, d.name, d2.netaddr, d."ACTIVE", d2.id_dev as parentId, d2.name as parentName,  s.id_server, s.name as serverName, std.facts as dbCount from device d
        join device d2 on d2.id_ctrl=d.id_ctrl and d2.id_reader is null
        join devtype dt on d2.id_devtype=dt.id_devtype
        left join server s on d2.id_server=s.id_server
        left join st_data std on d.id_dev=std.id_dev and std.id_param in (8)
        where d.id_reader is not null
        order by d.id_dev';
	
	
	
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
	
}
	

