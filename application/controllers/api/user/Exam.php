<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Exam extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api_auth');

        // Existing models
        $this->load->model('student_model');
        $this->load->model('examschedule_model');

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
        $student_session = $this->db
            ->where('student_id', $student_id)
            ->order_by('id', 'DESC')
            ->get('student_session')
            ->row_array();

        if (empty($student_session)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student session not found',
                'data'    => []
            ], 404);
        }

        $class_id   = $student_session['class_id'] ?? '';
        $section_id = $student_session['section_id'] ?? '';

        if (empty($class_id) || empty($section_id)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student class or section not found',
                'data'    => []
            ], 404);
        }

        $exam_result = $this->examschedule_model
            ->getExamByClassandSection(
                $class_id,
                $section_id
            );

        if (empty($exam_result)) {
            return $this->response([
                'status'  => true,
                'message' => 'No exams found',
                'data'    => []
            ], 200);
        }

        $data = [];

        foreach ($exam_result as $exam) {

            $data[] = [
                'exam_id'       => $exam['id'] ?? '',
                'exam'          => $exam['exam'] ?? '',
                'subject_id'    => $exam['subject_id'] ?? '',
                'subject_name'  => $exam['name'] ?? '',
                'date_of_exam'  => $exam['date_of_exam'] ?? '',
                'start_time'    => $exam['start_to'] ?? '',
                'end_time'      => $exam['end_from'] ?? '',
                'room_no'       => $exam['room_no'] ?? '',
                'full_marks'    => $exam['full_marks'] ?? '',
                'passing_marks' => $exam['passing_marks'] ?? ''
            ];
        }

        return $this->response([
            'status'  => true,
            'message' => 'Exams fetched successfully',
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