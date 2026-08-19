<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
  | -------------------------------------------------------------------------
  | URI ROUTING
  | -------------------------------------------------------------------------
  | This file lets you re-map URI requests to specific controller functions.
  |
  | Typically there is a one-to-one relationship between a URL string
  | and its corresponding controller class/method. The segments in a
  | URL normally follow this pattern:
  |
  |	example.com/class/method/id/
  |
  | In some instances, however, you may want to remap this relationship
  | so that a different class/function is called than the one
  | corresponding to the URL.
  |
  | Please see the user guide for complete details:
  |
  |	https://codeigniter.com/user_guide/general/routing.html
  |
  | -------------------------------------------------------------------------
  | RESERVED ROUTES
  | -------------------------------------------------------------------------
  |
  | There are three reserved routes:
  |
  |	$route['default_controller'] = 'welcome';
  |
  | This route indicates which controller class should be loaded if the
  | URI contains no data. In the above example, the "welcome" class
  | would be loaded.
  |
  |	$route['404_override'] = 'errors/page_missing';
  |
  | This route will tell the Router which controller/method to use if those
  | provided in the URL cannot be matched to a valid route.
  |
  |	$route['translate_uri_dashes'] = FALSE;
  |
  | This is not exactly a route, but allows you to automatically route
  | controller and method names that contain dashes. '-' isn't a valid
  | class or method name character, so it requires translation.
  | When you set this option to TRUE, it will replace ALL dashes in the
  | controller and method URI segments.
  |
  | Examples:	my-controller/index	-> my_controller/index
  |		my-controller/my-method	-> my_controller/my_method
 */
$route['default_controller'] = 'welcome/index';
$route['user/resetpassword/([a-z]+)/(:any)'] = 'site/resetpassword/$1/$2';
$route['admin/resetpassword/(:any)'] = 'site/admin_resetpassword/$1';
$route['admin/unauthorized'] = 'admin/admin/unauthorized';
$route['parent/unauthorized'] = 'parent/parents/unauthorized';
$route['student/unauthorized'] = 'user/user/unauthorized';
$route['teacher/unauthorized'] = 'teacher/teacher/unauthorized';
$route['accountant/unauthorized'] = 'accountant/accountant/unauthorized';
$route['librarian/unauthorized'] = 'librarian/librarian/unauthorized';
//$route['404_override'] = '';
$route['404_override'] = 'school/show_404';
$route['translate_uri_dashes'] = FALSE;

//======= front url rewriting==========
$route['page/(:any)'] = 'welcome/page/$1';
$route['read/(:any)'] = 'welcome/read/$1';
$route['frontend'] = 'welcome';

$route['user/api/login'] = 'api/user/auth/login';
$route['user/api/attendance'] = 'api/user/attendence/index';
$route['user/api/subjects'] = 'api/user/subject/index';
$route['user/api/profile'] = 'api/user/profile/index';
$route['user/api/fees'] = 'api/user/fees/index';
$route['user/api/timetable'] = 'api/user/timetable/index';
$route['user/api/books'] = 'api/user/book/index';
$route['user/api/my-books'] = 'api/user/book/my_books';
$route['user/api/calendar/events'] = 'api/user/calendar/events';
$route['user/api/calendar/tasks'] = 'api/user/calendar/tasks';
$route['user/api/calendar/task'] = 'api/user/calendar/task';
$route['user/api/calendar/add-task'] = 'api/user/calendar/add_task';
$route['user/api/calendar/complete-task'] = 'api/user/calendar/complete_task';
$route['user/api/calendar/delete-task'] = 'api/user/calendar/delete_task';
$route['user/api/homework'] = 'api/user/homework/index';
$route['user/api/homework/detail'] = 'api/user/homework/detail';
$route['user/api/homework/download'] = 'api/user/homework/download';
$route['user/api/teachers'] = 'api/user/teacher/index';
$route['user/api/exams'] = 'api/user/exam/index';
$route['user/api/marks'] = 'api/user/mark/index';
$route['user/api/notifications'] = 'api/user/notification/index';
$route['user/api/notifications/status'] = 'api/user/notification/updateStatus';
$route['user/api/timeline'] = 'api/user/timeline/add';
$route['user/api/route'] = 'api/user/route/index';
$route['user/api/route/bus-detail'] = 'api/user/route/getbusdetail';
$route['user/api/hostel'] = 'api/user/hostel/index';
$route['user/api/hostel/rooms'] = 'api/user/hostel/rooms';
$route['user/api/hostel/assigned-room'] = 'api/user/hostel/assignedRoom';