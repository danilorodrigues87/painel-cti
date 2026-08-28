<?php

namespace App\Common\Helpers;

use App\Model\Entity\SaasEmpresaCti;
use App\Model\Entity\User as EntityUser;

class SaasEmpresaCtiHelper {

	public static function formatCnpj(?string $cnpj): string {
		$d = preg_replace('/\D+/', '', (string)($cnpj ?? ''));
		if (strlen($d) !== 14) {
			return trim((string)($cnpj ?? '')) ?: '—';
		}
		return substr($d, 0, 2).'.'.substr($d, 2, 3).'.'.substr($d, 5, 3).'/'
			.substr($d, 8, 4).'-'.substr($d, 12, 2);
	}

	public static function formatCpf(?string $cpf): string {
		$d = preg_replace('/\D+/', '', (string)($cpf ?? ''));
		if (strlen($d) !== 11) {
			return trim((string)($cpf ?? '')) ?: '—';
		}
		return substr($d, 0, 3).'.'.substr($d, 3, 3).'.'.substr($d, 6, 3).'-'.substr($d, 9, 2);
	}

	public static function resolverEndereco(?SaasEmpresaCti $emp): string {
		if (!$emp instanceof SaasEmpresaCti) {
			return 'endereço não informado';
		}
		$cidadeUf = ContratoVariaveisBuilder::resolverCidadeUf(
			(int)($emp->cidade ?? 0),
			(int)($emp->estado ?? 0)
		);
		return ContratoVariaveisBuilder::montarEnderecoEscola([
			'endereco' => $emp->endereco ?? '',
			'numero'   => $emp->numero ?? '',
			'bairro'   => $emp->bairro ?? '',
		], $cidadeUf);
	}

	public static function resolverForo(?SaasEmpresaCti $emp): string {
		if (!$emp instanceof SaasEmpresaCti) {
			return 'comarca da sede da LICENCIANTE';
		}
		$foro = trim((string)($emp->foro_comarca ?? ''));
		if ($foro !== '') {
			return $foro;
		}
		$cidadeUf = ContratoVariaveisBuilder::resolverCidadeUf(
			(int)($emp->cidade ?? 0),
			(int)($emp->estado ?? 0)
		);
		return $cidadeUf !== '' ? $cidadeUf : 'comarca da sede da LICENCIANTE';
	}

	/**
	 * Resolve representante legal CTI a partir de usuarios (FK) ou campos legados.
	 * @return array{nome:string,cpf:string,rg:string,cargo:string,usuario_id:int}|null
	 */
	public static function resolverRepresentanteLegal(?SaasEmpresaCti $emp): ?array {
		if (!$emp instanceof SaasEmpresaCti) {
			return null;
		}

		$cargo = trim((string)($emp->rep_cargo ?? '')) ?: 'Administrador';

		if (SaasEmpresaCti::temColunaRepLegalUsuarioId()) {
			$uid = (int)($emp->rep_legal_usuario_id ?? 0);
			if ($uid > 0) {
				$user = EntityUser::getUserById($uid);
				if ($user instanceof EntityUser) {
					return [
						'nome'        => trim((string)$user->nome),
						'cpf'         => preg_replace('/\D+/', '', (string)($user->cpf ?? '')),
						'rg'          => trim((string)($user->rg ?? '')),
						'cargo'       => $cargo,
						'usuario_id'  => $uid,
					];
				}
			}
		}

		$nome = trim((string)($emp->rep_nome ?? ''));
		if ($nome === '') {
			return null;
		}

		return [
			'nome'       => $nome,
			'cpf'        => preg_replace('/\D+/', '', (string)($emp->rep_cpf ?? '')),
			'rg'         => trim((string)($emp->rep_rg ?? '')),
			'cargo'      => $cargo,
			'usuario_id' => 0,
		];
	}

	/** @return array{ok:bool,faltando:string[]} */
	public static function checarCompleto(?SaasEmpresaCti $emp): array {
		$faltando = [];
		if (!$emp instanceof SaasEmpresaCti) {
			return ['ok' => false, 'faltando' => ['Cadastro da empresa CTI']];
		}
		$cnpj = preg_replace('/\D+/', '', (string)($emp->cnpj ?? ''));
		if (strlen($cnpj) !== 14 || !ConectCnpjHelper::validar($cnpj)) {
			$faltando[] = 'CNPJ da CTI';
		}
		if (trim((string)($emp->endereco ?? '')) === '') {
			$faltando[] = 'Endereço da CTI';
		}

		$rep = self::resolverRepresentanteLegal($emp);
		if (!$rep || trim($rep['nome']) === '') {
			$faltando[] = 'Representante legal CTI (usuário Master)';
		} elseif (strlen($rep['cpf']) !== 11) {
			$faltando[] = 'CPF do representante legal CTI (cadastre no usuário Master)';
		}

		return ['ok' => empty($faltando), 'faltando' => $faltando];
	}

	public static function defaults(): SaasEmpresaCti {
		$e = new SaasEmpresaCti();
		$e->razao_social = 'Centro de Tecnologia e Inovação Educacional';
		$e->nome_fantasia = 'CTI Educacional';
		$e->email = 'contato@ctieducacional.com.br';
		$e->site = 'https://ctieducacional.com.br';
		$e->rep_cargo = 'Administrador';
		return $e;
	}

	public static function getOuDefaults(): SaasEmpresaCti {
		$emp = SaasEmpresaCti::get();
		return $emp instanceof SaasEmpresaCti ? $emp : self::defaults();
	}
}
