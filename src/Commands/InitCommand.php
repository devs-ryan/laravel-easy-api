<?php

namespace DevsRyan\LaravelEasyApi\Commands;
use DevsRyan\LaravelEasyApi\Services\FileService;
use DevsRyan\LaravelEasyApi\Services\HelperService;

use Illuminate\Console\Command;

class InitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easy-api:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Copies all models added to your Easy Admin and sets their API permissions as view only';

    /**
     * Continue Commands.
     *
     * @var array
     */
    protected $continue_commands = ['y', 'yes'];

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
        $this->helperService = new HelperService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("<<<!!!Info!!!>>>\nAt any time enter 'q', 'quit', or 'exit' to cancel.");

        if (!$this->FileService->checkIsModelListCorrupted()) {
            $continue = $this->ask("This will reset EasyApi completely, continue? [y]es or [n]o");

            //continue check
            if (!in_array(strtolower($continue), $this->continue_commands)) {
                $this->info("Command exit code entered.. terminating.");
                return;
            }
        }

        $this->FileService->initFromEasyAdmin();
        $this->info('Easy Admin initialized successfully!');
    }
}
