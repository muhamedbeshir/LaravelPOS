<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

final class ActivationController extends Controller
{
    public function showForm()
    {
        // Get device UUID (hardcoded for now, but should be dynamic in production)
        $deviceId = env('DEVICE_ID', '825BAB14-FACE-11E9-80DB-F875A4160D63');
        return view('activation.form', compact('deviceId'));
    }

    public function submit(Request $request)
    {
        $request->validate([
            'device_id' => 'required',
            'activation_code' => 'required',
        ]);

        $deviceId = $request->input('device_id');
        $activationCode = $request->input('activation_code');

        if ($activationCode === ($deviceId . '+69')) {
            File::put(base_path('docs/activation.txt'), (string) $deviceId);
            return redirect('/')->with('success', 'تم التفعيل بنجاح!');
        }

        return back()->withErrors(['activation_code' => 'رمز التفعيل غير صحيح.']);
    }
} 