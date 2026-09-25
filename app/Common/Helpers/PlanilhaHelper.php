<?php

namespace App\Common\Helpers;

class PlanilhaHelper {

	/** Máximo de linhas de contato em campanhas por planilha. */
	public const LIMITE_LINHAS_CAMPANHA = 3000;

	public static function lerArquivo($caminho, $extensao){

		$extensao = strtolower(trim($extensao));

		if($extensao === 'csv'){
			return self::lerCsv($caminho);
		}

		if($extensao === 'xlsx'){
			return self::lerXlsx($caminho);
		}

		throw new \InvalidArgumentException('Formato de arquivo não suportado.');
	}

	private static function lerCsv($caminho){

		$linhas = [];
		$handle = fopen($caminho, 'r');

		if($handle === false){
			throw new \RuntimeException('Não foi possível ler o arquivo CSV.');
		}

		$primeiraLinha = fgets($handle);
		if($primeiraLinha === false){
			fclose($handle);
			return [];
		}

		$primeiraLinha = self::removerBom($primeiraLinha);
		$delimitador = self::detectarDelimitador($primeiraLinha);

		$linhas[] = str_getcsv($primeiraLinha, $delimitador);

		while(($dados = fgetcsv($handle, 0, $delimitador)) !== false){
			if(self::linhaVazia($dados)){
				continue;
			}
			$linhas[] = $dados;
		}

		fclose($handle);

		return $linhas;
	}

	private static function lerXlsx($caminho){

		if(!class_exists('ZipArchive')){
			throw new \RuntimeException('Extensão ZipArchive não está habilitada no PHP.');
		}

		$zip = new \ZipArchive();

		if($zip->open($caminho) !== true){
			throw new \RuntimeException('Arquivo XLSX inválido ou corrompido.');
		}

		$sharedStrings = self::lerSharedStrings($zip);
		$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

		if($sheetXml === false){
			$zip->close();
			throw new \RuntimeException('Planilha vazia ou sem aba principal.');
		}

		$zip->close();

		$xml = simplexml_load_string($sheetXml);

		if($xml === false || !isset($xml->sheetData->row)){
			return [];
		}

		$linhas = [];

		foreach($xml->sheetData->row as $row){

			$celulas = [];

			foreach($row->c as $cell){
				$referencia = (string)$cell['r'];
				$coluna     = preg_replace('/[0-9]+/', '', $referencia);
				$indice     = self::colunaParaIndice($coluna);
				$valor      = self::extrairValorCelula($cell, $sharedStrings);
				$celulas[$indice] = $valor;
			}

			if(count($celulas) === 0){
				continue;
			}

			$linha = self::normalizarLinha($celulas);

			if(self::linhaVazia($linha)){
				continue;
			}

			$linhas[] = $linha;
		}

		return $linhas;
	}

	private static function lerSharedStrings($zip){

		$sharedStrings = [];
		$sharedXml = $zip->getFromName('xl/sharedStrings.xml');

		if($sharedXml === false){
			return $sharedStrings;
		}

		$xml = simplexml_load_string($sharedXml);

		if($xml === false || !isset($xml->si)){
			return $sharedStrings;
		}

		foreach($xml->si as $si){

			if(isset($si->t)){
				$sharedStrings[] = (string)$si->t;
				continue;
			}

			$texto = '';

			if(isset($si->r)){
				foreach($si->r as $parte){
					$texto .= (string)$parte->t;
				}
			}

			$sharedStrings[] = $texto;
		}

		return $sharedStrings;
	}

	private static function extrairValorCelula($cell, $sharedStrings){

		if(isset($cell['t']) && (string)$cell['t'] === 's'){
			$indice = (int)$cell->v;
			return $sharedStrings[$indice] ?? '';
		}

		if(isset($cell['t']) && (string)$cell['t'] === 'inlineStr'){
			return (string)($cell->is->t ?? '');
		}

		return (string)($cell->v ?? '');
	}

	private static function detectarDelimitador($linha){
		$virgulas   = substr_count($linha, ',');
		$pontoVirgula = substr_count($linha, ';');
		return $pontoVirgula > $virgulas ? ';' : ',';
	}

	private static function removerBom($texto){
		if(strpos($texto, "\xEF\xBB\xBF") === 0){
			return substr($texto, 3);
		}
		return $texto;
	}

	private static function colunaParaIndice($letras){

		$letras = strtoupper($letras);
		$numero = 0;
		$tamanho = strlen($letras);

		for($i = 0; $i < $tamanho; $i++){
			$numero = ($numero * 26) + (ord($letras[$i]) - ord('A') + 1);
		}

		return $numero - 1;
	}

	private static function normalizarLinha($celulas, $totalColunas = 6){

		$linha = [];

		for($i = 0; $i < $totalColunas; $i++){
			$linha[] = trim($celulas[$i] ?? '');
		}

		return $linha;
	}

	private static function linhaVazia($linha){

		foreach($linha as $valor){
			if(trim((string)$valor) !== ''){
				return false;
			}
		}

		return true;
	}

