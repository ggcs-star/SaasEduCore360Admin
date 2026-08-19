<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
class Attendence extends CI_Controller
{
   public function __construct()
    {
        parent::__construct();

        $this->load->library('api_auth');

        $this->load->model('student_model');
        $this->load->model('attendencetype_model');
    }

    public function index()
    {
        $year  = $this->input->post('year');
        $month = $this->input->post('month');

        if (empty($year) || empty($month)) {
            return $this->response([
                'status'  => false,
                'message' => 'Year and month are required',
                'data'    => []
            ], 400);
        }

        if ((int) $month < 1 || (int) $month > 12) {
            return $this->response([
                'status'  => false,
                'message' => 'Invalid month',
                'data'    => []
            ], 400);
        }
        $user_id = $this->api_auth->userId();

        if (empty($user_id)) {
            return $this->response([
                'status'  => false,
                'message' => 'Unauthorized. Invalid or expired token.',
                'data'    => []
            ], 401);
        }

        $student = $this->db
            ->where('id', $user_id)
            ->get('students')
            ->row_array();

        if (empty($student)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student not found',
                'data'    => []
            ], 404);
        }

        $student_id = $student['id'];
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

        $student_session_id = $student_session['id'];
        $totalDays = cal_days_in_month(
            CAL_GREGORIAN,
            (int) $month,
            (int) $year
        );

        $attendance = [];

        for ($day = 1; $day <= $totalDays; $day++) {

            $date = sprintf(
                '%04d-%02d-%02d',
                (int) $year,
                (int) $month,
                $day
            );

            $student_attendence =
                $this->attendencetype_model
                    ->getStudentAttendence(
                        $date,
                        $student_session_id
                    );

            if (!empty($student_attendence)) {

                $type = $student_attendence->type;

                $attendance[] = [
                    'student_id'   => (string) $student_id,
                    'student_name' => trim(
                        ($student['firstname'] ?? '') . ' ' .
                        ($student['lastname'] ?? '')
                    ),
                    'date'         => $date,
                    'status'       => $type
                ];
            }
        }

        return $this->response([
            'status'  => true,
            'message' => empty($attendance)
                ? 'No attendance found'
                : 'Attendance fetched successfully',
            'data'    => $attendance
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
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                )
            );
    }
}