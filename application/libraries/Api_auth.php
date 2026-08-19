<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Api_auth
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();

        $this->CI->load->database();
    }

    /**
     * Get Bearer Token from Authorization header
     */
    public function getToken()
    {
        $header = '';

        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $header = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();

            if (isset($headers['Authorization'])) {
                $header = trim($headers['Authorization']);
            }
        }

        if (empty($header)) {
            return null;
        }

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Get logged-in user from token
     */
    public function user()
    {
        $token = $this->getToken();

        if (empty($token)) {
            return false;
        }

        $auth = $this->CI->db
            ->where('token', $token)
            ->where('expired_at >', date('Y-m-d H:i:s'))
            ->get('users_authentication')
            ->row_array();

        if (empty($auth)) {
            return false;
        }

        return $auth;
    }

    /**
     * Get users_id from token
     */
    public function userId()
    {
        $auth = $this->user();

        if (empty($auth)) {
            return false;
        }

        return $auth['users_id'];
    }
}