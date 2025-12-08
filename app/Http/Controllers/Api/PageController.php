<?php

namespace App\Http\Controllers\Api;

use App\Models\Page;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;


class PageController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
        ]);
        
        if (Auth::role() == 'admin')
        {
            $page = Page::updateOrCreate([
                ['key' => $request->title],
                ['content' => $request->content],
            ]);
            return response()->json($page, 201);
        }
        else 
        {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function index()
    {
        $pages = Page::all();
        return response()->json($pages, 200);
    }
}
