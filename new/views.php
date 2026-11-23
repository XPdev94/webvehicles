<?php

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function recordUrl($id): string
{
    return 'index.php?view=record&id=' . (int) $id;
}

function vehicleUrl($code): string
{
    return 'index.php?view=register&vehicle=' . rawurlencode((string) $code);
}

function csrf(): void
{
    echo '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
}

function displayDate($value): string
{
    $date = $value ? strtotime($value) : false;
    return $date !== false ? date('d/m/Y', $date) : '—';
}

function vehicleName(array $vehicle): string
{
    return trim(($vehicle['MARCA'] ?? '') . ' ' . ($vehicle['MODEL'] ?? '')) ?: ($vehicle['DESCRIPCIOOBRA'] ?? 'Vehículo');
}

function layout(string $title, ?array $worker, ?array $notice = null): void
{
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> · OSG Vehículos</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="index.php"><span class="wordmark">OSG</span><span>Vehículos</span></a>
        <?php if ($worker): ?>
        <div class="account">
            <div class="account-name"><strong><?= h($worker['name']) ?></strong><span><?= h($worker['company_name']) ?></span></div>
            <form method="post" action="index.php"><?php csrf(); ?>
                <button class="button button-top" name="action" value="logout">Salir</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</header>
<main class="shell<?= $worker ? '' : ' shell-login' ?>" id="contenido">
    <?php if ($notice): ?>
        <div class="notice <?= h($notice['tone']) ?>" role="<?= $notice['tone'] === 'error' ? 'alert' : 'status' ?>"><?= h($notice['text']) ?></div>
    <?php endif; ?>
    <?php
}

function endLayout(): void
{
    echo '</main></body></html>';
}

function pageHeading(string $title, ?string $subtitle = null, bool $back = true): void
{
    ?>
    <div class="page-heading">
        <?php if ($back): ?><a class="back" href="index.php">← Volver a vehículos</a><?php endif; ?>
        <h1><?= h($title) ?></h1>
        <?php if ($subtitle): ?><p><?= h($subtitle) ?></p><?php endif; ?>
    </div>
    <?php
}

function renderLogin(?array $notice, string $username): void
{
    layout('Acceso', null, $notice);
    ?>
    <section class="card login-card">
        <h1>Registro de vehículos</h1>
        <p class="muted">Accede con tu usuario de OSG.</p>
        <form method="post" action="index.php" class="fields">
            <?php csrf(); ?>
            <label>Usuario
                <input name="username" value="<?= h($username) ?>" autocomplete="username" required autofocus>
            </label>
            <label>Contraseña
                <input name="password" type="password" autocomplete="current-password" required>
            </label>
            <button class="button" name="action" value="login">Entrar</button>
        </form>
    </section>
    <?php
    endLayout();
}

function renderDashboard(array $worker, ?array $notice, array $recent, array $completed, string $search, array $results): void
{
    layout('Vehículos', $worker, $notice);
    pageHeading('Vehículos', 'Registra un vehículo, completa una revisión o comunica una incidencia.', false);
    $latest = $recent[0] ?? null;
    $lastCheck = $completed[0] ?? null;
    $lastCheckTime = $lastCheck ? strtotime($lastCheck['DIA']) : false;
    $overdue = $lastCheckTime !== false && $lastCheckTime < strtotime('-30 days');
    ?>
    <div class="dashboard">
        <section class="card" aria-labelledby="search-title">
            <h2 id="search-title">Buscar vehículo</h2>
            <form action="index.php" method="get" class="search">
                <label for="q" class="sr-only">Matrícula, código o descripción</label>
                <input id="q" name="q" type="search" placeholder="Matrícula, código o descripción" value="<?= h($search) ?>" required>
                <button class="button">Buscar</button>
            </form>
            <?php if ($search !== ''): ?>
                <?php if (!$results): ?><p class="muted">No hay vehículos que coincidan con «<?= h($search) ?>».</p><?php else: ?>
                <ul class="vehicle-results">
                    <?php foreach ($results as $result): ?>
                        <li><div><strong><?= h($result['MATRICULA'] ?: $result['CODIOBRA']) ?></strong><span><?= h(vehicleName($result)) ?></span></div>
                            <a class="button button-secondary" href="<?= h(vehicleUrl($result['CODIOBRA'])) ?>">Seleccionar<span class="sr-only"> <?= h($result['MATRICULA'] ?: $result['CODIOBRA']) ?></span></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            <?php endif; ?>
        </section>
        <section class="card side-card" aria-labelledby="last-title">
            <h2 id="last-title">Último vehículo</h2>
            <?php if ($latest): ?>
                <strong class="plate"><?= h($latest['MATRICULA'] ?: $latest['CODIOBRA']) ?></strong>
                <p class="muted"><?= h(vehicleName($latest)) ?></p>
                <a class="button button-secondary" href="<?= h(vehicleUrl($latest['CODIOBRA'])) ?>">Usar este vehículo</a>
            <?php else: ?>
                <p class="muted">Aún no has registrado ningún vehículo.</p>
            <?php endif; ?>
        </section>
    </div>
    <section class="card history" aria-labelledby="recent-title">
        <div class="section-heading">
            <h2 id="recent-title">Registros recientes</h2>
            <?php if (!$lastCheck): ?>
                <p class="status warning">Sin checklists completados</p>
            <?php elseif ($overdue): ?>
                <p class="status warning">Han pasado más de 30 días desde tu último checklist</p>
            <?php else: ?>
                <p class="status">Último checklist · <?= h(displayDate($lastCheck['DIA'])) ?></p>
            <?php endif; ?>
        </div>
        <?php if (!$recent): ?>
            <p class="muted">Tus registros aparecerán aquí.</p>
        <?php else: ?>
        <div class="table-scroll">
            <table>
                <thead><tr><th scope="col">Fecha</th><th scope="col">Vehículo</th><th scope="col">Kilometraje</th><th scope="col">Tipo de revisión</th><th scope="col">Estado</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $entry): $stage = recordStage($entry); ?>
                <tr>
                    <td><?= h(displayDate($entry['DIA'])) ?></td>
                    <td><strong><?= h($entry['MATRICULA'] ?: $entry['CODIOBRA']) ?></strong><span class="table-detail"><?= h(vehicleName($entry)) ?></span></td>
                    <td><?= h($entry['QUILOMETRES']) ?> km</td>
                    <td><?= h($entry['REVIEW_NAME'] ?? '—') ?><?php if ($entry['INCIDENT_NAME']): ?><span class="table-detail"><?= h($entry['INCIDENT_NAME']) ?></span><?php endif; ?></td>
                    <td><?php if (in_array($stage, ['checklist', 'incident'], true)): ?>
                        <a href="<?= h(recordUrl($entry['IDVEHICLETREB'])) ?>">Continuar</a>
                        <?php elseif ($stage === 'photos'): ?>
                        <a href="<?= h(recordUrl($entry['IDVEHICLETREB'])) ?>">Añadir fotos</a>
                        <?php else: ?>
                        <span class="muted">Completado</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
    <?php
    endLayout();
}

