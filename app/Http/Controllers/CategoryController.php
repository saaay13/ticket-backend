<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index() : View
    {
        $categories = Category::all();
        return view('category.index',['categories' => $categories]);
        }
    public function form()
    {  
        return view('category.form');
    }
    public function save(Request $request)
    {
        Category::create($request->toArray());
        return redirect()->route('category.show');
    }
}
