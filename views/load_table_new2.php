<script type="text/javascript">
$(document).ready(function() {
    var $table = $("#tablesorter");
    $table.tablesorter({
        theme: 'blue',
        headers: {
            0: { sorter: false, filter: false },
            19: { sorter: false, filter: false },
        },
        widgets: ['zebra', 'filter'],
        widgetOptions: {
            filter_reset: '.reset-filter',
            filter_searchDelay: 300,
            filter_placeholder: { search: 'Поиск...' }
        }
    });
    
    // Выделить все
    $('#check_all3').change(function() {
        $('input[name^="id_dev["]').prop('checked', $(this).prop('checked'));
    });
    
    // Обновление состояния чекбокса "Выделить все"
    $('input[name^="id_dev["]').change(function() {
        var allChecked = $('input[name^="id_dev["]').length === $('input[name^="id_dev["]:checked').length;
        $('#check_all3').prop('checked', allChecked);
    });
});
</script>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo __('device_panel_title').' '.date('Y-m-d H:i:s'); ?></h3>
    </div>
    
    <div class="panel-body">
        <div class="panel panel-danger">
            <div class="panel-body">
                <?php 
                echo __('device_panel_title_desc', array('date_from'=>$date_stat['min'], 'date_to'=>$date_stat['max']));
                ?>
            </div>
        </div>
        
        <?php
        $t1 = microtime(true);
        echo Form::open('Dev/device_control');
        ?>
        
        <table id="tablesorter" class="table table-striped table-hover table-condensed tablesorter">
            <thead align="center">
                <tr>
                    <th>
                        Выделить<br><label><input type="checkbox" name="id_dev" id="check_all3"></label>
                    </th>
                    <?php
                    echo '<th>'.__('SERVER_NAME').'</th>';
                    echo '<th>'.__('DEVICE_NAME').'</th>';
                    // КОЛОНКА 3 - С ВЫПАДАЮЩИМ СПИСКОМ (через класс)
	echo '<th class="filter-select" data-placeholder="Все">'.__('DEVICE_IsActive').'</th>';
                    echo '<th>'.__('DEVICE_TYPE').'</th>';
                    echo '<th>'.__('IP').'</th>';
	echo '<th class="filter-select" data-placeholder="Все">'.__('isOnLine').'</th>';
	echo '<th class="filter-select" data-placeholder="Все">'.__('isWp').'</th>';
	echo '<th class="filter-select" data-placeholder="Все">'.__('isTest').'</th>';
                    echo '<th>'.__('DOOR_NAME').'</th>';
                    echo '<th>'.__('DEVICE_VERSION').'</th>';
                    echo '<th>'.__('SCUD_MODE').'</th>';
                    echo '<th>'.__('BASE_COUNT').'</th>';
                    echo '<th>'.__('DEVICE_COUNT').'</th>';
                    echo '<th>'.__('delta_count').'</th>';
                    echo '<th>'.__('DOORSTATE_MODE').'</th>';
	echo '<th class="filter-select" data-placeholder="Все">'.__('isBlocked').'</th>';
	echo '<th class="filter-select" data-placeholder="Все">'.__('isAlarm').'</th>';
                    echo '<th>'.__('time').'</th>';
                    echo '<th>'.__('timestamp', array('title'=>'Дата получения информации')).'</th>';
                    echo '<th class="filter-false sorter-false">'.__('collectAlarm').'</th>';
                    ?>
                </tr>
            </thead>
            <tbody>
            <?php 
            foreach ($list as $key => $deviceInfo) {
                // разница в картах
                $deltacard = ($deviceInfo->keyCount_reader - $deviceInfo->countDataBase);
                $tr_class = 'success';
                
                $collectAttention = false;
                if($deviceInfo->isBlocked 
                    OR $deviceInfo->isAlarm 
                    OR (!$deviceInfo->onLine)
                    OR ($deltacard != 0) 
                    OR ($deviceInfo->doorMode == 'Fire') 
                    OR ($deviceInfo->doorMode == 'Blocked') 
                    OR ($deviceInfo->doorMode == 'Alarm')) {
                    $collectAttention = true;
                }
                
                if($deltacard == 0) $tr_class = 'active';
                if($deltacard < 0) $tr_class = 'danger';
                if($deltacard > 0) $tr_class = 'warning';
                
                echo '<tr class="'.$tr_class.'">';
                
                // Колонка 0 - Чекбокс
                echo '<td><label>';
                echo Form::checkbox('id_dev['.$deviceInfo->id_dev.']', $deviceInfo->id_dev, false, array('class'=>'checkbox'));
                echo '</label></td>';
                
                // Колонка 1 - SERVER_NAME
                echo '<td>'. iconv('CP1251', 'UTF-8', $deviceInfo->servername).'</td>';
                
                // Колонка 2 - DEVICE_NAME
                echo '<td>'.$deviceInfo->parentid . ' ' . HTML::anchor('device/deviceinfo/'.$deviceInfo->parentid, iconv('windows-1251','UTF-8', $deviceInfo->parentname)).'</td>';
                
                // КОЛОНКА 3 - DEVICE_IsActive (с data-value для select фильтра)
                echo '<td data-value="' . ($deviceInfo->active == 1 ? '1' : '0') . '">';
                if($deviceInfo->active == 1) {
                    echo '<span class="hidden">1</span>';
                    echo HTML::image("static/images/Card_on.png", array('height' => 20, 'alt' => 'Включено', 'title'=>'Устройство включено в БД СКУД.'));
                    echo __(' On');
                } else {
                    echo '<span class="hidden">0</span>';
                    echo HTML::image("static/images/Card_off.png", array('height' => 20, 'alt' => 'Выключено', 'title'=>'Устройство выключено в БД СКУД.'));
                    echo __(' Off');
                }
                echo '</td>';
                
                // Колонка 4 - DEVICE_TYPE
                echo '<td>'.iconv('CP1251', 'UTF-8', $deviceInfo->devtypename).'</td>';
                
                // Колонка 5 - IP
                echo '<td>';
                if (is_null($deviceInfo->ip)) {
                    echo '<span class="label label-danger">'.__('no_ip').'</span><br>';
                } else {
                    echo HTML::anchor('http://'.$deviceInfo->ip, $deviceInfo->ip, array('target' => '_blank'));
                }
                echo '</td>';
                
// Колонка 6 - isOnLine (исправленный вариант)
echo '<td data-value="';
if($deviceInfo->mac != '00-00-00-00-00-00') {
    echo $deviceInfo->onLine ? '0' : '1';
} else {
    echo '2';
}
echo '">';
if($deviceInfo->mac != '00-00-00-00-00-00') {
    if ($deviceInfo->onLine) {
        echo '<span class="hidden">0</span>';
        echo HTML::image("static/images/dot_green_n.png", array('height' => 20, 'alt' => 'Да'));
        echo ' Онлайн';
    } else {
        echo '<span class="hidden">1</span>';
        echo HTML::image("static/images/dot_red_h.png", array('height' => 20, 'alt' => 'Нет'));
        echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания'));
        echo ' Офлайн';
    }
} else {
    echo '<span class="hidden">2</span>';
    echo HTML::image("static/images/dot_yellow_h.png", array('height' => 20, 'alt' => 'Плохая связь'));
    echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Плохая связь', 'title'=>'Плохая связь.'));
    echo ' Плохая связь';
}
echo '</td>';
                
// Колонка 7 - isWp
if($deviceInfo->onLine) {
    $wp_value = $deviceInfo->isWP ? '1' : '0';
    echo '<td data-value="' . $wp_value . '">';
    echo '<span class="hidden">' . $wp_value . '</span>';
    echo Form::checkbox('', 1, $deviceInfo->isWP == true, array('disabled'=>'disabled'));
    echo $deviceInfo->isWP ? ' Да' : ' Нет';
    echo '</td>';
} else {
    echo '<td data-value="-">-</td>';
}
                
// Колонка 8 - isTest
if($deviceInfo->onLine) {
    $test_value = $deviceInfo->isTest ? '1' : '0';
    echo '<td data-value="' . $test_value . '">';
    echo '<span class="hidden">' . $test_value . '</span>';
    echo Form::checkbox('', 1, $deviceInfo->isTest == true, array('disabled'=>'disabled'));
    echo $deviceInfo->isTest ? ' Да' : ' Нет';
    echo '</tr>';
} else {
    echo '<td data-value="-">-</td>';
}
                
                // Колонка 9 - DOOR_NAME
                echo '<td>'.$deviceInfo->id_dev.' '.HTML::anchor('door/doorInfo/'.$deviceInfo->id_dev, iconv('CP1251', 'UTF-8', $deviceInfo->name)).'</td>';
                
                // Колонка 10 - DEVICE_VERSION
                echo '<td>'.$deviceInfo->softVersion.'</td>';
                
                // Колонка 11 - SCUD_MODE
                echo '<td>/';
                echo $deviceInfo->scud.' '.$deviceInfo->id_reader;
                if(($deviceInfo->scud == 'd1') AND ($deviceInfo->id_reader == 1) AND ($deviceInfo->doorMode != 'Disabled')) {
                    echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Для настройки Одна дверь вторую точку прохода необходимо выключить.')); 
                    echo HTML::image("static/images/star_red.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Недопустимая комбинация настроек.')); 
                }
                echo '</td>';
                
                // Колонка 12 - BASE_COUNT
                echo '<td>'.iconv('CP1251', 'UTF-8', $deviceInfo->countDataBase).'</td>';
                
                // Колонка 13 - DEVICE_COUNT
                if($deviceInfo->onLine) {
                    echo '<td>'.$deviceInfo->keyCount_reader.'</td>';
                } else {
                    echo '<td>-</td>';
                }
                
                // Колонка 14 - delta_count
                if($deviceInfo->onLine) {
                    if ($deltacard == 0) {
                        echo '<td class="success">'.$deltacard.'</td>';
                    } else {
                        echo '<td>'.$deltacard;
                        echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания'));
                        echo '</td>';
                    }
                } else {
                    echo '<td>-</td>';
                }
                
                // Колонка 15 - DOORSTATE_MODE
                echo '<td>';
                switch($deviceInfo->doorMode) {
                    case 'Fire':
                        echo '<span class="hidden">1</span>';
                        echo __('<acronym title=":doorMode">Откр всегда</acronym>', array(':doorMode'=>$deviceInfo->doorMode))
                            . ' ' . HTML::image("static/images/replace2.png", array('height' => 20, 'alt' => 'Откр всегда'))
                            . HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Дверь открыта навсегда командой с компьютера.'));
                        break;
                    case 'Blocked':
                        echo '<span class="hidden">1</span>';
                        echo __('Закр всегда <acronym>:doorMode</acronym>', array(':doorMode'=>$deviceInfo->doorMode))
                            . ' ' . HTML::image("static/images/replace2.png", array('height' => 20, 'alt' => 'Закр всегда'))
                            . HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Дверь закрыта навсегда командой с компьютера.'));
                        break;
                    case 'Closed':
                        echo __('<acronym title=":doorMode">Рабочий режим</acronym>', array(':doorMode'=>$deviceInfo->doorMode));
                        break;
                    case 'Open':
                        echo __('<acronym title=":doorMode">Рабочий режим</acronym>', array(':doorMode'=>$deviceInfo->doorMode))
                            . ' ' . HTML::image("static/images/green-check.png", array('height' => 20, 'alt' => 'Рабочий режим'));
                        break;
                    case 'Alarm':
                        echo '<span class="hidden">1</span>';
                        echo __('<acronym title=":doorMode">Взлом</acronym>', array(':doorMode'=>$deviceInfo->doorMode))
                            . ' ' . HTML::image("static/images/docs-point-big2.png", array('height' => 20, 'alt' => 'Взлом'))
                            . HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Взлом двери. Проверьте состояние геркона.'));
                        break;
                    case 'Disabled':
                        echo __('<acronym title=":doorMode">Отключен</acronym>', array(':doorMode'=>$deviceInfo->doorMode));
                        break;
                    case 'no':
                        echo __('<acronym title=":doorMode">-</acronym>', array(':doorMode'=>$deviceInfo->doorMode));
                        break;
                    default:
                        echo __('<acronym title=":doorMode">Не определен</acronym>', array(':doorMode'=>$deviceInfo->doorMode))
                            . ' ' . HTML::image("static/images/man-says.png", array('height' => 20, 'alt' => 'Не определен'));
                        break;
                }
                echo '</td>';
                
               // Колонка 16 - isBlocked
if($deviceInfo->onLine) {
    $blocked_value = $deviceInfo->isBlocked ? '1' : '0';
    echo '<td data-value="' . $blocked_value . '">';
    echo '<span class="hidden">' . $blocked_value . '</span>';
    echo Form::checkbox('', 1, $deviceInfo->isBlocked == true, array('disabled'=>'disabled'));
    if ($deviceInfo->isBlocked == true) {
        echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Вход блокировки замкнут на "землю".'));
    }
    echo '</td>';
} else {
    echo '<td data-value="-">-</td>';
}
                
               // Колонка 17 - isAlarm
if($deviceInfo->onLine) {
    $alarm_value = $deviceInfo->isAlarm ? '1' : '0';
    echo '<td data-value="' . $alarm_value . '">';
    echo '<span class="hidden">' . $alarm_value . '</span>';
    echo Form::checkbox('', 1, $deviceInfo->isAlarm == true, array('disabled'=>'disabled'));
    if ($deviceInfo->isAlarm == true) {
        echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title'=>'Вход Alarm замкнут на "землю".'));
    }
    echo '</td>';
} else {
    echo '<td data-value="-">-</td>';
}
                
                // Колонка 18 - time
                echo '<td>/'.number_format($deviceInfo->timeExecute, 3, '.', '').'</td>';
                
                // Колонка 19 - timestamp
                echo '<td>';
                echo date('d.m.Y H:i:s', $deviceInfo->timeGetData);
                
                $tt3 = time();
                $pbvalue = intval((($deviceInfo->timeGetData + 60*60*24 - $tt3) * 100) / (60*60*24));
                $pbcolor = 'progress-bar-danger';
                
                if($pbvalue >= 76) $pbcolor = 'progress-bar-success';
                if($pbvalue >= 51 and $pbvalue < 75) $pbcolor = 'progress-bar-info';
                if($pbvalue >= 26 and $pbvalue < 51) $pbcolor = 'progress-bar-warning';
                if($pbvalue < 26) $pbcolor = 'progress-bar-danger';
                
                echo '<div class="progress">
                    <div class="progress-bar '.$pbcolor.'" role="progressbar" style="width: '.$pbvalue.'%"></div>
                </div>';
                echo '</td>';
                
                // Колонка 20 - collectAlarm
                echo '<td>';
                if($collectAttention) {
                    echo HTML::image("static/images/attention.png", array('height' => 30, 'alt' => 'Требует внимания'));
                    echo '<span class="hidden">1</span>';
                }
                echo '</td>';
                
                echo '</tr>';
            }
            ?>
            </tbody>
        </table>
        
        <br><br><br><br><br><br><br>
        
        <nav class="navbar navbar-default navbar-fixed-bottom disable" role="navigation">
            <div class="container">
                <button type="submit" class="btn btn-primary sm" name="synctime" value="1" title="Синхронизация времени в контроллерах"><?php echo __('synctime_dev'); ?></button>
                <button type="submit" class="btn btn-primary sm" name="settz" value="1" title="Установить временные зоны для выбранных контроллеров"><?php echo __('settz'); ?></button>
                <button type="submit" class="btn btn-danger sm" name="clear_device" value="1" title="Удалить карты из выбранных точек прохода"><?php echo __('clear_device'); ?></button>
                <button type="submit" class="btn btn-danger sm" name="load_card" value="1" title="Загрузить карты в выбранные точки прохода"><?php echo __('load_card'); ?></button>
                <button type="submit" class="btn btn-success sm" name="checkStatus" value="1" title="Чтение состояния и запись данных в базу данных."><?php echo __('checkStatus'); ?></button>
                <button type="submit" class="btn btn-warning sm" name="readkey" value="1" title="Вычитка карт из точки прохода и запись в файл"><?php echo __('Comparekey'); ?></button>
                <button type="submit" class="btn btn-warning sm" name="cardidx_refresh" value="1" title="cardidx_refresh"><?php echo __('cardidx_refresh'); ?></button>
                
                <?php 
                echo Form::button('control_door', 'Разблокировать', array('value'=>'unlockdoor', 'class'=>'btn btn-warning', 'type' => 'submit'));
                echo Form::button('control_door', 'Открыть 1 раз', array('value'=>'opendoor', 'class'=>'btn btn-warning', 'type' => 'submit'));
                echo Form::button('control_door', 'Открыть навсегда', array('value'=>'opendooralways', 'class'=>'btn btn-warning', 'type' => 'submit'));
                echo Form::button('control_door', 'Закрыть навсегда', array('value'=>'lockdoor', 'class'=>'btn btn-warning', 'type' => 'submit'));
                ?>
            </div>
        </nav>
        
        <?php echo Form::close(); ?>
    </div>
</div>