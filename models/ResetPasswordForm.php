<?php

namespace app\models;

use yii\base\Model;
use yii\base\InvalidArgumentException;

/**
 * ResetPasswordForm - formularz resetowania hasła
 */
class ResetPasswordForm extends Model
{
    public $password;
    public $password_repeat;

    /**
     * @var \app\models\User
     */
    private $_user;

    /**
     * Creates a form model given a token.
     *
     * @param string $token
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @throws \yii\base\InvalidArgumentException if token is empty or not valid
     */
    public function __construct($token, $config = [])
    {
        if (empty($token) || !is_string($token)) {
            throw new InvalidArgumentException('Token resetowania hasła nie może być pusty.');
        }
        
        $this->_user = User::findByPasswordResetToken($token);
        
        if (!$this->_user) {
            throw new InvalidArgumentException('Nieprawidłowy token resetowania hasła.');
        }
        
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['password', 'password_repeat'], 'required', 'message' => 'To pole jest wymagane.'],
            ['password', 'string', 'min' => 6, 'message' => 'Hasło musi mieć minimum 6 znaków.'],
            ['password_repeat', 'compare', 'compareAttribute' => 'password', 'message' => 'Hasła muszą być identyczne.'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'password' => 'Nowe hasło',
            'password_repeat' => 'Powtórz hasło',
        ];
    }

    /**
     * Resets password.
     *
     * @return bool if password was reset.
     */
    public function resetPassword()
    {
        if (!$this->validate()) {
            return false;
        }
        
        $user = $this->_user;
        $user->setPassword($this->password);
        $user->removePasswordResetToken();
        $user->generateAuthKey();

        return $user->save(false);
    }
}