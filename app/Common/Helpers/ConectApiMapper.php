<?php

namespace App\Common\Helpers;

class ConectApiMapper {

	public static function tokens(string $accessToken, int $expiresIn = 86400): array {
		return [
			'accessToken'  => $accessToken,
			'refreshToken' => $accessToken,
			'expiresIn'    => $expiresIn,
		];
	}

	public static function userCandidato($user, $candidato): array {
		return [
			'id'          => (int)($user->id ?? 0),
			'nome'        => (string)($user->nome ?? ''),
			'email'       => (string)($user->email ?? ''),
			'nivel'       => 'Candidato',
			'candidatoId' => (int)($candidato->id ?? 0),
			'idAdmin'     => (int)($candidato->id_admin ?? 0),
			'tipo'        => (string)($candidato->tipo ?? 'externo'),
		];
	}

	/**
	 * @param object|array<string,mixed> $candidato
	 * @param list<string> $habilidades
	 * @param list<array<string,mixed>> $formacao
	 */
	public static function candidatoPerfil($candidato, array $habilidades = [], array $formacao = [], bool $temSelo = false): array {
		$c = is_array($candidato) ? $candidato : (array)$candidato;
		$fotoBasename = $c['foto'] ?? null;
		$fotoUrl = null;
		if (!empty($fotoBasename)) {
			$fotoUrl = UserFotoHelper::urlPublica((string)$fotoBasename);
		}
		return [
			'id'              => (int)($c['id'] ?? 0),
			'nome'            => (string)($c['nome'] ?? ''),
			'email'           => (string)($c['email'] ?? ''),
			'whatsapp'        => (string)($c['whatsapp'] ?? ''),
			'resumo'          => (string)($c['resumo'] ?? ''),
			'cidadeId'        => isset($c['cidade_id']) && (int)$c['cidade_id'] > 0 ? (int)$c['cidade_id'] : null,
			'bairro'          => (string)($c['bairro'] ?? ''),
			'uf'              => (string)($c['uf'] ?? ''),
			'disponibilidade' => (string)($c['disponibilidade'] ?? 'imediata'),
			'tipo'            => (string)($c['tipo'] ?? 'externo'),
			'fotoUrl'         => $fotoUrl,
			'habilidades'     => $habilidades,
			'formacao'        => $formacao,
			'temSeloCertificado' => $temSelo,
		];
	}

	/**
	 * @param array<string,mixed>|object $row
	 */
	public static function formacao($row): array {
		$r = is_array($row) ? $row : (array)$row;
		return [
			'id'              => (int)($r['id'] ?? 0),
			'titulo'          => (string)($r['titulo'] ?? ''),
			'origem'          => (string)($r['origem'] ?? 'manual'),
			'status'          => (string)($r['status'] ?? 'em_andamento'),
			'cargaH'          => isset($r['carga_h']) ? (int)$r['carga_h'] : null,
			'seloCertificado' => !empty($r['selo_certificado']),
			'concluidoEm'     => (string)($r['concluido_em'] ?? ''),
		];
	}

	/**
	 * @param array<string,mixed>|object $row
	 */
	public static function candidatura($row): array {
		$r = is_array($row) ? $row : (array)$row;
		return [
			'id'               => (int)($r['id'] ?? 0),
			'vagaId'           => (int)($r['id_vaga'] ?? 0),
			'vagaTitulo'       => (string)($r['vaga_titulo'] ?? ''),
			'vagaSlug'         => (string)($r['vaga_slug'] ?? ''),
			'tipoVaga'         => (string)($r['tipo_vaga'] ?? ''),
			'empresaNome'      => (string)($r['empresa_nome'] ?? ''),
			'status'           => (string)($r['status'] ?? 'enviada'),
			'mensagemCandidato'=> (string)($r['mensagem_candidato'] ?? ''),
			'createdAt'        => (string)($r['created_at'] ?? ''),
		];
	}

	/**
	 * Candidatura enriquecida para a área da empresa.
	 * @param array<string,mixed>|object $row
	 */
	public static function candidaturaEmpresa($row): array {
		$r = is_array($row) ? $row : (array)$row;
		$base = self::candidatura($row);
		return array_merge($base, [
			'candidatoId'             => (int)($r['id_candidato'] ?? 0),
			'candidatoNome'           => (string)($r['candidato_nome'] ?? ''),
			'candidatoEmail'          => (string)($r['candidato_email'] ?? ''),
			'candidatoWhatsapp'       => (string)($r['candidato_whatsapp'] ?? ''),
			'candidatoResumo'         => (string)($r['candidato_resumo'] ?? ''),
			'candidatoDisponibilidade'=> (string)($r['candidato_disponibilidade'] ?? ''),
			'candidatoTipo'           => (string)($r['candidato_tipo'] ?? ''),
			'mensagemEmpresa'         => (string)($r['mensagem_empresa'] ?? ''),
		]);
	}

