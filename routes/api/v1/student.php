<?php

use App\Http\Response;
use App\Controller\Api\Student;

$studentMw = ['cors-student', 'api'];
$studentAuth = ['cors-student', 'api', 'student-jwt'];

$respond = static function (array $res) {
	$contentType = $res['contentType'] ?? 'application/json';
	return new Response($res['code'] ?? 200, $res['json'] ?? '{}', $contentType);
};

// Auth (sem JWT)
$obRouter->post('/api/v1/student/auth/login', [
	'middlewares' => $studentMw,
	function ($request) use ($respond) {
		return $respond(Student\Auth::login($request));
	}
]);

$obRouter->post('/api/v1/student/auth/forgot-password', [
	'middlewares' => $studentMw,
	function ($request) use ($respond) {
		return $respond(Student\Auth::forgotPassword($request));
	}
]);

$obRouter->post('/api/v1/student/auth/reset-password', [
	'middlewares' => $studentMw,
	function ($request) use ($respond) {
		return $respond(Student\Auth::resetPassword($request));
	}
]);

// Preflight CORS — {path+} cobre /auth/login, /courses/1/lessons/2, etc.
$obRouter->options('/api/v1/student/{path+}', [
	'middlewares' => ['cors-student'],
	function () {
		return new Response(204, '', 'application/json');
	}
]);

// Me / dashboard / courses
$obRouter->get('/api/v1/student/me', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Portal::me($request));
	}
]);

$obRouter->post('/api/v1/student/me/avatar', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Portal::updateAvatar($request));
	}
]);

$obRouter->post('/api/v1/student/me/password', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Portal::changePassword($request));
	}
]);

$obRouter->get('/api/v1/student/me/access-window', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Portal::accessWindow($request));
	}
]);

$obRouter->get('/api/v1/student/dashboard', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Portal::dashboard($request));
	}
]);

$obRouter->get('/api/v1/student/ranking', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Portal::ranking($request));
	}
]);

$obRouter->get('/api/v1/student/achievements', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Portal::achievements($request));
	}
]);

$obRouter->get('/api/v1/student/referral/status', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Referral::status($request));
	}
]);

$obRouter->post('/api/v1/student/referral', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Referral::submit($request));
	}
]);

$obRouter->get('/api/v1/student/notifications', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Notifications::list($request));
	}
]);

$obRouter->get('/api/v1/student/finance', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Finance::summary($request));
	}
]);

$obRouter->post('/api/v1/student/notifications/mark-all-read', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Notifications::markAllRead($request));
	}
]);

$obRouter->post('/api/v1/student/notifications/{id}/read', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Notifications::markRead($request, $id));
	}
]);

$obRouter->get('/api/v1/student/courses', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Portal::listCourses($request));
	}
]);

$obRouter->get('/api/v1/student/courses/{id}', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Portal::getCourse($request, $id));
	}
]);

$obRouter->post('/api/v1/student/courses/{id}/rating', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Portal::rateCourse($request, $id));
	}
]);

$obRouter->get('/api/v1/student/courses/{courseId}/lessons/{lessonId}', [
	'middlewares' => $studentAuth,
	function ($request, $courseId, $lessonId) use ($respond) {
		return $respond(Student\Portal::getLesson($request, $courseId, $lessonId));
	}
]);

$obRouter->post('/api/v1/student/courses/{courseId}/lessons/{lessonId}/complete', [
	'middlewares' => $studentAuth,
	function ($request, $courseId, $lessonId) use ($respond) {
		return $respond(Student\Portal::completeLesson($request, $courseId, $lessonId));
	}
]);

$obRouter->post('/api/v1/student/study/heartbeat', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Portal::studyHeartbeat($request));
	}
]);

$obRouter->get('/api/v1/student/courses/{courseId}/lessons/{lessonId}/comments', [
	'middlewares' => $studentAuth,
	function ($request, $courseId, $lessonId) use ($respond) {
		return $respond(Student\Portal::listComments($request, $courseId, $lessonId));
	}
]);

$obRouter->post('/api/v1/student/courses/{courseId}/lessons/{lessonId}/comments', [
	'middlewares' => $studentAuth,
	function ($request, $courseId, $lessonId) use ($respond) {
		return $respond(Student\Portal::postComment($request, $courseId, $lessonId));
	}
]);

$obRouter->post('/api/v1/student/courses/{courseId}/lessons/{lessonId}/comments/{commentId}/delete', [
	'middlewares' => $studentAuth,
	function ($request, $courseId, $lessonId, $commentId) use ($respond) {
		return $respond(Student\Portal::deleteComment($request, $courseId, $lessonId, $commentId));
	}
]);

// Assessments
$obRouter->get('/api/v1/student/assessments', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Assessments::list($request));
	}
]);

$obRouter->get('/api/v1/student/assessments/{id}', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Assessments::get($request, $id));
	}
]);

$obRouter->post('/api/v1/student/assessments/{id}/submit', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Assessments::submit($request, $id));
	}
]);

$obRouter->post('/api/v1/student/assessments/{id}/start', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Assessments::start($request, $id));
	}
]);

$obRouter->post('/api/v1/student/assessments/{id}/answer', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Assessments::answer($request, $id));
	}
]);

$obRouter->post('/api/v1/student/assessments/{id}/finalize', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Assessments::finalize($request, $id));
	}
]);

// Roleplay
$obRouter->get('/api/v1/student/roleplay/scenarios', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Roleplay::listScenarios($request));
	}
]);

$obRouter->get('/api/v1/student/roleplay/scenarios/{id}', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Roleplay::getScenario($request, $id));
	}
]);

$obRouter->post('/api/v1/student/roleplay/simulations', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Roleplay::start($request));
	}
]);

$obRouter->get('/api/v1/student/roleplay/simulations/{id}', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Roleplay::getSimulation($request, $id));
	}
]);

$obRouter->post('/api/v1/student/roleplay/simulations/{id}/messages', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Roleplay::sendMessage($request, $id));
	}
]);

$obRouter->post('/api/v1/student/roleplay/simulations/{id}/finish', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Roleplay::finish($request, $id));
	}
]);

$obRouter->get('/api/v1/student/roleplay/history', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Roleplay::history($request));
	}
]);

// AI tutor
$obRouter->get('/api/v1/student/ai/conversations', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\AiTutor::list($request));
	}
]);

$obRouter->post('/api/v1/student/ai/conversations', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\AiTutor::create($request));
	}
]);

$obRouter->post('/api/v1/student/ai/conversations/{id}/messages', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\AiTutor::sendMessage($request, $id));
	}
]);

// Certificates
$obRouter->get('/api/v1/student/certificates', [
	'middlewares' => $studentAuth,
	function ($request) use ($respond) {
		return $respond(Student\Certificates::list($request));
	}
]);

$obRouter->get('/api/v1/student/certificates/{id}/html', [
	'middlewares' => $studentAuth,
	function ($request, $id) use ($respond) {
		return $respond(Student\Certificates::html($request, $id));
	}
]);
