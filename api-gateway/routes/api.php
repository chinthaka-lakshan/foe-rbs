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

// Password reset routes
Route::post('/forgot-password/{path}', function ($path, Request $request) {
    try {
        // Forward the POST request to the Auth Service, including the specific path (/email, /verify-otp, or /reset)
        $response = Http::timeout(30)->post("http://auth_service/api/forgot-password/{$path}", $request->all());
        
        // Use the helper function to pass the original status code (200, 401, 422) and body
        return handleProxyResponse($response, 'Password reset request failed.');
        
    } catch (Exception $e) {
        // Handle connection failure (e.g., Auth Service container is down)
        return response()->json([
            'message' => 'Cannot connect to authentication service',
            'error' => $e->getMessage()
        ], 503);
    }
})->where('path', '.*');

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
            : response()->json(['message' => 'Update failed'], $response->status());
    });

    // Permission overrides proxy
    Route::get('/users/{id}/permissions', function (Request $request, $id) {
        $response = Http::withToken($request->bearerToken())
            ->get("http://auth_service/api/users/{$id}/permissions");
        return $response->json();
    });

    Route::post('/users/{id}/permissions', function (Request $request, $id) {
        $response = Http::withToken($request->bearerToken())
            ->post("http://auth_service/api/users/{$id}/permissions", $request->all());
        return $response->json();
    });

    // Get all overrides
    Route::get('/users/permissions/overrides', function (Request $request) {
        $response = Http::withToken($request->bearerToken())
            ->get('http://auth_service/api/users/permissions/overrides');
        return $response->json();
    });

    // Category CRUD routes proxying to resource service
    Route::get('/categories', function (Request $request) {
        try {
            $response = Http::timeout(30)->withToken($request->bearerToken())
                ->get('http://resource_service/api/categories');
            return $response->json();
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Cannot connect to resource service',
                'error' => $e->getMessage()
            ], 503);
        }
    });
        // Create category 
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
    // Delete category route
    Route::delete('/categories/{id}', function (Request $request, $id) {
        try {
            $response = Http::timeout(30)->withToken($request->bearerToken())
                ->delete("http://resource_service/api/categories/{$id}");
            
            return $response->json();
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Cannot connect to resource service',
                'error' => $e->getMessage()
            ], 503);
        }
    });

    // Resource Service routes
    // Department Routes
    Route::get('/departments', function (Request $request) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->get('http://resource_service/api/departments');
            
            return handleProxyResponse($response, 'Failed to fetch departments.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });

    // Create department
    Route::post('/departments', function (Request $request) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->post('http://resource_service/api/departments', $request->all());

            return handleProxyResponse($response, 'Failed to create department.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
    // Show single department
    Route::get('/departments/{department}', function (Request $request, $department) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->get("http://resource_service/api/departments/{$department}");
            
            return handleProxyResponse($response, 'Failed to fetch department.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
    // Update department
    Route::put('/departments/{department}', function (Request $request, $department) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->put("http://resource_service/api/departments/{$department}", $request->all());
            
            return handleProxyResponse($response, 'Department update failed.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
    // Delete department
    Route::delete('/departments/{department}', function (Request $request, $department) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->delete("http://resource_service/api/departments/{$department}");
            
            return handleProxyResponse($response, 'Department deletion failed.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
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
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $http = Http::timeout(60)
                ->withHeaders(['X-User-Id' => (string)$user->id])
                ->withToken($request->bearerToken())
                ->acceptJson()
                ->asMultipart();
            
            $data = $request->except(['images']);

            // Robust recursive function to handle deep nesting
            $flattenAndAttach = function ($http, $data, $prefix = '') use (&$flattenAndAttach) {
                foreach ($data as $key => $value) {
                    $currentKey = $prefix ? "{$prefix}[{$key}]" : $key;

                    if (is_array($value)) {
                        $http = $flattenAndAttach($http, $value, $currentKey);
                    } else {
                        // FIX: Use a stream to prevent Laravel's array_filter from stripping empty strings or '0'
                        $contents = $value;
                        if (is_bool($value)) {
                            $contents = $value ? '1' : '0';
                        } elseif (is_null($value)) {
                            $contents = '';
                        }
                        
                        $stream = fopen('php://temp', 'r+');
                        fwrite($stream, (string)$contents);
                        rewind($stream);
                        
                        $http = $http->attach($currentKey, $stream);
                    }
                }
                return $http;
            };

            $http = $flattenAndAttach($http, $data);

            // Handle Images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $file) {
                    if ($file && $file->isValid()) {
                        \Log::debug("Gateway attaching image: " . $file->getClientOriginalName());
                        $http = $http->attach(
                            'images[]',
                            file_get_contents($file->getRealPath()),
                            $file->getClientOriginalName()
                        );
                    } else {
                        return response()->json([
                            'message' => 'Image upload failed. The file may be too large.',
                            'errors' => ['images' => ['One or more images exceed the maximum allowed size (2MB).']]
                        ], 422);
                    }
                }
            }

            $response = $http->post('http://resource_service/api/resources');
            return handleProxyResponse($response, 'Resource creation failed.');

        } catch (Exception $e) {
            \Log::error('Gateway Multipart Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });

    // Update resource
    Route::match(['put', 'post'], '/resources/{id}', function (Request $request, $id) {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $http = Http::timeout(60)
                ->withHeaders(['X-User-Id' => (string)$user->id])
                ->withToken($request->bearerToken());
            
            // Use Multipart if there are files or complex nested arrays like availability slots
            if ($request->hasFile('images') || $request->has('availability') || $request->has('equipment')) {
                $http->asMultipart();
                
                // Define Recursive function to handle deep nesting (3+ levels)
                $flattenAndAttach = function ($http, $data, $prefix = '') use (&$flattenAndAttach) {
                    foreach ($data as $key => $value) {
                        $currentKey = $prefix ? "{$prefix}[{$key}]" : $key;
                        if (is_array($value)) {
                            $http = $flattenAndAttach($http, $value, $currentKey);
                        } else {
                            $contents = $value;
                            if (is_bool($value)) {
                                $contents = $value ? '1' : '0';
                            } elseif (is_null($value)) {
                                $contents = '';
                            }
                            
                            $stream = fopen('php://temp', 'r+');
                            fwrite($stream, (string)$contents);
                            rewind($stream);

                            $http = $http->attach($currentKey, $stream);
                        }
                    }
                    return $http;
                };
                
                // Process all data except images (handled separately)
                $flattenAndAttach($http, $request->except(['images']));
                
                // Attach image files properly
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $file) {
                        if ($file && $file->isValid()) {
                            $http->attach(
                                'images[]',
                                file_get_contents($file->getRealPath()),
                                $file->getClientOriginalName()
                            );
                        } else {
                            return response()->json([
                                'message' => 'Image upload failed. The file may be too large.',
                                'errors' => ['images' => ['One or more images exceed the maximum allowed size (2MB).']]
                            ], 422);
                        }
                    }
                }
                
                // We use POST to the microservice because multipart PUT can be unstable in some PHP versions
                $response = $http->post("http://resource_service/api/resources/{$id}");
            } else {
                // Use standard JSON PUT for simple updates (name, price, etc.)
                $response = $http->put("http://resource_service/api/resources/{$id}", $request->all());
            }
            
            return handleProxyResponse($response, 'Resource update failed.');
            
        } catch (Exception $e) {
            \Log::error('Gateway Multipart Update Error: ' . $e->getMessage());
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
    // Delete resource
    Route::delete('/resources/{id}', function (Request $request, $id) {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            $response = Http::timeout(30)
                ->withHeaders(['X-User-Id' => (string)$user->id])
                ->withToken($request->bearerToken())
                ->delete("http://resource_service/api/resources/{$id}");
            
            return handleProxyResponse($response, 'Resource deletion failed.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });


    // Booking Service routes
    // Booking Items Routes
    Route::get('/booking-items', function (Request $request) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->get('http://resource_service/api/booking-items');
            
            return handleProxyResponse($response, 'Failed to fetch booking items.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
    // Available booking items for selection
    Route::get('/booking-items/available', function (Request $request) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->get('http://resource_service/api/booking-items/available');
            
            return handleProxyResponse($response, 'Failed to fetch available booking items.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
    // Create booking item
    Route::post('/booking-items', function (Request $request) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->post('http://resource_service/api/booking-items', $request->all());
            
            return handleProxyResponse($response, 'Booking item creation failed.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
    // Get single booking item
    Route::get('/booking-items/{id}', function (Request $request, $id) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->get("http://resource_service/api/booking-items/{$id}");
            
            return handleProxyResponse($response, 'Failed to fetch booking item.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
    // Update booking item
    Route::put('/booking-items/{id}', function (Request $request, $id) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->put("http://resource_service/api/booking-items/{$id}", $request->all());
            
            return handleProxyResponse($response, 'Booking item update failed.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
    // Delete booking item
    Route::delete('/booking-items/{id}', function (Request $request, $id) {
        try {
            $response = Http::timeout(30)
                ->withToken($request->bearerToken())
                ->delete("http://resource_service/api/booking-items/{$id}");
            
            return handleProxyResponse($response, 'Booking item deletion failed.');
        } catch (Exception $e) {
            return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
        }
    });
});


// BOOKING ROUTES (Booking Service)
// Get all bookings
Route::get('/bookings', function (Request $request) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->get('http://booking_service/api/bookings');
        return handleProxyResponse($response, 'Failed to fetch bookings.');
    } catch (Exception $e) {
        \Log::error('Booking gateway error: ' . $e->getMessage());
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});

// Create booking
Route::post('/bookings', function (Request $request) {
    try {
        $response = Http::timeout(60)  // Increased timeout for email
            ->withToken($request->bearerToken())
            ->post('http://booking_service/api/bookings', $request->all());
        return handleProxyResponse($response, 'Booking creation failed.');
    } catch (Exception $e) {
        \Log::error('Booking creation gateway error: ' . $e->getMessage());
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});

// Get single booking
Route::get('/bookings/{id}', function (Request $request, $id) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->get("http://booking_service/api/bookings/{$id}");
        return handleProxyResponse($response, 'Failed to fetch booking.');
    } catch (Exception $e) {
        \Log::error('Booking fetch gateway error: ' . $e->getMessage());
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});

// Get bookings by resource ID
Route::get('/bookings/resource/{resourceId}', function (Request $request, $resourceId) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->get("http://booking_service/api/bookings/resource/{$resourceId}");
        return handleProxyResponse($response, 'Failed to fetch bookings for resource.');
    } catch (Exception $e) {
        \Log::error('Bookings by resource gateway error: ' . $e->getMessage());
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});

// Update booking status
Route::patch('/bookings/{id}/status', function (Request $request, $id) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->patch("http://booking_service/api/bookings/{$id}/status", $request->all());
        return handleProxyResponse($response, 'Booking status update failed.');
    } catch (Exception $e) {
        \Log::error('Booking status update gateway error: ' . $e->getMessage());
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});

// Cancel booking
Route::post('/bookings/{id}/cancel', function (Request $request, $id) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->post("http://booking_service/api/bookings/{$id}/cancel");
        return handleProxyResponse($response, 'Booking cancellation failed.');
    } catch (Exception $e) {
        \Log::error('Booking cancellation gateway error: ' . $e->getMessage());
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});

// Verify OTP
Route::post('/bookings/{id}/verify-otp', function (Request $request, $id) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->post("http://booking_service/api/bookings/{$id}/verify-otp", $request->all());
        return handleProxyResponse($response, 'OTP verification failed.');
    } catch (Exception $e) {
        \Log::error('OTP verification gateway error: ' . $e->getMessage());
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});

// Resend OTP
Route::post('/bookings/{id}/resend-otp', function (Request $request, $id) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->post("http://booking_service/api/bookings/{$id}/resend-otp");
        return handleProxyResponse($response, 'OTP resend failed.');
    } catch (Exception $e) {
        \Log::error('OTP resend gateway error: ' . $e->getMessage());
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});

// Get all bookings for assigned admin
Route::get('/bookings/admin/assigned', function (Request $request) {
    try {
        $response = Http::timeout(30)
            ->withHeaders(['X-User-Id' => $request->user()->id])
            ->withToken($request->bearerToken())
            ->get('http://booking_service/api/bookings/admin/assigned', $request->all());
        
        return handleProxyResponse($response, 'Failed to fetch admin bookings.');
    } catch (Exception $e) {
        \Log::error('Admin bookings gateway error: ' . $e->getMessage());
        return response()->json([
            'message' => 'Gateway error',
            'error' => $e->getMessage()
        ], 500);
    }
});
//delete a booking
Route::delete('/bookings/{id}', function (Request $request, $id) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->delete("http://booking_service/api/bookings/{$id}");
        return handleProxyResponse($response, 'Booking deletion failed.');
    } catch (Exception $e) {
        \Log::error('Booking deletion gateway error: ' . $e->getMessage());
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});


// Resource Templates
// List all resource templates
Route::get('/resource-templates', function (Request $request) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->get('http://resource_service/api/resource-templates');
        return handleProxyResponse($response, 'Failed to fetch templates.');
    } catch (Exception $e) {
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});
// Create resource template
Route::post('/resource-templates', function (Request $request) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->post('http://resource_service/api/resource-templates', $request->all());
        return handleProxyResponse($response, 'Template creation failed.');
    } catch (Exception $e) {
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});
// Get single resource template
Route::get('/resource-templates/{id}', function (Request $request, $id) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->get("http://resource_service/api/resource-templates/{$id}");
        return handleProxyResponse($response, 'Failed to fetch template.');
    } catch (Exception $e) {
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});
// Update resource template
Route::put('/resource-templates/{id}', function (Request $request, $id) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->put("http://resource_service/api/resource-templates/{$id}", $request->all());
        return handleProxyResponse($response, 'Template update failed.');
    } catch (Exception $e) {
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});
// Delete resource template
Route::delete('/resource-templates/{id}', function (Request $request, $id) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->delete("http://resource_service/api/resource-templates/{$id}");
        return handleProxyResponse($response, 'Template deletion failed.');
    } catch (Exception $e) {
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});

// In api_gateway routes/api.php
Route::get('/settings', function (Request $request) {
    try {
        $response = Http::timeout(30)
            ->withToken($request->bearerToken())
            ->get('http://system_settings_service/api/settings');
        
        return handleProxyResponse($response, 'Failed to fetch system settings.');
    } catch (Exception $e) {
        \Log::error('System settings fetch gateway error: ' . $e->getMessage());
        return response()->json(['message' => 'Gateway error', 'error' => $e->getMessage()], 500);
    }
});
// Optimized Gateway Update
Route::post('/settings', function (Request $request) {
    $req = Http::timeout(30)->withToken($request->bearerToken());

    if ($request->hasFile('logo')) {
        $file = $request->file('logo');
        $req->attach(
            'logo', 
            file_get_contents($file->getRealPath()), 
            $file->getClientOriginalName()
        );
    }

    // Send everything (text + file) in one single POST
    $response = $req->post('http://system_settings_service/api/settings', $request->except('logo'));
    
    return handleProxyResponse($response, 'Update failed.');
});

// image retrieval route
Route::get('/settings/logo/{filename}', function ($filename) {
    try {
        // We fetch the file directly from the internal service's storage path
        $response = Http::get("http://system_settings_service/storage/logos/{$filename}");
        
        if ($response->successful()) {
            return response($response->body(), 200)
                ->header('Content-Type', $response->header('Content-Type'));
        }
        
        return response()->json(['message' => 'Image not found'], 404);
    } catch (Exception $e) {
        return response()->json(['message' => 'Gateway file error'], 500);
    }
});

// Resource image retrieval route
Route::get('/resources/storage/{path}', function ($path) {
    try {
        // We fetch the file directly from the internal service's storage path
        $response = Http::get("http://resource_service/storage/{$path}");
        
        if ($response->successful()) {
            return response($response->body(), 200)
                ->header('Content-Type', $response->header('Content-Type'));
        }
        
        return response()->json(['message' => 'Image not found'], 404);
    } catch (Exception $e) {
        return response()->json(['message' => 'Gateway file error'], 500);
    }
})->where('path', '.*');