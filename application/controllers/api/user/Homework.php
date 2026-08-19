<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Homework extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api_auth');

        $this->load->model('student_model');
        $this->load->model('homework_model');
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

        $student_id = $this->api_auth->userId();

        if (empty($student_id)) {
            return $this->response([
                'status'  => false,
                'message' => 'Unauthorized. Invalid or expired token.',
                'data'    => []
            ], 401);
        }

        $student = $this->student_model->get($student_id);

        if (empty($student)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student not found',
                'data'    => []
            ], 404);
        }

        $class_id   = $student['class_id'] ?? '';
        $section_id = $student['section_id'] ?? '';

        if (empty($class_id) || empty($section_id)) {
            return $this->response([
                'status'  => true,
                'message' => 'Student class or section not found',
                'data'    => []
            ], 200);
        }

        $homeworklist = $this->homework_model
            ->getStudentHomework(
                $class_id,
                $section_id
            );

        if (empty($homeworklist)) {
            return $this->response([
                'status'  => true,
                'message' => 'No homework found',
                'data'    => []
            ], 200);
        }

        foreach ($homeworklist as $key => $homework) {

            $report = $this->homework_model
                ->getEvaluationReportForStudent(
                    $homework['id'],
                    $student_id
                );

            $homeworklist[$key]['report'] = $report;
        }

        return $this->response([
            'status'  => true,
            'message' => 'Homework fetched successfully',
            'data'    => $homeworklist
        ], 200);
    }

    public function detail()
    {
        // Only POST
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->response([
                'status'  => false,
                'message' => 'Only POST method is allowed',
                'data'    => []
            ], 405);
        }

        $student_id = $this->api_auth->userId();

        if (empty($student_id)) {
            return $this->response([
                'status'  => false,
                'message' => 'Unauthorized. Invalid or expired token.',
                'data'    => []
            ], 401);
        }

        $student = $this->student_model->get($student_id);

        if (empty($student)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student not found',
                'data'    => []
            ], 404);
        }

        $input = json_decode(
            $this->input->raw_input_stream,
            true
        );

        if (!is_array($input)) {
            $input = $this->input->post();
        }

        $homework_id = isset($input['id'])
            ? (int) $input['id']
            : 0;

        if ($homework_id <= 0) {
            return $this->response([
                'status'  => false,
                'message' => 'Homework ID is required',
                'data'    => []
            ], 422);
        }
        $homework = $this->homework_model
            ->getRecord($homework_id);

        if (empty($homework)) {
            return $this->response([
                'status'  => false,
                'message' => 'Homework not found',
                'data'    => []
            ], 404);
        }

        if (
            (string) $homework['class_id'] !==
                (string) ($student['class_id'] ?? '') ||
            (string) $homework['section_id'] !==
                (string) ($student['section_id'] ?? '')
        ) {
            return $this->response([
                'status'  => false,
                'message' => 'You are not authorized to access this homework',
                'data'    => []
            ], 403);
        }

        $report = $this->homework_model
            ->getEvaluationReportForStudent(
                $homework_id,
                $student_id
            );

        $homework['report'] = $report;

        return $this->response([
            'status'  => true,
            'message' => 'Homework detail fetched successfully',
            'data'    => $homework
        ], 200);
    }

    public function download()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->response([
                'status'  => false,
                'message' => 'Only POST method is allowed',
                'data'    => []
            ], 405);
        }

        $student_id = $this->api_auth->userId();

        if (empty($student_id)) {
            return $this->response([
                'status'  => false,
                'message' => 'Unauthorized. Invalid or expired token.',
                'data'    => []
            ], 401);
        }

        $student = $this->student_model->get($student_id);

        if (empty($student)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student not found',
                'data'    => []
            ], 404);
        }

        $input = json_decode(
            $this->input->raw_input_stream,
            true
        );

        if (!is_array($input)) {
            $input = $this->input->post();
        }

        $homework_id = isset($input['id'])
            ? (int) $input['id']
            : 0;

        $doc = isset($input['doc'])
            ? trim($input['doc'])
            : '';

        if ($homework_id <= 0) {
            return $this->response([
                'status'  => false,
                'message' => 'Homework ID is required',
                'data'    => []
            ], 422);
        }

        if ($doc === '') {
            return $this->response([
                'status'  => false,
                'message' => 'Document name is required',
                'data'    => []
            ], 422);
        }
        $homework = $this->homework_model
            ->getRecord($homework_id);

        if (empty($homework)) {
            return $this->response([
                'status'  => false,
                'message' => 'Homework not found',
                'data'    => []
            ], 404);
        }
        if (
            (string) $homework['class_id'] !==
                (string) ($student['class_id'] ?? '') ||
            (string) $homework['section_id'] !==
                (string) ($student['section_id'] ?? '')
        ) {
            return $this->response([
                'status'  => false,
                'message' => 'You are not authorized to download this homework',
                'data'    => []
            ], 403);
        }
        $extension = pathinfo($doc, PATHINFO_EXTENSION);

        if ($extension === '') {
            return $this->response([
                'status'  => false,
                'message' => 'Invalid document name',
                'data'    => []
            ], 422);
        }
        $extension = strtolower($extension);

        $allowed_extensions = [
            'pdf',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'jpg',
            'jpeg',
            'png',
            'csv'
        ];

        if (!in_array($extension, $allowed_extensions, true)) {
            return $this->response([
                'status'  => false,
                'message' => 'File type is not allowed',
                'data'    => []
            ], 400);
        }

        $filepath = FCPATH .
            'uploads/homework/' .
            $homework_id .
            '.' .
            $extension;

        if (!file_exists($filepath)) {
            return $this->response([
                'status'  => false,
                'message' => 'Homework document not found',
                'data'    => []
            ], 404);
        }

        $this->load->helper('download');

        $file_data = file_get_contents($filepath);

        force_download(
            $doc,
            $file_data
        );
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