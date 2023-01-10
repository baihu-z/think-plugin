<?php

namespace think\plugin;

use think\app\MultiApp;
use think\app\Url;
use think\Service as BaseService;

class Service extends BaseService
{
    public function boot()
    {
        $this->commands([
            'app-plugin:create' => command\AppPluginCreateCommand::class,
        ]);
    }
}