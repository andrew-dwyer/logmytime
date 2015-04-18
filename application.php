#!/usr/bin/env php
<?php
// application.php

require __DIR__.'/vendor/autoload.php';

#use Logmytime;
use Dwyera\Logmytime;
#use Dwyera;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new Logmytime());
$application->run();