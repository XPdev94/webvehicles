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

function renderNotice(?array $notice): void
{
    if ($notice): ?>
        <div class="notice <?= h($notice['tone']) ?>" role="<?= $notice['tone'] === 'error' ? 'alert' : 'status' ?>">
            <?= h($notice['text']) ?>
        </div>
    <?php endif;
}

function layout(string $title, ?array $worker, ?array $notice = null, bool $login = false): void
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
    <body<?= $login ? ' class="login-page"' : '' ?>>
        <?php if (!$login): ?>
            <header class="topbar">
                <div class="topbar-inner">
                    <a class="brand" href="index.php"><img src="osg.png" alt="OSG" height="30"><span>Vehículos</span></a>
                    <?php if ($worker): ?>
                        <div class="account">
                            <div class="account-name">
                                <strong><?= h($worker['name']) ?></strong><span><?= h($worker['company_name']) ?></span>
                            </div>
                            <?php if (!empty($worker['supervisor'])): ?>
                                <a class="button button-top" href="index.php?view=workers">Cambiar trabajador</a>
                            <?php endif; ?>
                            <form method="post" action="index.php"><?php csrf(); ?>
                                <button class="button button-top" name="action" value="logout">Salir</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </header>
        <?php endif; ?>
        <main class="shell<?= $worker ? '' : ' shell-login' ?>" id="contenido">
            <?php renderNotice($notice);
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
                <?php if ($subtitle): ?>
                    <p><?= h($subtitle) ?></p><?php endif; ?>
            </div>
            <?php
}

function renderLogin(?array $notice, string $username): void
{
    layout('Acceso', null, null, true);
    ?>
            <section class="card login-card">
                <header class="login-header">
                    <h1>Registro de vehículos</h1>
                </header>
                <div class="login-content">
                    <?php renderNotice($notice); ?>
                    <form method="post" action="index.php" class="fields">
                        <?php csrf(); ?>
                        <div class="floating-field">
                            <input id="username" name="username" value="<?= h($username) ?>" placeholder=" "
                                autocomplete="username" required autofocus>
                            <label for="username">Usuario</label>
                        </div>
                        <div class="floating-field">
                            <input id="password" name="password" type="password" placeholder=" "
                                autocomplete="current-password" required>
                            <label for="password">Contraseña</label>
                        </div>
                        <button class="button" name="action" value="login">Entrar</button>
                    </form>
                </div>
            </section>
            <?php
            endLayout();
}