	public static function pareceCabecalho($linha){

		$primeiro = strtolower(trim($linha[0] ?? ''));

		return in_array($primeiro, [
			'nome',
			'name',
			'cliente',
			'lead'
		]);
	}

	/**
	 * Extrai dados de uma linha de planilha (CRM / campanhas).
	 * @return array{nome?:string,whatsapp?:string,email?:?string,curso_interesse?:?string,bairro?:?string,cidade?:?string,erro?:string}
	 */
	public static function extrairLinhaContato(array $linha): array {
		$nome           = trim($linha[0] ?? '');
		$whatsappBruto  = trim($linha[1] ?? '');
		$email          = trim($linha[2] ?? '');
		$cursoInteresse = trim($linha[3] ?? '');
		$bairro         = trim($linha[4] ?? '');
		$cidade         = trim($linha[5] ?? '');

		$whatsapp = preg_replace('/\D/', '', $whatsappBruto);

		if ($nome === '') {
			return ['erro' => 'Nome não informado.'];
		}

		if (strlen($whatsapp) < 10) {
			return ['erro' => 'WhatsApp inválido (mínimo 10 dígitos com DDD).'];
		}

		$email = self::normalizarEmailPlanilha($email);

		if ($email !== '' && strpos($email, '@') === false) {
			if ($cursoInteresse === '') {
				$cursoInteresse = $email;
			}
			$email = '';
		}

		if ($email !== '' && !EmailValidator::isValido($email)) {
			return ['erro' => EmailValidator::mensagemErro($email, false) ?: 'E-mail inválido.'];
		}

		return [
			'nome'            => $nome,
			'whatsapp'        => $whatsapp,
			'email'           => $email !== '' ? $email : null,
			'curso_interesse' => $cursoInteresse !== '' ? $cursoInteresse : null,
			'bairro'          => $bairro !== '' ? $bairro : null,
			'cidade'          => $cidade !== '' ? $cidade : null,
		];
	}

	private static function normalizarEmailPlanilha(string $email): string {
		$email = trim($email);
		if ($email === '') {
			return '';
		}
		$vazio = ['-', '--', 'n/a', 'na', 'sem email', 'sem e-mail', 's/email', 'null', 'nenhum'];
		if (in_array(strtolower($email), $vazio, true)) {
			return '';
		}
		return $email;
	}

	/**
	 * Lê upload de planilha (campo planilha) e retorna contatos válidos para campanha.
	 * @return array{contatos:array<int,array{nome:string,whatsapp:string,email:?string,curso:?string}>,erros:array<int,string>,ignorados:int}
	 */
	public static function contatosDeUpload(?array $arquivo, int $limiteLinhas = self::LIMITE_LINHAS_CAMPANHA): array {
		$vazio = ['contatos' => [], 'erros' => [], 'ignorados' => 0];

		if (!$arquivo || ($arquivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			return array_merge($vazio, ['erros' => ['Selecione um arquivo CSV ou XLSX válido.']]);
		}

		$nomeArquivo = $arquivo['name'] ?? '';
		$extensao = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
		if (!in_array($extensao, ['csv', 'xlsx'], true)) {
			return array_merge($vazio, ['erros' => ['Formato não suportado. Envie arquivos .csv ou .xlsx.']]);
		}

		$tamanhoMaximo = 5 * 1024 * 1024;
		if (($arquivo['size'] ?? 0) > $tamanhoMaximo) {
			return array_merge($vazio, ['erros' => ['Arquivo muito grande. O limite é 5MB.']]);
		}

		try {
			$linhas = self::lerArquivo($arquivo['tmp_name'], $extensao);
		} catch (\Throwable $e) {
			return array_merge($vazio, ['erros' => ['Erro ao ler a planilha: '.$e->getMessage()]]);
		}

		if (count($linhas) === 0) {
			return array_merge($vazio, ['erros' => ['A planilha não contém linhas.']]);
		}

		if (self::pareceCabecalho($linhas[0])) {
			array_shift($linhas);
		}

		if (count($linhas) === 0) {
			return array_merge($vazio, ['erros' => ['A planilha possui apenas o cabeçalho, sem dados.']]);
		}

		if (count($linhas) > $limiteLinhas) {
			return array_merge($vazio, ['erros' => ['Limite de '.$limiteLinhas.' linhas por planilha.']]);
		}

		$contatos = [];
		$erros = [];
		$ignorados = 0;

		foreach ($linhas as $indice => $linha) {
			$numeroLinha = $indice + 2;
			$dados = self::extrairLinhaContato(is_array($linha) ? $linha : []);
			if (!empty($dados['erro'])) {
				$ignorados++;
				$erros[] = 'Linha '.$numeroLinha.': '.$dados['erro'];
				continue;
			}
			$contatos[] = [
				'nome'     => $dados['nome'],
				'whatsapp' => $dados['whatsapp'],
				'email'    => $dados['email'],
				'curso'    => $dados['curso_interesse'] ?? '',
			];
		}

		return [
			'contatos'  => $contatos,
			'erros'     => $erros,
			'ignorados' => $ignorados,
		];
	}

}
