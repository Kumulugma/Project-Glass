<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use yii\behaviors\TimestampBehavior;

/**
 * Model User
 *
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property string $auth_key
 * @property string|null $password_reset_token
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $role
 * @property int $status
 * @property int|null $last_login_at
 * @property string|null $last_login_ip
 * @property int $created_at
 * @property int $updated_at
 * 
 * @property-read string $fullName
 * @property-read bool $isAdmin
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;
    
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';
    
    public $password; // Wirtualne pole do ustawiania hasła
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%users}}';
    }
    
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::class,
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['username', 'trim'],
            ['username', 'required'],
            ['username', 'unique', 'targetClass' => '\app\models\User', 'message' => 'Ta nazwa użytkownika jest już zajęta.'],
            ['username', 'string', 'min' => 3, 'max' => 255],
            
            ['email', 'trim'],
            ['email', 'required'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'unique', 'targetClass' => '\app\models\User', 'message' => 'Ten adres email jest już zajęty.'],
            
            ['password', 'string', 'min' => 6],
            ['password', 'required', 'on' => 'create'],
            
            [['first_name', 'last_name'], 'string', 'max' => 100],
            
            ['role', 'in', 'range' => [self::ROLE_USER, self::ROLE_ADMIN]],
            ['role', 'default', 'value' => self::ROLE_USER],
            
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED]],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => 'Nazwa użytkownika',
            'email' => 'Email',
            'password' => 'Hasło',
            'first_name' => 'Imię',
            'last_name' => 'Nazwisko',
            'role' => 'Rola',
            'status' => 'Status',
            'last_login_at' => 'Ostatnie logowanie',
            'created_at' => 'Utworzono',
            'updated_at' => 'Zaktualizowano',
        ];
    }
    
    /**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }
    
    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_key' => $token, 'status' => self::STATUS_ACTIVE]);
    }
    
    /**
     * Znajduje użytkownika po nazwie
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }
    
    /**
     * Znajduje użytkownika po emailu
     */
    public static function findByEmail($email)
    {
        return static::findOne(['email' => $email, 'status' => self::STATUS_ACTIVE]);
    }
    
    /**
     * Znajduje użytkownika po tokenie resetowania hasła
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }
        
        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }
    
    /**
     * Sprawdza czy token resetowania hasła jest ważny
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        
        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'] ?? 3600;
        
        return $timestamp + $expire >= time();
    }
    
    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }
    
    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }
    
    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }
    
    /**
     * Waliduje hasło
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }
    
    /**
     * Ustawia hasło
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }
    
    /**
     * Generuje auth key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
    
    /**
     * Generuje token resetowania hasła
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }
    
    /**
     * Usuwa token resetowania hasła
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
    
    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if ($insert) {
                $this->generateAuthKey();
            }
            
            if (!empty($this->password)) {
                $this->setPassword($this->password);
            }
            
            return true;
        }
        return false;
    }
    
    /**
     * Zwraca pełne imię i nazwisko
     */
    public function getFullName()
    {
        if ($this->first_name || $this->last_name) {
            return trim($this->first_name . ' ' . $this->last_name);
        }
        return $this->username;
    }
    
    /**
     * Sprawdza czy użytkownik jest adminem
     */
    public function getIsAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }
    
    /**
     * Aktualizuje informacje o ostatnim logowaniu
     */
    public function updateLastLogin()
    {
        $this->last_login_at = time();
        $this->last_login_ip = Yii::$app->request->userIP;
        $this->save(false, ['last_login_at', 'last_login_ip']);
    }
    
    /**
     * Relacje
     */
    public function getLogs()
    {
        return $this->hasMany(UserLog::class, ['user_id' => 'id'])
            ->orderBy(['created_at' => SORT_DESC]);
    }
}