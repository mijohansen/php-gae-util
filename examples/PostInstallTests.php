<?php
/**
 * Created by PhpStorm.
 * User: michael
 * Date: 02/03/2018
 * Time: 11:31
 */



require_once dirname(__FILE__)."/../vendor/autoload.php";
use GaeUtil\PostInstall;

PostInstall::cleanGoogleApiClasses(null);