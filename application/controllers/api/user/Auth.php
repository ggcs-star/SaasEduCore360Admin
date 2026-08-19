<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('user_model');
        $this->load->model('student_model');
        $this->load->database();
    }

    public function login()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            return $this->jsonResponse(
                false,
                'Only POST method is allowed',
                [],
                405
            );
        }

        $input = json_decode(
            $this->input->raw_input_stream,
            true
        );

        if (!is_array($input)) {
            $input = $this->input->post();
        }

        $username = isset($input['username'])
            ? trim($input['username'])
            : '';

        $password = isset($input['password'])
            ? $input['password']
            : '';

        if ($username === '' || $password === '') {
            return $this->jsonResponse(
                false,
                'Username and password are required',
                [],
                422
            );
        }

        $login_post = [
            'username' => $username,
            'password' => $password
        ];

        $login_details = $this->user_model->checkLogin($login_post);

        if (empty($login_details)) {
            return $this->jsonResponse(
                false,
                'Invalid Username or Password',
                [],
                401
            );
        }

        $user = $login_details[0];
        if (
            $user->role !== 'student' &&
            $user->role !== 'parent'
        ) {
            return $this->jsonResponse(
                false,
                'Only student and parent login is allowed',
                [],
                403
            );
        }

        if ($user->is_active !== 'yes') {
            return $this->jsonResponse(
                false,
                'Your account is disabled please contact to administrator',
                [],
                403
            );
        }

        $current_time = date('Y-m-d H:i:s');

        $existing_token = $this->db
            ->where('users_id', $user->id)
            ->order_by('id', 'DESC')
            ->get('users_authentication')
            ->row_array();

        if (
            !empty($existing_token) &&
            !empty($existing_token['token']) &&
            !empty($existing_token['expired_at']) &&
            strtotime($existing_token['expired_at']) > time()
        ) {

            $token = $existing_token['token'];
            $expired_at = $existing_token['expired_at'];

        } else {

            try {
                $token = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                return $this->jsonResponse(
                    false,
                    'Unable to generate authentication token',
                    [],
                    500
                );
            }

            $expired_at = date(
                'Y-m-d H:i:s',
                strtotime('+30 days')
            );

            if (!empty($existing_token)) {

                $updated = $this->db
                    ->where('id', $existing_token['id'])
                    ->update(
                        'users_authentication',
                        [
                            'token'      => $token,
                            'expired_at' => $expired_at,
                            'updated_at' => $current_time
                        ]
                    );

                if (!$updated) {
                    return $this->jsonResponse(
                        false,
                        'Unable to update authentication token',
                        [],
                        500
                    );
                }

            } else {

                $inserted = $this->db
                    ->insert(
                        'users_authentication',
                        [
                            'users_id'   => $user->id,
                            'token'      => $token,
                            'expired_at' => $expired_at,
                            'created_at' => $current_time,
                            'updated_at' => $current_time
                        ]
                    );

                if (!$inserted) {
                    return $this->jsonResponse(
                        false,
                        'Unable to create authentication token',
                        [],
                        500
                    );
                }
            }
        }
        if ($user->role === 'student') {

            $result = $this->user_model
                ->read_user_information($user->id);

            if (empty($result)) {
                return $this->jsonResponse(
                    false,
                    'Student account information not found',
                    [],
                    404
                );
            }

            $student = $result[0];

            $data = [
                'token'      => $token,
                'token_type' => 'Bearer',
                'expires_at' => $expired_at,

                'user' => [
                    'id'        => $user->id,
                    'username'  => $student->username,
                    'firstname' => $student->firstname,
                    'lastname'  => $student->lastname,
                    'role'      => 'student',
                    'image'     => $student->image
                ]
            ];

            return $this->jsonResponse(
                true,
                'Login successful',
                $data,
                200
            );
        }

        if ($user->role === 'parent') {

            $data = [
                'token'       => $token,
                'token_type'  => 'Bearer',
                'expires_at'  => $expired_at,

                'user' => [
                    'id'       => $user->id,
                    'username' => $user->username,
                    'role'     => 'parent',
                    'child_id' => !empty($user->childs)
                        ? $user->childs
                        : null
                ]
            ];

            return $this->jsonResponse(
                true,
                'Login successful',
                $data,
                200
            );
        }

        return $this->jsonResponse(
            false,
            'Unable to process login',
            [],
            403
        );
    }

    private function jsonResponse(
        $status,
        $message,
        $data = [],
        $httpCode = 200
    ) {
        return $this->output
            ->set_content_type('application/json')
            ->set_status_header($httpCode)
            ->set_output(
                json_encode(
                    [
                        'status'  => $status,
                        'message' => $message,
                        'data'    => $data
                    ],
                    JSON_UNESCAPED_UNICODE |
                    JSON_UNESCAPED_SLASHES
                )
            );
    }
}