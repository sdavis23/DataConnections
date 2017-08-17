<?php

namespace App\SuiteComponents;

/*

	A SuiteModelController is the php class responsible for
	commuunicating between the SuiteClient and the model used 
	internally in the mapping application

*/


abstract class SuiteModelController
{

	




	/* 
		Returns an array of the field names to include
		in the SuiteQuery associated with this model

	*/
	abstract protected function getFields();

	/*

		Returns the necessary array representing the linked fields we wish to grab.
		By default: returns an empty array meaning there is no linked data.
	*/
	protected function getLinkedFields()
	{

		return array();
	}

	/*

		Given a json sugar obejct returns the corresponding model

	*/
	abstract protected function sugarObjectToModelObject($json);


	/*
		Returns the suitecrm name of the given module
	*/
	abstract protected function moduleName();

	public function getModelObject($client, $id)
	{
		$result = $client->getModelDataSingle($this->moduleName(), $id, $this->getFields(), $this->getLinkedFields());
		$relations = array();


		//echo "Result: " . print_r($result, true);

		if(count($result->relationship_list) > 0 )
		{
			$relations = $result->relationship_list[0];
		}


		return $this->jsonObjectToModelObject($result->entry_list[0], $relations);
	}

	

	/*

		Returns the list of ModelObjects associated with this
		particular SuiteCRM model.

		client - the object representing a suite client
		query - the WHERE part of a database query in association with the model without the WHERE
			Example: $query = (lng <> 0)
		order_by - the order_by clause of a database query
		max_results - the maximum number of results to return
		
	*/
	public function getModelObjects($client, $query, $order_by, $max_results)
	{
		/*return array_map(	[$this, "jsonObjectToModelObject"], 
							$client->getModelData($this->moduleName(), 
										$query, 
										$order_by, 
										$this->getFields(), 
										$max_results, 
										$this->getLinkedFields())); */

		$entry_value_list = $client->getModelData($this->moduleName(), 
										$query, 
										$order_by, 
										$this->getFields(), 
										$max_results, 
										$this->getLinkedFields());



		//echo "Entry: " . print_r($entry_value_list, true);
		//return print_r($entry_value_list, true);
		return array_map([$this, "jsonObjectToModelObject"], $entry_value_list->entry_list, $entry_value_list->relationship_list);
	
	}

	public function getModelById($id)
	{

		$client = new MainSuiteClient();

		return $this->getModelObject($client, $id);
	}

	/**
     * 
     * @param client - the SuiteCRM client we're getting
     * @param owner_module_name - the name of the module that owns the related module we're after
     * @param relationship_name - the name of the relationship
     * @param query - the raw database query we're after.
     * @param order_by - the orderby claus in the corresponding database query
     * @param max_results - the maximum number of results to return
     * @return the model that corresponds to this particular controller
     */
	public function getRelatedModelObjects($client, $owner_module_name, $owner_id, $relationship_name, $query, $order_by, $max_results)
	{

		$entry_value_list = 
			$client->retrieveRelatedRecords($owner_module_name,
						$owner_id,
						$relationship_name,
						$query,
						$order_by,
						$this->getFields(),
						$max_results,
						$this->getLinkedFields() );

		//echo print_r($entry_value_list, true);

		//return print_r($entry_value_list, true);
		return array_map([$this, "jsonObjectToModelObject"], $entry_value_list->entry_list, $entry_value_list->relationship_list);

	}


	protected function getPrimaryValue($model_array)
	{
		return $model_array['name_value_list'];
	}

	private function suiteRecordToLinkValue($record)
	{
		return $record->link_value;
	}

	private function suiteRelatedRecordToLinkValue($record)
	{

		return $record;

	}

	/*
		Gets the values of the linked fields given in the model_array
			where: $model_array is the JSON struct of the model returned by SUITE
			and relationship_index - is the index of the relationship in

		$relationship_index -> corresponds to the index in the getFields array from which
			the linked values were grabbed.
	*/
	protected function getFirstLinkedValue($model_array, $relationship_index)
	{
		if($this->getAllLinkedValues($model_array, $relationship_index)> 0)
		{

			return $this->getAllLinkedValues($model_array, $relationship_index)[0];

		}

		else
		{
			return array();
		}

			
	}

	protected function getAllLinkedValues($model_array, $relationship_index)
	{
		

		//echo "Linked Field: " . print_r($model_array, true);


		if(isset($model_array['linked_fields']->link_list))
		{

			if(count($model_array['linked_fields']->link_list) > 0)
			{
				return array_map([$this, "suiteRecordToLinkValue"], $model_array['linked_fields']->link_list[$relationship_index]->records);
			}
			else
			{
				return array();
			}			

		}
		else
		{
			
			return array_map([$this, "suiteRelatedRecordToLinkValue"], $model_array['linked_fields'][$relationship_index]->records);
		}

	}

	/*

		Takes in the $json_object that wraps around the actual name_value list
		and 

	*/
	private function jsonObjectToModelObject($json_object, $relationship_list)
	{

		
			//echo print_r($json_object, true);

		return $this->sugarObjectToModelObject(array('name_value_list' => $json_object->name_value_list, 'linked_fields' => $relationship_list));

		
		
	}


}





