const form = document.getElementById('form-pagamento');
const mensagem = document.getElementById('mensagem');
const botao = document.getElementById('btn-gravar');

function mostrarMensagem(texto, tipo) {
    mensagem.className = 'msg ' + tipo;
    mensagem.textContent = texto;
}

function mostrarSucesso(pagamento) {
    mensagem.className = 'msg sucesso';
    mensagem.textContent = '';

    const texto = document.createElement('p');
    texto.textContent = 'Pagamento gravado com sucesso. ID gerado: ' + pagamento.id + '.';

    const link = document.createElement('a');
    link.href = pagamento.entrega_url;
    link.target = '_blank';
    link.rel = 'noopener';
    link.textContent = 'Abrir servico de entregas';

    const json = document.createElement('pre');
    json.textContent = JSON.stringify(pagamento, null, 2);

    mensagem.appendChild(texto);
    mensagem.appendChild(link);
    mensagem.appendChild(json);
}

form.addEventListener('submit', async function (event) {
    event.preventDefault();

    const dados = {
        pedido: document.getElementById('pedido').value,
        nome_cliente: document.getElementById('nome_cliente').value,
        forma_pagamento: document.getElementById('forma_pagamento').value,
        valor: document.getElementById('valor').value,
        status: document.getElementById('status').value,
        entrega_url_base: document.getElementById('entrega_url_base').value
    };

    botao.disabled = true;
    botao.textContent = 'Gravando...';

    try {
        const resposta = await fetch('index.php?api=pagamentos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dados)
        });

        const retorno = await resposta.json();

        if (!resposta.ok) {
            const erros = retorno.erros ? retorno.erros.join(' ') : retorno.erro;
            mostrarMensagem(erros || 'Nao foi possivel gravar o pagamento.', 'erro');
            return;
        }

        mostrarSucesso(retorno);
        form.reset();
        document.getElementById('entrega_url_base').value = 'http://localhost/tp_microservicos/index.php';
    } catch (erro) {
        mostrarMensagem('Erro ao chamar a API de pagamentos.', 'erro');
    } finally {
        botao.disabled = false;
        botao.textContent = 'Gravar Pagamento';
    }
});
