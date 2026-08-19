<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Route extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api_auth');

        $this->load->model('student_model');
        $this->load->model('vehroute_model');

        $this->load->database();
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

        $user = $this->db
            ->where('id', $user_id)
            ->get('users')
            ->row_array();

        if (empty($user)) {
            return $this->response([
                'status'  => false,
                'message' => 'User not found',
                'data'    => []
            ], 404);
        }

        if (($user['role'] ?? '') !== 'student') {
            return $this->response([
                'status'  => false,
                'message' => 'Only student access is allowed',
                'data'    => []
            ], 403);
        }

        $student_id = $user['user_id'] ?? '';

        if (empty($student_id)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student ID not found',
                'data'    => []
            ], 404);
        }

        $student = $this->student_model->get($student_id);

        if (empty($student)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student not found',
                'data'    => []
            ], 404);
        }

        
        $routes = $this->vehroute_model->listroute();

        if (empty($routes)) {
            return $this->response([
                'status'  => true,
                'message' => 'No routes found',
                'data'    => []
            ], 200);
        }
        return $this->response([
            'status'  => true,
            'message' => 'Routes fetched successfully',
            'data'    => [
                'student' => $student,
                'routes'  => $routes
            ]
        ], 200);
    }

    public function getbusdetail()
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

        $user = $this->db
            ->where('id', $user_id)
            ->get('users')
            ->row_array();

        if (empty($user)) {
            return $this->response([
                'status'  => false,
                'message' => 'User not found',
                'data'    => []
            ], 404);
        }

        if (($user['role'] ?? '') !== 'student') {
            return $this->response([
                'status'  => false,
                'message' => 'Only student access is allowed',
                'data'    => []
            ], 403);
        }

        $input = json_decode(
            $this->input->raw_input_stream,
            true
        );

        if (!is_array($input)) {
            $input = $this->input->post();
        }

        $vehrouteid = isset($input['vehrouteid'])
            ? trim($input['vehrouteid'])
            : '';

        if ($vehrouteid === '') {
            return $this->response([
                'status'  => false,
                'message' => 'Vehicle route ID is required',
                'data'    => []
            ], 422);
        }

        $result = $this->vehroute_model
            ->getVechileDetailByVecRouteID($vehrouteid);

        if (empty($result)) {
            return $this->response([
                'status'  => true,
                'message' => 'No vehicle details found',
                'data'    => []
            ], 200);
        }

        return $this->response([
            'status'  => true,
            'message' => 'Vehicle details fetched successfully',
            'data'    => $result
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