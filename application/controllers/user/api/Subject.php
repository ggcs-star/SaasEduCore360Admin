<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Subject extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // Logged-in student
        $student_id = $this->customlib->getStudentSessionUserID();

        $student = $this->student_model->get($student_id);

        // Student record nahi mila
        if (empty($student)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_status_header(404)
                ->set_output(json_encode([
                    'status'  => false,
                    'message' => 'Student not found',
                    'data'    => []
                ]));
        }

        // Student ka class & section
        $class_id = $student['class_id'];
        $section_id = $student['section_id'];

        // Subjects fetch
        $subjects = $this->teachersubject_model
            ->getSubjectByClsandSection($class_id, $section_id);

        if (empty($subjects)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status'  => true,
                    'message' => 'No subjects found',
                    'data'    => []
                ]));
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

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status'  => true,
                'message' => 'Subjects fetched successfully',
                'data'    => $data
            ]));
    }
}