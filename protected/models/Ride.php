<?php

/**
 * This is the model class for table "rides".
 *
 * The followings are the available columns in table 'rides':
 * @property integer $id
 * @property integer $driver_fk
 * @property integer $departuretown_fk
 * @property integer $arrivaltown_fk
 * @property string $description
 * @property string $departure
 * @property string $arrival
 * @property integer $seats
 * @property string $startDate
 * @property string $endDate
 * @property string $monday
 * @property string $tuesday
 * @property string $wednesday
 * @property string $thursday
 * @property string $friday
 * @property string $saturday
 * @property string $sunday
 * @property string $visibility
 *
 * The followings are the available model relations:
 * @property Comments[] $comments
 * @property Registrations[] $registrations
 * @property Ridebadges[] $ridebadges
 * @property Towns $departuretownFk
 * @property Towns $arrivaltownFk
 * @property Ride[] $rides
 * @property Users $driverFk
 */
class Ride extends CActiveRecord
{

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'rides';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('driver_fk, departuretown_fk, arrivaltown_fk, seats', 'required'),
			array('driver_fk, departuretown_fk, arrivaltown_fk, seats', 'numerical', 'integerOnly'=>true),
			array('description, departure, arrival, startDate, endDate', 'safe'),
			array('id, driver_fk, departuretown_fk, arrivaltown_fk, description, departure, arrival, seats, startDate, endDate', 'safe', 'on'=>'search'),
			array('departure, arrival', 'required', 'message'=>'Les heures ne sont pas valides'),
			array('startDate, endDate', 'required', 'message'=>'Vous devez choisir une date'),
			array('endDate', 'endDateValidation'),
			array('startDate', 'startDateValidation'),
			array('arrival', 'timeValidation')
		);
	}
	public function timeValidation($attribute)
	{
		if(strtotime($this->arrival)<strtotime($this->departure))
		{
			 $this->addError($attribute, 'L\'heure de la fin du trajet doit ??tre plus grande que l\'heure de d??part');
		}
	}
	public function endDateValidation($attribute)
	{
	     if(strtotime($this->endDate)<strtotime($this->startDate))
	     {
	     	 $this->addError($attribute, 'La date de fin du trajet n\'est pas valide');
	     }
	}
	public function startDateValidation($attribute)
	{
		$date = date("d.m.Y");
		if(strtotime($this->startDate)<strtotime($date))
		{
			 $this->addError($attribute, 'La date du trajet ne doit pas ??tre situ??e dans le pass??');
		}
	}

	protected function afterFind ()
    {
        parent::afterFind ();
    }

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'comments' => array(self::HAS_MANY, 'Comment', 'ride_fk'),
			'registrations' => array(self::HAS_MANY, 'Registration', 'ride_fk'),
			'ridebadges' => array(self::HAS_MANY, 'Ridebadge', 'ride_fk'),
			'departuretown' => array(self::BELONGS_TO, 'Town', 'departuretown_fk'),
			'arrivaltown' => array(self::BELONGS_TO, 'Town', 'arrivaltown_fk'),
			'driver' => array(self::BELONGS_TO, 'User', 'driver_fk'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'driver_fk' => 'Driver Fk',
			'departuretown_fk' => 'Departuretown Fk',
			'arrivaltown_fk' => 'Arrivaltown Fk',
			'description' => 'Description',
			'departure' => 'Departure',
			'arrival' => 'Arrival',
			'seats' => 'Seats',
			'startDate' => 'Start Date',
			'endDate' => 'End Date',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('driver_fk',$this->driver_fk);
		$criteria->compare('departuretown_fk',$this->departuretown_fk);
		$criteria->compare('arrivaltown_fk',$this->arrivaltown_fk);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('departure',$this->departure,true);
		$criteria->compare('arrival',$this->arrival,true);
		$criteria->compare('seats',$this->seats);
		$criteria->compare('startDate',$this->startDate,true);
		$criteria->compare('endDate',$this->endDate,true);
		//$criteria->compare('day',$this->day);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Ride the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
