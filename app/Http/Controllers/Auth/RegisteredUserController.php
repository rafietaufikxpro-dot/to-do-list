<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use OpenApi\Attributes as OA;

class RegisteredUserController extends Controller
{
    #[OA\Get(
        path: "/register",
        description: "Display the registration view.",
        summary: "Display the registration view.",
        requestsbody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", description: "The name of the user."),
                    new OA\Property(property: "email", type: "string", description: "The email address of the user."),
                    new OA\Property(property: "password", type: "string", description: "The password of the user."),
                    new OA\Property(property: "password_confirmation", type: "string", description: "The password confirmation of the user."),
                ]
            )

        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Registration view displayed successfully."
            ),
            new OA\Response(
                response: 422,
                description: "Validation error."
            ),
        ]
    )]
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'user', // Set the default role to 'user'
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);
        $token = $user->createToken('auth_token')->plainTextToken;

       return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
            'access_token' => $token,
        ]);
    }
}
