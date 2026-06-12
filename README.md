Markdown
# 🚗 EstacionaAqui

O **EstacionaAqui** é um sistema inteligente de gerenciamento e reserva de vagas de estacionamento em tempo real. Desenvolvido como um projeto prático acadêmico, o sistema visa conectar motoristas que buscam uma vaga segura a proprietários de estacionamentos locais.

## 🚀 Funcionalidades Atuais

* **Fluxo de Acesso Dinâmico:** Interface unificada que alterna dinamicamente entre as visões de "Cliente" e "Dono de Estacionamento".
* **Painel do Cliente:** Tela intuitiva preparada para autenticação e cadastro de novos usuários.
* **Filtros e Consultas:** Estrutura pronta para permitir buscas por bairros, preços e serviços adicionais (vagas cobertas, lavagem, etc.).

## 📊 Próximas Implementações (Back-Office & Admin)
Para garantir o monitoramento completo da plataforma, o roteiro de evolução do projeto prevê a criação de um **Painel Administrativo Geral**:
* **Dashboard Global:** Visualização de métricas (número total de cadastros ativos, volume de reservas do dia e faturamento).
* **Gestão de Usuários:** Interface para o Administrador listar, auditar e realizar o bloqueio/banimento de contas em caso de infrações.
* **Suporte Avançado:** Acesso administrativo para reset manual de credenciais e recuperação de contas de usuários com problemas de acesso.

## 🛠️ Tecnologias Utilizadas

* **Frontend:** HTML5, CSS3 (Estilização ágil com *Tailwind CSS* via CDN), JavaScript (Manipulação de DOM para alternância de abas).
* **Backend:** PHP (Processamento de requisições de API).
* **Banco de Dados:** MySQL (Persistência de dados gerenciada localmente via XAMPP/phpMyAdmin).

## 💻 Como Rodar o Projeto Localmente

1. Certifique-se de ter o ambiente local **XAMPP** instalado.
2. Clone este repositório ou mova a pasta do projeto para o diretório de arquivos do servidor: `C:\xampp\htdocs\estaciona-aqui\`.
3. Inicie os módulos **Apache** e **MySQL** através do *XAMPP Control Panel*.
4. Acesse o painel de gerenciamento do banco em `http://localhost/phpmyadmin/` e certifique-se de que a estrutura das tabelas está importada.
5. Abra o seu navegador e acesse: `http://localhost/estaciona-aqui/`.

---
*Projeto desenvolvido para fins de estudo e evolução técnica em Sistemas de Informação