function options(array $items, string $key, $selected): void
{
    echo '<option value="">Selecciona una opción</option>';
    foreach ($items as $item) {
        $id = (int) $item[$key];
        if ($id > 0) {
            echo '<option value="' . $id . '"' . ($id === (int) $selected ? ' selected' : '') . '>'
                . h($item['DESCRIPCION']) . '</option>';
        }
    }
}

function renderRegistration(array $worker, ?array $notice, array $vehicle, array $types, array $draft, string $submission): void
{
    layout('Registrar vehículo', $worker, $notice);
    pageHeading('Registrar vehículo', vehicleName($vehicle));
    $itv = $vehicle['NEXT_ITV'];
    $itvTime = $itv ? strtotime($itv) : false;
    ?>
    <div class="form-layout">
        <section class="card side-card" aria-label="Datos del vehículo">
            <strong class="plate"><?= h($vehicle['MATRICULA'] ?: $vehicle['CODIOBRA']) ?></strong>
            <dl>
                <dt>Código</dt><dd><?= h($vehicle['CODIOBRA']) ?></dd>
                <dt>Próxima ITV</dt><dd><?= h(displayDate($itv)) ?>
                <?php if ($itvTime !== false && $itvTime < strtotime('today')): ?><span class="status warning">Caducada</span><?php endif; ?></dd>
            </dl>
        </section>
        <section class="card">
            <h2>Datos de la revisión</h2>
            <form action="<?= h(vehicleUrl($vehicle['CODIOBRA'])) ?>" method="post" class="fields">
                <?php csrf(); ?>
                <input type="hidden" name="submission" value="<?= h($submission) ?>">
                <label>Tipo de revisión
                    <select name="type" required><?php options($types, 'IDTIPUSREVISIO', $draft['type'] ?? ''); ?></select>
                </label>
                <label>Kilometraje
                    <input name="mileage" type="number" inputmode="numeric" min="0" max="999999999" value="<?= h($draft['mileage'] ?? '') ?>">
                    <span class="muted">Obligatorio salvo para registrar una incidencia.</span>
                </label>
                <label>Observaciones <span class="muted">(opcional)</span>
                    <textarea name="notes" rows="4"><?= h($draft['notes'] ?? '') ?></textarea>
                </label>
                <div class="actions"><button class="button" name="action" value="create">Guardar y continuar</button></div>
            </form>
        </section>
    </div>
    <?php
    endLayout();
}

function recordDescription(array $record): string
{
    return ($record['MATRICULA'] ?: $record['CODIOBRA']) . ' · ' . vehicleName($record);
}

function renderIncident(array $worker, ?array $notice, array $record, array $categories, array $draft): void
{
    layout('Registrar incidencia', $worker, $notice);
    pageHeading('Registrar incidencia', recordDescription($record));
    ?>
    <section class="card narrow">
        <form method="post" action="<?= h(recordUrl($record['IDVEHICLETREB'])) ?>" class="fields">
            <?php csrf(); ?>
            <label>Tipo de incidencia
                <select name="type" required><?php options($categories, 'IDVEHICLEINC', $draft['type']); ?></select>
            </label>
            <label>Descripción
                <textarea name="notes" rows="5" required><?= h($draft['notes']) ?></textarea>
            </label>
            <div class="actions"><button class="button" name="action" value="incident">Guardar y continuar</button></div>
        </form>
    </section>
    <?php
    endLayout();
}

