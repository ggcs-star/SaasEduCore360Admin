<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Profile extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $student_id = $this->customlib->getStudentSessionUserID();

        $student = $this->student_model->get($student_id);

        if (empty($student)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Student not found',
                    'data' => []
                ]));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Profile fetched successfully',
                'data' => [
                    'student_id' => $student['id'],
                    'admission_no' => $student['admission_no'] ?? '',
                    'firstname' => $student['firstname'] ?? '',
                    'lastname' => $student['lastname'] ?? '',
                    'mobile' => $student['mobileno'] ?? '',
                    'email' => $student['email'] ?? '',
                    'image' => $student['image'] ?? ''
                ]
            ]));
    }
}