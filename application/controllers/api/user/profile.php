<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Profile extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // API authentication
        $this->load->library('api_auth');

        // Existing student model
        $this->load->model('student_model');
    }

    /**
     * Student Profile API
     *
     * Method: POST
     *
     * URL:
     * /user/api/profile
     *
     * Authorization:
     * Bearer {token}
     */
    public function index()
    {
        // ---------------------------------------
        // 1. Only POST request allowed
        // ---------------------------------------

        if ($this->input->method(TRUE) !== 'POST') {
            return $this->response([
                'status'  => false,
                'message' => 'Only POST method is allowed',
                'data'    => []
            ], 405);
        }

        // ---------------------------------------
        // 2. Authenticate Bearer Token
        // ---------------------------------------

        $user_id = $this->api_auth->userId();

        if (empty($user_id)) {
            return $this->response([
                'status'  => false,
                'message' => 'Unauthorized. Invalid or expired token.',
                'data'    => []
            ], 401);
        }

        // ---------------------------------------
        // 3. Get Student
        // ---------------------------------------

        $student = $this->student_model->get($user_id);

        if (empty($student)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student not found',
                'data'    => []
            ], 404);
        }

        // ---------------------------------------
        // 4. Profile Response
        // ---------------------------------------

        $data = [
            'student_id'   => $student['id'] ?? '',
            'admission_no' => $student['admission_no'] ?? '',
            'firstname'    => $student['firstname'] ?? '',
            'lastname'     => $student['lastname'] ?? '',
            'mobile'       => $student['mobileno'] ?? '',
            'email'        => $student['email'] ?? '',
            'image'        => $student['image'] ?? ''
        ];

        // ---------------------------------------
        // 5. Final Response
        // ---------------------------------------

        return $this->response([
            'status'  => true,
            'message' => 'Profile fetched successfully',
            'data'    => $data
        ], 200);
    }

    /**
     * JSON Response
     */
    private function response($data, $status_code = 200)
    {
        return $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(
                json_encode(
                    $data,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                )
            );
    }
}