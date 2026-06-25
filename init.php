<?php defined('SYSPATH') or die('No direct script access.');
defined('DEV_VERSION') OR define('DEV_VERSION', '2.1.2');

Kohana::$config->load('menu')
    ->set('dev', array(
        'title' => 'Очередь загрузки',
        'url' => 'dev/load_order',
        'icon' => 'fa-cog',
        'order' => 10,
       
    ));
	
Kohana::$config->load('menu')
    ->set('device', array(
        'title' => 'Контроллеры',
        'url' => 'dev/load',
        'icon' => 'fa-cog',
        'order' => 9,
         'show' => array(
            //'logged_in' => true  // Только для авторизованных
            'roles' => array('admin', 'moderator')  // Доступно для админов и модераторов
        ),
		
       
    ));