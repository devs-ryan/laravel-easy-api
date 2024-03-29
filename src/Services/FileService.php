<?php
namespace DevsRyan\LaravelEasyApi\Services;

use Illuminate\Support\Facades\DB;
use DevsRyan\LaravelEasyApi\Services\HelperService;
use Intervention\Image\Facades\Image;
use Exception;
use Throwable;


class FileService
{

    /**
     * Helper Service.
     *
     * @var class
     */
    protected $helperService;

    /**
     * Template for public model classes
     *
     * @var string
     */
    protected $public_model_template;

    /**
     * Template for app models list
     *
     * @var string
     */
    protected $app_model_list_template;


    /**
     * Image resize
     *
     * @var class
     */
    public $image_sizes = [
        'thumbnail' => '150|auto',
        'small' => '300|auto',
        'medium' => '600|auto',
        'large' => '1200|auto',
        'xtra_large' => '2400|auto',
        'square_thumbnail' => '150|150',
        'square' => '600|600',
        'square_large' => '1200|1200',
        'original' => 'size not altered'
    ];

    /**
     * Image resize
     *
     * @var class
     */
    public $model_types = [
        'page',
        'post',
        'partial'
    ];

    /**
     * Create a new service instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->helperService = new HelperService;

        $path = str_replace('/Services', '', __DIR__).'/FileTemplates/AppModelList.template';
        $this->app_model_list_template = file_get_contents($path) or die("Unable to open file!");
    }

    /**
     * Check if AppModelList is corrupted
     *
     * @return boolean
     */
    public function checkIsModelListCorrupted()
    {
        try {
            $this->helperService->getAllModels();
        }
        catch(Exception $e) {
            return true;
        }
        return false;
    }

    /**
     * Reset AppModelList file
     *
     * @return void
     */
    public function resetAppModelList()
    {
        $write_path = app_path('EasyApi/AppModelList.php');
        file_put_contents($write_path, $this->app_model_list_template) or die("Unable to write to file!");
    }

    /**
     * Import from CMS for AppModelList file
     *
     * @return void
     */
    public function createAppModelList($models, $partials)
    {
        $write_path = app_path('EasyApi/AppModelList.php');
        $leading_space = '            ';
        $models_text = '';
        $partials_text = '';

        foreach($models as $model) {
            $models_text .= "{$leading_space}'{$model}',\n";
        }

        foreach($partials as $partial) {
            $partials_text .= "{$leading_space}'{$partial}',\n";
        }

        $file_output = str_replace("{$leading_space}//Models Here - Format: Namespace.Model", $models_text, $this->app_model_list_template);
        $file_output = str_replace("{$leading_space}//Models Here - Format: PageModel.Model || Global.Model", $partials_text, $file_output);
        file_put_contents($write_path, $file_output) or die("Unable to write to file!");
    }

    /**
     * Check if a model has already been added to easy admin
     *
     * @param string $model
     * @return boolean
     */
    public function checkModelExists($model)
    {
        $models = $this->helperService->getAllConvertedModels();
        if (in_array($model, $models)) return true;
        return false;
    }

    /**
     * Check if a public class for this model already exists
     *
     * @param string $model
     * @return boolean
     */
    public function checkPublicModelExists($model_path)
    {
        try {
            $this->helperService->getPublicModel($model_path);
        }
        catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Add Model into EasyApi models list
     *
     * @param string $namespace, $model, $type, $type_target
     * @return void
     */
    public function addModelToList($namespace, $model, $type = 'None', $type_target = null)
    {
        //add model to AppModelList file
        $path = app_path('EasyApi/AppModelList.php');

        $package_file = file_get_contents($path) or die("Unable to open file!");

        for($i = 0; $i < strlen($package_file); $i++) {
            //find end of array
            if ($package_file[$i] == ']' && $package_file[$i+1] == ';') {
                $insert = "            '" . rtrim($namespace, '\\') . '.' . $model . "',\n";
                $new_text = substr_replace($package_file, $insert, $i - 8, 0);
                file_put_contents($path, $new_text) or die("Unable to write to file!");
                break;
            }
        }

        // if special type, add to
        if (in_array($type, $this->model_types)) {

            $package_file = file_get_contents($path) or die("Unable to open file!");
            $target = $type . 'Models()';

            $stack = '';
            $target_found = false;
            for($i = 0; $i < strlen($package_file); $i++) {

                if (!$target_found) {
                    if (strlen($stack) + 1 > strlen($target)) $stack = ltrim($stack, $stack[0]) . $package_file[$i];
                    else $stack .= $package_file[$i];
                    if ($stack == $target) $target_found = true;
                }
                else {
                    //find end of array
                    if ($package_file[$i] == ']' && $package_file[$i+1] == ';') {
                        switch($type) {
                            case 'page':
                            case 'post':
                                $insert = "            '" . $model . "',\n";
                                break;
                            case 'partial':
                                if ($type_target === null)
                                    throw new Exception('Invaled type target for model type: ' . $type);
                                    $insert = "            '" . $type_target . '.' . $model . "',\n";
                                break;
                        }

                        $new_text = substr_replace($package_file, $insert, $i - 8, 0);
                        file_put_contents($path, $new_text) or die("Unable to write to file!");
                        break;
                    }
                }

            }
        }
    }

    /**
     * Remove Model from EasyApi models list
     *
     * @param string $namespace, $model
     * @return void
     */
    public function removeModelFromList($namespace, $model)
    {
        $path = app_path('EasyApi/AppModelList.php');
        $input_lines = file_get_contents($path) or die("Unable to open file!");
        $overwrite_string = preg_replace('/^.*(\.)?'.$model.'\',\n/m', '', $input_lines);
        file_put_contents($path, $overwrite_string) or die("Unable to write to file!");
    }

    /**
     * Copy All files from EasyAdmin to to EasyApi and change namespace
     *
     * @return void
     */
    public function initFromEasyAdmin() {
        $read_path = app_path() . '/EasyAdmin/';
        $write_path = app_path() . '/EasyApi/';

        $files = scandir($read_path);


        foreach($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $file_contents = file_get_contents($read_path . $file) or die("Unable to open file!");
                $write_contents = str_replace('EasyAdmin', 'EasyApi', $file_contents);
                $write_contents = str_replace("'create'", " //'create'", $write_contents);
                $write_contents = str_replace("'update'", " //'update'", $write_contents);
                $write_contents = str_replace("'delete'", " //'delete'", $write_contents);
                $write_contents = str_replace("\n            'seed'", "", $write_contents);
                if (!file_exists($write_path)) mkdir($write_path, 0777, true);
                file_put_contents($write_path . $file, $write_contents) or die("Unable to write to file!");
            }
        }
    }

