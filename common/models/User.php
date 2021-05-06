<?php
namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $verification_token
 * @property string $fio
 * @property string $avatar
 * @property string $inn
 * @property string $workphone
 * @property string $phone
 * @property string $partner_contact
 * @property string $staff
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_UNACTIVATED = 8;
    const STATUS_INACTIVE = 9;
    const STATUS_ACTIVE = 10;
    
    const ERROR_UNKNOWN = 1000;
    const ERROR_ACCESS_DENIED = 1001;
    const ERROR_BAD_PHONE = 1002;
    const ERROR_BAD_DATA = 1003;
    const ERROR_SMS_OFTEN = 1004;
    const ERROR_WRONG_LOGIN_PASSWORD = 1005;

    const APP_ID = "d3d70ebc-5e96-4dc2-a51d-a644f2daaa83";
    const APP_TOKEN = "OGIwMzU3N2MtNWExMS00MDVkLTkzYTUtMTQ0ZTZiYmU4MGIy";

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_INACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DELETED, self::STATUS_UNACTIVATED]],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        return static::findOne(['auth_key' => $token]);
//        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
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
     * Finds user by verification email token
     *
     * @param string $token verify email token
     * @return static|null
     */
    public static function findByVerificationToken($token) {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return md5($password) === $this->password_hash;
//        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Generates new token for email verification
     */
    public function generateEmailVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
    
    function attributes()
    {
        $attributes = parent::attributes();
        $attributes[] = 'role';
        $attributes[] = 'roles';
        return $attributes;
    }
    
    public function getProfile() {
        $user = new \stdClass();
        $user = $this;
        unset($user->password_hash);
        unset($user->password_reset_token);
        unset($user->verification_token);
        
        $roles = [];
        $userAssigned = Yii::$app->authManager->getAssignments($this->id);
        foreach($userAssigned as $userAssign){
            array_push($roles, $userAssign->roleName);
        }
        $user->roles = $roles;
        
        return $user;
    }

    public function getPublicProfile() {
        $user = new \stdClass();
        $user = $this;
        unset($user->password_hash);
        unset($user->password_reset_token);
        unset($user->verification_token);
        // unset($user->status);
        unset($user->auth_key);
        // unset($user->username);
        unset($user->created_at);
        unset($user->updated_at);
        
        $roles = [];
        $userAssigned = Yii::$app->authManager->getAssignments($this->id);
        foreach($userAssigned as $userAssign){
            array_push($roles, $userAssign->roleName);
        }

        $user->roles = $roles;
        
        $connection = Yii::$app->getDb();
        if (in_array('pacient', $roles)) {
            
        } else {
            
        }
        
        return $user;
    }
}
