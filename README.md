# 💻 Lili pay

> API de pagamentos em PHP para atividade de microsserviços. O sistema permite cadastrar, listar, consultar, atualizar e remover pagamentos, salvando os dados em um arquivo JSON local.

---

## 🛠️ Tecnologias Utilizadas

Liste as principais linguagens, frameworks e ferramentas que sustentam o projeto:
* **Backend:** PHP
* **Frontend:** HTML, CSS e JavaScript
* **Banco de Dados:** Arquivo JSON (`db.json`)
* **Infraestrutura:** Servidor embutido do PHP

---

## 🚀 Como Começar

Siga estas instruções para obter uma cópia do projeto e executá-lo em sua máquina local para fins de desenvolvimento e teste.

### Pré-requisitos
O que você precisa instalar antes de rodar o projeto:
* Git
* XAMPP ou PHP instalado localmente
* Node.js / npm

### Instalação e Configuração

1. **Clone o repositório:**
   ```bash
   git clone https://github.com
   cd microsservicos
   ```

2. **Configure as variáveis de ambiente:**
   O projeto não utiliza arquivo `.env`; as configurações principais estão no `index.php`, como a URL padrão do serviço de entregas.
   ```bash
   # DEFAULT_ENTREGA_URL = 'http://localhost/tp_microservicos/index.php'
   ```

3. **Instale as dependências:**
   ```bash
   npm install
   ```

4. **Execute as migrações do banco de dados (se houver):**
   ```bash
   # Não há migrações. Os dados são gravados diretamente no arquivo db.json.
   ```

5. **Inicie o servidor de desenvolvimento:**
   ```bash
   npm start
   ```
   O sistema estará disponível em: `http://localhost:8000`

   Se estiver usando o XAMPP, coloque a pasta do projeto dentro de `htdocs`, inicie o Apache pelo painel do XAMPP e acesse pelo navegador:
   ```bash
   http://localhost/microsservicos
   ```

---

## 🧪 Executando os Testes

Instruções sobre como rodar os testes automatizados do sistema:

```bash
# Não há scripts de teste configurados no package.json.
```

---

## 📐 Estrutura do Projeto

Uma visão macro de como os arquivos estão organizados (opcional, mas altamente recomendado):

```text
├── index.php         # Tela de cadastro e endpoints da API de pagamentos
├── script.js         # Captura o formulário e envia os dados para a API
├── style.css         # Estilos da interface
├── db.json           # Base de dados local com os pagamentos
├── package.json      # Metadados e script de inicialização do projeto
├── package-lock.json # Controle de versão das dependências npm
└── README.md         # Esta documentação
```

---

## 🤝 Como Contribuir

1. Faça um **Fork** do projeto.
2. Crie uma **Branch** para sua funcionalidade (`git checkout -b feature/NovaFeature`).
3. Faça o **Commit** de suas alterações (`git commit -m 'Adiciona nova feature'`).
4. Envie para o repositório remoto (`git push origin feature/NovaFeature`).
5. Abra um **Pull Request**.

---

## 📄 Licença

Este projeto está sob a licença [MIT] - veja o arquivo `LICENSE` para mais detalhes.
