<h1>Módulo de ejemplo</h1>
<?php foreach ($registros as $fila): ?>
    <p><?= htmlspecialchars((string) $fila['id']) ?></p>
<?php endforeach; ?>
