<?php
namespace Weikit\Wechat\Sdk;

use InvalidArgumentException;

/**
 * @property \Weikit\Wechat\Sdk\Components\Menu $menu 自定义菜单
 * @property \Weikit\Wechat\Sdk\Components\Message $message 客服消息
 * @property \Weikit\Wechat\Sdk\Components\MassMessage $massMessage 高级群发接口
 * @property \Weikit\Wechat\Sdk\Components\Template $template 模板消息接口
 * @property \Weikit\Wechat\Sdk\Components\Oauth $oauth 网页授权
 * @property \Weikit\Wechat\Sdk\Components\Material $material 素材管理
 * @property \Weikit\Wechat\Sdk\Components\User $user 用户管理
 * @property \Weikit\Wechat\Sdk\Components\Qrcode $qrcode 二维码管理
 * @property \Weikit\Wechat\Sdk\Components\Stats $stats 数据统计
 * @property \Weikit\Wechat\Sdk\Components\CustomerService $customerService 新版客服功能
 * @property \Weikit\Wechat\Sdk\Components\Card $card 微信卡券
 * @property \Weikit\Wechat\Sdk\Components\Poi $poi 门店
 */
class Wechat extends BaseWechat
{
    /**
     * 微信官网基本地址
     */
    const WECHAT_WEB_URL = 'https://mp.weixin.qq.com';
    /**
     * 微信接口基本地址
     */
    const WECHAT_BASE_URL = 'https://api.weixin.qq.com';
    /**
     * @var string 微信基本Url
     */
    public $baseUrl = self::WECHAT_BASE_URL;
    /**
     * @var string 公众号appId
     */
    public $appId;
    /**
     * @var string 公众号appSecret
     */
    public $appSecret;
    /**
     * @var string 公众号接口验证token
     */
    public $token;

