<?php
const VALIDATION_FIELDS = ['name', 'email', 'pet_category', 'pet_name'];
const FIELD_MAP = [
    'name' => "Имя",
    'email' => "E-mail",
    'pet_category' => 'Категория питомца',
    'pet_name' => 'Имя питомца'
];

const SENDSAY_CONFIG = [
    'login' => 'x_1742494340678122',
    'sublogin' => 'goodvin',
    'password' => 'V5LZk93vXVM1'
];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (VALIDATION_FIELDS as $field) {
        $value = trim($_POST[$field] ?? '');

        if ($value === '') {
            $errors[$field] = "Поле " . FIELD_MAP[$field] . " не должно быть пустым.";
        }

        if ($field === 'email' && !isset($errors['email']) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = "Некорректный формат e-mail.";
        }
    }

    if (empty($errors)) {
        $sendSay = new SendsayService(SENDSAY_CONFIG['login'], SENDSAY_CONFIG['sublogin'], SENDSAY_CONFIG['password']);
        $sendSay->auth();
        echo '<div class="alert alert-success">Регистрация успешна!</div>';
    }
}

class SendsayService
{
    private string $login;
    private string $sublogin;
    private string $password;
    private string $api_url;
    private string $session;

    public function __construct(string $login, string $sublogin, string $password)
    {
        $this->login = $login;
        $this->sublogin = $sublogin;
        $this->password = $password;
        $this->api_url = sprintf('https://api.sendsay.ru/general/api/v100/json/%s/', $login);
    }

    public function send(array $data)
    {
        //
    }

    public function auth(): array
    {
        $request = [
            'action' => 'login',
            'login' => $this->login,
            'sublogin' => $this->sublogin,
            'passwd' => $this->password
        ];

        $response = $this->curlRequest($request);

        if (isset($response['errors'])) {
            die('Ошибка авторизации в Sendsay: ' . json_encode($response['errors']));
        }

        $this->session = $response['session'];
    }

    private function curlRequest($data)
    {
        $curl = curl_init($this->api_url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            echo '<div class="alert alert-success">Ошибка cURL!</div>';
            return;
//            die('Ошибка cURL: ' . curl_error($curl));
        }

        curl_close($curl);

        return json_decode($response, true);
    }
}

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация питомца</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm border-0" style="max-width: 500px; margin: auto;">
        <div class="card-header bg-success text-white text-center">
            <h4><i class="fas fa-paw"></i> Регистрация питомца</h4>
        </div>
        <div class="card-body">
            <form action="" method="POST" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="name" class="form-label">Ваше имя</label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                    <?php if (isset($errors['name'])): ?>
                        <span class="text-danger">
                            <?= $errors['name'] ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    <?php if (isset($errors['email'])): ?>
                        <span class="text-danger">
                            <?= $errors['email'] ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="pet_category" class="form-label">Категория питомца</label>
                    <select class="form-select" id="pet_category" name="pet_category" required>
                        <option value="" disabled <?= empty($_POST['pet_category']) ? 'selected' : '' ?>>Выберите
                            категорию
                        </option>
                        <option value="Кот" <?= ($_POST['pet_category'] ?? '') === 'Кот' ? 'selected' : '' ?>>Кот
                        </option>
                        <option value="Собака" <?= ($_POST['pet_category'] ?? '') === 'Собака' ? 'selected' : '' ?>>
                            Собака
                        </option>
                        <option value="Грызун" <?= ($_POST['pet_category'] ?? '') === 'Грызун' ? 'selected' : '' ?>>
                            Грызун
                        </option>
                        <option value="Рыбки" <?= ($_POST['pet_category'] ?? '') === 'Рыбки' ? 'selected' : '' ?>>
                            Рыбки
                        </option>
                        <option value="Другое" <?= ($_POST['pet_category'] ?? '') === 'Другое' ? 'selected' : '' ?>>
                            Другое
                        </option>
                    </select>
                    <?php if (isset($errors['pet_category'])): ?>
                        <span class="text-danger">
                            <?= $errors['pet_category'] ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="pet_name" class="form-label">Имя питомца</label>
                    <input type="text" class="form-control" id="pet_name" name="pet_name"
                           value="<?= htmlspecialchars($_POST['pet_name'] ?? '') ?>" required>
                    <?php if (isset($errors['pet_name'])): ?>
                        <span class="text-danger">
                            <?= $errors['pet_name'] ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Отправить</button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>