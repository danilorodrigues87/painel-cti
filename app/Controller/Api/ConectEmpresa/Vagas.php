<?php

namespace App\Controller\Api\ConectEmpresa;

use App\Common\Helpers\ConectApiMapper;
use App\Model\Entity\CjEmpresa;
use App\Model\Entity\CjVaga;
use App\Model\Db\Database;

class Vagas {

	private static function respond(array $body, int $code = 200): array {
		return [
			'code' => $code,
			'json' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
		];
	}

	public static function listar($request): array {
		$empresa = $request->empresa ?? null;
		if (!$empresa instanceof CjEmpresa) {
			return self::respond(['message' => 'Não autenticado.'], 401);
		}
		if (!CjVaga::tabelaExiste()) {
			return self::respond(['items' => [], 'sqlOk' => false]);
		}
		$rows = CjVaga::queryLista([
			'empresa_id_internal' => (int)$empresa->id,
			'status_any'          => true,
			'limit'               => 100,
		]);
		$items = array_map([ConectApiMapper::class, 'vaga'], $rows);
		return self::respond(['items' => $items, 'sqlOk' => true]);
	}

	public static function criar($request): array {
		$empresa = $request->empresa ?? null;
		if (!$empresa instanceof CjEmpresa) {
			return self::respond(['message' => 'Não autenticado.'], 401);
		}
		if (($empresa->status ?? '') !== 'aprovada') {
			return self::respond(['message' => 'Empresa aguardando aprovação para publicar vagas.'], 403);
		}
		if (!CjVaga::tabelaExiste()) {
			return self::respond(['message' => 'Módulo não instalado.'], 503);
		}

		$post = $request->getPostVars() ?: [];
		$titulo = trim((string)($post['titulo'] ?? ''));
		$descricao = trim((string)($post['descricao'] ?? ''));
		if ($titulo === '' || $descricao === '') {
			return self::respond(['message' => 'Título e descrição são obrigatórios.'], 400);
		}

		$tipo = (string)($post['tipoVaga'] ?? $post['tipo_vaga'] ?? 'clt');
		if (!in_array($tipo, ['aprendiz', 'estagio', 'clt', 'freelance'], true)) {
			$tipo = 'clt';
		}
		$modalidade = (string)($post['modalidade'] ?? 'presencial');
		if (!in_array($modalidade, ['presencial', 'hibrido', 'remoto'], true)) {
			$modalidade = 'presencial';
		}

		$slugBase = ConectApiMapper::slugify($titulo);
		$slug = CjVaga::slugUnico($slugBase);
		$cidadeId = (int)($post['cidadeId'] ?? $post['cidade_id'] ?? 0);

		$id = (new Database('cj_vagas'))->insert([
			'id_empresa'  => (int)$empresa->id,
			'titulo'      => mb_substr($titulo, 0, 191),
			'slug'        => $slug,
			'tipo_vaga'   => $tipo,
			'descricao'   => $descricao,
			'requisitos'  => trim((string)($post['requisitos'] ?? '')),
			'cidade_id'   => $cidadeId > 0 ? $cidadeId : ($empresa->cidade_id ?: null),
			'uf'          => (string)($post['uf'] ?? $empresa->uf ?? ''),
			'modalidade'  => $modalidade,
			'status'      => 'pendente',
		]);

		if (!$id) {
			return self::respond(['message' => 'Erro ao salvar vaga.'], 500);
		}

		$row = CjVaga::getById((int)$id);
		return self::respond([
			'message' => 'Vaga enviada para moderação.',
			'vaga'    => $row ? ConectApiMapper::vaga($row) : null,
		], 201);
	}

	public static function enviarModeracao($request, int $vagaId): array {
		$empresa = $request->empresa ?? null;
		if (!$empresa instanceof CjEmpresa || $vagaId <= 0) {
			return self::respond(['message' => 'Inválido.'], 400);
		}
		$row = CjVaga::getById($vagaId);
		if (!$row || (int)$row['id_empresa'] !== (int)$empresa->id) {
			return self::respond(['message' => 'Vaga não encontrada.'], 404);
		}
		(new Database('cj_vagas'))->update('id = '.(int)$vagaId, ['status' => 'pendente']);
		return self::respond(['message' => 'Vaga enviada para moderação.']);
	}
}
