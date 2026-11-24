<?php

if (!defined('WEBVEHICLES_ENTRY')) {
    http_response_code(404);
    exit;
}

require __DIR__ . '/database.php';
require __DIR__ . '/fleet.php';
require __DIR__ . '/views.php';

session_name('webvehicles_new');
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$worker = $_SESSION['worker'] ?? null;
$notice = $_SESSION['notice'] ?? null;
unset($_SESSION['notice']);
$username = '';

function field(array $source, string $name): string
{
    return is_string($source[$name] ?? null) ? trim($source[$name]) : '';
}

function go(string $location, ?string $message = null, string $tone = 'success'): void
{
    if ($message !== null) {
        $_SESSION['notice'] = ['text' => $message, 'tone' => $tone];
    }
    header('Location: ' . $location, true, 303);
    exit;
}

function choiceExists(array $options, string $column, int $id): bool
{
    return $id > 0 && in_array($id, array_map('intval', array_column($options, $column)), true);
}

try {
    $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
    $action = field($_POST, 'action');
    $page = field($_GET, 'view');
    $recordId = max(0, (int) field($_GET, 'id'));
    $vehicleCode = field($_GET, 'vehicle');

    if ($isPost && !hash_equals($_SESSION['csrf'], field($_POST, 'csrf'))) {
        go('index.php', 'La sesión del formulario ha caducado. Vuelve a intentarlo.', 'error');
    }
    if ($isPost && $action === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        go('index.php');
    }

    if (!$worker) {
        if ($isPost && $action === 'login') {
            $username = field($_POST, 'username');
            $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
            $user = $username !== '' && $password !== '' ? findWorker(database(), $username) : null;
            if ($user && isset($user['WEBPASSWORD']) && (string) $user['WEBPASSWORD'] !== '' && hash_equals((string) $user['WEBPASSWORD'], $password)) {
                session_regenerate_id(true);
                $_SESSION = [
                    'csrf' => bin2hex(random_bytes(32)),
                    'worker' => [
                        'username' => $user['WEBNOMUSUARI'],
                        'name' => $user['NOMTREBALLADOR'] ?: $user['WEBNOMUSUARI'],
                        'company_name' => $user['NOMEMPRESA'],
                        'company' => (int) $user['CODIEMPRESA'] === 400 ? 6 : (int) $user['CODIEMPRESA'],
                        'source_company' => (int) $user['CODIEMPRESA'],
                        'employee_company' => $user['EMPRESATREB'],
                        'employee' => $user['CODITREBALLADOR'],
                        'supervisor' => (bool) $user['IS_SUPERVISOR'],
                    ],
                ];
                $_SESSION['account'] = $_SESSION['worker'];
                go('index.php');
            }
            $notice = ['text' => 'El usuario o la contraseña son incorrectos.', 'tone' => 'error'];
        }
        renderLogin($notice, $username);
        exit;
    }

    $db = database();
    if ($page === 'workers') {
        $account = $_SESSION['account'] ?? $worker;
        if (!$account['supervisor']) {
            go('index.php', 'El cambio de trabajador está reservado a supervisores.', 'error');
        }
        $workers = companyWorkers($db, $account);
        if ($isPost && $action === 'switch-worker') {
            foreach ($workers as $candidate) {
                if ((string) $candidate['CODITREBALLADOR'] !== field($_POST, 'employee')) {
                    continue;
                }
                $_SESSION['account'] = $account;
                $_SESSION['worker'] = array_replace($account, [
                    'employee' => $candidate['CODITREBALLADOR'],
                    'employee_company' => $candidate['EMPRESATREB'],
                    'name' => $candidate['NOMTREBALLADOR'] ?: $account['username'],
                ]);
                unset($_SESSION['registrations'], $_SESSION['finished_records']);
                go('index.php');
            }
            $notice = ['text' => 'Selecciona un trabajador de tu empresa.', 'tone' => 'error'];
        }
        renderWorkers($worker, $notice, $workers);
        exit;
    }

    if ($page === 'register') {
        $vehicle = findVehicle($db, $worker['company'], $vehicleCode);
        if (!$vehicle) {
            go('index.php', 'No se ha encontrado ese vehículo en tu empresa.', 'error');
        }
        $types = reviewTypes($db, $worker);
        $draft = [];
        $submission = bin2hex(random_bytes(16));
        if ($isPost && $action === 'create') {
            $submission = field($_POST, 'submission');
            if (!preg_match('/^[a-f0-9]{32}$/D', $submission)) {
                go(vehicleUrl($vehicle['CODIOBRA']), 'Vuelve a enviar el formulario de registro.', 'error');
            }
            $existing = $_SESSION['registrations'][$submission] ?? null;
            if ($existing !== null) {
                go(recordUrl($existing));
            }
            $draft = [
                'type' => field($_POST, 'type'),
                'mileage' => field($_POST, 'mileage'),
                'notes' => field($_POST, 'notes'),
            ];
            try {
                $type = (int) $draft['type'];
                if (!choiceExists($types, 'IDTIPUSREVISIO', $type)) {
                    throw new DomainException('Selecciona un tipo de revisión disponible.');
                }
                $mileage = $draft['mileage'] === '' ? '0' : $draft['mileage'];
                if (!ctype_digit($mileage) || strlen($mileage) > 9 || ($type !== INCIDENT_REVIEW && (int) $mileage < 1)) {
                    throw new DomainException('Introduce un kilometraje válido. Es obligatorio para este tipo de revisión, salvo incidencias.');
                }
                $recordId = createRecord($db, $worker, $vehicle, $type, (int) $mileage, $draft['notes']);
                $_SESSION['registrations'][$submission] = $recordId;
                $_SESSION['registrations'] = array_slice($_SESSION['registrations'], -20, null, true);
                go(recordUrl($recordId));
            } catch (DomainException $error) {
                $notice = ['text' => $error->getMessage(), 'tone' => 'error'];
            }
        }
        renderRegistration($worker, $notice, $vehicle, $types, $draft, $submission);
        exit;
    }

    if ($page === 'delete') {
        $account = $_SESSION['account'] ?? $worker;
        if (!sameWorker($account, $worker)) {
            go('index.php', 'Solo puedes eliminar registros de tu propio usuario.', 'error');
        }
        $record = findRecord($db, $account, $recordId);
        if (!$record) {
            go('index.php', 'No se ha encontrado ese registro.', 'error');
        }
        if ($isPost && $action === 'delete') {
            try {
                $deleted = deleteOwnRecord($db, $account, $recordId);
            } catch (PDOException $error) {
                if ((int) ($error->errorInfo[1] ?? 0) !== 1451) {
                    throw $error;
                }
                go('index.php', 'No se puede eliminar ese registro porque tiene datos vinculados.', 'error');
            }
            if ($deleted) {
                unset($_SESSION['finished_records'][$recordId]);
                go('index.php', 'Registro eliminado.');
            }
            go('index.php', 'No se ha encontrado ese registro.', 'error');
        }
        renderDelete($worker, $record);
        exit;
    }

    if (in_array($page, ['record', 'done'], true)) {
        $record = findRecord($db, $worker, $recordId);
        if (!$record) {
            go('index.php', 'No se ha encontrado ese registro.', 'error');
        }
        $url = recordUrl($recordId);
        $stage = recordStage($record);
        if ($stage === 'photos' && isset($_SESSION['finished_records'][$recordId])) {
            $stage = 'done';
        }
        $incidentDraft = ['type' => '', 'notes' => $record['OBSERVACIONS'] ?? ''];
        $missing = [];
        $categories = $stage === 'incident' ? incidentTypes($db) : [];

        if ($isPost) {
            try {
                if ($stage === 'incident' && $action === 'incident') {
                    $incidentDraft = ['type' => field($_POST, 'type'), 'notes' => field($_POST, 'notes')];
                    $type = (int) $incidentDraft['type'];
                    if (!choiceExists($categories, 'IDVEHICLEINC', $type) || $incidentDraft['notes'] === '') {
                        throw new DomainException('Selecciona un tipo de incidencia y describe lo ocurrido.');
                    }
                    query($db, 'UPDATE VEHICLESTREBS SET IDVEHICLEINC = ?, OBSERVACIONS = ? WHERE IDVEHICLETREB = ?',
                        [$type, $incidentDraft['notes'], $recordId]);
                    go($url, 'Incidencia guardada.');
                }
                if ($stage === 'checklist' && in_array($action, ['answer', 'expiry', 'clear-expiry', 'comment', 'finish'], true)) {
                    $missing = updateChecklist($db, $record, $action, [
                        'question' => (int) field($_POST, 'question'),
                        'answer' => field($_POST, 'answer'),
                        'expiry' => field($_POST, 'expiry'),
                        'comment' => field($_POST, 'comment'),
                    ]);
                    if (!$missing) {
                        go($url, $action === 'finish' ? 'Checklist completado.' : null);
                    }
                    $notice = ['text' => 'Completa los campos marcados para continuar.', 'tone' => 'error'];
                }
                if ($stage === 'checklist' && $action === 'review-type') {
                    $type = (int) field($_POST, 'type');
                    if (!choiceExists(reviewTypes($db, $worker), 'IDTIPUSREVISIO', $type)) {
                        throw new DomainException('Selecciona un tipo de revisión disponible.');
                    }
                    query($db, 'UPDATE VEHICLESTREBS SET IDTIPUSREVISIO = ? WHERE IDVEHICLETREB = ?
                                AND (FINALCHECKLIST IS NULL OR FINALCHECKLIST <> -1)', [$type, $recordId]);
                    go($url, 'Tipo de revisión guardado.');
                }
                if ($stage === 'checklist' && $action === 'incident-type') {
                    $type = (int) field($_POST, 'type');
                    if (!choiceExists(incidentTypes($db), 'IDVEHICLEINC', $type)) {
                        throw new DomainException('Selecciona un tipo de incidencia disponible.');
                    }
                    query($db, 'UPDATE VEHICLESTREBS SET IDVEHICLEINC = ? WHERE IDVEHICLETREB = ?
                                AND (FINALCHECKLIST IS NULL OR FINALCHECKLIST <> -1)', [$type, $recordId]);
                    go($url, 'Tipo de incidencia guardado.');
                }
                if ($stage === 'photos' && $action === 'photo') {
                    savePhoto($db, $worker, $record, $_FILES['photo'] ?? []);
                    go($url, 'Foto guardada.');
                }
                if ($stage === 'photos' && $action === 'finalize') {
                    $_SESSION['finished_records'][$recordId] = true;
                    go('index.php?view=done&id=' . $recordId);
                }
            } catch (DomainException $error) {
                $notice = ['text' => $error->getMessage(), 'tone' => 'error'];
            }
        }

        if ($stage === 'done' || ($page === 'done' && $stage === 'photos')) {
            renderDone($worker, $notice, $record);
        } elseif ($stage === 'incident') {
            renderIncident($worker, $notice, $record, $categories, $incidentDraft);
        } elseif ($stage === 'checklist') {
            renderChecklist($worker, $notice, $record, checklist($db, $record), $missing,
                reviewTypes($db, $worker), incidentTypes($db));
        } else {
            renderPhotos($worker, $notice, $record);
        }
        exit;
    }

    $search = field($_GET, 'q');
    renderDashboard($worker, $notice, recentRecords($db, $worker), recentRecords($db, $worker, true), $search,
        $search === '' ? [] : searchVehicles($db, $worker['company'], substr($search, 0, 120)),
        sameWorker($_SESSION['account'] ?? $worker, $worker));
} catch (Throwable $error) {
    error_log('webvehicles/new: ' . get_class($error) . ' (' . $error->getCode() . ')');
    http_response_code(503);
    renderUnavailable($worker);
}
