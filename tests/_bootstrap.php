<?php
// This is global bootstrap for autoloading
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');

Yii::setAlias('tests', dirname(__DIR__) . '/tests');