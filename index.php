<?php
/**
 * Encurtador de Links em PHP
 * Autor: Thyago Ferreira (https://github.com/thyagodev021)
 *
 * Cria links curtos (ex.: http://localhost:8000/?c=aB3xYz), conta os cliques
 * e guarda tudo em um arquivo JSON, sem banco de dados.
 */

const ARQUIVO = __DIR__ . '/links.json';

function carregar(): array
{
    if (!file_exists(ARQUIVO)) {
        return [];
    }
    $dados = json_decode(file_get_contents(ARQUIVO), true);
    return is_array($dados) ? $dados : [];
}

function salvar(array $links): void
{
    file_put_contents(ARQUIVO, json_encode($links, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function gerarCodigo(array $links): string
{
    $letras = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $codigo = '';
        for ($i = 0; $i < 6; $i++) {
            $codigo .= $letras[random_int(0, strlen($letras) - 1)];
        }
    } while (isset($links[$codigo]));
    return $codigo;
}

/** Aceita somente endereços http:// ou https:// (evita links perigosos como javascript:). */
function urlValida(string $url): bool
{
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    $esquema = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return in_array($esquema, ['http', 'https'], true);
}

function caminhoAtual(): string
{
    return strtok($_SERVER['REQUEST_URI'], '?');
}

function urlCurta(string $codigo): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . caminhoAtual() . '?c=' . $codigo;
}

function e(string $texto): string
{
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
}

$links = carregar();
$erro = '';
$urlDigitada = '';

// 1) Alguém abriu um link curto: conta o clique e redireciona
$codigo = $_GET['c'] ?? '';
if ($codigo !== '') {
    if (isset($links[$codigo])) {
        $links[$codigo]['cliques']++;
        salvar($links);
        header('Location: ' . $links[$codigo]['url'], true, 302);
        exit;
    }
    http_response_code(404);
    $erro = 'Link curto não encontrado.';
}

// 2) Formulários (encurtar / excluir)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'encurtar') {
        $urlDigitada = trim($_POST['url'] ?? '');
        if (!urlValida($urlDigitada)) {
            $erro = 'Digite um endereço válido, começando com http:// ou https://';
        } else {
            $novo = gerarCodigo($links);
            $links[$novo] = [
                'url'     => $urlDigitada,
                'criado'  => date('d/m/Y H:i'),
                'cliques' => 0,
            ];
            salvar($links);
            header('Location: ' . caminhoAtual() . '?novo=' . $novo);
            exit;
        }
    } elseif ($acao === 'excluir') {
        unset($links[$_POST['codigo'] ?? '']);
        salvar($links);
        header('Location: ' . caminhoAtual());
        exit;
    }
}

$novoCodigo = $_GET['novo'] ?? '';
$lista = array_reverse($links, true); // mais recentes primeiro
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Encurtador de Links - PHP</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #0f172a; margin: 0; padding: 2rem 1rem; color: #e2e8f0; }
        main { max-width: 720px; margin: 0 auto; }
        h1 { margin: 0 0 .25rem; }
        .sub { color: #94a3b8; margin-bottom: 1.5rem; }
        .card { background: #1e293b; padding: 1.25rem; border-radius: 12px; margin-bottom: 1rem; }
        form.novo { display: flex; gap: .5rem; }
        input[type=text] { flex: 1; padding: .7rem; border: 1px solid #334155; border-radius: 8px; background: #0f172a; color: #e2e8f0; font-size: 1rem; }
        button { cursor: pointer; border: 0; border-radius: 8px; padding: .7rem 1rem; font-size: .95rem; }
        .primario { background: #38bdf8; color: #0f172a; font-weight: 600; }
        .excluir { background: #7f1d1d; color: #fecaca; padding: .4rem .7rem; }
        .erro { background: #7f1d1d; color: #fecaca; padding: .75rem 1rem; border-radius: 8px; margin-top: .75rem; }
        .sucesso { background: #14532d; color: #bbf7d0; padding: .75rem 1rem; border-radius: 8px; margin-bottom: 1rem; word-break: break-all; }
        .sucesso a { color: #fff; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: .6rem .4rem; border-bottom: 1px solid #334155; font-size: .9rem; vertical-align: middle; }
        td.url { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #94a3b8; }
        a { color: #38bdf8; }
        footer { text-align: center; color: #64748b; font-size: .8rem; margin-top: 1.5rem; }
    </style>
</head>
<body>
<main>
    <h1>🔗 Encurtador de Links</h1>
    <p class="sub">Cole um endereço grande e receba um link curto.</p>

    <?php if ($novoCodigo !== '' && isset($links[$novoCodigo])): ?>
        <div class="sucesso">
            Link criado: <a href="<?= e(urlCurta($novoCodigo)) ?>" target="_blank"><?= e(urlCurta($novoCodigo)) ?></a>
        </div>
    <?php endif; ?>

    <div class="card">
        <form class="novo" method="post">
            <input type="hidden" name="acao" value="encurtar">
            <input type="text" name="url" placeholder="https://exemplo.com/um/endereco/bem/grande" value="<?= e($urlDigitada) ?>" required>
            <button class="primario" type="submit">Encurtar</button>
        </form>
        <?php if ($erro !== ''): ?>
            <div class="erro"><?= e($erro) ?></div>
        <?php endif; ?>
    </div>

    <div class="card">
        <?php if (empty($lista)): ?>
            <p>Nenhum link criado ainda.</p>
        <?php else: ?>
            <table>
                <tr><th>Link curto</th><th>Destino</th><th>Cliques</th><th></th></tr>
                <?php foreach ($lista as $cod => $info): ?>
                    <tr>
                        <td><a href="<?= e(urlCurta((string) $cod)) ?>" target="_blank">?c=<?= e((string) $cod) ?></a></td>
                        <td class="url" title="<?= e($info['url']) ?>"><?= e($info['url']) ?></td>
                        <td><?= (int) $info['cliques'] ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="codigo" value="<?= e((string) $cod) ?>">
                                <button class="excluir" type="submit">✕</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>

    <footer>Feito em PHP por Thyago Ferreira</footer>
</main>
</body>
</html>
