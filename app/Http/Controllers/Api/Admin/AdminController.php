<?php

namespace App\Http\Controllers\Api\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Addon;

class AdminController extends Controller
{
    public function dashboard()
    {
        return "Admin Dashboard";
    }
    // public function approveVendor(Request $request)
    // {
    // }
}
