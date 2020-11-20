<?php

namespace Industrious\MakeUser\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MakeUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:user
        {--name= : Name}
        {--email= : Email address}
        {--password= : Password}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new application user.';

    /**
     * Handle creation of the new application user.
     *
     * @return void
     */
    public function handle()
    {
        $randomPassword = Str::random(32);

        if (! $name = $this->option('name')) {
            $name = $this->ask('What is the new user\'s name?') ?: '';
        }

        if (! $email = $this->option('email')) {
            $email = $this->ask('What is the new user\'s email address?');
        }

        if (! $password = $this->option('password')) {
            $password = $this->secret('What is the new user\'s password? (blank generates a random one)');
        }

        if (! $password) {
            $password = $randomPassword;
        }

        try {
            app('db')->beginTransaction();

            $this->validateEmail($email);

            $user = $this->getUserModel()
                ->fill([
                    'email' => $email,
                    'name' => $name,
                    'password' => Hash::make($password),
                ]);

            $user->save();

            $this->info(sprintf('Created new user for [%s]', $email));

            if ($password === $randomPassword) {
                $this->info(sprintf('New password for user: %s', $randomPassword));
            }

            app('db')->commit();
        } catch (Exception $e) {
            $this->error(sprintf('The user was not created: %s', $e->getMessage()));

            app('db')->rollBack();
        }
    }

    /**
     * Determine if the given email address already exists.
     *
     * @param string $email
     * @return void
     * @throws Exception
     */
    private function validateEmail($email)
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception(sprintf('Invalid email [%s]', $email));
        }

        $userModel = $this->getUserModel();

        if ($userModel->where('email', $email)->exists()) {
            throw new Exception(sprintf('Email already exists [%s]', $email));
        }
    }

    /**
     * @return Model
     */
    private function getUserModel(): Model
    {
        return App::make(Config::get('auth.providers.users.model'));
    }
}
