<?php
namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
{
    $user = User::create([
        'name'     => $request->name,
        'email'    => $request->email,
        'phone'    => $request->phone,
        'password' => Hash::make($request->password),
        'role'     => $request->email === 'wwwadmin@eslam.com' ? 'admin' : 'user',
    ]);

    // قم بتسجيل الدخول مباشرة بعد التسجيل
    
    auth()->login($user);
    $request->session()->regenerate(); 

    return  response()->json(['user' => new UserResource($user)]);
}

   public function login(LoginRequest $request)
{
    $request->validated();

    $user = User::where('email', $request->email)->first();

    if (! $user || ! Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages(['email' => ['Invalid credentials']]);
    }
     

    auth()->login($user);
    $request->session()->regenerate(); // حماية من جلسات مزورة

    return response()->json(['user' => new UserResource($user)]);
}


public function logout(Request $request)
{
    Auth::guard('web')->logout(); // ← هذا يعمل إذا الحارس هو web
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['message' => 'Logged out successfully']);
}


    public function user(Request $request)
{
    return response()->json(['user' => new UserResource($request->user())]);
}


    public function index()
{
    $users = User::all();
      $users->each(function ($user) {
        $user->wallet_balance = $user->balance;
    });

   
    return response()->json(['users' => UserResource::collection($users)]);
}


 public function show($id)
{
    $user = User::find($id);

    if (! $user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    return response()->json(['user' => new UserResource($user)]);
}

   

    public function destroy($id, Request $request)
{
    $user = User::findOrFail($id);
    if ($request->user()->role !== 'admin') {
        return response()->json(['error' => 'Cannot delete admin user'], 403);
    }

    $user->delete();
    return response()->json(['message' => 'User deleted successfully']);
}

    public function changeRole(Request $request,  $id)
{
    $user = User::findOrFail($id);
    $request->validate(['role' => 'required|in:admin,user']);

    if($request->user()->role !== 'admin') {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $user->role = $request->role;
    $user->save();

    return response()->json(['user' => $user]);
}

public function count()
{
    return response()->json([
        'count' => User::count()
    ]);
}
}
