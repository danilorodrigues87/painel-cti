<?php

namespace App\Common\Helpers;

use App\Common\Upload;

/**
 * Identidade visual CTI (UI) vs logo da escola (somente impressos).
 */
class BrandingHelper {

	public const LOGO_CTI = 'resources/assets/img/icons/logo-2.png';
	public const ICONE_CTI = 'resources/assets/img/icons/icone.png';
	public const DIR_ESCOLAS = '/img/escolas/';
	public const DIR_MODELO_CERT = '/img/certificado/modelos/';
	public const MODELO_CERT_PADRAO = 'uploads/img/certificado/modelo_cert.png';

	public static function urlBase(): string {
		return rtrim((string)URL, '/');
	}

	public static function urlLogoCti(): string {
		return self::urlBase().'/'.self::LOGO_CTI;
	}

	public static function urlFaviconCti(): string {
		return self::urlBase().'/'.self::ICONE_CTI;
	}

	public static function urlModeloCertPadrao(): string {
		return self::urlBase().'/'.self::MODELO_CERT_PADRAO;
	}

	/**
	 * Logo da escola para contrato e recibo.
	 * Aceita basename em uploads/img/escolas/ ou legado em icons/.
	 * Sem logo válida, usa a logo CTI.
	 */
	public static function urlLogoEscola(?string $logo): string {
		$logo = trim((string)$logo);
		if ($logo === '' || strpos($logo, '..') !== false || strpos($logo, '/') !== false || strpos($logo, '\\') !== false) {
			return self::urlLogoCti();
		}

		$raiz = realpath(__DIR__.'/../../../');
		if ($raiz === false) {
			return self::urlLogoCti();
		}

		$uploadFs = $raiz.DIRECTORY_SEPARATOR.'uploads'.str_replace('/', DIRECTORY_SEPARATOR, self::DIR_ESCOLAS).$logo;
		if (is_file($uploadFs)) {
			return self::urlBase().'/uploads'.self::DIR_ESCOLAS.$logo;
		}

		$iconsFs = $raiz.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'img'.DIRECTORY_SEPARATOR.'icons'.DIRECTORY_SEPARATOR.$logo;
		if (is_file($iconsFs)) {
			return self::urlBase().'/resources/assets/img/icons/'.$logo;
		}

		return self::urlLogoCti();
	}

	/**
	 * Fundo do certificado da escola (imagem A4 paisagem já com logo).
	 * Sem modelo da escola, usa o padrão global.
	 */
	public static function urlModeloCertificado(?string $arquivo): string {
		$arquivo = trim((string)$arquivo);
		if ($arquivo === '' || strpos($arquivo, '..') !== false || strpos($arquivo, '/') !== false || strpos($arquivo, '\\') !== false) {
			return self::urlModeloCertPadrao();
		}

		$raiz = realpath(__DIR__.'/../../../');
		if ($raiz === false) {
			return self::urlModeloCertPadrao();
		}

		$fs = $raiz.DIRECTORY_SEPARATOR.'uploads'.str_replace('/', DIRECTORY_SEPARATOR, self::DIR_MODELO_CERT).$arquivo;
		if (is_file($fs)) {
			return self::urlBase().'/uploads'.self::DIR_MODELO_CERT.$arquivo;
		}

		return self::urlModeloCertPadrao();
	}

	/**
	 * Processa upload da logo da escola. Retorna basename novo ou o atual.
	 */
	public static function processarUploadLogo(?array $file, ?string $logoAtual = null): ?string {
		return self::processarUploadImagem($file, self::DIR_ESCOLAS, $logoAtual, 5 * 1024 * 1024);
	}

	/**
	 * Processa upload do modelo de certificado (PNG/JPG, até 8 MB).
	 */
	public static function processarUploadModeloCertificado(?array $file, ?string $atual = null): ?string {
		return self::processarUploadImagem($file, self::DIR_MODELO_CERT, $atual, 8 * 1024 * 1024);
	}

	private static function processarUploadImagem(?array $file, string $dirRelativo, ?string $atual, int $maxBytes): ?string {
		$atual = trim((string)$atual);
		if ($atual === '') {
			$atual = null;
		}

		if (!is_array($file) || empty($file['name']) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			return $atual;
		}

		$type = strtolower((string)($file['type'] ?? ''));
		if (strpos($type, 'image/') !== 0) {
			return $atual;
		}

		$size = (int)($file['size'] ?? 0);
		if ($size <= 0 || $size > $maxBytes) {
			return $atual;
		}

		$obUpload = new Upload($file);
		$obUpload->generateNewName();
		$ok = $obUpload->upload($dirRelativo, false, $atual);
		if (!$ok) {
			return $atual;
		}

		return $obUpload->getBasename();
	}

	/** HTML do rodapé padrão (CTI + XDTEC). */
	public static function footerHtml(): string {
		return '<footer class="py-4 bg-dark text-white mt-auto">'
			.'<div class="container-fluid px-4">'
			.'<div class="small text-muted d-flex flex-wrap gap-2 justify-content-between align-items-center">'
			.'<span>&copy; <a class="text-muted text-decoration-none" target="_blank" rel="noopener noreferrer" href="https://ctieducacional.com.br">Centro de Tecnologia e Inovação Educacional</a></span>'
			.'<span>Desenvolvido por <a class="text-muted text-decoration-none" target="_blank" rel="noopener noreferrer" href="https://xdtec.com.br">XDTEC</a></span>'
			.'</div></div></footer>';
	}
}