function renderWorkers(array $worker, ?array $notice, array $workers): void
{
    layout('Cambiar trabajador', $worker, $notice);
    pageHeading('Cambiar trabajador', $worker['company_name']);
    ?>
            <section class="card narrow">
                <?php if (!$workers): ?>
                    <p class="muted">No hay trabajadores disponibles.</p>
                <?php else: ?>
                    <form method="post" action="index.php?view=workers" class="fields">
                        <?php csrf(); ?>
                        <label>Trabajador
                            <select name="employee" required>
                                <?php foreach ($workers as $candidate): ?>
                                    <option value="<?= h($candidate['CODITREBALLADOR']) ?>" <?= (string) $candidate['CODITREBALLADOR'] === (string) $worker['employee'] ? ' selected' : '' ?>>
                                        <?= h($candidate['NOMTREBALLADOR']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <div class="actions"><button class="button" name="action" value="switch-worker">Cambiar</button></div>
                    </form>
                <?php endif; ?>
            </section>
            <?php
            endLayout();
}

function renderDashboard(
    array $worker,
    ?array $notice,
    array $recent,
    array $completed,
    string $search,
    array $results,
    bool $canDelete = false
): void {
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
                        <input id="q" name="q" type="search" placeholder="Matrícula, código o descripción"
                            value="<?= h($search) ?>" required>
                        <button class="button">Buscar</button>
                    </form>
                    <?php if ($search !== ''): ?>
                        <?php if (!$results): ?>
                            <p class="muted">No hay vehículos que coincidan con «<?= h($search) ?>».</p><?php else: ?>
                            <ul class="vehicle-results">
                                <?php foreach ($results as $result): ?>
                                    <li>
                                        <div><strong><?= h($result['MATRICULA'] ?: $result['CODIOBRA']) ?></strong><span><?= h($result['CODIOBRA']) ?>
                                                · <?= h(vehicleName($result)) ?></span></div>
                                        <a class="button button-secondary"
                                            href="<?= h(vehicleUrl($result['CODIOBRA'])) ?>">Seleccionar<span class="sr-only">
                                                <?= h($result['MATRICULA'] ?: $result['CODIOBRA']) ?></span></a>
                                    </li>
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
                        <a class="button button-secondary" href="<?= h(vehicleUrl($latest['CODIOBRA'])) ?>">Usar este
                            vehículo</a>
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
                            <thead>
                                <tr>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Código</th>
                                    <th scope="col">Modelo</th>
                                    <th scope="col">Matrícula</th>
                                    <th scope="col">Kilometraje</th>
                                    <th scope="col">Tipo de revisión</th>
                                    <th scope="col">Incidencia</th><?php if ($canDelete): ?>
                                        <th scope="col">Acciones</th><?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent as $entry): ?>
                                    <tr>
                                        <td><?= h(displayDate($entry['DIA'])) ?></td>
                                        <td><?= h($entry['CODIOBRA']) ?></td>
                                        <td><?= h($entry['MODEL'] ?? '—') ?></td>
                                        <td><?= h($entry['MATRICULA'] ?: '—') ?></td>
                                        <td><?= h($entry['QUILOMETRES']) ?> km</td>
                                        <td>
                                            <?= h($entry['REVIEW_NAME'] ?? '—') ?>
                                            <?php if (recordStage($entry) === 'checklist'): ?>
                                                <br><a href="<?= h(recordUrl($entry['IDVEHICLETREB'])) ?>">Continuar checklist</a>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= h($entry['INCIDENT_NAME'] ?? '—') ?></td>
                                        <?php if ($canDelete): ?>
                                            <td><a class="delete-link"
                                                    href="index.php?view=delete&amp;id=<?= (int) $entry['IDVEHICLETREB'] ?>">Eliminar<span
                                                        class="sr-only"> registro del <?= h(displayDate($entry['DIA'])) ?></span></a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
            <section class="card history" aria-labelledby="completed-title">
                <h2 id="completed-title">Últimos checklists completados</h2>
                <?php if (!$completed): ?>
                    <p class="muted">Aún no has completado ningún checklist.</p>
                <?php else: ?>
                    <div class="table-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th scope="col">Fecha</th>
                                    <th scope="col">Código</th>
                                    <th scope="col">Matrícula</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed as $entry): ?>
                                    <tr>
                                        <td><?= h(displayDate($entry['DIA'])) ?></td>
                                        <td><?= h($entry['CODIOBRA']) ?></td>
                                        <td><?= h($entry['MATRICULA'] ?: '—') ?></td>
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

function vehicleSummary(array $vehicle, bool $registered = false): void
{
    $itv = checklistDate($vehicle['NEXT_ITV']);
    ?>
            <section class="card" aria-label="Datos del vehículo">
                <h2>Datos del vehículo</h2>
                <dl>
                    <dt>Código</dt>
                    <dd><?= h($vehicle['CODIOBRA']) ?></dd>
                    <dt>Marca</dt>
                    <dd><?= h($vehicle['MARCA'] ?? '—') ?></dd>
                    <dt>Modelo</dt>
                    <dd><?= h($vehicle['MODEL'] ?? '—') ?></dd>
                    <dt>Matrícula</dt>
                    <dd><?= h($vehicle['MATRICULA'] ?: '—') ?></dd>
                    <dt>Próxima ITV</dt>
                    <dd><?= h(displayDate($itv)) ?>
                        <?php if ($itv !== null && $itv < date('Y-m-d')): ?><span
                                class="status warning">Caducada</span><?php endif; ?>
                    </dd>
                    <?php if ($registered): ?>
                        <dt>Tipo de revisión</dt>
                        <dd><?= h($vehicle['REVIEW_NAME'] ?? '—') ?></dd>
                        <dt>Kilometraje</dt>
                        <dd><?= h($vehicle['QUILOMETRES']) ?> km</dd>
                    <?php endif; ?>
                </dl>
            </section>
            <?php
}

