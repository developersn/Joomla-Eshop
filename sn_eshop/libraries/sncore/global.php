<?php
defined('_JEXEC') or die('Restricted access');

class SNGlobal
{
    static $user = null;
    static $session = null;
    static $language = null;
    static $database = null;
    static $config = null;
    static $document = NULL;

    static $displayQueries = 0;

    public static function curl($url,$params,$jsonDecode=FALSE)
    {
        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_POSTFIELDS,json_encode($params));
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT,12); // Connection Timeout
        curl_setopt($ch, CURLOPT_TIMEOUT,17); // Curl Timeout

        $result = curl_exec($ch);

        $curlErrorNumber = curl_errno($ch);
        $curlErrorMsg = curl_error($ch);

        if($curlErrorNumber)
        {
            return array(false,'CURL ERROR : ['.$curlErrorNumber.'] '.$curlErrorMsg,array());
        }

        curl_close($ch);

        return array(true,'',$jsonDecode ? self::jsonDecode($result) : $result);
    }

    public static function jsonDecode($string,$assoc=TRUE)
    {
        return json_decode($string,$assoc);
    }

    public static function jsonEncode($array)
    {
        if(version_compare(phpversion(),'5.4.0','>'))
        {
            return json_encode($array,JSON_UNESCAPED_UNICODE);
        }
        else
        {
            //return json_encode($array,256);

            $encoded = json_encode($array);
            $unescaped = preg_replace_callback('/(?<!\\\\)\\\\u(\w{4})/', function ($matches) {
                return html_entity_decode('&#x' . $matches[1] . ';', ENT_COMPAT, 'UTF-8');
            }, $encoded);
            return $unescaped;
        }
    }

    public static function getVar($key,$default=NULL,$filter=NULL,$type='request')
    {
        $return = NULL;

        if($default !== NULL)
        {
            $return = $default;
        }

        if($type == 'request' && isset($_REQUEST[$key]))
        {
            $return = $_REQUEST[$key];
        }
        elseif($type == 'post' && isset($_POST[$key]))
        {
            $return = $_POST[$key];
        }
        elseif($type == 'get' && isset($_GET[$key]))
        {
            $return = $_GET[$key];
        }

        if($filter !== NULL)
        {
            $return = self::filterVar($return,$filter,$default);
        }

        return $return;
    }

    static function filterVar($value,$filter,$default=NULL)
    {
        if($filter == 'none')
        {
            // Not Filter
        }
        else if($filter == 'int')
        {
            $value = preg_match('/^(?:0|[1-9][0-9]*)$/',$value) ? $value : $default;
        }
        else if($filter == 'pid')
        {
            $value = preg_match('/^(?:0|-1|[1-9][0-9]*)$/',$value) ? $value : $default;
        }
        else if($filter == 'boolean')
        {
            return in_array($value,array(0,1,'0','1',FALSE,TRUE),TRUE) ? (bool)$value : $default;
        }
        else if($filter == 'time')
        {
            $value = str_replace(' ','',$value);
            $value = preg_match('/(2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9])/',$value) ? $value : $default;
        }
        else if($filter == 'jalali_date')
        {
            $value = preg_match('/^([1][3]\d{2}\/([0]\d|[1][0-2])\/([0-2]\d|[3][0-1]))$/',$value) ? $value : $default;
        }
        else if($filter == 'jalali_date_time')
        {
            $value = preg_match('/^([1][3]\d{2}\/([0]\d|[1][0-2])\/([0-2]\d|[3][0-1]) ([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9]))$/',$value) ? $value : $default;
        }
        else if($filter == 'jalali_date_ymd')
        {
            $value = preg_match('/^([1][3-4]\d{2}\/([0]\d|[1][0-2])\/([0-2]\d|[3][0-1]))$/',$value) ? $value : $default;
        }
        else if($filter == 'jalali_date_ym')
        {
            $value = preg_match('/^([1][3-4]\d{2}\/([0]\d|[1][0-2]))$/',$value) ? $value : $default;
        }
        else if($filter == 'jalali_date_y')
        {
            $value = preg_match('/^([1][3-4]\d{2})$/',$value) ? $value : $default;
        }
        else if($filter == 'escape')
        {
            $value = self::escape($value);
        }
        else if($filter == 'int_array')
        {
            $value = self::checkIntvalArray($value);
        }
        else if ($filter == 'email')
        {
            $value = filter_var($value,FILTER_VALIDATE_EMAIL) !== FALSE ? $value : $default;
        }
        else if ($filter == 'phone')
        {
            $value = (preg_match('/^[0-9]{10}$/',$value) && substr($value,0,1) == '9') ? $value : $default;
        }
        else if ($filter == 'phone_by_0')
        {
            $value = (preg_match('/^[0-9]{11}$/',$value) && substr($value,0,1) == '0') ? $value : $default;
        }
        else if($filter == 'ir_phone')
        {
            $modifyValue = trim($value);
            $modifyValue = ltrim($modifyValue,'0');
            if(substr($modifyValue,0,3) === '+98')
            {
                $modifyValue = substr($modifyValue,3);
            }
            if(substr($modifyValue,0,2) === '98')
            {
                $modifyValue = substr($modifyValue,2);
            }

            $value = (is_numeric($modifyValue) && strlen($modifyValue) == 10) ? '0'.$modifyValue : $default;
        }
        else if($filter == 'int_str')
        {
            $r = explode(',',$value);
            $value = array();
            if(!empty($r))
            {
                foreach ($r as $val)
                {
                    if(is_numeric($val))
                    {
                        $value[] = $val;
                    }
                }
            }
        }
        elseif($filter == 'hex_color')
        {
            $value = strtolower($value);
            $value = preg_match('/#([a-f0-9]{3}){2}\b/',$value) ? $value : $default;
        }
        else
        {
            die($filter.' Filter Not Exist');
        }

        return $value;
    }

    static function checkIntvalArray($array)
    {
        $return = array();
        if(empty($array))
            return $return;

        foreach ($array as $key1 => $value1)
        {
            if(is_array($value1))
            {
                foreach ($value1 as $key2 => $value2)
                {
                    if(is_numeric($value2))
                    {
                        $return[$key1][$key2] = $value2;
                    }
                }
            }
            else
            {
                if(is_numeric($value1))
                {
                    $return[$key1] = $value1;
                }
            }
        }
        return $return;
    }

    public static function setUserVariable()
    {
        if(self::$user === null)
        {
            self::$user = JFactory::getUser();
        }
    }

    public static function getCurrentUserId()
    {
        self::setUserVariable();
        $user = self::$user;

        return empty($user->id) || $user->guest ? 0 : $user->id;
    }

    public static function getCurrentUserName()
    {
        self::setUserVariable();
        $user = self::$user;

        return $user->name;
    }

    public static function getCurrentUserEmail()
    {
        self::setUserVariable();
        $user = self::$user;

        return $user->email;
    }

    public static function getCurrentUserIp()
    {
        $ipAddress = '';
        if (getenv('HTTP_CLIENT_IP'))
        {
            $ipAddress = getenv('HTTP_CLIENT_IP');
        }
        else if(getenv('HTTP_X_FORWARDED_FOR'))
        {
            $ipAddress = getenv('HTTP_X_FORWARDED_FOR');
        }
        else if(getenv('HTTP_X_FORWARDED'))
        {
            $ipAddress = getenv('HTTP_X_FORWARDED');
        }
        else if(getenv('HTTP_FORWARDED_FOR'))
        {
            $ipAddress = getenv('HTTP_FORWARDED_FOR');
        }
        else if(getenv('HTTP_FORWARDED'))
        {
            $ipAddress = getenv('HTTP_FORWARDED');
        }
        else if(getenv('REMOTE_ADDR'))
        {
            $ipAddress = getenv('REMOTE_ADDR');
        }

        if(strpos($ipAddress,',') !== false)
        {
            $ipAddress = explode(',',$ipAddress);
            $ipAddress = $ipAddress[0];
        }

        $ipAddress = trim($ipAddress);
        return $ipAddress;
    }

    static function postData($url,$data,$time=2500)
    {
        $return = '';

        $return .= '
			<script language="javascript" type="text/javascript">    
				function requestTransaction() 
				{
					var form = document.createElement("form");
					form.setAttribute("method", "POST");
					form.setAttribute("action", "' . $url . '");         
					form.setAttribute("target", "_self");
		';
        foreach ($data as $key => $value)
        {
            $return .= '
					var hiddenField = document.createElement("input");              
					hiddenField.setAttribute("name","'.$key.'");
					hiddenField.setAttribute("value","'.$value.'");
					form.appendChild(hiddenField);
			';
        }
        $return .= '
					document.body.appendChild(form);         
					form.submit();
					document.body.removeChild(form);
				}
				setTimeout(function(){
					requestTransaction();
				},'.$time.');
			</script>
		';

        return $return;
    }


    public static function setSessionVariable()
    {
        if(self::$session === NULL)
        {
            self::$session = JFactory::getSession();
        }
    }

    public static function getSessionPrefix()
    {
        return '';
    }

    public static function setSession($name,$value)
    {
        self::setSessionVariable();
        $prefix = self::getSessionPrefix();
        return self::$session->set($prefix.$name,$value);
    }

    public static function getSession($name)
    {
        self::setSessionVariable();
        $prefix = self::getSessionPrefix();
        return self::$session->get($prefix.$name);
    }

    public static function clearSession($name)
    {
        self::setSessionVariable();
        $prefix = self::getSessionPrefix();
        return self::$session->clear($prefix.$name);
    }

    public static function redirect($url)
    {
        JFactory::getApplication()->redirect($url,'','error');
        die();
    }

    public static function loadView($file,$data=array())
    {
        if(!file_exists($file))
        {
            return '';
        }

        if(!empty($data))
        {
            compact($data);
        }

        ob_start();
        include $file;
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    public static function setLanguageVariable()
    {
        if(self::$language === NULL)
        {
            self::$language = JFactory::getLanguage();
        }
    }

    public static function getLanguageTag($strtolower=FALSE)
    {
        self::setLanguageVariable();
        $lang = self::$language;

        $currentLang = $lang->getTag();

        return $strtolower ? strtolower($currentLang) : $currentLang;
    }

    public static function getLanguageName()
    {
        self::setLanguageVariable();
        $lang = self::$language;

        return $lang->getName();
    }

    public static function loadLanguage($extension,$basePath=NULL,$langTag=NULL,$reload=FALSE)
    {
        self::setLanguageVariable();
        $lang = self::$language;

        if($langTag === NULL)
        {
            $langTag = self::getLanguageTag();
        }

        if($basePath === NULL)
        {
            $basePath = self::isBackend() ? JPATH_ADMINISTRATOR : JPATH_SITE;
        }

        $lang->load($extension,$basePath,$langTag,$reload);
    }

    public static function isBackend()
    {
        return JFactory::getApplication()->isAdmin();
    }

    public static function setDatabaseVariable()
    {
        if(self::$database === NULL)
        {
            self::$database = JFactory::getDbo();
        }
    }

    public static function escape($input)
    {
        self::setDatabaseVariable();

        $db = self::$database;
        return $db->escape($input);
    }

    static function tablesExist($tables=array())
    {
        self::setDatabaseVariable();

        $db = self::$database;
        $allTables = $db->getTableList();

        foreach ($tables as $table)
        {
            $table = str_replace('#__',self::getConfig('dbprefix'),$table);
            if(!in_array($table,$allTables))
            {
                return false;
            }
        }
        return TRUE;
    }

    public static function dbQuery($query)
    {
        self::setDatabaseVariable();
        self::displayQuery($query);

        $db = self::$database;
        $db->setQuery($query);
        $db->Query();

        return $db->getAffectedRows();
    }

    public static function select($table,$columns='*',$where='',$select=1,$order='',$asc='',$start='',$limit='',$groupby='',$join='',$use_align=FALSE)
    {
        self::setDatabaseVariable();

        $db = self::$database;
        $table_prefix = '';
        $align = $use_align == TRUE ? ' AS self' : '';
        $prefix_column = $use_align == TRUE ? 'self.' : '';
        $join = !empty($join) ? ' '.$join : '';

        $query = "SELECT ".$columns." FROM `#__".$table_prefix.$table."`".$align.$join." WHERE 1 $where ";
        if(!empty($groupby))
        {
            $query .= " GROUP BY {$prefix_column}$groupby ";
        }
        if(!empty($order))
        {
            if(!empty($asc) && in_array($asc,array('ASC','DESC')))
            {
                $order = " ORDER BY `$order` $asc ";
            }
            else
            {
                $order = " ORDER BY `$order` ASC ";
            }
        }

        $query .= $order;
        $start = (int) $start;
        if($start >= 0 && !empty($limit))
        {
            $query .= " LIMIT $start,$limit";
        }

        return self::selectByQuery($query,$select);
    }

    public static function selectByQuery($query,$select=1)
    {
        self::setDatabaseVariable();
        self::displayQuery($query);

        $db = self::$database;
        $db->setQuery($query);



        if($select == 1)
        {
            return $db->loadAssocList();
        }
        elseif($select == 2)
        {
            return $db->loadAssoc();
        }
        elseif($select == 3)
        {
            return $db->loadObjectList();
        }
        elseif($select == 4)
        {
            return $db->loadObject();
        }
    }

    public static function insert($query)
    {
        self::setDatabaseVariable();
        self::displayQuery($query);

        $db = self::$database;
        $db->setQuery($query);
        $db->Query();

        return $db->insertid();
    }

    public static function insertByArray($table,$data)
    {
        $columnsQuery = $valuesQuery = '';
        foreach ($data as $key => $value)
        {
            $columnsQuery .= "`".$key."`,";
            $valuesQuery .= "'".self::escape($value)."',";
        }
        $columnsQuery = rtrim($columnsQuery,',');
        $valuesQuery = rtrim($valuesQuery,',');

        if(empty($columnsQuery) || empty($valuesQuery))
        {
            return false;
        }

        $query = "INSERT INTO `#__".$table."` (".$columnsQuery.") VALUES (".$valuesQuery.")";
        return self::insert($query);
    }

    public static function update($query)
    {
        self::setDatabaseVariable();
        self::displayQuery($query);

        $db = self::$database;
        $db->setQuery($query);
        $db->Query();

        return $db->getAffectedRows();
    }

    public static function updateByArray($table,$data,$where)
    {
        if(empty($data))
        {
            return false;
        }

        $updateQuery = '';
        foreach ($data as $key => $value)
        {
            $updateQuery .= "`".$key."`='".self::escape($value)."',";
        }
        $updateQuery = rtrim($updateQuery,',');

        if(empty($updateQuery))
        {
            return false;
        }

        $query = "UPDATE `#__".$table."` SET ".$updateQuery.(!empty($where) ? ' WHERE '.$where : '');
        return self::update($query);
    }

    public static function delete($query)
    {
        self::setDatabaseVariable();
        self::displayQuery($query);

        $db = self::$database;
        $db->setQuery($query);
        $db->Query();

        return $db->getAffectedRows();
    }

    public static function displayQuery($query)
    {
        if(self::$displayQueries)
        {
            $query = str_replace('#__',self::getConfig('dbprefix'),$query);
            self::$queries[] = $query;

            dd($query,0);
        }
    }

    public static function setConfigVariable()
    {
        if(self::$config === NULL)
        {
            self::$config = JFactory::getConfig();
        }
    }

    public static function getConfig($key)
    {
        self::setConfigVariable();

        $config = self::$config;

        return $config->get($key);
    }

    public static function setDocumentVariable()
    {
        if(self::$document === NULL)
        {
            self::$document = JFactory::getDocument();
        }
    }

    public static function addScript($path)
    {
        self::setDocumentVariable();
        self::$document->addScript($path);
    }

    public static function addStyle($path)
    {
        self::setDocumentVariable();
        self::$document->addStyleSheet($path);
    }

    public static function addCssDeclaration($content)
    {
        self::setDocumentVariable();
        self::$document->addStyleDeclaration($content);
    }

    public static function addScriptDeclaration($content)
    {
        self::setDocumentVariable();
        self::$document->addScriptDeclaration($content);
    }

    public static function getAllScripts()
    {
        self::setDocumentVariable();
        return self::$document->_scripts;
    }

    public static function getDeclarationScripts()
    {
        self::setDocumentVariable();
        return self::$document->_script;
    }

    public static function getAllStyles()
    {
        self::setDocumentVariable();
        return self::$document->_styleSheets;
    }

    public static function getDeclarationStyles()
    {
        self::setDocumentVariable();
        return self::$document->_style;
    }

    public static function setMetaData($name,$content)
    {
        self::setDocumentVariable();
        self::$document->setMetaData($name,$content);
    }

    static function indexArray($arr,$index='id',$val='')
    {
        if(empty($arr))
        {
            return array();
        }

        $return = array();
        foreach ($arr as $key => $value)
        {
            if(!key_exists($value[$index],$return))
            {
                if(!empty($val) && $val != $value[$index])
                {
                    continue;
                }
                $return[$value[$index]] = $value;
            }

        }
        return $return;
    }
}