<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Subject extends CI_Controller
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

        $class_id   = $student['class_id'];
        $section_id = $student['section_id'];

        $current_session = $this->setting_model->getCurrentSession();

        $sql = "SELECT
                    teacher_subjects.*,
                    staff.name AS teacher_name,
                    staff.surname,
                    subjects.name,
                    subjects.type,
                    subjects.code
                FROM teacher_subjects
                INNER JOIN subjects
                    ON teacher_subjects.subject_id = subjects.id
                INNER JOIN class_sections
                    ON teacher_subjects.class_section_id = class_sections.id
                INNER JOIN staff
                    ON staff.id = teacher_subjects.teacher_id
                WHERE class_sections.class_id = " . $this->db->escape($class_id) . "
                AND class_sections.section_id = " . $this->db->escape($section_id) . "
                AND teacher_subjects.session_id = " . $this->db->escape($current_session);

        $query = $this->db->query($sql);

        $subjects = $query->result_array();

        if (empty($subjects)) {
            return $this->response([
                'status'  => true,
                'message' => 'No subjects found',
                'data'    => []
            ], 200);
        }

        $data = [];

        foreach ($subjects as $subject) {

            $data[] = [
                'subject_id'   => $subject['id'] ?? '',
                'subject_name' => $subject['name'] ?? '',
                'subject_code' => $subject['code'] ?? '',
                'teacher'      => $subject['teacher_name'] ?? ''
            ];
        }

        return $this->response([
            'status'  => true,
            'message' => 'Subjects fetched successfully',
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