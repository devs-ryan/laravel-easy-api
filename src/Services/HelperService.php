<?php
namespace DevsRyan\LaravelEasyApi\Services;

use App\EasyApi\AppModelList;
use Illuminate\Support\Facades\DB;
use Exception;
use Throwable;


class HelperService
{
    /**
     * Find the form input type
     *
     * @param string $field
     * @param string $model
     * @return string
     */
    public static function inputType($field, $model)
    {
        $record = new $model;
        $table = $record->getTable();
        $column_type = '';

        //find column type
        $columns = DB::select('SHOW COLUMNS FROM ' . $table);
        foreach($columns as $column) {
            if ($column->Field == $field) {
                $column_type = $column->Type;
                break;
            }
        }

        //convert to options:
        //int, float, boolean, date, timestamp, password, text (default)

        //check password
        if ($field == 'password') {
            return 'password';
        }
        //check boolean
        if ($column_type == 'tinyint(1)') {
            return 'boolean';
        }
        //check integer
        if (strpos($column_type, 'int') !== false) {
            return 'integer';
        }
        //check decimal
        foreach(['double', 'decimal', 'float'] as $check) {
            if (strpos($column_type, $check) !== false) {
                return 'decimal';
            }
        }
        //check timestamp
        if (strpos($column_type, 'timestamp') !== false) {
            return 'timestamp';
        }
        //check date
        if (strpos($column_type, 'date') !== false) {
            return 'date';
        }

        //default to text
        return 'text';
    }

    /**
     * Find relationship column name for a parent model
     *
     * @param string $parent
     * @param Array $nav_items
     * @return mixed
     */
    public function findParentIdColumnName($parent, $nav_items) {
        // find field to get parent ID
        foreach($nav_items as $url_string => $model_name) {
            if ($model_name === $parent) {
                return str_replace('-', '_', $url_string) . '_id';
            }
        }
        throw new Exception('Missing parent model in nav items');
    }

    /**
     * Finds a models parent model
     *
     * @param string $child_model
     * @return mixed
     */
    public function findParent($child_model) {
        $partial_models = $this->getAllPartialModels();

        foreach ($partial_models as $partial_model) {
            $pieces = explode('.', $partial_model);
            $parent = $pieces[0];
            $partial = $pieces[1];

            if ($partial === $child_model) {
                return $parent;
            }

        }
        throw new Exception('Unable to find parent model');
    }

    /**
     * Convert the URL model to the app model
     *
     * @return Array
     */
    public function convertUrlModel($url_model)
    {
        $model = '';
        $pieces = explode('-', $url_model);
        foreach ($pieces as $piece) {
            $model .= ucfirst($piece);
        }

        $app_models = $this->getAllModels();

        foreach ($app_models as $app_model) {
            //parse model
            $pieces = explode('.', $app_model);
            if (count($pieces) != 2) {
                throw new Exception('Parse error in AppModelList');
            }
            //check form match
            $app_model = $pieces[1];
            $name_space = $pieces[0];

            if ($app_model == $model) {
                return $name_space . '\\' . $model;
            }
            if ($app_model == rtrim($model, 's')) {
                return $name_space . '\\' . rtrim($model, 's');
            }
        }

        throw new Exception('Model not found: ' . $url_model);
    }

    /**
     * Return all models added to admin area (without full path)
     *
     * @return Array
     */
    public function getModelsForIndex()
    {
        $models = [];
        $all_models = $this->getAllModels();

        foreach ($all_models as $model) {
            //parse model
            $pieces = explode('.', $model);
            if (count($pieces) != 2) {
                throw new Exception('Parse error in AppModelList');
            }
            //check form match
            $app_model = $pieces[1];
            $control_model = "App\\EasyApi\\" . $app_model;
            $allowed = $control_model::allowed();
            $link = $this->convertModelToLink($app_model);

            // add to actions
            $models[$app_model]['actions'] = [
                'GET' => [
                    'route' => '/' . env('EASY_API_BASE_URL', 'easy-api') . '/' . $link,
                    'fields' => $this->addTypesToFields($control_model::index(), str_replace('.', '\\', $model))
                ]
            ];
            if (in_array('create', $allowed)) {
                $models[$app_model]['actions']['POST'] = [
                    'route' => '/' . env('EASY_API_BASE_URL', 'easy-api') . '/' . $link,
                    'fields' => $this->addTypesToFields($control_model::create(), str_replace('.', '\\', $model))
                ];
            }
            if (in_array('update', $allowed)) {
                $models[$app_model]['actions']['PUT/PATCH'] = [
                    'route' => '/' . env('EASY_API_BASE_URL', 'easy-api') . '/' . $link . '/{id}',
                    'fields' => $this->addTypesToFields($control_model::update(), str_replace('.', '\\', $model))
                ];
            }
            if (in_array('delete', $allowed)) {
                $models[$app_model]['actions']['DELETE'] = [
                    'route' => '/' . env('EASY_API_BASE_URL', 'easy-api') . '/' . $link . '/{id}'
                ];
            }
        }
        return $models;
    }

