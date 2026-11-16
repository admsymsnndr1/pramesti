<?php

namespace App\Http\Controllers;

use App\Exports\ProjectSummaryExport;
use App\Models\Deliverable;
use App\Models\Project;
use App\Models\User;
use App\Models\WbsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelWriter;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:projects.view')->only(['index', 'show', 'timeline', 'deliverables']);
        $this->middleware('permission:wbs.view')->only('wbs');
        $this->middleware('permission:projects.create')->only(['create', 'store']);
        $this->middleware('permission:projects.update')->only(['edit', 'update']);
        $this->middleware('permission:projects.delete')->only('destroy');
        $this->middleware('permission:projects.report')->only('report');
    }

    /* =====================
     | Project List
     ===================== */
    public function index()
    {
        $breadcrumb = 'Projects';
        $breadcrumb_item = 'List';
        $projects = Project::with('pic')
            ->latest()
            ->paginate(10);
        $role = auth()->user()->role;
        return view('projects.index', compact('role', 'breadcrumb', 'breadcrumb_item', 'projects'));
    }

    public function report(Project $project, ExcelWriter $excel)
    {
        $fileName = 'Project_Report_' . str_replace(' ', '_', $project->name) . '_' . now()->format('YmdHis') . '.xlsx';
        
        return $excel->download(new ProjectSummaryExport($project), $fileName);
    }

    /* =====================
     | Create Form
     ===================== */
    public function create()
    {
        $breadcrumb = 'Projects';
        $breadcrumb_item = 'List';
        $users = User::orderBy('name')->where('role', 'pm')->get();

        return view('projects.create', compact('breadcrumb', 'breadcrumb_item', 'users'));
    }

    /* =====================
     | Store
     ===================== */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => 'nullable|string|max:50',
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'status'      => 'required|in:draft,ongoing,completed,on_hold',
            'pm_id'      => 'nullable|exists:users,id',
        ]);

        $data['created_by'] = Auth::id();

        Project::create($data);

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project created');
    }

    /* =====================
     | Project Detail (Overview)
     ===================== */
    public function show(Project $project)
    {
        $breadcrumb = 'Projects';
        $breadcrumb_item = 'List';
        $project->load(['pic', 'creator']);
    
        // progress is rolled up from the WBS (WbsProgressService)
        $projectProgress = $project->progress;

        // === WBS STATS ===
        $totalWbs = WbsItem::where('project_id', $project->id)->count();

        $leafWbs = WbsItem::where('project_id', $project->id)
            ->whereDoesntHave('children')
            ->count();
    
        return view('projects.show', [
            'breadcrumb' => $breadcrumb, 
            'breadcrumb_item' => $breadcrumb_item, 
            'project' => $project,
            'tab' => 'show',
            'stats' => [
                'total_wbs' => $totalWbs,
                'leaf_wbs' => $leafWbs,
                'deliverables' => Deliverable::whereHas('wbsItem', fn ($q) => $q->where('project_id', $project->id))->count(),
            ],
            'progress' => $projectProgress,
        ]);
    }
     

    public function wbs(Project $project)
    {
        $breadcrumb = 'Projects';
        $breadcrumb_item = 'List';

        $project->load(['pic', 'creator']);
        
        $items = DB::select("
            WITH RECURSIVE wbs_tree AS (
                -- ROOT
                SELECT
                    id,
                    parent_id,
                    name,
                    type,
                    status,
                    code,
                    sort_order,
                    progress,
                    weight,
                    start_date,
                    end_date,
                    0 AS depth
                FROM wbs_items
                WHERE parent_id IS NULL
                  AND project_id = ?
    
                UNION ALL
    
                -- CHILDREN
                SELECT
                    w.id,
                    w.parent_id,
                    w.name,
                    w.type,
                    w.status,
                    w.code,
                    w.sort_order,
                    w.progress,
                    w.weight,
                    w.start_date,
                    w.end_date,
                    wt.depth + 1
                FROM wbs_items w
                INNER JOIN wbs_tree wt ON w.parent_id = wt.id
            )
            SELECT * FROM wbs_tree
            ORDER BY parent_id, sort_order, id
        ", [$project->id]);
    
        // build nested tree
        $tree = $this->buildTree($items);
    
        return view('wbs.index', [
            'breadcrumb' => $breadcrumb, 
            'breadcrumb_item' => $breadcrumb_item, 
            'project' => $project,
            'tab' => 'wbs',
            'tree' => $tree
        ]);
    }

    public function timeline(Project $project)
    {
        $breadcrumb = 'Projects';
        $breadcrumb_item = 'List';
        $project->load(['pic', 'creator']);
    
        // progress is rolled up from the WBS (WbsProgressService)
        $projectProgress = $project->progress;

        $tasks = WbsItem::with('pic')
            ->where('project_id', $project->id)
            ->where('type', 'task')
            ->orderBy('start_date')
            ->get();

        // === WBS STATS ===
        $totalWbs = WbsItem::where('project_id', $project->id)->count();

        $leafWbs = WbsItem::where('project_id', $project->id)
            ->whereDoesntHave('children')
            ->count();
            
        return view('projects.timeline', [
            'breadcrumb' => $breadcrumb, 
            'breadcrumb_item' => $breadcrumb_item, 
            'project' => $project,
            'tasks'   => $tasks,
            'tab'     => 'timeline',
            'progress' => $projectProgress,
            'stats' => [
                'total_wbs' => $totalWbs,
                'leaf_wbs' => $leafWbs,
                'deliverables' => Deliverable::whereHas('wbsItem', fn ($q) => $q->where('project_id', $project->id))->count(),
            ],
        ]);
    }

    public function deliverables(Project $project)
    {
        $breadcrumb = 'Projects';
        $breadcrumb_item = 'List';
        $project->load(['pic', 'creator']);

        // only WBS items that have deliverables
        $wbsWithDeliverables = WbsItem::where('project_id', $project->id)
            ->whereHas('deliverables') // Hanya yang punya deliverables
            ->with('deliverables')
            ->get();

        // === WBS STATS ===
        $totalWbs = WbsItem::where('project_id', $project->id)->count();

        $leafWbs = WbsItem::where('project_id', $project->id)
            ->whereDoesntHave('children')
            ->count();

        // progress is rolled up from the WBS (WbsProgressService)
        $projectProgress = $project->progress;

        return view('projects.deliverables', [
            'breadcrumb' => $breadcrumb, 
            'breadcrumb_item' => $breadcrumb_item, 
            'project' => $project,
            'wbsItems' => $wbsWithDeliverables, // list of tasks with deliverables
            'tab' => 'deliverables',
            'progress' => $projectProgress,
            'stats' => [
                'total_wbs' => $totalWbs,
                'leaf_wbs' => $leafWbs,
                'deliverables' => Deliverable::whereHas('wbsItem', fn ($q) => $q->where('project_id', $project->id))->count(),
            ],
        ]);
    }


    private function buildTree($items)
    {
        $map = [];
        $tree = [];

        foreach ($items as $item) {
            $item->children = [];
            $map[$item->id] = $item;
        }

        foreach ($map as $item) {
            if ($item->parent_id) {
                $map[$item->parent_id]->children[] = $item;
            } else {
                $tree[] = $item;
            }
        }

        return $tree;
    }

    /* =====================
     | Edit Form
     ===================== */
    public function edit(Project $project)
    {
        $breadcrumb = 'Projects';
        $breadcrumb_item = 'Edit';
        $users = User::orderBy('name')->where('role', 'pm')->get();

        return view('projects.edit', compact('breadcrumb', 'breadcrumb_item', 'project', 'users'));
    }

    /* =====================
     | Update
     ===================== */
    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'code'        => 'nullable|string|max:50',
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'status'      => 'required|in:draft,ongoing,completed,on_hold',
            'pm_id'      => 'nullable|exists:users,id',
        ]);

        $data['updated_by'] = Auth::id();

        $project->update($data);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project updated');
    }

    /* =====================
     | Delete
     ===================== */
    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()
            ->route('projects.index')
            ->with('success', 'Project deleted');
    }
}
