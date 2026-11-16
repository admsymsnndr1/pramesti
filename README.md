# Pramesti — Project Management

[![tests](https://github.com/admsymsnndr1/pramesti/actions/workflows/tests.yml/badge.svg)](https://github.com/admsymsnndr1/pramesti/actions/workflows/tests.yml)
![Laravel](https://img.shields.io/badge/Laravel-9-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-06B6D4?logo=tailwindcss&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?logo=alpinedotjs&logoColor=white)

A project management app built around a **Work Breakdown Structure (WBS)**. Project managers break a
project into phases and tasks, assign each task to a person in charge (PIC), and the app rolls
progress up the tree automatically. A daily snapshot powers trend charts and an **S-curve** that
compares planned against actual progress.

> Built as my undergraduate thesis project (Information Systems). This public version runs on
> fictional demo data.

![Dashboard](docs/screenshots/dashboard.png)

## Features

- **Multi-level WBS** – unlimited depth, outline codes (`2.1.3`), items can move to another parent
  (an item can never end up under its own child).
- **Weighted progress roll-up** – only leaf tasks are updated by hand; every parent and the project
  itself are recalculated as a weighted average of their children in a single transaction.
- **Task status flow** – Open → On Progress → On Testing / Revision → Complete, kept in sync with progress.
- **Deliverables** – PICs upload files or links per task; reviewers approve them.
- **Project analytics** – tasks per status, daily trends, progress per WBS level and an S-curve
  (plan vs actual, with today's deviation).
- **Role-based access control** – roles and permissions are managed from the UI; menus, routes and
  buttons all follow the same permission names. Admins can "log in as" a user to check what they see.
- **Excel export** – project report and role matrix.
- Responsive layout with dark mode.

| WBS | Analytics |
|---|---|
| ![WBS](docs/screenshots/wbs.png) | ![Analytics](docs/screenshots/analytics.png) |

## How the progress roll-up works

```
Project 52.5%
├── 1 Analysis & Design (weight 20)   100%
│   ├── 1.1 Requirement gathering (30) 100%
│   └── ...
├── 2 Development (weight 50)          65%   = (100 × 50 + 30 × 50) / 100
│   ├── 2.1 Backend API (50)           100%
│   └── 2.2 Frontend (50)              30%
└── ...
```

- Weights are **relative to siblings**, so `20 / 50 / 30` and `2 / 5 / 3` mean the same thing.
  If every sibling has weight 0, a plain average is used.
- `WbsItemObserver` reacts to any change that affects progress (progress, weight, parent, project,
  delete) and asks `WbsProgressService` to recalculate the **whole project tree in one query**,
  so moving an item between branches or projects never leaves stale numbers behind.
- The planned S-curve spreads each task's share evenly over its start/end dates
  (`ProjectDashboardService::plannedCurve`). The actual line comes from `project_daily_snapshots`,
  written every weekday evening by `php artisan dashboard:snapshot`.

Relevant code: [`app/Services`](app/Services), [`app/Observers/WbsItemObserver.php`](app/Observers/WbsItemObserver.php),
[`tests/Feature`](tests/Feature).

## Roles

| Role | Can do |
|---|---|
| Administrator | Everything (permissions cannot be removed) |
| Project Manager | Projects, WBS, assign PICs, approve deliverables |
| Team Leader | Like PM without delete rights, and works on own tasks |
| Developer | Own tasks and deliverables |
| QA | View projects/WBS, own tasks, approve deliverables |
| Admin Project | Read-only WBS and the analytics dashboard |

All of these are editable in **Roles & Permissions**; new roles can be added from the UI.

## Tech stack

- **Backend:** Laravel 9, PHP 8.1+, MySQL 8 / MariaDB, Maatwebsite Excel
- **Frontend:** Blade components, Tailwind CSS 3, Alpine.js, Chart.js 4, Lucide icons, Vite
- **Testing:** PHPUnit feature tests on MySQL, GitHub Actions CI

## Getting started

Requirements: PHP 8.1+, Composer, Node 18+, MySQL 8 (or MariaDB 10.6+).

```bash
git clone https://github.com/admsymsnndr1/pramesti.git
cd pramesti
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
```

Create a database called `pramesti` (or change `DB_*` in `.env`), then:

```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Open http://127.0.0.1:8000 and sign in with one of the demo accounts (password `password`):

| Email | Role |
|---|---|
| admin@example.com | Administrator |
| pm@example.com | Project Manager |
| dev@example.com | Developer |
| qa@example.com | QA |
| analytics@example.com | Admin Project |

To keep the trend charts and S-curve up to date, run the scheduler
(`* * * * * php artisan schedule:run`) or take a snapshot by hand:

```bash
php artisan dashboard:snapshot
```

## Tests

The tests need a MySQL database named `pramesti_test` (see `phpunit.xml`).

```bash
php artisan test
```

They cover the progress roll-up (weights, several levels, moving and deleting items), the S-curve and
snapshot numbers, outline codes, and access control per role.

## Roadmap

- Upgrade to Laravel 11 and PHP 8.3
- Gantt chart with dependencies between tasks
- Notifications when a task is assigned or a deliverable is approved

## License

[MIT](LICENSE)
