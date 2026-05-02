<script type="text/javascript">
      $(function () {
		var dateBegin = new Date();
		dateBegin.setHours(22, 0, 0, 0);
		dateBegin.setMonth(dateBegin.getMonth()+2);
	    //Инициализация datetimepicker1
        $("#datetimepicker1").datetimepicker(
		{language: 'ru', 
		showToday: true,
		sideBySide: true,
		defaultDate: dateBegin
		}
		);
      });

      $(document).ready(function() {
    	    $("#check_all3").click(function () {
    	         if (!$("#check_all3").is(":checked")){
    	            $(".checkbox").prop("checked",false);
					console.log('false');
				 } else {
    	            $(".checkbox").prop("checked",true);
					console.log('true')
				 }
    	    });
    	});
</script> 
<div class="panel panel-primary">
  <div class="panel-heading">
    <h3 class="panel-title"><?php echo __('device_panel_title').' '.date('Y-m-d H:i:s')?></h3>
  </div>
  

  
  <div class="panel-body">
  
     <div class="panel panel-danger">

  <div class="panel-body">
    <?php echo __('device_panel_title_desc', array('date_from'=>$date_stat['min'], 'date_to'=>$date_stat['max']));?>
  </div>
  </div>
  
<?php 

	echo Form::open('Dev/saveStateSkud');

		?>
	<button type="submit" class="btn btn-primary pull-right " name="refresh"  value="stateExport"><?php echo __('stateExport')?></button>
<?php echo Form::close();	





echo __('load_table', array('count_door'=>count($list)));

echo $dataListView;