function renderRegistration(array $worker, ?array $notice, array $vehicle, array $types, array $draft, string $submission): void
{
    layout('Registrar vehículo', $worker, $notice);
    pageHeading('Registrar vehículo', vehicleName($vehicle));
    ?>
            <div class="form-layout">
                <?php vehicleSummary($vehicle); ?>
                <section class="card">
                    <h2>Datos de la revisión</h2>
                    <form action="<?= h(vehicleUrl($vehicle['CODIOBRA'])) ?>" method="post" class="fields">
                        <?php csrf(); ?>
                        <input type="hidden" name="submission" value="<?= h($submission) ?>">
                        <label>Tipo de revisión
                            <select name="type"
                                required><?php options($types, 'IDTIPUSREVISIO', $draft['type'] ?? ''); ?></select>
                        </label>
                        <label>Kilometraje
                            <input name="mileage" type="number" inputmode="numeric" min="0" max="999999999"
                                value="<?= h($draft['mileage'] ?? '') ?>">
                            <span class="muted">Obligatorio salvo para registrar una incidencia.</span>
                        </label>
                        <label>Observaciones <span class="muted">(opcional)</span>
                            <textarea name="notes" rows="4"><?= h($draft['notes'] ?? '') ?></textarea>
                        </label>
                        <div class="actions">
                            <a class="button button-secondary" href="index.php">Cancelar</a>
                            <button class="button" name="action" value="create">Guardar y continuar</button>
                        </div>
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
            <div class="form-layout">
                <?php vehicleSummary($record, true); ?>
                <section class="card">
                    <form method="post" action="<?= h(recordUrl($record['IDVEHICLETREB'])) ?>" class="fields">
                        <?php csrf(); ?>
                        <label>Tipo de incidencia
                            <select name="type"
                                required><?php options($categories, 'IDVEHICLEINC', $draft['type']); ?></select>
                        </label>
                        <label>Descripción
                            <textarea name="notes" rows="5" required><?= h($draft['notes']) ?></textarea>
                        </label>
                        <div class="actions"><button class="button" name="action" value="incident">Guardar y
                                continuar</button></div>
                    </form>
                </section>
            </div>
            <?php
            endLayout();
}

