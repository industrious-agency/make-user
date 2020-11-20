<?php

namespace Industrious\MakeUser;

use Illuminate\Support\ServiceProvider;
use Industrious\MakeUser\Commands\MakeUserCommand;

class MakeUserServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeUserCommand::class,
            ]);
        }
    }
}
