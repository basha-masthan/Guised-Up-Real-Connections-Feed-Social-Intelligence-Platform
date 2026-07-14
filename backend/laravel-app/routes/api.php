<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::get('/test-token', function (Request $request) {
    $userId = $request->query('user_id');
    $user = $userId ? User::find($userId) : User::first();
    
    if (!$user) {
        return response()->json(['success' => false, 'message' => 'No users seeded yet.'], 404);
    }
    $token = $user->createToken('demo-token')->plainTextToken;
    return response()->json([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
        ]
    ]);
});

// Authenticated Routes (Sanctum Bearer Token)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json(['success' => true, 'data' => $request->user()]);
    });

    Route::post('/posts', [ApiController::class, 'createPost']);
    Route::get('/feed', [ApiController::class, 'getFeed']);
    Route::get('/search', [ApiController::class, 'searchPosts']);
    Route::post('/interactions', [ApiController::class, 'logInteraction']);
});
