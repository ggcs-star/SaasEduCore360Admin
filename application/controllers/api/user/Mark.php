<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Mark extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api_auth');

        $this->load->model('student_model');
        $this->load->model('examschedule_model');
        $this->load->model('examresult_model');

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

        $exam_list = $this->examschedule_model
            ->getExamByClassandSection(
                $class_id,
                $section_id
            );

        if (empty($exam_list)) {
            return $this->response([
                'status'  => true,
                'message' => 'No exam results found',
                'data'    => []
            ], 200);
        }

        $data = [];

        foreach ($exam_list as $exam) {

            $exam_id = $exam['exam_id'] ?? '';

            if (empty($exam_id)) {
                continue;
            }

            $exam_subjects = $this->examschedule_model
                ->getresultByStudentandExam(
                    $exam_id,
                    $student_id
                );

            $exam_result = [];

            if (!empty($exam_subjects)) {

                foreach ($exam_subjects as $result) {

                    $exam_result[] = [
                        'exam_schedule_id' => $result['exam_schedule_id'] ?? '',
                        'exam_id'          => $result['exam_id'] ?? '',
                        'subject_name'     => $result['name'] ?? '',
                        'exam_type'        => $result['type'] ?? '',
                        'full_marks'       => $result['full_marks'] ?? '',
                        'passing_marks'    => $result['passing_marks'] ?? '',
                        'attendance'       => $result['attendence'] ?? '',
                        'get_marks'        => $result['get_marks'] ?? ''
                    ];
                }
            }

            $data[] = [
                'exam_id'     => $exam_id,
                'exam_name'   => $exam['name'] ?? '',
                'exam_result' => $exam_result
            ];
        }

        if (empty($data)) {
            return $this->response([
                'status'  => true,
                'message' => 'No exam results found',
                'data'    => []
            ], 200);
        }

        return $this->response([
            'status'  => true,
            'message' => 'Exam results fetched successfully',
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