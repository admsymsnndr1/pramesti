<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Deliverable;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\WbsItem;

class DashboardController extends Controller
{
    /**
     * Show specified view.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->user();

        // roles that only have the admin project dashboard
        if (!$user->hasAnyPermission(['projects.view', 'tasks.view']) && $user->hasPermission('dashboard.admin_project')) {
            return redirect()->route('dashboard.admin-project');
        }

        // project dashboard (admin, pm, team leader, qa, ...)
        if ($user->hasPermission('projects.view')) {

            // team composition per role (admin excluded)
            $teamRoles = Role::where('name', '!=', Role::SUPER_ROLE)
                ->withCount('users')
                ->orderBy('id')
                ->get();

            $totalProjects = Project::count();
            $ongoingProjects = Project::where('status', 'ongoing')->count();
            $completedProjects = Project::where('status', 'completed')->count();

            $totalTasks = WbsItem::where('type', 'task')->count();

            $overallProgress = round(
                WbsItem::where('type', 'task')->avg('progress') ?? 0,
                2
            );

            $projectStatus = Project::selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $recentTasks = WbsItem::with('project')
                ->where('type', 'task')
                ->latest()
                ->take(5)
                ->get();

            $recentDeliverables = Deliverable::with(['task', 'uploader'])
                ->latest()
                ->take(5)
                ->get();

            return view('dashboard.projects', compact(
                'totalProjects',
                'ongoingProjects',
                'completedProjects',
                'totalTasks',
                'overallProgress',
                'projectStatus',
                'teamRoles',
                'recentTasks',
                'recentDeliverables'
            ));
        }

        // personal task dashboard (developer, ...)
        if ($user->hasPermission('tasks.view')) {

            $projects = Project::whereHas('wbsItems', function ($q) use ($user) {
                $q->where('pic_id', $user->id);
            })
            ->with(['wbsItems' => function ($q) use ($user) {
                $q->where('pic_id', $user->id)
                ->where('type', 'task');
            }])
            ->get();
                
            $assignedProjects = $projects->count();

            $assignedTasks = WbsItem::where('pic_id', $user->id)
                ->count();

            $completedTasks = WbsItem::where('pic_id', $user->id)
                ->where('progress', 100)
                ->count();

            $avgProgress = round(
                WbsItem::where('pic_id', $user->id)
                    ->avg('progress') ?? 0,
                2
            );

            $taskStatusChart = [
                'Completed'   => $completedTasks,
                'On Progress' => $assignedTasks - $completedTasks,
            ];

            $tasksByProject = $projects
                ->mapWithKeys(function ($project) {
                    return [
                        $project->name => $project->wbsItems->count()
                    ];
                })
                ->toArray();
            

            $recentTasks = WbsItem::with('project')
                ->where('pic_id', $user->id)
                ->latest()
                ->take(5)
                ->get();

            return view('dashboard.tasks', compact(
                'assignedProjects',
                'assignedTasks',
                'completedTasks',
                'avgProgress',
                'taskStatusChart',
                'tasksByProject',
                'recentTasks'
            ));
        }



        abort(403);
    }

}
