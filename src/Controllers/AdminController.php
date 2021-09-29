<?php
namespace DevsRyan\LaravelEasyApi\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use DevsRyan\LaravelEasyApi\Services\HelperService;
use DevsRyan\LaravelEasyApi\Services\ValidationService;
use Auth;

class AdminController extends Controller
{

    /**
     * Helper Service.
     *
     * @var class
     */
    protected $helperService;

    /**
     * Validation Service.
     *
     * @var class
     */
    protected $validationService;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->helperService = new HelperService;
        $this->validationService = new ValidationService;

        //EasyApi Middleware
        $this->middleware(function ($request, $next) {
            if ($request->has('api_token') && $request->api_token == env('API_TOKEN', -1)) {
                return $next($request);
            }
            return abort(403);
        });
    }

    /**
     * Display landing page.
     *
     * @return \Illuminate\Http\Response
     */
    public function home()
    {
        return response()->json([
            'models' => $this->helperService->getModelsForIndex()
        ], 200);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  string  $model
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index($model, Request $request)
    {
        //gather info for action
        $url_model = $model;
        $model_path = $this->helperService->convertUrlModel($url_model);
        $model = $this->helperService->stripPathFromModel($model_path);
        $nav_items = $this->helperService->getModelsForNav();
        $partials = $this->helperService->getAllPartialModels();
        $partial_models = $this->helperService->stripParentFromPartials($partials);
        $appModel = "App\\EasyApi\\" . $model;
        $index_columns = $appModel::index();
        $allowed = $appModel::allowed();
        $parent_id = ($request->parent_id && ctype_digit($request->parent_id) && intval($request->parent_id) > 0)
            ? $request->parent_id : null;

        //get data
        $check_model = $model_path::first();
        if ($check_model && $check_model->id)
            $data = $model_path::orderByDesc('id');
        else if ($check_model && $check_model->create_at)
            $data = $model_path::orderByDesc('create_at');
        else $data = $model_path::query();

        // files
        $file_fields = $appModel::files();

        //apply filters
        foreach($request->except(['parent_id']) as $filter => $value) {
            if ($value === null) continue;

            if (strpos($filter, '__from') !== false) { //from comparison
                $filter = str_replace('__from', '', $filter);
                if (!$check_model->$filter) continue;
                $data = $data->where($filter, '>=', date($value));
                continue;
            }
            if (strpos($filter, '__to') !== false) { //from comparison
                $filter = str_replace('__to', '', $filter);
                if (!$check_model->$filter) continue;
                $data = $data->where($filter, '<=', date($value));
                continue;
            }

            // regular comparison
            if (!$check_model) continue;
            if (!$check_model->$filter) continue;
            $data = $data->where($filter, 'LIKE', "%$value%");
        }

        //apply parent id filter
        if ($request->has('parent_id')) {
            $parent = $this->helperService->findParent($model);
            $relationship_column_name = $this->helperService->findParentIdColumnName($parent, $nav_items);
            $data = $data->where($relationship_column_name, $request->parent_id);
        }

        //paginate
        $data = $data->paginate(50);

        return view('easy-api::index')
            ->with('data', $data)
            ->with('model', $model)
            ->with('model_path', $model_path)
            ->with('nav_items', $nav_items)
            ->with('partials', $partials)
            ->with('partial_models', $partial_models)
            ->with('title', 'Index')
            ->with('index_columns', $index_columns)
            ->with('allowed', $allowed)
            ->with('url_model', $url_model)
            ->with('file_fields', $file_fields)
            ->with('parent_id', $parent_id);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  string  $model
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create($model, Request $request)
    {
        //gather info for action
        $url_model = $model;
        $model_path = $this->helperService->convertUrlModel($url_model);
        $model = $this->helperService->stripPathFromModel($model_path);
        $nav_items = $this->helperService->getModelsForNav();
        $partials = $this->helperService->getAllPartialModels();
        $partial_models = $this->helperService->stripParentFromPartials($partials);
        $appModel = "App\\EasyApi\\" . $model;
        $model_partials = $this->helperService->getPartials($model);
        $parent_id = ($request->parent_id && ctype_digit($request->parent_id) && intval($request->parent_id) > 0)
            ? $request->parent_id : null;

        //check allowed
        $allowed = $appModel::allowed();
        if (!in_array('create', $allowed)) abort(403, 'Unauthorized action.');

        //create fields
        $fields = $appModel::create();
        $required_fields = $this->validationService->getRequiredFields($model_path);

        // wysiwyg_editors
        $wysiwyg_editors = $appModel::wysiwyg_editors();

        // files
        $file_fields = $appModel::files();

        //apply parent id filter
        $relationship_column_name = null;
        if ($request->has('parent_id')) {
            $parent = $this->helperService->findParent($model);
            $relationship_column_name = $this->helperService->findParentIdColumnName($parent, $nav_items);
        }

        //return view
        return view('easy-api::create')
            ->with('model', $model)
            ->with('model_path', $model_path)
            ->with('allowed', $allowed)
            ->with('url_model', $url_model)
            ->with('nav_items', $nav_items)
            ->with('partials', $partials)
            ->with('partial_models', $partial_models)
            ->with('fields', $fields)
            ->with('required_fields', $required_fields)
            ->with('wysiwyg_fields', $wysiwyg_editors)
            ->with('file_fields', $file_fields)
            ->with('model_partials', $model_partials)
            ->with('parent_id', $parent_id)
            ->with('relationship_column_name', $relationship_column_name);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  string  $model
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store($model, Request $request)
    {
        //gather info for view
        $url_model = $model;
        $model_path = $this->helperService->convertUrlModel($url_model);
        $model = $this->helperService->stripPathFromModel($model_path);
        $appModel = "App\\EasyApi\\" . $model;

        //check allowed
        $allowed = $appModel::allowed();
        $file_fields = $appModel::files();
        if (!in_array('create', $allowed)) abort(403, 'Unauthorized action.');

        //create
        $response = $this->validationService->createModel($request, $model_path, $model, $file_fields);

        // check / set parent_id
        $redirect_parent_id = '';
        if ($request->has('easy_admin_submit_with_parent_id')) {
            $redirect_parent_id = '?parent_id=' . $request->easy_admin_submit_with_parent_id;
        }

        //return redirect to edit when submit + add partials clicked
        if ($request->has('partial_redirect_easy_admin') && $response['record'] !== null) {
            return redirect('/easy-api/'. $url_model . '/' . $response['record']->id .'/edit' . $redirect_parent_id)
                ->with('message', $response['message']);
        }

        // create + redirect back to create form
        if ($response['record'] === null)
            return redirect('/easy-api/'. $url_model .'/create' . $redirect_parent_id)
                ->withInput()->with('message', $response['message']);
        else
            return redirect('/easy-api/'. $url_model .'/create' . $redirect_parent_id)
                ->with('message', $response['message']);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $model
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function edit($model, $id, Request $request)
    {
        //gather info for view
        $url_model = $model;
        $model_path = $this->helperService->convertUrlModel($url_model);
        $model = $this->helperService->stripPathFromModel($model_path);
        $nav_items = $this->helperService->getModelsForNav();
        $partials = $this->helperService->getAllPartialModels();
        $partial_models = $this->helperService->stripParentFromPartials($partials);
        $appModel = "App\\EasyApi\\" . $model;
        $model_partials = $this->helperService->getPartials($model);
        $parent_id = ($request->parent_id && ctype_digit($request->parent_id) && intval($request->parent_id) > 0)
            ? $request->parent_id : null;

        // check allowed to edit or view only mode
        $allowed = $appModel::allowed();

        //update fields
        $fields = $appModel::update();
        $required_fields = $this->validationService->getRequiredFields($model_path);

        // wysiwyg_editors
        $wysiwyg_editors = $appModel::wysiwyg_editors();

        // files
        $file_fields = $appModel::files();

        //find model
        $data = $model_path::findOrFail($id);

        //apply parent id filter
        $relationship_column_name = null;
        if ($request->has('parent_id')) {
            $parent = $this->helperService->findParent($model);
            $relationship_column_name = $this->helperService->findParentIdColumnName($parent, $nav_items);
        }

        //return view
        return view('easy-api::edit')
            ->with('id', $id)
            ->with('model', $model)
            ->with('model_path', $model_path)
            ->with('allowed', $allowed)
            ->with('url_model', $url_model)
            ->with('nav_items', $nav_items)
            ->with('partials', $partials)
            ->with('partial_models', $partial_models)
            ->with('fields', $fields)
            ->with('required_fields', $required_fields)
            ->with('data', $data)
            ->with('wysiwyg_fields', $wysiwyg_editors)
            ->with('file_fields', $file_fields)
            ->with('model_partials', $model_partials)
            ->with('parent_id', $parent_id)
            ->with('relationship_column_name', $relationship_column_name);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  string  $model
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update($model, $id, Request $request)
    {
        //gather info for action
        $url_model = $model;
        $model_path = $this->helperService->convertUrlModel($url_model);
        $model = $this->helperService->stripPathFromModel($model_path);
        $appModel = "App\\EasyApi\\" . $model;
        $allowed = $appModel::allowed();

        //check allowed
        $allowed = $appModel::allowed();
        $file_fields = $appModel::files();
        if (!in_array('update', $allowed)) abort(403, 'Unauthorized action.');

        //find model
        $data = $model_path::findOrFail($id);

        //update
        $message = $this->validationService->updateModel($request, $data, $model, $file_fields);

        // check / set parent_id
        $redirect_parent_id = '';
        if ($request->has('easy_admin_submit_with_parent_id')) {
            $redirect_parent_id = '?parent_id=' . $request->easy_admin_submit_with_parent_id;
        }

        //return redirect
        return redirect('/easy-api/'. $url_model .'/'. $id .'/edit' . $redirect_parent_id)
            ->with('message', $message);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $model
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function destroy($model, $id, Request $request)
    {
        //gather info for action
        $url_model = $model;
        $model_path = $this->helperService->convertUrlModel($url_model);
        $model = $this->helperService->stripPathFromModel($model_path);
        $appModel = "App\\EasyApi\\" . $model;
        $allowed = $appModel::allowed();

        //check allowed
        $allowed = $appModel::allowed();
        $file_fields = $appModel::files();
        if (!in_array('delete', $allowed)) abort(403, 'Unauthorized action.');

        //find model
        $data = $model_path::findOrFail($id);

        //do not allow user to delete themselves
        if ($model === 'User' && $data == Auth::user())
            return redirect()->back()->with('message', 'Unauthorized action, cannot delete the currently authenticated user.');

        //delete model
        $message = $this->validationService->deleteModel($data, $model, $file_fields);

        // check / set parent_id
        $redirect_parent_id = '';
        if ($request->has('easy_admin_delete_with_parent_id')) {
            $redirect_parent_id = '?parent_id=' . $request->easy_admin_delete_with_parent_id;
        }

        //return redirect
        return redirect('/easy-api/'. $url_model . '/index' . $redirect_parent_id)
            ->with('message', $message);
    }

}

