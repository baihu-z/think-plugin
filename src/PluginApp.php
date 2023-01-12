<?php

namespace think\plugin;

use Closure;
use think\App;
use think\exception\HttpException;
use think\Request;
use think\Response;

/**
 * 应用插件模式支持
 */
class PluginApp
{
    /** @var App */
    protected $app;

    /**
     * 请求对象
     * @var string
     */
    protected $request;

    /**
     * 应用名称
     * @var string
     */
    protected $name;

    /**
     * 应用名称
     * @var string
     */
    protected $appName;

    /**
     * 应用路径
     * @var string
     */
    protected $path;

    /**
     * 插件路径
     * @var string
     */
    protected $plugin_path;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->name = $this->getName();
        $this->path = $this->app->http->getPath();
        $this->plugin_path = $app->plugin->getpluginPath() . $this->name;
    }

    /**
     * 多应用解析
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
//        if (!$this->parseMultiApp()) {
//            return $next($request);
//        }

        return $next($request);
        return $this->app->middleware->pipeline($this->name)
            ->send($request)
            ->then(function ($request) use ($next) {
                return $next($request);
            });
    }

    /**
     * 获取插件标识
     * @return mixed|null
     */
    final protected function getName()
    {
        $class = get_class($this);
        list(, $name,) = explode('\\', $class);
        $this->request->plugin = $name;

        return $name;
    }

}