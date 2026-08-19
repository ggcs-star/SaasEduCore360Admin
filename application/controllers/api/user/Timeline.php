<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Timeline extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api_auth');
        $this->load->model('timeline_model');
        $this->load->database();
    }

    public function add()
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

        $title = trim(
            $this->input->post('timeline_title')
        );

        if ($title === '') {
            return $this->response([
                'status'  => false,
                'message' => 'Timeline title is required',
                'data'    => [
                    'timeline_title' => 'Timeline title is required'
                ]
            ], 422);
        }

        $timeline = [
            'title'      => $title,
            'status'     => '',
            'date'       => date('Y-m-d'),
            'student_id' => $student_id
        ];

        $id = $this->timeline_model->add($timeline);

        if (empty($id)) {
            return $this->response([
                'status'  => false,
                'message' => 'Unable to add timeline',
                'data'    => []
            ], 500);
        }

        $document = '';

        if (
            isset($_FILES['timeline_doc']) &&
            !empty($_FILES['timeline_doc']['name'])
        ) {

            $upload_dir = './uploads/homework/';

            if (!is_dir($upload_dir)) {
                if (!mkdir($upload_dir, 0755, true)) {
                    return $this->response([
                        'status'  => false,
                        'message' => 'Unable to create upload directory',
                        'data'    => []
                    ], 500);
                }
            }

            $file_info = pathinfo(
                $_FILES['timeline_doc']['name']
            );

            $extension = strtolower(
                $file_info['extension'] ?? ''
            );

            $document = basename(
                $_FILES['timeline_doc']['name']
            );

            $file_name = $id . '.' . $extension;

            if (!move_uploaded_file(
                $_FILES['timeline_doc']['tmp_name'],
                $upload_dir . $file_name
            )) {

                return $this->response([
                    'status'  => false,
                    'message' => 'Timeline created but document upload failed',
                    'data'    => [
                        'timeline_id' => $id
                    ]
                ], 500);
            }
        }

        $upload_data = [
            'id'       => $id,
            'document' => $document
        ];

        $this->timeline_model->add($upload_data);

        return $this->response([
            'status'  => true,
            'message' => 'Timeline added successfully',
            'data'    => [
                'timeline_id' => $id,
                'title'       => $title,
                'date'        => date('Y-m-d'),
                'document'    => $document
            ]
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