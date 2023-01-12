<?php

namespace think\plugin;

use think\exception\HttpException;
use think\facade\Config;
use think\facade\Event;
use think\helper\Str;

class Route
{
    /**
     * 插件路由请求
     * @param null $plugin
     * @param null $controller
     * @param null $action
     * @return mixed
     */
    public static function execute($plugin = null, $controller = null, $action = null)
    {

        $app = app();
        $request = $app->request;
        if (empty($plugin) || empty($controller) || empty($action)) {
            throw new HttpException(500, lang('plugin can not be empty'));
        }
        $request->plugin = $plugin;
        // 设置当前请求的控制器、操作
        $request->setController($controller)->setAction($action);
        $class = get_plugins_class($plugin, 'controller', $controller);
        if (!$class) {
            throw new HttpException(404, lang('plugin controller %s not found', [Str::studly($controller)]));
        }
        // 重写视图基础路径
        $config = Config::get('view');
        $config['view_path'] = $app->plugin->getpluginPath() . $plugin . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        Config::set($config, 'view');
        // 生成控制器对象
        $instance = new $class($app);
        $vars = [];
        if (is_callable([$instance, $action])) {
            // 执行操作方法
            $call = [$instance, $action];
        } elseif (is_callable([$instance, '_empty'])) {
            // 空操作
            $call = [$instance, '_empty'];
            $vars = [$action];
        } else {
            // 操作不存在
            throw new HttpException(404, lang('plugin action %s not found', [get_class($instance) . '->' . $action . '()']));
        }

        return call_user_func_array($call, $vars);
    }
}