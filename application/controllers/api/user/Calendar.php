<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Calendar extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->library('api_auth');

        $this->load->model('student_model');
        $this->load->model('calendar_model');
    }

    public function events()
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

        $result = $this->calendar_model->getStudentEvents();

        $events = [];

        if (!empty($result)) {

            foreach ($result as $value) {

                if ($value['event_type'] === 'task') {

                    if ((string) $value['event_for'] !== (string) $user_id) {
                        continue;
                    }
                }

                $events[] = [
                    'id'          => $value['id'],
                    'title'       => $value['event_title'],
                    'start'       => $value['start_date'],
                    'end'         => $value['end_date'],
                    'description' => $value['event_description'],
                    'event_type'  => $value['event_type'],
                    'event_color' => $value['event_color'],
                    'is_active'   => $value['is_active']
                ];
            }
        }

        return $this->response([
            'status'  => true,
            'message' => empty($events)
                ? 'No calendar events found'
                : 'Calendar events fetched successfully',
            'data'    => $events
        ], 200);
    }

    public function tasks()
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

        $tasks = $this->calendar_model->getTask(
            100,
            0,
            $user_id,
            0
        );

        if (empty($tasks)) {
            return $this->response([
                'status'  => true,
                'message' => 'No tasks found',
                'data'    => []
            ], 200);
        }

        $data = [];

        foreach ($tasks as $task) {

            $data[] = [
                'id'          => $task['id'],
                'title'       => $task['event_title'],
                'description' => $task['event_description'],
                'start_date'  => $task['start_date'],
                'end_date'    => $task['end_date'],
                'event_type'  => $task['event_type'],
                'event_color' => $task['event_color'],
                'is_active'   => $task['is_active'],
                'event_for'   => $task['event_for']
            ];
        }

        return $this->response([
            'status'  => true,
            'message' => 'Tasks fetched successfully',
            'data'    => $data
        ], 200);
    }

    public function task()
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

        $input = json_decode(
            $this->input->raw_input_stream,
            true
        );

        if (!is_array($input)) {
            $input = $this->input->post();
        }

        $task_id = isset($input['id'])
            ? (int) $input['id']
            : 0;

        if ($task_id <= 0) {
            return $this->response([
                'status'  => false,
                'message' => 'Task ID is required',
                'data'    => []
            ], 422);
        }

        $task = $this->calendar_model->getEvents($task_id);

        if (empty($task)) {
            return $this->response([
                'status'  => false,
                'message' => 'Task not found',
                'data'    => []
            ], 404);
        }

        if (
            $task['event_type'] === 'task' &&
            (string) $task['event_for'] !== (string) $user_id
        ) {
            return $this->response([
                'status'  => false,
                'message' => 'You are not authorized to access this task',
                'data'    => []
            ], 403);
        }

        return $this->response([
            'status'  => true,
            'message' => 'Task fetched successfully',
            'data'    => $task
        ], 200);
    }

    public function add_task()
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

        $input = json_decode(
            $this->input->raw_input_stream,
            true
        );

        if (!is_array($input)) {
            $input = $this->input->post();
        }

        $task_title = isset($input['task_title'])
            ? trim($input['task_title'])
            : '';

        $task_date = isset($input['task_date'])
            ? trim($input['task_date'])
            : '';

        $event_id = isset($input['eventid'])
            ? (int) $input['eventid']
            : 0;

        if ($task_title === '') {
            return $this->response([
                'status'  => false,
                'message' => 'Task title is required',
                'data'    => []
            ], 422);
        }

        if ($task_date === '') {
            return $this->response([
                'status'  => false,
                'message' => 'Task date is required',
                'data'    => []
            ], 422);
        }

        $timestamp = strtotime($task_date);

        if ($timestamp === false) {
            return $this->response([
                'status'  => false,
                'message' => 'Invalid task date',
                'data'    => []
            ], 422);
        }

        $start_date = date(
            'Y-m-d H:i:s',
            $timestamp
        );

        $eventdata = [
            'event_title'       => $task_title,
            'event_description' => '',
            'start_date'        => $start_date,
            'end_date'          => $start_date,
            'event_type'        => 'task',
            'event_color'       => '#000',
            'event_for'         => $user_id,
            'role_id'           => 0
        ];

        if ($event_id > 0) {

            $existing = $this->calendar_model
                ->getEvents($event_id);

            if (empty($existing)) {
                return $this->response([
                    'status'  => false,
                    'message' => 'Task not found',
                    'data'    => []
                ], 404);
            }

            if (
                $existing['event_type'] === 'task' &&
                (string) $existing['event_for'] !== (string) $user_id
            ) {
                return $this->response([
                    'status'  => false,
                    'message' => 'You are not authorized to update this task',
                    'data'    => []
                ], 403);
            }

            $eventdata['id'] = $event_id;

            $this->calendar_model->saveEvent($eventdata);

            return $this->response([
                'status'  => true,
                'message' => 'Task updated successfully',
                'data'    => [
                    'id' => $event_id
                ]
            ], 200);
        }

        $this->calendar_model->saveEvent($eventdata);

        $new_id = $this->db->insert_id();

        return $this->response([
            'status'  => true,
            'message' => 'Task created successfully',
            'data'    => [
                'id' => $new_id
            ]
        ], 201);
    }

    public function complete_task()
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

        $input = json_decode(
            $this->input->raw_input_stream,
            true
        );

        if (!is_array($input)) {
            $input = $this->input->post();
        }

        $task_id = isset($input['id'])
            ? (int) $input['id']
            : 0;

        $active = isset($input['active'])
            ? trim($input['active'])
            : '';

        if ($task_id <= 0) {
            return $this->response([
                'status'  => false,
                'message' => 'Task ID is required',
                'data'    => []
            ], 422);
        }

        if (!in_array($active, ['yes', 'no'], true)) {
            return $this->response([
                'status'  => false,
                'message' => 'Active must be yes or no',
                'data'    => []
            ], 422);
        }

        $task = $this->calendar_model->getEvents($task_id);

        if (empty($task)) {
            return $this->response([
                'status'  => false,
                'message' => 'Task not found',
                'data'    => []
            ], 404);
        }

        if (
            $task['event_type'] !== 'task' ||
            (string) $task['event_for'] !== (string) $user_id
        ) {
            return $this->response([
                'status'  => false,
                'message' => 'You are not authorized to update this task',
                'data'    => []
            ], 403);
        }

        $this->calendar_model->saveEvent([
            'id'        => $task_id,
            'is_active' => $active
        ]);

        return $this->response([
            'status'  => true,
            'message' => 'Task status updated successfully',
            'data'    => [
                'id'        => $task_id,
                'is_active' => $active
            ]
        ], 200);
    }

    public function delete_task()
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

        $input = json_decode(
            $this->input->raw_input_stream,
            true
        );

        if (!is_array($input)) {
            $input = $this->input->post();
        }

        $task_id = isset($input['id'])
            ? (int) $input['id']
            : 0;

        if ($task_id <= 0) {
            return $this->response([
                'status'  => false,
                'message' => 'Task ID is required',
                'data'    => []
            ], 422);
        }

        $task = $this->calendar_model->getEvents($task_id);

        if (empty($task)) {
            return $this->response([
                'status'  => false,
                'message' => 'Task not found',
                'data'    => []
            ], 404);
        }

        if (
            $task['event_type'] !== 'task' ||
            (string) $task['event_for'] !== (string) $user_id
        ) {
            return $this->response([
                'status'  => false,
                'message' => 'You are not authorized to delete this task',
                'data'    => []
            ], 403);
        }

        $this->calendar_model->deleteEvent($task_id);

        return $this->response([
            'status'  => true,
            'message' => 'Task deleted successfully',
            'data'    => [
                'id' => $task_id
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