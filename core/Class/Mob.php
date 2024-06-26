<?php

#namespace Mob;
namespace Core\MClass;

use Languages\Language;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Core\Languages;

class Mob
{

    private function path($n = 1)
    {
        $caminho = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $slicePath = explode('/', trim($caminho, '/'));

        // Verifica se $slicePath[$n] existe antes de retornar o valor
        return isset($slicePath[$n]) ? $slicePath[$n] : '';
    }

    public function lib($mode, $array = false)
    {
        $type = ['css' => [], 'js' => []];

        if ($mode == 'css') {
            $type['css'][] = '<link rel="stylesheet" href="[local]">' . "\n";
        } elseif ($mode == 'js') {
            $type['js'][] = '<script src="[local]"></script>' . "\n";
        } else {
            return '<!-- Library data provided incorrectly or non-existent. -->';
        }

        $library = LIB;
        $n = 0;
        if (is_array($array)) {
            foreach ($array as $key) {
                if (isset($library[$mode][$key])) {
                    $barra = (strpos($library[$mode][$key], 'http') !== 0) ? '/' : '';
                    $space = ($n > 0 || $mode == 'css') ? '    ' : '';
                    echo $space . str_replace('[local]', $barra . $library[$mode][$key], $type[$mode][0]);
                    $n++;
                }
            }
        } else {
            $library = isset($library[$mode]) ? $library[$mode] : null;
            if (is_array($library)) {
                foreach ($library as $key => $value) {
                    $barra = (strpos($library[$key], 'http') !== 0) ? '/' : '';
                    $space = ($n > 0 || $mode == 'css') ? '    ' : '';
                    echo $space . str_replace('[local]', $barra . $library[$key], $type[$mode][0]);
                    // $n++;
                }
            }
        }
    }


    public function ErrorMini($error, $msg, $page = true)
    {

        $file = ROOT . "/templates/Error/MobError.php";
        if (!file_exists($file)) {
            return "<div class='alert alert-danger mx-3' role='alert'>$msg</div>";
        } else {
            // $langError = new Languages\Language;
            $erro = [
                'title' => 'Error',
                'message' => $msg
            ];

            if ($page) {
                $value = '';
                require_once ($file);
            }

            $this->log('error', strip_tags("{$erro['title']} {$erro['message']}"));
        }

    }