    public function __construct($config = array())
    {
        // merge core components with custom components
        foreach ($this->coreComponents() as $id => $component) {
            if (!isset($config['components'][$id])) {
                $config['components'][$id] = $component;
            } elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
                $config['components'][$id]['class'] = $component['class'];
            }
        }
        parent::__construct($config);
    }

    public function init()
    {
        if ($this->appId === null) {
            throw new InvalidArgumentException('The wechat property "appId" must be set.');
        } elseif ($this->appSecret === null) {
            throw new InvalidArgumentException('The wechat property "appSecret" must be set.');
        } elseif ($this->token === null) {
            throw new InvalidArgumentException('The wechat property "token" must be set.');
        }
    }

    /**
     * 核心组件
     *
     * @return array
     */
    public function coreComponents()
    {
        return array(
            'cache' => array('class' => 'Weikit\Wechat\Sdk\Caches\FileCache'), // 缓存组件
            'request' => array('class' => 'Weikit\Wechat\Sdk\Requests\CurlRequest'), // 接口HTTP请求组件

            'menu' => array('class' => 'Weikit\Wechat\Sdk\Components\Menu'), // 自定义菜单
            'message' => array('class' => 'Weikit\Wechat\Sdk\Components\Message'), // 消息管理
            'massMessage' => array('class' => 'Weikit\Wechat\Sdk\Components\MassMessage'), // 高级群发接口
            'template' => array('class' => 'Weikit\Wechat\Sdk\Components\Template'), // 模板消息接口
            'oauth' => array('class' => 'Weikit\Wechat\Sdk\Components\Oauth'), // 网页授权
            'material' => array('class' => 'Weikit\Wechat\Sdk\Components\Material'), // 素材管理
            'qrcode' => array('class' => 'Weikit\Wechat\Sdk\Components\Qrcode'), // 二维码管理
            'stats' => array('class' => 'Weikit\Wechat\Sdk\Components\Stats'), // 数据统计
            'customerService' => array('class' => 'Weikit\Wechat\Sdk\Components\CustomerService'), // 新版客服功能
            'card' => array('class' => 'Weikit\Wechat\Sdk\Components\Card'), // 微信卡券
            'poi' => array('class' => 'Weikit\Wechat\Sdk\Components\Poi'), // 门店
        );
    }

    /**
     * @var BaseRequest
     */
    private $_request;

     /**
     * 获取Request组件
     *
     * @return BaseRequest
     */
    public function getRequest()
    {
        if ($this->_request === null) {
            $this->setRequest($this->get('request', false));
        }
        return $this->_request;
    }

    /**
     * 设置Request组件
     *
     * @param BaseRequest $request
     */
    public function setRequest(BaseRequest $request)
    {
        if ($request->baseUrl === null) {
            $request->baseUrl = $this->baseUrl;
        }
        $this->_request = $request;
    }

    /**
     * @var BaseCache
     */
    private $_cache;

    /**
     * 获取Cache组件
     *
     * @return BaseCache
     */
    public function getCache()
    {
        if ($this->_cache === null) {
            $this->setCache($this->get('cache', false));
        }
        return $this->_cache;
    }

    /**
     * 设置Cache组件
     *
     * @param BaseCache $cache
     */
    public function setCache(BaseCache $cache)
    {
        if ($cache->keyPrefix === null) {
            $cache->keyPrefix = 'wechat_' . $this->appId . '_'; // 设置默认缓存前缀
        }
        $this->_cache = $cache;
    }

    /**
     * access_token API前缀
     */
    const WECHAT_ACCESS_TOKEN_PREFIX = 'cgi-bin/token';
    /**
     * 接口请求获取access_token
     *
     * @see https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140183&token=&lang=zh_CN
     * @param string $grantType
     * @return array|bool
     */
    protected function requestAccessToken($grantType = 'client_credential')
    {
        $result = $this->getRequest()
            ->get(array(
                self::WECHAT_ACCESS_TOKEN_PREFIX,
                'appid' => $this->appId,
                'secret' => $this->appSecret,
                'grant_type' => $grantType
            ));
        return isset($result['access_token']) ? $result : false;
    }
    /* =========== 第三方强授权相关接口 =========== */
    /**
     * 请求获取api ticket
     */
    const TYPE_API_TICKET_GET_PREFIX = 'cgi-bin/ticket/getticket';
    /**
     * 请求获取api ticket
     *
     * @param string $type api ticket 类型
     * @return bool|mixed
     */
    public function requestApiTicket($type)
    {
        $result = $this->getRequest()
            ->get(array(
                self::WECHAT_ACCESS_TOKEN_PREFIX,
                'access_token' => $this->getAccessToken(),
                'type' => $type
            ));
        return isset($result['errmsg']) && $result['errmsg'] === 'ok' ? $result : false;
    }

    /**
     * 使用授权码换取公众号的授权信息
     */
    const WECHAT_AUTHORIZATION_INFO_TOKEN_GET_PREFIX = 'cgi-bin/component/api_query_auth';
    /**
     * 使用授权码换取公众号的授权信息
     *
     * @param array $data
     * @return bool|array
     */
    public function getAuthorizationByToken(array $data)
    {
        $result = $this->getRequest()
            ->raw(array(
                self::WECHAT_AUTHORIZATION_INFO_TOKEN_GET_PREFIX,
                'access_token' => $this->getAccessToken(),
            ), $data);
        return isset($result['authorization_info']) ? $result['authorization_info'] : false;
    }

    /**
     * 获取授权方的账户信息
     */
    const WECHAT_AUTHORIZER_INFO_TOKEN_GET_PREFIX = 'cgi-bin/component/api_get_authorizer_info';
    /**
     * 获取授权方的账户信息
     *
     * @param array $data
     * @return bool|array
     */
    public function getAuthorizer(array $data)
    {
        $result = $this->getRequest()
            ->raw(array(
                self::WECHAT_AUTHORIZER_INFO_TOKEN_GET_PREFIX,
                'access_token' => $this->getAccessToken(),
            ), $data);
        return isset($result['authorizer_info']) ? $result : false;
    }

    /**
     * 确认授权
     */
    const WECHAT_AUTHORIZATION_CONFIRM_PREFIX = 'cgi-bin/component/api_confirm_authorization';
    /**
     * 确认授权
     *
     * @param array $data
     * @return bool
     */
    public function confirmAuthorization(array $data)
    {
        $result = $this->getRequest()
            ->raw(array(
                self::WECHAT_AUTHORIZATION_INFO_GET_PREFIX,
                'access_token' => $this->getAccessToken(),
            ), $data);
        return isset($result['errcode']) && !$result['code'];
    }
    /**
     * 获取微信服务器IP地址
     */
    const WECHAT_IP_LIST_GET_PREFIX = 'cgi-bin/getcallbackip';
    /**
     * 获取微信服务器IP地址
     *
     * @return array|bool
     */
    public function getIpList()
    {
        $result = $this->getRequest()
            ->get(array(
                self::WECHAT_IP_LIST_GET_PREFIX,
                'access_token' => $this->getAccessToken()
            ));
        return isset($result['ip_list']) ? $result['ip_list'] : false;
    }
    /* =================== 消息管理 =================== */

    /**
     * @see Weikit\Wechat\Sdk\Components\Menu 自定义菜单
     */

    /* =================== 消息管理 =================== */

    /**
     * @see Weikit\Wechat\Sdk\Components\Message 客服消息
     * @see Weikit\Wechat\Sdk\Components\MassMessage 高级群发接口
     * @see Weikit\Wechat\Sdk\Components\Template 模板消息接口
     */

    /**
     * 获取公众号的自动回复规则
     */
    const WECHAT_AUTO_REPLY_INFO_GET_PREFIX = 'cgi-bin/get_current_autoreply_info';
    /**
     * 获取公众号的自动回复规则
     *
     * @return array|bool|mixed
     */
    public function getAutoReplyInfo()
    {
        $result = $this
            ->getRequest()
            ->get(array(
                self::WECHAT_AUTO_REPLY_INFO_GET_PREFIX,
                'access_token' => $this->getAccessToken()
            ));
        return !array_key_exists('errcode', $result) ? $result : false;
    }

    /* =================== 微信网页开发 =================== */

    /**
     * @see Weikit\Wechat\Sdk\Components\Oauth 模板消息接口
     */

    /* =================== 素材管理 =================== */

    /**
     * @see Weikit\Wechat\Sdk\Components\Material 素材管理
     */

    /* =================== 用户管理 =================== */

    /**
     * @see Weikit\Wechat\Sdk\Components\User 用户管理
     */

    /* =================== 账号管理 =================== */

    /**
     * @see Weikit\Wechat\Sdk\Components\Qrcode 二维码管理
     */
    /**
     * 长链接转短链接接口
     */
    const WECHAT_SHORT_URL_GET_PREFIX = 'cgi-bin/shorturl';
    /**
     * 长链接转短链接接口
     *
     * @param $url
     * @return bool|mixed
     */
    public function getShortUrl($url)
    {
        return $this->getRequest()
            ->raw(array(
                self::WECHAT_SHORT_URL_GET_PREFIX,
                'access_token' => $this->getAccessToken()
            ), array(
                'action' => 'long2short',
                'long_url' => $url
            ));
        return isset($result['short_url']) ? $result['short_url'] : false;
    }

    /* =================== 数据统计 =================== */

    /**
     * @see Weikit\Wechat\Sdk\Components\Stats 数据统计
     */

    /* =================== 微信卡券 =================== */

    /**
     * @see Weikit\Wechat\Sdk\Components\Card 微信卡券
     */

    /* =================== 微信门店 =================== */

    /**
     * @see Weikit\Wechat\Sdk\Components\Poi 门店
     */

    /* =================== 微信小店 =================== */
    /* =================== 微信设备功能 =================== */

    /* =================== 新版客服功能 =================== */

    /**
     * @see Weikit\Wechat\Sdk\Components\CustomerService 新版客服功能
     */

    /* =================== 微信摇一摇周边 =================== */
    /* =================== 微信连WIFI =================== */
    /* =================== 微信扫一扫 =================== */

}