    /////////////////////////////////////
    //FILTER FUNCTIONS FOR ABOVE METHOD//
    /////////////////////////////////////
    private function formFilter($fields)
    {
        $fields = trim($fields);
        $fields = str_replace('\'id', '//\'id', $fields);
        $fields = str_replace('\'remember_token', '//\'remember_token', $fields);
        $fields = str_replace('\'email_verified_at', '//\'email_verified_at', $fields);
        $fields = str_replace('\'created_at', '//\'created_at', $fields);
        $fields = str_replace('\'updated_at', '//\'updated_at', $fields);

        return $fields;
    }
    private function indexFilter($fields)
    {
        $fields = trim($fields);
        $fields = str_replace('\'password', '//\'password', $fields);
        $fields = str_replace('\'remember_token', '//\'remember_token', $fields);
        $fields = str_replace('\'email_verified_at', '//\'email_verified_at', $fields);
        $fields = str_replace('\'created_at', '//\'created_at', $fields);
        $fields = str_replace('\'updated_at', '//\'updated_at', $fields);

        return $fields;
    }


    /**
     * Remove the App/EasyApi directory
     *
     * @return void
     */
    public function removeAppDirectory() {
        $dir = app_path() . '/EasyApi';

        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it,
                     \RecursiveIteratorIterator::CHILD_FIRST);
        foreach($files as $file) {
            if ($file->isDir()){
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        rmdir($dir);
    }

    /**
     * Create the App/EasyApi directory
     *
     * @return void
     */
    public function createAppDirectory() {
        $dir = app_path() . '/EasyApi';
        if (!file_exists($dir)) {
            mkdir($dir);
        }
    }

    /**
     * Undocumented function
     *
     * @param string $model_name
     * @param string $field_name
     * @param string $value
     * @return void
     */
    public static function getFileLink($model_name, $field_name, $value) {

        // check if is file
        $path = public_path() . '/devsryan/LaravelEasyApi/storage/files/' . $model_name . '-' .  $field_name;
        if (file_exists($path . '/' . $value)) {
            return '/devsryan/LaravelEasyApi/storage/files/' . $model_name . '-' .  $field_name . '/' . $value;
        }

        // check if is image
         $path = public_path() . '/devsryan/LaravelEasyApi/storage/img/' . $model_name . '-' .  $field_name . '/original';
         if (file_exists($path . '/' . $value)) {
             return '/devsryan/LaravelEasyApi/storage/img/' . $model_name . '-' .  $field_name . '/original/' . $value;
         }

         return null;
    }

    /**
     * Get an image path
     *
     * @param string $model_name
     * @param string $field_name
     * @param string $file_name
     * @param string $size
     * @return string
     */
    public function getImagePath($model_name, $field_name, $file_name, $size = 'original') {

        if (!in_array($size, $this->image_sizes)) $size = 'original';

        return '/devsryan/LaravelEasyApi/storage/img/' . $model_name . '-' .  $field_name . '/' . $size . '/' . $file_name;
    }

    /**
     * Get an file path
     *
     * @param string $model_name
     * @param string $field_name
     * @param string $file_name
     * @return string
     */
    public function getFilePath($model_name, $field_name, $file_name) {
        return '/devsryan/LaravelEasyApi/storage/files/' . $model_name . '-' .  $field_name . '/' . $file_name;
    }
}





