function renderChecklist(array $worker, ?array $notice, array $record, array $questions, array $missing): void
{
    layout('Checklist del vehículo', $worker, $notice);
    pageHeading('Checklist del vehículo', recordDescription($record));
    if (!$questions): ?>
        <div class="notice error" role="alert">Este registro no tiene preguntas de revisión configuradas.</div>
    <?php else: ?>
    <section class="card">
        <form method="post" action="<?= h(recordUrl($record['IDVEHICLETREB'])) ?>" class="fields">
            <?php csrf(); ?>
            <div class="question-list">
            <?php foreach ($questions as $question):
                $id = (int) $question['IDCHECKINGRESPOSTA'];
                $answer = $question['RESPOSTA'] === null ? '' : (string) $question['RESPOSTA'];
                $expiry = substr((string) $question['CADUCITAT'], 0, 10);
                $needsDate = $question['TIPOARTICULO'] === 'M';
                $incomplete = in_array($id, $missing, true);
                ?>
                <fieldset class="question<?= $incomplete ? ' question-missing' : '' ?>">
                    <legend><?= h($question['DESCRIPCION'] ?: 'Elemento de revisión') ?></legend>
                    <div class="question-fields">
                        <div class="choices">
                            <label><input type="radio" name="answers[<?= $id ?>]" value="-1" <?= $answer === '-1' ? 'checked' : '' ?>> Sí</label>
                            <label><input type="radio" name="answers[<?= $id ?>]" value="0" <?= $answer === '0' ? 'checked' : '' ?>> No</label>
                        </div>
                        <?php if ($needsDate): ?>
                            <label>Caducidad
                                <input type="date" name="expiry[<?= $id ?>]" value="<?= h($expiry) ?>">
                                <?php if (validDate($expiry) && $expiry <= date('Y-m-d', strtotime('+30 days'))): ?>
                                    <span class="status warning"><?= $expiry < date('Y-m-d') ? 'Caducada' : 'Caduca pronto' ?></span>
                                <?php endif; ?>
                            </label>
                        <?php endif; ?>
                    </div>
                    <?php if ($incomplete): ?><p class="field-error"><?= $answer === '' ? 'Selecciona Sí o No.' : '' ?> <?= $needsDate && !$expiry ? 'Indica la fecha de caducidad.' : '' ?></p><?php endif; ?>
                </fieldset>
            <?php endforeach; ?>
            </div>
            <label>Comentario <span class="muted">(opcional)</span>
                <textarea name="comment" rows="3"><?= h($record['COMENTARI'] ?? '') ?></textarea>
            </label>
            <div class="actions">
                <button class="button button-secondary" name="action" value="save">Guardar borrador</button>
                <button class="button" name="action" value="finish">Completar checklist</button>
            </div>
        </form>
    </section>
    <?php
    endif;
    endLayout();
}

function renderPhotos(array $worker, ?array $notice, array $record): void
{
    layout('Fotos del vehículo', $worker, $notice);
    pageHeading('Fotos del vehículo', recordDescription($record));
    ?>
    <section class="card narrow">
        <h2>Añadir fotos <span class="muted">(opcional)</span></h2>
        <?php if ((int) $record['TEFOTOGRAFIES'] === -1): ?><p class="status">Este registro tiene fotos guardadas.</p><?php endif; ?>
        <form method="post" action="<?= h(recordUrl($record['IDVEHICLETREB'])) ?>" enctype="multipart/form-data" class="fields">
            <?php csrf(); ?>
            <label>Seleccionar foto
                <input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif" required aria-describedby="photo-help">
                <span class="muted" id="photo-help">JPG, PNG, WebP o GIF. Hasta 10 MB por foto.</span>
            </label>
            <div class="actions">
                <button class="button button-secondary" name="action" value="photo">Guardar foto</button>
                <a class="button" href="index.php?view=done&amp;id=<?= (int) $record['IDVEHICLETREB'] ?>">Finalizar</a>
            </div>
        </form>
    </section>
    <?php
    endLayout();
}

function renderDone(array $worker, ?array $notice, array $record): void
{
    layout('Registro completado', $worker, $notice);
    ?>
    <section class="card narrow confirmation">
        <span class="confirmation-mark" aria-hidden="true">✓</span>
        <h1>Registro completado</h1>
        <p class="muted"><?= h(recordDescription($record)) ?></p>
        <a class="button" href="index.php">Volver a vehículos</a>
    </section>
    <?php
    endLayout();
}

function renderUnavailable(?array $worker): void
{
    layout('Servicio no disponible', $worker);
    ?>
    <section class="card narrow">
        <h1>Servicio no disponible</h1>
        <p>El registro de vehículos no está disponible en este momento.</p>
        <a class="button button-secondary" href="index.php">Volver al inicio</a>
    </section>
    <?php
    endLayout();
}
