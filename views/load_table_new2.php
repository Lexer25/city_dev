<?php
/**
 * load_table_new2.php
 * Таблица устройств с экспортом в CSV и панелью управления.
 */

// === Хелперы ===
if (!function_exists('cp2utf')) {
    function cp2utf($s) {
        if ($s === null || $s === '') return '';
        $r = @iconv('CP1251', 'UTF-8//IGNORE', $s);
        return $r !== false ? $r : $s;
    }
}

// === Права администратора (безопасное определение) ===
$isAdmin      = !empty($is_admin);
$navClass     = $isAdmin
    ? 'navbar navbar-default navbar-fixed-bottom'
    : 'navbar navbar-default navbar-fixed-bottom disabled-admin';
$disabledAttr = $isAdmin ? '' : ' disabled';
$disabledArr  = $isAdmin ? array() : array('disabled' => 'disabled');
?>

<style>
    .navbar.disabled-admin {
        opacity: .6;
        pointer-events: none;
    }
    .fp-disabled { color: #999; }
    .fp-notable  { color: #f0ad4e; }
    .fp-error    { color: #d9534f; }
    .fp-ok       { color: #5cb85c; }
    .fp-ok-muted { color: #ddd; }
    .fp-small    { font-size: 11px; }
    #export-button-place { margin: 10px 0; }
</style>

<script type="text/javascript">
$(document).ready(function() {
    var $table = $("#tablesorter");

    $table.tablesorter({
        theme: 'blue',
        headers: {
            0:  { sorter: false, filter: false },
            19: { sorter: false, filter: false }
        },
        widgets: ['zebra', 'filter'],
        widgetOptions: {
            filter_reset: '.reset-filter',
            filter_searchDelay: 300,
            filter_placeholder: { search: 'Поиск...' }
        }
    });

    // Делегирование: "Выделить все" и чекбоксы строк
    $(document).on('change', '#check_all3', function() {
        var checked = $(this).prop('checked');
        $('.js-dev-checkbox:visible').prop('checked', checked);
    });

    $(document).on('change', '.js-dev-checkbox', function() {
        var $visible = $('.js-dev-checkbox:visible');
        var allChecked = $visible.length > 0 &&
                         $visible.length === $visible.filter(':checked').length;
        $('#check_all3').prop('checked', allChecked);
    });

    // Кнопка "Экспорт в CSV"
    initExportButton();
});

function initExportButton() {
    if (document.getElementById('exportCsvBtn')) return;

    var container = document.getElementById('export-button-place');
    if (!container) {
        if (window.console) console.warn('export-button-place не найден в DOM');
        return;
    }

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-success';
    btn.id = 'exportCsvBtn';
    btn.style.margin = '5px';
    btn.innerHTML = '<span class="glyphicon glyphicon-export"></span> Экспорт в CSV';
    btn.addEventListener('click', function() {
        exportTableToCSV('devices_export_' + getDateString() + '.csv');
    });

    container.appendChild(btn);
}

function getDateString() {
    var d = new Date();
    return d.getFullYear() + '-' +
           String(d.getMonth() + 1).padStart(2, '0') + '-' +
           String(d.getDate()).padStart(2, '0') + '_' +
           String(d.getHours()).padStart(2, '0') + '-' +
           String(d.getMinutes()).padStart(2, '0');
}

function exportTableToCSV(filename) {
    var table = document.getElementById('tablesorter');
    if (!table) {
        alert('Таблица не найдена');
        return;
    }

    // Экспортируем ВСЕ строки tbody, независимо от фильтра и видимости
    var allRows = table.querySelectorAll('tbody tr');
    if (allRows.length === 0) {
        alert('Нет данных для экспорта');
        return;
    }

    // Заголовки (пропускаем 0-ю колонку с чекбоксом)
    var headers = [];
    var ths = table.querySelectorAll('thead tr th');
    ths.forEach(function(th, i) {
        if (i === 0) return; // чекбокс
        var text = th.textContent.trim().replace(/\s+/g, ' ');
        if (text === '') text = 'Колонка ' + i;
        headers.push(text);
    });

    // Данные — по всем строкам
    var data = [];
    allRows.forEach(function(tr) {
        var row = [];
        var tds = tr.querySelectorAll('td');
        tds.forEach(function(td, i) {
            if (i === 0) return; // пропускаем чекбокс

            // Приоритет: data-value → data-text → текст
            var value = td.getAttribute('data-value');
            if (value === null || value === '') {
                value = td.getAttribute('data-text');
            }
            if (value === null || value === '') {
                var clone = td.cloneNode(true);
                clone.querySelectorAll('.hidden').forEach(function(el) { el.remove(); });
                value = clone.textContent.trim().replace(/\s+/g, ' ');
            }
            row.push(value);
        });
        data.push(row);
    });

    // Формируем CSV с CRLF для лучшей совместимости с Excel
    var csv = '\uFEFF';
    csv += headers.join(';') + '\r\n';

    data.forEach(function(row) {
        var escaped = row.map(function(cell) {
            cell = String(cell == null ? '' : cell);
            if (cell.includes(';') || cell.includes('"') || cell.includes('\n') || cell.includes('\r')) {
                return '"' + cell.replace(/"/g, '""') + '"';
            }
            return cell;
        });
        csv += escaped.join(';') + '\r\n';
    });

    // Скачиваем
    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(link.href);
}
</script>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo __('device_panel_title') . ' ' . date('Y-m-d H:i:s'); ?></h3>
    </div>

    <div class="panel-body">
        <div class="panel panel-danger">
            <div class="panel-body">
                <?php
                echo __('device_panel_title_desc', array(
                    'date_from' => $date_stat['min'],
                    'date_to'   => $date_stat['max']
                ));
                ?>
            </div>
        </div>

        <!-- Якорь для кнопки "Экспорт в CSV" — НАД таблицей -->
        <div id="export-button-place"></div>

        <?php echo Form::open('Dev/device_control', array('method' => 'post')); ?>

        <table id="tablesorter" class="table table-striped table-hover table-condensed tablesorter">
            <thead align="center">
                <tr>
                    <th>
                        Выделить<br>
                        <label><input type="checkbox" name="id_dev" id="check_all3"></label>
                    </th>
                    <?php
                    echo '<th>' . __('SERVER_NAME') . '</th>';
                    echo '<th>' . __('DEVICE_NAME') . '</th>';
                    echo '<th class="filter-select" data-placeholder="Все">' . __('DEVICE_IsActive') . '</th>';
                    echo '<th>' . __('DEVICE_TYPE') . '</th>';
                    echo '<th>' . __('IP') . '</th>';
                    echo '<th class="filter-select" data-placeholder="Все">' . __('isOnLine') . '</th>';
                    echo '<th class="filter-select" data-placeholder="Все">' . __('isWp') . '</th>';
                    echo '<th class="filter-select" data-placeholder="Все">' . __('isTest') . '</th>';
                    echo '<th>' . __('DOOR_NAME') . '</th>';
                    echo '<th class="filter-select" data-placeholder="Все">' . __('on_plane') . '</th>';
                    echo '<th>' . __('DEVICE_VERSION') . '</th>';
                    echo '<th>' . __('SCUD_MODE') . '</th>';
                    echo '<th>' . __('BASE_COUNT') . '</th>';
                    echo '<th>' . __('DEVICE_COUNT') . '</th>';
                    echo '<th>' . __('delta_count') . '</th>';
                    echo '<th>' . __('DOORSTATE_MODE') . '</th>';
                    echo '<th class="filter-select" data-placeholder="Все">' . __('isBlocked') . '</th>';
                    echo '<th class="filter-select" data-placeholder="Все">' . __('isAlarm') . '</th>';
                    echo '<th>' . __('time') . '</th>';
                    echo '<th>' . __('timestamp', array('title' => 'Дата получения информации')) . '</th>';
                    echo '<th class="filter-false sorter-false">' . __('collectAlarm') . '</th>';
                    ?>
                </tr>
            </thead>
            <tbody>
            <?php
            foreach ($list as $key => $deviceInfo) {
                // Разница в картах
                $deltacard = ($deviceInfo->keyCount_reader - $deviceInfo->countDataBase);

                // Требует внимания?
                $collectAttention = $deviceInfo->isBlocked
                    || $deviceInfo->isAlarm
                    || !$deviceInfo->onLine
                    || ($deltacard != 0)
                    || in_array($deviceInfo->doorMode, array('Fire', 'Blocked', 'Alarm'), true);

                // Класс строки по дельте карт
                $tr_class = 'active';
                if ($deltacard < 0) $tr_class = 'danger';
                if ($deltacard > 0) $tr_class = 'warning';

                echo '<tr class="' . $tr_class . '">';

                // Колонка 0 — Чекбокс
                echo '<td><label>';
                echo Form::checkbox(
                    'id_dev[' . (int)$deviceInfo->id_dev . ']',
                    $deviceInfo->id_dev,
                    false,
                    array('class' => 'checkbox js-dev-checkbox')
                );
                echo '</label></td>';

                // Колонка 1 — SERVER_NAME
                echo '<td>' . cp2utf($deviceInfo->servername) . '</td>';

                // Колонка 2 — DEVICE_NAME
                echo '<td>' . (int)$deviceInfo->parentid . ' '
                    . HTML::anchor('devices/edit/' . (int)$deviceInfo->parentid, cp2utf($deviceInfo->parentname))
                    . '</td>';

                // Колонка 3 — DEVICE_IsActive
                echo '<td data-value="' . ($deviceInfo->active == 1 ? '1' : '0') . '">';
                if ($deviceInfo->active == 1) {
                    echo '<span class="hidden">1</span>';
                    echo HTML::image("static/images/Card_on.png", array('height' => 20, 'alt' => 'Включено', 'title' => 'Устройство включено в БД СКУД.'));
                    echo __(' On');
                } else {
                    echo '<span class="hidden">0</span>';
                    echo HTML::image("static/images/Card_off.png", array('height' => 20, 'alt' => 'Выключено', 'title' => 'Устройство выключено в БД СКУД.'));
                    echo __(' Off');
                }
                echo '</td>';

                // Колонка 4 — DEVICE_TYPE
                echo '<td>' . cp2utf($deviceInfo->devtypename) . '</td>';

                // Колонка 5 — IP
                echo '<td>';
                if (is_null($deviceInfo->ip)) {
                    echo '<span class="label label-danger">' . __('no_ip') . '</span><br>';
                } elseif (filter_var($deviceInfo->ip, FILTER_VALIDATE_IP)) {
                    echo HTML::anchor('http://' . $deviceInfo->ip, $deviceInfo->ip, array('target' => '_blank'));
                } else {
                    echo htmlspecialchars($deviceInfo->ip, ENT_QUOTES, 'UTF-8');
                }
                echo '</td>';

                // Колонка 6 — isOnLine
                echo '<td data-value="';
                if ($deviceInfo->mac != '00-00-00-00-00-00') {
                    echo $deviceInfo->onLine ? '0' : '1';
                } else {
                    echo '2';
                }
                echo '">';
                if ($deviceInfo->mac != '00-00-00-00-00-00') {
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
                    echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Плохая связь', 'title' => 'Плохая связь.'));
                    echo ' Плохая связь';
                }
                echo '</td>';

                // Колонка 7 — isWp
                if ($deviceInfo->onLine) {
                    $wp_value = $deviceInfo->isWP ? '1' : '0';
                    echo '<td data-value="' . $wp_value . '">';
                    echo '<span class="hidden">' . $wp_value . '</span>';
                    echo Form::checkbox('', 1, $deviceInfo->isWP == true, array('disabled' => 'disabled'));
                    echo $deviceInfo->isWP ? ' Да' : ' Нет';
                    echo '</td>';
                } else {
                    echo '<td data-value="-">-</td>';
                }

                // Колонка 8 — isTest
                if ($deviceInfo->onLine) {
                    $test_value = $deviceInfo->isTest ? '1' : '0';
                    echo '<td data-value="' . $test_value . '">';
                    echo '<span class="hidden">' . $test_value . '</span>';
                    echo Form::checkbox('', 1, $deviceInfo->isTest == true, array('disabled' => 'disabled'));
                    echo $deviceInfo->isTest ? ' Да' : ' Нет';
                    echo '</td>';
                } else {
                    echo '<td data-value="-">-</td>';
                }

                // Колонка 9 — DOOR_NAME
                echo '<td>';
                echo (int)$deviceInfo->id_dev . ' '
                    . HTML::anchor('door/doorInfo/' . (int)$deviceInfo->id_dev, cp2utf($deviceInfo->name));
                echo '</td>';

                // Колонка 10 — on_plane
                echo '<td>';
                if ($deviceInfo->floorplanStatus == 'disabled') {
                    echo '<span class="glyphicon glyphicon-ban-circle fp-disabled" title="Модуль планов отключен"></span>';
                    echo ' <span class="fp-disabled fp-small">Отключен</span>';
                } elseif ($deviceInfo->floorplanStatus == 'no_table') {
                    echo '<span class="glyphicon glyphicon-exclamation-sign fp-notable" title="Таблица планов не найдена"></span>';
                    echo ' <span class="fp-notable fp-small">Нет таблицы</span>';
                } elseif ($deviceInfo->floorplanStatus == 'error') {
                    echo '<span class="glyphicon glyphicon-remove-sign fp-error" title="' . htmlspecialchars($deviceInfo->floorplanMessage, ENT_QUOTES, 'UTF-8') . '"></span>';
                    echo ' <span class="fp-error fp-small">Ошибка</span>';
                } else {
                    echo '<span class="hidden">' . ($deviceInfo->hasFloorplan ? '1' : '0') . '</span>';
                    if ($deviceInfo->hasFloorplan) {
                        echo HTML::anchor(
                            'floorplan/findDevice?id_dev=' . (int)$deviceInfo->id_dev,
                            '<span class="glyphicon glyphicon-map-marker fp-ok" style="font-size:16px;" title="Показать на плане"></span>',
                            array(
                                'target' => '_blank',
                                'style'  => 'text-decoration: none; margin-left: 3px;',
                                'title'  => 'Показать на плане'
                            )
                        );
                        echo ' <span class="fp-ok fp-small">На плане</span>';
                    } else {
                        echo '<span class="glyphicon glyphicon-map-marker fp-ok-muted" style="font-size:16px;" title="Не размещен на плане"></span>';
                        echo ' <span class="fp-disabled fp-small">Не на плане</span>';
                    }
                }
                echo '</td>';

                // Колонка 11 — DEVICE_VERSION
                echo '<td>' . htmlspecialchars($deviceInfo->softVersion, ENT_QUOTES, 'UTF-8') . '</td>';

                // Колонка 12 — SCUD_MODE
                echo '<td>/';
                echo htmlspecialchars($deviceInfo->scud, ENT_QUOTES, 'UTF-8') . ' '
                    . htmlspecialchars($deviceInfo->id_reader, ENT_QUOTES, 'UTF-8');
                if (($deviceInfo->scud == 'd1') && ($deviceInfo->id_reader == 1) && ($deviceInfo->doorMode != 'Disabled')) {
                    echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title' => 'Для настройки Одна дверь вторую точку прохода необходимо выключить.'));
                    echo HTML::image("static/images/star_red.png", array('height' => 20, 'alt' => 'Требует внимания', 'title' => 'Недопустимая комбинация настроек.'));
                }
                echo '</td>';

                // Колонка 13 — BASE_COUNT
                echo '<td>' . cp2utf($deviceInfo->countDataBase) . '</td>';

                // Колонка 14 — DEVICE_COUNT
                echo '<td>' . ($deviceInfo->onLine ? (int)$deviceInfo->keyCount_reader : '-') . '</td>';

                // Колонка 15 — delta_count
                if ($deviceInfo->onLine) {
                    if ($deltacard == 0) {
                        echo '<td class="success">' . (int)$deltacard . '</td>';
                    } else {
                        echo '<td>' . (int)$deltacard;
                        echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания'));
                        echo '</td>';
                    }
                } else {
                    echo '<td>-</td>';
                }

                // Колонка 16 — DOORSTATE_MODE
                echo '<td>';
                switch ($deviceInfo->doorMode) {
                    case 'Fire':
                        echo '<span class="hidden">1</span>';
                        echo '<acronym title="' . htmlspecialchars($deviceInfo->doorMode, ENT_QUOTES, 'UTF-8') . '">Откр всегда</acronym>'
                            . ' ' . HTML::image("static/images/replace2.png", array('height' => 20, 'alt' => 'Откр всегда'))
                            . HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title' => 'Дверь открыта навсегда командой с компьютера.'));
                        break;
                    case 'Blocked':
                        echo '<span class="hidden">1</span>';
                        echo 'Закр всегда <acronym>' . htmlspecialchars($deviceInfo->doorMode, ENT_QUOTES, 'UTF-8') . '</acronym>'
                            . ' ' . HTML::image("static/images/replace2.png", array('height' => 20, 'alt' => 'Закр всегда'))
                            . HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title' => 'Дверь закрыта навсегда командой с компьютера.'));
                        break;
                    case 'Closed':
                        echo '<acronym title="' . htmlspecialchars($deviceInfo->doorMode, ENT_QUOTES, 'UTF-8') . '">Рабочий режим</acronym>';
                        break;
                    case 'Open':
                        echo '<acronym title="' . htmlspecialchars($deviceInfo->doorMode, ENT_QUOTES, 'UTF-8') . '">Рабочий режим</acronym>'
                            . ' ' . HTML::image("static/images/green-check.png", array('height' => 20, 'alt' => 'Рабочий режим'));
                        break;
                    case 'Alarm':
                        echo '<span class="hidden">1</span>';
                        echo '<acronym title="' . htmlspecialchars($deviceInfo->doorMode, ENT_QUOTES, 'UTF-8') . '">Взлом</acronym>'
                            . ' ' . HTML::image("static/images/docs-point-big2.png", array('height' => 20, 'alt' => 'Взлом'))
                            . HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title' => 'Взлом двери. Проверьте состояние геркона.'));
                        break;
                    case 'Disabled':
                        echo '<acronym title="' . htmlspecialchars($deviceInfo->doorMode, ENT_QUOTES, 'UTF-8') . '">Отключен</acronym>';
                        break;
                    case 'no':
                        echo '<acronym title="' . htmlspecialchars($deviceInfo->doorMode, ENT_QUOTES, 'UTF-8') . '">-</acronym>';
                        break;
                    default:
                        echo '<acronym title="' . htmlspecialchars($deviceInfo->doorMode, ENT_QUOTES, 'UTF-8') . '">Не определен</acronym>'
                            . ' ' . HTML::image("static/images/man-says.png", array('height' => 20, 'alt' => 'Не определен'));
                        break;
                }
                echo '</td>';

                // Колонка 17 — isBlocked
                if ($deviceInfo->onLine) {
                    $blocked_value = $deviceInfo->isBlocked ? '1' : '0';
                    echo '<td data-value="' . $blocked_value . '">';
                    echo '<span class="hidden">' . $blocked_value . '</span>';
                    echo Form::checkbox('', 1, $deviceInfo->isBlocked == true, array('disabled' => 'disabled'));
                    if ($deviceInfo->isBlocked == true) {
                        echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title' => 'Вход блокировки замкнут на "землю".'));
                    }
                    echo '</td>';
                } else {
                    echo '<td data-value="-">-</td>';
                }

                // Колонка 18 — isAlarm
                if ($deviceInfo->onLine) {
                    $alarm_value = $deviceInfo->isAlarm ? '1' : '0';
                    echo '<td data-value="' . $alarm_value . '">';
                    echo '<span class="hidden">' . $alarm_value . '</span>';
                    echo Form::checkbox('', 1, $deviceInfo->isAlarm == true, array('disabled' => 'disabled'));
                    if ($deviceInfo->isAlarm == true) {
                        echo HTML::image("static/images/attention.png", array('height' => 20, 'alt' => 'Требует внимания', 'title' => 'Вход Alarm замкнут на "землю".'));
                    }
                    echo '</td>';
                } else {
                    echo '<td data-value="-">-</td>';
                }

                // Колонка 19 — time
                echo '<td>/' . sprintf('%.3f', $deviceInfo->timeExecute) . '</td>';

                // Колонка 20 — timestamp
                echo '<td>';
                echo $deviceInfo->timeGetData
                    ? date('d.m.Y H:i:s', $deviceInfo->timeGetData)
                    : '-';

                if ($deviceInfo->timeGetData) {
                    $tt3 = time();
                    $pbvalue = intval((($deviceInfo->timeGetData + 60 * 60 * 24 - $tt3) * 100) / (60 * 60 * 24));
                    $pbvalue = max(0, min(100, $pbvalue));

                    $pbcolor = 'progress-bar-danger';
                    if ($pbvalue >= 76)                              $pbcolor = 'progress-bar-success';
                    elseif ($pbvalue >= 51 && $pbvalue < 75)         $pbcolor = 'progress-bar-info';
                    elseif ($pbvalue >= 26 && $pbvalue < 51)         $pbcolor = 'progress-bar-warning';

                    echo '<div class="progress">'
                        . '<div class="progress-bar ' . $pbcolor . '" role="progressbar" style="width: ' . $pbvalue . '%"></div>'
                        . '</div>';
                }
                echo '</td>';

                // Колонка 21 — collectAlarm
                echo '<td>';
                if ($collectAttention) {
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

        <!-- Блок кнопок в нижней части экрана -->
        <nav class="<?php echo $navClass; ?>" role="navigation">
            <div class="container">
                <?php
                $buttons = array(
                    array('synctime',        'synctime_dev',  'btn-primary', 'Синхронизация времени в контроллерах'),
                    array('settz',           'settz',         'btn-primary', 'Установить временные зоны для выбранных контроллеров'),
                    array('clear_device',    'clear_device',  'btn-danger',  'Удалить карты из выбранных точек прохода'),
                    array('load_card',       'load_card',     'btn-danger',  'Загрузить карты в выбранные точки прохода'),
                    array('checkStatus',     'checkStatus',   'btn-success', 'Чтение состояния и запись данных в базу данных.'),
                );
                foreach ($buttons as $b) {
                    printf(
                        '<button type="submit" class="btn %s sm" name="%s" value="1" title="%s"%s>%s</button>',
                        $b[2],
                        htmlspecialchars($b[0], ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($b[3], ENT_QUOTES, 'UTF-8'),
                        $disabledAttr,
                        __($b[1])
                    );
                }
                ?>
                <br>
                <button type="submit" class="btn btn-warning sm" name="cardidx_refresh" value="1"
                        title="cardidx_refresh"<?php echo $disabledAttr; ?>><?php echo __('cardidx_refresh'); ?></button>

                <?php
                $doorButtons = array(
                    'unlockdoor'     => 'Разблокировать',
                    'opendoor'       => 'Открыть 1 раз',
                    'opendooralways' => 'Открыть навсегда',
                    'lockdoor'       => 'Закрыть навсегда',
                );
                foreach ($doorButtons as $value => $label) {
                    echo Form::button('control_door', $label, array_merge(array(
                        'value' => $value,
                        'class' => 'btn btn-warning',
                        'type'  => 'submit',
                    ), $disabledArr));
                }
                ?>
            </div>
        </nav>

        <?php echo Form::close(); ?>
    </div>
</div>