<?php

class Application extends Config {

    private $routingRules = [
        'Application' => [
            'index' => 'Application/actionIndex'
        ],
        'robots.txt' => [
            'index' => 'Application/actionRobots'
        ],
        'debug' => [
            'index' => 'Application/actionDebug'
        ]
    ];

    /**
     * @var $view View
     */
    private $view;

    function __construct() {
        parent::__construct();
        $this->view = new View($this);
        if ($this->requestMethod == 'POST') {
            header('Content-Type: application/json');
            die(json_encode($this->ajaxHandler($_POST)));
        } else {
            //Normal GET request. Nothing to do yet
        }
    }

    public function run() {
        if (array_key_exists($this->routing->controller, $this->routingRules)) {
            if (array_key_exists($this->routing->action, $this->routingRules[$this->routing->controller])) {
                list($controller, $action) = explode(DIRECTORY_SEPARATOR, $this->routingRules[$this->routing->controller][$this->routing->action]);
                call_user_func([$controller, $action]);
            } else { http_response_code(404); die('action not found'); }
        } else { http_response_code(404); die('controller not found'); }
    }

    public function actionIndex() {
        return $this->view->render('index');
    }

    public function actionDebug() {
        return $this->view->render('debug');
    }

    public function actionRobots() {
        return implode(PHP_EOL, ['User-Agent: *', 'Disallow: /']);
    }

    /**
     * Здесь нужно реализовать механизм валидации данных формы
     * @param $data array
     * $data - массив пар ключ-значение, генерируемое JavaScript функцией serializeArray()
     * name - Имя, обязательное поле, не должно содержать цифр и не быть больше 64 символов
     * phone - Телефон, обязательное поле, должно быть в правильном международном формате. Например +38 (067) 123-45-67
     * email - E-mail, необязательное поле, но должно быть либо пустым либо содержать валидный адрес e-mail
     * comment - необязательное поле, но не должно содержать тэгов и быть больше 1024 символов
     *
     * @return array
     * Возвращаем массив с обязательными полями:
     * result => true, если данные валидны, и false если есть хотя бы одна ошибка.
     * error => ассоциативный массив с найдеными ошибками,
     * где ключ - name поля формы, а значение - текст ошибки (напр. ['phone' => 'Некорректный номер']).
     * в случае отсутствия ошибок, возвращать следует пустой массив
     */
    public function actionFormSubmit($data) {
        //строка ошибок
       // $str ='';
        $result = true;
        //errors => ассоциативный массив с найдеными ошибками,
        $errors = array();

        //декодировать полученный массив

        $arr = json_decode($_POST['data'],true);

         // обязательное поле не должно содержать цифр и не быть больше 64 символов
        $name = $arr["name"];
        $name_reg = '[\D]{1,64}';//не цифра < 64 символов

        if(!preg_match($name_reg, $name)) {
            $str = "имя не должно быть цмфрой и более 64 символов";
            // $result = true;
            $errors = array('name' => $str);

        }
        // обязательное поле, Например +38 (067) 123-45-67
        $phone = $arr["phone"];
        $phone_reg = '^\+\d{2}\(\d{3}\)\d{3}-\d{2}-\d{2}$';

        if(!preg_match($phone_reg,$phone)) {
            $str = 'Телефон не соответствует стандарту +38 (xxx) xxx-xx-xx';
            $errors += array('phone' => $str);
        }

        // не обязательное поле
        $email = $arr["email"];
        $email_reg = "^[-\w.]+@([A-z0-9][-A-z0-9]+\.)+[A-z]{2,4}$";
        if(!preg_match($email_reg,$email)) {
            $str = "e-mail не валидный";
            $errors +=  array('email' => $str);
        }

        // не обязательное поле
        $comment = $arr["comment"];
        $comment_reg = '.{1,1024}(\<(/?[^\>]+)\>)';

        if(!preg_match($comment_reg,$comment)) {
            $str = 'В коментариях не должно быть тегов и более 1024 символов';
            $errors +=  array('comment' => $str);
        }


        //массив с обязательными полями
        $arr = [
            'name' =>$name,
            'phone' =>$phone
        ];

        $errors = [];                                  //Отсутствие ошибок

        return [
            'result' => count($errors) === 0,
            'error' => $errors,
            'array' => $arr,
        ];
    }


    /**
     * Функция обработки AJAX запросов
     * @param $post
     * @return array
     */
    private function ajaxHandler($post) {
        if (count($post)) {
            if (isset($post['method'])) {
                switch($post['method']) {
                    case 'formSubmit': $result = $this->actionFormSubmit($post['data']);
                        break;
                    default: $result = ['error' => 'Unknown method']; break;
                }
            } else { $result = ['error' => 'Unspecified method!']; }
        } else { $result = ['error' => 'Empty request!']; }
        return $result;
    }
}
