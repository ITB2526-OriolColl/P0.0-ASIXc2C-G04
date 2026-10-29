<?php
require __DIR__ . '/config.php';

try {
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    http_response_code(500);
    die('<p>No se puede conectar con la base de datos en ' . htmlspecialchars($DB_HOST) .
        '. Revisa que MySQL escuche en la red y que el router deje pasar el puerto 3306.</p>');
}

// Lista de tablas: solo se permite ver una tabla que exista (evita inyección SQL)
$tablas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$tabla  = in_array($_GET['t'] ?? '', $tablas, true) ? $_GET['t'] : ($tablas[0] ?? null);
$buscar = trim($_GET['q'] ?? '');

$filas = []; $total = 0;
if ($tabla) {
    $total = $pdo->query("SELECT COUNT(*) FROM `$tabla`")->fetchColumn();
    $cols  = $pdo->query("SHOW COLUMNS FROM `$tabla`")->fetchAll(PDO::FETCH_COLUMN);
    if ($buscar !== '') {
        $where = implode(' OR ', array_map(fn($c) => "`$c` LIKE :q", $cols));
        $st = $pdo->prepare("SELECT * FROM `$tabla` WHERE $where LIMIT 100");
        $st->execute([':q' => "%$buscar%"]);
    } else {
        $st = $pdo->query("SELECT * FROM `$tabla` LIMIT 100");
    }
    $filas = $st->fetchAll(PDO::FETCH_ASSOC);
}
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Equipamientos de educación · Barcelona</title>
<style>
  :root { --azul:#1f2bd8; --tinta:#1b1d2a; --gris:#eef0f7; --borde:#c9cde0; }
  body { margin:0; font-family: "Segoe UI", Roboto, sans-serif; color:var(--tinta); }
  header { background:var(--azul); color:#fff; padding:1.2rem 1.5rem; }
  header h1 { margin:0; font-size:1.4rem; }
  header p { margin:.3rem 0 0; opacity:.85; font-size:.9rem; }
  main { padding:1rem 1.5rem; }
  form { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1rem; }
  select, input, button { font:inherit; padding:.4rem .6rem; border:1px solid var(--borde); border-radius:4px; }
  button { background:var(--azul); color:#fff; border-color:var(--azul); cursor:pointer; }
  .tabla { overflow-x:auto; border:1px solid var(--borde); }
  table { border-collapse:collapse; font-size:.85rem; min-width:100%; }
  th, td { padding:.4rem .6rem; border-bottom:1px solid var(--borde); text-align:left; white-space:nowrap; }
  th { background:var(--gris); position:sticky; top:0; }
  .info { font-size:.85rem; color:#555; }
</style>
</head>
<body>
<header>
  <h1>Equipamientos de educación de Barcelona</h1>
  <p>Datos de Open Data BCN servidos desde <?= $h($DB_HOST) ?> · servidor web <?= $h(gethostname()) ?></p>
</header>
<main>
<?php if (!$tabla): ?>
  <p>La base de datos <?= $h($DB_NAME) ?> no tiene tablas todavía. Carga el CSV en B-NCC y recarga.</p>
<?php else: ?>
  <form method="get">
    <select name="t">
      <?php foreach ($tablas as $t): ?>
        <option <?= $t === $tabla ? 'selected' : '' ?>><?= $h($t) ?></option>
      <?php endforeach; ?>
    </select>
    <input name="q" placeholder="Buscar (nombre, barrio, distrito...)" value="<?= $h($buscar) ?>">
    <button>Buscar</button>
  </form>
  <p class="info">Tabla <strong><?= $h($tabla) ?></strong>: <?= $total ?> registros en total, mostrando <?= count($filas) ?>.</p>
  <div class="tabla"><table>
    <tr><?php foreach (array_keys($filas[0] ?? []) as $c): ?><th><?= $h($c) ?></th><?php endforeach; ?></tr>
    <?php foreach ($filas as $f): ?>
      <tr><?php foreach ($f as $v): ?><td><?= $h($v) ?></td><?php endforeach; ?></tr>
    <?php endforeach; ?>
  </table></div>
<?php endif; ?>
</main>
</body>
</html>
