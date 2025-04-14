<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\EventService;
use Illuminate\Http\Request;

class EventController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new EventService;
    }

    public function callback(Request $request)
    {
        $this->service->handler($request->all());

    }
}
