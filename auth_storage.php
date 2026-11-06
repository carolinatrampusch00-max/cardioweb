<?php
if (!function_exists('get_auth_storage_path')) {
    function get_auth_storage_path()
    {
        return __DIR__ . '/../salvar/auth-data.json';
    }
}

if (!function_exists('build_default_auth_store')) {
    function build_default_auth_store()
    {
        return [
            'users' => [
                [
                    'id' => 'admin-1',
                    'name' => 'Admin CardioWeb',
                    'email' => 'admin@cardioweb.com',
                    'password' => 'admin123',
                    'role' => 'admin',
                    'specialty' => '',
                    'phone' => '(11) 4002-8922',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 'doctor-1',
                    'name' => 'Dr. Roberto Mendes',
                    'email' => 'roberto@cardioweb.com',
                    'password' => 'medico123',
                    'role' => 'doctor',
                    'specialty' => 'Cardiologia',
                    'phone' => '(11) 99999-1111',
                    'created_at' => date('Y-m-d H:i:s')
                ],
                [
                    'id' => 'doctor-2',
                    'name' => 'Dra. Aline Costa',
                    'email' => 'aline@cardioweb.com',
                    'password' => 'medico123',
                    'role' => 'doctor',
                    'specialty' => 'Arritmologia',
                    'phone' => '(11) 99999-2222',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ],
            'appointments' => [
                [
                    'id' => 'appt-1',
                    'doctor_id' => 'doctor-1',
                    'patient_name' => 'Ana Silva',
                    'patient_age' => 47,
                    'cpf' => '123.456.789-00',
                    'phone' => '(11) 99999-1111',
                    'symptoms' => 'Dor no peito, falta de ar e palpitações.',
                    'exam_results' => 'Hemograma normal; Ecocardiograma sem alterações.',
                    'doctor_observations' => 'Paciente orientado a retornar em 30 dias.',
                    'prescribed_exams' => 'Exame de esforço',
                    'status' => 'Pendente',
                    'date' => date('Y-m-d', strtotime('+2 days')),
                    'time' => '09:00'
                ],
                [
                    'id' => 'appt-2',
                    'doctor_id' => 'doctor-1',
                    'patient_name' => 'Carlos Mendes',
                    'patient_age' => 61,
                    'cpf' => '987.654.321-00',
                    'phone' => '(11) 98888-2222',
                    'symptoms' => 'Fadiga, tontura e pressão elevada.',
                    'exam_results' => 'Colesterol elevado; Holter normal.',
                    'doctor_observations' => 'Recomendado acompanhamento com dieta e atividade física.',
                    'prescribed_exams' => 'Exame de colesterol e glicemia',
                    'status' => 'Realizada',
                    'date' => date('Y-m-d', strtotime('+5 days')),
                    'time' => '11:00'
                ],
                [
                    'id' => 'appt-3',
                    'doctor_id' => 'doctor-2',
                    'patient_name' => 'Beatriz Ramos',
                    'patient_age' => 39,
                    'cpf' => '456.789.123-11',
                    'phone' => '(11) 97777-3333',
                    'symptoms' => 'Palpitações esporádicas e ansiedade.',
                    'exam_results' => 'Eletrocardiograma com arritmia leve.',
                    'doctor_observations' => 'Pacientes com arritmia leve; manter acompanhamento.',
                    'prescribed_exams' => 'Holter 24h',
                    'status' => 'Pendente',
                    'date' => date('Y-m-d', strtotime('+3 days')),
                    'time' => '14:00'
                ]
            ]
        ];
    }
}

if (!function_exists('get_auth_store')) {
    function get_auth_store()
    {
        $path = get_auth_storage_path();
        if (!file_exists($path)) {
            $store = build_default_auth_store();
            save_auth_store($store);
            return $store;
        }

        $content = @file_get_contents($path);
        if ($content === false || trim($content) === '') {
            $store = build_default_auth_store();
            save_auth_store($store);
            return $store;
        }

        $store = json_decode($content, true);
        if (!is_array($store)) {
            $store = build_default_auth_store();
            save_auth_store($store);
            return $store;
        }

        if (!isset($store['users']) || !is_array($store['users'])) {
            $store['users'] = [];
        }
        if (!isset($store['appointments']) || !is_array($store['appointments'])) {
            $store['appointments'] = [];
        }

        return $store;
    }
}

if (!function_exists('save_auth_store')) {
    function save_auth_store($store)
    {
        $path = get_auth_storage_path();
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($path, json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

if (!function_exists('find_user_by_email')) {
    function find_user_by_email($email)
    {
        $email = trim(strtolower($email ?? ''));
        foreach (get_auth_store()['users'] ?? [] as $user) {
            if (strtolower($user['email'] ?? '') === $email) {
                return $user;
            }
        }
        return null;
    }
}

if (!function_exists('authenticate_local_user')) {
    function authenticate_local_user($email, $password)
    {
        $user = find_user_by_email($email);
        if (!$user) {
            return null;
        }
        if (($user['password'] ?? '') === (string) $password) {
            return $user;
        }
        return null;
    }
}

if (!function_exists('create_doctor_profile')) {
    function create_doctor_profile($name, $email, $password, $specialty = '', $phone = '', $extra = [])
    {
        $store = get_auth_store();
        $users = $store['users'] ?? [];
        if (find_user_by_email($email)) {
            return null;
        }

        $user = [
            'id' => 'doctor-' . time() . '-' . rand(100, 999),
            'name' => trim($name),
            'email' => trim($email),
            'password' => (string) $password,
            'role' => 'doctor',
            'specialty' => trim($specialty),
            'phone' => trim($phone),
            'created_at' => date('Y-m-d H:i:s')
        ];

        if (is_array($extra)) {
            foreach ($extra as $key => $value) {
                if ($key === 'password') {
                    continue;
                }
                $user[$key] = $value;
            }
        }

        $users[] = $user;
        $store['users'] = $users;
        save_auth_store($store);
        return $user;
    }
}

if (!function_exists('get_doctors')) {
    function get_doctors()
    {
        $users = get_auth_store()['users'] ?? [];
        return array_values(array_filter($users, function ($user) {
            return ($user['role'] ?? '') === 'doctor';
        }));
    }
}

if (!function_exists('update_doctor_profile')) {
    function update_doctor_profile($doctor_id, $updates)
    {
        $store = get_auth_store();
        $users = $store['users'] ?? [];
        $found = false;
        foreach ($users as &$user) {
            if (($user['id'] ?? '') === $doctor_id) {
                foreach ($updates as $k => $v) {
                    if ($k === 'id' || $k === 'password') continue;
                    $user[$k] = $v;
                }
                $found = true;
                break;
            }
        }
        if ($found) {
            $store['users'] = $users;
            save_auth_store($store);
            return true;
        }
        return false;
    }
}

if (!function_exists('find_doctor_by_name')) {
    function find_doctor_by_name($doctor_name)
    {
        $needle = strtolower(trim(preg_replace('/^dr\.?\s+/i', '', (string) $doctor_name)));
        foreach (get_doctors() as $doctor) {
            $candidate = strtolower(trim(preg_replace('/^dr\.?\s+/i', '', (string) ($doctor['name'] ?? ''))));
            if ($candidate === $needle) {
                return $doctor;
            }
        }
        return null;
    }
}

if (!function_exists('get_appointments_for_doctor')) {
    function get_appointments_for_doctor($doctor_id)
    {
        $appointments = get_auth_store()['appointments'] ?? [];
        return array_values(array_filter($appointments, function ($appointment) use ($doctor_id) {
            return ($appointment['doctor_id'] ?? '') === $doctor_id;
        }));
    }
}

if (!function_exists('get_all_appointments')) {
    function get_all_appointments()
    {
        return get_auth_store()['appointments'] ?? [];
    }
}

if (!function_exists('create_appointment_record')) {
    function create_appointment_record($doctor_id, $patient_name, $patient_age, $cpf, $phone, $symptoms, $exam_results, $doctor_observations, $prescribed_exams, $status, $date, $time)
    {
        $store = get_auth_store();
        $appointments = $store['appointments'] ?? [];
        $appointments[] = [
            'id' => 'appt-' . time() . '-' . rand(1000, 9999),
            'doctor_id' => (string) $doctor_id,
            'patient_name' => trim((string) $patient_name),
            'patient_age' => (int) $patient_age,
            'cpf' => trim((string) $cpf),
            'phone' => trim((string) $phone),
            'symptoms' => trim((string) $symptoms),
            'exam_results' => trim((string) $exam_results),
            'doctor_observations' => trim((string) $doctor_observations),
            'prescribed_exams' => trim((string) $prescribed_exams),
            'status' => trim((string) $status),
            'date' => trim((string) $date),
            'time' => trim((string) $time),
        ];
        $store['appointments'] = $appointments;
        save_auth_store($store);
        return end($appointments);
    }
}

if (!function_exists('update_appointment_record')) {
    function update_appointment_record($appointment_id, $updates)
    {
        $store = get_auth_store();
        $appointments = $store['appointments'] ?? [];
        foreach ($appointments as &$appointment) {
            if (($appointment['id'] ?? '') === $appointment_id) {
                foreach ($updates as $key => $value) {
                    $appointment[$key] = $value;
                }
                break;
            }
        }
        $store['appointments'] = $appointments;
        save_auth_store($store);
        return true;
    }
}
