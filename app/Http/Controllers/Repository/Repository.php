<?php

namespace App\Http\Controllers\Repository;

use App\Http\Controllers\Traits\ApiResponse;
use BenjiMeugi\Contracts\IModel;
use BenjiMeugi\Parser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Repository implements IRepository
{
    use ApiResponse;

    protected Model $model;

    public function __construct($model = null)
    {
        if ($model instanceof Model) {
            $this->model = $model;
        } elseif ($model) {
            $this->model = new $model();
        }
    }

    public function setModel($model)
    {
        $this->model = is_string($model) ? new $model() : $model;

        return $this;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function rules($multiple = false)
    {
        return $this->model->rules($multiple);
    }

    public function update_rules()
    {
        return $this->model->update_rules();
    }

    public function parse_filters(Request $request)
    {
        $query = is_string($request->input('query_'))
            ? json_decode($request->input('query_'), true)
            : $request->input('query_');

        return (new Parser())->parse_filters((array) $query, $this->model);
    }

    public function show($request, $id, $resource = null)
    {
        $query = is_string($request->input('query_'))
            ? json_decode($request->input('query_'), true)
            : $request->input('query_');

        $data = (new Parser())
            ->parse_filters((array) $query, $this->model)
            ->where($this->model->getKeyName(), $id)
            ->first();

        if (! $data) {
            return $this->respondNotFound($id);
        }

        return $this->respondOk(is_null($resource) ? $data : $resource::call($data, $request->all()));
    }

    public function store($request)
    {
        return (new Parser())->store($this->getRequestInput($request), $this->model);
    }

    public function update($request, $id)
    {
        (new Parser())->update($this->getRequestInput($request), $this->model, $id);

        return $this->model->newQuery()->find($id);
    }

    public function delete($request, $id)
    {
        $validator = Validator::make(['id' => $id], ['id' => 'exists:' . $this->model->getTable() . ',id']);
        if ($validator->fails()) {
            return $this->respondBadRequest($validator->errors()->messages());
        }

        return (new Parser())->delete($request->all(), $this->model, $id);
    }

    public function check($request, $rules = [], $id = null)
    {
        $requestInput = $this->getRequestInput($request);
        $requestInput = is_null($id) ? $requestInput : array_merge($requestInput, [$this->model->getKeyName() => $id]);

        $rules = $this->getRules($rules, $id);

        if ($id !== null) {
            $rules[$this->model->getKeyName()] = [
                'exists:' . $this->model->getTable() . ',' . $this->model->getKeyName(),
            ];
        }

        $validator = Validator::make($requestInput, $rules);
        if ($validator->fails()) {
            return $this->respondBadRequest($validator->errors()->messages());
        }

        return true;
    }

    private function getRequestInput($request): array
    {
        if (is_array($request)) {
            return $request;
        }

        $input = $request->all();
        if ($input !== []) {
            return $input;
        }

        $requestInput = $request->request->all();
        if ($requestInput !== []) {
            return $requestInput;
        }

        return $_POST !== [] ? $_POST : $request->json()->all();
    }

    public function getRules($rules, $id)
    {
        $response = [];
        foreach ($rules as $key => $rule) {
            $rule = is_array($rule) ? $rule : explode('|', $rule);
            if (in_array(IModel::IGNORE_RULE, $rule)) {
                $rule = array_merge(
                    $rule,
                    [Rule::unique($this->model->getTable(), $key)->ignore($id)]
                );

                unset($rule[array_search(IModel::IGNORE_RULE, $rule)]);
            }
            $response[$key] = $rule;
        }

        return $response;
    }
}
