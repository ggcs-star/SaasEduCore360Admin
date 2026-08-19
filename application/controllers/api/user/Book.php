<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Book extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api_auth');

        $this->load->model('student_model');
        $this->load->model('book_model');
        $this->load->model('librarymember_model');
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

        $books = $this->book_model->listbook();

        if (empty($books)) {
            return $this->response([
                'status'  => true,
                'message' => 'No books found',
                'data'    => []
            ], 200);
        }

        return $this->response([
            'status'  => true,
            'message' => 'Books fetched successfully',
            'data'    => $books
        ], 200);
    }

    public function my_books()
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

        $member_type = 'student';

        $book_list = $this->librarymember_model
            ->checkIsMember(
                $member_type,
                $user_id
            );

        if ($book_list === false || empty($book_list)) {
            return $this->response([
                'status'  => true,
                'message' => 'No issued books found',
                'data'    => []
            ], 200);
        }

        return $this->response([
            'status'  => true,
            'message' => 'Issued books fetched successfully',
            'data'    => $book_list
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