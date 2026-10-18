# 🔗 Encurtador de Links em PHP

Aplicação web simples em PHP puro: cole um endereço grande e receba um link curto. Cada acesso ao link curto é contado e redireciona para o endereço original. Os dados ficam no arquivo `links.json`, sem banco de dados.

## Funcionalidades

- Gera códigos curtos aleatórios de 6 caracteres
- Redireciona para o endereço original e conta os cliques
- Lista os links criados (mais recentes primeiro)
- Exclui links
- Só aceita endereços `http://` e `https://` (bloqueia links perigosos como `javascript:`)

## Como rodar

Requisito: PHP 8.0 ou superior.

```bash
git clone https://github.com/thyagodev021/php-encurtador-links.git
cd php-encurtador-links
php -S localhost:8000
```

Abra http://localhost:8000 no navegador. Se usar XAMPP, copie a pasta para `htdocs` e acesse http://localhost/php-encurtador-links.

## Conceitos usados

- PHP puro (sem framework)
- Validação com `filter_var` e `parse_url`
- Códigos aleatórios seguros com `random_int`
- `htmlspecialchars` para evitar XSS
- Padrão Post/Redirect/Get
- Armazenamento em JSON

## Autor

Thyago Ferreira - [@thyagodev021](https://github.com/thyagodev021)
