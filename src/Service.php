<?php

namespace think\plugin;

use think\plugin\Plugin;
use think\Service as BaseService;
use think\Route;
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
        // $this->autoload();
        // 加载插件事件
        // $this->loadEvent();
        // 加载插件系统服务
        $this->loadService();
        // 绑定插件容器
        $this->app->bind('plugin', Service::class);

    }

    public function boot()
    {
        //注册中间件
        $this->app->event->listen('HttpRun', function () {
            $this->app->middleware->add(Plugin::class);
        });
        // $this->app->config->load($file, pathinfo($file, PATHINFO_FILENAME));
        //注册命令
        $this->commands([
            'app-plugin:create' => command\AppPluginCreateCommand::class,
        ]);
        //注册路由
        $this->app->bind([
            'think\plugin\Url' => Url::class,
        ]);
        $this->registerRoutes(function (Route $route) {
            // 路由脚本
            $execute = '\\think\\plugin\\Url::execute';
            // 注册控制器路由
            $route->rule("plugin/:plugin/[:controller]/[:action]", $execute)->middleware(Plugin::class);
            // 自定义路由
//            $routes = (array) Config::get('plugin.route', []);
//            foreach ($routes as $key => $val) {
//                if (!$val) {
//                    continue;
//                }
//                if (is_array($val)) {
//                    $domain = $val['domain'];
//                    $rules = [];
//                    foreach ($val['rule'] as $k => $rule) {
//                        [$plugin, $controller, $action] = explode('/', $rule);
//                        $rules[$k] = [
//                            'plugin'        => $plugin,
//                            'controller'    => $controller,
//                            'action'        => $action,
//                            'indomain'      => 1,
//                        ];
//                    }
//                    $route->domain($domain, function () use ($rules, $route, $execute) {
//                        // 动态注册域名的路由规则
//                        foreach ($rules as $k => $rule) {
//                            $route->rule($k, $execute)
//                                ->name($k)
//                                ->completeMatch(true)
//                                ->append($rule);
//                        }
//                    });
//                } else {
//                    list($plugin, $controller, $action) = explode('/', $val);
//                    $route->rule($key, $execute)
//                        ->name($key)
//                        ->completeMatch(true)
//                        ->append([
//                            'plugin' => $plugin,
//                            'controller' => $controller,
//                            'action' => $action
//                        ]);
//                }
//            }
        });

    }

    /**
     * 插件事件
     */
    private function loadEvent()
    {
        $hooks = $this->app->isDebug() ? [] : Cache::get('hooks', []);
        if (empty($hooks)) {
            $hooks = (array)Config::get('plugins.hooks', []);
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
        if (!Config::get('plugins.autoload', true)) {
            return true;
        }
        $config = Config::get('plugins');
        // 读取插件目录及钩子列表
        $base = get_class_methods("\\think\\Plugins");
        // 读取插件目录中的php文件
        foreach (glob($this->getpluginsPath() . '*/*.php') as $plugins_file) {
            // 格式化路径信息
            $info = pathinfo($plugins_file);
            // 获取插件目录名
            $name = pathinfo($info['dirname'], PATHINFO_FILENAME);
            // 找到插件入口文件
            if (strtolower($info['filename']) === 'plugin') {
                // 读取出所有公共方法
                $methods = (array)get_class_methods("\\plugins\\" . $name . "\\" . $info['filename']);
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
        }
        Config::set($config, 'plugins');
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