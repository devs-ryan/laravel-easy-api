<?php

namespace DevsRyan\LaravelEasyApi\Commands;
use DevsRyan\LaravelEasyApi\Services\FileService;
use DevsRyan\LaravelEasyApi\Services\HelperService;
use Exception;
use Illuminate\Console\Command;

class MigrateCMSCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easy-api:migrate-cms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add all models within app to the Easy Api GUI';

    /**
     * Exit Commands.
     *
     * @var array
     */
    protected $exit_commands = ['q', 'quit', 'exit'];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->FileService = new FileService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("Migrating models list from easy admin. Any existing Easy Admin models will be overwritten.");

        try {
            $models = \App\EasyAdmin\AppModelList::models();
            $partials = \App\EasyAdmin\AppModelList::partialModels();
        }
        catch(Exception $e) {
            $this->error('Error accessing easy admin models list. Aborting.');
            return;
        }

        $this->FileService->createAppModelList($models, $partials);
        $this->info('Easy admin models imported successfully.');
    }
}
