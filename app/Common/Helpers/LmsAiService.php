<?php

namespace App\Common\Helpers;

use App\Model\Entity\EscolaIntegracoes;

/**
 * Proxy LLM por escola (OpenAI-compatible / Gemini). Stub se inativo ou API falhar.
 */
class LmsAiService {

	/** Último erro técnico (não expor ao aluno em produção). */
	private static ?string $lastError = null;

	public static function getLastError(): ?string {
		return self::$lastError;
	}

	public static function chat(int $idAdmin, array $messages, string $systemPrompt = ''): string {
		self::$lastError = null;
		$cfg = EscolaIntegracoes::getByIdAdmin($idAdmin);
		if (!$cfg instanceof EscolaIntegracoes || !$cfg->temAiAtivo()) {
			self::$lastError = 'IA inativa ou sem chave.';
			return self::stubReply($messages, false);
		}
		$provider = (string)($cfg->ai_provider ?: 'openai');
		$key = $cfg->getAiApiKeyDescriptografada();
		$model = trim((string)($cfg->ai_model ?: ''));
		if ($model === '') {
			$model = $provider === 'gemini' ? 'gemini-2.0-flash' : 'gpt-4o-mini';
		}

		if ($provider === 'gemini') {
			$text = self::callGemini($key, $model, $messages, $systemPrompt);
			if ($text !== null) {
				return $text;
			}
			// Fallback de modelo antigo → novo
			if (stripos($model, '1.5') !== false) {
				$text = self::callGemini($key, 'gemini-2.0-flash', $messages, $systemPrompt);
				if ($text !== null) {
					return $text;
				}
			}
			return self::stubReply($messages, true);
		}
		$text = self::callOpenAiCompatible($key, $model, $messages, $systemPrompt, null);
		return $text !== null ? $text : self::stubReply($messages, true);
	}

	private static function stubReply(array $messages, bool $configuredButFailed): string {
		$last = '';
		foreach (array_reverse($messages) as $m) {
			if (($m['role'] ?? '') === 'user') {
				$last = (string)($m['content'] ?? '');
				break;
			}
		}
		$snip = mb_substr(trim($last), 0, 80);
		if ($configuredButFailed) {
			$hint = 'A chave de IA está salva, mas a chamada à API falhou'
				.(self::$lastError ? ' ('.self::$lastError.')' : '')
				.'. Verifique o modelo em Configurações → IA Pedagógica (ex.: gemini-2.0-flash ou gpt-4o-mini).';
		} else {
			$hint = 'Configure a IA em Configurações → IA Pedagógica (ativar + chave API).';
		}
		return "Entendi: \"{$snip}\".\n\n(Resposta simulada — {$hint})\n\nPode continuar; estou no personagem do exercício.";
	}

