<?php

namespace App\Http\Controllers\Repository;

use Illuminate\Http\Request;

interface IRepository
{
    public function setModel($model);

    public function getModel();

    public function rules($multiple = false);

    public function update_rules();

    public function parse_filters(Request $request);

    public function show($request, $id, $resource = null);

    public function store($request);

    public function update($request, $id);

    public function delete($request, $id);

    public function check($request, $rules = [], $id = null);

    public function getRules($rules, $id);
}
