<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Repository\Repository;
use App\Models\ChildEvent;
use Illuminate\Http\Request;

class ChildEventController extends Controller
{
    private ChildEvent $model;

    private Repository $repository;

    public function __construct()
    {
        $this->model = new ChildEvent();
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
        if (! $request->filled('recorded_by_user_id') && $this->getAuthenticatedUserId($request)) {
            $request->merge(['recorded_by_user_id' => $this->getAuthenticatedUserId($request)]);
        }

        if (! $request->filled('event_at')) {
            $request->merge(['event_at' => now()]);
        }

        $validator = $this->repository->check($request, $this->repository->rules());
        if (true !== $validator) {
            return $validator;
        }

        return $this->respondOk($this->repository->store($request));
    }

    public function update(Request $request, int $id)
    {
        $validator = $this->repository->check($request, $this->repository->update_rules(), $id);
        if (true !== $validator) {
            return $validator;
        }

        return $this->respondOk($this->repository->update($request, $id));
    }

    public function delete(Request $request, int $id)
    {
        return $this->respondOk($this->repository->delete($request, $id));
    }

    /**
     * Marque une demande de fournitures (couches, lingettes, habits de rechange)
     * comme ayant été honorée/apportée par le parent.
     */
    public function fulfillSupply(Request $request, int $id)
    {
        $event = ChildEvent::findOrFail($id);

        $event->supply_fulfilled_at = now();
        $metadata = is_array($event->metadata) ? $event->metadata : [];
        $metadata['supply_status'] = 'fulfilled';
        $metadata['fulfilled_by_user_id'] = $this->getAuthenticatedUserId($request);
        $event->metadata = $metadata;
        $event->save();

        return $this->respondOk($event, 'Fournitures réceptionnées et marquées comme complétées.');
    }
}
