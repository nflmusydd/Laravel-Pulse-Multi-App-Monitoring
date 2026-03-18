<?php

namespace App\Http\Controllers\Dashboard1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function getUsers(Request $request)
    {
        if ($request->ajax()) {
            $query = User::select(['name', 'email']);
            Log::info('User Dashboard Query:', [
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);
            return DataTables::of($query)->make(true);
        }
    }

    public function getAllUsers()
    {
        $users = User::select('name', 'email')->get();
        return response()->json($users);
    }

}
