<?php

namespace DevsRyan\LaravelEasyApi\Commands;

use Illuminate\Console\Command;
use DevsRyan\LaravelEasyApi\Services\FileService;
use Exception;

class RemoveModelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'easy-api:remove-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove a model from the Easy Api UI';

    /**
     * Exit Commands.
     *
     * @var array
     */
    protected $exit_commands = ['q', 'quit', 'exit'];

    /**
     * Helper Service.
     *
     * @var class
     */
    protected $fileService;

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
        //check AppModelList corrupted
        if ($this->FileService->checkIsModelListCorrupted()) {
            $this->info("App\EasyApi\AppModelList.php is corrupt.\nRun php artisan easy-api:reset or correct manually to continue.");
            return;
        }

        $this->info("<<<!!!Info!!!>>>\nAt any time enter 'q', 'quit', or 'exit' to cancel.");

        //get namespace
        if (env('EASY_ADMIN_DEFAULT_NAMESPACE', false)) {
            $namespace = 'App\Models';
        }
        else {
            $namespace = $this->ask("Enter the model namespace(Default: App\Models\)");
            if (in_array($namespace, $this->exit_commands)) {
                $this->info("Command exit code entered.. terminating.");
                return;
            }
            if ($namespace == '') $namespace = 'App\Models';
        }
        $namespace = $this->filterInput($namespace, true);

        //get model
        $model = $this->ask("Enter the model name");
        if (in_array($model, $this->exit_commands)) {
            $this->info("Command exit code entered.. terminating.");
            return;
        }
        $model = $this->filterInput($model);

        //check if model/namespace is valid
        $model_path = $namespace . $model;
        $this->info('Removing Model from Easy Api..' . $model_path);
        if (!class_exists($model_path)) {
            $this->info('Model does not exist.. terminating.');
            return;
        }

        //check if package file has already (create otherwise)
        if ($this->FileService->checkModelExists($model_path)) {
            $this->FileService->removeModelFromList($namespace, $model);
            $this->info('Removed EasyApi models list file..');
        }
        else {
            $this->info('Model not found in EasyApi models list, checking for \App\EasyApi file..');
        }
        //check if App file exists
        if ($this->FileService->checkPublicModelExists($model_path)) {
            $this->FileService->removePublicModel($model_path);
            $this->info('\App\EasyApi public file removed..');
        }
        else {
            $this->info('\App\EasyApi public file not found..');
        }

        $this->info('Model removed successfully!');
    }

    /**
     * Filter Namespace.
     *
     * @return mixed
     */
    private function filterInput($input, $namespace = false)
    {
        $input = preg_replace('/\s+/', '', $input);
        $input = str_replace('/', '\\', $input);
        $input = preg_replace('/(\\\\)+/', '\\', $input);

        //add trailing slash to namespace if not included
        if ($input != '' && $input[strlen($input) - 1] != '\\' && $namespace) {
            $input .= '\\';
        }
        return $input;
    }
}




















