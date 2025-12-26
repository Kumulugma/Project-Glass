<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * PasswordResetRequestForm - formularz żądania resetowania hasła
 */
class PasswordResetRequestForm extends Model
{
    public $email;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['email', 'trim'],
            ['email', 'required', 'message' => 'Adres email jest wymagany.'],
            ['email', 'email', 'message' => 'Nieprawidłowy adres email.'],
            ['email', 'exist',
                'targetClass' => '\app\models\User',
                'filter' => ['status' => User::STATUS_ACTIVE],
                'message' => 'Nie znaleziono użytkownika z tym adresem email.'
            ],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'email' => 'Adres email',
        ];
    }

    /**
     * Wysyła email z linkiem do resetowania hasła
     *
     * @return bool whether the email was send
     */
    public function sendEmail()
    {
        $user = User::findOne([
            'status' => User::STATUS_ACTIVE,
            'email' => $this->email,
        ]);

        if (!$user) {
            return false;
        }
        
        if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
            $user->generatePasswordResetToken();
            if (!$user->save(false)) {
                return false;
            }
        }

        return Yii::$app
            ->mailer
            ->compose(
                ['html' => 'passwordResetToken-html', 'text' => 'passwordResetToken-text'],
                ['user' => $user]
            )
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setTo($this->email)
            ->setSubject('Resetowanie hasła - ' . Yii::$app->name)
            ->send();
    }
}