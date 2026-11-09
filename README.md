# HP Terraplenagem — Sistema de Gestão de Frota

Projeto de extensão: sistema web simples para uma empresa de terraplenagem, feito em **PHP + MySQL** e rodando no **XAMPP**.

## Funcionalidades

**Operador**
- Lança o apontamento de horas: máquina, serviço/cliente e horímetro inicial e final.
- Vê o último serviço registrado e o histórico dos seus últimos lançamentos.

**Administrador**
- **Painel:** alertas de máquinas com troca de óleo vencida ou próxima.
- **Gerenciamento de Horas:** horas apontadas por mês, máquina, operador ou cliente.
- **Relatórios:** busca por período e exportação para CSV (Excel).
- **Controle de Gastos:** lançar, editar e excluir gastos de combustível e manutenção, com totais do mês.
- **Manutenção:** situação do horímetro de cada máquina e registro da troca de óleo.
- **Documentos:** placa, RENAVAM e upload do CRLV (PDF ou imagem).
- **Fechamento Mensal:** soma as horas e os gastos reais do mês e guarda no histórico.
- **Cadastro de Usuários:** cria novos operadores ou administradores.

## Como rodar no XAMPP

1. Copie a pasta do projeto para dentro de `htdocs` do XAMPP:
   - Linux: `/opt/lampp/htdocs/trabalho-extensao`
   - Windows: `C:\xampp\htdocs\trabalho-extensao`
2. Abra o painel do XAMPP e ligue o **Apache** e o **MySQL**.
3. Acesse `http://localhost/phpmyadmin`, vá na aba **Importar**, escolha o arquivo `banco.sql` e clique em **Importar**.
   Isso cria o banco `hp_terraplenagem` com dados de exemplo.
4. Acesse `http://localhost/trabalho-extensao`.

> No Linux, a pasta `uploads/` precisa ter permissão de escrita para o Apache:
> `sudo chmod 777 /opt/lampp/htdocs/trabalho-extensao/uploads`

## Usuários de teste

| Login | Senha | Perfil |
|---|---|---|
| `admin` | `admin` | Administrador |
| `operador` | `operador` | Operador |

## Configuração do banco

Os dados de conexão ficam em `config.php`. O padrão do XAMPP é usuário `root` sem senha.
Se o seu MySQL tiver senha, altere a linha `DB_SENHA`.

## Estrutura

```
banco.sql             -> script do banco (importar no phpMyAdmin)
config.php            -> conexão com o banco, sessão e funções auxiliares
cabecalho.php         -> <head> comum incluído por todas as páginas
index.php             -> login
logout.php            -> sair
painel.php            -> painel do admin / apontamento do operador
dashboard_horas.php   -> gerenciamento de horas
relatorios.php        -> relatório de produção + exportação CSV
controle_gastos.php   -> despesas
tela_manutencao.php   -> manutenção (troca de óleo)
documentos.php        -> documentos dos veículos (upload do CRLV)
arquivo.php           -> abre o CRLV (só para admin logado)
fechamento.php        -> fechamento mensal
cadastro.php          -> cadastro de usuários
uploads/              -> arquivos de CRLV enviados (acesso direto bloqueado)
style.css, media/     -> visual e imagens
```

## Banco de dados

| Tabela | Conteúdo |
|---|---|
| `usuarios` | logins (senha criptografada com `password_hash`) |
| `maquinas` | frota, horímetro, regras de troca de óleo e documentos |
| `apontamentos` | horas lançadas pelos operadores |
| `gastos` | despesas de combustível e manutenção |
| `manutencoes` | histórico de trocas de óleo |
| `fechamentos` | consolidação mensal |
