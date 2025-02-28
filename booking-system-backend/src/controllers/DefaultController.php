<?php
/**
 * Default Controller
 * 
 * Handles default routes
 */

namespace App\Controllers;

use App\Utils\Response;

class DefaultController extends BaseController {
    /**
     * Index action - default route
     *
     * @return void
     */
    public function index() {
        Response::json([
            'status' => 'ok',
            'message' => 'Booking System API',
            'version' => '1.0.0'
        ]);
    }
}