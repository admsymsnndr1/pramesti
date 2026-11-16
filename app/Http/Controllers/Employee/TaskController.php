<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Models\WbsItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function __construct()
    {
        // own tasks (queries below are still limited by pic_id)
        $this->middleware('permission:tasks.view')->only(['index', 'detail', 'detailTask']);
        // task detail opened from the WBS page
        $this->middleware('permission:tasks.view,wbs.view')->only('show');
        $this->middleware('permission:tasks.assign')->only('assign');
    }

    public function index()
    {
        $breadcrumb = 'My Tasks';
        $breadcrumb_item = 'Projects';
        $user = Auth::user();

        $projects = Project::whereHas('wbsItems', function ($q) use ($user) {
                $q->where('pic_id', $user->id);
            })
            ->with(['wbsItems' => function ($q) use ($user) {
                $q->where('pic_id', $user->id)
                  ->orderBy('start_date');
            }])
            ->orderBy('name')
            ->paginate(10);
        
        $role = auth()->user()->role;

        return view('employee.tasks.index', compact('role', 'projects', 'breadcrumb', 'breadcrumb_item'));
    }  
    
    public function detail(Project $project)
    {
        $breadcrumb = 'My Tasks'; 
        $breadcrumb_item = 'Tasks';
        $user = Auth::user();

        // the project must contain at least one task of this user
        $hasAccess = $project->wbsItems()
            ->where('pic_id', $user->id)
            ->exists();

        if (! $hasAccess) {
            abort(403, 'You have no tasks in this project');
        }

        // Load task + deliverables
        $project->load([
            'wbsItems' => function ($q) use ($user) {
                $q->where('pic_id', $user->id)
                ->orderBy('start_date');
            },
            'wbsItems.deliverables'
        ]);

        return view('employee.tasks.detail', [
            'project' => $project,
            'role'    => $user->role,
            'breadcrumb' => $breadcrumb,
            'breadcrumb_item' => $breadcrumb_item
        ]);
    }

    public function detailTask(WbsItem $task)
    {
        $breadcrumb = 'My Tasks'; 
        $breadcrumb_item = 'Task';
        $user = Auth::user();

        // only the PIC of the task may open it here
        if ($task->pic_id !== $user->id) {
            abort(403);
        }

        // Load relasi
        $task->load([
            'project',
            'deliverables' => function ($q) {
                $q->with(['files', 'uploader'])
                ->latest();
            }
        ]);

        return view('employee.tasks.detail-task', compact('task', 'breadcrumb', 'breadcrumb_item'));
    }

    public function show(WbsItem $task)
    {
        $breadcrumb = 'WBS'; 
        $breadcrumb_item = 'List';
   
        // without WBS access a user may only open their own tasks
        if (!auth()->user()->hasPermission('wbs.view')) {
            abort_if($task->pic_id !== auth()->id(), 403);
        }

        $task->load(['parent', 'pic', 'deliverables' => fn ($q) => $q->with(['files', 'uploader'])->latest()]);

        $employees = [];

        // possible PICs = roles with the "my tasks" permission
        $employees = User::withPermission('tasks.view')->orderBy('name')->get();
        
        return view('employee.tasks.show', [
            'breadcrumb' => $breadcrumb, 
            'breadcrumb_item' => $breadcrumb_item, 
            'task' => $task,
            'employees' => $employees,
        ]);
    }

    public function assign(Request $request, WbsItem $task)
    {
        $validated = $request->validate([
            'pic_id'      => 'nullable|exists:users,id',
            'description' => 'nullable',
        ]);
        
        // update the PIC only when sent
        if ($request->filled('pic_id')) {
            $task->pic_id = $validated['pic_id'];
        }

        // update the description only when sent
        if ($request->has('description')) {
            $task->description = $validated['description'];
        }

        $task->save();

        return redirect()
            ->route('employee.tasks.show', $task)
            ->with('success', 'Task updated');
    }

    
}
