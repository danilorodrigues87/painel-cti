<?php

namespace App\Common\Communication;

/**
 * Templates prontos de fluxos WhatsApp (Fase B).
 */
class WhatsappFlowTemplates {

	/** @return list<array{id:string,nome:string,descricao:string,definicao:array}> */
	public static function todos(): array {
		return [
			self::boasVindasSetores(),
			self::qualificacaoLead(),
		];
	}

	public static function getById(string $id): ?array {
		foreach (self::todos() as $t) {
			if ($t['id'] === $id) {
				return $t;
			}
		}
		return null;
	}

	private static function boasVindasSetores(): array {
		return [
			'id' => 'boas_vindas_setores',
			'nome' => 'Boas-vindas + escolha de setor',
			'descricao' => 'Saudação na primeira mensagem e opções numéricas para Comercial, Financeiro ou falar com atendente.',
			'definicao' => [
				'trigger' => ['tipo' => 'saudacao', 'modo' => 'contem', 'palavra' => ''],
				'settings' => [
					'timeout_horas' => 24,
					'timeout_acao' => 'humano',
				],
				'start' => 'n1',
				'nodes' => [
					'n1' => [
						'type' => 'send_text',
						'config' => [
							'texto' => "Olá! Bem-vindo(a) ao atendimento automático.\nComo podemos ajudar?",
							'next' => 'n2',
						],
						'next' => 'n2',
					],
					'n2' => [
						'type' => 'ask_options',
						'config' => [
							'texto' => "Escolha digitando o *número*:\n*1* - Comercial\n*2* - Financeiro\n*3* - Falar com atendente",
							'opcoes' => [
								['num' => '1', 'label' => 'Comercial', 'next' => 'n3'],
								['num' => '2', 'label' => 'Financeiro', 'next' => 'n4'],
								['num' => '3', 'label' => 'Atendente', 'next' => 'n5'],
							],
						],
					],
					'n3' => [
						'type' => 'goto_setor',
						'config' => [
							'setor_id' => 0,
							'texto' => 'Certo! Vou te encaminhar para o *Comercial*. Aguarde um momento.',
						],
					],
					'n4' => [
						'type' => 'goto_setor',
						'config' => [
							'setor_id' => 0,
							'texto' => 'Certo! Vou te encaminhar para o *Financeiro*. Aguarde um momento.',
						],
					],
					'n5' => [
						'type' => 'goto_humano',
						'config' => [
							'texto' => 'Perfeito. Em breve um atendente vai te responder.',
						],
					],
				],
			],
		];
	}

	private static function qualificacaoLead(): array {
		return [
			'id' => 'qualificacao_lead',
			'nome' => 'Qualificação de lead (CRM)',
			'descricao' => 'Palavra-chave “quero” / “interesse”: pergunta nome e curso, cria lead no CRM e encaminha para a fila.',
			'definicao' => [
				'trigger' => ['tipo' => 'keyword', 'modo' => 'contem', 'palavra' => 'quero'],
				'settings' => [
					'timeout_horas' => 12,
					'timeout_acao' => 'encerrar',
				],
				'start' => 'n1',
				'nodes' => [
					'n1' => [
						'type' => 'send_text',
						'config' => [
							'texto' => 'Ótimo! Vou te ajudar a iniciar. Responda algumas perguntas rápidas.',
							'next' => 'n2',
						],
						'next' => 'n2',
					],
					'n2' => [
						'type' => 'ask_text',
						'config' => [
							'texto' => 'Qual o seu *nome*?',
							'var' => 'nome',
							'next' => 'n3',
						],
						'next' => 'n3',
					],
					'n3' => [
						'type' => 'ask_text',
						'config' => [
							'texto' => 'Qual *curso* você tem interesse, {{nome}}?',
							'var' => 'curso',
							'next' => 'n4',
						],
						'next' => 'n4',
					],
					'n4' => [
						'type' => 'criar_lead',
						'config' => [
							'nome_var' => 'nome',
							'curso_var' => 'curso',
							'origem' => 'WhatsApp bot',
							'next' => 'n5',
						],
						'next' => 'n5',
					],
					'n5' => [
						'type' => 'goto_humano',
						'config' => [
							'texto' => 'Obrigado, {{nome}}! Registramos seu interesse em *{{curso}}*. Em breve um atendente fala com você.',
						],
					],
				],
			],
		];
	}
}
