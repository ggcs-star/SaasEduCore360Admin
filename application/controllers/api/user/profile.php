<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Profile extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api_auth');

        $this->load->model('student_model');
    }
    public function index()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->response([
                'status'  => false,
                'message' => 'Only POST method is allowed',
                'data'    => []
            ], 405);
        }
        $user_id = $this->api_auth->userId();

        if (empty($user_id)) {
            return $this->response([
                'status'  => false,
                'message' => 'Unauthorized. Invalid or expired token.',
                'data'    => []
            ], 401);
        }

        $student = $this->student_model->get($user_id);

        if (empty($student)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student not found',
                'data'    => []
            ], 404);
        }

        $data = [
            'student_id'   => $student['id'] ?? '',
            'admission_no' => $student['admission_no'] ?? '',
            'firstname'    => $student['firstname'] ?? '',
            'lastname'     => $student['lastname'] ?? '',
            'mobile'       => $student['mobileno'] ?? '',
            'email'        => $student['email'] ?? '',
            'image'        => $student['image'] ?? ''
        ];

        return $this->response([
            'status'  => true,
            'message' => 'Profile fetched successfully',
            'data'    => $data
        ], 200);
    }

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