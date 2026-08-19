<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Teacher extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api_auth');

        $this->load->model('staff_model');
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

        $teachers = $this->staff_model->getEmployee('Teacher');

        if (empty($teachers)) {
            return $this->response([
                'status'  => true,
                'message' => 'No teachers found',
                'data'    => []
            ], 200);
        }

        $data = [];

        foreach ($teachers as $teacher) {

            $teacher_id = is_object($teacher)
                ? ($teacher->id ?? '')
                : ($teacher['id'] ?? '');

            $name = is_object($teacher)
                ? ($teacher->name ?? '')
                : ($teacher['name'] ?? '');

            $surname = is_object($teacher)
                ? ($teacher->surname ?? '')
                : ($teacher['surname'] ?? '');

            $email = is_object($teacher)
                ? ($teacher->email ?? '')
                : ($teacher['email'] ?? '');

            $phone = is_object($teacher)
                ? ($teacher->contact_no ?? ($teacher->phone ?? ''))
                : ($teacher['contact_no'] ?? ($teacher['phone'] ?? ''));

            $gender = is_object($teacher)
                ? ($teacher->sex ?? '')
                : ($teacher['sex'] ?? '');

            $image = is_object($teacher)
                ? ($teacher->image ?? '')
                : ($teacher['image'] ?? '');

            $data[] = [
                'teacher_id' => $teacher_id,
                'name'       => trim($name . ' ' . $surname),
                'email'      => $email,
                'phone'      => $phone,
                'gender'     => $gender,
                'image'      => $image
            ];
        }

        return $this->response([
            'status'  => true,
            'message' => 'Teachers fetched successfully',
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