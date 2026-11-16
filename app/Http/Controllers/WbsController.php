<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use App\Models\WbsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class WbsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:wbs.view')->only('index');
        $this->middleware('permission:wbs.create')->only(['create', 'store']);
        $this->middleware('permission:wbs.update')->only(['edit', 'update']);
        $this->middleware('permission:wbs.delete')->only('destroy');
    }

    /**
     * Project list to open a project's WBS (menu WBS for roles without the Project menu)
     */
    public function index()
    {
        $breadcrumb = 'WBS';
        $breadcrumb_item = 'List';

        $projects = Project::withCount('wbsItems')
            ->orderByRaw("FIELD(status, 'ongoing', 'draft', 'on_hold', 'completed')")
            ->orderBy('name')
            ->get();

        return view('wbs.projects', compact('breadcrumb', 'breadcrumb_item', 'projects'));
    }


    /* ======================
     * CREATE
     * ====================== */
    public function create(Request $request)
    {
        $breadcrumb = 'WBS';
        $breadcrumb_item = 'Create';
        $parentId = $request->get('parent_id');
        $project_id = $request->project_id;
        
        $parent = null;
        if ($parentId) {
            $parent = WbsItem::findOrFail($parentId);
        }

        // dd($parent->start_date, $parent->end_date);

        return view('wbs.create', compact('breadcrumb', 'breadcrumb_item', 'parent', 'project_id'));
    }

    public function store(Request $request)
    {
        $parent = $request->parent_id
            ? WbsItem::findOrFail($request->parent_id)
            : null;
    
        $data = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'parent_id'  => ['nullable', Rule::exists('wbs_items', 'id')->where('project_id', $request->input('project_id'))],
            'name'       => 'required|string|max:255',
            'type'       => 'required|in:wbs,task',
            'weight'     => 'required|numeric|min:0|max:100',
            'start_date' => [
                'required',
                'date',
                $parent ? 'after_or_equal:' . $parent->start_date : null,
                $parent ? 'before_or_equal:' . $parent->end_date : null,
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                $parent ? 'before_or_equal:' . $parent->end_date : null,
            ],
        ]);
    
        $data['progress']   = 0;
        $data['created_by'] = auth()->id();
        $data['is_locked']  = $data['type'] === 'wbs';
        $data['parent_id']  = $data['parent_id'] ?? null;
        $data['sort_order'] = WbsItem::where('project_id', $data['project_id'])
            ->where('parent_id', $data['parent_id'])
            ->max('sort_order') + 1;
        $data['code'] = $this->generateCode((int) $data['project_id'], $data['parent_id'] ? (int) $data['parent_id'] : null);
    
        // progress of the parent & project is recalculated by WbsItemObserver
        $wbs = WbsItem::create($data);

        return response()->json([
            'message' => 'Item added',
            'redirect' => route('projects.wbs', $data['project_id']),
        ]);
    }    

    /**
     * Outline number: "3" for the third root item of the project, "3.2" for the second child of "3".
     */
    private function generateCode(int $projectId, ?int $parentId): string
    {
        $siblings = WbsItem::where('project_id', $projectId)->where('parent_id', $parentId)->count() + 1;

        if (!$parentId) {
            return (string) $siblings;
        }

        return WbsItem::whereKey($parentId)->value('code') . '.' . $siblings;
    }

    /* ======================
     * EDIT
     * ====================== */
    public function edit($id)
    {
        $breadcrumb = 'WBS';
        $breadcrumb_item = 'Edit';
        $wbs = WbsItem::findOrFail($id);

        // candidate parents for the dropdown (excluding itself and its descendants)
        $allWbs = WbsItem::where('project_id', $wbs->project_id)
            ->whereNotIn('id', [$wbs->id, ...$wbs->descendantIds()])
            ->get();

        $isLeaf = $wbs->isLeaf();

        return view('wbs.edit', compact('breadcrumb', 'breadcrumb_item', 'wbs', 'allWbs', 'isLeaf'));
    }

    /* ======================
     * UPDATE
     * ====================== */
    public function update(Request $request, $id)
    {
        $wbs = WbsItem::findOrFail($id);

        $data = $request->validate([
            'parent_id' => [
                'nullable',
                Rule::exists('wbs_items', 'id')->where('project_id', $wbs->project_id),
                Rule::notIn([$wbs->id, ...$wbs->descendantIds()]),
            ],
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'progress' => 'nullable|numeric|min:0|max:100',
            'status' => ['nullable', Rule::in(array_keys(WbsItem::STATUSES))],
            'weight' => 'nullable|numeric|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ], [
            'parent_id.not_in' => 'An item cannot be moved under itself or one of its sub items.',
        ]);

        // parent progress is always calculated from its sub items
        if (!$wbs->isLeaf() || !isset($data['progress'])) {
            unset($data['progress']);
        }
        // status only applies to leaf items; when not changed it follows the progress (WbsItemObserver)
        if (!$wbs->isLeaf() || !isset($data['status'])) {
            unset($data['status']);
        }
        $data['weight'] = $data['weight'] ?? 0;

        // progress of the parent & project is recalculated by WbsItemObserver
        $wbs->update($data);

        return redirect()->route('projects.wbs', $wbs->project_id)
            ->with('success', 'WBS item updated.');
    }

    /* ======================
     * DELETE
     * ====================== */
    public function destroy($id)
    {
        $wbs = WbsItem::findOrFail($id);
        $projectId = $wbs->project_id;

        $wbs->delete();

        return redirect()->route('projects.wbs', $projectId)
            ->with('success', 'WBS item deleted.');
    }
}
