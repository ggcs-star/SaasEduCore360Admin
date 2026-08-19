<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Fees extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $student_id = $this->customlib->getStudentSessionUserID();

        $student = $this->student_model->get($student_id);

        // Student not found
        if (empty($student)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Student not found',
                    'data' => []
                ]));
        }

        // Student session not available
        if (empty($student['student_session_id'])) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'No fees found',
                    'data' => []
                ]));
        }

        // Get student fees
        $fees = $this->studentfeemaster_model->getStudentFees(
            $student['student_session_id']
        );

        if (empty($fees)) {
            return $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => true,
                    'message' => 'No fees found',
                    'data' => []
                ]));
        }

        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Fees fetched successfully',
                'data' => $fees
            ]));
    }
}