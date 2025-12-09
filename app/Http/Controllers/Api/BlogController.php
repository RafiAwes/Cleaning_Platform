<?php

namespace App\Http\Controllers\Api;

use App\Models\Blog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;

class BlogController extends Controller
{
    public function createBlog(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'required',
        ]);

        if (!File::exists(public_path('images/blog')))
        {
            File::makeDirectory(public_path('images/blog'), 0777, true, true);
        }

        $imageName = null;
        if($request->hasFile('image'))
        {
            $imageName = time() . '.' . $request->image->getClientOriginalName();
            $request->image->move(public_path('images/blog'), $imageName);
        }

        $blog = new Blog();
        $blog->title = $request->title;
        $blog->description = $request->description;
        $blog->image = 'images/blog/' . $imageName;
        $blog->save();
        return redirect()->back()->with('message', 'Blog created successfully');
    }

    public function editBlog(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'description' => 'required',
        ]);

        $blog = Blog::find($id);
        $blog->title = $request->title;
        $blog->description = $request->description;
        if($request->hasFile('image'))
        {
            $imageName = time() . '.' . $request->image->getClientOriginalName();
            $request->image->move(public_path('images/blog'), $imageName);
            $blog->image = 'images/blog/' . $imageName;
        }
        $blog->save();
        return redirect()->back()->with('message', 'Blog updated successfully');
    }

    public function deleteBlog($id)
    {
        $blog = Blog::find($id);
        $blog->delete();
        return redirect()->back()->with('message', 'Blog deleted successfully');
    }
}
