<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Pulse\Pulse;

class PulseController extends Controller
{
    public function recordRemoteMetric(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'value' => 'required',
        ]);

        $name = $request->input('name');
        $value = $request->input('value');
        // dd($name, $value);

        Pulse::record("remote.{$name}", $value);

        return response()->json(['status' => 'success']);
    }
}
