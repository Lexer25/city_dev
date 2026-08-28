<!-- Управление очередью загрузки карт в контроллеры -->
<script type="text/javascript">
   	$(function() {		
		$("#table15").tablesorter({sortList:[[0,0],[2,1]], widgets: ['zebra']});
		$("#options").tablesorter({sortList: [[0,0]], headers: { 0:{sorter: false}}});
	});	

  	$(function() {		
  		$("#table1").tablesorter({sortList:[[0,0]], headers: { 0:{sorter: false}}});
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
  
		<div class="panel panel-primary" id="refresh">
			<div class="panel-heading"><?echo __('loading_card');?></div>
			<div class="panel-body">
				<?echo __('loading_card');?>
						<!-- <table class="table table-striped table-hover table-condensed">  -->
						<table id="table1" class=" table table-striped table-hover table-condensed tablesorter">
						<thead>
							<tr>
								<th>
									<label><input type="checkbox" name="stop_load" id="check_all1"> Выделить всё</label>
								</th>
								<th><?echo __('ID_DEV');?></th>
								<th><?echo __('SERVER');?></th>
								<th><?echo __('DEVICE');?></th>
								<th><?echo __('NAME');?></th>
								<th><?echo __('CARD_FOR_LOAD');?></th>
								<th><?echo __('CARD_FOR_DELETE');?></th>
								<th><?echo __('CARD_FOR_DELETE');?></th>
							</tr>
						</thead>
						<tbody>
							<? 
							$count_write=0;
							$count_delete=0;
							foreach ($list as $key => $value)
							{
								echo '<tr class="'.Arr::get($value, 'TR_COLOR', 'active').'">';
								echo '<td><label>'.Form::checkbox('stop_load['.Arr::get($value, 'ID_DEV').']', 1, FALSE, array('class'=>'checkbox1')).'</label></td>';
									echo '<td>'.Arr::get($value, 'ID_DEV', 'No data').'</td>';
									echo '<td>'.Arr::get($value, 'SERVER', 'No data').'</td>';
									echo '<td>'.Arr::get($value, 'DEVICE', 'No data').'</td>';
									echo '<td>';
									
									if(count(Arr::get($errArrForDevice, Arr::get($value, 'ID_DEV'))) > 0)
									{
										$title= iconv('CP1251', 'UTF-8', implode("\n", Arr::get($errArrForDevice, Arr::get($value, 'ID_DEV'))));
									}	else {
										//echo Debug::vars('80', $errArrForDevice); exit;
										$title='no_err';
									}
										echo '<abbr title=\''
										.$title
										.'\'>'
										.HTML::anchor('door/doorInfo/'.Arr::get($value, 'ID_DEV'), Arr::get($value, 'NAME', 'No data'))
										.'</abbr>';
									echo '</td>';
									echo '<td>'.Arr::get($value, 'COUNT_WRITE', '-').'</td>';
									echo '<td>'.Arr::get($value, 'COUNT_DELETE', '-').'</td>';
									echo '<td>';
									
									
									$mess=array(
										'code is 1'=>'Не хватает памяти',
										'recv()'=>'Нет связи',
										'not found'=>'Не настроен или отключен'
									);
		
		
									if(count(Arr::get($errArrForDevice, Arr::get($value, 'ID_DEV'))) > 0)
									{
										//echo Debug::vars('109', Arr::get($errArrForDevice, Arr::get($value, 'ID_DEV'))); 
										
										foreach(Arr::get($errArrForDevice, Arr::get($value, 'ID_DEV')) as $key2=>$value2)
										{
										
											foreach ($mess as $key=>$value3){
												if (stripos($value2, $key) !== false) {
														echo '<abbr title="'.__($key).'">'.$value3.'</abbr>';
													} else {
														//echo $value2;
													}
												
											}												
											echo '<br>';
										}
										
										$title= iconv('CP1251', 'UTF-8', implode("\n", Arr::get($errArrForDevice, Arr::get($value, 'ID_DEV'))));
									}	else {
										//echo Debug::vars('80', $errArrForDevice); exit;
										$title='no_err';
									}
									
									
									
										//echo Debug::vars('97', Arr::get($errArrForDevice, Arr::get($value, 'ID_DEV')));
										
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
								<td><?echo $count_write;?></td>
								<td><?echo $count_delete;?></td>
								<td></td>
							</tr>
							<tr>
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
			
		
	</div>	
	<?echo Form::close();?>
	
  
</div>
