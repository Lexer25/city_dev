<!-- Управление очередью загрузки карт в контроллеры -->
<script type="text/javascript">
   	$(function() {		
		$("#table15").tablesorter({sortList:[[0,0],[2,1]], widgets: ['zebra']});
		$("#options").tablesorter({sortList: [[0,0]], headers: { 0:{sorter: false}}});
	});	

  	$(function() {		
  		// В шапке две строки: сортировка идёт только по первой (вторая - номера колонок).
  		// Колонка 0 (номер по порядку) и колонка 1 (чекбоксы) не сортируются.
  		// theme: 'blue' обязателен - именно он вешает на таблицу класс tablesorter-blue,
  		// по которому theme.blue.min.css рисует стрелки-указатели порядка сортировки
  		// (нейтральная, вверх - по возрастанию, вниз - по убыванию).
  		$("#table1").tablesorter({
  			theme: 'blue',
  			selectorHeaders: 'thead tr:first-child th',
  			sortList:[[0,0]],
  			headers: { 0:{sorter: false}, 1:{sorter: false}}
  		});
  	});

  	$(function() {		
  		$("#table2").tablesorter({sortList:[[0,0]], headers: { 0:{sorter: false}}});
  	});	
  	
	$(function() {		
		$("#table12").tablesorter({sortList:[[0,0],[2,1]], widgets: ['zebra']});
		$("#options").tablesorter({sortList: [[0,0]], headers: { 0:{sorter: false}}});
	});	
  	
	//setInterval(function() { $("#refresh").load(location.href+" #refresh>*","");}, 5000);
	
	     $(document).ready(function() {
    	    $("#check_all1").click(function () {
    	         if (!$("#check_all1").is(":checked"))
    	            $(".checkbox1").prop("checked",false);
    	        else
    	            $(".checkbox1").prop("checked",true);
    	    });
    	});

      $(document).ready(function() {
  	    $("#check_all2").click(function () {
  	         if (!$("#check_all2").is(":checked"))
  	            $(".checkbox2").prop("checked",false);
  	        else
  	            $(".checkbox2").prop("checked",true);
  	    });
  	});
	
</script> 
<div class="panel panel-primary">
	<div class="panel-heading">
		<h3 class="panel-title"><?echo __('Load_panel_title')?></h3>
	</div>
	<?echo Form::open('Dashboard/load_order');?>
	<div class="panel-body">
  
		<div id="refresh">
						<!-- <table class="table table-striped table-hover table-condensed">  -->
						<table id="table1" class=" table table-striped table-hover table-condensed tablesorter">
						<thead>
							<tr>
								<th><?echo __('Номер по порядку');?></th>
								<th>
									<label><input type="checkbox" name="stop_load" id="check_all1" title="Выделить всё" aria-label="Выделить всё"></label>
								</th>
								<th><?echo __('ID_DEV');?></th>
								<th><?echo __('SERVER');?></th>
								<th><?echo __('DEVICE');?></th>
								<th><?echo __('NAME');?></th>
								<th><?echo __('CARD_FOR_LOAD');?></th>
								<th><?echo __('CARD_FOR_DELETE');?></th>
								<th><?echo __('Ошибки загрузки');?></th>
							</tr>
							<? // вторая строка шапки - номера колонок, начиная с 1 (9 колонок в этой таблице) ?>
							<tr class="info" style="font-size: 10px">
								<?php for ($col = 1; $col <= 9; $col++): ?>
									<th class="text-center"><?php echo $col; ?></th>
								<?php endfor; ?>
							</tr>
						</thead>
						<tbody>
							<? 
							$count_write=0;
							$count_delete=0;
							$row_number=0;
							// модель нужна для перевода технических текстов ошибок загрузки в понятные описания
							$devModel = Model::Factory('Dev');
							foreach ($list as $key => $value)
							{
								$row_number++;
								echo '<tr class="'.Arr::get($value, 'TR_COLOR', 'active').'">';
								echo '<td class="text-center">'.$row_number.'</td>';
								echo '<td><label>'.Form::checkbox('stop_load['.(int)Arr::get($value, 'ID_DEV').']', 1, FALSE, array('class'=>'checkbox1')).'</label></td>';
									echo '<td>'.(int)Arr::get($value, 'ID_DEV', 0).'</td>';
									// P0: данные приходят из БД, поэтому экранируем вывод
									echo '<td>'.htmlspecialchars(Arr::get($value, 'SERVER', 'No data'), ENT_QUOTES, 'UTF-8').'</td>';
									echo '<td>'.htmlspecialchars(Arr::get($value, 'DEVICE', 'No data'), ENT_QUOTES, 'UTF-8').'</td>';
									echo '<td>';
									
									if(count(Arr::get($errArrForDevice, Arr::get($value, 'ID_DEV'))) > 0)
									{
										$title= iconv('CP1251', 'UTF-8', implode("\n", Arr::get($errArrForDevice, Arr::get($value, 'ID_DEV'))));
									}	else {
										$title='no_err';
									}
									if ($title === false) $title = 'no_err';
										echo '<abbr title="'
										.htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
										.'">'
										.HTML::anchor('door/doorInfo/'.(int)Arr::get($value, 'ID_DEV'), htmlspecialchars(Arr::get($value, 'NAME', 'No data'), ENT_QUOTES, 'UTF-8'))
										.'</abbr>';
									echo '</td>';
									echo '<td>'.Arr::get($value, 'COUNT_WRITE', '-').'</td>';
									echo '<td>'.Arr::get($value, 'COUNT_DELETE', '-').'</td>';
									echo '<td>';
									
									
									// Перевод и группировку технических текстов ошибок делает модель
									// (Model_Dev::humanizeLoadResultList): одинаковые по смыслу сообщения
									// сворачиваются в одну строку, в скобках - число повторов.
									// Оригиналы прячем в подсказку (title).
									if(count(Arr::get($errArrForDevice, Arr::get($value, 'ID_DEV'))) > 0)
									{
										$errorMessages = array();
										foreach($devModel->humanizeLoadResultList(Arr::get($errArrForDevice, Arr::get($value, 'ID_DEV'))) as $humanText=>$errorInfo)
										{
											$label = $humanText.($errorInfo['count'] > 1 ? ' ('.$errorInfo['count'].')' : '');
											$errorMessages[] = '<abbr title="'.htmlspecialchars(implode("\n", $errorInfo['raw']), ENT_QUOTES, 'UTF-8').'">'
												.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</abbr>';
										}
										echo implode('<br>', $errorMessages);
									}
									
									
									
										
									echo '</td>';
									$count_write=$count_write+Arr::get($value, 'COUNT_WRITE', 0);
									$count_delete=$count_delete+Arr::get($value, 'COUNT_DELETE', 0);
								echo '</tr>';
								
							}
							?>
							
							<tr>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td><?echo $count_write;?></td>
								<td><?echo $count_delete;?></td>
								<td></td>
							</tr>
							<tr>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td></td>
								<td><?echo __('total')?></td>
								<td><?echo $count_write+$count_delete;?></td>
								<td></td>
								<td></td>
							</tr>
					</tbody>
						</table>

					<!--<button type="submit" class="btn btn-primary" >Остановить загрузку</button>-->
				
		</div>
			
		
	</div>	
	<?echo Form::close();?>
	
  
</div>