    public function components($components)
    {
        $error = false;
        // Verifica se $components é um array
        if (is_array($components)) {
            // Itera sobre os componentes fornecidos
            foreach ($components as $component) {
                $cmp = ucfirst($component);

                // PESQUISA PARTE 1
                $pathComponents = [
                    'modal' => "app/Components/$cmp/{$cmp}Modal.php",
                    'component' => "app/Components/$cmp/{$cmp}Controller.php",
                    'view' => "app/Components/$cmp/{$cmp}View.php"
                ];

                foreach ($pathComponents as $name => $path) {
                    if (!file_exists($path)) {
                        $error = true;
                        break;
                    }
                }

                //PESQUISA PARTE 2
                if ($error) {
                    $pathComponents = [
                        'modal' => "packages/$cmp/{$cmp}Modal.php",
                        'component' => "packages/$cmp/{$cmp}Controller.php",
                        'view' => "packages/$cmp/{$cmp}View.php"
                    ];

                    foreach ($pathComponents as $name => $path) {
                        if (!file_exists($path)) {
                            $error = true;
                            $msg = "There was an error trying to load the '$cmp' component.";
                            echo $this->ErrorMini('err3001', $msg);
                            exit();
                        }
                    }
                }

                echo "<div mb-component='$cmp' id='{$cmp}Component'>";
                require_once ($pathComponents['modal']);
                require_once ($pathComponents['component']);
                require_once ($pathComponents['view']);
                echo "</div>";
            }
        } else {
            // Exibe mensagem de erro se $components não for um array
            $msg = "Components must be declared within an [`array`].";
            echo $this->ErrorMini('err3000', "Erro 3000: $msg");
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Adiciona comandos de carregamento do Bootstrap
    |--------------------------------------------------------------------------
    | $app->loadBootstrap(['css','icon']);
    */
    public function loadBootstrap($types = ['css'])
    {
        $basePath = 'vendor/twbs/bootstrap';

        foreach ($types as $type) {
            switch ($type) {
                case 'css':
                    echo '<link rel="stylesheet" href="/' . $basePath . '/dist/css/bootstrap.min.css">' . "\n";
                    break;
                case 'js':
                    echo '<script src="/' . $basePath . '/dist/js/bootstrap.bundle.min.js"></script>' . "\n";
                    break;
                case 'icon':
                    echo '<link rel="stylesheet" href="/' . $basePath . '-icons/font/bootstrap-icons.min.css">' . "\n";
                    break;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Cria um hash usando o algoritmo PASSWORD_BCRYPT
    |--------------------------------------------------------------------------
    */
    public function createHash($password)
    {
        $hashSenha = password_hash($password, PASSWORD_BCRYPT);
        return $hashSenha;
    }

    /*
    |--------------------------------------------------------------------------
    | Verifica se a senha corresponde ao hash armazenado
    |--------------------------------------------------------------------------
    */
    public function verifyHash($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /*
    |--------------------------------------------------------------------------
    | Gerador de senhas
    |--------------------------------------------------------------------------
     */
    function createPass($n = 6, $useLowerCase = true, $useUpperCase = true, $useNumbers = true, $useSpecialChars = true)
    {
        // Definições dos caracteres
        $lowerCaseChars = 'abcdefghijklmnopqrstuvwxyz';
        $upperCaseChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numberChars = '0123456789';
        $specialChars = '!@#$%^&*()-_=+';

        // Inicializa a variável de senha
        $password = '';

        // Constrói o conjunto de caracteres com base nas configurações
        $chars = '';

        if ($useLowerCase) {
            $chars .= $lowerCaseChars;
        }
        if ($useUpperCase) {
            $chars .= $upperCaseChars;
        }
        if ($useNumbers) {
            $chars .= $numberChars;
        }
        if ($useSpecialChars) {
            $chars .= $specialChars;
        }

        // Calcula o tamanho do conjunto de caracteres
        $charLength = strlen($chars);

        // Gera a senha
        for ($i = 0; $i < $n; $i++) {
            // Seleciona um caractere aleatório do conjunto de caracteres
            $randomChar = $chars[rand(0, $charLength - 1)];
            // Adiciona o caractere à senha
            $password .= $randomChar;
        }

        // Retorna a senha gerada
        return $password;
    }

    /*
    |--------------------------------------------------------------------------
    | Retorna o título com base na URI
    |--------------------------------------------------------------------------
    */
    public function title($type = false)
    {
        // Lê o conteúdo do arquivo JSON
        $jsonConteudo = file_get_contents("./core/json/routes.json");

        // Decodifica o JSON em um array associativo
        $array = json_decode($jsonConteudo, true);

        // Obtém a parte da URI a partir da posição 2
        $uri = "/" . $this->path(2);

        // Variáveis para controle de erro e rota
        $erro = false;
        $route = "";

        // Verifica se o tipo é 'component'
        if ($type == 'component') {
            // Verifica se a chave 'routes' existe no array
            if (isset($array['routes'])) {
                // Itera sobre as rotas
                foreach ($array['routes'] as $routes) {
                    // Verifica se a rota atual corresponde à URI
                    if ($routes['path'] == $uri) {
                        $route = $routes['controller'];
                        break;
                    }
                }

                // Verifica se a rota foi encontrada
                if (!empty($route)) {
                    // Caminho do arquivo do controlador
                    $fileController = "./app/pages/$route/$route.controller.php";

                    // Verifica se o arquivo do controlador existe
                    if (file_exists($fileController)) {
                        // Inclui o arquivo do controlador
                        require_once ($fileController);

                        // Cria uma instância do controlador
                        $$route = new $route;

                        // Exibe o título do controlador
                        echo $$route->title;
                    } else {
                        // Exibe mensagem de erro se o arquivo do controlador não for encontrado
                        $msg = "Erro: O arquivo do controlador $route não foi encontrado em $fileController";
                        $this->error($msg);
                    }
                } else {
                    // Exibe mensagem de erro se a rota não for encontrada
                    $msg = "Erro: A rota correspondente à URI $uri não foi encontrada no arquivo JSON de rotas.";
                    $this->error($msg);
                }
            } else {
                // Exibe mensagem de erro se a chave 'routes' não existir no array
                $msg = "Erro: A chave 'routes' não foi encontrada no arquivo JSON de rotas.";
                $this->error($msg);
            }

            if (@$msg)
                echo $msg;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Adiciona o script do jQuery
    |--------------------------------------------------------------------------
    */
    public function loadJQuery()
    {
        echo "<script src='/node_modules/jquery/dist/jquery.min.js'></script>";
    }

    /*
    |--------------------------------------------------------------------------
    | Adiciona o script do jQuery
    |--------------------------------------------------------------------------
    */
    public function loadChart()
    {
        echo "<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>";
    }

    /*
    |--------------------------------------------------------------------------
    | Carrega arquivo personalizado
    |--------------------------------------------------------------------------
    */
    public function loadMob($type)
    {
        switch ($type) {
            case 'css':
                $this->loadMobcss();
                break;
            case 'js':
                $this->loadMobjs();
                break;
            default:

                break;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Carrega o estilo CSS personalizado
    |--------------------------------------------------------------------------
    */
    public function loadMobcss()
    {
        $time = (APP['mode'] == 0) ? "?t=" . time() : '';
        echo "<link rel='stylesheet' href='/public/css/MainStyle.css$time'>\n";
    }

    /*
    |--------------------------------------------------------------------------
    | Cria um formulário com base em uma matriz de configuração
    |--------------------------------------------------------------------------
    */
    public function createForm($formData, $bts = 'true')
    {
        $formName = $formData['formName'];
        $method = isset($formData['method']) ? " method='{$formData['method']}'" : "";

        // Início do formulário principal
        $html = "<form id='{$formName}' method='{$method}'>";

        // Contador de Grupos
        $n = 0;

        $row = ($bts) ? 'row' : '';

        // Iterar sobre os grupos de campos
        foreach ($formData as $group) {

            if (is_array($group)) {
                // Início de uma nova div para o grupo de campos
                $html .= "<div id='$formName$n' class='$row'>";

                // Iterar sobre os campos no grupo
                foreach ($group as $fieldName => $fieldConfig) {
                    $html .= $this->generateFormField($fieldName, $fieldConfig, $bts);
                }

                // Fim da div do grupo
                $html .= "</div>";
            }

            $n++;

        }

        // Fim do formulário principal
        $html .= "</form>";

        echo $html;
    }

    /*
    |--------------------------------------------------------------------------
    | Gera um campo de formulário com base na configuração fornecida
    |--------------------------------------------------------------------------
    */
    private function generateFormField($fieldName, $fieldConfig, $bts = 'true')
    {
        $type = $fieldConfig['type'];

        $required = isset($fieldConfig['required']) && $fieldConfig['required'] ? 'required' : '';
        $readonly = isset($fieldConfig['readonly']) && $fieldConfig['readonly'] ? 'readonly' : '';
        $options = isset($fieldConfig['options']) ? $fieldConfig['options'] : '';
        $placeholder = isset($fieldConfig['placeholder']) ? "placeholder='{$fieldConfig['placeholder']}'" : '';
        $class = isset($fieldConfig['class']) && $fieldConfig['class'] ? $fieldConfig['class'] : '';
        $value = isset($fieldConfig['value']) && $fieldConfig['value'] ? $fieldConfig['value'] : '';
        $col = isset($fieldConfig['column']) && $fieldConfig['column'] ? "col-sm-" . $fieldConfig['column'] : 'col-sm';
        $title0 = isset($fieldConfig['title']) ? "<label for='{$fieldName}' class='form-label'>{$fieldConfig['title']}</label>" : '';
        $title = ($type == 'checkbox') ? "<label class='form-check-label' for='{$fieldName}'>{$fieldConfig['title']}</label>" : $title0;

        if ($bts) {
            $mb3 = 'mb-3';
            $frmg = 'form-control';
            $frms = 'form-select';
        } else {
            $mb3 = $frmg = $frms = '';
        }

        // Geração do campo com base no tipo
        switch ($type) {
            case 'text':
            case 'email':
            case 'password':
            case 'datetime':
                return "<div class='$mb3 $col'>{$title}<input type='{$type}' name='{$fieldName}' title='{$title}' class='$frmg $class' {$required} {$placeholder} {$readonly}></div>";
            case 'select':
                $selectOptions = '';
                foreach ($options as $label => $value) {
                    $selectOptions .= "<option value='{$value}'>{$label}</option>";
                }
                return "<div class='$mb3 $col'>{$title}<select name='{$fieldName}' title='{$title}' class='{$frms}' {$required} {$readonly}>{$selectOptions}</select></div>";
            case 'textarea':
                return "<div class='$mb3 $col'>{$title}<textarea name='{$fieldName}' title='{$title}' class='$frmg $class' {$required} {$readonly}></textarea></div>";
            case 'checkbox':
                return "<div class='form-check $mb3 $col ps-2'><input class='form-check-input mx-1' type='checkbox' value='{$value}' id='{$fieldName}'>{$title}</div>";
            default:
                return '';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Adiciona o script do Mob
    |--------------------------------------------------------------------------
    */
    public function loadMobjs()
    {
        $time = (APP['mode'] == 0) ? "?t=" . time() : '';
        echo "    <script src='/ctrl/mob.min.js$time'></script>\n";
    }

    /*
    |--------------------------------------------------------------------------
    | Registra mensagens de erro no arquivo de log
    |--------------------------------------------------------------------------
    */
    public function error($message,$type = 'ERROR')
    {
        // Obter o endereço IP do cliente
        $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

        // Obter a URI da solicitação (filtrada para evitar XSS)
        $uri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);

        // Construir a mensagem de erro no formato desejado
        $errorMessage = date('Y-m-d H:i:s') . " - $type: $message, ";

        // Adicionar informações adicionais ao registro de erro
        $errorMessage .= "IP ADRESS: $ipAddress, ";
        $errorMessage .= "URL: $uri\n";

        // Escrever a mensagem de erro no log
        error_log($errorMessage, 3, 'var/Logs/Mob.log'); 
    }


    public function log($type = 'error', $message)
    {
        $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        $uri = filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_URL);
        $errorMessage = date('Y-m-d H:i:s') . ";$type;{$ipAddress};{$uri};$message\n";
        error_log($errorMessage, 3, 'var/Logs/Mob.log');
    }

    /**
     * Envia um e-mail usando PHPMailer
     *
     * @param string $to E-mail do destinatário
     * @param string $subject Assunto do e-mail
     * @param string $body Corpo do e-mail (pode conter HTML)
     * @param string|null $fromEmail E-mail do remetente (se null, usa o configurado)
     * @param string|null $fromName Nome do remetente (se null, usa o configurado)
     *
     * @return bool Retorna true se o e-mail for enviado com sucesso, false caso contrário.
     */ 
    public function sendMail($to, $subject, $body, $fromEmail = null, $fromName = null)
    {
        // Verifica se a constante MAIL foi definida
        if (!defined('MAIL')) {
            trigger_error('Configurações do PHPMailer não definidas.', E_USER_ERROR);
            return false;
        }

        // Carrega as configurações do PHPMailer
        $mailConfig = MAIL;

        // Cria uma instância do PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Configurações do servidor SMTP
            $mail->isSMTP();
            $mail->Host = $mailConfig['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $mailConfig['username'];
            $mail->Password = $mailConfig['password'];
            $mail->SMTPSecure = $mailConfig['smtp_secure'];
            $mail->Port = $mailConfig['port'];

            // Configurações do remetente
            $mail->setFrom($fromEmail ?? $mailConfig['from_email'], $fromName ?? $mailConfig['from_name']);

            // Configurações padrão do e-mail
            $mail->isHTML($mailConfig['is_html']);
            $mail->CharSet = $mailConfig['charset'];

            // Destinatário, assunto e corpo do e-mail
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;

            // Envia o e-mail
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Trata erros durante o envio
            $errorMessage = "Error sending email: {$mail->ErrorInfo}";

            // Salva no log
            $this->error("[email][$errorMessage]");

            return false;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gerador de senhas
    |--------------------------------------------------------------------------
    */
    public function createPassword($size = 12, $useLetters = true, $useNumbers = true, $useSpecialChars = true)
    {
        // Define os conjuntos de caracteres a serem utilizados
        $characters = '';
        $characters .= ($useLetters) ? 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz' : '';
        $characters .= ($useNumbers) ? '0123456789' : '';
        $characters .= ($useSpecialChars) ? '!@#$&()' : '';

        // Verifica se há caracteres suficientes para gerar uma senha segura
        if (strlen($characters) < 1) {
            throw new Exception("É necessário selecionar pelo menos um tipo de caractere (letras, números, caracteres especiais).");
        }

        // Garante que o tamanho mínimo da senha seja 6 caracteres
        $size = max(6, $size);

        $password = '';

        // Gera a senha aleatória
        for ($i = 0; $i < $size; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $password;
    }

    /*
    |--------------------------------------------------------------------------
    | Imprime o botão para a criação de novos usuários
    |--------------------------------------------------------------------------
    */
    public function registration_for_new_users()
    {
        $lang = new Language();
        $file = "templates/others/registration_for_new_users.php";
        if (@MADMIN['registration_for_new_users']) {
            if (file_exists($file)) {
                require_once ($file);
            }
        }
    }
}
