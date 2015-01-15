<?php

class Api_RidesController extends Controller
{
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column2';

	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
		);
	}

	protected function beforeAction($event)
	{
		$auth = new AuthenticationCheck;
		$auth->checkAuth();
		return true;
	}



	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',
				'actions'=>array('view','create','update', 'list', 'delete'),
				'users'=>array('*'),
			),
			array('allow', // allow admin user to perform 'admin' action
				'actions'=>array('admin'),
				'users'=>array('admin'),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}


	public function actionList(){
			//TODO Ordonner les rides
		$token = $_GET['token'];
		header('Content-type: ' . 'application/json');

		$today = date('Y-m-d 00:00:00', time());

		if (isset($_GET['mine']) && $_GET['mine'] == 'true') { //voit que les trajets où il est inscrit ou qu'il conduit
			$userRequest = User::model()->find('token=:token', array(':token' => $token));
			$rides = Ride::model()->with('driver')->with('departuretown')->with('arrivaltown')->findAll(array('condition' => 'driver_fk=:user_fk and enddate >= :today and visibility = 1', 'limit' => Yii::app()->params['rideListNumber'], 'params' => array(':user_fk'=> $userRequest->id,':today' => $today)));

			$registrations = Registration::model()->with('rideFk')->findAll(array('condition' => 'user_fk=:user_fk and date >= :today and visibility=1', 'params' => array(':user_fk'=> $userRequest->id,':today' => $today)));

			foreach($registrations as $registration) {
				array_push($rides, $registration->rideFk);
			}

			// Trajets triés sur la date de commencement du trajet
			/* TODO imaginer un tri plus pertinant ? Dans le cas où un trajet est disponible depuis longtemps mais qu'on s'y inscrit pour l'occurence dans 1 mois,
			 * ce vieux trajet apparaîtera en premier même si on a des inscriptions avant
			 */
			usort($rides, function( $a, $b ) {
				return strtotime($a["startDate"]) - strtotime($b["endDate"]);
			});

			$array = array();
			foreach ($rides as $ride) {
				$rideArray = array(
					"id" => $ride->id,
					"isDriver" => $ride->driver->id==$userRequest->id,
					"driver" => array("prenom" => $ride->driver->firstname, "nom" => $ride->driver->lastname),
					"departuretown" => array("id" => $ride->departuretown->id, "name" => $ride->departuretown->name),
					"departure" => date("H:i", strtotime($ride->departure)),
					"arrivaltown" => array("id" => $ride->arrivaltown->id, "name" => $ride->arrivaltown->name),
					"arrival" => date("H:i", strtotime($ride->arrival)),
					"startdate" => $ride->startDate,
					"enddate" => $ride->endDate,
					"description" => $ride->description,
					"seats" => $ride->seats,
					"isrecurrence" => $ride->startDate != $ride->endDate,
					"recurrence" => array(
						"monday" => $ride->monday,
						"tuesday" => $ride->tuesday,
						"wednesday" => $ride->wednesday,
						"thursday" => $ride->thursday,
						"friday" => $ride->friday,
						"saturday" => $ride->saturday,
						"sunday" => $ride->sunday
					),
					//"registrations" => $registrationsArray
				);
				array_push($array, $rideArray);
			}

			echo CJSON::encode($array);
		} else if (isset($_GET['q']) && $_GET['q'] != '') { //voit que les trajets dont le nom des villes contient la requête
			$towns = Town::model()->findAll(array('condition' => 'name like :query', 'params' => array(':query'=>'%'.$_GET['q'].'%')));
			var_dump(count($towns));
			$townsArray = array();
			foreach($towns as $t){
				array_push($townsArray, $t->id);
			}
			$criteria = new CDbCriteria;
			$criteria->addInCondition('departuretown_fk', $townsArray, 'OR');
			$criteria->addInCondition('arrivaltown_fk', $townsArray, 'OR');
			$criteria->addCondition('visibility=1','AND');

			$rides = Ride::model()->with('driver')->with('departuretown')->with('arrivaltown')->findAll($criteria);

			$array = array();
			foreach ($rides as $ride) {
				$rideArray = array(
					"id" => $ride->id,
					"driver" => array("prenom" => $ride->driver->firstname, "nom" => $ride->driver->lastname),
					"departuretown" => array("id" => $ride->departuretown->id, "name" => $ride->departuretown->name),
					"departure" => date("H:i", strtotime($ride->departure)),
					"arrivaltown" => array("id" => $ride->arrivaltown->id, "name" => $ride->arrivaltown->name),
					"arrival" => date("H:i", strtotime($ride->arrival)),
					"startdate" => $ride->startDate,
					"enddate" => $ride->endDate,
					"description" => $ride->description,
					"seats" => $ride->seats,
					"isrecurrence" => $ride->startDate != $ride->endDate,
					"recurrence" => array(
						"monday" => $ride->monday,
						"tuesday" => $ride->tuesday,
						"wednesday" => $ride->wednesday,
						"thursday" => $ride->thursday,
						"friday" => $ride->friday,
						"saturday" => $ride->saturday,
						"sunday" => $ride->sunday
					),
				);
				array_push($array, $rideArray);
			}

			echo CJSON::encode($array);
		}else{ //voit tous les trajets sauf ceux qu'il conduit
			$userRequest = User::model()->find('token=:token', array(':token' => $token));
			$rides = Ride::model()->with('driver')->with('departuretown')->with('arrivaltown')->findAll(array('order'=>'t.startdate asc, t.enddate asc, t.departure asc, t.arrival asc, t.id asc','condition' => 'driver_fk!=:user_fk and enddate >= :today and visibility = 1', 'limit' => Yii::app()->params['rideListNumber'], 'params' => array(':user_fk'=>$userRequest->id,':today' => $today)));
			$array = array();
			foreach ($rides as $ride) {
				$rideArray = array(
					"id" => $ride->id,
					"driver" => array("prenom" => $ride->driver->firstname, "nom" => $ride->driver->lastname),
					"departuretown" => array("id" => $ride->departuretown->id, "name" => $ride->departuretown->name),
					"departure" => date("H:i", strtotime($ride->departure)),
					"arrivaltown" => array("id" => $ride->arrivaltown->id, "name" => $ride->arrivaltown->name),
					"arrival" => date("H:i", strtotime($ride->arrival)),
					"startdate" => $ride->startDate,
					"enddate" => $ride->endDate,
					"description" => $ride->description,
					"seats" => $ride->seats,
					"isrecurrence" => $ride->startDate != $ride->endDate,
					"recurrence" => array(
						"monday" => $ride->monday,
						"tuesday" => $ride->tuesday,
						"wednesday" => $ride->wednesday,
						"thursday" => $ride->thursday,
						"friday" => $ride->friday,
						"saturday" => $ride->saturday,
						"sunday" => $ride->sunday
					),
				);
				array_push($array, $rideArray);
			}

			echo CJSON::encode($array);
		}
		Yii::app()->end();
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		header('Content-type: ' . 'application/json');

		$requestedRide = Ride::model()->with('registrations')->with('driver')->find('t.id=:id and visibility=1', array(':id' => $id));
		if($requestedRide != null) {
			$registrationsArray = array($requestedRide->registrations);
			usort($registrationsArray[0], function ($a, $b) {
				return strtotime($a["date"]) - strtotime($b["date"]);
			});
			$rideArray = array(
				"id" => $requestedRide->id,
				"driver" => array("prenom" => $requestedRide->driver->firstname, "nom" => $requestedRide->driver->lastname),
				"departuretown" => array("id" => $requestedRide->departuretown->id, "name" => $requestedRide->departuretown->name),
				"departure" => date("H:i", strtotime($requestedRide->departure)),
				"arrivaltown" => array("id" => $requestedRide->arrivaltown->id, "name" => $requestedRide->arrivaltown->name),
				"arrival" => date("H:i", strtotime($requestedRide->arrival)),
				"startdate" => $requestedRide->startDate,
				"enddate" => $requestedRide->endDate,
				"description" => $requestedRide->description,
				"seats" => $requestedRide->seats,
				"isrecurrence" => $requestedRide->startDate != $requestedRide->endDate,
				"recurrence" => array(
					"monday" => $requestedRide->monday,
					"tuesday" => $requestedRide->tuesday,
					"wednesday" => $requestedRide->wednesday,
					"thursday" => $requestedRide->thursday,
					"friday" => $requestedRide->friday,
					"saturday" => $requestedRide->saturday,
					"sunday" => $requestedRide->sunday
				),
				"registrations" => $registrationsArray
			);

			echo CJSON::encode($rideArray);
		}else{
			header('HTTP/1.1 404');
		}
		Yii::app()->end();
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreate()
	{
		$token = $_GET['token'];
		header('Content-type: ' . 'application/json');

		// TODO effectuer une validation à l'aide d'un regex
		// TODO les valeurs par défaut sont probablement moisies
		$userRequest = User::model()->find('token=:token', array(':token' => $token));
		$ride = new Ride();
		$ride->driver_fk = $userRequest->id;
		$data = CJSON::decode(file_get_contents('php://input'));
		$ride->departuretown_fk = isset($data['departuretown']['id']) ? $data['departuretown']['id'] : 1;
		$ride->departure = isset($data['departure']) ? "1970-01-01 ".$data['departure'] : "";
		$ride->arrivaltown_fk = isset($data['arrivaltown']['id']) ? $data['arrivaltown']['id'] : 1;
		$ride->arrival = isset($data['arrival']) ? "1970-01-01 ".$data['arrival'] : "";
		$ride->startDate = isset($data['startdate']) ? $data['startdate'] : "";
		$ride->endDate = isset($data['enddate']) ? $data['enddate'] : "";
		$ride->description = isset($data['description']) ? $data['description'] : "";
		$ride->seats = isset($data['seats']) ? $data['seats'] : 0;
		$ride->monday =  isset($data['recurrence']['monday']) ? $data['recurrence']['monday'] : 0;
		$ride->tuesday =  isset($data['recurrence']['tuesday']) ? $data['recurrence']['tuesday'] : 0;
		$ride->wednesday =  isset($data['recurrence']['wednesday']) ? $data['recurrence']['wednesday'] : 0;
		$ride->thursday =  isset($data['recurrence']['thursday']) ? $data['recurrence']['thursday'] : 0;
		$ride->friday =  isset($data['recurrence']['friday']) ? $data['recurrence']['friday'] : 0;
		$ride->saturday =  isset($data['recurrence']['saturday']) ? $data['recurrence']['saturday'] : 0;
		$ride->sunday =  isset($data['recurrence']['sunday']) ? $data['recurrence']['sunday'] : 0;
		$ride->visibility =  isset($data['visibility']) ? $data['visibility'] : 1;
		$ride->save();
		if(count($ride->errors)>0){
			header('HTTP/1.1 400');
		}else{
			header('HTTP/1.1 201');
			$registrationsArray = array($ride->registrations);
			usort($registrationsArray[0], function( $a, $b ) {
				return strtotime($a["date"]) - strtotime($b["date"]);
			});
			$rideArray = array(
				"id"=>$ride->id,
				"driver" => array("prenom" => $ride->driver->firstname, "nom" => $ride->driver->lastname),
				"departuretown" => array("id"=>$ride->departuretown->id,"name"=>$ride->departuretown->name),
				"departure"=>date("H:i",strtotime($ride->departure)),
				"arrivaltown" => array("id"=>$ride->arrivaltown->id,"name"=>$ride->arrivaltown->name),
				"arrival"=>date("H:i",strtotime($ride->arrival)),
				"startdate"=>$ride->startDate,
				"enddate"=>$ride->endDate,
				"description"=>$ride->description,
				"seats"=>$ride->seats,
				"isrecurrence"=>$ride->startDate!=$ride->endDate,
				"recurrence" => array(
					"monday" => $ride->monday,
					"tuesday" => $ride->tuesday,
					"wednesday" => $ride->wednesday,
					"thursday" => $ride->thursday,
					"friday" => $ride->friday,
					"saturday" => $ride->saturday,
					"sunday" => $ride->sunday
				),
				"registrations"=> $registrationsArray
			);
			echo CJSON::encode($rideArray);
		}

		Yii::app()->end();
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 * @throws CHttpException
	 */
	public function actionUpdate($id)
	{
		$token = $_GET['token'];
		header('Content-type: ' . 'application/json');
		$userRequest = User::model()->find('token=:token', array(':token' => $token));
		$ride = Ride::model()->find('id=:id and visibility=1', array(':id' => $id));
		if(isset($ride) && $ride->driver_fk == $userRequest->id){
			$data = CJSON::decode(file_get_contents('php://input'));
			$ride->departuretown_fk = isset($data['departuretown']['id']) ? $data['departuretown']['id'] : $ride->departuretown_fk;
			$ride->departure = isset($data['departure']) ? "1970-01-01 ".$data['departure'] : $ride->departure;
			$ride->arrivaltown_fk = isset($data['arrivaltown']['id']) ? $data['arrivaltown']['id'] : $ride->arrivaltown_fk;
			$ride->arrival = isset($data['arrival']) ? "1970-01-01 ".$data['arrival'] : $ride->arrival;
			$ride->startDate = isset($data['startdate']) ? $data['startdate'] : $ride->startDate;
			$ride->endDate = isset($data['enddate']) ? $data['enddate'] : $ride->endDate;
			$ride->description = isset($data['description']) ? $data['description'] : $ride->description;
			$ride->seats = isset($data['seats']) ? $data['seats'] : $ride->seats;
			$ride->monday =  isset($data['recurrence']['monday']) ? $data['recurrence']['monday'] : $ride->monday;
			$ride->tuesday =  isset($data['recurrence']['tuesday']) ? $data['recurrence']['tuesday'] : $ride->tuesday;
			$ride->wednesday =  isset($data['recurrence']['wednesday']) ? $data['recurrence']['wednesday'] : $ride->wednesday;
			$ride->thursday =  isset($data['recurrence']['thursday']) ? $data['recurrence']['thursday'] : $ride->thursday;
			$ride->friday =  isset($data['recurrence']['friday']) ? $data['recurrence']['friday'] : $ride->friday;
			$ride->saturday =  isset($data['recurrence']['saturday']) ? $data['recurrence']['saturday'] : $ride->saturday;
			$ride->sunday =  isset($data['recurrence']['sunday']) ? $data['recurrence']['sunday'] : $ride->sunday;
			$ride->visibility =  isset($data['visibility']) ? $data['visibility'] : $ride->visibility;
			$ride->save(); //Si on met update(), les données ne sont pas revalidées

			if(count($ride->errors)>0){
				header('HTTP/1.1 400');
			}else{
				header('HTTP/1.1 200');
				$registrationsArray = array($ride->registrations);
				usort($registrationsArray[0], function( $a, $b ) {
					return strtotime($a["date"]) - strtotime($b["date"]);
				});
				$rideArray = array(
					"id"=>$ride->id,
					"driver" => array("prenom" => $ride->driver->firstname, "nom" => $ride->driver->lastname),
					"departuretown" => array("id"=>$ride->departuretown->id,"name"=>$ride->departuretown->name),
					"departure"=>date("H:i",strtotime($ride->departure)),
					"arrivaltown" => array("id"=>$ride->arrivaltown->id,"name"=>$ride->arrivaltown->name),
					"arrival"=>date("H:i",strtotime($ride->arrival)),
					"startdate"=>$ride->startDate,
					"enddate"=>$ride->endDate,
					"description"=>$ride->description,
					"seats"=>$ride->seats,
					"isrecurrence"=>$ride->startDate!=$ride->endDate,
					"recurrence" => array(
						"monday" => $ride->monday,
						"tuesday" => $ride->tuesday,
						"wednesday" => $ride->wednesday,
						"thursday" => $ride->thursday,
						"friday" => $ride->friday,
						"saturday" => $ride->saturday,
						"sunday" => $ride->sunday
					),
					"registrations"=> $registrationsArray
				);
				echo CJSON::encode($rideArray);
			}

			Yii::app()->end();
		}else if(!isset($ride)){
			throw new CHttpException(404,'Ride not found.');
		}else {
			throw new CHttpException(403,'You have no rights to update that ride.');
		}
	}

	public function actionDelete($id){
		$token = $_GET['token'];
		$userRequest = User::model()->find('token=:token', array(':token' => $token));
		$ride = Ride::model()->find('id=:id', array(':id' => $id));
		if(null==$ride || $ride->visibility==0){
			throw new CHttpException(404,'The ride doesn\'t exist');
		}

		if($ride->driver->id == $userRequest->id){
			$ride->visibility = 0;
			$ride->update();
		}else{
			throw new CHttpException(403,'You have no rights to delete that ride.');
		}
	}
}