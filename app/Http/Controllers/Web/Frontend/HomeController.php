<?php

namespace App\Http\Controllers\Web\Frontend;

use App\Enums\PageEnum;
use App\Http\Controllers\Controller;
use App\Models\CMS;
use App\Models\Post;
use App\Models\Product;
use App\Models\SocialLink;
use Modules\Portfolio\Models\Project;
use Modules\Portfolio\Models\Type;

class HomeController extends Controller
{
    public function index()
    {
        $cms = [
            'home' => CMS::where('page', PageEnum::HOME)->where('status', 'active')->get(),
            'common' => CMS::where('page', PageEnum::COMMON)->where('status', 'active')->get(),
        ];

        $socials = SocialLink::where('status', 'active')->get();
        
        $posts = Post::with(['category', 'subcategory', 'user', 'images'])->where('status', 'active')->latest()->limit(3)->get();

        $types = Type::where('status', 'active')->get();
        $projects = Project::where('status', 'active')->get();

        $products = Product::with(['category', 'user'])->where('status', 'active')->get();

        return view('frontend.layouts.home.index', compact('cms', 'posts', 'types', 'projects', 'products', 'socials'));
    }

    public function post($slug){
        $cms = [
            'home' => CMS::where('page', PageEnum::HOME)->where('status', 'active')->get(),
            'common' => CMS::where('page', PageEnum::COMMON)->where('status', 'active')->get(),
        ];
        $post = Post::where('slug', base64_decode($slug))->where('status', 'active')->firstOrFail();
        return view('frontend.layouts.post', compact('cms', 'post'));
    }
}
