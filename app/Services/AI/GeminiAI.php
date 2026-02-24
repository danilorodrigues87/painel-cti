<?php

namespace App\Services\AI;

use App\Common\Environment;
use App\Common\Logger;
use App\Model\Entity\Course;
use GuzzleHttp\Client;

class GeminiAI {
    private static $apiKey;
    private static $model = 'gemini-pro';
    private static $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    private static function init() {
        try {
            // Carrega variáveis de ambiente
            Environment::load(__DIR__ . '/../../../../');
            
            self::$apiKey = getenv('GEMINI_API_KEY');
            
            if (empty(self::$apiKey)) {
                Logger::log([
                    'step' => 'erro_gemini_config',
                    'error' => 'GEMINI_API_KEY não configurada'
                ]);
                return false;
            }
            return true;
        } catch(\Exception $e) {
            Logger::log([
                'step' => 'erro_gemini_init',
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private static function callGeminiAPI($prompt) {
        try {
            if (!self::init()) {
                throw new \Exception('Falha na inicialização do Gemini AI');
            }

            Logger::log([
                'step' => 'iniciando_chamada_gemini',
                'prompt' => $prompt
            ]);

            $client = new Client();
            $url = self::$apiUrl . self::$model . ':generateContent?key=' . self::$apiKey;

            $response = $client->post($url, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $result = json_decode($response->getBody(), true);
            
            Logger::log([
                'step' => 'resposta_gemini_recebida',
                'response' => $result
            ]);

            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return $result['candidates'][0]['content']['parts'][0]['text'];
            }

            throw new \Exception('Resposta inválida da API');

        } catch(\Exception $e) {
            Logger::log([
                'step' => 'erro_chamada_gemini',
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public static function generateResponse($message, $phone, $webhook_token) {
        try {
            Logger::log([
                'step' => 'gerando_resposta_ia',
                'message' => $message,
                'whatsapp' => $phone
            ]);

            // Se for uma saudação simples, retorna resposta padrão
            if (self::isSimpleGreeting($message)) {
                $response = self::generateDefaultResponse($message);
                Logger::log([
                    'step' => 'resposta_saudacao',
                    'response' => $response
                ]);
                return $response;
            }

            // Busca cursos para contextualizar a resposta
            $courses = Course::getProfessionalCourses();
            
            // Constrói o prompt com contexto
            $prompt = self::buildPrompt($message, $courses);
            
            // Chama a API do Gemini
            $aiResponse = self::callGeminiAPI($prompt);
            
            if ($aiResponse) {
                Logger::log([
                    'step' => 'resposta_ia_gerada',
                    'response' => $aiResponse
                ]);
                return $aiResponse;
            }

            // Fallback para resposta baseada em regras
            return self::generateRuleBasedResponse($message);

        } catch(\Exception $e) {
            Logger::log([
                'step' => 'erro_geral_gemini',
                'error' => $e->getMessage()
            ]);
            return "Desculpe, tive um problema ao processar sua mensagem. 😅\n\n" .
                   "Você pode reformular sua pergunta ou falar com um de nossos atendentes digitando *falar com atendente*.";
        }
    }

    private static function isSimpleGreeting($message) {
        $message = strtolower(trim($message));
        return in_array($message, [
            'oi', 'olá', 'ola', 'bom dia', 
            'boa tarde', 'boa noite', 'hi', 'hello'
        ]);
    }

    private static function generateDefaultResponse($message) {
        $hour = (int)date('H');
        $greeting = '';
        
        if ($hour >= 5 && $hour < 12) {
            $greeting = 'Bom dia';
        } elseif ($hour >= 12 && $hour < 18) {
            $greeting = 'Boa tarde';
        } else {
            $greeting = 'Boa noite';
        }

        return "{$greeting}! 😊 Sou o assistente virtual da CTI Educacional.\n\n" .
               "Como posso ajudar você hoje? Temos:\n\n" .
               "📚 Cursos Profissionalizantes\n" .
               "🎓 Graduação EAD\n" .
               "📖 EJA (Ensino Fundamental e Médio)\n\n" .
               "Sobre qual área você gostaria de saber mais?";
    }

    private static function buildPrompt($message, $courses) {
        $prompt = "Você é um assistente virtual da CTI Educacional, uma instituição de ensino profissionalizante. 
        
Diretrizes de comunicação:
- Seja amigável e profissional
- Use emojis moderadamente
- Formate valores monetários como R$ X.XXX,XX
- Mantenha respostas concisas (máximo 3 parágrafos)
- Sempre sugira um próximo passo ao cliente

Informações disponíveis:
1. Cursos Profissionalizantes:
";

        // Adiciona informações dos cursos
        foreach ($courses as $course) {
            $prompt .= "- {$course['nome']}\n";
            $prompt .= "  Duração: {$course['duracao']} meses\n";
            $prompt .= "  Carga: {$course['carga_h']} horas\n";
            $prompt .= "  Valor: R$ {$course['valor_mensal']}/mês\n\n";
        }

        $prompt .= "
2. Graduação EAD:
- Pedagogia
- Administração
- Gestão de RH
(Valores e durações sob consulta)

3. EJA (Educação de Jovens e Adultos):
- Ensino Fundamental
- Ensino Médio
(Duração personalizada conforme histórico escolar)

Formas de pagamento:
- Boleto
- Cartão de crédito (até 12x)
- PIX

Mensagem do cliente: {$message}

Responda de forma natural, como um assistente virtual educacional.";

        return $prompt;
    }
}
