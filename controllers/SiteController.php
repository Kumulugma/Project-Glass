<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\PasswordResetRequestForm;
use app\models\ResetPasswordForm;
use app\models\UserLog;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Strona główna - przekierowanie do dashboardu
     */
    public function actionIndex()
    {
        if (Yii::$app->user->isGuest) {
            return $this->redirect(['/site/login']);
        }
        
        return $this->redirect(['/dashboard/index']);
    }

    /**
     * Logowanie
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        // Layout dla niezalogowanych
        $this->layout = 'main';

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            Yii::$app->session->setFlash('success', 'Witaj z powrotem, ' . Yii::$app->user->identity->fullName . '!');
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Wylogowanie
     */
    public function actionLogout()
    {
        if (!Yii::$app->user->isGuest) {
            UserLog::logLogout(Yii::$app->user->id);
        }
        
        Yii::$app->user->logout();
        Yii::$app->session->setFlash('info', 'Zostałeś wylogowany.');

        return $this->redirect(['/site/login']);
    }

    /**
     * Żądanie resetu hasła
     */
    public function actionRequestPasswordReset()
    {
        $this->layout = 'main';
        
        $model = new PasswordResetRequestForm();
        
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Sprawdź swoją skrzynkę email. Wysłaliśmy link do resetowania hasła.');
                return $this->redirect(['login']);
            } else {
                Yii::$app->session->setFlash('error', 'Niestety nie udało się wysłać emaila. Spróbuj ponownie później.');
            }
        }

        return $this->render('request-password-reset', [
            'model' => $model,
        ]);
    }

    /**
     * Reset hasła
     */
    public function actionResetPassword($token)
    {
        $this->layout = 'main';
        
        try {
            $model = new ResetPasswordForm($token);
        } catch (\Exception $e) {
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect(['login']);
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'Hasło zostało zmienione. Możesz się teraz zalogować.');
            return $this->redirect(['login']);
        }

        return $this->render('reset-password', [
            'model' => $model,
        ]);
    }
}