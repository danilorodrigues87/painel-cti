<?php 

namespace App\Controller\Admin;

use \App\Utils\View;
use \App\Model\Entity\Trilhas as EntityTrilhas;
use \App\Model\Entity\User as EntityUser;
use \App\Model\Entity\Certificados as EntityCertificados;

class CertificadoPdf extends Page {

    // RETORNA O FORMULÁRIO
    public static function index($request, $id) {
        $dados = self::geraCertPdf($id);

        // CONTEÚDO DE FORMULÁRIO
        return View::render('admin/modules/certificados/certificado', [
            'img-qrcode' => $dados['img_qrcode'],
            'script' => $dados['script']
        ]);
    }

    // FUNÇÃO PARA GERAR O CERTIFICADO EM PDF
    public static function geraCertPdf($id) {
        $dados = (array) EntityCertificados::getCertificadoById($id);

        $codigo = $dados['codigo'];
        $id_aluno = $dados['id_aluno'];
        $id_trilha = $dados['id_trilha'];
        $descricao = 'Composto pelos módulos ' . $dados['modulos'];
        $hora = 'Perfazendo a carga horária total de ' . $dados['carga_h'] . ' horas';
        $dataDeConclusao = 'Concluído em ' . $dados['conclusao'];

        $obUser = (array) EntityUser::getUserById($id_aluno);
        $nome = $obUser['nome'];

        $dadosTrilha = (array) EntityTrilhas::getTrilhaById($id_trilha);
        $curso = $dadosTrilha['nome'];

        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data='.SITE.'/certificado?crt='.$codigo; 

        // Caminho para salvar a imagem no servidor
        $caminhoSalvar = $_SERVER['DOCUMENT_ROOT'] . '/uploads/img/certificado/';
        $nomeArquivo = 'img_qr_temporaria.png';
        $caminhoCompleto = $caminhoSalvar . $nomeArquivo;

        // Baixar a imagem da API e salvar no servidor
        $imgData = file_get_contents($qrCodeUrl);
        if ($imgData === false || file_put_contents($caminhoCompleto, $imgData) === false) {
            throw new \Exception("Erro ao salvar a imagem QR Code.");
        }

        // Caminho da imagem para ser usado no HTML
        $img_qrcode = '<img id="qrCodeImg" src="' . URL . '/uploads/img/certificado/' . $nomeArquivo . '" style="display:none;">';

        // Script JavaScript com debug
        $baseUrl = URL; // Use a constante ou variável que contém a URL base do projeto.

$script = <<<SCRIPT
<script>
    console.log("Iniciando script...");

    const canvas = document.getElementById("canvas");
    const ctx = canvas.getContext("2d");

    canvas.width = 842; // Largura A4 em modo paisagem
    canvas.height = 595; // Altura A4 em modo paisagem

    const name = "{$nome}";
    const curso = "{$curso}";
    const descricao = "{$descricao}";
    const dataDeConclusao = "{$dataDeConclusao}";
    const hora = "{$hora}";

    const certImage = new Image();
    const qrCodeImg = new Image();

    certImage.src = "{$baseUrl}/uploads/img/certificado/modelo_cert.png";
    qrCodeImg.src = "{$baseUrl}/uploads/img/certificado/{$nomeArquivo}";

    console.log("Carregando imagem do certificado:", certImage.src);
    console.log("Carregando QR Code:", qrCodeImg.src);

    certImage.onload = function () {
        console.log("Imagem do certificado carregada.");
        draw();
    };

    qrCodeImg.onload = function () {
        console.log("QR Code carregado.");
    };

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(certImage, 0, 0, canvas.width, canvas.height);
        ctx.drawImage(qrCodeImg, 50, 470, 80, 80);

        ctx.fillStyle = "black";
        ctx.textAlign = "center";

        ctx.font = "bold italic 24pt Arial";
        ctx.fillText(name, 400, 280);

        ctx.font = "normal 14pt Arial";
        ctx.fillText("Concluiu com louvor o curso de", 400, 320);
        ctx.fillText(curso, 400, 350);

        ctx.font = "normal 12pt Arial";
        const maxWidth = 700;
        const lineHeight = 30;
        let x = 400;
        let y = 380;
        let line = "";
        const words = descricao.split(" ");

        for (const word of words) {
            const testLine = line + word + " ";
            const metrics = ctx.measureText(testLine);
            const testWidth = metrics.width;

            if (testWidth > maxWidth && line !== "") {
                ctx.fillText(line, x, y);
                line = word + " ";
                y += lineHeight;
            } else {
                line = testLine;
            }
        }
        ctx.fillText(line, x, y);

        ctx.font = "normal 11pt Arial";
        ctx.fillText(dataDeConclusao, 500, 540);
        ctx.fillText(hora, 500, 470);
    }

    document.getElementById("dpdf").addEventListener("click", function () {
        console.log("Botão PDF clicado");
        const imgData = canvas.toDataURL("image/jpeg", 1.0);
        const pdf = new jsPDF("l", "mm", "a4");
        pdf.addImage(imgData, "JPEG", 0, 0, 297, 210);
        pdf.save("certificado.pdf");
    });

    document.getElementById("dimg").addEventListener("click", function () {
        console.log("Botão Imagem clicado");
        const imgData = canvas.toDataURL("image/jpeg", 1.0);
        const link = document.createElement("a");
        link.download = "certificado.jpg";
        link.href = imgData;
        link.click();
    });
</script>
SCRIPT;


        return [
            'img_qrcode' => $img_qrcode,
            'script' => $script,
        ];
    }
}

?>
