<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Attendence extends Student_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get Student Attendance
     *
     * Example:
     * /user/api/attendence?year=2026&month=08
     */
    public function index()
    {
        $year  = $this->input->get('year');
        $month = $this->input->get('month');

        // Validation
        if (empty($year) || empty($month)) {
            return $this->response([
                'status'  => false,
                'message' => 'Year and month are required',
                'data'    => []
            ], 400);
        }

        // Logged-in student ID
        $student_id = $this->customlib->getStudentSessionUserID();

        if (empty($student_id)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student not found',
                'data'    => []
            ], 404);
        }

        // Student details
        $student = $this->student_model->get($student_id);

        if (empty($student) || empty($student['student_session_id'])) {
            return $this->response([
                'status'  => false,
                'message' => 'Student session not found',
                'data'    => []
            ], 404);
        }

        $student_session_id = $student['student_session_id'];
        

        // Number of days in selected month
        $totalDays = cal_days_in_month(
            CAL_GREGORIAN,
            (int) $month,
            (int) $year
        );

        $attendance = [];

        for ($day = 1; $day <= $totalDays; $day++) {

            $date = sprintf(
                '%04d-%02d-%02d',
                $year,
                $month,
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

                $classname = '';

                if ($type == 'Present') {
                    $classname = 'grade-4';

                } elseif ($type == 'Absent') {
                    $classname = 'grade-1';

                } elseif ($type == 'Late') {
                    $classname = 'grade-3';

                } elseif ($type == 'Late with excuse') {
                    $classname = 'grade-2';

                } elseif ($type == 'Holiday') {
                    $classname = 'grade-5';

                } elseif ($type == 'Half Day') {
                    $classname = 'grade-2';
                }

                $attendance[] = [
                    'student_id' => $student_id,
                    'student_name' => $student['firstname'] . ' ' . $student['lastname'],
                    'date' => $date,
                    'status' => $type
                ];
            }
        }

        return $this->response([
            'status'  => true,
            'message' => empty($attendance)
                ? 'No attendance found'
                : 'Attendance fetched successfully',
            'data' => $attendance
        ], 200);
    }


    /**
     * JSON Response
     */
    private function response($data, $status_code = 200)
    {
        return $this->output
            ->set_status_header($status_code)
            ->set_content_type('application/json')
            ->set_output(json_encode($data));
    }
}