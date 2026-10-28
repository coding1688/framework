<?php

namespace App\Http\Controllers\Backend;

use App\Services\Backend\PageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends BaseController
{
    public function __construct(private PageService $service) {}

    public function __invoke(string $section): View
    {
        $page = $this->service->page($section);

        return view($page['view'], $page['data']);
    }

    public function menu(Request $request): View
    {
        $page = $this->service->menuPage($request->path());

        return view($page['view'], $page['data']);
    }
}
