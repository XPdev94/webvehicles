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

function recentRecords(PDO $db, array $worker, bool $completedOnly = false): array
{
    $filter = $completedOnly ? ' AND r.FINALCHECKLIST = -1' : '';
    return query($db, '
        SELECT r.*, v.MARCA, v.MODEL, v.MATRICULA, v.DESCRIPCIOOBRA,
               y.DESCRIPCION AS REVIEW_NAME, i.DESCRIPCION AS INCIDENT_NAME
        FROM VEHICLESTREBS r
        LEFT JOIN OBRES v ON v.CODIEMPRESA = r.CODIEMPRESA AND v.CODIOBRA = r.CODIOBRA
        LEFT JOIN CHECKINGVEHICLESTIPUSREVISIO y ON y.IDTIPUSREVISIO = r.IDTIPUSREVISIO
        LEFT JOIN CHECKINGVEHICLESTIPUSINCIDENCIA i ON i.IDVEHICLEINC = r.IDVEHICLEINC
        WHERE r.EMPRESATREB = ? AND r.CODITREB = ? AND r.CODIEMPRESA = ?'
        . $filter . ' ORDER BY r.DIA DESC, r.IDVEHICLETREB DESC LIMIT 5',
        [$worker['employee_company'], $worker['employee'], $worker['company']])->fetchAll();
}

function searchVehicles(PDO $db, int $company, string $search): array
{
    $pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    return query($db, "
        SELECT CODIOBRA, MARCA, MODEL, MATRICULA, DESCRIPCIOOBRA FROM OBRES
        WHERE CODIEMPRESA = ? AND TIPOPROYECTOADOBY = '003'
        AND (MATRICULA LIKE ? ESCAPE '!' OR CODIOBRA LIKE ? ESCAPE '!' OR DESCRIPCIOOBRA LIKE ? ESCAPE '!')
        ORDER BY MATRICULA, CODIOBRA LIMIT 30", [$company, $pattern, $pattern, $pattern])->fetchAll();
}

function findVehicle(PDO $db, int $company, string $code): ?array
{
    $vehicle = query($db, "
        SELECT v.*, (SELECT MAX(t.PROXIMITV) FROM ITV t
            WHERE t.CODIEMPRESA = v.CODIEMPRESA AND t.CODIOBRA = v.CODIOBRA) AS NEXT_ITV
        FROM OBRES v WHERE v.CODIEMPRESA = ? AND v.CODIOBRA = ? AND v.TIPOPROYECTOADOBY = '003'",
        [$company, $code])->fetch();
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
    $record = query($db, '
        SELECT r.*, v.MARCA, v.MODEL, v.MATRICULA, y.DESCRIPCION AS REVIEW_NAME
        FROM VEHICLESTREBS r
        LEFT JOIN OBRES v ON v.CODIEMPRESA = r.CODIEMPRESA AND v.CODIOBRA = r.CODIOBRA
        LEFT JOIN CHECKINGVEHICLESTIPUSREVISIO y ON y.IDTIPUSREVISIO = r.IDTIPUSREVISIO
        WHERE r.IDVEHICLETREB = ? AND r.EMPRESATREB = ? AND r.CODITREB = ? AND r.CODIEMPRESA = ?',
        [$id, $worker['employee_company'], $worker['employee'], $worker['company']])->fetch();
    return $record ?: null;
}

function createRecord(PDO $db, array $worker, array $vehicle, int $type, int $mileage, string $notes): int
{
    $db->beginTransaction();
    try {
        query($db, '
            INSERT INTO VEHICLESTREBS (EMPRESATREB, CODITREB, CODIOBRA, CODIEMPRESA, OBSERVACIONS,
                                       IDTIPUSREVISIO, QUILOMETRES)
            VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$worker['employee_company'], $worker['employee'], $vehicle['CODIOBRA'], $worker['company'],
             $notes === '' ? null : $notes, $type, $mileage]);
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
        $db->rollBack();
        throw $error;
    }
}

