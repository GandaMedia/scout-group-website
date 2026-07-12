<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;

class ShowEventController extends Controller
{
    public function __invoke(CalendarEvent $event) {}
}
