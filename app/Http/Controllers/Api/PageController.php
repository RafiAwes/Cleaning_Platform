<?php

namespace App\Http\Controllers\Api;

use App\Models\FAQ;
use App\Models\Page;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;


class PageController extends Controller
{
    public function createPageContent(Request $request)
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

    public function indexPageContent()
    {
        $pages = Page::all();
        return response()->json($pages, 200);
    }

    public function createFaqContent(Request $request)
    {
        $request->validate([
            'question' => 'required|max:255',
            'answer' => 'required',
        ]);
        
        if (Auth::role() == 'admin')
        {
            $faq = FAQ::updateOrCreate([
                ['question' => $request->question],
                ['answer' => $request->answer],
            ]);
            return response()->json($faq, 201);
        }
        else 
        {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function indexFaqContent()
    {
        $faqs = FAQ::all();
        return response()->json($faqs, 200);
    }
}
