<?php
defined('_JEXEC') or die('Restricted access');

if(!defined('DS'))
{
    define('DS',DIRECTORY_SEPARATOR);
}

$root = dirname(__FILE__);

/**
 * Joomla classes
 */
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

/**
 * Sn classes
 */

include_once $root .DS. 'global.php';
include_once $root .DS. 'api.php';