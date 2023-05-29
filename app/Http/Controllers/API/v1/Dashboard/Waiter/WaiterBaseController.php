<?php

namespace App\Http\Controllers\API\v1\Dashboard\Waiter;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;

abstract class WaiterBaseController extends Controller
{
    use ApiResponse;

    public function __construct()
    {
        parent::__construct();
        $this->middleware(['sanctum.check', 'role:waiter']);
    }
}
