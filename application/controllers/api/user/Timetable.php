<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Timetable extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        // API authentication
        $this->load->library('api_auth');

        // Existing models
        $this->load->model('student_model');
        $this->load->model('timetable_model');
    }

    /**
     * Student Timetable API
     *
     * Method: POST
     *
     * URL:
     * /user/api/timetable
     *
     * Authorization:
     * Bearer {token}
     */
    public function index()
    {
        // ---------------------------------------
        // 1. Only POST request allowed
        // ---------------------------------------

        if ($this->input->method(TRUE) !== 'POST') {
            return $this->response([
                'status'  => false,
                'message' => 'Only POST method is allowed',
                'data'    => []
            ], 405);
        }

        // ---------------------------------------
        // 2. Authenticate Bearer Token
        // ---------------------------------------

        $user_id = $this->api_auth->userId();

        if (empty($user_id)) {
            return $this->response([
                'status'  => false,
                'message' => 'Unauthorized. Invalid or expired token.',
                'data'    => []
            ], 401);
        }

        // ---------------------------------------
        // 3. Get student
        // ---------------------------------------

        $student = $this->student_model->get($user_id);

        if (empty($student)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student not found',
                'data'    => []
            ], 404);
        }

        // ---------------------------------------
        // 4. Student class & section
        // ---------------------------------------

        $class_id   = $student['class_id'];
        $section_id = $student['section_id'];

        // ---------------------------------------
        // 5. Current session
        // ---------------------------------------

        $current_session = $this->setting_model->getCurrentSession();

        // ---------------------------------------
        // 6. Get student subjects
        // Same subject logic as existing MVC
        // ---------------------------------------

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

        // ---------------------------------------
        // 7. No subjects found
        // ---------------------------------------

        if (empty($subjects)) {
            return $this->response([
                'status'  => true,
                'message' => 'No timetable found',
                'data'    => []
            ], 200);
        }

        // ---------------------------------------
        // 8. Days
        // Same days used by existing timetable
        // ---------------------------------------

        $days = [
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday'
        ];

        $final_array = [];

        // ---------------------------------------
        // 9. Get timetable subject-wise
        // ---------------------------------------

        foreach ($subjects as $subject) {

            $subject_timetable = [];

            foreach ($days as $day) {

                $where_array = [
                    'teacher_subject_id' => $subject['id'],
                    'day_name'            => $day
                ];

                $result = $this->timetable_model->get($where_array);

                if (!empty($result)) {

                    $subject_timetable[$day] = [
                        'status'     => 'Yes',
                        'start_time' => $result[0]['start_time'],
                        'end_time'   => $result[0]['end_time'],
                        'room_no'    => $result[0]['room_no']
                    ];

                } else {

                    $subject_timetable[$day] = [
                        'status'     => 'No',
                        'start_time' => 'N/A',
                        'end_time'   => 'N/A',
                        'room_no'    => 'N/A'
                    ];
                }
            }

            $final_array[$subject['name']] = $subject_timetable;
        }

        // ---------------------------------------
        // 10. Final response
        // ---------------------------------------

        return $this->response([
            'status'  => true,
            'message' => 'Timetable fetched successfully',
            'data'    => $final_array
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
            ->set_output(
                json_encode(
                    $data,
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                )
            );
    }
}