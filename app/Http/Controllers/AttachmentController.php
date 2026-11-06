<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Repository\Repository;
use App\Models\Attachment;
use App\Models\Child;
use App\Models\ChildEvent;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    private Attachment $model;

    private Repository $repository;

    public function __construct()
    {
        $this->model = new Attachment();
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

    /**
     * Ajoute une pièce justificative à un dossier d'inscription (Section VII du PDF :
     * acte de naissance, carnet de vaccination, certificat médical, photos d'identité).
     */
    public function storeForRegistration(Request $request, int $id)
    {
        Registration::findOrFail($id);

        return $this->storeForAttachable($request, 'registration', $id);
    }

    /**
     * Ajoute une pièce jointe (photo d'activité, ordonnance) à un événement de l'enfant.
     */
    public function storeForChildEvent(Request $request, int $id)
    {
        ChildEvent::findOrFail($id);

        return $this->storeForAttachable($request, 'child_event', $id);
    }

    /**
     * Ajoute une pièce jointe directement rattachée au dossier d'un enfant.
     */
    public function storeForChild(Request $request, int $id)
    {
        Child::findOrFail($id);

        return $this->storeForAttachable($request, 'child', $id);
    }

    /**
     * Logique commune d'enregistrement de pièce jointe sur le disque local privé.
     */
    private function storeForAttachable(Request $request, string $attachableType, int $attachableId)
    {
        $validator = $this->repository->check($request, $this->repository->rules());
        if (true !== $validator) {
            return $validator;
        }

        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store('attachments');

        $attachment = Attachment::create([
            'document_type_id' => $request->input('document_type_id'),
            'file_path' => $path,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'attachable_type' => $attachableType,
            'attachable_id' => $attachableId,
            'is_verified' => (bool) $request->boolean('is_verified', false),
            'uploaded_by_user_id' => $this->getAuthenticatedUserId($request),
        ]);

        return $this->respondOk($attachment->load('documentType'));
    }

    public function update(Request $request, int $id)
    {
        $validator = $this->repository->check($request, $this->repository->update_rules(), $id);
        if (true !== $validator) {
            return $validator;
        }

        return $this->respondOk($this->repository->update($request, $id));
    }

    /**
     * Sert le fichier physique via une route contrôlée par permission.
     */
    public function download(int $id)
    {
        $attachment = Attachment::findOrFail($id);

        return Storage::disk('local')->download(
            $attachment->file_path,
            $attachment->original_name ?? basename($attachment->file_path)
        );
    }

    public function delete(Request $request, int $id)
    {
        $attachment = Attachment::findOrFail($id);
        Storage::disk('local')->delete($attachment->file_path);

        return $this->respondOk($this->repository->delete($request, $id));
    }
}
