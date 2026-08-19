<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('user_model');
        $this->load->model('student_model');
        $this->load->database();
    }

    /**
     * Student Login API
     *
     * POST /user/api/login
     */
    public function login()
    {
        // Only POST request allowed
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->jsonResponse(
                false,
                'Only POST method is allowed',
                [],
                405
            );
        }

        // Support JSON request
        $input = json_decode(
            $this->input->raw_input_stream,
            true
        );

        if (!is_array($input)) {
            $input = $this->input->post();
        }

        // Get username/password
        $username = isset($input['username'])
            ? trim($input['username'])
            : '';

        $password = isset($input['password'])
            ? $input['password']
            : '';

        // Validation
        if ($username === '' || $password === '') {
            return $this->jsonResponse(
                false,
                'Username and password are required',
                [],
                422
            );
        }

        // Existing MVC login logic
        $login_post = [
            'username' => $username,
            'password' => $password
        ];

        $login_details = $this->user_model->checkLogin($login_post);

        // Invalid username/password
        if (empty($login_details)) {
            return $this->jsonResponse(
                false,
                'Invalid Username or Password',
                [],
                401
            );
        }

        // Existing login returns array of objects
        $user = $login_details[0];

        // Only student login allowed
        if ($user->role !== 'student') {
            return $this->jsonResponse(
                false,
                'Only student login is allowed',
                [],
                403
            );
        }

        // Check account status
        if ($user->is_active !== 'yes') {
            return $this->jsonResponse(
                false,
                'Your account is disabled please contact to administrator',
                [],
                403
            );
        }

        // Get complete student information
        $result = $this->user_model->read_user_information($user->id);

        if (empty($result)) {
            return $this->jsonResponse(
                false,
                'Account Suspended',
                [],
                403
            );
        }

        $student = $result[0];

        // Generate secure random token
        try {
            $token = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            return $this->jsonResponse(
                false,
                'Unable to generate authentication token',
                [],
                500
            );
        }

        // Token expiry - 30 days
        $expired_at = date(
            'Y-m-d H:i:s',
            strtotime('+30 days')
        );

        /*
         * Save API token in existing table
         *
         * users_authentication:
         * users_id
         * token
         * expired_at
         * created_at
         * updated_at
         */
        $token_data = [
            'users_id'   => $user->id,
            'token'      => $token,
            'expired_at' => $expired_at,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $inserted = $this->db
            ->insert('users_authentication', $token_data);

        // Token save failed
        if (!$inserted) {
            return $this->jsonResponse(
                false,
                'Unable to create authentication token',
                [],
                500
            );
        }

        // Login response
        $data = [
            'token'      => $token,
            'token_type' => 'Bearer',

            'student' => [
                'id'        => $student->user_id,
                'user_id'   => $student->id,
                'username'  => $student->username,
                'firstname' => $student->firstname,
                'lastname'  => $student->lastname,
                'role'      => $student->role,
                'image'     => $student->image
            ]
        ];

        return $this->jsonResponse(
            true,
            'Login successful',
            $data,
            200
        );
    }

    /**
     * Common JSON response
     */
    private function jsonResponse(
        $status,
        $message,
        $data = [],
        $httpCode = 200
    ) {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($httpCode)
            ->set_output(
                json_encode([
                    'status'  => $status,
                    'message' => $message,
                    'data'    => $data
                ])
            );
    }
}