<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Repository\Repository;
use App\Models\ChildGuardian;
use Illuminate\Http\Request;

class ChildGuardianController extends Controller
{
    private ChildGuardian $model;

    private Repository $repository;

    public function __construct()
    {
        $this->model = new ChildGuardian();
        $this->repository = new Repository($this->model);
    }

    public function index(Request $request)
    {
        $query = $this->repository->parse_filters($request);

        if ($request->has('page') && $request->has('per_page')) {
            return $this->respondOk($query->paginate($request->input('per_page')));
        }

        return $this->respondOk($query->get());
    }

    public function show(Request $request, int $id)
    {
        return $this->repository->show($request, $id);
    }

    public function store(Request $request)
    {
        $validator = $this->repository->check($request, $this->repository->rules());
        if (true !== $validator) {
            return $validator;
        }

        return $this->respondOk($this->repository->store($request), 'Lien parent-enfant enregistré avec succès.');
    }

    public function update(Request $request, int $id)
    {
        $validator = $this->repository->check($request, $this->repository->update_rules(), $id);
        if (true !== $validator) {
            return $validator;
        }

        return $this->respondOk($this->repository->update($request, $id), 'Lien parent-enfant mis à jour avec succès.');
    }

    public function delete(Request $request, int $id)
    {
        return $this->respondOk($this->repository->delete($request, $id), 'Lien parent-enfant dissocié avec succès.');
    }
}
