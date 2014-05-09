<?php
/**
 * Extension of CActiveRecord, implementing automatically
 * creating of STAT relations for any MANY_MANY or HAS_MANY relations
 * 
 * @author mihanentalpo@yandex.ru
 */
class AutoStatActiveRecord extends CActiveRecord
{
	/**
	 * Array of relations that have their STAT brothers, looking like:
	 * array('relation_name' => 'relation_name_AUTOSTAT')
	 * @var array
	 */
	protected $autoStatedRelations = array();

	/**
	 * Static variable to cache all model()'s calls
	 * @var array
	 */
	protected static $_models = array();

	public static function model($className = __CLASS__)
	{
		if(isset(self::$_models[$className]))
		{
			$returnModel = self::$_models[$className];
		}
		else
		{//If we dosn't have this model cached in the static variable, we'll take it from parent, and make some maniulations :)
			$model = parent::model($className);
			$model->autoStatedRelations = self::augmentMetaData($model->getMetaData());
			$_models[$className] = $model;
			$returnModel = $model;
		}
		return $returnModel;

	}

	/**
	 * Add STAT relations into MetaData object of static model
	 * @staticvar array $usefullFields Array of usefull fields, which are have to be copied from HAS_MANY or MANY_MANY to new STAT relation
	 * @staticvar array $unoverridableField Array of fields, which are have bot to be copied (excludes form $usefullFields array)
	 * @param CActiveRecordMetaData $metaData Object, which would be augmented
	 */
	protected static function augmentMetaData(CActiveRecordMetaData &$metaData)
	{
		$autoStatedRelations=array();

		static $usefullFields = null;
		static $unoverridableField = array('select'=>true,'name'=>true,'className'=>true,'foreignKey'=>true,'order'=>true);
		if (is_null($usefullFields))
		{
			//Creating temprorary CStatRelations, to know, which fields it contains.
			$x = new CStatRelation("x","y","z");
			$fields = get_object_vars($x);
			unset($x);
			$usefullFields = $fields;
		}

		//Going throug relations in search of MANY_MANY and HAS_MANY ones
		foreach($metaData->relations as $name=>$relObj)
		{
			if (get_class($relObj)=='CHasManyRelation' || get_class($relObj)=='CManyManyRelation')
			{
				//new relations would have name appended with _AUTOSTAT
				$newName = $name . "_AUTOSTAT";
				$conf = array(CActiveRecord::STAT, $relObj->className, $relObj->foreignKey);
				$options = get_object_vars($relObj);

				//Unsetting unoverridable fields from options
				foreach($options as $key=>$value)
				{
					if (!isset($usefullFields[$key]) || isset($unoverridableField[$key]))
					{
						unset($options[$key]);
					}
				}
				//Building conf array
				$configArray = array_merge($conf,$options);

				$metaData->addRelation($newName,$configArray);

				$autoStatedRelations[$name] = $newName;
			}

		}

		return $autoStatedRelations;
	}

	/**
	 * Count of elements in a relation (works on HAS_MANY and MANY_MANY).
	 * Of course, the same effect could be achieved by accessing $model->some_relation_AUTOSTAT,
	 * but this function adds some debugging info (with Exceptions)
	 * @param string $relName name of source (without _AUTOSTAT) relation
	 * @return integer
	 */
	public function countRelation($relName)
	{
		if (!isset(static::model()->autoStatedRelations[$relName]))
		{
			$relations = $this->relations();
			if (!isset($relations[$relName]))
			{
				throw new RuntimeException("There are no relation '$relName' in model '" . get_class($this) . "'");
			}
			throw new RuntimeException("There are relation '{$relName} in model '" . get_class($this) . "', but it doesn't have '_AUTOSTAT' relaton built.");
		}
		$realRelName = $relName . "_AUTOSTAT";
		return $this->$realRelName;
	}
}
