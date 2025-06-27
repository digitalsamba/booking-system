<?php
/**
 * User Controller
 * 
 * Handles user profile management
 * Delegates to AuthController for actual implementation
 */

namespace App\Controllers;

use App\Utils\Response;

class UserController extends BaseController {
    private $authController;
    
    public function __construct() {
        $this->authController = new AuthController();
    }
    
    /**
     * Get user profile
     * Delegates to AuthController::getProfile()
     */
    public function getProfile() {
        return $this->authController->getProfile();
    }
    
    /**
     * Update user profile
     * Delegates to AuthController::updateProfile()
     */
    public function updateProfile() {
        return $this->authController->updateProfile();
    }
}
