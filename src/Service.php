<?php

namespace think\plugin;

use think\Console;
use think\plugin\Plugin;
use think\plugin\Url;
use think\Service as BaseService;
use think\Route;
use think\facade\Config;
use think\facade\Cache;
use think\facade\Event;

/**
 * 插件服务
 * Class Service
 * @package think\plugin
 */
class Service extends BaseService
{

    protected $plugins_path;

    public function register()
    {
        $this->plugins_path = $this->getpluginPath();
        // 自动载入插件
        $this->autoload();
        // 加载插件事件
        $this->loadEvent();
        // 加载插件系统服务
        $this->loadService();
        // 绑定插件容器
        $this->app->bind('plugin', Service::class);

    }

    public function boot()
    {
        //注册插件基础中间件
//        $this->app->event->listen('HttpRun', function () {
//            $this->app->middleware->add(PluginApp::class);
//        });
        //注册插件基础命令
        $this->commands([
            'app-plugin:create' => command\AppPluginCreateCommand::class,
            "app-plugin:make:controller" => command\make\Controller::class,
            "app-plugin:make:model" => command\make\Model::class,
        ]);
        //注册插件基础路由
        $this->app->bind([
            'think\plugin\Url' => Url::class,
        ]);
        $routes = (array)Config::get();

        $this->registerRoutes(function (Route $route) {
            // 路由脚本
            $execute = '\\think\\plugin\\Route::execute';
            // 注册控制器路由
            $route->rule("plugin/:plugin/[:controller]/[:action]", $execute);
            // 自定义路由
        });

    }

    /**
     * 插件事件
     */
    private function loadEvent()
    {
        $hooks = $this->app->isDebug() ? [] : Cache::get('hooks', []);
        if (empty($hooks)) {
            $hooks = (array)Config::get('plugin.hooks', []);
            // 初始化钩子
            foreach ($hooks as $key => $values) {
                if (is_string($values)) {
                    $values = explode(',', $values);
                } else {
                    $values = (array)$values;
                }
                $hooks[$key] = array_filter(array_map(function ($v) use ($key) {
                    return [get_plugins_class($v), $key];
                }, $values));
            }
            Cache::set('hooks', $hooks);
        }
        //如果在插件中有定义 pluginsInit，则直接执行
        if (isset($hooks['pluginsInit'])) {
            foreach ($hooks['pluginsInit'] as $k => $v) {
                Event::trigger('pluginsInit', $v);
            }
        }
        Event::listenEvents($hooks);
    }

    /**
     * 挂载插件服务
     */
    private function loadService()
    {
        $results = scandir($this->plugins_path);
        $bind = [];
        foreach ($results as $name) {
            if ($name === '.' or $name === '..') {
                continue;
            }
            if (is_file($this->plugins_path . $name)) {
                continue;
            }
            $pluginDir = $this->plugins_path . $name . DIRECTORY_SEPARATOR;
            if (!is_dir($pluginDir)) {
                continue;
            }

            if (!is_file($pluginDir . ucfirst($name) . '.php')) {
                continue;
            }

            $service_file = $pluginDir . 'service.ini';
            if (!is_file($service_file)) {
                continue;
            }
            $info = parse_ini_file($service_file, true, INI_SCANNER_TYPED) ?: [];
            $bind = array_merge($bind, $info);
        }

        $this->app->bind($bind);
    }

    /**
     * 自动载入插件
     * @return bool
     */
    private function autoload()
    {
        // 是否处理自动载入
        if (!Config::get('plugin.autoload', true)) {
            return true;
        }
        $config = Config::get('plugin');
        // 读取插件目录及钩子列表
        $base = get_class_methods("\\think\\plugin");
        $file = [];
        $file = array_merge($file, glob($this->getpluginPath() . '*/*' . $this->app->getConfigExt()));
        $file = array_merge($file, glob($this->getpluginPath() . '*/config/*' . $this->app->getConfigExt()));
        $file = $file ? array_unique($file) : $file;
        $config_files = [];
        // 读取插件目录中的php文件
        foreach ($file as $plugins_file) {
            // 格式化路径信息
            $info = pathinfo($plugins_file);
            // 获取插件目录名
            $name = pathinfo($info['dirname'], PATHINFO_FILENAME);

            // 找到插件入口文件
            if (strtolower($info['filename']) === 'plugin') {
                // 读取出所有公共方法
                $methods = (array)get_class_methods("\\plugin\\" . $name . "\\" . $info['filename']);
                var_dump($methods);
                // 跟插件基类方法做比对，得到差异结果
                $hooks = array_diff($methods, $base);
                // 循环将钩子方法写入配置中
                foreach ($hooks as $hook) {
                    if (!isset($config['hooks'][$hook])) {
                        $config['hooks'][$hook] = [];
                    }
                    // 兼容手动配置项
                    if (is_string($config['hooks'][$hook])) {
                        $config['hooks'][$hook] = explode(',', $config['hooks'][$hook]);
                    }
                    if (!in_array($name, $config['hooks'][$hook])) {
                        $config['hooks'][$hook][] = $name;
                    }

                }

            }
            $config['a'] = [];
            if (strtolower($info['filename']) === 'common') {
                include_once $plugins_file;
            }
//            if (strtolower($info['filename']) === 'event') {
//                $this->app->loadEvent(include $plugins_file);
//            }
//            if (strtolower($info['filename']) === 'middleware') {
//                $this->app->middleware->import(include $plugins_file, 'plugin');
//            }
//            if (strtolower($info['filename']) === 'provider') {
//                $this->app->bind(include $plugins_file);
//            }
            //加载插件自定义命令
            if (strtolower($info['filename']) === 'console') {
                $console_config = include $plugins_file;
                $this->commands($console_config['commands']);
            }
            $config_files[] = $info['dirname'];
        }
        Config::set($config, 'plugin');
    }

    /**
     * 获取 plugins 路径
     * @return string
     */
    public function getpluginPath()
    {
        // 初始化插件目录
        $plugins_path = $this->app->getRootPath() . 'plugin' . DIRECTORY_SEPARATOR;
        // 如果插件目录不存在则创建
        if (!is_dir($plugins_path)) {
            @mkdir($plugins_path, 0755, true);
        }

        return $plugins_path;
    }

    /**
     * 获取插件的配置信息
     * @param string $name
     * @return array
     */
    public function getpluginsConfig()
    {
        $name = $this->app->request->plugin;
        $plugin = get_plugins_instance($name);
        if (!$plugin) {
            return [];
        }

        return $plugin->getConfig();
    }
}