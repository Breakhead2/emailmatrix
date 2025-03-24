<?php
const VALIDATION_FIELDS = ['name', 'email', 'pet_category', 'pet_name'];
const FIELD_MAP = [
    'name' => "Имя",
    'email' => "E-mail",
    'pet_category' => 'Категория питомца',
    'pet_name' => 'Имя питомца'
];

const GROUP_MAP = [
    'Владельцы питомцев' => 'pl41768'
];

const SENDSAY_CONFIG = [
    'login' => 'x_1742494340678122',
    'api_key' => "18WH7WxqmfLTiBFUTfpU-_a9fiOpSsU7miUVNxXLhO8rBut39mrGM5tHUA63iopfC3BRQt-s28mUmcg"
];

$errors = [];
$data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (VALIDATION_FIELDS as $field) {
        $value = trim($_POST[$field] ?? '');

        if ($value === '') {
            $errors[$field] = "Поле " . FIELD_MAP[$field] . " не должно быть пустым.";
        }

        if ($field === 'email' && !isset($errors['email']) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $errors[$field] = "Некорректный формат e-mail.";
        }

        $data[$field] = $value;
    }

    if (empty($errors)) {
        $sendSay = new SendsayService(SENDSAY_CONFIG['login'], SENDSAY_CONFIG['api_key']);
        $response = $sendSay->addNewUser($data, 'Владельцы питомцев');

        if ($response['status'] === 200) {
            $response = $sendSay->sendEmail($data, 'Владельцы питомцев');
            if ($response['status'] === 200) {
                echo '<div class="alert alert-success">Регистрация успешна! Мы отправили вам на почту письмо с подтверждением данных.</div>';
                $_POST = [];
            }
        } else {
            echo '<div class="alert alert-error">' . $response['error'] . '</div>';
        }

    }
}

class SendsayService
{
    private string $login;
    private string $api_key;
    private string $api_url;

    public function __construct(string $login, string $api_key)
    {
        $this->login = $login;
        $this->api_key = $api_key;
        $this->api_url = sprintf('https://api.sendsay.ru/general/api/v100/json/%s/', $login);
    }

    public function addNewUser(array $data, string $groupName): array
    {
        $groupId = GROUP_MAP[$groupName];

        $data = [
            "action" => "member.set",
            "email" => $data['email'],
            "apikey" => $this->api_key,
            "addr_type" => "email",
            "force_subscribe" => true,
            "datakey" => [
                ["base.firstName", "set", $data['name']],
                ["custom.pet_name", "set", $data['pet_name']],
                ["custom.pet_category", "set", $data['pet_category']],
                ["-group.{$groupId}", "set", "1"]
            ]
        ];

        return $this->curlRequest($data);
    }

    public function sendEmail(array $data, string $groupName): array
    {
        $data = [
            "action" => "issue.send",
            "email" => $data["email"],
            "apikey" => $this->api_key,
            "sendwhen" => "now",
            "group" => "personal",
            "letter" => [
                "subject" => "Подтверждение регистрации",
                "from.name" => "EMAILMATRIX",
                "from.email" => "denis.sazonov@swipeandlike.me",
                "draft.id" => 60,
            ],
            "extra" => [
                "confirm_link" => "https://emailmatrix.ru/"
            ]
        ];

        return $this->curlRequest($data);
    }

    private function curlRequest($data): array
    {
        $ch = curl_init($this->api_url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            return [
                'status' => $httpCode,
                'error' => curl_error($ch)
            ];
        }

        return [
            'status' => $httpCode,
            'response' => $response
        ];
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