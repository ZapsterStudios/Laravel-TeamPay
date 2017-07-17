<?php

namespace ZapsterStudios\TeamPay\Controllers\Account;

use Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PasswordController extends Controller
{
    /**
     * Update an existing password.
     *
     * @return Response
     */
    public function update(Request $request)
    {
        $user = $request->user();
        $this->validate($request, [
            'current' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        if (! Hash::check($request->current, $user->password)) {
            return response()->json([
                'current' => [
                    lang('auth.failed'),
                ],
            ], 422);
        }

        return response()->json(tap($user)->update([
            'password' => bcrypt($request->password),
        ]), 200);
    }
}
