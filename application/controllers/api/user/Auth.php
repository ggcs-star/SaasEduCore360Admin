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

    /**
     * Login API
     *
     * Method: POST
     * URL: /user/api/login
     *
     * Allowed roles:
     * - student
     * - parent
     */
    public function login()
    {
        // --------------------------------------------------
        // 1. Only POST allowed
        // --------------------------------------------------

        if ($this->input->method(TRUE) !== 'POST') {
            return $this->jsonResponse(
                false,
                'Only POST method is allowed',
                [],
                405
            );
        }

        // --------------------------------------------------
        // 2. Read JSON / form-data
        // --------------------------------------------------

        $input = json_decode(
            $this->input->raw_input_stream,
            true
        );

        if (!is_array($input)) {
            $input = $this->input->post();
        }

        // --------------------------------------------------
        // 3. Username & Password
        // --------------------------------------------------

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

        // --------------------------------------------------
        // 4. Existing MVC Login Logic
        // --------------------------------------------------

        $login_post = [
            'username' => $username,
            'password' => $password
        ];

        $login_details = $this->user_model->checkLogin($login_post);

        // --------------------------------------------------
        // 5. Invalid Login
        // --------------------------------------------------

        if (empty($login_details)) {
            return $this->jsonResponse(
                false,
                'Invalid Username or Password',
                [],
                401
            );
        }

        $user = $login_details[0];

        // --------------------------------------------------
        // 6. Allow only Student / Parent
        // --------------------------------------------------

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

        // --------------------------------------------------
        // 7. Account Status
        // --------------------------------------------------

        if ($user->is_active !== 'yes') {
            return $this->jsonResponse(
                false,
                'Your account is disabled please contact to administrator',
                [],
                403
            );
        }

        // --------------------------------------------------
        // 8. TOKEN
        // --------------------------------------------------

        $current_time = date('Y-m-d H:i:s');

        $existing_token = $this->db
            ->where('users_id', $user->id)
            ->order_by('id', 'DESC')
            ->get('users_authentication')
            ->row_array();

        // --------------------------------------------------
        // 9. Reuse existing valid token
        // --------------------------------------------------

        if (
            !empty($existing_token) &&
            !empty($existing_token['token']) &&
            !empty($existing_token['expired_at']) &&
            strtotime($existing_token['expired_at']) > time()
        ) {

            $token = $existing_token['token'];
            $expired_at = $existing_token['expired_at'];

        } else {

            // --------------------------------------------------
            // 10. Generate new token
            // --------------------------------------------------

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

            // Token valid for 30 days
            $expired_at = date(
                'Y-m-d H:i:s',
                strtotime('+30 days')
            );

            // --------------------------------------------------
            // 11. Update existing expired token
            // --------------------------------------------------

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

                // --------------------------------------------------
                // 12. First login - create token
                // --------------------------------------------------

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

        // --------------------------------------------------
        // 13. Student Response
        // --------------------------------------------------

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

        // --------------------------------------------------
        // 14. Parent Response
        // --------------------------------------------------

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

        // --------------------------------------------------
        // 15. Fallback
        // --------------------------------------------------

        return $this->jsonResponse(
            false,
            'Unable to process login',
            [],
            403
        );
    }

    /**
     * Common JSON Response
     */
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