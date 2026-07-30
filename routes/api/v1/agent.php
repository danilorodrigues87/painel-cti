<?php

use App\Http\Response;
use App\Controller\Api\Agent;

$agentMw = ['api', 'agent-api-key'];

$respond = static function (array $res) {
	return new Response($res['code'] ?? 200, $res['json'] ?? '{}', 'application/json');
};

// Health / whoami
$obRouter->get('/api/v1/agent/health', [
	'middlewares' => $agentMw,
	function ($request) use ($respond) {
		return $respond(Agent\Analytics::health($request));
	}
]);

// Master: listar escolas
$obRouter->get('/api/v1/agent/escolas', [
	'middlewares' => $agentMw,
	function ($request) use ($respond) {
		return $respond(Agent\Analytics::escolas($request));
	}
]);

// Resumo (escola: sem id; master: ?id_admin= ou /escolas/{id}/...)
$obRouter->get('/api/v1/agent/resumo', [
	'middlewares' => $agentMw,
	function ($request) use ($respond) {
		return $respond(Agent\Analytics::resumo($request));
	}
]);

$obRouter->get('/api/v1/agent/escolas/{idAdmin}/resumo', [
	'middlewares' => $agentMw,
	function ($request, $idAdmin) use ($respond) {
		return $respond(Agent\Analytics::resumo($request, $idAdmin));
	}
]);

$obRouter->get('/api/v1/agent/agenda/hoje', [
	'middlewares' => $agentMw,
	function ($request) use ($respond) {
		return $respond(Agent\Analytics::agendaHoje($request));
	}
]);

$obRouter->get('/api/v1/agent/escolas/{idAdmin}/agenda/hoje', [
	'middlewares' => $agentMw,
	function ($request, $idAdmin) use ($respond) {
		return $respond(Agent\Analytics::agendaHoje($request, $idAdmin));
	}
]);

$obRouter->get('/api/v1/agent/financeiro/inadimplentes', [
	'middlewares' => $agentMw,
	function ($request) use ($respond) {
		return $respond(Agent\Analytics::inadimplentes($request));
	}
]);

$obRouter->get('/api/v1/agent/escolas/{idAdmin}/financeiro/inadimplentes', [
	'middlewares' => $agentMw,
	function ($request, $idAdmin) use ($respond) {
		return $respond(Agent\Analytics::inadimplentes($request, $idAdmin));
	}
]);

$obRouter->get('/api/v1/agent/financeiro/a-receber', [
	'middlewares' => $agentMw,
	function ($request) use ($respond) {
		return $respond(Agent\Analytics::aReceber($request));
	}
]);

$obRouter->get('/api/v1/agent/escolas/{idAdmin}/financeiro/a-receber', [
	'middlewares' => $agentMw,
	function ($request, $idAdmin) use ($respond) {
		return $respond(Agent\Analytics::aReceber($request, $idAdmin));
	}
]);

$obRouter->get('/api/v1/agent/crm/resumo', [
	'middlewares' => $agentMw,
	function ($request) use ($respond) {
		return $respond(Agent\Analytics::crm($request));
	}
]);

$obRouter->get('/api/v1/agent/escolas/{idAdmin}/crm/resumo', [
	'middlewares' => $agentMw,
	function ($request, $idAdmin) use ($respond) {
		return $respond(Agent\Analytics::crm($request, $idAdmin));
	}
]);

$obRouter->get('/api/v1/agent/matriculas/resumo', [
	'middlewares' => $agentMw,
	function ($request) use ($respond) {
		return $respond(Agent\Analytics::matriculas($request));
	}
]);

$obRouter->get('/api/v1/agent/escolas/{idAdmin}/matriculas/resumo', [
	'middlewares' => $agentMw,
	function ($request, $idAdmin) use ($respond) {
		return $respond(Agent\Analytics::matriculas($request, $idAdmin));
	}
]);

$obRouter->get('/api/v1/agent/whatsapp/fila', [
	'middlewares' => $agentMw,
	function ($request) use ($respond) {
		return $respond(Agent\Analytics::whatsapp($request));
	}
]);

$obRouter->get('/api/v1/agent/escolas/{idAdmin}/whatsapp/fila', [
	'middlewares' => $agentMw,
	function ($request, $idAdmin) use ($respond) {
		return $respond(Agent\Analytics::whatsapp($request, $idAdmin));
	}
]);
