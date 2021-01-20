<?php

namespace Yandex\Metrika;

use Bitrix\Main\Diag\Debug;
use \Datetime;

class YandexMetrikaApi
{
    private $service;
    private $date;
    private $logsPath = '/local/modules/yandex.metrika/logs';
    
    public function __construct($token = '')
    {
        $this->service = new YandexMetrikaRequest($token);
        $this->date = new DateTime();
    }
    
    public function getTrafficSourceByOrderId($orderId)
    {
        $resource = "preset=purchase&filters=ym:s:purchaseID==".$orderId."&attribution=lastsign&date1=30daysAgo";
        return $response = $this->service->getResponse($resource);
    }

    public function parse($data)
    {
        $trafficInfoArr = [];
        $jsonData = json_decode($data, true);
        if (!$jsonData || empty($jsonData['data'])) {
            return;
        }
        $trafficInfoArr['trafficSource'] = $jsonData['data'][0]['dimensions'][0]['name'];
        $trafficInfoArr['$sourceEngine'] = $jsonData['data'][0]['dimensions'][1]['name'];
        return $trafficInfoArr;
    }
    
    public function addTrafficSourceByOrderId($orderId, $trafficSource, $sourceEngine)
    {
        $result = '';
        $arFields = array(
            "ORDER_ID" => $orderId,
            "ORDER_PROPS_ID" => 30,
            "NAME" => "Источник заказа",
            "CODE" => "TRAFFIC_SOURCE",
            "VALUE" => $trafficSource . " - " . $sourceEngine,
        );

        if($arProp = \CSaleOrderProps::GetList(array(), array('CODE' => $arFields["CODE"]))->Fetch()) {
            $db_vals = \CSaleOrderPropsValue::GetList(
                array(),
                array(
                    "ORDER_ID" => $orderId,
                    "ORDER_PROPS_ID" => $arProp["ID"]
                )
            );
            if ($arVals = $db_vals->Fetch()) {
                $result = \CSaleOrderPropsValue::Update($arVals["ID"], $arFields);
            } else {
                $result = \CSaleOrderPropsValue::Add($arFields);
            }
            if(!$result) {        
                $strError = $this->getStrError();
                $this->logTofile($strError);
            } 
        }
    }
    
    public function getStrError()
    {
        global $APPLICATION;
        if($ex = $APPLICATION->GetException()) {
            return $ex->GetString();
       }
    }
    
    public function logTofile($result)
    {
        Debug::writeToFile($result, "", sprintf('%s/trafficInfoToOrder-%s.log', $this->logsPath, $this->date->format('d.m.Y')));
    }

    public function run($orderId)
    {
        $data = $this->getTrafficSourceByOrderId($orderId);
        $parseData = $this->parse($data);
        if(!empty($parseData)) {
            $this->addTrafficSourceByOrderId($orderId, $parseData['trafficSource'], $parseData['$sourceEngine']);
        }
    }
}