	/** @return string|null texto ou null se falhou */
	private static function callOpenAiCompatible(?string $key, string $model, array $messages, string $systemPrompt, ?string $baseUrl = null): ?string {
		$url = ($baseUrl ?: 'https://api.openai.com/v1').'/chat/completions';
		$payloadMessages = [];
		if ($systemPrompt !== '') {
			$payloadMessages[] = ['role' => 'system', 'content' => $systemPrompt];
		}
		foreach ($messages as $m) {
			$role = $m['role'] === 'ai' ? 'assistant' : ($m['role'] ?? 'user');
			if ($role === 'assistant' || $role === 'user' || $role === 'system') {
				$payloadMessages[] = ['role' => $role, 'content' => (string)($m['content'] ?? '')];
			}
		}
		$body = json_encode([
			'model' => $model,
			'messages' => $payloadMessages,
			'temperature' => 0.7,
		], JSON_UNESCAPED_UNICODE);

		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => [
				'Content-Type: application/json',
				'Authorization: Bearer '.$key,
			],
			CURLOPT_POSTFIELDS => $body,
			CURLOPT_TIMEOUT => 45,
		]);
		$raw = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$err = curl_error($ch);
		curl_close($ch);
		if ($raw === false) {
			self::$lastError = 'cURL: '.$err;
			return null;
		}
		if ($code >= 400) {
			self::$lastError = 'OpenAI HTTP '.$code.': '.mb_substr((string)$raw, 0, 120);
			return null;
		}
		$data = json_decode($raw, true);
		$text = $data['choices'][0]['message']['content'] ?? null;
		return is_string($text) && $text !== '' ? $text : null;
	}

	/** @return string|null */
	private static function callGemini(?string $key, string $model, array $messages, string $systemPrompt): ?string {
		$model = preg_replace('#^models/#', '', $model);
		$url = 'https://generativelanguage.googleapis.com/v1beta/models/'.rawurlencode($model).':generateContent?key='.urlencode((string)$key);

		$contents = [];
		foreach ($messages as $m) {
			$role = ($m['role'] ?? '') === 'ai' || ($m['role'] ?? '') === 'assistant' ? 'model' : 'user';
			if (($m['role'] ?? '') === 'system') {
				continue;
			}
			$contents[] = [
				'role' => $role,
				'parts' => [['text' => (string)($m['content'] ?? '')]],
			];
		}
		if (count($contents) === 0) {
			$contents[] = ['role' => 'user', 'parts' => [['text' => 'Olá']]];
		}

		$payload = ['contents' => $contents];
		if ($systemPrompt !== '') {
			$payload['systemInstruction'] = [
				'parts' => [['text' => $systemPrompt]],
			];
		}

		$body = json_encode($payload, JSON_UNESCAPED_UNICODE);
		$ch = curl_init($url);
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_POST => true,
			CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
			CURLOPT_POSTFIELDS => $body,
			CURLOPT_TIMEOUT => 45,
		]);
		$raw = curl_exec($ch);
		$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$err = curl_error($ch);
		curl_close($ch);
		if ($raw === false) {
			self::$lastError = 'cURL Gemini: '.$err;
			return null;
		}
		if ($code >= 400) {
			self::$lastError = 'Gemini HTTP '.$code.': '.mb_substr((string)$raw, 0, 160);
			return null;
		}
		$data = json_decode($raw, true);
		$text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
		return is_string($text) && $text !== '' ? $text : null;
	}

	/**
	 * Corrige questão aberta. Retorna ['score'=>0-100, 'feedback'=>string, 'correct'=>bool].
	 */
	public static function gradeEssay(int $idAdmin, string $prompt, string $answer, string $lessonContext = ''): array {
		$answer = trim($answer);
		if ($answer === '') {
			return ['score' => 0, 'feedback' => 'Resposta em branco.', 'correct' => false];
		}
		$sys = 'Você é um corretor pedagógico. Avalie a resposta do aluno de 0 a 100. '
			.'Responda APENAS JSON válido: {"score":0-100,"feedback":"texto curto em português","correct":true|false}. '
			.'correct=true se score>=70. Seja justo: valorize raciocínio correto mesmo com redação simples.';
		$user = "Enunciado:\n{$prompt}\n\nResposta do aluno:\n{$answer}";
		if ($lessonContext !== '') {
			$user = "Contexto da aula (use só como referência, sem inventar fatos):\n{$lessonContext}\n\n".$user;
		}
		$raw = self::chat($idAdmin, [['role' => 'user', 'content' => $user]], $sys);
		$json = null;
		if (preg_match('/\{.*\}/s', $raw, $m)) {
			$json = json_decode($m[0], true);
		}
		if (!is_array($json)) {
			$len = mb_strlen($answer);
			$score = $len < 20 ? 40 : ($len < 80 ? 65 : 75);
			return [
				'score' => $score,
				'feedback' => 'Avaliação automática (IA indisponível no momento).',
				'correct' => $score >= 70,
			];
		}
		$score = max(0, min(100, (int)($json['score'] ?? 0)));
		return [
			'score' => $score,
			'feedback' => (string)($json['feedback'] ?? ''),
			'correct' => !empty($json['correct']) || $score >= 70,
		];
	}

	public static function evaluateRoleplay(int $idAdmin, array $scenario, array $messages): array {
		$transcript = '';
		foreach ($messages as $m) {
			$transcript .= strtoupper((string)($m['role'] ?? '')).': '.($m['content'] ?? '')."\n";
		}
		$prompt = "Avalie esta simulação de role play. Responda APENAS JSON válido com keys: overallScore (0-100), summary, strengths (array), improvements (array), mistakes (array), reviewTopics (array).\n"
			."Cenário: ".($scenario['title'] ?? '')."\nObjetivos: ".json_encode($scenario['objectives'] ?? [], JSON_UNESCAPED_UNICODE)."\n"
			."Diálogo:\n".$transcript;
		$raw = self::chat($idAdmin, [['role' => 'user', 'content' => $prompt]], 'Você é um avaliador pedagógico rigoroso.');
		$json = null;
		if (preg_match('/\{.*\}/s', $raw, $m)) {
			$json = json_decode($m[0], true);
		}
		if (!is_array($json)) {
			$json = [
				'overallScore' => 75,
				'summary' => 'Avaliação automática (IA indisponível). Configure/verifique o modelo em IA Pedagógica.',
				'strengths' => ['Participação na conversa'],
				'improvements' => ['Aprofundar argumentos'],
				'mistakes' => [],
				'reviewTopics' => [],
			];
		}
		$min = (int)($scenario['minScore'] ?? 70);
		$score = (int)($json['overallScore'] ?? 75);
		return [
			'overallScore' => $score,
			'passed' => $score >= $min,
			'summary' => (string)($json['summary'] ?? ''),
			'strengths' => $json['strengths'] ?? [],
			'improvements' => $json['improvements'] ?? [],
			'mistakes' => $json['mistakes'] ?? [],
			'reviewTopics' => $json['reviewTopics'] ?? [],
			'competencies' => [['key' => 'geral', 'label' => 'Desempenho geral', 'score' => $score]],
			'timeline' => [],
			'referenceConversation' => [],
		];
	}
}