function seedChecklist(PDO $db, array $vehicle, int $id): void
{
    $previous = query($db, '
        SELECT MAX(r.IDVEHICLETREB) FROM VEHICLESTREBS r
        WHERE r.CODIEMPRESA = ? AND r.CODIOBRA = ? AND r.IDVEHICLETREB < ?
          AND r.FINALCHECKLIST = -1
          AND EXISTS (SELECT 1 FROM CHECKINGVEHICLESRESPOSTES a WHERE a.IDVEHICLETREB = r.IDVEHICLETREB)',
        [$vehicle['CODIEMPRESA'], $vehicle['CODIOBRA'], $id])->fetchColumn();
    $questions = query($db, '
        SELECT q.IDVEHICLECHECKING, q.ORDEN, q.TIPUSCHECKING, MAX(a.CADUCITAT) AS CADUCITAT
        FROM CHECKINGVEHICLESTIPMAQ q
        LEFT JOIN CHECKINGVEHICLESRESPOSTES a
          ON a.IDVEHICLECHECKING = q.IDVEHICLECHECKING AND a.IDVEHICLETREB = ?
        WHERE q.CODIGOTIPOMAQUINA = ?
        GROUP BY q.IDVEHICLECHECKING, q.ORDEN, q.TIPUSCHECKING',
        [$previous ?: 0, $vehicle['IDTIPUSVEHICLE']])->fetchAll();
    if ((int) $vehicle['TIPUSUS'] === 999) {
        $extra = query($db, 'SELECT IDVEHICLECHECKING, ORDEN, TIPUSCHECKING, NULL AS CADUCITAT
                            FROM CHECKINGVEHICLESPREGUNTES WHERE TIPUSCHECKING = 2')->fetchAll();
        $questions = array_merge($questions, $extra);
    }

    $insert = $db->prepare('INSERT INTO CHECKINGVEHICLESRESPOSTES
        (IDVEHICLETREB, ORDRE, TIPUSCHECKING, IDVEHICLECHECKING, CADUCITAT) VALUES (?, ?, ?, ?, ?)');
    $added = [];
    foreach ($questions as $question) {
        $questionId = $question['IDVEHICLECHECKING'];
        if (!isset($added[$questionId])) {
            $insert->execute([$id, $question['ORDEN'], $question['TIPUSCHECKING'], $questionId, $question['CADUCITAT']]);
            $added[$questionId] = true;
        }
    }
    if (!$added) {
        throw new DomainException('Este vehículo no tiene preguntas de revisión configuradas.');
    }
}

function checklist(PDO $db, int $id): array
{
    return query($db, '
        SELECT a.IDCHECKINGRESPOSTA, a.RESPOSTA, a.CADUCITAT, q.DESCRIPCION, q.TIPOARTICULO
        FROM CHECKINGVEHICLESRESPOSTES a
        LEFT JOIN CHECKINGVEHICLESPREGUNTES q ON q.IDVEHICLECHECKING = a.IDVEHICLECHECKING
        WHERE a.IDVEHICLETREB = ? ORDER BY a.TIPUSCHECKING, a.ORDRE, a.IDCHECKINGRESPOSTA', [$id])->fetchAll();
}

function saveChecklist(PDO $db, array $record, array $answers, array $expiry, string $comment, bool $finish): array
{
    $id = (int) $record['IDVEHICLETREB'];
    $questions = checklist($db, $id);
    if (!$questions) {
        throw new DomainException('Este registro no tiene preguntas de revisión configuradas.');
    }

    $missing = [];
    $updates = [];
    foreach ($questions as $question) {
        $key = $question['IDCHECKINGRESPOSTA'];
        $answer = $answers[$key] ?? '';
        if (!in_array($answer, ['', '-1', '0'], true)) {
            throw new DomainException('Una de las respuestas no es válida.');
        }
        $date = $question['CADUCITAT'];
        if ($question['TIPOARTICULO'] === 'M') {
            $date = $expiry[$key] ?? '';
            if (!is_string($date) || ($date !== '' && !validDate($date))) {
                throw new DomainException('Introduce una fecha válida en cada campo de caducidad.');
            }
            $date = $date === '' ? null : $date;
        }
        if ($answer === '' || ($question['TIPOARTICULO'] === 'M' && $date === null)) {
            $missing[] = (int) $key;
        }
        $updates[] = [$answer === '' ? null : (int) $answer, $date, $id, $key];
    }

    $db->beginTransaction();
    try {
        $update = $db->prepare('UPDATE CHECKINGVEHICLESRESPOSTES SET RESPOSTA = ?, CADUCITAT = ?
                                 WHERE IDVEHICLETREB = ? AND IDCHECKINGRESPOSTA = ?');
        foreach ($updates as $values) {
            $update->execute($values);
        }
        query($db, 'UPDATE VEHICLESTREBS SET COMENTARI = ? WHERE IDVEHICLETREB = ?', [$comment, $id]);
        if ($finish && !$missing) {
            query($db, 'UPDATE VEHICLESTREBS SET FINALCHECKLIST = -1 WHERE IDVEHICLETREB = ?', [$id]);
        }
        $db->commit();
    } catch (Throwable $error) {
        $db->rollBack();
        throw $error;
    }
    return $missing;
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
    $extensions = [IMAGETYPE_JPEG => 'JPG', IMAGETYPE_PNG => 'PNG', IMAGETYPE_WEBP => 'WEBP', IMAGETYPE_GIF => 'GIF'];
    $extension = $info ? ($extensions[$info[2]] ?? null) : null;
    if (!$extension) {
        throw new DomainException('Usa una imagen JPG, PNG, WebP o GIF.');
    }

    $label = preg_replace('/[^A-Za-z0-9_.-]/', '_', $worker['username'] . '-' . ($record['MATRICULA'] ?? $record['CODIOBRA']));
    $filename = date('YmdHis') . '-' . substr($label, 0, 100) . '-' . $record['IDVEHICLETREB']
        . '-' . bin2hex(random_bytes(6)) . '.' . $extension;
    $path = dirname(__DIR__) . '/fotos/' . $filename;
    if (!move_uploaded_file($temp, $path)) {
        throw new RuntimeException('Fleet photo could not be stored.');
    }
    try {
        query($db, 'UPDATE VEHICLESTREBS SET TEFOTOGRAFIES = -1 WHERE IDVEHICLETREB = ?', [$record['IDVEHICLETREB']]);
    } catch (Throwable $error) {
        unlink($path);
        throw $error;
    }
}
