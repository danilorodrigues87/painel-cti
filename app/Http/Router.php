<?php

namespace App\Http;

use \Closure;
use \Exception;
use \ReflectionFunction;
use \App\Http\Middleware\Queue as MiddlewareQueue;
use \App\Utils\View;


class Router{

	private $url = ''; // URL completa da raiz do projeto
	private $prefix = ''; // Prefixo de todas as rotas
	private $routes = []; // Index de todas rotas
	private $request; // Instancia de Request
	private $contentType = 'text/html'; // ContentType padrão

	// Metodo inicia as classes
	public function __construct($url){
		$this->request = new Request($this);
		$this->url = $url;
		$this->setPrefix();
	}

	//ALTERA O VALOR DO CONTENTTYPE
	public function setContentType($contentType){
		$this->contentType = $contentType;
	}

	//define o prefixo das rotas
	private function setPrefix(){
		//Informações da URL atual
		$parseUrl = parse_url($this->url);
		
		//Define o prefixo (sem barra final — getUri também normaliza assim)
		$this->prefix = rtrim((string)($parseUrl['path'] ?? ''), '/');
	}

	//Adiciona uma rota na classe
	private function addRoute($method,$route,$params = []){
		//Validação dos parametros
		foreach ($params as $key=>$value) {
			if($value instanceof Closure){
				$params['controller'] = $value;
				unset($params[$key]);
				continue;
			}
		}

		//MIDDLEWARES DA ROTA
		$params['middlewares'] = $params['middlewares'] ?? [];

		//VARIAVEIS DA ROTA
		$params['variables'] = [];

		// {nome+} captura um ou mais segmentos (ex.: auth/login)
		$patternPlus = '/\{([a-zA-Z_][a-zA-Z0-9_]*)\+\}/';
		if (preg_match_all($patternPlus, $route, $matchesPlus)) {
			$route = preg_replace($patternPlus, '(.+)', $route);
			$params['variables'] = array_merge($params['variables'], $matchesPlus[1]);
		}

		//PADRÃO DE VALIDAÇÃO DAS VARIAVEIS DAS ROTAS
		// ([^/]+) = um segmento; evita /courses/{id} capturar /courses/2/lessons/2
		$patternVariable = '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/';
		if (preg_match_all($patternVariable, $route, $matches)) {
			$route = preg_replace($patternVariable, '([^/]+)', $route);
			$params['variables'] = array_merge($params['variables'], $matches[1]);
		}

		//REMOVE BARRA NO FINAL DA ROTA (mantém "/" para a home/login)
		$route = rtrim($route,'/');
		if ($route === '') {
			$route = '/';
		}

		//PADRÃO DE VALIDAÇÃO DA URL
		$patternRoute = '/^'.str_replace('/', '\/', $route).'$/';
		
		//ADICIONA A ROTA DENTRO DA CLASSE
		$this->routes[$patternRoute][$method] = $params;
	}

	//definir um rota de GET
	public function get($route,$params = []){
		return $this->addRoute('GET',$route,$params);
	}

	//definir um rota de post
	public function post($route,$params = []){
		return $this->addRoute('POST',$route,$params);
	}

	//definir um rota de PUT
	public function put($route,$params = []){
		return $this->addRoute('PUT',$route,$params);
	}

	//definir um rota de DELETE
	public function delete($route,$params = []){
		return $this->addRoute('DELETE',$route,$params);
	}

	// CORS preflight (portal aluno / APIs)
	public function options($route,$params = []){
		return $this->addRoute('OPTIONS',$route,$params);
	}

	public function getUri(){
		// URI da request (ex.: /pjt/painel-cti/privacidade)
		$uri = $this->request->getUri();
		// Prefixo sem barra final — evita explode falhar quando URL do .env termina com /
		$prefix = rtrim((string)$this->prefix, '/');
		if ($prefix !== '' && strpos($uri, $prefix) === 0) {
			$uri = substr($uri, strlen($prefix)) ?: '/';
		}
		// Sempre com barra inicial (rotas são cadastradas como /privacidade, /painel, …)
		$uri = '/'.ltrim($uri, '/');
		$uri = rtrim($uri, '/');
		return $uri === '' ? '/' : $uri;
	}

	//retorna os dados da rota atual
	private function getRoute(){
		//URI
		$uri = $this->getUri();
		
		//METHOD
		$httpMethod = $this->request->getHttpMethod();

		// URI bateu em algum padrão (ex.: OPTIONS catch-all), mas sem o método atual
		$uriMatched = false;
		
		//VALIDA AS ROTAS 
		foreach($this->routes as $patternRoute=>$methods){

			//VERIFICA SE A URI BATE O PADRÃO
			if(preg_match($patternRoute,$uri,$matches)){
				$uriMatched = true;

				//VERIFICA O METHOD — se não tiver, tenta o próximo padrão
				// (evita 405 quando OPTIONS /{path} casa antes de GET /courses)
				if(!isset($methods[$httpMethod])){
					continue;
				}

				//REMOVE A PRIMEIRA POSIÇÃO
				unset($matches[0]);

				//VARIAVEIS PROCESSADAS 
				$keys = $methods[$httpMethod]['variables'];
				$methods[$httpMethod]['variables'] = array_combine($keys, $matches);
				$methods[$httpMethod]['variables']['request'] = $this->request; 


				//RETORNA OS PARAMETROS DA ROTA
				return $methods[$httpMethod];
			}
		}

		if($uriMatched){
			throw new Exception(View::render('erros/405',[]), 405);
		}

		//URL NÃO ENCONTRADA
		throw new Exception(View::render('erros/404',[]), 404);

	}

	

	//EXECUTA A ROTA ATUAL
	public function run(){
		try {
			
			//OBTEM A ROTA ATUAL
			$route = $this->getRoute();
		
		//VERIFICA O CONTROLADOR - A URL não pôde ser processada
			if(!isset($route['controller'])){
				throw new Exception(View::render('erros/405',[]), 500);

			}

			//ARGUMENTOS DA FUNÇÃO
			$args = [];
 
			//REFLECTION
			$reflection = new ReflectionFunction($route['controller']);
			foreach($reflection->getParameters() as $parameter){
				$name = $parameter->getName();
				$args[$name] = $route['variables'][$name] ?? '';
			}

			//RETORNA A EXECUÇÃO DA FILA DE MIDDLEWARES
			return (new MiddlewareQueue($route['middlewares'],$route['controller'],$args))->next($this->request);

		} catch (Exception $e) {
			return new Response($e->getCode(),$this->getErrorMessage($e->getMessage()),$this->contentType);
		}
	}

	private function getErrorMessage($message){

		switch ($this->contentType) {
			case 'application/json':
				return [
					'erro' => $message
				];
				break;
			
			default:
				return $message;
				break;
		}
	}

	//RETORNA URL ATUAL
	public function getCurrentUrl(){
		return $this->url.$this->getUri();
	}

	//REDIRECIONA A URL
	public function redirect($route){
		//URL
		$url = $this->url.$route;
	
		//EXECUTA O DIRECT
		header('location: '.$url);
		exit;
	}

}