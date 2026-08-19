<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Content extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('content_model');
        $this->load->model('student_model');
    }

    public function index()
    {
        // Only POST method allowed
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Only POST method is allowed',
                'data'    => []
            ], 405);
        }

        /*
         * Get request data
         *
         * Supports:
         * 1. application/json
         * 2. x-www-form-urlencoded
         * 3. form-data
         */

        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            $input = [];
        }

        // JSON first, then normal POST
        $student_id = isset($input['student_id'])
            ? $input['student_id']
            : $this->input->post('student_id');

        $category = isset($input['category'])
            ? $input['category']
            : $this->input->post('category');


        // Student ID validation
        if (empty($student_id)) {

            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Student ID is required',
                'data'    => []
            ], 400);
        }


        // Category validation
        if (empty($category)) {

            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Category is required',
                'data'    => []
            ], 400);
        }


        // Allowed categories
        $allowed_categories = [
            'Assignments',
            'Study Material',
            'Syllabus',
            'Other Download'
        ];


        if (!in_array($category, $allowed_categories)) {

            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Invalid category',
                'data'    => [],
                'allowed_categories' => $allowed_categories
            ], 400);
        }


        // Get student details
        $student = $this->student_model->get($student_id);


        if (empty($student)) {

            return $this->jsonResponse([
                'status'  => false,
                'message' => 'Student not found',
                'data'    => []
            ], 404);
        }


        // Get student's class and section
        $class_id   = $student['class_id'];
        $section_id = $student['section_id'];


        // Get content according to:
        // Class + Section + Category
        $list = $this->content_model->getListByCategoryforUser(
            $class_id,
            $section_id,
            $category
        );


        // No content found
        if (empty($list)) {

            return $this->jsonResponse([
                'status'  => true,
                'message' => 'No content found',
                'data'    => []
            ], 200);
        }


        // Success
        return $this->jsonResponse([
            'status'  => true,
            'message' => 'Content fetched successfully',
            'data'    => $list
        ], 200);
    }


    /**
     * JSON Response
     */
    private function jsonResponse($data, $status_code = 200)
    {
        http_response_code($status_code);

        header('Content-Type: application/json');

        echo json_encode($data);

        exit;
    }
}