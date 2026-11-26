<?php

const INCIDENT_REVIEW = 3;
const DIRECT_REVIEW = 5;

function findWorker(PDO $db, string $username): ?array
{
    $worker = query($db, '
        SELECT t.*, CASE WHEN EXISTS (
            SELECT 1 FROM SUPERVISORS s
            WHERE s.CODIEMPRESA = t.CODIEMPRESA AND s.CODITREB = t.CODITREBALLADOR
        ) THEN 1 ELSE 0 END AS IS_SUPERVISOR
        FROM TREBALLADORS t WHERE t.WEBNOMUSUARI = ?', [$username])->fetch();
    return $worker ?: null;
}

function sameWorker(array $account, array $worker): bool
{
    return (int) $account['company'] === (int) $worker['company']
        && (string) $account['employee_company'] === (string) $worker['employee_company']
        && (string) $account['employee'] === (string) $worker['employee'];
}

function companyWorkers(PDO $db, array $worker): array
{
    return query(
        $db,
        'SELECT CODITREBALLADOR, EMPRESATREB, NOMTREBALLADOR
                       FROM TREBALLADORS WHERE CODIEMPRESA = ? ORDER BY NOMTREBALLADOR',
        [$worker['source_company'] ?? $worker['company']]
    )->fetchAll();
}

function recentRecords(PDO $db, array $worker, bool $completedOnly = false): array
{
    $filter = $completedOnly ? ' AND r.FINALCHECKLIST = -1' : '';
    return query(
        $db,
        '
        SELECT r.*, v.MARCA, v.MODEL, v.MATRICULA, v.DESCRIPCIOOBRA,
               y.DESCRIPCION AS REVIEW_NAME, i.DESCRIPCION AS INCIDENT_NAME
        FROM VEHICLESTREBS r
        LEFT JOIN OBRES v ON v.CODIEMPRESA = r.CODIEMPRESA AND v.CODIOBRA = r.CODIOBRA
        LEFT JOIN CHECKINGVEHICLESTIPUSREVISIO y ON y.IDTIPUSREVISIO = r.IDTIPUSREVISIO
        LEFT JOIN CHECKINGVEHICLESTIPUSINCIDENCIA i ON i.IDVEHICLEINC = r.IDVEHICLEINC
        WHERE r.EMPRESATREB = ? AND r.CODITREB = ? AND r.CODIEMPRESA = ?'
        . $filter . ' ORDER BY r.DIA DESC, r.IDVEHICLETREB DESC LIMIT 5',
        [$worker['employee_company'], $worker['employee'], $worker['company']]
    )->fetchAll();
}

function searchVehicles(PDO $db, int $company, string $search): array
{
    $pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    return query($db, "
        SELECT CODIOBRA, MARCA, MODEL, MATRICULA, DESCRIPCIOOBRA FROM OBRES
        WHERE CODIEMPRESA = ? AND TIPOPROYECTOADOBY = '003'
        AND (MATRICULA LIKE ? ESCAPE '!' OR CODIOBRA LIKE ? ESCAPE '!' OR DESCRIPCIOOBRA LIKE ? ESCAPE '!')
        ORDER BY MATRICULA, CODIOBRA", [$company, $pattern, $pattern, $pattern])->fetchAll();
}

function findVehicle(PDO $db, int $company, string $code): ?array
{
    $vehicle = query(
        $db,
        "
        SELECT v.*, (SELECT MAX(t.PROXIMITV) FROM ITV t
            WHERE t.CODIEMPRESA = v.CODIEMPRESA AND t.CODIOBRA = v.CODIOBRA) AS NEXT_ITV
        FROM OBRES v WHERE v.CODIEMPRESA = ? AND v.CODIOBRA = ?",
        [$company, $code]
    )->fetch();
    return $vehicle ?: null;
}

function reviewTypes(PDO $db, array $worker): array
{
    $filter = $worker['supervisor'] ? '' : ' WHERE ESSUPERVISOR = 0';
    return query($db, 'SELECT IDTIPUSREVISIO, DESCRIPCION FROM CHECKINGVEHICLESTIPUSREVISIO'
        . $filter . ' ORDER BY DESCRIPCION')->fetchAll();
}

function incidentTypes(PDO $db): array
{
    return query($db, 'SELECT IDVEHICLEINC, DESCRIPCION FROM CHECKINGVEHICLESTIPUSINCIDENCIA ORDER BY DESCRIPCION')->fetchAll();
}

function recordStage(array $record): string
{
    if ((int) $record['IDTIPUSREVISIO'] === DIRECT_REVIEW) {
        return 'done';
    }
    if ((int) $record['IDTIPUSREVISIO'] === INCIDENT_REVIEW) {
        return (int) $record['IDVEHICLEINC'] > 0 ? 'photos' : 'incident';
    }
    return (int) $record['FINALCHECKLIST'] === -1 ? 'photos' : 'checklist';
}

function findRecord(PDO $db, array $worker, int $id): ?array
{
    $record = query(
        $db,
        '
        SELECT r.*, v.MARCA, v.MODEL, v.MATRICULA, v.DESCRIPCIOOBRA,
               y.DESCRIPCION AS REVIEW_NAME, i.DESCRIPCION AS INCIDENT_NAME,
               (SELECT MAX(t.PROXIMITV) FROM ITV t
                WHERE t.CODIEMPRESA = r.CODIEMPRESA AND t.CODIOBRA = r.CODIOBRA) AS NEXT_ITV
        FROM VEHICLESTREBS r
        LEFT JOIN OBRES v ON v.CODIEMPRESA = r.CODIEMPRESA AND v.CODIOBRA = r.CODIOBRA
        LEFT JOIN CHECKINGVEHICLESTIPUSREVISIO y ON y.IDTIPUSREVISIO = r.IDTIPUSREVISIO
        LEFT JOIN CHECKINGVEHICLESTIPUSINCIDENCIA i ON i.IDVEHICLEINC = r.IDVEHICLEINC
        WHERE r.IDVEHICLETREB = ? AND r.EMPRESATREB = ? AND r.CODITREB = ? AND r.CODIEMPRESA = ?',
        [$id, $worker['employee_company'], $worker['employee'], $worker['company']]
    )->fetch();
    return $record ?: null;
}

function deleteOwnRecord(PDO $db, array $account, int $id): bool
{
    return query(
        $db,
        'DELETE FROM VEHICLESTREBS
                       WHERE IDVEHICLETREB = ? AND EMPRESATREB = ? AND CODITREB = ? AND CODIEMPRESA = ?',
        [$id, $account['employee_company'], $account['employee'], $account['company']]
    )->rowCount() === 1;
}

function createRecord(PDO $db, array $worker, array $vehicle, int $type, int $mileage, string $notes): int
{
    $db->beginTransaction();
    try {
        query(
            $db,
            '
            INSERT INTO VEHICLESTREBS (EMPRESATREB, CODITREB, CODIOBRA, CODIEMPRESA, OBSERVACIONS,
                                       IDTIPUSREVISIO, QUILOMETRES)
            VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $worker['employee_company'],
                $worker['employee'],
                $vehicle['CODIOBRA'],
                $worker['company'],
                $notes === '' ? null : $notes,
                $type,
                $mileage
            ]
        );
        $id = (int) $db->lastInsertId();
        if ($id < 1) {
            throw new RuntimeException('No fleet record ID returned.');
        }
        if (!in_array($type, [INCIDENT_REVIEW, DIRECT_REVIEW], true)) {
            seedChecklist($db, $vehicle, $id);
        }
        $db->commit();
        return $id;
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function previousChecklistId(PDO $db, array $vehicle, int $id): int
{
    return (int) query(
        $db,
        '
        SELECT MAX(r.IDVEHICLETREB) FROM VEHICLESTREBS r
        WHERE r.CODIEMPRESA = ? AND r.CODIOBRA = ? AND r.IDVEHICLETREB < ?
          AND r.FINALCHECKLIST = -1
          AND EXISTS (SELECT 1 FROM CHECKINGVEHICLESRESPOSTES a WHERE a.IDVEHICLETREB = r.IDVEHICLETREB)',
        [$vehicle['CODIEMPRESA'], $vehicle['CODIOBRA'], $id]
    )->fetchColumn();
}

function seedChecklist(PDO $db, array $vehicle, int $id): void
{
    $previous = previousChecklistId($db, $vehicle, $id);
    $questions = query(
        $db,
        '
        SELECT m.*, p.ORDEN AS QUESTION_ORDER, p.TIPUSCHECKING AS QUESTION_TYPE, history.CADUCITAT
        FROM CHECKINGVEHICLESTIPMAQ m
        LEFT JOIN CHECKINGVEHICLESPREGUNTES p ON p.IDVEHICLECHECKING = m.IDVEHICLECHECKING
        LEFT JOIN (
            SELECT IDVEHICLECHECKING, MAX(CADUCITAT) AS CADUCITAT
            FROM CHECKINGVEHICLESRESPOSTES
            WHERE IDVEHICLETREB = ?
            GROUP BY IDVEHICLECHECKING
        ) history ON history.IDVEHICLECHECKING = m.IDVEHICLECHECKING
        WHERE m.CODIGOTIPOMAQUINA = ?',
        [$previous, $vehicle['IDTIPUSVEHICLE']]
    )->fetchAll();
    if ((int) $vehicle['TIPUSUS'] === 999) {
        $extra = query($db, 'SELECT IDVEHICLECHECKING, ORDEN, TIPUSCHECKING, NULL AS CADUCITAT
                            FROM CHECKINGVEHICLESPREGUNTES WHERE TIPUSCHECKING = 2')->fetchAll();
        $questions = array_merge($questions, $extra);
    }

    $rows = [];
    foreach ($questions as $question) {
        $questionId = $question['IDVEHICLECHECKING'];
        if (!isset($rows[$questionId])) {
            $order = $question['ORDEN'] ?? $question['QUESTION_ORDER'] ?? null;
            $type = $question['TIPUSCHECKING'] ?? $question['QUESTION_TYPE'] ?? null;
            $expiry = checklistDate($question['CADUCITAT']);
            $rows[$questionId] = [$id, $order, $type, $questionId, $expiry];
        }
    }
    if (!$rows) {
        throw new DomainException('Este vehículo no tiene preguntas de revisión configuradas.');
    }

    $placeholders = implode(', ', array_fill(0, count($rows), '(?, ?, ?, ?, ?)'));
    query(
        $db,
        'INSERT INTO CHECKINGVEHICLESRESPOSTES
         (IDVEHICLETREB, ORDRE, TIPUSCHECKING, IDVEHICLECHECKING, CADUCITAT) VALUES ' . $placeholders,
        array_merge(...array_values($rows))
    );
}

function checklist(PDO $db, array $record): array
{
    $id = (int) $record['IDVEHICLETREB'];
    $previous = previousChecklistId($db, $record, $id);

    return query(
        $db,
        '
        SELECT a.IDCHECKINGRESPOSTA, a.RESPOSTA, a.CADUCITAT, q.DESCRIPCION, q.TIPOARTICULO,
               history.CADUCITAT AS PREVIOUS_EXPIRY
        FROM CHECKINGVEHICLESRESPOSTES a
        LEFT JOIN CHECKINGVEHICLESPREGUNTES q ON q.IDVEHICLECHECKING = a.IDVEHICLECHECKING
        LEFT JOIN (
            SELECT IDVEHICLECHECKING, MAX(CADUCITAT) AS CADUCITAT
            FROM CHECKINGVEHICLESRESPOSTES
            WHERE IDVEHICLETREB = ?
            GROUP BY IDVEHICLECHECKING
        ) history ON history.IDVEHICLECHECKING = a.IDVEHICLECHECKING
        WHERE a.IDVEHICLETREB = ?
        ORDER BY a.TIPUSCHECKING, a.ORDRE, a.IDCHECKINGRESPOSTA',
        [$previous, $id]
    )->fetchAll();
}

function updateChecklist(PDO $db, array $record, string $action, array $input): array
{
    $id = (int) $record['IDVEHICLETREB'];
    $db->beginTransaction();
    try {
        $current = query($db, 'SELECT * FROM VEHICLESTREBS WHERE IDVEHICLETREB = ? FOR UPDATE', [$id])->fetch();
        if (!$current || recordStage($current) !== 'checklist') {
            throw new DomainException('El checklist ya está cerrado.');
        }

        $missing = [];
        if ($action === 'prepare-checklist') {
            $hasQuestions = query($db, 'SELECT 1 FROM CHECKINGVEHICLESRESPOSTES WHERE IDVEHICLETREB = ? LIMIT 1', [$id])->fetchColumn();
            if (!$hasQuestions) {
                $vehicle = findVehicle($db, (int) $current['CODIEMPRESA'], $current['CODIOBRA']);
                if (!$vehicle) {
                    throw new DomainException('No se ha encontrado el vehículo de este registro.');
                }
                seedChecklist($db, $vehicle, $id);
            }
        } elseif ($action === 'finish') {
            $questions = checklist($db, $current);
            if (!$questions) {
                throw new DomainException('Este registro no tiene preguntas de revisión configuradas.');
            }
            foreach ($questions as $question) {
                if ($question['RESPOSTA'] === null || ($question['TIPOARTICULO'] === 'M' && !checklistDate($question['CADUCITAT']))) {
                    $missing[] = (int) $question['IDCHECKINGRESPOSTA'];
                }
            }
            if (!$missing) {
                query($db, 'UPDATE VEHICLESTREBS SET FINALCHECKLIST = -1 WHERE IDVEHICLETREB = ?', [$id]);
            }
        } elseif ($action === 'comment') {
            query($db, "UPDATE VEHICLESTREBS SET COMENTARI = ? WHERE IDVEHICLETREB = ?
                        AND (COMENTARI IS NULL OR COMENTARI = '')", [$input['comment'], $id]);
        } else {
            $question = query($db, '
                SELECT a.IDCHECKINGRESPOSTA, q.TIPOARTICULO
                FROM CHECKINGVEHICLESRESPOSTES a
                LEFT JOIN CHECKINGVEHICLESPREGUNTES q ON q.IDVEHICLECHECKING = a.IDVEHICLECHECKING
                WHERE a.IDCHECKINGRESPOSTA = ? AND a.IDVEHICLETREB = ?', [$input['question'], $id])->fetch();
            if (!$question) {
                throw new DomainException('La pregunta no pertenece a este checklist.');
            }

            if ($action === 'answer') {
                if (!in_array($input['answer'], ['-1', '0'], true)) {
                    throw new DomainException('Selecciona Sí o No.');
                }
                query(
                    $db,
                    'UPDATE CHECKINGVEHICLESRESPOSTES SET RESPOSTA = ?
                            WHERE IDCHECKINGRESPOSTA = ? AND IDVEHICLETREB = ?',
                    [(int) $input['answer'], $input['question'], $id]
                );
            } else {
                if ($question['TIPOARTICULO'] !== 'M') {
                    throw new DomainException('Esta pregunta no tiene fecha de caducidad.');
                }
                $date = $action === 'expiry' && validDate($input['expiry']) ? $input['expiry'] : null;
                if ($action === 'expiry' && $date === null) {
                    throw new DomainException('Introduce una fecha de caducidad válida.');
                }
                query($db, 'UPDATE CHECKINGVEHICLESRESPOSTES SET CADUCITAT = ?
                            WHERE IDCHECKINGRESPOSTA = ? AND IDVEHICLETREB = ?', [$date, $input['question'], $id]);
            }
        }
        $db->commit();
        return $missing;
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        throw $error;
    }
}

function checklistDate($value): ?string
{
    $date = substr((string) $value, 0, 10);
    return validDate($date) ? $date : null;
}

function validDate(string $value): bool
{
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $date !== false && $date->format('Y-m-d') === $value && substr($value, 0, 4) !== '0000';
}

function savePhoto(PDO $db, array $worker, array $record, array $upload): void
{
    $limit = 10 * 1024 * 1024;
    if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ($upload['size'] ?? 0) > $limit) {
        throw new DomainException('Selecciona una imagen de hasta 10 MB y vuelve a intentarlo.');
    }
    $temp = $upload['tmp_name'] ?? '';
    $info = is_uploaded_file($temp) ? @getimagesize($temp) : false;
    $extensions = [
        'image/jpeg' => 'JPG',
        'image/png' => 'PNG',
        'image/webp' => 'WEBP',
        'image/gif' => 'GIF',
        'image/bmp' => 'BMP',
        'image/tiff' => 'TIFF',
        'image/avif' => 'AVIF',
        'image/heic' => 'HEIC',
        'image/heif' => 'HEIF',
        'image/heic-sequence' => 'HEIC',
        'image/heif-sequence' => 'HEIF',
    ];
    $mime = $info['mime'] ?? null;
    if ($mime === null && is_uploaded_file($temp) && class_exists(finfo::class)) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temp);
    }
    $extension = $extensions[$mime] ?? null;
    if ($extension === null) {
        throw new DomainException('Selecciona un archivo de imagen compatible.');
    }

    $label = preg_replace('/[^A-Za-z0-9_.-]/', '_', $worker['username'] . '-' . ($record['MATRICULA'] ?? $record['CODIOBRA']));
    $filename = date('YmdHis') . '-' . substr($label, 0, 100) . '-' . $record['IDVEHICLETREB']
        . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
    $path = dirname(__DIR__) . '/fotos/' . $filename;
    $stored = false;
    $db->beginTransaction();
    try {
        $current = query(
            $db,
            'SELECT * FROM VEHICLESTREBS WHERE IDVEHICLETREB = ? FOR UPDATE',
            [$record['IDVEHICLETREB']]
        )->fetch();
        if (!$current || recordStage($current) !== 'photos') {
            throw new DomainException('El registro ya no admite fotos.');
        }
        if (!move_uploaded_file($temp, $path)) {
            throw new RuntimeException('Fleet photo could not be stored.');
        }
        $stored = true;
        query($db, 'UPDATE VEHICLESTREBS SET TEFOTOGRAFIES = -1 WHERE IDVEHICLETREB = ?', [$record['IDVEHICLETREB']]);
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        if ($stored) {
            unlink($path);
        }
        throw $error;
    }
}
