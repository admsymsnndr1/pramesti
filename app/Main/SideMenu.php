<?php

namespace App\Main;

use App\Models\User;

class SideMenu
{
    /**
     * Sidebar items for the given user, filtered by the permissions of their role.
     *
     * Each item: title, icon (lucide name), route, active (route name pattern).
     */
    public static function for(?User $user): array
    {
        $menu = [[
            'title'  => 'Dashboard',
            'icon'   => 'layout-dashboard',
            'route'  => 'dashboard',
            'active' => 'dashboard',
        ]];

        if (!$user) {
            return $menu;
        }

        // roles that only have the admin project dashboard land there directly
        if ($user->hasPermission('dashboard.admin_project')) {
            if ($user->hasAnyPermission(['projects.view', 'tasks.view'])) {
                $menu[] = [
                    'title'  => 'Project Analytics',
                    'icon'   => 'bar-chart-3',
                    'route'  => 'dashboard.admin-project',
                    'active' => 'dashboard.admin-project',
                ];
            } else {
                $menu[0]['route'] = 'dashboard.admin-project';
                $menu[0]['active'] = 'dashboard.admin-project';
            }
        }

        if ($user->hasPermission('projects.view')) {
            $menu[] = [
                'title'  => 'Projects',
                'icon'   => 'briefcase',
                'route'  => 'projects.index',
                'active' => 'projects.*',
            ];
        }

        // WBS without the Projects menu (admin project): project list -> read-only WBS
        if ($user->hasPermission('wbs.view') && !$user->hasPermission('projects.view')) {
            $menu[] = [
                'title'  => 'WBS',
                'icon'   => 'git-branch',
                'route'  => 'wbs.index',
                'active' => 'wbs.*',
            ];
        }

        if ($user->hasPermission('tasks.view')) {
            $menu[] = [
                'title'  => 'My Tasks',
                'icon'   => 'clipboard-list',
                'route'  => 'employee.tasks.index',
                'active' => 'employee.tasks.*',
            ];
        }

        if ($user->hasPermission('users.view')) {
            $menu[] = [
                'title'  => 'Users',
                'icon'   => 'users',
                'route'  => 'users.index',
                'active' => 'users.*',
            ];
        }

        if ($user->hasPermission('roles.manage')) {
            $menu[] = [
                'title'  => 'Roles & Permissions',
                'icon'   => 'shield-check',
                'route'  => 'roles.index',
                'active' => 'roles.*',
            ];
        }

        return $menu;
    }
}