function checklistQuestionGroups(array $questions): array
{
    $labels = [
        'Motor i líquids' => [
            ['Nivell d’oli', 'Nivel de aceite', 'Nivel de aceite del motor', 'Nivell oli'],
            ['Nivell d’aigua del radiador', 'Nivel de agua del radiador', 'Nivel del agua del radiador', 'Nivel de refrigerante'],
            ['Nivell líquid netejaparabrises', 'Nivel líquido limpiaparabrisas', 'Nivel de líquido limpiaparabrisas', 'Nivel líquido parabrisas', 'Nivell líquid neteja parabrises'],
            ['Estat de les escombretes', 'Estado de las escobillas', 'Estado escobillas parabrisas', 'Estado de las escobillas del limpiaparabrisas'],
        ],
        'Pneumàtics' => [
            ['Pressió de les rodes', 'Presión de las ruedas', 'Presión de los neumáticos'],
            ['Estat dels pneumàtics', 'Estado de los neumáticos', 'Estado neumáticos'],
        ],
        'Seguretat i equipament' => [
            ['Kit antipunxades / Roda de recanvi', 'Kit antipinchazos / Rueda de repuesto', 'Kit antipunxades', 'Roda de recanvi', 'Kit antipinchazos', 'Rueda de repuesto'],
            ['Estat dels frens', 'Estado de los frenos', 'Estado frenos'],
            ['Funcionament de les llums', 'Funcionamiento de las luces', 'Funcionamiento luces'],
            ['Hi ha triangles', 'Hay triángulos', 'Triángulos'],
            ['Hi ha armilla reflectant', 'Hay chaleco reflectante', 'Chaleco reflectante'],
            ['Hi ha extintor (2 kg)', 'Hay extintor (2 kg)', 'Extintor (2 kg)', 'Extintor 2 kg'],
        ],
        'Neteja' => [
            ['Cotxe net', 'Coche limpio', 'Vehicle net', 'Vehículo limpio'],
        ],
        'Farmaciola' => [
            ['Aigua oxigenada 250 ml', 'Agua oxigenada 250 ml'],
            ['Alcohol 250 ml', 'Alcohol 250 ml'],
        ],
    ];

    // Ignore punctuation and spacing in stored labels, including interleaved language separators.
    $normalize = static function (string $label): string {
        $label = strtr($label, [
            'à' => 'a', 'À' => 'a', 'á' => 'a', 'Á' => 'a', 'è' => 'e', 'È' => 'e', 'é' => 'e', 'É' => 'e',
            'í' => 'i', 'Í' => 'i', 'ï' => 'i', 'Ï' => 'i', 'ò' => 'o', 'Ò' => 'o', 'ó' => 'o', 'Ó' => 'o',
            'ú' => 'u', 'Ú' => 'u', 'ü' => 'u', 'Ü' => 'u', 'ç' => 'c', 'Ç' => 'c', 'ñ' => 'n', 'Ñ' => 'n',
        ]);
        return preg_replace('/[^a-z0-9]/', '', strtolower($label));
    };

    $groups = array_fill_keys(array_keys($labels), []);
    $groups['Altres elements'] = [];
    $lookup = [];
    foreach ($labels as $category => $items) {
        foreach ($items as $names) {
            [$catalan, $spanish] = $names;
            $display = [$category, $catalan, $spanish];
            foreach ($names as $name) {
                foreach ([$name, $catalan . $name, $name . $catalan, $spanish . $name, $name . $spanish] as $variant) {
                    $lookup[$normalize($variant)] = $display;
                }
            }
        }
    }

    foreach ($questions as $question) {
        $description = trim((string) $question['DESCRIPCION']);
        [$category, $catalan, $spanish] = $lookup[$normalize($description)] ?? ['Altres elements', '', ''];
        $groups[$category][] = [$question, $catalan, $spanish, $description];
    }

    return array_filter($groups);
}

