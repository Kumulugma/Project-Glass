<?php

namespace app\models;

use Yii;
use yii\base\Model;

/**
 * ChangePasswordForm - formularz zmiany hasła
 */
class ChangePasswordForm extends Model
{
    public $currentPassword;
    public $newPassword;
    public $confirmPassword;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['currentPassword', 'newPassword', 'confirmPassword'], 'required', 'message' => 'To pole jest wymagane.'],
            ['currentPassword', 'validateCurrentPassword'],
            ['newPassword', 'string', 'min' => 6, 'message' => 'Hasło musi mieć minimum 6 znaków.'],
            ['confirmPassword', 'compare', 'compareAttribute' => 'newPassword', 'message' => 'Hasła muszą być identyczne.'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'currentPassword' => 'Aktualne hasło',
            'newPassword' => 'Nowe hasło',
            'confirmPassword' => 'Potwierdź nowe hasło',
        ];
    }

    /**
     * Validates the current password
     */
    public function validateCurrentPassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = Yii::$app->user->identity;
            if (!$user || !$user->validatePassword($this->currentPassword)) {
                $this->addError($attribute, 'Nieprawidłowe aktualne hasło.');
            }
        }
    }

    /**
     * Changes password
     *
     * @return bool if password was changed
     */
    public function changePassword()
    {
        if (!$this->validate()) {
            return false;
        }
        
        $user = Yii::$app->user->identity;
        $user->setPassword($this->newPassword);
        $user->generateAuthKey();
        
        return $user->save(false);
    }
}