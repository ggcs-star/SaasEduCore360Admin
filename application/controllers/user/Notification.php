<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Notification extends Student_Controller {

    function __construct() {
        parent::__construct();
    }

    function index() {

    $this->session->set_userdata('sub_menu', 'user/notification');

    $data['title'] = 'Notifications';
    $data['notificationlist'] = array();

    $student_id = $this->customlib->getStudentSessionUserID();

    $student = $this->student_model->get($student_id);

    // Student record not found
    if (empty($student)) {

        $this->load->view('layout/student/header', $data);
        $this->load->view('user/notification/notificationList', $data);
        $this->load->view('layout/student/footer', $data);

        return;
    }

    $student_id = $student['id'];

    $notifications = $this->notification_model->getNotificationForStudent($student_id);

    $data['notificationlist'] = !empty($notifications)
        ? $notifications
        : array();

    $this->load->view('layout/student/header', $data);
    $this->load->view('user/notification/notificationList', $data);
    $this->load->view('layout/student/footer', $data);
}

    function updatestatus() {
        $notification_id = $this->input->post('notification_id');
        $student_id = $this->customlib->getStudentSessionUserID();
        $data = $this->notification_model->updateStatus($notification_id, $student_id);
        $array = array('status' => "success", 'data' => $data);
        echo json_encode($array);
    }

}

?>