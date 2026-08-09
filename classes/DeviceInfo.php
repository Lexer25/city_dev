<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
*	15.08.2024 
*	7.01.2025
*Класс DeviceInfo - Класс сводной информации по точки прохода.
*предполагается последовательный вывод данных этого класса при выборе точки прохода.
данные берутся из строки {"ip":"10.25.16.11","mac":"00-20-A6-FE-2B-A3","Onl":true,"isWp":true,"isTst":false,"dMode_0":"Closed","dMode_1":"Disabled","InputPortState":["1","0","1","1","1","1","1","1"]}

*/
class DeviceInfo
{
	public $ip=null;
	public $mac=null;
	public $onLine=FALSE;
	public $isWP=null;
	public $isTest=false;
	public $doorMode_0=null;
	public $doorMode_1=null;
	public $inputPortState=array();
	public $softVersion=null;
	public $keyCount=array();
	public $timeGetData=0; // метка времени  сбора информации time fix
	public $timeExecute=0; // время, потраченное на сбор информации о контроллере
	public $scud='d0'; // режим работы контроллера (одна или две двери)
	
	
	public $id=null;
	public $name=null;
	public $parentid=null;
	public $parentname=null;
	
	
	 public function __construct($id_dev, $json_string)
    {
		
	//	echo Debug::vars('28', $id_dev, $json_string);exit;
		if(strlen($json_string)>0){
			//echo Debug::vars('25', $json_string);exit;
			//$deviceInfo=json_decode($json_string);
			$deviceInfo=json_decode($json_string, true);
			
			$this->id_dev=$id_dev;
			$this->ip=Arr::get($deviceInfo, 'ip');
			$this->mac=Arr::get($deviceInfo, 'mac');
			$this->onLine=Arr::get($deviceInfo, 'Onl');
			$this->isWP=Arr::get($deviceInfo, 'isWp');
			$this->isTest=Arr::get($deviceInfo, 'isTst');
			$this->doorMode_0=Arr::get($deviceInfo, 'dMode_0');
			$this->doorMode_1=Arr::get($deviceInfo, 'dMode_1');
			$this->inputPortState=Arr::get($deviceInfo, 'InputPortState');
			$this->softVersion=Arr::get($deviceInfo, 'sver');
			$this->keyCount=Arr::get($deviceInfo, 'kc');
			//$this->timeGetData=Arr::get($deviceInfo, 'timef');
			$this->timeGetData=$this->standardizeNumber(Arr::get($deviceInfo, 'timef'));
			//$this->timeExecute=Arr::get($deviceInfo, 'te');
			$this->timeExecute=$this->standardizeNumber(Arr::get($deviceInfo, 'te'));
			$this->scud=Arr::get($deviceInfo, 'scud');
		} else {
			
		}
	}
	
	public function standardizeNumber($input) {
    // Может быть строкой или числом
    if (is_numeric($input)) {
        return (string)$input; // уже число
    }
    
    if (is_string($input)) {
        // Заменяем запятую на точку и убираем лишнее
        $normalized = str_replace(',', '.', $input);
        $normalized = preg_replace('/[^0-9\.\-]/', '', $normalized);
        return $normalized;
    }
    
    return '0';
}
}