echo Form::open('Dev/device_control');?>	

   <!-- <table class="table table-striped table-hover table-condensed">  -->
   <table id="tablesorter" class="table table-striped table-hover table-condensed tablesorter">
   <thead allign="center">

		
		<tr>
			<th>
				Выделить<br><label><input type="checkbox" name="id_dev" id="check_all3"></label>
			</th>
			<?php
			echo '<th>'.__('SERVER_NAME').'</th>'; //2
			echo '<th>'.__('DEVICE_NAME').'</th>'; //21
			echo '<th>'.__('DEVICE_IsActive').'</th>'; //22
			echo '<th>'.__('DEVICE_TYPE').'</th>'; //5
			echo '<th>'.__('IP').'</th>'; //5
			echo '<th>'.__('isOnLine').'</th>'; //5
			echo '<th>'.__('isWp').'</th>'; //50
			echo '<th>'.__('isTest').'</th>'; //52
			echo '<th>'.__('DOOR_NAME').'</th>'; //6
			echo '<th>'.__('DEVICE_VERSION').'</th>'; //8
			echo '<th>'.__('SCUD_MODE').'</th>'; //81
			echo '<th>'.__('BASE_COUNT').'</th>'; //9 количество карт по базе данных
			echo '<th>'.__('DEVICE_COUNT').'</th>'; //90 количество карт в контроллере
			echo '<th>'.__('delta_count').'</th>'; //91
			echo '<th>'.__('DOORSTATE_MODE').'</th>'; //11
			echo '<th>'.__('isBlocked').'</th>'; //12
			echo '<th>'.__('isAlarm').'</th>'; //13
			echo '<th>'.__('time').'</th>'; //14
			echo '<th>'.__('timestamp', array('title'=>'Дата получения информации')).'</th>'; //15
			echo '<th  class="filter-false sorter-false" >'.__('collectAlarm').'</th>'; //15
			?>
			
		</tr>
	
		</thead>
		<tbody>
		<? 
		//echo Debug::vars('26', $_SESSION, $list);
		//echo Debug::vars('26'

		$device=new Device ();
		$t1=microtime(true);
		//echo Debug::vars('134',$list);exit;
		//$list=array_slice($list, 0, 10, true);// операция для отладки. Беру первые 10 точек прохода
		foreach ($list as $key => $value)//для каждой точки прохода набираю данные
		{
			$t2=microtime(true);
			
			$door= Model::Factory('Door')->getDoor($key);//информация из базы данных по двери
			
			if($device->id != Arr::get($door, 'ID_DEV_DEV')){// если id не равен родительскому id_dev, то надо создать новый devcie
				
				$device=new Device (Arr::get($door, 'ID_DEV_DEV'));//получаю информацию по контроллеру. id_dev взят из свойства точки прохода.
				
				$deviceInfo=new DeviceInfo(Arr::get($door, 'ID_DEV_DEV'), trim(Model::Factory('Stat')->getDeviceStatData(Arr::get($door, 'ID_DEV_DEV'))));// взять информацию из таблицы st_data для указанного контроллера

			} else {
				// а если равен, то, значит, у нас уже есть экземпляр класса. Ничего не надо создавать, работаем с ним же.
			}
			
			
			//построение данных для канала 0			
			if(Arr::get($door, 'ID_READER') ==0){
				$deviceInfo->isBlocked = false;
				$deviceInfo->isAlarm = false;
				if(Arr::get($deviceInfo->inputPortState, 2) == 0) $deviceInfo->isBlocked = true;
				if(Arr::get($deviceInfo->inputPortState, 3) ==0 ) $deviceInfo->isAlarm = true;
				$deviceInfo->keyCount_reader=Arr::get($deviceInfo->keyCount, 0);
				$doorMode=$deviceInfo->doorMode_0;
				
			}
			
			//построение данных для канала 1
			if(Arr::get($door, 'ID_READER') ==1){
				$deviceInfo->isBlocked = false;
				$deviceInfo->isAlarm = false;
				if(Arr::get($deviceInfo->inputPortState, 6) == 0) $deviceInfo->isBlocked = true;
				if(Arr::get($deviceInfo->inputPortState, 7) == 0) $deviceInfo->isAlarm = true;
				$deviceInfo->keyCount_reader=Arr::get($deviceInfo->keyCount, 1);
				$doorMode=$deviceInfo->doorMode_1;
				
			}
			
			//echo Debug::vars('156', $device->id, $key, Arr::get($door, 'ID_READER'),  $deviceInfo);//exit;
			
			$deltacard=(($deviceInfo->keyCount_reader) - Arr::get($countDataBase, Arr::get($door, 'ID_DEV')));
			/** определяю цвет строки.
			*active серый - нет связи
			*success зеленый - все в порядке
			*info синий
			*warning - желтая
			*danger красная
						
			*/
			
			/** собираю сигнла ;collectAttention - знак общего внимания.
			*/
			$collectAttention=false;
			if($deviceInfo->isBlocked 
				OR $deviceInfo->isAlarm 
				OR (!$deviceInfo->onLine)
				OR ($deltacard<>0) 
				OR ($doorMode == 'Fire') 
				OR ($doorMode == 'Blocked') 
				OR ($doorMode == 'Alarm')) $collectAttention = TRUE;
				
			//if(!$deviceInfo->onLine) $collectAttention = false;	
			
			$tr_class='success';
			if(!$deviceInfo->onLine){
					$tr_class='active';
				} elseif ($deltacard>0){
					$tr_class='warning';
					}
					elseif ($deltacard<0){
						$tr_class='danger';
					}
						elseif ($collectAttention){
							$tr_class='danger';
						}
			$tr_class='success';		
			if($deltacard<0) $tr_class='danger';
			if($deltacard>0) $tr_class='warning';
			
			echo '<tr class="'.$tr_class.'">';
				
				echo '<td><label>'.Form::checkbox('id_dev['.$key.']', $key, FALSE, array('class'=>'checkbox')).'</label></td>'; //1
				echo '<td><p>'.Arr::get($door, 'SERVER_NAME').'</p></td>';//2
				echo '<td>'.$device->id //21
					.' '
					.HTML::anchor('device/deviceinfo/'.$device->id,iconv('windows-1251','UTF-8',$device->name));
					echo '</td>';//поле 3 название контроллера
					
				echo '<td>';
					if($device->is_active == 1) {
						echo '<span class="hidden">1</span>';
						echo  HTML::image("static/images/Card_on.png", array('height' => 20, 'alt' => 'Включено', 'title'=>'Устройство включено в БД СКУД.'));
						echo __('On');
					} else {
						echo '<span class="hidden">0</span>';
						echo  HTML::image("static/images/Card_off.png", array('height' => 20, 'alt' => 'Выключено', 'title'=>'Устройство выключено в БД СКУД.'));
						echo __('Off');
					}
				echo '</td>';//поле 4 включен в СКУД
				echo '<td>'.Arr::get($devtypeList, $device->type).'</td>';//5
				echo '<td>';//IP
					echo '<p>';
					if (is_null($device->connectionString)){
						echo ' <span class="label label-danger">'.__('no_ip').'</span><br>';
					} else {
						echo HTML::anchor('http://'.$device->connectionString ,$device->connectionString, array('target' => '_blank'));
					}
					echo '</p>';
				echo '</td>';//
				echo '<td>';
				if($deviceInfo->mac != '00-00-00-00-00-00') {
					if ($deviceInfo->onLine) {
							echo '<span class="hidden">0</span>';
							echo HTML::image("static/images/dot_green_n.png", array('height' => 20, 'alt' => 'Да'));
							
					} else {
							 echo '<span class="hidden">1</span>';
							echo HTML::image("static/images/dot_red_h.png", array('height' => 20, 'alt' => 'Нет'))
							. HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания'))
							;
							
					}
				} else {
							 echo '<span class="hidden">2</span>';
							echo HTML::image("static/images/dot_yellow_h.png", array('height' => 20, 'alt' => 'Плохая связь'))
							. HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Плохая связь', 'title'=>'Плохая связь.'))
							; 
				}
					
					
				echo '</td>';//50
				
		//заполняю сразу две колонки		
				if($deviceInfo->onLine){
					echo '<td>'.Form::checkbox('', 1, $deviceInfo->isWP == True, array('disabled'=>'disabled')).'<span class="hidden">1</span></td>';//51
					echo '<td>'.Form::checkbox('', 1, $deviceInfo->isTest == True, array('disabled'=>'disabled')).'<span class="hidden">1</span></td>';//52
					
					
				} else {
					echo '<td>-</td>';//51
					echo '<td>-</td>';//52
				}
				
		//название точки прохода
				echo '<td>'.Arr::get($door, 'ID_DEV').' '.HTML::anchor('door/doorInfo/'.Arr::get($door, 'ID_DEV'),Arr::get($door, 'NAME')).'</td>';//6
				echo '<td>'.$deviceInfo->softVersion.'</td>';//8
				echo '<td>';
					echo $deviceInfo->scud.' '.Arr::get($door, 'ID_READER');
					if(($deviceInfo->scud == 'd1') AND (Arr::get($door, 'ID_READER') == 1) AND($doorMode != 'Disabled')) {
							echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Для настройки Одна дверь вторую точку прохода необходимо выключить.')); 
							echo HTML::image("static/images/star_red.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Недопустимая комбинация настроек.')); 
					}
				echo '</td>';//81

				echo '<td>'.Arr::get($countDataBase, Arr::get($door, 'ID_DEV')).'</td>';//9
				
				if($deviceInfo->onLine){
					echo '<td>'.$deviceInfo->keyCount_reader.'</td>';//90
					
					if ($deltacard ==0){
						echo '<td class="success">'.$deltacard. '</td>';//91
						} else {
							 
							 echo '<td>'.$deltacard;
								 echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания'));
							echo '</td>';//91
							 
						}
					
				}else {
					echo '<td>-</td>';//91
					echo '<td>-</td>';//91
				}
				
				//echo '<td>'.$doorMode.'</td>';//11
				//колонка Режим работы
				echo '<td>';
						
							switch($doorMode){
								case 'Fire':
									 echo '<span class="hidden">1</span>';
									echo __('<acronym title=":doorMode">Откр всегда</acronym>', array(':doorMode'=>$doorMode))
										.' '
										. HTML::image("static/images/replace2.png", array('height' => 20, 'alt' => 'Откр всегда'))
										. HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Дверь открыта навсегда командой с компьютера.'))
										;
								break;
								
								case 'Blocked':
									 echo '<span class="hidden">1</span>';
									echo __('Закр всегда <acronym>:doorMode</acronym>', array(':doorMode'=>$doorMode))
										.' '
										. HTML::image("static/images/replace2.png", array('height' => 20, 'alt' => 'Закр всегда'))
										. HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Дверь закрыта навсегда командой с компьютера.'))
										;
								break;
								
								case 'Closed':
									echo __('<acronym title =":doorMode">Рабочий режим</acronym>', array(':doorMode'=>$doorMode));
									
								break;
								
								case 'Open':
									echo __('<acronym title =":doorMode">Рабочий режим</acronym>', array(':doorMode'=>$doorMode)).' '. HTML::image("static/images/green-check.png", array('height' => 20, 'alt' => 'Рабочий режим'));
								break;
								
								case 'Alarm':
									 echo '<span class="hidden">1</span>';
									echo __('<acronym title =":doorMode">Взлом</acronym>', array(':doorMode'=>$doorMode))
										.' '
										. HTML::image("static/images/docs-point-big2.png", array('height' => 20, 'alt' => 'Взлом'))
										. HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Взлом двери. Проверьте состояние геркона.'))
										;
								break;
								
								case 'Disabled':
									echo __('<acronym title =":doorMode">Отключен</acronym>', array(':doorMode'=>$doorMode));
								break;
								
								case 'no':
									echo __('<acronym title =":doorMode">-</acronym>', array(':doorMode'=>$doorMode));
								break;
								
								default: //не определено
									echo __('<acronym title =":doorMode">Не определен</acronym>', array(':doorMode'=>$doorMode)).' '. HTML::image("static/images/man-says.png", array('height' => 20, 'alt' => 'Не определен'));
								break;
								
				
								
							};
					
				echo '</td>';	
				if($deviceInfo->onLine){
					echo '<td>';
						echo Form::checkbox('', 1, $deviceInfo->isBlocked == True, array('disabled'=>'disabled'));
						if ($deviceInfo->isBlocked == True) {
							echo '<span class="hidden">1</span>';
							echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания','title'=>'Вход блокировки замкнут на "землю". Дверь закрыта, на карты и нажатие кнопок не открывается.'))
							.'1'
							;
						}
					echo '</td>';//12
					echo '<td>';
						echo Form::checkbox('', 1, $deviceInfo->isAlarm == True, array('disabled'=>'disabled'));
						if ($deviceInfo->isAlarm == True) {
							 echo '<span class="hidden">1</span>';
								echo  HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Вход Alarm замкнут на "землю". Дверь постоянно открыта.'))
								.''
								;
						}
					echo '</td>';//13
				} else {
					echo '<td>-</td>';//12
					echo '<td>-</td>';//13
					
				}
				echo '<td>'.number_format($deviceInfo->timeExecute, 3,'.','').'</td>';//14
				echo '<td>'.date('d.m.Y H:i:s',$deviceInfo->timeGetData);//15
				// progress-bar-success, progress-bar-info, progress-bar-warning и progress-bar-danger
				$tt3=time();
				$pbmax=100;
				
				$pbmin=10;
				$pbcolor='progress-bar-danger';
				
				$pbvalue=intval((($deviceInfo->timeGetData+60*60*24-$tt3)*100)/(60*60*24));
				
				if($pbvalue>=76) $pbcolor='progress-bar-success';
				if($pbvalue>=51 and $pbvalue<75) $pbcolor='progress-bar-info';
				if($pbvalue>=26 and $pbvalue<51) $pbcolor='progress-bar-warning';
				if($pbvalue<26) $pbcolor='progress-bar-danger';
				echo '<div class="progress">
					<div class="progress-bar '.$pbcolor.'" role="progressbar" style="width: '.$pbvalue.'%" ></div>
					
					</div>
					';
					
				echo '</td>';//15
				echo '<td>';
					if($collectAttention)echo HTML::image("static/images/attention.png", array('height' => 30, 'alt' => 'Требует внимания')).'<span class="hidden">1</span>';
				echo '</td>';
				
				
			echo '</tr>';
			

			//exit;
		}
		?>
		</tbody>
	</table>
<?php
echo Debug::vars('162',(microtime(true)-$t1));//exit;
?>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<nav class="navbar navbar-default navbar-fixed-bottom disable" role="navigation">
  <div class="container">
  	<button type="submit" class="btn btn-primary sm" name="synctime" value="1" title = "Синхронизация времени в контроллерах"><?php echo __('synctime_dev')?></button>
	<button type="submit" class="btn btn-primary sm" name="settz"  value="1" title = "Установить временные зоны для выбранных контроллеров"><?php echo __('settz')?></button>
	<button type="submit" class="btn btn-danger sm" name="clear_device"  value="1" title = "Удалить карты из выбранных точек прохода"><?php echo __('clear_device')?></button>
	<button type="submit" class="btn btn-danger sm" name="load_card"  value="1" title = "Загрузить карты в выбранные точки прохода"><?php echo __('load_card')?></button>
	<!--<button type="submit" class="btn btn-info" name="checkStatusOnLine"  value="1" title = "Чтение текущего состояния контроллера он-лайн." disabled="disabled"><?php echo __('checkStatusOnLine')?></button>-->
	<button type="submit" class="btn btn-success  sm" name="checkStatus"  value="1" title = "Чтение состояния и запись данных в базу данных."><?php echo __('checkStatus')?></button>
	<button type="submit" class="btn btn-warning sm" name="readkey"  value="1" title = "Вычитка карт из точки прохода и запись в файл"><?php echo __('Comparekey')?></button>
	<button type="submit" class="btn btn-warning sm" name="cardidx_refresh"  value="1" title = "cardidx_refresh"><?php echo __('cardidx_refresh')?></button>
	
	<?php 
		echo Form::button('control_door', 'Разблокировать', array('value'=>'unlockdoor','class'=>'btn btn-warning', 'type' => 'submit'));
		echo Form::button('control_door', 'Открыть 1 раз', array('value'=>'opendoor','class'=>'btn btn-warning', 'type' => 'submit'));
		echo Form::button('control_door', 'Открыть навсегда', array('value'=>'opendooralways','class'=>'btn btn-warning', 'type' => 'submit'));
		echo Form::button('control_door', 'Закрыть навсегда', array('value'=>'lockdoor','class'=>'btn btn-warning', 'type' => 'submit'));
		
		//echo Form::button('checkStateDoor', 'checkDoorState', array('value'=>'fixDoorState','class'=>'btn btn-success', 'type' => 'submit'));

	?>
	
	</div>
</nav>

<?php echo Form::close();?>		
  </div>
  
 
							  
							
</div>
