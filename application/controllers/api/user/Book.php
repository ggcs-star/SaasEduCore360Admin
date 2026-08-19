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

    /**
     * Available Library Books
     *
     * POST /user/api/books
     */
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

    /**
     * My Issued Books
     *
     * POST /user/api/my-books
     */
    public function my_books()
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
        // 4. Existing MVC Library Logic
        // ---------------------------------------

        $member_type = 'student';

        $book_list = $this->librarymember_model
            ->checkIsMember(
                $member_type,
                $user_id
            );

        // ---------------------------------------
        // 5. Student is not library member
        // OR no issued books
        // ---------------------------------------

        if ($book_list === false || empty($book_list)) {
            return $this->response([
                'status'  => true,
                'message' => 'No issued books found',
                'data'    => []
            ], 200);
        }

        // ---------------------------------------
        // 6. Issued Books Response
        // ---------------------------------------

        return $this->response([
            'status'  => true,
            'message' => 'Issued books fetched successfully',
            'data'    => $book_list
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