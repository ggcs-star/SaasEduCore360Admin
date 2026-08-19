<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Notification extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api_auth');

        $this->load->model('notification_model');
        $this->load->model('student_model');

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

        $student = $this->db
            ->where('id', $student_id)
            ->get('students')
            ->row_array();

        if (empty($student)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student not found',
                'data'    => []
            ], 404);
        }

        $notifications = $this->notification_model
            ->getNotificationForStudent($student_id);

        if (empty($notifications)) {
            return $this->response([
                'status'  => true,
                'message' => 'No notifications found',
                'data'    => []
            ], 200);
        }

        $data = [];

        foreach ($notifications as $notification) {

            if (is_object($notification)) {
                $notification = (array) $notification;
            }

            $data[] = $notification;
        }

        return $this->response([
            'status'  => true,
            'message' => 'Notifications fetched successfully',
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
 
    public function updateStatus()
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

        $input = json_decode(
            $this->input->raw_input_stream,
            true
        );

        if (!is_array($input)) {
            $input = $this->input->post();
        }

        $notification_id = isset($input['notification_id'])
            ? trim($input['notification_id'])
            : '';

        if ($notification_id === '') {
            return $this->response([
                'status'  => false,
                'message' => 'Notification ID is required',
                'data'    => []
            ], 422);
        }

        $result = $this->notification_model->updateStatus(
            $notification_id,
            $student_id
        );

        return $this->response([
            'status'  => true,
            'message' => 'Notification status updated successfully',
            'data'    => $result
        ], 200);
    }
}
