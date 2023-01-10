<?php

namespace think\plugin\command;

use think\Console;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class AppPluginCreateCommand extends Command
{
    protected function configure()
    {
        $this->setName('app-plugin:create')
            ->addArgument('name', Argument::REQUIRED, "应用插件名称")
            ->setDescription('应用插件创建命令');
    }

    protected function execute(Input $input, Output $output)
    {
        $output->writeln('创建应用插件开始');
        $name = trim($input->getArgument('name'));
        if (strpos($name, '/') !== false || strpos($name, "\\")) {
            $output->writeln('<error>名称错误，名称不能包含字符 \'/\'</error>');
            return false;
        }
        if (is_dir($plugin_config_path = root_path() . "/plugin/$name")) {
            $output->writeln("<error>目录 $plugin_config_path 已经存在!</error>");
            return false;
        }
        $this->mkdirDirAll($name);
        //创建对应插件目录
        $output->writeln('创建应用插件成功');


    }

    /**
     * @param $name
     * @return void
     */
    protected function mkdirDirAll($name)
    {
        $base_path = root_path();
        $this->mkdir("$base_path/plugin/$name/app/controller");
        $this->mkdir("$base_path/plugin/$name/app/model");
        $this->mkdir("$base_path/plugin/$name/app/middleware");
        $this->mkdir("$base_path/plugin/$name/app/route");
        $this->mkdir("$base_path/plugin/$name/app/validate");
        $this->mkdir("$base_path/plugin/$name/app/command");
        $this->mkdir("$base_path/plugin/$name/app/config");
        $this->mkdir("$base_path/plugin/$name/app/view/index");
        //生成相关文件
        $this->createCommonFile("$base_path/plugin/$name/app/common.php");
        $this->createEnevtFile("$base_path/plugin/$name/app/event.php");
        $this->createControllerFile("$base_path/plugin/$name/app/controller/IndexController.php", $name);
        $this->createViewFile("$base_path/plugin/$name/app/view/index/index.html");
        $this->createCommandFile("$base_path/plugin/$name/app/command/TestCommand.php", $name);
        $this->createCommandConfigFile("$base_path/plugin/$name/app/config/console.php", $name);
        $this->createRouteConfigFile("$base_path/plugin/$name/app/route/route.php");
    }

    /**
     * @param $path
     * @return void
     */
    protected function mkdir($path)
    {
        if (is_dir($path)) {
            return;
        }
        (new Output())->writeln("Create $path");
        mkdir($path, 0777, true);
    }


    /**
     * @param $fileName
     * @return void
     */
    protected function createCommonFile($fileName)
    {
        $content = <<<EOF
<?php
// +----------------------------------------------------------------------
// | REBORN 
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.reborn.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed REBORN 并不是自由软件，未经许可不能去掉 REBORN 相关版权
// +----------------------------------------------------------------------

EOF;
        file_put_contents($fileName, $content);
    }

    /**
     * @param $fileName
     * @return void
     */
    protected function createEnevtFile($fileName)
    {
        $content = <<<EOF
<?php
// +----------------------------------------------------------------------
// | REBORN 
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.reborn.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed REBORN 并不是自由软件，未经许可不能去掉 REBORN 相关版权
// +----------------------------------------------------------------------

// 事件定义文件
return [
    'listen'    => [
        'AppInit'  => [],
        'HttpRun'  => [],
        'HttpEnd'  => [],
        'LogLevel' => [],
        'LogWrite' => []
    ]
];
EOF;
        file_put_contents($fileName, $content);
    }

    /**
     * @param $fileName
     * @param $name
     * @return void
     */
    protected function createControllerFile($fileName, $name)
    {

        $content = <<<EOF
<?php
// +----------------------------------------------------------------------
// | REBORN 
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.reborn.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed REBORN 并不是自由软件，未经许可不能去掉 REBORN 相关版权
// +----------------------------------------------------------------------
namespace  app\\plugin\\$name\\app\\controller;
class IndexController
{

    public function index()
    {
        return view('index/index', ['name' => '$name']);
    }

}
EOF;
        file_put_contents($fileName, $content);
    }

    /**
     * @param $fileName
     * @return void
     */
    protected function createViewFile($fileName)
    {
        $content = <<<EOF
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="/favicon.ico"/>
    <title>reborn app plugin</title>

</head>
<body>
hello <?=htmlspecialchars(\$name)?>
</body>
</html>


EOF;
        file_put_contents($fileName, $content);
    }


    /**
     * @param $fileName
     * @param $name
     * @return void
     */
    protected function createCommandFile($fileName, $name)
    {

        $content = <<<EOF
<?php
// +----------------------------------------------------------------------
// | REBORN 
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.reborn.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed REBORN 并不是自由软件，未经许可不能去掉 REBORN 相关版权
// +----------------------------------------------------------------------
namespace app\\plugin\\$name\\app\\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
class TestCommand extends Command
{
    protected function configure()
    {
        \$this->setName('test:create')
            ->setDescription('测试');
    }

    protected function execute(Input \$input, Output \$output)
    {
        \$output->writeln('这是测试');

    }
}
EOF;

        file_put_contents($fileName, $content);


    }

    /**
     * @param $fileName
     * @param $name
     * @return void
     */
    protected function createCommandConfigFile($fileName, $name)
    {
        $content = <<<EOF
<?php
// +----------------------------------------------------------------------
// | REBORN 
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.reborn.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed REBORN 并不是自由软件，未经许可不能去掉 REBORN 相关版权
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'test:create' => 'app\\plugin\\$name\\app\\command\\TestCommand',//应用插件测试命令行
    ],
];
EOF;
        file_put_contents($fileName, $content);
    }


    /**
     * @param $fileName
     * @return void
     */
    protected function createRouteConfigFile($fileName)
    {
        $content = <<<EOF
<?php
// +----------------------------------------------------------------------
// | REBORN 
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 https://www.reborn.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed REBORN 并不是自由软件，未经许可不能去掉 REBORN 相关版权
// +----------------------------------------------------------------------
return [];
EOF;
        file_put_contents($fileName, $content);
    }

}