	public static function empresaPerfil($empresa): array {
		$e = is_array($empresa) ? $empresa : (array)$empresa;
		return [
			'id'           => (int)($e['id'] ?? 0),
			'cnpj'         => (string)($e['cnpj'] ?? ''),
			'razaoSocial'  => (string)($e['razao_social'] ?? ''),
			'nomeFantasia' => (string)($e['nome_fantasia'] ?? ''),
			'logoUrl'      => BrandingHelper::urlConectEmpresaLogo($e['logo'] ?? null),
			'whatsapp'     => (string)($e['whatsapp'] ?? ''),
			'email'        => (string)($e['email'] ?? ''),
			'contatoNome'  => (string)($e['contato_nome'] ?? ''),
			'cidadeId'     => isset($e['cidade_id']) && (int)$e['cidade_id'] > 0 ? (int)$e['cidade_id'] : null,
			'bairro'       => (string)($e['bairro'] ?? ''),
			'uf'           => (string)($e['uf'] ?? ''),
			'status'       => (string)($e['status'] ?? 'pendente'),
		];
	}

	/**
	 * @param array<string,mixed>|object $row
	 */
	public static function notificacao($row): array {
		$r = is_array($row) ? $row : (array)$row;
		return [
			'id'        => (int)($r['id'] ?? 0),
			'tipo'      => (string)($r['tipo'] ?? 'system'),
			'titulo'    => (string)($r['titulo'] ?? ''),
			'mensagem'  => (string)($r['mensagem'] ?? ''),
			'link'      => !empty($r['link']) ? (string)$r['link'] : null,
			'lida'      => !empty($r['lido_em']),
			'createdAt' => (string)($r['created_at'] ?? ''),
		];
	}

	public static function userEmpresa($user, $empresa): array {
		return [
			'id'        => (int)($user->id ?? 0),
			'nome'      => (string)($user->nome ?? ''),
			'email'     => (string)($user->email ?? ''),
			'nivel'     => 'Empresa',
			'empresaId' => (int)($empresa->id ?? 0),
			'status'    => (string)($empresa->status ?? 'pendente'),
		];
	}

	/**
	 * @param array<string,mixed>|object $row
	 */
	public static function vaga($row): array {
		$r = is_array($row) ? $row : (array)$row;
		return [
			'id'          => (int)($r['id'] ?? 0),
			'slug'        => (string)($r['slug'] ?? ''),
			'titulo'      => (string)($r['titulo'] ?? ''),
			'tipoVaga'    => (string)($r['tipo_vaga'] ?? ''),
			'descricao'   => (string)($r['descricao'] ?? ''),
			'requisitos'  => (string)($r['requisitos'] ?? ''),
			'cidadeId'    => isset($r['cidade_id']) ? (int)$r['cidade_id'] : null,
			'cidadeNome'  => (string)($r['cidade_nome'] ?? ''),
			'bairro'      => (string)($r['bairro'] ?? ''),
			'uf'          => (string)($r['uf'] ?? ''),
			'modalidade'  => (string)($r['modalidade'] ?? ''),
			'empresaId'   => (int)($r['id_empresa'] ?? 0),
			'empresaNome' => (string)($r['empresa_nome'] ?? ''),
			'empresaLogoUrl' => BrandingHelper::urlConectEmpresaLogo($r['empresa_logo'] ?? null),
			'status'      => (string)($r['status'] ?? ''),
			'publicadaEm' => (string)($r['publicada_em'] ?? ''),
			'viewsCount'  => (int)($r['views_count'] ?? 0),
		];
	}

	public static function empresa($row): array {
		$r = is_array($row) ? $row : (array)$row;
		return [
			'id'           => (int)($r['id'] ?? 0),
			'nomeFantasia' => (string)($r['nome_fantasia'] ?? $r['razao_social'] ?? ''),
			'razaoSocial'  => (string)($r['razao_social'] ?? ''),
			'logoUrl'      => BrandingHelper::urlConectEmpresaLogo($r['logo'] ?? null),
			'cidadeId'     => isset($r['cidade_id']) ? (int)$r['cidade_id'] : null,
			'cidadeNome'   => (string)($r['cidade_nome'] ?? ''),
			'uf'           => (string)($r['uf'] ?? ''),
		];
	}

	public static function branding($row): array {
		$r = is_array($row) ? $row : (array)$row;
		$cores = [];
		if (!empty($r['cores_json'])) {
			$decoded = json_decode((string)$r['cores_json'], true);
			if (is_array($decoded)) {
				$cores = $decoded;
			}
		}
		return [
			'nomePortal'         => (string)($r['nome_portal'] ?? 'Conecta Jovem'),
			'logoUrl'            => BrandingHelper::urlConectLogo($r['logo'] ?? null),
			'heroImageUrl'       => BrandingHelper::urlConectHero($r['hero_image'] ?? null),
			'textoInstitucional' => (string)($r['texto_institucional'] ?? ''),
			'cores'              => $cores,
		];
	}

	private static function absUrl(string $path): string {
		if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
			return $path;
		}
		return rtrim((string)URL, '/').'/'.ltrim($path, '/');
	}

	public static function slugify(string $text): string {
		$text = mb_strtolower(trim($text));
		$text = preg_replace('/[^\pL\d]+/u', '-', $text) ?? '';
		$text = trim($text, '-');
		if ($text === '') {
			$text = 'vaga';
		}
		return mb_substr($text, 0, 200);
	}
}
