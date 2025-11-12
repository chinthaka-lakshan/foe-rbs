<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;



function handleProxyResponse($response, $defaultMessage) {
    // If the microservice returned a success code (2xx), return the body.
    if ($response->successful()) {
        return response($response->body(), $response->status())
                ->header('Content-Type', 'application/json');
    }
    
    // If the microservice returned an error (4xx or 5xx), return its original body/status.
    // This allows the client (Vue.js) to see validation errors or 403 Forbidden messages.
    return response($response->body(), $response->status())
            ->header('Content-Type', 'application/json');
}
//--------------------------------------------------------------------------
// API Routes
//--------------------------------------------------------------------------
//User login route
Route::post('/login', function (Request $request) {
    try {
        $response = Http::timeout(30)->post('http://auth_service/api/login', $request->all());
        
        return $response->json();
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Cannot connect to authentication service',
            'error' => $e->getMessage()
        ], 503);
    }
});

//User registration route
Route::post('/users', function (Request $request) {
    try {
        $response = Http::timeout(30)->withToken($request->bearerToken())
            ->post('http://auth_service/api/users', $request->all());
        
        return $response->json();
    } catch (Exception $e) {
        return response()->json([
            'message' => 'Cannot connect to authentication service',
            'error' => $e->getMessage()
        ], 503);
    }
});



// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Get authenticated user details
    Route::get('/users', function (Request $request) {
        $response = Http::withToken($request->bearerToken())
            ->get('http://auth_service/api/users');
            
        return $response->successful() 
            ? $response->json() 
            : response()->json(['message' => 'Failed to get user'], $response->status());
    });

    // User logout route
    Route::post('/logout', function (Request $request) {
        $response = Http::withToken($request->bearerToken())
            ->post('http://auth_service/api/logout');
            
        return $response->successful() 
            ? $response->json() 
            : response()->json(['message' => 'Logout failed'], $response->status());
    });
    // Update user route (Admin, Master Admin)
    Route::put('/users/{user}', function (Request $request, $user) {
        $response = Http::withToken($request->bearerToken())
            ->put("http://auth_service/api/users/{$user}", $request->all());
            
        return $response->successful() 
            ? $response->json() 
            : response()->json(['message' => 'User update failed'], $response->status());
    });
    // Delete user route (Admin, Master Admin)
    Route::delete('/users/{user}', function (Request $request, $user) {
        $response = Http::withToken($request->bearerToken())
            ->delete("http://auth_service/api/users/{$user}");
            
        return $response->successful() 
            ? $response->json() 
            : response()->json(['message' => 'User deletion failed'], $response->status());
    });

    // Category CRUD routes proxying to resource service
        // Create category route
    Route::post('/categories', function (Request $request) {
        try {
            $response = Http::timeout(30)->withToken($request->bearerToken())
                ->post('http://resource_service/api/categories', $request->all());
            return $response->json();
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Cannot connect to resource service',
                'error' => $e->getMessage()
            ], 503);
        }
    });
    // Update category route
    Route::put('/categories/{id}', function (Request $request, $id) {
        try {
            $response = Http::timeout(30)->withToken($request->bearerToken())
                ->put("http://resource_service/api/categories/{$id}", $request->all());
            
            return $response->json();
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Cannot connect to resource service',
                'error' => $e->getMessage()
            ], 503);
        }
    });
    // List all resources
    Route::get('/resources', function (Request $request) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->get('http://resource_service/api/resources');
            
            return handleProxyResponse($response, 'Failed to fetch resources.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
    // Show single resource
    Route::get('/resources/{id}', function (Request $request, $id) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->get("http://resource_service/api/resources/{$id}");
            
            return handleProxyResponse($response, 'Failed to fetch resource.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
    // post a new resource
    Route::post('/resources', function (Request $request) {
    try {
        \Log::info('Gateway received request', [
            'data' => $request->except(['images']),
            'has_images' => $request->hasFile('images')
        ]);
        
        $http = Http::timeout(30)->withToken($request->bearerToken());
        $http->asMultipart();
        $data = $request->except(['images']);
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $index => $item) {
                    if (is_array($item)) {
                        foreach ($item as $subKey => $subValue) {
                            $attachValue = $subValue;
                            if (is_bool($subValue)) {
                                $attachValue = $subValue ? '1' : '0';
                            } elseif (is_null($subValue)) {
                                $attachValue = '';
                            }
                            
                            $http->attach(
                                "{$key}[{$index}][{$subKey}]",
                                $attachValue
                            );
                        }
                    } else {
                        $attachValue = $item;
                        if (is_bool($item)) {
                            $attachValue = $item ? '1' : '0';
                        } elseif (is_null($item)) {
                            $attachValue = '';
                        }
                        
                        $http->attach("{$key}[{$index}]", $attachValue);
                    }
                }
            } else {
                $attachValue = $value;
                if (is_bool($value)) {
                    $attachValue = $value ? '1' : '0';
                } elseif (is_null($value)) {
                    $attachValue = '';
                }
                
                $http->attach($key, $attachValue);
            }
        }
        
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                if ($file && $file->isValid() && is_readable($file->getRealPath())) {
                    $contents = file_get_contents($file->getRealPath());
                    
                    if ($contents !== false) {
                        $http->attach(
                            'images[]',
                            $contents, 
                            $file->getClientOriginalName()
                        );
                    }
                }
            }
        }
        
        \Log::info('Sending request to resource service');
        
        $response = $http->post('http://resource_service/api/resources');
        
        \Log::info('Resource service response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);
        
        return handleProxyResponse($response, 'Resource creation failed.'); 
        
    } catch (Exception $e) {
        \Log::error('Resource proxy error: ' . $e->getMessage());
        return response()->json([
            'message' => 'Gateway error',
            'error' => $e->getMessage()
        ], 500);
    }
});

    // Update resource
    Route::match(['put', 'post'], '/resources/{id}', function (Request $request, $id) {
        try {
            $http = Http::timeout(30)->withToken($request->bearerToken());
            
            if ($request->hasFile('images') || $request->has('delete_images') || $request->has('delete_equipment')) {
                $http->asMultipart();
                
                // Attach all fields with proper boolean/null handling
                foreach ($request->except(['images']) as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $index => $item) {
                            if (is_array($item)) {
                                foreach ($item as $subKey => $subValue) {
                                    $attachValue = $subValue;
                                    if (is_bool($subValue)) {
                                        $attachValue = $subValue ? '1' : '0';
                                    } elseif (is_null($subValue)) {
                                        $attachValue = '';
                                    }
                                    
                                    $http->attach("{$key}[{$index}][{$subKey}]", $attachValue);
                                }
                            } else {
                                $attachValue = $item;
                                if (is_bool($item)) {
                                    $attachValue = $item ? '1' : '0';
                                } elseif (is_null($item)) {
                                    $attachValue = '';
                                }
                                
                                $http->attach("{$key}[{$index}]", $attachValue);
                            }
                        }
                    } else {
                        $attachValue = $value;
                        if (is_bool($value)) {
                            $attachValue = $value ? '1' : '0';
                        } elseif (is_null($value)) {
                            $attachValue = '';
                        }
                        
                        $http->attach($key, $attachValue);
                    }
                }
                
                // Attach image files
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $file) {
                        if ($file && $file->isValid()) {
                            $http->attach(
                                'images[]',
                                file_get_contents($file->getRealPath()),
                                $file->getClientOriginalName()
                            );
                        }
                    }
                }
                
                $response = $http->post("http://resource_service/api/resources/{$id}");
            } else {
                // Use JSON for simple updates
                $response = $http->put("http://resource_service/api/resources/{$id}", $request->all());
            }
            
            return handleProxyResponse($response, 'Resource update failed.');
            
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });

    // Delete resource
    Route::delete('/resources/{id}', function (Request $request, $id) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->delete("http://resource_service/api/resources/{$id}");
            
            return handleProxyResponse($response, 'Resource deletion failed.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
});