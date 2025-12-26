<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\AccessControl;
use yii\data\ActiveDataProvider;
use app\models\User;
use app\models\UserLog;

/**
 * UserController - zarządzanie użytkownikami i profilem
 */
class UserController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'], // Tylko zalogowani
                    ],
                ],
            ],
        ];
    }
    
    /**
     * Profil zalogowanego użytkownika
     */
    public function actionProfile()
    {
        $model = Yii::$app->user->identity;
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            UserLog::log('update_profile', 'Zaktualizowano profil użytkownika');
            Yii::$app->session->setFlash('success', 'Profil został zaktualizowany.');
            return $this->refresh();
        }
        
        return $this->render('profile', [
            'model' => $model,
        ]);
    }
    
    /**
     * Zmiana hasła
     */
    public function actionChangePassword()
    {
        $model = new \app\models\ChangePasswordForm();
        
        if ($model->load(Yii::$app->request->post()) && $model->changePassword()) {
            UserLog::log('change_password', 'Zmieniono hasło');
            Yii::$app->session->setFlash('success', 'Hasło zostało zmienione.');
            return $this->redirect(['profile']);
        }
        
        return $this->render('change-password', [
            'model' => $model,
        ]);
    }
    
    /**
     * Lista użytkowników (tylko admin)
     */
    public function actionIndex()
    {
        if (!Yii::$app->user->identity->isAdmin) {
            throw new NotFoundHttpException('Brak dostępu.');
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => User::find()->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);
        
        // Statystyki
        $stats = [
            'all' => User::find()->count(),
            'active' => User::find()->where(['status' => User::STATUS_ACTIVE])->count(),
            'inactive' => User::find()->where(['status' => User::STATUS_INACTIVE])->count(),
            'admins' => User::find()->where(['role' => User::ROLE_ADMIN])->count(),
        ];
        
        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
        ]);
    }
    
    /**
     * Szczegóły użytkownika (tylko admin)
     */
    public function actionView($id)
    {
        if (!Yii::$app->user->identity->isAdmin) {
            throw new NotFoundHttpException('Brak dostępu.');
        }
        
        $model = $this->findModel($id);
        
        // Ostatnie logi
        $logsProvider = new ActiveDataProvider([
            'query' => UserLog::find()->where(['user_id' => $id])->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 20],
        ]);
        
        return $this->render('view', [
            'model' => $model,
            'logsProvider' => $logsProvider,
        ]);
    }
    
    /**
     * Tworzenie użytkownika (tylko admin)
     */
    public function actionCreate()
    {
        if (!Yii::$app->user->identity->isAdmin) {
            throw new NotFoundHttpException('Brak dostępu.');
        }
        
        $model = new User();
        $model->scenario = 'create';
        
        if ($model->load(Yii::$app->request->post())) {
            $model->setPassword($model->password);
            $model->generateAuthKey();
            
            if ($model->save()) {
                UserLog::log('create_user', "Utworzono użytkownika: {$model->username}", 'user', $model->id);
                Yii::$app->session->setFlash('success', 'Użytkownik został utworzony.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }
        
        return $this->render('create', [
            'model' => $model,
        ]);
    }
    
    /**
     * Edycja użytkownika (tylko admin)
     */
    public function actionUpdate($id)
    {
        if (!Yii::$app->user->identity->isAdmin) {
            throw new NotFoundHttpException('Brak dostępu.');
        }
        
        $model = $this->findModel($id);
        
        if ($model->load(Yii::$app->request->post())) {
            // Jeśli podano nowe hasło
            if (!empty($model->password)) {
                $model->setPassword($model->password);
                $model->generateAuthKey();
            }
            
            if ($model->save()) {
                UserLog::log('update_user', "Zaktualizowano użytkownika: {$model->username}", 'user', $model->id);
                Yii::$app->session->setFlash('success', 'Użytkownik został zaktualizowany.');
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }
        
        $model->password = ''; // Wyczyść hasło dla bezpieczeństwa
        
        return $this->render('update', [
            'model' => $model,
        ]);
    }
    
    /**
     * Usunięcie użytkownika (tylko admin)
     */
    public function actionDelete($id)
    {
        if (!Yii::$app->user->identity->isAdmin) {
            throw new NotFoundHttpException('Brak dostępu.');
        }
        
        $model = $this->findModel($id);
        
        // Nie można usunąć samego siebie
        if ($model->id === Yii::$app->user->id) {
            Yii::$app->session->setFlash('error', 'Nie możesz usunąć swojego konta.');
            return $this->redirect(['index']);
        }
        
        $username = $model->username;
        $model->delete();
        
        UserLog::log('delete_user', "Usunięto użytkownika: {$username}");
        Yii::$app->session->setFlash('success', 'Użytkownik został usunięty.');
        
        return $this->redirect(['index']);
    }
    
    /**
     * Logi użytkowników (tylko admin)
     */
    public function actionLogs($userId = null, $action = null)
    {
        if (!Yii::$app->user->identity->isAdmin) {
            throw new NotFoundHttpException('Brak dostępu.');
        }
        
        $query = UserLog::find()->with('user');
        
        if ($userId) {
            $query->where(['user_id' => $userId]);
        }
        
        if ($action) {
            $query->andWhere(['action' => $action]);
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query->orderBy(['created_at' => SORT_DESC]),
            'pagination' => ['pageSize' => 50],
        ]);
        
        // Statystyki
        $stats = [
            'all' => UserLog::find()->count(),
            'today' => UserLog::find()->where(['>=', 'created_at', strtotime('today')])->count(),
            'week' => UserLog::find()->where(['>=', 'created_at', strtotime('-7 days')])->count(),
        ];
        
        // Lista użytkowników
        $users = User::find()->select(['id', 'username', 'first_name', 'last_name'])->orderBy(['username' => SORT_ASC])->all();
        
        // Lista akcji
        $actions = UserLog::find()->select('action')->distinct()->column();
        
        return $this->render('logs', [
            'dataProvider' => $dataProvider,
            'stats' => $stats,
            'users' => $users,
            'actions' => $actions,
            'selectedUserId' => $userId,
            'selectedAction' => $action,
        ]);
    }
    
    /**
     * Znajduje model użytkownika
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Użytkownik nie istnieje.');
    }
}