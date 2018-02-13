<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');

if(!defined('DS'))
{
    define('DS',DIRECTORY_SEPARATOR);
}

class plgSystemSn_eshop extends JPlugin
{
	function __construct(&$subject,$config)
	{
		parent::__construct($subject,$config);
	}
}