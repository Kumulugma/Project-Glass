<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * LoginForm - formularz logowania
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;

    private $_user = false;

    /**
     * @return array the validation rules.
     */
    public function rules()
    {
        return [
            [['username', 'password'], 'required', 'message' => 'To pole jest wymagane.'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'username' => 'Nazwa użytkownika lub email',
            'password' => 'Hasło',
            'rememberMe' => 'Zapamiętaj mnie',
        ];
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if (!$user || !$user->validatePassword($this->password)) {
                $this->addError($attribute, 'Nieprawidłowa nazwa użytkownika lub hasło.');
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {
            $user = $this->getUser();
            
            if (Yii::$app->user->login($user, $this->rememberMe ? 3600*24*30 : 0)) {
                // Aktualizuj informacje o ostatnim logowaniu
                $user->updateLastLogin();
                
                // Zaloguj w systemie logów
                UserLog::logLogin($user->id);
                
                return true;
            }
        }
        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->_user === false) {
            // Szukaj po nazwie użytkownika lub emailu
            $this->_user = User::findByUsername($this->username);
            
            if (!$this->_user) {
                $this->_user = User::findByEmail($this->username);
            }
        }

        return $this->_user;
    }
}