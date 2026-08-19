<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Hostel extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api_auth');

        $this->load->model('student_model');
        $this->load->model('hostel_model');
        $this->load->model('hostelroom_model');

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

        $hostels = $this->hostel_model->listhostel();

        if (empty($hostels)) {
            return $this->response([
                'status'  => true,
                'message' => 'No hostels found',
                'data'    => []
            ], 200);
        }

        return $this->response([
            'status'  => true,
            'message' => 'Hostels fetched successfully',
            'data'    => $hostels
        ], 200);
    }

    public function rooms()
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

        $input = json_decode(
            $this->input->raw_input_stream,
            true
        );

        if (!is_array($input)) {
            $input = $this->input->post();
        }

        $hostel_id = isset($input['hostel_id'])
            ? trim($input['hostel_id'])
            : '';

        if ($hostel_id === '') {
            return $this->response([
                'status'  => false,
                'message' => 'Hostel ID is required',
                'data'    => []
            ], 422);
        }

        $hostel = $this->db
            ->where('id', $hostel_id)
            ->get('hostel')
            ->row_array();

        if (empty($hostel)) {
            return $this->response([
                'status'  => false,
                'message' => 'Hostel not found',
                'data'    => []
            ], 404);
        }

        $rooms = $this->hostelroom_model
            ->getRoomByHoselID($hostel_id);

        if (empty($rooms)) {
            return $this->response([
                'status'  => true,
                'message' => 'No rooms found',
                'data'    => []
            ], 200);
        }

        return $this->response([
            'status'  => true,
            'message' => 'Hostel rooms fetched successfully',
            'data'    => $rooms
        ], 200);
    }


    public function assignedRoom()
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

        $student = $this->student_model->get($student_id);

        if (empty($student)) {
            return $this->response([
                'status'  => false,
                'message' => 'Student not found',
                'data'    => []
            ], 404);
        }

        if (empty($student['hostel_room_id'])) {
            return $this->response([
                'status'  => true,
                'message' => 'No hostel room assigned',
                'data'    => []
            ], 200);
        }

        $this->db->select('
            hostel_rooms.id AS hostel_room_id,
            hostel_rooms.hostel_id,
            hostel_rooms.room_type_id,
            hostel_rooms.room_no,
            hostel_rooms.no_of_bed,
            hostel_rooms.cost_per_bed,
            hostel_rooms.description,
            hostel.hostel_name,
            room_types.room_type
        ');

        $this->db->from('hostel_rooms');

        $this->db->join(
            'hostel',
            'hostel.id = hostel_rooms.hostel_id'
        );

        $this->db->join(
            'room_types',
            'room_types.id = hostel_rooms.room_type_id'
        );

        $this->db->where(
            'hostel_rooms.id',
            $student['hostel_room_id']
        );

        $assigned_room = $this->db
            ->get()
            ->row_array();

        if (empty($assigned_room)) {
            return $this->response([
                'status'  => true,
                'message' => 'Assigned hostel room not found',
                'data'    => []
            ], 200);
        }

        return $this->response([
            'status'  => true,
            'message' => 'Assigned hostel room fetched successfully',
            'data'    => [
                'student_id' => $student['id'],
                'hostel' => $assigned_room
            ]
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