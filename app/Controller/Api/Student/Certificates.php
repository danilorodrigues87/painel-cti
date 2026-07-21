<?php

namespace App\Controller\Api\Student;

use App\Model\Entity\Certificados;
use App\Model\Entity\Trilhas;

class Certificates {

	private static function ok($data, int $code = 200): array {
		return ['code' => $code, 'json' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)];
	}

	public static function list($request) {
		$u = $request->user;
		$stmt = Certificados::getCertificados(
			'id_aluno = '.(int)$u->id.' AND id_admin = '.(int)$u->id_admin,
			'id DESC'
		);
		$out = [];
		while ($c = $stmt->fetchObject(Certificados::class)) {
			$trilha = Trilhas::getTrilhaById((int)$c->id_trilha);
			$out[] = [
				'id' => (string)$c->id,
				'courseId' => (string)$c->id_trilha,
				'courseTitle' => $trilha ? (string)$trilha->nome : 'Curso',
				'issuedAt' => $c->conclusao ? date('c', strtotime($c->conclusao)) : date('c'),
				'workloadHours' => (int)$c->carga_h,
				'pdfUrl' => null,
			];
		}
		return self::ok($out);
	}
}
