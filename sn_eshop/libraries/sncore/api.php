<?php
defined('_JEXEC') or die('Restricted access');

class SNApi
{
    const SN_CURRENCY_RIAL = 1;
    const SN_CURRENCY_TOMAN = 0;

    static $errors = array(
        -1 => 'پارامترهای ارسالی برای متد مورد نظر ناقص یا خالی هستند . پارمترهای اجباری باید ارسال گردد',
        -2 => 'دسترسی api برای شما مسدود است',
        -6 => 'عدم توانایی اتصال به گیت وی بانک از سمت وبسرویس',
        -9 => 'خطای ناشناخته',
        -20 => 'پین نامعتبر',
        -21 => 'ip نامعتبر',
        -22 => 'مبلغ وارد شده کمتر از حداقل مجاز میباشد',
        -23 => 'مبلغ وارد شده بیشتر از حداکثر مبلغ مجاز هست',
        -24 => 'مبلغ وارد شده نامعتبر',
        -26 => 'درگاه غیرفعال است',
        -27 => 'آی پی مسدود شده است',
        -28 => 'آدرس کال بک نامعتبر است ، احتمال مغایرت با آدرس ثبت شده',
        -29 => 'آدرس کال بک خالی یا نامعتبر است',
        -30 => 'چنین تراکنشی یافت نشد',
        -31 => 'تراکنش ناموفق است',
        -32 => 'مغایرت مبالغ اعلام شده با مبلغ تراکنش',
        -35 => 'شناسه فاکتور اعلامی order_id نامعتبر است',
        -36 => 'پارامترهای برگشتی بانک bank_return نامعتبر است',
        -38 => 'تراکنش برای چندمین بار وریفای شده است',
        -39 => 'تراکنش در حال انجام است',
    );

    static $requestUrl = 'aHR0cHM6Ly9kZXZlbG9wZXJhcGkubmV0L2FwaS92MS9yZXF1ZXN0';
    static $verifyUrl = 'aHR0cHM6Ly9kZXZlbG9wZXJhcGkubmV0L2FwaS92MS92ZXJpZnk=';
    
    /*
        $data = array(
            'pin'=> '',
            'price'=> 1000,                 // toman
            'callback'=> '',
            'order_id'=> 1,
            'email'=> 'buyer@example.org',  //optional
            'description'=> 'test',         //optional
            'name'=> 'Reza',                //optional
            'mobile'=> '9181111111',        //optional
            'ip'=> '192.168.1.1',           //optional
            'callback_type'=>2              //optional
        )
    */
    static function request($data,$sendPayerInfo,$extension)
    {
        if(!$sendPayerInfo)
        {
            unset($data['email'],$data['description'],$data['name'],$data['mobile'],$data['ip']);
        }
        else
        {
            if(!isset($data['name']))
            {
                $data['name'] = SNGlobal::getCurrentUserName();
            }
            if(!isset($data['email']))
            {
                $data['email'] = SNGlobal::getCurrentUserEmail();
            }
            if(!isset($data['ip']))
            {
                $data['ip'] = SNGlobal::getCurrentUserIp();
            }
        }

        if(!isset($data['callback_type']))
        {
            $data['callback_type'] = 2;
        }

        $url = base64_decode(self::$requestUrl);
        list($status,$msg,$response) = SNGlobal::curl($url,$data,true);

        if($status != true)
        {
            $return = array(false,$msg,array());
        }
        else
        {
            if(isset($response['result'],$response['au']) && $response['result'] == 1 && !empty($response['au']))
            {
                $return = array(true,$msg,$response);
            }
            else
            {
                $errorMsg = (isset($response['result']) && isset(self::$errors[$response['result']])) ? self::$errors[$response['result']] : '';

                $errorMsg = empty($errorMsg) && isset($response['msg']) ? $response['msg'] : $errorMsg;
                $return = array(false,$errorMsg,$response);
            }
        }

        if($return[0] !== true)
        {
            self::setLog('[action : request] - [order-id : '.$data['order_id'].'] - [message : '.$return[1].']',$extension);
        }

        return $return;
    }

    /*
        $data = array (
            'pin' => 'gtd1u16d960522r810',
            'price' => 1000,
            'order_id' => 1,
            'au' => '4531u16g14d960523r4166',
            'bank_return' => array (
                'SaleReferenceId' => '20170814113803',
                'ResCode' => 'random_res_code_8',
                'card_pan' => '1111111111111111',
                'State' => '1',
            ),
        );
     */
    static function verify($data,$extension)
    {
        $url = base64_decode(self::$verifyUrl);
        list($status,$msg,$response) = SNGlobal::curl($url,$data,true);

        if($status != true)
        {
            $return = array(false,$msg,array());
        }
        else
        {
            if(isset($response['result'],$response['au']) && $response['result'] == 1 && !empty($response['au']))
            {
                $return = array(true,$msg,$response);
            }
            else
            {
                $errorMsg = (isset($response['result']) && isset(self::$errors[$response['result']])) ? self::$errors[$response['result']] : '';
                $errorMsg = empty($errorMsg) && isset($response['msg']) ? $response['msg'] : $errorMsg;
                $return = array(false,$errorMsg,$response);
            }
        }

        if($return[0] !== true && isset($response[2]['result']) && $response[2]['result'] != 0)
        {
            self::setLog('[action : verify] - [order-id : '.$data['order_id'].'] - [message : '.$return[1].']',$extension);
        }

        return $return;
    }

    public static function modifyPrice($price,$currency)
    {
        if($currency == self::SN_CURRENCY_RIAL)
        {
            $price = $price/10;
        }
        elseif($currency == self::SN_CURRENCY_TOMAN)
        {

        }

        $price = ceil($price);
        return $price;
    }

    public static function setData($newData)
    {
        $oldData = self::getData();
        $data = array_merge($oldData,$newData);
        SNGlobal::setSession('sn_invoice',$data);
    }

    public static function getData()
    {
        $data = SNGlobal::getSession('sn_invoice');
        return (!empty($data) && is_array($data)) ? $data : array();
    }

    public static function clearData()
    {
        $data = SNGlobal::clearSession('sn_invoice');
    }

    public static function setLog($log,$extension)
    {
        $logPath = SNGlobal::getConfig('log_path');
        if(!JFolder::exists($logPath))
        {
            JFolder::create($logPath);
        }

        $file = $logPath .DS. 'sn_'.$extension.'.php';
        $content = '';
        if(JFile::exists($file))
        {
            $content = JFile::read($file).PHP_EOL;
        }
        else
        {
            $content = '<?php echo die("Forbidden"); ?>'.PHP_EOL.PHP_EOL;
        }

        date_default_timezone_set('Asia/Tehran');
        $content .= '['.date('Y/m/d H:i:s').'] - ' . $log;
        JFile::write($file,$content);
    }
}
