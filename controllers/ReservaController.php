<?php

namespace app\controllers;

use Yii;
use app\models\Reserva;
use app\models\ReservaSearch;
use app\models\Rol;
use app\models\Producto;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ReservaController implements the CRUD actions for Reserva model.
 */
class ReservaController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
                
            ],
             'access' => [
                'class' => \yii\filters\AccessControl::className(),
                'ruleConfig' => [
                    'class' => \app\models\AccessRule::className(),
                ],
                'only' => ['index', 'view', 'update', 'delete', 'create'],
                'rules' => [
                    //'class' => AccessRule::className(),
                        [
                        'allow' => true,
                        'actions' => ['index', 'view', 'update', 'delete', 'create'],
                        'roles' => [\app\models\Rol::ROL_ADMIN],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all Reserva models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new ReservaSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Reserva model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $can_edit['editar'] = $this->tiene_rol(Rol::ROL_ADMIN);
        $can_edit['eliminar'] = $this->tiene_rol(Rol::ROL_ADMIN);
        $can_edit['recibir'] = $this->tiene_rol(Rol::ROL_ADMIN) ||
                               $this->tiene_rol(Rol::ROL_GESTOR);
        
        return $this->render('view', [
            'model' => $this->findModel($id),
            'can_edit' => $can_edit,
        ]);
    }

    /**
     * Creates a new Reserva model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($id_modelo = null)
    {
        $model = new Reserva();
        $model->idUsuario = Yii::$app->user->identity->idUsuario;

        $can_edit['idUsuario'] = $this->tiene_rol(Rol::ROL_ADMIN);
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            $model->producto->save();
            
            return $this->redirect(['view', 'id' => $model->idReserva]);
        }

        $productos = null;
        if ($id_modelo != null){
            $productos = Producto::find()
                                 ->where(['idModelo' => $id_modelo])
                                 ->all();
        }
        
        return $this->render('create', [
            'model' => $model,
            'can_edit' => $can_edit,
            'productos' => $productos,
        ]);
    }

    /**
     * Updates an existing Reserva model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        $can_edit['editar'] = $this->tiene_rol(Rol::ROL_ADMIN);
        $can_edit['eliminar'] = $this->tiene_rol(Rol::ROL_ADMIN);
        $can_edit['recibir'] = $this->tiene_rol(Rol::ROL_ADMIN) ||
                               $this->tiene_rol(Rol::ROL_GESTOR);
        
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->idReserva]);
        }

        return $this->render('update', [
            'model' => $model,
            'can_edit' => $can_edit,
        ]);
    }

    /**
     * Deletes an existing Reserva model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Setear que la reserva fue recibida.
     */
    public function actionRecibido($id){
        $model = $this->findModel($id);

        // Debe chequearse que el usuario sea el que creo la reserva o un
        // admin
        if (($this->tiene_rol(Rol::ROL_ADMIN)) or
            (Yii::$app->user->identity->idUsuario == $model->idUsuario)) {
            
            $model->recibido = true;
            $model->save();
        }
        

        return $this->redirect(['view', 'id' => $model->idReserva]);
    } // actionRecibido
    
    /**
     * Finds the Reserva model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Reserva the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Reserva::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    protected function tiene_rol($rol){
        return Yii::$app->user->identity->idRol == $rol;
    }
}
