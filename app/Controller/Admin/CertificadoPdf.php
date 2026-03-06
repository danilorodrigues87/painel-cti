<?php 

namespace App\Controller\Admin;

use \App\Utils\View;
use \App\Model\Entity\Trilhas as EntityTrilhas;
use \App\Model\Entity\User as EntityUser;
use \App\Model\Entity\Certificados as EntityCertificados;

class CertificadoPdf extends Page {

    /**
     * Método responsável por retornar o formulário/página do certificado
     */
    public static function index($request, $id) {
        $dados = self::geraCertPdf($id);

        return View::render('admin/modules/certificados/certificado', [
            'img-qrcode' => $dados['img_qrcode'],
            'script'     => $dados['script']
        ]);
    }

    /**
     * Método responsável por processar os dados e gerar os caminhos do certificado
     */
    public static function geraCertPdf($id) {
        // Busca os dados do certificado
        $dados = (array) EntityCertificados::getCertificadoById($id);

        $codigo          = $dados['codigo'];
        $id_aluno       = $dados['id_aluno'];
        $id_trilha      = $dados['id_trilha'];
        $descricao      = 'Composto pelos módulos ' . $dados['modulos'];
        $hora           = 'Perfazendo a carga horária total de ' . $dados['carga_h'] . ' horas';
        $dataDeConclusao = 'Concluído em ' . $dados['conclusao'];

        // Busca dados do aluno
        $obUser = (array) EntityUser::getUserById($id_aluno);
        $nome   = $obUser['nome'];

        // Busca dados da trilha/curso
        $dadosTrilha = (array) EntityTrilhas::getTrilhaById($id_trilha);
        $curso       = $dadosTrilha['nome'];

        // URL para a API de QR Code
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.SITE.'/certificado?crt='.$codigo; 

        // --- LÓGICA DE CAMINHOS FÍSICOS (SISTEMA) ---
        // Sobe 3 níveis a partir de App/Controller/Admin para chegar na raiz do projeto
        $caminhoProjeto = realpath(__DIR__ . '/../../../'); 
        $diretorioImg   = '/uploads/img/certificado/';
        $caminhoSalvar  = $caminhoProjeto . $diretorioImg;

        // Cria a pasta caso ela não exista
        if (!is_dir($caminhoSalvar)) {
            mkdir($caminhoSalvar, 0755, true);
        }

        // Nome único para o QR Code para evitar conflitos com o modelo de fundo
        $nomeArquivoQr   = 'qr_' . $codigo . '.png';
        $caminhoCompleto = $caminhoSalvar . $nomeArquivoQr;

        // Baixa a imagem da API e salva no servidor
        $imgData = @file_get_contents($qrCodeUrl);
        if ($imgData === false || file_put_contents($caminhoCompleto, $imgData) === false) {
            throw new \Exception("Erro ao salvar a imagem QR Code em: " . $caminhoCompleto);
        }

        // --- LÓGICA DE URL (NAVEGADOR) ---
        $img_qrcode = '<img id="qrCodeImg" src="' . URL . $diretorioImg . $nomeArquivoQr . '" style="display:none;">';
        $baseUrl    = URL;

        // Script JavaScript para renderização no Canvas


$script = <<<SCRIPT
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    console.log("Iniciando script de geração de certificado...");

    const canvas = document.getElementById("canvas");
    const ctx = canvas.getContext("2d");

    // Configuração do tamanho A4 em modo Paisagem (Pixels)
    canvas.width = 842; 
    canvas.height = 595; 

    // Variáveis vindas do PHP
    const name = "{$nome}";
    const curso = "{$curso}";
    const descricao = "{$descricao}";
    const dataDeConclusao = "{$dataDeConclusao}";
    const hora = "{$hora}";
    const codigoCert = "{$codigo}";

    // Controle de carregamento de imagens
    const certImage = new Image();
    const qrCodeImg = new Image();
    let imagensCarregadas = 0;
    const totalImagens = 2;

    function aoCarregarImagem() {
        imagensCarregadas++;
        if (imagensCarregadas === totalImagens) {
            console.log("Sucesso: Todas as imagens prontas. Desenhando...");
            draw();
        }
    }

    certImage.onerror = () => console.error("Erro ao carregar o fundo do certificado.");
    qrCodeImg.onerror = () => console.error("Erro ao carregar a imagem do QR Code.");

    certImage.onload = aoCarregarImagem;
    qrCodeImg.onload = aoCarregarImagem;

    // Define as fontes (URLs) das imagens
    certImage.src = "{$baseUrl}/uploads/img/certificado/modelo_cert.png";
    qrCodeImg.src = "{$baseUrl}/uploads/img/certificado/{$nomeArquivoQr}";

    function draw() {
        // 1. Limpa o canvas e desenha o fundo
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(certImage, 0, 0, canvas.width, canvas.height);
        
        // 2. Desenha o QR Code
        ctx.drawImage(qrCodeImg, 50, 470, 80, 80);

        // 3. Configurações de Texto
        ctx.fillStyle = "black";
        ctx.textAlign = "center";

        // Nome do Aluno
        ctx.font = "bold italic 26pt Arial";
        ctx.fillText(name, 421, 280);

        // Texto do Curso
        ctx.font = "normal 16pt Arial";
        ctx.fillText("Concluiu com louvor o curso de", 421, 325);
        
        ctx.font = "bold 18pt Arial";
        ctx.fillText(curso, 421, 355);

        // Descrição com Quebra de Linha Automática
        ctx.font = "normal 12pt Arial";
        const maxWidth = 650;
        const lineHeight = 25;
        let x = 421;
        let y = 390;
        let line = "";
        const words = descricao.split(" ");

        for (const word of words) {
            const testLine = line + word + " ";
            const metrics = ctx.measureText(testLine);
            if (metrics.width > maxWidth && line !== "") {
                ctx.fillText(line, x, y);
                line = word + " ";
                y += lineHeight;
            } else {
                line = testLine;
            }
        }
        ctx.fillText(line, x, y);

        // Data e Carga Horária
        ctx.font = "italic 11pt Arial";
        ctx.textAlign = "left";
        ctx.fillText(hora, 500, 480);
        ctx.fillText(dataDeConclusao, 500, 500);
        
        // Código de Autenticação (opcional, perto do QR Code)
        ctx.font = "8pt Arial";
        ctx.fillText("Cód: " + codigoCert, 50, 565);
    }

    // Evento para baixar PDF
    document.getElementById("dpdf").addEventListener("click", function () {
        const { jsPDF } = window.jspdf;
        const imgData = canvas.toDataURL("image/jpeg", 1.0);
        const pdf = new jsPDF("l", "mm", "a4");
        pdf.addImage(imgData, "JPEG", 0, 0, 297, 210);
        pdf.save("certificado_" + codigoCert + ".pdf");
    });

    // Evento para baixar Imagem
    document.getElementById("dimg").addEventListener("click", function () {
        const imgData = canvas.toDataURL("image/png", 1.0);
        const link = document.createElement("a");
        link.download = "certificado_" + codigoCert + ".png";
        link.href = imgData;
        link.click();
    });
</script>
SCRIPT;


        return [
            'img_qrcode' => $img_qrcode,
            'script'     => $script,
        ];
    }
}