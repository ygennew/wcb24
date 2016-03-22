<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Отправка данных лида прямым запросом
 *
 * @param $lead_order - № заказа
 * @param $total - сумма
 * @param $checkout - данные по оплате, доставке и способам
 * @return bool|string
 */
function wcb24_send_lead($lead_order, $total, array $checkout)
{
	$result = false;

// Формируем данные для отправки в Битрикс
// Дата вместо названия лида
	date_default_timezone_set('Etc/GMT-3');
	$current_data = date('dmyHi');

// Забираем № roistat из кукки
	$roistat = isset($_COOKIE['roistat_visit']) ? $_COOKIE['roistat_visit'] : 0;

	// get lead data from the form
	$postData = array(
		'TITLE'             => $current_data,
		'NAME'              => $checkout['billing_first_name'],
		'LAST_NAME'			=> $checkout['billing_last_name'],
		'PHONE_MOBILE'      => $checkout['billing_phone'],
		'UF_CRM_1458036309' => $roistat,
		'UF_CRM_1458036199' => wp_title(), // Посадочная
		'SOURCE_ID'         => 'WEB', // источник
//			'UF_CRM_1458036597' => $lead_form, // Лид-форма
		'UF_CRM_1458036686' => $lead_order, // Заказ
		'OPPORTUNITY' 		=> $total,

		// Новые поля
		// @todo Скорректировать после добавления к лиду в Б24
		'UF_CRM_PAYMENT_METHOD' => $checkout['payment_method'],
		'UF_CRM_SHIPPING_METHOD' => $checkout['shipping_method'],
		'UF_CRM_BILLING_FIRST_NAME' => $checkout['billing_first_name'],
		'UF_CRM_BILLING_LAST_NAME' => $checkout['billing_last_name'],
		'UF_CRM_BILLING_COMPANY' => $checkout['billing_company'],
		'UF_CRM_BILLING_EMAIL' => $checkout['billing_email'],
		'UF_CRM_BILLING_PHONE' => $checkout['billing_phone'],
		'UF_CRM_BILLING_COUNTRY' => $checkout['billing_country'],
		'UF_CRM_BILLING_ADDRESS_1' => $checkout['billing_address_1'],
		'UF_CRM_BILLING_ADDRESS_2' => $checkout['billing_address_2'],
		'UF_CRM_BILLING_CITY' => $checkout['billing_city'],
		'UF_CRM_BILLING_STATE' => $checkout['billing_state'],
		'UF_CRM_BILLING_POSTCODE' => $checkout['billing_postcode'],
		'UF_CRM_ORDER_COMMENTS' => $checkout['order_comments'],
		'UF_CRM_TOTAL' => $total,

	);

	// append authorization data
	if (defined('CRM_AUTH')) {
		$postData['AUTH'] = CRM_AUTH;
	} else {
		$postData['LOGIN'] = WCB24_CRM_LOGIN;
		$postData['PASSWORD'] = WCB24_CRM_PASSWORD;
	}

	// open socket to CRM
	$fp = fsockopen("ssl://" . WCB24_CRM_HOST, WCB24_CRM_PORT, $errno, $errstr, 30);
	if ($fp) {
		// prepare POST data
		$strPostData = '';
		foreach ($postData as $key => $value) {
			$strPostData .= ($strPostData == '' ? '' : '&') . $key . '=' . urlencode($value);
		}

		// prepare POST headers
		$str = "POST " . WCB24_CRM_LEAD_PATH . " HTTP/1.0\r\n";
		$str .= "Host: " . WCB24_CRM_HOST . "\r\n";
		$str .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$str .= "Content-Length: " . strlen($strPostData) . "\r\n";
		$str .= "Connection: close\r\n\r\n";

		$str .= $strPostData;

		// send POST to CRM
		fwrite($fp, $str);

		// get CRM headers
		$result = '';
		while (!feof($fp)) {
			$result .= fgets($fp, 128);
		}
		fclose($fp);

		// проверка отправки, выводим на экран
		$response = explode("\r\n\r\n", $result);

//		error_log('wcb24_send_lead: Response is '.print_r($response, true));

		$resp = preg_replace("/'/", '"', $response[1]);
		$resp = json_decode($resp, true);
		$jle = json_last_error();

		// Ошибка декодирования json ответа
		if($jle !== 0) {
			error_log("wcb24_send_lead: Error response decoding[$jle]: ".print_r($resp, true));
			return false;
		}

		// Статус ответа не 201
		if($resp['error'] != 201) {
			error_log('wcb24_send_lead: Error response status: '.print_r($resp, true));
			return false;
		}

		$result = $resp['ID'];

	} else {
		error_log('wcb24_send_lead: Connection Failed! ' . $errstr . ' (' . $errno . ')');
	}

	return $result;
}

function wcb24_rest_send_lead($lead_order, $total, array $checkout)
{
	$result = false;

	$rest = new \WCB24\REST();

//	$rest->authenticate();

	return $result;
}