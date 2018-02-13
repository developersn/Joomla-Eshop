<?php
defined('_JEXEC') or die('Restricted access');

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

if(!defined('DS'))
{
    define('DS',DIRECTORY_SEPARATOR);
}

class plgSystemSn_eshopInstallerScript
{
    function preflight($type,$parent)
    {
        if(!JFolder::exists(JPATH_ADMINISTRATOR .DS. 'components' .DS. 'com_eshop'))
        {
            Jerror::raiseWarning(null,'لطفا ابتدا افزونه ای-شاپ را نصب نمایید.');
            return false;
        }
    }

    function postflight($type,$parent)
    {
        /* Enable Plugin */
        $database = JFactory::getDBO();
        $query = "UPDATE `#__extensions` SET `enabled` = 1 WHERE `element` = 'sn_eshop'";
        $database->setQuery($query);
        $database->query();

        /* Move Libraries Files */
        $from = JPATH_ROOT .DS. 'plugins' .DS. 'system' .DS. 'sn_eshop' .DS. 'libraries' .DS;
        $to = JPATH_ROOT .DS. 'libraries' .DS;
        JFolder::copy($from,$to,'',true);

        if(JFolder::exists($from))
        {
            JFolder::delete($from);
        }

        $mainRoot = JPATH_ROOT .DS. 'plugins' .DS. 'system' .DS. 'sn_eshop' .DS. 'files' .DS;
        $copyRoot = JPATH_ROOT .DS. 'components' .DS. 'com_eshop' .DS. 'plugins' .DS. 'payment' .DS;
        if(JFolder::exists($mainRoot))
        {
            JFolder::copy($mainRoot,$copyRoot,'',true);
            JFolder::delete($mainRoot);
        }

        jimport('sncore.include');

        if(strtolower($type) == 'install')
        {
            $query = "INSERT INTO `#__eshop_payments` 
		          (`name`, `title`, `author`, `creation_date`, `copyright`, `license`, `author_email`, `author_url`, `version`, `description`, `params`, `ordering`, `published`) VALUES 
				  ('os_sneshop', 'Sn Eshop', 'Sn Eshop', '".date('Y-m-d H:i:s')."', 'CopyRight Sn Eshop 2017', 'Please do not copy.', '', '', '1.0.0', 'Sn Eshop', NULL, 9, 1);";

            SNGlobal::insert($query);
        }
    }

    function uninstall($parent)
    {
        jimport('sncore.include');

        $root = JPATH_ROOT .DS. 'components' .DS. 'com_eshop' .DS. 'plugins' .DS. 'payment';
        if(JFile::exists($root .DS. 'os_sneshop.php'))
        {
            JFile::delete($root .DS. 'os_sneshop.php');
        }
        if(JFile::exists($root .DS. 'os_sneshop.xml'))
        {
            JFile::delete($root .DS. 'os_sneshop.xml');
        }

        $query = "DELETE FROM `#__eshop_payments` WHERE `name`='os_sneshop'";
        SNGlobal::delete($query);
    }
}