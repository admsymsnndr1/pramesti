<?php

namespace App\Http\Controllers;

use App\Models\Deliverable;
use App\Models\WbsItem;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DeliverableController extends Controller
{
    /**
     * File types a deliverable may contain.
     */
    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

    private const MAX_KB = 10240;

    public function __construct()
    {
        $this->middleware('permission:deliverables.create')->only(['create', 'store']);
        $this->middleware('permission:deliverables.update')->only(['edit', 'update']);
        $this->middleware('permission:deliverables.delete')->only('destroy');
        $this->middleware('permission:deliverables.upload')->only('uploadFile');
    }

    public function create(WbsItem $task)
    {
        return view('deliverables.create', [
            'task'            => $task,
            'breadcrumb'      => 'Deliverables',
            'breadcrumb_item' => 'Create',
        ]);
    }

    public function edit(Deliverable $deliverable)
    {
        $deliverable->load('files');

        return view('deliverables.edit', [
            'deliverable'     => $deliverable,
            'task'            => $deliverable->task,
            'breadcrumb'      => 'Deliverables',
            'breadcrumb_item' => 'Edit',
        ]);
    }

    public function store(Request $request, WbsItem $task)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'url'         => 'nullable|url|max:255',
            'files'       => 'nullable|array',
            'files.*'     => $this->fileRule(),
        ]);

        $deliverable = Deliverable::create([
            'wbs_item_id' => $task->id,
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'url'         => $data['url'] ?? null,
            'uploaded_by' => Auth::id(),
            'status'      => 'draft',
        ]);

        foreach ($request->file('files', []) as $file) {
            $this->saveFile($file, $deliverable);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Deliverable added',
            'redirect' => $this->backTo($task),
        ]);
    }

    public function update(Request $request, Deliverable $deliverable)
    {
        $user = Auth::user();

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'url'         => 'nullable|url|max:255',
            'status'      => [
                'required',
                // approving needs the permission (or the item was already approved)
                $user->hasPermission('deliverables.approve') || $deliverable->status === 'approved'
                    ? 'in:draft,submitted,approved'
                    : 'in:draft,submitted',
            ],
            'files'   => 'nullable|array',
            'files.*' => $this->fileRule(),
        ]);

        $deliverable->update([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'url'         => $data['url'] ?? null,
            'status'      => $data['status'],
        ]);

        foreach ($request->file('files', []) as $file) {
            $this->saveFile($file, $deliverable);
        }

        return response()->json([
            'success'  => true,
            'message'  => 'Deliverable updated',
            'redirect' => $this->backTo($deliverable->task),
        ]);
    }

    public function uploadFile(Request $request, Deliverable $deliverable)
    {
        $request->validate(['file' => ['required', ...$this->fileRule()]]);

        $this->saveFile($request->file('file'), $deliverable);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded',
        ]);
    }

    public function destroy(Deliverable $deliverable)
    {
        Storage::disk('public')->delete($deliverable->files->pluck('file_path')->all());

        $deliverable->delete(); // deliverable_files rows cascade

        return back()->with('success', 'Deliverable deleted');
    }

    private function fileRule(): array
    {
        return ['file', 'max:' . self::MAX_KB, 'mimes:' . implode(',', self::EXTENSIONS)];
    }

    /**
     * Store an uploaded file under a slugged, timestamped name.
     */
    private function saveFile(UploadedFile $file, Deliverable $deliverable): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        abort_unless(in_array($extension, self::EXTENSIONS, true), 422, 'File type not allowed');

        $baseName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
        $path = $file->storeAs('deliverables', $baseName . '-' . Str::random(8) . '.' . $extension, 'public');

        $deliverable->files()->create([
            'file_path'   => $path,
            'file_name'   => $file->getClientOriginalName(),
            'file_size'   => $file->getSize(),
            'file_type'   => $extension,
            'uploaded_by' => Auth::id(),
        ]);
    }

    /**
     * Users without WBS access go back to their own task page.
     */
    private function backTo(WbsItem $task): string
    {
        return Auth::user()->hasPermission('wbs.view')
            ? route('employee.tasks.show', $task)
            : route('employee.tasks.detail-task', $task);
    }
}
