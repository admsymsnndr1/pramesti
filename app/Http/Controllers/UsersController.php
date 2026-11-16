<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class UsersController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users.view')->only('index');
        $this->middleware('permission:users.create')->only(['create', 'store']);
        $this->middleware('permission:users.update')->only(['edit', 'update']);
        $this->middleware('permission:users.delete')->only('destroy');
        $this->middleware('permission:users.login_as')->only('loginAs');
        // logoutAs has no permission on purpose: it runs while logged in as another user
    }

    /**
     * Display all users
     */
    public function index()
    {
        $breadcrumb = 'Users';
        $breadcrumb_item = 'List';
        $users = User::with('userRole')->orderBy('name', 'asc')->paginate(20);
        
        return view('users.index', compact('breadcrumb', 'breadcrumb_item', 'users'));
    }

    /* ======================
     * CREATE
     * ====================== */
    public function create()
    {
        $breadcrumb = 'Users'; 
        $breadcrumb_item = 'Create';
        $roles = Role::orderBy('display_name')->get();
        return view('users.create', compact('breadcrumb', 'breadcrumb_item', 'roles'));
    }

    /* ======================
     * STORE
     * ====================== */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|min:2',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'gender'   => 'required',
            'role'     => 'required|exists:roles,name',
            'active'   => 'required|boolean',
            'photo'    => 'nullable|image|max:2048'
        ]);
    
        $photoPath = null;
    
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('photo', 'public');
        }
    
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt($request->password),
            'gender'   => $request->gender,
            'role'     => $request->role,
            'active'   => $request->active,
            'photo'    => $photoPath
        ]);
    
        return redirect()
            ->route('users.index')
            ->with('success', 'User created');
    }

    /* ======================
     * EDIT
     * ====================== */
    public function edit(User $user)
    {
        $breadcrumb = 'Users'; 
        $breadcrumb_item = 'Edit';
        $roles = Role::orderBy('display_name')->get();
        return view('users.edit', compact('user', 'breadcrumb', 'breadcrumb_item', 'roles'));
    }

    /* ======================
     * UPDATE
     * ====================== */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|unique:users,email,' . $user->id,
            'role'   => 'required|exists:roles,name',
            'gender' => 'required',
            'active' => 'required|boolean',
            'photo'  => 'nullable|image|max:2048'
        ]);
    
        $data = $request->only('name', 'email', 'role', 'gender', 'active');
    
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }
    
        if ($request->hasFile('photo')) {
            // remove the old photo so unused files do not pile up
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }
    
            $data['photo'] = $request->file('photo')->store('photo', 'public');
        }
        // without a new upload the photo column is left untouched (not overwritten with null)
    
        $user->update($data);
    
        return redirect()
            ->route('users.index')
            ->with('success', 'User updated');
    }

    /* ======================
     * DELETE
     * ====================== */
    public function destroy(User $user)
    {
        if ($user->is(Auth::user())) {
            return back()->with('error', 'You cannot delete your own account');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted');
    }

    /**
     * Login as another user (for admin/superadmin)
     */
    public function loginAs(User $user)
    {
        // permission users.login_as is checked by the middleware in the constructor
        if ($user->is(Auth::user()) || Session::has('login_as_original_user')) {
            return back()->with('error', 'You cannot log in as this user');
        }

        // keep the original user so they can switch back
        Session::put('login_as_original_user', Auth::id());
        
        // log in as the selected user
        Auth::loginUsingId($user->id);
        
        return redirect()->route('dashboard')->with('success', 'You are now logged in as ' . $user->name);
    }

    /**
     * Logout from login as and return to original user
     */
    public function logoutAs()
    {
        // original user id from the session
        $originalUserId = Session::get('login_as_original_user');
        
        if (!$originalUserId) {
            return redirect()->route('login')->with('error', 'Invalid session');
        }
        
        // log back in as the original user
        Auth::loginUsingId($originalUserId);
        
        // clear the login-as session
        Session::forget('login_as_original_user');
        
        return redirect()->route('users.index')->with('success', 'Back to your own account');
    }
}