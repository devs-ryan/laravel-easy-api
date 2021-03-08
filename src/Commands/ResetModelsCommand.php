<?php

namespace DevsRyan\LaravelEasyApi\Commands;

use Illuminate\Console\Command;
use DevsRyan\LaravelEasyApi\Services\FileService;
use Exception;


class ResetModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easy-api:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset the Easy Api models file';

    /**
     * Helper Service.
     *
     * @var class
     */
    protected $fileService;

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
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info("<<<!!!Info!!!>>>\nAt any time enter 'q', 'quit', or 'exit' to cancel.");
        $continue = $this->ask("This will reset EasyApi completely, continue? [y]es or [n]o");

        //exit code check
        if (in_array($continue, $this->exit_commands)) {
            $this->info("Command exit code entered.. terminating.");
            return;
        }

        //continue check
        if (!in_array(strtolower($continue), $this->continue_commands)) {
            $this->info("Command exit code entered.. terminating.");
            return;
        }

        $this->FileService->removeAppDirectory();
        $this->FileService->createAppDirectory();
        $this->FileService->resetAppModelList();
        $this->info('EasyApi models reset successfully.');
    }
}
















