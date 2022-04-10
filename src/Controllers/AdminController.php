<?php
namespace DevsRyan\LaravelEasyApi\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DevsRyan\LaravelEasyApi\Services\HelperService;
use DevsRyan\LaravelEasyApi\Services\ValidationService;

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

        /**
         * EasyApi Middleware
         * Check if api requires API token to access, verify token if it does.
         */
        $this->middleware(function ($request, $next) {

            if (! env('EASY_API_REQUIRES_TOKEN', false)) {
                return $next($request);
            }

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
        return response()->json($this->helperService->getModelsForIndex(), 200);
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

        //get data
        $check_model = $model_path::first();
        $data = $model_path::query();

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

        // append partials
        $data = $this->helperService->appendPartials($model, $data);

        // allow sorting
        if ($request->has('sort_by')) {
            $data = $this->helperService->sortResults($check_model, $request->sort_by, $data);
        }

        //paginate
        $data = $data->paginate(50);

        return response()->json($data, 200);
    }


    /**
     * Display a listing of the resource.
     *
     * @param  string  $model
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show($model, $id)
    {
        //gather info for action
        $url_model = $model;
        $model_path = $this->helperService->convertUrlModel($url_model);
        $model = $this->helperService->stripPathFromModel($model_path);

        //get data
        $data = $model_path::where('id', $id);

        // append partials
        $data = $this->helperService->appendPartials($model, $data);

        // get result or 404
        $data = $data->firstOrFail();

        return response()->json($data, 200);
    }

}