    /**
     * Helper for Above function, adds types to fields
     *
     * @param [type] $fields
     * @param [type] $model
     * @return array
     */
    private function addTypesToFields($fields, $model) {
        $results = [];

        foreach($fields as $field) {
            $results[$field] = self::inputType($field, $model);
        }

        return $results;
    }

    /**
     * Strip the model away from the models full path
     *
     * @return Array
     */
    public function stripPathFromModel($model)
    {
        $pieces = explode('\\', $model);
        $length = count($pieces);

        return $pieces[$length - 1];
    }

    /**
     * Strip the Global/Parent away from partials
     *
     * @return Array
     */
    public function stripParentFromPartials($partials)
    {
        $output = [];

        foreach($partials as $partial) {
            $pieces = explode('.', $partial);
            $output[] = $pieces[1];
        }

        return $output;
    }

    /**
     * Get Partials that belong to a specific Model
     *
     * @return Array
     */
    public function getPartials($model)
    {
        $output = [];

        $partials = $this->getAllPartialModels();

        foreach($partials as $partial) {
            $pieces = explode('.', $partial);

            if ($pieces[0] == $model)
                $output[] = $pieces[1];
        }

        return $output;
    }

    /**
     * Return all models added to admin area
     * Format Namespace.Model
     *
     * @return Array
     */
    public function getAllModels()
    {
        try {
            return AppModelList::models();
        }
        catch (Throwable $t) {
            throw new Exception('Parse Error: AppModelList.php has been corrupted.');
        }
    }

    /**
     * Return all page models added to admin area
     * Format Model
     *
     * @return Array
     */
    public function getAllPageModels()
    {
        try {
            return AppModelList::pageModels();
        }
        catch (Throwable $t) {
            throw new Exception('Parse Error: AppModelList.php has been corrupted.');
        }
    }

    /**
     * Return all post models added to admin area
     * Format Model
     *
     * @return Array
     */
    public function getAllPostModels()
    {
        try {
            return AppModelList::postModels();
        }
        catch (Throwable $t) {
            throw new Exception('Parse Error: AppModelList.php has been corrupted.');
        }
    }

    /**
     * Return all partial models added to admin area
     * Format Model
     *
     * @return Array
     */
    public function getAllPartialModels()
    {
        try {
            return AppModelList::partialModels();
        }
        catch (Throwable $t) {
            throw new Exception('Parse Error: AppModelList.php has been corrupted.');
        }
    }

    /**
     * Get public model file
     *
     * @return Array
     */
    public function getPublicModel($model_path)
    {
        $model = $this->stripPathFromModel($model_path);
        $app_model = "App\\EasyApi\\" . $model;

        try {
            if (class_exists($app_model)) {
                return $app_model;
            }
            throw new Exception('Error: Public model does not exist.');
        }
        catch (Throwable $t) {
            throw new Exception('Error: Public model does not exist.');
        }
    }

    /**
     * Return all models added to admin area
     * Format Namespace\Model
     *
     * @return Array
     */
    public function getAllConvertedModels()
    {
        $models = $this->getAllModels();
        $converted = [];
        foreach ($models as $model) {
            array_push($converted, str_replace('.', '\\', $model));
        }
        return $converted;
    }

    /**
     * Convert Model to Link
     *
     * @return string
     */
    public function convertModelToLink($model)
    {
        $link = $model;

        for ($i = 1; $i < strlen($link); $i++) {
            if (ctype_upper($link[$i])) {
                $link = substr_replace($link, '-' . strtolower($link[$i]), $i, 1);
            }
        }
        return strtolower($link);
    }

    /**
     * Check if model has ID field
     *
     * @return boolean
     */
    public function checkModelHasId($model_path)
    {
        $record = new $model_path;
        $table = $record->getTable();

        $columns = DB::select('SHOW COLUMNS FROM ' . $table);

        foreach($columns as $column_data) {
            if ($column_data->Field == 'id') {
                return true;
            }
        }
        return false;
    }
}


















