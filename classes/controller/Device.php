<?php defined('SYSPATH') or die('No direct script access.');
class Controller_Device extends Controller_Template { 

	public function before()
	{
			
			parent::before();
			$session = Session::instance();
			//echo Debug::vars('9', $_POST, $_GET);
			
	}
	
	
	public function action_index()
	{
		$_SESSION['menu_active']='device';
		
		$content = View::factory('device/search');
        $this->template->content = $content;
	}
	 
	/** 21.08.2024 Обновление информации по контроллеру
	*/
	public function action_update()
	{
		//echo Debug::vars('26', $_GET, $_POST); exit;
		$todo = $this->request->post('todo');
		switch ($todo){};
		
		
		$post=Validation::factory($this->request->post());
		$post->rule('todo', 'equals', array(':value','devtype_edit'))
				->rule('new_id_devtype', 'digit')
				->rule('new_id_devtype', 'digit')
				->rule('id_dev', 'digit')
		//		->rule('connectionString', 'digit')
				
				;
		if($post->check())
		{
						
						$dev= new Device(Arr::get($post, 'id_dev'));
						$dev->connectionString=Arr::get($post, 'connectionString');
						$dev->connectionString=Arr::get($post, 'connectionString');
						$dev->type=Arr::get($post, 'type');
						$dev->name=iconv('UTF-8','windows-1251',Arr::get($post, 'name'));
						if($dev->update() == 0){
						
							$arrAlert[]=array('actionResult'=>0, 'actionDesc'=>'Обновление данных прошло успешно');
							
						}  else {
						
						$arrAlert[]=array('actionResult'=>2, 'actionDesc'=>'Ошибка сохранения данных.');
						
						
					}
					
		} else {
					
			$arrAlert[]=array('actionResult'=>2, 'actionDesc'=>'Ошибка! Проверьте введенные данные.');
		}
		
		//http://localhost/city/index.php/device/deviceInfo/107
		$this->redirect('device/deviceInfo/'.Arr::get($post, 'id_dev'));
	}
	 
	 
	 
	 public function action_card_for_not_active()// 16.03.2020 вывод списка неактивных устройств
	{
		$list_not_active_device=Model::Factory('Device')->getListNotActiveDevices();
		$content=View::Factory('device/ListNotActiveDevices', array(
			'ListNotActiveDevices' => $list_not_active_device,
			));
		 $this->template->content = $content;	
	}
	
	public function action_find()
	 {
	 
	 $search=Arr::get($_GET, 'deviceInfo');
	 $result=Model::Factory('Device')->findIdDev($search);
		 if(count($result)>0)
		 {
			//$this->redirect('device/deviceInfo/'.$result);
			$content=View::Factory('device/select', array(
			'list' => $result,
			
			));
		 $this->template->content = $content;
		 
		 } else {
		 $content=View::Factory('device/search');
		 $this->template->content = $content;
		 }
	 }
	
	
	
	public function action_deviceInfo($id_dev=false)// 24.01.2022
	{
			$id_dev = $this->request->param('id');
			$_SESSION['menu_active']='device';
			//echo Debug::vars('44', $_POST, $_GET, $id_dev); exit;
			if ($id_dev == NULL) $this->redirect('device/find');
			$device_data=Model::Factory('Device')->get_device_info($id_dev);//информация о контроллере
			$devtypeList=Model::Factory('Device')->getDevtypeList();//получить типы устройств
			
			
		$content=View::Factory('device/view', array(
			'device_data'	=> $device_data,
			'devtypeList'	=> $devtypeList,
			'id_dev'	=> $id_dev,
			
			
			));
			
		$this->template->content = $content;
	}
	
	

}
