<?php
namespace  think\plugin;
use think\helper\Str;
use think\facade\Event;
use think\facade\Config;
use think\exception\HttpException;
use think\App;
use think\Route;
use think\route\Url as UrlBuild;
class Url extends UrlBuild
{

    /**
     * 直接解析URL地址
     * @access protected
     * @param  string      $url URL
     * @param  string|bool $domain Domain
     * @return string
     */
    protected function parseUrl(string $url, &$domain): string
    {

    }

    public function build()
    {


    }
    /**
     * 获取URL的应用名
     * @access protected
     * @return string
     */
    protected function getAppName()
    {
        $app = $this->app->http->getName();
        $map = $this->app->config->get('app.app_map', []);

        if ($key = array_search($app, $map)) {
            $app = $key;
        }

        return $app;
    }


}