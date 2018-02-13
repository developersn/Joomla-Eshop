<?php
defined('_JEXEC') or die();

jimport('sncore.include');

class os_sneshop extends os_payment
{
    public $snParams = array();

	public function __construct($params)
	{
        SNGlobal::loadLanguage('plg_system_sn_eshop',JPATH_ADMINISTRATOR);

        $config = array(
            'type' => 0,
            'show_card_type' => false,
            'show_card_holder_name' => false
        );

        $this->snParams = array(
            'pin' => $params->get('sn_pin'),
            'currency' => $params->get('sn_currency'),
            'send_payer_info' => $params->get('sn_send_payer_info'),
        );

        parent::__construct($params,$config);
	}

	public function processPayment($data)
	{
		if($data['payment_method'] != 'os_sneshop')
		{
		    return false;
		}

		$orderId = $data['order_id'];
        $amount = $data['total'];
        $email = !empty($data['email']) ? $data['email'] : '';
        $backUrl = JURI::base() . 'index.php?option=com_eshop&task=checkout.verifyPayment&payment_method=os_sneshop&orderId='.$data['order_id'];

        $pin = $this->snParams['pin'];
        $currency = $this->snParams['currency'];
        $sendPayerInfo = $this->snParams['send_payer_info'];

        $amount = SNApi::modifyPrice($amount,$currency);

        $data = array(
            'pin'=> $pin,
            'price'=> $amount,
            'callback'=> $backUrl,
            'order_id'=> $orderId,
            'email'=> $email,
            'description'=> '',
            'mobile'=> '',
        );

        list($status,$msg,$resultData) = SNApi::request($data,$sendPayerInfo,'eshop');

        if($status == true)
        {
            $formDetails = $resultData['form_details'];

            $data['bank_callback_details'] = $resultData['bank_callback_details'];
            $data['au'] = $resultData['au'];

            SNApi::clearData();
            SNApi::setData($data);

            $this->url = $formDetails['action'];
            $this->data = $formDetails['fields'];
            $this->submitPost();
            return;
        }

        $error = '<h5>'.$msg.'</h5>';
        $app = JFactory::getApplication();
        $app->enqueueMessage($error,'error');
	}

	function verifyPayment()
	{
        $app = JFactory::getApplication();

        $orderId = SNGlobal::getVar('orderId','0','int');
        $au = SNGlobal::getVar('au','','none','request');
        $paymentMethod = SNGlobal::getVar('payment_method','0','none');
        $sessionData = SNApi::getData();
        
		if($paymentMethod != 'os_sneshop')
		{
		    return false;
		}

		if(!empty($orderId) && !empty($sessionData) && $sessionData['order_id'] == $orderId)
        {
            $row = JTable::getInstance('Eshop','Order');
            $row->load($orderId);

            if($row->order_status_id == EshopHelper::getConfigValue('complete_status_id'))
            {
                return false;
            }

            $bankData = array();
            foreach (!empty($sessionData['bank_callback_details']['params']) ? $sessionData['bank_callback_details']['params'] : array() as $bankParam)
            {
                $bankData[$bankParam] = !empty($_REQUEST[$bankParam]) ? $_REQUEST[$bankParam] : '';
            }

            $data = array (
                'pin' => $sessionData['pin'],
                'price' => $sessionData['price'],
                'order_id' => $sessionData['order_id'],
                'au' => $au,
                'bank_return' => $bankData,
            );

            list($status,$msg,$resultData) = SNApi::verify($data,'eshop');

            if($status == true)
            {
                $bankAu = !empty($resultData['bank_au']) ? $resultData['bank_au'] : $au;

                $currency = new EshopCurrency();
                $row->transaction_id = $bankAu;
                $row->order_status_id = EshopHelper::getConfigValue('complete_status_id');
                $row->store();
                EshopHelper::completeOrder($row);
                if(EshopHelper::getConfigValue('order_alert_mail'))
                {
                    EshopHelper::sendEmails($row);
                }

                /* empty cart */
                $cart = new EshopCart();
                $products = $cart->getCartData();

                foreach (!empty($products) ? $products : array() as $key => $value)
                {
                    $cart->remove($value['key']);
                }

                $successMsg = '<h5>'.JText::_('SN_PAID_TRANSACTION').'</h5>';
                $successMsg = str_replace('{REF}',$bankAu,$successMsg);

                $link = JRoute::_(JUri::root().'index.php?option=com_eshop&view=checkout&layout=complete',false);
                $app->redirect($link,$successMsg,$msgType='Message');
                return true;
            }

        }

        $errorMsg = '<h5>'.JText::_('SN_UNPAID_TRANSACTION').'</h5>';
        $link = JRoute::_(JUri::root().'index.php?option=com_eshop&view=checkout&layout=cancel',false);
        $app->redirect($link,$errorMsg,'Error');
        return false;
    }
}