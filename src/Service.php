<?php

namespace think\plugin;

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