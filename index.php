<?php
$id = $_POST ["id"];
$nome = $_POST ["nome"];
$valor = $_POST['valor'];


// Define o fuso horario usado nas datas salvas nos pagamentos.
date_default_timezone_set('America/Sao_Paulo');

// Caminho do arquivo JSON usado como banco de dados simples.
const DB_FILE = __DIR__ . '/db.json';

// URL padrao do microsservico de entregas, usada para montar o link de entrega.
const DEFAULT_ENTREGA_URL = 'http://localhost/tp_microservicos/index.php';


// Carrega os dados do db.json e garante que a estrutura principal exista.
function carregarDb()
{
    // Se o arquivo ainda nao existir, cria um banco vazio.
    if (!file_exists(DB_FILE)) {
        salvarDb(['pagamento' => []]);
    }

    $conteudo = file_get_contents(DB_FILE);
    $db = json_decode($conteudo, true);

    // Se o JSON estiver invalido, reinicia a estrutura em memoria.
    if (!is_array($db)) {
        $db = ['pagamento' => []];
    }

    // Garante que a chave "pagamento" sempre seja uma lista.
    if (!isset($db['pagamento']) || !is_array($db['pagamento'])) {
        $db['pagamento'] = [];
    }

    return $db;
}

// Salva os dados no db.json em formato legivel.
function salvarDb($db)
{
    file_put_contents(
        DB_FILE,
        json_encode($db, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        LOCK_EX
    );
}

// Envia uma resposta JSON e encerra a execucao da requisicao.
function responderJson($dados, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Le os dados enviados na requisicao, aceitando JSON ou formulario comum.
function lerEntrada()
{
    $conteudo = file_get_contents('php://input');
    $json = json_decode($conteudo, true);

    if (is_array($json)) {
        return $json;
    }

    return $_POST;
}

// Descobre o id do pagamento pela query string ou pelo caminho da URL.
function obterIdDaRequisicao()
{
    if (isset($_GET['id'])) {
        return (int) $_GET['id'];
    }

    $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (preg_match('/pagamentos\/(\d+)$/', $caminho, $matches)) {
        return (int) $matches[1];
    }

    return null;
}

// Calcula o proximo id com base no maior id ja salvo.
function proximoId($pagamentos)
{
    $maior = 0;
    foreach ($pagamentos as $pagamento) {
        $maior = max($maior, (int) ($pagamento['id'] ?? 0));
    }

    return $maior + 1;
}

// // Monta a URL que sera enviada para o servico de entregas.
// function montarUrlEntrega($pagamento, $baseUrl = DEFAULT_ENTREGA_URL)
// {
//     // Usa "?" ou "&" dependendo se a URL base ja possui parametros.
//     $separador = strpos($baseUrl, '?') === false ? '?' : '&';

//     return $baseUrl . $separador . http_build_query([
//         'pagamento' => $pagamento['id'],
//         'nome' => $pagamento['nome_cliente'] ?? ''
//     ]);
// }

// Adiciona o campo entrega_url ao pagamento antes de responder ao cliente.
function comUrlEntrega($pagamento, $baseUrl = DEFAULT_ENTREGA_URL)
{
    $pagamento['entrega_url'] = montarUrlEntrega($pagamento, $baseUrl);
    return $pagamento;
}

// Valida e padroniza os dados de um novo pagamento.
function validarPagamento($dados, $id)
{
    $erros = [];
    $formasPermitidas = ['pix', 'cartao', 'dinheiro'];
    $statusPermitidos = ['aprovado', 'pendente', 'recusado'];

    $pedido = isset($dados['pedido']) ? (int) $dados['pedido'] : 0;
    $nomeCliente = trim((string) ($dados['nome_cliente'] ?? ''));
    $formaPagamento = strtolower(trim((string) ($dados['forma_pagamento'] ?? '')));
    $valorTexto = str_replace(',', '.', (string) ($dados['valor'] ?? ''));
    $valor = is_numeric($valorTexto) ? (float) $valorTexto : 0;
    $status = strtolower(trim((string) ($dados['status'] ?? 'aprovado')));

    // Regras minimas para aceitar o cadastro.
    if ($pedido <= 0) {
        $erros[] = 'Informe um pedido valido.';
    }

    if ($nomeCliente === '') {
        $nomeCliente = 'Cliente do pedido ' . $pedido;
    }

    if (!in_array($formaPagamento, $formasPermitidas, true)) {
        $erros[] = 'Forma de pagamento invalida. Use pix, cartao ou dinheiro.';
    }

    if ($valor <= 0) {
        $erros[] = 'Informe um valor maior que zero.';
    }

    if (!in_array($status, $statusPermitidos, true)) {
        $erros[] = 'Status invalido. Use aprovado, pendente ou recusado.';
    }

    return [
        'erros' => $erros,
        'pagamento' => [
            'id' => $id,
            'pedido' => $pedido,
            'nome_cliente' => $nomeCliente,
            'forma_pagamento' => $formaPagamento,
            'valor' => round($valor, 2),
            'status' => $status,
            'data_pagamento' => date('c')
        ]
    ];
}

// Atualiza apenas os campos permitidos de um pagamento existente.
function atualizarPagamento($pagamento, $dados)
{
    $permitidos = ['forma_pagamento', 'valor', 'status', 'nome_cliente'];

    foreach ($permitidos as $campo) {
        if (isset($dados[$campo])) {
            $pagamento[$campo] = $dados[$campo];
        }
    }

    if (isset($pagamento['forma_pagamento'])) {
        $pagamento['forma_pagamento'] = strtolower(trim((string) $pagamento['forma_pagamento']));
    }

    if (isset($pagamento['status'])) {
        $pagamento['status'] = strtolower(trim((string) $pagamento['status']));
    }

    if (isset($pagamento['valor'])) {
        $pagamento['valor'] = round((float) str_replace(',', '.', (string) $pagamento['valor']), 2);
    }

    $pagamento['atualizado_em'] = date('c');
    return $pagamento;
}

// Controla todos os endpoints da API de pagamentos.
function manipularApiPagamentos()
{
    // Cabecalhos CORS para permitir chamadas da tela ou de outros servicos.
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    // Requisicoes OPTIONS sao usadas pelo navegador antes de alguns metodos HTTP.
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit;
    }

    $db = carregarDb();
    $metodo = $_SERVER['REQUEST_METHOD'];
    $id = obterIdDaRequisicao();

    if ($metodo === 'GET') {
        // Quando existe id, busca apenas um pagamento.
        if ($id !== null) {
            foreach ($db['pagamento'] as $pagamento) {
                if ((int) $pagamento['id'] === $id) {
                    responderJson(comUrlEntrega($pagamento));
                }
            }

            responderJson(['erro' => 'Pagamento nao encontrado.'], 404);
        }

        // Sem id, retorna todos os pagamentos cadastrados.
        $pagamentos = array_map('comUrlEntrega', $db['pagamento']);
        responderJson(['pagamento' => $pagamentos]);
    }

    if ($metodo === 'POST') {
        // Cria um novo pagamento a partir dos dados enviados.
        $dados = lerEntrada();
        $idNovo = proximoId($db['pagamento']);
        $resultado = validarPagamento($dados, $idNovo);

        if (!empty($resultado['erros'])) {
            responderJson(['erros' => $resultado['erros']], 422);
        }

        $db['pagamento'][] = $resultado['pagamento'];
        salvarDb($db);

        $baseEntrega = trim((string) ($dados['entrega_url_base'] ?? DEFAULT_ENTREGA_URL));
        responderJson(comUrlEntrega($resultado['pagamento'], $baseEntrega), 201);
    }

    if ($metodo === 'PUT' || $metodo === 'PATCH') {
        // PUT/PATCH precisam de id para saber qual pagamento alterar.
        if ($id === null) {
            responderJson(['erro' => 'Informe o id do pagamento.'], 400);
        }

        $dados = lerEntrada();
        // Procura o pagamento, atualiza, salva e retorna o registro atualizado.
        foreach ($db['pagamento'] as $indice => $pagamento) {
            if ((int) $pagamento['id'] === $id) {
                $db['pagamento'][$indice] = atualizarPagamento($pagamento, $dados);
                salvarDb($db);
                responderJson(comUrlEntrega($db['pagamento'][$indice]));
            }
        }

        responderJson(['erro' => 'Pagamento nao encontrado.'], 404);
    }

    if ($metodo === 'DELETE') {
        // DELETE tambem precisa de id para remover o pagamento correto.
        if ($id === null) {
            responderJson(['erro' => 'Informe o id do pagamento.'], 400);
        }

        // Remove o pagamento encontrado da lista.
        foreach ($db['pagamento'] as $indice => $pagamento) {
            if ((int) $pagamento['id'] === $id) {
                array_splice($db['pagamento'], $indice, 1);
                salvarDb($db);
                responderJson(['mensagem' => 'Pagamento removido.']);
            }
        }

        responderJson(['erro' => 'Pagamento nao encontrado.'], 404);
    }

    responderJson(['erro' => 'Metodo nao permitido.'], 405);
}

// Identifica se a requisicao atual deve ser tratada pela API.
$caminho = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$rotaPorQuery = isset($_GET['api']) && $_GET['api'] === 'pagamentos';
$rotaPorCaminho = preg_match('#(^|/)index\.php/pagamentos(/\d+)?$#', $caminho)
    || preg_match('#^/pagamentos(/\d+)?$#', $caminho);

// Se for uma rota da API, responde em JSON e nao renderiza o HTML abaixo.
if ($rotaPorQuery || $rotaPorCaminho) {
    manipularApiPagamentos();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <!-- Configuracoes basicas da pagina e arquivo de estilos. -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API de Pagamentos</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <main class="container">
        <!-- Cabecalho da tela de cadastro. -->
        <header>
            <h1>API de Pagamentos</h1>
            <p>Cadastre o pagamento e envie os dados para o servico de entregas.</p>
        </header>

        <!-- Area usada pelo JavaScript para mostrar mensagens de sucesso ou erro. -->
        <section id="mensagem" class="msg escondido"></section>

        <!-- Formulario que envia os dados para a API pelo script.js. -->
        <form id="form-pagamento" class="form-pagamento" action="http://192.168.0.100/tp_microservicos/" method="POST">
            <div class="input-group">
                <label for="pedido">ID do Pedido:</label>
                <input type="number" id="pedido" name="pedido" min="1"value = "<?php echo $id; ?>">
            </div>

            <div class="input-group">
                <label for="nome_cliente">Nome do Cliente:</label>
                <input type = "text" id = "nome_cliente" name="nome" value = "<?php echo $nome; ?>">
            </div>

            <div class="input-group">
                <label for="forma_pagamento">Forma de Pagamento:</label>
                <select id="forma_pagamento" name="forma_pagamento" required>
                    <option value="pix">Pix</option>
                    <option value="cartao">Cartao</option>
                    <option value="dinheiro">Dinheiro</option>
                </select>
            </div>

            <div class="input-group">
                <label for="valor">Valor:</label>
                <input type="text" id="valor" name="valor" value = "<?php echo $valor; ?>">
            </div>

            <div class="input-group">
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="aprovado">Aprovado</option>
                    <option value="pendente">Pendente</option>
                    <option value="recusado">Recusado</option>
                </select>
            </div>

            <div class="input-group">
                <label for="entrega_url_base">URL do Servico de Entregas:</label>
                <!-- htmlspecialchars evita que caracteres especiais quebrem o HTML do value. -->
                <input type="url" id="entrega_url_base" name="entrega_url_base" value="<?php echo htmlspecialchars(DEFAULT_ENTREGA_URL, ENT_QUOTES, 'UTF-8'); ?>">
            </div>

            <button type="submit" class="btn-gravar" id="btn-gravar">Gravar Pagamento</button>
        </form>

    </main>

    <!-- JavaScript responsavel por capturar o formulario e chamar a API. -->
    <!-- <script src="script.js"></script> -->
</body>

</html>
