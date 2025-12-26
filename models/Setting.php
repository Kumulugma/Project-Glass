<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;

/**
 * Model Setting
 *
 * @property int $id
 * @property string $setting_key
 * @property string|null $setting_value
 * @property string|null $description
 * @property string $setting_type
 * @property int $created_at
 * @property int $updated_at
 */
class Setting extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%settings}}';
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
            [['setting_key'], 'required'],
            [['setting_key'], 'string', 'max' => 100],
            [['setting_key'], 'unique'],
            [['setting_value', 'description'], 'string'],
            [['setting_type'], 'in', 'range' => ['string', 'number', 'boolean', 'json', 'password']],
            [['setting_type'], 'default', 'value' => 'string'],
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'setting_key' => 'Klucz',
            'setting_value' => 'Wartość',
            'description' => 'Opis',
            'setting_type' => 'Typ',
            'created_at' => 'Utworzono',
            'updated_at' => 'Zaktualizowano',
        ];
    }
    
    /**
     * Pobiera wartość ustawienia
     * 
     * @param string $key Klucz ustawienia
     * @param mixed $default Wartość domyślna jeśli nie znaleziono
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        $setting = static::findOne(['setting_key' => $key]);
        
        if (!$setting) {
            return $default;
        }
        
        return static::parseValue($setting->setting_value, $setting->setting_type);
    }
    
    /**
     * Ustawia wartość ustawienia (lub tworzy nowe)
     * 
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string|null $description
     * @return bool
     */
    public static function set($key, $value, $type = 'string', $description = null)
    {
        $setting = static::findOne(['setting_key' => $key]);
        
        if (!$setting) {
            $setting = new static();
            $setting->setting_key = $key;
            $setting->setting_type = $type;
        }
        
        $setting->setting_value = static::formatValue($value, $type);
        
        if ($description !== null) {
            $setting->description = $description;
        }
        
        return $setting->save();
    }
    
    /**
     * Parsuje wartość na podstawie typu
     * 
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    private static function parseValue($value, $type)
    {
        switch ($type) {
            case 'number':
                return is_numeric($value) ? (float)$value : 0;
            
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            
            case 'json':
                return json_decode($value, true);
            
            case 'password':
            case 'string':
            default:
                return $value;
        }
    }
    
    /**
     * Formatuje wartość do zapisu
     * 
     * @param mixed $value
     * @param string $type
     * @return string
     */
    private static function formatValue($value, $type)
    {
        switch ($type) {
            case 'number':
                return (string)$value;
            
            case 'boolean':
                return $value ? '1' : '0';
            
            case 'json':
                return is_string($value) ? $value : json_encode($value);
            
            case 'password':
            case 'string':
            default:
                return (string)$value;
        }
    }
    
    /**
     * Pobiera wszystkie ustawienia channela
     * 
     * @param string $channelIdentifier Identyfikator channela (np. 'email', 'sms', 'push')
     * @return array
     */
    public static function getChannelSettings($channelIdentifier)
    {
        $prefix = "channel_{$channelIdentifier}_";
        
        $settings = static::find()
            ->where(['like', 'setting_key', $prefix])
            ->all();
        
        $result = [];
        foreach ($settings as $setting) {
            $key = str_replace($prefix, '', $setting->setting_key);
            $result[$key] = static::parseValue($setting->setting_value, $setting->setting_type);
        }
        
        return $result;
    }
    
    /**
     * Zapisuje ustawienia channela
     * 
     * @param string $channelIdentifier
     * @param array $settings Tablica klucz => wartość
     * @return bool
     */
    public static function setChannelSettings($channelIdentifier, array $settings)
    {
        $success = true;
        
        foreach ($settings as $key => $value) {
            $fullKey = "channel_{$channelIdentifier}_{$key}";
            
            // Określ typ na podstawie wartości
            $type = 'string';
            if (is_numeric($value)) {
                $type = 'number';
            } elseif (is_bool($value)) {
                $type = 'boolean';
            } elseif (is_array($value)) {
                $type = 'json';
            } elseif (str_contains($key, 'password') || str_contains($key, 'key') || str_contains($key, 'secret')) {
                $type = 'password';
            }
            
            if (!static::set($fullKey, $value, $type)) {
                $success = false;
            }
        }
        
        return $success;
    }
}