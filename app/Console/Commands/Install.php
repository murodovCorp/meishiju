<?php

namespace App\Console\Commands;

use Artisan;
use Illuminate\Console\Command;
use Throwable;

class Install extends Command
{

    protected $signature = 'install:project';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install project';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $hasError = false;

        try {
            $this->info('Thank you for your purchase 😁🛍!');

            try {
                $this->info('migrate running wait 5-10 minute');
                Artisan::call('migrate');
            } catch (Throwable $e) {
                $this->error('migrate: ' . $e->getMessage());
                $this->error('check in .env db configurations');
                $hasError = true;
            }

            try {
                $this->info('seed running wait 1-2 minute');
                Artisan::call('db:seed');
            } catch (Throwable $e) {
                $this->error('seed: ' . $e->getMessage());
                $hasError = true;
            }

            try {
                $this->info('storage running');
                Artisan::call('storage:link');
            } catch (Throwable $e) {
                $this->error('storage: ' . $e->getMessage());
                $this->error('check storage directories, create if not exists: storage/debugbar, storage/framework/cache/data, storage/framework/sessions, storage/framework/views');
                $hasError = true;
            }

            try {
                $this->info('optimize running');
                Artisan::call('optimize:clear');
            } catch (Throwable $e) {
                $this->error('optimize: ' . $e->getMessage());
                $hasError = true;
            }

            try {
                $this->info('cache running');
                Artisan::call('cache:clear');
            } catch (Throwable $e) {
                $this->error('cache: ' . $e->getMessage());
                $hasError = true;
            }

        } catch (Throwable $e) {
            $this->error($e->getMessage());
            $hasError = true;

        }

        if ($hasError) {
            $this->error('Error in installation. Check your php v(>=8), php.ini extensions, in .env db configurations.');
            return;
        }

        $this->info('Success! Bye. 👋');
    }

}