function renderChecklist(
    array $worker,
    ?array $notice,
    array $record,
    array $questions,
    array $missing,
    array $reviewTypes,
    array $incidentTypes
): void {
    layout('Checklist del vehículo', $worker, $notice);
    $url = h(recordUrl($record['IDVEHICLETREB']));
    ?>
            <div class="checklist-page">
                <?php pageHeading('Checklist del vehículo', recordDescription($record)); ?>
                <section class="card review-details" aria-label="Datos de la revisión">
                    <form method="post" action="<?= $url ?>" class="fields">
                        <?php csrf(); ?>
                        <label>Tipo de revisión
                            <select name="type"
                                required><?php options($reviewTypes, 'IDTIPUSREVISIO', $record['IDTIPUSREVISIO']); ?></select>
                        </label>
                        <div class="actions"><button class="button button-secondary" name="action" value="review-type">Guardar
                                tipo de revisión</button></div>
                    </form>
                    <?php if ($incidentTypes): ?>
                        <form method="post" action="<?= $url ?>" class="fields">
                            <?php csrf(); ?>
                            <label>Tipo de incidencia
                                <select name="type"
                                    required><?php options($incidentTypes, 'IDVEHICLEINC', $record['IDVEHICLEINC']); ?></select>
                            </label>
                            <div class="actions"><button class="button button-secondary" name="action" value="incident-type">Guardar
                                    tipo de incidencia</button></div>
                        </form>
                    <?php endif; ?>
                </section>

                <?php if (!$questions): ?>
                    <form method="post" action="<?= $url ?>" class="actions">
                        <?php csrf(); ?>
                        <button class="button" name="action" value="prepare-checklist">Preparar checklist</button>
                    </form>
                <?php else: ?>
                    <?php
                    $today = date('Y-m-d');
                    $expiryLimit = date('Y-m-d', strtotime('+30 days'));
                    ?>
                    <section class="card checklist-card" lang="ca" aria-label="Preguntes del checklist">
                        <?php foreach (checklistQuestionGroups($questions) as $category => $items): ?>
                            <div class="question-group">
                                <h2 class="question-group-heading"><?= h($category) ?></h2>
                                <?php foreach ($items as [$question, $catalan, $spanish, $description]):
                                    $id = (int) $question['IDCHECKINGRESPOSTA'];
                                    $expiry = checklistDate($question['CADUCITAT']);
                                    $expiryStatus = $expiry === null ? '' : ($expiry < $today ? 'expired' : ($expiry <= $expiryLimit ? 'warning' : ''));
                                    $needsDate = $question['TIPOARTICULO'] === 'M';
                                    $incomplete = in_array($id, $missing, true);
                                    $answer = $question['RESPOSTA'] === null ? null : (int) $question['RESPOSTA'];
                                    ?>
                                    <div id="question-<?= $id ?>" class="question<?= $incomplete ? ' question-missing' : '' ?>"
                                         role="group" aria-labelledby="question-label-<?= $id ?>">
                                        <span class="question-label" id="question-label-<?= $id ?>">
                                            <?php if ($catalan !== ''): ?>
                                                <?= h($catalan) ?>
                                                <span class="question-translation" lang="es"><?= h($spanish) ?></span>
                                            <?php else: ?>
                                                <span lang="<?= $description === '' ? 'ca' : '' ?>"><?= h($description !== '' ? $description : 'Element de revisió') ?></span>
                                            <?php endif; ?>
                                        </span>
                                        <form method="post" action="<?= $url ?>" class="choices">
                                            <?php csrf(); ?>
                                            <input type="hidden" name="action" value="answer">
                                            <input type="hidden" name="question" value="<?= $id ?>">
                                            <button class="button button-secondary" name="answer" value="-1"
                                                    aria-pressed="<?= $answer !== null && $answer !== 0 ? 'true' : 'false' ?>"><span aria-hidden="true">✓</span> Sí</button>
                                            <button class="button button-secondary" name="answer" value="0"
                                                    aria-pressed="<?= $answer === 0 ? 'true' : 'false' ?>"><span aria-hidden="true">×</span> No</button>
                                        </form>
                                        <?php if ($needsDate): ?>
                                            <form method="post" action="<?= $url ?>" class="question-fields">
                                                <?php csrf(); ?>
                                                <input type="hidden" name="question" value="<?= $id ?>">
                                                <?php if ($expiry === null): ?>
                                                    <label>
                                                        <span class="sr-only">Data de caducitat</span>
                                                        <input type="date" name="expiry"
                                                            value="<?= h(checklistDate($question['PREVIOUS_EXPIRY'])) ?>" required>
                                                    </label>
                                                    <button class="button button-secondary" name="action" value="expiry">Desar la data</button>
                                                <?php else: ?>
                                                    <span class="question-expiry<?= $expiryStatus !== '' ? ' status ' . $expiryStatus : '' ?>">
                                                        <span class="sr-only">Caducitat</span>
                                                        <time datetime="<?= h($expiry) ?>"><?= h(displayDate($expiry)) ?></time>
                                                        <?php if ($expiryStatus !== ''): ?>
                                                            · <?= $expiryStatus === 'expired' ? 'Caducat' : 'Caduca aviat' ?>
                                                        <?php endif; ?>
                                                    </span>
                                                    <button class="button question-date-change" name="action" value="clear-expiry">Canviar la data</button>
                                                <?php endif; ?>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($incomplete): ?>
                                            <p class="field-error"><?= $answer === null ? 'Selecciona Sí o No.' : '' ?>
                                                <?= $needsDate && $expiry === null ? 'Indica la data de caducitat.' : '' ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if ((string) $record['COMENTARI'] === ''): ?>
                            <form method="post" action="<?= $url ?>" class="fields">
                                <?php csrf(); ?>
                                <label>Comentari <span class="muted">(opcional)</span><textarea name="comment"
                                        rows="3"></textarea></label>
                                <div class="actions"><button class="button button-secondary" name="action" value="comment">Desar el comentari</button></div>
                            </form>
                        <?php else: ?>
                            <div>
                                <h2>Comentari</h2>
                                <p class="record-comment"><?= h($record['COMENTARI']) ?></p>
                            </div>
                        <?php endif; ?>
                        <form method="post" action="<?= $url ?>" class="actions">
                            <?php csrf(); ?>
                            <button class="button" name="action" value="finish">Completar el checklist</button>
                        </form>
                    </section>
                <?php endif; ?>
            </div>
            <?php
            endLayout();
}

