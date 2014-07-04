<?php

if (!is_file($autoloadFile = __DIR__.'/../vendor/autoload.php')) {
    throw new \LogicException('Could not find autoload.php in vendor/. Did you run "composer install --dev"?');
}

require $autoloadFile;

//set_include_path(__DIR__ . '/../vendor/phing/phing/classes' . PATH_SEPARATOR . __DIR__.'/../Behavior' . PATH_SEPARATOR . get_include_path());

//require_once(__DIR__.'/../Behavior/C33sPropelBehaviorAttachable.php');

$class = new \ReflectionClass('EventDispatcherBehavior');
$builder = new \PropelQuickBuilder();
$builder->getConfig()->setBuildProperty('behavior.event_dispatcher.class', $class->getFileName());
$builder->setSchema(file_get_contents(__DIR__.'/../Resources/config/schema.xml'));
$builder->setClassTargets(array('tablemap', 'peer', 'object', 'query'));
$builder->build();
