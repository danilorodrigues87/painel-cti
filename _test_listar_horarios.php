<?php
require __DIR__ . '/includes/app.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = ['dia_semana' => 1, 'laboratorio_id' => 0, 'id_horario' => 0];
session_save_path(__DIR__ . '/app/sessions');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
// pick first admin user id from DB
$pdo = new PDO('mysql:host='.getenv('DB_HOST').';dbname='.getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASS'));
$row = $pdo->query('SELECT id, id_admin, nome, email, nivel, termos_uso, acesso FROM usuarios WHERE nivel NOT IN ("Cliente","Empresa") AND ativo="s" LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if (!$row) { echo "no user\n"; exit(1);} 
$_SESSION['usuario-mvc-1'] = [
  'id' => (int)$row['id'],
  'id_admin' => (int)$row['id_admin'],
  'nome' => $row['nome'],
  'email' => $row['email'],
  'nivel' => $row['nivel'],
  'termos_uso' => (int)$row['termos_uso'],
  'acesso' => json_decode($row['acesso'] ?? '[]', true) ?? [],
];
class MockRequest {
  public function getPostVars(){ return $_POST; }
}
ob_start();
try {
  $html = \App\Controller\Admin\AgendaLaboratorio::listarHorarios(new MockRequest());
  echo "OK len=".strlen($html)."\n";
  echo substr($html,0,200)."\n";
} catch (Throwable $e) {
  echo "EX: ".$e->getMessage()."\n";
}