function renderPhotos(array $worker, ?array $notice, array $record): void
{
    layout('Fotos del vehículo', $worker, $notice);
    pageHeading('Fotos del vehículo', recordDescription($record));
    ?>
            <div class="form-layout">
                <?php vehicleSummary($record, true); ?>
                <section class="card">
                    <h2>Añadir fotos <span class="muted">(opcional)</span></h2>
                    <?php if ((int) $record['TEFOTOGRAFIES'] === -1): ?>
                        <p class="status">Este registro tiene fotos guardadas.</p><?php endif; ?>
                    <form method="post" action="<?= h(recordUrl($record['IDVEHICLETREB'])) ?>" enctype="multipart/form-data"
                        class="fields">
                        <?php csrf(); ?>
                        <label>Seleccionar foto
                            <input type="file" name="photo" accept="image/*,.heic,.heif" required
                                aria-describedby="photo-help">
                            <span class="muted" id="photo-help">Selecciona una foto de la cámara o de la galería y pulsa
                                Guardar foto. Hasta 10 MB.</span>
                        </label>
                        <div class="actions">
                            <button class="button button-secondary" name="action" value="photo">Guardar foto</button>
                        </div>
                    </form>
                    <form method="post" action="<?= h(recordUrl($record['IDVEHICLETREB'])) ?>" class="actions photo-finish">
                        <?php csrf(); ?>
                        <button class="button" name="action" value="finalize">Finalizar</button>
                    </form>
                </section>
            </div>
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
                <p>Gracias, <?= h($worker['name']) ?>.</p>
                <a class="button" href="index.php">Volver a vehículos</a>
            </section>
            <?php
            endLayout();
}

function renderDelete(array $worker, array $record): void
{
    layout('Eliminar registro', $worker);
    pageHeading('Eliminar registro', recordDescription($record));
    ?>
            <section class="card narrow">
                <p>¿Quieres eliminar definitivamente el registro del <?= h(displayDate($record['DIA'])) ?>?</p>
                <form method="post" action="index.php?view=delete&amp;id=<?= (int) $record['IDVEHICLETREB'] ?>"
                    class="actions">
                    <?php csrf(); ?>
                    <a class="button button-secondary" href="index.php">Cancelar</a>
                    <button class="button button-danger" name="action" value="delete">Eliminar registro</button>
                </form>
            </section>
            <?php
            endLayout();
}

function renderUnavailable(?array $worker, ?string $registrationError = null): void
{
    layout('Servicio no disponible', $worker);
    ?>
            <section class="card narrow">
                <?php if ($registrationError !== null): ?>
                    <h1>No se pudo mostrar el resultado del registro</h1>
                    <p>Consulta los registros recientes antes de volver a guardarlo.</p>
                    <p class="muted">Referencia del error <?= h($registrationError) ?></p>
                <?php else: ?>
                    <h1>Servicio no disponible</h1>
                    <p>El registro de vehículos no está disponible en este momento.</p>
                <?php endif; ?>
                <a class="button button-secondary" href="index.php">Volver al inicio</a>
            </section>
            <?php
            endLayout();
}
