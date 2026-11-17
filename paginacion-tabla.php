<?php
/** @var int $paginaActual */
/** @var int $totalPaginas */
/** @var int $totalRegistros */
/** @var int $registrosPorPagina */

$desdeRegistro = $totalRegistros > 0
    ? (($paginaActual - 1) * $registrosPorPagina) + 1
    : 0;
$hastaRegistro = min($paginaActual * $registrosPorPagina, $totalRegistros);
$parametrosPaginacion = $_GET;
unset($parametrosPaginacion['pagina']);

$parametrosAnterior = $parametrosPaginacion;
$parametrosAnterior['pagina'] = max(1, $paginaActual - 1);
$urlAnterior = '?' . http_build_query($parametrosAnterior);

$parametrosSiguiente = $parametrosPaginacion;
$parametrosSiguiente['pagina'] = min($totalPaginas, $paginaActual + 1);
$urlSiguiente = '?' . http_build_query($parametrosSiguiente);
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-3">
    <small class="text-muted">
        Mostrando <?= $desdeRegistro ?>-<?= $hastaRegistro ?> de <?= $totalRegistros ?> registros
    </small>
    <div class="d-flex flex-wrap align-items-center gap-2">
        <?php if ($paginaActual > 1): ?>
            <a class="btn btn-outline-primary btn-sm" style="padding-top: 0.15rem; padding-bottom: 0.15rem; line-height: 1.25;" href="<?= htmlspecialchars($urlAnterior, ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
        <?php else: ?>
            <span class="btn btn-outline-secondary btn-sm disabled" style="padding-top: 0.15rem; padding-bottom: 0.15rem; line-height: 1.25;" aria-disabled="true">Anterior</span>
        <?php endif; ?>

        <form method="get" class="d-flex align-items-center gap-2">
            <?php foreach ($parametrosPaginacion as $nombre => $valor): ?>
                <?php if (is_scalar($valor)): ?>
                    <input type="hidden" name="<?= htmlspecialchars((string) $nombre, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <label for="pagina-tabla" class="small mb-0">Página</label>
            <input
                type="number"
                id="pagina-tabla"
                name="pagina"
                class="form-control form-control-sm"
                style="width: 60px; height: 26px; min-height: 0; padding-top: 0.15rem; padding-bottom: 0.15rem; line-height: 1.25;"
                min="1"
                max="<?= $totalPaginas ?>"
                value="<?= $paginaActual ?>"
                required>
            <span class="small text-muted">de <?= $totalPaginas ?></span>
            <button type="submit" class="btn btn-primary btn-sm" style="padding-top: 0.15rem; padding-bottom: 0.15rem; line-height: 1.25;">Ir</button>
        </form>

        <?php if ($paginaActual < $totalPaginas): ?>
            <a class="btn btn-outline-primary btn-sm" style="padding-top: 0.15rem; padding-bottom: 0.15rem; line-height: 1.25;" href="<?= htmlspecialchars($urlSiguiente, ENT_QUOTES, 'UTF-8') ?>">Siguiente</a>
        <?php else: ?>
            <span class="btn btn-outline-secondary btn-sm disabled" style="padding-top: 0.15rem; padding-bottom: 0.15rem; line-height: 1.25;" aria-disabled="true">Siguiente</span>
        <?php endif; ?>
    </div>
</div>