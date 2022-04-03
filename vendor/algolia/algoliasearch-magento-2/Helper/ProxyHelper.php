<?php

namespace Algolia\AlgoliaSearch\Helper;

class ProxyHelper
{
    const PROXY_URL = 'https://magento-proxy.algolia.com/';

    const PROXY_URL_PARAM_GET_INFO = 'get-info/';
    const PROXY_URL_PARAM_POST_DATA = 'hs-push/';
    const PROXY_URL_PARAM_TRACK_EVENT = 'event/';

    const INFO_TYPE_EXTENSION_SUPPORT = 'extension_support';
    const INFO_TYPE_QUERY_RULES = 'query_rules';
    const INFO_TYPE_ANALYTICS = 'analytics';
    const INFO_TYPE_PLAN_LEVEL = 'plan_level';
    const INFO_TYPE_PERSONALIZATION = 'personalization';
    const INFO_TYPE_ALL = 'all';

    /** @var ConfigHelper */
    private $configHelper;

    private $allClientData;

    /** @param ConfigHelper $configHelper */
    public function __construct(ConfigHelper $configHelper)
    {
        $this->configHelper = $configHelper;
    }

    /**
     * @param string $type
     *
     * @return string|array
     */
    public function getInfo($type)
    {
        $appId = $this->configHelper->getApplicationID();
        $apiKey = $this->configHelper->getAPIKey();

        $token = $appId . ':' . $apiKey;
        $token = base64_encode($token);
        $token = str_replace(["\n", '='], '', $token);

        $params = [
            'appId' => $appId,
            'token' => $token,
        ];

        if ($type !== self::INFO_TYPE_EXTENSION_SUPPORT) {
            $params['type'] = $type;
        }

        $info = $this->postRequest($params, self::PROXY_URL_PARAM_GET_INFO);

        if ($info) {
            $info = json_decode($info, true);
        }

        return $info;
    }

    /**
     * @param string $appId
     * @param string $eventName
     * @param array $data
     */
    public function trackEvent($appId, $eventName, $data)
    {
        $params = [
            'appId' => $appId,
            'eventName' => $eventName,
            'data' => $data,
        ];

        $this->postRequest($params, self::PROXY_URL_PARAM_TRACK_EVENT);
    }

    public function getClientConfigurationData()
    {
        if (!$this->allClientData) {
            $this->allClientData = $this->getInfo(self::INFO_TYPE_ALL);
        }

        return $this->allClientData;
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function pushSupportTicket($data)
    {
        $result = $this->postRequest($data, self::PROXY_URL_PARAM_POST_DATA);

        return $result === 'true';
    }

    /**
     * @param $data
     * @param $proxyMethod
     *
     * @return bool|string
     */
    private function postRequest($data, $proxyMethod)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::PROXY_URL . $proxyMethod);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }
}
